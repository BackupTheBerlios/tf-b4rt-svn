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

//*********************************************************
// avddelete()
function avddelete($file) {
	@chmod($file,0777);
	if (@is_dir($file)) {
		$handle = @opendir($file);
		while($filename = readdir($handle)) {
			if ($filename != "." && $filename != "..")
				avddelete($file."/".$filename);
		}
		closedir($handle);
		@rmdir($file);
	} else {
		@unlink($file);
	}
}

//*********************************************************
// SaveMessage
function SaveMessage($to_user, $from_user, $message, $to_all=0, $force_read=0) {
	global $_SERVER, $cfg, $db;
	$message = str_replace(array("'"), "", $message);
	$create_time = time();
	$sTable = 'tf_messages';
	if($to_all == 1) {
		$message .= "\n\n__________________________________\n*** "._MESSAGETOALL." ***";
		$sql = 'select user_id from tf_users';
		$result = $db->Execute($sql);
		showError($db,$sql);
		while($row = $result->FetchRow())
		{
			$rec = array(
						'to_user' => $row['user_id'],
						'from_user' => $from_user,
						'message' => $message,
						'IsNew' => 1,
						'ip' => $cfg['ip'],
						'time' => $create_time,
						'force_read' => $force_read
						);

			$sql = $db->GetInsertSql($sTable, $rec);
			$result2 = $db->Execute($sql);
			showError($db,$sql);
		}
	} else {
		// Only Send to one Person
		$rec = array(
					'to_user' => $to_user,
					'from_user' => $from_user,
					'message' => $message,
					'IsNew' => 1,
					'ip' => $cfg['ip'],
					'time' => $create_time,
					'force_read' => $force_read
					);
		$sql = $db->GetInsertSql($sTable, $rec);
		$result = $db->Execute($sql);
		showError($db,$sql);
	}
}

//*********************************************************
function addNewUser($newUser, $pass1, $userType) {
	global $cfg, $db;
	$create_time = time();
	$record = array(
					'user_id'=>strtolower($newUser),
					'password'=>md5($pass1),
					'hits'=>0,
					'last_visit'=>$create_time,
					'time_created'=>$create_time,
					'user_level'=>$userType,
					'hide_offline'=>"0",
					'theme'=>$cfg["default_theme"],
					'language_file'=>$cfg["default_language"],
					'state'=>1
					);
	$sTable = 'tf_users';
	$sql = $db->GetInsertSql($sTable, $record);
	$result = $db->Execute($sql);
	showError($db,$sql);
}

//*********************************************************
function PruneDB() {
	global $cfg, $db;
	// Prune LOG
	$testTime = time()-($cfg['days_to_keep'] * 86400); // 86400 is one day in seconds
	$sql = "delete from tf_log where time < " . $db->qstr($testTime);
	$result = $db->Execute($sql);
	showError($db,$sql);
	unset($result);
	$testTime = time()-($cfg['minutes_to_keep'] * 60);
	$sql = "delete from tf_log where time < " . $db->qstr($testTime). " and action=".$db->qstr($cfg["constants"]["hit"]);
	$result = $db->Execute($sql);
	showError($db,$sql);
	unset($result);
}

//*********************************************************
function IsOnline($user) {
	global $cfg, $db;
	$online = false;
	$sql = "SELECT count(*) FROM tf_log WHERE user_id=" . $db->qstr($user)." AND action=".$db->qstr($cfg["constants"]["hit"]);
	$number_hits = $db->GetOne($sql);
	showError($db,$sql);
	if ($number_hits > 0)
		$online = true;
	return $online;
}

//*********************************************************
function IsUser($user) {
	global $cfg, $db;
	$isUser = false;
	$sql = "SELECT count(*) FROM tf_users WHERE user_id=".$db->qstr($user);
	$number_users = $db->GetOne($sql);
	if ($number_users > 0)
		$isUser = true;
	return $isUser;
}

//*********************************************************
function getOwner($file) {
	global $cfg, $db;
	$rtnValue = "n/a";
	// Check log to see what user has a history with this file
	$sql = "SELECT user_id FROM tf_log WHERE file=".$db->qstr($file)." AND (action=".$db->qstr($cfg["constants"]["file_upload"])." OR action=".$db->qstr($cfg["constants"]["url_upload"])." OR action=".$db->qstr($cfg["constants"]["reset_owner"]).") ORDER  BY time DESC";
	$user_id = $db->GetOne($sql);
	if($user_id != "") {
		$rtnValue = $user_id;
	} else {
		// try and get the owner from the stat file
		$rtnValue = resetOwner($file);
	}
	return $rtnValue;
}

//*********************************************************
function resetOwner($file) {
	global $cfg, $db;
	include_once("AliasFile.php");
	// log entry has expired so we must renew it
	$rtnValue = "";
	$alias = getAliasName($file).".stat";
	if(file_exists($cfg["torrent_file_path"].$alias)) {
		$af = AliasFile::getAliasFileInstance($cfg["torrent_file_path"].$alias, $cfg["user"], $cfg);
		if (IsUser($af->transferowner)) {
			// We have an owner!
			$rtnValue = $af->transferowner;
		} else {
			// no owner found, so the super admin will now own it
			$rtnValue = GetSuperAdmin();
		}
		$host_resolved = gethostbyaddr($cfg['ip']);
		$create_time = time();
		$rec = array(
						'user_id' => $rtnValue,
						'file' => $file,
						'action' => $cfg["constants"]["reset_owner"],
						'ip' => $cfg['ip'],
						'ip_resolved' => $host_resolved,
						'user_agent' => $_SERVER['HTTP_USER_AGENT'],
						'time' => $create_time
					);
		$sTable = 'tf_log';
		$sql = $db->GetInsertSql($sTable, $rec);
		// add record to the log
		$result = $db->Execute($sql);
		showError($db,$sql);
	}
	return $rtnValue;
}

//*********************************************************
function getCookie($cid) {
	global $cfg, $db;
	$rtnValue = "";
	$sql = "SELECT host, data FROM tf_cookies WHERE cid=".$cid;
	$rtnValue = $db->GetAll($sql);
	return $rtnValue[0];
}

// ***************************************************************************
// Delete Cookie Host Information
function deleteCookieInfo($cid) {
	global $db;
	$sql = "delete from tf_cookies where cid=".$cid;
	$result = $db->Execute($sql);
	showError($db,$sql);
}

// ***************************************************************************
// addCookieInfo - Add New Cookie Host Information
function addCookieInfo( $newCookie ) {
	global $db, $cfg;
	// Get uid of user
	$sql = "SELECT uid FROM tf_users WHERE user_id = '" . $cfg["user"] . "'";
	$uid = $db->GetOne( $sql );
	$sql = "INSERT INTO tf_cookies ( cid, uid, host, data ) VALUES ( '', '" . $uid . "', '" . $newCookie["host"] . "', '" . $newCookie["data"] . "' )";
	$db->Execute( $sql );
	showError( $db, $sql );
}

