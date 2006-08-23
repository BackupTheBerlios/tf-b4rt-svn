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

require_once("config.php");
require_once("functions.php");
require_once("AliasFile.php");


# create new template
if (!ereg('^[^./][^/]*$', $cfg["theme"])) {
	$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/downloadhosts.tmpl");
}
else {
	$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/downloadhosts.tmpl");
}

$torrent = getRequestVar('torrent');
$error = "";
$torrentowner = getOwner($torrent);
$background = "#000000";
$alias = getRequestVar('alias');
if (!empty($alias)) {
	// read the alias file
	$af = AliasFile::getAliasFileInstance($cfg["torrent_file_path"].$alias, $torrentowner, $cfg);
	for ($inx = 0; $inx < sizeof($af->errors); $inx++)
		$error .= "<li style=\"font-size:10px;color:#ff0000;\">".$af->errors[$inx]."</li>";
} else {
	die("fatal error torrent file not specified");
}
$torrent_cons = "";
if (($af->running == 1) && ($alias != "")) {
	$torrent_pid = getTorrentPid($alias);
	$torrent_cons = netstatConnectionsByPid($torrent_pid);
	$torrent_hosts = netstatHostsByPid($torrent_pid);
}
$torrentLabel = $torrent;
if(strlen($torrentLabel) >= 39)
	$torrentLabel = substr($torrent, 0, 35)."...";
$hd = getStatusImage($af);
$tmpl->setvar(_ID_HOSTS, false, "30", $af->percent_done."% ");
$tmpl->setvar('head', getHead(_ID_HOSTS, false, "15", ""));

if ($error != ""){
	$tmpl->setvar('is_error', 1);
	$tmpl->setvar('error', $error);
}
$tmpl->setvar('torrentLabel', $torrentLabel);
$tmpl->setvar('cons_hosts', $torrent_cons." "._ID_HOSTS);
$tmpl->setvar('torrent', $torrent);
$tmpl->setvar('alias', $alias);
$tmpl->setvar('hd_image', $hd->image);
$tmpl->setvar('hd_title', $hd->title);
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
$tmpl->setvar('body_data_bg', $cfg["body_data_bg"]);
if (($torrent_hosts != null) && ($torrent_hosts != "")) {
	$tmpl->setvar('torrent_hosts_aval', 1);
	$tmpl->setvar('_ID_HOST', _ID_HOST);
	$tmpl->setvar('_ID_PORT', _ID_PORT);
	$hostAry = array_keys($torrent_hosts);
	$list_host = array();
	foreach ($hostAry as $host) {
		$host = @trim($host);
		$port = @trim($torrent_hosts[$host]);
		if ($cfg["downloadhosts"] == 1) {
			$host = @gethostbyaddr($host);
		}
		if ($host != "") {
			$tmpl->setvar('hosts', 1);
			array_push($list_host, array(
				'host' => $host,
				'port' => $port,
				)
			);
		}
	}
	$tmpl->setloop('list_host', $list_host);
}
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('index_page', $cfg["index_page"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('refresh_details', 1);
$tmpl->setvar('foot', getFoot(false,false));
$tmpl->setvar('iid', $_GET["iid"]);
$tmpl->pparse();
?>