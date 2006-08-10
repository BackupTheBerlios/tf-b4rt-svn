<?php

/* $Id: details.php 189 2006-08-06 20:03:40Z msn_exploder $ */

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


global $cfg;

# create new template
if (!ereg('^[^./][^/]*$', $cfg["theme"])) {
	$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/details.tmpl");
}
else {
	$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/details.tmpl");
}
$tmpl->setvar('head', getHead(_TORRENTDETAILS));
$tmpl->setvar('getDriveSpaceBar', getDriveSpaceBar(getDriveSpace($cfg["path"])));
$tmpl->setvar('main_bgcolor', $cfg["main_bgcolor"]);

$torrent = getRequestVar('torrent');

$als = getRequestVar('als');
if($als == "false") {
	$tmpl->setvar('showMetaInfo', showMetaInfo($torrent,false));
}
else {
	$tmpl->setvar('showMetaInfo', showMetaInfo($torrent,true));
}

switch ($cfg["metainfoclient"]) {
	case "transmissioncli":
		$tmpl->setvar('transmissioncli', 1);
		$tmpl->setvar('getTorrentScrapeInfo', getTorrentScrapeInfo($torrent));
	break;
}

$tmpl->setvar('foot', getFoot());
$tmpl->pparse();
?>