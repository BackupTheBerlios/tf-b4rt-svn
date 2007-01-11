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

// prevent invocation from web (hopefully on all the php-config-permutations)
if (!empty($_REQUEST)) die();
if (!empty($_GET)) die();
if (!empty($_POST)) die();
if (empty($argv[0])) die();
if (empty($_SERVER['argv'][0])) die();
if ($argv[0] != $_SERVER['argv'][0]) die();

// dummy
$_SESSION = array('cache' => false);

/******************************************************************************/

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

// change to docroot if cwd is in bin.
$cwd = getcwd();
$cwdBase = basename($cwd);
if ($cwdBase == "bin")
	chdir("..");

// include path
ini_set('include_path', ini_get('include_path').':../:');

// main.core
require_once('inc/main.core.php');

// load default-language
loadLanguageFile($cfg["default_language"]);

// some vars
$s_running = 1;
$s_size = 0;
$s_downtotal = 0;
$s_percent_done = 0;
$s_down_speed = "0.00 kB/s";
$s_time_left = '-';
$speed = 0;

// -----------------------------------------------------------------------------
// Main
// -----------------------------------------------------------------------------

// args
$transferFile = $argv[1];
$owner = $argv[2];
$path = $argv[3];
$drate = $argv[4];
$retries = $argv[5];
$pasv = $argv[6];
$transfer = str_replace($cfg['transfer_file_path'], '', $transferFile);

// set admin-var
$cfg['isAdmin'] = IsAdmin($owner);

// re-use sf-object
$sf = new StatFile($transfer, $owner);
$sf->up_speed = "0.00 kB/s";
$sf->sharing = "0";
$sf->transferowner = $owner;
$sf->seeds = "1";
$sf->peers = "1";
$sf->seedlimit = "0";
$sf->uptotal = "0";

// write out stat-file now
writeStatFile();

// command-string
$command = "cd ".$path.";";
$command .= " HOME=".$path."/; export HOME;";
if ($cfg["enable_umask"] != 0)
    $command .= " umask 0000;";
if ($cfg["nice_adjust"] != 0)
    $command .= " nice -n ".$cfg["nice_adjust"];
$command .= " ".$cfg['bin_wget'];
if (($drate != "") && ($drate != "0"))
	$command .= " --limit-rate=" . $drate;
if ($retries != "")
	$command .= " --tries=" . $retries;
if ($pasv == 1)
	$command .= " --passive-ftp";
$command .= " -i ".escapeshellarg($cfg['transfer_file_path'].$transfer);
$command .= " 2>&1"; // direct STDERR to STDOUT
$command .= " & echo $! > ".$cfg['transfer_file_path'].$transfer.".pid"; // write pid-file

// start process
$header = true;
$wget = popen($command,'r');
// wait for 0.25 seconds
usleep(250000);
do {
	// read header
	$ctr = 0;
	while ($ctr < 10) {
		// read
		$read = @fread($wget, 1024);
		if (preg_match("/.*Length:(.*) .*/i", $read, $reg)) {
			$header = false;
			$s_size = str_replace(',','', $reg[1]);
			break;
		} else if (empty($read)) {
			break;
		} else {
			$ctr++;
		}
	}
	// read
	$read = @fread($wget, 2048);
	// process data
	processData($read);
	// write stat file
	writeStatFile();
	// wait
	sleep(5);

} while (!feof($wget));
pclose($wget);

// Run again afterwards just to make sure it finished writing the file.
$s_running = '0';
$s_down_speed = "0.00 kB/s";
$s_percent_done = 100;
if ($s_size > 0)
	$s_downtotal = $s_size;
else
	$s_size = $s_downtotal;
$s_time_left = "Download Succeeded!";
writeStatFile();

// delete pid-file
@unlink($cfg['transfer_file_path'].$transfer.".pid");

// exit
exit();

// -----------------------------------------------------------------------------
// functions
// -----------------------------------------------------------------------------

/**
 * writeStatFile
 *
 */
function writeStatFile() {
	global $cfg, $transfer, $sf, $s_size, $s_downtotal, $s_percent_done, $s_down_speed, $s_running, $speed, $s_time_left;
	$sf->running = $s_running;
	$sf->percent_done = $s_percent_done;
	$sf->down_speed = $s_down_speed;
	$sf->time_left = $s_time_left;
	$sf->downtotal = $s_downtotal;
	$sf->size = ($s_size > 0) ? $s_size : $s_downtotal;
	$sf->write();
}

/**
 * processData
 *
 * @param $data
 */
function processData($data){
	global $transfer, $s_size, $s_downtotal, $s_percent_done, $s_down_speed, $s_running, $speed, $s_time_left;
	// completed
	if (@preg_match("/(\d*)K \./i", $data, $reg))
		$s_downtotal = $reg[1] << 10;
	// percentage + speed
	if (@preg_match("/(\d*)%(\s*)(.*)\/s/i", $data, $reg)) {
		// percentage
		$s_percent_done = $reg[1];
		// speed
		$s_down_speed = $reg[3]."/s";
		// we dont want upper-case k
		$s_down_speed = str_replace("KB/s", "kB/s", $s_down_speed);
		if (substr($s_down_speed, -4) == "kB/s") {
			$speed = substr($s_down_speed, 0, strlen($s_down_speed) - 5);
		} elseif (substr($s_down_speed, -4) == "MB/s"){
			$speed = substr($s_down_speed, 0, strlen($s_down_speed) - 5);
			$speed = $speed >> 10;
		}
		// ETA
		$s_time_left = (($s_size > 0) && ($speed > 0))
			? convertTime((($s_size - $s_downtotal) >> 10) / $speed)
			: '-';
	}
	// download done
	if (@preg_match("/.*saved [.*/", $data)) {
		$s_running = '0';
		$s_down_speed = "0.00 kB/s";
		$s_percent_done = 100;
		if ($s_size > 0)
			$s_downtotal = $s_size;
		else
			$s_size = $s_downtotal;
		$s_time_left = "Download Succeeded!";
	}
}

?>