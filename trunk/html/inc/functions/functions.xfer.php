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
	sumUsage($transferowner, ($transferTotalsCurrent["downtotal"]+0), ($transferTotalsCurrent["uptotal"]+0), 'total');
	sumUsage($transferowner, ($transferTotalsCurrent["downtotal"]+0), ($transferTotalsCurrent["uptotal"]+0), 'month');
	sumUsage($transferowner, ($transferTotalsCurrent["downtotal"]+0), ($transferTotalsCurrent["uptotal"]+0), 'week');
	sumUsage($transferowner, ($transferTotalsCurrent["downtotal"]+0), ($transferTotalsCurrent["uptotal"]+0), 'day');
	//XFER: if new day add upload/download totals to last date on record and subtract from today in SQL
	if ($newday) {
		$newday = 2;
		$sql = 'SELECT date FROM tf_xfer ORDER BY date DESC';
		$lastDate = $db->GetOne($sql);
		showError($db,$sql);
		// MySQL 4.1.0 introduced 'ON DUPLICATE KEY UPDATE' to make this easier
		$sql = 'SELECT 1 FROM tf_xfer WHERE user = "'.$transferowner.'" AND date = "'.$lastDate.'"';
		if ($db->GetOne($sql)) {
			$sql = 'UPDATE tf_xfer SET download = download+'.($transferTotalsCurrent["downtotal"]+0).', upload = upload+'.($transferTotalsCurrent["uptotal"]+0).' WHERE user = "'.$transferowner.'" AND date = "'.$lastDate.'"';
			$db->Execute($sql);
			showError($db,$sql);
		} else {
			showError($db,$sql);
			$sql = 'INSERT INTO tf_xfer (user,date,download,upload) values ("'.$transferowner.'","'.$lastDate.'",'.($transferTotalsCurrent["downtotal"]+0).','.($transferTotalsCurrent["uptotal"]+0).')';
			$db->Execute($sql);
			showError($db,$sql);
		}
		$sql = 'SELECT 1 FROM tf_xfer WHERE user = "'.$transferowner.'" AND date = '.$db->DBDate(time());
		if ($db->GetOne($sql)) {
			$sql = 'UPDATE tf_xfer SET download = download-'.($transferTotalsCurrent["downtotal"]+0).', upload = upload-'.($transferTotalsCurrent["uptotal"]+0).' WHERE user = "'.$transferowner.'" AND date = '.$db->DBDate(time());
			$db->Execute($sql);
			showError($db,$sql);
		} else {
			showError($db,$sql);
			$sql = 'INSERT INTO tf_xfer (user,date,download,upload) values ("'.$transferowner.'",'.$db->DBDate(time()).',-'.($transferTotalsCurrent["downtotal"]+0).',-'.($transferTotalsCurrent["uptotal"]+0).')';
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
		$sql = 'INSERT INTO tf_xfer (user,date) values ( "",'.$db->DBDate(time()).')';
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

//XFER:****************************************************
//XFER: getUsage(timestamp, usage_array)
//XFER: Gets upload/download usage for all users starting at timestamp from SQL
function getUsage($start, $period) {
  global $xfer, $xfer_total, $db;
  $sql = 'SELECT user, SUM(download) AS download, SUM(upload) AS upload FROM tf_xfer WHERE date >= "'.$start.'" AND user != "" GROUP BY user';
  $rtnValue = $db->GetAll($sql);
  showError($db,$sql);
  foreach ($rtnValue as $row) sumUsage($row[0], $row[1], $row[2], $period);
}

//XFER:****************************************************
//XFER: sumUsage(user, downloaded, uploaded, usage_array)
//XFER: Adds download/upload into correct usage_array (total, month, etc)
function sumUsage($user, $download, $upload, $period) {
  global $xfer, $xfer_total;
  @ $xfer[$user][$period]['download'] += $download;
  @ $xfer[$user][$period]['upload'] += $upload;
  @ $xfer[$user][$period]['total'] += $download + $upload;
  @ $xfer_total[$period]['download'] += $download;
  @ $xfer_total[$period]['upload'] += $upload;
  @ $xfer_total[$period]['total'] += $download + $upload;
}

//XFER:****************************************************
//XFER: saveXfer(user, download, upload)
//XFER: Inserts or updates SQL upload/download for user
function saveXfer($user, $down, $up) {
  global $db;
  $sql = 'SELECT 1 FROM tf_xfer WHERE user = "'.$user.'" AND date = '.$db->DBDate(time());
  if ($db->GetRow($sql)) {
    $sql = 'UPDATE tf_xfer SET download = download+'.($down+0).', upload = upload+'.($up+0).' WHERE user = "'.$user.'" AND date = '.$db->DBDate(time());
    $db->Execute($sql);
    showError($db,$sql);
  } else {
    showError($db,$sql);
    $sql = 'INSERT INTO tf_xfer (user,date,download,upload) values ("'.$user.'",'.$db->DBDate(time()).','.($down+0).','.($up+0).')';
    $db->Execute($sql);
    showError($db,$sql);
  }
}


//XFER:****************************************************
//XFER: getXferBar(max_bytes, used_bytes, title)
//XFER: gets xfer percentage bar
function getXferBar($total, $used, $title) {
	global $cfg;
	// create template-instance
	$tmpl = getTemplateInstance($cfg["theme"], "inc.xferBar.tmpl");
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

//XFER:****************************************************
//XFER: getXfer()
//XFER: gets xfer usage page
function getXfer() {
	global $cfg;
	// create template-instance
	$tmpl = getTemplateInstance($cfg["theme"], "inc.getXfer.tmpl");
	$tmpl->setvar('XferList', getXferList());
	if (isset($_GET['user'])) {
		$tmpl->setvar('user', $_GET['user']);
		$tmpl->setvar('_SERVERXFERSTATS', $cfg['_SERVERXFERSTATS']);
		$tmpl->setvar('_USERDETAILS', $cfg['_USERDETAILS']);
		if (isset($_GET['month'])) {
			$mstart = $_GET['month'].'-'.$cfg['month_start'];
			$mend = date('Y-m-d',strtotime('+1 Month',strtotime($mstart)));
		} else {
			$mstart = 0;
			$mend = 0;
		}
		if (isset($_GET['week'])) {
			$wstart = $_GET['week'];
			$wend = date('Y-m-d',strtotime('+1 Week',strtotime($_GET['week'])));
		} else {
			$wstart = $mstart;
			$wend = $mend;
		}
		$tmpl->setvar('xferDetailMonth', getXferDetail($_GET['user'],$cfg['_MONTHSTARTING'],0,0));
		$tmpl->setvar('xferDetailWeek', getXferDetail($_GET['user'],$cfg['_WEEKSTARTING'],$mstart,$mend));
		$tmpl->setvar('xferDetailDay', getXferDetail($_GET['user'],$cfg['_DAY'],$wstart,$wend));
	}
	// grab the template
	$output = $tmpl->grab();
	return $output;
}

//XFER:****************************************************
//XFER: getXferDetail(user, period_title, start_timestamp, end_timestamp)
//XFER: get table of month/week/day's usage for user
function getXferDetail($user_id,$period,$period_start,$period_end) {
	global $cfg, $xfer, $xfer_total, $db;
	$period_query = ($period_start) ? 'and date >= "'.$period_start.'" and date < "'.$period_end.'"' : '';
	$sql = 'SELECT SUM(download) AS download, SUM(upload) AS upload, date FROM tf_xfer WHERE user LIKE "'.$user_id.'" '.$period_query.' GROUP BY date ORDER BY date';
	$rtnValue = $db->GetAll($sql);
	showError($db,$sql);
	$displayXferDetail = "<table width='760' border=1 bordercolor='$cfg[table_admin_border]' cellpadding='2' cellspacing='0' bgcolor='$cfg[table_data_bg]'>";
	$displayXferDetail .= '<tr>';
	$displayXferDetail .= "<td bgcolor='$cfg[table_header_bg]' width='20%'><div align=center class='title'>$period</div></td>";
	$displayXferDetail .= "<td bgcolor='$cfg[table_header_bg]' width='27%'><div align=center class='title'>".$cfg['_TOTAL'].'</div></td>';
	$displayXferDetail .= "<td bgcolor='$cfg[table_header_bg]' width='27%'><div align=center class='title'>".$cfg['_DOWNLOAD'].'</div></td>';
	$displayXferDetail .= "<td bgcolor='$cfg[table_header_bg]' width='27%'><div align=center class='title'>".$cfg['_UPLOAD'].'</div></td>';
	$displayXferDetail .= '</tr>';
	$start = '';
	$download = 0;
	$upload = 0;
	foreach ($rtnValue as $row) {
		$rtime = strtotime($row[2]);
		switch ($period) {
			case 'Month Starting':
				$newstart = $cfg['month_start'].' ';
				$newstart .= (date('j',$rtime) < $cfg['month_start']) ? date('M Y',strtotime('-1 Month',$rtime)) : date('M Y',$rtime);
				break;
			case 'Week Starting':
				$newstart = date('d M Y',strtotime('+1 Day last '.$cfg['week_start'],$rtime));
				break;
			case 'Day':
				$newstart = $row[2];
				break;
		}
		if ($row[2] == date('Y-m-d')) {
			if ($user_id == '%') {
				$row[0] = $xfer_total['day']['download'];
				$row[1] = $xfer_total['day']['upload'];
			} else {
				$row[0] = $xfer[$user_id]['day']['download'];
				$row[1] = $xfer[$user_id]['day']['upload'];
			}
		}
		if ($start != $newstart) {
			if ($upload + $download != 0) {
				$displayXferDetail .= '<tr>';
					$displayXferDetail .= "<td>$rowstr</td>";
					$downloadstr = formatFreeSpace($download/(1024*1024));
					$uploadstr = formatFreeSpace($upload/(1024*1024));
					$totalstr = formatFreeSpace(($download+$upload)/(1024*1024));
					$displayXferDetail .= "<td><div class='tiny' align='center'><b>$totalstr</b></div></td>";
					$displayXferDetail .= "<td><div class='tiny' align='center'>$downloadstr</div></td>";
					$displayXferDetail .= "<td><div class='tiny' align='center'>$uploadstr</div></td>";
				$displayXferDetail .= '</tr>';
			}
			$download = $row[0];
			$upload = $row[1];
			$start = $newstart;
		}
		else {
			$download += $row[0];
			$upload += $row[1];
		}
		switch ($period) {
			case 'Month Starting':
				$rowstr = "<a href='index.php?iid=xfer&op=xfer&user=$user_id&month=".date('Y-m',strtotime($start))."'>$start</a>";
				break;
			case 'Week Starting':
				$rowstr = "<a href='index.php?iid=xfer&op=xfer&user=$user_id&month=". @ $_GET[month] . "&week=".date('Y-m-d',strtotime($start))."'>$start</a>";
				break;
			case 'Day':
				$rowstr = $start;
				break;
		}
	}
	if ($upload + $download != 0) {
		$displayXferDetail .= '<tr>';
		$displayXferDetail .= "<td>$rowstr</td>";
		$downloadstr = formatFreeSpace($download/(1024*1024));
		$uploadstr = formatFreeSpace($upload/(1024*1024));
		$totalstr = formatFreeSpace(($download+$upload)/(1024*1024));
		$displayXferDetail .= "<td><div class='tiny' align='center'><b>$totalstr</b></div></td>";
		$displayXferDetail .= "<td><div class='tiny' align='center'>$downloadstr</div></td>";
		$displayXferDetail .= "<td><div class='tiny' align='center'>$uploadstr</div></td>";
		$displayXferDetail .= '</tr>';
	}
	$displayXferDetail .= '</table><br>';
	return $displayXferDetail;
}

//XFER:****************************************************
//XFER: getXferList()
//XFER: get top summary table of xfer usage page
function getXferList() {
	global $cfg, $xfer, $xfer_total, $db;
	$displayXferList = "<table width='760' border=1 bordercolor='$cfg[table_admin_border]' cellpadding='2' cellspacing='0' bgcolor='$cfg[table_data_bg]'>";
	$displayXferList .= '<tr>';
	$displayXferList .= "<td bgcolor='$cfg[table_header_bg]' width='15%'><div align=center class='title'>".$cfg['_USER'].'</div></td>';
	$displayXferList .= "<td bgcolor='$cfg[table_header_bg]' width='22%'><div align=center class='title'>".$cfg['_TOTALXFER'].'</div></td>';
	$displayXferList .= "<td bgcolor='$cfg[table_header_bg]' width='22%'><div align=center class='title'>".$cfg['_MONTHXFER'].'</div></td>';
	$displayXferList .= "<td bgcolor='$cfg[table_header_bg]' width='22%'><div align=center class='title'>".$cfg['_WEEKXFER'].'</div></td>';
	$displayXferList .= "<td bgcolor='$cfg[table_header_bg]' width='22%'><div align=center class='title'>".$cfg['_DAYXFER'].'</div></td>';
	$displayXferList .= '</tr>';
	$sql = 'SELECT user_id FROM tf_users ORDER BY user_id';
	$rtnValue = $db->GetCol($sql);
	showError($db,$sql);
	foreach ($rtnValue as $user_id) {
		$displayXferList .= '<tr>';
		$displayXferList .= '<td><a href="index.php?iid=xfer&op=xfer&user='.$user_id.'">'.$user_id.'</a></td>';
		$total = formatFreeSpace($xfer[$user_id]['total']['total']/(1024*1024));
		$month = formatFreeSpace(@ $xfer[$user_id]['month']['total']/(1024*1024));
		$week = formatFreeSpace(@ $xfer[$user_id]['week']['total']/(1024*1024));
		$day = formatFreeSpace(@ $xfer[$user_id]['day']['total']/(1024*1024));
		$displayXferList .= '<td><div class="tiny" align="center">'.$total.'</div></td>';
		$displayXferList .= '<td><div class="tiny" align="center">'.$month.'</div></td>';
		$displayXferList .= '<td><div class="tiny" align="center">'.$week.'</div></td>';
		$displayXferList .= '<td><div class="tiny" align="center">'.$day.'</div></td>';
		$displayXferList .= '</tr>';
	}
	$displayXferList .= '<td><a href="index.php?iid=xfer&op=xfer&user=%"><b>'.$cfg['_TOTAL'].'</b></a></td>';
	$total = formatFreeSpace($xfer_total['total']['total']/(1024*1024));
	$month = formatFreeSpace($xfer_total['month']['total']/(1024*1024));
	$week = formatFreeSpace($xfer_total['week']['total']/(1024*1024));
	$day = formatFreeSpace($xfer_total['day']['total']/(1024*1024));
	$displayXferList .= '<td><div class="tiny" align="center"><b>'.$total.'</b></div></td>';
	$displayXferList .= '<td><div class="tiny" align="center"><b>'.$month.'</b></div></td>';
	$displayXferList .= '<td><div class="tiny" align="center"><b>'.$week.'</b></div></td>';
	$displayXferList .= '<td><div class="tiny" align="center"><b>'.$day.'</b></div></td>';
	$displayXferList .= '</table>';
	return $displayXferList;
}

?>