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
if ($cfg["enable_rar"] != 1) {
	AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to use rar");
	showErrorPage("rar is disabled.");
}

// init template-instance
tmplInitializeInstance($cfg["theme"], "page.uncomp.tmpl");

// process
if ((isset($_POST['exec'])) && ($_POST['exec'] == true)) {
	$passwd = @ $_POST['passwd'];
	if ($passwd == "")
		$passwd = "-";
	$cmd = $cfg['bin_php']." bin/uncompress.php " .escapeshellarg($_POST['file']) ." ". escapeshellarg($_POST['dir']) ." ". escapeshellarg($_REQUEST['type']);
	if (strcasecmp('rar', $_REQUEST['type']) == 0)
		$cmd .= " ". $cfg['bin_unrar'];
	else if (strcasecmp('zip', $_REQUEST['type']) == 0)
		$cmd .= " ". $cfg['bin_unzip'];
	$cmd .= " ".escapeshellarg($passwd);
	// os-switch
	switch ($cfg["_OS"]) {
		case 1: // linux
			$cmd .= ' 2>&1';
			break;
		case 2: // bsd (snip from khr0n0s)
			$cmd .= ' 2>&1 &';
			break;
	}
	$handle = popen($cmd, 'r' );
	$buff= "";
	while (!feof($handle))
		$buff .= fgets($handle,30);
	$tmpl->setvar('buff', nl2br($buff));
	pclose($handle);
}

// set vars
if ((isset($_REQUEST['file'])) && ($_REQUEST['file'] != "")) {
	$tmpl->setvar('is_file', 1);
	$tmpl->setvar('url_file', str_replace('%2F', '/', urlencode($cfg["path"].$_REQUEST['file'])));
	$tmpl->setvar('url_dir', str_replace('%2F', '/', urlencode($cfg["path"].$_REQUEST['dir'])));
	$tmpl->setvar('type', $_REQUEST['type']);
} else {
	$tmpl->setvar('is_file', 0);
}
//
tmplSetTitleBar('Uncompressing File', false);
$tmpl->setvar('torrentFluxLink', getTorrentFluxLink());
$tmpl->setvar('iid', $_REQUEST["iid"]);

// parse template
$tmpl->pparse();

?>