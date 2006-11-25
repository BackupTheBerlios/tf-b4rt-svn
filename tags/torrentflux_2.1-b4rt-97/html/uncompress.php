#!/usr/bin/env php
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
 * @author    R.D. Damron
 * @name      rar/zip uncompression
 * @usage	  ./uncompress.php "pathtofile" "extractdir" "typeofcompression" "uncompressor-bin" "password"
 */

$logfile = 'error.log';

//convert and set varibles
$arg1 = urldecode($argv[1]);
$arg2 = urldecode($argv[2]);
$arg3 = $argv[3];
$arg4 = $argv[4];
$arg5 = $argv[5];

// functions
function is_running($PID){
    exec("ps ".escapeshellarg($PID), $ProcessState);
    return(count($ProcessState) >= 2);
}

function kill($PID){
    exec("kill -KILL ".escapeshellarg($PID));
    return true;
}

function del($file){
    exec("rm -rf ".escapeshellarg($file));
    return true;
}

// unrar file
if(strcasecmp('rar', $arg3) == 0){
	if(file_exists($arg2.$logfile))
		del($arg2.$logfile);
    $Command = escapeshellarg($arg4)." x -p". $arg5 ." ". escapeshellarg($arg1) . " " . escapeshellarg($arg2);
	$unrarpid = shell_exec("nohup $Command > " . escapeshellarg($arg2.$logfile) . " 2>&1 & echo $!");
	echo 'Uncompressing file...<BR>PID is: ' . $unrarpid . '<BR>';
	while(is_running($unrarpid)) {
		if(file_exists($arg2.$logfile)) {
			$lines = file($arg2.$logfile);
			foreach($lines as $chkline) {
				if(strpos($chkline, 'already exists. Overwrite it ?') !== FALSE){
					kill($unrarpid);
					echo 'File has already been extracted, please delete extracted file if re-extraction is necessary.';
					break 2;
				}
				if(strpos($chkline, 'Cannot find volume') !== FALSE){
					kill($unrarpid);
					echo 'File has a missing volume and can not been extracted.';
					break 2;
				}
				if(strpos($chkline, 'ERROR: Bad archive') !== FALSE){
					kill($unrarpid);
					echo 'File has a bad volume and can not been extracted.';
					break 2;
				}
				if(strpos($chkline, 'CRC failed') !== FALSE){
					kill($unrarpid);
					echo 'File extraction has failed with a CRC error and was not been extracted.';
					break 2;
				}
			}
		}
	}
	if(file_exists($arg2.$logfile)) {
		$lines = file($arg2.$logfile);
		foreach($lines as $chkline) {
			if(strpos($chkline, 'All OK') !== FALSE){
				echo 'File has successfully been extracted!';
				if(file_exists($arg2.$logfile)) {
					del($arg2.$logfile);
				}
			}
		}
	}
}

// unzip
if(strcasecmp('zip', $arg3) == 0){
	if(file_exists($arg2.$logfile))
		del($arg2.$logfile);
    $Command = escapeshellarg($arg4).' ' . escapeshellarg($arg1) . ' -d ' . escapeshellarg($arg2);
	$unzippid = shell_exec("nohup $Command > " . escapeshellarg($arg2.$logfile) . " 2>&1 & echo $!");
	echo 'Uncompressing file...<BR>PID is: ' . $unzippid . '<BR>';
	while(is_running($unzippid)) {
		/* occupy time to cause popup window load bar to load in conjunction with unzip progress */
	}
}

//debug: echo variables
if(strcasecmp('debug', $arg3) == 0){
	echo $arg1 . '<BR>';
	echo $arg2 . '<BR>';
	echo $arg3 . '<BR>';
	echo $arg4 . '<BR>';
	echo $arg5 . '<BR>';
}
?>