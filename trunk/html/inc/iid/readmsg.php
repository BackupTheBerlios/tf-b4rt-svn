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

// user-check
if (empty($cfg["user"])) {
	 // the user probably hit this page direct
	header("location: index.php?iid=index");
	exit();
}

// delete
if (isset($_REQUEST['delete'])) {
	$delete = getRequestVar('delete');
	if (!empty($delete))
		DeleteMessage($delete);
	header("location: index.php?iid=readmsg");
	exit();
}

// create template-instance
$tmpl = tmplGetInstance($cfg["theme"], "page.readmsg.tmpl");

if (isset($_REQUEST['mid'])) {
	$mid = getRequestVar('mid');
	list($from_user, $message, $ip, $time, $isnew, $force_read) = GetMessage($mid);
	if (!empty($from_user) && $isnew == 1) {
		// We have a Message that is being seen
		// Mark it as NOT new.
		MarkMessageRead($mid);
	}
	$message = check_html($message, "a");
	$message = str_replace("\n", "<br>", $message);
	if (IsUser($from_user))
		$tmpl->setvar('IsUser'. 1);
	$tmpl->setvar('from_user', $from_user);
	$tmpl->setvar('message', $message);
	$tmpl->setvar('mid', $mid);
} else {
	// read and display all messages in a list.
	$inx = 0;
	$sql = "SELECT mid, from_user, message, IsNew, ip, time, force_read FROM tf_messages WHERE to_user=".$db->qstr($cfg["user"])." ORDER BY time";
	$result = $db->Execute($sql);
	showError($db,$sql);
	$message_list = array();
	while (list($mid, $from_user, $message, $new, $ip, $time, $force_read) = $result->FetchRow()) {
		if ($new == 1)
			$mail_image = "themes/".$cfg['theme']."/images/new_message.gif";
		else
			$mail_image = "themes/".$cfg['theme']."/images/old_message.gif";
		$display_message = check_html($message, "nohtml");
		if (strlen($display_message) >= 40)
			$display_message = substr($display_message, 0, 39)."...";
		// No, let them reply or delete it
		if (IsUser($from_user))
			$IsUser2 = 1;
		else
			$IsUser2 = 0;
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
	}
	if ($inx == 0) {
		$tmpl->setvar('no_inx', 1);
		$tmpl->setvar('_NORECORDSFOUND', $cfg['_NORECORDSFOUND']);
	} else {
		$tmpl->setvar('no_inx', 0);
		$tmpl->setloop('message_list', $message_list);
	}
	$tmpl->setvar('messageList', getMessageList());
}

// set vars
$tmpl->setvar('date1', date($cfg['_DATETIMEFORMAT'], $time));
//
$tmpl->setvar('_FROM', $cfg['_FROM']);
$tmpl->setvar('_REPLY', $cfg['_REPLY']);
$tmpl->setvar('_DELETE', $cfg['_DELETE']);
$tmpl->setvar('_DATE', $cfg['_DATE']);
$tmpl->setvar('_ADMIN', $cfg['_ADMIN']);
$tmpl->setvar('_MESSAGE', $cfg['_MESSAGE']);
$tmpl->setvar('_RETURNTOMESSAGES', $cfg['_RETURNTOMESSAGES']);
//
tmplSetTitleBar($cfg["pagetitle"].' - '.$cfg['_MESSAGES']);
tmplSetFoot();
$tmpl->setvar('iid', $_GET["iid"]);

// parse template
$tmpl->pparse();

?>