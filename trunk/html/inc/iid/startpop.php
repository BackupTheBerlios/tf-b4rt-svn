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

// startpop functions
require_once('inc/functions/functions.startpop.php');

// require
require_once("inc/metaInfo.php");

// create template-instance
$tmpl = tmplGetInstance($cfg["theme"], "page.startpop.tmpl");

// get torren-param
$torrent = getRequestVar('torrent');

// torrent exists ?
$torrentExists = (getTorrentDataSize($torrent) > 0);

// display name
if (strlen($torrent) >= 55)
	$displayName = substr($torrent, 0, 52)."...";
else
	$displayName = $torrent;

// set some template-vars
$tmpl->setvar('displayName', $displayName);
$tmpl->setvar('torrent', $torrent);
$tmpl->setvar('torrentExists', $torrentExists);
$tmpl->setvar('showdirtree', $cfg["showdirtree"]);
$tmpl->setvar('enableBtclientChooser', $cfg["enable_btclient_chooser"]);
if ($cfg["enable_btclient_chooser"] != 0)
	tmplSetClientSelectForm($cfg["btclient"]);
else
	$tmpl->setvar('btclientDefault', $cfg["btclient"]);

switch ($cfg["enable_home_dirs"]) {
    case 1:
    default:
    	tmplSetDirTree($cfg["path"].getOwner($torrent).'/', $cfg["maxdepth"]);
		break;
    case 0:
    	tmplSetDirTree($cfg["path"].$cfg["path_incoming"].'/', $cfg["maxdepth"]);
    	break;
}
if ($torrentExists) {
	$tmpl->setvar('torrent_exists', 1);
	if ($cfg["skiphashcheck"] != 0)
		$tmpl->setvar('is_skip', 1);
	else
		$tmpl->setvar('is_skip', 0);
}
// Force Queuing if not an admin.
if ($queueActive)
	$tmpl->setvar('is_queue', 1);
else
	$tmpl->setvar('is_queue', 0);
// profiles
if ($cfg["enable_transfer_profile"] == "1") {
	if ($cfg['transfer_profile_level'] >= "1") {
		$with_profiles = 1;
	} else {
		if ($cfg['isAdmin'])
			$with_profiles = 1;
		else
			$with_profiles = 0;
	}
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
		if ($cfg["runtime"] == "False")
			$tmpl->setvar('selected', "selected");
		else
			$tmpl->setvar('selected', "");
		$tmpl->setvar('runtimeValue', $settings["runtime"]);
		$tmpl->setvar('sharekill', $settings["sharekill"]);
		if ($settings['superseeder'] == 1)
			$tmpl->setvar('superseeder', "checked");
		else
			$tmpl->setvar('superseeder', "");
		$tmpl->setvar('superseederValue', $settings['superseeder']);
		// Load saved settings
		loadTorrentSettingsToConfig($torrent);
		// savepath
		if ((!isset($cfg["savepath"])) || (empty($cfg["savepath"]))) {
			switch ($cfg["enable_home_dirs"]) {
			    case 1:
			    default:
					$cfg["savepath"] = $cfg["path"].getOwner($torrent).'/';
					break;
			    case 0:
			    	$cfg["savepath"] = $cfg["path"].$cfg["path_incoming"].'/';
			    	break;
			}
		}
		$tmpl->setvar('savepath', $cfg["savepath"]);
	} else {
		$tmpl->setvar('useLastSettings', 1);
		setVarsFromPersistentSettings();
	}
	// load profile list
	if ($cfg['transfer_profile_level'] == "2" or $cfg['isAdmin'])
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
$tmpl->setvar('metaInfo', showMetaInfo($torrent,false));
//
$tmpl->setvar('_RUNTRANSFER', $cfg['_RUNTRANSFER']);
//
$tmpl->setvar('iid', $_GET["iid"]);

// parse template
$tmpl->pparse();

?>