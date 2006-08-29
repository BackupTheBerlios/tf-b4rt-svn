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
 * getCredentials
 *
 * @return array with credentials or null if no credentials found.
 */
function getCredentials() {
	global $cfg, $db;
	$retVal = array();

	// check for basic-auth-supplied credentials (only if activated or there may
	// be wrong credentials fetched)
	if (($cfg['auth_type'] == 2) || ($cfg['auth_type'] == 3)) {
		if ((isset($_SERVER['PHP_AUTH_USER'])) && (isset($_SERVER['PHP_AUTH_PW']))) {
			$retVal['username'] = strtolower($_SERVER['PHP_AUTH_USER']);
			$retVal['password'] = addslashes($_SERVER['PHP_AUTH_PW']);
			return $retVal;
		}
	}

	// check for http-post/get-supplied credentials
	if ((isset($_REQUEST['username'])) && (isset($_REQUEST['iamhim']))) {
		$retVal['username'] = strtolower($_REQUEST['username']);
		$retVal['password'] = addslashes($_REQUEST['iamhim']);
		return $retVal;
	}

	// check for cookie-supplied credentials (only if activated)
	if ($cfg['auth_type'] == 1) {
		if ((isset($HTTP_COOKIE_VARS['username'])) && (isset($HTTP_COOKIE_VARS['iamhim']))) {
			$retVal['username'] = strtolower($HTTP_COOKIE_VARS['username']);
			$retVal['password'] = addslashes($HTTP_COOKIE_VARS['iamhim']);
			return $retVal;
		}
	}

	// no credentials found, return null
	return null;
}

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
 * check if user authenticated
 *
 * @return int with :
 *                     1 : user authenticated
 *                     0 : user not authenticated
 */
function isAuthenticated() {
	global $cfg, $db;
	$create_time = time();
	if(!isset($_SESSION['user'])) {
		return 0;
	}
	if ($_SESSION['user'] == md5($cfg["pagetitle"])) {
		// user changed password and needs to login again
		return 0;
	}
	$sql = "SELECT uid, hits, hide_offline, theme, language_file FROM tf_users WHERE user_id=".$db->qstr($cfg['user']);
	$recordset = $db->Execute($sql);
	showError($db, $sql);
	if($recordset->RecordCount() != 1) {
		AuditAction($cfg["constants"]["error"], "FAILED AUTH: ".$cfg['user']);
		@session_destroy();
		return 0;
	}
	list($uid, $hits, $cfg["hide_offline"], $cfg["theme"], $cfg["language_file"]) = $recordset->FetchRow();
	// hold the uid in cfg-array
	$cfg["uid"] = $uid;
	// Check for valid theme
	if (!ereg('^[^./][^/]*$', $cfg["theme"]) && strpos($cfg["theme"], "tf_standard_themes")) {
		AuditAction($cfg["constants"]["error"], "THEME VARIABLE CHANGE ATTEMPT: ".$cfg["theme"]." from ".$cfg['user']);
		$cfg["theme"] = $cfg["default_theme"];
	}
	// Check for valid language file
	if(!ereg('^[^./][^/]*$', $cfg["language_file"])) {
		AuditAction($cfg["constants"]["error"], "LANGUAGE VARIABLE CHANGE ATTEMPT: ".$cfg["language_file"]." from ".$cfg['user']);
		$cfg["language_file"] = $cfg["default_language"];
	}
	if (!is_dir("themes/".$cfg["theme"]))
		$cfg["theme"] = $cfg["default_theme"];
	// Check for valid language file
	if (!is_file("inc/language/".$cfg["language_file"]))
		$cfg["language_file"] = $cfg["default_language"];
	$hits++;
	$sql = 'select * from tf_users where uid = '.$uid;
	$rs = $db->Execute($sql);
	showError($db, $sql);
	$rec = array(
					'hits' => $hits,
					'last_visit' => $create_time,
					'theme' => $cfg['theme'],
					'language_file' => $cfg['language_file']
				);
	$sql = $db->GetUpdateSQL($rs, $rec);
	$result = $db->Execute($sql);
	showError($db,$sql);
	return 1;
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

/* ************************************************************************** */

/*
 * netstatConnectionsSum
 */
function netstatConnectionsSum() {
	global $cfg;
	require_once("inc/classes/ClientHandler.php");
	// messy...
	$nCount = 0;
	switch (_OS) {
		case 1: // linux
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg,"tornado");
			$nCount += (int) trim(shell_exec($cfg['bin_netstat']." -e -p --tcp -n 2> /dev/null | ".$cfg['bin_grep']." -v root | ".$cfg['bin_grep']." -v 127.0.0.1 | ".$cfg['bin_grep']." -c ". $clientHandler->binSocket));
			unset($clientHandler);
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg,"transmission");
			$nCount += (int) trim(shell_exec($cfg['bin_netstat']." -e -p --tcp -n 2> /dev/null | ".$cfg['bin_grep']." -v root | ".$cfg['bin_grep']." -v 127.0.0.1 | ".$cfg['bin_grep']." -c ". $clientHandler->binSocket));
		break;
		case 2: // bsd
			$processUser = posix_getpwuid(posix_geteuid());
			$webserverUser = $processUser['name'];
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg,"tornado");
			$nCount += (int) trim(shell_exec($cfg['bin_sockstat']." | ".$cfg['bin_grep']." -cE ".$webserverUser.".+".$clientHandler->binSocket.".+tcp"));
			unset($clientHandler);
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg,"transmission");
			$nCount += (int) trim(shell_exec($cfg['bin_sockstat']." | ".$cfg['bin_grep']." -cE ".$webserverUser.".+".$clientHandler->binSocket.".+tcp"));
		break;
	}
	return $nCount;
}

/*
 * netstatConnections
 */
function netstatConnections($torrentAlias) {
	return netstatConnectionsByPid(getTorrentPid($torrentAlias));
}

/*
 * netstatConnectionsByPid
 */
function netstatConnectionsByPid($torrentPid) {
	global $cfg;
	switch (_OS) {
		case 1: // linux
			return trim(shell_exec($cfg['bin_netstat']." -e -p --tcp --numeric-hosts --numeric-ports 2> /dev/null | ".$cfg['bin_grep']." -v root | ".$cfg['bin_grep']." -v 127.0.0.1 | ".$cfg['bin_grep']." -c \"".$torrentPid ."/\""));
		break;
		case 2: // bsd
			$processUser = posix_getpwuid(posix_geteuid());
			$webserverUser = $processUser['name'];
			$netcon = (int) trim(shell_exec($cfg['bin_sockstat']." | ".$cfg['bin_grep']." -cE ".$webserverUser.".+".$torrentPid.".+tcp"));
			$netcon--;
			return $netcon;
		break;
	}
}

/*
 * netstatPortList
 */
function netstatPortList() {
	global $cfg;
	require_once("inc/classes/ClientHandler.php");
	// messy...
	$retStr = "";
	switch (_OS) {
		case 1: // linux
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg,"tornado");
			$retStr .= shell_exec($cfg['bin_netstat']." -e -l -p --tcp --numeric-hosts --numeric-ports 2> /dev/null | ".$cfg['bin_grep']." -v root | ".$cfg['bin_grep']." ". $clientHandler->binSocket ." | ".$cfg['bin_awk']." '{print \$4}' | ".$cfg['bin_awk']." 'BEGIN{FS=\":\"}{print \$2}'");
			unset($clientHandler);
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg,"transmission");
			$retStr .= shell_exec($cfg['bin_netstat']." -e -l -p --tcp --numeric-hosts --numeric-ports 2> /dev/null | ".$cfg['bin_grep']." -v root | ".$cfg['bin_grep']." ". $clientHandler->binSocket ." | ".$cfg['bin_awk']." '{print \$4}' | ".$cfg['bin_awk']." 'BEGIN{FS=\":\"}{print \$2}'");
		break;
		case 2: // bsd
			$processUser = posix_getpwuid(posix_geteuid());
			$webserverUser = $processUser['name'];
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg,"tornado");
			$retStr .= shell_exec($cfg['bin_sockstat']." | ".$cfg['bin_awk']." '/".substr($clientHandler->binSocket, 0, 9).".+tcp/ {split (\$6, a, \":\");print a[2]}'");
			unset($clientHandler);
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg,"transmission");
			$retStr .= shell_exec($cfg['bin_sockstat']." | ".$cfg['bin_awk']." '/".substr($clientHandler->binSocket, 0, 9).".+tcp/ {split (\$6, a, \":\");print a[2]}'");
		break;
	}
	return $retStr;
}

/*
 * netstatPort
 */
function netstatPort($torrentAlias) {
  return netstatPortByPid(getTorrentPid($torrentAlias));
}

/*
 * netstatPortByPid
 */
function netstatPortByPid($torrentPid) {
	global $cfg;
	switch (_OS) {
		case 1: // linux
			return trim(shell_exec($cfg['bin_netstat']." -l -e -p --tcp --numeric-hosts --numeric-ports 2> /dev/null | ".$cfg['bin_grep']." -v root | ".$cfg['bin_grep']." \"".$torrentPid ."/\" | ".$cfg['bin_awk']." '{print \$4}' | ".$cfg['bin_awk']." 'BEGIN{FS=\":\"}{print \$2}'"));
		break;
		case 2: // bsd
			$processUser = posix_getpwuid(posix_geteuid());
			$webserverUser = $processUser['name'];
			return (shell_exec($cfg['bin_sockstat']." | ".$cfg['bin_awk']." '/".$webserverUser.".*".$torrentPid.".*tcp.*\*:\*/ {split(\$6, a, \":\");print a[2]}'"));
		break;
	}
}

/*
 * netstatHostList
 */
function netstatHostList() {
	global $cfg;
	require_once("inc/classes/ClientHandler.php");
	// messy...
	$retStr = "";
	switch (_OS) {
		case 1: // linux
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg,"tornado");
			$retStr .= shell_exec($cfg['bin_netstat']." -e -p --tcp --numeric-hosts --numeric-ports 2> /dev/null | ".$cfg['bin_grep']." -v root | ".$cfg['bin_grep']." -v 127.0.0.1 | ".$cfg['bin_grep']." ". $clientHandler->binSocket ." | ".$cfg['bin_awk']." '{print \$5}'");
			unset($clientHandler);
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg,"transmission");
			$retStr .= shell_exec($cfg['bin_netstat']." -e -p --tcp --numeric-hosts --numeric-ports 2> /dev/null | ".$cfg['bin_grep']." -v root | ".$cfg['bin_grep']." -v 127.0.0.1 | ".$cfg['bin_grep']." ". $clientHandler->binSocket ." | ".$cfg['bin_awk']." '{print \$5}'");
		break;
		case 2: // bsd
			$processUser = posix_getpwuid(posix_geteuid());
			$webserverUser = $processUser['name'];
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg,"tornado");
			$retStr .= shell_exec($cfg['bin_sockstat']." | ".$cfg['bin_grep']." -E ".$webserverUser.".+".substr($clientHandler->binSocket, 0, 9).".+tcp.+[0-9]+\.[0-9]+\.[0-9]+\.[0-9]:[0-9].+[0-9]:[0-9]");
			unset($clientHandler);
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg,"transmission");
			$retStr .= shell_exec($cfg['bin_sockstat']." | ".$cfg['bin_grep']." -E ".$webserverUser.".+".substr($clientHandler->binSocket, 0, 9).".+tcp.+[0-9]+\.[0-9]+\.[0-9]+\.[0-9]:[0-9].+[0-9]:[0-9]");
		break;
	}
	return $retStr;
}

/*
 * netstatHosts
 */
function netstatHosts($torrentAlias) {
  return netstatHostsByPid(getTorrentPid($torrentAlias));
}

/*
 * netstatHostsByPid
 */
function netstatHostsByPid($torrentPid) {
	global $cfg;
	$hostHash = null;
	switch (_OS) {
		case 1: // linux
			$hostList = shell_exec($cfg['bin_netstat']." -e -p --tcp --numeric-hosts --numeric-ports 2> /dev/null | ".$cfg['bin_grep']." -v root | ".$cfg['bin_grep']." -v 127.0.0.1 | ".$cfg['bin_grep']." \"".$torrentPid."/\" | ".$cfg['bin_awk']." '{print \$5}'");
			$hostAry = explode("\n",$hostList);
			foreach ($hostAry as $line) {
				$hostLineAry = explode(':',trim($line));
				$hostHash[$hostLineAry[0]] = @ $hostLineAry[1];
			}
		break;
		case 2: // bsd
			$processUser = posix_getpwuid(posix_geteuid());
			$webserverUser = $processUser['name'];
			$hostList = shell_exec($cfg['bin_sockstat']." | ".$cfg['bin_awk']." '/".$webserverUser.".+".$torrentPid.".+tcp.+[0-9]:[0-9].+[0-9]:[0-9]/ {print \$7}'");
			$hostAry = explode("\n",$hostList);
			foreach ($hostAry as $line) {
				$hostLineAry = explode(':',trim($line));
				$hostHash[$hostLineAry[0]] = @ $hostLineAry[1];
			}
		break;
	}
	return $hostHash;
}

/* ************************************************************************** */

/*
 * getTorrentPid
 */
function getTorrentPid($torrentAlias) {
	global $cfg;
	return trim(shell_exec($cfg['bin_cat']." ".$cfg["torrent_file_path"].$torrentAlias.".pid"));
}

/* ************************************************************************** */

/**
 * Returns sum of max numbers of connections of all running torrents.
 *
 * @return int with max cons
 */
function getSumMaxCons() {
  global $db;
  $retVal = $db->GetOne("SELECT SUM(maxcons) AS maxcons FROM tf_torrents WHERE running = '1'");
  if ($retVal > 0)
	return $retVal;
  else
	return 0;
}

/**
 * Returns sum of max upload-speed of all running torrents.
 *
 * @return int with max upload-speed
 */
function getSumMaxUpRate() {
  global $db;
  $retVal = $db->GetOne("SELECT SUM(rate) AS rate FROM tf_torrents WHERE running = '1'");
  if ($retVal > 0)
	return $retVal;
  else
	return 0;
}

/**
 * Returns sum of max download-speed of all running torrents.
 *
 * @return int with max download-speed
 */
function getSumMaxDownRate() {
  global $db;
  $retVal = $db->GetOne("SELECT SUM(drate) AS drate FROM tf_torrents WHERE running = '1'");
  if ($retVal > 0)
	return $retVal;
  else
	return 0;
}

/* ************************************************************************** */

/*
 * Function to delete saved Torrent Settings
 */
function deleteTorrentSettings($torrent) {
	global $db;
	$sql = "DELETE FROM tf_torrents WHERE torrent = '".$torrent."'";
	$db->Execute($sql);
	showError($db, $sql);
	return true;
}

/*
 * Function for saving Torrent Settings
 */
