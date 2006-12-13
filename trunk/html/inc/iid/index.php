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

// prevent direct invocation
if (!isset($cfg['user'])) {
	@ob_end_clean();
	header("location: ../../index.php");
	exit();
}

/******************************************************************************/

if (isset($_REQUEST['ajax_update'])) {
	$isAjaxUpdate = true;
	$ajaxUpdateParams = $_REQUEST['ajax_update'];
	// init template-instance
	tmplInitializeInstance($cfg["theme"], "inc.transferList.tmpl");
} else {
	$isAjaxUpdate = false;
	// init template-instance
	tmplInitializeInstance($cfg["theme"], "page.index.tmpl");
}

// =============================================================================
// set common vars
// =============================================================================

// language
$tmpl->setvar('_STATUS', $cfg['_STATUS']);
$tmpl->setvar('_ESTIMATEDTIME', $cfg['_ESTIMATEDTIME']);
$tmpl->setvar('_TRANSFERDETAILS', $cfg['_TRANSFERDETAILS']);
$tmpl->setvar('_RUNTRANSFER', $cfg['_RUNTRANSFER']);
$tmpl->setvar('_STOPTRANSFER', $cfg['_STOPTRANSFER']);
$tmpl->setvar('_DELQUEUE', $cfg['_DELQUEUE']);
$tmpl->setvar('_SEEDTRANSFER', $cfg['_SEEDTRANSFER']);
$tmpl->setvar('_DELETE', $cfg['_DELETE']);
$tmpl->setvar('_WARNING', $cfg['_WARNING']);
$tmpl->setvar('_NOTOWNER', $cfg['_NOTOWNER']);
$tmpl->setvar('_STOPPING', $cfg['_STOPPING']);
$tmpl->setvar('_TRANSFERFILE', $cfg['_TRANSFERFILE']);
$tmpl->setvar('_ADMIN', $cfg['_ADMIN']);
$tmpl->setvar('_USER', $cfg['_USER']);

// username
$tmpl->setvar('user', $cfg["user"]);

// queue
$tmpl->setvar('queueActive', (FluxdQmgr::isRunning()) ? 1 : 0);

// incoming-path
$tmpl->setvar('path_incoming', ($cfg["enable_home_dirs"] != 0) ? $cfg["user"] : $cfg["path_incoming"]);

// some configs
$tmpl->setvar('enable_torrent_download', $cfg["enable_torrent_download"]);
$tmpl->setvar('enable_multiops', $cfg["enable_multiops"]);
$tmpl->setvar('enable_file_priority', $cfg["enable_file_priority"]);

