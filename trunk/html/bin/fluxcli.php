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

// prevent invocation from web
if (!isset($argv)) die();

/******************************************************************************/

// defines
define('_DUMP_DELIM', '*');
preg_match('|.* (\d+) .*|', '$Revision$', $revisionMatches);
define('_REVISION_FLUXCLI', $revisionMatches[1]);

// change to docroot if cwd is in bin.
$cwd = getcwd();
$cwdBase = basename($cwd);
if ($cwdBase == "bin")
	chdir("..");

// include path
ini_set('include_path', ini_get('include_path').':../:');

// all functions
require_once('inc/functions/functions.all.php');

// main.core
require_once('inc/main.core.php');

// load default-language
loadLanguageFile($cfg["default_language"]);

// classes
require_once("inc/classes/ClientHandler.php");
require_once("inc/classes/AliasFile.php");
require_once("inc/classes/RunningTransfer.php");
require_once("inc/classes/SimpleHTTP.php");
require_once("inc/classes/Rssd.php");
require_once("inc/classes/MaintenanceAndRepair.php");

// config
$cfg["ip"] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = "fluxcli.php/" . _REVISION_FLUXCLI;

// set admin-var
$cfg['isAdmin'] = true;

// -----------------------------------------------------------------------------
// Main
// -----------------------------------------------------------------------------
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
			cliProcessRssFeed(@$argv[2],@$argv[3],@$argv[4],@$argv[5]);
			break;
		case "xfer":
			cliXferShutdown(@$argv[2]);
			break;
		case "repair":
			$mat = MaintenanceAndRepair::getInstance($cfg);
			$mat->repair();
        	exit();
		case "maintenance":
			$mat = MaintenanceAndRepair::getInstance($cfg);
			$mat->maintenance(((isset($argv[2])) && ($argv[2] == "true")) ? true : false);
        	exit();
		case "dump":
			cliDumpDatabase(@$argv[2]);
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