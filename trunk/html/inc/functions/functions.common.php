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
 * perform Authentication
 *
 * @param $username
 * @param $password
 * @return int with :
 *                     1 : user authenticated
 *                     0 : user not authenticated
 */
function performAuthentication($username = '', $password = '') {
	global $cfg, $db;
	if (! isset($username))
		return 0;
	if (! isset($password))
		return 0;
	if ($username == '')
		return 0;
	if ($password == '')
		return 0;
	$sql = "SELECT uid, hits, hide_offline, theme, language_file FROM tf_users WHERE state = 1 AND user_id=".$db->qstr($username)." AND password=".$db->qstr(md5($password));
	$result = $db->Execute($sql);
	showError($db,$sql);
	list($uid,$hits,$cfg["hide_offline"],$cfg["theme"],$cfg["language_file"]) = $result->FetchRow();
	if(!array_key_exists("shutdown",$cfg))
		$cfg['shutdown'] = '';
	if(!array_key_exists("upload_rate",$cfg))
		$cfg['upload_rate'] = '';
	if($result->RecordCount() == 1) { // suc. auth.
		// Add a hit to the user
		$hits++;
		$sql = 'select * from tf_users where uid = '.$uid;
		$rs = $db->Execute($sql);
		showError($db, $sql);
		$rec = array(
						'hits'=>$hits,
						'last_visit'=>$db->DBDate(time()),
						'theme'=>$cfg['theme'],
						'language_file'=>$cfg['language_file'],
						'shutdown'=>$cfg['shutdown'],
						'upload_rate'=>$cfg['upload_rate']
					);
		$sql = $db->GetUpdateSQL($rs, $rec);
		$result = $db->Execute($sql);
		showError($db, $sql);
		$_SESSION['user'] = $username;
		$_SESSION['uid'] = $uid;
		$cfg["user"] = strtolower($_SESSION['user']);
		$cfg['uid'] = $uid;
		@session_write_close();
		return 1;
	} else { // wrong credentials
		AuditAction($cfg["constants"]["access_denied"], "FAILED AUTH: ".$username);
		unset($_SESSION['user']);
		unset($_SESSION['uid']);
		unset($cfg["user"]);
		return 0;
	}
	return 0;
}

/**
 * firstLogin
 *
 * @param $username
 * @param $password
 */
function firstLogin($username = '', $password = '') {
	global $cfg, $db;
	if (! isset($username))
		return 0;
	if (! isset($password))
		return 0;
	if ($username == '')
		return 0;
	if ($password == '')
		return 0;
	$create_time = time();
	// This user is first in DB.  Make them super admin.
	// this is The Super USER, add them to the user table
	$record = array(
					'user_id'=>$username,
					'password'=>md5($password),
					'hits'=>1,
					'last_visit'=>$create_time,
					'time_created'=>$create_time,
					'user_level'=>2,
					'hide_offline'=>0,
					'theme'=>$cfg["default_theme"],
					'language_file'=>$cfg["default_language"],
					'state'=>1
					);
	$sTable = 'tf_users';
	$sql = $db->GetInsertSql($sTable, $record);
	$result = $db->Execute($sql);
	showError($db,$sql);
	// Test and setup some paths for the TF settings
	$pythonCmd = $cfg["pythonCmd"];
	$tfPath = getcwd() . "/downloads/";
	if (!isFile($cfg["pythonCmd"])) {
		$pythonCmd = trim(shell_exec("which python"));
		if ($pythonCmd == "")
			$pythonCmd = $cfg["pythonCmd"];
	}
	$settings = array(
						"pythonCmd" => $pythonCmd,
						"path" => $tfPath
					);
	saveSettings($settings);
	AuditAction($cfg["constants"]["update"], "Initial Settings Updated for first login.");
}

/**
 * Checks for the location of the torrents
 * If it does not exist, then it creates it.
 *
 */
function checkTorrentPath() {
	global $cfg;
	// is there a stat and torrent dir?
	if (!@is_dir($cfg["torrent_file_path"]) && is_writable($cfg["path"])) {
		// Then create it
		@checkDirectory($cfg["torrent_file_path"], 0777);
	}
}

