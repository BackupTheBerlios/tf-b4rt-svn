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

/*************************************************************
*  TorrentFlux xfer Statistics hack
*  blackwidow - matt@mattjanssen.net
**************************************************************/
/*
	TorrentFlux xfer Statistics hack is free code; you can redistribute it
	and/or modify it under the terms of the GNU General Public License as
	published by the Free Software Foundation; either version 2 of the License,
	or (at your option) any later version.
*/

// common functions
require_once('inc/functions/functions.common.php');

// xfer functions
require_once('inc/functions/functions.xfer.php');

// create template-instance
$tmpl = tmplGetInstance($cfg["theme"], "xfer.tmpl");

// set vars
if ($cfg['enable_xfer'] == 1) {
	$tmpl->setvar('is_xfer', 1);
	// getTransferListArray to update xfer-stats
	$cfg['xfer_realtime'] = 1;
	@getTransferListArray();
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
	if (($cfg['enable_public_xfer'] == 1 ) || $isAdmin) {
		$tmpl->setvar('show_xfer', 1);
		$sql = 'SELECT user_id FROM tf_users ORDER BY user_id';
		$rtnValue = $db->GetCol($sql);
		showError($db,$sql);
		$user_list = array();
		foreach ($rtnValue as $user_id) {
			array_push($user_list, array(
				'user_id' => $user_id,
				'total' => formatFreeSpace(@ $xfer["$user_id"]['total']['total'] / (1048576)),
				'month' => formatFreeSpace(@ $xfer["$user_id"]['month']['total'] / (1048576)),
				'week' => formatFreeSpace(@ $xfer["$user_id"]['week']['total'] / (1048576)),
				'day' => formatFreeSpace(@ $xfer["$user_id"]['day']['total'] / (1048576)),
				)
			);
		}
		$tmpl->setloop('user_list', $user_list);
		$tmpl->setvar('total_total', formatFreeSpace(@ $xfer_total['total']['total'] / (1048576)));
		$tmpl->setvar('total_month', formatFreeSpace(@ $xfer_total['month']['total'] / (1048576)));
		$tmpl->setvar('total_week', formatFreeSpace(@ $xfer_total['week']['total'] / (1048576)));
		$tmpl->setvar('total_day', formatFreeSpace(@ $xfer_total['day']['total'] / (1048576)));
		if (isset($_REQUEST['user']) && ($_REQUEST['user'] != "%")) {
			$tmpl->setvar('user', $_REQUEST['user']);
			if (isset($_REQUEST['month'])) {
				$mstart = $_REQUEST['month'].'-'.$cfg['month_start'];
				$mend = date('Y-m-d',strtotime('+1 Month',strtotime($mstart)));
			} else {
				$mstart = 0;
				$mend = 0;
			}
			if (isset($_REQUEST['week'])) {
				$wstart = $_REQUEST['week'];
				$wend = date('Y-m-d',strtotime('+1 Week',strtotime($_REQUEST['week'])));
			} else {
				$wstart = $mstart;
				$wend = $mend;
			}
			// month stats
			$sql = 'SELECT SUM(download) AS download, SUM(upload) AS upload, date FROM tf_xfer WHERE user LIKE "'.$_REQUEST["user"].'" GROUP BY date ORDER BY date';
			$rtnValue = $db->GetAll($sql);
			showError($db,$sql);
			$start = '';
			$download = 0;
			$upload = 0;
			$month_list = array();
			foreach ($rtnValue as $row) {
				$rtime = strtotime($row[2]);
				$newstart = $cfg['month_start'].' ';
				$newstart .= (date('j',$rtime) < $cfg['month_start']) ? date('M Y',strtotime('-1 Month',$rtime)) : date('M Y',$rtime);
				if ($start != $newstart) {
					if ($upload + $download != 0) {
						array_push($month_list, array(
							'user_id' => $_REQUEST["user"],
							'month' => date('Y-m',strtotime($start)),
							'start' => $start,
							'downloadstr' => formatFreeSpace($download / (1048576)),
							'uploadstr' => formatFreeSpace($upload / (1048576)),
							'totalstr' => formatFreeSpace(($download + $upload) / (1048576)),
							)
						);
					}
					$download = $row[0];
					$upload = $row[1];
					$start = $newstart;
				}
				else {
					$download += $row[0];
					$upload += $row[1];
				}
			}
			if ($upload + $download != 0) {
				array_push($month_list, array(
					'user_id' => $_REQUEST["user"],
					'month' => date('Y-m',strtotime($start)),
					'start' => $start,
					'downloadstr' => formatFreeSpace($download / (1048576)),
					'uploadstr' => formatFreeSpace($upload / (1048576)),
					'totalstr' => formatFreeSpace(($download + $upload) / (1048576)),
					)
				);
			}
			$tmpl->setloop('month_list', $month_list);
			// weekly stats
			$period_query = ($mstart) ? 'and date >= "'.$mstart.'" and date < "'.$mend.'"' : '';
			$sql = 'SELECT SUM(download) AS download, SUM(upload) AS upload, date FROM tf_xfer WHERE user LIKE "'.$_REQUEST["user"].'" '.$period_query.' GROUP BY date ORDER BY date';
			$rtnValue = $db->GetAll($sql);
			showError($db,$sql);
			$start = '';
			$download = 0;
			$upload = 0;
			$week_list = array();
			foreach ($rtnValue as $row) {
				$rtime = strtotime($row[2]);
				$newstart = date('d M Y',strtotime('+1 Day last '.$cfg['week_start'],$rtime));
				if ($start != $newstart) {
					if ($upload + $download != 0) {
						array_push($week_list, array(
							'user_id' => $_REQUEST["user"],
							'month' => @ $_REQUEST["month"],
							'week' => date('Y-m-d',strtotime($start)),
							'start' => $start,
							'downloadstr' => formatFreeSpace($download / (1048576)),
							'uploadstr' => formatFreeSpace($upload / (1048576)),
							'totalstr' => formatFreeSpace(($download+$upload) / (1048576)),
							)
						);
					}
					$download = $row[0];
					$upload = $row[1];
					$start = $newstart;
				}
				else {
					$download += $row[0];
					$upload += $row[1];
				}
			}
			if ($upload + $download != 0) {
				array_push($week_list, array(
					'user_id' => $_REQUEST["user"],
					'month' => @ $_REQUEST["month"],
					'week' => date('Y-m-d',strtotime($start)),
					'start' => $start,
					'downloadstr' => formatFreeSpace($download / (1048576)),
					'uploadstr' => formatFreeSpace($upload / (1048576)),
					'totalstr' => formatFreeSpace(($download+$upload) / (1048576)),
					)
				);
			}
			$tmpl->setloop('week_list', $week_list);
			// daily stats
			$period_query = ($wstart) ? 'and date >= "'.$wstart.'" and date < "'.$wend.'"' : '';
			$sql = 'SELECT SUM(download) AS download, SUM(upload) AS upload, date FROM tf_xfer WHERE user LIKE "'.$_REQUEST["user"].'" '.$period_query.' GROUP BY date ORDER BY date';
			$rtnValue = $db->GetAll($sql);
			showError($db,$sql);
			$start = '';
			$download = 0;
			$upload = 0;
			$day_list = array();
			foreach ($rtnValue as $row) {
				$rtime = strtotime($row[2]);
				$newstart = $row[2];
				if ($row[2] == date('Y-m-d')) {
					if ($user_id == '%') {
						$row[0] = $xfer_total['day']['download'];
						$row[1] = $xfer_total['day']['upload'];
					} else {
						$row[0] = $xfer[$_REQUEST["user"]]['day']['download'];
						$row[1] = $xfer[$_REQUEST["user"]]['day']['upload'];
					}
				}
				if ($upload + $download != 0) {
					array_push($day_list, array(
						'start' => $start,
						'downloadstr' => formatFreeSpace($download / (1048576)),
						'uploadstr' => formatFreeSpace($upload / (1048576)),
						'totalstr' => formatFreeSpace(($download+$upload) / (1048576)),
						)
					);
				}
				$download = $row[0];
				$upload = $row[1];
				$start = $newstart;
			}
			if ($upload + $download != 0) {
				array_push($day_list, array(
					'start' => $start,
					'downloadstr' => formatFreeSpace($download / (1048576)),
					'uploadstr' => formatFreeSpace($upload / (1048576)),
					'totalstr' => formatFreeSpace(($download+$upload) / (1048576)),
					)
				);
			}
			$tmpl->setloop('day_list', $day_list);
		}
		//
		$tmpl->setvar('_TOTAL', $cfg["_TOTAL"]);
		$tmpl->setvar('_SERVERXFERSTATS', $cfg['_SERVERXFERSTATS']);
		$tmpl->setvar('_USERDETAILS', $cfg['_USERDETAILS']);
		$tmpl->setvar('_USER', $cfg["_USER"]);
		$tmpl->setvar('_TOTALXFER', $cfg["_TOTALXFER"]);
		$tmpl->setvar('_MONTHXFER', $cfg["_MONTHXFER"]);
		$tmpl->setvar('_WEEKXFER', $cfg["_WEEKXFER"]);
		$tmpl->setvar('_DAYXFER', $cfg["_DAYXFER"]);
		$tmpl->setvar('_DOWNLOAD', $cfg['_DOWNLOAD']);
		$tmpl->setvar('_UPLOAD', $cfg['_UPLOAD']);
	}
} else {
	$tmpl->setvar('is_xfer', 0);
}
//
tmplSetTitleBar($cfg["pagetitle"].' - '.$cfg['_XFER']);
tmplSetFoot();
$tmpl->setvar('iid', $_GET["iid"]);

// parse template
$tmpl->pparse();

?>