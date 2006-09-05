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

// metainfo
require_once("inc/metaInfo.php");

// create template-instance
$tmpl = getTemplateInstance($cfg["theme"], "details.tmpl");

$tmpl->setvar('head', getHead(_TRANSFERDETAILS));
$tmpl->setvar('getDriveSpaceBar', getDriveSpaceBar(getDriveSpace($cfg["path"])));
$tmpl->setvar('main_bgcolor', $cfg["main_bgcolor"]);

$transfer = getRequestVar('torrent');
if ((substr(strtolower($transfer),-8 ) == ".torrent")) {
	// this is a torrent-client
	$als = getRequestVar('als');
	if($als == "false") {
		$tmpl->setvar('showMetaInfo', showMetaInfo($transfer, false));
	} else {
		$tmpl->setvar('showMetaInfo', showMetaInfo($transfer, true));
	}
	$tmpl->setvar('getTorrentScrapeInfo', getTorrentScrapeInfo($transfer));
	$tmpl->setvar('scrape', 1);
} else if ((substr(strtolower($transfer),-5 ) == ".wget")) {
	// this is wget.
	require_once("inc/classes/ClientHandler.php");
	$clientHandler = ClientHandler::getClientHandlerInstance($cfg, 'wget');
	$clientHandler->setVarsFromFile($transfer);
	$showMetaInfo = "<table>";
	$showMetaInfo .= "<tr><td width=\"110\">Metainfo File:</td><td>".$transfer."</td></tr>";
	$showMetaInfo .= "<tr><td>URL:</td><td>".$clientHandler->url."</td></tr>";
	$showMetaInfo .= "</table>";
	$tmpl->setvar('showMetaInfo', $showMetaInfo);
	$tmpl->setvar('scrape', 0);
} else {
	$tmpl->setvar('showMetaInfo', "");
	$tmpl->setvar('scrape', 0);
}

$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('iid', $_GET["iid"]);
$tmpl->setvar('foot', getFoot());
$tmpl->pparse();

?>