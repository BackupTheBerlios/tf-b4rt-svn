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

	default:
		//require_once("admin/default.php");
		//break;
	case "configSettings":
		require_once("admin/configSettings.php");
		break;

	case "updateConfigSettings":
		$settings = processSettingsParams();
		saveSettings($settings);
		AuditAction($cfg["constants"]["admin"], " Updating TorrentFlux Settings");
		$continue = getRequestVar('continue');
		require_once("admin/".$continue.".php");
		break;

	case "showUserActivity":
		$min = getRequestVar('min');
		if(empty($min)) $min=0;
		$user_id = getRequestVar('user_id');
		$srchFile = getRequestVar('srchFile');
		$srchAction = getRequestVar('srchAction');
		require_once("admin/showUserActivity.php");
		break;

	case "xfer":
		require_once("admin/xfer.php");
		break;

	case "backupDatabase":
		require_once("admin_backupDatabase.php");
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
		AuditAction($cfg["constants"]["admin"], _DELETE." RSS: ".getRSS($rid));
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
		AuditAction($cfg["constants"]["admin"], _DELETE." Link: ".getSite($lid)." [".getLink($lid)."]");
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
			AuditAction($cfg["constants"]["admin"], _DELETE." "._USER.": ".$user_id);
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

	case "fluxdSettings":
		require_once("admin/fluxdSettings.php");
		break;

	case "controlFluxd":
		require_once("admin/controlFluxd.php");
		break;

	case "updateFluxdSettings":
		require_once("admin/updateFluxdSettings.php");
		break;

	case "uiSettings":
		require_once("admin/uiSettings.php");
		break;

	case "updateUiSettings":
		$settings = processSettingsParams();
		saveSettings($settings);
		AuditAction($cfg["constants"]["admin"], " Updating TorrentFlux UI Settings");
		header("location: index.php?iid=admin&op=uiSettings");
		break;

	case "searchSettings":
		require_once("admin/searchSettings.php");
		break;

	case "updateSearchSettings":
		require_once("admin/updateSearchSettings.php");
		break;

}

?>