<?php

/* $Id$ */

/*******************************************************************************

 LICENSE

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License (GPL)
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.

 To read the license please visit http://www.gnu.org/copyleft/gpl.html

*******************************************************************************/

/**
 * inits restricted entries array.
 *
 */
function initRestrictedDirEntries() {
	global $cfg, $restrictedFileEntries;
	if ((isset($cfg["dir_restricted"])) && (strlen($cfg["dir_restricted"]) > 0))
		$restrictedFileEntries = split(":", trim($cfg["dir_restricted"]));
	else
		$restrictedFileEntries = array();
}

// Checks for the location of the users directory
// If it does not exist, then it creates it.
function checkUserPath() {
	global $cfg;
	// is there a user dir?
	if (!is_dir($cfg["path"].$cfg["user"])) {
		//Then create it
		mkdir($cfg["path"].$cfg["user"], 0777);
	}
}

// This function returns the extension of a given file.
// Where the extension is the part after the last dot.
// When no dot is found the noExtensionFile string is
// returned. This should point to a 'unknown-type' image
// time by default. This string is also returned when the
// file starts with an dot.
function getExtension($fileName) {
	$noExtensionFile="unknown"; // The return when no extension is found
	//Prepare the loop to find an extension
	$length = -1*(strlen($fileName)); // The maximum negative value for $i
	$i=-1; //The counter which counts back to $length
	//Find the last dot in an string
	while (substr($fileName,$i,1) != "." && $i > $length) {$i -= 1; }
	//Get the extension (with dot)
	$ext = substr($fileName,$i);
	//Decide what to return.
	if (substr($ext,0,1)==".") {$ext = substr($ext,((-1 * strlen($ext))+1)); } else {$ext = $noExtensionFile;}
	//Return the extension
	return strtolower($ext);
}

/**
 * checks if file/dir is valid.
 *
 * @param $fileEntry
 * @return true/false
 */
function isValidEntry($entry) {
	global $restrictedFileEntries;
	// check if dot-entry
	if (substr($entry, 0, 1) == ".")
		return false;
	// check if weirdo macos-entry
	if (substr($entry, 0, 1) == ":")
		return false;
	// check if in restricted array
	if (in_array($entry, $restrictedFileEntries))
		return false;
	// entry ok
	return true;
}

// SFV Check hack
//*************************************************************************
// findSVF()
// This method Builds and displays the Torrent Section of the Index Page
function findSFV($dirName) {
	$sfv = false;
	$d = dir($dirName);
	while (false !== ($entry = $d->read())) {
   		if($entry != '.' && $entry != '..' && !empty($entry) ) {
			if((is_file($dirName.'/'.$entry)) && (strtolower(substr($entry, -4, 4)) == '.sfv')) {
				$sfv[dir] = $dirName;
				$sfv[sfv] = $dirName.'/'.$entry;
			}
	   	}
	}
	$d->close();
	return $sfv;
}

//*************************************************************************
// correctFileName()
// Adds backslashes above special characters to obtain attainable directory
// names for disk usage
function correctFileName ($inName) {
       $replaceItems = array("'", ",", "#", "%", "!", "+", ":", "/", " ", "@", "$", "&", "?", "\"", "(", ")");
       $replacedItems = array("\'", "\,", "\#", "\%", "\!", "\+", "\:", "\/", "\ ", "\@", "\$", "\&", "\?", "\\\"", "\(", "\)");
       $cleanName = str_replace($replaceItems, $replacedItems, $inName);
       return $cleanName;
}

?>