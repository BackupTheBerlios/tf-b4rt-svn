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

// prevent invocation from web
$bail = 0;
if ((isset($_SERVER['REMOTE_ADDR'])) && ($_SERVER['REMOTE_ADDR'] != ""))
	$bail++;
if ((isset($_SERVER['HTTP_USER_AGENT'])) && ($_SERVER['HTTP_USER_AGENT'] != ""))
	$bail++;
if ($bail > 0) {
	@ob_end_clean();
	exit();
}

/******************************************************************************/

// include path
ini_set('include_path', ini_get('include_path').':../:');

// all functions
require_once('inc/functions/functions.all.php');

// main.core
require_once('inc/main.core.php');

// load default-language
loadLanguageFile($cfg["default_language"]);

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
if (!isset($argv[5]))
	die('Arg Error');
if (!isset($argv[6]))
	die('Arg Error');
if (!isset($argv[7]))
	die('Arg Error');
if (!isset($argv[8]))
	die('Arg Error');

// args
$_URL = $argv[1];
$_ALIAS = $argv[2];
$_PID = $argv[3];
$_OWNER = $argv[4];
$_PATH = $argv[5];
$_LIMIT_RATE = $argv[6];
$_LIMIT_RETRIES = $argv[7];
$_PASV = $argv[8];

// set admin-var
$cfg['isAdmin'] = IsAdmin($_OWNER);

// write out stat-file now
writeStatFile();

/*

-i,  	--input-file=FILE		download URLs found in FILE.

-c,  	--continue				resume getting a partially-downloaded file.

		--http-user=USER		set http user to USER.
		--http-passwd=PASS		set http password to PASS.

*/

// command-string
$command = "cd ".$cfg["path"].$_PATH.";";
$command .= " HOME=".$cfg["path"].$_PATH."/; export HOME;";
if ($cfg["enable_umask"] != 0)
    $command .= " umask 0000;";
if ($cfg["nice_adjust"] != 0)
    $command .= " nice -n ".$cfg["nice_adjust"];
$command .= " ".$cfg['bin_wget'];
if (($_LIMIT_RATE != "") && ($_LIMIT_RATE != "0"))
	$command .= " --limit-rate=" . $_LIMIT_RATE;
if ($_LIMIT_RETRIES != "")
	$command .= " --tries=" . $_LIMIT_RETRIES;
if ($_PASV == 1)
	$command .= " --passive-ftp";
$command .= " -i ".$_URL;
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
		$read = @fread($wget, 1024);
		if (preg_match("/.*Length:(.*) .*/i", $read, $reg)) {
			$header = false;
			$_SIZE = str_replace(',','', $reg[1]);
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
	unset($af);
}

/**
 * processData
 *
 * @param $data
 */
function processData($data){
	global $_URL, $_SIZE, $_COMPLETED, $_PERCENTAGE, $_SPEED, $_STATUS, $_INT_SPEED, $_ETA;
	// completed
	if (@preg_match("/(\d*)K \./i", $data, $reg))
		$_COMPLETED = $reg[1] << 10;
	// percentage + speed
	if (@preg_match("/(\d*)%(\s*)(.*)\/s/i", $data, $reg)) {
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
		if ($_INT_SPEED > 0)
			$_ETA = convertTime((($_SIZE - $_COMPLETED) >> 10) / $_INT_SPEED);
		else
			$_ETA = '-';
	}
	// download done
	if (@preg_match("/.*saved [.*/", $data)) {
		$_STATUS = '0';
		$_SPEED = "0.00 kB/s";
		$_PERCENTAGE = 100;
		$_COMPLETED = $_SIZE;
		$_ETA = "Download Succeeded!";
	}
}

?>