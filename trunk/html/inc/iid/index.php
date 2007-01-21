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
	@header("location: ../../index.php");
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
$tmpl->setvar('enable_metafile_download', $cfg["enable_metafile_download"]);
$tmpl->setvar('enable_multiops', $cfg["enable_multiops"]);
$tmpl->setvar('enable_file_priority', $cfg["enable_file_priority"]);
$tmpl->setvar('advanced_start', $cfg["advanced_start"]);
$tmpl->setvar('twd', $cfg["transfer_window_default"]);

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
foreach ($arList as $transfer) {
	// ---------------------------------------------------------------------
	// displayname
	$displayname = (strlen($transfer) >= 47) ? substr($transfer, 0, 44)."..." : $transfer;
	// owner
	$transferowner = getOwner($transfer);
	$owner = IsOwner($cfg["user"], $transferowner);
	// stat
	$sf = new StatFile($transfer, $transferowner);
	// settings
	if (isset($transfers['settings'][$transfer])) {
		$settingsAry = $transfers['settings'][$transfer];
	} else {
		$settingsAry = array();
		if (substr($transfer, -8) == ".torrent") {
			// this is a t-client
			$settingsAry['type'] = "torrent";
			$settingsAry['client'] = $cfg["btclient"];
		} else if (substr($transfer, -5) == ".wget") {
			// this is wget.
			$settingsAry['type'] = "wget";
			$settingsAry['client'] = "wget";
		} else if (substr($transfer, -4) == ".nzb") {
			// this is nzbperl.
			$settingsAry['type'] = "nzb";
			$settingsAry['client'] = "nzbperl";
		} else {
			AuditAction($cfg["constants"]["error"], "INVALID TRANSFER: ".$transfer);
			@error("Invalid Transfer", "", "", array($transfer));
		}
		$settingsAry['hash'] = "";
		$settingsAry["savepath"] = ($cfg["enable_home_dirs"] != 0)
			? $cfg["path"].$transferowner.'/'
			: $cfg["path"].$cfg["path_incoming"].'/';
		$settingsAry['datapath'] = "";
	}
	// cache running-flag in local var. we will access that often
	$transferRunning = $sf->running;
	// cache percent-done in local var. ...
	$percentDone = $sf->percent_done;

	// status-image
	$hd = getStatusImage($sf);

	// more vars
	$detailsLinkString = "<a style=\"font-size:9px; text-decoration:none;\" href=\"JavaScript:showTransfer('index.php?iid=".$cfg["transfer_window_default"]."&transfer=".urlencode($transfer)."')\">";

	// ---------------------------------------------------------------------
	//XFER: add upload/download stats to the xfer array
	if (($cfg['enable_xfer'] == 1) && ($cfg['xfer_realtime'] == 1))
		@transferListXferUpdate1($transfer, $transferowner, $settingsAry['client'], $settingsAry['hash'], $sf->uptotal, $sf->downtotal);

	// ---------------------------------------------------------------------
	// injects
	if (!file_exists($cfg["transfer_file_path"].$transfer.".stat")) {
		$transferRunning = 2;
		$sf->running = "2";
		$sf->size = getDownloadSize($transfer);
		injectTransfer($transfer);
	}

	// totals-preparation
	// if downtotal + uptotal + progress > 0
	if (($settings[2] + $settings[3] + $settings[5]) > 0) {
		$ch = ClientHandler::getInstance($settingsAry['client']);
		$transferTotals = $ch->getTransferTotalOP($transfer, $settingsAry['hash'], $sf->uptotal, $sf->downtotal);
	}

	// ---------------------------------------------------------------------
	// preprocess stat-file and get some vars
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
			$cfg["total_upload"] = $cfg["total_upload"] + GetSpeedValue($sf->up_speed);
			$cfg["total_download"] = $cfg["total_download"] + GetSpeedValue($sf->down_speed);
			// $estTime
			if ($transferRunning == 0) {
				$estTime = $sf->time_left;
			} else {
				if ($sf->time_left != "" && $sf->time_left != "0") {
					if (($cfg["display_seeding_time"] == 1) && ($sf->percent_done >= 100) ) {
						$estTime = (($sf->seedlimit > 0) && (!empty($sf->up_speed)) && ((int) ($sf->up_speed{0}) > 0))
							? convertTime(((($sf->seedlimit) / 100 * $sf->size) - $sf->uptotal) / GetSpeedInBytes($sf->up_speed))
							: '-';
					} else {
						$estTime = $sf->time_left;
					}
				}
			}
			// $show_run + $statusStr
			if ($percentDone >= 100) {
				$statusStr = (trim($sf->up_speed) != "" && $transferRunning == 1) ? $detailsLinkString.'Seeding</a>' : $detailsLinkString.'Done</a>';
				$show_run = false;
			} else if ($percentDone < 0) {
				$statusStr = $detailsLinkString."Stopped</a>";
				$show_run = true;
			} else {
				$statusStr = $detailsLinkString."Leeching</a>";
			}
			// pid-file
			$is_no_file = (is_file($cfg["transfer_file_path"].$transfer.".pid")) ? 0 : 1;
			break;
	}

	// ==================================================================== name

	// =================================================================== owner

	// ==================================================================== size
	$format_af_size = ($settings[1] != 0) ? formatBytesTokBMBGBTB($sf->size) : "&nbsp;";

	// =============================================================== downtotal
	$format_downtotal = ($settings[2] != 0) ? formatBytesTokBMBGBTB($transferTotals["downtotal"]) : "&nbsp;";

	// ================================================================= uptotal
	$format_uptotal = ($settings[3] != 0) ? formatBytesTokBMBGBTB($transferTotals["uptotal"]) : "&nbsp;";

	// ================================================================== status

	// ================================================================ progress
	if ($settings[5] != 0) {
		if (($percentDone >= 100) && (trim($sf->up_speed) != "")) {
			$graph_width = -1;
			$percentage = @number_format((($transferTotals["uptotal"] / $sf->size) * 100), 2) . '%';
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
			$down_speed = (trim($sf->down_speed) != "") ? $sf->down_speed : '0.0 kB/s';
		else
			$down_speed = "&nbsp;";
	} else {
		$down_speed = "&nbsp;";
	}

	// ====================================================================== up
	if ($settings[7] != 0) {
		if ($transferRunning == 1)
			$up_speed = (trim($sf->up_speed) != "") ? $sf->up_speed : '0.0 kB/s';
		else
			$up_speed = "&nbsp;";
	} else {
		$up_speed = "&nbsp;";
	}

	// =================================================================== seeds
	if ($settings[8] != 0) {
		$seeds = ($transferRunning == 1)
			? $sf->seeds
			:  "&nbsp;";
	} else {
		$seeds = "&nbsp;";
	}

	// =================================================================== peers
	if ($settings[9] != 0) {
		$peers = ($transferRunning == 1)
			? $sf->peers
			:  "&nbsp;";
	} else {
		$peers = "&nbsp;";
	}

	// ===================================================================== ETA

	// ================================================================== client
	if ($settings[11] != 0) {
		switch ($settingsAry['client']) {
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
			case "nzbperl":
				$client = "N";
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
		'is_owner' => ($cfg['isAdmin']) ? true : $owner,
		'transferRunning' => $transferRunning,
		'url_entry' => urlencode($transfer),
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
		'clientType' => $settingsAry['type'],
		'client' => $client,
		'url_path' => urlencode(str_replace($cfg["path"],'', $settingsAry['savepath']).$settingsAry['datapath']),
		'datapath' => $settingsAry['datapath'],
		'is_no_file' => $is_no_file,
		'show_run' => $show_run,
		'entry' => $transfer
	);
	// Is this transfer for the user list or the general list?
	if ($owner)
		array_push($arUserTorrent, $tArray);
	else
		array_push($arListTorrent, $tArray);
}
$tmpl->setloop('arUserTorrent', $arUserTorrent);
$tmpl->setloop('arListTorrent', $arListTorrent);

//XFER: if a new day but no .stat files where found put blank entry into the
//      DB for today to indicate accounting has been done for the new day
if (($cfg['enable_xfer'] == 1) && ($cfg['xfer_realtime'] == 1))
	@transferListXferUpdate2();

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
	$tmpl->setvar('are_user_transfer', 1);
$boolCond = true;
if ($cfg['enable_restrictivetview'] == 1)
	$boolCond = $cfg['isAdmin'];
$tmpl->setvar('are_transfer', (($boolCond) && (sizeof($arListTorrent) > 0)) ? 1 : 0);

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
		$countUsers = count($cfg['users']);
		$arOnlineUsers = array();
		$arOfflineUsers = array();
		for ($i = 0; $i < $countUsers; $i++) {
			if (IsOnline($cfg['users'][$i]))
				array_push($arOnlineUsers, $cfg['users'][$i]);
			else
				array_push($arOfflineUsers, $cfg['users'][$i]);
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
    @header("Cache-Control: ");
    @header("Pragma: ");
	@header("Content-Type: text/plain");
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
	$netstatConnectionsMax = (isset($transfers['sum']['maxcons']))
		? "(".$transfers['sum']['maxcons'].")"
		: "(0)";
} else {
	$netstatConnectionsSum = "n/a";
	$netstatConnectionsMax = "";
}
// loadavg
$loadavgString = ($cfg["show_server_load"] != 0) ? @getLoadAverageString() : "n/a";

// links
if ($cfg["ui_displaylinks"] != "0") {
	if (isset($cfg['linklist']))
		$tmpl->setloop('linklist', $cfg['linklist']);
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
		$tmpl->setvar('settingsHackStats55', $cfg['freeSpaceFormatted']);
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
	$userCount = count($cfg['users']);
	$arOnlineUsers = array();
	$arOfflineUsers = array();
	for ($inx = 0; $inx < $userCount; $inx++) {
		if (IsOnline($cfg['users'][$inx]))
			array_push($arOnlineUsers, array('user' => $cfg['users'][$inx]));
		else
			array_push($arOfflineUsers, array('user' => $cfg['users'][$inx]));
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
		$monthText = (date('j') < $cfg['month_start']) ? date('M�j',strtotime('-1 Day',$monthStart)) : date('M�j',strtotime('+1 Month -1 Day',$monthStart));
		if ($cfg['xfer_month'])
			$tmpl->setvar('xfer_month', getXferBar($cfg['xfer_month'],$xfer_total['month']['total'],$cfg['_XFERTHRU'].' '.$monthText.':'));
		if ($cfg['xfer_total'])
			$tmpl->setvar('xfer_total', getXferBar($cfg['xfer_total'],$xfer_total['total']['total'],$cfg['_TOTALXFER'].':'));
	}
}

// drivespace-warning
if ($cfg['driveSpace'] >= 98) {
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
		$tmpl->setvar('limitGlobal', $cfg["fluxd_Qmgr_maxTotalTransfers"]);
		$tmpl->setvar('limitUser', $cfg["fluxd_Qmgr_maxUserTransfers"]);
	}
	$tmpl->setvar('_OTHERSERVERSTATS', $cfg['_OTHERSERVERSTATS']);
	$tmpl->setvar('downloadspeed1', @number_format($cfg["total_download"], 2));
	$tmpl->setvar('downloadspeed11', @number_format($transfers['sum']['drate'], 2));
	$tmpl->setvar('uploadspeed1', @number_format($cfg["total_upload"], 2));
	$tmpl->setvar('uploadspeed11', @number_format($transfers['sum']['rate'], 2));
	$tmpl->setvar('totalspeed1', @number_format($cfg["total_download"]+$cfg["total_upload"], 2));
	$tmpl->setvar('totalspeed11', @number_format($transfers['sum']['rate'] + $transfers['sum']['drate'], 2));
	$tmpl->setvar('id_connections1', $netstatConnectionsSum);
	$tmpl->setvar('id_connections11', $netstatConnectionsMax);
	$tmpl->setvar('drivespace1', $cfg['freeSpaceFormatted']);
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

// nzbperl
switch ($cfg['enable_nzbperl']) {
	case 2:
		$tmpl->setvar('enable_nzbperl', 1);
		break;
	case 1:
		if ($cfg['isAdmin'])
			$tmpl->setvar('enable_nzbperl', 1);
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
$tmpl->setvar('drivespace', $cfg['driveSpace']);
$tmpl->setvar('freeSpaceFormatted', $cfg['freeSpaceFormatted']);
$tmpl->setvar('file_types_label', $cfg['file_types_label']);
$tmpl->setloop('Engine_List', tmplSetSearchEngineDDL($cfg["searchEngine"]));
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