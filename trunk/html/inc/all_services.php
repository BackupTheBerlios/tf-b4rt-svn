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


# create new template
if (!ereg('^[^./][^/]*$', $cfg["theme"])) {
	$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/all_services.tmpl");
}
else {
	$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/all_services.tmpl");
}

$result = shell_exec("df -h ".$cfg["path"]);
$result2 = shell_exec("du -sh ".$cfg["path"]."*");
$result4 = shell_exec("w");
$result5 = shell_exec("free -mo");

$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('index_page', $cfg["index_page"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('head', getHead(_ALL));
$tmpl->setvar('getDriveSpaceBar', getDriveSpaceBar(getDriveSpace($cfg["path"])));
$tmpl->setvar('main_bgcolor', $cfg["main_bgcolor"]);
$tmpl->setvar('_DRIVESPACE', _DRIVESPACE);
$tmpl->setvar('result', $result);
$tmpl->setvar('result2', $result2);
$tmpl->setvar('_SERVERSTATS', _SERVERSTATS);
$tmpl->setvar('result4', $result4);
$tmpl->setvar('result5', $result5);
$tmpl->setvar('_ID_CONNECTIONS', _ID_CONNECTIONS);
$tmpl->setvar('netstatConnectionsSum', netstatConnectionsSum());
$tmpl->setvar('_ID_PORTS', _ID_PORTS);
$tmpl->setvar('netstatPortList', netstatPortList());
$tmpl->setvar('_ID_HOSTS', _ID_HOSTS);
$tmpl->setvar('netstatHostList', netstatHostList());
$tmpl->setvar('foot', getFoot());
$tmpl->setvar('page', $_GET["page"]);
$tmpl->pparse();
?>