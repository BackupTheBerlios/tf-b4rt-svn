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
if ((!isset($cfg['user'])) || (isset($_REQUEST['cfg']))) {
	@ob_end_clean();
	@header("location: ../../index.php");
	exit();
}

/******************************************************************************/

// common functions
require_once('inc/functions/functions.common.php');

// metainfo-functions
require_once("inc/functions/functions.metainfo.php");

// transfer functions
require_once('inc/functions/functions.transfer.php');

// init template-instance
tmplInitializeInstance($cfg["theme"], "page.transferDetails.tmpl");

// init transfer
transfer_init();

// client-switch
if (substr($transfer, -8) == ".torrent") {
	// this is a t-client
	$tmpl->setvar('clientType', "torrent");
	$tmpl->setvar('transferMetaInfo', ($cfg["enable_file_priority"] == 1) ? showMetaInfo($transfer, true) : showMetaInfo($transfer, false));
} else if (substr($transfer, -5) == ".wget") {
	// this is wget.
	$tmpl->setvar('clientType', "wget");
	$ch = ClientHandler::getInstance('wget');
	$ch->setVarsFromFile($transfer);
	$tmpl->setvar('transferUrl', $ch->url);
} else if (substr($transfer, -4) == ".nzb") {
	// this is nzbperl.
	$tmpl->setvar('clientType', "nzb");
	$tmpl->setvar('transferMetaInfo', @htmlentities(file_get_contents($cfg["transfer_file_path"].$transfer), ENT_QUOTES));
}

// title + foot
tmplSetFoot(false);
tmplSetTitleBar($transferLabel." - Details", false);

// iid
$tmpl->setvar('iid', $_REQUEST["iid"]);

// parse template
$tmpl->pparse();

?>