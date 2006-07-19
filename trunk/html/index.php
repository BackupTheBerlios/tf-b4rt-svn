<?php

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

require_once("config.php");
require_once("functions.php");
require_once("lib/vlib/vlibTemplate.php");

# create new template
$tmpl = new vlibTemplate("themes/".$cfg["default_theme"]."/tmpl/index.tmpl");

// global fields
$messages = "";

// =============================================================================
// set refresh option into the session cookie
if(array_key_exists("pagerefresh", $_GET)) {
	if($_GET["pagerefresh"] == "false") {
		$_SESSION['prefresh'] = false;
		header("location: index.php");
		exit();
	}
	if($_GET["pagerefresh"] == "true") {
		$_SESSION["prefresh"] = true;
		header("location: index.php");
		exit();
	}
}

// =============================================================================
// queue-check
$queueActive = false;
if ($cfg["AllowQueing"]) {
	include_once("QueueManager.php");
	$queueManager = QueueManager::getQueueManagerInstance($cfg);
	if (! $queueManager->isQueueManagerRunning()) {
		if (($queueManager->prepareQueueManager()) && ($queueManager->startQueueManager())) {
			$queueActive = true;
		} else {
			AuditAction($cfg["constants"]["error"], "Error starting Queue Manager");
			if (IsAdmin())
				header("location: admin.php?op=queueSettings");
			else
				header("location: index.php");
			exit();
		}
	} else {
		$queueActive = true;
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
	// <DD32>:
	if(! $url_wget == '') {
		exec("nohup ".$cfg['bin_php']." -f wget.php ".$url_wget." ".$cfg['user']." > /dev/null &");
		sleep(2); //sleep so that hopefully the other script has time to write out the stat files.
		header("location: index.php");
		exit();
	}
	// </DD32>
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
	deleteTorrent($delfile, getRequestVar('alias_file'));
	header("location: index.php");
	exit();
}

// =============================================================================
// Did the user select the option to kill a running torrent?
$killTorrent = getRequestVar('kill_torrent');
if(! $killTorrent == '') {
	$return = getRequestVar('return');
	include_once("ClientHandler.php");
	$clientHandler = ClientHandler::getClientHandlerInstance($cfg, getTorrentClient($killTorrent));
	$clientHandler->stopTorrentClient($killTorrent, getRequestVar('alias_file'), getRequestVar('kill'), $return);
	if (!empty($return))
		header("location: ".$return.".php?op=queueSettings");
	else
		header("location: index.php");
	exit();
}

// =============================================================================
// Did the user select the option to remove a torrent from the Queue?
if(isset($_REQUEST["dQueue"])) {
	$QEntry = getRequestVar('QEntry');
	include_once("QueueManager.php");
	$queueManager = QueueManager::getQueueManagerInstance($cfg);
	$queueManager->dequeueTorrent($QEntry);
	header("location: index.php");
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

if(! isset($_SESSION['user'])) {
	header('location: login.php');
	exit();
}

$tmpl->setvar('index_page', $cfg["index_page"]);
if ($cfg["index_page"] == "b4rt") {
	$transferList = getTransferList();
}
elseif ($cfg["index_page"] == "tf") {
	$transferList = getDirList($cfg["torrent_file_path"]);

}

if ($cfg['ui_indexrefresh'] != "0") {
	if(!isset($_SESSION['prefresh']) || ($_SESSION['prefresh'] == true)) {
		$tmpl->setvar('refresh', 1);
		$tmpl->setvar('page_refresh', $cfg["page_refresh"]);
	}
}

if ($messages != "") {
	$tmpl->setvar('messages', $messages);
}

if(! $queueActive) {
	$tmpl->setvar('queueActive', 1);
}
else {
	if ( IsAdmin() ) {
		$tmpl->setvar('queueActive', 2);
	}
}

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

if ($cfg["enable_goodlookstats"] != "0") {
	$settingsHackStats = convertByteToArray($cfg["hack_goodlookstats_settings"]);
	if ($settingsHackStats[0] == 1) {
		$tmpl->setvar('settingsHackStats1', 1);
		$tmpl->setvar('settingsHackStats11', number_format($cfg["total_download"], 2));
	}
	if ($settingsHackStats[1] == 1) {
		$tmpl->setvar('settingsHackStats2', 1);
		$tmpl->setvar('settingsHackStats22', number_format($cfg["total_upload"], 2));
	}
	if ($settingsHackStats[2] == 1) {
		$tmpl->setvar('settingsHackStats3', 1);
		$tmpl->setvar('settingsHackStats33', number_format($cfg["total_download"]+$cfg["total_upload"], 2));
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
				)
			);
			$tmpl->setloop('arOnlineUsers', $arOnlineUsers);
		}
		else {
			array_push($arOfflineUsers, array(
				'user' => $arUsers[$inx],
				)
			);
			$tmpl->setloop('arOfflineUsers', $arOfflineUsers);
		}
	}
}

