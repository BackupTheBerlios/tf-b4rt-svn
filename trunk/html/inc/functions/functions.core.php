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

/*
 * netstatConnectionsSum
 */
function netstatConnectionsSum() {
	global $cfg;
	require_once("inc/classes/ClientHandler.php");
	// messy...
	$nCount = 0;
	switch ($cfg["_OS"]) {
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
	switch ($cfg["_OS"]) {
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
	switch ($cfg["_OS"]) {
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
	switch ($cfg["_OS"]) {
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
	switch ($cfg["_OS"]) {
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
	switch ($cfg["_OS"]) {
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

/*
 * getTorrentPid
 */
function getTorrentPid($torrentAlias) {
	global $cfg;
	return trim(shell_exec($cfg['bin_cat']." ".$cfg["torrent_file_path"].$torrentAlias.".pid"));
}

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
	// mainline
	$clientHandler = ClientHandler::getClientHandlerInstance($cfg,"mainline");
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
		case "ttools.pl":
			$fluxDocRoot = dirname($_SERVER["SCRIPT_FILENAME"]);
			return shell_exec($cfg["perlCmd"].' -I "'.$fluxDocRoot.'/bin/ttools" "'.$fluxDocRoot.'/bin/ttools/ttools.pl" -i "'.$cfg["torrent_file_path"].$torrent.'"');
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
		case "ttools.pl":
			$fluxDocRoot = dirname($_SERVER["SCRIPT_FILENAME"]);
			return shell_exec($cfg["perlCmd"].' -I "'.$fluxDocRoot.'/bin/ttools" "'.$fluxDocRoot.'/bin/ttools/ttools.pl" -s "'.$cfg["torrent_file_path"].$torrent.'"');
		case "btshowmetainfo.py":
			return "not supported by btshowmetainfo.py.";
		default:
			return "error.";
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

/**
 * getLoadAverageString
 *
 * @return string with load-average
 */
function getLoadAverageString() {
	global $cfg;
	switch ($cfg["_OS"]) {
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

/**
 * get the header portion
 *
 * @param $subTopic
 * @param $showButtons
 * @param $refresh
 * @param $percentdone
 * @return string
 */
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

/**
 * get the footer portion
 *
 * @param $showReturn
 * @param $showVersionLink
 * @return string
 */
function getFoot($showReturn=true, $showVersionLink = false) {
	global $cfg;
	# create new template
	if ((strpos($cfg['theme'], '/')) === false)
		$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/inc.getFoot.tmpl");
	else
		$tmpl = new vlibTemplate("themes/tf_standard_themes/tmpl/inc.getFoot.tmpl");
	//set some vars
	$tmpl->setvar('showReturn', $showReturn);
	$tmpl->setvar('_RETURNTOTORRENTS', _RETURNTOTORRENTS);
	$tmpl->setvar('getTorrentFluxLink', getTorrentFluxLink($showVersionLink));
	// grab the template
	$output = $tmpl->grab();
	return $output;
}

/**
 * get TF Link and Version
 *
 * @param $showVersionLink
 * @return string
 */
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

/**
 * get Title Bar.
 *
 * @param $pageTitleText
 * @param $showButtons
 * @return string
 */
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

/**
 * Build Search Engine Drop Down List
 *
 * @param $selectedEngine
 * @param $autoSubmit
 * @return string
 */
function buildSearchEngineDDL($selectedEngine = 'TorrentSpy', $autoSubmit = false) {
	global $cfg;
	# create new template
	if ((strpos($cfg['theme'], '/')) === false)
		$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/inc.buildSearchEngineDDL.tmpl");
	else
		$tmpl = new vlibTemplate("themes/tf_standard_themes/tmpl/inc.buildSearchEngineDDL.tmpl");
	
	$tmpl->setvar('autoSubmit', $autoSubmit);
	$handle = opendir("./inc/searchEngines");
	while($entry = readdir($handle)) {
		$entrys[] = $entry;
	}
	natcasesort($entrys);
	$Engine_List = array();
	foreach($entrys as $entry) {
		if ($entry != "." && $entry != ".." && substr($entry, 0, 1) != "." && strpos($entry,"Engine.php")) {
			$tmpEngine = str_replace("Engine",'',substr($entry,0,strpos($entry,".")));
			$selected = 0;
			if ($selectedEngine == $tmpEngine) {
				$selected = 1;
			}
			array_push($Engine_List, array(
				'selected' => $selected,
				'Engine' => $tmpEngine,
				)
			);
		}
	}
	$tmpl->setloop('Engine_List', $Engine_List);
	// grab the template
	$output = $tmpl->grab();
	return $output;
}

/**
 * get Engine Link
 *
 * @param $searchEngine
 * @return string
 */
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

/**
 * get superadmin-popup-link-html-snip.
 *
 * @param $param
 * @param $linkText
 * @return string
 */
function getSuperAdminLink($param = "", $linkText = "") {
	global $cfg;
	# create new template
	if ((strpos($cfg['theme'], '/')) === false)
		$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/inc.getSuperAdminLink.tmpl");
	else
		$tmpl = new vlibTemplate("themes/tf_standard_themes/tmpl/inc.getSuperAdminLink.tmpl");

	$tmpl->setvar('param', $param);
	if ((isset($linkText)) && ($linkText != ""))
		$tmpl->setvar('linkText', $linkText);
	// grab the template
	$output = $tmpl->grab();
	return $output;
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
function TransferListString() {
	global $cfg, $db;
	# create new template
	if ((strpos($cfg['theme'], '/')) === false)
		$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/inc.TransferListString.tmpl");
	else
		$tmpl = new vlibTemplate("themes/tf_standard_themes/tmpl/inc.TransferListString.tmpl");
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
		if(strlen($entry) >= 47) {
			// needs to be trimmed
			$displayname = substr($entry, 0, 44);
			$displayname .= "...";
		}
		$show_run = true;
		$hd = getStatusImage($af);

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
		}

		// ================================================================ down
		if ($settings[6] != 0) {
			if ($transferRunning == 1) {
				if (trim($af->down_speed) != "")
					$down_speed = $af->down_speed;
				else
					$down_speed = '0.0 kB/s';
			}
		}

		// ================================================================== up
		if ($settings[7] != 0) {
			if ($transferRunning == 1) {
				if (trim($af->up_speed) != "")
					$up_speed = $af->up_speed;
				else
					$up_speed = '0.0 kB/s';
			}
		}

		// ============================================================== client
		if ($settings[11] != 0) {
			switch ($settingsAry['btclient']) {
				case "tornado":
					$client = "B";
				break;
				case "transmission":
					$client = "T";
				break;
				case "mainline":
					$client = "M";
				break;
				case "wget":
					$client = "W";
				break;
				default:
					$client = "U";
			}
		}
		if ($owner || IsAdmin($cfg["user"])) {
			$is_owner = 1;
			if($percentDone >= 0 && $transferRunning == 1) {
				$is_running = 1;
			} else {
				if($transferowner != "n/a") {
					if ($transferRunning != 3) {
						if (!is_file($cfg["torrent_file_path"].$alias.".pid")) {
							$is_no_file = 1;
						}
					}
				}
			}
		}
		// ---------------------------------------------------------------------
		// Is this torrent for the user list or the general list?
		if ($owner)
			array_push($arUserTorrent, array(
				'transferRunning' => $transferRunning,
				'alias' => $alias,
				'url_entry' => urlencode($entry),
				'hd_image' => $hd->image,
				'hd_title' => $hd->title,
				'displayname' => $displayname,
				'transferowner' => $transferowner,
				'format_af_size' => formatBytesTokBMBGBTB($af->size),
				'format_downtotal' => formatBytesTokBMBGBTB($transferTotals["downtotal"]+0),
				'format_uptotal' => formatBytesTokBMBGBTB($transferTotals["uptotal"]+0),
				'statusStr' => $statusStr,
				'graph_width' => $graph_width,
				'percentage' => $percentage,
				'progress_color' => $progress_color,
				'bar_width' => $bar_width,
				'background' => $background,
				'100_graph_width' => (100 - $graph_width),
				'down_speed' => $down_speed,
				'up_speed' => $up_speed,
				'seeds' => $af->seeds,
				'peers' => $af->peers,
				'estTime' => $estTime,
				'client' => $client,
				'url_path' => urlencode(str_replace($cfg["path"],'', $settingsAry['savepath']).$settingsAry['datapath']),
				'datapath' => $settingsAry['datapath'],
				'is_owner' => $is_owner,
				'is_running' => $is_running,
				'isTorrent' => $isTorrent,
				'kill_id' => $kill_id,
				'is_no_file' => $is_no_file,
				'show_run' => $show_run,
				'entry' => $entry,
				)
			);
		else
			array_push($arListTorrent, array(
				'transferRunning' => $transferRunning,
				'alias' => $alias,
				'url_entry' => urlencode($entry),
				'hd_image' => $hd->image,
				'hd_title' => $hd->title,
				'displayname' => $displayname,
				'transferowner' => $transferowner,
				'format_af_size' => formatBytesTokBMBGBTB($af->size),
				'format_downtotal' => formatBytesTokBMBGBTB($transferTotals["downtotal"]+0),
				'format_uptotal' => formatBytesTokBMBGBTB($transferTotals["uptotal"]+0),
				'statusStr' => $statusStr,
				'graph_width' => $graph_width,
				'percentage' => $percentage,
				'progress_color' => $progress_color,
				'bar_width' => $bar_width,
				'background' => $background,
				'100_graph_width' => (100 - $graph_width),
				'down_speed' => $down_speed,
				'up_speed' => $up_speed,
				'seeds' => $af->seeds,
				'peers' => $af->peers,
				'estTime' => $estTime,
				'client' => $client,
				'url_path' => urlencode(str_replace($cfg["path"],'', $settingsAry['savepath']).$settingsAry['datapath']),
				'datapath' => $settingsAry['datapath'],
				'is_owner' => $is_owner,
				'is_running' => $is_running,
				'isTorrent' => $isTorrent,
				'kill_id' => $kill_id,
				'is_no_file' => $is_no_file,
				'show_run' => $show_run,
				'entry' => $entry,
				)
			);
	}
	$tmpl->setloop('arUserTorrent', $arUserTorrent);
	$tmpl->setloop('arListTorrent', $arListTorrent);

	//XFER: if a new day but no .stat files where found put blank entry into the
	//      DB for today to indicate accounting has been done for the new day
	if (($cfg['enable_xfer'] == 1) && ($cfg['xfer_realtime'] == 1))
		transferListXferUpdate2($newday);

	// -------------------------------------------------------------------------
	// build output-string
	
	
	$tmpl->setvar('table_data_bg', $cfg["table_data_bg"]);
	$tmpl->setvar('table_border_dk', $cfg["table_border_dk"]);
	$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
	$tmpl->setvar('enable_torrent_download', $cfg["enable_torrent_download"]);
	$tmpl->setvar('theme', $cfg["theme"]);
	$tmpl->setvar('enable_multiops', $cfg["enable_multiops"]);
	$tmpl->setvar('advanced_start', $cfg["advanced_start"]);
	$tmpl->setvar('user', $cfg["user"]);
	$tmpl->setvar('sortOrder', $sortOrder);
	
	
	$tmpl->setvar('settings_0', $settings[0]);
	$tmpl->setvar('settings_1', $settings[1]);
	$tmpl->setvar('settings_2', $settings[2]);
	$tmpl->setvar('settings_3', $settings[3]);
	$tmpl->setvar('settings_4', $settings[4]);
	$tmpl->setvar('settings_5', $settings[5]);
	$tmpl->setvar('settings_6', $settings[6]);
	$tmpl->setvar('settings_7', $settings[7]);
	$tmpl->setvar('settings_8', $settings[8]);
	$tmpl->setvar('settings_9', $settings[9]);
	$tmpl->setvar('settings_10', $settings[10]);
	$tmpl->setvar('settings_11', $settings[11]);
	$tmpl->setvar('_TRANSFERDETAILS', _TRANSFERDETAILS);
	$tmpl->setvar('_STOPTRANSFER', _STOPTRANSFER);
	$tmpl->setvar('_NOTOWNER', _NOTOWNER);
	$tmpl->setvar('_DELQUEUE', _DELQUEUE);
	$tmpl->setvar('_RUNTRANSFER', _RUNTRANSFER);
	$tmpl->setvar('_SEEDTRANSFER', _SEEDTRANSFER);
	$tmpl->setvar('_STOPPING', _STOPPING);
	$tmpl->setvar('_DELETE', _DELETE);
	$tmpl->setvar('_TRANSFERFILE', _TRANSFERFILE);
	$tmpl->setvar('_USER', _USER);
	$tmpl->setvar('_STATUS', _STATUS);
	$tmpl->setvar('_ESTIMATEDTIME', _ESTIMATEDTIME);
	$tmpl->setvar('_ADMIN', _ADMIN);
	
	if (sizeof($arUserTorrent) > 0) {
		$tmpl->setvar('are_user_torrent', 1);
	}
	$boolCond = true;
	if ($cfg['enable_restrictivetview'] == 1)
		$boolCond = IsAdmin();
	if (($boolCond) && (sizeof($arListTorrent) > 0)) {
		$tmpl->setvar('are_torrent', 1);
	}
	// grab the template
	$output = $tmpl->grab();
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
	# create new template
	if ((strpos($cfg['theme'], '/')) === false)
		$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/inc.getBandwidthBar_tf.tmpl");
	else
		$tmpl = new vlibTemplate("themes/tf_standard_themes/tmpl/inc.getBandwidthBar_tf.tmpl");
	$tmpl->setvar('theme', $theme);
	$tmpl->setvar('percent', $percent);
	$tmpl->setvar('text', $text);
	$tmpl->setvar('100_percent', (100 - $percent));
	// grab the template
	$output = $tmpl->grab();
	return $output;
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
	# create new template
	if ((strpos($cfg['theme'], '/')) === false)
		$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/inc.getBandwidthBar_xfer.tmpl");
	else
		$tmpl = new vlibTemplate("themes/tf_standard_themes/tmpl/inc.getBandwidthBar_xfer.tmpl");
	$bgcolor = '#';
	$bgcolor .= str_pad(dechex(255 - 255 * ((100 - $percent) / 150)), 2, 0, STR_PAD_LEFT);
	$bgcolor .= str_pad(dechex(255 * ((100 - $percent) / 150)), 2, 0, STR_PAD_LEFT);
	$bgcolor .='00';
	$tmpl->setvar('bgcolor', $bgcolor);
	$tmpl->setvar('percent', $percent);
	$tmpl->setvar('100_percent', (100 - $percent));
	// grab the template
	$output = $tmpl->grab();
	return $output;
}

/**
 * get Request Var
 *
 * @param $varName
 * @return string
 */
function getRequestVar($varName) {
    if (array_key_exists($varName,$_REQUEST))
        return trim($_REQUEST[$varName]);
    else
        return '';
}

/**
 * Audit Action
 *
 * @param $action
 * @param $file
 */
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

/**
 * isFile
 *
 * @param $file
 * @return boolean
 */
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

/**
 * avddelete
 *
 * @param $file
 */
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
function IsOwner($user, $owner) {
	$rtnValue = false;
	if (strtolower($user) == strtolower($owner))
		$rtnValue = true;
	return $rtnValue;
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
// file_size()
// Returns file size... overcomes PHP limit of 2.0GB
function file_size($file) {
	$size = @filesize($file);
	if ( $size == 0)
		$size = exec("ls -l \"".$file."\" | awk '{print $5}'");
	return $size;
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