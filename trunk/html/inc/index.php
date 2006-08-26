<?php

/* $Id$ */

/*************************************************************
*  TorrentFlux - PHP Torrent Manager
*  www.torrentflux.com
**************************************************************/
/*
	This file is part of TorrentFlux.

	TorrentFlux is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	TorrentFlux is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with TorrentFlux; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

# create new template
if (!ereg('^[^./][^/]*$', $cfg["theme"])) {
	$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/index.tmpl");
} else {
	$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/index.tmpl");
}

// global fields
$messages = "";

// =============================================================================
// set refresh option into the session cookie
if(array_key_exists("pagerefresh", $_GET)) {
	if($_GET["pagerefresh"] == "false") {
		$_SESSION['prefresh'] = false;
		header("location: index.php?iid=index");
		exit();
	}
	if($_GET["pagerefresh"] == "true") {
		$_SESSION["prefresh"] = true;
		header("location: index.php?iid=index");
		exit();
	}
}

// =============================================================================
// start
$torrent = getRequestVar('torrent');
if(! empty($torrent)) {
	$interactiveStart = getRequestVar('interactive');
	if ((isset($interactiveStart)) && ($interactiveStart)) /* interactive */
		indexStartTorrent($torrent,1);
	else /* silent */
		indexStartTorrent($torrent,0);
}

// =============================================================================
// wget
if ($cfg['enable_wget'] == 1) {
	$url_wget = getRequestVar('url_wget');
	if(! $url_wget == '') {
		include_once("ClientHandler.php");
		$clientHandler = ClientHandler::getClientHandlerInstance($cfg, 'wget');
		$clientHandler->startClient($url_wget, 0, false);
		sleep(5);
		header("location: index.php?iid=index");
		exit();
	}
}

// =============================================================================
// Do they want us to get a torrent via a URL?
$url_upload = getRequestVar('url_upload');
if(! $url_upload == '')
	indexProcessDownload($url_upload);

// =============================================================================
// Handle the file upload if there is one
if(!empty($_FILES['upload_file']['name']))
	indexProcessUpload();

// =============================================================================
// if a file was set to be deleted then delete it
$delfile = getRequestVar('delfile');
if(! $delfile == '') {
	deleteTransfer($delfile, getRequestVar('alias_file'));
	header("location: index.php?iid=index");
	exit();
}

// =============================================================================
// Did the user select the option to kill a running torrent?
$killTorrent = getRequestVar('kill_torrent');
if(! $killTorrent == '') {
	$return = getRequestVar('return');
	include_once("ClientHandler.php");
	if ((substr(strtolower($killTorrent),-8 ) == ".torrent")) {
		// this is a torrent-client
		$clientHandler = ClientHandler::getClientHandlerInstance($cfg, getTransferClient($killTorrent));
	} else if ((substr(strtolower($killTorrent),-5 ) == ".wget")) {
		// this is wget.
		$clientHandler = ClientHandler::getClientHandlerInstance($cfg, 'wget');
	} else {
		$clientHandler = ClientHandler::getClientHandlerInstance($cfg, 'tornado');
	}
	$clientHandler->stopClient($killTorrent, getRequestVar('alias_file'), getRequestVar('kill'), $return);
	if (!empty($return))
		header("location: ".$return.".php?op=queueSettings");
	else
		header("location: index.php?iid=index");
	exit();
}

// =============================================================================
// Did the user select the option to remove a torrent from the Queue?
if(isset($_REQUEST["dQueue"])) {
	$QEntry = getRequestVar('QEntry');
	$fluxdQmgr->dequeueTorrent($QEntry, $cfg['user']);
	header("location: index.php?iid=index");
	exit();
}

// =============================================================================
// init some vars
// =============================================================================
// drivespace
$drivespace = getDriveSpace($cfg["path"]);
// connections
$netstatConnectionsSum = "n/a";
if ($cfg["index_page_connections"] != 0)
	$netstatConnectionsSum = @netstatConnectionsSum();