if (($cfg['enable_xfer'] == 1) && ($cfg['enable_public_xfer'] == 1)) {
	$tmpl->setvar('enable_xfer', 1);
}

if (($cfg['enable_xfer'] != 0) && ($cfg['xfer_realtime'] != 0)) {
	$tmpl->setvar('xfer_realtime', 1);
	if ($cfg['xfer_day']) {
		$tmpl->setvar('xfer_day', displayXferBar($cfg['xfer_day'],$xfer_total['day']['total'],_XFERTHRU.' Today:'));
	}
	if ($cfg['xfer_week']) {
		$tmpl->setvar('xfer_week', displayXferBar($cfg['xfer_week'],$xfer_total['week']['total'],_XFERTHRU.' '.$cfg['week_start'].':'));
	}
	$monthStart = strtotime(date('Y-m-').$cfg['month_start']);
	$monthText = (date('j') < $cfg['month_start']) ? date('M�j',strtotime('-1 Day',$monthStart)) : date('M�j',strtotime('+1 Month -1 Day',$monthStart));
	if ($cfg['xfer_month']) {
		$tmpl->setvar('xfer_month', displayXferBar($cfg['xfer_month'],$xfer_total['month']['total'],_XFERTHRU.' '.$monthText.':'));
	}
	if ($cfg['xfer_total']) {
		$tmpl->setvar('xfer_month', displayXferBar($cfg['xfer_total'],$xfer_total['total']['total'],_TOTALXFER.':'));
	}
}
if ($cfg['enable_bigboldwarning'] != "0") {
	//Big bold warning hack by FLX
	if($drivespace >= 98) {
		$tmpl->setvar('enable_bigboldwarning', 1);
	}
}

// bigboldwarning
if ($cfg['enable_bigboldwarning'] != "1") {
	if($drivespace >= 98) {
		$tmpl->setvar('no_bigboldwarning', 1);
	}
}

