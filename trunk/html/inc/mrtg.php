<?php

/* $Id: mrtg.php 189 2006-08-06 20:03:40Z msn_exploder $ */

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

require_once("config.php");
require_once("functions.php");
require_once("lib/vlib/vlibTemplate.php");

$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/mrtg.tmpl");

// request-vars
$mrtgTarget = getRequestVar('mrtg_target');
if($mrtgTarget == '')
  $mrtgTarget = _DEFAULT_TARGET;

// get list of available targets
$mrtgTargets = null;
$htmlTargets = "";
if ($dirHandle = opendir('./mrtg')) {
  $htmlTargets .= '<table width="740" border="0" cellpadding="0" cellspacing="0"><tr><td align="center">';
  $htmlTargets .= '<form name="targetSelector" action="'.$_SERVER['SCRIPT_NAME'].'" method="post">';
  $htmlTargets .= '<select name="mrtg_target" size="1" onChange="submit();">';
  $idx = 0;
  while (false !== ($file = readdir($dirHandle))) {
	if( preg_match("/.*inc/i", $file) ) {
	  $mrtgTargets[$idx] = $file;
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

// get content
$htmlGraph = "";
$filename = "./mrtg/".$mrtgTarget.".inc";
if (file_exists($filename)) {
  $fileHandle = fopen ($filename, "r");
  while (!feof($fileHandle))
	$htmlGraph .= fgets($fileHandle, 4096);
  fclose ($fileHandle);
  // we are only interested in the "real" content
  $htmlGraph = array_shift(explode("_CONTENT_END_",array_pop(explode("_CONTENT_BEGIN_",$htmlGraph))));
  // rewrite image-links
  $htmlGraph = preg_replace('/(.*")(.*)(png".*)/i', '${1}mrtg/${2}${3}', $htmlGraph);
}

// render page content
$tmpl->setvar('head', getHead(_ID_MRTG));
if ((count($mrtgTargets)) > 0) {
	$tmpl->setvar('htmlTargets', $htmlTargets);
}
$tmpl->setvar('main_bgcolor', $cfg["main_bgcolor"]);
$tmpl->setvar('htmlGraph', $htmlGraph);
$tmpl->setvar('foot', getFoot());

$tmpl->pparse();
?>