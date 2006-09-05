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

// contributed by NovaKing -- thanks duder!

// common functions
require_once('inc/functions/functions.common.php');

// create template-instance
$tmpl = getTemplateInstance($cfg["theme"], "viewnfo.tmpl");

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
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('iid', $_GET["iid"]);
$tmpl->pparse();

?>