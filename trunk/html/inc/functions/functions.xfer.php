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

/**
 * get xfer stats
 * note : this can only be used after a call to update transfer-values in cfg-
 *        array (eg by getTransferListArray)
 *
 * @return array
 *
 * "xferGlobalTotal"     0
 * "xferGlobalMonth"     1
 * "xferGlobalWeek"      2
 * "xferGlobalDay"       3
 * "xferUserTotal"       4
 * "xferUserMonth"       5
 * "xferUserWeek"        6
 * "xferUserDay"         7
 *
 */
function getXferStats() {
	global $cfg, $xfer_total, $xfer;
	$xferStats = array();
	// global
    $xferGlobalTotal = "n/a";
	$xferGlobalTotal =  @formatFreeSpace($xfer_total['total']['total'] / 1048576);
	array_push($xferStats, $xferGlobalTotal);
    $xferGlobalMonth = "n/a";
	$xferGlobalMonth =  @formatFreeSpace($xfer_total['month']['total'] / 1048576);
	array_push($xferStats, $xferGlobalMonth);
    $xferGlobalWeek = "n/a";
	$xferGlobalWeek =  @formatFreeSpace($xfer_total['week']['total'] / 1048576);
	array_push($xferStats, $xferGlobalWeek);
    $xferGlobalDay = "n/a";
	$xferGlobalDay =  @formatFreeSpace($xfer_total['day']['total'] / 1048576);
	array_push($xferStats, $xferGlobalDay);
	// user
    $xferUserTotal = "n/a";
	$xferUserTotal =  @formatFreeSpace($xfer[$cfg["user"]]['total']['total'] / 1048576);
	array_push($xferStats, $xferUserTotal);
    $xferUserMonth = "n/a";
	$xferUserMonth =  @formatFreeSpace($xfer[$cfg["user"]]['month']['total'] / 1048576);
	array_push($xferStats, $xferUserMonth);
    $xferUserWeek = "n/a";
	$xferUserWeek =  @formatFreeSpace($xfer[$cfg["user"]]['week']['total'] / 1048576);
	array_push($xferStats, $xferUserWeek);
    $xferUserDay = "n/a";
	$xferUserDay =  @formatFreeSpace($xfer[$cfg["user"]]['day']['total'] / 1048576);
	array_push($xferStats, $xferUserDay);
	// return
	return $xferStats;
}

/**
 * transferListXferUpdate1
 *
 * @param $entry
 * @param $transferowner
 * @param $af
 * @param $settingsAry
 * @return unknown
 */
function transferListXferUpdate1($entry, $transferowner, $af, $settingsAry) {
	global $cfg, $db;
	$transferTotalsCurrent = getTransferTotalsCurrentOP($entry, $settingsAry['hash'], $settingsAry['btclient'], $af->uptotal, $af->downtotal);
	$newday = 0;
	$sql = 'SELECT 1 FROM tf_xfer WHERE date = '.$db->DBDate(time());
	$newday = !$db->GetOne($sql);
	showError($db,$sql);
	sumUsage($transferowner, ($transferTotalsCurrent["downtotal"]), ($transferTotalsCurrent["uptotal"]), 'total');
	sumUsage($transferowner, ($transferTotalsCurrent["downtotal"]), ($transferTotalsCurrent["uptotal"]), 'month');
	sumUsage($transferowner, ($transferTotalsCurrent["downtotal"]), ($transferTotalsCurrent["uptotal"]), 'week');
	sumUsage($transferowner, ($transferTotalsCurrent["downtotal"]), ($transferTotalsCurrent["uptotal"]), 'day');
	//XFER: if new day add upload/download totals to last date on record and subtract from today in SQL
	if ($newday) {
		$newday = 2;
		$sql = 'SELECT date FROM tf_xfer ORDER BY date DESC';
		$lastDate = $db->GetOne($sql);
		showError($db,$sql);
		// MySQL 4.1.0 introduced 'ON DUPLICATE KEY UPDATE' to make this easier
		$sql = 'SELECT 1 FROM tf_xfer WHERE user_id = "'.$transferowner.'" AND date = "'.$lastDate.'"';
		if ($db->GetOne($sql)) {
			$sql = 'UPDATE tf_xfer SET download = download+'.($transferTotalsCurrent["downtotal"]).', upload = upload+'.($transferTotalsCurrent["uptotal"]).' WHERE user_id = "'.$transferowner.'" AND date = "'.$lastDate.'"';
			$db->Execute($sql);
			showError($db,$sql);
		} else {
			showError($db,$sql);
			$sql = 'INSERT INTO tf_xfer (user_id,date,download,upload) values ("'.$transferowner.'","'.$lastDate.'",'.($transferTotalsCurrent["downtotal"]).','.($transferTotalsCurrent["uptotal"]).')';
			$db->Execute($sql);
			showError($db,$sql);
		}
		$sql = 'SELECT 1 FROM tf_xfer WHERE user_id = "'.$transferowner.'" AND date = '.$db->DBDate(time());
		if ($db->GetOne($sql)) {
			$sql = 'UPDATE tf_xfer SET download = download-'.($transferTotalsCurrent["downtotal"]).', upload = upload-'.($transferTotalsCurrent["uptotal"]).' WHERE user_id = "'.$transferowner.'" AND date = '.$db->DBDate(time());
			$db->Execute($sql);
			showError($db,$sql);
		} else {
			showError($db,$sql);
			$sql = 'INSERT INTO tf_xfer (user_id,date,download,upload) values ("'.$transferowner.'",'.$db->DBDate(time()).',-'.($transferTotalsCurrent["downtotal"]).',-'.($transferTotalsCurrent["uptotal"]).')';
			$db->Execute($sql);
			showError($db,$sql);
		}
	}
	return $newday;
}

