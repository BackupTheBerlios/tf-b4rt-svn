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
 * writeStatFile
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
		$ch->logMessage($data."\n");
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