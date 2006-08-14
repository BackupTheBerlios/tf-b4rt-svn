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


require_once("config.php");
require_once("functions.php");


# create new template
if (!ereg('^[^./][^/]*$', $cfg["theme"])) {
	$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/renameFolder.tmpl");
}
else {
	$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/renameFolder.tmpl");
}

$tmpl->setvar('head', getHead(_REN_TITLE, false));
if((isset($_GET['start'])) && ($_GET['start'] == true)) {
	$tmpl->setvar('is_start', 1);
	$tmpl->setvar('_REN_FILE', _REN_FILE);
	$tmpl->setvar('file', $_GET['file']);
	$tmpl->setvar('_REN_STRING', _REN_STRING);
	$tmpl->setvar('dir', $_GET['dir']);
}
else {
	$cmd = "mv \"".$cfg["path"].$_POST['dir'].$_POST['fileFrom']."\" \"".$cfg["path"].$_POST['dir'].$_POST['fileTo']."\"";
	$cmd .= ' 2>&1';
	$handle = popen($cmd, 'r' );
	// get the output and print it.
	$gotError = -1;
	$buff = fgets($handle);
	$tmpl->setvar('buff', nl2br($buff));
	$gotError = $gotError + 1;
	pclose($handle);
	if($gotError <= 0) {
		$tmpl->setvar('no_error', 1);
		$tmpl->setvar('_REN_DONE', _REN_DONE);
		$tmpl->setvar('fileFrom', $_POST['fileFrom']);
		$tmpl->setvar('fileTo', $_POST['fileTo']);
	}
	$tmpl->setvar('_REN_ERROR', _REN_ERROR);
}
$tmpl->setvar('getTorrentFluxLink', getTorrentFluxLink());
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('index_page', $cfg["index_page"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
# lets parse the hole thing
$tmpl->pparse();
?>