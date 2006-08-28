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

//******************************************************************************


//****************************************************************************
// getMenu -- displays Admin Menu
//****************************************************************************
function getMenu() {
	global $cfg;
	# create new template
	if ((strpos($cfg['theme'], '/')) === false)
		$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/admin/inc.menu.tmpl");
	else
		$tmpl = new vlibTemplate("themes/tf_standard_themes/tmpl/admin/inc.menu.tmpl");
	# define vars
	$tmpl->setvar('function', "getMenu");
	$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
	$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
	$tmpl->setvar('theme', $cfg["theme"]);
	// superadmin
	if (IsSuperAdmin()) {
		$tmpl->setvar('is_superadmin', 1);
		$tmpl->setvar('superAdminLink', getSuperAdminLink('','<font class="adminlink">superadmin</font>')." | ");
	}
	$tmpl->setvar('_SETTINGS_MENU', _SETTINGS_MENU);
	$tmpl->setvar('_FLUXD_MENU', _FLUXD_MENU);
	$tmpl->setvar('_SEARCHSETTINGS_MENU', _SEARCHSETTINGS_MENU);
	$tmpl->setvar('_LINKS_MENU', _LINKS_MENU);
	$tmpl->setvar('_ACTIVITY_MENU', _ACTIVITY_MENU);
	// grab the template
	$output = $tmpl->grab();
	return $output;
}

//****************************************************************************
// getUserSection -- displays the user section
//****************************************************************************
function getUserSection() {
	global $cfg, $db;
	# create new template
	if ((strpos($cfg['theme'], '/')) === false)
		$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/admin/inc.users.tmpl");
	else
		$tmpl = new vlibTemplate("themes/tf_standard_themes/tmpl/admin/inc.users.tmpl");
	# define vars
	$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
	$tmpl->setvar('table_data_bg', $cfg["table_data_bg"]);
	$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
	$tmpl->setvar('theme', $cfg["theme"]);
	$tmpl->setvar('days_to_keep', $cfg["days_to_keep"]);
	$tmpl->setvar('_USERDETAILS', _USERDETAILS);
	$tmpl->setvar('_USER', _USER);
	$tmpl->setvar('_HITS', _HITS);
	$tmpl->setvar('_UPLOADACTIVITY', _UPLOADACTIVITY);
	$tmpl->setvar('_JOINED', _JOINED);
	$tmpl->setvar('_LASTVISIT', _LASTVISIT);
	$tmpl->setvar('_ADMIN', _ADMIN);
	$tmpl->setvar('_DAYS', _DAYS);
	$tmpl->setvar('_SENDMESSAGETO', _SENDMESSAGETO);
	$tmpl->setvar('_WARNING', _WARNING);
	$tmpl->setvar('_ABOUTTODELETE', _ABOUTTODELETE);
	$tmpl->setvar('_USERSACTIVITY', _USERSACTIVITY);
	$tmpl->setvar('_EDIT', _EDIT);
	$tmpl->setvar('_DELETE', _DELETE);
	// xfer-prepare
	$tmpl->setvar('enable_xfer', $cfg["enable_xfer"]);
	if ($cfg['enable_xfer'] == 1) {
		$tmpl->setvar('colspan', 8);
		// getTransferListArray to update xfer-stats
		$cfg['xfer_realtime'] = 1;
		@getTransferListArray();
	} else {
		$tmpl->setvar('colspan', 8);
		$xfer_usage = "";
	}
	// activity-prepare
	$total_activity = GetActivityCount();
	$sql = "SELECT user_id, hits, last_visit, time_created, user_level, state FROM tf_users ORDER BY user_id";
	$result = $db->Execute($sql);
	showError($db, $sql);
	// user-details
	$user_details_list = array();
	while (list($user_id, $hits, $last_visit, $time_created, $user_level, $user_state) = $result->FetchRow()) {
		// disk-usage
		$disk_usage = "0";
		$dudir = @shell_exec($cfg['bin_du']." -sk -h -D ".correctFileName($cfg["path"].$user_id."/"));
		$dusize = @explode("\t", $dudir);
		$disk_usage = @array_shift($dusize);
		// xfer-usage
		if ($cfg['enable_xfer'] == 1) {
			$sql2 = 'SELECT SUM(download) AS download, SUM(upload) AS upload FROM tf_xfer WHERE user LIKE "'.$user_id.'"';
			$result2 = $db->Execute($sql2);
			showError($db, $sql2);
			$row = $result2->FetchRow();
			if (!empty($row)) {
				$xfer_usage = formatFreeSpace(($row["download"] / (1024 * 1024)) + ($row["upload"] / (1024 * 1024)));
			} else {
				$xfer_usage = "0";
			}
		}
		// activity
		$user_activity = GetActivityCount($user_id);
		if ($user_activity == 0)
			$user_percent = 0;
		else
			$user_percent = number_format(($user_activity / $total_activity)*100);
		// online
		$user_icon = "images/user_offline.gif";
		if (IsOnline($user_id))
			$user_icon = "images/user.gif";
		// level
		$user_image = "images/user.gif";
		$type_user = _NORMALUSER;
		if ($user_level == 1) {
			$user_image = "images/admin_user.gif";
			$type_user = _ADMINISTRATOR;
		}
		if ($user_level == 2) {
			$user_image = "images/superadmin.gif";
			$type_user = _SUPERADMIN;
		}
		if ($user_level <= 1 || IsSuperAdmin())
			$is_superadmin = 1;
		// add to list
		array_push($user_details_list, array(
			'is_user' => IsUser($user_id),
			'user_id' => $user_id,
			'user_icon' => $user_icon,
			'hits' => $hits,
			'disk_usage' => $disk_usage,
			'xfer_usage' => $xfer_usage,
			'user_percent' => $user_percent,
			'user_percent2' => $user_percent*2,
			'user_percent3' => (200 - ($user_percent*2)),
			'time_created' => date(_DATEFORMAT, $time_created),
			'last_visit' => date(_DATETIMEFORMAT, $last_visit),
			'user_image' => $user_image,
			'type_user' => $type_user,
			'user_level' => $user_level,
			'user_state' => $user_state,
			'is_superadmin' => $is_superadmin,
			)
		);
	}
	$tmpl->setloop('user_details_list', $user_details_list);
	// grab the template
	$output = $tmpl->grab();
	return $output;
}

