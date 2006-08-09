<?php

/* $Id: checkSFV.php 189 2006-08-06 20:03:40Z msn_exploder $ */

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

require_once("config.php");
require_once("functions.php");
require_once("settingsfunctions.php");
require_once("lib/vlib/vlibTemplate.php");
loadSettings();

$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/checkSFV.tmpl");

$tmpl->setvar('head', getHead('sfv check', false));
$tmpl->setvar('main_bgcolor', $cfg["main_bgcolor"]);

$cmd = $cfg['bin_cksfv'] . ' -C ' . escapeshellarg($_GET['dir']) . ' -f ' . escapeshellarg($_GET['file']);

$handle = popen($cmd . ' 2>&1', 'r' );

$buff = fgets($handle);
$tmpl->setvar('buff', nl2br($buff));

pclose($handle);
$tmpl->pparse();
?>