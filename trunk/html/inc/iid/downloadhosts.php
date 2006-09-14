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

// require
require_once("inc/classes/AliasFile.php");

// create template-instance
$tmpl = getTemplateInstance($cfg["theme"], "downloadhosts.tmpl");

$torrent = getRequestVar('torrent');
$error = "";
$transferowner = getOwner($torrent);
$background = "#000000";
$alias = getRequestVar('alias');
if (!empty($alias)) {
	// read the alias file
	$af = AliasFile::getAliasFileInstance($cfg["transfer_file_path"].$alias, $transferowner, $cfg);
} else {
	die("fatal error torrent file not specified");
}
$torrent_cons = "";
if ($af->running == 1) {
	$torrent_pid = getTransferPid($alias);
	$torrent_cons = netstatConnectionsByPid($torrent_pid);
	$torrent_hosts = netstatHostsByPid($torrent_pid);
}
$torrentLabel = $torrent;
if(strlen($torrentLabel) >= 39)
	$torrentLabel = substr($torrent, 0, 35)."...";
$hd = getStatusImage($af);
$tmpl->setvar($cfg['_ID_HOSTS'], false, "30", $af->percent_done."% ");
$tmpl->setvar('torrentLabel', $torrentLabel);
$tmpl->setvar('cons_hosts', $torrent_cons." ".$cfg['_ID_HOSTS']);
$tmpl->setvar('torrent', $torrent);
$tmpl->setvar('alias', $alias);
$tmpl->setvar('hd_image', $hd->image);
$tmpl->setvar('hd_title', $hd->title);
if ((isset($torrent_hosts)) && ($torrent_hosts != "")) {
	$tmpl->setvar('torrent_hosts_aval', 1);
	$tmpl->setvar('_ID_HOST', $cfg['_ID_HOST']);
	$tmpl->setvar('_ID_PORT', $cfg['_ID_PORT']);
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
//$tmpl->setvar('head', getHead($cfg['_ID_HOSTS'], false, "15", ""));
$tmpl->setvar('head', getHead($cfg['_ID_HOSTS'], false));
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
$tmpl->setvar('body_data_bg', $cfg["body_data_bg"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('refresh_details', 1);
$tmpl->setvar('foot', getFoot(false));
$tmpl->setvar('iid', $_GET["iid"]);
$tmpl->pparse();
?>