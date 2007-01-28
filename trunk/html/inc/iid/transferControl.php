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

// transfer functions
require_once('inc/functions/functions.transfer.php');

// init template-instance
tmplInitializeInstance($cfg["theme"], "page.transferControl.tmpl");

// init transfer
transfer_init();

// request-vars
$pageop = getRequestVar('pageop');
$client = getRequestVar('client');

// init ch-instance
$ch = ($client == "")
	? ClientHandler::getInstance(getTransferClient($transfer))
	: ClientHandler::getInstance($client);

// customize-vars
transfer_setCustomizeVars();

// load settings, default if settings could not be loaded (fresh transfer)
if ($ch->settingsLoad($transfer) !== true) {
	$ch->settingsDefault();
	$settings_exist = 0;
} else {
	$settings_exist = 1;
}
$tmpl->setvar('settings_exist', $settings_exist);

// set running-field
$ch->running = isTransferRunning($transfer) ? 1 : 0;

// pageop
//
// * control (start, stats)
// * start (form or link)
//
if (empty($pageop))
	$pageop = ($ch->running == 0) ? "start" : "control";
$tmpl->setvar('pageop', $pageop);
// op-switch
switch ($pageop) {

	case "control":
		break;

	case "start":

		// client-chooser
		if ($ch->type == "torrent") {
			$tmpl->setvar('enableClientChooser', 1);
			$tmpl->setvar('enableBtclientChooser', $cfg["enable_btclient_chooser"]);
			if ($cfg["enable_btclient_chooser"] != 0)
				tmplSetClientSelectForm($ch->type);
			else
				$tmpl->setvar('btclientDefault', $cfg["btclient"]);
		} else {
			$tmpl->setvar('enableClientChooser', 0);
		}

		// hash-check
		if ($supportMap[$ch->client]['skip_hash_check'] == 1) {
			$dsize = getTorrentDataSize($transfer);
			$tmpl->setvar('is_skip',
				(($dsize > 0) && ($dsize != 4096))
					? $cfg["skiphashcheck"]
					: 0
			);
		} else {
			$tmpl->setvar('is_skip', 0);
		}

		// file prio
		$tmpl->setvar('enable_file_priority',
			($supportMap[$ch->client]['file_priority'] == 1)
				? $cfg["enable_file_priority"]
				: 0
		);

		// dirtree
		$tmpl->setvar('showdirtree', $cfg["showdirtree"]);
		$dirTree = ($cfg["enable_home_dirs"] != 0)
			? $cfg["path"].getOwner($transfer).'/'
			: $cfg["path"].$cfg["path_incoming"].'/';
		tmplSetDirTree($dirTree, $cfg["maxdepth"]);

		// queue
		$tmpl->setvar('is_queue', (FluxdQmgr::isRunning()) ? 1 : 0);

		// set vars
		transfer_setProfiledVars();

		// TODO

		break;

	default:
		@error("Invalid pageop", "", "", array($pageop));

}

// title + foot
tmplSetFoot(false);
tmplSetTitleBar($transferLabel." - Control", false);

// lang vars
$tmpl->setvar('_RUNTRANSFER', $cfg['_RUNTRANSFER']);

// iid
$tmpl->setvar('iid', $_REQUEST["iid"]);

// parse template
$tmpl->pparse();

?>