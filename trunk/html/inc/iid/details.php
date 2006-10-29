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

// metainfo
require_once("inc/metaInfo.php");

// init template-instance
tmplInitializeInstance($cfg["theme"], "page.details.tmpl");

// set vars
$transfer = getRequestVar('torrent');
if ((substr(strtolower($transfer), -8) == ".torrent")) {
	// this is a torrent-client
	$als = getRequestVar('als');
	if ($als == "false")
		$tmpl->setvar('metaInfo', showMetaInfo($transfer, false));
	else
		$tmpl->setvar('metaInfo', showMetaInfo($transfer, true));
	$tmpl->setvar('scrapeInfo', getTorrentScrapeInfo($transfer));
	$tmpl->setvar('scrape', 1);
} else if ((substr(strtolower($transfer), -5) == ".wget")) {
	// this is wget.
	require_once("inc/classes/ClientHandler.php");
	$clientHandler = ClientHandler::getClientHandlerInstance($cfg, 'wget');
	$clientHandler->setVarsFromFile($transfer);
	$metaInfo = "<table>";
	$metaInfo .= "<tr><td width=\"110\">Metainfo File:</td><td>".$transfer."</td></tr>";
	$metaInfo .= "<tr><td>URL:</td><td>".$clientHandler->url."</td></tr>";
	$metaInfo .= "</table>";
	$tmpl->setvar('metaInfo', $metaInfo);
	$tmpl->setvar('scrape', 0);
} else {
	$tmpl->setvar('metaInfo', "");
	$tmpl->setvar('scrape', 0);
}
//
tmplSetTitleBar($cfg["pagetitle"].' - '.$cfg['_TRANSFERDETAILS']);
tmplSetDriveSpaceBar();
tmplSetFoot();
$tmpl->setvar('iid', $_REQUEST["iid"]);

// parse template
$tmpl->pparse();

?>