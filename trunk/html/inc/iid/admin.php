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
		require_once("admin_".$continue.".php");
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
	$menu = "<table width=\"760\" border=1 bordercolor=\"".$cfg["table_admin_border"]."\" cellpadding=\"2\" cellspacing=\"0\">";
	$menu .= "<tr><td colspan=6 bgcolor=\"".$cfg["table_header_bg"]."\" background=\"themes/".$cfg["theme"]."/images/bar.gif\"><div align=\"center\">";
	// superadmin
	if (IsSuperAdmin())
		$menu .= getSuperAdminLink('','<font class="adminlink">superadmin</font>')." | ";
	// settings
	$menu .= "<a href=\"index.php?iid=admin&op=configSettings\"><font class=\"adminlink\">"._SETTINGS_MENU."</font></a> | ";
	// fluxd
	$menu .= "<a href=\"index.php?iid=admin&op=fluxdSettings\"><font class=\"adminlink\">"._FLUXD_MENU."</font></a> | ";
	// ui
	$menu .= "<a href=\"index.php?iid=admin&op=uiSettings\"><font class=\"adminlink\">ui</font></a> | ";
	// search
	$menu .= "<a href=\"index.php?iid=admin&op=searchSettings\"><font class=\"adminlink\">"._SEARCHSETTINGS_MENU."</font></a> | ";
	// links
	$menu .= "<a href=\"index.php?iid=admin&op=editLinks\"><font class=\"adminlink\">"._LINKS_MENU."</font></a> | ";
	// rss
	$menu .= "<a href=\"index.php?iid=admin&op=editRSS\"><font class=\"adminlink\">rss</font></a> | ";
	// users
	$menu .= "<a href=\"index.php?iid=admin&op=showUsers\"><font class=\"adminlink\">users</font></a> | ";
	// activity
	$menu .= "<a href=\"index.php?iid=admin&op=showUserActivity\"><font class=\"adminlink\">"._ACTIVITY_MENU."</font></a>";
	//
	$menu .= "</div></td></tr>";
	$menu .= "</table><br>";
	return $menu;
}

