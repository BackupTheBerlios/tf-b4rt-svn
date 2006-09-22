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
 * template-factory.
 *
 * @param $theme
 * @param $template
 * @return vlib-template-instance
 */
function tmplGetInstance($theme, $template) {
	global $cfg;
	// theme-switch
	if ((strpos($theme, '/')) === false)
		$path = "themes/".$theme."/tmpl/";
	else
		$path = "themes/tf_standard_themes/tmpl/";
	// template-cache-switch
	switch ($cfg['enable_tmpl_cache']) {
		case 1:
			$tmpl = new vlibTemplateCache($path.$template);
			break;
		case 0:
		default:
			$tmpl =  new vlibTemplate($path.$template);
			break;
	}
	//  set common template-vars
	$tmpl->setvar('isAdmin', @ $cfg['isAdmin']);
	$tmpl->setvar('theme', $theme);
    $tmpl->setvar('pagetitle', $cfg["pagetitle"]);
    $tmpl->setvar('main_bgcolor', $cfg["main_bgcolor"]);
    $tmpl->setvar('table_border_dk', $cfg["table_border_dk"]);
    $tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
    $tmpl->setvar('table_data_bg', $cfg["table_data_bg"]);
    $tmpl->setvar('body_data_bg', $cfg["body_data_bg"]);
    // return template-instance
    return $tmpl;
}

/**
 * set Title Bar vars.
 *
 * @param $pageTitleText
 * @param $showButtons
 */
function tmplSetTitleBar($pageTitleText, $showButtons = true) {
	global $cfg, $db, $tmpl;
	// set some vars
	$tmpl->setvar('titleBar_title', $pageTitleText);
	$tmpl->setvar('titleBar_showButtons', $showButtons);
	$tmpl->setvar('_TORRENTS', $cfg['_TORRENTS']);
	$tmpl->setvar('_DIRECTORYLIST', $cfg['_DIRECTORYLIST']);
	$tmpl->setvar('_UPLOADHISTORY', $cfg['_UPLOADHISTORY']);
	$tmpl->setvar('_MYPROFILE', $cfg['_MYPROFILE']);
	$tmpl->setvar('_MESSAGES', $cfg['_MESSAGES']);
	$tmpl->setvar('_ADMINISTRATION', $cfg['_ADMINISTRATION']);
	if ($showButtons) {
		// Does the user have messages?
		$sql = "select count(*) from tf_messages where to_user='".$cfg["user"]."' and IsNew = 1";
		$number_messages = $db->GetOne($sql);
		showError($db,$sql);
		$tmpl->setvar('titleBar_number_messages', $number_messages);
	}
}

/**
 * set sub-foot vars
 *
 * @param $showReturn
 */
function tmplSetFoot($showReturn = true) {
	global $cfg, $tmpl;
	// set some vars
	$tmpl->setvar('_RETURNTOTRANSFERS', $cfg['_RETURNTOTRANSFERS']);
	$tmpl->setvar('subfoot_showReturn', $showReturn);
	$tmpl->setvar('subfoot_torrentFluxLink', getTorrentFluxLink());
}

/**
 * set vars for Search Engine Drop Down List
 *
 * @param $selectedEngine
 * @param $autoSubmit
 */
function tmplSetSearchEngineDDL($selectedEngine = 'TorrentSpy', $autoSubmit = false) {
	global $cfg, $tmpl;
	// set some vars
	$tmpl->setvar('autoSubmit', $autoSubmit);
	$handle = opendir("./inc/searchEngines");
	while($entry = readdir($handle))
		$entrys[] = $entry;
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
}

/**
 * drivespace bar
 *
 */
function tmplSetDriveSpaceBar() {
	global $cfg, $tmpl, $driveSpace, $freeSpaceFormatted;
	$tmpl->setvar('_STORAGE', $cfg['_STORAGE']);
	$tmpl->setvar('drivespacebar_type', $cfg['drivespacebar']);
	$tmpl->setvar('drivespacebar_space', $driveSpace);
	$tmpl->setvar('drivespacebar_space2', (100 - $driveSpace));
	$tmpl->setvar('drivespacebar_freeSpace', $freeSpaceFormatted);
	// color for xfer
	switch ($cfg['drivespacebar']) {
		case "xfer":
			$bgcolor = '#';
			$bgcolor .= str_pad(dechex(256 - 256 * ((100 - $driveSpace) / 100)), 2, 0, STR_PAD_LEFT);
			$bgcolor .= str_pad(dechex(256 * ((100 - $driveSpace) / 100)), 2, 0, STR_PAD_LEFT);
			$bgcolor .= '00';
			$tmpl->setvar('drivespacebar_bgcolor', $bgcolor);
			break;
	}
}

/**
 * bandwidth bars
 *
 */
function tmplSetBandwidthBars() {
	global $cfg, $tmpl;
	$tmpl->setvar('bandwidthbars_type', $cfg['bandwidthbar']);
	// upload
	$max_upload = $cfg["bandwidth_up"] / 8;
	if ($max_upload > 0)
		$percent_upload = number_format(($cfg["total_upload"] / $max_upload) * 100, 0);
	else
		$percent_upload = 0;
	if ($percent_upload > 0)
		$tmpl->setvar('bandwidthbars_upload_text', number_format($cfg["total_upload"], 2));
	else
		$tmpl->setvar('bandwidthbars_upload_text', "0.0");
	$tmpl->setvar('bandwidthbars_upload_percent', $percent_upload);
	$tmpl->setvar('bandwidthbars_upload_percent2', (100 - $percent_upload));
	// download
	$max_download = $cfg["bandwidth_down"] / 8;
	if ($max_download > 0)
		$percent_download = number_format(($cfg["total_download"] / $max_download) * 100, 0);
	else
		$percent_download = 0;
	if ($percent_download > 0)
		$tmpl->setvar('bandwidthbars_download_text', number_format($cfg["total_download"], 2));
	else
		$tmpl->setvar('bandwidthbars_download_text', "0.0");
	$tmpl->setvar('bandwidthbars_download_percent', $percent_download);
	$tmpl->setvar('bandwidthbars_download_percent2', (100 - $percent_download));
	// colors for xfer
	switch ($cfg['bandwidthbar']) {
		case "xfer":
			// upload
			$bgcolor = '#';
			$bgcolor .= str_pad(dechex(255 - 255 * ((100 - $percent_upload) / 150)), 2, 0, STR_PAD_LEFT);
			$bgcolor .= str_pad(dechex(255 * ((100 - $percent_upload) / 150)), 2, 0, STR_PAD_LEFT);
			$bgcolor .='00';
			$tmpl->setvar('bandwidthbars_upload_bgcolor', $bgcolor);
			// download
			$bgcolor = '#';
			$bgcolor .= str_pad(dechex(255 - 255 * ((100 - $percent_download) / 150)), 2, 0, STR_PAD_LEFT);
			$bgcolor .= str_pad(dechex(255 * ((100 - $percent_download) / 150)), 2, 0, STR_PAD_LEFT);
			$bgcolor .='00';
			$tmpl->setvar('bandwidthbars_download_bgcolor', $bgcolor);
	}
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
	// create template-instance
	$tmpl = tmplGetInstance($cfg["theme"], "component.superAdminLink.tmpl");
	$tmpl->setvar('param', $param);
	if ((isset($linkText)) && ($linkText != ""))
		$tmpl->setvar('linkText', $linkText);
	// grab the template
	$output = $tmpl->grab();
	return $output;
}