// loadavg
$loadavgString = "n/a";
if ($cfg["show_server_load"] != 0)
	$loadavgString = @getLoadAverageString();

// =============================================================================
// output
// =============================================================================

// index-page-selection
$tmpl->setvar('index_page', $cfg["index_page"]);
if ($cfg["index_page"] == "b4rt")
	$transferList = getTransferListString();
elseif ($cfg["index_page"] == "tf")
	$transferList = getDirList($cfg["torrent_file_path"]);

// refresh
if ($cfg['ui_indexrefresh'] != "0") {
	if(!isset($_SESSION['prefresh']) || ($_SESSION['prefresh'] == true)) {
		$tmpl->setvar('refresh', 1);
		$tmpl->setvar('page_refresh', $cfg["page_refresh"]);
	}
}

// messages
if ($messages != "")
	$tmpl->setvar('messages', $messages);

// queue
if(!$queueActive) {
	$tmpl->setvar('queueActive', 1);
} else {
	if (IsAdmin())
		$tmpl->setvar('queueActive', 2);
}

// links
if ($cfg["ui_displaylinks"] != "0") {
	$arLinks = array();
	$arLinks = GetLinks();
	if ((isset($arLinks)) && (is_array($arLinks))) {
		$linklist = array();
		foreach($arLinks as $link) {
			array_push($linklist, array(
				'link_url' => $link['url'],
				'link_sitename' => $link['sitename'],
				)
			);
		}
		$tmpl->setloop('linklist', $linklist);
	}
}

// goodlookingstats
if ($cfg["enable_goodlookstats"] != "0") {
	$settingsHackStats = convertByteToArray($cfg["hack_goodlookstats_settings"]);
	if ($settingsHackStats[0] == 1) {
		$tmpl->setvar('settingsHackStats1', 1);
		$tmpl->setvar('settingsHackStats11', @number_format($cfg["total_download"], 2));
	}
	if ($settingsHackStats[1] == 1) {
		$tmpl->setvar('settingsHackStats2', 1);
		$tmpl->setvar('settingsHackStats22', @number_format($cfg["total_upload"], 2));
	}
	if ($settingsHackStats[2] == 1) {
		$tmpl->setvar('settingsHackStats3', 1);
		$tmpl->setvar('settingsHackStats33', @number_format($cfg["total_download"]+$cfg["total_upload"], 2));
	}
	if ($settingsHackStats[3] == 1) {
		$tmpl->setvar('settingsHackStats4', 1);
		$tmpl->setvar('settingsHackStats44', $netstatConnectionsSum);
	}
	if ($settingsHackStats[4] == 1) {
		$tmpl->setvar('settingsHackStats5', 1);
		$tmpl->setvar('settingsHackStats55', formatFreeSpace($cfg["free_space"]));
	}
	if ($settingsHackStats[5] == 1) {
		$tmpl->setvar('settingsHackStats6', 1);
		$tmpl->setvar('settingsHackStats66', $loadavgString);
	}
}

# users
if ($cfg["ui_displayusers"] != "0") {
	$arUsers = GetUsers();
	$arOnlineUsers = array();
	$arOfflineUsers = array();
	for($inx = 0; $inx < count($arUsers); $inx++) {
		if(IsOnline($arUsers[$inx])) {
			array_push($arOnlineUsers, array(
				'user' => $arUsers[$inx],
				'is_on' => 1,
				)
			);
		} else {
			array_push($arOfflineUsers, array(
				'user' => $arUsers[$inx],
				'is_off' => 1,
				)
			);
		}
	}
	$tmpl->setloop('arOnlineUsers', $arOnlineUsers);
	$tmpl->setloop('arOfflineUsers', $arOfflineUsers);
}

// xfer
if (($cfg['enable_xfer'] == 1) && ($cfg['enable_public_xfer'] == 1))
	$tmpl->setvar('enable_xfer', 1);
