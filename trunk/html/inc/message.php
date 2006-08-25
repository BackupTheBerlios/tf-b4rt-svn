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

# create new template
if (!ereg('^[^./][^/]*$', $cfg["theme"])) {
	$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/message.tmpl");
}
else {
	$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/message.tmpl");
}

$to_user = getRequestVar('to_user');
if(empty($to_user) or empty($cfg['user'])) {
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
	SaveMessage($to_user, $cfg['user'], $message, $to_all, $force_read);
	header("location: index.php?iid=readmsg");
} else {
	$rmid = getRequestVar('rmid');
	if(!empty($rmid)) {
		list($from_user, $message, $ip, $time) = GetMessage($rmid);
		$message = _DATE.": ".date(_DATETIMEFORMAT, $time)."\n".$from_user." "._WROTE.":\n\n".$message;
		$message = ">".str_replace("\n", "\n>", $message);
		$message = "\n\n\n".$message;
	}
	$tmpl->setvar('no_message', 1);
	$tmpl->setvar('head', getHead(_SENDMESSAGETITLE));
	$tmpl->setvar('table_data_bg', $cfg["table_data_bg"]);
	$tmpl->setvar('_TO', _TO);
	$tmpl->setvar('to_user', $to_user);
	$tmpl->setvar('user', $cfg['user']);
	$tmpl->setvar('_FROM', _FROM);
	$tmpl->setvar('_YOURMESSAGE', _YOURMESSAGE);
	$tmpl->setvar('message', $message);
	$tmpl->setvar('_SENDTOALLUSERS', _SENDTOALLUSERS);
	$tmpl->setvar('_FORCEUSERSTOREAD', _FORCEUSERSTOREAD);
	if (IsAdmin()) {
		$tmpl->setvar('is_admin',1);
	}
	$tmpl->setvar('_SEND', _SEND);
	$tmpl->setvar('foot', getFoot());
} // end the else

$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('index_page', $cfg["index_page"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('iid', $_GET["iid"]);
$tmpl->pparse();
?>