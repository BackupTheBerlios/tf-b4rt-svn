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

// index functions
require_once("inc/functions/functions.index.php");

// global fields
$messages = "";

/*******************************************************************************
 * set refresh option into the session cookie
 ******************************************************************************/
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

/*******************************************************************************
 * transfer-start
 ******************************************************************************/
$transfer = getRequestVar('torrent');
if(!empty($transfer)) {
	if ((substr(strtolower($transfer),-8 ) == ".torrent")) {
		// this is a torrent-client
		$interactiveStart = getRequestVar('interactive');
		if ((isset($interactiveStart)) && ($interactiveStart)) /* interactive */
			indexStartTorrent($transfer, 1);
		else /* silent */
			indexStartTorrent($transfer, 0);
	} else if ((substr(strtolower($transfer),-5 ) == ".wget")) {
		// this is wget.
		require_once("inc/classes/ClientHandler.php");
		$clientHandler = ClientHandler::getClientHandlerInstance($cfg, 'wget');
		$clientHandler->startClient($transfer, 0, false);
		sleep(5);
		header("location: index.php?iid=index");
	} else {
		return;
	}
}

/*******************************************************************************
 * wget-inject
 ******************************************************************************/
if ($cfg['enable_wget'] == 1) {
	$url_wget = getRequestVar('url_wget');
	if(! $url_wget == '') {
		require_once("inc/classes/ClientHandler.php");
		$clientHandler = ClientHandler::getClientHandlerInstance($cfg, 'wget');
		$clientHandler->inject($url_wget);
		$wget_start = getRequestVar('wget_start');
		if ($wget_start == 1) {
			sleep(2);
			$clientHandler->startClient($url_wget, 0, false);
			sleep(5);
		}
		header("location: index.php?iid=index");
		exit();
	}
}

/*******************************************************************************
 * get torrent via url
 ******************************************************************************/
$url_upload = getRequestVar('url_upload');
if(! $url_upload == '')
	indexProcessDownload($url_upload);

/*******************************************************************************
 * file upload
 ******************************************************************************/
if(!empty($_FILES['upload_file']['name']))
	indexProcessUpload();

/*******************************************************************************
 * del file
 ******************************************************************************/
$delfile = getRequestVar('delfile');
if(! $delfile == '') {
	deleteTransfer($delfile, getRequestVar('alias_file'));
	header("location: index.php?iid=index");
	exit();
}

/*******************************************************************************
 * kill
 ******************************************************************************/