//****************************************************************************
// getUserSection -- displays the user section
//****************************************************************************
function getUserSection() {
	global $cfg, $db;
	$userSection = "<table width=\"760\" border=1 bordercolor=\"".$cfg["table_admin_border"]."\" cellpadding=\"2\" cellspacing=\"0\" bgcolor=\"".$cfg["table_data_bg"]."\">";
	$userSection .= "<tr><td colspan=6 bgcolor=\"".$cfg["table_header_bg"]."\" background=\"themes/".$cfg["theme"]."/images/bar.gif\"><img src=\"images/user_group.gif\" width=17 height=14 border=0>&nbsp;&nbsp;<font class=\"title\">"._USERDETAILS."</font></div></td></tr>";
	$userSection .= "<tr>";
	$userSection .= "<td bgcolor=\"".$cfg["table_header_bg"]."\" width=\"15%\"><div align=center class=\"title\">"._USER."</div></td>";
	$userSection .= "<td bgcolor=\"".$cfg["table_header_bg"]."\" width=\"6%\"><div align=center class=\"title\">"._HITS."</div></td>";
	$userSection .= "<td bgcolor=\"".$cfg["table_header_bg"]."\"><div align=center class=\"title\">"._UPLOADACTIVITY." (".$cfg["days_to_keep"]." "._DAYS.")</div></td>";
	$userSection .= "<td bgcolor=\"".$cfg["table_header_bg"]."\" width=\"6%\"><div align=center class=\"title\">"._JOINED."</div></td>";
	$userSection .= "<td bgcolor=\"".$cfg["table_header_bg"]."\" width=\"15%\"><div align=center class=\"title\">"._LASTVISIT."</div></td>";
	$userSection .= "<td bgcolor=\"".$cfg["table_header_bg"]."\" width=\"8%\"><div align=center class=\"title\">"._ADMIN."</div></td>";
	$userSection .= "</tr>";
	$total_activity = GetActivityCount();
	$sql= "SELECT user_id, hits, last_visit, time_created, user_level, state FROM tf_users ORDER BY user_id";
	$result = $db->Execute($sql);
	while(list($user_id, $hits, $last_visit, $time_created, $user_level, $user_state) = $result->FetchRow()) {
		$user_activity = GetActivityCount($user_id);
		if ($user_activity == 0)
			$user_percent = 0;
		else
			$user_percent = number_format(($user_activity/$total_activity)*100);
		$user_icon = "images/user_offline.gif";
		if (IsOnline($user_id))
			$user_icon = "images/user.gif";
		$userSection .= "<tr>";
		if (IsUser($user_id))
			$userSection .= "<td><a href=\"index.php?iid=message&to_user=".$user_id."\"><img src=\"".$user_icon."\" width=17 height=14 title=\""._SENDMESSAGETO." ".$user_id."\" border=0 align=\"bottom\">".$user_id."</a></td>";
		else
			$userSection .= "<td><img src=\"".$user_icon."\" width=17 height=14 title=\"n/a\" border=0 align=\"bottom\">".$user_id."</td>";
		$userSection .= "<td><div class=\"tiny\" align=\"right\">".$hits."</div></td>";
		$userSection .= "<td><div align=center>";
		$userSection .= '<table width="310" border="0" cellpadding="0" cellspacing="0">';
		$userSection .= '<tr>';
		$userSection .= '<td width="200">';
			$userSection .= '<table width="200" border="0" cellpadding="0" cellspacing="0">';
			$userSection .= '<tr>';
				$user_percent2 = $user_percent*2;
				$userSection .= '<td background="themes/'.$cfg["theme"].'/images/proglass.gif" width="'.$user_percent2.'"><img src="images/blank.gif" width="1" height="12" border="0"></td>';
				$user_percent3 = (200 - ($user_percent*2));
				$userSection .= '<td background="themes/'.$cfg["theme"].'/images/noglass.gif" width="'.$user_percent3.'"><img src="images/blank.gif" width="1" height="12" border="0"></td>';
			$userSection .= '</tr>';
			$userSection .= '</table>';
		$userSection .= '</td>';
		$userSection .= '<td align="right" width="40"><div class="tiny" align="right">'.$user_activity.'</div></td>';
		$userSection .= '<td align="right" width="40"><div class="tiny" align="right">'.$user_percent.'%</div></td>';
		$userSection .= '<td align="right"><a href="index.php?iid=admin&op=showUserActivity&user_id='.$user_id.'">';
		$userSection .= '<img src="images/properties.png" width="18" height="13" title="'.$user_id.'\'s '._USERSACTIVITY.'" border="0"></a></td>';
		$userSection .= '</tr>';
		$userSection .= '</table>';
		$userSection .= "</td>";
		$userSection .= "<td><div class=\"tiny\" align=\"center\" nowrap>".date(_DATEFORMAT, $time_created)."</div></td>";
		$userSection .= "<td><div class=\"tiny\" align=\"center\" nowrap>".date(_DATETIMEFORMAT, $last_visit)."</div></td>";
		$userSection .= "<td><div align=\"right\" class=\"tiny\" nowrap>";
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
		// user-type-pic
		$userSection .= "<img src=\"".$user_image."\" title=\"".$user_id." - ".$type_user."\">";
		$userSection .= "&nbsp;";
		// state
		if ($user_level <= 1) {
			if ($user_state == 1)
				$userSection .= "<a href=\"index.php?iid=admin&op=setUserState&user_id=".$user_id."&state=0\"><img src=\"images/green.gif\" width=\"13\" height=\"13\" title=\"deactivate ".$user_id."\" border=\"0\"></a>";
			else
				$userSection .= "<a href=\"index.php?iid=admin&op=setUserState&user_id=".$user_id."&state=1\"><img src=\"images/red.gif\" width=\"13\" height=\"13\" title=\"activate ".$user_id."\" border=\"0\"></a>";
		} else {
			$userSection .= "<img src=\"images/black.gif\" width=\"13\" height=\"13\" title=\"superadmin always activated\">";
		}
		$userSection .= "&nbsp;";
		// edit
		if ($user_level <= 1 || IsSuperAdmin())
			$userSection .= "<a href=\"index.php?iid=admin&op=editUser&user_id=".$user_id."\"><img src=\"images/edit.png\" width=12 height=13 title=\""._EDIT." ".$user_id."\" border=0></a>";
		$userSection .= "&nbsp;";
		// delete
		if ($user_level <= 1)
			$userSection .= "<a href=\"index.php?iid=admin&op=deleteUser&user_id=".$user_id."\"><img src=\"images/delete_on.gif\" border=0 width=16 height=16 title=\""._DELETE." ".$user_id."\" onclick=\"return ConfirmDeleteUser('".$user_id."')\"></a>";
		else
			$userSection .= "<img src=\"images/delete_off.gif\" width=16 height=16 title=\"n/a\">";
		$userSection .= "&nbsp;";
		//
		$userSection .= "</div></td>";
		$userSection .= "</tr>";
	}
	$userSection .= "</table>";
	$userSection .= '<script language="JavaScript">';
	$userSection .= 'function ConfirmDeleteUser(user) {';
		$userSection .= 'return confirm("'._WARNING.': '._ABOUTTODELETE.': " + user)';
	$userSection .= '}';
	$userSection .= '</script>';
	return $userSection;
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
	$activity .= '<script src="overlib.js" type="text/javascript"></script>';
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