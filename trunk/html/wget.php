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

include_once('config.php');
include_once('db.php');
include_once("settingsfunctions.php");
include_once("functions.tf.php");
include_once("functions.hacks.php");

// Create Connection + load settings
$db = getdb();
loadSettings();
$cfg["torrent_file_path"] = $cfg["path"].".torrents/";

// some vars
$_STATUS = 1;
$_SIZE = 0;
$_COMPLETED = 0;
$_PERCENTAGE = 0;
$_SPEED = "0.00 kB/s";
//$_INT_SPEED = 0.00;
$_INT_SPEED = 0;
$_URL = '';
$_REAL_NAME = '';
$_OWNER = '';

// check args

// args
$_URL = $argv[1];
$_ALIAS = $argv[2];
$_PID = $argv[3];
$_OWNER = $argv[4];

// write out stat-file now
include_once('AliasFile.php');
write_stat_file(false);

// umask
$umask = "";
if ($cfg["enable_umask"] != 0)
    $umask = " umask 0000;";
// nice
$nice = "";
if ($cfg["nice_adjust"] != 0)
    $nice = " nice -n ".$cfg["nice_adjust"];
// command-string
$command = "cd ".$cfg["path"].$_OWNER.";";
$command .= " HOME=".$cfg["path"].$_OWNER."/; export HOME;";
$command .= $umask;
$command .= $nice;
$command .= " ".$cfg['bin_wget']." -i ".$_URL;
$command .= " 2>&1"; // direct STDERR to STDOUT
$command .= " & echo $! > ".$_PID; // write pid-file
//system('echo command >> /tmp/tflux.debug; echo "'. $command .'" >> /tmp/tflux.debug')

// start process
$wget = popen($command,'r');
do {
	$read = @fread($wget, 2096);
	new_data($read);
	write_stat_file(false);
	sleep(5);
} while (!feof($wget));
pclose($wget);

// Run again afterwards just to make sure it finished writing the file.
$_PERCENTAGE = 100;
$_STATUS = '0';
write_stat_file(true);

// update xfer
if ($cfg['enable_xfer'] == 1)
	saveXfer($_OWNER, 0, $_COMPLETED);

// delete pid-file
@unlink($_PID);

// exit
exit();

/* -------------------------------------------------------------------------- */

/**
 * convert_time
 *
 * @param $seconds
 * @return
 */
function convert_time($seconds){
	$seconds = round($seconds,0);
	if($seconds > 361440){
		$days = $seconds % 361440;
		$seconds -= $days*361440;
	} else {
		$days = "0";
	}
	if($seconds > 3600){
		$hours = $seconds % 3600;
		$seconds -= $days*3600;
	} else {
		$hours = "00";
	}
	if($seconds > 60){
		$minutes = $seconds % 60;
		$seconds -= $days*60;
	} else {
		$minutes = "00";
	}
	if($days > 0)
		return "$days:$hours:$minutes:$seconds";
	else
		return "$hours:$minutes:$seconds";
}

/**
 * write_stat_file
 *
 */
function write_stat_file($completed = false) {
	global $cfg, $_URL, $_SIZE, $_COMPLETED, $_PERCENTAGE, $_SPEED, $_STATUS, $_REAL_NAME, $_INT_SPEED, $_OWNER, $_ALIAS;
    $af = AliasFile::getAliasFileInstance($_ALIAS, $_OWNER, $cfg, 'wget');
	$af->running = $_STATUS;
	$af->percent_done = $_PERCENTAGE;
	if ($completed) {
		$af->time_left = "Download Succeeded!";
		$af->down_speed = "0.00 kB/s";
	} else {
		/*
		if($_INT_SPEED > 0){
		    // because size is 0 this wont work so lets put a fallback here now
			//$af->time_left = convert_time((($_SIZE-$_COMPLETED)/1024)/$_INT_SPEED);
			$af->time_left = '-';
		} else {
			$af->time_left = "Inf".$_INT_SPEED;
		}
		*/
		$af->time_left = '-';
		$af->down_speed = $_SPEED;
	}
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
 * new_data
 *
 * @param $data
 */
function new_data($data){
	global $_URL, $_SIZE, $_COMPLETED, $_PERCENTAGE, $_SPEED, $_STATUS, $_INT_SPEED;
	// Check if they are set first, if they're not its pointless wasting cycles
	// on them as they wont change during the run. Comparisons use less CPU
	// than a Regex
	//if( ($_REAL_NAME == '') && preg_match("/=> `(.*?)'/i",$data,$reg)){
	//	$_REAL_NAME = $reg[1];
	//} else
	if(($_SIZE == '') && preg_match("/Length: (.*?) \(/i",$data,$reg))
		$_SIZE = str_replace(',','',$reg[1]);
	if( preg_match("/(\d*)K \./i",$data,$reg))
		$_COMPLETED = $reg[1]*1024;
	if( preg_match("/(\d*)%(\s*)(.*)\/s/i",$data,$reg)){
		$_PERCENTAGE = $reg[1];
		if ($_PERCENTAGE == 100){
			$_COMPLETED = $_SIZE;
			$_STATUS = '0';
		}
		$_SPEED = $reg[3]."/s";
		if(substr($_SPEED,-4) == "KB/s"){
			$_INT_SPEED = substr($_SPEED,0,strlen($_SPEED)-5);
		} elseif(substr($_SPEED,-4) == "MB/s"){
			$_INT_SPEED = substr($_SPEED,0,strlen($_SPEED)-5);
			$_INT_SPEED = $_INT_SPEED*1024;
		}
	}
	if( preg_match("/- `(.*)' saved [(\d*)\/(\d*)]/",$data,$reg)){
		//var_export($reg);
		$_SIZE = $reg[2];
		$_COMPLETED = $reg[1];
		$_PERCENTAGE = '100';
		$_STATUS = '0';
	}
	// well it better than nothing for now. have to check parsing code.
	$_SIZE = $_COMPLETED;
}


?>