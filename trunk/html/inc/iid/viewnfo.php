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

// common functions
require_once('inc/functions/functions.common.php');

// config
loadSettings('tf_settings_dir');

// is enabled ?
if ($cfg["enable_view_nfo"] != 1) {
	AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to use nfo-viewer");
	showErrorPage("nfo-viewer is disabled.");
}

// target-file
$file = getRequestVar("path");
$fileIsValid = (isValidPath($file, ".nfo") || isValidPath($file, ".txt") || isValidPath($file, ".log"));
if (!$fileIsValid) {
	AuditAction($cfg["constants"]["error"], "Invalid NFO-file : ".$cfg["user"]." tried to access ".$file);
	showErrorPage("Invalid NFO-file : <br>".$file);
}

// init template-instance
tmplInitializeInstance($cfg["theme"], "page.viewnfo.tmpl");

// set vars
$tmpl->setvar('file', $file);
$folder = htmlspecialchars(substr($file, 0, strrpos($file, "/" )));
$tmpl->setvar('folder', $folder);
if ($fileHandle = @fopen($cfg["path"].$file,'r')) {
	$output = "";
	while (!@feof($fileHandle))
		$output .= @fgets($fileHandle, 4096);
	@fclose ($fileHandle);
} else {
	$output = "Error opening NFO File.";
}
if ((empty($_REQUEST["dos"]) && empty($_REQUEST["win"])) || !empty($_REQUEST["dos"]))
	$tmpl->setvar('output', htmlentities($output, ENT_COMPAT, "cp866"));
else
	$tmpl->setvar('output', htmlentities($output));
//
tmplSetTitleBar($cfg["pagetitle"].' - View NFO');
tmplSetFoot();
$tmpl->setvar('iid', $_REQUEST["iid"]);

// parse template
$tmpl->pparse();

?>