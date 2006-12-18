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

// ids of server-details
$serverIds = array(
	"speedDown",          /*  0 */
	"speedUp",            /*  1 */
	"speedTotal",         /*  2 */
	"cons",               /*  3 */
	"freeSpace",          /*  4 */
	"loadavg",            /*  5 */
	"running",            /*  6 */
	"queued",             /*  7 */
	"speedDownPercent",   /*  8 */
	"speedUpPercent",     /*  9 */
	"driveSpacePercent"   /* 10 */
);
$serverIdCount = count($serverIds);

// ids of transfer-details
$transferIds = array(
	"running",            /*  0 */
	"speedDown",          /*  1 */
	"speedUp",            /*  2 */
	"downCurrent",        /*  3 */
	"upCurrent",          /*  4 */
	"downTotal",          /*  5 */
	"upTotal",            /*  6 */
	"percentDone",        /*  7 */
	"sharing",            /*  8 */
	"eta",                /*  9 */
	"seeds",              /* 10 */
	"peers",              /* 11 */
	"cons"                /* 12 */
);
$transferIdCount = count($transferIds);

// ids of xfer-details
$xferIds = array(
	"xferGlobalTotal",    /* 0 */
	"xferGlobalMonth",    /* 1 */
	"xferGlobalWeek",     /* 2 */
	"xferGlobalDay",      /* 3 */
	"xferUserTotal",      /* 4 */
	"xferUserMonth",      /* 5 */
	"xferUserWeek",       /* 6 */
	"xferUserDay"         /* 7 */
);
$xferIdCount = count($xferIds);

// ids of user-details
$userIds = array(
	"state"               /* 0 */
);
$userIdCount = count($userIds);

// defines
define('_FILE_THIS', $_SERVER['SCRIPT_NAME']);
define('_URL_THIS', 'http://'.$_SERVER['SERVER_NAME']. _FILE_THIS);

// cache
require_once('inc/main.cache.php');

// core-classes
require_once("inc/classes/CoreClasses.php");

// core functions
require_once('inc/functions/functions.core.php');

// stats-functions
require_once('inc/functions/functions.stats.php');

// start session
@session_start();

// config
if ((isset($_SESSION['user'])) && (cacheIsSet($_SESSION['user']))) {
	// db-config
	require_once('inc/config/config.db.php');
	// initialize database
	dbInitialize();
	// init cache
	cacheInit($_SESSION['user']);
	// init transfers-cache
	cacheTransfersInit();
} else {
	// main.core
	require_once('inc/main.core.php');
	// set transfers-cache
	cacheTransfersSet();
}

// public-stats-switch
switch ($cfg['stats_enable_public']) {
	case 1:
		// xfer functions
		if ($cfg['enable_xfer'] == 1)
			require_once('inc/functions/functions.xfer.php');
		// load default-language and transfers if cache not set
		if ((!isset($_SESSION['user'])) || (!(cacheIsSet($_SESSION['user'])))) {
			// common functions
			require_once('inc/functions/functions.common.php');
			// lang file
			loadLanguageFile($cfg["default_language"]);
		}
		// Fluxd
		Fluxd::initialize();
		// Qmgr
		FluxdServiceMod::initializeServiceMod('Qmgr');
		// public stats... show all .. we set the user to superadmin
		$superAdm = GetSuperAdmin();
		if ((isset($superAdm)) && ($superAdm != "")) {
			$cfg["user"] = $superAdm;
			$cfg['isAdmin'] = true;
		} else {
			@ob_end_clean();
			exit();
		}
		break;
	case 0:
	default:
		// main.internal
		require_once("inc/main.internal.php");
}

// -----------------------------------------------------------------------------
// Main
// -----------------------------------------------------------------------------

// header (default)
$header = $cfg['stats_default_header'];

// type (default)
$type = $cfg['stats_default_type'];

// format (default)
$format = $cfg['stats_default_format'];

// send as attachment ? (default)
$sendAsAttachment = $cfg['stats_default_attach'];

// send compressed ? (default)
$sendCompressed = $cfg['stats_default_compress'];

// showUsage-request ?
if (isset($_REQUEST["usage"]))
	sendUsage();

// read params
$gotParams = 0;
if (isset($_REQUEST["h"])) {
    $header = trim($_REQUEST["h"]);
    $gotParams++;
}
if (isset($_REQUEST["t"])) {
    $type = trim($_REQUEST["t"]);
    $gotParams++;
}
if (isset($_REQUEST["f"])) {
    $format = trim($_REQUEST["f"]);
    $gotParams++;
}
if (isset($_REQUEST["a"])) {
	$sendAsAttachment = (int) trim($_REQUEST["a"]);
	$gotParams++;
}
if (isset($_REQUEST["c"])) {
    $sendCompressed = (int) trim($_REQUEST["c"]);
    $gotParams++;
}
if (($cfg['stats_show_usage'] == 1) && ($gotParams == 0))
	sendUsage();

// init global vars
switch ($type) {
    case "all":
    	if (!(($format == "txt") && ($header == 0)))
    		$transferHeads = getTransferListHeadArray();
    	$indent = " ";
		$cfg['xfer_realtime'] = 1;
    	$transferList = getTransferListArray();
    	initServerStats();
    	initXferStats();
    	initUserStats();
    	break;
    case "server":
    	$indent = "";
    	$transferList = getTransferListArray();
    	initServerStats();
    	break;
    case "xfer":
    	$indent = "";
		$cfg['xfer_realtime'] = 1;
    	$transferList = getTransferListArray();
    	initXferStats();
    	break;
    case "transfers":
    	$indent = "";
    	$transferList = getTransferListArray();
    	if (!(($format == "txt") && ($header == 0)))
    		$transferHeads = getTransferListHeadArray();
    	break;
    case "transfer":
		if (! isset($_REQUEST["i"]))
			sendUsage();
		$transferID = trim($_REQUEST["i"]);
    	$indent = "";
    	$transferDetails = getTransferDetails($transferID, false);
    	break;
    case "users":
    	$indent = "";
    	initUserStats();
    	break;
}

// action
switch ($format) {
	case "xml":
		sendXML($type);
	case "rss":
		sendRSS($type);
	case "txt":
		sendTXT($type);
}
exit();

?>