if ($cfg['index_page_stats'] != 0) {
	$tmpl->setvar('index_page_stats', 1);
	if (!array_key_exists("total_download",$cfg)) $cfg["total_download"] = 0;
	if (!array_key_exists("total_upload",$cfg)) $cfg["total_upload"] = 0;
	if (($cfg['enable_xfer'] != 0) && ($cfg['xfer_realtime'] != 0)) {
		$totalxfer1 = _TOTALXFER.': <strong>'.formatFreeSpace($xfer_total['total']['total']/(1024*1024)).'</strong><br>';
		$tmpl->setvar('totalxfer1', $totalxfer1);
		$monthxfer1 = _MONTHXFER.': <strong>'.formatFreeSpace($xfer_total['month']['total']/(1024*1024)).'</strong><br>';
		$tmpl->setvar('monthxfer1', $monthxfer1);
		$weekxfer1 = _WEEKXFER.': <strong>'.formatFreeSpace($xfer_total['week']['total']/(1024*1024)).'</strong><br>';
		$tmpl->setvar('weekxfer1', $weekxfer1);
		$dayxfer1 = _DAYXFER.': <strong>'.formatFreeSpace($xfer_total['day']['total']/(1024*1024)).'</strong><br>';
		$tmpl->setvar('dayxfer1', $dayxfer1);
	}
	if ($queueActive) {
		$tmpl->setvar('queueActive2', 1);
		include_once("QueueManager.php");
		$queueManager = QueueManager::getQueueManagerInstance($cfg);
		$tmpl->setvar('_QUEUEMANAGER', $_QUEUEMANAGER);
		$tmpl->setvar('managerName', $queueManager->managerName);
		$getRunningTorrentCount = strval(getRunningTorrentCount());
		$countQueuedTorrents = strval($queueManager->countQueuedTorrents());
		$tmpl->setvar('getRunningTorrentCount', $getRunningTorrentCount);
		$tmpl->setvar('countQueuedTorrents', $countQueuedTorrents);
		$tmpl->setvar('limitGlobal', $queueManager->limitGlobal);
		$tmpl->setvar('limitUser', $queueManager->limitUser);
	}
	$tmpl->setvar('_OTHERSERVERSTATS', _OTHERSERVERSTATS);
	$sumMaxUpRate = getSumMaxUpRate();
	$sumMaxDownRate = getSumMaxDownRate();
	$sumMaxRate = $sumMaxUpRate + $sumMaxDownRate;
	$downloadspeed1 = _DOWNLOADSPEED.': <strong>'.number_format($cfg["total_download"], 2).' ('.number_format($sumMaxDownRate, 2).')</strong> kB/s<br>';
	$tmpl->setvar('downloadspeed1', $downloadspeed1);
	$uploadspeed1 = _UPLOADSPEED.': <strong>'.number_format($cfg["total_upload"], 2).' ('.number_format($sumMaxUpRate, 2).')</strong> kB/s<br>';
	$tmpl->setvar('uploadspeed1', $uploadspeed1);
	$totalspeed1 = _TOTALSPEED.': <strong>'.number_format($cfg["total_download"]+$cfg["total_upload"], 2).' ('.number_format($sumMaxRate, 2).')</strong> kB/s<br>';
	$tmpl->setvar('totalspeed1', $totalspeed1);
	if ($cfg["index_page_connections"] != 0) {
		$id_connections1 = _ID_CONNECTIONS.': <strong>'.$netstatConnectionsSum.' ('.getSumMaxCons().')</strong><br>';
		$tmpl->setvar('id_connections1', $id_connections1);
	}
	$drivespace1 = _DRIVESPACE.': <strong>'.formatFreeSpace($cfg["free_space"]).'</strong><br>';
	$tmpl->setvar('drivespace1', $drivespace1);
	if ($cfg["show_server_load"] != 0) {
		$serverload1 = _SERVERLOAD . ': <strong>'.$loadavgString.'</strong>';
		$tmpl->setvar('serverload1', $serverload1);
	}
	if (($cfg['enable_xfer'] != 0) && ($cfg['xfer_realtime'] != 0)) {
		$tmpl->setvar('_YOURXFERSTATS', _YOURXFERSTATS);
		$total2 = _TOTALXFER.': <strong>'.formatFreeSpace($xfer[$cfg['user']]['total']['total']/(1024*1024)).'</strong><br>';
		$tmpl->setvar('total2', $total2);
		$month2 = _MONTHXFER.': <strong>'.formatFreeSpace($xfer[$cfg['user']]['month']['total']/(1024*1024)).'</strong><br>';
		$tmpl->setvar('month2', $month2);
		$week2 = _WEEKXFER.': <strong>'.formatFreeSpace($xfer[$cfg['user']]['week']['total']/(1024*1024)).'</strong><br>';
		$tmpl->setvar('week2', $week2);
		$day2 = _DAYXFER.': <strong>'.formatFreeSpace($xfer[$cfg['user']]['day']['total']/(1024*1024)).'</strong><br>';
		$tmpl->setvar('day2', $day2);
	}
}
if (IsForceReadMsg()) {
	$tmpl->setvar('IsForceReadMsg', 1);
}

# define some things
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
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
$tmpl->setvar('user', $cfg["user"]);
$tmpl->setvar('formatFreeSpace', formatFreeSpace($cfg["free_space"]));
$tmpl->setvar('transferList', $transferList);
$tmpl->setvar('_TORRENTDETAILS', _TORRENTDETAILS);
$tmpl->setvar('_RUNTORRENT', _RUNTORRENT);
$tmpl->setvar('_STOPDOWNLOAD', _STOPDOWNLOAD);
$tmpl->setvar('AllowQueing', $cfg["AllowQueing"]);
$tmpl->setvar('_DELQUEUE', _DELQUEUE);
$tmpl->setvar('_SEEDTORRENT', _SEEDTORRENT);
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
$tmpl->setvar('DisplayTitleBar', DisplayTitleBar($cfg["pagetitle"]));
$tmpl->setvar('displayDriveSpaceBar', displayDriveSpaceBar($drivespace));
$tmpl->setvar('DisplayTorrentFluxLink', DisplayTorrentFluxLink(true));

$tmpl->pparse();
?>