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

// includes
require_once("config.php");
require_once("functions.php");
require_once("lib/vlib/vlibTemplate.php");

$tmpl = new vlibTemplate("themes/".$cfg["default_theme"]."/tmpl/dereferrer.tmpl");

if (isset($_REQUEST["u"])) {
	$tmpl->setvar('set', 1);
	$tmpl->setvar('head', getHead("dereferrer",false,'0;URL='.$_REQUEST["u"]));
	$tmpl->setvar('main_bgcolor', $cfg["main_bgcolor"]);
	$tmpl->setvar('_REQUEST', $_REQUEST["u"]);
	$tmpl->setvar('foot', getFoot(false,false));
} else {
	header("location: index.php");
	exit();
}
$tmpl->pparse();
?>