// =============================================================================
// transfer-list
// =============================================================================
$arUserTorrent = array();
$arListTorrent = array();
// settings
$settings = convertIntegerToArray($cfg["index_page_settings"]);
// sortOrder
$sortOrder = getRequestVar("so");
$tmpl->setvar('sortOrder', (empty($sortOrder)) ? $cfg["index_page_sortorder"] : $sortOrder);
// t-list
$arList = getTransferArray($sortOrder);
$progress_color = "#00ff00";
$bar_width = "4";
foreach ($arList as $entry) {
	// ---------------------------------------------------------------------
	// displayname
	$displayname = (strlen($entry) >= 47) ? substr($entry, 0, 44)."..." : $entry;

	// ---------------------------------------------------------------------
	// alias / stat
	$alias = getAliasName($entry).".stat";
	if ((substr(strtolower($entry), -8) == ".torrent")) {
		// this is a torrent-client
		$isTorrent = true;
		$transferowner = getOwner($entry);
		$owner = (IsOwner($cfg["user"], $transferowner)) ? 1 : 0;
		$settingsAry = loadTorrentSettings($entry);
		$af = AliasFile::getAliasFileInstance($alias, $transferowner, $cfg, $settingsAry['btclient']);
	} else if ((substr(strtolower($entry), -5) == ".wget")) {
		// this is wget.
		$isTorrent = false;
		$transferowner = getOwner($entry);
		$owner = (IsOwner($cfg["user"], $transferowner)) ? 1 : 0;
		$settingsAry = array();
		$settingsAry['btclient'] = "wget";
		$settingsAry['hash'] = $entry;
		$settingsAry["savepath"] = ($cfg["enable_home_dirs"] != 0)
			? $cfg["path"].$transferowner.'/'
			: $cfg["path"].$cfg["path_incoming"].'/';
		$settingsAry['datapath'] = "";
		$af = AliasFile::getAliasFileInstance($alias, $transferowner, $cfg, 'wget');
	} else {
		// this is "something else". use tornado statfile as default
		$isTorrent = false;
		$transferowner = $cfg["user"];
		$owner = 1;
		$settingsAry = array();
		$settingsAry['btclient'] = "tornado";
		$settingsAry['hash'] = $entry;
		$settingsAry["savepath"] = ($cfg["enable_home_dirs"] != 0)
			? $cfg["path"].$transferowner.'/'
			: $cfg["path"].$cfg["path_incoming"].'/';
		$settingsAry['datapath'] = "";
		$af = AliasFile::getAliasFileInstance($alias, $cfg["user"], $cfg, 'tornado');
	}
	// cache running-flag in local var. we will access that often
	$transferRunning = $af->running;
	// cache percent-done in local var. ...
	$percentDone = $af->percent_done;

	// status-image
	$hd = getStatusImage($af);

	// more vars
	$detailsLinkString = "<a style=\"font-size:9px; text-decoration:none;\" href=\"JavaScript:ShowDetails('index.php?iid=downloaddetails&alias=".$alias."&torrent=".urlencode($entry)."')\">";

	// ---------------------------------------------------------------------
	//XFER: add upload/download stats to the xfer array
	if (($cfg['enable_xfer'] == 1) && ($cfg['xfer_realtime'] == 1))
		$newday = transferListXferUpdate1($entry, $transferowner, $af, $settingsAry);

	// ---------------------------------------------------------------------
	// injects
	if(! file_exists($cfg["transfer_file_path"].$alias)) {
		$transferRunning = 2;
		$af->running = "2";
		$af->size = getDownloadSize($cfg["transfer_file_path"].$entry);
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
	$show_run = true;
	switch ($transferRunning) {
		case 2: // new
			$statusStr = $detailsLinkString."<font color=\"#32cd32\">New</font></a>";
			$is_no_file = 1;
			break;
		case 3: // queued
			$statusStr = $detailsLinkString."Queued</a>";
			$estTime = "Waiting...";
			$is_no_file = 1;
			break;
		default: // running
			// increment the totals
			if (!isset($cfg["total_upload"]))
				$cfg["total_upload"] = 0;
			if (!isset($cfg["total_download"]))
				 $cfg["total_download"] = 0;
			$cfg["total_upload"] = $cfg["total_upload"] + GetSpeedValue($af->up_speed);
			$cfg["total_download"] = $cfg["total_download"] + GetSpeedValue($af->down_speed);
			// $estTime
			if ($transferRunning == 0) {
				$estTime = $af->time_left;
			} else {
				if ($af->time_left != "" && $af->time_left != "0") {
					if (($cfg["display_seeding_time"] == 1) && ($af->percent_done >= 100) ) {
						$estTime = (($af->seedlimit > 0) && (!empty($af->up_speed)) && ((int) ($af->up_speed{0}) > 0))
							? convertTime(((($af->seedlimit) / 100 * $af->size) - $af->uptotal) / GetSpeedInBytes($af->up_speed)) . " left"
							: '-';
					} else {
						$estTime = $af->time_left;
					}
				}
			}
			// $show_run + $statusStr
			if ($percentDone >= 100) {
				$statusStr = (trim($af->up_speed) != "" && $transferRunning == 1) ? $detailsLinkString.'Seeding</a>' : $detailsLinkString.'Done</a>';
				$show_run = false;
			} else if ($percentDone < 0) {
				$statusStr = $detailsLinkString."Stopped</a>";
				$show_run = true;
			} else {
				$statusStr = $detailsLinkString."Leeching</a>";
			}
			// pid-file
			$is_no_file = (is_file($cfg["transfer_file_path"].$alias.".pid")) ? 0 : 1;
			break;
	}

	// ==================================================================== name

	// =================================================================== owner

	// ==================================================================== size
	$format_af_size = ($settings[1] != 0) ? formatBytesTokBMBGBTB($af->size) : "&nbsp;";

	// =============================================================== downtotal
	$format_downtotal = ($settings[2] != 0) ? formatBytesTokBMBGBTB($transferTotals["downtotal"]) : "&nbsp;";

	// ================================================================= uptotal
	$format_uptotal = ($settings[3] != 0) ? formatBytesTokBMBGBTB($transferTotals["uptotal"]) : "&nbsp;";

	// ================================================================== status

	// ================================================================ progress
	if ($settings[5] != 0) {
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
		$background = ($graph_width == 100) ? $progress_color : "#000000";
	} else {
		$graph_width = 0;
		$background = "";
		$percentage = "";
	}

	// ==================================================================== down
	if ($settings[6] != 0) {
		if ($transferRunning == 1)
			$down_speed = (trim($af->down_speed) != "") ? $af->down_speed : '0.0 kB/s';
		else
			$down_speed = "&nbsp;";
	} else {
		$down_speed = "&nbsp;";
	}

	// ====================================================================== up
	if ($settings[7] != 0) {
		if ($transferRunning == 1)
			$up_speed = (trim($af->up_speed) != "") ? $af->up_speed : '0.0 kB/s';
		else
			$up_speed = "&nbsp;";
	} else {
		$up_speed = "&nbsp;";
	}

	// =================================================================== seeds
	if ($settings[8] != 0) {
		if ($transferRunning == 1)
			$seeds = $af->seeds;
		else
			$seeds = "&nbsp;";
	} else {
		$seeds = "&nbsp;";
	}

	// =================================================================== peers
	if ($settings[9] != 0) {
		if ($transferRunning == 1)
			$peers = $af->peers;
		else
			$peers = "&nbsp;";
	} else {
		$peers = "&nbsp;";
	}

	// ===================================================================== ETA

	// ================================================================== client
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
		$client = "&nbsp;";
	}

	// -------------------------------------------------------------------------
	// create temp-array
	$tArray = array(
		'is_owner' => ($cfg['isAdmin']) ? 1 : $owner,
		'transferRunning' => $transferRunning,
		'alias' => $alias,
		'url_entry' => urlencode($entry),
		'hd_image' => $hd->image,
		'hd_title' => $hd->title,
		'displayname' => $displayname,
		'transferowner' => $transferowner,
		'format_af_size' => $format_af_size,
		'format_downtotal' => $format_downtotal,
		'format_uptotal' => $format_uptotal,
		'statusStr' => $statusStr,
		'graph_width' => $graph_width,
		'percentage' => $percentage,
		'progress_color' => $progress_color,
		'bar_width' => $bar_width,
		'background' => $background,
		'100_graph_width' => (100 - $graph_width),
		'down_speed' => $down_speed,
		'up_speed' => $up_speed,
		'seeds' => $seeds,
		'peers' => $peers,
		'estTime' => $estTime,
		'client' => $client,
		'url_path' => urlencode(str_replace($cfg["path"],'', $settingsAry['savepath']).$settingsAry['datapath']),
		'datapath' => $settingsAry['datapath'],
		'is_no_file' => $is_no_file,
		'isTorrent' => $isTorrent,
		'show_run' => $show_run,
		'entry' => $entry
	);
	// Is this transfer for the user list or the general list?
	if ($owner == 1)
		array_push($arUserTorrent, $tArray);
	else
		array_push($arListTorrent, $tArray);
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
	$boolCond = $cfg['isAdmin'];
$tmpl->setvar('are_torrent', (($boolCond) && (sizeof($arListTorrent) > 0)) ? 1 : 0);

// =============================================================================
// ajax-index
// =============================================================================

if ($isAjaxUpdate) {
	$content = "";
	$isFirst = true;
	// server stats
	if ($ajaxUpdateParams{0} == "1") {
		$isFirst = false;
		$serverStats = getServerStats();
		$serverCount = count($serverStats);
		for ($i = 0; $i < $serverCount; $i++) {
			$content .= $serverStats[$i];
			if ($i < ($serverCount - 1))
				$content .= $cfg['stats_txt_delim'];
		}
	}
	// xfer
	if ($ajaxUpdateParams{1} == "1") {
		if ($isFirst)
			$isFirst = false;
		else
			$content .= "|";
		$xferStats = getXferStats();
		$xferCount = count($xferStats);
		for ($i = 0; $i < $xferCount; $i++) {
			$content .= $xferStats[$i];
			if ($i < ($xferCount - 1))
				$content .= $cfg['stats_txt_delim'];
		}
	}
	// users
	if ($ajaxUpdateParams{2} == "1") {
		if ($isFirst)
			$isFirst = false;
		else
			$content .= "|";
		$arUsers = GetUsers();
		$countUsers = count($arUsers);
		$arOnlineUsers = array();
		$arOfflineUsers = array();
		for ($i = 0; $i < $countUsers; $i++) {
			if (IsOnline($arUsers[$i]))
				array_push($arOnlineUsers, $arUsers[$i]);
			else
				array_push($arOfflineUsers, $arUsers[$i]);
		}
		$countOnline = count($arOnlineUsers);
		for ($i = 0; $i < $countOnline; $i++) {
			$content .= $arOnlineUsers[$i];
			if ($i < ($countOnline - 1))
				$content .= $cfg['stats_txt_delim'];
		}
		if ($cfg["hide_offline"] == 0) {
			$content .= "+";
			$countOffline = count($arOfflineUsers);
			for ($i = 0; $i < $countOffline; $i++) {
				$content .= $arOfflineUsers[$i];
				if ($i < ($countOffline - 1))
					$content .= $cfg['stats_txt_delim'];
			}
		}
	}
	// transfer list
	if ($ajaxUpdateParams{3} == "1") {
		if ($isFirst)
			$isFirst = false;
		else
			$content .= "|";
		$content .= $tmpl->grab();
	}
	// send and out
    header("Cache-Control: ");
    header("Pragma: ");
	header("Content-Type: text/plain");
	echo $content;
	exit();
}

// =============================================================================
// standard-index
// =============================================================================

// goodlookingstats-init
if ($cfg["enable_goodlookstats"] != "0") {
	$tmpl->setvar('enable_goodlookstats', 1);
	$settingsHackStats = convertByteToArray($cfg["hack_goodlookstats_settings"]);
}

$onLoad = "";

// page refresh
if ($_SESSION['settings']['index_meta_refresh'] != 0) {
	$tmpl->setvar('page_refresh', $cfg["page_refresh"]);
	$tmpl->setvar('meta_refresh', $cfg["page_refresh"].';URL=index.php?iid=index');
	$onLoad .= "initRefresh(".$cfg["page_refresh"].");";
	$tmpl->setvar('_PAGEWILLREFRESH', $cfg['_PAGEWILLREFRESH']);
} else {
	$tmpl->setvar('_TURNONREFRESH', $cfg['_TURNONREFRESH']);
}

// AJAX update
if ($_SESSION['settings']['index_ajax_update'] != 0) {
	$tmpl->setvar('index_ajax_update', $cfg["index_ajax_update"]);
	$ajaxInit = "ajax_initialize(";
	$ajaxInit .= (((int) $cfg['index_ajax_update']) * 1000);
	$ajaxInit .= ",'".$cfg['stats_txt_delim']."'";
	$ajaxInit .= ",".$cfg["enable_index_ajax_update_silent"];
	$ajaxInit .= ",".$cfg["enable_index_ajax_update_title"];
	$ajaxInit .= ",'".$cfg['pagetitle']."'";
	$ajaxInit .= ",".$cfg["enable_goodlookstats"];
	if ($cfg["enable_goodlookstats"] != "0")
		$ajaxInit .= ",'".$settingsHackStats[0].':'.$settingsHackStats[1].':'.$settingsHackStats[2].':'.$settingsHackStats[3].':'.$settingsHackStats[4].':'.$settingsHackStats[5]."'";
	else
		$ajaxInit .= ",'0:0:0:0:0:0'";
	$ajaxInit .= ",".$cfg["index_page_stats"];
	if (FluxdQmgr::isRunning())
		$ajaxInit .= ",1";
	else
		$ajaxInit .= ",0";
	if (($cfg['enable_xfer'] == 1) && ($cfg['xfer_realtime'] == 1))
		$ajaxInit .= ",1";
	else
		$ajaxInit .= ",0";
	if (($cfg['ui_displayusers'] == 1) && ($cfg['enable_index_ajax_update_users'] == 1))
		$ajaxInit .= ",1";
	else
		$ajaxInit .= ",0";
	$ajaxInit .= ",".$cfg["hide_offline"];
	$ajaxInit .= ",".$cfg["enable_index_ajax_update_list"];
	$ajaxInit .= ",".$cfg["enable_sorttable"];
	$ajaxInit .= ",'".$cfg['drivespacebar']."'";
	$ajaxInit .= ",".$cfg["ui_displaybandwidthbars"];
	$ajaxInit .= ",'".$cfg['bandwidthbar']."'";
	$ajaxInit .= ");";
	$onLoad .= $ajaxInit;
}

// onLoad
if ($onLoad != "") {
	$tmpl->setvar('onLoad', $onLoad);
	$tmpl->setvar('_SECONDS', $cfg['_SECONDS']);
	$tmpl->setvar('_TURNOFFREFRESH', $cfg['_TURNOFFREFRESH']);
}

// connections
if ($cfg["index_page_connections"] != 0) {
	$netstatConnectionsSum = @netstatConnectionsSum();
	$netstatConnectionsMax = "(".@getSumMaxCons().")";
} else {
	$netstatConnectionsSum = "n/a";
	$netstatConnectionsMax = "";
}
// loadavg
$loadavgString = ($cfg["show_server_load"] != 0) ? @getLoadAverageString() : "n/a";

// messages
if (isset($_REQUEST['messages']))
	if ($_REQUEST['messages'] != "")
		$tmpl->setvar('messages', urldecode($_REQUEST['messages']));

// links
if ($cfg["ui_displaylinks"] != "0") {
	$arLinks = GetLinks();
	if ((isset($arLinks)) && (is_array($arLinks))) {
		$linklist = array();
		foreach ($arLinks as $link) {
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
		$tmpl->setvar('settingsHackStats55', $freeSpaceFormatted);
	}
	if ($settingsHackStats[5] == 1) {
		$tmpl->setvar('settingsHackStats6', 1);
		$tmpl->setvar('settingsHackStats66', $loadavgString);
	}
}

// users
if ($cfg["ui_displayusers"] != "0") {
	$tmpl->setvar('ui_displayusers',1);
	$tmpl->setvar('hide_offline', $cfg["hide_offline"]);
	$arUsers = GetUsers();
	$userCount = count($arUsers);
	$arOnlineUsers = array();
	$arOfflineUsers = array();
	for ($inx = 0; $inx < $userCount; $inx++) {
		if (IsOnline($arUsers[$inx]))
			array_push($arOnlineUsers, array('user' => $arUsers[$inx]));
		else
			array_push($arOfflineUsers, array('user' => $arUsers[$inx]));
	}
	if (count($arOnlineUsers) > 0)
		$tmpl->setloop('arOnlineUsers', $arOnlineUsers);
	if (count($arOfflineUsers) > 0)
		$tmpl->setloop('arOfflineUsers', $arOfflineUsers);
}

// xfer
if ($cfg['enable_xfer'] == 1) {
	if ($cfg['enable_public_xfer'] == 1)
		$tmpl->setvar('enable_xfer', 1);
	if ($cfg['xfer_realtime'] == 1) {
		$tmpl->setvar('xfer_realtime', 1);
		if ($cfg['xfer_day'])
			$tmpl->setvar('xfer_day', getXferBar($cfg['xfer_day'],$xfer_total['day']['total'],$cfg['_XFERTHRU'].' Today:'));
		if ($cfg['xfer_week'])
			$tmpl->setvar('xfer_week', getXferBar($cfg['xfer_week'],$xfer_total['week']['total'],$cfg['_XFERTHRU'].' '.$cfg['week_start'].':'));
		$monthStart = strtotime(date('Y-m-').$cfg['month_start']);
		$monthText = (date('j') < $cfg['month_start']) ? date('M j',strtotime('-1 Day',$monthStart)) : date('M j',strtotime('+1 Month -1 Day',$monthStart));
		if ($cfg['xfer_month'])
			$tmpl->setvar('xfer_month', getXferBar($cfg['xfer_month'],$xfer_total['month']['total'],$cfg['_XFERTHRU'].' '.$monthText.':'));
		if ($cfg['xfer_total'])
			$tmpl->setvar('xfer_total', getXferBar($cfg['xfer_total'],$xfer_total['total']['total'],$cfg['_TOTALXFER'].':'));
	}
}

// drivespace-warning
if ($driveSpace >= 98) {
	if ($cfg['enable_bigboldwarning'] != 0)
		$tmpl->setvar('enable_bigboldwarning', 1);
	else
		$tmpl->setvar('enable_jswarning', 1);
}

// bottom stats
if ($cfg['index_page_stats'] != 0) {
	$tmpl->setvar('index_page_stats', 1);
	if (!array_key_exists("total_download",$cfg))
		$cfg["total_download"] = 0;
	if (!array_key_exists("total_upload",$cfg))
		$cfg["total_upload"] = 0;
	if (($cfg['enable_xfer'] != 0) && ($cfg['xfer_realtime'] != 0)) {
		$tmpl->setvar('totalxfer1', @formatFreeSpace($xfer_total['total']['total'] / 1048576));
		$tmpl->setvar('monthxfer1', @formatFreeSpace($xfer_total['month']['total'] / 1048576));
		$tmpl->setvar('weekxfer1', @formatFreeSpace($xfer_total['week']['total'] / 1048576));
		$tmpl->setvar('dayxfer1', @formatFreeSpace($xfer_total['day']['total'] / 1048576));
	}
	if (FluxdQmgr::isRunning()) {
		$tmpl->setvar('_QUEUEMANAGER', $cfg['_QUEUEMANAGER']);
		$tmpl->setvar('runningTransferCount', getRunningTransferCount());
		$tmpl->setvar('countQueuedTransfers', FluxdQmgr::countQueuedTransfers());
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
	$tmpl->setvar('id_connections1', $netstatConnectionsSum);
	$tmpl->setvar('id_connections11', $netstatConnectionsMax);
	$tmpl->setvar('drivespace1', $freeSpaceFormatted);
	$tmpl->setvar('serverload1', $loadavgString);
	if (($cfg['enable_xfer'] != 0) && ($cfg['xfer_realtime'] != 0)) {
		$tmpl->setvar('_YOURXFERSTATS', $cfg['_YOURXFERSTATS']);
		$tmpl->setvar('total2', @formatFreeSpace($xfer[$cfg["user"]]['total']['total'] / 1048576));
		$tmpl->setvar('month2', @formatFreeSpace($xfer[$cfg["user"]]['month']['total'] / 1048576));
		$tmpl->setvar('week2', @formatFreeSpace($xfer[$cfg["user"]]['week']['total'] / 1048576));
		$tmpl->setvar('day2', @formatFreeSpace($xfer[$cfg["user"]]['day']['total'] / 1048576));
	}
}

// pm
if (IsForceReadMsg())
	$tmpl->setvar('IsForceReadMsg', 1);

// Graphical Bandwidth Bar
if ($cfg["ui_displaybandwidthbars"] != 0) {
	$tmpl->setvar('ui_displaybandwidthbars', 1);
	tmplSetBandwidthBars();
}

// wget
switch ($cfg["enable_wget"]) {
	case 2:
		$tmpl->setvar('enable_wget', 1);
		break;
	case 1:
		if ($cfg['isAdmin'])
			$tmpl->setvar('enable_wget', 1);
}

$tmpl->setvar('version', $cfg["version"]);
$tmpl->setvar('enable_multiupload', $cfg["enable_multiupload"]);
$tmpl->setvar('enable_search', $cfg["enable_search"]);
$tmpl->setvar('enable_dereferrer', $cfg["enable_dereferrer"]);
$tmpl->setvar('enable_sorttable', $cfg["enable_sorttable"]);
$tmpl->setvar('enable_mrtg', $cfg["enable_mrtg"]);
$tmpl->setvar('enable_bulkops', $cfg["enable_bulkops"]);
$tmpl->setvar('ui_displaylinks', $cfg["ui_displaylinks"]);
$tmpl->setvar('ui_dim_main_w', $cfg["ui_dim_main_w"]);
$tmpl->setvar('ui_displayfluxlink', $cfg["ui_displayfluxlink"]);
$tmpl->setvar('advanced_start', $cfg["advanced_start"]);
$tmpl->setvar('drivespace', $driveSpace);
$tmpl->setvar('freeSpaceFormatted', $freeSpaceFormatted);
tmplSetSearchEngineDDL($cfg["searchEngine"]);
//
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
$tmpl->setvar('_DRIVESPACEUSED', $cfg['_DRIVESPACEUSED']);
$tmpl->setvar('_SERVERXFERSTATS', $cfg['_SERVERXFERSTATS']);
$tmpl->setvar('_ADMINMESSAGE', $cfg['_ADMINMESSAGE']);
$tmpl->setvar('_TOTALXFER', $cfg['_TOTALXFER']);
$tmpl->setvar('_MONTHXFER', $cfg['_MONTHXFER']);
$tmpl->setvar('_WEEKXFER', $cfg['_WEEKXFER']);
$tmpl->setvar('_DAYXFER', $cfg['_DAYXFER']);
//
tmplSetTitleBar($cfg["pagetitle"]);
tmplSetDriveSpaceBar();
//
$tmpl->setvar('iid', $_REQUEST["iid"]);

// parse template
$tmpl->pparse();

?>