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

// main.internal
require_once("inc/main.internal.php");

// iid-switch
if(isset($_GET['iid'])) {
	switch($_GET['iid']) {
		default:
		case "index":
			require_once("inc/iid/index.php");
			break;
		case "dispatcher":
			require_once("inc/iid/dispatcher.php");
			break;
		case "admin":
			require_once("inc/iid/admin.php");
			break;
		case "dir":
			require_once("inc/iid/dir.php");
			break;
		case "xfer":
			require_once("inc/iid/xfer.php");
			break;
		case "profile":
			require_once("inc/iid/profile.php");
			break;
		case "history":
			require_once("inc/iid/history.php");
			break;
		case "who":
			require_once("inc/iid/who.php");
			break;
		case "viewnfo":
			require_once("inc/iid/viewnfo.php");
			break;
		case "uncomp":
			require_once("inc/iid/uncomp.php");
			break;
		case "torrentSearch":
			require_once("inc/iid/torrentSearch.php");
			break;
		case "startpop":
			require_once("inc/iid/startpop.php");
			break;
		case "renameFolder":
			require_once("inc/iid/renameFolder.php");
			break;
		case "readrss":
			require_once("inc/iid/readrss.php");
			break;
		case "readmsg":
			require_once("inc/iid/readmsg.php");
			break;
		case "multiup":
			require_once("inc/iid/multiup.php");
			break;
		case "move":
			require_once("inc/iid/move.php");
			break;
		case "mrtg":
			require_once("inc/iid/mrtg.php");
			break;
		case "message":
			require_once("inc/iid/message.php");
			break;
		case "maketorrent":
			require_once("inc/iid/maketorrent.php");
			break;
		case "dereferrer":
			require_once("inc/iid/dereferrer.php");
			break;
		case "details":
			require_once("inc/iid/details.php");
			break;
		case "downloaddetails":
			require_once("inc/iid/downloaddetails.php");
			break;
		case "downloadhosts":
			require_once("inc/iid/downloadhosts.php");
			break;
		case "drivespace":
			require_once("inc/iid/drivespace.php");
			break;
		case "cookiehelp":
			require_once("inc/iid/cookiehelp.php");
			break;
		case "checkSFV":
			require_once("inc/iid/checkSFV.php");
			break;
		case "all_services":
			require_once("inc/iid/all_services.php");
			break;
		case "servermon":
			require_once("inc/iid/servermon.php");
			break;
		case "logout":
			require_once("inc/iid/logout.php");
			break;
	}
} else { // use "old" style to stay flux-compatible as good as possible
	require_once("inc/iid/index.php");
}

?>