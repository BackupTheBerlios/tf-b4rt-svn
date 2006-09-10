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

/**
 * get Admin Menu
 *
 * @return string
 */
function getMenu() {
	global $cfg;
	// create template-instance
	$tmpl = getTemplateInstance($cfg["theme"], "admin/inc.menu.tmpl");
	// set some vars
	$tmpl->setvar('function', "getMenu");
	$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
	$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
	$tmpl->setvar('theme', $cfg["theme"]);
	// superadmin
	if (IsSuperAdmin()) {
		$tmpl->setvar('is_superadmin', 1);
		$tmpl->setvar('superAdminLink', getSuperAdminLink('','<font class="adminlink">superadmin</font></a>'));
	}
	$tmpl->setvar('_SETTINGS_MENU', $cfg['_SETTINGS_MENU']);
	$tmpl->setvar('_FLUXD_MENU', $cfg['_FLUXD_MENU']);
	$tmpl->setvar('_SEARCHSETTINGS_MENU', $cfg['_SEARCHSETTINGS_MENU']);
	$tmpl->setvar('_LINKS_MENU', $cfg['_LINKS_MENU']);
	$tmpl->setvar('_ACTIVITY_MENU', $cfg['_ACTIVITY_MENU']);
	// grab the template
	$output = $tmpl->grab();
	return $output;
}

/**
 * gets the user section
 *
 * @return string
 */