/**
 * transferListXferUpdate2
 *
 * @param $newday
 */
function transferListXferUpdate2($newday) {
	global $cfg, $db;
	if ($newday == 1) {
		$sql = 'INSERT INTO tf_xfer (user_id,date) values ( "",'.$db->DBDate(time()).')';
		$db->Execute($sql);
		showError($db,$sql);
	}
	getUsage(0, 'total');
	$month_start = (date('j')>=$cfg['month_start']) ? date('Y-m-').$cfg['month_start'] : date('Y-m-',strtotime('-1 Month')).$cfg['month_start'];
	getUsage($month_start, 'month');
	$week_start = date('Y-m-d',strtotime('last '.$cfg['week_start']));
	getUsage($week_start, 'week');
	$day_start = date('Y-m-d');
	getUsage($day_start, 'day');
}

/**
 * Gets upload/download usage for all users starting at timestamp from SQL
 *
 * @param $start
 * @param $period
 */
function getUsage($start, $period) {
	global $xfer, $xfer_total, $db;
	$sql = 'SELECT user_id, SUM(download) AS download, SUM(upload) AS upload FROM tf_xfer WHERE date >= "'.$start.'" AND user_id != "" GROUP BY user_id';
	$rtnValue = $db->GetAll($sql);
	showError($db,$sql);
	foreach ($rtnValue as $row)
		sumUsage($row[0], $row[1], $row[2], $period);
}

/**
 *  Adds download/upload into correct usage_array (total, month, etc)
 *
 * @param $user
 * @param $download
 * @param $upload
 * @param $period
 */
function sumUsage($user, $download, $upload, $period) {
	global $xfer, $xfer_total;
	@ $xfer[$user][$period]['download'] += $download;
	@ $xfer[$user][$period]['upload'] += $upload;
	@ $xfer[$user][$period]['total'] += $download + $upload;
	@ $xfer_total[$period]['download'] += $download;
	@ $xfer_total[$period]['upload'] += $upload;
	@ $xfer_total[$period]['total'] += $download + $upload;
}

/**
 * Inserts or updates SQL upload/download for user
 *
 * @param $user
 * @param $down
 * @param $up
 */
function saveXfer($user, $down, $up) {
	global $db;
	$sql = 'SELECT 1 FROM tf_xfer WHERE user_id = "'.$user.'" AND date = '.$db->DBDate(time());
	if ($db->GetRow($sql)) {
		$sql = 'UPDATE tf_xfer SET download = download+'.$down.', upload = upload+'.$up.' WHERE user_id = "'.$user.'" AND date = '.$db->DBDate(time());
		$db->Execute($sql);
		showError($db,$sql);
	} else {
		showError($db,$sql);
		$sql = 'INSERT INTO tf_xfer (user_id,date,download,upload) values ("'.$user.'",'.$db->DBDate(time()).','.$down.','.$up.')';
		$db->Execute($sql);
		showError($db,$sql);
	}
}

/**
 * gets xfer percentage bar
 *
 * @param $total
 * @param $used
 * @param $title
 * @return string
 */
function getXferBar($total, $used, $title) {
	global $cfg;
	// create template-instance
	$tmpl = tmplGetInstance($cfg["theme"], "component.xferBar.tmpl");
	$remaining = max(0,$total-$used/(1024*1024));
	$percent = round($remaining/$total*100,0);
	$text = ' ('.formatFreeSpace($remaining).') '.$cfg['_REMAINING'];
	$bgcolor = '#';
	$bgcolor .= str_pad(dechex(255-255*($percent/150)),2,0,STR_PAD_LEFT);
	$bgcolor .= str_pad(dechex(255*($percent/150)),2,0,STR_PAD_LEFT);
	$bgcolor .='00';
	$tmpl->setvar('title', $title);
	$tmpl->setvar('bgcolor', $bgcolor);
	$tmpl->setvar('percent_1', ($percent+1));
	$tmpl->setvar('percent', $percent);
	$tmpl->setvar('text', $text);
	$percent_100 = 100-$percent;
	$tmpl->setvar('percent_100', $percent_100);
	// grab the template
	$output = $tmpl->grab();
	return $output;
}

?>