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

// declare ticks
declare(ticks = 1);

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
if ((!isset($argc)) || ($argc < 7))
	die("Arg Error\n");

// change to docroot if cwd is in bin.
$cwd = getcwd();
$cwdBase = basename($cwd);
if ($cwdBase == "bin")
	chdir("..");

// include path
ini_set('include_path', ini_get('include_path').':../:');

// main.core
require_once('inc/main.core.php');

// all functions
require_once('inc/functions/functions.all.php');

// wget functions
require_once('inc/functions/functions.wget.php');

// wget class
require_once('inc/classes/Wrapper.wget.php');

// load default-language
loadLanguageFile($cfg["default_language"]);

// from here on the controller-object takes over
$wrapper = new WrapperWget($argv[1], $argv[2], $argv[3], $argv[4], $argv[5], $argv[6]);
$wrapper->start();

// exit
exit();



























/* --------------------------------------------------------------- DEPRECATED */

// some vars
$s_running = 1;
$s_size = 0;
$s_downtotal = 0;
$s_percent_done = 0;
$s_down_speed = "0.00 kB/s";
$s_time_left = '-';
$speed = 0;

// args
$transferFile = $argv[1];
$owner = $argv[2];
$path = $argv[3];
$drate = $argv[4];
$retries = $argv[5];
$pasv = $argv[6];
$transfer = str_replace($cfg['transfer_file_path'], '', $transferFile);

// clienthandler-object
$ch = ClientHandler::getInstance('wget');
$ch->setVarsFromTransfer($transfer);

// set admin-var
$cfg['isAdmin'] = IsAdmin($owner);

// re-use sf-object
$sf = new StatFile($transfer, $owner);

// write out stat-file now
$sf->up_speed = "0.00 kB/s";
$sf->sharing = "0";
$sf->transferowner = $owner;
$sf->seeds = "1";
$sf->peers = "1";
$sf->seedlimit = "0";
$sf->uptotal = "0";
writeStatFile();

// log
$ch->logMessage("wget.php starting up :\n");
$ch->logMessage(" - transfer : ".$transfer."\n");
$ch->logMessage(" - owner : ".$owner."\n");
$ch->logMessage(" - path : ".$path."\n");
$ch->logMessage(" - drate : ".$drate."\n");
$ch->logMessage(" - retries : ".$retries."\n");
$ch->logMessage(" - pasv : ".$pasv."\n");

// command-string
$command = "cd ".$path.";";
$command .= " HOME=".$path."; export HOME;";
if ($cfg["enable_umask"] != 0)
    $command .= " umask 0000;";
if ($cfg["nice_adjust"] != 0)
    $command .= " nice -n ".$cfg["nice_adjust"];
$command .= " ".$cfg['bin_wget'];
if (($drate != "") && ($drate != "0"))
	$command .= " --limit-rate=" . $drate;
if ($retries != "")
	$command .= " -t ".$retries;
if ($pasv == 1)
	$command .= " -c";
$command .= " --passive-ftp";
$command .= " -i ".escapeshellarg($cfg['transfer_file_path'].$transfer);
$command .= " 2>&1"; // direct STDERR to STDOUT
$command .= " & echo $! > ".$cfg['transfer_file_path'].$transfer.".pid"; // write pid-file

// log
$ch->logMessage("wget.php starting up wget...\n");
$ch->logMessage("executing command : \n".$command."\n", true);

// start process
$wget = popen($command, 'r');

// wait for 0.25 seconds
usleep(250000);

// main-loop
$header = true;
$read = "";
do {
	// read header
	$ctr = 0;
	while ($header) {
		// read
		$read .= @fread($wget, 256);
		if (preg_match("/.*Length: (.*) .*/i", $read, $reg)) {
			$header = false;
			$s_size = str_replace(',','', $reg[1]);
		} else if (empty($read)) {
			$header = false;
		} else {
			if ($ctr > 10)
				$header = false;
			$ctr++;
		}
		// log
		$ch->logMessage($read."\n");
		// wait for 0.25 seconds
		usleep(250000);
	}
	// read
	$read = @fread($wget, 8192);
	// process data
	processData($read);
	// write stat file
	writeStatFile();
	// wait
	sleep(5);
} while (!feof($wget));
pclose($wget);

// log exit
$ch->logMessage("wget.php shutting down...\n");

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

// stop transfer settings
stopTransferSettings($transfer);

// delete pid-file
pidFileDelete();

// log exit
$ch->logMessage("wget.php exit\n");

// exit
exit();

?>