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

// create template-instance
$tmpl = getTemplateInstance($cfg["theme"], "viewnfo.tmpl");

// set vars
$file = $_REQUEST["path"];
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
	$tmpl->setvar('output', htmlentities( $output ));
//
$tmpl->setvar('head', getHead("View NFO"));
$tmpl->setvar('foot', getFoot());
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('iid', $_GET["iid"]);

// parse template
$tmpl->pparse();

?>