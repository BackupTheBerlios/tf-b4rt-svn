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

// prevent direct invocation
if (!isset($cfg['user'])) {
	@ob_end_clean();
	header("location: ../../index.php");
	exit();
}

/******************************************************************************/

// create template-instance
$tmpl = tmplGetInstance($cfg["theme"], "page.checkSFV.tmpl");

// process
$cmd = $cfg['bin_cksfv'] . ' -C ' . escapeshellarg($_REQUEST['dir']) . ' -f ' . escapeshellarg($_REQUEST['file']);
$handle = popen($cmd . ' 2>&1', 'r' );
$buff= "";
while(!feof($handle))
	$buff .= @fgets($handle,30);
$tmpl->setvar('buff', nl2br($buff));
pclose($handle);

// set vars
$tmpl->setvar('iid', $_REQUEST["iid"]);

// parse template
$tmpl->pparse();

?>