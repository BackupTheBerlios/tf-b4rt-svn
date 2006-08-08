<?php

/* $Id: functions.display.php 188 2006-08-06 19:17:07Z msn_exploder $ */

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

//XFER:****************************************************
//XFER: displayXferBar(max_bytes, used_bytes, title)
//XFER: displays xfer percentage bar
function displayXferBar($total, $used, $title) {
	global $cfg;
	$remaining = max(0,$total-$used/(1024*1024));
	$percent = round($remaining/$total*100,0);
	$text = ' ('.formatFreeSpace($remaining).') '._REMAINING;
	$bgcolor = '#';
	$bgcolor .= str_pad(dechex(255-255*($percent/150)),2,0,STR_PAD_LEFT);
	$bgcolor .= str_pad(dechex(255*($percent/150)),2,0,STR_PAD_LEFT);
	$bgcolor .='00';
	$displayXferBar = '<tr>';
		$displayXferBar .= '<td width="2%" nowrap align="right"><div class="tiny">'.$title.'</div></td>';
		$displayXferBar .= '<td width="92%">';
			$displayXferBar .= '<table width="100%" border="0" cellpadding="0" cellspacing="0" style="margin-top:1px;margin-bottom:1px;"><tr>';
			$displayXferBar .= '<td bgcolor="'.$bgcolor.'" width="'.($percent+1).'%">';
	if ($percent >= 50) {
		$displayXferBar .= '<div class="tinypercent" align="center"';
		if ($percent == 100) {
			$displayXferBar .= ' style="background:#ffffff;">';
		}
		$displayXferBar .=
		$displayXferBar .= '>';
		$displayXferBar .= $percent.'%'.$text;
		$displayXferBar .= '</div>';
	}
	$displayXferBar .= '</td>';
	$displayXferBar .= '<td bgcolor="#000000" width="'.(100-$percent).'%" height="100%">';
	if ($percent < 50) {
		$displayXferBar .= '<div class="tinypercent" align="center" style="color:'.$bgcolor;
		if ($percent == 0) {
			$displayXferBar .= '; background:#ffffff;">';
		}
		else {
			$displayXferBar .= ';">';
		}
		$displayXferBar .= $percent.'%'.$text;
		$displayXferBar .= '</div>';
	}
	$displayXferBar .= '</td>';
	$displayXferBar .= '</tr></table>';
	$displayXferBar .= '</td>';
	$displayXferBar .= '</tr>';
	return $displayXferBar;
}

//XFER:****************************************************
//XFER: displayXfer()
//XFER: displays xfer usage page
function displayXfer() {
	global $cfg;
	$displayXferList = displayXferList();
	if (isset($_GET['user'])) {
		$displayXferList .= '<br><b>';
		$displayXferList .= ($_GET['user'] == '%') ? _SERVERXFERSTATS : _USERDETAILS.': '.$_GET['user'];
		$displayXferList .= '</b><br>';
		displayXferDetail($_GET['user'],_MONTHSTARTING,0,0);
		if (isset($_GET['month'])) {
			$mstart = $_GET['month'].'-'.$cfg['month_start'];
			$mend = date('Y-m-d',strtotime('+1 Month',strtotime($mstart)));
		}
		else {
			$mstart = 0;
			$mend = 0;
		}
		if (isset($_GET['week'])) {
			$wstart = $_GET['week'];
			$wend = date('Y-m-d',strtotime('+1 Week',strtotime($_GET['week'])));
		}
		else {
			$wstart = $mstart;
			$wend = $mend;
		}
		$displayXferList .= displayXferDetail($_GET['user'],_WEEKSTARTING,$mstart,$mend);
		$displayXferList .= displayXferDetail($_GET['user'],_DAY,$wstart,$wend);
	}
	return $displayXferList;
}

//XFER:****************************************************
//XFER: displayXferDetail(user, period_title, start_timestamp, end_timestamp)
//XFER: display table of month/week/day's usage for user
function displayXferDetail($user_id,$period,$period_start,$period_end)
{
	global $cfg, $xfer, $xfer_total, $db;
	$period_query = ($period_start) ? 'and date >= "'.$period_start.'" and date < "'.$period_end.'"' : '';
	$sql = 'SELECT SUM(download) AS download, SUM(upload) AS upload, date FROM tf_xfer WHERE user LIKE "'.$user_id.'" '.$period_query.' GROUP BY date ORDER BY date';
	$rtnValue = $db->GetAll($sql);
	showError($db,$sql);
	$displayXferDetail = "<table width='760' border=1 bordercolor='$cfg[table_admin_border]' cellpadding='2' cellspacing='0' bgcolor='$cfg[table_data_bg]'>";
	$displayXferDetail .= '<tr>';
	$displayXferDetail .= "<td bgcolor='$cfg[table_header_bg]' width='20%'><div align=center class='title'>$period</div></td>";
	$displayXferDetail .= "<td bgcolor='$cfg[table_header_bg]' width='27%'><div align=center class='title'>"._TOTAL.'</div></td>';
	$displayXferDetail .= "<td bgcolor='$cfg[table_header_bg]' width='27%'><div align=center class='title'>"._DOWNLOAD.'</div></td>';
	$displayXferDetail .= "<td bgcolor='$cfg[table_header_bg]' width='27%'><div align=center class='title'>"._UPLOAD.'</div></td>';
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
			}
			else {
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
				$rowstr = "<a href='?op=xfer&user=$user_id&month=".date('Y-m',strtotime($start))."'>$start</a>";
			break;
			case 'Week Starting':
				$rowstr = "<a href='?op=xfer&user=$user_id&month=". @ $_GET[month] . "&week=".date('Y-m-d',strtotime($start))."'>$start</a>";
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
//XFER: dixpayXferList()
//XFER: show top summary table of xfer usage page
function displayXferList() {
	global $cfg, $xfer, $xfer_total, $db;
$displayXferList = "<table width='760' border=1 bordercolor='$cfg[table_admin_border]' cellpadding='2' cellspacing='0' bgcolor='$cfg[table_data_bg]'>";
	$displayXferList .= '<tr>';
	$displayXferList .= "<td bgcolor='$cfg[table_header_bg]' width='15%'><div align=center class='title'>"._USER.'</div></td>';
	$displayXferList .= "<td bgcolor='$cfg[table_header_bg]' width='22%'><div align=center class='title'>"._TOTALXFER.'</div></td>';
	$displayXferList .= "<td bgcolor='$cfg[table_header_bg]' width='22%'><div align=center class='title'>"._MONTHXFER.'</div></td>';
	$displayXferList .= "<td bgcolor='$cfg[table_header_bg]' width='22%'><div align=center class='title'>"._WEEKXFER.'</div></td>';
	$displayXferList .= "<td bgcolor='$cfg[table_header_bg]' width='22%'><div align=center class='title'>"._DAYXFER.'</div></td>';
	$displayXferList .= '</tr>';
	$sql = 'SELECT user_id FROM tf_users ORDER BY user_id';
	$rtnValue = $db->GetCol($sql);
	showError($db,$sql);
	foreach ($rtnValue as $user_id) {
		$displayXferList .= '<tr>';
		$displayXferList .= '<td><a href="?op=xfer&user='.$user_id.'">'.$user_id.'</a></td>';
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
	$displayXferList .= '<td><a href="?op=xfer&user=%"><b>'._TOTAL.'</b></a></td>';
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