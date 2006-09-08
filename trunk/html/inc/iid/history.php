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

// common functions
require_once('inc/functions/functions.common.php');

// create template-instance
$tmpl = getTemplateInstance($cfg["theme"], "history.tmpl");

$offset = 50;
$inx = 0;
$min = 0;
if (isset($_REQUEST['min']))
	$min = $_REQUEST['min'];
$max = $min+$offset;
$output = "";

// "Only Admin can see other user torrents"
$sql = "";
if ($cfg['enable_restrictivetview'] == 0) {
	$sql = "SELECT user_id, file, time FROM tf_log WHERE action=".$db->qstr($cfg["constants"]["url_upload"])." OR action=".$db->qstr($cfg["constants"]["file_upload"])." ORDER BY time desc";
} else {
	if (IsAdmin() == true)
		$sql = "SELECT user_id, file, time FROM tf_log WHERE action=".$db->qstr($cfg["constants"]["url_upload"])." OR action=".$db->qstr($cfg["constants"]["file_upload"])." ORDER BY time desc";
	else
		$sql = "SELECT user_id, file, time FROM tf_log WHERE user_id='".$cfg["user"]."' AND ( action=".$db->qstr($cfg["constants"]["url_upload"])." OR action=".$db->qstr($cfg["constants"]["file_upload"])." ) ORDER BY time desc";
}
// "Only Admin can see other user torrents"

$result = $db->SelectLimit($sql, $offset, $min);
$file_result = array();
while(list($user_id, $file, $time) = $result->FetchRow()) {
	$user_icon = "themes/".$cfg['theme']."/images/user_offline.gif";
	if (IsOnline($user_id)) {
		$user_icon = "themes/".$cfg['theme']."/images/user.gif";
	}
	array_push($file_result, array(
		'user_id' => $user_id,
		'user_icon' => $user_icon,
		'file' => $file,
		'date' => date($cfg['_DATETIMEFORMAT'], $time),
		)
	);
	$inx++;
}
if($inx == 0) {
	$tmpl->setvar('inx', 1);
	$tmpl->setvar('_NORECORDSFOUND', $cfg['_NORECORDSFOUND']);
}
$tmpl->setloop('file_result', $file_result);

$prev = ($min-$offset);
if ($prev>=0) {
	$tmpl->setvar('prevlink', 1);
	$prevlink = 1;
} else {
	$tmpl->setvar('prevlink', 0);
	$prevlink = 0;
}
$next=$min+$offset;
if ($inx>=$offset) {
	$tmpl->setvar('morelink', 1);
	$morelink = 1;
} else {
	$tmpl->setvar('morelink', 0);
	$morelink = 0;
}

$tmpl->setvar('empty', 0);
if(!empty($prevlink) && !empty($morelink))
	$tmpl->setvar('empty', 1);
elseif ((!empty($prevlink)) && (empty($morelink)))
	$tmpl->setvar('empty', 2);
elseif ((!empty($morelink)) && (empty($prevlink)))
	$tmpl->setvar('empty', 3);

# define some things
$tmpl->setvar('head', getHead($cfg['_UPLOADHISTORY']));
$tmpl->setvar('foot', getFoot());
$tmpl->setvar('_SHOWPREVIOUS', $cfg['_SHOWPREVIOUS']);
$tmpl->setvar('_SHOWMORE', $cfg['_SHOWMORE']);
$tmpl->setvar('prev', $prev);
$tmpl->setvar('min', $min);
$tmpl->setvar('max', $max);
$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
$tmpl->setvar('table_data_bg', $cfg["table_data_bg"]);
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('_UPLOADACTIVITY', $cfg['_UPLOADACTIVITY']);
$tmpl->setvar('days_to_keep', $cfg["days_to_keep"]);
$tmpl->setvar('_DAYS', $cfg['_DAYS']);
$tmpl->setvar('_USER', $cfg['_USER']);
$tmpl->setvar('_FILE', $cfg['_FILE']);
$tmpl->setvar('_TIMESTAMP', $cfg['_TIMESTAMP']);
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('iid', $_GET["iid"]);
$tmpl->pparse();

?>