/**
 * Enter description here...
 *
 */
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

/**
 * process post-params on config-update and init settings-array
 *
 * @return array with settings
 */
function processSettingsParams() {
	// move hack
	unset($_POST['addCatButton']);
	unset($_POST['remCatButton']);
	unset($_POST['categorylist']);
	unset($_POST['category']);
	// init settings array from params
	// process and handle all specials and exceptions while doing this.
	$settings = array();
	// good-look-stats
	$hackStatsPrefix = "hack_goodlookstats_settings_";
	$hackStatsStringLen = strlen($hackStatsPrefix);
	$settingsHackAry = array();
	for ($i = 0; $i <= 5; $i++)
		$settingsHackAry[$i] = 0;
	$hackStatsUpdate = false;
	// index-page
	$indexPageSettingsPrefix = "index_page_settings_";
	$indexPageSettingsPrefixLen = strlen($indexPageSettingsPrefix);
	$settingsIndexPageAry = array();
	for ($j = 0; $j <= 10; $j++)
		$settingsIndexPageAry[$j] = 0;
	$indexPageSettingsUpdate = false;
	//
	foreach ($_POST as $key => $value) {
		if ((substr($key, 0, $hackStatsStringLen)) == $hackStatsPrefix) {
			// good-look-stats
			$idx = (int) substr($key, -1, 1);
			if ($value != "0")
				$settingsHackAry[$idx] = 1;
			else
				$settingsHackAry[$idx] = 0;
			$hackStatsUpdate = true;
		} else if ((substr($key, 0, $indexPageSettingsPrefixLen)) == $indexPageSettingsPrefix) {
			// index-page
			$idx = (int) substr($key, ($indexPageSettingsPrefixLen - (strlen($key))));
			if ($value != "0")
				$settingsIndexPageAry[$idx] = 1;
			else
				$settingsIndexPageAry[$idx] = 0;
			$indexPageSettingsUpdate = true;
		} else {
			switch ($key) {
				case "path": // tf-path
					$settings[$key] = trim(checkDirPathString($value));
					break;
				case "move_paths": // move-hack-paths
					$dirAry = explode(":",$value);
					$val = "";
					for ($idx = 0; $idx < count($dirAry); $idx++) {
						if ($idx > 0)
							$val .= ':';
						$val .= trim(checkDirPathString($dirAry[$idx]));
					}
					$settings[$key] = trim($val);
					break;
				default: // "normal" key-val-pair
					$settings[$key] = $value;
			}
		}
	}
	// good-look-stats
	if ($hackStatsUpdate)
		$settings['hack_goodlookstats_settings'] = convertArrayToByte($settingsHackAry);
	// index-page
	if ($indexPageSettingsUpdate)
		$settings['index_page_settings'] = convertArrayToInteger($settingsIndexPageAry);
	// return
	return $settings;
}

/**
 * load Settings
 *
 */
function loadSettings() {
    global $cfg, $db;
    // pull the config params out of the db
    $sql = "SELECT tf_key, tf_value FROM tf_settings";
    $recordset = $db->Execute($sql);
    showError($db, $sql);
    while(list($key, $value) = $recordset->FetchRow()) {
        $tmpValue = '';
		if (strpos($key,"Filter")>0) {
		  $tmpValue = unserialize($value);
		} elseif ($key == 'searchEngineLinks') {
            $tmpValue = unserialize($value);
    	}
    	if(is_array($tmpValue))
            $value = $tmpValue;
        $cfg[$key] = $value;
    }
}

/**
 * insert Setting
 *
 * @param $key
 * @param $value
 */
function insertSetting($key,$value) {
    global $cfg, $db;
	// flush session-cache
	unset($_SESSION['cache']);
    $update_value = $value;
    if (is_array($value))
        $update_value = serialize($value);
    $sql = "INSERT INTO tf_settings VALUES ('".$key."', '".$update_value."')";
    if ( $sql != "" ) {
        //$result = $db->Execute($sql);
        $db->Execute($sql);
        showError($db,$sql);
        // update the Config.
        $cfg[$key] = $value;
    }
}

