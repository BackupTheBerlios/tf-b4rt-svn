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
 * Strip the folders from the path
 *
 * @param $path
 * @return string
 */
function StripFolders( $path ) {
	$pos = strrpos( $path, "/" );
	if ($pos === false)
		$pos = 0;
	else
		$pos = $pos +1;
	$path = substr( $path, $pos );
	return $path;
}

/**
 * Convert a timestamp to a duration string
 *
 * @param $timestamp
 * @return string
 */
function duration($timestamp) {
	$years = floor( $timestamp / ( 60 * 60 * 24 * 365 ) );
	$timestamp %= 60 * 60 * 24 * 365;
	$weeks = floor( $timestamp / ( 60 * 60 * 24 * 7 ) );
	$timestamp %= 60 * 60 * 24 * 7;
	$days = floor( $timestamp / ( 60 * 60 * 24 ) );
	$timestamp %= 60 * 60 * 24;
	$hrs = floor( $timestamp / ( 60 * 60 ) );
	$timestamp %= 60 * 60;
	$mins = floor( $timestamp / 60 );
	$secs = $timestamp % 60;
	$str = "";
	if( $years >= 1 )
		$str .= "{$years} years ";
	if( $weeks >= 1 )
		$str .= "{$weeks} weeks ";
	if( $days >= 1 )
		$str .= "{$days} days ";
	if( $hrs >= 1 )
		$str .= "{$hrs} hours ";
	if( $mins >= 1 )
		$str .= "{$mins} minutes ";
	if( $secs >= 1 )
		$str.="{$secs} seconds ";
	return $str;
}

?>