function saveTorrentSettings($torrent, $running, $rate, $drate, $maxuploads, $runtime, $sharekill, $minport, $maxport, $maxcons, $savepath, $btclient = 'tornado') {
	// Messy - a not exists would prob work better
	deleteTorrentSettings($torrent);
	global $db;
	// get hash
	$tHash = getTorrentHash($torrent);
	// get datapath
	$tDatapath = getTorrentDatapath($torrent);
	//
	$sql = "INSERT INTO tf_torrents ( torrent , running ,rate , drate, maxuploads , runtime , sharekill , minport , maxport, maxcons , savepath , btclient, hash, datapath )
			VALUES (
					'".$torrent."',
					'".$running."',
					'".$rate."',
					'".$drate."',
					'".$maxuploads."',
					'".$runtime."',
					'".$sharekill."',
					'".$minport."',
					'".$maxport."',
					'".$maxcons."',
					'".$savepath."',
					'".$btclient."',
					'".$tHash."',
					'".$tDatapath."'
				   )";
	$db->Execute($sql);
		showError($db, $sql);
	return true;
}

/*
 * Function to load the settings for a torrent. returns array with settings
 */
function loadTorrentSettings($torrent) {
	global $cfg, $db;
	$sql = "SELECT * FROM tf_torrents WHERE torrent = '".$torrent."'";
	$result = $db->Execute($sql);
	showError($db, $sql);
	$row = $result->FetchRow();
	if (!empty($row)) {
		$retAry = array();
		$retAry["running"]					= $row["running"];
		$retAry["max_upload_rate"]			= $row["rate"];
		$retAry["max_download_rate"]		= $row["drate"];
		$retAry["torrent_dies_when_done"]	= $row["runtime"];
		$retAry["max_uploads"]				= $row["maxuploads"];
		$retAry["minport"]					= $row["minport"];
		$retAry["maxport"]					= $row["maxport"];
		$retAry["sharekill"]				= $row["sharekill"];
		$retAry["maxcons"]					= $row["maxcons"];
		$retAry["savepath"]					= $row["savepath"];
		$retAry["btclient"]					= $row["btclient"];
		$retAry["hash"]						= $row["hash"];
		$retAry["datapath"]					= $row["datapath"];
		return $retAry;
	}
	return;
}

/*
 * Function to load the settings for a torrent to global cfg-array
 *
 * @param $torrent name of the torrent
 * @return boolean if the settings could be loaded (were existent in db already)
 */
function loadTorrentSettingsToConfig($torrent) {
	global $cfg, $db, $superseeder;
	$sql = "SELECT * FROM tf_torrents WHERE torrent = '".$torrent."'";
	$result = $db->Execute($sql);
	showError($db, $sql);
	$row = $result->FetchRow();
	if (!empty($row)) {
		$cfg["running"]					= $row["running"];
		$cfg["max_upload_rate"]			= $row["rate"];
		$cfg["max_download_rate"]		= $row["drate"];
		$cfg["torrent_dies_when_done"]	= $row["runtime"];
		$cfg["max_uploads"]				= $row["maxuploads"];
		$cfg["minport"]					= $row["minport"];
		$cfg["maxport"]					= $row["maxport"];
		$cfg["sharekill"]				= $row["sharekill"];
		$cfg["maxcons"]					= $row["maxcons"];
		$cfg["savepath"]				= $row["savepath"];
		$cfg["btclient"]				= $row["btclient"];
		$cfg["hash"]					= $row["hash"];
		$cfg["datapath"]				= $row["datapath"];
		return true;
	} else {
		return false;
	}
}

/**
 * sets the running flag in the db to stopped.
 *
 * @param $torrent name of the torrent
 */
function stopTorrentSettings($torrent) {
  global $db;
  $sql = "UPDATE tf_torrents SET running = '0' WHERE torrent = '".$torrent."'";
  $db->Execute($sql);
  return true;
}

/* ************************************************************************** */

/**
 * checks if transfer is running by checking for existencte of pid-file.
 *
 * @param $transfer name of the transfer
 * @return 1|0
 */
function isTransferRunning($transfer) {
	global $cfg;
	if ((substr(strtolower($transfer),-8 ) == ".torrent")) {
		// this is a torrent-client
		if (file_exists($cfg["torrent_file_path"].substr($transfer,0,-8).'.stat.pid'))
			return 1;
		else
			return 0;
	} else if ((substr(strtolower($transfer),-5 ) == ".wget")) {
		// this is wget.
		if (file_exists($cfg["torrent_file_path"].substr($transfer,0,-5).'.stat.pid'))
			return 1;
		else
			return 0;
	} else {
		return 0;
	}
}

/* ************************************************************************** */

/**
 * gets the btclient of the torrent out of the the db.
 *
 * @param $torrent name of the torrent
 * @return btclient
 */
function getTransferClient($torrent) {
  global $db;
  return $db->GetOne("SELECT btclient FROM tf_torrents WHERE torrent = '".$torrent."'");
}

/**
 * gets hash of a torrent
 * this should not be called external if its no must, use cached value in
 * tf_torrents if possible.
 *
 * @param $torrent name of the torrent
 * @return var with torrent-hash
 */
function getTorrentHash($torrent) {
	//info = metainfo['info']
	//info_hash = sha(bencode(info))
	//print 'metainfo file.: %s' % basename(metainfo_name)
	//print 'info hash.....: %s' % info_hash.hexdigest()
	global $cfg;
	$result = getTorrentMetaInfo($torrent);
	if (! isset($result))
		return "";
	$resultAry = explode("\n",$result);
	$hashAry = array();
	switch ($cfg["metainfoclient"]) {
		case "transmissioncli":
		case "ttools":
			$hashAry = explode(":",trim($resultAry[0]));
		break;
		case "btshowmetainfo.py":
		default:
			$hashAry = explode(":",trim($resultAry[3]));
		break;
	}
	$tHash = @trim($hashAry[1]);
	// return
	if (isset($tHash) && $tHash != "")
		return $tHash;
	else
		return "";
}

/**
 * gets datapath of a torrent.
 * this should not be called external if its no must, use cached value in
 * tf_torrents if possible.
 *
 * @param $torrent name of the torrent
 * @return var with torrent-datapath or empty string on error
 */
function getTorrentDatapath($torrent) {
	global $cfg;
    require_once('inc/classes/BDecode.php');
    $ftorrent=$cfg["torrent_file_path"].$torrent;
    $fd = fopen($ftorrent, "rd");
    $alltorrent = fread($fd, filesize($ftorrent));
    $btmeta = BDecode($alltorrent);
    $data = $btmeta['info']['name'];
    if(trim($data) != "")
        return $data;
    else
    	return "";
}

/* ************************************************************************** */

/**
 * updates totals of a transfer
 *
 * @param $transfer name of the transfer
 * @param $uptotal uptotal of the transfer
 * @param $downtotal downtotal of the transfer
 */
function updateTransferTotals($transfer) {
	global $cfg, $db;
	$torrentId = getTorrentHash($transfer);
	$transferTotals = getTransferTotals($transfer);
	// very ugly exists check... too lazy now
	$sql = "SELECT uptotal,downtotal FROM tf_torrent_totals WHERE tid = '".$torrentId."'";
	$result = $db->Execute($sql);
		showError($db, $sql);
	$row = $result->FetchRow();
	if (!empty($row)) {
		$sql = "UPDATE tf_torrent_totals SET uptotal = '".($transferTotals["uptotal"]+0)."', downtotal = '".($transferTotals["downtotal"]+0)."' WHERE tid = '".$torrentId."'";
		$db->Execute($sql);
	} else {
		$sql = "INSERT INTO tf_torrent_totals ( tid , uptotal ,downtotal )
					VALUES (
					'".$torrentId."',
					'".($transferTotals["uptotal"]+0)."',
					'".($transferTotals["downtotal"]+0)."'
				   )";
		$db->Execute($sql);
	}
	showError($db, $sql);
}

/**
 * gets totals of a transfer
 *
 * @param $transfer name of the transfer
 * @return array with transfer-totals
 */
function getTransferTotals($transfer) {
	global $cfg;
	require_once("inc/classes/ClientHandler.php");
	if ((substr(strtolower($transfer),-8 ) == ".torrent")) {
		// this is a torrent-client
		$btclient = getTransferClient($transfer);
		$clientHandler = ClientHandler::getClientHandlerInstance($cfg, $btclient);
	} else if ((substr(strtolower($transfer),-5 ) == ".wget")) {
		// this is wget.
		$clientHandler = ClientHandler::getClientHandlerInstance($cfg, 'wget');
	} else {
		$clientHandler = ClientHandler::getClientHandlerInstance($cfg, 'tornado');
	}
	return $clientHandler->getTransferTotal($transfer);
}

/**
 * gets totals of a transfer
 *
 * @param $transfer name of the transfer
 * @param $tid of the transfer
 * @param $tclient client of the transfer
 * @param $afu alias-file-uptotal of the transfer
 * @param $afd alias-file-downtotal of the transfer
 * @return array with transfer-totals
 */
function getTransferTotalsOP($transfer, $tid, $tclient, $afu, $afd) {
	global $cfg;
	require_once("inc/classes/ClientHandler.php");
	$clientHandler = ClientHandler::getClientHandlerInstance($cfg, $tclient);
	return $clientHandler->getTransferTotalOP($transfer, $tid, $afu, $afd);
}

/**
 * gets current totals of a transfer
 *
 * @param $transfer name of the transfer
 * @return array with transfer-totals
 */
function getTransferTotalsCurrent($transfer) {
	global $cfg;
	require_once("inc/classes/ClientHandler.php");
	if ((substr( strtolower($transfer),-8 ) == ".torrent")) {
		// this is a torrent-client
		$btclient = getTransferClient($transfer);
		$clientHandler = ClientHandler::getClientHandlerInstance($cfg, $btclient);
	} else if ((substr(strtolower($transfer),-5 ) == ".wget")) {
		// this is wget.
		$clientHandler = ClientHandler::getClientHandlerInstance($cfg, 'wget');
	} else {
		$clientHandler = ClientHandler::getClientHandlerInstance($cfg, 'tornado');
	}
	return $clientHandler->getTransferCurrent($transfer);
}

/**
 * gets current totals of a transfer
 *
 * @param $transfer name of the transfer
 * @param $tid of the transfer
 * @param $tclient client of the transfer
 * @param $afu alias-file-uptotal of the transfer
 * @param $afd alias-file-downtotal of the transfer
 * @return array with transfer-totals
 */
function getTransferTotalsCurrentOP($transfer, $tid, $tclient, $afu, $afd) {
	global $cfg;
	require_once("inc/classes/ClientHandler.php");
	$clientHandler = ClientHandler::getClientHandlerInstance($cfg, $tclient);
	return $clientHandler->getTransferCurrentOP($transfer, $tid, $afu, $afd);
}

/**
 * resets totals of a torrent
 *
 * @param $transfer name of the torrent
 * @param $delete boolean if to delete torrent-file
 * @return boolean of success
 */
function resetTorrentTotals($torrent, $delete = false) {
	global $cfg, $db;
	if ( !isset($torrent) || !preg_match('/^[a-zA-Z0-9._]+$/', $torrent) )
		return false;
	// vars
	$torrentId = getTorrentHash($torrent);
	$alias = getAliasName($torrent);
	$owner = getOwner($torrent);
	// delete torrent
	if ($delete == true) {
		deleteTransfer($torrent, $alias);
		// delete the stat file. shouldnt be there.. but...
		@unlink($cfg["torrent_file_path"].$alias.".stat");
	} else {
		// reset in stat-file
		require_once("inc/classes/AliasFile.php");
		$af = AliasFile::getAliasFileInstance($cfg["torrent_file_path"].$alias.".stat", $owner, $cfg);
		if (isset($af)) {
			$af->uptotal = 0;
			$af->downtotal = 0;
			$af->WriteFile();
		}
	}
	// reset in db
	$sql = "DELETE FROM tf_torrent_totals WHERE tid = '".$torrentId."'";
	$db->Execute($sql);
		showError($db, $sql);
	return true;
}

/* ************************************************************************** */

/**
 * deletes a transfer
 *
 * @param $transfer name of the torrent
 * @param $alias_file alias-file of the torrent
 * @return boolean of success
 */
function deleteTransfer($transfer, $alias_file) {
	$delfile = $transfer;
	global $cfg;
	$transferowner = getOwner($delfile);
	if (($cfg["user"] == $transferowner) || IsAdmin()) {
		require_once("inc/classes/AliasFile.php");
		if ((substr( strtolower($transfer),-8 ) == ".torrent")) {
			// this is a torrent-client
			$btclient = getTransferClient($delfile);
			$af = AliasFile::getAliasFileInstance($cfg['torrent_file_path'].$alias_file, $transferowner, $cfg, $btclient);
			// update totals for this torrent
			updateTransferTotals($delfile);
			// remove torrent-settings from db
			deleteTorrentSettings($delfile);
			// client-proprietary leftovers
			require_once("inc/classes/ClientHandler.php");
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg,$btclient);
			$clientHandler->deleteCache($transfer);
		} else if ((substr( strtolower($transfer),-5 ) == ".wget")) {
			// this is wget.
			$af = AliasFile::getAliasFileInstance($cfg['torrent_file_path'].$alias_file, $cfg['user'], $cfg, 'wget');
		} else {
			// this is "something else". use tornado statfile as default
			$af = AliasFile::getAliasFileInstance($cfg['torrent_file_path'].$alias_file, $cfg['user'], $cfg, 'tornado');
		}
		//XFER: before torrent deletion save upload/download xfer data to SQL
		$transferTotals = getTransferTotals($delfile);
		saveXfer($transferowner,($transferTotals["downtotal"]+0),($transferTotals["uptotal"]+0));
		// torrent+stat
		@unlink($cfg["torrent_file_path"].$delfile);
		@unlink($cfg["torrent_file_path"].$alias_file);
		// try to remove the QInfo if in case it was queued.
		@unlink($cfg["torrent_file_path"]."queue/".$alias_file.".Qinfo");
		// try to remove the pid file
		@unlink($cfg["torrent_file_path"].$alias_file.".pid");
		@unlink($cfg["torrent_file_path"].getAliasName($delfile).".prio");
		AuditAction($cfg["constants"]["delete_torrent"], $delfile);
		return true;
	} else {
		AuditAction($cfg["constants"]["error"], $cfg["user"]." attempted to delete ".$delfile);
		return false;
	}
}

/**
 * deletes data of a torrent
 *
 * @param $torrent name of the torrent
 */
