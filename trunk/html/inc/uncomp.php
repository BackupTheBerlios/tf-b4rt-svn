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

# create new template
if (!ereg('^[^./][^/]*$', $cfg["theme"])) {
	$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/uncomp.tmpl");
}
else {
	$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/uncomp.tmpl");
}

$tmpl->setvar('head', getHead('Uncompressing File', false));
$tmpl->setvar('main_bgcolor', $cfg["main_bgcolor"]);
if((isset($_GET['file'])) && ($_GET['file'] != "")) {
	$tmpl->setvar('is_file', 1);
	$tmpl->setvar('url_file', str_replace('%2F', '/', urlencode($cfg['path'].$_GET['file'])));
	$tmpl->setvar('url_dir', str_replace('%2F', '/', urlencode($cfg['path'].$_GET['dir'])));
	$tmpl->setvar('type', $_GET['type']);
}
if((isset($_POST['exec'])) && ($_POST['exec'] == true)) {
	$passwd = $_POST['passwd'];
	if( $passwd == "") {
		$passwd = "-";
	}
	// @usage		 ./uncompress.php "pathtofile" "extractdir" "typeofcompression" "uncompressor-bin" "password"
	$cmd = $cfg['bin_php']." uncompress.php " .$_POST['file'] ." ". $_POST['dir'] ." ". $_POST['type'];
	if (strcasecmp('rar', $_GET['type']) == 0) {
		$cmd .= " ". $cfg['bin_unrar'];
	} else if (strcasecmp('zip', $_GET['type']) == 0) {
		$cmd .= " ". $cfg['bin_unzip'];
	}
	$cmd .= " ". $passwd;
	// os-switch
	switch (_OS) {
		case 1: // linux
			$cmd .= ' 2>&1';
		break;
		case 2: // bsd (snip from khr0n0s)
			$cmd .= ' 2>&1 &';
		break;
	}
	$handle = popen($cmd, 'r' );
	$buff= "";
	while(!feof($handle)) {
		$buff .= fgets($handle,30);
	}
	$tmpl->setvar('buff', nl2br($buff));
	pclose($handle);
}
$tmpl->setvar('getTorrentFluxLink', getTorrentFluxLink());
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('index_page', $cfg["index_page"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('iid', $_GET["iid"]);
$tmpl->pparse();

?>