/**
 * updateSetting
 *
 * @param $key
 * @param $value
 */
function updateSetting($key,$value) {
    global $cfg, $db;
	// flush session-cache
	unset($_SESSION['cache']);
    $update_value = $value;
	if (is_array($value))
        $update_value = serialize($value);
    $sql = "UPDATE tf_settings SET tf_value = '".$update_value."' WHERE tf_key = '".$key."'";
    if ( $sql != "" ) {
        //$result = $db->Execute($sql);
        $db->Execute($sql);
        showError($db,$sql);
        // update the Config.
        $cfg[$key] = $value;
    }
}

/**
 * save Settings
 *
 * @param $settings
 */
function saveSettings($settings) {
    global $cfg, $db;
    foreach ($settings as $key => $value) {
        if (array_key_exists($key, $cfg)) {
            if(is_array($cfg[$key]) || is_array($value)) {
                if(serialize($cfg[$key]) != serialize($value)) {
                    updateSetting($key, $value);
                }
            } elseif ($cfg[$key] != $value) {
                updateSetting($key, $value);
            } else {
                // Nothing has Changed..
            }
        } else {
            insertSetting($key,$value);
        }
    }
}

/*
 * Function for saving user Settings
 *
 * @param $uid uid of the user
 * @param $settings settings-array
 */
function saveUserSettings($uid, $settings) {
	if (! isset($uid))
		return false;
	// Messy - a not exists would prob work better. but would have to be done
	// on every key/value pair so lots of extra-statements.
	deleteUserSettings($uid);
	// insert new settings
	foreach ($settings as $key => $value)
		insertUserSettingPair($uid,$key,$value);
	return true;
}

/*
 * insert setting-key/val pair for user into db
 *
 * @param $uid uid of the user
 * @param $key
 * @param $value
 * @return boolean
 */
function insertUserSettingPair($uid,$key,$value) {
	if (! isset($uid))
		return false;
	global $cfg, $db;
	$update_value = $value;
	if (is_array($value)) {
		$update_value = serialize($value);
	} else {
		// only insert if setting different from global settings or has changed
		if ($cfg[$key] == $value)
			return true;
	}
	// flush session-cache
	unset($_SESSION['cache'][$cfg["user"]]);
	$sql = "INSERT INTO tf_settings_user VALUES ('".$uid."', '".$key."', '".$update_value."')";
	$result = $db->Execute($sql);
	showError($db,$sql);
	// update the Config.
	$cfg[$key] = $value;
	return true;
}

/*
 * Function to delete saved user Settings
 *
 * @param $uid uid of the user
 */
function deleteUserSettings($uid) {
	if ( !isset($uid))
		return false;
	global $db;
	// flush session-cache
	unset($_SESSION['cache'][$cfg["user"]]);
	$sql = "DELETE FROM tf_settings_user WHERE uid = '".$uid."'";
	$db->Execute($sql);
		showError($db, $sql);
	return true;
}

/*
 * Function to load the settings for a user to global cfg-array
 *
 * @param $uid uid of the user
 * @return boolean
 */
function loadUserSettingsToConfig($uid) {
	if ( !isset($uid))
		return false;
	global $cfg, $db;
	// get user-settings from db and set in global cfg-array
	$sql = "SELECT tf_key, tf_value FROM tf_settings_user WHERE uid = '".$uid."'";
	$recordset = $db->Execute($sql);
	showError($db, $sql);
	if ((isset($recordset)) && ($recordset->NumRows() > 0)) {
		while(list($key, $value) = $recordset->FetchRow())
			$cfg[$key] = $value;
	}
	return true;
}

/**
 * add New User
 *
 * @param $newUser
 * @param $pass1
 * @param $userType
 */
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

/**
 * get form of index page settings (0-2047)
 *
 * #
 * Torrent
 *
 * User			  [0]
 * Size			  [1]
 * DLed			  [2]
 * ULed			  [3]
 *
 * Status		  [4]
 * Progress		  [5]
 * DL Speed		  [6]
 * UL Speed		  [7]
 *
 * Seeds		  [8]
 * Peers		  [9]
 * ETA			 [10]
 * TorrentClient [11]
 *
 */