/**
 * get TF Link and Version
 *
 * @return string
 */
function getTorrentFluxLink() {
	global $cfg;
	if ($cfg["ui_displayfluxlink"] != 0) {
		$torrentFluxLink = "<div align=\"right\">";
		$torrentFluxLink .= "<a href=\"http://tf-b4rt.berlios.de/\" target=\"_blank\"><font class=\"tinywhite\">torrentflux-b4rt ".$cfg["version"]."</font></a>&nbsp;&nbsp;";
		$torrentFluxLink .= "</div>";
		return $torrentFluxLink;
	} else {
		return "";
	}
}

/**
 * prints nice error-page
 *
 * @param $errorMessage
 */
function showErrorPage($errorMessage) {
	global $cfg;
	require_once("themes/".$cfg["default_theme"]."/index.php");
	require_once("inc/lib/vlib/vlibTemplate.php");
	$tmpl = tmplGetInstance($cfg["default_theme"], "page.error.tmpl");
	$tmpl->setvar('ErrorMsg', $errorMessage);
	$tmpl->pparse();
	exit();
}

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
	$sql = "SELECT uid, hits, hide_offline, theme, language_file FROM tf_users WHERE user_id=".$db->qstr($cfg["user"]);
	$recordset = $db->Execute($sql);
	showError($db, $sql);
	if($recordset->RecordCount() != 1) {
		AuditAction($cfg["constants"]["error"], "FAILED AUTH: ".$cfg["user"]);
		@session_destroy();
		return 0;
	}
	list($uid, $hits, $cfg["hide_offline"], $cfg["theme"], $cfg["language_file"]) = $recordset->FetchRow();
	// hold the uid in cfg-array
	$cfg["uid"] = $uid;
	// Check for valid theme
	if (!ereg('^[^./][^/]*$', $cfg["theme"]) && strpos($cfg["theme"], "tf_standard_themes")) {
		AuditAction($cfg["constants"]["error"], "THEME VARIABLE CHANGE ATTEMPT: ".$cfg["theme"]." from ".$cfg["user"]);
		$cfg["theme"] = $cfg["default_theme"];
	}
	// Check for valid language file
	if(!ereg('^[^./][^/]*$', $cfg["language_file"])) {
		AuditAction($cfg["constants"]["error"], "LANGUAGE VARIABLE CHANGE ATTEMPT: ".$cfg["language_file"]." from ".$cfg["user"]);
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
 * netstatConnectionsSum
 *
 * @return int
 */
function netstatConnectionsSum() {
	global $cfg;
	switch ($cfg["_OS"]) {
		case 1: // linux
			return (int) trim(shell_exec($cfg['bin_netstat']." -e -p --tcp -n 2> /dev/null | ".$cfg['bin_grep']." -v root | ".$cfg['bin_grep']." -v 127.0.0.1 | ".$cfg['bin_grep']." -cE '.*(python|transmissionc|wget).*'"));
		case 2: // bsd
			$processUser = posix_getpwuid(posix_geteuid());
			$webserverUser = $processUser['name'];
			return (int) trim(shell_exec($cfg['bin_sockstat']." | ".$cfg['bin_grep']." -cE '".$webserverUser.".+(python|transmission).+tcp.+[[:digit:]]:[[:digit:]].+\*:\*|".$webserverUser.".+wget.+tcp.+[[:digit:]]:[[:digit:]].+[[:digit:]]:(21|80)'"));
	}
	return 0;
}

/**
 * netstatConnections
 *
 * @param $transferAlias
 * @return int
 */
function netstatConnections($transferAlias) {
	return netstatConnectionsByPid(getTransferPid($transferAlias));
}

/**
 * netstatConnectionsByPid
 *
 * @param $transferPid
 * @return int
 */
function netstatConnectionsByPid($transferPid) {
	global $cfg;
	switch ($cfg["_OS"]) {
		case 1: // linux
			return trim(shell_exec($cfg['bin_netstat']." -e -p --tcp --numeric-hosts --numeric-ports 2> /dev/null | ".$cfg['bin_grep']." -v root | ".$cfg['bin_grep']." -v 127.0.0.1 | ".$cfg['bin_grep']." -c \"".$transferPid ."/\""));
			break;
		case 2: // bsd
			$processUser = posix_getpwuid(posix_geteuid());
			$webserverUser = $processUser['name'];
			$netcon = (int) trim(shell_exec($cfg['bin_sockstat']." | ".$cfg['bin_grep']." -cE ".$webserverUser.".+".$transferPid.".+tcp"));
			$netcon--;
			return $netcon;
			break;
	}
}

/**
 * netstatPortList
 *
 * @return string
 */
function netstatPortList() {
	global $cfg;
	// messy...
	$retStr = "";
	switch ($cfg["_OS"]) {
		case 1: // linux
			require_once("inc/classes/ClientHandler.php");
			// not time-critical (only used on all_services-page), use the
			// generic and correct way :
			// array with all clients
			$clients = array('tornado', 'transmission', 'wget');
			// get informations
			foreach($clients as $client) {
				$clientHandler = ClientHandler::getClientHandlerInstance($cfg, $client);
				$retStr .= shell_exec($cfg['bin_netstat']." -e -l -p --tcp --numeric-hosts --numeric-ports 2> /dev/null | ".$cfg['bin_grep']." -v root | ".$cfg['bin_grep']." ". $clientHandler->binSocket ." | ".$cfg['bin_awk']." '{print \$4}' | ".$cfg['bin_awk']." 'BEGIN{FS=\":\"}{print \$2}'");
				unset($clientHandler);
			}
			break;
		case 2: // bsd
			$processUser = posix_getpwuid(posix_geteuid());
			$webserverUser = $processUser['name'];
			$retStr .= shell_exec($cfg['bin_sockstat']." | ".$cfg['bin_awk']." '/(python|transmis|wget).+tcp/ {split(\$6, a, \":\");print a[2]}'");
			break;
	}
	return $retStr;
}

/**
 * netstatPort
 *
 * @param $transferAlias
 * @return int
 */
function netstatPort($transferAlias) {
	return netstatPortByPid(getTransferPid($transferAlias));
}

/**
 * netstatPortByPid
 *
 * @param $transferPid
 * @return int
 */
function netstatPortByPid($transferPid) {
	global $cfg;
	switch ($cfg["_OS"]) {
		case 1: // linux
			return trim(shell_exec($cfg['bin_netstat']." -l -e -p --tcp --numeric-hosts --numeric-ports 2> /dev/null | ".$cfg['bin_grep']." -v root | ".$cfg['bin_grep']." \"".$transferPid ."/\" | ".$cfg['bin_awk']." '{print \$4}' | ".$cfg['bin_awk']." 'BEGIN{FS=\":\"}{print \$2}'"));
			break;
		case 2: // bsd
			$processUser = posix_getpwuid(posix_geteuid());
			$webserverUser = $processUser['name'];
			return (shell_exec($cfg['bin_sockstat']." | ".$cfg['bin_awk']." '/".$webserverUser.".*".$transferPid.".*tcp.*(\*:\*|[[:digit:]]:(21|80))/ {split(\$6, a, \":\");print a[2]}'"));
			break;
	}
}

/**
 * netstatHostList
 *
 * @return string
 */
function netstatHostList() {
	global $cfg;
	// messy...
	$retStr = "";
	switch ($cfg["_OS"]) {
		case 1: // linux
			require_once("inc/classes/ClientHandler.php");
			// not time-critical (only used on all_services-page), use the
			// generic and correct way :
			// array with all clients
			$clients = array('tornado', 'transmission', 'wget');
			// get informations
			foreach($clients as $client) {
				$clientHandler = ClientHandler::getClientHandlerInstance($cfg, $client);
				$retStr .= shell_exec($cfg['bin_netstat']." -e -p --tcp --numeric-hosts --numeric-ports 2> /dev/null | ".$cfg['bin_grep']." -v root | ".$cfg['bin_grep']." -v 127.0.0.1 | ".$cfg['bin_grep']." ". $clientHandler->binSocket ." | ".$cfg['bin_awk']." '{print \$5}'");
				unset($clientHandler);
			}
			break;
		case 2: // bsd
			$processUser = posix_getpwuid(posix_geteuid());
			$webserverUser = $processUser['name'];
			$retStr .= shell_exec($cfg['bin_sockstat']." | ".$cfg['bin_grep']." -E \"".$webserverUser.".+(python|transmis|wget).+tcp.+[[:digit:]]:[[:digit:]].+[[:digit:]]:[[:digit:]]\"");
			break;
	}
	return $retStr;
}

/**
 * netstatHosts
 *
 * @param $transferAlias
 * @return array
 */
function netstatHosts($transferAlias) {
	return netstatHostsByPid(getTransferPid($transferAlias));
}

/**
 * netstatHostsByPid
 *
 * @param $transferPid
 * @return array
 */
function netstatHostsByPid($transferPid) {
	global $cfg;
	$hostHash = null;
	switch ($cfg["_OS"]) {
		case 1: // linux
			$hostList = shell_exec($cfg['bin_netstat']." -e -p --tcp --numeric-hosts --numeric-ports 2> /dev/null | ".$cfg['bin_grep']." -v root | ".$cfg['bin_grep']." -v 127.0.0.1 | ".$cfg['bin_grep']." \"".$transferPid."/\" | ".$cfg['bin_awk']." '{print \$5}'");
			$hostAry = explode("\n",$hostList);
			foreach ($hostAry as $line) {
				$hostLineAry = explode(':',trim($line));
				$hostHash[$hostLineAry[0]] = @ $hostLineAry[1];
			}
			break;
		case 2: // bsd
			$processUser = posix_getpwuid(posix_geteuid());
			$webserverUser = $processUser['name'];
			$hostList = shell_exec($cfg['bin_sockstat']." | ".$cfg['bin_awk']." '/".$webserverUser.".+".$transferPid.".+tcp.+[0-9]:[0-9].+[0-9]:[0-9]/ {print \$7}'");
			$hostAry = explode("\n",$hostList);
			foreach ($hostAry as $line) {
				$hostLineAry = explode(':',trim($line));
				$hostHash[$hostLineAry[0]] = @ $hostLineAry[1];
			}
			break;
	}
	return $hostHash;
}

/**
 * getTransferPid
 *
 * @param $transferAlias
 * @return int
 */
function getTransferPid($transferAlias) {
	global $cfg;
	$data = "";
	if ($fileHandle = @fopen($cfg["transfer_file_path"].$transferAlias.".pid",'r')) {
		while (!@feof($fileHandle))
			$data .= @fgets($fileHandle, 64);
		@fclose ($fileHandle);
	}
	return trim($data);
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

/**
 * Function to delete saved Torrent Settings
 *
 * @param $torrent
 * @return boolean
 */
function deleteTorrentSettings($torrent) {
	global $db;
	$sql = "DELETE FROM tf_torrents WHERE torrent = '".$torrent."'";
	$db->Execute($sql);
	showError($db, $sql);
	return true;
}

/**
 * Function for saving Torrent Settings
 *
 * @param $torrent
 * @param $running
 * @param $rate
 * @param $drate
 * @param $maxuploads
 * @param $runtime
 * @param $sharekill
 * @param $minport
 * @param $maxport
 * @param $maxcons
 * @param $savepath
 * @param $btclient
 * @return boolean
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

/**
 * Function to load the settings for a torrent. returns array with settings
 *
 * @param $torrent
 * @return array
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
	if ((substr(strtolower($transfer), -8) == ".torrent")) {
		// this is a torrent-client
		if (file_exists($cfg["transfer_file_path"].substr($transfer, 0, -8).'.stat.pid'))
			return 1;
		else
			return 0;
	} else if ((substr(strtolower($transfer), -5) == ".wget")) {
		// this is wget.
		if (file_exists($cfg["transfer_file_path"].substr($transfer, 0, -5).'.stat.pid'))
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
		case "torrentinfo-console.py":
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
    $ftorrent=$cfg["transfer_file_path"].$torrent;
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
	if ((substr(strtolower($transfer), -8) == ".torrent")) {
		// this is a torrent-client
		$tclient = getTransferClient($transfer);
		$clientHandler = ClientHandler::getClientHandlerInstance($cfg, $tclient);
	} else if ((substr(strtolower($transfer), -5) == ".wget")) {
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
	if ((substr( strtolower($transfer), -8) == ".torrent")) {
		// this is a torrent-client
		$tclient = getTransferClient($transfer);
		$clientHandler = ClientHandler::getClientHandlerInstance($cfg, $tclient);
	} else if ((substr(strtolower($transfer), -5) == ".wget")) {
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
		@unlink($cfg["transfer_file_path"].$alias.".stat");
	} else {
		// reset in stat-file
		require_once("inc/classes/AliasFile.php");
		$af = AliasFile::getAliasFileInstance($cfg["transfer_file_path"].$alias.".stat", $owner, $cfg);
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
	global $cfg;
	$transferowner = getOwner($transfer);
	if (($cfg["user"] == $transferowner) || $cfg['isAdmin']) {
		require_once("inc/classes/AliasFile.php");
		if ((substr(strtolower($transfer), -8) == ".torrent")) {
			// this is a torrent-client
			$btclient = getTransferClient($transfer);
			$af = AliasFile::getAliasFileInstance($cfg['transfer_file_path'].$alias_file, $transferowner, $cfg, $btclient);
			// update totals for this torrent
			updateTransferTotals($transfer);
			// remove torrent-settings from db
			deleteTorrentSettings($transfer);
			// client-proprietary leftovers
			require_once("inc/classes/ClientHandler.php");
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg,$btclient);
			$clientHandler->deleteCache($transfer);
		} else if ((substr(strtolower($transfer), -5) == ".wget")) {
			// this is wget.
			$af = AliasFile::getAliasFileInstance($cfg['transfer_file_path'].$alias_file, $transferowner, $cfg, 'wget');
		} else {
			// this is "something else". use tornado statfile as default
			$af = AliasFile::getAliasFileInstance($cfg['transfer_file_path'].$alias_file, $cfg["user"], $cfg, 'tornado');
		}
		//XFER: before torrent deletion save upload/download xfer data to SQL
		$transferTotals = getTransferTotals($transfer);
		saveXfer($transferowner,($transferTotals["downtotal"]+0),($transferTotals["uptotal"]+0));
		// torrent+stat
		@unlink($cfg["transfer_file_path"].$transfer);
		@unlink($cfg["transfer_file_path"].$alias_file);
		// try to remove the pid file
		@unlink($cfg["transfer_file_path"].$alias_file.".pid");
		@unlink($cfg["transfer_file_path"].getAliasName($transfer).".prio");
		AuditAction($cfg["constants"]["delete_torrent"], $transfer);
		return true;
	} else {
		AuditAction($cfg["constants"]["error"], $cfg["user"]." attempted to delete ".$transfer);
		return false;
	}
}

/**
 * deletes data of a torrent
 *
 * @param $torrent name of the torrent
 */
function deleteTorrentData($torrent) {
	global $cfg;
	if (($cfg["user"] == getOwner($torrent)) || $cfg['isAdmin']) {
		# the user is the owner of the torrent -> delete it
		require_once('inc/classes/BDecode.php');
		$ftorrent=$cfg["transfer_file_path"].$torrent;
		$fd = fopen($ftorrent, "rd");
		$alltorrent = fread($fd, filesize($ftorrent));
		$btmeta = BDecode($alltorrent);
		$delete = $btmeta['info']['name'];
		if(trim($delete) != "") {
			// load torrent-settings from db to get data-location
			loadTorrentSettingsToConfig(urldecode($torrent));
			if ((! isset($cfg["savepath"])) || (empty($cfg["savepath"]))) {
				switch ($cfg["enable_home_dirs"]) {
				    case 1:
				    default:
						$cfg["savepath"] = $cfg["path"].getOwner($torrent).'/';
						break;
				    case 0:
				    	$cfg["savepath"] = $cfg["path"].$cfg["path_incoming"].'/';
				    	break;
				}
			}
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
				 AuditAction($cfg["constants"]["error"], "ILLEGAL DELETE: ".$cfg["user"]." tried to delete ".$del);
			}
		}
	} else {
		AuditAction($cfg["constants"]["error"], $cfg["user"]." attempted to delete ".$torrent);
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
	$ftorrent=$cfg["transfer_file_path"].$torrent;
	$fd = fopen($ftorrent, "rd");
	$alltorrent = fread($fd, filesize($ftorrent));
	$btmeta = BDecode($alltorrent);
	$name = $btmeta['info']['name'];
	if(trim($name) != "") {
		// load torrent-settings from db to get data-location
		loadTorrentSettingsToConfig($torrent);
		if ((! isset($cfg["savepath"])) || (empty($cfg["savepath"]))) {
			switch ($cfg["enable_home_dirs"]) {
			    case 1:
			    default:
					$cfg["savepath"] = $cfg["path"].getOwner($torrent).'/';
					break;
			    case 0:
			    	$cfg["savepath"] = $cfg["path"].$cfg["path_incoming"].'/';
			    	break;
			}
		}
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
 * getRunningTransferCount
 *
 * @return int with number of running transfers
 */
function getRunningTransferCount() {
	global $cfg;
	// use pid-files-direct-access for now because all clients of currently
	// available handlers write one. then its faster and correct meanwhile.
	if ($dirHandle = opendir($cfg["transfer_file_path"])) {
		$tCount = 0;
		while (false !== ($file = readdir($dirHandle))) {
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
			return shell_exec($cfg["btclient_transmission_bin"] . " -i \"".$cfg["transfer_file_path"].$torrent."\"");
		case "ttools.pl":
			$fluxDocRoot = dirname($_SERVER["SCRIPT_FILENAME"]);
			return shell_exec($cfg["perlCmd"].' -I "'.$fluxDocRoot.'/bin/ttools" "'.$fluxDocRoot.'/bin/ttools/ttools.pl" -i "'.$cfg["transfer_file_path"].$torrent.'"');
		case "torrentinfo-console.py":
			$fluxDocRoot = dirname($_SERVER["SCRIPT_FILENAME"]);
			return shell_exec("cd ".$cfg["transfer_file_path"]."; ".$cfg["pythonCmd"]." -OO ".$fluxDocRoot."/bin/TF_Mainline/torrentinfo-console.py \"".$torrent."\"");
		case "btshowmetainfo.py":
		default:
			$fluxDocRoot = dirname($_SERVER["SCRIPT_FILENAME"]);
			return shell_exec("cd ".$cfg["transfer_file_path"]."; ".$cfg["pythonCmd"]." -OO ".$fluxDocRoot."/bin/TF_BitTornado/btshowmetainfo.py \"".$torrent."\"");
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
			return shell_exec($cfg["btclient_transmission_bin"] . " -s \"".$cfg["transfer_file_path"].$torrent."\"");
		case "ttools.pl":
			$fluxDocRoot = dirname($_SERVER["SCRIPT_FILENAME"]);
			return shell_exec($cfg["perlCmd"].' -I "'.$fluxDocRoot.'/bin/ttools" "'.$fluxDocRoot.'/bin/ttools/ttools.pl" -s "'.$cfg["transfer_file_path"].$torrent.'"');
		case "btshowmetainfo.py":
			return "not supported by btshowmetainfo.py.";
		case "torrentinfo-console.py":
			return "not supported by torrentinfo-console.py.";
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
	if ($dirHandle = opendir($cfg["transfer_file_path"])) {
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

/**
 * Function to convert bit-array to (unsigned) byte
 *
 * @param $dataArray
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

/**
 * Function to convert (unsigned) byte to bit-array
 *
 * @param $dataByte
 * @return array
 */
function convertByteToArray($dataByte) {
   if (($dataByte > 255) || ($dataByte < 0)) return false;
   $binString = strrev(str_pad(decbin($dataByte),8,"0",STR_PAD_LEFT));
   $bitArray = explode(":",chunk_split($binString, 1, ":"));
   return $bitArray;
}

/**
 * Function to convert bit-array to (unsigned) integer
 *
 * @param $dataArray
 * @return int
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

/**
 * Function to convert (unsigned) integer to bit-array
 *
 * @param $dataInt
 * @return array
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
  if ((is_dir($dir) && is_writable ($dir)) || mkdir($dir,$mode))
	return true;
  if (! checkDirectory(dirname($dir),$mode))
	return false;
  return mkdir($dir,$mode);
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
			$data = "";
			if ($fileHandle = @fopen($cfg["loadavg_path"],'r')) {
				while (!@feof($fileHandle))
					$data .= @fgets($fileHandle, 128);
				@fclose ($fileHandle);
				$loadavg_array = explode(" ", $data);
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
	$af = AliasFile::getAliasFileInstance($cfg["transfer_file_path"].getAliasName($torrent).".stat",	 $cfg["user"], $cfg);
	$af->running = "2"; // file is new
	$af->size = getDownloadSize($cfg["transfer_file_path"].$torrent);
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
 * rnatcasesort
 *
 * @param &$a
 */
function rnatcasesort(&$a){
   natcasesort($a);
   $a = array_reverse($a, true);
}

/**
 * This method gets transfers in an array
 *
 * @param $sortOrder
 * @return array
 */
function getTransferArray($sortOrder = '') {
	global $cfg;
	$arList = array();
	$file_filter = getFileFilter($cfg["file_types_array"]);
	if (is_dir($cfg["transfer_file_path"]))
		$handle = opendir($cfg["transfer_file_path"]);
	else
		return null;
	while($entry = readdir($handle)) {
		if ($entry != "." && $entry != "..") {
			if (is_dir($cfg["transfer_file_path"]."/".$entry)) {
				// don''t do a thing
			} else {
				if (ereg($file_filter, $entry)) {
					$key = filemtime($cfg["transfer_file_path"]."/".$entry).md5($entry);
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
		array_push($retVal, $cfg['_USER']);
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
		array_push($retVal, $cfg['_STATUS']);
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
		array_push($retVal, $cfg['_ESTIMATEDTIME']);
	// ================================================================== client
	if ($settings[11] != 0)
		array_push($retVal, "Client");
	// return
	return $retVal;
}

/**
 * This method gets the list of transfer
 *
 * @return array
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
		if ((substr(strtolower($entry), -8) == ".torrent")) {
			// this is a torrent-client
			$isTorrent = true;
			$transferowner = getOwner($entry);
			$owner = IsOwner($cfg["user"], $transferowner);
			$settingsAry = loadTorrentSettings($entry);
			$af = AliasFile::getAliasFileInstance($cfg["transfer_file_path"].$alias, $transferowner, $cfg, $settingsAry['btclient']);
		} else if ((substr(strtolower($entry), -5) == ".wget")) {
			// this is wget.
			$isTorrent = false;
			$transferowner = getOwner($entry);
			$owner = IsOwner($cfg["user"], $transferowner);
			$settingsAry = array();
			$settingsAry['btclient'] = "wget";
			$settingsAry['hash'] = $entry;
			$af = AliasFile::getAliasFileInstance($cfg["transfer_file_path"].$alias, $transferowner, $cfg, 'wget');
		} else {
			// this is "something else". use tornado statfile as default
			$isTorrent = false;
			$transferowner = $cfg["user"];
			$owner = true;
			$settingsAry = array();
			$settingsAry['btclient'] = "tornado";
			$settingsAry['hash'] = $entry;
			$af = AliasFile::getAliasFileInstance($cfg["transfer_file_path"].$alias, $cfg["user"], $cfg, 'tornado');
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
		if(! file_exists($cfg["transfer_file_path"].$alias)) {
			$transferRunning = 2;
			$af->running = "2";
			$af->size = getDownloadSize($cfg["transfer_file_path"].$entry);
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
			default: // running + stopped
				// increment the totals
				if(!isset($cfg["total_upload"])) $cfg["total_upload"] = 0;
				if(!isset($cfg["total_download"])) $cfg["total_download"] = 0;
				$cfg["total_upload"] = $cfg["total_upload"] + GetSpeedValue($af->up_speed);
				$cfg["total_download"] = $cfg["total_download"] + GetSpeedValue($af->down_speed);
				// $estTime
				if ($transferRunning == 0) {
					$estTime = $af->time_left;
				} else {
					if ($af->time_left != "" && $af->time_left != "0") {
						if (($cfg["display_seeding_time"] == 1) && ($af->percent_done >= 100) ) {
							if (($af->seedlimit > 0) && (!empty($af->up_speed)) && ((int) ($af->up_speed{0}) > 0))
								$estTime = convertTime(((($af->seedlimit) / 100 * $af->size) - $af->uptotal) / GetSpeedInBytes($af->up_speed)) . " left";
							else
								$estTime = '-';
						} else {
							$estTime = $af->time_left;
						}
					}
				}
				// $lastUser
				$lastUser = $transferowner;
				// $show_run + $statusStr
				if($percentDone >= 100) {
					if (($transferRunning == 1) && (trim($af->up_speed) != "")) {
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
				case "mainline":
					array_push($transferAry, "M");
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
		$boolCond = $cfg['isAdmin'];
	if (($boolCond) && (sizeof($arListTorrent) > 0)) {
		foreach($arListTorrent as $torrentrow)
			array_push($retVal, $torrentrow);
	}
	return $retVal;
}

/**
 * get server stats
 * note : this can only be used after a call to update transfer-values in cfg-
 *        array (eg by getTransferListArray)
 *
 * @return array
 *
 * "speedDown"            0
 * "speedUp"              1
 * "speedTotal"           2
 * "cons"                 3
 * "freeSpace"            4
 * "loadavg"              5
 * "running"              6
 * "queued"               7
 * "speedDownPercent"     8
 * "speedUpPercent"       9
 * "driveSpacePercent"   10
 *
 */
function getServerStats() {
	global $cfg;
	$serverStats = array();
	// speedDown
    $speedDown = "n/a";
	$speedDown = @number_format($cfg["total_download"], 2);
	array_push($serverStats, $speedDown);
	// speedUp
    $speedUp = "n/a";
	$speedUp =  @number_format($cfg["total_upload"], 2);
	array_push($serverStats, $speedUp);
	// speedTotal
    $speedTotal = "n/a";
	$speedTotal = @number_format($cfg["total_download"] + $cfg["total_upload"], 2);
	array_push($serverStats, $speedTotal);
	// cons
    $cons = "n/a";
	$cons = @netstatConnectionsSum();
	array_push($serverStats, $cons);
	// freeSpace
    $freeSpace = "n/a";
	$freeSpace = @formatFreeSpace($cfg["free_space"]);
	array_push($serverStats, $freeSpace);
	// loadavg
	$loadavg = "n/a";
	$loadavg = @getLoadAverageString();
	array_push($serverStats, $loadavg);
	// running
	$running = "n/a";
	$running = @getRunningTransferCount();
	array_push($serverStats, $running);
	// queued
	$queued = "n/a";
	if ((isset($queueActive)) && ($queueActive) && (isset($fluxdQmgr)))
	    $queued = @ $fluxdQmgr->countQueuedTorrents();
	array_push($serverStats, $queued);
	// speedDownPercent
	$percentDownload = 0;
	$maxDownload = $cfg["bandwidth_down"] / 8;
	if ($maxDownload > 0)
		$percentDownload = @number_format(($cfg["total_download"] / $maxDownload) * 100, 0);
	else
		$percentDownload = 0;
	array_push($serverStats, $percentDownload);
	// speedUpPercent
	$percentUpload = 0;
	$maxUpload = $cfg["bandwidth_up"] / 8;
	if ($maxUpload > 0)
		$percentUpload = @number_format(($cfg["total_upload"] / $maxUpload) * 100, 0);
	else
		$percentUpload = 0;
	array_push($serverStats, $percentUpload);
	// driveSpacePercent
    $driveSpacePercent = 0;
	$driveSpacePercent = @getDriveSpace($cfg["path"]);
	array_push($serverStats, $driveSpacePercent);
	// return
	return $serverStats;
}

/**
 * gets details of a transfer as array
 *
 * @param $transfer
 * @param $full
 * @param $alias
 * @return array with details
 *
 * array-keys :
 *
 * running
 * speedDown
 * speedUp
 * downCurrent
 * upCurrent
 * downTotal
 * upTotal
 * percentDone
 * sharing
 * timeLeft
 * seeds
 * peers
 * cons
 * errors
 *
 * owner
 * size
 * maxSpeedDown
 * maxSpeedUp
 * maxcons
 * sharekill
 * port
 *
 */
function getTransferDetails($transfer, $full, $alias = "") {
	global $cfg;
	$details = array();
	// common functions
	require_once('inc/functions/functions.common.php');
	// aliasfile
	require_once("inc/classes/AliasFile.php");
	// alias-file
	if ((!(isset($alias))) || ($alias == "")) {
		$aliasName = getAliasName($transfer);
		$alias = $aliasName.".stat";
	}
	// alias / stat
	if ((substr(strtolower($transfer), -8) == ".torrent")) {
		// this is a torrent-client
		$transferowner = getOwner($transfer);
		$transferExists = loadTorrentSettingsToConfig($transfer);
		if (!$transferExists) {
			// new torrent
			$cfg['hash'] = $transfer;
		}
		$af = AliasFile::getAliasFileInstance($cfg["transfer_file_path"].$alias, $transferowner, $cfg, $cfg['btclient']);
	} else if ((substr(strtolower($transfer), -5) == ".wget")) {
		// this is wget.
		$transferowner = getOwner($transfer);
		$cfg['btclient'] = "wget";
		$cfg['hash'] = $transfer;
		$af = AliasFile::getAliasFileInstance($cfg["transfer_file_path"].$alias, $transferowner, $cfg, "wget");
	} else {
		// this is "something else". use tornado statfile as default
		$transferowner = $cfg["user"];
		$cfg['btclient'] = "tornado";
		$cfg['hash'] = $transfer;
		$af = AliasFile::getAliasFileInstance($cfg["transfer_file_path"].$alias, $cfg["user"], $cfg, 'tornado');
	}
	// size
	$size = $af->size;
	// totals
	$afu = $af->uptotal;
	$afd = $af->downtotal;
	$totalsCurrent = getTransferTotalsCurrentOP($transfer, $cfg['hash'], $cfg['btclient'], $afu, $afd);
	$totals = getTransferTotalsOP($transfer, $cfg['hash'], $cfg['btclient'], $afu, $afd);
	// running
	$running = $af->running;
	$details['running'] = $running;
	// speed_down + speed_up + seeds + peers + cons
	if ($running == 1) {
		// pid
		$pid = getTransferPid($alias);
		// speed_down
		if (trim($af->down_speed) != "")
			$details['speedDown'] = $af->down_speed;
		else
			$details['speedDown'] = '0.0 kB/s';
		// speed_up
		if (trim($af->up_speed) != "")
			$details['speedUp'] = $af->up_speed;
		else
			$details['speedUp'] = '0.0 kB/s';
		// down_current
		$details['downCurrent'] = formatFreeSpace($totalsCurrent["downtotal"] / 1048576);
		// up_current
		$details['upCurrent'] = formatFreeSpace($totalsCurrent["uptotal"] / 1048576);
		// seeds
		$details['seeds'] = $af->seeds;
		// peers
		$details['peers'] = $af->peers;
		// cons
		$details['cons'] = netstatConnectionsByPid($pid);
	} else {
		// speed_down
		$details['speedDown'] = "";
		// speed_up
		$details['speedUp'] = "";
		// down_current
		$details['downCurrent'] = "";
		// up_current
		$details['upCurrent'] = "";
		// seeds
		$details['seeds'] = "";
		// peers
		$details['peers'] = "";
		// cons
		$details['cons'] = "";
	}
	// down_total
	$details['downTotal'] = formatFreeSpace($totals["downtotal"] / 1048576);
	// up_total
	$details['upTotal'] = formatFreeSpace($totals["uptotal"] / 1048576);
	// percentage
	$percentage = $af->percent_done;
	if ($percentage < 0) {
		$percentage = round(($percentage * -1) - 100, 1);
		$af->time_left = $cfg['_INCOMPLETE'];
	} elseif ($percentage > 100) {
		$percentage = 100;
	}
	$details['percentDone'] = $percentage;
	// eta
	$details['eta'] = $af->time_left;
	// sharing
	if ($size > 0)
		$details['sharing'] = number_format((($totals["uptotal"] / $size) * 100), 2);
	else
		$details['sharing'] = 0;
	// errors
	$details['errors'] = $af->errors;
	// full (including static) details
	if ($full) {
		// owner
		$details['owner'] = $transferowner;
		// size
		$details['size'] = formatBytesTokBMBGBTB($size);
		if ($running == 1) {
			// max_download_rate
			$details['maxSpeedDown'] = number_format($cfg["max_download_rate"], 2);
			// max_upload_rate
			$details['maxSpeedUp'] = number_format($cfg["max_upload_rate"], 2);
			// maxcons
			$details['maxcons'] = $cfg["maxcons"];
			// sharekill
			$details['sharekill'] = $cfg["sharekill"];
			// port
			$details['port'] = netstatPortByPid($pid);
		} else {
			// max_download_rate
			$details['maxSpeedDown'] = "";
			// max_upload_rate
			$details['maxSpeedUp'] = "";
			// maxcons
			$details['maxcons'] = "";
			// sharekill
			$details['sharekill'] = "";
			// port
			$details['port'] = "";
		}
	}
	// return
	return $details;
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
    	'user_id' => $cfg["user"],
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
    if (@is_file($file)) {
        $rtnValue = True;
    } else {
        if ($file == trim(shell_exec("ls 2>/dev/null ".$file)))
            $rtnValue = True;
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

/**
 * IsOnline
 *
 * @param $user
 * @return boolean
 */
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

/**
 * IsUser
 *
 * @param $user
 * @return boolean
 */
function IsUser($user) {
	global $cfg, $db;
	$isUser = false;
	$sql = "SELECT count(*) FROM tf_users WHERE user_id=".$db->qstr($user);
	$number_users = $db->GetOne($sql);
	if ($number_users > 0)
		$isUser = true;
	return $isUser;
}

/**
 * getOwner
 *
 * @param $file
 * @return string
 */
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

/**
 * resetOwner
 *
 * @param $file
 * @return string
 */
function resetOwner($file) {
	global $cfg, $db;
	require_once("inc/classes/AliasFile.php");
	// log entry has expired so we must renew it
	$rtnValue = "";
	$alias = getAliasName($file).".stat";
	if(file_exists($cfg["transfer_file_path"].$alias)) {
		$af = AliasFile::getAliasFileInstance($cfg["transfer_file_path"].$alias, $cfg["user"], $cfg);
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

/**
 * IsOwner
 *
 * @param $user
 * @param $owner
 * @return boolean
 */
function IsOwner($user, $owner) {
	$rtnValue = false;
	if (strtolower($user) == strtolower($owner))
		$rtnValue = true;
	return $rtnValue;
}

/**
 * GetSpeedValue
 *
 * @param $inValue
 * @return number
 */
function GetSpeedValue($inValue) {
	$rtnValue = 0;
	$arTemp = split(" ", trim($inValue));
	if (is_numeric($arTemp[0]))
		$rtnValue = $arTemp[0];
	return $rtnValue;
}

/**
 * Is User Admin : user is Admin if level is 1 or higher
 *
 * @param $user
 * @return boolean
 */
function IsAdmin($user="") {
	global $cfg, $db;
	$isAdmin = false;
	if ($user == "")
		$user = $cfg["user"];
	$sql = "SELECT user_level FROM tf_users WHERE user_id=".$db->qstr($user);
	$user_level = $db->GetOne($sql);
	if ($user_level >= 1)
		$isAdmin = true;
	return $isAdmin;
}

/**
 * Is User SUPER Admin : user is Super Admin if level is higher than 1
 *
 * @param $user
 * @return boolean
 */
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

/**
 * Get Users in an array
 *
 * @return array
 */
function GetUsers() {
	global $cfg, $db;
	$user_array = array();
	$sql = "select user_id from tf_users order by user_id";
	$user_array = $db->GetCol($sql);
	showError($db,$sql);
	return $user_array;
}

/**
 * Get Super Admin User ID as a String
 *
 * @return string
 */
function GetSuperAdmin() {
	global $cfg, $db;
	$rtnValue = "";
	$sql = "select user_id from tf_users WHERE user_level=2";
	$rtnValue = $db->GetOne($sql);
	showError($db,$sql);
	return $rtnValue;
}

/**
 * Get Links in an array
 *
 * @return array
 */
function GetLinks() {
	global $cfg, $db;
	$link_array = array();
	$link_array = $db->GetAssoc("SELECT lid, url, sitename, sort_order FROM tf_links ORDER BY sort_order");
	return $link_array;
}

/**
 * Build Search Engine Links
 *
 * @param $selectedEngine
 * @return array
 */
function buildSearchEngineArray($selectedEngine = 'TorrentSpy') {
	global $cfg;
	$settingsNeedsSaving = false;
	$settings['searchEngineLinks'] = Array();
	$output = array();
	if( (!array_key_exists('searchEngineLinks', $cfg)) || (!is_array($cfg['searchEngineLinks'])))
		saveSettings('tf_settings', $settings);
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
					if ($selectedEngine == $tmpEngine) {
						array_push($output, array(
							'hreflink' => $hreflink,
							'selected' => 1,
							)
						);
					} else {
						array_push($output, array(
							'hreflink' => $hreflink,
							'selected' => 0,
							)
						);
					}
				}
			}
	}
	if (count($settings['searchEngineLinks'],COUNT_RECURSIVE) <> count($cfg['searchEngineLinks'],COUNT_RECURSIVE))
		$settingsNeedsSaving = true;
	if ($settingsNeedsSaving) {
		natcasesort($settings['searchEngineLinks']);
		saveSettings('tf_settings', $settings);
	}
	return $output;
}

/**
 * Removes HTML from Messages
 *
 * @param $str
 * @param $strip
 * @return string
 */
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
			$str = preg_replace('/(http:\/\/)(.*)([[:space:]]*)/i', '<a href="index.php?iid=dereferrer&u=${1}${2}" target="_blank">${1}${2}</a>${3}', $str);
		else
			$str = preg_replace('/(http:\/\/)(.*)([[:space:]]*)/i', '<a href="${1}${2}" target="_blank">${1}${2}</a>${3}', $str);
	}
	return $str;
}

/**
 * Returns the drive space used as a percentage i.e 85 or 95
 *
 * @param $drive
 * @return int
 */
function getDriveSpace($drive) {
	$percent = 0;
	if (is_dir($drive)) {
		$dt = disk_total_space($drive);
		$df = disk_free_space($drive);
		$percent = round((($dt - $df)/$dt) * 100);
	}
	return $percent;
}

/**
 * get File Filter
 *
 * @param $inArray
 * @return string
 */
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

/**
 * Create Alias name for Text file and Screen Alias
 *
 * @param $inName
 * @return string
 */
function getAliasName($inName) {
	global $cfg;
	$alias = preg_replace("/[^0-9a-z.]+/i",'_', $inName);
	$replaceArray = array();
	foreach ($cfg['file_types_array'] as $ftype)
		array_push($replaceArray, ".".$ftype);
	$alias = str_replace($replaceArray, "", $alias);
	return $alias;
}

/**
 * Remove bad characters that cause problems
 *
 * @param $inName
 * @return string
 */
function cleanFileName($inName) {
	$replaceItems = array("?", "&", "'", "\"", "+", "@");
	$cleanName = str_replace($replaceItems, "", $inName);
	$cleanName = ltrim($cleanName, "-");
	$cleanName = preg_replace("/[^0-9a-z.]+/i",'_', $cleanName);
	return $cleanName;
}

/**
 * split on the "*" coming from Varchar URL
 *
 * @param $url
 * @return string
 */
function cleanURL($url) {
	$rtnValue = $url;
	$arURL = explode("*", $url);
	if (sizeof($arURL) > 1)
		$rtnValue = $arURL[1];
	return $rtnValue;
}

/**
 * get data from URL. Has support for specific sites
 *
 * @param $url
 * @return string
 */
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

/**
 * method to get data from URL -- uses timeout and user agent
 *
 * @param $url
 * @param $referer
 * @return string
 */
function FetchHTML($url, $referer = "") {
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

/**
 * Grab the full size of the download from the torrent metafile
 *
 * @param $torrent
 * @return int
 */
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

/**
 * Returns a string "file name" of the status image icon
 *
 * @param $af
 * @return string
 */
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

/**
 * Returns file size... overcomes PHP limit of 2.0GB
 *
 * @param $file
 * @return int
 */
function file_size($file) {
	$size = @filesize($file);
	if ($size == 0)
		$size = exec("ls -l \"".$file."\" 2>/dev/null | awk '{print $5}'");
	return $size;
}

/**
 * Estimated time left to seed
 *
 * @param $inValue
 * @return string
 */
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

/**
 * Returns true if user has message from admin with force_read
 *
 * @return boolean
 */
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

/**
 * class ProcessInfo
 *
 */
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

/**
 * class ProcessInfo : Stores the image and title of for the health of a file.
 *
 */
class HealthData {
	var $image = "";
	var $title = "";
}

?>