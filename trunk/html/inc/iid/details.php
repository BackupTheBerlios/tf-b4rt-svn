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
	@header("location: ../../index.php");
	exit();
}

/******************************************************************************/

// common functions
require_once('inc/functions/functions.common.php');

// metainfo-functions
require_once("inc/functions/functions.metainfo.php");

// init template-instance
tmplInitializeInstance($cfg["theme"], "page.details.tmpl");

// set vars
$transfer = getRequestVar('transfer');
if (substr($transfer, -8) == ".torrent") {
	// this is a torrent-client
	$als = getRequestVar('als');
	$tmpl->setvar('metaInfo', ($als == "false") ? showMetaInfo($transfer, false) : showMetaInfo($transfer, true));
	$tmpl->setvar('scrapeInfo', getTorrentScrapeInfo($transfer));
	$tmpl->setvar('scrape', 1);
} else if (substr($transfer, -5) == ".wget") {
	// this is wget.
	$clientHandler = ClientHandler::getInstance('wget');
	$clientHandler->setVarsFromFile($transfer);
	$metaInfo = "<table>";
	$metaInfo .= "<tr><td width=\"110\">Metainfo File:</td><td>".$transfer."</td></tr>";
	$metaInfo .= "<tr><td>URL:</td><td>".$clientHandler->url."</td></tr>";
	$metaInfo .= "</table>";
	$tmpl->setvar('metaInfo', $metaInfo);
	$tmpl->setvar('scrape', 0);
} else if ((substr(strtolower($transfer), -4) == ".nzb")) {
	// this is nzbperl
	require_once("inc/classes/ClientHandler.php");
	$clientHandler = ClientHandler::getInstance('nzbperl');
	$metaInfo = "<table>";
	$metaInfo .= "<tr><td width=\"110\">Metainfo File:</td><td>".$transfer."</td></tr>";
	$metaInfo .= "</table>";
	$tmpl->setvar('metaInfo', $metaInfo);
	$tmpl->setvar('scrape', 0);
} else {
	AuditAction($cfg["constants"]["error"], "INVALID TRANSFER: ".$transfer);
	@error("Invalid Transfer", "index.php?iid=index", "", array($transfer));
}
//
tmplSetTitleBar($cfg["pagetitle"].' - '.$cfg['_TRANSFERDETAILS']);
tmplSetDriveSpaceBar();
tmplSetFoot();
$tmpl->setvar('iid', $_REQUEST["iid"]);

// parse template
$tmpl->pparse();

?>