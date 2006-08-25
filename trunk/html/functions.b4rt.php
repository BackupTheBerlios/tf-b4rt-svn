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
	if (!ereg('^[^./][^/]*$', $cfg["theme"]) && strpos($cfg["theme"], "old_style_themes")) {
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
	$btphpbin = getcwd() . "/bin/TF_BitTornado/btphptornado.py";
	$maketorrent = getcwd() . "/bin/TF_BitTornado/btmakemetafile.py";
	$btshowmetainfo = getcwd() . "/bin/TF_BitTornado/btshowmetainfo.py";
	$tfPath = getcwd() . "/downloads/";
	if (!isFile($cfg["pythonCmd"])) {
		$pythonCmd = trim(shell_exec("which python"));
		if ($pythonCmd == "")
			$pythonCmd = $cfg["pythonCmd"];
	}
	$settings = array(
						"pythonCmd" => $pythonCmd,
						"btphpbin" => $btphpbin,
						"btmakemetafile" => $maketorrent,
						"btshowmetainfo" => $btshowmetainfo,
						"path" => $tfPath,
						"btclient_tornado_bin" => $btphpbin
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
	include_once("ClientHandler.php");
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
			$netcon = (int) trim(shell_exec($cfg['bin_sockstat']." | ".$cfg['bin_grep']." -cE".$webserverUser.".+".$torrentPid.".+tcp"));
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
	include_once("ClientHandler.php");
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
	include_once("ClientHandler.php");
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
 * checks if torrent is running by checking for existencte of pid-file.
 *
 * @param $transfer name of the transfer
 * @return 1|0
 */
function isTransferRunning($transfer) {
	global $cfg;
	if (file_exists($cfg["torrent_file_path"].substr($transfer,0,-8).'.stat.pid'))
		return 1;
	else
		return 0;
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
    require_once('BDecode.php');
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
	$btclient = getTransferClient($transfer);
	include_once("ClientHandler.php");
	$clientHandler = ClientHandler::getClientHandlerInstance($cfg, $btclient);
	return $clientHandler->getTransferTotal($transfer);
}

/**
 * gets totals of a transfer
 *
 * @param $transfer name of the transfer
 * @param $tid of the transfer
 * @param $btclient client of the transfer
 * @param $afu alias-file-uptotal of the transfer
 * @param $afd alias-file-downtotal of the transfer
 * @return array with transfer-totals
 */
function getTransferTotalsOP($transfer, $tid, $btclient, $afu, $afd) {
	global $cfg;
	include_once("ClientHandler.php");
	$clientHandler = ClientHandler::getClientHandlerInstance($cfg, $btclient);
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
	$btclient = getTransferClient($transfer);
	include_once("ClientHandler.php");
	$clientHandler = ClientHandler::getClientHandlerInstance($cfg, $btclient);
	return $clientHandler->getTransferCurrent($transfer);
}

/**
 * gets current totals of a transfer
 *
 * @param $transfer name of the transfer
 * @param $tid of the transfer
 * @param $btclient client of the transfer
 * @param $afu alias-file-uptotal of the transfer
 * @param $afd alias-file-downtotal of the transfer
 * @return array with transfer-totals
 */
function getTransferTotalsCurrentOP($transfer, $tid, $btclient, $afu, $afd) {
	global $cfg;
	include_once("ClientHandler.php");
	$clientHandler = ClientHandler::getClientHandlerInstance($cfg, $btclient);
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
		include_once("AliasFile.php");
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
		include_once("AliasFile.php");
		if ((substr( strtolower($transfer),-8 ) == ".torrent")) {
			// this is a torrent-client
			$btclient = getTransferClient($delfile);
			$af = AliasFile::getAliasFileInstance($cfg['torrent_file_path'].$alias_file, $transferowner, $cfg, $btclient);
			// update totals for this torrent
			updateTransferTotals($delfile);
			// remove torrent-settings from db
			deleteTorrentSettings($delfile);
			// client-proprietary leftovers
			include_once("ClientHandler.php");
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg,$btclient);
			$clientHandler->deleteCache($transfer);
		} else if ((substr( strtolower($transfer),-4 ) == ".url")) {
			// this is wget. use wget statfile
			$alias_file = str_replace(".url", "", $alias_file);
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
		require_once('BDecode.php');
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
	require_once('BDecode.php');
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
	include_once("ClientHandler.php");
	// messy...
	$RunningProcessInfo = " ---=== tornado ===---\n\n";
	$clientHandler = ClientHandler::getClientHandlerInstance($cfg,"tornado");
	$RunningProcessInfo .= $clientHandler->printRunningClientsInfo();
	$pinfo = shell_exec("ps auxww | ".$cfg['bin_grep']." ". $clientHandler->binClient ." | ".$cfg['bin_grep']." -v grep");
	$RunningProcessInfo .= "\n\n --- Process-List --- \n\n".$pinfo;
	unset($clientHandler);
	unset($pinfo);
	$RunningProcessInfo .= "\n\n ---=== transmission ===---\n\n";
	$clientHandler = ClientHandler::getClientHandlerInstance($cfg,"transmission");
	$RunningProcessInfo .= $clientHandler->printRunningClientsInfo();
	$pinfo = shell_exec("ps auxww | ".$cfg['bin_grep']." ". $clientHandler->binSystem ." | ".$cfg['bin_grep']." -v grep");
	$RunningProcessInfo .= "\n\n --- Process-List --- \n\n".$pinfo;
	return $RunningProcessInfo;
}

/**
 * getRunningTransferCount
 *
 * @return int with number of running transfers
 */
function getRunningTransferCount() {
	global $cfg;
	/*
	include_once("ClientHandler.php");
	// messy...
	$tCount = 0;
	$clientHandler = ClientHandler::getClientHandlerInstance($cfg,"tornado");
	$tCount += $clientHandler->getRunningClientCount();
	unset($clientHandler);
	$clientHandler = ClientHandler::getClientHandlerInstance($cfg,"transmission");
	$tCount += $clientHandler->getRunningClientCount();
	return $tCount;
	*/
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
	include_once("ClientHandler.php");
	// get only torrents of a particular client
	if ((isset($clientType)) && ($clientType != '')) {
		$clientHandler = ClientHandler::getClientHandlerInstance($cfg,$clientType);
		return $clientHandler->getRunningClients();
	}
	// get torrents of all clients
	// messy...
	$retAry = array();
	$clientHandler = ClientHandler::getClientHandlerInstance($cfg,"tornado");
	$tempAry = $clientHandler->getRunningClients();
	foreach ($tempAry as $val)
		array_push($retAry,$val);
	unset($clientHandler);
	unset($tempAry);
	$clientHandler = ClientHandler::getClientHandlerInstance($cfg,"transmission");
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
			return shell_exec($cfg["perlCmd"].' -I "'.$cfg["ttools_path"].'" "'.$cfg["ttools_path"].'/showmetainfo.pl" "'.$cfg["torrent_file_path"].$torrent.'"');
		break;
		case "btshowmetainfo.py":
		default:
			return shell_exec("cd ".$cfg["torrent_file_path"]."; ".$cfg["pythonCmd"]." -OO " . $cfg["btshowmetainfo"]." \"".$torrent."\"");
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
		include_once("setpriority.php");
		// Process setPriority Request.
		setPriority($torrent);
	}
	switch ($interactive) {
		case 0:
			include_once("ClientHandler.php");
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
				include_once("ClientHandler.php");
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
				include_once("setpriority.php");
				// Process setPriority Request.
				setPriority(urldecode($file_name));
			}
			include_once("ClientHandler.php");
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
							include_once("setpriority.php");
							// Process setPriority Request.
							setPriority(urldecode($file_name));
						}
						include_once("ClientHandler.php");
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
	include_once("AliasFile.php");
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
 * injects a atorrent
 *
 * @param $torrent
 * @return boolean
 */
function injectTorrent($torrent) {
	global $cfg;
	include_once("AliasFile.php");
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

/**
 * transferListXferUpdate1
 *
 * @param $entry
 * @param $torrentowner
 * @param $af
 * @param $settingsAry
 * @return unknown
 */
function transferListXferUpdate1($entry, $torrentowner, $af, $settingsAry) {
	global $cfg, $db;
	$transferTotalsCurrent = getTransferTotalsCurrentOP($entry, $settingsAry['hash'], $settingsAry['btclient'], $af->uptotal, $af->downtotal);
	$newday = 0;
	$sql = 'SELECT 1 FROM tf_xfer WHERE date = '.$db->DBDate(time());
	$newday = !$db->GetOne($sql);
	showError($db,$sql);
	sumUsage($torrentowner, ($transferTotalsCurrent["downtotal"]+0), ($transferTotalsCurrent["uptotal"]+0), 'total');
	sumUsage($torrentowner, ($transferTotalsCurrent["downtotal"]+0), ($transferTotalsCurrent["uptotal"]+0), 'month');
	sumUsage($torrentowner, ($transferTotalsCurrent["downtotal"]+0), ($transferTotalsCurrent["uptotal"]+0), 'week');
	sumUsage($torrentowner, ($transferTotalsCurrent["downtotal"]+0), ($transferTotalsCurrent["uptotal"]+0), 'day');
	//XFER: if new day add upload/download totals to last date on record and subtract from today in SQL
	if ($newday) {
		$newday = 2;
		$sql = 'SELECT date FROM tf_xfer ORDER BY date DESC';
		$lastDate = $db->GetOne($sql);
		showError($db,$sql);
		// MySQL 4.1.0 introduced 'ON DUPLICATE KEY UPDATE' to make this easier
		$sql = 'SELECT 1 FROM tf_xfer WHERE user = "'.$torrentowner.'" AND date = "'.$lastDate.'"';
		if ($db->GetOne($sql)) {
			$sql = 'UPDATE tf_xfer SET download = download+'.($transferTotalsCurrent["downtotal"]+0).', upload = upload+'.($transferTotalsCurrent["uptotal"]+0).' WHERE user = "'.$torrentowner.'" AND date = "'.$lastDate.'"';
			$db->Execute($sql);
			showError($db,$sql);
		} else {
			showError($db,$sql);
			$sql = 'INSERT INTO tf_xfer (user,date,download,upload) values ("'.$torrentowner.'","'.$lastDate.'",'.($transferTotalsCurrent["downtotal"]+0).','.($transferTotalsCurrent["uptotal"]+0).')';
			$db->Execute($sql);
			showError($db,$sql);
		}
		$sql = 'SELECT 1 FROM tf_xfer WHERE user = "'.$torrentowner.'" AND date = '.$db->DBDate(time());
		if ($db->GetOne($sql)) {
			$sql = 'UPDATE tf_xfer SET download = download-'.($transferTotalsCurrent["downtotal"]+0).', upload = upload-'.($transferTotalsCurrent["uptotal"]+0).' WHERE user = "'.$torrentowner.'" AND date = '.$db->DBDate(time());
			$db->Execute($sql);
			showError($db,$sql);
		} else {
			showError($db,$sql);
			$sql = 'INSERT INTO tf_xfer (user,date,download,upload) values ("'.$torrentowner.'",'.$db->DBDate(time()).',-'.($transferTotalsCurrent["downtotal"]+0).',-'.($transferTotalsCurrent["uptotal"]+0).')';
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

/*
 * This method gets the list of transfer
 *
 * @return transfer-list 2-dim array
 */
function getTransferListArray() {
	global $cfg, $db;
	include_once("AliasFile.php");
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
		$torrentowner = getOwner($entry);
		$owner = IsOwner($cfg["user"], $torrentowner);

		// ---------------------------------------------------------------------
		// alias / stat
		$alias = getAliasName($entry).".stat";
		if ((substr( strtolower($entry),-8 ) == ".torrent")) {
			// this is a torrent-client
			$settingsAry = loadTorrentSettings($entry);
			$af = AliasFile::getAliasFileInstance($cfg["torrent_file_path"].$alias, $torrentowner, $cfg, $settingsAry['btclient']);
		} else if ((substr( strtolower($entry),-4 ) == ".url")) {
			// this is wget. use tornado statfile
			$settingsAry = array();
			$settingsAry['btclient'] = "wget";
			$alias = str_replace(".url", "", $alias);
			$af = AliasFile::getAliasFileInstance($cfg["torrent_file_path"].$alias, $cfg['user'], $cfg, 'tornado');
		} else {
			$settingsAry = array();
			$settingsAry['btclient'] = "tornado";
			// this is "something else". use tornado statfile as default
			$af = AliasFile::getAliasFileInstance($cfg["torrent_file_path"].$alias, $cfg['user'], $cfg, 'tornado');
		}
		// cache running-flag in local var. we will access that often
		$transferRunning = (int) $af->running;
		// cache percent-done in local var. ...
		$percentDone = $af->percent_done;

		// ---------------------------------------------------------------------
		//XFER: add upload/download stats to the xfer array
		if (($cfg['enable_xfer'] == 1) && ($cfg['xfer_realtime'] == 1))
			$newday = transferListXferUpdate1($entry, $torrentowner, $af, $settingsAry);

		// ---------------------------------------------------------------------
		// injects
		if(! file_exists($cfg["torrent_file_path"].$alias)) {
			$transferRunning = 2;
			$af->running = "2";
			$af->size = getDownloadSize($cfg["torrent_file_path"].$entry);
			$af->WriteFile();
		}

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
				if ($af->time_left != "" && $af->time_left != "0")
					$estTime = $af->time_left;
				// $lastUser
				$lastUser = $torrentowner;
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
		// totals-preparation
		// if downtotal + uptotal + progress > 0
		if (($settings[2] + $settings[3] + $settings[5]) > 0)
			$transferTotals = getTransferTotalsOP($entry, $settingsAry['hash'], $settingsAry['btclient'], $af->uptotal, $af->downtotal);

		// ---------------------------------------------------------------------
		// fill temp array
		$transferAry = array();

		// ================================================================ name
		array_push($transferAry, $entry);

		// =============================================================== owner
		if ($settings[0] != 0)
			array_push($transferAry, $torrentowner);

		// ================================================================ size
		if ($settings[1] != 0)
			array_push($transferAry, formatBytesToKBMGGB($af->size));

		// =========================================================== downtotal
		if ($settings[2] != 0)
			array_push($transferAry, formatBytesToKBMGGB($transferTotals["downtotal"]+0));

		// ============================================================= uptotal
		if ($settings[3] != 0)
			array_push($transferAry, formatBytesToKBMGGB($transferTotals["uptotal"]+0));

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
		if ($cfg["user"] == getOwner($entry))
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

/* ************************************************************************** */

?>