function getIndexPageSettingsForm() {
	global $cfg;
	$settingsIndexPage = convertIntegerToArray($cfg["index_page_settings"]);
	$indexPageSettingsForm = '<table>';
	$indexPageSettingsForm .= '<tr>';
	$indexPageSettingsForm .= '<td align="right" nowrap>Owner: <input name="index_page_settings_0" type="Checkbox" value="1"';
	if ($settingsIndexPage[0] == 1)
		$indexPageSettingsForm .= ' checked';
	$indexPageSettingsForm .= '></td>';
	$indexPageSettingsForm .= '<td align="right" nowrap>Size: <input name="index_page_settings_1" type="Checkbox" value="1"';
	if ($settingsIndexPage[1] == 1)
		$indexPageSettingsForm .= ' checked';
	$indexPageSettingsForm .= '></td>';
	$indexPageSettingsForm .= '<td align="right" nowrap>Total Down: <input name="index_page_settings_2" type="Checkbox" value="1"';
	if ($settingsIndexPage[2] == 1)
		$indexPageSettingsForm .= ' checked';
	$indexPageSettingsForm .= '></td>';
	$indexPageSettingsForm .= '<td align="right" nowrap>Total Up: <input name="index_page_settings_3" type="Checkbox" value="1"';
	if ($settingsIndexPage[3] == 1)
		$indexPageSettingsForm .= ' checked';
	$indexPageSettingsForm .= '></td>';
	$indexPageSettingsForm .= '</tr>';
	$indexPageSettingsForm .= '<tr>';
	$indexPageSettingsForm .= '<td align="right" nowrap>Status : <input name="index_page_settings_4" type="Checkbox" value="1"';
	if ($settingsIndexPage[4] == 1)
		$indexPageSettingsForm .= ' checked';
	$indexPageSettingsForm .= '></td>';
	$indexPageSettingsForm .= '<td align="right" nowrap>Progress : <input name="index_page_settings_5" type="Checkbox" value="1"';
	if ($settingsIndexPage[5] == 1)
		$indexPageSettingsForm .= ' checked';
	$indexPageSettingsForm .= '></td>';
	$indexPageSettingsForm .= '<td align="right" nowrap>Down-Speed : <input name="index_page_settings_6" type="Checkbox" value="1"';
	if ($settingsIndexPage[6] == 1)
		$indexPageSettingsForm .= ' checked';
	$indexPageSettingsForm .= '></td>';
	$indexPageSettingsForm .= '<td align="right" nowrap>Up-Speed : <input name="index_page_settings_7" type="Checkbox" value="1"';
	if ($settingsIndexPage[7] == 1)
		$indexPageSettingsForm .= ' checked';
	$indexPageSettingsForm .= '></td>';
	$indexPageSettingsForm .= '</tr>';
	$indexPageSettingsForm .= '<tr>';
	$indexPageSettingsForm .= '<td align="right" nowrap>Seeds : <input name="index_page_settings_8" type="Checkbox" value="1"';
	if ($settingsIndexPage[8] == 1)
		$indexPageSettingsForm .= ' checked';
	$indexPageSettingsForm .= '></td>';
	$indexPageSettingsForm .= '<td align="right" nowrap>Peers : <input name="index_page_settings_9" type="Checkbox" value="1"';
	if ($settingsIndexPage[9] == 1)
		$indexPageSettingsForm .= ' checked';
	$indexPageSettingsForm .= '></td>';
	$indexPageSettingsForm .= '<td align="right" nowrap>Estimated Time : <input name="index_page_settings_10" type="Checkbox" value="1"';
	if ($settingsIndexPage[10] == 1)
		$indexPageSettingsForm .= ' checked';
	$indexPageSettingsForm .= '></td>';
	$indexPageSettingsForm .= '<td align="right" nowrap>Client : <input name="index_page_settings_11" type="Checkbox" value="1"';
	if ($settingsIndexPage[11] == 1)
		$indexPageSettingsForm .= ' checked';
	$indexPageSettingsForm .= '></td>';
	$indexPageSettingsForm .= '</tr>';
	$indexPageSettingsForm .= '</table>';
	return $indexPageSettingsForm;
}

