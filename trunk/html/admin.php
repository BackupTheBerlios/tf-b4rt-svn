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

// main.internal
require_once("inc/main.internal.php");

// common functions
require_once('inc/functions/functions.common.php');

// admin functions
require_once('inc/functions/functions.admin.php');

// access-check
if (!$cfg['isAdmin']) {
	 // the user probably hit this page direct
	AuditAction($cfg["constants"]["access_denied"], $_SERVER['PHP_SELF']);
	header("location: index.php?iid=index");
}

// op-switch
if (isset($_REQUEST['op']))
	$op = $_REQUEST['op'];
else
	$op = "default";
switch ($op) {

	case "updateServerSettings":
		$settings = processSettingsParams(false,false);
		saveSettings('tf_settings', $settings);
		AuditAction($cfg["constants"]["admin"], " Updating TorrentFlux Server Settings");
		$continue = getRequestVar('continue');
		header("location: admin.php?op=serverSettings");
		exit();

	case "updateTransferSettings":
		$settings = processSettingsParams(false,false);
		saveSettings('tf_settings', $settings);
		AuditAction($cfg["constants"]["admin"], " Updating TorrentFlux Transfer Settings");
		$continue = getRequestVar('continue');
		header("location: admin.php?op=transferSettings");
		exit();

	case "updateWebappSettings":
		$settings = processSettingsParams(false,false);
		saveSettings('tf_settings', $settings);
		AuditAction($cfg["constants"]["admin"], " Updating TorrentFlux WebApp Settings");
		$continue = getRequestVar('continue');
		header("location: admin.php?op=webappSettings");
		exit();

	case "updateIndexSettings":
		$settings = processSettingsParams(true,true);
		saveSettings('tf_settings', $settings);
		AuditAction($cfg["constants"]["admin"], " Updating TorrentFlux Index Settings");
		header("location: admin.php?op=indexSettings");
		exit();

	case "updateStartpopSettings":
		$settings = processSettingsParams(false,false);
		saveSettings('tf_settings', $settings);
		AuditAction($cfg["constants"]["admin"], " Updating TorrentFlux StartPop Settings");
		header("location: admin.php?op=startpopSettings");
		exit();

	case "updateDirSettings":
		$settings = processSettingsParams(false,false);
		loadSettings('tf_settings_dir');
		saveSettings('tf_settings_dir', $settings);
		AuditAction($cfg["constants"]["admin"], " Updating TorrentFlux Dir Settings");
		header("location: admin.php?op=dirSettings");
		exit();

	case "updateStatsSettings":
		$settings = processSettingsParams(false,false);
		saveSettings('tf_settings_stats', $settings);
		AuditAction($cfg["constants"]["admin"], " Updating TorrentFlux Stats Settings");
		header("location: admin.php?op=statsSettings");
		exit();

	case "updateXferSettings":
		$settings = processSettingsParams(false,false);
		saveSettings('tf_settings', $settings);
		AuditAction($cfg["constants"]["admin"], " Updating TorrentFlux Xfer Settings");
		header("location: admin.php?op=xferSettings");
		exit();

	case "addRSS":
		$newRSS = getRequestVar('newRSS');
		if(!empty($newRSS)){
			addNewRSS($newRSS);
			AuditAction($cfg["constants"]["admin"], "New RSS: ".$newRSS);
		}
		header("location: admin.php?op=editRSS");
		exit();

	case "deleteRSS":
		$rid = getRequestVar('rid');
		AuditAction($cfg["constants"]["admin"], $cfg['_DELETE']." RSS: ".getRSS($rid));
		deleteOldRSS($rid);
		header("location: admin.php?op=editRSS");
		exit();

	case "deleteLink":
		$lid = getRequestVar('lid');
		AuditAction($cfg["constants"]["admin"], $cfg['_DELETE']." Link: ".getSite($lid)." [".getLink($lid)."]");
		deleteOldLink($lid);
		header("location: admin.php?op=editLinks");
		exit();

	case "deleteUser":
		$user_id = getRequestVar('user_id');
		if (!IsSuperAdmin($user_id)) {
			DeleteThisUser($user_id);
			AuditAction($cfg["constants"]["admin"], $cfg['_DELETE']." ".$cfg['_USER'].": ".$user_id);
		}
		header("location: admin.php");
		exit();

	case "setUserState":
		setUserState();
		header("location: admin.php?op=showUsers");
		exit();

	default:
		require_once("inc/iid/admin/".$op.".php");
		exit();
}

?>