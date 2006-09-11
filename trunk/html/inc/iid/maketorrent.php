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

// maketorrent
require_once("inc/functions/functions.maketorrent.php");

/*******************************************************************************
 * torrent download
 ******************************************************************************/
if(!empty($_REQUEST["download"]))
	downloadTorrent($_REQUEST["download"]);

/*******************************************************************************
 * page + create
 ******************************************************************************/
// config
loadSettings('tf_settings_dir');

// file + torrent vars
$file = @ $_GET['path'];
$torrent = @ cleanFileName(StripFolders(trim($file))).".torrent";

// check if there is a var sent for client, if not use default
if (isset($_REQUEST["client"]))
	$client = $_REQUEST["client"];
else
	$client = $cfg["dir_maketorrent_default"];

// client-switch
switch ($client) {
	default:
	case "tornado":
		if (isset($_POST['file']))
			$file = $_POST['file'];
		$tfile = @ $_POST['torrent'];
		$announce = @ ($_POST['announce']) ? $_POST['announce'] : "http://";
		$ancelist = @ $_POST['announcelist'];
		$comment = @ $_POST['comments'];
		$piece = @ $_POST['piecesize'];
		$alert = @ ($_POST['alert']) ? 1 : "";
		$private = @ ($_POST['Private'] == "Private") ? true : false;
		$dht = @ ($_POST['DHT'] == "DHT") ? true : false;
		break;
	case "mainline":
		// TODO
		break;
}

/*******************************************************************************
 * create request
 ******************************************************************************/
if (!empty($_REQUEST["create"])) {
	switch ($_REQUEST["create"]) {
		default:
		case "tornado":
			$onLoad = createTorrentTornado();
			break;
		case "mainline":
			// TODO
			break;
	}
}

/*******************************************************************************
 * page
 ******************************************************************************/
// create template-instance
$tmpl = getTemplateInstance($cfg["theme"], "maketorrent.tmpl");
// set vars
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('main_bgcolor', $cfg["main_bgcolor"]);
$tmpl->setvar('table_border_dk', $cfg["table_border_dk"]);
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
$tmpl->setvar('body_data_bg', $cfg["body_data_bg"]);
$tmpl->setvar('getTitleBar', getTitleBar($cfg["pagetitle"]." - Torrent Maker", false));
//
$tmpl->setvar('file', $file);
$tmpl->setvar('torrent', $torrent);
// client-specific
$tmpl->setvar('client', $client);
$tmpl->setvar('client_select_action', $_SERVER['REQUEST_URI']);
switch ($client) {
	default:
	case "tornado":
		$tmpl->setvar('form_action', $_SERVER['REQUEST_URI']."&create=tornado");
		if ((!empty($private)) && ($private))
			$tmpl->setvar('is_private', 1);
		else
			$tmpl->setvar('is_private', 0);
		if (!empty($onLoad))
			$tmpl->setvar('onLoad', $onLoad);
		$tmpl->setvar('announce', $announce);
		$tmpl->setvar('ancelist', $ancelist);
		$tmpl->setvar('comment', $comment);
		$tmpl->setvar('dht', $dht);
		$tmpl->setvar('alert', $alert);
		break;
	case "mainline":
		$tmpl->setvar('form_action', $_SERVER['REQUEST_URI']."&create=mainline");
		// TODO
		break;
}
$tmpl->setvar('getTorrentFluxLink', getTorrentFluxLink());
$tmpl->setvar('iid', $_GET["iid"]);
$tmpl->pparse();

?>