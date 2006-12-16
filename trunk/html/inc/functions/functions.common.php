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
 * set vars for form of index page settings (0-2047)
 *
 * #
 * Torrent
 *
 * User			  [0]
 * Size			  [1]
 * DLed			  [2]
 * ULed			  [3]
 * Status		  [4]
 * Progress		  [5]
 * DL Speed		  [6]
 * UL Speed		  [7]
 * Seeds		  [8]
 * Peers		  [9]
 * ETA			 [10]
 * TorrentClient [11]
 *
 */
function tmplSetIndexPageSettingsForm() {
	global $cfg, $tmpl;
	$settingsIndexPage = convertIntegerToArray($cfg["index_page_settings"]);
	$tmpl->setvar('indexPageSettingsForm_settings_0', $settingsIndexPage[0]);
	$tmpl->setvar('indexPageSettingsForm_settings_1', $settingsIndexPage[1]);
	$tmpl->setvar('indexPageSettingsForm_settings_2', $settingsIndexPage[2]);
	$tmpl->setvar('indexPageSettingsForm_settings_3', $settingsIndexPage[3]);
	$tmpl->setvar('indexPageSettingsForm_settings_4', $settingsIndexPage[4]);
	$tmpl->setvar('indexPageSettingsForm_settings_5', $settingsIndexPage[5]);
	$tmpl->setvar('indexPageSettingsForm_settings_6', $settingsIndexPage[6]);
	$tmpl->setvar('indexPageSettingsForm_settings_7', $settingsIndexPage[7]);
	$tmpl->setvar('indexPageSettingsForm_settings_8', $settingsIndexPage[8]);
	$tmpl->setvar('indexPageSettingsForm_settings_9', $settingsIndexPage[9]);
	$tmpl->setvar('indexPageSettingsForm_settings_10', $settingsIndexPage[10]);
	$tmpl->setvar('indexPageSettingsForm_settings_11', $settingsIndexPage[11]);
}

/**
 * set vars for form of good looking stats (0-63)
 */
function tmplSetGoodLookingStatsForm() {
	global $cfg, $tmpl;
	$settingsHackStats = convertByteToArray($cfg["hack_goodlookstats_settings"]);
	$tmpl->setvar('goodLookingStatsForm_settings_0', $settingsHackStats[0]);
	$tmpl->setvar('goodLookingStatsForm_settings_1', $settingsHackStats[1]);
	$tmpl->setvar('goodLookingStatsForm_settings_2', $settingsHackStats[2]);
	$tmpl->setvar('goodLookingStatsForm_settings_3', $settingsHackStats[3]);
	$tmpl->setvar('goodLookingStatsForm_settings_4', $settingsHackStats[4]);
	$tmpl->setvar('goodLookingStatsForm_settings_5', $settingsHackStats[5]);
}

/**
 * Set Client Select Form vars
 *
 * @param $btclient
 */
function tmplSetClientSelectForm($btclient = 'tornado') {
	global $cfg, $tmpl;
	$btclients = array("tornado", "transmission", "mainline");
	$client_list = array();
	foreach ($btclients as $client) {
		array_push($client_list, array(
			'client' => $client,
			'selected' => ($btclient == $client) ? 1 : 0
			)
		);
	}
	$tmpl->setloop('clientSelectForm_client_list', $client_list);
}

/**
 * set dir tree vars
 *
 * @param $dir
 * @param $maxdepth
 */
function tmplSetDirTree($dir, $maxdepth) {
	global $cfg, $tmpl;
	$tmpl->setvar('dirtree_dir', $dir);
	if (is_numeric ($maxdepth)) {
		$retvar_list = array();
		$last = ($maxdepth == 0)
			? exec("find ".escapeshellarg($dir)." -type d | sort && echo", $retval)
			: exec("find ".escapeshellarg($dir)." -maxdepth ".escapeshellarg($maxdepth)." -type d | sort && echo", $retval);
		for ($i = 1; $i < (count ($retval) - 1); $i++)
			array_push($retvar_list, array('retval' => $retval[$i]));
		$tmpl->setloop('dirtree_retvar_list', $retvar_list);
	}
}

/**
 * set vars for form of move-settings
 */
function tmplSetMoveSettings() {
	global $cfg, $tmpl;
	if ((isset($cfg["move_paths"])) && (strlen($cfg["move_paths"]) > 0)) {
		$dirs = split(":", trim($cfg["move_paths"]));
		$dir_list = array();
		foreach ($dirs as $dir) {
			$target = trim($dir);
			if ((strlen($target) > 0) && ((substr($target, 0, 1)) != ";"))
				array_push($dir_list, array('target' => $target));
		}
		$tmpl->setloop('moveSettings_move_list', $dir_list);
	}
	$tmpl->setvar('moveSettings_move_paths', $cfg["move_paths"]);
}

/**
 * perform Authentication
 *
 * @param $username
 * @param $password
 * @param $md5password
 * @return int with :
 *                     1 : user authenticated
 *                     0 : user not authenticated
 */
