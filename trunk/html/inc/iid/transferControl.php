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

// request-vars
$transfer = getRequestVar('transfer');
if (empty($transfer))
	@error("missing params", "index.php?iid=index", "", array('transfer'));
$pageop = getRequestVar('pageop');
$profile = getRequestVar('profile');

// validate transfer
if (isValidTransfer($transfer) !== true) {
	AuditAction($cfg["constants"]["error"], "INVALID TRANSFER: ".$transfer);
	@error("Invalid Transfer", "", "", array($transfer));
}

// init template-instance
tmplInitializeInstance($cfg["theme"], "page.transferControl.tmpl");

// get label
$transferLabel = (strlen($transfer) >= 39) ? substr($transfer, 0, 35)."..." : $transfer;

// set transfer vars
$tmpl->setvar('transfer', $transfer);
$tmpl->setvar('transferLabel', $transferLabel);

// init ch-instance
$ch = ClientHandler::getInstance(getTransferClient($transfer));

// supports-settings
transfer_setSupportsVars();

// load settings, default if settings could not be loaded (fresh transfer)
if ($ch->settingsLoad($transfer) !== true) {
	$ch->settingsDefault();
	$settings_exist = 0;
} else {
	$settings_exist = 1;
}
$tmpl->setvar('settings_exist', $settings_exist);

// hash-check
$dsize = getTorrentDataSize($transfer);
$tmpl->setvar('is_skip', (($dsize > 0) && ($dsize != 4096)) ? $cfg["skiphashcheck"] : 0);

// set running-field
$ch->running = isTransferRunning($transfer) ? 1 : 0;

// pageop
//
// * control (start, stop, restart)
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

		// file prio
		if ($supportMap[$ch->client]['supports_file_priority'] == 1)
			$tmpl->setvar('enable_file_priority', $cfg["enable_file_priority"]);

		// client-chooser
		$tmpl->setvar('enableBtclientChooser', $cfg["enable_btclient_chooser"]);
		if ($ch->type == "torrent") {
			if ($cfg["enable_btclient_chooser"] != 0)
				tmplSetClientSelectForm($cfg["btclient"]);
			else
				$tmpl->setvar('btclientDefault', $cfg["btclient"]);
		} else {
			$tmpl->setvar('btclientDefault', $ch->type);
		}

		// dirtree
		$tmpl->setvar('showdirtree', $cfg["showdirtree"]);
		$dirTree = ($cfg["enable_home_dirs"] != 0)
			? $cfg["path"].getOwner($transfer).'/'
			: $cfg["path"].$cfg["path_incoming"].'/';
		tmplSetDirTree($dirTree, $cfg["maxdepth"]);

		// queuing
		$tmpl->setvar('is_queue', (FluxdQmgr::isRunning()) ? 1 : 0);

		// profiles
		if ($cfg["enable_transfer_profile"] == "1") {
			if ($cfg['transfer_profile_level'] >= "1")
				$with_profiles = 1;
			else
				$with_profiles = ($cfg['isAdmin']) ? 1 : 0;
		} else {
			$with_profiles = 0;
		}
		if ($with_profiles == 0) {
			// set vars
			transfer_setVars($with_profiles);
			$tmpl->setvar('useLastSettings', $settings_exist);
		} else {
			// set vars
			transfer_setVars($with_profiles);
			$tmpl->setvar('useLastSettings', (($profile != "") && ($profile != "last_used")) ? 0 : $settings_exist);
			// load profile list
			if ($cfg['transfer_profile_level'] == "2" || $cfg['isAdmin'])
				$profiles = GetProfiles($cfg["uid"], $profile);
			if ($cfg['transfer_profile_level'] >= "1")
				$public_profiles = GetPublicProfiles($profile);
			if ((count($profiles) + count($public_profiles)) > 0) {
				$tmpl->setloop('profiles', $profiles);
				$tmpl->setloop('public_profiles', $public_profiles);
			} else {
				$with_profiles = 0;
			}
		}
		$tmpl->setvar('with_profiles', $with_profiles);

		// customize settings
		if ($cfg['transfer_customize_settings'] == "2")
			$customize_settings = 1;
		elseif ($cfg['transfer_customize_settings'] == "1" && $cfg['isAdmin'])
			$customize_settings = 1;
		else
			$customize_settings = 0;
		$tmpl->setvar('customize_settings', $customize_settings);

		// meta-info
		//$tmpl->setvar('metaInfo', showMetaInfo($transfer, false));

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