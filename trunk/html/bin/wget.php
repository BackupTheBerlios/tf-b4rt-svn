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

// declare ticks
declare(ticks = 1);

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

// check args
if ((!isset($argc)) || ($argc < 7))
	die("Arg Error\n");

// change to docroot if cwd is in bin.
$cwd = getcwd();
$cwdBase = basename($cwd);
if ($cwdBase == "bin")
	chdir("..");

// include path
ini_set('include_path', ini_get('include_path').':../:');

// main.core
require_once('inc/main.core.php');

// cache
require_once("inc/main.cache.php");

// common functions
require_once('inc/functions/functions.common.php');

// wget wrapper class
require_once('inc/classes/Wrapper.wget.php');

// load default-language
loadLanguageFile($cfg["default_language"]);

// from here on the wrapper-object takes over
$wrapper = new WrapperWget($argv[1], $argv[2], $argv[3], $argv[4], $argv[5], $argv[6]);
$wrapper->start();

?>