//****************************************************************************
// getActivity -- displays Activity
//****************************************************************************
function getActivity($min=0, $user="", $srchFile="", $srchAction="") {
	global $cfg, $db;
	# create new template
	if ((strpos($cfg['theme'], '/')) === false)
		$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/admin/inc.activity.tmpl");
	else
		$tmpl = new vlibTemplate("themes/tf_standard_themes/tmpl/admin/inc.activity.tmpl");

	$sqlForSearch = "";
	$userdisplay = $user;
	if($user != "")
		$sqlForSearch .= "user_id='".$user."' AND ";
	else
		$userdisplay = _ALLUSERS;
	if($srchFile != "")
		$sqlForSearch .= "file like '%".$srchFile."%' AND ";
	if($srchAction != "")
		$sqlForSearch .= "action like '%".$srchAction."%' AND ";
	$offset = 50;
	$inx = 0;
	if (!isset($min)) $min=0;
	$max = $min+$offset;
	$sql = "SELECT user_id, file, action, ip, ip_resolved, user_agent, time FROM tf_log WHERE ".$sqlForSearch."action!=".$db->qstr($cfg["constants"]["hit"])." ORDER BY time desc";
	$result = $db->SelectLimit($sql, $offset, $min);
	$act_list = array();
	while(list($user_id, $file, $action, $ip, $ip_resolved, $user_agent, $time) = $result->FetchRow()) {
		$user_icon = "images/user_offline.gif";
		if (IsOnline($user_id))
			$user_icon = "images/user.gif";
		$is_superuser = 0;
		if (IsUser($user_id)) {
			$is_superuser = 1;
		}
		array_push($act_list, array(
			'is_superuser' => $is_superuser,
			'user_id' => $user_id,
			'user_icon' => $user_icon,
			'action' => $action,
			'file' => $file,
			'ip_resolved' => $ip_resolved,
			'user_agent' => $user_agent,
			'ip' => $ip,
			'date' => date(_DATETIMEFORMAT, $time),
			)
		);
		$inx++;
	}
	$tmpl->setloop('act_list', $act_list);
	$prev = ($min-$offset);
	# define vars
	$tmpl->setvar('_NORECORDSFOUND', _NORECORDSFOUND);
	$tmpl->setvar('_SENDMESSAGETO', _SENDMESSAGETO);
	$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
	$tmpl->setvar('inx', $inx);
	$tmpl->setvar('_ACTIVITYSEARCH', _ACTIVITYSEARCH);
	$tmpl->setvar('_FILE', _FILE);
	$tmpl->setvar('srchFile', $srchFile);
	$tmpl->setvar('prev', $prev);
	$tmpl->setvar('user', $user);
	$tmpl->setvar('min', $min);
	$tmpl->setvar('max', $max);
	$tmpl->setvar('srchAction', $srchAction);
	$tmpl->setvar('_SHOWPREVIOUS', _SHOWPREVIOUS);
	$tmpl->setvar('_SHOWMORE', _SHOWMORE);
	$tmpl->setvar('_ACTION', _ACTION);
	$tmpl->setvar('_ALL', _ALL);
	$selected = "";
	$action_list = array();
	foreach ($cfg["constants"] as $action) {
		$selected = "";
		if($action != $cfg["constants"]["hit"]) {
			if($srchAction == $action) {
				$selected = "selected";
			}
			array_push($action_list, array(
				'action' => $action,
				'selected' => $selected,
				)
			);
		}
	}
	$tmpl->setloop('action_list', $action_list);
	$tmpl->setvar('_USER', _USER);
	$user_list = array();
	$users = GetUsers();
	$selected = "";
	for($inx2 = 0; $inx2 < sizeof($users); $inx2++) {
		$selected = "";
		if($user == $users[$inx2]) {
			$selected = "selected";
		}
		array_push($user_list, array(
			'user' => $users[$inx2],
			'selected' => $selected,
			)
		);
	}
	$tmpl->setloop('user_list', $user_list);
	$tmpl->setvar('_SEARCH', _SEARCH);
	$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
	$tmpl->setvar('table_data_bg', $cfg["table_data_bg"]);
	$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
	$tmpl->setvar('theme', $cfg["theme"]);
	$tmpl->setvar('_ACTIVITYLOG', _ACTIVITYLOG);
	$tmpl->setvar('days_to_keep', $cfg["days_to_keep"]);
	$tmpl->setvar('_DAYS', _DAYS);
	$tmpl->setvar('userdisplay', $userdisplay);
	if($prev >= 0) {
		$tmpl->setvar('is_prev', 1);
	}
	if($inx>=$offset) {
		$tmpl->setvar('is_more', 1);
	}
	$tmpl->setvar('_USER', _USER);
	$tmpl->setvar('_ACTION', _ACTION);
	$tmpl->setvar('_FILE', _FILE);
	$tmpl->setvar('_IP', _IP);
	$tmpl->setvar('_TIMESTAMP', _TIMESTAMP);
	if($prev >= 0 || $inx>=$offset) {
		$tmpl->setvar('both_set', 1);
	}
	// grab the template
	$output = $tmpl->grab();
	return $output;
}

