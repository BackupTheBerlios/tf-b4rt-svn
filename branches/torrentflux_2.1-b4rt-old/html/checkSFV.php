<?php

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

	include("config.php");
	include("functions.php");

	// load settings for bin-path
	include_once("settingsfunctions.php");
	loadSettings();

	DisplayHead('sfv check', false);

	// Main BG
	echo "<body bgcolor=".$cfg["main_bgcolor"]." leftmargin=0 topmargin=0 marginwidth=0 marginheight=0>";
	// CHECK SFV

	$cmd = $cfg['bin_cksfv'] . ' -C ' . escapeshellarg($_GET['dir']) . ' -f ' . escapeshellarg($_GET['file']);

	$handle = popen($cmd . ' 2>&1', 'r' );

	while(!feof($handle))
	{
		$buff = fgets($handle,30);
		echo nl2br($buff) ;
		ob_flush();
		flush();
	}
	pclose($handle);
	echo "done";
	echo "</body>";

?>