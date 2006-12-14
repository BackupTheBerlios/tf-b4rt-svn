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

// request-vars
$torrent = getRequestVar('torrent');
$alias = getRequestVar('alias');

// alias
$transferowner = getOwner($torrent);
if ((!empty($torrent)) && (!empty($alias)))
	$af = AliasFile::getAliasFileInstance($alias, $transferowner);
else
	showErrorPage("missing params");

// init template-instance
tmplInitializeInstance($cfg["theme"], "page.downloadhosts.tmpl");

// set vars
if ($af->running == 1) {
	$torrent_pid = getTransferPid($alias);
	$torrent_cons = netstatConnectionsByPid($torrent_pid);
	$torrent_hosts = netstatHostsByPid($torrent_pid);
} else {
	$torrent_cons = "";
}
$hd = getStatusImage($af);
$tmpl->setvar('torrentLabel', (strlen($torrent) >= 39) ? substr($torrent, 0, 35)."..." : $torrent);
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
		if ($cfg["downloadhosts"] == 1)
			$host = @gethostbyaddr($host);
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
//
$tmpl->setvar('meta_refresh', '15;URL=index.php?iid=downloadhosts&torrent='.$torrent.'&alias='.$alias);
//
tmplSetTitleBar($cfg["pagetitle"]." - ".$cfg['_ID_HOSTS'], false);
tmplSetFoot(false);
$tmpl->setvar('iid', $_REQUEST["iid"]);

// parse template
$tmpl->pparse();

?>