function deleteTorrentData($torrent) {
	$element = $torrent;
	global $cfg;
	if (($cfg["user"] == getOwner($element)) || IsAdmin()) {
		# the user is the owner of the torrent -> delete it
		require_once('inc/classes/BDecode.php');
		$ftorrent=$cfg["torrent_file_path"].$element;
		$fd = fopen($ftorrent, "rd");
		$alltorrent = fread($fd, filesize($ftorrent));
		$btmeta = BDecode($alltorrent);
		$delete = $btmeta['info']['name'];
		if(trim($delete) != "") {
			// load torrent-settings from db to get data-location
			loadTorrentSettingsToConfig(urldecode($torrent));
			if ((! isset($cfg["savepath"])) || (empty($cfg["savepath"])))
				$cfg["savepath"] = $cfg["path"].getOwner($torrent).'/';
			$delete = $cfg["savepath"].$delete;
			# this is from dir.php - its not a function, and we need to call it several times
			$del = stripslashes(stripslashes($delete));
			if (!ereg("(\.\.\/)", $del)) {
				 avddelete($del);
				 $arTemp = explode("/", $del);
				 if (count($arTemp) > 1) {
					 array_pop($arTemp);
					 $current = implode("/", $arTemp);
				 }
				 AuditAction($cfg["constants"]["fm_delete"], $del);
			} else {
				 AuditAction($cfg["constants"]["error"], "ILLEGAL DELETE: ".$cfg['user']." tried to delete ".$del);
			}
		}
	} else {
		AuditAction($cfg["constants"]["error"], $cfg["user"]." attempted to delete ".$element);
	}
}

/* ************************************************************************** */

/**
 * gets size of data of a torrent
 *
 * @param $torrent name of the torrent
 * @return int with size of data of torrent.
 *		   -1 if error
 *		   4096 if dir (lol ~)
 *		   string with file/dir-name if doesnt exist. (lol~)
 */
function getTorrentDataSize($torrent) {
	global $cfg;
	require_once('inc/classes/BDecode.php');
	$ftorrent=$cfg["torrent_file_path"].$torrent;
	$fd = fopen($ftorrent, "rd");
	$alltorrent = fread($fd, filesize($ftorrent));
	$btmeta = BDecode($alltorrent);
	$name = $btmeta['info']['name'];
	if(trim($name) != "") {
		// load torrent-settings from db to get data-location
		loadTorrentSettingsToConfig($torrent);
		if ((! isset($cfg["savepath"])) || (empty($cfg["savepath"])))
			$cfg["savepath"] = $cfg["path"].getOwner($torrent).'/';
		$name = $cfg["savepath"].$name;
		# this is from dir.php - its not a function, and we need to call it several times
		$tData = stripslashes(stripslashes($name));
		if (!ereg("(\.\.\/)", $tData)) {
			$fileSize = file_size($tData);
			return $fileSize;
		}
	}
	return -1;
}

/* ************************************************************************** */

/**
 * deletes a dir-entry. recursive process via avddelete
 *
 * @param $del entry to delete
 * @return string with current
 */
function delDirEntry($del) {
	global $cfg;
	$current = "";
	// The following lines of code were suggested by Jody Steele jmlsteele@stfu.ca
	// this is so only the owner of the file(s) or admin can delete
	if(IsAdmin($cfg["user"]) || preg_match("/^" . $cfg["user"] . "/",$del)) {
		// Yes, then delete it
		// we need to strip slashes twice in some circumstances
		// Ex.	If we are trying to delete test/tester's file/test.txt
		//	  $del will be "test/tester\\\'s file/test.txt"
		//	  one strip will give us "test/tester\'s file/test.txt
		//	  the second strip will give us the correct
		//		  "test/tester's file/test.txt"
		$del = stripslashes(stripslashes($del));
		if (!ereg("(\.\.\/)", $del)) {
			avddelete($cfg["path"].$del);
			$arTemp = explode("/", $del);
			if (count($arTemp) > 1) {
				array_pop($arTemp);
				$current = implode("/", $arTemp);
			}
			AuditAction($cfg["constants"]["fm_delete"], $del);
		} else {
			AuditAction($cfg["constants"]["error"], "ILLEGAL DELETE: ".$cfg['user']." tried to delete ".$del);
		}
	} else {
		AuditAction($cfg["constants"]["error"], "ILLEGAL DELETE: ".$cfg['user']." tried to delete ".$del);
	}
	return $current;
}

/* ************************************************************************** */

/**
 * RunningProcessInfo
 *
 */
function RunningProcessInfo() {
	global $cfg;
	require_once("inc/classes/ClientHandler.php");
	// messy...
	$RunningProcessInfo = " ---=== tornado ===---\n\n";
	$clientHandler = ClientHandler::getClientHandlerInstance($cfg,"tornado");
	$RunningProcessInfo .= $clientHandler->printRunningClientsInfo();
	$pinfo = shell_exec("ps auxww | ".$cfg['bin_grep']." ". $clientHandler->binClient ." | ".$cfg['bin_grep']." -v grep");
	$RunningProcessInfo .= "\n --- Process-List --- \n".$pinfo;
	unset($clientHandler);
	unset($pinfo);
	$RunningProcessInfo .= "\n\n ---=== transmission ===---\n\n";
	$clientHandler = ClientHandler::getClientHandlerInstance($cfg,"transmission");
	$RunningProcessInfo .= $clientHandler->printRunningClientsInfo();
	$pinfo = shell_exec("ps auxww | ".$cfg['bin_grep']." ". $clientHandler->binSystem ." | ".$cfg['bin_grep']." -v grep");
	$RunningProcessInfo .= "\n --- Process-List --- \n".$pinfo;
	unset($clientHandler);
	unset($pinfo);
	$RunningProcessInfo .= "\n\n ---=== wget ===---\n\n";
	$clientHandler = ClientHandler::getClientHandlerInstance($cfg,"wget");
	$RunningProcessInfo .= $clientHandler->printRunningClientsInfo();
	$pinfo = shell_exec("ps auxww | ".$cfg['bin_grep']." ". $clientHandler->binSystem ." | ".$cfg['bin_grep']." -v grep");
	$RunningProcessInfo .= "\n --- Process-List --- \n".$pinfo;
	return $RunningProcessInfo;
}

/**
 * getRunningTransferCount
 *
 * @return int with number of running transfers
 */
function getRunningTransferCount() {
	global $cfg;
	// use pid-files-direct-access for now because all clients of currently
	// available handlers write one. then its faster and correct meanwhile.
	if ($dirHandle = opendir($cfg["torrent_file_path"])) {
		$tCount = 0;
		while (false !== ($file = readdir($dirHandle))) {
			//if ((substr($file, -1, 1)) == "d")
			if ((substr($file, -4, 4)) == ".pid")
				$tCount++;
		}
		closedir($dirHandle);
		return $tCount;
	} else {
		return 0;
	}
}

/**
 * getRunningTransfers
 *
 * @param $clientType
 * @return array
 */
function getRunningTransfers($clientType = '') {
	global $cfg;
	require_once("inc/classes/ClientHandler.php");
	// get only torrents of a particular client
	if ((isset($clientType)) && ($clientType != '')) {
		$clientHandler = ClientHandler::getClientHandlerInstance($cfg,$clientType);
		return $clientHandler->getRunningClients();
	}
	// get torrents of all clients
	// messy...
	$retAry = array();
	// tornado
	$clientHandler = ClientHandler::getClientHandlerInstance($cfg,"tornado");
	$tempAry = $clientHandler->getRunningClients();
	foreach ($tempAry as $val)
		array_push($retAry,$val);
	unset($clientHandler);
	unset($tempAry);
	// transmission
	$clientHandler = ClientHandler::getClientHandlerInstance($cfg,"transmission");
	$tempAry = $clientHandler->getRunningClients();
	foreach ($tempAry as $val)
		array_push($retAry,$val);
	unset($clientHandler);
	unset($tempAry);
	// wget
	$clientHandler = ClientHandler::getClientHandlerInstance($cfg,"wget");
	$tempAry = $clientHandler->getRunningClients();
	foreach ($tempAry as $val)
		array_push($retAry,$val);
	return $retAry;
}

/* ************************************************************************** */

/**
 * gets metainfo of a torrent as string
 *
 * @param $torrent name of the torrent
 * @return string with torrent-meta-info
 */
function getTorrentMetaInfo($torrent) {
	global $cfg;
	switch ($cfg["metainfoclient"]) {
		case "transmissioncli":
			return shell_exec($cfg["btclient_transmission_bin"] . " -i \"".$cfg["torrent_file_path"].$torrent."\"");
			break;
		case "ttools":
			$fluxDocRoot = dirname($_SERVER["SCRIPT_FILENAME"]);
			return shell_exec($cfg["perlCmd"].' -I "'.$fluxDocRoot.'/bin/ttools" "'.$fluxDocRoot.'/bin/ttools/showmetainfo.pl" "'.$cfg["torrent_file_path"].$torrent.'"');
			break;
		case "btshowmetainfo.py":
		default:
			$fluxDocRoot = dirname($_SERVER["SCRIPT_FILENAME"]);
			return shell_exec("cd ".$cfg["torrent_file_path"]."; ".$cfg["pythonCmd"]." -OO ".$fluxDocRoot."/bin/TF_BitTornado/btshowmetainfo.py \"".$torrent."\"");
	}
}

/**
 * gets scrape-info of a torrent as string
 *
 * @param $torrent name of the torrent
 * @return string with torrent-scrape-info
 */
function getTorrentScrapeInfo($torrent) {
	global $cfg;
	switch ($cfg["metainfoclient"]) {
		case "transmissioncli":
			return shell_exec($cfg["btclient_transmission_bin"] . " -s \"".$cfg["torrent_file_path"].$torrent."\"");
			break;
		default:
			return "error. torrent-scrape needs transmissioncli.";
	}
}

/**
 * gets torrent-list from file-system. (never-started are included here)
 * @return array with torrents
 */
function getTorrentListFromFS() {
	global $cfg;
	$retVal = array();
	if ($dirHandle = opendir($cfg["torrent_file_path"])) {
		while (false !== ($file = readdir($dirHandle))) {
			if ((substr($file, -2)) == "nt")
				array_push($retVal, $file);
		}
		closedir($dirHandle);
	}
	return $retVal;
}

/**
 * gets torrent-list from database.
 * @return array with torrents
 */
function getTorrentListFromDB() {
	global $db;
	$retVal = array();
	$sql = "SELECT torrent FROM tf_torrents ORDER BY torrent ASC";
	$recordset = $db->Execute($sql);
	showError($db, $sql);
	while(list($torrent) = $recordset->FetchRow())
		array_push($retVal, $torrent);
	return $retVal;
}

/* ************************************************************************** */

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
	$sql = "INSERT INTO tf_settings_user VALUES ('".$uid."', '".$key."', '".$update_value."')";
	if ( $sql != "" ) {
		$result = $db->Execute($sql);
		showError($db,$sql);
		// update the Config.
		$cfg[$key] = $value;
	}
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

/* ************************************************************************** */

/*
 * Function to convert bit-array to (unsigned) byte
 *
 * @param bit-array
 * @return byte
 */
function convertArrayToByte($dataArray) {
   if (count($dataArray) > 8) return false;
   foreach ($dataArray as $key => $value) {
	   if ($value) $dataArray[$key] = 1;
	   if (!$value) $dataArray[$key] = 0;
   }
   $binString = strrev(implode('', $dataArray));
   $bitByte = bindec($binString);
   return $bitByte;
}

/*
 * Function to convert (unsigned) byte to bit-array
 *
 * @param byte
 * @return bit-array
 */
function convertByteToArray($dataByte) {
   if (($dataByte > 255) || ($dataByte < 0)) return false;
   $binString = strrev(str_pad(decbin($dataByte),8,"0",STR_PAD_LEFT));
   $bitArray = explode(":",chunk_split($binString, 1, ":"));
   return $bitArray;
}

/*
 * Function to convert bit-array to (unsigned) integer
 *
 * @param bit-array
 * @return integer
 */
function convertArrayToInteger($dataArray) {
   if (count($dataArray) > 31) return false;
   foreach ($dataArray as $key => $value) {
	   if ($value) $dataArray[$key] = 1;
	   if (!$value) $dataArray[$key] = 0;
   }
   $binString = strrev(implode('', $dataArray));
   $bitInteger = bindec($binString);
   return $bitInteger;
}

/*
 * Function to convert (unsigned) integer to bit-array
 *
 * @param integer
 * @return bit-array
 */
function convertIntegerToArray($dataInt) {
   if (($dataInt > 2147483647) || ($dataInt < 0)) return false;
   $binString = strrev(str_pad(decbin($dataInt),31,"0",STR_PAD_LEFT));
   $bitArray = explode(":",chunk_split($binString, 1, ":"));
   return $bitArray;
}

/* ************************************************************************** */

/*
 * Function with which torrents are started in index-page
 *
 * @param $torrent torrent-name
 * @param $interactive (1|0) : is this a interactive startup with dialog ?
 */
function indexStartTorrent($torrent,$interactive) {
	global $cfg, $queueActive;
	if ($cfg["enable_file_priority"]) {
		include_once("inc/setpriority.php");
		// Process setPriority Request.
		setPriority($torrent);
	}
	switch ($interactive) {
		case 0:
			require_once("inc/classes/ClientHandler.php");
			$btclient = getTransferClient($torrent);
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg,$btclient);
			$clientHandler->startClient($torrent, 0, $queueActive);
			// just 2 sec..
			sleep(2);
			// header + out
			header("location: index.php?iid=index");
			exit();
			break;
		case 1:
			$spo = getRequestVar('setPriorityOnly');
			if (!empty($spo)){
				// This is a setPriorityOnly Request.
			} else {
				require_once("inc/classes/ClientHandler.php");
				$clientHandler = ClientHandler::getClientHandlerInstance($cfg, getRequestVar('btclient'));
				$clientHandler->startClient($torrent, 1, $queueActive);
				if ($clientHandler->status == 3) { // hooray
					// wait another sec
					sleep(1);
					if (array_key_exists("closeme",$_POST)) {
						echo '<script  language="JavaScript">';
						echo ' window.opener.location.reload(true);';
						echo ' window.close();';
						echo '</script>';
					} else {
						header("location: index.php?iid=index");
					}
				} else { // start failed
					echo $clientHandler->messages;
				}
				exit();
			}
			break;
	}
}

/*
 * Function with which torrents are downloaded and injected on index-page
 *
 * @param $url_upload url of torrent to download
 */
