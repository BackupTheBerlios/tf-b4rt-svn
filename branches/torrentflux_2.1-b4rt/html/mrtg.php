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

define('_DEFAULT_TARGET','traffic');

include_once("config.php");
include_once("functions.php");

// request-vars
$mrtgTarget = getRequestVar('mrtg_target');
if ($mrtgTarget == '')
	$mrtgTarget = _DEFAULT_TARGET;

// content-var
$htmlGraph = "";

// get list of available targets
$mrtgTargets = null;
$htmlTargets = "";
$htmlTargetsCount = 0;
if ($dirHandle = @opendir('./mrtg')) {
	$htmlTargets .= '<table width="740" border="0" cellpadding="0" cellspacing="0"><tr><td align="center">';
	$htmlTargets .= '<form name="targetSelector" action="'.$_SERVER['SCRIPT_NAME'].'" method="post">';
	$htmlTargets .= '<select name="mrtg_target" size="1" onChange="submit();">';
	$idx = 0;
	while (false !== ($file = readdir($dirHandle))) {
		if ((strlen($file) > 4) && (substr(strtolower($file), -4) == ".inc")) {
			$htmlTargetsCount++;
			$mrtgTargets[$idx] = $file;
			$tempAry = explode('.',$file);
			$targetName = array_shift($tempAry);
			$htmlTargets .= '<option value="'.$targetName.'"';
			if ($mrtgTarget == $targetName)
				$htmlTargets .= ' selected';
			$htmlTargets .= '>'.$targetName.'</option>';
			$idx++;
		}
	}
	closedir($dirHandle);
	$htmlTargets .= '</select><input type="submit" value="Change Graph">';
	$htmlTargets .= '</form>';
	$htmlTargets .= '</td></tr></table>'."\n";
}
if ($htmlTargetsCount == 0)
	$htmlGraph = "<br><p><strong>No Graphs found.</strong></p>";

// get content
$filename = "./mrtg/".$mrtgTarget.".inc";
if (file_exists($filename)) {
	$fileHandle = @fopen($filename, "r");
	while (!feof($fileHandle))
		$htmlGraph .= @fgets($fileHandle, 4096);
	@fclose ($fileHandle);
	// we are only interested in the "real" content
	$tempAry = explode("_CONTENT_BEGIN_", $htmlGraph);
	$tempVar = array_pop($tempAry);
	$tempAry = explode("_CONTENT_END_", $tempVar);
	$htmlGraph = array_shift($tempAry);
	// rewrite image-links
	$htmlGraph = preg_replace('/(.*")(.*)(png".*)/i', '${1}mrtg/${2}${3}', $htmlGraph);
}

// render page content
DisplayHead(_ID_MRTG);
if ($htmlTargetsCount > 0)
  echo $htmlTargets;
echo '<div align="center" id="BodyLayer" name="BodyLayer" style="border: thin solid ';
echo $cfg["main_bgcolor"];
echo 'position:relative; width:740; height:500; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
echo $htmlGraph;
echo '</div>';
DisplayFoot();

?>