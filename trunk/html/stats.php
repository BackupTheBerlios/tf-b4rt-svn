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

// defines
define('_FILE_THIS', $_SERVER['SCRIPT_NAME']);
define('_URL_THIS', 'http://'.$_SERVER['SERVER_NAME']. _FILE_THIS);

// stats-functions
require_once('inc/functions/functions.stats.php');

// config
if ((isset($_SESSION['user'])) && (isset($_SESSION['cache'][$_SESSION['user']]))) {
	$cfg = $_SESSION['cache'][$_SESSION['user']];
} else {
	// main.core
	require_once('inc/main.core.php');
}

// public-stats-switch
switch ($cfg['stats_enable_public']) {
	case 1:
		// xfer functions
		if ($cfg['enable_xfer'] == 1)
			require_once('inc/functions/functions.xfer.php');
		// load default-language
		loadLanguageFile($cfg["default_language"]);
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

// AliasFile
require_once("inc/classes/AliasFile.php");

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

// init some global vars
switch ($type) {
    case "all":
    	$indent = " ";
    	$transferList = getTransferListArray();
    	if (!(($format == "txt") && ($header == 0)))
    		$transferHeads = getTransferListHeadArray();
    	initServerStats();
    	break;
    case "server":
    	$indent = "";
    	$transferList = getTransferListArray();
    	initServerStats();
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