/**
 * get form of good looking stats hack (0-63)
 *
 */
function getGoodLookingStatsForm() {
	global $cfg;
	$settingsHackStats = convertByteToArray($cfg["hack_goodlookstats_settings"]);
	$goodLookingStatsForm = '<table>';
	$goodLookingStatsForm .= '<tr><td align="right" nowrap>Download Speed: <input name="hack_goodlookstats_settings_0" type="Checkbox" value="1"';
	if ($settingsHackStats[0] == 1)
		$goodLookingStatsForm .= ' checked';
	$goodLookingStatsForm .= '></td>';
	$goodLookingStatsForm .= '<td align="right" nowrap>Upload Speed: <input name="hack_goodlookstats_settings_1" type="Checkbox" value="1"';
	if ($settingsHackStats[1] == 1)
		$goodLookingStatsForm .= ' checked';
	$goodLookingStatsForm .= '></td>';
	$goodLookingStatsForm .= '<td align="right" nowrap>Total Speed: <input name="hack_goodlookstats_settings_2" type="Checkbox" value="1"';
	if ($settingsHackStats[2] == 1)
		$goodLookingStatsForm .= ' checked';
	$goodLookingStatsForm .= '></td></tr>';
	$goodLookingStatsForm .= '<tr><td align="right" nowrap>Connections: <input name="hack_goodlookstats_settings_3" type="Checkbox" value="1"';
	if ($settingsHackStats[3] == 1)
		$goodLookingStatsForm .= ' checked';
	$goodLookingStatsForm .= '></td>';
	$goodLookingStatsForm .= '<td align="right" nowrap>Drive Space: <input name="hack_goodlookstats_settings_4" type="Checkbox" value="1"';
	if ($settingsHackStats[4] == 1)
		$goodLookingStatsForm .= ' checked';
	$goodLookingStatsForm .= '></td>';
	$goodLookingStatsForm .= '<td align="right" nowrap>Server Load: <input name="hack_goodlookstats_settings_5" type="Checkbox" value="1"';
	if ($settingsHackStats[5] == 1)
		$goodLookingStatsForm .= ' checked';
	$goodLookingStatsForm .= '></td></tr>';
	$goodLookingStatsForm .= '</table>';
	return $goodLookingStatsForm;
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

/**
 * Save Message
 *
 * @param $to_user
 * @param $from_user
 * @param $message
 * @param $to_all
 * @param $force_read
 */
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

// ***************************************************************************
// Get Message data in an array
function GetMessage($mid) {
	global $cfg, $db;
	$sql = "select from_user, message, ip, time, isnew, force_read from tf_messages where mid=".$mid." and to_user=".$db->qstr($cfg['user']);
	$rtnValue = $db->GetRow($sql);
	showError($db,$sql);
	return $rtnValue;
}

/**
 * get dropdown list to send message to a user
 *
 * @return string
 */
function getMessageList() {
	global $cfg;
	$users = GetUsers();
	$messageList = '<div align="center">'.
	'<table border="0" cellpadding="0" cellspacing="0">'.
	'<form name="formMessage" action="index.php?iid=message" method="post">'.
	'<tr><td>' . _SENDMESSAGETO ;
	$messageList .= '<select name="to_user">';
	for($inx = 0; $inx < sizeof($users); $inx++) {
		$messageList .= '<option>'.$users[$inx].'</option>';
	}
	$messageList .= '</select>';
	$messageList .= '<input type="Submit" value="' . _COMPOSE .'">';
	$messageList .= '</td></tr></form></table></div>';
	return $messageList;
}

// ***************************************************************************
// Get Themes data in an array
function GetThemes() {
	$arThemes = array();
	$dir = "themes/";
	$handle = opendir($dir);
	while($entry = readdir($handle)) {
		if (is_dir($dir.$entry) && ($entry != "." && $entry != ".." && $entry != ".svn" && $entry != "CVS" && $entry != "tf_standard_themes"))
			array_push($arThemes, $entry);
	}
	closedir($handle);
	sort($arThemes);
	return $arThemes;
}
// ***************************************************************************
// Get Themes data in an array
function GetThemesStandard() {
	$arThemes = array();
	$dir = "themes/tf_standard_themes/";
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

/**
 * get BTClient Select
 *
 * @param $btclient
 * @return string
 */
function getBTClientSelect($btclient = 'tornado') {
	global $cfg;
	$getBTClientSelect = '<select name="btclient">';
	$getBTClientSelect .= '<option value="tornado"';
	if ($btclient == "tornado")
		$getBTClientSelect .= " selected";
	$getBTClientSelect .= '>tornado</option>';
	$getBTClientSelect .= '<option value="transmission"';
	if ($btclient == "transmission")
		$getBTClientSelect .= " selected";
	$getBTClientSelect .= '>transmission</option>';
	$getBTClientSelect .= '<option value="mainline"';
	if ($btclient == "mainline")
		$getBTClientSelect .= " selected";
	$getBTClientSelect .= '>mainline</option>';
	$getBTClientSelect .= '</select>';
	return $getBTClientSelect;
}

/**
 * get form of sort-order-settings
 *
 */
function getSortOrderSettings() {
	global $cfg;
	# create new template
	if ((strpos($cfg['theme'], '/')) === false)
		$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/inc.getSortOrderSettings.tmpl");
	else
		$tmpl = new vlibTemplate("themes/tf_standard_themes/tmpl/inc.getSortOrderSettings.tmpl");
	//set some vars
	$tmpl->setvar('index_page_sortorder', $cfg["index_page_sortorder"]);
	// grab the template
	$output = $tmpl->grab();
	return $output;
}

/**
 * get form of move-settings
 *
 */
function getMoveSettings() {
	global $cfg;
	# create new template
	if ((strpos($cfg['theme'], '/')) === false)
		$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/inc.getMoveSettings.tmpl");
	else
		$tmpl = new vlibTemplate("themes/tf_standard_themes/tmpl/inc.getMoveSettings.tmpl");
	//set some vars
	if ((isset($cfg["move_paths"])) && (strlen($cfg["move_paths"]) > 0)) {
		$dirs = split(":", trim($cfg["move_paths"]));
		$dir_list = array();
		foreach ($dirs as $dir) {
			$target = trim($dir);
			if ((strlen($target) > 0) && ((substr($target, 0, 1)) != ";")) {
				array_push($dir_list, array(
					'target' => $target,
					)
				);
			}
		}
		$tmpl->setloop('dir_list', $dir_list);
	}
	$tmpl->setvar('move_paths', $cfg["move_paths"]);
	// grab the template
	$output = $tmpl->grab();
	return $output;
}

/**
 * Specific save path
 *
 * @param $dir
 * @param $maxdepth
 * @return unknown
 */
function dirTree2($dir, $maxdepth) {
        $dirTree2 = "<option value=\"".$dir."\">".$dir."</option>\n" ;
        if (is_numeric ($maxdepth)) {
                if ($maxdepth == 0) {
                        //$last = exec ("du ".$dir." | cut -f 2- | sort", $retval);
                        $last = exec ("find ".$dir." -type d | sort", $retval);
                        for ($i = 1; $i < (count ($retval) - 1); $i++)
                        {
                                $dirTree2 .= "<option value=\"".$retval[$i]."\">".$retval[$i]."</option>\n" ;
                        }
                } else if ($maxdepth > 0) {
                        //$last = exec ("du --max-depth=".$maxdepth." ".$dir." | cut -f 2- | sort", $retval);
                        $last = exec ("find ".$dir." -maxdepth ".$maxdepth." -type d | sort", $retval);
                        for ($i = 1; $i < (count ($retval) - 1); $i++)
                                $dirTree2 .= "<option value=\"".$retval[$i]."\">".$retval[$i]."</option>\n" ;
                } else {
                        $dirTree2 .= "<option value=\"".$dir."\">".$dir."</option>\n" ;
                }
        } else {
                $dirTree2 .= "<option value=\"".$dir."\">".$dir."</option>\n" ;
        }
        return $dirTree2;
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

// Profiles hack
//*************************************************************************
// GetProfiles()
// This method Gets Download profiles for the actual user

function GetProfiles($user, $profile) {
	global $cfg, $db;
	$profiles_array = array();
	$sql = "SELECT name FROM tf_trprofiles WHERE owner LIKE '".$user."' AND public='0'";
	$rs = $db->GetCol($sql);
	if ($rs) {
		foreach($rs as $arr) {
			if($arr == $profile)
				$is_select = 1;
			else
				$is_select = 0;
			array_push($profiles_array, array(
				'name' => $arr,
				'is_selected' => $is_select,
				)
			);
		}
	}
	showError($db,$sql);
	return $profiles_array;
}

//*************************************************************************
// GetPublicProfiles()
// This method Gets public Download profiles
function GetPublicProfiles($profile) {
	global $cfg, $db;
	$profiles_array = array();
	$sql = "SELECT name FROM tf_trprofiles WHERE public= '1'";
	$rs = $db->GetCol($sql);
	if ($rs) {
		foreach($rs as $arr) {
			if($arr == $profile)
				$is_select = 1;
			else
				$is_select = 0;
			array_push($profiles_array, array(
				'name' => $arr,
				'is_selected' => $is_select,
				)
			);
		}
	}
	showError($db,$sql);
	return $profiles_array;
}

// Profiles hack
//*************************************************************************
// GetProfileSettings()
// This method fetch settings for an specific profile
function GetProfileSettings($profile) {
	global $cfg, $db;
	$sql = "SELECT minport, maxport, maxcons, rerequest, rate, maxuploads, drate, runtime, sharekill, superseeder from tf_trprofiles where name like '".$profile."'";
	$settings = $db->GetRow($sql);
	showError($db,$sql);
	return $settings;
}

// ***************************************************************************
// addProfileInfo - Add New Profile Information
function AddProfileInfo( $newProfile ) {
	global $db, $cfg;
	$sql ="INSERT INTO tf_trprofiles ( name , owner , minport , maxport , maxcons , rerequest , rate , maxuploads , drate , runtime , sharekill , superseeder , public ) VALUES ('".$newProfile["name"]."', '".$cfg['uid']."', '".$newProfile["minport"]."', '".$newProfile["maxport"]."', '".$newProfile["maxcons"]."', '".$newProfile["rerequest"]."', '".$newProfile["rate"]."', '".$newProfile["maxuploads"]."', '".$newProfile["drate"]."', '".$newProfile["runtime"]."', '".$newProfile["sharekill"]."', '".$newProfile["superseeder"]."', '".$newProfile["public"]."')";
	$db->Execute( $sql );
	showError( $db, $sql );
}

//*********************************************************
function getProfile($pid) {
	global $cfg, $db;
	$rtnValue = "";
	$sql = "SELECT id , name , minport , maxport , maxcons , rerequest , rate , maxuploads , drate , runtime , sharekill , superseeder , public FROM tf_trprofiles WHERE id LIKE '".$pid."'";
	$rtnValue = $db->GetAll($sql);
	return $rtnValue[0];
}

// ***************************************************************************
// modProfileInfo - Modify Profile Information
function modProfileInfo($pid, $newProfile) {
	global $cfg, $db;
	$sql = "UPDATE tf_trprofiles SET owner = '".$cfg['uid']."', name = '".$newProfile["name"]."', minport = '".$newProfile["minport"]."', maxport = '".$newProfile["maxport"]."', maxcons = '".$newProfile["maxcons"]."', rerequest = '".$newProfile["rerequest"]."', rate = '".$newProfile["rate"]."', maxuploads = '".$newProfile["maxuploads"]."', drate = '".$newProfile["drate"]."', runtime = '".$newProfile["runtime"]."', sharekill = '".$newProfile["sharekill"]."', superseeder = '".$newProfile["superseeder"]."', public = '".$newProfile["public"]."' WHERE id = '".$pid."'";
	$db->Execute($sql);
	showError($db,$sql);
}

// ***************************************************************************
// Delete Profile Information
function deleteProfileInfo($pid) {
	global $db;
	$sql = "DELETE FROM tf_trprofiles WHERE id=".$pid;
	$result = $db->Execute($sql);
	showError($db,$sql);
}


?>