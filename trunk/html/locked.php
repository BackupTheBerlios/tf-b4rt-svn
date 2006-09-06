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

// main.common
require_once('inc/main.common.php');

/* -------------------------------------------------------------------------- */

// load default-language
loadLanguageFile($cfg["default_language"]);

// default-theme
include("themes/".$cfg["default_theme"]."/index.php");

// vlib
require_once("inc/lib/vlib/vlibTemplate.php");

// create template-instance
$tmpl = getTemplateInstance($cfg["default_theme"], "cookiehelp.tmpl");

# define some things
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('default_theme', $cfg["default_theme"]);
$tmpl->setvar('main_bgcolor', $cfg["main_bgcolor"]);
$tmpl->setvar('table_border_dk', $cfg["table_border_dk"]);
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
$tmpl->setvar('body_data_bg', $cfg["body_data_bg"]);
$tmpl->setvar('iid', 'locked');
# lets parse the hole thing
$tmpl->pparse();

?>