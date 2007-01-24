#!/usr/bin/env php
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

// prevent invocation from web (hopefully on all the php-config-permutations)
if (!empty($_REQUEST)) die();
if (!empty($_GET)) die();
if (!empty($_POST)) die();
if (empty($argv[0])) die();
if (empty($_SERVER['argv'][0])) die();
if ($argv[0] != $_SERVER['argv'][0]) die();

// dummy
$_SESSION = array('cache' => false);

/******************************************************************************/

// change to docroot if needed
if (!is_file(realpath(getcwd().'/inc/main.core.php')))
	chdir(realpath(dirname(__FILE__)."/.."));

// check for home
if (!is_file('inc/main.core.php'))
	exit("Error: this script can only be used in its default-path (DOCROOT/bin/)\n");

// main.core
require_once('inc/main.core.php');

// all functions
require_once('inc/functions/functions.all.php');

// FluxCLI-class
require_once('inc/classes/FluxCLI.php');

// load default-language
loadLanguageFile($cfg["default_language"]);

// transfers-array
initGlobalTransfersArray();

// Fluxd
Fluxd::initialize();

// Qmgr
FluxdServiceMod::initializeServiceMod('Qmgr');

// control to class
FluxCLI::processRequest($argv);

?>