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
 * POSIX-wrapper for PHPs lacking posix-support (--disable-posix)
 */
if (!function_exists("posix_geteuid"))
	require_once("inc/functions/functions.posix.php");

/**
 * ADOdb
 */
require_once('inc/lib/adodb/adodb.inc.php');

/**
 * initialize ADOdb-connection
 */
function dbInitialize() {
	global $cfg, $db;
	// create ado-object
    $db = ADONewConnection($cfg["db_type"]);
    // connect
    if ($cfg["db_pcon"])
    	@ $db->PConnect($cfg["db_host"], $cfg["db_user"], $cfg["db_pass"], $cfg["db_name"]);
    else
    	@ $db->Connect($cfg["db_host"], $cfg["db_user"], $cfg["db_pass"], $cfg["db_name"]);
    // check for error
    if ($db->ErrorNo() != 0)
    	@error("Database Connection Problems", "", "", array("Check your database-config-file. (inc/config/config.db.php)"));
}

/**
 * db-error-function
 *
 * @param $sql
 */
function dbError($sql) {
	global $cfg, $db;
	$msgs = array();
	$dbErrMsg = $db->ErrorMsg();
	array_push($msgs, "ErrorMsg : ");
	array_push($msgs, $dbErrMsg);
	if ($cfg["debug_sql"] != 0) {
		array_push($msgs, "\nSQL : ");
		array_push($msgs, $sql);
	}
	array_push($msgs, "");
	if (preg_match('/.*Query.*empty.*/i', $dbErrMsg))
		array_push($msgs, "\nDatabase may be corrupted. Try to repair the tables.");
	else
		array_push($msgs, "\nAlways check your database settings in the config.db.php file.");
	@error("Database-Error", "", "", $msgs);
}

/**
 * global error-function
 *
 * @param $msg
 * @param $link
 * @param $linklabel
 * @param $msgs
 */
function error($msg, $link = "", $linklabel = "", $msgs = array()) {
	global $cfg;
	// web/cli
    if (empty($_REQUEST)) { // cli
    	// message
    	$exitMsg = "Error : ".$msg."\n";
    	// messages
    	if (!empty($msgs))
    		$exitMsg .= implode("\n", $msgs)."\n";
    	// get out here
    	exit($exitMsg);
    } else { // web
		// theme
		$theme = "default";
		if (isset($cfg["theme"]))
			$theme = $cfg["theme"];
		else if (isset($cfg["default_theme"]))
			$theme = $cfg["default_theme"];
		// template
		require_once("themes/".$theme."/index.php");
		require_once("inc/lib/vlib/vlibTemplate.php");
		$_tmpl = tmplGetInstance($theme, "page.error.tmpl");
		// message
		$_tmpl->setvar('message', htmlentities($msg, ENT_QUOTES));
		// messages
		if (!empty($msgs)) {
			$msgAry = array_map("htmlentities", $msgs);
			$_tmpl->setvar('messages', implode("\n", $msgAry));
		}
		// link + linklabel
		if (!empty($link)) {
			$_tmpl->setvar('link', $link);
			$_tmpl->setvar('linklabel', (!empty($linklabel)) ? htmlentities($linklabel, ENT_QUOTES) : "OK");
		}
		// parse template
		$_tmpl->pparse();
		// get out here
		exit();
    }
}

/**
 * initialize global template-instance "$tmpl"
 *
 * @param $theme
 * @param $template
 */
