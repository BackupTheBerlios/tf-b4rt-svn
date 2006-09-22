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
 * checks if $user has the $permission on $object
 *
 * @param $object
 * @param $user
 * @param $permission
 */
function hasPermission($object, $user, $permission) {
	global $cfg;
	// if homedirs disabled return true
	if ($cfg["enable_home_dirs"] == 0)
		return true;
	// check permission
	switch ($permission) {
		case 'r':
			// public read enabled return true
			if ($cfg["dir_public_read"] == 1)
				return true;
			break;
		case 'w':
			// public write enabled return true
			if ($cfg["dir_public_write"] == 1)
				return true;
			break;
		default:
			return false;
	}
	// check if object in users home-dir
	if (preg_match("/^".$user."/", $object))
		return true;
	// only admin has right
	return $cfg['isAdmin'];
}

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

/**
 * Checks for the location of the incoming directory
 * If it does not exist, then it creates it.
 *
 */
function checkIncomingPath() {
	global $cfg;
	switch ($cfg["enable_home_dirs"]) {
	    case 1:
	    default:
			// is there a user dir?
			checkDirectory($cfg["path"].$cfg["user"], 0777);
	        break;
	    case 0:
			// is there a incoming dir?
			checkDirectory($cfg["path"].$cfg["path_incoming"], 0777);
	        break;
	}
}

/**
 * deletes a dir-entry. recursive process via avddelete
 *
 * @param $del entry to delete
 * @return string with current
 */
function delDirEntry($del) {
	global $cfg;
	$current = "";
	// we need to strip slashes twice in some circumstances
	// Ex.	If we are trying to delete test/tester's file/test.txt
	//	  $del will be "test/tester\\\'s file/test.txt"
	//	  one strip will give us "test/tester\'s file/test.txt
	//	  the second strip will give us the correct
	//		  "test/tester's file/test.txt"
	$del = stripslashes(stripslashes($del));
	if (!ereg("(\.\.\/)", $del)) {
		avddelete($cfg["path"].$del);
		$arTemp = explode("/", $del);
		if (count($arTemp) > 1) {
			array_pop($arTemp);
			$current = implode("/", $arTemp);
		}
		AuditAction($cfg["constants"]["fm_delete"], $del);
	} else {
		AuditAction($cfg["constants"]["error"], "ILLEGAL DELETE: ".$cfg["user"]." tried to delete ".$del);
	}
	return $current;
}

/**
 * This function returns the extension of a given file.
 * Where the extension is the part after the last dot.
 * When no dot is found the noExtensionFile string is
 * returned. This should point to a 'unknown-type' image
 * time by default. This string is also returned when the
 * file starts with an dot.
 *
 * @param $fileName
 * @return
 */
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
	// is set
	if (!(isset($entry)))
		return false;
	// check if empty
	if ((strlen($entry)) < 1)
		return false;
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

/**
 * checks if file is nfo.
 *
 * @param $entry
 * @return 0|1
 */
function isNfo($entry) {
	$subst = substr(strtolower($entry), -4);
	if ($subst == ".nfo")
		return 1;
	if ($subst == ".txt")
		return 1;
	if ($subst == ".log")
		return 1;
	return 0;
}

/**
 * checks if file is rar.
 *
 * @param $entry
 * @return 0|1|2 ; 0 = no match, 1 = rar-file, 2 = zip-file
 */
function isRar($entry) {
	if ((strpos($entry, '.rar') !== FALSE AND strpos($entry, '.Part') === FALSE) OR (strpos($entry, '.part01.rar') !== FALSE ) OR (strpos($entry, '.part1.rar') !== FALSE ))
		return 1;
	if (strpos($entry, '.zip') !== FALSE)
		return 2;
	return 0;
}

/**
 * SFV Check hack
 *
 * @param $dirName
 * @return
 */
function findSFV($dirName) {
	$sfv = false;
	$d = dir($dirName);
	while (false !== ($entry = $d->read())) {
   		if($entry != '.' && $entry != '..' && !empty($entry)) {
			if((isFile($dirName.'/'.$entry)) && (strtolower(substr($entry, -4, 4)) == '.sfv')) {
				$sfv['dir'] = $dirName;
				$sfv['sfv'] = $dirName.'/'.$entry;
			}
	   	}
	}
	$d->close();
	return $sfv;
}

/**
 * recursive chmod
 *
 * @param $path
 * @param $mode
 * @return boolean
 */
function chmodRecursive($path, $mode = 0777) {
	if ((! @is_dir($path)) && (isValidEntry(basename($path))))
		return @chmod($path, $mode);
	$dirHandle = opendir($path);
	while ($file = readdir($dirHandle)) {
		if (isValidEntry(basename($file))) {
			$fullpath = $path.'/'.$file;
			if (! @is_dir($fullpath)) {
				if (! @chmod($fullpath, $mode))
					return false;
			} else {
				if (! chmodRecursive($fullpath, $mode))
					return false;
			}
		}
	}
	closedir($dirHandle);
	if ((isValidEntry(basename($path))) && (@chmod($path, $mode)))
		return true;
	else
		return false;
}

?>