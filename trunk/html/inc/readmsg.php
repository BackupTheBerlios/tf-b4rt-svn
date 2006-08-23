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

require_once("config.php");
require_once("functions.php");


# create new template
if (!ereg('^[^./][^/]*$', $cfg["theme"])) {
	$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/readmsg.tmpl");
}
else {
	$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/readmsg.tmpl");
}

if(empty($cfg['user'])) {
	 // the user probably hit this page direct
	header("location: index.php?iid=index");
	exit;
}

$delete = getRequestVar('delete');
if(!empty($delete)) {
	DeleteMessage($delete);
	header("location: ".$_SERVER['PHP_SELF']);
}

$mid = getRequestVar('mid');
if (!empty($mid)) {
	$tmpl->setvar('mid', 1);
	list($from_user, $message, $ip, $time, $isnew, $force_read) = GetMessage($mid);
	if(!empty($from_user) && $isnew == 1) {
		// We have a Message that is being seen
		// Mark it as NOT new.
		MarkMessageRead($mid);
	}
	//$message = check_html($message, "nohtml");
	$message = check_html($message, "a");
	$message = str_replace("\n", "<br>", $message);

	if (IsUser($from_user)) {
		$tmpl->setvar('IsUser'. 1);
	}
} else {
	// read and display all messages in a list.
	$inx = 0;
	$sql = "SELECT mid, from_user, message, IsNew, ip, time, force_read FROM tf_messages WHERE to_user=".$db->qstr($cfg['user'])." ORDER BY time";
	$result = $db->Execute($sql);
	showError($db,$sql);
	$message_list = array();
	while(list($mid, $from_user, $message, $new, $ip, $time, $force_read) = $result->FetchRow()) {
		if($new == 1)
			$mail_image = "images/new_message.gif";
		else
			$mail_image = "images/old_message.gif";
		$display_message = check_html($message, "nohtml");
		if(strlen($display_message) >= 40) { // needs to be trimmed
			$display_message = substr($display_message, 0, 39);
			$display_message .= "...";
		}
		// No, let them reply or delete it
		if (IsUser($from_user)) {
			$IsUser2 = 1;
		}
		array_push($message_list, array (
			'mid' => $mid,
			'mail_image' => $mail_image,
			'from_user' => $from_user,
			'display_message' => $display_message,
			'date2' => date(_DATETIMEFORMAT, $time),
			'force_read' => $force_read,
			'IsUser2' => $IsUser2,
			)
		);
		$inx++;
	} // End While
	if($inx == 0) {
		$tmpl->setvar('no_inx', 1);
		$tmpl->setvar('_NORECORDSFOUND', _NORECORDSFOUND);
	}
	else {
		$tmpl->setloop('message_list', $message_list);
	}
} // end the else

# a page is nothing without vars
$tmpl->setvar('head', getHead(_MESSAGES));
$tmpl->setvar('foot', getFoot());
$tmpl->setvar('_RETURNTOMESSAGES', _RETURNTOMESSAGES);
$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
$tmpl->setvar('_FROM', _FROM);
$tmpl->setvar('from_user', $from_user);
$tmpl->setvar('mid', $mid);
$tmpl->setvar('_REPLY', _REPLY);
$tmpl->setvar('_DELETE', _DELETE);
$tmpl->setvar('_DATE', _DATE);
$tmpl->setvar('date1', date(_DATETIMEFORMAT, $time));
$tmpl->setvar('table_data_bg', $cfg["table_data_bg"]);
$tmpl->setvar('_MESSAGE', _MESSAGE);
$tmpl->setvar('message', $message);
$tmpl->setvar('messageList', getMessageList());
$tmpl->setvar('_ADMIN', _ADMIN);
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('index_page', $cfg["index_page"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('page', $_GET["page"]);
# lets parse the hole thing
$tmpl->pparse();
?>