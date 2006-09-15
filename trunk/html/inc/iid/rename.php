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

// create template-instance
$tmpl = tmplGetInstance($cfg["theme"], "rename.tmpl");

// process move and set vars
if ((isset($_REQUEST['start'])) && ($_REQUEST['start'] == true)) {
	$tmpl->setvar('is_start', 1);
	$tmpl->setvar('file', $_REQUEST['file']);
	$tmpl->setvar('dir', $_REQUEST['dir']);
	$tmpl->setvar('_REN_FILE', $cfg['_REN_FILE']);
	$tmpl->setvar('_REN_STRING', $cfg['_REN_STRING']);
} else {
	$tmpl->setvar('is_start', 0);
	$cmd = "mv \"".$cfg["path"].$_POST['dir'].$_POST['fileFrom']."\" \"".$cfg["path"].$_POST['dir'].$_POST['fileTo']."\"";
	$cmd .= ' 2>&1';
	$handle = popen($cmd, 'r' );
	$gotError = -1;
	$buff = fgets($handle);
	$gotError = $gotError + 1;
	pclose($handle);
	$tmpl->setvar('buff', nl2br($buff));
	if ($gotError <= 0) {
		$tmpl->setvar('no_error', 1);
		$tmpl->setvar('fileFrom', $_POST['fileFrom']);
		$tmpl->setvar('fileTo', $_POST['fileTo']);
		$tmpl->setvar('_REN_DONE', $cfg['_REN_DONE']);
	} else {
		$tmpl->setvar('no_error', 0);
		$tmpl->setvar('_REN_ERROR', $cfg['_REN_ERROR']);
	}
}
//
tmplSetTitleBar($cfg['_REN_TITLE'], false);
$tmpl->setvar('getTorrentFluxLink', getTorrentFluxLink());
$tmpl->setvar('iid', $_GET["iid"]);

// parse template
$tmpl->pparse();

?>