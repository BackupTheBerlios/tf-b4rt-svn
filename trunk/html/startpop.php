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
require_once("metaInfo.php");
require_once("lib/vlib/vlibTemplate.php");

$tmpl = new vlibTemplate("themes/".$cfg["default_theme"]."/tmpl/startpop.tmpl");

$torrent = getRequestVar('torrent');
// Load saved settings
$btclient_default = $cfg["btclient"];
$torrentExists = loadTorrentSettingsToConfig($torrent);
// savepath
if ((! isset($cfg["savepath"])) || (empty($cfg["savepath"])))
	$cfg["savepath"] = $cfg["path"].getOwner($torrent).'/';
// torrent exists ?
$torrentExists = (getTorrentDataSize($torrent) > 0);
// display name
$displayName = $torrent;
if(strlen($displayName) >= 55) {
	$displayName = substr($displayName, 0, 52)."...";
}
$tmpl->setvar('_RUNTORRENT', _RUNTORRENT);
$tmpl->setvar('displayName', $displayName);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('body_data_bg', $cfg["body_data_bg"]);
$tmpl->setvar('torrent', $torrent);
if ($torrentExists) {
	$tmpl->setvar('bt_client', getBTClientSelect($cfg["btclient"]));
}
else {
	$tmpl->setvar('bt_client', getBTClientSelect($btclient_default));
}
$tmpl->setvar('max_upload_rate', $cfg["max_upload_rate"]);
$tmpl->setvar('max_uploads', $cfg["max_uploads"]);
$tmpl->setvar('max_download_rate', $cfg["max_download_rate"]);
$tmpl->setvar('maxcons', $cfg["maxcons"]);
$tmpl->setvar('rerequest_interval', $cfg["rerequest_interval"]);
if($cfg["AllowQueing"] == true) {
	$tmpl->setvar('is_queue', 1);
// Force Queuing if not an admin.
	if (IsAdmin()) {
		$tmpl->setvar('is_admin', 1);
	}
}
$selected = "";
if ($cfg["torrent_dies_when_done"] == "False") {
	$selected = "selected";
}
$tmpl->setvar('selected', $selected);
$tmpl->setvar('minport', $cfg["minport"]);
$tmpl->setvar('maxport', $cfg["maxport"]);
$tmpl->setvar('sharekill', $sharekill);
$tmpl->setvar('showdirtree', $cfg["showdirtree"]);
$tmpl->setvar('savepath', $cfg["savepath"]);
$tmpl->setvar('arDirTree', dirTree2($cfg["path"].getOwner($torrent).'/', $cfg["maxdepth"]));
if ($torrentExists) {
	$tmpl->setvar('torrent_exists', 1);
	if ($cfg["skiphashcheck"] != 0) {
		$tmpl->setvar('is_skip', 1);
	}
}
$tmpl->setvar('main_bgcolor', $cfg["main_bgcolor"]);
$tmpl->setvar('bgLight', $cfg["bgLight"]);
$tmpl->setvar('showMetaInfo', showMetaInfo($torrent,false));
$tmpl->setvar('_RUNTORRENT', _RUNTORRENT);

$tmpl->pparse();
?>