function getUserSection() {
	global $cfg, $db;
	// create template-instance
	$tmpl = getTemplateInstance($cfg["theme"], "admin/inc.users.tmpl");
	// set some vars
	$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
	$tmpl->setvar('table_data_bg', $cfg["table_data_bg"]);
	$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
	$tmpl->setvar('theme', $cfg["theme"]);
	$tmpl->setvar('days_to_keep', $cfg["days_to_keep"]);
	$tmpl->setvar('_USERDETAILS', $cfg['_USERDETAILS']);
	$tmpl->setvar('_USER', $cfg['_USER']);
	$tmpl->setvar('_HITS', $cfg['_HITS']);
	$tmpl->setvar('_UPLOADACTIVITY', $cfg['_UPLOADACTIVITY']);
	$tmpl->setvar('_JOINED', $cfg['_JOINED']);
	$tmpl->setvar('_LASTVISIT', $cfg['_LASTVISIT']);
	$tmpl->setvar('_ADMIN', $cfg['_ADMIN']);
	$tmpl->setvar('_DAYS', $cfg['_DAYS']);
	$tmpl->setvar('_SENDMESSAGETO', $cfg['_SENDMESSAGETO']);
	$tmpl->setvar('_WARNING', $cfg['_WARNING']);
	$tmpl->setvar('_ABOUTTODELETE', $cfg['_ABOUTTODELETE']);
	$tmpl->setvar('_USERSACTIVITY', $cfg['_USERSACTIVITY']);
	$tmpl->setvar('_EDIT', $cfg['_EDIT']);
	$tmpl->setvar('_DELETE', $cfg['_DELETE']);
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
		$tDir = $cfg["path"].$user_id."/";
		if (is_dir($tDir)) {
			switch ($cfg["_OS"]) {
				case 1: //Linux
					$dudir = shell_exec($cfg['bin_du']." -sk -h -D ".$tDir);
					break;
				case 2: //BSD
					$dudir = shell_exec($cfg['bin_du']." -sk -h -L ".$tDir);
					break;
			}
			$dusize = explode("\t", $dudir);
			$disk_usage = array_shift($dusize);
		}
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
		$user_icon = "themes/".$cfg['theme']."/images/user_offline.gif";
		if (IsOnline($user_id))
			$user_icon = "themes/".$cfg['theme']."/images/user.gif";
		// level
		$user_image = "themes/".$cfg['theme']."/images/user.gif";
		$type_user = $cfg['_NORMALUSER'];
		if ($user_level == 1) {
			$user_image = "themes/".$cfg['theme']."/images/admin_user.gif";
			$type_user = $cfg['_ADMINISTRATOR'];
		}
		if ($user_level == 2) {
			$user_image = "themes/".$cfg['theme']."/images/superadmin.gif";
			$type_user = $cfg['_SUPERADMIN'];
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
			'time_created' => date($cfg['_DATEFORMAT'], $time_created),
			'last_visit' => date($cfg['_DATETIMEFORMAT'], $last_visit),
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

/**
 * get Activity
 *
 * @param $min
 * @param $user
 * @param $srchFile
 * @param $srchAction
 * @return string
 */
function getActivity($min=0, $user="", $srchFile="", $srchAction="") {
	global $cfg, $db;
	// create template-instance
	$tmpl = getTemplateInstance($cfg["theme"], "admin/inc.activity.tmpl");
	$sqlForSearch = "";
	$userdisplay = $user;
	if($user != "")
		$sqlForSearch .= "user_id='".$user."' AND ";
	else
		$userdisplay = $cfg['_ALLUSERS'];
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
		$user_icon = "themes/".$cfg['theme']."/images/user_offline.gif";
		if (IsOnline($user_id))
			$user_icon = "themes/".$cfg['theme']."/images/user.gif";
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
			'date' => date($cfg['_DATETIMEFORMAT'], $time),
			)
		);
		$inx++;
	}
	$tmpl->setloop('act_list', $act_list);
	$prev = ($min-$offset);
	# define vars
	$tmpl->setvar('_NORECORDSFOUND', $cfg['_NORECORDSFOUND']);
	$tmpl->setvar('_SENDMESSAGETO', $cfg['_SENDMESSAGETO']);
	$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
	$tmpl->setvar('inx', $inx);
	$tmpl->setvar('_ACTIVITYSEARCH', $cfg['_ACTIVITYSEARCH']);
	$tmpl->setvar('_FILE', $cfg['_FILE']);
	$tmpl->setvar('srchFile', $srchFile);
	$tmpl->setvar('prev', $prev);
	$tmpl->setvar('user', $user);
	$tmpl->setvar('min', $min);
	$tmpl->setvar('max', $max);
	$tmpl->setvar('srchAction', $srchAction);
	$tmpl->setvar('_SHOWPREVIOUS', $cfg['_SHOWPREVIOUS']);
	$tmpl->setvar('_SHOWMORE', $cfg['_SHOWMORE']);
	$tmpl->setvar('_ACTION', $cfg['_ACTION']);
	$tmpl->setvar('_ALL', $cfg['_ALL']);
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
	$tmpl->setvar('_USER', $cfg['_USER']);
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
	$tmpl->setvar('_SEARCH', $cfg['_SEARCH']);
	$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
	$tmpl->setvar('table_data_bg', $cfg["table_data_bg"]);
	$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
	$tmpl->setvar('theme', $cfg["theme"]);
	$tmpl->setvar('_ACTIVITYLOG', $cfg['_ACTIVITYLOG']);
	$tmpl->setvar('days_to_keep', $cfg["days_to_keep"]);
	$tmpl->setvar('_DAYS', $cfg['_DAYS']);
	$tmpl->setvar('userdisplay', $userdisplay);
	if($prev >= 0) {
		$tmpl->setvar('is_prev', 1);
	}
	if($inx>=$offset) {
		$tmpl->setvar('is_more', 1);
	}
	$tmpl->setvar('_USER', $cfg['_USER']);
	$tmpl->setvar('_ACTION', $cfg['_ACTION']);
	$tmpl->setvar('_FILE', $cfg['_FILE']);
	$tmpl->setvar('_IP', $cfg['_IP']);
	$tmpl->setvar('_TIMESTAMP', $cfg['_TIMESTAMP']);
	if($prev >= 0 || $inx>=$offset) {
		$tmpl->setvar('both_set', 1);
	}
	// grab the template
	$output = $tmpl->grab();
	return $output;
}

/**
 * Validates the existance of a file and returns the status image
 *
 * @param $the_file
 * @return string
 */
