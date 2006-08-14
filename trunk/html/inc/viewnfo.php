<?php

/* $Id$ */

/*************************************************************
*  TorrentFlux - PHP Torrent Manager
*  www.torrentflux.com
**************************************************************/
/*
	This file is part of TorrentFlux.

	TorrentFlux is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	TorrentFlux is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with TorrentFlux; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// contributed by NovaKing -- thanks duder!

require_once("config.php");
require_once("functions.php");


# create new template
if (!ereg('^[^./][^/]*$', $cfg["theme"])) {
	$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/viewnfo.tmpl");
}
else {
	$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/viewnfo.tmpl");
}

$tmpl->setvar('head', getHead("View NFO"));

$file = $_GET["path"];
$folder = htmlspecialchars( substr( $file, 0, strrpos( $file, "/" ) ) );
$tmpl->setvar('folder', $folder);

if( ( $output = @file_get_contents( $cfg["path"] . $file ) ) === false ) {
	$output = "Error opening NFO File.";
}
$tmpl->setvar('file', $file);

if( ( empty( $_GET["dos"] ) && empty( $_GET["win"] ) ) || !empty( $_GET["dos"] ) ) {
	$tmpl->setvar('output', htmlentities( $output, ENT_COMPAT, "cp866" ));
}
else {
	$tmpl->setvar('output', htmlentities( $output ));
}

$tmpl->setvar('foot', getFoot());
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('index_page', $cfg["index_page"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->pparse();
?>