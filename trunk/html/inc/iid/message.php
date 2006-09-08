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
$tmpl = getTemplateInstance($cfg["theme"], "message.tmpl");

$to_user = getRequestVar('to_user');
if(empty($to_user) or empty($cfg["user"])) {
	 // the user probably hit this page direct
	header("location: index.php?iid=index");
	exit;
}

$message = getRequestVar('message');
if (!empty($message)) {
	$to_all = getRequestVar('to_all');
	if(!empty($to_all))
		$to_all = 1;
	else
		$to_all = 0;
	$force_read = getRequestVar('force_read');
	if(!empty($force_read) && IsAdmin())
		$force_read = 1;
	else
		$force_read = 0;
	$message = check_html($message, "nohtml");
	SaveMessage($to_user, $cfg["user"], $message, $to_all, $force_read);
	header("location: index.php?iid=readmsg");
} else {
	$rmid = getRequestVar('rmid');
	if(!empty($rmid)) {
		list($from_user, $message, $ip, $time) = GetMessage($rmid);
		$message = $cfg['_DATE'].": ".date($cfg['_DATETIMEFORMAT'], $time)."\n".$from_user." ".$cfg['_WROTE'].":\n\n".$message;
		$message = ">".str_replace("\n", "\n>", $message);
		$message = "\n\n\n".$message;
	}
	$tmpl->setvar('no_message', 1);
	$tmpl->setvar('head', getHead($cfg['_SENDMESSAGETITLE']));
	$tmpl->setvar('table_data_bg', $cfg["table_data_bg"]);
	$tmpl->setvar('_TO', $cfg['_TO']);
	$tmpl->setvar('to_user', $to_user);
	$tmpl->setvar('user', $cfg["user"]);
	$tmpl->setvar('_FROM', $cfg['_FROM']);
	$tmpl->setvar('_YOURMESSAGE', $cfg['_YOURMESSAGE']);
	$tmpl->setvar('message', $message);
	$tmpl->setvar('_SENDTOALLUSERS', $cfg['_SENDTOALLUSERS']);
	$tmpl->setvar('_FORCEUSERSTOREAD', $cfg['_FORCEUSERSTOREAD']);
	if (IsAdmin()) {
		$tmpl->setvar('is_admin',1);
	}
	$tmpl->setvar('_SEND', $cfg['_SEND']);
	$tmpl->setvar('foot', getFoot());
} // end the else

$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('iid', $_GET["iid"]);
$tmpl->pparse();

?>