function validateFile($the_file) {
	global $cfg;
	$msg = '<img src="themes/'.$cfg['theme'].'/images/red.gif" align="absmiddle" title="Path is not Valid"><br><font color="#ff0000">Path is not Valid</font>';
	if (isFile($the_file))
		$msg = '<img src="themes/'.$cfg['theme'].'/images/green.gif" align="absmiddle" title="Valid">';
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

/*
 * repairTorrentflux
 *
 */
function repairTorrentflux() {
	global $cfg, $db;

	// delete pid-files of torrent-clients
	if ($dirHandle = opendir($cfg["transfer_file_path"])) {
		while (false !== ($file = readdir($dirHandle))) {
			if ((substr($file, -1, 1)) == "d")
				@unlink($cfg["transfer_file_path"].$file);
		}
		closedir($dirHandle);
	}

	// rewrite stat-files
	require_once("inc/classes/AliasFile.php");
	$torrents = getTorrentListFromFS();
	foreach ($torrents as $torrent) {
		$alias = getAliasName($torrent);
		$owner = getOwner($torrent);
		$btclient = getTransferClient($torrent);
		$af = AliasFile::getAliasFileInstance($cfg["transfer_file_path"].$alias.".stat", $owner, $cfg, $btclient);
		if (isset($af)) {
			$af->running = 0;
			$af->percent_done = -100.0;
			$af->time_left = 'Torrent Stopped';
			$af->down_speed = 0;
			$af->up_speed = 0;
			$af->seeds = 0;
			$af->peers = 0;
			$af->WriteFile();
		}
	}

	// set flags in db
	$db->Execute("UPDATE tf_torrents SET running = '0'");

	// delete leftovers of fluxd (only do this if daemon is not running)
	$fluxdRunning = trim(shell_exec("ps aux 2> /dev/null | ".$cfg['bin_grep']." -v grep | ".$cfg['bin_grep']." -c fluxd.pl"));
	if ($fluxdRunning == "0") {
		// pid
		if (file_exists($cfg["path"].'.fluxd/fluxd.pid'))
			@unlink($cfg["path"].'.fluxd/fluxd.pid');
		// socket
		if (file_exists($cfg["path"].'.fluxd/fluxd.sock'))
			@unlink($cfg["path"].'.fluxd/fluxd.sock');
	}

}

// ***************************************************************************
// addNewLink - Add New Link
//function addNewLink($newLink)
function addNewLink($newLink,$newSite) {
	global $db;
	//$rec = array('url'=>$newLink);
	// Link sort order index:
	$idx=-1;
	// Get current highest link index:
	$sql="SELECT sort_order FROM tf_links ORDER BY sort_order DESC";
	$result=$db->SelectLimit($sql, 1);
	showError($db, $sql);
	if($result->fields === false){
		// No links currently in db:
		$idx=0;
	} else {
		$idx=$result->fields["sort_order"]+1;
	}
	$rec = array(
		'url'=>$newLink,
		'sitename'=>$newSite,
		'sort_order'=>$idx
	);
	$sTable = 'tf_links';
	$sql = $db->GetInsertSql($sTable, $rec);
	$db->Execute($sql);
	showError($db,$sql);
}

//**************************************************************************
// alterLink()
// This function updates the database and alters the selected links values
function alterLink($lid,$newLink,$newSite) {
	global $cfg, $db;
	$sql = "UPDATE tf_links SET url='".$newLink."',`sitename`='".$newSite."' WHERE `lid` = ".$lid." LIMIT 1";
	$db->Execute($sql);
	showError($db,$sql);
}

//*********************************************************
function getLink($lid) {
	global $cfg, $db;
	$rtnValue = "";
	$sql = "SELECT url FROM tf_links WHERE lid=".$lid;
	$rtnValue = $db->GetOne($sql);
	return $rtnValue;
}

// ***************************************************************************
// Delete Link
function deleteOldLink($lid) {
	global $db;
	// Link Mod
	//$sql = "delete from tf_links where lid=".$lid;
	// Get Current sort order index of link with this link id:
	$idx=getLinkSortOrder($lid);
	// Fetch all link ids and their sort orders where the sort order is greater
	// than the one we're removing - we need to shuffle each sort order down
	// one:
	$sql="SELECT sort_order, lid FROM tf_links ";
	$sql.="WHERE sort_order > $idx ORDER BY sort_order ASC";
	$result=$db->Execute($sql);
	showError($db,$sql);
	$arLinks=$result->GetAssoc();
	// Decrement the sort order of each link:
	foreach($arLinks as $sid=>$this_lid){
		$sql="UPDATE tf_links SET sort_order=sort_order-1 WHERE lid=$this_lid";
		$db->Execute($sql);
		showError($db,$sql);
	}
	// Finally delete the link:
	$sql = "DELETE FROM tf_links WHERE lid=".$lid;
	// Link Mod
	$result = $db->Execute($sql);
	showError($db,$sql);
}

/**
 * Enter description here...
 *
 * @param unknown_type $lid
 * @return unknown
 */
function getLinkSortOrder($lid) {
    global $db;
    // Get Current sort order index of link with this link id:
    $sql="SELECT sort_order FROM tf_links WHERE lid=$lid";
    $rtnValue=$db->GetOne($sql);
    showError($db,$sql);
    return $rtnValue;
}

/**
 * Enter description here...
 *
 * @param unknown_type $lid
 * @return unknown
 */
function getSite($lid) {
    global $cfg, $db;
    $rtnValue = "";
    $sql = "SELECT sitename FROM tf_links WHERE lid=".$lid;
    $rtnValue = $db->GetOne($sql);
    return $rtnValue;
}

// ***************************************************************************
// addNewRSS - Add New RSS Link
function addNewRSS($newRSS) {
	global $db;
	$rec = array('url'=>$newRSS);
	$sTable = 'tf_rss';
	$sql = $db->GetInsertSql($sTable, $rec);
	$db->Execute($sql);
	showError($db,$sql);
}

// ***************************************************************************
// Delete RSS
function deleteOldRSS($rid) {
	global $db;
	$sql = "delete from tf_rss where rid=".$rid;
	$result = $db->Execute($sql);
	showError($db,$sql);
}

//*********************************************************
function getRSS($rid) {
	global $cfg, $db;
	$rtnValue = "";
	$sql = "SELECT url FROM tf_rss WHERE rid=".$rid;
	$rtnValue = $db->GetOne($sql);
	return $rtnValue;
}

// ***************************************************************************
// Delete User
function DeleteThisUser($user_id) {
	global $db;
	$sql = "SELECT uid FROM tf_users WHERE user_id = ".$db->qstr($user_id);
	$uid = $db->GetOne( $sql );
	showError($db,$sql);
	// delete any cookies this user may have had
	//$sql = "DELETE tf_cookies FROM tf_cookies, tf_users WHERE (tf_users.uid = tf_cookies.uid) AND tf_users.user_id=".$db->qstr($user_id);
	$sql = "DELETE FROM tf_cookies WHERE uid=".$uid;
	$result = $db->Execute($sql);
	showError($db,$sql);
	// Now cleanup any message this person may have had
	$sql = "DELETE FROM tf_messages WHERE to_user=".$db->qstr($user_id);
	$result = $db->Execute($sql);
	showError($db,$sql);
	// now delete the user from the table
	$sql = "DELETE FROM tf_users WHERE user_id=".$db->qstr($user_id);
	$result = $db->Execute($sql);
	showError($db,$sql);
}

// ***************************************************************************
// Update User -- used by admin
function updateThisUser($user_id, $org_user_id, $pass1, $userType, $hideOffline) {
	global $db;
	if ($hideOffline == "")
		$hideOffline = 0;
	$sql = 'select * from tf_users where user_id = '.$db->qstr($org_user_id);
	$rs = $db->Execute($sql);
	showError($db,$sql);
	$rec = array();
	$rec['user_id'] = $user_id;
	$rec['user_level'] = $userType;
	$rec['hide_offline'] = $hideOffline;
	if ($pass1 != "")
		$rec['password'] = md5($pass1);
	$sql = $db->GetUpdateSQL($rs, $rec);
	if ($sql != "") {
		$result = $db->Execute($sql);
		showError($db,$sql);
	}
	// if the original user id and the new id do not match, we need to update messages and log
	if ($user_id != $org_user_id) {
		$sql = "UPDATE tf_messages SET to_user=".$db->qstr($user_id)." WHERE to_user=".$db->qstr($org_user_id);
		$result = $db->Execute($sql);
		showError($db,$sql);
		$sql = "UPDATE tf_messages SET from_user=".$db->qstr($user_id)." WHERE from_user=".$db->qstr($org_user_id);
		$result = $db->Execute($sql);
		showError($db,$sql);
		$sql = "UPDATE tf_log SET user_id=".$db->qstr($user_id)." WHERE user_id=".$db->qstr($org_user_id);
		$result = $db->Execute($sql);
		showError($db,$sql);
	}
}

// ***************************************************************************
// changeUserLevel Changes the Users Level
function changeUserLevel($user_id, $level) {
	global $db;
	$sql='select * from tf_users where user_id = '.$db->qstr($user_id);
	$rs = $db->Execute($sql);
	showError($db,$sql);
	$rec = array('user_level'=>$level);
	$sql = $db->GetUpdateSQL($rs, $rec);
	$result = $db->Execute($sql);
	showError($db,$sql);
}

?>