if (($cfg['enable_xfer'] != 0) && ($cfg['xfer_realtime'] != 0)) {
	$tmpl->setvar('xfer_realtime', 1);
	if ($cfg['xfer_day']) {
		$tmpl->setvar('xfer_day', getXferBar($cfg['xfer_day'],$xfer_total['day']['total'],_XFERTHRU.' Today:'));
	}
	if ($cfg['xfer_week']) {
		$tmpl->setvar('xfer_week', getXferBar($cfg['xfer_week'],$xfer_total['week']['total'],_XFERTHRU.' '.$cfg['week_start'].':'));
	}
	$monthStart = strtotime(date('Y-m-').$cfg['month_start']);
	$monthText = (date('j') < $cfg['month_start']) ? date('M j',strtotime('-1 Day',$monthStart)) : date('M j',strtotime('+1 Month -1 Day',$monthStart));
	if ($cfg['xfer_month']) {
		$tmpl->setvar('xfer_month', getXferBar($cfg['xfer_month'],$xfer_total['month']['total'],_XFERTHRU.' '.$monthText.':'));
	}
	if ($cfg['xfer_total']) {
		$tmpl->setvar('xfer_total', getXferBar($cfg['xfer_total'],$xfer_total['total']['total'],_TOTALXFER.':'));
	}
}

// bigboldwarning
if ($cfg['enable_bigboldwarning'] != "0") {
	//Big bold warning hack by FLX
	if($drivespace >= 98)
		$tmpl->setvar('enable_bigboldwarning', 1);
}
if ($cfg['enable_bigboldwarning'] != "1") {
	if($drivespace >= 98)
		$tmpl->setvar('no_bigboldwarning', 1);
}

// bottom stats
if ($cfg['index_page_stats'] != 0) {
	$tmpl->setvar('index_page_stats', 1);
	if (!array_key_exists("total_download",$cfg)) $cfg["total_download"] = 0;
	if (!array_key_exists("total_upload",$cfg)) $cfg["total_upload"] = 0;
	if (($cfg['enable_xfer'] != 0) && ($cfg['xfer_realtime'] != 0)) {
		$tmpl->setvar('totalxfer1', formatFreeSpace($xfer_total['total']['total']/(1024*1024)));
		$tmpl->setvar('monthxfer1', formatFreeSpace($xfer_total['month']['total']/(1024*1024)));
		$tmpl->setvar('weekxfer1', formatFreeSpace($xfer_total['week']['total']/(1024*1024)));
		$tmpl->setvar('dayxfer1', formatFreeSpace($xfer_total['day']['total']/(1024*1024)));
	}
	if ($queueActive) {
		$tmpl->setvar('queueActive2', 1);
		$tmpl->setvar('_QUEUEMANAGER', _QUEUEMANAGER);
		$runningTransferCount = strval(getRunningTransferCount());
		$tmpl->setvar('runningTransferCount', $runningTransferCount);
		$countQueuedTorrents = strval($fluxdQmgr->countQueuedTorrents());
		$tmpl->setvar('countQueuedTorrents', $countQueuedTorrents);
		$tmpl->setvar('limitGlobal', $cfg["fluxd_Qmgr_maxTotalTorrents"]);
		$tmpl->setvar('limitUser', $cfg["fluxd_Qmgr_maxUserTorrents"]);
	}
	$tmpl->setvar('_OTHERSERVERSTATS', _OTHERSERVERSTATS);
	$sumMaxUpRate = getSumMaxUpRate();
	$sumMaxDownRate = getSumMaxDownRate();
	$sumMaxRate = $sumMaxUpRate + $sumMaxDownRate;
	$tmpl->setvar('downloadspeed1', number_format($cfg["total_download"], 2));
	$tmpl->setvar('downloadspeed11', number_format($sumMaxDownRate, 2));
	$tmpl->setvar('uploadspeed1', number_format($cfg["total_upload"], 2));
	$tmpl->setvar('uploadspeed11', number_format($sumMaxUpRate, 2));
	$tmpl->setvar('totalspeed1', number_format($cfg["total_download"]+$cfg["total_upload"], 2));
	$tmpl->setvar('totalspeed11', number_format($sumMaxRate, 2));
	if ($cfg["index_page_connections"] != 0) {
		$tmpl->setvar('id_connections1', $netstatConnectionsSum);
		$tmpl->setvar('id_connections11', getSumMaxCons());
	}
	$tmpl->setvar('drivespace1', formatFreeSpace($cfg["free_space"]));
	if ($cfg["show_server_load"] != 0) {
		$tmpl->setvar('serverload1', $loadavgString);
	}
	if (($cfg['enable_xfer'] != 0) && ($cfg['xfer_realtime'] != 0)) {
		$tmpl->setvar('_YOURXFERSTATS', _YOURXFERSTATS);
		$tmpl->setvar('total2', formatFreeSpace($xfer[$cfg['user']]['total']['total']/(1024*1024)));
		$tmpl->setvar('month2', formatFreeSpace($xfer[$cfg['user']]['month']['total']/(1024*1024)));
		$tmpl->setvar('week2', formatFreeSpace($xfer[$cfg['user']]['week']['total']/(1024*1024)));
		$tmpl->setvar('day2', formatFreeSpace($xfer[$cfg['user']]['day']['total']/(1024*1024)));
	}
}

