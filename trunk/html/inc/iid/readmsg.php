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
$tmpl = getTemplateInstance($cfg["theme"], "readmsg.tmpl");

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
			$mail_image = "themes/".$cfg['theme']."/images/new_message.gif";
		else
			$mail_image = "themes/".$cfg['theme']."/images/old_message.gif";
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
			'date2' => date($cfg['_DATETIMEFORMAT'], $time),
			'force_read' => $force_read,
			'IsUser2' => $IsUser2,
			)
		);
		$inx++;
	} // End While
	if($inx == 0) {
		$tmpl->setvar('no_inx', 1);
		$tmpl->setvar('_NORECORDSFOUND', $cfg['_NORECORDSFOUND']);
	}
	else {
		$tmpl->setloop('message_list', $message_list);
	}
} // end the else

# a page is nothing without vars
$tmpl->setvar('head', getHead($cfg['_MESSAGES']));
$tmpl->setvar('foot', getFoot());
$tmpl->setvar('_RETURNTOMESSAGES', $cfg['_RETURNTOMESSAGES']);
$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
$tmpl->setvar('_FROM', $cfg['_FROM']);
$tmpl->setvar('from_user', $from_user);
$tmpl->setvar('mid', $mid);
$tmpl->setvar('_REPLY', $cfg['_REPLY']);
$tmpl->setvar('_DELETE', $cfg['_DELETE']);
$tmpl->setvar('_DATE', $cfg['_DATE']);
$tmpl->setvar('date1', date($cfg['_DATETIMEFORMAT'], $time));
$tmpl->setvar('table_data_bg', $cfg["table_data_bg"]);
$tmpl->setvar('_MESSAGE', $cfg['_MESSAGE']);
$tmpl->setvar('message', $message);
$tmpl->setvar('messageList', getMessageList());
$tmpl->setvar('_ADMIN', $cfg['_ADMIN']);
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('iid', $_GET["iid"]);
# lets parse the hole thing
$tmpl->pparse();

?>