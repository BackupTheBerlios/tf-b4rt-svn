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
$tmpl = getTemplateInstance($cfg["theme"], "dereferrer.tmpl");

if (isset($_REQUEST["u"])) {
	$tmpl->setvar('set', 1);
	$tmpl->setvar('head', getHead("dereferrer",false,'0;URL='.$_REQUEST["u"]));
	$tmpl->setvar('main_bgcolor', $cfg["main_bgcolor"]);
	$tmpl->setvar('deref', 1);
	$tmpl->setvar('_REQUEST', $_REQUEST["u"]);
	$tmpl->setvar('foot', getFoot(false));
} else {
	header("location: index.php?iid=index");
	exit();
}

$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('iid', $_GET["iid"]);
$tmpl->pparse();

?>