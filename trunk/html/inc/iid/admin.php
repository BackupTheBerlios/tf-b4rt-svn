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

// admin functions
require_once('inc/functions/functions.admin.php');


if(!IsAdmin()) {
	 // the user probably hit this page direct
	AuditAction($cfg["constants"]["access_denied"], $_SERVER['PHP_SELF']);
	header("location: index.php?iid=index");
}

// op-switch
$op = getRequestVar('op');
switch ($op) {

	case "serverSettings":
		require_once("admin/serverSettings.php");
		break;

	case "transferSettings":
		require_once("admin/transferSettings.php");
		break;

	case "webappSettings":
		require_once("admin/webappSettings.php");
		break;

	case "indexSettings":
		require_once("admin/indexSettings.php");
		break;

	case "startpopSettings":
		require_once("admin/startpopSettings.php");
		break;

	case "dirSettings":
		require_once("admin/dirSettings.php");
		break;

	case "statsSettings":
		require_once("admin/statsSettings.php");
		break;

	case "fluxdSettings":
		require_once("admin/fluxdSettings.php");
		break;

	case "xferSettings":
		require_once("admin/xferSettings.php");
		break;

	case "updateServerSettings":
		$settings = processSettingsParams(false,false);
		saveSettings('tf_settings', $settings);
		AuditAction($cfg["constants"]["admin"], " Updating TorrentFlux Server Settings");
		$continue = getRequestVar('continue');
		header("location: index.php?iid=admin&op=serverSettings");
		break;

	case "updateTransferSettings":
		$settings = processSettingsParams(false,false);
		saveSettings('tf_settings', $settings);
		AuditAction($cfg["constants"]["admin"], " Updating TorrentFlux Transfer Settings");
		$continue = getRequestVar('continue');
		header("location: index.php?iid=admin&op=transferSettings");
		break;

	case "updateWebappSettings":
		$settings = processSettingsParams(false,false);
		saveSettings('tf_settings', $settings);
		AuditAction($cfg["constants"]["admin"], " Updating TorrentFlux WebApp Settings");
		$continue = getRequestVar('continue');
		header("location: index.php?iid=admin&op=webappSettings");
		break;

	case "updateIndexSettings":
		$settings = processSettingsParams(true,true);
		saveSettings('tf_settings', $settings);
		AuditAction($cfg["constants"]["admin"], " Updating TorrentFlux Index Settings");
		header("location: index.php?iid=admin&op=indexSettings");
		break;

	case "updateStartpopSettings":
		$settings = processSettingsParams(false,false);
		saveSettings('tf_settings', $settings);
		AuditAction($cfg["constants"]["admin"], " Updating TorrentFlux StartPop Settings");
		header("location: index.php?iid=admin&op=startpopSettings");
		break;

	case "updateDirSettings":
		$settings = processSettingsParams(false,false);
		loadSettings('tf_settings_dir');
		saveSettings('tf_settings_dir', $settings);
		AuditAction($cfg["constants"]["admin"], " Updating TorrentFlux Dir Settings");
		header("location: index.php?iid=admin&op=dirSettings");
		break;

	case "updateStatsSettings":
		$settings = processSettingsParams(false,false);
		loadSettings('tf_settings_stats');
		saveSettings('tf_settings_stats', $settings);
		AuditAction($cfg["constants"]["admin"], " Updating TorrentFlux Stats Settings");
		header("location: index.php?iid=admin&op=statsSettings");
		break;

	case "controlFluxd":
		require_once("admin/controlFluxd.php");
		break;

	case "updateFluxdSettings":
		require_once("admin/updateFluxdSettings.php");
		break;

	case "updateXferSettings":
		$settings = processSettingsParams(false,false);
		saveSettings('tf_settings', $settings);
		AuditAction($cfg["constants"]["admin"], " Updating TorrentFlux Xfer Settings");
		header("location: index.php?iid=admin&op=xferSettings");
		break;

	case "editRSS":
		require_once("admin/editRSS.php");
		break;

	case "addRSS":
		$newRSS = getRequestVar('newRSS');
		if(!empty($newRSS)){
			addNewRSS($newRSS);
			AuditAction($cfg["constants"]["admin"], "New RSS: ".$newRSS);
		}
		header("location: index.php?iid=admin&op=editRSS");
		break;

	case "deleteRSS":
		$rid = getRequestVar('rid');
		AuditAction($cfg["constants"]["admin"], $cfg['_DELETE']." RSS: ".getRSS($rid));
		deleteOldRSS($rid);
		header("location: index.php?iid=admin&op=editRSS");
		break;

	case "editLink":
		$lid = getRequestVar('lid');
		$editLink = getRequestVar('editLink');
		$editSite = getRequestVar('editSite');
		require_once("admin/editLink.php");
		break;

	case "editLinks":
		require_once("admin/editLinks.php");
		break;

	case "addLink":
		$newLink = getRequestVar('newLink');
		$newSite = getRequestVar('newSite');
		require_once("admin/addLink.php");
		break;

	case "moveLink":
		$lid = getRequestVar('lid');
		$direction = getRequestVar('direction');
		require_once("admin/moveLink.php");
		break;

	case "deleteLink":
		$lid = getRequestVar('lid');
		AuditAction($cfg["constants"]["admin"], $cfg['_DELETE']." Link: ".getSite($lid)." [".getLink($lid)."]");
		deleteOldLink($lid);
		header("location: index.php?iid=admin&op=editLinks");
		break;

	case "showUsers":
		require_once("admin/showUsers.php");
		break;

	case "CreateUser":
		require_once("admin/CreateUser.php");
		break;

	case "addUser":
		$newUser = getRequestVar('newUser');
		$pass1 = getRequestVar('pass1');
		$userType = getRequestVar('userType');
		require_once("admin/addUser.php");
		break;

	case "deleteUser":
		$user_id = getRequestVar('user_id');
		if (!IsSuperAdmin($user_id)) {
			DeleteThisUser($user_id);
			AuditAction($cfg["constants"]["admin"], $cfg['_DELETE']." ".$cfg['_USER'].": ".$user_id);
		}
		header("location: index.php?iid=admin");
		break;

	case "editUser":
		$user_id = getRequestVar('user_id');
		require_once("admin/editUser.php");
		break;

	case "updateUser":
		$user_id = getRequestVar('user_id');
		$org_user_id = getRequestVar('org_user_id');
		$pass1 = getRequestVar('pass1');
		$userType = getRequestVar('userType');
		$hideOffline = getRequestVar('hideOffline');
		require_once("admin/updateUser.php");
		break;

	case "setUserState":
		setUserState();
		header("location: index.php?iid=admin&op=showUsers");
		break;

	case "searchSettings":
		require_once("admin/searchSettings.php");
		break;

	case "updateSearchSettings":
		require_once("admin/updateSearchSettings.php");
		break;

	default:
		//require_once("admin/default.php");
		//break;

	case "showUserActivity":
		$min = getRequestVar('min');
		if (empty($min))
			$min=0;
		$user_id = getRequestVar('user_id');
		$srchFile = getRequestVar('srchFile');
		$srchAction = getRequestVar('srchAction');
		require_once("admin/showUserActivity.php");
		break;
}

?>