function indexProcessDownload($url_upload) {
	global $cfg, $messages, $queueActive;
	$arURL = explode("/", $url_upload);
	$file_name = urldecode($arURL[count($arURL)-1]); // get the file name
	$file_name = str_replace(array("'",","), "", $file_name);
	$file_name = stripslashes($file_name);
	$ext_msg = "";
	// Check to see if url has something like ?passkey=12345
	// If so remove it.
	if( ( $point = strrpos( $file_name, "?" ) ) !== false )
		$file_name = substr( $file_name, 0, $point );
	$ret = strrpos($file_name,".");
	if ($ret === false) {
		$file_name .= ".torrent";
	} else {
		if(!strcmp(strtolower(substr($file_name, strlen($file_name)-8, 8)), ".torrent") == 0)
			$file_name .= ".torrent";
	}
	$url_upload = str_replace(" ", "%20", $url_upload);
	// This is to support Sites that pass an id along with the url for torrent downloads.
	$tmpId = getRequestVar("id");
	if(!empty($tmpId))
		$url_upload .= "&id=".$tmpId;
	// Call fetchtorrent to retrieve the torrent file
	$output = FetchTorrent( $url_upload );
	if (array_key_exists("save_torrent_name",$cfg)) {
		if ($cfg["save_torrent_name"] != "")
			$file_name = $cfg["save_torrent_name"];
	}
	$file_name = cleanFileName($file_name);
	// if the output had data then write it to a file
	if ((strlen($output) > 0) && (strpos($output, "<br />") === false)) {
		if (is_file($cfg["torrent_file_path"].$file_name)) {
			// Error
			$messages .= "<b>Error</b> with (<b>".$file_name."</b>), the file already exists on the server.<br><center><a href=\"".$_SERVER['PHP_SELF']."\">[Refresh]</a></center>";
			$ext_msg = "DUPLICATE :: ";
		} else {
			// open a file to write to
			$fw = fopen($cfg["torrent_file_path"].$file_name,'w');
			fwrite($fw, $output);
			fclose($fw);
		}
	} else {
		$messages .= "<b>Error</b> Getting the File (<b>".$file_name."</b>), Could be a Dead URL.<br><center><a href=\"".$_SERVER['PHP_SELF']."\">[Refresh]</a></center>";
	}
	if($messages != "") { // there was an error
		AuditAction($cfg["constants"]["error"], $cfg["constants"]["url_upload"]." :: ".$ext_msg.$file_name);
	} else {
		AuditAction($cfg["constants"]["url_upload"], $file_name);
		// init stat-file
		injectTorrent($file_name);
		// instant action ?
		$actionId = getRequestVar('aid');
		if (isset($actionId)) {
			if ($cfg["enable_file_priority"]) {
				include_once("inc/setpriority.php");
				// Process setPriority Request.
				setPriority(urldecode($file_name));
			}
			require_once("inc/classes/ClientHandler.php");
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg);
			switch ($actionId) {
				case 3:
					$clientHandler->startClient($file_name, 0, true);
					break;
				case 2:
					$clientHandler->startClient($file_name, 0, false);
					break;
			}
			// just a sec..
			sleep(1);
		}
		header("location: index.php?iid=index");
		exit();
	}
}

/*
 * Function with which torrents are uploaded and injected on index-page
 *
 */
function indexProcessUpload() {
	global $cfg, $messages;
	$file_name = stripslashes($_FILES['upload_file']['name']);
	$file_name = str_replace(array("'",","), "", $file_name);
	$file_name = cleanFileName($file_name);
	$ext_msg = "";
	if($_FILES['upload_file']['size'] <= 1000000 && $_FILES['upload_file']['size'] > 0) {
		if (ereg(getFileFilter($cfg["file_types_array"]), $file_name)) {
			//FILE IS BEING UPLOADED
			if (is_file($cfg["torrent_file_path"].$file_name)) {
				// Error
				$messages .= "<b>Error</b> with (<b>".$file_name."</b>), the file already exists on the server.<br><center><a href=\"".$_SERVER['PHP_SELF']."\">[Refresh]</a></center>";
				$ext_msg = "DUPLICATE :: ";
			} else {
				if(move_uploaded_file($_FILES['upload_file']['tmp_name'], $cfg["torrent_file_path"].$file_name)) {
					chmod($cfg["torrent_file_path"].$file_name, 0644);
					AuditAction($cfg["constants"]["file_upload"], $file_name);
					// init stat-file
					injectTorrent($file_name);
					// instant action ?
					$actionId = getRequestVar('aid');
					if (isset($actionId)) {
						if ($cfg["enable_file_priority"]) {
							include_once("inc/setpriority.php");
							// Process setPriority Request.
							setPriority(urldecode($file_name));
						}
						require_once("inc/classes/ClientHandler.php");
						$clientHandler = ClientHandler::getClientHandlerInstance($cfg);
						switch ($actionId) {
							case 3:
								$clientHandler->startClient($file_name, 0, true);
								break;
							case 2:
								$clientHandler->startClient($file_name, 0, false);
								break;
						}
						// just a sec..
						sleep(1);
					}
				} else {
					$messages .= "<font color=\"#ff0000\" size=3>ERROR: File not uploaded, file could not be found or could not be moved:<br>".$cfg["torrent_file_path"] . $file_name."</font><br>";
				}
			}
		} else {
			$messages .= "<font color=\"#ff0000\" size=3>ERROR: The type of file you are uploading is not allowed.</font><br>";
		}
	} else {
		$messages .= "<font color=\"#ff0000\" size=3>ERROR: File not uploaded, check file size limit.</font><br>";
	}
	if($messages != "") { // there was an error
		AuditAction($cfg["constants"]["error"], $cfg["constants"]["file_upload"]." :: ".$ext_msg.$file_name);
	} else {
		header("location: index.php?iid=index");
		exit();
	}
}

/* ************************************************************************** */

/**
 * checks a dir. recursive process to emulate "mkdir -p" if dir not present
 *
 * @param $dir the name of the dir
 * @param $mode the mode of the dir if created. default is 0755
 * @return boolean if dir exists/could be created
 */
function checkDirectory($dir, $mode = 0755) {
  if ((is_dir($dir) && is_writable ($dir)) || @mkdir($dir,$mode))
	return true;
  if (! checkDirectory(dirname($dir),$mode))
	return false;
  return @mkdir($dir,$mode);
}

/* ************************************************************************** */

/*
 * repairTorrentflux
 *
 */