function performAuthentication($username = '', $password = '', $md5password = '') {
	global $cfg, $db;
	// check username
	if (!isset($username))
		return 0;
	if ($username == '')
		return 0;
	// sql-state
	$sql = "SELECT uid, hits, hide_offline, theme, language_file FROM tf_users WHERE state = 1 AND user_id=".$db->qstr($username)." AND password=";
	if ((isset($md5password)) && (strlen($md5password) == 32)) /* md5-password */
		$sql .= $db->qstr($md5password);
	elseif (isset($password)) /* plaintext-password */
		$sql .= $db->qstr(md5($password));
	else /* no password */
		return 0;
	// exec query
	$result = $db->Execute($sql);
	dbDieOnError($sql);
	list($uid, $hits, $cfg["hide_offline"], $cfg["theme"], $cfg["language_file"]) = $result->FetchRow();
	if ($result->RecordCount() == 1) { // suc. auth.
		// Add a hit to the user
		$hits++;
		$sql = 'select * from tf_users where uid = '.$uid;
		$rs = $db->Execute($sql);
		dbDieOnError($sql);
		$rec = array(
						'hits'=>$hits,
						'last_visit'=>$db->DBDate(time()),
						'theme'=>$cfg['theme'],
						'language_file'=>$cfg['language_file']
					);
		$sql = $db->GetUpdateSQL($rs, $rec);
		$result = $db->Execute($sql);
		dbDieOnError($sql);
		$_SESSION['user'] = $username;
		$_SESSION['uid'] = $uid;
		$cfg["user"] = $_SESSION['user'];
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
 * get image-code
 *
 * @param $rstr
 * @param $rnd
 * @return string
 */
function loginImageCode($rstr, $rnd) {
    return substr((hexdec(md5($_SERVER['HTTP_USER_AGENT'].$rstr.$rnd.date("F j")))), 3, 6);
}

/**
 * first Login
 *
 * @param $username
 * @param $password
 */
function firstLogin($username = '', $password = '') {
	global $cfg, $db;
	if (!isset($username))
		return 0;
	if (!isset($password))
		return 0;
	if ($username == '')
		return 0;
	if ($password == '')
		return 0;
	$create_time = time();
	// This user is first in DB.  Make them super admin.
	// this is The Super USER, add them to the user table
	$record = array(
					'user_id'=>strtolower($username),
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
	dbDieOnError($sql);
	// Test and setup some paths for the TF settings
	// path
	$tfPath = $cfg["path"];
	if (!is_dir($cfg["path"]))
		$tfPath = getcwd() . "/downloads/";
	// settings
	$settings = array(
						"path" => $tfPath,
						"pythonCmd" => $cfg["pythonCmd"],
						"perlCmd" => $cfg["perlCmd"],
						"bin_php" => $cfg["bin_php"],
						"bin_grep" => $cfg["bin_grep"],
						"bin_awk" => $cfg["bin_awk"],
						"bin_du" => $cfg["bin_du"],
						"bin_wget" => $cfg["bin_wget"],
						"bin_unrar" => $cfg["bin_unrar"],
						"bin_unzip" => $cfg["bin_unzip"],
						"bin_cksfv" => $cfg["bin_cksfv"],
						"bin_vlc" => $cfg["bin_vlc"],
						"btclient_transmission_bin" => $cfg["btclient_transmission_bin"],
						"bin_netstat" => $cfg["bin_netstat"],
						"bin_sockstat" => $cfg["bin_sockstat"]
					);
	// binaries to test
	$binaries = array(
						"pythonCmd" => $cfg["pythonCmd"],
						"perlCmd" => $cfg["perlCmd"],
						"bin_php" => $cfg["bin_php"],
						"bin_grep" => $cfg["bin_grep"],
						"bin_awk" => $cfg["bin_awk"],
						"bin_du" => $cfg["bin_du"],
						"bin_wget" => $cfg["bin_wget"],
						"bin_unrar" => $cfg["bin_unrar"],
						"bin_unzip" => $cfg["bin_unzip"],
						"bin_cksfv" => $cfg["bin_cksfv"],
						"bin_vlc" => $cfg["bin_vlc"],
						"btclient_transmission_bin" => $cfg["btclient_transmission_bin"],
						"bin_netstat" => $cfg["bin_netstat"],
						"bin_sockstat" => $cfg["bin_sockstat"]
					);
	// bins for which
	$bins = array(
						"pythonCmd" => "python",
						"perlCmd" => "perl",
						"bin_php" => "php",
						"bin_grep" => "grep",
						"bin_awk" => "awk",
						"bin_du" => "du",
						"bin_wget" => "wget",
						"bin_unrar" => "unrar",
						"bin_unzip" => "unzip",
						"bin_cksfv" => "cksfv",
						"bin_vlc" => "vlc",
						"btclient_transmission_bin" => "transmissioncli",
						"bin_netstat" => "netstat",
						"bin_sockstat" => "sockstat"
					);
	// check
	foreach ($binaries as $key => $value) {
		if (!is_file($value)) {
			$bin = "";
			$bin = @trim(shell_exec("which ".$bins[$key]));
			if ($bin != "")
				$settings[$key] = $bin;
		}
	}
	// save
	saveSettings('tf_settings', $settings);
	AuditAction($cfg["constants"]["update"], "Initial Settings Updated for first login.");
}

/**
 * checks main-directories.
 *
 * @return boolean
 */
function checkMainDirectories() {
	global $cfg;
	// main-path
	if (!(@is_dir($cfg["path"]) === true)) {
		// dir doesnt exist, try to create
		if (!((@mkdir($cfg["path"], 0777)) === true))
			showErrorPage("Main-Path <em>".$cfg["path"]."</em> does not exist and cant be created.");
	}
	if (!(@is_writable($cfg["path"]) === true))
		showErrorPage("Main-Path <em>".$cfg["path"]."</em> is not writable.");
	// transfer-file-path
	if (!(@is_dir($cfg["transfer_file_path"]) === true)) {
		// dir doesnt exist, try to create
		if (!((@mkdir($cfg["transfer_file_path"], 0777)) === true))
			showErrorPage("Transfer-File-Path <em>".$cfg["transfer_file_path"]."</em> does not exist and cant be created.");
	}
	if (!(@is_writable($cfg["transfer_file_path"]) === true))
		showErrorPage("Transfer-File-Path <em>".$cfg["transfer_file_path"]."</em> is not writable.");
}

/**
 * process post-params on config-update and init settings-array
 *
 * @param $updateIndexSettings
 * @param $updateGoodlookinSettings
 * @return array with settings
 */
function processSettingsParams($updateIndexSettings = true, $updateGoodlookinSettings = true) {
	// move
	if (isset($_POST['categorylist']))
		unset($_POST['categorylist']);
	if (isset($_POST['category']))
		unset($_POST['category']);
	// res-dir
	if (isset($_POST['resdirlist']))
		unset($_POST['resdirlist']);
	if (isset($_POST['resdirentry']))
		unset($_POST['resdirentry']);
	// fluxd : watch
	if (isset($_POST['watch_jobs']))
		unset($_POST['watch_jobs']);
	if (isset($_POST['watch_user']))
		unset($_POST['watch_user']);
	if (isset($_POST['watch_dir']))
		unset($_POST['watch_dir']);
	if (isset($_POST['checkdir'])) {
		$doCheckdir = ($_POST['checkdir'] == "true") ? true : false;
		unset($_POST['checkdir']);
	} else {
		$doCheckdir = false;
	}
	// init settings array from params
	// process and handle all specials and exceptions while doing this.
	$settings = array();
	// index-page
	if ($updateIndexSettings) {
		$indexPageSettingsPrefix = "index_page_settings_";
		$indexPageSettingsPrefixLen = strlen($indexPageSettingsPrefix);
		$settingsIndexPageAry = array();
		for ($j = 0; $j <= 11; $j++)
			$settingsIndexPageAry[$j] = 0;
	}
	// good-look-stats
	if ($updateGoodlookinSettings) {
		$hackStatsPrefix = "hack_goodlookstats_settings_";
		$hackStatsStringLen = strlen($hackStatsPrefix);
		$settingsHackAry = array();
		for ($i = 0; $i <= 5; $i++)
			$settingsHackAry[$i] = 0;
	}
	//
	foreach ($_POST as $key => $value) {
		if (($updateIndexSettings) && ((substr($key, 0, $hackStatsStringLen)) == $hackStatsPrefix)) {
			// good-look-stats
			$idx = (int) substr($key, -1, 1);
			$settingsHackAry[$idx] = ($value != "0") ? 1 : 0;
		} else if (($updateGoodlookinSettings) && ((substr($key, 0, $indexPageSettingsPrefixLen)) == $indexPageSettingsPrefix)) {
			// index-page
			$idx = (int) substr($key, ($indexPageSettingsPrefixLen - (strlen($key))));
			$settingsIndexPageAry[$idx] = ($value != "0") ? 1 : 0;
		} else {
			switch ($key) {
				case "path": // tf-path
					$settings[$key] = trim(checkDirPathString($value));
					break;
				case "docroot": // tf-docroot
					$settings[$key] = trim(checkDirPathString($value));
					break;
				case "move_paths": // move-hack-paths
					if (strlen($value) > 0) {
						$val = "";
						$dirAry = explode(":",$value);
						for ($idx = 0; $idx < count($dirAry); $idx++) {
							if ($idx > 0)
								$val .= ':';
							$val .= trim(checkDirPathString($dirAry[$idx]));
						}
						$settings[$key] = trim($val);
					} else {
						$settings[$key] = "";
					}
					break;
				case "fluxd_Watch_jobs": // watch-jobs
					if (strlen($value) > 0) {
						$val = "";
						$jobs = explode(";", $value);
						$idx = 0;
						foreach ($jobs as $job) {
							$jobAry = explode(":", trim($job));
							$user = trim(array_shift($jobAry));
							$dir = trim(array_shift($jobAry));
							$dir = trim(checkDirPathString($dir));
							if ($idx > 0)
								$val .= ';';
							$val .= $user;
							$val .= ':';
							$val .= $dir;
							$idx++;
							if ($doCheckdir)
								checkDirectory($dir);
						}
						$settings[$key] = trim($val);
					} else {
						$settings[$key] = "";
					}
					break;
				default: // "normal" key-val-pair
					$settings[$key] = $value;
			}
		}
	}
	// index-page
	if ($updateIndexSettings)
		$settings['index_page_settings'] = convertArrayToInteger($settingsIndexPageAry);
	// good-look-stats
	if ($updateGoodlookinSettings)
		$settings['hack_goodlookstats_settings'] = convertArrayToByte($settingsHackAry);
	// return
	return $settings;
}

/**
 * load Settings
 *
 * @param $dbTable
 */
function loadSettings($dbTable) {
    global $cfg, $db;
    // pull the config params out of the db
    $sql = "SELECT tf_key, tf_value FROM ".$dbTable;
    $recordset = $db->Execute($sql);
    dbDieOnError($sql);
    while(list($key, $value) = $recordset->FetchRow()) {
		$tmpValue = '';
		if (strpos($key,"Filter") > 0)
			$tmpValue = unserialize($value);
		elseif ($key == 'searchEngineLinks')
			$tmpValue = unserialize($value);
		if (is_array($tmpValue))
			$value = $tmpValue;
		$cfg[$key] = $value;
    }
}

/**
 * insert Setting
 *
 * @param $dbTable
 * @param $key
 * @param $value
 */
function insertSetting($dbTable, $key, $value) {
    global $cfg, $db;
	// flush session-cache
	cacheFlush();
    $update_value = (is_array($value)) ? serialize($value) : $value;
    $sql = "INSERT INTO ".$dbTable." VALUES ('".$key."', '".$update_value."')";
	$db->Execute($sql);
	dbDieOnError($sql);
	// update the Config.
	$cfg[$key] = $value;
}

/**
 * updateSetting
 *
 * @param $dbTable
 * @param $key
 * @param $value
 */
function updateSetting($dbTable, $key, $value) {
    global $cfg, $db;
	// flush session-cache
	cacheFlush();
    $update_value = (is_array($value)) ? serialize($value) : $value;
    $sql = "UPDATE ".$dbTable." SET tf_value = '".$update_value."' WHERE tf_key = '".$key."'";
    $db->Execute($sql);
    dbDieOnError($sql);
    // update the Config.
    $cfg[$key] = $value;
}

/**
 * save Settings
 *
 * @param $dbTable
 * @param $settings
 */
function saveSettings($dbTable, $settings) {
    global $cfg, $db;
    foreach ($settings as $key => $value) {
        if (array_key_exists($key, $cfg)) {
            if (is_array($cfg[$key]) || is_array($value)) {
                if (serialize($cfg[$key]) != serialize($value))
                    updateSetting($dbTable, $key, $value);
            } elseif ($cfg[$key] != $value) {
                updateSetting($dbTable, $key, $value);
            }
        } else {
            insertSetting($dbTable, $key, $value);
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
	if (!isset($uid))
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
	if (!isset($uid))
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
	cacheFlush($cfg["user"]);
	$sql = "INSERT INTO tf_settings_user VALUES ('".$uid."', '".$key."', '".$update_value."')";
	$result = $db->Execute($sql);
	dbDieOnError($sql);
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
	if (!isset($uid))
		return false;
	global $db;
	// flush session-cache
	cacheFlush($cfg["user"]);
	$sql = "DELETE FROM tf_settings_user WHERE uid = '".$uid."'";
	$db->Execute($sql);
	dbDieOnError($sql);
	return true;
}

/*
 * Function to load the settings for a user to global cfg-array
 *
 * @param $uid uid of the user
 * @return boolean
 */
function loadUserSettingsToConfig($uid) {
	if (!isset($uid))
		return false;
	global $cfg, $db;
	// get user-settings from db and set in global cfg-array
	$sql = "SELECT tf_key, tf_value FROM tf_settings_user WHERE uid = '".$uid."'";
	$recordset = $db->Execute($sql);
	dbDieOnError($sql);
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
	dbDieOnError($sql);
}

/**
 * UpdateUserProfile
 *
 * @param $user_id
 * @param $pass1
 * @param $hideOffline
 * @param $theme
 * @param $language
 */
function UpdateUserProfile($user_id, $pass1, $hideOffline, $theme, $language) {
	global $cfg, $db;
	if (empty($hideOffline) || $hideOffline == "" || !isset($hideOffline))
		$hideOffline = "0";
	// update values
	$rec = array();
	if ($pass1 != "") {
		$rec['password'] = md5($pass1);
		AuditAction($cfg["constants"]["update"], $cfg['_PASSWORD']);
	}
	$sql = 'select * from tf_users where user_id = '.$db->qstr($user_id);
	$rs = $db->Execute($sql);
	dbDieOnError($sql);
	$rec['hide_offline'] = $hideOffline;
	$rec['theme'] = $theme;
	$rec['language_file'] = $language;
	$sql = $db->GetUpdateSQL($rs, $rec);
	if ($sql != "") {
		$result = $db->Execute($sql);
		dbDieOnError($sql);
		// flush session-cache
		cacheFlush($cfg["user"]);
	}
}

/**
 * Delete Message
 *
 * @param $mid
 */
function DeleteMessage($mid) {
	global $cfg, $db;
	$sql = "delete from tf_messages where mid=".$mid." and to_user=".$db->qstr($cfg["user"]);
	$result = $db->Execute($sql);
	dbDieOnError($sql);
}

/**
 * Mark Message as Read
 *
 * @param  $mid
 */
function MarkMessageRead($mid) {
	global $cfg, $db;
	$sql = 'select * from tf_messages where mid = '.$mid;
	$rs = $db->Execute($sql);
	dbDieOnError($sql);
	$rec = array('IsNew'=>0, 'force_read'=>0);
	$sql = $db->GetUpdateSQL($rs, $rec);
	$db->Execute($sql);
	dbDieOnError($sql);
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
	if ($to_all == 1) {
		$message .= "\n\n__________________________________\n*** ".$cfg['_MESSAGETOALL']." ***";
		$sql = 'select user_id from tf_users';
		$result = $db->Execute($sql);
		dbDieOnError($sql);
		while ($row = $result->FetchRow()) {
			$rec = array(
						'to_user' => strtolower($row['user_id']),
						'from_user' => strtolower($from_user),
						'message' => $message,
						'IsNew' => 1,
						'ip' => $cfg['ip'],
						'time' => $create_time,
						'force_read' => $force_read
						);
			$sql = $db->GetInsertSql($sTable, $rec);
			$result2 = $db->Execute($sql);
			dbDieOnError($sql);
		}
	} else {
		// Only Send to one Person
		$rec = array(
					'to_user' => strtolower($to_user),
					'from_user' => strtolower($from_user),
					'message' => $message,
					'IsNew' => 1,
					'ip' => $cfg['ip'],
					'time' => $create_time,
					'force_read' => $force_read
					);
		$sql = $db->GetInsertSql($sTable, $rec);
		$result = $db->Execute($sql);
		dbDieOnError($sql);
	}
}

/**
 * Get Message data in an array
 *
 * @param $mid
 * @return array
 */
function GetMessage($mid) {
	global $cfg, $db;
	$sql = "select from_user, message, ip, time, isnew, force_read from tf_messages where mid=".$mid." and to_user=".$db->qstr($cfg["user"]);
	$rtnValue = $db->GetRow($sql);
	dbDieOnError($sql);
	return $rtnValue;
}

/**
 * Get Themes data in an array
 *
 * @return array
 */
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

/**
 * Get Themes data in an array
 *
 * @return array
 */
function GetThemesStandard() {
	$arThemes = array();
	$dir = "themes/tf_standard_themes/";
	$handle = opendir($dir);
	while($entry = readdir($handle)) {
		if (is_dir($dir.$entry) && ($entry != "." && $entry != ".." && $entry != ".svn" && $entry != "CVS" && $entry != "css" && $entry != "tmpl" && $entry != "scripts" && $entry != "images"))
			array_push($arThemes, $entry);
	}
	closedir($handle);
	sort($arThemes);
	return $arThemes;
}

/**
 * Get Languages in an array
 *
 * @return array
 */
function GetLanguages() {
	$arLanguages = array();
	$dir = "inc/language/";
	$handle = opendir($dir);
	while($entry = readdir($handle)) {
		if (is_file($dir.$entry) && (strcmp(substr($entry, strlen($entry)-4, 4), ".php") == 0))
			array_push($arLanguages, $entry);
	}
	closedir($handle);
	sort($arLanguages);
	return $arLanguages;
}

/**
 * Get Language name from file name
 *
 * @param $inFile
 * @return string
 */
function GetLanguageFromFile($inFile) {
	$rtnValue = "";
	$rtnValue = str_replace("lang-", "", $inFile);
	$rtnValue = str_replace(".php", "", $rtnValue);
	return $rtnValue;
}

/**
 * loads a language-file and sets string-vars.
 *
 * @param $language
 */
function loadLanguageFile($language) {
	global $cfg;
	// load language
	require_once("inc/language/".$language);
	// set vars
	$cfg['_CHARSET'] = _CHARSET;
	$cfg['_SELECTFILE'] = _SELECTFILE;
	$cfg['_URLFILE'] = _URLFILE;
	$cfg['_UPLOAD'] = _UPLOAD;
	$cfg['_GETFILE'] = _GETFILE;
	$cfg['_TORRENTLINKS'] = _TORRENTLINKS;
	$cfg['_ONLINE'] = _ONLINE;
	$cfg['_OFFLINE'] = _OFFLINE;
	$cfg['_STORAGE'] = _STORAGE;
	$cfg['_DRIVESPACE'] = _DRIVESPACE;
	$cfg['_SERVERSTATS'] = _SERVERSTATS;
	$cfg['_DIRECTORYLIST'] = _DIRECTORYLIST;
	$cfg['_ALL'] = _ALL;
	$cfg['_PAGEWILLREFRESH'] = _PAGEWILLREFRESH;
	$cfg['_SECONDS'] = _SECONDS;
	$cfg['_TURNONREFRESH'] = _TURNONREFRESH;
	$cfg['_TURNOFFREFRESH'] = _TURNOFFREFRESH;
	$cfg['_WARNING'] = _WARNING;
	$cfg['_DRIVESPACEUSED'] = _DRIVESPACEUSED;
	$cfg['_ADMINMESSAGE'] = _ADMINMESSAGE;
	$cfg['_TORRENTS'] = _TORRENTS;
	$cfg['_UPLOADHISTORY'] = _UPLOADHISTORY;
	$cfg['_MYPROFILE'] = _MYPROFILE;
	$cfg['_ADMINISTRATION'] = _ADMINISTRATION;
	$cfg['_SENDMESSAGETO'] = _SENDMESSAGETO;
	$cfg['_TRANSFERFILE'] = _TRANSFERFILE;
	$cfg['_FILESIZE'] = _FILESIZE;
	$cfg['_STATUS'] = _STATUS;
	$cfg['_ADMIN'] = _ADMIN;
	$cfg['_BADFILE'] = _BADFILE;
	$cfg['_DATETIMEFORMAT'] = _DATETIMEFORMAT;
	$cfg['_DATEFORMAT'] = _DATEFORMAT;
	$cfg['_ESTIMATEDTIME'] = _ESTIMATEDTIME;
	$cfg['_DOWNLOADSPEED'] = _DOWNLOADSPEED;
	$cfg['_UPLOADSPEED'] = _UPLOADSPEED;
	$cfg['_SHARING'] = _SHARING;
	$cfg['_USER'] = _USER;
	$cfg['_DONE'] = _DONE;
	$cfg['_INCOMPLETE'] = _INCOMPLETE;
	$cfg['_NEW'] = _NEW;
	$cfg['_TRANSFERDETAILS'] = _TRANSFERDETAILS;
	$cfg['_STOPTRANSFER'] = _STOPTRANSFER;
	$cfg['_RUNTRANSFER'] = _RUNTRANSFER;
	$cfg['_SEEDTRANSFER'] = _SEEDTRANSFER;
	$cfg['_DELETE'] = _DELETE;
	$cfg['_ABOUTTODELETE'] = _ABOUTTODELETE;
	$cfg['_NOTOWNER'] = _NOTOWNER;
	$cfg['_MESSAGETOALL'] = _MESSAGETOALL;
	$cfg['_TRYDIFFERENTUSERID'] = _TRYDIFFERENTUSERID;
	$cfg['_HASBEENUSED'] = _HASBEENUSED;
	$cfg['_RETURNTOEDIT'] = _RETURNTOEDIT;
	$cfg['_ADMINUSERACTIVITY'] = _ADMINUSERACTIVITY;
	$cfg['_ADMIN_MENU'] = _ADMIN_MENU;
	$cfg['_ACTIVITY_MENU'] = _ACTIVITY_MENU;
	$cfg['_LINKS_MENU'] = _LINKS_MENU;
	$cfg['_NEWUSER_MENU'] = _NEWUSER_MENU;
	$cfg['_BACKUP_MENU'] = _BACKUP_MENU;
	$cfg['_ALLUSERS'] = _ALLUSERS;
	$cfg['_NORECORDSFOUND'] = _NORECORDSFOUND;
	$cfg['_SHOWPREVIOUS'] = _SHOWPREVIOUS;
	$cfg['_SHOWMORE'] = _SHOWMORE;
	$cfg['_ACTIVITYSEARCH'] = _ACTIVITYSEARCH;
	$cfg['_FILE'] = _FILE;
	$cfg['_ACTION'] = _ACTION;
	$cfg['_SEARCH'] = _SEARCH;
	$cfg['_ACTIVITYLOG'] = _ACTIVITYLOG;
	$cfg['_DAYS'] = _DAYS;
	$cfg['_IP'] = _IP;
	$cfg['_TIMESTAMP'] = _TIMESTAMP;
	$cfg['_USERDETAILS'] = _USERDETAILS;
	$cfg['_HITS'] = _HITS;
	$cfg['_UPLOADACTIVITY'] = _UPLOADACTIVITY;
	$cfg['_JOINED'] = _JOINED;
	$cfg['_LASTVISIT'] = _LASTVISIT;
	$cfg['_USERSACTIVITY'] = _USERSACTIVITY;
	$cfg['_NORMALUSER'] = _NORMALUSER;
	$cfg['_ADMINISTRATOR'] = _ADMINISTRATOR;
	$cfg['_SUPERADMIN'] = _SUPERADMIN;
	$cfg['_EDIT'] = _EDIT;
	$cfg['_USERADMIN'] = _USERADMIN;
	$cfg['_EDITUSER'] = _EDITUSER;
	$cfg['_UPLOADPARTICIPATION'] = _UPLOADPARTICIPATION;
	$cfg['_UPLOADS'] = _UPLOADS;
	$cfg['_PERCENTPARTICIPATION'] = _PERCENTPARTICIPATION;
	$cfg['_PARTICIPATIONSTATEMENT'] = _PARTICIPATIONSTATEMENT;
	$cfg['_TOTALPAGEVIEWS'] = _TOTALPAGEVIEWS;
	$cfg['_THEME'] = _THEME;
	$cfg['_USERTYPE'] = _USERTYPE;
	$cfg['_NEWPASSWORD'] = _NEWPASSWORD;
	$cfg['_CONFIRMPASSWORD'] = _CONFIRMPASSWORD;
	$cfg['_HIDEOFFLINEUSERS'] = _HIDEOFFLINEUSERS;
	$cfg['_UPDATE'] = _UPDATE;
	$cfg['_USERIDREQUIRED'] = _USERIDREQUIRED;
	$cfg['_PASSWORDLENGTH'] = _PASSWORDLENGTH;
	$cfg['_PASSWORDNOTMATCH'] = _PASSWORDNOTMATCH;
	$cfg['_PLEASECHECKFOLLOWING'] = _PLEASECHECKFOLLOWING;
	$cfg['_NEWUSER'] = _NEWUSER;
	$cfg['_PASSWORD'] = _PASSWORD;
	$cfg['_CREATE'] = _CREATE;
	$cfg['_ADMINEDITLINKS'] = _ADMINEDITLINKS;
	$cfg['_FULLURLLINK'] = _FULLURLLINK;
	$cfg['_BACKTOPARRENT'] = _BACKTOPARRENT;
	$cfg['_DOWNLOADDETAILS'] = _DOWNLOADDETAILS;
	$cfg['_PERCENTDONE'] = _PERCENTDONE;
	$cfg['_RETURNTOTRANSFERS'] = _RETURNTOTRANSFERS;
	$cfg['_DATE'] = _DATE;
	$cfg['_WROTE'] = _WROTE;
	$cfg['_SENDMESSAGETITLE'] = _SENDMESSAGETITLE;
	$cfg['_TO'] = _TO;
	$cfg['_FROM'] = _FROM;
	$cfg['_YOURMESSAGE'] = _YOURMESSAGE;
	$cfg['_SENDTOALLUSERS'] = _SENDTOALLUSERS;
	$cfg['_FORCEUSERSTOREAD'] = _FORCEUSERSTOREAD;
	$cfg['_SEND'] = _SEND;
	$cfg['_PROFILE'] = _PROFILE;
	$cfg['_PROFILEUPDATEDFOR'] = _PROFILEUPDATEDFOR;
	$cfg['_REPLY'] = _REPLY;
	$cfg['_MESSAGE'] = _MESSAGE;
	$cfg['_MESSAGES'] = _MESSAGES;
	$cfg['_RETURNTOMESSAGES'] = _RETURNTOMESSAGES;
	$cfg['_COMPOSE'] = _COMPOSE;
	$cfg['_LANGUAGE'] = _LANGUAGE;
	$cfg['_CURRENTDOWNLOAD'] = _CURRENTDOWNLOAD;
	$cfg['_CURRENTUPLOAD'] = _CURRENTUPLOAD;
	$cfg['_SERVERLOAD'] = _SERVERLOAD;
	$cfg['_FREESPACE'] = _FREESPACE;
	$cfg['_UPLOADED'] = _UPLOADED;
	$cfg['_QMANAGER_MENU'] = _QMANAGER_MENU;
	$cfg['_FLUXD_MENU'] = _FLUXD_MENU;
	$cfg['_SETTINGS_MENU'] = _SETTINGS_MENU;
	$cfg['_SEARCHSETTINGS_MENU'] = _SEARCHSETTINGS_MENU;
	$cfg['_ERRORSREPORTED'] = _ERRORSREPORTED;
	$cfg['_STARTED'] = _STARTED;
	$cfg['_ENDED'] = _ENDED;
	$cfg['_QUEUED'] = _QUEUED;
	$cfg['_DELQUEUE'] = _DELQUEUE;
	$cfg['_FORCESTOP'] = _FORCESTOP;
	$cfg['_STOPPING'] = _STOPPING;
	$cfg['_COOKIE_MENU'] = _COOKIE_MENU;
	$cfg['_TOTALXFER'] = _TOTALXFER;
	$cfg['_MONTHXFER'] = _MONTHXFER;
	$cfg['_WEEKXFER'] = _WEEKXFER;
	$cfg['_DAYXFER'] = _DAYXFER;
	$cfg['_XFERTHRU'] = _XFERTHRU;
	$cfg['_REMAINING'] = _REMAINING;
	$cfg['_TOTALSPEED'] = _TOTALSPEED;
	$cfg['_SERVERXFERSTATS'] = _SERVERXFERSTATS;
	$cfg['_YOURXFERSTATS'] = _YOURXFERSTATS;
	$cfg['_OTHERSERVERSTATS'] = _OTHERSERVERSTATS;
	$cfg['_TOTAL'] = _TOTAL;
	$cfg['_DOWNLOAD'] = _DOWNLOAD;
	$cfg['_MONTHSTARTING'] = _MONTHSTARTING;
	$cfg['_WEEKSTARTING'] = _WEEKSTARTING;
	$cfg['_DAY'] = _DAY;
	$cfg['_XFER'] = _XFER;
	$cfg['_XFER_USAGE'] = _XFER_USAGE;
	$cfg['_QUEUEMANAGER'] = _QUEUEMANAGER;
	$cfg['_MULTIPLE_UPLOAD'] = _MULTIPLE_UPLOAD;
	$cfg['_TDDU'] = _TDDU;
	$cfg['_FULLSITENAME'] = _FULLSITENAME;
	$cfg['_MOVE_STRING'] = _MOVE_STRING;
	$cfg['_DIR_MOVE_LINK'] = _DIR_MOVE_LINK;
	$cfg['_MOVE_FILE'] = _MOVE_FILE;
	$cfg['_MOVE_FILE_TITLE'] = _MOVE_FILE_TITLE;
	$cfg['_REN_STRING'] = _REN_STRING;
	$cfg['_DIR_REN_LINK'] = _DIR_REN_LINK;
	$cfg['_REN_FILE'] = _REN_FILE;
	$cfg['_REN_DONE'] = _REN_DONE;
	$cfg['_REN_ERROR'] = _REN_ERROR;
	$cfg['_REN_ERR_ARG'] = _REN_ERR_ARG;
	$cfg['_REN_TITLE'] = _REN_TITLE;
	$cfg['_ID_PORT'] = _ID_PORT;
	$cfg['_ID_PORTS'] = _ID_PORTS;
	$cfg['_ID_CONNECTIONS'] = _ID_CONNECTIONS;
	$cfg['_ID_HOST'] = _ID_HOST;
	$cfg['_ID_HOSTS'] = _ID_HOSTS;
	$cfg['_ID_MRTG'] = _ID_MRTG;
}

/**
 * get cookie
 *
 * @param $cid
 * @return string
 */
function getCookie($cid) {
	global $cfg, $db;
	$rtnValue = "";
	$sql = "SELECT host, data FROM tf_cookies WHERE cid=".$cid;
	$rtnValue = $db->GetAll($sql);
	return $rtnValue[0];
}

/**
 * Delete Cookie Host Information
 *
 * @param $cid
 */
function deleteCookieInfo($cid) {
	global $db;
	$sql = "delete from tf_cookies where cid=".$cid;
	$result = $db->Execute($sql);
	dbDieOnError($sql);
}

/**
 * Add New Cookie Host Information
 *
 * @param $newCookie
 */
function addCookieInfo( $newCookie ) {
	global $db, $cfg;
	// Get uid of user
	$sql = "SELECT uid FROM tf_users WHERE user_id = '" . $cfg["user"] . "'";
	$uid = $db->GetOne( $sql );
	$sql = "INSERT INTO tf_cookies ( cid, uid, host, data ) VALUES ( '', '" . $uid . "', '" . $newCookie["host"] . "', '" . $newCookie["data"] . "' )";
	$db->Execute( $sql );
	dbDieOnError($sql);
}

/**
 * Modify Cookie Host Information
 *
 * @param $cid
 * @param $newCookie
 */
function modCookieInfo($cid, $newCookie) {
	global $db;
	$sql = "UPDATE tf_cookies SET host='" . $newCookie["host"] . "', data='" . $newCookie["data"] . "' WHERE cid='" . $cid . "'";
	$db->Execute($sql);
	dbDieOnError($sql);
}

/**
 * GetActivityCount
 *
 * @param $user
 * @return int
 */
function GetActivityCount($user="") {
	global $cfg, $db;
	$count = 0;
	$for_user = ($user != "") ? "user_id=".$db->qstr($user)." AND " : "";
	$sql = "SELECT count(*) FROM tf_log WHERE ".$for_user."(action=".$db->qstr($cfg["constants"]["file_upload"])." OR action=".$db->qstr($cfg["constants"]["url_upload"]).")";
	$count = $db->GetOne($sql);
	return $count;
}

/**
 * This method Gets Download profiles for the actual user
 *
 * @param $user
 * @param $profile
 * @return array
 */
function GetProfiles($user, $profile) {
	global $cfg, $db;
	$profiles_array = array();
	$sql = "SELECT name FROM tf_trprofiles WHERE owner LIKE '".$user."' AND public='0'";
	$rs = $db->GetCol($sql);
	if ($rs) {
		foreach($rs as $arr) {
			array_push($profiles_array, array(
				'name' => $arr,
				'is_selected' => ($arr == $profile) ? 1 : 0
				)
			);
		}
	}
	dbDieOnError($sql);
	return $profiles_array;
}

/**
 * This method Gets public Download profiles
 *
 * @param $profile
 * @return array
 */
function GetPublicProfiles($profile) {
	global $cfg, $db;
	$profiles_array = array();
	$sql = "SELECT name FROM tf_trprofiles WHERE public= '1'";
	$rs = $db->GetCol($sql);
	if ($rs) {
		foreach($rs as $arr) {
			array_push($profiles_array, array(
				'name' => $arr,
				'is_selected' => ($arr == $profile) ? 1 : 0
				)
			);
		}
	}
	dbDieOnError($sql);
	return $profiles_array;
}

/**
 * This method fetch settings for an specific profile
 *
 * @param $profile
 * @return array
 */
function GetProfileSettings($profile) {
	global $cfg, $db;
	$sql = "SELECT minport, maxport, maxcons, rerequest, rate, maxuploads, drate, runtime, sharekill, superseeder from tf_trprofiles where name like '".$profile."'";
	$settings = $db->GetRow($sql);
	dbDieOnError($sql);
	return $settings;
}

/**
 * Add New Profile Information
 *
 * @param $newProfile
 */
function AddProfileInfo( $newProfile ) {
	global $db, $cfg;
	$sql = 'INSERT INTO tf_trprofiles ( name , owner , minport , maxport , maxcons , rerequest , rate , maxuploads , drate , runtime , sharekill , superseeder , public )'." VALUES ('".$newProfile["name"]."', '".$cfg['uid']."', '".$newProfile["minport"]."', '".$newProfile["maxport"]."', '".$newProfile["maxcons"]."', '".$newProfile["rerequest"]."', '".$newProfile["rate"]."', '".$newProfile["maxuploads"]."', '".$newProfile["drate"]."', '".$newProfile["runtime"]."', '".$newProfile["sharekill"]."', '".$newProfile["superseeder"]."', '".$newProfile["public"]."')";
	$db->Execute( $sql );
	dbDieOnError($sql);
}

/**
 * getProfile
 *
 * @param $pid
 * @return
 */
function getProfile($pid) {
	global $cfg, $db;
	$rtnValue = "";
	$sql = "SELECT id , name , minport , maxport , maxcons , rerequest , rate , maxuploads , drate , runtime , sharekill , superseeder , public FROM tf_trprofiles WHERE id LIKE '".$pid."'";
	$rtnValue = $db->GetAll($sql);
	return $rtnValue[0];
}

/**
 * Modify Profile Information
 *
 * @param $pid
 * @param $newProfile
 */
function modProfileInfo($pid, $newProfile) {
	global $cfg, $db;
	$sql = "UPDATE tf_trprofiles SET owner = '".$cfg['uid']."', name = '".$newProfile["name"]."', minport = '".$newProfile["minport"]."', maxport = '".$newProfile["maxport"]."', maxcons = '".$newProfile["maxcons"]."', rerequest = '".$newProfile["rerequest"]."', rate = '".$newProfile["rate"]."', maxuploads = '".$newProfile["maxuploads"]."', drate = '".$newProfile["drate"]."', runtime = '".$newProfile["runtime"]."', sharekill = '".$newProfile["sharekill"]."', superseeder = '".$newProfile["superseeder"]."', public = '".$newProfile["public"]."' WHERE id = '".$pid."'";
	$db->Execute($sql);
	dbDieOnError($sql);
}

/**
 * Delete Profile Information
 *
 * @param $pid
 */
function deleteProfileInfo($pid) {
	global $db;
	$sql = "DELETE FROM tf_trprofiles WHERE id=".$pid;
	$result = $db->Execute($sql);
	dbDieOnError($sql);
}

?>