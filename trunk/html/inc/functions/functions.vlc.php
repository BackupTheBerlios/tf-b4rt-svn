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
 * start a vlc-stream
 *
 * @param $host
 * @param $port
 * @param $file
 * @param $vidc
 * @param $vbit
 * @param $audc
 * @param $abit
 */
function vlcStart($host, $port, $file, $vidc, $vbit, $audc, $abit) {
	global $cfg;
	// build command
	$cmd = "nohup";
	$cmd .= " ".$cfg['bin_vlc'];
	$cmd .= " --rc-fake-tty";
	$cmd .= " --sout '#transcode{vcodec=".$vidc.",vb=".$vbit.",scale=1,acodec=".$audc.",ab=".$abit.",channels=2}:std{access=mmsh,mux=asfh,dst=".$host.":".$port."}'";
	$cmd .= " ".escapeshellarg($file);
	$cmd .= " > /dev/null &";
	// DEBUG : log the command
	if ($cfg['debuglevel'] > 1)
		AuditAction($cfg["constants"]["debug"], "vlcStart : ".$cmd);
	// exec command
	exec($cmd);
}

/**
 * stop all streams
 */
function vlcStop() {
	// TERM
	shell_exec("killall -15 vlc > /dev/null");
	// give it 1 second
	sleep(1);
	// KILL
	shell_exec("killall -9 vlc > /dev/null");
	// wait another second
	sleep(1);
}

/**
 * check if a stream is running on host/port
 *
 * @param $host
 * @param $port
 * @return boolean
 */
function vlcIsRunning($host, $port) {
	$fp = false;
	$fp = @fsockopen($host, $port, $errno, $errstr, 1);
	if ($fp === false)
		return false;
	@fclose($fp);
	return true;
}

/**
 * get current running stream
 *
 * @return string
 */
function vlcGetRunningCurrent() {
	global $cfg;
	$retVal = "";
	$vlcPS = trim(shell_exec("ps x -o pid='' -o ppid='' -o command='' -ww | ".$cfg['bin_grep']." ". $cfg['bin_vlc'] ." | ".$cfg['bin_grep']." ".$cfg["vlc_port"]." | ".$cfg['bin_grep']." -v grep"));
	if (strlen($vlcPS > 0)) {
		$tempArray = explode("\n", $vlcPS);
		if ((count($tempArray)) > 0) {
			$streamProcess = array_pop($tempArray);
			$processArray = explode(" ", $streamProcess);
			if ((count($processArray)) > 0) {
				$fileString = array_pop($processArray);
				$fileArray = explode("/", $fileString);
				if ((count($fileArray)) > 0)
					$retVal = array_pop($fileArray);
			}
		}
	}
	return $retVal;
}

?>