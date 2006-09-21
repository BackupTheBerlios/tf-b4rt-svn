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
if (isset($_REQUEST['iid'])) {
	switch($_REQUEST['iid']) {
		default:
		case "index":
			require_once("inc/iid/index.php");
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
		case "rename":
			require_once("inc/iid/rename.php");
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
} else { // this else block is solely for tf 2.1 compat :
	// dispatcher functions
	require_once("inc/functions/functions.dispatcher.php");
	// iid-var
	$_REQUEST['iid'] = "index";

/*******************************************************************************
 * transfer-start
 ******************************************************************************/
/*
if (isset($_REQUEST['torrent'])) {
	$transfer = getRequestVar('torrent');
	if (!empty($transfer)) {
		if ((substr(strtolower($transfer), -8) == ".torrent")) {
			// this is a torrent-client
			$interactiveStart = getRequestVar('interactive');
			if ((isset($interactiveStart)) && ($interactiveStart)) // interactive
				indexStartTorrent($transfer, 1);
			else // silent
				indexStartTorrent($transfer, 0);
		} else if ((substr(strtolower($transfer), -5) == ".wget")) {
			// this is wget.
			require_once("inc/classes/ClientHandler.php");
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg, 'wget');
			$clientHandler->startClient($transfer, 0, false);
			sleep(5);
			header("location: index.php?iid=index");
			exit();
		}
	}
}
*/

/*******************************************************************************
 * get torrent via url
 ******************************************************************************/
/*
if (isset($_REQUEST['url_upload'])) {
	$url_upload = getRequestVar('url_upload');
	if (!empty($url_upload))
		indexProcessDownload($url_upload);
}
*/

/*******************************************************************************
 * file upload
 ******************************************************************************/
/*
if (isset($_FILES['upload_file'])) {
	if(!empty($_FILES['upload_file']['name']))
		indexProcessUpload();
}
*/

/*******************************************************************************
 * del file
 ******************************************************************************/
/*
if (isset($_REQUEST['delfile'])) {
	$transfer = getRequestVar('delfile');
	if (!empty($transfer)) {
		deleteTransfer($transfer, getRequestVar('alias_file'));
		header("location: index.php?iid=index");
		exit();
	}
}
*/

/*******************************************************************************
 * kill
 ******************************************************************************/
/*
if (isset($_REQUEST["kill_torrent"])) {
	$transfer = getRequestVar('kill_torrent');
	if (!empty($transfer)) {
		$return = getRequestVar('return');
		require_once("inc/classes/ClientHandler.php");
		if ((substr(strtolower($transfer), -8) == ".torrent")) {
			// this is a torrent-client
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg, getTransferClient($transfer));
		} else if ((substr(strtolower($transfer), -5) == ".wget")) {
			// this is wget.
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg, 'wget');
		} else {
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg, 'tornado');
		}
		$clientHandler->stopClient($transfer, getRequestVar('alias_file'), "", $return);
		if (!empty($return))
			header("location: ".$return.".php?op=queueSettings");
		else
			header("location: index.php?iid=index");
		exit();
	}
}
*/

/*******************************************************************************
 * deQueue
 ******************************************************************************/
/*
if (isset($_REQUEST["QEntry"])) {
	$QEntry = getRequestVar('QEntry');
	if (!empty($QEntry)) {
		$fluxdQmgr->dequeueTorrent($QEntry, $cfg["user"]);
		header("location: index.php?iid=index");
		exit();
	}
}
*/

	require_once("inc/iid/index.php");
}

?>