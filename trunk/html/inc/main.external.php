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

// main.core
require_once('inc/main.core.php');

// load default-language
loadLanguageFile($cfg["default_language"]);

// default-theme
include("themes/".$cfg["default_theme"]."/index.php");

// set admin-var
$cfg['isAdmin'] = false;

// vlib
require_once("inc/lib/vlib/vlibTemplate.php");

// check for setup.php and upgrade.php
if (file_exists("setup.php"))
	showErrorPage("Error : <em>setup.php</em> must be deleted.");
if (file_exists("upgrade.php"))
	showErrorPage("Error : <em>upgrade.php</em> must be deleted.");

?>