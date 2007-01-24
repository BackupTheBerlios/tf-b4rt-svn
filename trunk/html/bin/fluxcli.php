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

// change to docroot if cwd is in bin.
$cwd = getcwd();
$cwdBase = basename($cwd);
if ($cwdBase == "bin")
	chdir("..");

// include path
ini_set('include_path', ini_get('include_path').':../:');

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

// set admin-var
$cfg['isAdmin'] = true;

// control to class
FluxCLI::processRequest($argv);

// exit
exit();


































/* ------------------ DEPRECATED -------------------------------------------- */



$action = @$argv[1];
if ((isset($action)) && ($action != "")) {
	switch ($action) {
		case "transfers":
			cliPrintTransfers();
			break;
		case "netstat":
			cliPrintNetStat();
			break;
		case "start":
			cliStartTransfer(@$argv[2]);
			break;
		case "stop":
			cliStopTransfer(@$argv[2]);
			break;
		case "start-all":
			cliStartTransfers();
			break;
		case "resume-all":
			cliResumeTransfers();
			break;
		case "stop-all":
			cliStopTransfers();
			break;
		case "reset":
			cliResetTransfer(@$argv[2]);
			break;
		case "delete":
			cliDeleteTransfer(@$argv[2]);
			break;
		case "wipe":
			cliWipeTransfer(@$argv[2]);
			break;
		case "inject":
			cliInjectTransfer(@$argv[2],@$argv[3]);
			break;
		case "watch":
			cliWatchDir(@$argv[2],@$argv[3]);
			break;
		case "rss":
			cliProcessRssFeed(@$argv[2],@$argv[3],@$argv[4],@$argv[5], @$argv[6]);
			break;
		case "xfer":
			cliXferShutdown(@$argv[2]);
			break;
		case "repair":
			require_once("inc/classes/MaintenanceAndRepair.php");
			MaintenanceAndRepair::repair();
        	break;
		case "maintenance":
			require_once("inc/classes/MaintenanceAndRepair.php");
			MaintenanceAndRepair::maintenance(((isset($argv[2])) && ($argv[2] == "true")) ? true : false);
        	break;
		case "dump":
			cliDumpDatabase(@$argv[2]);
			break;
		case "filelist":
			printFileList((isset($argv[2])) ? $argv[2] : $cfg['docroot'], 1, 1);
			break;
		case "checksums":
			printFileList((isset($argv[2])) ? $argv[2] : $cfg['docroot'], 2, 1);
			break;
		case "version":
		case "-version":
		case "--version":
		case "-v":
			cliPrintVersion();
			break;
		case "help":
		case "--help":
		case "-h":
		default:
			cliPrintUsage();
			break;
	}
} else {
	cliPrintUsage();
}
exit();

?>