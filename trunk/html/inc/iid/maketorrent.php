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

// prevent direct invocation
if (!isset($cfg['user'])) {
	@ob_end_clean();
	header("location: ../../index.php");
	exit();
}

/******************************************************************************/

// common functions
require_once('inc/functions/functions.common.php');

// maketorrent
require_once("inc/functions/functions.maketorrent.php");

/*******************************************************************************
 * torrent download
 ******************************************************************************/
if ((isset($_REQUEST["download"])) && (!(empty($_REQUEST["download"]))))
	downloadTorrent($_REQUEST["download"]);

/*******************************************************************************
 * create + page
 ******************************************************************************/
// config
loadSettings('tf_settings_dir');

// file + torrent vars
$path = @ $_REQUEST['path'];
$torrent = @cleanFileName(StripFolders(trim($path))).".torrent";

// check if there is a var sent for client, if not use default
if (isset($_REQUEST["client"]))
	$client = $_REQUEST["client"];
else
	$client = $cfg["dir_maketorrent_default"];

// client-generic vars
$tfile = @ $_POST['torrent'];
$comment = @ $_POST['comments'];
$alert = @ ($_POST['alert']) ? 1 : "";

// client-switch
switch ($client) {
	default:
	case "tornado":
		$announce = @ ($_POST['announce']) ? $_POST['announce'] : "http://";
		$ancelist = @ $_POST['announcelist'];
		$private = @ ($_POST['Private'] == "Private") ? true : false;
		$dht = @ ($_POST['DHT'] == "DHT") ? true : false;
		$piece = @ $_POST['piecesize'];
		break;
	case "mainline":
		if (isset($_POST['use_tracker']))
			$use_tracker = $_POST['use_tracker'];
		else
			$use_tracker = 1;
		if (isset($_POST['tracker_name']))
			$tracker_name = $_POST['tracker_name'];
		else
			$tracker_name = "http://";
		if (isset($_POST['piecesize']))
			$piece = $_POST['piecesize'];
		else
			$piece = 0;
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
			$onLoad = createTorrentMainline();
			break;
	}
}

/*******************************************************************************
 * page
 ******************************************************************************/

// create template-instance
$tmpl = tmplGetInstance($cfg["theme"], "page.maketorrent.tmpl");

// set vars
$tmpl->setvar('path', $path);
$tmpl->setvar('torrent', $torrent);
$tmpl->setvar('comment', $comment);
if (!empty($onLoad))
	$tmpl->setvar('onLoad', $onLoad);
$tmpl->setvar('alert', $alert);
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
		$tmpl->setvar('announce', $announce);
		$tmpl->setvar('ancelist', $ancelist);
		$tmpl->setvar('dht', $dht);
		break;
	case "mainline":
		$tmpl->setvar('form_action', $_SERVER['REQUEST_URI']."&create=mainline");
		$tmpl->setvar('use_tracker', $use_tracker);
		$tmpl->setvar('tracker_name', $tracker_name);
		$tmpl->setvar('piecesize', $piece);
		break;
}
//
$tmpl->setvar('getTorrentFluxLink', getTorrentFluxLink());
//
tmplSetTitleBar($cfg["pagetitle"]." - Torrent Maker", false);
//
$tmpl->setvar('iid', $_REQUEST["iid"]);

// parse template
$tmpl->pparse();

?>