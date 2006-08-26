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

// config
require_once('config.php');
// main
require_once("main.php");
// vlib
require_once("lib/vlib/vlibTemplate.php");

// fluxd
//
// allways use this instance of Fluxd in included pages.
// allways use this boolean for "is fluxd up and running" in included pages.
// allways use this instance of FluxdQmgr in included pages.
// allways use this boolean for "is queue up and running" in included pages.
//
require_once("Fluxd.php");
require_once("Fluxd.ServiceMod.php");
$fluxd = new Fluxd(serialize($cfg));
$fluxdRunning = $fluxd->isFluxdRunning();
$fluxdQmgr = null;
$queueActive = false;
if($cfg["fluxd_Qmgr_enabled"] == 1) {
	if ($fluxd->modState('Qmgr') == 1) {
		$fluxdQmgr = FluxdServiceMod::getFluxdServiceModInstance($cfg, $fluxd, 'Qmgr');
		$queueActive = true;
	}
}

// iid-switch
if(isset($_GET['iid'])) {
	switch($_GET['iid']) {
		default:
			require_once("inc/index.php");
		break;
		case "index":
			require_once("inc/index.php");
		break;
		case "dir":
			require_once("inc/dir.php");
		break;
		case "history":
			require_once("inc/history.php");
		break;
		case "xfer":
			require_once("inc/xfer.php");
		break;
		case "who":
			require_once("inc/who.php");
		break;
		case "viewnfo":
			require_once("inc/viewnfo.php");
		break;
		case "uncomp":
			require_once("inc/uncomp.php");
		break;
		case "torrentSearch":
			require_once("inc/torrentSearch.php");
		break;
		case "startpop":
			require_once("inc/startpop.php");
		break;
		case "renameFolder":
			require_once("inc/renameFolder.php");
		break;
		case "readrss":
			require_once("inc/readrss.php");
		break;
		case "readmsg":
			require_once("inc/readmsg.php");
		break;
		case "profile":
			require_once("inc/profile.php");
		break;
		case "multiup":
			require_once("inc/multiup.php");
		break;
		case "move":
			require_once("inc/move.php");
		break;
		case "mrtg":
			require_once("inc/mrtg.php");
		break;
		case "message":
			require_once("inc/message.php");
		break;
		case "maketorrent":
			require_once("inc/maketorrent.php");
		break;
		case "login":
			require_once("inc/login.php");
		break;
		case "dereferrer":
			require_once("inc/dereferrer.php");
		break;
		case "details":
			require_once("inc/details.php");
		break;
		case "downloaddetails":
			require_once("inc/downloaddetails.php");
		break;
		case "downloadhosts":
			require_once("inc/downloadhosts.php");
		break;
		case "drivespace":
			require_once("inc/drivespace.php");
		break;
		case "cookiehelp":
			require_once("inc/cookiehelp.php");
		break;
		case "checkSFV":
			require_once("inc/checkSFV.php");
		break;
		case "all_services":
			require_once("inc/all_services.php");
		break;
		case "admin":
			require_once("inc/admin.php");
		break;
		case "locked":
			require_once("inc/locked.php");
		break;
	}
} else {
	// use "old" style not to break tools
	require_once("inc/index.php");
}

?>