function repairTorrentflux() {
	global $cfg, $db;

	// delete pid-files of torrent-clients
	if ($dirHandle = opendir($cfg["torrent_file_path"])) {
		while (false !== ($file = readdir($dirHandle))) {
			if ((substr($file, -1, 1)) == "d")
				@unlink($cfg["torrent_file_path"].$file);
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
		$af = AliasFile::getAliasFileInstance($cfg["torrent_file_path"].$alias.".stat", $owner, $cfg, $btclient);
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

/* ************************************************************************** */

/**
 * getLoadAverageString
 *
 * @return string with load-average
 */
function getLoadAverageString() {
	global $cfg;
	switch (_OS) {
		case 1: // linux
			if (isFile($cfg["loadavg_path"])) {
				$loadavg_array = explode(" ", exec($cfg['bin_cat']." ".$cfg["loadavg_path"]));
				return $loadavg_array[2];
			} else {
				return 'n/a';
			}
		break;
		case 2: // bsd
			$loadavg = preg_replace("/.*load averages:(.*)/", "$1", exec("uptime"));
			return $loadavg;
		break;
		default:
			return 'n/a';
	}
	return 'n/a';
}

/* ************************************************************************** */

/**
 * injects a torrent
 *
 * @param $torrent
 * @return boolean
 */
function injectTorrent($torrent) {
	global $cfg;
	require_once("inc/classes/AliasFile.php");
	$af = AliasFile::getAliasFileInstance($cfg["torrent_file_path"].getAliasName($torrent).".stat",	 $cfg['user'], $cfg);
	$af->running = "2"; // file is new
	$af->size = getDownloadSize($cfg["torrent_file_path"].$torrent);
	$af->WriteFile();
	return true;
}

/* ************************************************************************** */

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

/* ************************************************************************** */

/**
 * checks if a path-string has a trailing slash. concat if it hasnt
 *
 * @param $dirPath
 * @return string with dirPath
 */
function checkDirPathString($dirPath) {
	if (((strlen($dirPath) > 0)) && (substr($dirPath, -1 ) != "/"))
		$dirPath .= "/";
	return $dirPath;
}


/* ************************************************************************** */


//XFER:****************************************************
//XFER: getXferBar(max_bytes, used_bytes, title)
//XFER: gets xfer percentage bar
function getXferBar($total, $used, $title) {
	global $cfg;
	$remaining = max(0,$total-$used/(1024*1024));
	$percent = round($remaining/$total*100,0);
	$text = ' ('.formatFreeSpace($remaining).') '._REMAINING;
	$bgcolor = '#';
	$bgcolor .= str_pad(dechex(255-255*($percent/150)),2,0,STR_PAD_LEFT);
	$bgcolor .= str_pad(dechex(255*($percent/150)),2,0,STR_PAD_LEFT);
	$bgcolor .='00';
	$displayXferBar = '<tr>';
	  $displayXferBar .= '<td width="2%" nowrap align="right"><div class="tiny">'.$title.'</div></td>';
	  $displayXferBar .= '<td width="92%">';
		$displayXferBar .= '<table width="100%" border="0" cellpadding="0" cellspacing="0" style="margin-top:1px;margin-bottom:1px;"><tr>';
		$displayXferBar .= '<td bgcolor="'.$bgcolor.'" width="'.($percent+1).'%">';
		if ($percent >= 50) {
			$displayXferBar .= '<div class="tinypercent" align="center"';
			if ($percent == 100)
				$displayXferBar .= ' style="background:#FF0000;">';
			else
				$displayXferBar .= '>';
			$displayXferBar .= $percent.'%'.$text;
			$displayXferBar .= '</div>';
		}
		$displayXferBar .= '</td>';
		$displayXferBar .= '<td bgcolor="#000000" width="'.(100-$percent).'%" height="100%">';
		if ($percent < 50) {
			$displayXferBar .= '<div class="tinypercent" align="center" style="color:'.$bgcolor;
			if ($percent == 0)
				$displayXferBar .= '; background:#00FF00;">';
			else
				$displayXferBar .= ';">';
			$displayXferBar .= $percent.'%'.$text;
			$displayXferBar .= '</div>';
		}
		$displayXferBar .= '</td>';
		$displayXferBar .= '</tr></table>';
	  $displayXferBar .= '</td>';
	$displayXferBar .= '</tr>';
	return $displayXferBar;
}

//XFER:****************************************************
//XFER: getXfer()
//XFER: gets xfer usage page
function getXfer() {
	global $cfg;
	$displayXferList = getXferList();
	if (isset($_GET['user'])) {
		$displayXferList .= '<br><b>';
		$displayXferList .= ($_GET['user'] == '%') ? _SERVERXFERSTATS : _USERDETAILS.': '.$_GET['user'];
		$displayXferList .= '</b><br>';
		getXferDetail($_GET['user'],_MONTHSTARTING,0,0);
		if (isset($_GET['month'])) {
			$mstart = $_GET['month'].'-'.$cfg['month_start'];
			$mend = date('Y-m-d',strtotime('+1 Month',strtotime($mstart)));
		}
		else {
			$mstart = 0;
			$mend = 0;
		}
		if (isset($_GET['week'])) {
			$wstart = $_GET['week'];
			$wend = date('Y-m-d',strtotime('+1 Week',strtotime($_GET['week'])));
		}
		else {
			$wstart = $mstart;
			$wend = $mend;
		}
		$displayXferList .= getXferDetail($_GET['user'],_WEEKSTARTING,$mstart,$mend);
		$displayXferList .= getXferDetail($_GET['user'],_DAY,$wstart,$wend);
	}
	return $displayXferList;
}

//XFER:****************************************************
//XFER: getXferDetail(user, period_title, start_timestamp, end_timestamp)
//XFER: get table of month/week/day's usage for user
function getXferDetail($user_id,$period,$period_start,$period_end) {
	global $cfg, $xfer, $xfer_total, $db;
	$period_query = ($period_start) ? 'and date >= "'.$period_start.'" and date < "'.$period_end.'"' : '';
	$sql = 'SELECT SUM(download) AS download, SUM(upload) AS upload, date FROM tf_xfer WHERE user LIKE "'.$user_id.'" '.$period_query.' GROUP BY date ORDER BY date';
	$rtnValue = $db->GetAll($sql);
	showError($db,$sql);
	$displayXferDetail = "<table width='760' border=1 bordercolor='$cfg[table_admin_border]' cellpadding='2' cellspacing='0' bgcolor='$cfg[table_data_bg]'>";
	$displayXferDetail .= '<tr>';
	$displayXferDetail .= "<td bgcolor='$cfg[table_header_bg]' width='20%'><div align=center class='title'>$period</div></td>";
	$displayXferDetail .= "<td bgcolor='$cfg[table_header_bg]' width='27%'><div align=center class='title'>"._TOTAL.'</div></td>';
	$displayXferDetail .= "<td bgcolor='$cfg[table_header_bg]' width='27%'><div align=center class='title'>"._DOWNLOAD.'</div></td>';
	$displayXferDetail .= "<td bgcolor='$cfg[table_header_bg]' width='27%'><div align=center class='title'>"._UPLOAD.'</div></td>';
	$displayXferDetail .= '</tr>';
	$start = '';
	$download = 0;
	$upload = 0;
	foreach ($rtnValue as $row) {
		$rtime = strtotime($row[2]);
		switch ($period) {
			case 'Month Starting':
				$newstart = $cfg['month_start'].' ';
				$newstart .= (date('j',$rtime) < $cfg['month_start']) ? date('M Y',strtotime('-1 Month',$rtime)) : date('M Y',$rtime);
			break;
			case 'Week Starting':
				$newstart = date('d M Y',strtotime('+1 Day last '.$cfg['week_start'],$rtime));
			break;
			case 'Day':
				$newstart = $row[2];
			break;
		}
		if ($row[2] == date('Y-m-d')) {
			if ($user_id == '%') {
				$row[0] = $xfer_total['day']['download'];
				$row[1] = $xfer_total['day']['upload'];
			}
			else {
				$row[0] = $xfer[$user_id]['day']['download'];
				$row[1] = $xfer[$user_id]['day']['upload'];
			}
		}
		if ($start != $newstart) {
			if ($upload + $download != 0) {
				$displayXferDetail .= '<tr>';
					$displayXferDetail .= "<td>$rowstr</td>";
					$downloadstr = formatFreeSpace($download/(1024*1024));
					$uploadstr = formatFreeSpace($upload/(1024*1024));
					$totalstr = formatFreeSpace(($download+$upload)/(1024*1024));
					$displayXferDetail .= "<td><div class='tiny' align='center'><b>$totalstr</b></div></td>";
					$displayXferDetail .= "<td><div class='tiny' align='center'>$downloadstr</div></td>";
					$displayXferDetail .= "<td><div class='tiny' align='center'>$uploadstr</div></td>";
				$displayXferDetail .= '</tr>';
			}
			$download = $row[0];
			$upload = $row[1];
			$start = $newstart;
		}
		else {
			$download += $row[0];
			$upload += $row[1];
		}
		switch ($period) {
			case 'Month Starting':
				$rowstr = "<a href='index.php?iid=xfer&op=xfer&user=$user_id&month=".date('Y-m',strtotime($start))."'>$start</a>";
			break;
			case 'Week Starting':
				$rowstr = "<a href='index.php?iid=xfer&op=xfer&user=$user_id&month=". @ $_GET[month] . "&week=".date('Y-m-d',strtotime($start))."'>$start</a>";
			break;
			case 'Day':
				$rowstr = $start;
			break;
		}
	}
	if ($upload + $download != 0) {
		$displayXferDetail .= '<tr>';
		$displayXferDetail .= "<td>$rowstr</td>";
		$downloadstr = formatFreeSpace($download/(1024*1024));
		$uploadstr = formatFreeSpace($upload/(1024*1024));
		$totalstr = formatFreeSpace(($download+$upload)/(1024*1024));
		$displayXferDetail .= "<td><div class='tiny' align='center'><b>$totalstr</b></div></td>";
		$displayXferDetail .= "<td><div class='tiny' align='center'>$downloadstr</div></td>";
		$displayXferDetail .= "<td><div class='tiny' align='center'>$uploadstr</div></td>";
		$displayXferDetail .= '</tr>';
	}
	$displayXferDetail .= '</table><br>';
	return $displayXferDetail;
}

//XFER:****************************************************
//XFER: getXferList()
//XFER: get top summary table of xfer usage page
function getXferList() {
	global $cfg, $xfer, $xfer_total, $db;
	$displayXferList = "<table width='760' border=1 bordercolor='$cfg[table_admin_border]' cellpadding='2' cellspacing='0' bgcolor='$cfg[table_data_bg]'>";
	$displayXferList .= '<tr>';
	$displayXferList .= "<td bgcolor='$cfg[table_header_bg]' width='15%'><div align=center class='title'>"._USER.'</div></td>';
	$displayXferList .= "<td bgcolor='$cfg[table_header_bg]' width='22%'><div align=center class='title'>"._TOTALXFER.'</div></td>';
	$displayXferList .= "<td bgcolor='$cfg[table_header_bg]' width='22%'><div align=center class='title'>"._MONTHXFER.'</div></td>';
	$displayXferList .= "<td bgcolor='$cfg[table_header_bg]' width='22%'><div align=center class='title'>"._WEEKXFER.'</div></td>';
	$displayXferList .= "<td bgcolor='$cfg[table_header_bg]' width='22%'><div align=center class='title'>"._DAYXFER.'</div></td>';
	$displayXferList .= '</tr>';
	$sql = 'SELECT user_id FROM tf_users ORDER BY user_id';
	$rtnValue = $db->GetCol($sql);
	showError($db,$sql);
	foreach ($rtnValue as $user_id) {
		$displayXferList .= '<tr>';
		$displayXferList .= '<td><a href="index.php?iid=xfer&op=xfer&user='.$user_id.'">'.$user_id.'</a></td>';
		$total = formatFreeSpace($xfer[$user_id]['total']['total']/(1024*1024));
		$month = formatFreeSpace(@ $xfer[$user_id]['month']['total']/(1024*1024));
		$week = formatFreeSpace(@ $xfer[$user_id]['week']['total']/(1024*1024));
		$day = formatFreeSpace(@ $xfer[$user_id]['day']['total']/(1024*1024));
		$displayXferList .= '<td><div class="tiny" align="center">'.$total.'</div></td>';
		$displayXferList .= '<td><div class="tiny" align="center">'.$month.'</div></td>';
		$displayXferList .= '<td><div class="tiny" align="center">'.$week.'</div></td>';
		$displayXferList .= '<td><div class="tiny" align="center">'.$day.'</div></td>';
		$displayXferList .= '</tr>';
	}
	$displayXferList .= '<td><a href="index.php?iid=xfer&op=xfer&user=%"><b>'._TOTAL.'</b></a></td>';
	$total = formatFreeSpace($xfer_total['total']['total']/(1024*1024));
	$month = formatFreeSpace($xfer_total['month']['total']/(1024*1024));
	$week = formatFreeSpace($xfer_total['week']['total']/(1024*1024));
	$day = formatFreeSpace($xfer_total['day']['total']/(1024*1024));
	$displayXferList .= '<td><div class="tiny" align="center"><b>'.$total.'</b></div></td>';
	$displayXferList .= '<td><div class="tiny" align="center"><b>'.$month.'</b></div></td>';
	$displayXferList .= '<td><div class="tiny" align="center"><b>'.$week.'</b></div></td>';
	$displayXferList .= '<td><div class="tiny" align="center"><b>'.$day.'</b></div></td>';
	$displayXferList .= '</table>';
	return $displayXferList;
}

// get the header portion of admin views
function getHead($subTopic, $showButtons=true, $refresh="", $percentdone="") {
	global $cfg;
	# create new template
	if ((strpos($cfg['theme'], '/')) === false)
		$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/inc.getHead.tmpl");
	else
		$tmpl = new vlibTemplate("themes/tf_standard_themes/tmpl/inc.getHead.tmpl");
	//set some vars
	$tmpl->setvar('main_bgcolor', $cfg["main_bgcolor"]);
	$tmpl->setvar('table_border_dk', $cfg["table_border_dk"]);
	$tmpl->setvar('theme', $cfg["theme"]);
	$tmpl->setvar('TitleBar', getTitleBar($cfg["pagetitle"].' - '.$subTopic, $showButtons));
	$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
	$tmpl->setvar('body_data_bg', $cfg["body_data_bg"]);
	// grab the template
	$output = $tmpl->grab();
	return $output;
}

// ***************************************************************************
// ***************************************************************************
// get the footer portion
function getFoot($showReturn=true, $showVersionLink = false) {
	global $cfg;
	$foot = "</td></tr>";
	$foot .= "</table>";
	if ($showReturn)
		$foot .= "[<a href=\"index.php?iid=index\">"._RETURNTOTORRENTS."</a>]";
	$foot .= "</div>";
	$foot .= "</td>";
	$foot .= "</tr>";
	$foot .= "</table>";
	$foot .=  getTorrentFluxLink($showVersionLink);
		$foot .= "</td>
	</tr>
	</table>
	</div>
	</body>
	</html>
	";
	return $foot;
}

// ***************************************************************************
// ***************************************************************************
// get TF Link and Version
function getTorrentFluxLink($showVersionLink = false) {
	global $cfg;
	if ($cfg["ui_displayfluxlink"] != 0) {
		$torrentFluxLink = "<div align=\"right\">";
		$torrentFluxLink .= "<a href=\"http://tf-b4rt.berlios.de/\" target=\"_blank\"><font class=\"tinywhite\">torrentflux-b4rt ".$cfg["version"]."</font></a>&nbsp;&nbsp;";
		if ($showVersionLink)
			$torrentFluxLink .= getSuperAdminLink('?z=1','');
		$torrentFluxLink .= "</div>";
		return $torrentFluxLink;
	} else {
		return "";
	}

}

// ***************************************************************************
// ***************************************************************************
// get Title Bar
// 2004-12-09 PFM: now using adodb.
function getTitleBar($pageTitleText, $showButtons=true) {
	global $cfg, $db;
	# create new template
	if ((strpos($cfg['theme'], '/')) === false)
		$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/inc.getTitleBar.tmpl");
	else
		$tmpl = new vlibTemplate("themes/tf_standard_themes/tmpl/inc.getTitleBar.tmpl");

	$tmpl->setvar('pageTitleText', $pageTitleText);
	$tmpl->setvar('showButtons', $showButtons);
	$tmpl->setvar('theme', $cfg["theme"]);
	$tmpl->setvar('_TORRENTS', _TORRENTS);
	$tmpl->setvar('_DIRECTORYLIST', _DIRECTORYLIST);
	$tmpl->setvar('_UPLOADHISTORY', _UPLOADHISTORY);
	$tmpl->setvar('_MYPROFILE', _MYPROFILE);
	$tmpl->setvar('_MESSAGES', _MESSAGES);
	$tmpl->setvar('_ADMINISTRATION', _ADMINISTRATION);
	if ($showButtons) {
		// Does the user have messages?
		$sql = "select count(*) from tf_messages where to_user='".$cfg['user']."' and IsNew=1";
		$number_messages = $db->GetOne($sql);
		showError($db,$sql);
		$tmpl->setvar('number_messages', $number_messages);
		$tmpl->setvar('is_admin', IsAdmin());
	}
	// grab the template
	$output = $tmpl->grab();
	return $output;
}

// ***************************************************************************
// ***************************************************************************
// get dropdown list to send message to a user
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
// Build Search Engine Drop Down List
function buildSearchEngineDDL($selectedEngine = 'TorrentSpy', $autoSubmit = false) {
	$output = "<select name=\"searchEngine\" ";
	if ($autoSubmit) {
		 $output .= "onchange=\"this.form.submit();\" ";
	}
	$output .= " STYLE=\"width: 125px\">";
	$handle = opendir("./inc/searchEngines");
	while($entry = readdir($handle)) {
		$entrys[] = $entry;
	}
	natcasesort($entrys);
	foreach($entrys as $entry) {
		if ($entry != "." && $entry != ".." && substr($entry, 0, 1) != "." && strpos($entry,"Engine.php")) {
			$tmpEngine = str_replace("Engine",'',substr($entry,0,strpos($entry,".")));
			$output .= "<option";
			if ($selectedEngine == $tmpEngine) {
				$output .= " selected";
			}
			$output .= ">".str_replace("Engine",'',substr($entry,0,strpos($entry,".")))."</option>";
		}
	}
	$output .= "</select>\n";
	return $output;
}

function getEngineLink($searchEngine) {
	$tmpLink = '';
	$engineFile = 'inc/searchEngines/'.$searchEngine.'Engine.php';
	if (is_file($engineFile)) {
		$fp = @fopen($engineFile,'r');
		if ($fp) {
			$tmp = fread($fp, filesize($engineFile));
			@fclose( $fp );
			$tmp = substr($tmp,strpos($tmp,'$this->mainURL'),100);
			$tmp = substr($tmp,strpos($tmp,"=")+1);
			$tmp = substr($tmp,0,strpos($tmp,";"));
			$tmpLink = trim(str_replace(array("'","\""),"",$tmp));
		}
	}
	return $tmpLink;
}

/* ************************************************************************** */

/**
 * get superadmin-popup-link-html-snip.
 *
 */
function getSuperAdminLink($param = "", $linkText = "") {
	global $cfg;
	$superAdminLink = '
	<script language="JavaScript">
	function SuperAdmin(name_file) {
			window.open (name_file,"_blank","toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width='.$cfg["ui_dim_superadmin_w"].',height='.$cfg["ui_dim_superadmin_h"].'")
	}
	</script>';
	$superAdminLink .= "<a href=\"JavaScript:SuperAdmin('superadmin.php".$param."')\">";
	if ((isset($linkText)) && ($linkText != ""))
		$superAdminLink .= $linkText;
	else
		$superAdminLink .= '<img src="images/arrow.gif" width="9" height="9" title="Version" border="0">';
	$superAdminLink .= '</a>';
	return $superAdminLink;
}

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
	$getBTClientSelect .= '</select>';
	return $getBTClientSelect;
}

/**
 * get form of sort-order-settings
 *
 */
function getSortOrderSettingsForm() {
	global $cfg;
	$sortOrderSettingsForm = '<select name="index_page_sortorder">';
	$sortOrderSettingsForm .= '<option value="da"';
	if ($cfg['index_page_sortorder'] == "da")
		$sortOrderSettingsForm .= " selected";
	$sortOrderSettingsForm .= '>Date - Ascending</option>';
	$sortOrderSettingsForm .= '<option value="dd"';
	if ($cfg['index_page_sortorder'] == "dd")
		$sortOrderSettingsForm .= " selected";
	$sortOrderSettingsForm .= '>Date - Descending</option>';
	$sortOrderSettingsForm .= '<option value="na"';
	if ($cfg['index_page_sortorder'] == "na")
		$sortOrderSettingsForm .= " selected";
	$sortOrderSettingsForm .= '>Name - Ascending</option>';
	$sortOrderSettingsForm .= '<option value="nd"';
	if ($cfg['index_page_sortorder'] == "nd")
		$sortOrderSettingsForm .= " selected";
	$sortOrderSettingsForm .= '>Name - Descending</option>';
	$sortOrderSettingsForm .= '</select>';
	return $sortOrderSettingsForm;
}

/**
 * get form of move-settings
 *
 */
function getMoveSettingsForm() {
	global $cfg;
	$moveSettingsForm = '<table>';
	$moveSettingsForm .= '<tr>';
	$moveSettingsForm .= '<td valign="top" align="left">Target-Dirs:</td>';
	$moveSettingsForm .= '<td valign="top" align="left">';
	$moveSettingsForm .= '<select name="categorylist" size="5">';
	if ((isset($cfg["move_paths"])) && (strlen($cfg["move_paths"]) > 0)) {
		$dirs = split(":", trim($cfg["move_paths"]));
		foreach ($dirs as $dir) {
			$target = trim($dir);
			if ((strlen($target) > 0) && ((substr($target, 0, 1)) != ";"))
				$moveSettingsForm .= "<option value=\"$target\">".$target."</option>\n";
		}
	}
	$moveSettingsForm .= '</select>';
	$moveSettingsForm .= '<input type="button" name="remCatButton" value="remove" onclick="removeEntry()">';
	$moveSettingsForm .= '</td>';
	$moveSettingsForm .= '</tr>';
	$moveSettingsForm .= '<tr>';
	$moveSettingsForm .= '<td valign="top" align="left">New Target-Dir:</td>';
	$moveSettingsForm .= '<td valign="top" align="left">';
	$moveSettingsForm .= '<input type="text" name="category" size="30">';
	$moveSettingsForm .= '<input type="button" name="addCatButton" value="add" onclick="addEntry()" size="30">';
	$moveSettingsForm .= '<input type="hidden" name="move_paths" value="'.$cfg["move_paths"].'">';
	$moveSettingsForm .= '</td>';
	$moveSettingsForm .= '</tr>';
	$moveSettingsForm .= '</table>';
	return $moveSettingsForm;
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

/**
 * transferListXferUpdate1
 *
 * @param $entry
 * @param $transferowner
 * @param $af
 * @param $settingsAry
 * @return unknown
 */
function transferListXferUpdate1($entry, $transferowner, $af, $settingsAry) {
	global $cfg, $db;
	$transferTotalsCurrent = getTransferTotalsCurrentOP($entry, $settingsAry['hash'], $settingsAry['btclient'], $af->uptotal, $af->downtotal);
	$newday = 0;
	$sql = 'SELECT 1 FROM tf_xfer WHERE date = '.$db->DBDate(time());
	$newday = !$db->GetOne($sql);
	showError($db,$sql);
	sumUsage($transferowner, ($transferTotalsCurrent["downtotal"]+0), ($transferTotalsCurrent["uptotal"]+0), 'total');
	sumUsage($transferowner, ($transferTotalsCurrent["downtotal"]+0), ($transferTotalsCurrent["uptotal"]+0), 'month');
	sumUsage($transferowner, ($transferTotalsCurrent["downtotal"]+0), ($transferTotalsCurrent["uptotal"]+0), 'week');
	sumUsage($transferowner, ($transferTotalsCurrent["downtotal"]+0), ($transferTotalsCurrent["uptotal"]+0), 'day');
	//XFER: if new day add upload/download totals to last date on record and subtract from today in SQL
	if ($newday) {
		$newday = 2;
		$sql = 'SELECT date FROM tf_xfer ORDER BY date DESC';
		$lastDate = $db->GetOne($sql);
		showError($db,$sql);
		// MySQL 4.1.0 introduced 'ON DUPLICATE KEY UPDATE' to make this easier
		$sql = 'SELECT 1 FROM tf_xfer WHERE user = "'.$transferowner.'" AND date = "'.$lastDate.'"';
		if ($db->GetOne($sql)) {
			$sql = 'UPDATE tf_xfer SET download = download+'.($transferTotalsCurrent["downtotal"]+0).', upload = upload+'.($transferTotalsCurrent["uptotal"]+0).' WHERE user = "'.$transferowner.'" AND date = "'.$lastDate.'"';
			$db->Execute($sql);
			showError($db,$sql);
		} else {
			showError($db,$sql);
			$sql = 'INSERT INTO tf_xfer (user,date,download,upload) values ("'.$transferowner.'","'.$lastDate.'",'.($transferTotalsCurrent["downtotal"]+0).','.($transferTotalsCurrent["uptotal"]+0).')';
			$db->Execute($sql);
			showError($db,$sql);
		}
		$sql = 'SELECT 1 FROM tf_xfer WHERE user = "'.$transferowner.'" AND date = '.$db->DBDate(time());
		if ($db->GetOne($sql)) {
			$sql = 'UPDATE tf_xfer SET download = download-'.($transferTotalsCurrent["downtotal"]+0).', upload = upload-'.($transferTotalsCurrent["uptotal"]+0).' WHERE user = "'.$transferowner.'" AND date = '.$db->DBDate(time());
			$db->Execute($sql);
			showError($db,$sql);
		} else {
			showError($db,$sql);
			$sql = 'INSERT INTO tf_xfer (user,date,download,upload) values ("'.$transferowner.'",'.$db->DBDate(time()).',-'.($transferTotalsCurrent["downtotal"]+0).',-'.($transferTotalsCurrent["uptotal"]+0).')';
			$db->Execute($sql);
			showError($db,$sql);
		}
	}
	return $newday;
}

/**
 * transferListXferUpdate2
 *
 * @param $newday
 */
function transferListXferUpdate2($newday) {
	global $cfg, $db;
	if ($newday == 1) {
		$sql = 'INSERT INTO tf_xfer (user,date) values ( "",'.$db->DBDate(time()).')';
		$db->Execute($sql);
		showError($db,$sql);
	}
	getUsage(0, 'total');
	$month_start = (date('j')>=$cfg['month_start']) ? date('Y-m-').$cfg['month_start'] : date('Y-m-',strtotime('-1 Month')).$cfg['month_start'];
	getUsage($month_start, 'month');
	$week_start = date('Y-m-d',strtotime('last '.$cfg['week_start']));
	getUsage($week_start, 'week');
	$day_start = date('Y-m-d');
	getUsage($day_start, 'day');
}

/*
 * rnatcasesort
 *
 * @param &$a ref to array to sort
 */
function rnatcasesort(&$a){
   natcasesort($a);
   $a = array_reverse($a, true);
}

/*
 * This method gets transfers in an array
 *
 * @param $sortOrder
 * @return array with transfers
 */
function getTransferArray($sortOrder = '') {
	global $cfg;
	$arList = array();
	$file_filter = getFileFilter($cfg["file_types_array"]);
	if (is_dir($cfg["torrent_file_path"]))
		$handle = opendir($cfg["torrent_file_path"]);
	else
		return null;
	while($entry = readdir($handle)) {
		if ($entry != "." && $entry != "..") {
			if (is_dir($cfg["torrent_file_path"]."/".$entry)) {
				// don''t do a thing
			} else {
				if (ereg($file_filter, $entry)) {
					$key = filemtime($cfg["torrent_file_path"]."/".$entry).md5($entry);
					$arList[$key] = $entry;
				}
			}
		}
	}
	closedir($handle);
	// sort transfer-array
	$sortId = "";
	if ((isset($sortOrder)) && ($sortOrder != ""))
		$sortId = $sortOrder;
	else
		$sortId = $cfg["index_page_sortorder"];
	switch ($sortId) {
		case 'da': // sort by date ascending
			ksort($arList);
			break;
		case 'dd': // sort by date descending
			krsort($arList);
			break;
		case 'na': // sort alphabetically by name ascending
			natcasesort($arList);
			break;
		case 'nd': // sort alphabetically by name descending
			rnatcasesort($arList);
			break;
	}
	return $arList;
}

/**
 * This method gets the head of the transfer-list
 *
 * @param $settings
 * @return transfer-list-head array
 */
function getTransferListHeadArray($settings = null) {
	global $cfg;
	// settings
	if (!(isset($settings)))
		$settings = convertIntegerToArray($cfg["index_page_settings"]);
	// retval
	$retVal = array();
	// =================================================================== owner
	if ($settings[0] != 0)
		array_push($retVal, _USER);
	// ==================================================================== size
	if ($settings[1] != 0)
		array_push($retVal, "Size");
	// =============================================================== downtotal
	if ($settings[2] != 0)
		array_push($retVal, "T. Down");
	// ================================================================= uptotal
	if ($settings[3] != 0)
		array_push($retVal, "T. Up");
	// ================================================================== status
	if ($settings[4] != 0)
		array_push($retVal, _STATUS);
	// ================================================================ progress
	if ($settings[5] != 0)
		array_push($retVal, "Progress");
	// ==================================================================== down
	if ($settings[6] != 0)
		array_push($retVal, "Down");
	// ====================================================================== up
	if ($settings[7] != 0)
		array_push($retVal, "Up");
	// =================================================================== seeds
	if ($settings[8] != 0)
		array_push($retVal, "Seeds");
	// =================================================================== peers
	if ($settings[9] != 0)
		array_push($retVal, "Peers");
	// ===================================================================== ETA
	if ($settings[10] != 0)
		array_push($retVal, _ESTIMATEDTIME);
	// ================================================================== client
	if ($settings[11] != 0)
		array_push($retVal, "Client");
	// return
	return $retVal;
}

/*
 * This method gets the list of transfer
 *
 * @return transfer-list 2-dim array
 */
function getTransferListArray() {
	global $cfg, $db;
	require_once("inc/classes/AliasFile.php");
	$kill_id = "";
	$lastUser = "";
	$arUserTorrent = array();
	$arListTorrent = array();
	// settings
	$settings = convertIntegerToArray($cfg["index_page_settings"]);
	// sortOrder
	$sortOrder = getRequestVar("so");
	if ($sortOrder == "")
		$sortOrder = $cfg["index_page_sortorder"];
	// t-list
	$arList = getTransferArray($sortOrder);
	foreach($arList as $entry) {

		// ---------------------------------------------------------------------
		// init some vars
		$displayname = $entry;
		$show_run = true;

		// ---------------------------------------------------------------------
		// alias / stat
		$alias = getAliasName($entry).".stat";
		if ((substr(strtolower($entry),-8 ) == ".torrent")) {
			// this is a torrent-client
			$isTorrent = true;
			$transferowner = getOwner($entry);
			$owner = IsOwner($cfg["user"], $transferowner);
			$settingsAry = loadTorrentSettings($entry);
			$af = AliasFile::getAliasFileInstance($cfg["torrent_file_path"].$alias, $transferowner, $cfg, $settingsAry['btclient']);
		} else if ((substr(strtolower($entry),-5 ) == ".wget")) {
			// this is wget.
			$isTorrent = false;
			$transferowner = $cfg["user"];
			$owner = true;
			$settingsAry = array();
			$settingsAry['btclient'] = "wget";
			$settingsAry['hash'] = $entry;
			$af = AliasFile::getAliasFileInstance($cfg["torrent_file_path"].$alias, $cfg['user'], $cfg, 'wget');
		} else {
			// this is "something else". use tornado statfile as default
			$isTorrent = false;
			$transferowner = $cfg["user"];
			$owner = true;
			$settingsAry = array();
			$settingsAry['btclient'] = "tornado";
			$settingsAry['hash'] = $entry;
			$af = AliasFile::getAliasFileInstance($cfg["torrent_file_path"].$alias, $cfg['user'], $cfg, 'tornado');
		}
		// cache running-flag in local var. we will access that often
		$transferRunning = (int) $af->running;
		// cache percent-done in local var. ...
		$percentDone = $af->percent_done;

		// ---------------------------------------------------------------------
		//XFER: add upload/download stats to the xfer array
		if (($cfg['enable_xfer'] == 1) && ($cfg['xfer_realtime'] == 1))
			$newday = transferListXferUpdate1($entry, $transferowner, $af, $settingsAry);

		// ---------------------------------------------------------------------
		// injects
		if(! file_exists($cfg["torrent_file_path"].$alias)) {
			$transferRunning = 2;
			$af->running = "2";
			$af->size = getDownloadSize($cfg["torrent_file_path"].$entry);
			$af->WriteFile();
		}

		// totals-preparation
		// if downtotal + uptotal + progress > 0
		if (($settings[2] + $settings[3] + $settings[5]) > 0)
			$transferTotals = getTransferTotalsOP($entry, $settingsAry['hash'], $settingsAry['btclient'], $af->uptotal, $af->downtotal);

		// ---------------------------------------------------------------------
		// preprocess alias-file and get some vars
		$estTime = "";
		$statusStr = "";
		switch ($transferRunning) {
			case 2: // new
				$statusStr = 'New';
				break;
			case 3: // queued
				$statusStr = 'Queued';
				$estTime = 'Waiting';
				break;
			default: // running
				// increment the totals
				if(!isset($cfg["total_upload"])) $cfg["total_upload"] = 0;
				if(!isset($cfg["total_download"])) $cfg["total_download"] = 0;
				$cfg["total_upload"] = $cfg["total_upload"] + GetSpeedValue($af->up_speed);
				$cfg["total_download"] = $cfg["total_download"] + GetSpeedValue($af->down_speed);
				// $estTime
				if ($af->time_left != "" && $af->time_left != "0") {
					if ( ($cfg["display_seeding_time"]) && ($af->percent_done >= 100) ) {
						if (($af->seedlimit > 0) && (!empty($af->up_speed)) && ((int) ($af->up_speed{0}) > 0))
							$estTime = convertTime(((($af->seedlimit) / 100 * $af->size) - $af->uptotal) / GetSpeedInBytes($af->up_speed)) . " left";
						else
							$estTime = '-';
					} else {
						$estTime = $af->time_left;
					}
				}
				// $lastUser
				$lastUser = $transferowner;
				// $show_run + $statusStr
				if($percentDone >= 100) {
					if(trim($af->up_speed) != "" && $transferRunning == 1) {
						$statusStr = 'Seeding';
					} else {
						$statusStr = 'Done';
					}
					$show_run = false;
				} else if ($percentDone < 0) {
					$statusStr = 'Stopped';
					$show_run = true;
				} else {
					$statusStr = 'Leeching';
				}
				break;
		}

		// ---------------------------------------------------------------------
		// fill temp array
		$transferAry = array();

		// ================================================================ name
		array_push($transferAry, $entry);

		// =============================================================== owner
		if ($settings[0] != 0)
			array_push($transferAry, $transferowner);

		// ================================================================ size
		if ($settings[1] != 0)
			array_push($transferAry, formatBytesTokBMBGBTB($af->size));

		// =========================================================== downtotal
		if ($settings[2] != 0)
			array_push($transferAry, formatBytesTokBMBGBTB($transferTotals["downtotal"]+0));

		// ============================================================= uptotal
		if ($settings[3] != 0)
			array_push($transferAry, formatBytesTokBMBGBTB($transferTotals["uptotal"]+0));

		// ============================================================== status
		if ($settings[4] != 0)
			array_push($transferAry, $statusStr);

		// ============================================================ progress
		if ($settings[5] != 0) {
			$percentage = "";
			if (($percentDone >= 100) && (trim($af->up_speed) != "")) {
				$percentage = @number_format((($transferTotals["uptotal"] / $af->size) * 100), 2) . '%';
			} else {
				if ($percentDone >= 1) {
					$percentage = $percentDone . '%';
				} else if ($percentDone < 0) {
					$percentage = round(($percentDone*-1)-100,1) . '%';
				} else {
					$percentage = '0%';
				}
			}
			array_push($transferAry, $percentage);
		}

		// ================================================================ down
		if ($settings[6] != 0) {
			$down = "";
			if ($transferRunning == 1) {
				if (trim($af->down_speed) != "")
					$down = $af->down_speed;
				else
					$down = '0.0 kB/s';
			}
			array_push($transferAry, $down);
		}

		// ================================================================== up
		if ($settings[7] != 0) {
			$up = "";
			if ($transferRunning == 1) {
				if (trim($af->up_speed) != "")
					$up = $af->up_speed;
				else
					$up = '0.0 kB/s';
			}
			array_push($transferAry, $up);
		}

		// =============================================================== seeds
		if ($settings[8] != 0) {
			$seeds = "";
			if ($transferRunning == 1)
				$seeds = $af->seeds;
			array_push($transferAry, $seeds);
		}

		// =============================================================== peers
		if ($settings[9] != 0) {
			$peers = "";
			if ($transferRunning == 1)
				$peers = $af->peers;
			array_push($transferAry, $peers);
		}

		// ================================================================= ETA
		if ($settings[10] != 0)
			array_push($transferAry, $estTime);

		// ============================================================== client
		if ($settings[11] != 0) {
			switch ($settingsAry['btclient']) {
				case "tornado":
					array_push($transferAry, "B");
				break;
				case "transmission":
					array_push($transferAry, "T");
				break;
				case "wget":
					array_push($transferAry, "W");
				break;
				default:
					array_push($transferAry, "U");
			}
		}

		// ---------------------------------------------------------------------
		// Is this torrent for the user list or the general list?
		if ($owner)
			array_push($arUserTorrent, $transferAry);
		else
			array_push($arListTorrent, $transferAry);
	}

	//XFER: if a new day but no .stat files where found put blank entry into the
	//      DB for today to indicate accounting has been done for the new day
	if (($cfg['enable_xfer'] == 1) && ($cfg['xfer_realtime'] == 1))
		transferListXferUpdate2($newday);

	// -------------------------------------------------------------------------
	// build output-array
	$retVal = array();
	if (sizeof($arUserTorrent) > 0) {
		foreach($arUserTorrent as $torrentrow)
			array_push($retVal, $torrentrow);
	}
	$boolCond = true;
	if ($cfg['enable_restrictivetview'] == 1)
		$boolCond = IsAdmin();
	if (($boolCond) && (sizeof($arListTorrent) > 0)) {
		foreach($arListTorrent as $torrentrow)
			array_push($retVal, $torrentrow);
	}
	return $retVal;
}

/**
 * This method Builds the Transfers Section of the Index Page
 *
 * @return transfer-list as html-string
 */
function getTransferListString() {
	global $cfg, $db;
	require_once("inc/classes/AliasFile.php");
	$kill_id = "";
	$lastUser = "";
	$arUserTorrent = array();
	$arListTorrent = array();
	// settings
	$settings = convertIntegerToArray($cfg["index_page_settings"]);
	// sortOrder
	$sortOrder = getRequestVar("so");
	if ($sortOrder == "")
		$sortOrder = $cfg["index_page_sortorder"];
	// t-list
	$arList = getTransferArray($sortOrder);
	foreach($arList as $entry) {

		// ---------------------------------------------------------------------
		// init some vars
		$displayname = $entry;
		$show_run = true;
		if(strlen($entry) >= 47) {
			// needs to be trimmed
			$displayname = substr($entry, 0, 44);
			$displayname .= "...";
		}
		if ($cfg["enable_torrent_download"])
			$torrentfilelink = "<a href=\"index.php?iid=maketorrent&download=".urlencode($entry)."\"><img src=\"images/down.gif\" width=9 height=9 title=\"Download Torrent File\" border=0 align=\"absmiddle\"></a>";
		else
			$torrentfilelink = "";

		// ---------------------------------------------------------------------
		// alias / stat
		$alias = getAliasName($entry).".stat";
		if ((substr( strtolower($entry),-8 ) == ".torrent")) {
			// this is a torrent-client
			$isTorrent = true;
			$transferowner = getOwner($entry);
			$owner = IsOwner($cfg["user"], $transferowner);
			$settingsAry = loadTorrentSettings($entry);
			$af = AliasFile::getAliasFileInstance($cfg["torrent_file_path"].$alias, $transferowner, $cfg, $settingsAry['btclient']);
		} else if ((substr( strtolower($entry),-5 ) == ".wget")) {
			// this is wget.
			$isTorrent = false;
			$transferowner = $cfg["user"];
			$owner = true;
			$settingsAry = array();
			$settingsAry['btclient'] = "wget";
			$settingsAry['hash'] = $entry;
			$settingsAry['savepath'] = $cfg['path'].$transferowner."/";;
			$settingsAry['datapath'] = "";
			$af = AliasFile::getAliasFileInstance($cfg["torrent_file_path"].$alias, $cfg['user'], $cfg, 'wget');
		} else {
			// this is "something else". use tornado statfile as default
			$isTorrent = false;
			$transferowner = $cfg["user"];
			$owner = true;
			$settingsAry = array();
			$settingsAry['btclient'] = "tornado";
			$settingsAry['hash'] = $entry;
			$settingsAry['savepath'] = $cfg['path'].$transferowner."/";;
			$settingsAry['datapath'] = "";
			$af = AliasFile::getAliasFileInstance($cfg["torrent_file_path"].$alias, $cfg['user'], $cfg, 'tornado');
		}
		// cache running-flag in local var. we will access that often
		$transferRunning = (int) $af->running;
		// cache percent-done in local var. ...
		$percentDone = $af->percent_done;

		// more vars
		$detailsLinkString = "<a style=\"font-size:9px; text-decoration:none;\" href=\"JavaScript:ShowDetails('index.php?iid=downloaddetails&alias=".$alias."&torrent=".urlencode($entry)."')\">";

		// ---------------------------------------------------------------------
		//XFER: add upload/download stats to the xfer array
		if (($cfg['enable_xfer'] == 1) && ($cfg['xfer_realtime'] == 1))
			$newday = transferListXferUpdate1($entry, $transferowner, $af, $settingsAry);

		// ---------------------------------------------------------------------
		// injects
		if(! file_exists($cfg["torrent_file_path"].$alias)) {
			$transferRunning = 2;
			$af->running = "2";
			$af->size = getDownloadSize($cfg["torrent_file_path"].$entry);
			$af->WriteFile();
		}

		// totals-preparation
		// if downtotal + uptotal + progress > 0
		if (($settings[2] + $settings[3] + $settings[5]) > 0)
			$transferTotals = getTransferTotalsOP($entry, $settingsAry['hash'], $settingsAry['btclient'], $af->uptotal, $af->downtotal);

		// ---------------------------------------------------------------------
		// preprocess alias-file and get some vars
		$estTime = "&nbsp;";
		$statusStr = "&nbsp;";
		switch ($transferRunning) {
			case 2: // new
				$statusStr = $detailsLinkString."<font color=\"#32cd32\">New</font></a>";
				break;
			case 3: // queued
				$statusStr = $detailsLinkString."Queued</a>";
				$estTime = "Waiting...";
				break;
			default: // running
				// increment the totals
				if(!isset($cfg["total_upload"])) $cfg["total_upload"] = 0;
				if(!isset($cfg["total_download"])) $cfg["total_download"] = 0;
				$cfg["total_upload"] = $cfg["total_upload"] + GetSpeedValue($af->up_speed);
				$cfg["total_download"] = $cfg["total_download"] + GetSpeedValue($af->down_speed);
				// $estTime
				if ($af->time_left != "" && $af->time_left != "0")
					if ( ($cfg["display_seeding_time"]) && ($af->percent_done >= 100) ) {
						if (($af->seedlimit > 0) && (!empty($af->up_speed)) && ((int) ($af->up_speed{0}) > 0))
							$estTime = convertTime(((($af->seedlimit) / 100 * $af->size) - $af->uptotal) / GetSpeedInBytes($af->up_speed)) . " left";
						else
							$estTime = '&#8734';
					} else {
						$estTime = $af->time_left;
					}
				// $lastUser
				$lastUser = $transferowner;
				// $show_run + $statusStr
				if($percentDone >= 100) {
					if(trim($af->up_speed) != "" && $transferRunning == 1) {
						$statusStr = $detailsLinkString.'Seeding</a>';
					} else {
						$statusStr = $detailsLinkString.'Done</a>';
					}
					$show_run = false;
				} else if ($percentDone < 0) {
					$statusStr = $detailsLinkString."Stopped</a>";
					$show_run = true;
				} else {
					$statusStr = $detailsLinkString."Leeching</a>";
				}
				break;
		}

		// ---------------------------------------------------------------------
		// output-string
		$output = "<tr>";

		// ========================================================== led + meta
		$output .= '<td valign="bottom" align="center" nowrap>';
		// led
		$hd = getStatusImage($af);
		if ($transferRunning == 1)
			$output .= "<a href=\"JavaScript:ShowDetails('index.php?iid=downloadhosts&alias=".$alias."&torrent=".urlencode($entry)."')\">";
		$output .= "<img src=\"images/".$hd->image."\" width=\"16\" height=\"16\" title=\"".$hd->title.$entry."\" border=\"0\" align=\"absmiddle\">";
		if ($transferRunning == 1)
			$output .= "</a>";
		// meta
		$output .= $torrentfilelink;
		$output .= "</td>";

		// ================================================================ name
		$output .= "<td valign=\"bottom\" nowrap>".$detailsLinkString.$displayname."</a></td>";

		// =============================================================== owner
		if ($settings[0] != 0)
			$output .= "<td valign=\"bottom\" align=\"center\" nowrap><a href=\"index.php?iid=message&to_user=".$transferowner."\"><font class=\"tiny\">".$transferowner."</font></a></td>";

		// ================================================================ size
		if ($settings[1] != 0)
			$output .= "<td valign=\"bottom\" align=\"right\" nowrap>".$detailsLinkString.formatBytesTokBMBGBTB($af->size)."</a></td>";

		// =========================================================== downtotal
		if ($settings[2] != 0)
			$output .= "<td valign=\"bottom\" align=\"right\" nowrap>".$detailsLinkString.formatBytesTokBMBGBTB($transferTotals["downtotal"]+0)."</a></td>";

		// ============================================================= uptotal
		if ($settings[3] != 0)
			$output .= "<td valign=\"bottom\" align=\"right\" nowrap>".$detailsLinkString.formatBytesTokBMBGBTB($transferTotals["uptotal"]+0)."</a></td>";

		// ============================================================== status
		if ($settings[4] != 0)
			$output .= "<td valign=\"bottom\" align=\"center\" nowrap>".$detailsLinkString.$statusStr."</a></td>";

		// ============================================================ progress
		if ($settings[5] != 0) {
			$graph_width = 1;
			$progress_color = "#00ff00";
			$background = "#000000";
			$bar_width = "4";
			$percentage = "";
			if (($percentDone >= 100) && (trim($af->up_speed) != "")) {
				$graph_width = -1;
				$percentage = @number_format((($transferTotals["uptotal"] / $af->size) * 100), 2) . '%';
			} else {
				if ($percentDone >= 1) {
					$graph_width = $percentDone;
					$percentage = $graph_width . '%';
				} else if ($percentDone < 0) {
					$graph_width = round(($percentDone*-1)-100,1);
					$percentage = $graph_width . '%';
				} else {
					$graph_width = 0;
					$percentage = '0%';
				}
			}
			if($graph_width == 100)
				$background = $progress_color;
			$output .= "<td valign=\"bottom\" align=\"center\" nowrap>";
			if ($graph_width == -1) {
				$output .= $detailsLinkString.'<strong>'.$percentage.'</strong></a>';
			} else if ($graph_width > 0) {
				$output .= $detailsLinkString.'<strong>'.$percentage.'</strong></a>';
				$output .= "<br>";
				$output .= "<table width=\"100\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr>";
				$output .= "<td background=\"themes/".$cfg["theme"]."/images/progressbar.gif\" bgcolor=\"".$progress_color."\">".$detailsLinkString."<img src=\"images/blank.gif\" width=\"".$graph_width."\" height=\"".$bar_width."\" border=\"0\"></a></td>";
				$output .= "<td bgcolor=\"".$background."\">".$detailsLinkString."<img src=\"images/blank.gif\" width=\"".(100 - $graph_width)."\" height=\"".$bar_width."\" border=\"0\"></a></td>";
				$output .= "</tr></table>";
			} else {
				if ($transferRunning == 2) {
					$output .= '&nbsp;';
				} else {
					$output .= $detailsLinkString.'<strong>'.$percentage.'</strong></a>';
					$output .= "<br>";
					$output .= "<table width=\"100\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr>";
					$output .= "<td background=\"themes/".$cfg["theme"]."/images/progressbar.gif\" bgcolor=\"".$progress_color."\">".$detailsLinkString."<img src=\"images/blank.gif\" width=\"".$graph_width."\" height=\"".$bar_width."\" border=\"0\"></a></td>";
					$output .= "<td bgcolor=\"".$background."\">".$detailsLinkString."<img src=\"images/blank.gif\" width=\"".(100 - $graph_width)."\" height=\"".$bar_width."\" border=\"0\"></a></td>";
					$output .= "</tr></table>";
				}
			}
			$output .= "</td>";
		}

		// ================================================================ down
		if ($settings[6] != 0) {
			$output .= '<td valign="bottom" align="right" class="tiny" nowrap>';
			if ($transferRunning == 1) {
				$output .= $detailsLinkString;
				if (trim($af->down_speed) != "")
					$output .= $af->down_speed;
				else
					$output .= '0.0 kB/s';
				$output .= '</a>';
			} else {
				 $output .= '&nbsp;';
			}
			$output .= '</td>';
		}

		// ================================================================== up
		if ($settings[7] != 0) {
			$output .= '<td valign="bottom" align="right" class="tiny" nowrap>';
			if ($transferRunning == 1) {
				$output .= $detailsLinkString;
				if (trim($af->up_speed) != "")
					$output .= $af->up_speed;
				else
					$output .= '0.0 kB/s';
				$output .= '</a>';
			} else {
				 $output .= '&nbsp;';
			}
			$output .= '</td>';
		}

		// =============================================================== seeds
		if ($settings[8] != 0) {
			$output .= '<td valign="bottom" align="right" class="tiny" nowrap>';
			if ($transferRunning == 1) {
				$output .= $detailsLinkString;
				$output .= $af->seeds;
				$output .= '</a>';
			} else {
				 $output .= '&nbsp;';
			}
			$output .= '</td>';
		}

		// =============================================================== peers
		if ($settings[9] != 0) {
			$output .= '<td valign="bottom" align="right" class="tiny" nowrap>';
			if ($transferRunning == 1) {
				$output .= $detailsLinkString;
				$output .= $af->peers;
				$output .= '</a>';
			} else {
				 $output .= '&nbsp;';
			}
			$output .= '</td>';
		}

		// ================================================================= ETA
		if ($settings[10] != 0)
			$output .= "<td valign=\"bottom\" align=\"center\" nowrap>".$detailsLinkString.$estTime."</a></td>";

		// ============================================================== client
		if ($settings[11] != 0) {
			switch ($settingsAry['btclient']) {
				case "tornado":
					$output .= "<td valign=\"bottom\" align=\"center\">B</a></td>";
				break;
				case "transmission":
					$output .= "<td valign=\"bottom\" align=\"center\">T</a></td>";
				break;
				case "wget":
					$output .= "<td valign=\"bottom\" align=\"center\">W</a></td>";
				break;
				default:
					$output .= "<td valign=\"bottom\" align=\"center\">U</a></td>";
			}
		}

		// =============================================================== admin
		$output .= '<td nowrap>';
		include('inc/iid/index/admincell.php');
		$output .= "</td>";
		$output .= "</tr>\n";

		// ---------------------------------------------------------------------
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

	// -------------------------------------------------------------------------
	// build output-string
	$output = '<table bgcolor="'.$cfg["table_data_bg"].'" width="100%" bordercolor="'.$cfg["table_border_dk"].'" border="1" cellpadding="3" cellspacing="0" class="sortable" id="transfer_table">';
	if (sizeof($arUserTorrent) > 0) {
		$output .= getTransferTableHead($settings, $sortOrder, $cfg["user"]." : ");
		foreach($arUserTorrent as $torrentrow)
			$output .= $torrentrow;
	}
	$boolCond = true;
	if ($cfg['enable_restrictivetview'] == 1)
		$boolCond = IsAdmin();
	if (($boolCond) && (sizeof($arListTorrent) > 0)) {
		$output .= getTransferTableHead($settings, $sortOrder);
		foreach($arListTorrent as $torrentrow)
			$output .= $torrentrow;
	}
	$output .= "</tr></table>\n";
	return $output;
}

/*
 * This method gets html-snip of table-head
 *
 * @param $settings ref to array holding index-page-settings
 * @param $sortOrder
 * @param $nPrefix prefix of name-column
 * @return string with head-row
 */
function getTransferTableHead($settings, $sortOrder = '', $nPrefix = '') {
	global $cfg;
	$output = "<tr>";
	//
	// ============================================================== led + meta
	$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">";
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
	// ==================================================================== name
	$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">";
	switch ($sortOrder) {
		case 'na': // sort alphabetically by name ascending
			$output .= '<a href="?so=nd"><font class="adminlink">' .$nPrefix. _TRANSFERFILE .'</font></a>';
			$output .= '&nbsp;';
			$output .= '<a href="?so=nd"><img src="images/s_down.gif" width="9" height="9" border="0"></a>';
			break;
		case 'nd': // sort alphabetically by name descending
			$output .= '<a href="?so=na"><font class="adminlink">' .$nPrefix. _TRANSFERFILE .'</font></a>';
			$output .= '&nbsp;';
			$output .= '<a href="?so=na"><img src="images/s_up.gif" width="9" height="9" border="0"></a>';
			break;
		default:
			$output .= '<a href="?so=na"><font class="adminlink">' .$nPrefix. _TRANSFERFILE .'</font></a>';
			break;
	}
	$output .= "</div></td>";
	// =================================================================== owner
	if ($settings[0] != 0)
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">"._USER."</div></td>";
	// ==================================================================== size
	if ($settings[1] != 0)
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">Size</div></td>";
	// =============================================================== downtotal
	if ($settings[2] != 0)
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">T. Down</div></td>";
	// ================================================================= uptotal
	if ($settings[3] != 0)
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">T. Up</div></td>";
	// ================================================================== status
	if ($settings[4] != 0)
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">"._STATUS."</div></td>";
	// ================================================================ progress
	if ($settings[5] != 0)
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">Progress</div></td>";
	// ==================================================================== down
	if ($settings[6] != 0)
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">Down</div></td>";
	// ====================================================================== up
	if ($settings[7] != 0)
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">Up</div></td>";
	// =================================================================== seeds
	if ($settings[8] != 0)
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">Seeds</div></td>";
	// =================================================================== peers
	if ($settings[9] != 0)
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">Peers</div></td>";
	// ===================================================================== ETA
	if ($settings[10] != 0)
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">"._ESTIMATEDTIME."</div></td>";
	// ================================================================== client
	if ($settings[11] != 0)
		$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">C</div></td>";
	// =================================================================== admin
	$output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">"._ADMIN."</div></td>";
	//
	$output .= "</tr>\n";
	// return
	return $output;
}

/**
 * get the Upload Graphical Bar
 *
 * @return string with upload-bar
 */
function getUploadBar() {
	global $cfg;
	$max_upload = $cfg["bandwidth_up"] / 8;
	if ($max_upload > 0)
		$percent = number_format(($cfg["total_upload"] / $max_upload) * 100, 0);
	else
		$percent = 0;
	if ($percent > 0)
		$text = " (".number_format($cfg["total_upload"], 2)." Kb/s)";
	else
		$text = "";
	switch ($cfg['bandwidthbar']) {
		case "tf":
			return getBandwidthBar_tf($percent, $text);
		case "xfer":
			return getBandwidthBar_xfer($percent, $text);
	}
}

/**
 * get the Download Graphical Bar
 *
 * @return string with download-bar
 */
function getDownloadBar() {
	global $cfg;
	$max_download = $cfg["bandwidth_down"] / 8;
	if ($max_download > 0)
		$percent = number_format(($cfg["total_download"] / $max_download) * 100, 0);
	else
		$percent = 0;
	if ($percent > 0)
		$text = " (".number_format($cfg["total_download"], 2)." Kb/s)";
	else
		$text = "";
	switch ($cfg['bandwidthbar']) {
		case "tf":
			return getBandwidthBar_tf($percent, $text);
		case "xfer":
			return getBandwidthBar_xfer($percent, $text);
	}
}

/**
 * get a Bandwidth Graphical Bar in tf-style
 *
 * @param $percent
 * @param $text
 * @return string with bandwith-bar
 */
function getBandwidthBar_tf($percent, $text) {
	global $cfg;
	$retVal = "";
    $retVal .= '<table width="100%" border="0" cellpadding="0" cellspacing="0">';
    $retVal .= ' <tr nowrap>';
    $retVal .= '  <td width="80%">';
    $retVal .= '   <table width="100%" border="0" cellpadding="0" cellspacing="0">';
    $retVal .= '    <tr>';
    $retVal .= '     <td background="themes/'.$cfg["theme"].'/images/proglass.gif" width="'.$percent.'%"><div class="tinypercent" align="center">'.$percent.'%'.$text.'</div></td>';
    $retVal .= '     <td background="themes/'.$cfg["theme"].'/images/noglass.gif" width="'.(100 - $percent).'%"><img src="images/blank.gif" width="1" height="3" border="0"></td>';
    $retVal .= '    </tr>';
    $retVal .= '   </table>';
    $retVal .= '  </td>';
    $retVal .= ' </tr>';
    $retVal .= '</table>';
	return $retVal;
}

/**
 * get a Bandwidth Graphical Bar in xfer-style
 *
 * @param $percent
 * @param $text
 * @return string with bandwith-bar
 */
function getBandwidthBar_xfer($percent, $text) {
	global $cfg;
	//$percent = 0;
	$bgcolor = '#';
	$bgcolor .= str_pad(dechex(255 - 255 * ((100 - $percent) / 150)), 2, 0, STR_PAD_LEFT);
	$bgcolor .= str_pad(dechex(255 * ((100 - $percent) / 150)), 2, 0, STR_PAD_LEFT);
	$bgcolor .='00';
	$retVal = "";
	$retVal .= '<table width="100%" border="0" cellpadding="0" cellspacing="0" style="margin-top:1px;margin-bottom:1px;"><tr>';
	$retVal .= '<td bgcolor="'.$bgcolor.'" width="'.($percent).'%">';
	if ($percent >= 50) {
		$retVal .= '<div class="tinypercent" align="center"';
		if ($percent == 100)
			$retVal .= ' style="background:#FF0000;">';
		else
			$retVal .= '>';
		$retVal .= $percent.'%'.$text;
		$retVal .= '</div>';
	}
	$retVal .= '</td>';
	$retVal .= '<td bgcolor="#000000" width="'.(100 - $percent).'%" height="100%">';
	if ($percent < 50) {
		$retVal .= '<div class="tinypercent" align="center" style="color:'.$bgcolor;
		if ($percent == 0)
			$retVal .= '; background:#000000;">';
		else
			$retVal .= ';">';
		$retVal .= $percent.'%'.$text;
		$retVal .= '</div>';
	}
	$retVal .= '</td>';
	$retVal .= '</tr></table>';
	return $retVal;
}


/* ************************************************************************** */


//******************************************************************************
// getRequestVar
//******************************************************************************
function getRequestVar($varName) {
    if (array_key_exists($varName,$_REQUEST))
        return trim($_REQUEST[$varName]);
    else
        return '';
}

//******************************************************************************
// AuditAction
//******************************************************************************
function AuditAction($action, $file="") {
    global $_SERVER, $cfg, $db;
    $host_resolved = gethostbyaddr($cfg['ip']);
    $create_time = time();
	if (isset($_SERVER['HTTP_USER_AGENT']))
	   $user_agent = $_SERVER['HTTP_USER_AGENT'];
	if ((! isset($user_agent)) || ($user_agent == ""))
			$user_agent = "fluxcli.php/unknown";
	if ((! isset($action)) || ($action == ""))
			$action = "unset";
    $rec = array(
    	'user_id' => $cfg['user'],
    	'file' => $file,
    	'action' => $action,
    	'ip' => $cfg['ip'],
    	'ip_resolved' => $host_resolved,
    	'user_agent' => $user_agent,
    	'time' => $create_time
        );
    $sTable = 'tf_log';
    $sql = $db->GetInsertSql($sTable, $rec);
    // add record to the log
    //$result = $db->Execute($sql);
    $db->Execute($sql);
    showError($db,$sql);
}

//******************************************************************************
// loadSettings
//******************************************************************************
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

//******************************************************************************
// insertSetting
//******************************************************************************
function insertSetting($key,$value) {
    global $cfg, $db;
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

//******************************************************************************
// updateSetting
//******************************************************************************
function updateSetting($key,$value) {
    global $cfg, $db;
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

//******************************************************************************
// saveSettings
//******************************************************************************
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

//******************************************************************************
// isFile
//******************************************************************************
function isFile($file) {
    $rtnValue = False;
    if (is_file($file)) {
        $rtnValue = True;
    } else {
        if ($file == trim(shell_exec("ls ".$file))) {
            $rtnValue = True;
        }
    }
    return $rtnValue;
}


/* ************************************************************************** */


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
	require_once("inc/classes/AliasFile.php");
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
							$driveSpaceBar .= ' style="background:#00FF00;">';
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
							$driveSpaceBar .= '; background:#FF0000;">';
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
		require_once("inc/classes/BDecode.php");
		$fd = fopen($torrent, "rd");
		$alltorrent = fread($fd, filesize($torrent));
		$array = BDecode($alltorrent);
		fclose($fd);
		$rtnValue = $array["info"]["piece length"] * (strlen($array["info"]["pieces"]) / 20);
	}
	return $rtnValue;
}

/**
 * Returns a string in format of TB, GB, MB, or kB depending on the size
 *
 * @param $inBytes
 * @return string
 */
function formatBytesTokBMBGBTB($inBytes) {
	$rsize = "";
	if ($inBytes > 1099511627776)
		$rsize = round($inBytes / 1099511627776, 2) . " TB";
	elseif ($inBytes > 1073741824)
		$rsize = round($inBytes / 1073741824, 2) . " GB";
	elseif ($inBytes < 1048576)
		$rsize = round($inBytes / 1024, 1) . " kB";
	else
		$rsize = round($inBytes / 1048576, 1) . " MB";
	return $rsize;
}

/**
 * Convert free space to TB, GB or MB depending on size
 *
 * @param $freeSpace
 * @return string
 */
function formatFreeSpace($freeSpace) {
	$rtnValue = "";
	if ($freeSpace > 1048576)
		$rtnValue = number_format($freeSpace / 1048576, 2)." TB";
	elseif ($freeSpace > 1024)
		$rtnValue = number_format($freeSpace / 1024, 2)." GB";
	else
		$rtnValue = number_format($freeSpace, 2)." MB";
	return $rtnValue;
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


/* ************************************************************************** */


//XFER:****************************************************
//XFER: getUsage(timestamp, usage_array)
//XFER: Gets upload/download usage for all users starting at timestamp from SQL
function getUsage($start, $period) {
  global $xfer, $xfer_total, $db;
  $sql = 'SELECT user, SUM(download) AS download, SUM(upload) AS upload FROM tf_xfer WHERE date >= "'.$start.'" AND user != "" GROUP BY user';
  $rtnValue = $db->GetAll($sql);
  showError($db,$sql);
  foreach ($rtnValue as $row) sumUsage($row[0], $row[1], $row[2], $period);
}

//XFER:****************************************************
//XFER: sumUsage(user, downloaded, uploaded, usage_array)
//XFER: Adds download/upload into correct usage_array (total, month, etc)
function sumUsage($user, $download, $upload, $period) {
  global $xfer, $xfer_total;
  @ $xfer[$user][$period]['download'] += $download;
  @ $xfer[$user][$period]['upload'] += $upload;
  @ $xfer[$user][$period]['total'] += $download + $upload;
  @ $xfer_total[$period]['download'] += $download;
  @ $xfer_total[$period]['upload'] += $upload;
  @ $xfer_total[$period]['total'] += $download + $upload;
}

//XFER:****************************************************
//XFER: saveXfer(user, download, upload)
//XFER: Inserts or updates SQL upload/download for user
function saveXfer($user, $down, $up) {
  global $db;
  $sql = 'SELECT 1 FROM tf_xfer WHERE user = "'.$user.'" AND date = '.$db->DBDate(time());
  if ($db->GetRow($sql)) {
    $sql = 'UPDATE tf_xfer SET download = download+'.($down+0).', upload = upload+'.($up+0).' WHERE user = "'.$user.'" AND date = '.$db->DBDate(time());
    $db->Execute($sql);
    showError($db,$sql);
  } else {
    showError($db,$sql);
    $sql = 'INSERT INTO tf_xfer (user,date,download,upload) values ("'.$user.'",'.$db->DBDate(time()).','.($down+0).','.($up+0).')';
    $db->Execute($sql);
    showError($db,$sql);
  }
}

// Link Mod
function getLinkSortOrder($lid) {
    global $db;
    // Get Current sort order index of link with this link id:
    $sql="SELECT sort_order FROM tf_links WHERE lid=$lid";
    $rtnValue=$db->GetOne($sql);
    showError($db,$sql);
    return $rtnValue;
}

//*********************************************************
function getSite($lid) {
    global $cfg, $db;
    $rtnValue = "";
    $sql = "SELECT sitename FROM tf_links WHERE lid=".$lid;
    $rtnValue = $db->GetOne($sql);
    return $rtnValue;
}
// Link Mod

// Some Stats dir hack
//*************************************************************************
// correctFileName()
// Adds backslashes above special characters to obtain attainable directory
// names for disk usage
function correctFileName ($inName) {
       $replaceItems = array("'", ",", "#", "%", "!", "+", ":", "/", " ", "@", "$", "&", "?", "\"", "(", ")");
       $replacedItems = array("\'", "\,", "\#", "\%", "\!", "\+", "\:", "\/", "\ ", "\@", "\$", "\&", "\?", "\\\"", "\(", "\)");
       $cleanName = str_replace($replaceItems, $replacedItems, $inName);
       return $cleanName;
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

// SFV Check hack
//*************************************************************************
// findSVF()
// This method Builds and displays the Torrent Section of the Index Page
function findSFV($dirName) {
	$sfv = false;
	$d = dir($dirName);
	while (false !== ($entry = $d->read())) {
   		if($entry != '.' && $entry != '..' && !empty($entry) ) {
			if((is_file($dirName.'/'.$entry)) && (strtolower(substr($entry, -4, 4)) == '.sfv')) {
				$sfv[dir] = $dirName;
				$sfv[sfv] = $dirName.'/'.$entry;
			}
	   	}
	}
	$d->close();
	return $sfv;
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

// ****************************************************************************
// Estimated time left to seed
function GetSpeedInBytes($inValue) {
	$rtnValue = 0;
	$arTemp = split(" ", trim($inValue));
	if ($arTemp[1] == "kB/s")
		$rtnValue = $arTemp[0] * 1024;
	else
		$rtnValue = $arTemp[0];
	return $rtnValue;
}

/**
 * convertTime
 *
 * @param $seconds
 * @return common time-delta-string
 */
function convertTime($seconds) {
	$periods = array (
		31556926,
		2629743,
		604800,
		86400,
		3600,
		60,
		1
	);
	$seconds = (float) $seconds;
	$values = array();
	foreach ($periods as $period) {
		$count = floor($seconds / $period);
		if ($count == 0)
		continue;
		if ($count < 10)
			array_push($values, "0".$count);
		else
			array_push($values, $count);
		$seconds = $seconds % $period;
	}
	if (empty($values))
		return "?";
	else
		return implode(':', $values);
}

/* ************************************************************************** */

?>