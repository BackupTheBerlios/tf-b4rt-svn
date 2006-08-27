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
	$tmpl->setvar('_USERDETAILS', _USERDETAILS);
	$tmpl->setvar('_USER', _USER);
	$tmpl->setvar('_HITS', _HITS);
	$tmpl->setvar('_UPLOADACTIVITY', _UPLOADACTIVITY);
	$tmpl->setvar('_JOINED', _JOINED);
	$tmpl->setvar('_LASTVISIT', _LASTVISIT);
	$tmpl->setvar('_ADMIN', _ADMIN);
	$tmpl->setvar('days_to_keep', $cfg["days_to_keep"]);
	$tmpl->setvar('_DAYS', _DAYS);
	$tmpl->setvar('_SENDMESSAGETO', _SENDMESSAGETO);
	// activity
	$total_activity = GetActivityCount();
	$sql= "SELECT user_id, hits, last_visit, time_created, user_level, state FROM tf_users ORDER BY user_id";
	$result = $db->Execute($sql);
	$user_activity_list = array();
	while(list($user_id, $hits, $last_visit, $time_created, $user_level, $user_state) = $result->FetchRow()) {
		$user_activity = GetActivityCount($user_id);
		if ($user_activity == 0) {
			$user_percent = 0;
		}
		else {
			$user_percent = number_format(($user_activity/$total_activity)*100);
		}
		$user_icon = "images/user_offline.gif";
		if (IsOnline($user_id)) {
			$user_icon = "images/user.gif";
		}
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
		if ($user_level <= 1 || IsSuperAdmin()) {
			$is_superadmin = 1;
		}
		array_push($user_activity_list, array(
			'is_user' => IsUser($user_id),
			'user_id' => $user_id,
			'user_icon' => $user_icon,
			'hits' => $hits,
			'user_percent' => $user_percent,
			'user_percent2' => $user_percent*2,
			'user_percent3' => (200 - ($user_percent*2)),
			'hits' => $hits,
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
	$tmpl->setloop('user_activity_list', $user_activity_list);
	$tmpl->setvar('_WARNING', _WARNING);
	$tmpl->setvar('_ABOUTTODELETE', _ABOUTTODELETE);
	$tmpl->setvar('_USERSACTIVITY', _USERSACTIVITY);
	$tmpl->setvar('_EDIT', _EDIT);
	$tmpl->setvar('_DELETE', _DELETE);
	// grab the template
	$output = $tmpl->grab();
	return $output;
}

//****************************************************************************
// getActivity -- displays Activity
//****************************************************************************
function getActivity($min=0, $user="", $srchFile="", $srchAction="") {
	global $cfg, $db;
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
	$output = "";
	$morelink = "";
	$sql = "SELECT user_id, file, action, ip, ip_resolved, user_agent, time FROM tf_log WHERE ".$sqlForSearch."action!=".$db->qstr($cfg["constants"]["hit"])." ORDER BY time desc";
	$result = $db->SelectLimit($sql, $offset, $min);
	while(list($user_id, $file, $action, $ip, $ip_resolved, $user_agent, $time) = $result->FetchRow()) {
		$user_icon = "images/user_offline.gif";
		if (IsOnline($user_id))
			$user_icon = "images/user.gif";
		$ip_info = $ip_resolved."<br>".$user_agent;
		$output .= "<tr>";
		if (IsUser($user_id))
			$output .= "<td><a href=\"index.php?iid=message&to_user=".$user_id."\"><img src=\"".$user_icon."\" width=17 height=14 title=\""._SENDMESSAGETO." ".$user_id."\" border=0 align=\"bottom\">".$user_id."</a>&nbsp;&nbsp;</td>";
		else
			$output .= "<td><img src=\"".$user_icon."\" width=17 height=14 title=\"n/a\" border=0 align=\"bottom\">".$user_id."&nbsp;&nbsp;</td>";
		$output .= "<td><div class=\"tiny\">".$action."</div></td>";
		$output .= "<td><div align=center><div class=\"tiny\" align=\"left\">";
		$output .= $file;
		$output .= "</div></td>";
		$output .= "<td><div class=\"tiny\" align=\"left\"><a href=\"javascript:void(0)\" onclick=\"return overlib('".$ip_info."<br>', STICKY, CSSCLASS);\" onmouseover=\"return overlib('".$ip_info."<br>', CSSCLASS);\" onmouseout=\"return nd();\"><img src=\"images/properties.png\" width=\"18\" height=\"13\" border=\"0\"><font class=tiny>".$ip."</font></a></div></td>";
		$output .= "<td><div class=\"tiny\" align=\"center\">".date(_DATETIMEFORMAT, $time)."</div></td>";
		$output .= "</tr>";
		$inx++;
	}
	if($inx == 0)
		$output = "<tr><td colspan=6><center><strong>-- "._NORECORDSFOUND." --</strong></center></td></tr>";
	$prev = ($min-$offset);
	if ($prev >= 0) {
		$prevlink = "<a href=\"index.php?iid=admin&op=showUserActivity&min=".$prev."&user_id=".$user."&srchFile=".$srchFile."&srchAction=".$srchAction."\">";
		$prevlink .= "<font class=\"TinyWhite\">&lt;&lt;".$min." "._SHOWPREVIOUS."]</font></a> &nbsp;";
	}
	if ($inx>=$offset) {
		$morelink = "<a href=\"index.php?iid=admin&op=showUserActivity&min=".$max."&user_id=".$user."&srchFile=".$srchFile."&srchAction=".$srchAction."\">";
		$morelink .= "<font class=\"TinyWhite\">["._SHOWMORE."&gt;&gt;</font></a>";
	}
	$activity = '<div id="overDiv" style="position:absolute;visibility:hidden;z-index:1000;"></div>';
	$activity .= '<script language="JavaScript">';
	$activity .= 'var ol_closeclick = "1";';
	$activity .= 'var ol_close = "<font color=#ffffff><b>X</b></font>";';
	$activity .= 'var ol_fgclass = "fg";';
	$activity .= 'var ol_bgclass = "bg";';
	$activity .= 'var ol_captionfontclass = "overCaption";';
	$activity .= 'var ol_closefontclass = "overClose";';
	$activity .= 'var ol_textfontclass = "overBody";';
	$activity .= 'var ol_cap = "&nbsp;IP Info";';
	$activity .= '</script>';
	$activity .= '<script src="js/overlib.js" type="text/javascript"></script>';
	$activity .= '<div align="center">';
	$activity .= '<table>';
	$activity .= '<form action="index.php?iid=admin&op=showUserActivity" name="searchForm" method="post">';
	$activity .= '<tr>';
		$activity .= '<td>';
		$activity .= '<strong>'._ACTIVITYSEARCH.'</strong>&nbsp;&nbsp;&nbsp;';
		$activity .= _FILE;
		$activity .= '<input type="Text" name="srchFile" value="'.$srchFile.'" width="30"> &nbsp;&nbsp;';
		$activity .= _ACTION;
		$activity .= '<select name="srchAction">';
		$activity .= '<option value="">-- '._ALL.' --</option>';
		$selected = "";
		foreach ($cfg["constants"] as $action) {
			$selected = "";
			if($action != $cfg["constants"]["hit"]) {
				if($srchAction == $action)
					$selected = "selected";
				$activity .= "<option value=\"".$action."\" ".$selected.">".$action."</option>";
			}
		}
		$activity .= '</select>&nbsp;&nbsp;';
		$activity .= _USER.':';
		$activity .= '<select name="user_id">';
		$activity .= '<option value="">-- '._ALL.' --</option>';
		$users = GetUsers();
		$selected = "";
		for($inx = 0; $inx < sizeof($users); $inx++) {
			$selected = "";
			if($user == $users[$inx])
				$selected = "selected";
			$activity .= "<option value=\"".$users[$inx]."\" ".$selected.">".$users[$inx]."</option>";
		}
		$activity .= '</select>';
		$activity .= '<input type="Submit" value="'._SEARCH.'">';
		$activity .= '</td>';
	$activity .= '</tr>';
	$activity .= '</form>';
	$activity .= '</table>';
	$activity .= '</div>';
	$activity .= "<table width=\"760\" border=1 bordercolor=\"".$cfg["table_admin_border"]."\" cellpadding=\"2\" cellspacing=\"0\" bgcolor=\"".$cfg["table_data_bg"]."\">";
	$activity .= "<tr><td colspan=6 bgcolor=\"".$cfg["table_header_bg"]."\" background=\"themes/".$cfg["theme"]."/images/bar.gif\">";
	$activity .= "<table width=\"100%\" cellpadding=0 cellspacing=0 border=0><tr><td>";
	$activity .= "<img src=\"images/properties.png\" width=18 height=13 border=0>&nbsp;&nbsp;<font class=\"title\">"._ACTIVITYLOG." ".$cfg["days_to_keep"]." "._DAYS." (".$userdisplay.")</font>";
	if(!empty($prevlink) && !empty($morelink))
		$activity .= "</td><td align=\"right\">".$prevlink.$morelink."</td></tr></table>";
	elseif(!empty($prevlink))
		$activity .= "</td><td align=\"right\">".$prevlink."</td></tr></table>";
	elseif(!empty($prevlink))
		$activity .= "</td><td align=\"right\">".$morelink."</td></tr></table>";
	else
		$activity .= "</td><td align=\"right\"></td></tr></table>";
	$activity .= "</td></tr>";
	$activity .= "<tr>";
	$activity .= "<td bgcolor=\"".$cfg["table_header_bg"]."\"><div align=center class=\"title\">"._USER."</div></td>";
	$activity .= "<td bgcolor=\"".$cfg["table_header_bg"]."\"><div align=center class=\"title\">"._ACTION."</div></td>";
	$activity .= "<td bgcolor=\"".$cfg["table_header_bg"]."\"><div align=center class=\"title\">"._FILE."</div></td>";
	$activity .= "<td bgcolor=\"".$cfg["table_header_bg"]."\" width=\"13%\"><div align=center class=\"title\">"._IP."</div></td>";
	$activity .= "<td bgcolor=\"".$cfg["table_header_bg"]."\" width=\"15%\"><div align=center class=\"title\">"._TIMESTAMP."</div></td>";
	$activity .= "</tr>";
	$activity .= $output;
	if(!empty($prevlink) || !empty($morelink)) {
		$activity .= "<tr><td colspan=6 bgcolor=\"".$cfg["table_header_bg"]."\">";
		$activity .= "<table width=\"100%\" cellpadding=0 cellspacing=0 border=0><tr><td align=\"left\">";
		if(!empty($prevlink))
			$activity .= $prevlink;
		$activity .= "</td><td align=\"right\">";
		if(!empty($morelink))
			$activity .= $morelink;
		$activity .= "</td></tr></table>";
		$activity .= "</td></tr>";
	}
	$activity .= "</table>";
	return $activity;
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