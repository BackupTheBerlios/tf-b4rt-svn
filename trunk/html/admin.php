<?php

/*************************************************************
*  TorrentFlux - PHP Torrent Manager
*  www.torrentflux.com
**************************************************************/
/*
	This file is part of TorrentFlux.

	TorrentFlux is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	TorrentFlux is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with TorrentFlux; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

require_once("config.php");
require_once("functions.php");

if(!IsAdmin()) {
	 // the user probably hit this page direct
	AuditAction($cfg["constants"]["access_denied"], $_SERVER['PHP_SELF']);
	header("location: index.php");
}

//****************************************************************************
// displayMenu -- displays Admin Menu
//****************************************************************************
function displayMenu() {
	global $cfg;
	echo "<table width=\"760\" border=1 bordercolor=\"".$cfg["table_admin_border"]."\" cellpadding=\"2\" cellspacing=\"0\">";
	echo "<tr><td colspan=6 bgcolor=\"".$cfg["table_header_bg"]."\" background=\"themes/".$cfg["theme"]."/images/bar.gif\"><div align=\"center\">";
	echo "<a href=\"admin.php\"><font class=\"adminlink\">"._ADMIN_MENU."</font></a> | ";
	echo "<a href=\"admin.php?op=configSettings\"><font class=\"adminlink\">"._SETTINGS_MENU."</font></a> | ";
	echo "<a href=\"admin.php?op=queueSettings\"><font class=\"adminlink\">"._QMANAGER_MENU."</font></a> | ";
	echo "<a href=\"admin.php?op=searchSettings\"><font class=\"adminlink\">"._SEARCHSETTINGS_MENU."</font></a> | ";
	echo "<a href=\"admin.php?op=showUserActivity\"><font class=\"adminlink\">"._ACTIVITY_MENU."</font></a> | ";
	echo "<a href=\"admin.php?op=editLinks\"><font class=\"adminlink\">"._LINKS_MENU."</font></a> | ";
	echo "<a href=\"admin.php?op=editRSS\"><font class=\"adminlink\">rss</font></a> | ";
	echo "<a href=\"admin.php?op=CreateUser\"><font class=\"adminlink\">"._NEWUSER_MENU."</font></a> | ";
	//XFER
	if ($cfg['enable_xfer'] == 1) echo "<a href=\"admin.php?op=xfer\"><font class=\"adminlink\">"._XFER."</font></a> | ";
	echo "<a href=\"admin.php?op=backupDatabase\"><font class=\"adminlink\">"._BACKUP_MENU."</font></a> | ";
	echo "<a href=\"admin.php?op=uiSettings\"><font class=\"adminlink\">ui</font></a> | ";
	echo printSuperAdminLink('','<font class="adminlink">superadmin</font>');
	echo "</div></td></tr>";
	echo "</table><br>";
}
//****************************************************************************
// displayUserSection -- displays the user section
//****************************************************************************
function displayUserSection() {
	global $cfg, $db;
	echo "<table width=\"760\" border=1 bordercolor=\"".$cfg["table_admin_border"]."\" cellpadding=\"2\" cellspacing=\"0\" bgcolor=\"".$cfg["table_data_bg"]."\">";
	echo "<tr><td colspan=6 bgcolor=\"".$cfg["table_header_bg"]."\" background=\"themes/".$cfg["theme"]."/images/bar.gif\"><img src=\"images/user_group.gif\" width=17 height=14 border=0>&nbsp;&nbsp;<font class=\"title\">"._USERDETAILS."</font></div></td></tr>";
	echo "<tr>";
	echo "<td bgcolor=\"".$cfg["table_header_bg"]."\" width=\"15%\"><div align=center class=\"title\">"._USER."</div></td>";
	echo "<td bgcolor=\"".$cfg["table_header_bg"]."\" width=\"6%\"><div align=center class=\"title\">"._HITS."</div></td>";
	echo "<td bgcolor=\"".$cfg["table_header_bg"]."\"><div align=center class=\"title\">"._UPLOADACTIVITY." (".$cfg["days_to_keep"]." "._DAYS.")</div></td>";
	echo "<td bgcolor=\"".$cfg["table_header_bg"]."\" width=\"6%\"><div align=center class=\"title\">"._JOINED."</div></td>";
	echo "<td bgcolor=\"".$cfg["table_header_bg"]."\" width=\"15%\"><div align=center class=\"title\">"._LASTVISIT."</div></td>";
	echo "<td bgcolor=\"".$cfg["table_header_bg"]."\" width=\"8%\"><div align=center class=\"title\">"._ADMIN."</div></td>";
	echo "</tr>";
	$total_activity = GetActivityCount();
	$sql= "SELECT user_id, hits, last_visit, time_created, user_level FROM tf_users ORDER BY user_id";
	$result = $db->Execute($sql);
	while(list($user_id, $hits, $last_visit, $time_created, $user_level) = $result->FetchRow()) {
		$user_activity = GetActivityCount($user_id);
		if ($user_activity == 0)
			$user_percent = 0;
		else
			$user_percent = number_format(($user_activity/$total_activity)*100);
		$user_icon = "images/user_offline.gif";
		if (IsOnline($user_id))
			$user_icon = "images/user.gif";
		echo "<tr>";
		if (IsUser($user_id))
			echo "<td><a href=\"message.php?to_user=".$user_id."\"><img src=\"".$user_icon."\" width=17 height=14 title=\""._SENDMESSAGETO." ".$user_id."\" border=0 align=\"bottom\">".$user_id."</a></td>";
		else
			echo "<td><img src=\"".$user_icon."\" width=17 height=14 title=\"n/a\" border=0 align=\"bottom\">".$user_id."</td>";
		echo "<td><div class=\"tiny\" align=\"right\">".$hits."</div></td>";
		echo "<td><div align=center>";
?>
		<table width="310" border="0" cellpadding="0" cellspacing="0">
		<tr>
		<td width="200">
			<table width="200" border="0" cellpadding="0" cellspacing="0">
			<tr>
				<td background="themes/<?php echo $cfg["theme"] ?>/images/proglass.gif" width="<?php echo $user_percent*2 ?>"><img src="images/blank.gif" width="1" height="12" border="0"></td>
				<td background="themes/<?php echo $cfg["theme"] ?>/images/noglass.gif" width="<?php echo (200 - ($user_percent*2)) ?>"><img src="images/blank.gif" width="1" height="12" border="0"></td>
			</tr>
			</table>
		</td>
		<td align="right" width="40"><div class="tiny" align="right"><?php echo $user_activity ?></div></td>
		<td align="right" width="40"><div class="tiny" align="right"><?php echo $user_percent ?>%</div></td>
		<td align="right"><a href="admin.php?op=showUserActivity&user_id=<?php echo $user_id ?>"><img src="images/properties.png" width="18" height="13" title="<?php echo $user_id."'s "._USERSACTIVITY ?>" border="0"></a></td>
		</tr>
		</table>
<?php
		echo "</td>";
		echo "<td><div class=\"tiny\" align=\"center\">".date(_DATEFORMAT, $time_created)."</div></td>";
		echo "<td><div class=\"tiny\" align=\"center\">".date(_DATETIMEFORMAT, $last_visit)."</div></td>";
		echo "<td><div align=\"right\" class=\"tiny\">";
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
			echo "<a href=\"admin.php?op=editUser&user_id=".$user_id."\"><img src=\"images/edit.png\" width=12 height=13 title=\""._EDIT." ".$user_id."\" border=0></a>";
		echo "<img src=\"".$user_image."\" title=\"".$user_id." - ".$type_user."\">";
		if ($user_level <= 1)
			echo "<a href=\"admin.php?op=deleteUser&user_id=".$user_id."\"><img src=\"images/delete_on.gif\" border=0 width=16 height=16 title=\""._DELETE." ".$user_id."\" onclick=\"return ConfirmDeleteUser('".$user_id."')\"></a>";
		else
			echo "<img src=\"images/delete_off.gif\" width=16 height=16 title=\"n/a\">";
		echo "</div></td>";
		echo "</tr>";
	}
	echo "</table>";
?>
	<script language="JavaScript">
	function ConfirmDeleteUser(user) {
		return confirm("<?php echo _WARNING.": "._ABOUTTODELETE ?>: " + user)
	}
	</script>
<?php
}
//****************************************************************************
// displayActivity -- displays Activity
//****************************************************************************
function displayActivity($min=0, $user="", $srchFile="", $srchAction="") {
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
			$output .= "<td><a href=\"message.php?to_user=".$user_id."\"><img src=\"".$user_icon."\" width=17 height=14 title=\""._SENDMESSAGETO." ".$user_id."\" border=0 align=\"bottom\">".$user_id."</a>&nbsp;&nbsp;</td>";
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
		$prevlink = "<a href=\"admin.php?op=showUserActivity&min=".$prev."&user_id=".$user."&srchFile=".$srchFile."&srchAction=".$srchAction."\">";
		$prevlink .= "<font class=\"TinyWhite\">&lt;&lt;".$min." "._SHOWPREVIOUS."]</font></a> &nbsp;";
	}
	if ($inx>=$offset) {
		$morelink = "<a href=\"admin.php?op=showUserActivity&min=".$max."&user_id=".$user."&srchFile=".$srchFile."&srchAction=".$srchAction."\">";
		$morelink .= "<font class=\"TinyWhite\">["._SHOWMORE."&gt;&gt;</font></a>";
	}
?>
	<div id="overDiv" style="position:absolute;visibility:hidden;z-index:1000;"></div>
	<script language="JavaScript">
		var ol_closeclick = "1";
		var ol_close = "<font color=#ffffff><b>X</b></font>";
		var ol_fgclass = "fg";
		var ol_bgclass = "bg";
		var ol_captionfontclass = "overCaption";
		var ol_closefontclass = "overClose";
		var ol_textfontclass = "overBody";
		var ol_cap = "&nbsp;IP Info";
	</script>
	<script src="overlib.js" type="text/javascript"></script>
	<div align="center">
	<table>
	<form action="admin.php?op=showUserActivity" name="searchForm" method="post">
	<tr>
		<td>
		<strong><?php echo _ACTIVITYSEARCH ?></strong>&nbsp;&nbsp;&nbsp;
		<?php echo _FILE ?>:
		<input type="Text" name="srchFile" value="<?php echo $srchFile ?>" width="30"> &nbsp;&nbsp;
		<?php echo _ACTION ?>:
		<select name="srchAction">
		<option value="">-- <?php echo _ALL ?> --</option>
<?php
		$selected = "";
		foreach ($cfg["constants"] as $action) {
			$selected = "";
			if($action != $cfg["constants"]["hit"]) {
				if($srchAction == $action)
					$selected = "selected";
				echo "<option value=\"".$action."\" ".$selected.">".$action."</option>";
			}
		}
?>
		</select>&nbsp;&nbsp;
		<?php echo _USER ?>:
		<select name="user_id">
		<option value="">-- <?php echo _ALL ?> --</option>
<?php
		$users = GetUsers();
		$selected = "";
		for($inx = 0; $inx < sizeof($users); $inx++) {
			$selected = "";
			if($user == $users[$inx])
				$selected = "selected";
			echo "<option value=\"".$users[$inx]."\" ".$selected.">".$users[$inx]."</option>";
		}
?>
		</select>
		<input type="Submit" value="<?php echo _SEARCH ?>">
		</td>
	</tr>
	</form>
	</table>
	</div>

<?php
	echo "<table width=\"760\" border=1 bordercolor=\"".$cfg["table_admin_border"]."\" cellpadding=\"2\" cellspacing=\"0\" bgcolor=\"".$cfg["table_data_bg"]."\">";
	echo "<tr><td colspan=6 bgcolor=\"".$cfg["table_header_bg"]."\" background=\"themes/".$cfg["theme"]."/images/bar.gif\">";
	echo "<table width=\"100%\" cellpadding=0 cellspacing=0 border=0><tr><td>";
	echo "<img src=\"images/properties.png\" width=18 height=13 border=0>&nbsp;&nbsp;<font class=\"title\">"._ACTIVITYLOG." ".$cfg["days_to_keep"]." "._DAYS." (".$userdisplay.")</font>";
	if(!empty($prevlink) && !empty($morelink))
		echo "</td><td align=\"right\">".$prevlink.$morelink."</td></tr></table>";
	elseif(!empty($prevlink))
		echo "</td><td align=\"right\">".$prevlink."</td></tr></table>";
	elseif(!empty($prevlink))
		echo "</td><td align=\"right\">".$morelink."</td></tr></table>";
	else
		echo "</td><td align=\"right\"></td></tr></table>";
	echo "</td></tr>";
	echo "<tr>";
	echo "<td bgcolor=\"".$cfg["table_header_bg"]."\"><div align=center class=\"title\">"._USER."</div></td>";
	echo "<td bgcolor=\"".$cfg["table_header_bg"]."\"><div align=center class=\"title\">"._ACTION."</div></td>";
	echo "<td bgcolor=\"".$cfg["table_header_bg"]."\"><div align=center class=\"title\">"._FILE."</div></td>";
	echo "<td bgcolor=\"".$cfg["table_header_bg"]."\" width=\"13%\"><div align=center class=\"title\">"._IP."</div></td>";
	echo "<td bgcolor=\"".$cfg["table_header_bg"]."\" width=\"15%\"><div align=center class=\"title\">"._TIMESTAMP."</div></td>";
	echo "</tr>";
	echo $output;
	if(!empty($prevlink) || !empty($morelink)) {
		echo "<tr><td colspan=6 bgcolor=\"".$cfg["table_header_bg"]."\">";
		echo "<table width=\"100%\" cellpadding=0 cellspacing=0 border=0><tr><td align=\"left\">";
		if(!empty($prevlink)) echo $prevlink;
			echo "</td><td align=\"right\">";
		if(!empty($morelink)) echo $morelink;
			echo "</td></tr></table>";
		echo "</td></tr>";
	}
	echo "</table>";
}
//****************************************************************************
// validatePath -- Validates TF Path and Permissions
//****************************************************************************
function validatePath($path) {
	$msg = "<img src=\"images/red.gif\" align=\"absmiddle\" title=\"Path is not Valid\"><br><font color=\"#ff0000\">Path is not Valid</font>";
	if (is_dir($path)) {
		if (is_writable($path))
			$msg = "<img src=\"images/green.gif\" align=\"absmiddle\" title=\"Valid\">";
		else
			$msg = "<img src=\"images/red.gif\" align=\"absmiddle\" title=\"Path is not Writable\"><br><font color=\"#ff0000\">Path is not Writable -- make sure you chmod +w this path</font>";
	}
	return $msg;
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


//******************************************************************************
// TRAFFIC CONTROLER
//******************************************************************************

$op = getRequestVar('op');

switch ($op) {

	default:
		$min = getRequestVar('min');
		if(empty($min)) $min=0;
		require_once("admin_default.php");
	break;

	case "showUserActivity":
		$min = getRequestVar('min');
		if(empty($min)) $min=0;
		$user_id = getRequestVar('user_id');
		$srchFile = getRequestVar('srchFile');
		$srchAction = getRequestVar('srchAction');
		require_once("admin_showUserActivity.php");
	break;

	//XFER
	case "xfer":
		echo DisplayHead(_XFER);
		displayMenu();
		if ($cfg['enable_xfer'] == 1) {
			getDirList($cfg["torrent_file_path"],0);
			displayXfer();
		}
		echo DisplayFoot(true,true);
	break;

	case "backupDatabase":
		require_once("admin_backupDatabase.php");
	break;

	case "editRSS":
		require_once("admin_editRSS.php");
	break;

	case "addRSS":
		$newRSS = getRequestVar('newRSS');
		if(!empty($newRSS)){
			addNewRSS($newRSS);
			AuditAction($cfg["constants"]["admin"], "New RSS: ".$newRSS);
		}
		header("location: admin.php?op=editRSS");
	break;

	case "deleteRSS":
		$rid = getRequestVar('rid');
		AuditAction($cfg["constants"]["admin"], _DELETE." RSS: ".getRSS($rid));
		deleteOldRSS($rid);
		header("location: admin.php?op=editRSS");
	break;

	// Link Mod
	case "editLink":
		$lid = getRequestVar('lid');
		$editLink = getRequestVar('editLink');
		$editSite = getRequestVar('editSite');
		require_once("admin_editLink.php");
	break;
	// Link Mod

	case "editLinks":
		require_once("admin_editLinks.php");
	break;

	case "addLink":
		$newLink = getRequestVar('newLink');
		$newSite = getRequestVar('newSite');
		require_once("admin_addLink.php");
	break;

	case "moveLink":
		$lid = getRequestVar('lid');
		$direction = getRequestVar('direction');
		require_once("admin_moveLink.php");
	break;

	case "deleteLink":
		$lid = getRequestVar('lid');
		AuditAction($cfg["constants"]["admin"], _DELETE." Link: ".getSite($lid)." [".getLink($lid)."]");
		deleteOldLink($lid);
		header("location: admin.php?op=editLinks");
	break;

	case "CreateUser":
		require_once("admin_CreateUser.php");
	break;

	case "addUser":
		$newUser = getRequestVar('newUser');
		$pass1 = getRequestVar('pass1');
		$userType = getRequestVar('userType');
		require_once("admin_addUser.php");
	break;

	case "deleteUser":
		$user_id = getRequestVar('user_id');
		if (!IsSuperAdmin($user_id)) {
			DeleteThisUser($user_id);
			AuditAction($cfg["constants"]["admin"], _DELETE." "._USER.": ".$user_id);
		}
		header("location: admin.php");
	break;

	case "editUser":
		$user_id = getRequestVar('user_id');
		require_once("admin_editUser.php");
	break;

	case "updateUser":
		$user_id = getRequestVar('user_id');
		$org_user_id = getRequestVar('org_user_id');
		$pass1 = getRequestVar('pass1');
		$userType = getRequestVar('userType');
		$hideOffline = getRequestVar('hideOffline');
		require_once("admin_updateUser.php");
	break;

	case "configSettings":
		require_once("admin_configSettings.php");
	break;

	case "updateConfigSettings":
		if (! array_key_exists("debugTorrents", $_REQUEST))
			$_REQUEST["debugTorrents"] = false;
		$settings = processSettingsParams();
		saveSettings($settings);
		AuditAction($cfg["constants"]["admin"], " Updating TorrentFlux Settings");
		$continue = getRequestVar('continue');
		header("Location: admin.php?op=".$continue);
	break;

	case "updateQueueSettings":
		if (! array_key_exists("debugTorrents", $_REQUEST))
			$_REQUEST["debugTorrents"] = false;
		require_once("admin_updateQueueSettings.php");
	break;

	case "controlQueueManager":
		require_once("admin_controlQueueManager.php");
	break;

	case "queueSettings":
		require_once("admin_queueSettings.php");
	break;

	case "uiSettings":
		require_once("admin_uiSettings.php");
	break;

	case "updateUiSettings":
		$settings = processSettingsParams();
		saveSettings($settings);
		AuditAction($cfg["constants"]["admin"], " Updating TorrentFlux UI Settings");
		header("location: admin.php?op=uiSettings");
	break;

	case "searchSettings":
		require_once("admin_searchSettings.php");
	break;

	case "updateSearchSettings":
		require_once("admin_updateSearchSettings.php");
	break;

}
//******************************************************************************

?>