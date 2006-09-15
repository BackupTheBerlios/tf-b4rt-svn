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

// common functions
require_once('inc/functions/functions.common.php');

// default-target
define('_DEFAULT_TARGET','traffic');

// create template-instance
$tmpl = tmplGetInstance($cfg["theme"], "mrtg.tmpl");

// request-vars
if (isset($_REQUEST['mrtg_target']))
	$mrtgTarget = getRequestVar('mrtg_target');
else
	$mrtgTarget = _DEFAULT_TARGET;

// set vars
$htmlTargetsCount = 0;
if ($dirHandle = opendir('./mrtg')) {
	$htmlTargets = "";
	$htmlTargets .= '<table width="740" border="0" cellpadding="0" cellspacing="0"><tr><td align="center">';
	$htmlTargets .= '<form name="targetSelector" action="'.$_SERVER['SCRIPT_NAME'].'" method="get">';
	$htmlTargets .= '<input type="hidden" name="iid" value="mrtg">';
	$htmlTargets .= '<select name="mrtg_target" size="1" onChange="submit();">';
	$idx = 0;
	while (false !== ($file = readdir($dirHandle))) {
		if( preg_match("/.*inc/i", $file) ) {
			$htmlTargetsCount++;
			$targetName = array_shift(explode('.',$file));
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
if ($htmlTargetsCount > 0)
	$tmpl->setvar('htmlTargets', $htmlTargets);
else
	$tmpl->setvar('htmlTargets', "");
$filename = "./mrtg/".$mrtgTarget.".inc";
if (file_exists($filename)) {
	$htmlGraph = "";
	$fileHandle = fopen ($filename, "r");
	while (!feof($fileHandle))
		$htmlGraph .= fgets($fileHandle, 4096);
	fclose ($fileHandle);
	// we are only interested in the "real" content
	$htmlGraph = array_shift(explode("_CONTENT_END_", array_pop(explode("_CONTENT_BEGIN_", $htmlGraph))));
	// rewrite image-links
	$htmlGraph = preg_replace('/(.*")(.*)(png".*)/i', '${1}mrtg/${2}${3}', $htmlGraph);
	// set var
	$tmpl->setvar('htmlGraph', $htmlGraph);
} else {
	$tmpl->setvar('htmlGraph', "");
}
//
tmplSetTitleBar($cfg["pagetitle"].' - '.$cfg['_ID_MRTG']);
tmplSetFoot();
$tmpl->setvar('iid', $_GET["iid"]);

// parse template
$tmpl->pparse();

?>