$killTorrent = getRequestVar('kill_torrent');
if(! $killTorrent == '') {
	$return = getRequestVar('return');
	require_once("inc/classes/ClientHandler.php");
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


/*******************************************************************************
 * deQueue
 ******************************************************************************/
if(isset($_REQUEST["dQueue"])) {
	$QEntry = getRequestVar('QEntry');
	$fluxdQmgr->dequeueTorrent($QEntry, $cfg['user']);
	header("location: index.php?iid=index");
	exit();
}

/*******************************************************************************
 * index-page
 ******************************************************************************/

// =============================================================================
// init vars
// =============================================================================

// create template-instance
$tmpl = getTemplateInstance($cfg["theme"], "index.tmpl");

// drivespace
$drivespace = getDriveSpace($cfg["path"]);
$formatFreeSpace = formatFreeSpace($cfg["free_space"]);
// connections
$netstatConnectionsSum = "n/a";
if ($cfg["index_page_connections"] != 0)
	$netstatConnectionsSum = @netstatConnectionsSum();
// loadavg
$loadavgString = "n/a";
if ($cfg["show_server_load"] != 0)
	$loadavgString = @getLoadAverageString();

// =============================================================================
// transfer-list
// =============================================================================

require_once("inc/classes/AliasFile.php");
$kill_id = "";
$lastUser = "";
$arUserTorrent = array();
$arListTorrent = array();
// settings
$settings = convertIntegerToArray($cfg["index_page_settings"]);
// sortOrder
$sortOrder = getRequestVar("so");
if ($sortOrder == "")
	$sortOrder = $cfg["index_page_sortorder"];
// t-list
$arList = getTransferArray($sortOrder);
foreach($arList as $entry) {
	// ---------------------------------------------------------------------
	// init some vars
	$displayname = $entry;
	if(strlen($entry) >= 47) {
		// needs to be trimmed
		$displayname = substr($entry, 0, 44);
		$displayname .= "...";
	}
	$show_run = true;

	// ---------------------------------------------------------------------
	// alias / stat
	$alias = getAliasName($entry).".stat";
	if ((substr( strtolower($entry),-8 ) == ".torrent")) {
		// this is a torrent-client
		$isTorrent = true;
		$transferowner = getOwner($entry);
		$owner = IsOwner($cfg["user"], $transferowner);
		$settingsAry = loadTorrentSettings($entry);
		$af = AliasFile::getAliasFileInstance($cfg["torrent_file_path"].$alias, $transferowner, $cfg, $settingsAry['btclient']);
		$hd = getStatusImage($af);
	} else if ((substr( strtolower($entry),-5 ) == ".wget")) {
		// this is wget.
		$isTorrent = false;
		$transferowner = $cfg["user"];
		$owner = true;
		$settingsAry = array();
		$settingsAry['btclient'] = "wget";
		$settingsAry['hash'] = $entry;
		$settingsAry['savepath'] = $cfg['path'].$transferowner."/";;
		$settingsAry['datapath'] = "";
		$af = AliasFile::getAliasFileInstance($cfg["torrent_file_path"].$alias, $cfg['user'], $cfg, 'wget');
		$hd = getStatusImage($af);
	} else {
		// this is "something else". use tornado statfile as default
		$isTorrent = false;
		$transferowner = $cfg["user"];
		$owner = true;
		$settingsAry = array();
		$settingsAry['btclient'] = "tornado";
		$settingsAry['hash'] = $entry;
		$settingsAry['savepath'] = $cfg['path'].$transferowner."/";;
		$settingsAry['datapath'] = "";
		$af = AliasFile::getAliasFileInstance($cfg["torrent_file_path"].$alias, $cfg['user'], $cfg, 'tornado');
		$hd = getStatusImage($af);
	}
	// cache running-flag in local var. we will access that often
	$transferRunning = (int) $af->running;
	// cache percent-done in local var. ...
	$percentDone = $af->percent_done;

	// more vars
	$detailsLinkString = "<a style=\"font-size:9px; text-decoration:none;\" href=\"JavaScript:ShowDetails('index.php?iid=downloaddetails&alias=".$alias."&torrent=".urlencode($entry)."')\">";

	// ---------------------------------------------------------------------
	//XFER: add upload/download stats to the xfer array
	if (($cfg['enable_xfer'] == 1) && ($cfg['xfer_realtime'] == 1))
		$newday = transferListXferUpdate1($entry, $transferowner, $af, $settingsAry);

	// ---------------------------------------------------------------------
	// injects
	if(! file_exists($cfg["torrent_file_path"].$alias)) {
		$transferRunning = 2;
		$af->running = "2";
		$af->size = getDownloadSize($cfg["torrent_file_path"].$entry);
		$af->WriteFile();
	}

	// totals-preparation
	// if downtotal + uptotal + progress > 0
	if (($settings[2] + $settings[3] + $settings[5]) > 0)
		$transferTotals = getTransferTotalsOP($entry, $settingsAry['hash'], $settingsAry['btclient'], $af->uptotal, $af->downtotal);

	// ---------------------------------------------------------------------
	// preprocess alias-file and get some vars
	$estTime = "&nbsp;";
	$statusStr = "&nbsp;";
	switch ($transferRunning) {
		case 2: // new
			$statusStr = $detailsLinkString."<font color=\"#32cd32\">New</font></a>";
			break;
		case 3: // queued
			$statusStr = $detailsLinkString."Queued</a>";
			$estTime = "Waiting...";
			break;
		default: // running
			// increment the totals
			if(!isset($cfg["total_upload"])) $cfg["total_upload"] = 0;
			if(!isset($cfg["total_download"])) $cfg["total_download"] = 0;
			$cfg["total_upload"] = $cfg["total_upload"] + GetSpeedValue($af->up_speed);
			$cfg["total_download"] = $cfg["total_download"] + GetSpeedValue($af->down_speed);
			// $estTime
			if ($af->time_left != "" && $af->time_left != "0")
				if ( ($cfg["display_seeding_time"]) && ($af->percent_done >= 100) ) {
					if (($af->seedlimit > 0) && (!empty($af->up_speed)) && ((int) ($af->up_speed{0}) > 0))
						$estTime = convertTime(((($af->seedlimit) / 100 * $af->size) - $af->uptotal) / GetSpeedInBytes($af->up_speed)) . " left";
					else
						$estTime = '&#8734';
				} else {
					$estTime = $af->time_left;
				}
			// $lastUser
			$lastUser = $transferowner;
			// $show_run + $statusStr
			if($percentDone >= 100) {
				if(trim($af->up_speed) != "" && $transferRunning == 1) {
					$statusStr = $detailsLinkString.'Seeding</a>';
				} else {
					$statusStr = $detailsLinkString.'Done</a>';
				}
				$show_run = false;
			} else if ($percentDone < 0) {
				$statusStr = $detailsLinkString."Stopped</a>";
				$show_run = true;
			} else {
				$statusStr = $detailsLinkString."Leeching</a>";
			}
			break;
	}

	// ============================================================ progress
	if ($settings[5] != 0) {
		$graph_width = 1;
		$progress_color = "#00ff00";
		$background = "#000000";
		$bar_width = "4";
		$percentage = "";
		if (($percentDone >= 100) && (trim($af->up_speed) != "")) {
			$graph_width = -1;
			$percentage = @number_format((($transferTotals["uptotal"] / $af->size) * 100), 2) . '%';
		} else {
			if ($percentDone >= 1) {
				$graph_width = $percentDone;
				$percentage = $graph_width . '%';
			} else if ($percentDone < 0) {
				$graph_width = round(($percentDone*-1)-100,1);
				$percentage = $graph_width . '%';
			} else {
				$graph_width = 0;
				$percentage = '0%';
			}
		}
		if($graph_width == 100)
			$background = $progress_color;
	}

	// ================================================================ down
	if ($settings[6] != 0) {
		if ($transferRunning == 1) {
			if (trim($af->down_speed) != "")
				$down_speed = $af->down_speed;
			else
				$down_speed = '0.0 kB/s';
		} else {
			$down_speed = '';
		}
	}

	// ================================================================== up
	if ($settings[7] != 0) {
		if ($transferRunning == 1) {
			if (trim($af->up_speed) != "")
				$up_speed = $af->up_speed;
			else
				$up_speed = '0.0 kB/s';
		} else {
			$up_speed = '';
		}
	}

	// ============================================================== client
	if ($settings[11] != 0) {
		switch ($settingsAry['btclient']) {
			case "tornado":
				$client = "B";
			break;
			case "transmission":
				$client = "T";
			break;
			case "mainline":
				$client = "M";
			break;
			case "wget":
				$client = "W";
			break;
			default:
				$client = "U";
		}
	} else {
		$client = "";
	}


	if ($owner || IsAdmin($cfg["user"])) {
		$is_owner = 1;
		if($percentDone >= 0 && $transferRunning == 1) {
			$is_running = 1;
			$is_no_file = 0;
		} else {
			$is_running = 0;
			$is_no_file = 0;
			if($transferowner != "n/a") {
				if ($transferRunning != 3) {
					if (!is_file($cfg["torrent_file_path"].$alias.".pid")) {
						$is_no_file = 1;
					}
				}
			}
		}
	}
	// ---------------------------------------------------------------------
	// Is this torrent for the user list or the general list?
	if ($owner)
		array_push($arUserTorrent, array(
			'transferRunning' => $transferRunning,
			'alias' => $alias,
			'url_entry' => urlencode($entry),
			'hd_image' => $hd->image,
			'hd_title' => $hd->title,
			'displayname' => $displayname,
			'transferowner' => $transferowner,
			'format_af_size' => formatBytesTokBMBGBTB($af->size),
			'format_downtotal' => formatBytesTokBMBGBTB($transferTotals["downtotal"]+0),
			'format_uptotal' => formatBytesTokBMBGBTB($transferTotals["uptotal"]+0),
			'statusStr' => $statusStr,
			'graph_width' => $graph_width,
			'percentage' => $percentage,
			'progress_color' => $progress_color,
			'bar_width' => $bar_width,
			'background' => $background,
			'100_graph_width' => (100 - $graph_width),
			'down_speed' => $down_speed,
			'up_speed' => $up_speed,
			'seeds' => $af->seeds,
			'peers' => $af->peers,
			'estTime' => $estTime,
			'client' => $client,
			'url_path' => urlencode(str_replace($cfg["path"],'', $settingsAry['savepath']).$settingsAry['datapath']),
			'datapath' => $settingsAry['datapath'],
			'is_owner' => $is_owner,
			'is_running' => $is_running,
			'isTorrent' => $isTorrent,
			'kill_id' => $kill_id,
			'is_no_file' => $is_no_file,
			'show_run' => $show_run,
			'entry' => $entry,
			)
		);
	else
		array_push($arListTorrent, array(
			'transferRunning' => $transferRunning,
			'alias' => $alias,
			'url_entry' => urlencode($entry),
			'hd_image' => $hd->image,
			'hd_title' => $hd->title,
			'displayname' => $displayname,
			'transferowner' => $transferowner,
			'format_af_size' => formatBytesTokBMBGBTB($af->size),
			'format_downtotal' => formatBytesTokBMBGBTB($transferTotals["downtotal"]+0),
			'format_uptotal' => formatBytesTokBMBGBTB($transferTotals["uptotal"]+0),
			'statusStr' => $statusStr,
			'graph_width' => $graph_width,
			'percentage' => $percentage,
			'progress_color' => $progress_color,
			'bar_width' => $bar_width,
			'background' => $background,
			'100_graph_width' => (100 - $graph_width),
			'down_speed' => $down_speed,
			'up_speed' => $up_speed,
			'seeds' => $af->seeds,
			'peers' => $af->peers,
			'estTime' => $estTime,
			'client' => $client,
			'url_path' => urlencode(str_replace($cfg["path"],'', $settingsAry['savepath']).$settingsAry['datapath']),
			'datapath' => $settingsAry['datapath'],
			'is_owner' => $is_owner,
			'is_running' => $is_running,
			'isTorrent' => $isTorrent,
			'kill_id' => $kill_id,
			'is_no_file' => $is_no_file,
			'show_run' => $show_run,
			'entry' => $entry,
			)
		);
}
$tmpl->setloop('arUserTorrent', $arUserTorrent);
$tmpl->setloop('arListTorrent', $arListTorrent);

//XFER: if a new day but no .stat files where found put blank entry into the
//      DB for today to indicate accounting has been done for the new day
if (($cfg['enable_xfer'] == 1) && ($cfg['xfer_realtime'] == 1))
	transferListXferUpdate2($newday);

$tmpl->setvar('settings_0', $settings[0]);
$tmpl->setvar('settings_1', $settings[1]);
$tmpl->setvar('settings_2', $settings[2]);
$tmpl->setvar('settings_3', $settings[3]);
$tmpl->setvar('settings_4', $settings[4]);
$tmpl->setvar('settings_5', $settings[5]);
$tmpl->setvar('settings_6', $settings[6]);
$tmpl->setvar('settings_7', $settings[7]);
$tmpl->setvar('settings_8', $settings[8]);
$tmpl->setvar('settings_9', $settings[9]);
$tmpl->setvar('settings_10', $settings[10]);
$tmpl->setvar('settings_11', $settings[11]);

if (sizeof($arUserTorrent) > 0)
	$tmpl->setvar('are_user_torrent', 1);
$boolCond = true;
if ($cfg['enable_restrictivetview'] == 1)
	$boolCond = IsAdmin();
if (($boolCond) && (sizeof($arListTorrent) > 0))
	$tmpl->setvar('are_torrent', 1);

// =============================================================================
// set vars
// =============================================================================

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
		$tmpl->setvar('settingsHackStats55', $formatFreeSpace);
	}
	if ($settingsHackStats[5] == 1) {
		$tmpl->setvar('settingsHackStats6', 1);
		$tmpl->setvar('settingsHackStats66', $loadavgString);
	}
}

