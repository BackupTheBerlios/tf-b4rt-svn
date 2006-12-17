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

// is enabled ?
if ($cfg["advanced_start"] != 1) {
	AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to use advanced start");
	@error("advanced start is disabled", "index.php?iid=index", "");
}

// common functions
require_once('inc/functions/functions.common.php');

// startpop functions
require_once('inc/functions/functions.startpop.php');

// metainfo-functions
require_once("inc/functions/functions.metainfo.php");

// init template-instance
tmplInitializeInstance($cfg["theme"], "page.startpop.tmpl");

// get torren-param
$transfer = getRequestVar('torrent');

// torrent exists ?
$torrentExists = (getTorrentDataSize($transfer) > 0);

// set some template-vars
$tmpl->setvar('displayName', (strlen($transfer) >= 55) ? substr($transfer, 0, 52)."..." : $transfer);
$tmpl->setvar('torrent', $transfer);
$tmpl->setvar('torrentExists', $torrentExists);
$tmpl->setvar('showdirtree', $cfg["showdirtree"]);
$tmpl->setvar('enableBtclientChooser', $cfg["enable_btclient_chooser"]);
if ($cfg["enable_btclient_chooser"] != 0)
	tmplSetClientSelectForm($cfg["btclient"]);
else
	$tmpl->setvar('btclientDefault', $cfg["btclient"]);

// dirtree
$dirTree = ($cfg["enable_home_dirs"] != 0)
	? $cfg["path"].getOwner($transfer).'/'
	: $cfg["path"].$cfg["path_incoming"].'/';
tmplSetDirTree($dirTree, $cfg["maxdepth"]);

if ($torrentExists) {
	$tmpl->setvar('torrent_exists', 1);
	$tmpl->setvar('is_skip', ($cfg["skiphashcheck"] != 0) ? 1 : 0);
}
// Force Queuing if not an admin.
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
if ($with_profiles == 1) {
	$profile = getRequestVar('profile');
	if (isset($profile) && $profile != "" && $profile != "last_used") {
		$tmpl->setvar('useLastSettings', 0);
		//load custom settings
		$settings = GetProfileSettings($profile);
		$tmpl->setvar('minport', $settings["minport"]);
		$tmpl->setvar('maxport', $settings["maxport"]);
		$tmpl->setvar('maxcons', $settings["maxcons"]);
		$tmpl->setvar('rerequest_interval', $settings["rerequest"]);
		$tmpl->setvar('max_upload_rate', $settings["rate"]);
		$tmpl->setvar('max_uploads', $settings["maxuploads"]);
		$tmpl->setvar('max_download_rate', $settings["drate"]);
		$tmpl->setvar('selected', ($settings["runtime"] == "False") ? "selected" : "");
		$tmpl->setvar('runtimeValue', $settings["runtime"]);
		$tmpl->setvar('sharekill', $settings["sharekill"]);
		$tmpl->setvar('superseeder', ($settings['superseeder'] == 1) ? "checked" : "");
		$tmpl->setvar('superseederValue', $settings['superseeder']);
		// Load saved settings
		loadTransferSettingsToConfig($transfer);
		// savepath
		if ((!isset($cfg["savepath"])) || (empty($cfg["savepath"]))) {
			$cfg["savepath"] = ($cfg["enable_home_dirs"] != 0)
				? $cfg["path"].getOwner($transfer).'/'
				: $cfg["path"].$cfg["path_incoming"].'/';
		}
		$tmpl->setvar('savepath', $cfg["savepath"]);
	} else {
		$tmpl->setvar('useLastSettings', 1);
		setVarsFromPersistentSettings();
	}
	// load profile list
	if ($cfg['transfer_profile_level'] == "2" || $cfg['isAdmin'])
		$profiles = GetProfiles($cfg["uid"], $profile);
	if ($cfg['transfer_profile_level'] >= "1")
		$public_profiles = GetPublicProfiles($profile);
	if (count($profiles) || count($public_profiles)) {
		$tmpl->setloop('profiles', $profiles);
		$tmpl->setloop('public_profiles', $public_profiles);
	} else {
		$with_profiles = 0;
	}
	// customize settings
	$customize_settings = 0;
	if ($cfg['transfer_customize_settings'] == "2")
		$customize_settings = 1;
	elseif ($cfg['transfer_customize_settings'] == "1" && $cfg['isAdmin'])
		$customize_settings = 1;
	$tmpl->setvar('customize_settings', $customize_settings);
} else {
	setVarsFromPersistentSettings();
}
$tmpl->setvar('with_profiles', $with_profiles);
//
$tmpl->setvar('bgLight', $cfg["bgLight"]);
$tmpl->setvar('metaInfo', showMetaInfo($transfer,false));
//
$tmpl->setvar('_RUNTRANSFER', $cfg['_RUNTRANSFER']);
//
$tmpl->setvar('iid', $_REQUEST["iid"]);

// parse template
$tmpl->pparse();

?>