//****************************************************************************
// validateFile -- Validates the existance of a file and returns the status image
//****************************************************************************
function validateFile($the_file) {
	$msg = "<img src=\"images/red.gif\" align=\"absmiddle\" title=\"Path is not Valid\"><br><font color=\"#ff0000\">Path is not Valid</font>";
	if (isFile($the_file))
		$msg = "<img src=\"images/green.gif\" align=\"absmiddle\" title=\"Valid\">";
	return $msg;
}

/**
 * setUserState
 *
 */
function setUserState() {
	global $cfg, $db;
	$user_id = getRequestVar('user_id');
	$user_state = getRequestVar('state');
	// check params
	if (! (isset($user_id)) && (isset($user_state)))
		return false;
	// sanity-check, dont allow setting state of superadmin to 0
	if (($user_state == 0) && (IsSuperAdmin($user_id))) {
		AuditAction($cfg["constants"]["error"], "Invalid try to deactivate superadmin account.");
		return false;
	}
	// set new state
	$sql='SELECT * FROM tf_users WHERE user_id = '.$db->qstr($user_id);
	$rs = $db->Execute($sql);
	showError($db,$sql);
	$rec = array('state'=>$user_state);
	$sql = $db->GetUpdateSQL($rs, $rec);
	$result = $db->Execute($sql);
	showError($db,$sql);
	switch ($user_state) {
		case 0:
			AuditAction($cfg["constants"]["admin"], "User ".$user_id." deactivated.");
			break;
		case 1:
			AuditAction($cfg["constants"]["admin"], "User ".$user_id." activated.");
			break;
	}
	return true;
}

?>