// users
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
		$tmpl->setvar('xfer_day', getXferBar($cfg['xfer_day'],$xfer_total['day']['total'],$cfg['_XFERTHRU'].' Today:'));
	}
	if ($cfg['xfer_week']) {
		$tmpl->setvar('xfer_week', getXferBar($cfg['xfer_week'],$xfer_total['week']['total'],$cfg['_XFERTHRU'].' '.$cfg['week_start'].':'));
	}
	$monthStart = strtotime(date('Y-m-').$cfg['month_start']);
	$monthText = (date('j') < $cfg['month_start']) ? date('M�j',strtotime('-1 Day',$monthStart)) : date('M�j',strtotime('+1 Month -1 Day',$monthStart));
	if ($cfg['xfer_month']) {
		$tmpl->setvar('xfer_month', getXferBar($cfg['xfer_month'],$xfer_total['month']['total'],$cfg['_XFERTHRU'].' '.$monthText.':'));
	}
	if ($cfg['xfer_total']) {
		$tmpl->setvar('xfer_total', getXferBar($cfg['xfer_total'],$xfer_total['total']['total'],$cfg['_TOTALXFER'].':'));
	}
}

// bigboldwarning
if ($cfg['enable_bigboldwarning'] != "0") {
	//Big bold warning hack by FLX
	if($drivespace >= 98)
		$tmpl->setvar('enable_bigboldwarning', 1);
} else {
	if($drivespace >= 98)
		$tmpl->setvar('no_bigboldwarning', 1);
}