// ***************************************************************************
// modCookieInfo - Modify Cookie Host Information
function modCookieInfo($cid, $newCookie) {
	global $db;
	$sql = "UPDATE tf_cookies SET host='" . $newCookie["host"] . "', data='" . $newCookie["data"] . "' WHERE cid='" . $cid . "'";
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

//*********************************************************
function getRSS($rid) {
	global $cfg, $db;
	$rtnValue = "";
	$sql = "SELECT url FROM tf_rss WHERE rid=".$rid;
	$rtnValue = $db->GetOne($sql);
	return $rtnValue;
}

//*********************************************************
function IsOwner($user, $owner) {
	$rtnValue = false;
	if (strtolower($user) == strtolower($owner))
		$rtnValue = true;
	return $rtnValue;
}

//*********************************************************
function GetActivityCount($user="") {
	global $cfg, $db;
	$count = 0;
	$for_user = "";
	if ($user != "")
		$for_user = "user_id=".$db->qstr($user)." AND ";
	$sql = "SELECT count(*) FROM tf_log WHERE ".$for_user."(action=".$db->qstr($cfg["constants"]["file_upload"])." OR action=".$db->qstr($cfg["constants"]["url_upload"]).")";
	$count = $db->GetOne($sql);
	return $count;
}

//*********************************************************
function GetSpeedValue($inValue) {
	$rtnValue = 0;
	$arTemp = split(" ", trim($inValue));
	if (is_numeric($arTemp[0]))
		$rtnValue = $arTemp[0];
	return $rtnValue;
}

// ***************************************************************************
// Is User Admin
// user is Admin if level is 1 or higher
function IsAdmin($user="") {
	global $cfg, $db;
	$isAdmin = false;
	if($user == "")
		$user = $cfg["user"];
	$sql = "SELECT user_level FROM tf_users WHERE user_id=".$db->qstr($user);
	$user_level = $db->GetOne($sql);
	if ($user_level >= 1)
		$isAdmin = true;
	return $isAdmin;
}

// ***************************************************************************
// Is User SUPER Admin
// user is Super Admin if level is higher than 1
function IsSuperAdmin($user="") {
	global $cfg, $db;
	$isAdmin = false;
	if($user == "")
		$user = $cfg["user"];
	$sql = "SELECT user_level FROM tf_users WHERE user_id=".$db->qstr($user);
	$user_level = $db->GetOne($sql);
	if ($user_level > 1)
		$isAdmin = true;
	return $isAdmin;
}

// ***************************************************************************
// Returns true if user has message from admin with force_read
function IsForceReadMsg() {
	global $cfg, $db;
	$rtnValue = false;
	$sql = "SELECT count(*) FROM tf_messages WHERE to_user=".$db->qstr($cfg["user"])." AND force_read=1";
	$count = $db->GetOne($sql);
	showError($db,$sql);
	if ($count >= 1)
		$rtnValue = true;
	return $rtnValue;
}

// ***************************************************************************
// Get Message data in an array
function GetMessage($mid) {
	global $cfg, $db;
	$sql = "select from_user, message, ip, time, isnew, force_read from tf_messages where mid=".$mid." and to_user=".$db->qstr($cfg['user']);
	$rtnValue = $db->GetRow($sql);
	showError($db,$sql);
	return $rtnValue;
}

// ***************************************************************************
// Get Themes data in an array
function GetThemes() {
	$arThemes = array();
	$dir = "themes/";
	$handle = opendir($dir);
	while($entry = readdir($handle)) {
		if (is_dir($dir.$entry) && ($entry != "." && $entry != ".." && $entry != ".svn" && $entry != "CVS" && $entry != "old_style_themes"))
			array_push($arThemes, $entry);
	}
	closedir($handle);
	sort($arThemes);
	return $arThemes;
}
// ***************************************************************************
// Get Themes data in an array
function Get_old_Themes() {
	$arThemes = array();
	$dir = "themes/old_style_themes/";
	$handle = opendir($dir);
	while($entry = readdir($handle)) {
		if (is_dir($dir.$entry) && ($entry != "." && $entry != ".." && $entry != ".svn" && $entry != "CVS" && $entry != "css" && $entry != "tmpl" && $entry != "scripts"))
			array_push($arThemes, $entry);
	}
	closedir($handle);
	sort($arThemes);
	return $arThemes;
}

// ***************************************************************************
// Get Languages in an array
function GetLanguages() {
	$arLanguages = array();
	$dir = "inc/language/";
	$handle = opendir($dir);
	while($entry = readdir($handle)) {
		if (is_file($dir.$entry) && (strcmp(strtolower(substr($entry, strlen($entry)-4, 4)), ".php") == 0))
			array_push($arLanguages, $entry);
	}
	closedir($handle);
	sort($arLanguages);
	return $arLanguages;
}

// ***************************************************************************
// Get Language name from file name
function GetLanguageFromFile($inFile) {
	$rtnValue = "";
	$rtnValue = str_replace("lang-", "", $inFile);
	$rtnValue = str_replace(".php", "", $rtnValue);
	return $rtnValue;
}

// ***************************************************************************
// Delete Message
function DeleteMessage($mid) {
	global $cfg, $db;
	$sql = "delete from tf_messages where mid=".$mid." and to_user=".$db->qstr($cfg['user']);
	$result = $db->Execute($sql);
	showError($db,$sql);
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

// ***************************************************************************
// Delete RSS
function deleteOldRSS($rid) {
	global $db;
	$sql = "delete from tf_rss where rid=".$rid;
	$result = $db->Execute($sql);
	showError($db,$sql);
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

// ***************************************************************************
// Mark Message as Read
function MarkMessageRead($mid) {
	global $cfg, $db;
	$sql = 'select * from tf_messages where mid = '.$mid;
	$rs = $db->Execute($sql);
	showError($db,$sql);
	$rec = array('IsNew'=>0,
			 'force_read'=>0);
	$sql = $db->GetUpdateSQL($rs, $rec);
	$db->Execute($sql);
	showError($db,$sql);
}

// Link Mod
//**************************************************************************
// alterLink()
// This function updates the database and alters the selected links values
function alterLink($lid,$newLink,$newSite) {
	global $cfg, $db;
	$sql = "UPDATE tf_links SET url='".$newLink."',`sitename`='".$newSite."' WHERE `lid` = ".$lid." LIMIT 1";
	$db->Execute($sql);
	showError($db,$sql);
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
// Link Mod

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
// UpdateUserProfile
function UpdateUserProfile($user_id, $pass1, $hideOffline, $theme, $language) {
	global $cfg, $db;
	if (empty($hideOffline) || $hideOffline == "" || !isset($hideOffline))
		$hideOffline = "0";
	// update values
	$rec = array();
	if ($pass1 != "") {
		$rec['password'] = md5($pass1);
		AuditAction($cfg["constants"]["update"], _PASSWORD);
	}
	$sql = 'select * from tf_users where user_id = '.$db->qstr($user_id);
	$rs = $db->Execute($sql);
	showError($db,$sql);
	$rec['hide_offline'] = $hideOffline;
	$rec['theme'] = $theme;
	$rec['language_file'] = $language;
	$sql = $db->GetUpdateSQL($rs, $rec);
	$result = $db->Execute($sql);
	showError($db,$sql);
}

// ***************************************************************************
// Get Users in an array
function GetUsers() {
	global $cfg, $db;
	$user_array = array();
	$sql = "select user_id from tf_users order by user_id";
	$user_array = $db->GetCol($sql);
	showError($db,$sql);
	return $user_array;
}

// ***************************************************************************
// Get Super Admin User ID as a String
function GetSuperAdmin() {
	global $cfg, $db;
	$rtnValue = "";
	$sql = "select user_id from tf_users WHERE user_level=2";
	$rtnValue = $db->GetOne($sql);
	showError($db,$sql);
	return $rtnValue;
}

// ***************************************************************************
// Get Links in an array
function GetLinks() {
	global $cfg, $db;
	$link_array = array();
	// Link Mod
	//$link_array = $db->GetAssoc("SELECT lid, url FROM tf_links ORDER BY lid");
	$link_array = $db->GetAssoc("SELECT lid, url, sitename, sort_order FROM tf_links ORDER BY sort_order");
	// Link Mod
	return $link_array;
}

// ***************************************************************************
// Get RSS Links in an array
function GetRSSLinks() {
	global $cfg, $db;
	$link_array = array();
	$sql = "SELECT rid, url FROM tf_rss ORDER BY rid";
	$link_array = $db->GetAssoc($sql);
	showError($db,$sql);
	return $link_array;
}

// ***************************************************************************
// Build Search Engine Links
function buildSearchEngineLinks($selectedEngine = 'TorrentSpy') {
	global $cfg;
	$settingsNeedsSaving = false;
	$settings['searchEngineLinks'] = Array();
	$output = '';
	if( (!array_key_exists('searchEngineLinks', $cfg)) || (!is_array($cfg['searchEngineLinks'])))
		saveSettings($settings);
	$handle = opendir("./inc/searchEngines");
	while($entry = readdir($handle))
		$entrys[] = $entry;
	natcasesort($entrys);
	foreach($entrys as $entry) {
		if ($entry != "." && $entry != ".." && substr($entry, 0, 1) != ".")
			if(strpos($entry,"Engine.php")) {
				$tmpEngine = str_replace("Engine",'',substr($entry,0,strpos($entry,".")));
				if(array_key_exists($tmpEngine,$cfg['searchEngineLinks'])) {
					$hreflink = $cfg['searchEngineLinks'][$tmpEngine];
					$settings['searchEngineLinks'][$tmpEngine] = $hreflink;
				} else {
					$hreflink = getEngineLink($tmpEngine);
					$settings['searchEngineLinks'][$tmpEngine] = $hreflink;
					$settingsNeedsSaving = true;
				}
				if (strlen($hreflink) > 0) {
					$output .=	"<a href=\"http://".$hreflink."/\" target=\"_blank\">";
					if ($selectedEngine == $tmpEngine)
						$output .= "<b>".$hreflink."</b>";
					else
						$output .= $hreflink;
					$output .= "</a><br>\n";
				}
			}
	}
	if ( count($settings['searchEngineLinks'],COUNT_RECURSIVE) <> count($cfg['searchEngineLinks'],COUNT_RECURSIVE))
		$settingsNeedsSaving = true;
	if ($settingsNeedsSaving) {
		natcasesort($settings['searchEngineLinks']);
		saveSettings($settings);
	}
	return $output;
}

// Removes HTML from Messages
function check_html ($str, $strip="") {
	/* The core of this code has been lifted from phpslash */
	/* which is licenced under the GPL. */
	if ($strip == "nohtml")
		$AllowableHTML = array('');
	$str = stripslashes($str);
	$str = eregi_replace("<[[:space:]]*([^>]*)[[:space:]]*>",'<\\1>', $str);
	// Delete all spaces from html tags .
	$str = eregi_replace("<a[^>]*href[[:space:]]*=[[:space:]]*\"?[[:space:]]*([^\" >]*)[[:space:]]*\"?[^>]*>",'<a href="\\1">', $str);
	// Delete all attribs from Anchor, except an href, double quoted.
	$str = eregi_replace("<[[:space:]]* img[[:space:]]*([^>]*)[[:space:]]*>", '', $str);
	// Delete all img tags
	$str = eregi_replace("<a[^>]*href[[:space:]]*=[[:space:]]*\"?javascript[[:punct:]]*\"?[^>]*>", '', $str);
	// Delete javascript code from a href tags -- Zhen-Xjell @ http://nukecops.com
	$tmp = "";
	while (ereg("<(/?[[:alpha:]]*)[[:space:]]*([^>]*)>",$str,$reg)) {
		$i = strpos($str,$reg[0]);
		$l = strlen($reg[0]);
		if ($reg[1][0] == "/")
			$tag = strtolower(substr($reg[1],1));
		else
			$tag = strtolower($reg[1]);
		if ($a = $AllowableHTML[$tag]) {
			if ($reg[1][0] == "/") {
				$tag = "</$tag>";
			} elseif (($a == 1) || ($reg[2] == "")) {
				$tag = "<$tag>";
			} else {
			  # Place here the double quote fix function.
			  $attrb_list=delQuotes($reg[2]);
			  // A VER
			  $attrb_list = ereg_replace("&","&amp;",$attrb_list);
			  $tag = "<$tag" . $attrb_list . ">";
			} # Attribs in tag allowed
		} else {
			$tag = "";
		}
		$tmp .= substr($str,0,$i) . $tag;
		$str = substr($str,$i+$l);
	}
	$str = $tmp . $str;
	// parse for strings starting with http:// and subst em with hyperlinks.
	if ($strip != "nohtml") {
		global $cfg;
		if ($cfg["enable_dereferrer"] != "0")
			$str = preg_replace('/(http:\/\/)(.*)([[:space:]]*)/i', '<a href="'. _URL_DEREFERRER .'${1}${2}" target="_blank">${1}${2}</a>${3}', $str);
		else
			$str = preg_replace('/(http:\/\/)(.*)([[:space:]]*)/i', '<a href="${1}${2}" target="_blank">${1}${2}</a>${3}', $str);
	}
	return $str;
}

// ***************************************************************************
// ***************************************************************************
// Checks for the location of the torrents
// If it does not exist, then it creates it.
function checkTorrentPath() {
	global $cfg;
	// is there a stat and torrent dir?
	if (!@is_dir($cfg["torrent_file_path"]) && is_writable($cfg["path"])) {
		//Then create it
		@mkdir($cfg["torrent_file_path"], 0777);
	}
}

// ***************************************************************************
// ***************************************************************************
// Returns the drive space used as a percentage i.e 85 or 95
function getDriveSpace($drive) {
	$percent = 0;
	if (is_dir($drive)) {
		$dt = disk_total_space($drive);
		$df = disk_free_space($drive);
		$percent = round((($dt - $df)/$dt) * 100);
	}
	return $percent;
}

// ***************************************************************************
// ***************************************************************************
// get the Drive Space Graphical Bar
function getDriveSpaceBar($drivespace) {
	global $cfg;
	switch ($cfg['drivespacebar']) {
		case "tf":
			$freeSpace = "";
			if ($drivespace > 20)
				$freeSpace = " (".formatFreeSpace($cfg["free_space"])." Free)";
			$driveSpaceBar = '<table width="100%" border="0" cellpadding="0" cellspacing="0">';
			$driveSpaceBar .= '<tr nowrap>';
				$driveSpaceBar .= '<td width="2%"><div class="tiny">'._STORAGE.':</div></td>';
				$driveSpaceBar .= '<td width="80%">';
				   $driveSpaceBar .= '<table width="100%" border="0" cellpadding="0" cellspacing="0">';
					$driveSpaceBar .= '<tr>';
						$driveSpaceBar .= '<td background="themes/'.$cfg["theme"].'/images/proglass.gif" width="'.$drivespace.'%"><div class="tinypercent" align="center">'.$drivespace.'%'.$freeSpace.'</div></td>';
						$driveSpaceBar .= '<td background="themes/'.$cfg["theme"].'/images/noglass.gif" width="'.(100 - $drivespace).'%"><img src="images/blank.gif" width="1" height="3" border="0"></td>';
					$driveSpaceBar .= '</tr>';
					$driveSpaceBar .= '</table>';
				$driveSpaceBar .= '</td>';
			$driveSpaceBar .= '</tr>';
			$driveSpaceBar .= '</table>';
			break;
		case "xfer":
			$freeSpace = ($drivespace) ? ' ('.formatFreeSpace($cfg['free_space']).') Free' : '';
			$drivespace = 100 - $drivespace;
			$bgcolor = '#';
			$bgcolor .= str_pad(dechex(256-256*($drivespace/100)),2,0,STR_PAD_LEFT);
			$bgcolor .= str_pad(dechex(256*($drivespace/100)),2,0,STR_PAD_LEFT);
			$bgcolor .= '00';
			$driveSpaceBar = '<table width="100%" border="0" cellpadding="0" cellspacing="0">';
			  $driveSpaceBar .= '<tr nowrap>';
				$driveSpaceBar .= '<td width="2%"><div class="tiny">'._STORAGE.':</div></td>';
				$driveSpaceBar .= '<td width="92%">';
				  $driveSpaceBar .= '<table width="100%" border="0" cellpadding="0" cellspacing="0"><tr>';
					$driveSpaceBar .= '<td bgcolor="'.$bgcolor.'" width="'.$drivespace.'%">';
					if ($drivespace >= 50) {
						$driveSpaceBar .= '<div class="tinypercent" align="center"';
						if ($drivespace == 100)
							$driveSpaceBar .= ' style="background:#ffffff;">';
						else
							$driveSpaceBar .= '>';
						$driveSpaceBar .= $drivespace.'%'.$freeSpace;
						$driveSpaceBar .= '</div>';
					}
					$driveSpaceBar .= '</td>';
					$driveSpaceBar .= '<td bgcolor="#000000" width="'.(100-$drivespace).'%">';
					if ($drivespace < 50) {
						$driveSpaceBar .= '<div class="tinypercent" align="center" style="color:'.$bgcolor;
						if ($drivespace == 0)
							$driveSpaceBar .= '; background:#ffffff;">';
						else
							$driveSpaceBar .= ';">';
						$driveSpaceBar .= $drivespace.'%'.$freeSpace;
						$driveSpaceBar .= '</div>';
					}
					$driveSpaceBar .= '</td>';
				  $driveSpaceBar .= '</tr></table>';
				$driveSpaceBar .= '</td>';
			  $driveSpaceBar .= '</tr>';
			$driveSpaceBar .= '</table>';
		break;
	}
	return $driveSpaceBar;
}

// ***************************************************************************
// ***************************************************************************
// Convert free space to GB or MB depending on size
function formatFreeSpace($freeSpace) {
	$rtnValue = "";
	if ($freeSpace > 1024)
		$rtnValue = number_format($freeSpace/1024, 2)." GB";
	else
		$rtnValue = number_format($freeSpace, 2)." MB";
	return $rtnValue;
}

//**************************************************************************
// getFileFilter()
// Returns a string used as a file filter.
// Takes in an array of file types.
function getFileFilter($inArray) {
	$filter = "(\.".strtolower($inArray[0]).")|"; // used to hold the file type filter
	$filter .= "(\.".strtoupper($inArray[0]).")";
	// Build the file filter
	for($inx = 1; $inx < sizeof($inArray); $inx++) {
		$filter .= "|(\.".strtolower($inArray[$inx]).")";
		$filter .= "|(\.".strtoupper($inArray[$inx]).")";
	}
	$filter .= "$";
	return $filter;
}


//**************************************************************************
// getAliasName()
// Create Alias name for Text file and Screen Alias
function getAliasName($inName) {
	$alias = preg_replace("/[^0-9a-z.]+/i",'_', $inName);
	$alias = str_replace(".torrent", "", $alias);
	$alias = str_replace(".wget", "", $alias);
	return $alias;
}

//**************************************************************************
// cleanFileName()
// Remove bad characters that cause problems
function cleanFileName($inName) {
	$replaceItems = array("?", "&", "'", "\"", "+", "@");
	$cleanName = str_replace($replaceItems, "", $inName);
	$cleanName = ltrim($cleanName, "-");
	$cleanName = preg_replace("/[^0-9a-z.]+/i",'_', $cleanName);
	return $cleanName;
}

//**************************************************************************
// cleanURL()
// split on the "*" coming from Varchar URL
function cleanURL($url) {
	$rtnValue = $url;
	$arURL = explode("*", $url);
	if (sizeof($arURL) > 1)
		$rtnValue = $arURL[1];
	return $rtnValue;
}

// -------------------------------------------------------------------
// FetchTorrent() method to get data from URL
// Has support for specific sites
// -------------------------------------------------------------------
function FetchTorrent($url) {
	global $cfg, $db;
	ini_set("allow_url_fopen", "1");
	ini_set("user_agent", $_SERVER["HTTP_USER_AGENT"]);
	$domain	 = parse_url( $url );
	if( strtolower( substr( $domain["path"], -8 ) ) != ".torrent" ) {
		// Check know domain types
		if( strpos( strtolower ( $domain["host"] ), "mininova" ) !== false ) {
			// Sample (http://www.mininova.org/rss.xml):
			// http://www.mininova.org/tor/2254847
			// <a href="/get/2281554">FreeLinux.ISO.iso.torrent</a>
			// If received a /tor/ get the required information
			if( strpos( $url, "/tor/" ) !== false ) {
				// Get the contents of the /tor/ to find the real torrent name
				$html = FetchHTML( $url );
				// Check for the tag used on mininova.org
				if( preg_match( "/<a href=\"\/get\/[0-9].[^\"]+\">(.[^<]+)<\/a>/i", $html, $html_preg_match ) ) {
					// This is the real torrent filename
					$cfg["save_torrent_name"] = $html_preg_match[1];
				}
				// Change to GET torrent url
				$url = str_replace( "/tor/", "/get/", $url );
			}
			// Now fetch the torrent file
			$html = FetchHTML( $url );
			// This usually gets triggered if the original URL was /get/ instead of /tor/
			if( strlen( $cfg["save_torrent_name"] ) == 0 ) {
				// Get the name of the torrent, and make it the filename
				if( preg_match( "/name([0-9][^:]):(.[^:]+)/i", $html, $html_preg_match ) ) {
					$filelength = $html_preg_match[1];
					$filename = $html_preg_match[2];
					$cfg["save_torrent_name"] = substr( $filename, 0, $filelength ) . ".torrent";
				}
			}
			// Make sure we have a torrent file
			if( strpos( $html, "d8:" ) === false )	{
				// We don't have a Torrent File... it is something else
				AuditAction( $cfg["constants"]["error"], "BAD TORRENT for: " . $url . "\n" . $html );
				$html = "";
			}
			return $html;
		} elseif( strpos( strtolower ( $domain["host"] ), "isohunt" ) !== false ) {
			// Sample (http://isohunt.com/js/rss.php):
			// http://isohunt.com/download.php?mode=bt&id=8837938
			// http://isohunt.com/btDetails.php?ihq=&id=8464972
			$referer = "http://" . $domain["host"] . "/btDetails.php?id=";
			// If the url points to the details page, change it to the download url
			if( strpos( strtolower( $url ), "/btdetails.php?" ) !== false ) {
				$url = str_replace( "/btDetails.php?", "/download.php?", $url ) . "&mode=bt"; // Need to make it grab the torrent
			}
			// Grab contents of details page
			$html = FetchHTML( $url, $referer );
			// Get the name of the torrent, and make it the filename
			if( preg_match( "/name([0-9][^:]):(.[^:]+)/i", $html, $html_preg_match ) ) {
				$filelength = $html_preg_match[1];
				$filename = $html_preg_match[2];
				$cfg["save_torrent_name"] = substr( $filename, 0, $filelength ) . ".torrent";
			}
			// Make sure we have a torrent file
			if( strpos( $html, "d8:" ) === false ) {
				// We don't have a Torrent File... it is something else
				AuditAction( $cfg["constants"]["error"], "BAD TORRENT for: " . $url . "\n" . $html );
				$html = "";
			}
			return $html;
		} elseif( strpos( strtolower( $url ), "details.php?" ) !== false ) {
			// Sample (http://www.bitmetv.org/rss.php?passkey=123456):
			// http://www.bitmetv.org/details.php?id=18435&hit=1
			$referer = "http://" . $domain["host"] . "/details.php?id=";
			$html = FetchHTML( $url, $referer );
			// Sample (http://www.bitmetv.org/details.php?id=18435)
			// download.php/18435/SpiderMan%20Season%204.torrent
			if( preg_match( "/(download.php.[^\"]+)/i", $html, $html_preg_match ) ) {
				$torrent = str_replace( " ", "%20", substr( $html_preg_match[0], 0, -1 ) );
				$url2 = "http://" . $domain["host"] . "/" . $torrent;
				$html2 = FetchHTML( $url2 );
				// Make sure we have a torrent file
				if (strpos($html2, "d8:") === false) {
					// We don't have a Torrent File... it is something else
					AuditAction($cfg["constants"]["error"], "BAD TORRENT for: ".$url."\n".$html2);
					$html2 = "";
				}
				return $html2;
			} else {
				return "";
			}
		} elseif( strpos( strtolower( $url ), "download.asp?" ) !== false ) {
			// Sample (TF's TorrenySpy Search):
			// http://www.torrentspy.com/download.asp?id=519793
			$referer = "http://" . $domain["host"] . "/download.asp?id=";
			$html = FetchHTML( $url, $referer );
			// Get the name of the torrent, and make it the filename
			if( preg_match( "/name([0-9][^:]):(.[^:]+)/i", $html, $html_preg_match ) ) {
				$filelength = $html_preg_match[1];
				$filename = $html_preg_match[2];
				$cfg["save_torrent_name"] = substr( $filename, 0, $filelength ) . ".torrent";
			}
			if( !empty( $html ) ) {
				// Make sure we have a torrent file
				if( strpos( $html, "d8:" ) === false ) {
					// We don't have a Torrent File... it is something else
					AuditAction( $cfg["constants"]["error"], "BAD TORRENT for: " . $url . "\n" . $html );
					$html = "";
				}
				return $html;
			} else {
				return "";
			}
		}
	}
	$html = FetchHTML( $url );
	// Make sure we have a torrent file
	if( strpos( $html, "d8:" ) === false ) {
		// We don't have a Torrent File... it is something else
		AuditAction( $cfg["constants"]["error"], "BAD TORRENT for: " . $url.  "\n" . $html );
		$html = "";
	} else {
		// Get the name of the torrent, and make it the filename
		if( preg_match( "/name([0-9][^:]):(.[^:]+)/i", $html, $html_preg_match ) )
		{
			$filelength = $html_preg_match[1];
			$filename = $html_preg_match[2];
			$cfg["save_torrent_name"] = substr( $filename, 0, $filelength ) . ".torrent";
		}
	}
	return $html;
}

// -------------------------------------------------------------------
// FetchHTML() method to get data from URL -- uses timeout and user agent
// -------------------------------------------------------------------
function FetchHTML( $url, $referer = "" ) {
	global $cfg, $db;
	ini_set("allow_url_fopen", "1");
	ini_set("user_agent", $_SERVER["HTTP_USER_AGENT"]);
	//$url = cleanURL( $url );
	$domain = parse_url( $url );
	$getcmd	 = $domain["path"];
	if(!array_key_exists("query", $domain))
		$domain["query"] = "";
	$getcmd .= ( !empty( $domain["query"] ) ) ? "?" . $domain["query"] : "";
	$cookie = "";
	$rtnValue = "";
	// If the url already doesn't contain a passkey, then check
	// to see if it has cookies set to the domain name.
	if( ( strpos( $domain["query"], "passkey=" ) ) === false ) {
		$sql = "SELECT c.data FROM tf_cookies AS c LEFT JOIN tf_users AS u ON ( u.uid = c.uid ) WHERE u.user_id = '" . $cfg["user"] . "' AND c.host = '" . $domain['host'] . "'";
		$cookie = $db->GetOne( $sql );
		showError( $db, $sql );
	}
	if( !array_key_exists("port", $domain) )
		$domain["port"] = 80;
	// Check to see if this site requires the use of cookies
	if( !empty( $cookie ) ) {
		$socket = @fsockopen( $domain["host"], $domain["port"], $errno, $errstr, 30 ); //connect to server
		if( !empty( $socket ) ) {
			// Write the outgoing header packet
			// Using required cookie information
			$packet	 = "GET " . $url . "\r\n";
			$packet .= ( !empty( $referer ) ) ? "Referer: " . $referer . "\r\n" : "";
			$packet .= "Accept: */*\r\n";
			$packet .= "Accept-Language: en-us\r\n";
			$packet .= "User-Agent: ".$_SERVER["HTTP_USER_AGENT"]."\r\n";
			$packet .= "Host: " . $_SERVER["SERVER_NAME"] . "\r\n";
			$packet .= "Connection: Close\r\n";
			$packet .= "Cookie: " . $cookie . "\r\n\r\n";
			// Send header packet information to server
			@fputs( $socket, $packet );
			// Initialize variable, make sure null until we add too it.
			$rtnValue = null;
			// If http 1.0 just take it all as 1 chunk (Much easier, but for old servers)
			while( !@feof( $socket ) )
				$rtnValue .= @fgets( $socket, 500000 );
			@fclose( $socket ); // Close our connection
		}
	} else {
		if( $fp = @fopen( $url, 'r' ) ) {
			$rtnValue = "";
			while( !@feof( $fp ) )
				$rtnValue .= @fgets( $fp, 4096 );
			@fclose( $fp );
		}
	}
	// If the HTML is still empty, then try CURL
	if (($rtnValue == "" && function_exists("curl_init")) || (strpos($rtnValue, "HTTP/1.1 302") > 0 && function_exists("curl_init"))) {
		// Give CURL a Try
		$ch = curl_init();
		if ($cookie != "")
			curl_setopt($ch, CURLOPT_COOKIE, $cookie);
		curl_setopt($ch, CURLOPT_PORT, $domain["port"]);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_VERBOSE, FALSE);
		curl_setopt($ch, CURLOPT_HEADER, TRUE);
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		$response = curl_exec($ch);
		curl_close($ch);
		$rtnValue = substr($response, strpos($response, "d8:"));
		$rtnValue = rtrim($rtnValue, "\r\n");
	}
	return $rtnValue;
}

//**************************************************************************
// getDownloadSize()
// Grab the full size of the download from the torrent metafile
function getDownloadSize($torrent) {
	$rtnValue = "";
	if (file_exists($torrent)) {
		include_once("BDecode.php");
		$fd = fopen($torrent, "rd");
		$alltorrent = fread($fd, filesize($torrent));
		$array = BDecode($alltorrent);
		fclose($fd);
		$rtnValue = $array["info"]["piece length"] * (strlen($array["info"]["pieces"]) / 20);
	}
	return $rtnValue;
}

//**************************************************************************
// formatBytesToKBMGGB()
// Returns a string in format of GB, MB, or kB depending on the size for display
function formatBytesToKBMGGB($inBytes) {
	$rsize = "";
	if ($inBytes > (1024 * 1024 * 1024)) {
		$rsize = round($inBytes / (1024 * 1024 * 1024), 2) . " GB";
	} elseif ($inBytes < 1024 * 1024) {
		$rsize = round($inBytes / 1024, 1) . " kB";
	} else {
		$rsize = round($inBytes / (1024 * 1024), 1) . " MB";
	}
	return $rsize;
}

//**************************************************************************
// HealthData
// Stores the image and title of for the health of a file.
class HealthData {
	var $image = "";
	var $title = "";
}

//**************************************************************************
// getStatusImage() Takes in an AliasFile object
// Returns a string "file name" of the status image icon
function getStatusImage($af) {
	$hd = new HealthData();
	$hd->image = "black.gif";
	$hd->title = "";
	if ($af->running == "1") {
		// torrent is running
		if ($af->seeds < 2)
			$hd->image = "yellow.gif";
		if ($af->seeds == 0)
			$hd->image = "red.gif";
		if ($af->seeds >= 2)
			$hd->image = "green.gif";
	}
	if ($af->percent_done >= 100) {
		if(trim($af->up_speed) != "" && $af->running == "1") {
			// is seeding
			$hd->image = "green.gif";
		} else {
			// the torrent is finished
			$hd->image = "black.gif";
		}
	}
	if ($hd->image != "black.gif")
		$hd->title = "S:".$af->seeds." P:".$af->peers." ";
	if ($af->running == "3") {
		// torrent is queued
		$hd->image = "black.gif";
	}
	return $hd;
}

//**************************************************************************
class ProcessInfo {
	var $pid = "";
	var $ppid = "";
	var $cmdline = "";
	function ProcessInfo($psLine) {
		$psLine = trim($psLine);
		if (strlen($psLine) > 12) {
			$this->pid = trim(substr($psLine, 0, 5));
			$this->ppid = trim(substr($psLine, 5, 6));
			$this->cmdline = trim(substr($psLine, 12));
		}
	}
}

//**************************************************************************
// file_size()
// Returns file size... overcomes PHP limit of 2.0GB
function file_size($file) {
	$size = @filesize($file);
	if ( $size == 0)
		$size = exec("ls -l \"".$file."\" | awk '{print $5}'");
	return $size;
}

//**************************************************************************
// getDirList()
// This method Builds the Torrent Section of the Index Page
function getDirList($dirName) {
	global $cfg, $db;
	include_once("AliasFile.php");
	$lastUser = "";
	$arUserTorrent = array();
	$arListTorrent = array();
	// sortOrder
	$sortOrder = getRequestVar("so");
	if ($sortOrder == "")
		$sortOrder = $cfg["index_page_sortorder"];
	// t-list
	$arList = getTransferArray($sortOrder);
	foreach($arList as $entry) {
		$output = "";
		$displayname = $entry;
		$show_run = true;
		$kill_id = "";
		$estTime = "&nbsp;";

		// alias / stat
		$alias = getAliasName($entry).".stat";
		if ((substr( strtolower($entry),-8 ) == ".torrent")) {
			// this is a torrent-client
			$isTorrent = true;
			$transferowner = getOwner($entry);
			$owner = IsOwner($cfg["user"], $transferowner);
			$settingsAry = loadTorrentSettings($entry);
			$af = AliasFile::getAliasFileInstance($dirName.$alias, $transferowner, $cfg, $settingsAry['btclient']);
		} else if ((substr( strtolower($entry),-5 ) == ".wget")) {
			// this is wget.
			$isTorrent = false;
			$transferowner = $cfg["user"];
			$owner = true;
			$settingsAry = array();
			$settingsAry['btclient'] = "wget";
			$settingsAry['hash'] = $entry;
			$af = AliasFile::getAliasFileInstance($dirName.$alias, $cfg['user'], $cfg, 'wget');
		} else {
			// this is "something else". use tornado statfile as default
			$isTorrent = false;
			$transferowner = $cfg["user"];
			$owner = true;
			$settingsAry = array();
			$settingsAry['btclient'] = "tornado";
			$settingsAry['hash'] = $entry;
			$af = AliasFile::getAliasFileInstance($dirName.$alias, $cfg['user'], $cfg, 'tornado');
		}
		// cache running-flag in local var. we will access that often
		$transferRunning = (int) $af->running;
		// cache percent-done in local var. ...
		$percentDone = $af->percent_done;

		// ---------------------------------------------------------------------
		//XFER: add upload/download stats to the xfer array
		if (($cfg['enable_xfer'] == 1) && ($cfg['xfer_realtime'] == 1))
			$newday = transferListXferUpdate1($entry, $transferowner, $af, $settingsAry);

		$timeStarted = "";
		$torrentfilelink = "";
		if(! file_exists($dirName.$alias)) {
			$af->running = "2"; // file is new
			$af->size = getDownloadSize($dirName.$entry);
			$af->WriteFile();
		}
		if(strlen($entry) >= 47) {
			// needs to be trimmed
			$displayname = substr($entry, 0, 44);
			$displayname .= "...";
		}
		if ($cfg["enable_torrent_download"])
			$torrentfilelink = "<a href=\"index.php?iid=maketorrent&download=".urlencode($entry)."\"><img src=\"images/down.gif\" width=9 height=9 title=\"Download Torrent File\" border=0 align=\"absmiddle\"></a>";
		//
		$output .= "<tr>";
		$detailsLinkString = "<a style=\"font-size:9px; text-decoration:none;\" href=\"JavaScript:ShowDetails('index.php?iid=downloaddetails&alias=".$alias."&torrent=".urlencode($entry)."')\">";

		// ========================================================== led + meta
		$output .= '<td valign="bottom" align="center" nowrap>';
		// led
		$hd = getStatusImage($af);
		if ($af->running == 1)
		  $output .= "<a href=\"JavaScript:ShowDetails('index.php?iid=downloadhosts&alias=".$alias."&torrent=".urlencode($entry)."')\">";
		$output .= "<img src=\"images/".$hd->image."\" width=\"16\" height=\"16\" title=\"".$hd->title.$entry."\" border=\"0\" align=\"absmiddle\">";
		if ($af->running == 1)
		  $output .= "</a>";
		// meta
		$output .= $torrentfilelink;
		$output .= "</td>";

		// ================================================================ name
		$output .= "<td valign=\"bottom\" nowrap>";
		$output .= $detailsLinkString;
		$output .= $displayname;
		$output .= "</a>";
		$output .= "</td>";

		$output .= "<td align=\"right\" nowrap><font class=\"tiny\">".formatBytesToKBMGGB($af->size)."</font></td>";
		$output .= "<td align=\"center\" nowrap><a href=\"index.php?iid=message&to_user=".$transferowner."\"><font class=\"tiny\">".$transferowner."</font></a></td>";
		$output .= "<td valign=\"bottom\" nowrap><div align=\"center\">";
		if ($af->running == "2") {
			$output .= "<i><font color=\"#32cd32\">"._NEW."</font></i>";
		} elseif ($af->running == "3" ) {
			$estTime = "Waiting...";
			$output .= "<i><font color=\"#000000\" onmouseover=\"return overlib('"._QUEUED."<br>', CSSCLASS);\" onmouseout=\"return nd();\">"._QUEUED."</font></i>";
		} else {
			if ($af->time_left != "" && $af->time_left != "0")
				$estTime = $af->time_left;
			$sql_search_time = "Select time from tf_log where action like '%Upload' and file like '".$entry."%'";
			$result_search_time = $db->Execute($sql_search_time);
			list($uploaddate) = $result_search_time->FetchRow();
			$lastUser = $transferowner;
			$sharing = $af->sharing."%";
			$graph_width = 1;
			$progress_color = "#00ff00";
			$background = "#000000";
			$bar_width = "4";
			$popup_msg = _ESTIMATEDTIME.": ".$af->time_left;
			$popup_msg .= "<br>". _DOWNLOADSPEED .": ".$af->down_speed;
			$popup_msg .= "<br>". _UPLOADSPEED .": ".$af->up_speed;
			$popup_msg .= "<br>". _SHARING .": ".$sharing;
			$popup_msg .= "<br>Seeds: ".$af->seeds;
			$popup_msg .= "<br>Peers: ".$af->peers;
			$popup_msg .= "<br>". _USER .": ".$transferowner;
			$eCount = 0;
			foreach ($af->errors as $key => $value) {
				if(strpos($value," (x")) {
					$curEMsg = substr($value,strpos($value," (x")+3);
					$eCount += substr($curEMsg,0,strpos($curEMsg,")"));
				} else {
					$eCount += 1;
				}
			}
			$popup_msg .= "<br>"._ERRORSREPORTED.": ".strval($eCount);
			$popup_msg .= "<br>"._UPLOADED.": ".date("m/d/Y H:i:s", $uploaddate);
			if (is_file($dirName.$alias.".pid"))
				$timeStarted = "<br>"._STARTED.": ".date("m/d/Y H:i:s",	 strval(filectime($dirName.$alias.".pid")));
			// incriment the totals
			if(!isset($cfg["total_upload"])) $cfg["total_upload"] = 0;
			if(!isset($cfg["total_download"])) $cfg["total_download"] = 0;
			$cfg["total_upload"] = $cfg["total_upload"] + GetSpeedValue($af->up_speed);
			$cfg["total_download"] = $cfg["total_download"] + GetSpeedValue($af->down_speed);
			if($af->percent_done >= 100) {
				if(trim($af->up_speed) != "" && $af->running == "1") {
					$popup_msg .= $timeStarted;
					$output .= "<a href=\"JavaScript:ShowDetails('index.php?iid=downloaddetails&alias=".$alias."&torrent=".urlencode($entry)."')\" style=\"font-size:7pt;\" onmouseover=\"return overlib('".$popup_msg."<br>', CSSCLASS);\" onmouseout=\"return nd();\">seeding (".$af->up_speed.") ".$sharing."</a>";
				} else {
					$popup_msg .= "<br>"._ENDED.": ".date("m/d/Y H:i:s",  strval(filemtime($dirName.$alias)));
					$output .= "<a href=\"JavaScript:ShowDetails('index.php?iid=downloaddetails&alias=".$alias."&torrent=".urlencode($entry)."')\" onmouseover=\"return overlib('".$popup_msg."<br>', CSSCLASS);\" onmouseout=\"return nd();\"><i><font color=red>"._DONE."</font></i></a>";
				}
				$show_run = false;
			} else if ($af->percent_done < 0) {
				$popup_msg .= $timeStarted;
				$output .= "<a href=\"JavaScript:ShowDetails('index.php?iid=downloaddetails&alias=".$alias."&torrent=".urlencode($entry)."')\" onmouseover=\"return overlib('".$popup_msg."<br>', CSSCLASS);\" onmouseout=\"return nd();\"><i><font color=\"#989898\">"._INCOMPLETE."</font></i></a>";
				$show_run = true;
			} else {
				$popup_msg .= $timeStarted;
				if($af->percent_done > 1)
					$graph_width = $af->percent_done;
				if($graph_width == 100)
					$background = $progress_color;
				$output .= "<a href=\"JavaScript:ShowDetails('index.php?iid=downloaddetails&lias=".$alias."&torrent=".urlencode($entry)."')\" onmouseover=\"return overlib('".$popup_msg."<br>', CSSCLASS);\" onmouseout=\"return nd();\">";
				$output .= "<font class=\"tiny\"><strong>".$af->percent_done."%</strong> @ ".$af->down_speed."</font></a><br>";
				$output .= "<table width=\"100\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\">";
				$output .= "<tr><td background=\"themes/".$cfg["theme"]."/images/progressbar.gif\" bgcolor=\"".$progress_color."\"><img src=\"images/blank.gif\" width=\"".$graph_width."\" height=\"".$bar_width."\" border=\"0\"></td>";
				$output .= "<td bgcolor=\"".$background."\"><img src=\"images/blank.gif\" width=\"".(100 - $graph_width)."\" height=\"".$bar_width."\" border=\"0\"></td>";
				$output .= "</tr></table>";
			}
		}

		$output .= "</div></td>";
		$output .= "<td nowrap><div class=\"tiny\" align=\"center\">".$estTime."</div></td>";

		// =============================================================== admin
		$output .= '<td nowrap>';
		include('inc/index_admincell.php');
		$output .= "</td>";
		$output .= "</tr>\n";

		// Is this torrent for the user list or the general list?
		if ($owner)
			array_push($arUserTorrent, $output);
		else
			array_push($arListTorrent, $output);
	}

	//XFER: if a new day but no .stat files where found put blank entry into the
	//      DB for today to indicate accounting has been done for the new day
	if (($cfg['enable_xfer'] == 1) && ($cfg['xfer_realtime'] == 1))
		transferListXferUpdate2($newday);

	// build output string
	$output = '<table bgcolor="'.$cfg["table_data_bg"].'" width="100%" bordercolor="'.$cfg["table_border_dk"].'" border="1" cellpadding="3" cellspacing="0" class="sortable" id="transfer_table">';

	if (sizeof($arUserTorrent) > 0) {
		$output .= "<tr>";
		// first
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=center class=\"title\">";
		switch ($sortOrder) {
			case 'da': // sort by date ascending
				$output .= '<a href="?so=dd"><font class="adminlink">#</font></a>';
				$output .= '&nbsp;';
				$output .= '<a href="?so=dd"><img src="images/s_down.gif" width="9" height="9" border="0"></a>';
				break;
			case 'dd': // sort by date descending
				$output .= '<a href="?so=da"><font class="adminlink">#</font></a>';
				$output .= '&nbsp;';
				$output .= '<a href="?so=da"><img src="images/s_up.gif" width="9" height="9" border="0"></a>';
				break;
			default:
				$output .= '<a href="?so=dd"><font class="adminlink">#</font></a>';
				break;
		}
		$output .= "</div></td>";
		// name
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=center class=\"title\">";
		switch ($sortOrder) {
			case 'na': // sort alphabetically by name ascending
				$output .= '<a href="?so=nd"><font class="adminlink">' .$cfg["user"].": ". _TRANSFERFILE .'</font></a>';
				$output .= '&nbsp;';
				$output .= '<a href="?so=nd"><img src="images/s_down.gif" width="9" height="9" border="0"></a>';
				break;
			case 'nd': // sort alphabetically by name descending
				$output .= '<a href="?so=na"><font class="adminlink">' .$cfg["user"].": ". _TRANSFERFILE .'</font></a>';
				$output .= '&nbsp;';
				$output .= '<a href="?so=na"><img src="images/s_up.gif" width="9" height="9" border="0"></a>';
				break;
			default:
				$output .= '<a href="?so=na"><font class="adminlink">' .$cfg["user"].": ". _TRANSFERFILE .'</font></a>';
				break;
		}
		$output .= "</div></td>";
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=center class=\"title\">Size</div></td>";
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=center class=\"title\">"._USER."</div></td>";
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=center class=\"title\">"._STATUS."</div></td>";
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=center class=\"title\">"._ESTIMATEDTIME."</div></td>";
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=center class=\"title\">"._ADMIN."</div></td>";
		$output .= "</tr>\n";
		foreach($arUserTorrent as $torrentrow)
			$output .= $torrentrow;
	}

	// "Only Admin can see other user torrents"
	$boolCond = true;
	if ($cfg['enable_restrictivetview'] == 1)
		$boolCond = IsAdmin();
	if (($boolCond) && (sizeof($arListTorrent) > 0)) {
	// "Only Admin can see other user torrents"
		$output .= "<tr>";
		// first
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=center class=\"title\">";
		switch ($sortOrder) {
			case 'da': // sort by date ascending
				$output .= '<a href="?so=dd"><font class="adminlink">#</font></a>';
				$output .= '&nbsp;';
				$output .= '<a href="?so=dd"><img src="images/s_down.gif" width="9" height="9" border="0"></a>';
				break;
			case 'dd': // sort by date descending
				$output .= '<a href="?so=da"><font class="adminlink">#</font></a>';
				$output .= '&nbsp;';
				$output .= '<a href="?so=da"><img src="images/s_up.gif" width="9" height="9" border="0"></a>';
				break;
			default:
				$output .= '<a href="?so=dd"><font class="adminlink">#</font></a>';
				break;
		}
		$output .= "</div></td>";
		// name
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=center class=\"title\">";
		switch ($sortOrder) {
			case 'na': // sort alphabetically by name ascending
				$output .= '<a href="?so=nd"><font class="adminlink">' ._TRANSFERFILE .'</font></a>';
				$output .= '&nbsp;';
				$output .= '<a href="?so=nd"><img src="images/s_down.gif" width="9" height="9" border="0"></a>';
				break;
			case 'nd': // sort alphabetically by name descending
				$output .= '<a href="?so=na"><font class="adminlink">' ._TRANSFERFILE .'</font></a>';
				$output .= '&nbsp;';
				$output .= '<a href="?so=na"><img src="images/s_up.gif" width="9" height="9" border="0"></a>';
				break;
			default:
				$output .= '<a href="?so=na"><font class="adminlink">' ._TRANSFERFILE .'</font></a>';
				break;
		}
		$output .= "</div></td>";
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=center class=\"title\">Size</div></td>";
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=center class=\"title\">"._USER."</div></td>";
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=center class=\"title\">"._STATUS."</div></td>";
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=center class=\"title\">"._ESTIMATEDTIME."</div></td>";
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=center class=\"title\">"._ADMIN."</div></td>";
		$output .= "</tr>\n";
		foreach($arListTorrent as $torrentrow)
			$output .= $torrentrow;
	}
	return $output;
}

?>