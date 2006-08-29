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

// -----------------------------------------------------------------------------
// init
// -----------------------------------------------------------------------------

// include path
ini_set('include_path', ini_get('include_path').':../:');

// main.common
require_once('inc/main.common.php');

// default-language
require_once("inc/language/".$cfg["default_language"]);

// af
require_once('inc/classes/AliasFile.php');

// some vars
$_STATUS = 1;
$_SIZE = 0;
$_COMPLETED = 0;
$_PERCENTAGE = 0;
$_SPEED = "0.00 kB/s";
$_INT_SPEED = 0;
$_URL = '';
$_REAL_NAME = '';
$_OWNER = '';
$_ETA = '-';

// -----------------------------------------------------------------------------
// Main
// -----------------------------------------------------------------------------

// check args
if (!isset($argv[1]))
	die('Arg Error');
if (!isset($argv[2]))
	die('Arg Error');
if (!isset($argv[3]))
	die('Arg Error');
if (!isset($argv[4]))
	die('Arg Error');

// args
$_URL = $argv[1];
$_ALIAS = $argv[2];
$_PID = $argv[3];
$_OWNER = $argv[4];

// write out stat-file now
writeStatFile();

/*

-i,  	--input-file=FILE		download URLs found in FILE.

-c,  	--continue				resume getting a partially-downloaded file.

		--limit-rate=RATE		limit download rate to RATE.

		--http-user=USER		set http user to USER.
		--http-passwd=PASS		set http password to PASS.

		--passive-ftp			use the "passive" transfer mode.

		--limit-rate=amount
		   Limit the download speed to amount bytes per second.
		   Amount may be expressed in bytes, kilobytes with the k suf­
		   fix, or megabytes with the m suffix.  For example, --limit-rate=20k
		   will limit the retrieval rate to 20KB/s.
		   This kind of thing is useful when, for whatever reason, you don't
		   want Wget to consume the entire available band­width.

*/

// command-string
$command = "cd ".$cfg["path"].$_OWNER.";";
$command .= " HOME=".$cfg["path"].$_OWNER."/; export HOME;";
if ($cfg["enable_umask"] != 0)
    $command .= " umask 0000;";
if ($cfg["nice_adjust"] != 0)
    $command .= " nice -n ".$cfg["nice_adjust"];
$command .= " ".$cfg['bin_wget']." -i ".$_URL;
$command .= " 2>&1"; // direct STDERR to STDOUT
$command .= " & echo $! > ".$_PID; // write pid-file

// start process
$header = true;
$wget = popen($command,'r');
// wait for 0.25 seconds
usleep(250000);
do {
	// read header
	while ($header) {
		// read
		$read = @fread($wget, 80);
		// look for size-string
		if (!(stristr($read, 'Length:') === false)) {
			$header = false;
			$sizeRead = explode(':',trim($read));
			$_SIZE = @trim(array_shift(explode(' ', trim($sizeRead[1]))));
			$_SIZE = @str_replace(",", "", $_SIZE);
			// wait for 0.25 seconds
			usleep(250000);
		}
	}
	// read
	$read = @fread($wget, 2048);
	// process data
	processData($read);
	// write stat file
	writeStatFile();
	// sleep
	sleep(5);

} while (!feof($wget));
pclose($wget);

// Run again afterwards just to make sure it finished writing the file.
$_STATUS = '0';
$_SPEED = "0.00 kB/s";
$_PERCENTAGE = 100;
$_COMPLETED = $_SIZE;
$_ETA = "Download Succeeded!";
writeStatFile();

// update xfer
if ($cfg['enable_xfer'] == 1)
	saveXfer($_OWNER, $_SIZE, 0);

// delete pid-file
@unlink($_PID);

// exit
exit();

/* -------------------------------------------------------------------------- */

/**
 * writeStatFile
 *
 */
function writeStatFile() {
	global $cfg, $_URL, $_SIZE, $_COMPLETED, $_PERCENTAGE, $_SPEED, $_STATUS, $_REAL_NAME, $_INT_SPEED, $_OWNER, $_ALIAS, $_ETA;
    $af = AliasFile::getAliasFileInstance($_ALIAS, $_OWNER, $cfg, 'wget');
	$af->running = $_STATUS;
	$af->percent_done = $_PERCENTAGE;
	$af->down_speed = $_SPEED;
	$af->time_left = $_ETA;
	$af->up_speed = "0.00 kB/s";
	$af->sharing = "0";
	$af->transferowner = $_OWNER;
	$af->seeds = "1";
	$af->peers = "0";
	$af->seedlimit = "0";
	$af->uptotal = "0";
	$af->downtotal = $_COMPLETED;
	$af->size = $_SIZE;
	$af->WriteFile();
}

/**
 * processData
 *
 * @param $data
 */
function processData($data){
	global $_URL, $_SIZE, $_COMPLETED, $_PERCENTAGE, $_SPEED, $_STATUS, $_INT_SPEED, $_ETA;
	// completed
	if(preg_match("/(\d*)K \./i", $data, $reg))
		$_COMPLETED = $reg[1] << 10;
	// sanity-check
	if ((!(isset($_SIZE))) || ($_SIZE <= 0))
		$_SIZE = $_COMPLETED;
	// percentage + speed
	if(preg_match("/(\d*)%(\s*)(.*)\/s/i", $data, $reg)) {
		// percentage
		$_PERCENTAGE = $reg[1];
		// speed
		$_SPEED = $reg[3]."/s";
		// we dont want upper-case k
		$_SPEED = str_replace("KB/s", "kB/s", $_SPEED);
		if (substr($_SPEED, -4) == "kB/s") {
			$_INT_SPEED = substr($_SPEED, 0, strlen($_SPEED) - 5);
		} elseif (substr($_SPEED, -4) == "MB/s"){
			$_INT_SPEED = substr($_SPEED, 0, strlen($_SPEED) - 5);
			$_INT_SPEED = $_INT_SPEED >> 10;
		}
		// ETA
		if($_INT_SPEED > 0)
			$_ETA = convertTime((($_SIZE - $_COMPLETED) >> 10) / $_INT_SPEED);
		else
			$_ETA = '-';
	}
	// download done
	if (preg_match("/.*saved [.*/", $data)) {
		$_STATUS = '0';
		$_SPEED = "0.00 kB/s";
		$_PERCENTAGE = 100;
		$_COMPLETED = $_SIZE;
		$_ETA = "Download Succeeded!";
	}
}

?>