// bottom stats
if ($cfg['index_page_stats'] != 0) {
	$tmpl->setvar('index_page_stats', 1);
	if (!array_key_exists("total_download",$cfg))
		$cfg["total_download"] = 0;
	if (!array_key_exists("total_upload",$cfg))
		$cfg["total_upload"] = 0;
	if (($cfg['enable_xfer'] != 0) && ($cfg['xfer_realtime'] != 0)) {
		$tmpl->setvar('totalxfer1', formatFreeSpace($xfer_total['total']['total'] / 1048576));
		$tmpl->setvar('monthxfer1', formatFreeSpace($xfer_total['month']['total'] / 1048576));
		$tmpl->setvar('weekxfer1', formatFreeSpace($xfer_total['week']['total'] / 1048576));
		$tmpl->setvar('dayxfer1', formatFreeSpace($xfer_total['day']['total'] / 1048576));
	}
	if ($queueActive) {
		$tmpl->setvar('queueActive2', 1);
		$tmpl->setvar('_QUEUEMANAGER', $cfg['_QUEUEMANAGER']);
		$runningTransferCount = strval(getRunningTransferCount());
		$tmpl->setvar('runningTransferCount', $runningTransferCount);
		$countQueuedTorrents = strval($fluxdQmgr->countQueuedTorrents());
		$tmpl->setvar('countQueuedTorrents', $countQueuedTorrents);
		$tmpl->setvar('limitGlobal', $cfg["fluxd_Qmgr_maxTotalTorrents"]);
		$tmpl->setvar('limitUser', $cfg["fluxd_Qmgr_maxUserTorrents"]);
	}
	$tmpl->setvar('_OTHERSERVERSTATS', $cfg['_OTHERSERVERSTATS']);
	$sumMaxUpRate = getSumMaxUpRate();
	$sumMaxDownRate = getSumMaxDownRate();
	$sumMaxRate = $sumMaxUpRate + $sumMaxDownRate;
	$tmpl->setvar('downloadspeed1', @number_format($cfg["total_download"], 2));
	$tmpl->setvar('downloadspeed11', @number_format($sumMaxDownRate, 2));
	$tmpl->setvar('uploadspeed1', @number_format($cfg["total_upload"], 2));
	$tmpl->setvar('uploadspeed11', @number_format($sumMaxUpRate, 2));
	$tmpl->setvar('totalspeed1', @number_format($cfg["total_download"]+$cfg["total_upload"], 2));
	$tmpl->setvar('totalspeed11', @number_format($sumMaxRate, 2));
	if ($cfg["index_page_connections"] != 0) {
		$tmpl->setvar('id_connections1', $netstatConnectionsSum);
		$tmpl->setvar('id_connections11', getSumMaxCons());
	}
	$tmpl->setvar('drivespace1', $formatFreeSpace);
	if ($cfg["show_server_load"] != 0)
		$tmpl->setvar('serverload1', $loadavgString);
	if (($cfg['enable_xfer'] != 0) && ($cfg['xfer_realtime'] != 0)) {
		$tmpl->setvar('_YOURXFERSTATS', $cfg['_YOURXFERSTATS']);
		$tmpl->setvar('total2', formatFreeSpace($xfer[$cfg['user']]['total']['total'] / 1048576));
		$tmpl->setvar('month2', formatFreeSpace($xfer[$cfg['user']]['month']['total'] / 1048576));
		$tmpl->setvar('week2', formatFreeSpace($xfer[$cfg['user']]['week']['total'] / 1048576));
		$tmpl->setvar('day2', formatFreeSpace($xfer[$cfg['user']]['day']['total'] / 1048576));
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

// =============================================================================
// set more vars
// =============================================================================

$tmpl->setvar('version', $cfg["version"]);
$tmpl->setvar('user', $cfg["user"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('main_bgcolor', $cfg["main_bgcolor"]);
$tmpl->setvar('table_border_dk', $cfg["table_border_dk"]);
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
$tmpl->setvar('table_data_bg', $cfg["table_data_bg"]);
$tmpl->setvar('showdirtree', $cfg["showdirtree"]);
$tmpl->setvar('enable_multiupload', $cfg["enable_multiupload"]);
$tmpl->setvar('enable_wget', $cfg["enable_wget"]);
$tmpl->setvar('enable_search', $cfg["enable_search"]);
$tmpl->setvar('enable_dereferrer', $cfg["enable_dereferrer"]);
$tmpl->setvar('enable_sorttable', $cfg["enable_sorttable"]);
$tmpl->setvar('enable_mrtg', $cfg["enable_mrtg"]);
$tmpl->setvar('enable_torrent_download', $cfg["enable_torrent_download"]);
$tmpl->setvar('enable_multiops', $cfg["enable_multiops"]);
$tmpl->setvar('enable_bulkops', $cfg["enable_bulkops"]);
$tmpl->setvar('hide_offline', $cfg["hide_offline"]);
$tmpl->setvar('ui_displaylinks', $cfg["ui_displaylinks"]);
$tmpl->setvar('ui_displayusers', $cfg["ui_displayusers"]);
$tmpl->setvar('ui_dim_main_w', $cfg["ui_dim_main_w"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('ui_displayfluxlink', $cfg["ui_displayfluxlink"]);
$tmpl->setvar('advanced_start', $cfg["advanced_start"]);
$tmpl->setvar('sortOrder', $sortOrder);
$tmpl->setvar('drivespace', $drivespace);
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('titleBar', getTitleBar($cfg["pagetitle"]));
$tmpl->setvar('driveSpaceBar', getDriveSpaceBar($drivespace));
$tmpl->setvar('formatFreeSpace', $formatFreeSpace);
fillSearchEngineDDL($cfg["searchEngine"]);
$tmpl->setvar('_URL_DEREFERRER', $cfg["_URL_DEREFERRER"]);
$tmpl->setvar('_ABOUTTODELETE', $cfg['_ABOUTTODELETE']);
$tmpl->setvar('_SELECTFILE', $cfg['_SELECTFILE']);
$tmpl->setvar('_UPLOAD', $cfg['_UPLOAD']);
$tmpl->setvar('_MULTIPLE_UPLOAD', $cfg['_MULTIPLE_UPLOAD']);
$tmpl->setvar('_URLFILE', $cfg['_URLFILE']);
$tmpl->setvar('_GETFILE', $cfg['_GETFILE']);
$tmpl->setvar('_SEARCH', $cfg['_SEARCH']);
$tmpl->setvar('_TORRENTLINKS', $cfg['_TORRENTLINKS']);
$tmpl->setvar('_DOWNLOADSPEED', $cfg['_DOWNLOADSPEED']);
$tmpl->setvar('_UPLOADSPEED', $cfg['_UPLOADSPEED']);
$tmpl->setvar('_TOTALSPEED', $cfg['_TOTALSPEED']);
$tmpl->setvar('_ID_CONNECTIONS', $cfg['_ID_CONNECTIONS']);
$tmpl->setvar('_DRIVESPACE', $cfg['_DRIVESPACE']);
$tmpl->setvar('_SERVERLOAD', $cfg['_SERVERLOAD']);
$tmpl->setvar('_ONLINE', $cfg['_ONLINE']);
$tmpl->setvar('_OFFLINE', $cfg['_OFFLINE']);
$tmpl->setvar('_XFER_USAGE', $cfg['_XFER_USAGE']);
$tmpl->setvar('_ID_MRTG', $cfg['_ID_MRTG']);
$tmpl->setvar('_SERVERSTATS', $cfg['_SERVERSTATS']);
$tmpl->setvar('_ALL', $cfg['_ALL']);
$tmpl->setvar('_DIRECTORYLIST', $cfg['_DIRECTORYLIST']);
$tmpl->setvar('_TRANSFERDETAILS', $cfg['_TRANSFERDETAILS']);
$tmpl->setvar('_RUNTRANSFER', $cfg['_RUNTRANSFER']);
$tmpl->setvar('_STOPTRANSFER', $cfg['_STOPTRANSFER']);
$tmpl->setvar('_DELQUEUE', $cfg['_DELQUEUE']);
$tmpl->setvar('_SEEDTRANSFER', $cfg['_SEEDTRANSFER']);
$tmpl->setvar('_DELETE', $cfg['_DELETE']);
$tmpl->setvar('_PAGEWILLREFRESH', $cfg['_PAGEWILLREFRESH']);
$tmpl->setvar('_TURNOFFREFRESH', $cfg['_TURNOFFREFRESH']);
$tmpl->setvar('_TURNONREFRESH', $cfg['_TURNONREFRESH']);
$tmpl->setvar('_SECONDS', $cfg['_SECONDS']);
$tmpl->setvar('_WARNING', $cfg['_WARNING']);
$tmpl->setvar('_DRIVESPACEUSED', $cfg['_DRIVESPACEUSED']);
$tmpl->setvar('_SERVERXFERSTATS', $cfg['_SERVERXFERSTATS']);
$tmpl->setvar('_ADMINMESSAGE', $cfg['_ADMINMESSAGE']);
$tmpl->setvar('_TOTALXFER', $cfg['_TOTALXFER']);
$tmpl->setvar('_MONTHXFER', $cfg['_MONTHXFER']);
$tmpl->setvar('_WEEKXFER', $cfg['_WEEKXFER']);
$tmpl->setvar('_DAYXFER', $cfg['_DAYXFER']);
$tmpl->setvar('_STATUS', $cfg['_STATUS']);
$tmpl->setvar('_ESTIMATEDTIME', $cfg['_ESTIMATEDTIME']);
$tmpl->setvar('_NOTOWNER', $cfg['_NOTOWNER']);
$tmpl->setvar('_STOPPING', $cfg['_STOPPING']);
$tmpl->setvar('_TRANSFERFILE', $cfg['_TRANSFERFILE']);
$tmpl->setvar('_ADMIN', $cfg['_ADMIN']);
$tmpl->setvar('_USER', $cfg['_USER']);
//
if (isset($_GET["iid"]))
	$tmpl->setvar('iid', $_GET["iid"]);
else
	$tmpl->setvar('iid', 'index');

// parse template
$tmpl->pparse();

?>