function tmplInitializeInstance($theme, $template) {
	global $cfg, $tmpl;
	// theme-switch
	$path = ((strpos($theme, '/')) === false)
		? "themes/".$theme."/tmpl/"
		: "themes/tf_standard_themes/tmpl/";
	// template-cache-switch
	$tmpl = ($cfg['enable_tmpl_cache'] != 0)
		? new vlibTemplateCache($path.$template)
		: new vlibTemplate($path.$template);
	//  set common template-vars
	$tmpl->setvar('theme', $theme);
    $tmpl->setvar('pagetitle', @ $cfg["pagetitle"]);
    $tmpl->setvar('main_bgcolor', @ $cfg["main_bgcolor"]);
    $tmpl->setvar('table_border_dk', @ $cfg["table_border_dk"]);
    $tmpl->setvar('table_header_bg', @ $cfg["table_header_bg"]);
    $tmpl->setvar('table_data_bg', @ $cfg["table_data_bg"]);
    $tmpl->setvar('body_data_bg', @ $cfg["body_data_bg"]);
    $tmpl->setvar('isAdmin', @ $cfg['isAdmin']);
}

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
	$path = ((strpos($theme, '/')) === false)
		? "themes/".$theme."/tmpl/"
		: "themes/tf_standard_themes/tmpl/";
	// template-cache-switch
	$_tmpl = ($cfg['enable_tmpl_cache'] != 0)
		? new vlibTemplateCache($path.$template)
		: new vlibTemplate($path.$template);
	//  set common template-vars
	$_tmpl->setvar('theme', $theme);
    $_tmpl->setvar('pagetitle', @ $cfg["pagetitle"]);
    $_tmpl->setvar('main_bgcolor', @ $cfg["main_bgcolor"]);
    $_tmpl->setvar('table_border_dk', @ $cfg["table_border_dk"]);
    $_tmpl->setvar('table_header_bg', @ $cfg["table_header_bg"]);
    $_tmpl->setvar('table_data_bg', @ $cfg["table_data_bg"]);
    $_tmpl->setvar('body_data_bg', @ $cfg["body_data_bg"]);
    $_tmpl->setvar('isAdmin', @ $cfg['isAdmin']);
    // return template-instance
    return $_tmpl;
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
	if ($showButtons)
		$tmpl->setvar('titleBar_number_messages', $db->GetOne("select count(*) from tf_messages where to_user='".$cfg["user"]."' and IsNew = 1"));
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
			array_push($Engine_List, array(
				'selected' => ($selectedEngine == $tmpEngine) ? 1 : 0,
				'Engine' => $tmpEngine
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
	global $cfg, $tmpl;
	$tmpl->setvar('_STORAGE', $cfg['_STORAGE']);
	$tmpl->setvar('drivespacebar_type', $cfg['drivespacebar']);
	$tmpl->setvar('drivespacebar_space', $cfg['driveSpace']);
	$tmpl->setvar('drivespacebar_space2', (100 - $cfg['driveSpace']));
	$tmpl->setvar('drivespacebar_freeSpace', $cfg['freeSpaceFormatted']);
	// color for xfer
	switch ($cfg['drivespacebar']) {
		case "xfer":
			$bgcolor = '#';
			$bgcolor .= str_pad(dechex(256 - 256 * ((100 - $cfg['driveSpace']) / 100)), 2, 0, STR_PAD_LEFT);
			$bgcolor .= str_pad(dechex(256 * ((100 - $cfg['driveSpace']) / 100)), 2, 0, STR_PAD_LEFT);
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
	$percent_upload = ($max_upload > 0)
		? @number_format(($cfg["total_upload"] / $max_upload) * 100, 0)
		: 0;
	$tmpl->setvar('bandwidthbars_upload_text',
		($percent_upload > 0)
			? @number_format($cfg["total_upload"], 2)
			: "0.00");
	$tmpl->setvar('bandwidthbars_upload_percent', $percent_upload);
	$tmpl->setvar('bandwidthbars_upload_percent2', (100 - $percent_upload));
	// download
	$max_download = $cfg["bandwidth_down"] / 8;
	$percent_download = ($max_download > 0)
		? @number_format(($cfg["total_download"] / $max_download) * 100, 0)
		: 0;
	$tmpl->setvar('bandwidthbars_download_text',
		($percent_download > 0)
			? @number_format($cfg["total_download"], 2)
			: "0.00");
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
	$_tmpl = tmplGetInstance($cfg["theme"], "component.superAdminLink.tmpl");
	$_tmpl->setvar('param', $param);
	if ((isset($linkText)) && ($linkText != ""))
		$_tmpl->setvar('linkText', $linkText);
	// grab the template
	$output = $_tmpl->grab();
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
 * get path to images of current theme
 *
 * @return string
 */
function getImagesPath() {
	global $cfg;
	return "themes/".$cfg['theme']."/images/";
}

/**
 * try to get Credentials
 *
 * @return array with credentials or false if no credentials found.
 */
function getCredentials() {
	global $cfg;
	// check for basic-auth-supplied credentials (only if activated or there may
	// be wrong credentials fetched)
	if (($cfg['auth_type'] == 2) || ($cfg['auth_type'] == 3)) {
		if ((isset($_SERVER['PHP_AUTH_USER'])) && (isset($_SERVER['PHP_AUTH_PW']))) {
			$retVal = array();
			$retVal['username'] = $_SERVER['PHP_AUTH_USER'];
			$retVal['password'] = addslashes($_SERVER['PHP_AUTH_PW']);
			$retVal['md5pass'] = "";
			return $retVal;
		}
	}
	// check for http-post/get-supplied credentials (only if auth-type not 4)
	if ($cfg['auth_type'] != 4) {
		if (isset($_REQUEST['username'])) {
			if (isset($_REQUEST['md5pass'])) {
				$retVal = array();
				$retVal['username'] = $_REQUEST['username'];
				$retVal['password'] = "";
				$retVal['md5pass'] = $_REQUEST['md5pass'];
				return $retVal;
			} elseif (isset($_REQUEST['iamhim'])) {
				$retVal = array();
				$retVal['username'] = $_REQUEST['username'];
				$retVal['password'] = addslashes($_REQUEST['iamhim']);
				$retVal['md5pass'] = "";
				return $retVal;
			}
		}
	}
	// check for cookie-supplied credentials (only if activated)
	if ($cfg['auth_type'] == 1) {
		if (isset($_COOKIE["autologin"])) {
			$creds = explode('|', $_COOKIE["autologin"]);
			$retVal = array();
			$retVal['username'] = $creds[0];
			$retVal['password'] = "";
			$retVal['md5pass'] = $creds[1];
			return $retVal;
		}
	}
	// no credentials found, return false
	return false;
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
	// hold time
	$create_time = time();
	// user not set
	if (!isset($_SESSION['user']))
		return 0;
	// user changed password and needs to login again
	if ($_SESSION['user'] == md5($cfg["pagetitle"]))
		return 0;
	// user exists ?
	$recordset = $db->Execute("SELECT uid, hits FROM tf_users WHERE user_id=".$db->qstr($cfg["user"]));
	if ($recordset->RecordCount() != 1) {
		AuditAction($cfg["constants"]["access_denied"], "FAILED AUTH: ".$cfg["user"]);
		@session_destroy();
		return 0;
	}
	list($uid, $hits) = $recordset->FetchRow();
	// hold the uid in cfg-array
	$cfg["uid"] = $uid;
	// increment hit-counter
	$hits++;
	$db->Execute("UPDATE tf_users SET hits = '".$hits."', last_visit = '".$create_time."' WHERE uid = '".$uid."'");
	// return auth suc.
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
		case 2: // bsd
			$processUser = posix_getpwuid(posix_geteuid());
			$webserverUser = $processUser['name'];
			$netcon = (int) trim(shell_exec($cfg['bin_sockstat']." | ".$cfg['bin_grep']." -cE ".$webserverUser.".+".$transferPid.".+tcp"));
			$netcon--;
			return $netcon;
	}
}

/**
 * netstatPortList
 *
 * @return string
 */
function netstatPortList() {
	global $cfg;
	$retStr = "";
	switch ($cfg["_OS"]) {
		case 1: // linux
			// not time-critical (only used on allServices-page), use the
			// generic and correct way :
			// array with all clients
			$clients = array('tornado', 'transmission', 'wget');
			// get informations
			foreach ($clients as $client) {
				$clientHandler = ClientHandler::getInstance($client);
				$retStr .= shell_exec($cfg['bin_netstat']." -e -l -p --tcp --numeric-hosts --numeric-ports 2> /dev/null | ".$cfg['bin_grep']." -v root | ".$cfg['bin_grep']." ". $clientHandler->binSocket ." | ".$cfg['bin_awk']." '{print \$4}' | ".$cfg['bin_awk']." 'BEGIN{FS=\":\"}{print \$2}'");
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
		case 2: // bsd
			$processUser = posix_getpwuid(posix_geteuid());
			$webserverUser = $processUser['name'];
			return shell_exec($cfg['bin_sockstat']." | ".$cfg['bin_awk']." '/".$webserverUser.".*".$transferPid.".*tcp.*(\*:\*|[[:digit:]]:(21|80))/ {split(\$6, a, \":\");print a[2]}'");
	}
}

/**
 * netstatHostList
 *
 * @return string
 */
function netstatHostList() {
	global $cfg;
	$retStr = "";
	switch ($cfg["_OS"]) {
		case 1: // linux
			// not time-critical (only used on allServices-page), use the
			// generic and correct way :
			// array with all clients
			$clients = array('tornado', 'transmission', 'wget');
			// get informations
			foreach($clients as $client) {
				$clientHandler = ClientHandler::getInstance($client);
				$retStr .= shell_exec($cfg['bin_netstat']." -e -p --tcp --numeric-hosts --numeric-ports 2> /dev/null | ".$cfg['bin_grep']." -v root | ".$cfg['bin_grep']." -v 127.0.0.1 | ".$cfg['bin_grep']." ". $clientHandler->binSocket ." | ".$cfg['bin_awk']." '{print \$5}'");
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
	return $db->GetOne("SELECT SUM(maxcons) AS maxcons FROM tf_torrents WHERE running = '1'");
}

/**
 * Returns sum of max upload-speed of all running torrents.
 *
 * @return int with max upload-speed
 */
function getSumMaxUpRate() {
	global $db;
	return $db->GetOne("SELECT SUM(rate) AS rate FROM tf_torrents WHERE running = '1'");
}

/**
 * Returns sum of max download-speed of all running torrents.
 *
 * @return int with max download-speed
 */
function getSumMaxDownRate() {
	global $db;
	return $db->GetOne("SELECT SUM(drate) AS drate FROM tf_torrents WHERE running = '1'");
}

/**
 * Function to delete saved Settings
 *
 * @param $transfer
 * @return boolean
 */
function deleteTransferSettings($transfer) {
	global $db;
	$sql = "DELETE FROM tf_torrents WHERE torrent = '".$transfer."'";
	$db->Execute($sql);
	if ($db->ErrorNo() != 0) dbError($sql);
	return true;
}

/**
 * Function for saving transfer Settings
 *
 * @param $transfer
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
function saveTransferSettings($transfer, $running, $rate, $drate, $maxuploads, $runtime, $sharekill, $minport, $maxport, $maxcons, $savepath, $btclient = 'tornado') {
	global $db;
	// Messy - a not exists would prob work better
	deleteTransferSettings($transfer);
	// get hash
	$tHash = getTorrentHash($transfer);
	// get datapath
	$tDatapath = getTorrentDatapath($transfer);
	// insert
	$sql = "INSERT INTO tf_torrents ( torrent , running ,rate , drate, maxuploads , runtime , sharekill , minport , maxport, maxcons , savepath , btclient, hash, datapath )
			VALUES (
					".$db->qstr($transfer).",
					".$db->qstr($running).",
					".$db->qstr($rate).",
					".$db->qstr($drate).",
					".$db->qstr($maxuploads).",
					".$db->qstr($runtime).",
					".$db->qstr($sharekill).",
					".$db->qstr($minport).",
					".$db->qstr($maxport).",
					".$db->qstr($maxcons).",
					".$db->qstr($savepath).",
					".$db->qstr($btclient).",
					".$db->qstr($tHash).",
					".$db->qstr($tDatapath)."
				   )";
	$db->Execute($sql);
	if ($db->ErrorNo() != 0) dbError($sql);
	return true;
}

/**
 * Function to load the settings for a torrent. returns array with settings
 *
 * @param $transfer
 * @return array
 */
function loadTransferSettings($transfer) {
	global $db;
	$sql = "SELECT * FROM tf_torrents WHERE torrent = '".$transfer."'";
	$result = $db->Execute($sql);
	if ($db->ErrorNo() != 0) dbError($sql);
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
 * Function to load the settings for a transfer to global cfg-array
 *
 * @param $transfer name of the transfer
 * @return boolean if the settings could be loaded (were existent in db already)
 */
function loadTransferSettingsToConfig($transfer) {
	global $cfg, $db;
	$sql = "SELECT * FROM tf_torrents WHERE torrent = '".$transfer."'";
	$result = $db->Execute($sql);
	if ($db->ErrorNo() != 0) dbError($sql);
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
 * @param $transfer name of the torrent
 */
function stopTransferSettings($transfer) {
  global $db;
  $db->Execute("UPDATE tf_torrents SET running = '0' WHERE torrent = '".$transfer."'");
  return true;
}

/**
 * checks if transfer is running by checking for existence of pid-file.
 *
 * @param $transfer name of the transfer
 * @return 1|0
 */
function isTransferRunning($transfer) {
	global $cfg;
	if ((substr($transfer, -8) == ".torrent")) {
		// this is a torrent-client
		return (file_exists($cfg["transfer_file_path"].substr($transfer, 0, -8).'.stat.pid')) ? 1 : 0;
	} else if ((substr($transfer, -5) == ".wget")) {
		// this is wget.
		return (file_exists($cfg["transfer_file_path"].substr($transfer, 0, -5).'.stat.pid')) ? 1 : 0;
	} else {
		return 0;
	}
}

/**
 * waits until transfer is up/down
 *
 * @param $transfer name of the transfer
 * @param $state : 1 = start, 0 = stop
 * @param $maxWait in seconds
 * @return 1|0
 */
function waitForTransfer($transfer, $state, $maxWait = 10) {
	$maxLoops = $maxWait * 5;
	$loopCtr = 0;
	while (1) {
		if (isTransferRunning($transfer) == $state) {
			return 1;
		} else {
		 	$loopCtr++;
		 	if ($loopCtr > $maxLoops)
		 		return 0;
		 	else
		 		usleep(200000); // wait for 0.2 seconds
		}
	}
	return 0;
}

/**
 * gets the btclient of the torrent out of the the db.
 *
 * @param $transfer name of the torrent
 * @return string
 */
function getTransferClient($transfer) {
	global $cfg, $db;
	$client = $db->GetOne("SELECT btclient FROM tf_torrents WHERE torrent = '".$transfer."'");
	if (empty($client)) {
		return ((substr($transfer, -5) == ".wget"))
			? "wget"
			: $cfg["btclient"];
	} else {
		return $client;
	}
}

/**
 * gets hash of a torrent
 * this should not be called external if its no must, use cached value in
 * tf_torrents if possible.
 *
 * @param $transfer name of the torrent
 * @return var with torrent-hash
 */
function getTorrentHash($transfer) {
	//info = metainfo['info']
	//info_hash = sha(bencode(info))
	//print 'metainfo file.: %s' % basename(metainfo_name)
	//print 'info hash.....: %s' % info_hash.hexdigest()
	global $cfg;
	$result = getTorrentMetaInfo($transfer);
	if (empty($result))
		return "";
	$resultAry = explode("\n",$result);
	$hashAry = array();
	switch ($cfg["metainfoclient"]) {
		case "transmissioncli":
		case "ttools.pl":
			$hashAry = explode(":",trim($resultAry[0]));
			break;
		case "btshowmetainfo.py":
		case "torrentinfo-console.py":
		default:
			$hashAry = explode(":",trim($resultAry[3]));
			break;
	}
	// return
	return (isset($hashAry[1])) ? trim($hashAry[1]) : "";
}

/**
 * resets totals of a torrent
 *
 * @param $transfer name of the torrent
 * @param $delete boolean if to delete torrent-file
 * @return message
 */
function resetTorrentTotals($transfer, $delete = false) {
	global $cfg, $db;
	$msgs = array();
	// delete torrent
	if ($delete == true) {
		$clientHandler = ClientHandler::getInstance(getTransferClient($transfer));
		$clientHandler->delete($transfer);
		if (count($clientHandler->messages) > 0)
    		$msgs = array_merge($msgs, $clientHandler->messages);
	} else {
		// reset in stat-file
		$af = new AliasFile(getAliasName($transfer).".stat", getOwner($transfer));
		if (isset($af)) {
			$af->uptotal = 0;
			$af->downtotal = 0;
			$af->write();
		}
	}
	// reset in db
	$sql = "DELETE FROM tf_torrent_totals WHERE tid = '".getTorrentHash($transfer)."'";
	$db->Execute($sql);
	if ($db->ErrorNo() != 0) dbError($sql);
	return $msgs;
}

/**
 * gets ary of running clients (via call to ps)
 *
 * @param $clientType
 * @return array
 */
function getRunningClientProcesses($clientType = '') {
	// client-array
	$clients = ($clientType == '')
		? array('tornado', 'transmission', 'mainline', 'wget')
		: array($clientType);
	// get clients
	$retVal = array();
	foreach ($clients as $client) {
		// client-handler
		$clientHandler = ClientHandler::getInstance($client);
		$procs = $clientHandler->runningProcesses();
		if (!empty($procs))
			$retVal = array_merge($retVal, $procs);
	}
	// return
	return $retVal;
}

/**
 * get info of running clients (via call to ps)
 *
 * @param $clientType
 * @return string
 */
function getRunningClientProcessInfo($clientType = '') {
	// client-array
	$clients = ($clientType == '')
		? array('tornado', 'transmission', 'mainline', 'wget')
		: array($clientType);
	// get clients
	$retVal = "";
	foreach ($clients as $client) {
		// client-handler
		$clientHandler = ClientHandler::getInstance($client);
		$retVal .= $clientHandler->runningProcessInfo();
	}
	// return
	return $retVal;
}

/**
 * get log of a Transfer
 *
 * @param $transfer
 * @return string
 */
function getTransferLog($transfer) {
	global $cfg;
	$emptyLog = "log empty";
	// sanity-check
	if (!isset($transfer) || (isValidTransfer($transfer) !== true))
		return "invalid transfer";
	// alias-name + log-file
	$aliasName = getAliasName($transfer);
	$transferLogFile = $cfg["transfer_file_path"].$aliasName.".log";
	// check
	if (!(file_exists($transferLogFile)))
		return $emptyLog;
	// open
	$handle = false;
	$handle = @fopen($transferLogFile, "r");
	if (!$handle)
		return $emptyLog;
	// read
	$data = "";
	while (!@feof($handle))
		$data .= @fgets($handle, 8192);
	@fclose ($handle);
	if ($data == "")
		return $emptyLog;
	// return
	return $data;
}

/**
 * deletes data of a torrent
 *
 * @param $transfer name of the torrent
 */
function deleteTorrentData($transfer) {
	global $cfg;
	$owner = getOwner($transfer);
	if (($cfg["user"] == $owner) || $cfg['isAdmin']) {
		require_once('inc/classes/BDecode.php');
		$ftorrent = $cfg["transfer_file_path"].$transfer;
		$fd = fopen($ftorrent, "rd");
		$alltorrent = fread($fd, filesize($ftorrent));
		$btmeta = @BDecode($alltorrent);
		$delete = @trim($btmeta['info']['name']);
		if (!empty($delete)) {
			// load transfer-settings from db to get data-location
			loadTransferSettingsToConfig($transfer);
			if ((!isset($cfg["savepath"])) || (empty($cfg["savepath"]))) {
				$cfg["savepath"] = ($cfg["enable_home_dirs"] != 0)
					? $cfg["path"].$owner.'/'
					: $cfg["path"].$cfg["path_incoming"].'/';
			}
			$delete = $cfg["savepath"].$delete;
			$del = stripslashes(stripslashes($delete));
			if (isValidPath($del)) {
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
		AuditAction($cfg["constants"]["error"], $cfg["user"]." attempted to delete ".$transfer);
	}
}

/**
 * gets size of data of a torrent
 *
 * @param $transfer name of the torrent
 * @return int with size of data of torrent.
 *		   -1 if error
 *		   4096 if dir (lol ~)
 */
function getTorrentDataSize($transfer) {
	global $cfg;
	require_once('inc/classes/BDecode.php');
	$ftorrent = $cfg["transfer_file_path"].$transfer;
	$fd = fopen($ftorrent, "rd");
	$alltorrent = fread($fd, filesize($ftorrent));
	$btmeta = @BDecode($alltorrent);
	$name = @trim($btmeta['info']['name']);
	if (!empty($name)) {
		// load transfer-settings from db to get data-location
		loadTransferSettingsToConfig($transfer);
		if ((!isset($cfg["savepath"])) || (empty($cfg["savepath"]))) {
			$cfg["savepath"] = ($cfg["enable_home_dirs"] != 0)
				? $cfg["path"].getOwner($transfer).'/'
				: $cfg["path"].$cfg["path_incoming"].'/';
		}
		$name = $cfg["savepath"].$name;
		$tData = stripslashes(stripslashes($name));
		if (isValidPath($tData))
			return file_size($tData);
	}
	return -1;
}

/**
 * gets datapath of a torrent.
 * this should not be called external if its no must, use cached value in
 * tf_torrents if possible.
 *
 * @param $transfer name of the torrent
 * @return var with torrent-datapath or empty string on error
 */
function getTorrentDatapath($transfer) {
	global $cfg;
    require_once('inc/classes/BDecode.php');
    $ftorrent = $cfg["transfer_file_path"].$transfer;
    $fd = fopen($ftorrent, "rd");
    $alltorrent = fread($fd, filesize($ftorrent));
    $btmeta = @BDecode($alltorrent);
    return (empty($btmeta['info']['name'])) ? "" : trim($btmeta['info']['name']);
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
	if ($dirHandle = @opendir($cfg["transfer_file_path"])) {
		$tCount = 0;
		while (false !== ($file = @readdir($dirHandle))) {
			if ((substr($file, -4, 4)) == ".pid")
				$tCount++;
		}
		@closedir($dirHandle);
		return $tCount;
	} else {
		return 0;
	}
}

/**
 * gets metainfo of a torrent as string
 *
 * @param $transfer name of the torrent
 * @return string with torrent-meta-info
 */
function getTorrentMetaInfo($transfer) {
	global $cfg;
	switch ($cfg["metainfoclient"]) {
		case "transmissioncli":
			return shell_exec($cfg["btclient_transmission_bin"] . " -i ".escapeshellarg($cfg["transfer_file_path"].$transfer));
		case "ttools.pl":
			return shell_exec($cfg["perlCmd"].' -I "'.$cfg["docroot"].'bin/ttools" "'.$cfg["docroot"].'bin/ttools/ttools.pl" -i '.escapeshellarg($cfg["transfer_file_path"].$transfer));
		case "torrentinfo-console.py":
			return shell_exec("cd ".$cfg["transfer_file_path"]."; ".$cfg["pythonCmd"]." -OO ".$cfg["docroot"]."bin/TF_Mainline/torrentinfo-console.py ".escapeshellarg($transfer));
		case "btshowmetainfo.py":
		default:
			return shell_exec("cd ".$cfg["transfer_file_path"]."; ".$cfg["pythonCmd"]." -OO ".$cfg["docroot"]."bin/TF_BitTornado/btshowmetainfo.py ".escapeshellarg($transfer));
	}
}

/**
 * gets scrape-info of a torrent as string
 *
 * @param $transfer name of the torrent
 * @return string with torrent-scrape-info
 */
function getTorrentScrapeInfo($transfer) {
	global $cfg;
	switch ($cfg["metainfoclient"]) {
		case "transmissioncli":
			return shell_exec($cfg["btclient_transmission_bin"] . " -s ".escapeshellarg($cfg["transfer_file_path"].$transfer));
		case "ttools.pl":
			return shell_exec($cfg["perlCmd"].' -I "'.$cfg["docroot"].'bin/ttools" "'.$cfg["docroot"].'bin/ttools/ttools.pl" -s '.escapeshellarg($cfg["transfer_file_path"].$transfer));
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
	if ($db->ErrorNo() != 0) dbError($sql);
	while(list($transfer) = $recordset->FetchRow())
		array_push($retVal, $transfer);
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
	foreach ($dataArray as $key => $value)
		$dataArray[$key] = ($value) ? 1 : 0;
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
	$binString = strrev(str_pad(decbin($dataByte), 8, "0", STR_PAD_LEFT));
	$bitArray = explode(":", chunk_split($binString, 1, ":"));
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
	foreach ($dataArray as $key => $value)
		$dataArray[$key] = ($value) ? 1 : 0;
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
	$binString = strrev(str_pad(decbin($dataInt), 31, "0", STR_PAD_LEFT));
	$bitArray = explode(":", chunk_split($binString, 1, ":"));
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
	global $CHECKDIR_RECURSION;
	if (isset($CHECKDIR_RECURSION))
		$CHECKDIR_RECURSION++;
	else
		$CHECKDIR_RECURSION = 0;
	if ($CHECKDIR_RECURSION > 10)
		return false;
	if ((@is_dir($dir) && @is_writable($dir)) || @mkdir($dir, $mode))
		return true;
	if (!@checkDirectory(dirname($dir), $mode))
		return false;
	return @mkdir($dir, $mode);
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
			return preg_replace("/.*load averages:(.*)/", "$1", exec("uptime"));
			break;
		default:
			return 'n/a';
	}
	return 'n/a';
}

/**
 * injects a Alias
 *
 * @param $transfer
 * @return boolean
 */
function injectAlias($transfer) {
	global $cfg;
	$af = new AliasFile(getAliasName($transfer).".stat");
	$af->running = "2"; // file is new
	$af->size = getDownloadSize($cfg["transfer_file_path"].$transfer);
	if ($af->write()) {
		return true;
	} else {
        AuditAction($cfg["constants"]["error"], "aliasfile cannot be written when injecting : ".$transfer);
        return false;
	}
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
 * This method gets transfers in an array
 *
 * @param $sortOrder
 * @return array
 */
function getTransferArray($sortOrder = '') {
	global $cfg;
	$handle = @opendir($cfg["transfer_file_path"]);
	if (!$handle)
		return null;
	$arList = array();
	while ($entry = readdir($handle)) {
		if (($entry{0} != ".") && isValidTransfer($entry))
			$arList[filemtime($cfg["transfer_file_path"]."/".$entry)] = $entry;
	}
	closedir($handle);
	// sort transfer-array
	$sortId = ($sortOrder != "") ? $sortOrder : $cfg["index_page_sortorder"];
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
			natcasesort($arList);
			$arList = array_reverse($arList, true);
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
		if (substr($entry, -8) == ".torrent") {
			// this is a torrent-client
			$isTorrent = true;
			$transferowner = getOwner($entry);
			$owner = IsOwner($cfg["user"], $transferowner);
			$settingsAry = loadTransferSettings($entry);
			$af = new AliasFile($alias, $transferowner);
		} else if (substr($entry, -5) == ".wget") {
			// this is wget.
			$isTorrent = false;
			$transferowner = getOwner($entry);
			$owner = IsOwner($cfg["user"], $transferowner);
			$settingsAry = array();
			$settingsAry['btclient'] = "wget";
			$settingsAry['hash'] = $entry;
			$af = new AliasFile($alias, $transferowner);
		} else {
			AuditAction($cfg["constants"]["error"], "INVALID TRANSFER: ".$entry);
			@error("Invalid Transfer", "index.php?iid=index", "", array($entry));
		}
		// cache running-flag in local var. we will access that often
		$transferRunning = (int) $af->running;
		// cache percent-done in local var. ...
		$percentDone = $af->percent_done;

		// ---------------------------------------------------------------------
		//XFER: add upload/download stats to the xfer array
		if (($cfg['enable_xfer'] == 1) && ($cfg['xfer_realtime'] == 1))
			$newday = transferListXferUpdate1($entry, $transferowner, $settingsAry['btclient'], $settingsAry['hash'], $af->uptotal, $af->downtotal);

		// ---------------------------------------------------------------------
		// injects
		if(!file_exists($cfg["transfer_file_path"].$alias)) {
			$transferRunning = 2;
			$af->running = "2";
			$af->size = getDownloadSize($cfg["transfer_file_path"].$entry);
			injectAlias($entry);
		}

		// totals-preparation
		// if downtotal + uptotal + progress > 0
		if (($settings[2] + $settings[3] + $settings[5]) > 0) {
			$clientHandler = ClientHandler::getInstance($settingsAry['btclient']);
			$transferTotals = $clientHandler->getTransferTotalOP($entry, $settingsAry['hash'], $af->uptotal, $af->downtotal);
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
			default: // running + stopped
				// increment the totals
				if (!isset($cfg["total_upload"])) $cfg["total_upload"] = 0;
				if (!isset($cfg["total_download"])) $cfg["total_download"] = 0;
				$cfg["total_upload"] = $cfg["total_upload"] + GetSpeedValue($af->up_speed);
				$cfg["total_download"] = $cfg["total_download"] + GetSpeedValue($af->down_speed);
				// $estTime
				if ($transferRunning == 0) {
					$estTime = $af->time_left;
				} else {
					if ($af->time_left != "" && $af->time_left != "0") {
						if (($cfg["display_seeding_time"] == 1) && ($af->percent_done >= 100) ) {
							$estTime = (($af->seedlimit > 0) && (!empty($af->up_speed)) && ((int) ($af->up_speed{0}) > 0))
									? convertTime(((($af->seedlimit) / 100 * $af->size) - $af->uptotal) / GetSpeedInBytes($af->up_speed)) . " left"
									: '-';
						} else {
							$estTime = $af->time_left;
						}
					}
				}
				// $lastUser
				$lastUser = $transferowner;
				// $show_run + $statusStr
				if($percentDone >= 100) {
					$statusStr = (($transferRunning == 1) && (trim($af->up_speed) != "")) ? 'Seeding' : 'Done';
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
			array_push($transferAry, @formatBytesTokBMBGBTB($af->size));

		// =========================================================== downtotal
		if ($settings[2] != 0)
			array_push($transferAry, @formatBytesTokBMBGBTB($transferTotals["downtotal"]));

		// ============================================================= uptotal
		if ($settings[3] != 0)
			array_push($transferAry, @formatBytesTokBMBGBTB($transferTotals["uptotal"]));

		// ============================================================== status
		if ($settings[4] != 0)
			array_push($transferAry, $statusStr);

		// ============================================================ progress
		if ($settings[5] != 0) {
			$percentage = "";
			if (($percentDone >= 100) && (trim($af->up_speed) != "")) {
				$percentage = @number_format((($transferTotals["uptotal"] / $af->size) * 100), 2) . '%';
			} else {
				if ($percentDone >= 1)
					$percentage = $percentDone . '%';
				else if ($percentDone < 0)
					$percentage = round(($percentDone*-1)-100,1) . '%';
				else
					$percentage = '0%';
			}
			array_push($transferAry, $percentage);
		}

		// ================================================================ down
		if ($settings[6] != 0) {
			$down = "";
			if ($transferRunning == 1)
				$down = (trim($af->down_speed) != "") ? $af->down_speed : '0.0 kB/s';
			array_push($transferAry, $down);
		}

		// ================================================================== up
		if ($settings[7] != 0) {
			$up = "";
			if ($transferRunning == 1)
				$up = (trim($af->up_speed) != "") ? $af->up_speed : '0.0 kB/s';
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
		@transferListXferUpdate2($newday);

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
	$queued = FluxdQmgr::countQueuedTransfers();
	array_push($serverStats, $queued);
	// speedDownPercent
	$percentDownload = 0;
	$maxDownload = $cfg["bandwidth_down"] / 8;
	$percentDownload = ($maxDownload > 0)
		? @number_format(($cfg["total_download"] / $maxDownload) * 100, 0)
		: 0;
	array_push($serverStats, $percentDownload);
	// speedUpPercent
	$percentUpload = 0;
	$maxUpload = $cfg["bandwidth_up"] / 8;
	$percentUpload = ($maxUpload > 0)
		? @number_format(($cfg["total_upload"] / $maxUpload) * 100, 0)
		: 0;
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
	// alias-file
	if ((!(isset($alias))) || ($alias == "")) {
		$aliasName = getAliasName($transfer);
		$alias = $aliasName.".stat";
	}
	// alias / stat
	if (substr($transfer, -8) == ".torrent") {
		// this is a torrent-client
		$transferowner = getOwner($transfer);
		$transferExists = loadTransferSettingsToConfig($transfer);
		if (!$transferExists) {
			// new torrent
			$cfg['hash'] = $transfer;
		}
		$af = new AliasFile($alias, $transferowner);
	} else if (substr($transfer, -5) == ".wget") {
		// this is wget.
		$transferowner = getOwner($transfer);
		$cfg['btclient'] = "wget";
		$cfg['hash'] = $transfer;
		$af = new AliasFile($alias, $transferowner);
	} else {
		AuditAction($cfg["constants"]["error"], "INVALID TRANSFER: ".$transfer);
		@error("Invalid Transfer", "index.php?iid=index", "", array($transfer));
	}
	// size
	$size = (int) $af->size;
	// totals
	$afu = $af->uptotal;
	$afd = $af->downtotal;
	$clientHandler = ClientHandler::getInstance($cfg['btclient']);
	$totalsCurrent = $clientHandler->getTransferCurrentOP($transfer, $cfg['hash'], $afu, $afd);
	$totals = $clientHandler->getTransferTotalOP($transfer, $cfg['hash'], $afu, $afd);
	// running
	$running = $af->running;
	$details['running'] = $running;
	// speed_down + speed_up + seeds + peers + cons
	if ($running == 1) {
		// pid
		$pid = getTransferPid($alias);
		// speed_down
		$details['speedDown'] = (trim($af->down_speed) != "") ? $af->down_speed : '0.0 kB/s';
		// speed_up
		$details['speedUp'] = (trim($af->up_speed) != "") ? $af->up_speed : '0.0 kB/s';
		// down_current
		$details['downCurrent'] = @formatFreeSpace($totalsCurrent["downtotal"] / 1048576);
		// up_current
		$details['upCurrent'] = @formatFreeSpace($totalsCurrent["uptotal"] / 1048576);
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
	$details['downTotal'] = @formatFreeSpace($totals["downtotal"] / 1048576);
	// up_total
	$details['upTotal'] = @formatFreeSpace($totals["uptotal"] / 1048576);
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
	$details['sharing'] = ($size > 0) ? @number_format((($totals["uptotal"] / $size) * 100), 2) : 0;
	// errors
	$details['errors'] = $af->errors;
	// full (including static) details
	if ($full) {
		// owner
		$details['owner'] = $transferowner;
		// size
		$details['size'] = @formatBytesTokBMBGBTB($size);
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
	$return="";

	if(array_key_exists($varName, $_REQUEST)){
		// If magic quoting on, strip magic quotes:
		/**
		* TODO:
		* Codebase needs auditing to remove any unneeded stripslashes
		* calls before uncommenting this.  Also using this really means
		* checking any addslashes() calls to see if they're really needed
		* when magic quotes is on.

		if(ini_get('magic_quotes_gpc')){
			tfb_strip_quotes($_REQUEST[$varName]);
		}
		*/
		$return = htmlentities(trim($_REQUEST[$varName]), ENT_QUOTES);
	}

	return $return;
}

/**
 *  Avoid magic_quotes_gpc issues
 *  courtesy of iliaa@php.net
 * @param	ref		&$var reference to a $_REQUEST variable
 * @return	null
 */
function tfb_strip_quotes(&$var){
	if (is_array($var)) {
		foreach ($var as $k => $v) {
			if (is_array($v)) {
				array_walk($var[$k], 'tfb_strip_quotes');
			} else {
				$var[$k] = stripslashes($v);
			}
		}
	} else {
		$var = stripslashes($var);
	}
}

/**
 * Audit Action
 *
 * @param $action
 * @param $file
 */
function AuditAction($action, $file="") {
    global $cfg, $db;
    $rec = array(
    	'user_id' => $cfg["user"],
    	'file' => $file,
    	'action' => ($action != "") ? $action : "unset",
    	'ip' => $cfg['ip'],
    	'ip_resolved' => htmlentities(gethostbyaddr($cfg['ip']), ENT_QUOTES),
    	'user_agent' => (isset($_SERVER['HTTP_USER_AGENT'])) ? htmlentities($_SERVER['HTTP_USER_AGENT'], ENT_QUOTES) : "torrentflux-b4rt/".$cfg["version"],
    	'time' => time()
        );
    $sTable = 'tf_log';
    $sql = $db->GetInsertSql($sTable, $rec);
    // add record to the log
    $db->Execute($sql);
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
        if ($file == @trim(shell_exec("ls 2>/dev/null ".escapeshellarg($file))))
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
	return ($db->GetOne("SELECT count(*) FROM tf_log WHERE user_id=" . $db->qstr($user)." AND action=".$db->qstr($cfg["constants"]["hit"])) > 0);
}

/**
 * IsUser
 *
 * @param $user
 * @return boolean
 */
function IsUser($user) {
	global $db;
	return ($db->GetOne("SELECT count(*) FROM tf_users WHERE user_id=".$db->qstr($user)) > 0);
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
	$user_id = $db->GetOne("SELECT user_id FROM tf_log WHERE file=".$db->qstr($file)." AND (action=".$db->qstr($cfg["constants"]["file_upload"])." OR action=".$db->qstr($cfg["constants"]["url_upload"])." OR action=".$db->qstr($cfg["constants"]["reset_owner"]).") ORDER BY time DESC");
	$rtnValue = ($user_id != "")
		? $user_id
		: resetOwner($file); // try and get the owner from the stat file
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
	// log entry has expired so we must renew it
	$rtnValue = "";
	$alias = getAliasName($file).".stat";
	if (file_exists($cfg["transfer_file_path"].$alias)) {
		$af = new AliasFile($alias, $cfg["user"]);
		$rtnValue = (IsUser($af->transferowner))
			? $af->transferowner /* We have an owner */
			: GetSuperAdmin(); /* no owner found, so the super admin will now own it */
		$rec = array(
						'user_id' => $rtnValue,
						'file' => $file,
						'action' => $cfg["constants"]["reset_owner"],
						'ip' => $cfg['ip'],
						'ip_resolved' => gethostbyaddr($cfg['ip']),
						'user_agent' => (isset($_SERVER['HTTP_USER_AGENT'])) ? htmlentities($_SERVER['HTTP_USER_AGENT'], ENT_QUOTES) : "torrentflux-b4rt/".$cfg["version"],
						'time' => time()
					);
		$sTable = 'tf_log';
		$sql = $db->GetInsertSql($sTable, $rec);
		// add record to the log
		$result = $db->Execute($sql);
		if ($db->ErrorNo() != 0) dbError($sql);
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
	return (($user) == ($owner));
}

/**
 * GetSpeedValue
 *
 * @param $inValue
 * @return number
 */
function GetSpeedValue($inValue) {
	$arTemp = split(" ", trim($inValue));
	return (is_numeric($arTemp[0])) ? $arTemp[0] : 0;
}

/**
 * Is User Admin : user is Admin if level is 1 or higher
 *
 * @param $user
 * @return boolean
 */
function IsAdmin($user = "") {
	global $cfg, $db;
	if ($user == "")
		$user = $cfg["user"];
	return ($db->GetOne("SELECT user_level FROM tf_users WHERE user_id=".$db->qstr($user)) >= 1);
}

/**
 * Is User SUPER Admin : user is Super Admin if level is higher than 1
 *
 * @param $user
 * @return boolean
 */
function IsSuperAdmin($user = "") {
	global $cfg, $db;
	if ($user == "")
		$user = $cfg["user"];
	return ($db->GetOne("SELECT user_level FROM tf_users WHERE user_id=".$db->qstr($user)) > 1);
}

/**
 * Get Users in an array
 *
 * @return array
 */
function GetUsers() {
	global $db;
	$user_array = array();
	$user_array = $db->GetCol("select user_id from tf_users order by user_id");
	return $user_array;
}

/**
 * Get Super Admin User ID as a String
 *
 * @return string
 */
function GetSuperAdmin() {
	global $db;
	return $db->GetOne("select user_id from tf_users WHERE user_level=2");
}

/**
 * Get Links in an array
 *
 * @return array
 */
function GetLinks() {
	global $db;
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
	if ((!array_key_exists('searchEngineLinks', $cfg)) || (!is_array($cfg['searchEngineLinks'])))
		saveSettings('tf_settings', $settings);
	$handle = opendir("./inc/searchEngines");
	while($entry = readdir($handle))
		$entrys[] = $entry;
	natcasesort($entrys);
	foreach($entrys as $entry) {
		if ($entry != "." && $entry != ".." && substr($entry, 0, 1) != ".")
			if (strpos($entry, "Engine.php")) {
				$tmpEngine = str_replace("Engine",'',substr($entry,0,strpos($entry,".")));
				if (array_key_exists($tmpEngine,$cfg['searchEngineLinks'])) {
					$hreflink = $cfg['searchEngineLinks'][$tmpEngine];
					$settings['searchEngineLinks'][$tmpEngine] = $hreflink;
				} else {
					$hreflink = getEngineLink($tmpEngine);
					$settings['searchEngineLinks'][$tmpEngine] = $hreflink;
					$settingsNeedsSaving = true;
				}
				if (strlen($hreflink) > 0) {
					array_push($output, array(
							'hreflink' => $hreflink,
							'selected' => ($selectedEngine == $tmpEngine) ? 1 : 0,
							)
						);
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
		$tag = ($reg[1][0] == "/") ? strtolower(substr($reg[1],1)) : strtolower($reg[1]);
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
		$str = ($cfg["enable_dereferrer"] != 0)
			? preg_replace('/(http:\/\/)(.*)([[:space:]]*)/i', '<a href="index.php?iid=dereferrer&u=${1}${2}" target="_blank">${1}${2}</a>${3}', $str)
			: preg_replace('/(http:\/\/)(.*)([[:space:]]*)/i', '<a href="${1}${2}" target="_blank">${1}${2}</a>${3}', $str);
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
	if (is_dir($drive)) {
		$dt = disk_total_space($drive);
		$df = disk_free_space($drive);
		return round((($dt - $df) / $dt) * 100);
	}
	return 0;
}

/**
 * Grab the full size of the download from the torrent metafile
 *
 * @param $transfer
 * @return int
 */
function getDownloadSize($transfer) {
	if (@file_exists($transfer)) {
		require_once("inc/classes/BDecode.php");
		if ($fd = fopen($transfer, "rd")) {
			$alltorrent = @fread($fd, @filesize($transfer));
			$array = @BDecode($alltorrent);
			@fclose($fd);
		}
		return ((isset($array["info"]["piece length"])) && (isset($array["info"]["pieces"])))
			? $array["info"]["piece length"] * (strlen($array["info"]["pieces"]) / 20)
			: "";
	}
	return 0;
}

/**
 * Returns a string in format of TB, GB, MB, or kB depending on the size
 *
 * @param $inBytes
 * @return string
 */
function formatBytesTokBMBGBTB($inBytes) {
	if ($inBytes > 1099511627776)
		return round($inBytes / 1099511627776, 2) . " TB";
	elseif ($inBytes > 1073741824)
		return round($inBytes / 1073741824, 2) . " GB";
	elseif ($inBytes < 1048576)
		return round($inBytes / 1024, 1) . " kB";
	else
		return round($inBytes / 1048576, 1) . " MB";
}

/**
 * Convert free space to TB, GB or MB depending on size
 *
 * @param $freeSpace
 * @return string
 */
function formatFreeSpace($freeSpace) {
	if ($freeSpace > 1048576)
		return number_format($freeSpace / 1048576, 2)." TB";
	elseif ($freeSpace > 1024)
		return number_format($freeSpace / 1024, 2)." GB";
	else
		return number_format($freeSpace, 2)." MB";
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
		$hd->image = (trim($af->up_speed) != "" && $af->running == "1")
			? "green.gif" /* is seeding */
			: "black.gif"; /*  the torrent is finished */
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
		return exec("ls -l ".escapeshellarg($file)." 2>/dev/null | awk '{print $5}'");
	return $size;
}

/**
 * Estimated time left to seed
 *
 * @param $inValue
 * @return string
 */
function GetSpeedInBytes($inValue) {
	$arTemp = split(" ", trim($inValue));
	return ($arTemp[1] == "kB/s") ? $arTemp[0] * 1024 : $arTemp[0];
}

/**
 * convertTime
 *
 * @param $seconds
 * @return common time-delta-string
 */
function convertTime($seconds) {
	// sanity-check
	if ($seconds < 0) return '?';
	// one week is enough
	if ($seconds >= 604800) return '-';
	// format time-delta
	$periods = array (/* 31556926, 2629743, 604800,*/ 86400, 3600, 60, 1);
	$seconds = (float) $seconds;
	$values = array();
	foreach ($periods as $period) {
		$count = floor($seconds / $period);
		if ($count == 0)
			continue;
		array_push($values, ($count < 10) ? "0".$count : $count);
		$seconds = $seconds % $period;
	}
	return (empty($values)) ? "?" : implode(':', $values);
}

/**
 * Returns true if user has message from admin with force_read
 *
 * @return boolean
 */
function IsForceReadMsg() {
	global $cfg, $db;
	return ($db->GetOne("SELECT count(*) FROM tf_messages WHERE to_user=".$db->qstr($cfg["user"])." AND force_read=1") >= 1);
}

/**
 * check if path is valid
 *
 * @param $path
 * @param $ext
 * @return boolean
 */
function isValidPath($path, $ext = "") {
	if (preg_match("/\\\/", $path)) return false;
	if (preg_match("/\.\.\//", $path)) return false;
	if ($ext != "") {
		$extLength = strlen($ext);
		if (strlen($path) < $extLength) return false;
		if ((strtolower(substr($path, -($extLength)))) !== strtolower($ext)) return false;
	}
	return true;
}

/**
 * check if transfer is valid
 *
 * @param $transfer
 * @return boolean
 */
function isValidTransfer($transfer) {
	global $cfg;
	return ((preg_match('/^[0-9a-zA-Z._-]+('.$cfg["file_types_string"].')$/', $transfer)) == 1);
}

/**
 * Create Alias name for Text file and Screen Alias
 *
 * @param $inName
 * @return string
 */
function getAliasName($inName) {
	global $cfg;
	return str_replace($cfg["file_types_array"], "", preg_replace("/[^0-9a-zA-Z.-]+/",'_', $inName));
}

/**
 * Remove bad characters make extension lower-case
 *
 * @param $inName
 * @return string
 */
function cleanFileName($inName) {
	global $cfg;
	$outName = preg_replace("/[^0-9a-zA-Z.-]+/",'_', $inName);
	$stringLength = strlen($outName);
	foreach ($cfg['file_types_array'] as $ftype) {
		$extLength = strlen($ftype);
		if (($stringLength > $extLength) && (strtolower(substr($outName, -($extLength))) === ($ftype)))
			return substr($outName, 0, -($extLength)).$ftype;
	}
	$msgs = array();
	array_push($msgs, "inName : ".$inName);
	array_push($msgs, "outName : ".$outName);
	array_push($msgs, "\nvalid file-extensions :");
	$msgs = array_merge($msgs, $cfg['file_types_array']);
	AuditAction($cfg["constants"]["error"], "INVALID FILETYPE: ".$inName);
	@error("Invalid File-Type", "index.php?iid=index", "", $msgs);
}

/**
 * split on the "*" coming from Varchar URL
 *
 * @param $url
 * @return string
 */
function cleanURL($url) {
	$arURL = explode("*", $url);
	return (sizeof($arURL) > 1) ? $arURL[1] : $url;
}

/**
 * print message
 *
 * @param $msg
 */
function printMessage($mod, $msg) {
	@fwrite(STDOUT, @date("[Y/m/d - H:i:s]")."[".$mod."] ".$msg);
}

/**
 * print error
 *
 * @param $msg
 */
function printError($mod, $msg) {
	@fwrite(STDERR, @date("[Y/m/d - H:i:s]")."[".$mod."] ".$msg);
}

?>