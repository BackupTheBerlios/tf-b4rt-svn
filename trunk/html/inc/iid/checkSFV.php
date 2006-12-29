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
	@header("location: ../../index.php");
	exit();
}

/******************************************************************************/

// common functions
require_once('inc/functions/functions.common.php');

// is enabled ?
if ($cfg["enable_sfvcheck"] != 1) {
	AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to use checkSFV");
	@error("checkSFV is disabled", "index.php?iid=index", "");
}

// target
$dir = getRequestVar('dir');
$file = getRequestVar('file');

// validate dir + file
if (!empty($dir)) {
	if (!isValidPath($dir))
		@error("Invalid dir", "", "", array($dir));
}
if (!empty($file)) {
	if (!isValidPath($file))
		@error("Invalid file", "", "", array($file));
}

// init template-instance
tmplInitializeInstance($cfg["theme"], "page.checkSFV.tmpl");

// process
$cmd = $cfg['bin_cksfv'] . ' -C ' . escapeshellarg($dir) . ' -f ' . escapeshellarg($file);
$handle = popen($cmd . ' 2>&1', 'r' );
$buff = (isset($cfg["debuglevel"]) && $cfg["debuglevel"] == 2)
	? "<strong>Debug:</strong> Evaluating command:<br/><br/><pre>$cmd</pre><br/>Output follows below:<br/>"
	: "";
$buff .= "<pre>";
while (!feof($handle))
	$buff .= @fgets($handle, 30);
$tmpl->setvar('buff', nl2br($buff));
pclose($handle);
$buff.= "</pre>";

// set vars
$tmpl->setvar('iid', $_REQUEST["iid"]);

// parse template
$tmpl->pparse();

?>