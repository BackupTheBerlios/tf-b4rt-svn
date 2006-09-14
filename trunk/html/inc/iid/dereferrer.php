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

// check param
if (!(isset($_REQUEST["u"]))) {
	header("location: index.php?iid=index");
	exit();
} else {
	$url = $_REQUEST["u"];
}

// create template-instance
$tmpl = tmplGetInstance($cfg["theme"], "dereferrer.tmpl");

// set vars
$tmpl->setvar('url', $url);
$tmpl->setvar('meta_refresh', '0;URL='.$url);
//
$tmpl->setvar('head', getHead("dereferrer", false));
$tmpl->setvar('foot', getFoot(false));
$tmpl->setvar('iid', $_GET["iid"]);
$tmpl->pparse();

?>