// pm
if (IsForceReadMsg())
	$tmpl->setvar('IsForceReadMsg', 1);

// Graphical Bandwidth Bar
$tmpl->setvar('ui_displaybandwidthbars', $cfg["ui_displaybandwidthbars"]);
if ($cfg["ui_displaybandwidthbars"] != 0) {
	$tmpl->setvar('bandwidthbarDown', getDownloadBar());
	$tmpl->setvar('bandwidthbarUp', getUploadBar());
}

# define some things
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('showdirtree', $cfg["showdirtree"]);
$tmpl->setvar('_ABOUTTODELETE', _ABOUTTODELETE);
$tmpl->setvar('enable_sorttable', $cfg["enable_sorttable"]);
$tmpl->setvar('main_bgcolor', $cfg["main_bgcolor"]);
$tmpl->setvar('ui_dim_main_w', $cfg["ui_dim_main_w"]);
$tmpl->setvar('table_border_dk', $cfg["table_border_dk"]);
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
$tmpl->setvar('_SELECTFILE', _SELECTFILE);
$tmpl->setvar('_UPLOAD', _UPLOAD);
$tmpl->setvar('enable_multiupload', $cfg["enable_multiupload"]);
$tmpl->setvar('_MULTIPLE_UPLOAD', _MULTIPLE_UPLOAD);
$tmpl->setvar('_URLFILE', _URLFILE);
$tmpl->setvar('_GETFILE', _GETFILE);
$tmpl->setvar('enable_wget', $cfg["enable_wget"]);
$tmpl->setvar('enable_search', $cfg["enable_search"]);
$tmpl->setvar('_SEARCH', _SEARCH);
$tmpl->setvar('buildSearchEngineDDL', buildSearchEngineDDL($cfg["searchEngine"]));
$tmpl->setvar('table_data_bg', $cfg["table_data_bg"]);
$tmpl->setvar('ui_displaylinks', $cfg["ui_displaylinks"]);
$tmpl->setvar('_TORRENTLINKS', _TORRENTLINKS);
$tmpl->setvar('enable_dereferrer', $cfg["enable_dereferrer"]);
$tmpl->setvar('_URL_DEREFERRER', _URL_DEREFERRER);
$tmpl->setvar('_DOWNLOADSPEED', _DOWNLOADSPEED);
$tmpl->setvar('_UPLOADSPEED', _UPLOADSPEED);
$tmpl->setvar('_TOTALSPEED', _TOTALSPEED);
$tmpl->setvar('_ID_CONNECTIONS', _ID_CONNECTIONS);
$tmpl->setvar('_DRIVESPACE', _DRIVESPACE);
$tmpl->setvar('_SERVERLOAD', _SERVERLOAD);
$tmpl->setvar('ui_displayusers', $cfg["ui_displayusers"]);
$tmpl->setvar('_ONLINE', _ONLINE);
$tmpl->setvar('_OFFLINE', _OFFLINE);
$tmpl->setvar('hide_offline', $cfg["hide_offline"]);
$tmpl->setvar('drivespace', $drivespace);
$tmpl->setvar('enable_mrtg', $cfg["enable_mrtg"]);
$tmpl->setvar('_XFER_USAGE', _XFER_USAGE);
$tmpl->setvar('_ID_MRTG', _ID_MRTG);
$tmpl->setvar('_SERVERSTATS', _SERVERSTATS);
$tmpl->setvar('_ALL', _ALL);
$tmpl->setvar('_DIRECTORYLIST', _DIRECTORYLIST);
$tmpl->setvar('user2', $cfg["user"]);
$tmpl->setvar('formatFreeSpace', formatFreeSpace($cfg["free_space"]));
$tmpl->setvar('transferList', $transferList);
$tmpl->setvar('_TRANSFERDETAILS', _TRANSFERDETAILS);
$tmpl->setvar('_RUNTRANSFER', _RUNTRANSFER);
$tmpl->setvar('_STOPTRANSFER', _STOPTRANSFER);
$tmpl->setvar('_DELQUEUE', _DELQUEUE);
$tmpl->setvar('_SEEDTRANSFER', _SEEDTRANSFER);
$tmpl->setvar('_DELETE', _DELETE);
$tmpl->setvar('enable_torrent_download', $cfg["enable_torrent_download"]);
$tmpl->setvar('enable_multiops', $cfg["enable_multiops"]);
$tmpl->setvar('enable_bulkops', $cfg["enable_bulkops"]);
$tmpl->setvar('_PAGEWILLREFRESH', _PAGEWILLREFRESH);
$tmpl->setvar('_TURNOFFREFRESH', _TURNOFFREFRESH);
$tmpl->setvar('_TURNONREFRESH', _TURNONREFRESH);
$tmpl->setvar('_SECONDS', _SECONDS);
$tmpl->setvar('_WARNING', _WARNING);
$tmpl->setvar('_DRIVESPACEUSED', _DRIVESPACEUSED);
$tmpl->setvar('_SERVERXFERSTATS', _SERVERXFERSTATS);
$tmpl->setvar('_ADMINMESSAGE', _ADMINMESSAGE);
$tmpl->setvar('titleBar', getTitleBar($cfg["pagetitle"]));
$tmpl->setvar('driveSpaceBar', getDriveSpaceBar($drivespace));
$tmpl->setvar('ui_displayfluxlink', $cfg["ui_displayfluxlink"]);
$tmpl->setvar('version', $cfg["version"]);
$tmpl->setvar('ui_dim_superadmin_w', $cfg["ui_dim_superadmin_w"]);
$tmpl->setvar('ui_dim_superadmin_h', $cfg["ui_dim_superadmin_h"]);
$tmpl->setvar('_TOTALXFER', _TOTALXFER);
$tmpl->setvar('_MONTHXFER', _MONTHXFER);
$tmpl->setvar('_WEEKXFER', _WEEKXFER);
$tmpl->setvar('_DAYXFER', _DAYXFER);
$tmpl->setvar('index_page', $cfg["index_page"]);
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
if (isset($_GET["iid"]))
	$tmpl->setvar('iid', $_GET["iid"]);
else
	$tmpl->setvar('iid', 'index');
$tmpl->pparse();
?>