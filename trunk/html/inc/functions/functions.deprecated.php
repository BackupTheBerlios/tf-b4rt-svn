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

// =============================================================================
// maintenance- and repair-functions
// =============================================================================

/**
 * maintenance
 *
 * @param : $cliMode
 * @param $restartTransfers
 * @return boolean
 */
function maintenance($cliMode = false, $restartTransfers = false) {
	// print
	if ($cliMode)
		printMessage("fluxcli.php", "Running Maintenance...\n");
	// fluxd
	maintenanceFluxd($cliMode);
	// transfers
	maintenanceTransfers($cliMode, $restartTransfers);
	// database
	maintenanceDatabase($cliMode);
	// print
	if ($cliMode)
		printMessage("fluxcli.php", "Maintenance done.\n");
}

/**
 * repair
 *
 * @param : $cliMode
 * @return boolean
 */
function repair($cliMode = false) {
	global $cfg;
	// print
	if ($cliMode)
		printMessage("fluxcli.php", "Running Repair...\n");
	// fluxd
	maintenanceFluxd($cliMode);
	// repair app
	repairApp($cliMode);
	// database
	maintenanceDatabase($cliMode);
	// log
	AuditAction($cfg["constants"]["debug"], "Repair done.");
	/* done */
	if ($cliMode)
		printMessage("fluxcli.php", "Repair done.\n");
}

// =============================================================================
// maintenance-functions
// =============================================================================

/**
 * maintenanceFluxd
 * delete leftovers of fluxd (only do this if daemon is not running)
 *
 * @param $cliMode : boolean
 */
function maintenanceFluxd($cliMode = false) {
	global $cfg;
	// print
	if ($cliMode)
		printMessage("fluxcli.php", "fluxd-maintenance...\n");
	// files
	$fdp = $cfg["path"].'.fluxd/fluxd.pid';
	$fds = $cfg["path"].'.fluxd/fluxd.sock';
	$fdpe = file_exists($fdp);
	$fdse = file_exists($fds);
	// pid or socket exists
	if (($fdpe || $fdse) && (
		("0" == @trim(shell_exec("ps aux 2> /dev/null | ".$cfg['bin_grep']." -v grep | ".$cfg['bin_grep']." -c ".$cfg["docroot"]."bin/fluxd/fluxd.pl"))))) {
		// problems
		if ($cliMode)
			printMessage("fluxcli.php", "found and removing fluxd-leftovers...");
		// pid
		if ($fdpe)
			@unlink($fdp);
		// socket
		if ($fdse)
			@unlink($fds);
		// DEBUG : log the repair
		if ($cfg['debuglevel'] > 0)
			AuditAction($cfg["constants"]["debug"], "fluxd-maintenance : found and removed fluxd-leftovers.");
		// print
		if ($cliMode)
			printMessage("fluxcli.php", "done.\n");
	} else {
		// no problems
		if ($cliMode)
			printMessage("fluxcli.php", "no problems found.\n");
	}
	/* done */
	if ($cliMode)
		printMessage("fluxcli.php", "fluxd-maintenance done.\n");
}

/**
 * maintenanceTransfers
 *
 * @param : $cliMode
 * @param $restartTransfers
 * @return boolean
 */
function maintenanceTransfers($cliMode = false, $restartTransfers = false) {
	global $cfg, $db, $queueActive;
	// print
	if ($cliMode)
		printMessage("fluxcli.php", "transfers-maintenance...\n");
	// sanity-check for transfers-dir
	if (!is_dir($cfg["transfer_file_path"])) {
		if ($cliMode)
			printError("fluxcli.php", "invalid dir-settings. no dir : ".$cfg["transfer_file_path"]."\n");
		return false;
	}
	// pid-files of transfer-clients
	$pidFiles = array();
	if ($dirHandle = @opendir($cfg["transfer_file_path"])) {
		while (false !== ($file = @readdir($dirHandle))) {
			if ((strlen($file) > 3) && ((substr($file, -4, 4)) == ".pid"))
				array_push($pidFiles, $file);
		}
		@closedir($dirHandle);
	}
	// return if no pid-files found
	if (count($pidFiles) < 1) {
		if ($cliMode) {
			printMessage("fluxcli.php", "no pid-files found.\n");
			printMessage("fluxcli.php", "transfers-maintenance done.\n");
		}
		return true;
	}
	// get process-list
	$psString = trim(shell_exec("ps x -o pid='' -o ppid='' -o command='' -ww"));
	// test if client for pid is still up
	$bogusTransfers = array();
	foreach ($pidFiles as $pidFile) {
		$alias = substr($pidFile, 0, -4);
		$transfer = (substr($alias, 0, -5));
		if (stristr($psString, $transfer) === false)
			array_push($bogusTransfers, $transfer);
	}
	// return if no stale pid-files
	$countProblems = count($bogusTransfers);
	if ($countProblems < 1) {
		if ($cliMode) {
			printMessage("fluxcli.php", "no stale pid-files found.\n");
			printMessage("fluxcli.php", "transfers-maintenance done.\n");
		}
		return true;
	}

	/* repair the bogus clients */
	$countFixed = 0;
	if ($cliMode)
		printMessage("fluxcli.php", "repairing died clients...\n");
	require_once("inc/classes/AliasFile.php");
	foreach ($bogusTransfers as $bogusTransfer) {
		$transfer = $bogusTransfer.".torrent";
		$alias = $bogusTransfer.".stat";
		$pidFile = $alias.".pid";
		$settingsAry = loadTorrentSettings($transfer);
		if ((isset($settingsAry)) && (is_array($settingsAry))) {
			// this is a torrent-client
			// set stopped flag in db
			stopTorrentSettings($transfer);
		} else {
			// this is a wget-client
			$transfer = $bogusTransfer.".wget";
			$settingsAry = array();
			$settingsAry['btclient'] = "wget";
		}
		// print
		if ($cliMode)
			printMessage("fluxcli.php", "repairing ".$transfer." ...\n");
		// get owner
		$transferowner = getOwner($transfer);
		// rewrite stat-file
		$af = AliasFile::getAliasFileInstance($alias, $transferowner, $cfg, $settingsAry['btclient']);
		if (isset($af)) {
			$af->running = 0;
			$af->percent_done = -100.0;
			$af->time_left = 'Transfer Died';
			$af->down_speed = 0;
			$af->up_speed = 0;
			$af->seeds = 0;
			$af->peers = 0;
			$af->WriteFile();
			unset($af);
		}
		// delete pid-file
		@unlink($cfg["transfer_file_path"].$pidFile);
		// DEBUG : log the repair of the bogus transfer
		if ($cfg['debuglevel'] > 0)
			AuditAction($cfg["constants"]["debug"], "transfers-maintenance : transfer repaired : ".$transfer);
		// print
		if ($cliMode)
			printMessage("fluxcli.php", "done.\n");
		// count
		$countFixed++;
	}
	// print
	if ($countProblems > 0) {
		if ($cliMode)
			printMessage("fluxcli.php", "repaired transfers : ".$countFixed."/".$countProblems."\n");
	}

	/* restart transfers */
	if ($restartTransfers) {
		$countFixed = 0;
		if ($cliMode)
			printMessage("fluxcli.php", "restarting died clients...\n");
		// hold current user
		$whoami = ($cliMode) ? GetSuperAdmin() : $cfg["user"];
		foreach ($bogusTransfers as $bogusTransfer) {
			$transfer = $bogusTransfer.".torrent";
			$alias = $bogusTransfer.".stat";
			$pidFile = $alias.".pid";
			$settingsAry = loadTorrentSettings($transfer);
			if (!((isset($settingsAry)) && (is_array($settingsAry)))) {
				// this is a wget-client, skip it
				continue;
			}
			// print
			if ($cliMode)
				printMessage("fluxcli.php", "Starting ".$transfer." ...\n");
			// get owner
			$transferowner = getOwner($transfer);
			// set current user to transfer-owner
			$cfg["user"] = $transferowner;
			// file-prio
            if ($cfg["enable_file_priority"]) {
                include_once("inc/functions/functions.setpriority.php");
                // Process setPriority Request.
                setPriority($transfer);
            }
			// clientHandler + start
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg, $settingsAry['btclient']);
			$clientHandler->startClient($transfer, 0, $queueActive);
			// DEBUG : log the restart of the died transfer
			if ($cfg['debuglevel'] > 0) {
				$staret = ($clientHandler->state == 3) ? "OK" : "FAILED";
				AuditAction($cfg["constants"]["debug"], "transfers-maintenance : restarted transfer ".$transfer." for ".$whoami." : ".$staret);
			}
			// print
			if ($cliMode) {
				if ($clientHandler->state == 3) {
					// print
					printMessage("fluxcli.php", "done.\n");
					// count
					$countFixed++;
				} else {
					printError("fluxcli.php", $clientHandler->messages."\n");
				}
			}
		}
		// set user back
		$cfg["user"] = $whoami;
		// print
		if ($countProblems > 0) {
			if ($cliMode)
				printMessage("fluxcli.php", "restarted transfers : ".$countFixed."/".$countProblems."\n");
		}
	}

	/* done */
	if ($cliMode)
		printMessage("fluxcli.php", "transfers-maintenance done.\n");
	// return
	return true;
}

/**
 * maintenanceDatabase
 *
 * @param $cliMode : boolean
 */
function maintenanceDatabase($cliMode = false) {
	global $cfg, $db;
	// print
	if ($cliMode)
		printMessage("fluxcli.php", "database-maintenance...\n");

	/* tf_torrents */
	$countProblems = 0;
	$countFixed = 0;
	// print
	if ($cliMode)
		printMessage("fluxcli.php", "table-maintenance : tf_torrents\n");
	// running-flag
	$sql = "SELECT torrent FROM tf_torrents WHERE running = '1'";
	$recordset = $db->Execute($sql);
	showError($db, $sql);
	$rc = $recordset->RecordCount();
	if ($rc > 0) {
		while (list($tname) = $recordset->FetchRow()) {
			if (isTransferRunning($tname) == 0) {
				$countProblems++;
				// t is not running, reset running-flag
				if ($cliMode)
					printMessage("fluxcli.php", "reset of running-flag for transfer which is not running : ".$tname."\n");
				$sql = "UPDATE tf_torrents SET running = '0' WHERE torrent = '".$tname."'";
				$db->Execute($sql);
				$countFixed++;
				// print
				if ($cliMode)
					printMessage("fluxcli.php", "done.\n");
			}
		}
	}
	// empty hash
	$sql = "SELECT torrent FROM tf_torrents WHERE hash = ''";
	$recordset = $db->Execute($sql);
	showError($db, $sql);
	$rc = $recordset->RecordCount();
	if ($rc > 0) {
		$countProblems += $rc;
		while (list($tname) = $recordset->FetchRow()) {
			// t has no hash, update
			if ($cliMode)
				printMessage("fluxcli.php", "updating transfer which has empty hash : ".$tname."\n");
			// get hash
			$thash = getTorrentHash($tname);
			// update
			if (!empty($thash)) {
				$sql = "UPDATE tf_torrents SET hash = '".$thash."' WHERE torrent = '".$tname."'";
				$db->Execute($sql);
				$countFixed++;
				// print
				if ($cliMode)
					printMessage("fluxcli.php", "done.\n");
			}
		}
	}
	// empty datapath
	$sql = "SELECT torrent FROM tf_torrents WHERE datapath = ''";
	$recordset = $db->Execute($sql);
	showError($db, $sql);
	$rc = $recordset->RecordCount();
	if ($rc > 0) {
		$countProblems += $rc;
		while (list($tname) = $recordset->FetchRow()) {
			// t has no datapath, update
			if ($cliMode)
				printMessage("fluxcli.php", "updating transfer which has empty datapath : ".$tname."\n");
			// get datapath
			$tDatapath = getTorrentDatapath($tname);
			// update
			if (!empty($tDatapath)) {
				$sql = "UPDATE tf_torrents SET datapath = ".$db->qstr($tDatapath)." WHERE torrent = '".$tname."'";
				$db->Execute($sql);
				$countFixed++;
				// print
				if ($cliMode)
					printMessage("fluxcli.php", "done.\n");
			}
		}
	}
	// print + log
	if ($countProblems == 0) {
		// print
		if ($cliMode)
			printMessage("fluxcli.php", "no problems found.\n");
	} else {
		// DEBUG : log
		$msg = "found and fixed problems in tf_torrents : ".$countFixed."/".$countProblems;
		if ($cfg['debuglevel'] > 0)
			AuditAction($cfg["constants"]["debug"], "database-maintenance : table-maintenance : ".$msg);
		// print
		if ($cliMode)
			printMessage("fluxcli.php", $msg."\n");
	}

	/* tf_torrent_totals */
	$countProblems = 0;
	$countFixed = 0;
	// print
	if ($cliMode)
		printMessage("fluxcli.php", "table-maintenance : tf_torrent_totals\n");
	$countProblems = $db->GetOne("SELECT COUNT(*) FROM tf_torrent_totals WHERE tid = ''");
	if (($countProblems !== false) && ($countProblems > 0)) {
		// print
		if ($cliMode)
			printMessage("fluxcli.php", "found ".$countProblems." invalid entries, deleting...\n");
		$sql = "DELETE FROM tf_torrent_totals WHERE tid = ''";
		$result = $db->Execute($sql);
		showError($db, $sql);
		$countFixed = $db->Affected_Rows();
		// print
		if ($cliMode)
			printMessage("fluxcli.php", "done.\n");
		$rCount = ($countFixed !== false) ? $countFixed : $countProblems;
		// DEBUG : log
		$msg = "found and removed invalid totals-entries from tf_torrent_totals : ".$rCount."/".$countProblems;
		if ($cfg['debuglevel'] > 0)
			AuditAction($cfg["constants"]["debug"], "database-maintenance : table-maintenance : ".$msg);
		// print
		if ($cliMode)
			printMessage("fluxcli.php", $msg."\n");
	} else {
		// print
		if ($cliMode)
			printMessage("fluxcli.php", "no problems found.\n");
	}

	// prune db
	maintenanceDatabasePrune($cliMode);

	/* done */
	if ($cliMode)
		printMessage("fluxcli.php", "database-maintenance done.\n");

}

/**
 * prune database
 *
 * @param $cliMode : boolean
 */
function maintenanceDatabasePrune($cliMode = false) {
	global $cfg, $db;
	// print
	if ($cliMode) {
		printMessage("fluxcli.php", "pruning database...\n");
		printMessage("fluxcli.php", "table : tf_log\n");
	}
	// Prune LOG
	$count = 0;
	$testTime = time() - ($cfg['days_to_keep'] * 86400); // 86400 is one day in seconds
	$sql = "delete from tf_log where time < " . $db->qstr($testTime);
	$result = $db->Execute($sql);
	showError($db,$sql);
	$count += $db->Affected_Rows();
	unset($result);
	$testTime = time() - ($cfg['minutes_to_keep'] * 60);
	$sql = "delete from tf_log where time < " . $db->qstr($testTime). " and action=".$db->qstr($cfg["constants"]["hit"]);
	$result = $db->Execute($sql);
	showError($db,$sql);
	$count += $db->Affected_Rows();
	unset($result);
	/* done */
	if ($cliMode) {
		if ($count > 0)
			printMessage("fluxcli.php", "deleted entries from tf_log : ".$count."\n");
		else
			printMessage("fluxcli.php", "no entries found.\n");
		printMessage("fluxcli.php", "prune database done.\n");
	}
}

// =============================================================================
// repair-functions
// =============================================================================

/**
 * repairApp
 *
 * @param $cliMode : boolean
 */
function repairApp($cliMode = false) {
	global $cfg, $db;
	// print
	if ($cliMode)
		printMessage("fluxcli.php", "repairing app...\n");
	// sanity-check for transfers-dir
	if (!is_dir($cfg["transfer_file_path"])) {
		if ($cliMode)
			printError("fluxcli.php", "invalid dir-settings. no dir : ".$cfg["transfer_file_path"]."\n");
		return false;
	}
	// delete pid-files of torrent-clients
	if ($dirHandle = opendir($cfg["transfer_file_path"])) {
		while (false !== ($file = readdir($dirHandle))) {
			if ((strlen($file) > 3) && ((substr($file, -4, 4)) == ".pid"))
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
		$af = AliasFile::getAliasFileInstance($alias.".stat", $owner, $cfg, $btclient);
		if (isset($af)) {
			// print
			if ($cliMode)
				printMessage("fluxcli.php", "rewrite stat-file for ".$torrent." ...\n");
			$af->running = 0;
			$af->percent_done = -100.0;
			$af->time_left = 'Torrent Stopped';
			$af->down_speed = 0;
			$af->up_speed = 0;
			$af->seeds = 0;
			$af->peers = 0;
			$af->errors = array();
			$af->WriteFile();
			unset($af);
			// print
			if ($cliMode)
				printMessage("fluxcli.php", "done.\n");
		}
	}
	// set flags in db
	if ($cliMode)
		printMessage("fluxcli.php", "reset running-flag in database...\n");
	$db->Execute("UPDATE tf_torrents SET running = '0'");
	// print
	if ($cliMode)
		printMessage("fluxcli.php", "done.\n");
	/* done */
	if ($cliMode)
		printMessage("fluxcli.php", "repair app done.\n");
}


// =============================================================================
// HTTP-functions
// =============================================================================

/**
 * get data from URL. Has support for specific sites
 *
 * @param $url
 * @return string
 */
function FetchTorrent($url) {
	global $cfg, $db, $messages;

	// Initialize torrent name:
	$cfg["save_torrent_name"] = "";

	ini_set("allow_url_fopen", "1");
	ini_set("user_agent", $_SERVER['HTTP_USER_AGENT']);
	$domain	 = parse_url($url);

	// Check we have a remote URL:
	if(!isset($domain["host"])){
		// Not a remote URL:
		$messages="The torrent requested for download (".$url.") is not a remote torrent.  Please enter a valid remote torrent URL such as http://example.com/example.torrent\n";
		AuditAction($cfg["constants"]["error"], $messages);

		// return empty HTML:
		return($html="");
	}

	if (strtolower(substr($domain["path"], -8)) != ".torrent") {
		/*
			In these cases below, we check for torrent URLs that have to be manipulated in some
			way to obtain the torrent content.  These are sites that perhaps use redirection or
			URL rewriting in some way.
		*/
		// Check known domain types
		if (strpos(strtolower($domain["host"]), "mininova") !== false) {
			// Sample (http://www.mininova.org/rss.xml):
			// http://www.mininova.org/tor/2254847
			// <a href="/get/2281554">FreeLinux.ISO.iso.torrent</a>
			// If received a /tor/ get the required information
			if (strpos($url, "/tor/") !== false) {
				// Get the contents of the /tor/ to find the real torrent name
				$html = FetchHTML($url);
				// Check for the tag used on mininova.org
				if (preg_match("/<a href=\"\/get\/[0-9].[^\"]+\">(.[^<]+)<\/a>/i", $html, $html_preg_match)) {
					// This is the real torrent filename
					$cfg["save_torrent_name"] = $html_preg_match[1];
				}
				// Change to GET torrent url
				$url = str_replace("/tor/", "/get/", $url);
			}

			// Now fetch the torrent file
			$html = FetchHTML($url);
		} elseif (strpos(strtolower($domain["host"]), "isohunt") !== false) {
			// Sample (http://isohunt.com/js/rss.php):
			// http://isohunt.com/download.php?mode=bt&id=8837938
			// http://isohunt.com/btDetails.php?ihq=&id=8464972
			$referer = "http://" . $domain["host"] . "/btDetails.php?id=";

			// If the url points to the details page, change it to the download url
			if (strpos(strtolower($url), "/btdetails.php?") !== false) {
				// Need to make it grab the torrent
				$url = str_replace("/btDetails.php?", "/download.php?", $url) . "&mode=bt";
			}

			// Grab contents of details page
			$html = FetchHTML($url, $referer);
		} elseif (strpos(strtolower($url), "details.php?") !== false) {
			// Sample (http://www.bitmetv.org/rss.php?passkey=123456):
			// http://www.bitmetv.org/details.php?id=18435&hit=1
			$referer = "http://" . $domain["host"] . "/details.php?id=";
			$html = FetchHTML($url, $referer);

			// Sample (http://www.bitmetv.org/details.php?id=18435)
			// download.php/18435/SpiderMan%20Season%204.torrent
			if (preg_match("/(download.php.[^\"]+)/i", $html, $html_preg_match)) {
				$torrent = str_replace(" ", "%20", substr($html_preg_match[0], 0, -1));
				$url2 = "http://" . $domain["host"] . "/" . $torrent;
				$html = FetchHTML($url2);
			} else {
				$messages = "Error: could not find link to torrent file in $url";
				return($html="");
			}
		} elseif (strpos(strtolower($url), "download.asp?") !== false) {
			// Sample (TF's TorrenySpy Search):
			// http://www.torrentspy.com/download.asp?id=519793
			$referer = "http://" . $domain["host"] . "/download.asp?id=";
			$html = FetchHTML($url, $referer);
		} else {
			// Fallback case for any URL not ending in .torrent and not matching the above cases:
			$html = FetchHTML($url);
		}
	} else {
		$html = FetchHTML($url);
	}

	// Make sure we have a torrent file
	if (strpos($html, "d8:") === false)	{
		// We don't have a Torrent File... it is something else.  Let the user know about it:
		$messages = "Content returned from $url does not appear to be a valid torrent.";
		AuditAction($cfg["constants"]["error"], $messages);

		// Display the first part of $html if debuglevel higher than 1:
		if($cfg["debuglevel"] > 1){
			if(strlen($html) > 0){
				$messages .="  Displaying first 1024 chars of output: ".htmlentities(substr($html, 0, 1023), ENT_QUOTES);
			} else {
				$messages .="  Output from $url was empty.";
			}
		} else {
			$messages.="  Set debuglevel > 2 in 'Admin, Webapps' to see the content returned from $url.";
		}
		$html = "";
	} else {
		// If the torrent file name isn't set already, do it now:
		if ((!isset($cfg["save_torrent_name"])) || (strlen($cfg["save_torrent_name"]) == 0)) {
			// Get the name of the torrent, and make it the filename
			if (preg_match("/name([0-9][^:]):(.[^:]+)/i", $html, $html_preg_match)) {
				$filelength = $html_preg_match[1];
				$filename = $html_preg_match[2];
				$cfg["save_torrent_name"] = substr($filename, 0, $filelength) . ".torrent";
			} else {
				$cfg["save_torrent_name"] = "unknown.torrent";
			}
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
	ini_set("user_agent", $_SERVER['HTTP_USER_AGENT']);

	/**
	 * array of URL component parts for use in raw HTTP request
	 * @param	array	$domain
	 */
	$domain = parse_url($url);

	/**
	 * URI/path used in GET request:
	 * @param	string	$getcmd
	 */
	$getcmd	= $domain["path"];

    if (!array_key_exists("query", $domain))
        $domain["query"] = "";

	// append the query string if included:
    $getcmd .= (!empty($domain["query"])) ? "?" . $domain["query"] : "";

	/**
	 * Cookie string used in raw HTTP request
	 * @param	string	$cookie
	 */
	$cookie = "";

	// Check to see if cookie required for this domain:
	$sql = "SELECT c.data FROM tf_cookies AS c LEFT JOIN tf_users AS u ON ( u.uid = c.uid ) WHERE u.user_id = '" . $cfg["user"] . "' AND c.host = '" . $domain['host'] . "'";
	$cookie = $db->GetOne($sql);
	showError($db, $sql);

	if (!array_key_exists("port", $domain))
		$domain["port"] = 80;

	/**
	 * the raw HTTP request to send to the remote webserver
	 * @param	string	$request
	 */
	$request = "";

	/**
	 * the raw HTTP response received from the remote webserver
	 * @param	string	$responseBody
	 */
	$responseBody = "";

	/**
	 * Array of HTTP response headers
	 * @param	array	$responseHeaders
	 */
	$responseHeaders = array();

	/**
	 * Indicates if we got the response line or not from webserver
	 * 'HTTP/1.1 200 OK
	 * etc
	 * @param	bool	$gotResponseLine
	 */
	$gotResponseLine = false;

	/**
	 * Status code of webserver resonse
	 * @param	string	$status
	 */
	$status = "";

	/**
	 * Temporarily use HTTP/1.0 until chunked encoding is sorted out
	 * Valid values are '1.0' or '1.1'
	 * @param	string	$httpVersion
	 */
	$httpVersion = "1.0";

	/**
	 * Error string used in fsockopen
	 * @param	string	$errstr
	 */
	$errstr="";

	/**
	 * Error number used in fsockopen
	 * @param	int		$errno
	 */
	$errno="";

	// Check to see if this site requires the use of cookies
	// Whilst in SVN/testing, always use the cookie/raw HTTP handling code:
	if (true || !empty($cookie)) {
		$socket = @fsockopen($domain["host"], $domain["port"], $errno, $errstr, 30); //connect to server

		if(!empty($socket)) {
			// Write the outgoing HTTP request using cookie info

			// Standard HTTP/1.1 request looks like:
			//
			// GET /url/path/example.php HTTP/1.1
			// Host: example.com
			// Accept: */*
			// Accept-Language: en-us
			// User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-GB; rv:1.8.1) Gecko/20061010 Firefox/2.0
			// Connection: Close
			// Cookie: uid=12345;pass=asdfasdf;
			//
			$request  = "GET " . ($httpVersion=="1.1" ? $getcmd : $url ). " HTTP/" . $httpVersion ."\r\n";
			$request .= (!empty($referer)) ? "Referer: " . $referer . "\r\n" : "";
			$request .= "Accept: */*\r\n";
			$request .= "Accept-Language: en-us\r\n";
			$request .= "User-Agent: ".$_SERVER['HTTP_USER_AGENT']."\r\n";
			$request .= "Host: " . $domain["host"] . "\r\n";
			if($httpVersion=="1.1"){
				$request .= "Connection: Close\r\n";
			}
			$request .= "Cookie: " . $cookie . "\r\n\r\n";

			// Send header packet information to server
			fputs($socket, $request);

			// Get response headers:
			while ($line=@fgets($socket, 500000)){
				// First empty line/\r\n indicates end of response headers:
				if($line == "\r\n"){
					break;
				}

				if(!$gotResponseLine){
					preg_match("@HTTP/[^ ]+ (\d\d\d)@", $line, $matches);
					// TODO: Use this to see if we redirected (30x) and follow the redirect:
					$status = $matches[1];
					$gotResponseLine = true;
					continue;
				}

				// Get response headers:
				preg_match("/^([^:]+):\s*(.*)/", trim($line), $matches);
				$responseHeaders[strtolower($matches[1])] = $matches[2];
			}

			if(
				$httpVersion=="1.1"
				&& isset($responseHeaders["transfer-encoding"])
				&& !empty($responseHeaders["transfer-encoding"])
			){
				/*
				// NOT CURRENTLY WORKING, USE HTTP/1.0 ONLY UNTIL THIS IS FIXED!
				*/

				// Get body of HTTP response:
				// Handle chunked encoding:
				/*
						length := 0
						read chunk-size, chunk-extension (if any) and CRLF
						while (chunk-size > 0) {
						   read chunk-data and CRLF
						   append chunk-data to entity-body
						   length := length + chunk-size
						   read chunk-size and CRLF
						}
				*/

				// Used to count total of all chunk lengths, the content-length:
				$chunkLength=0;

				// Get first chunk size:
				$chunkSize = hexdec(trim(fgets($socket)));

				// 0 size chunk indicates end of content:
				while($chunkSize > 0){
					// Read in up to $chunkSize chars:
					$line=@fgets($socket, $chunkSize);

					// Discard crlf after current chunk:
					fgets($socket);

					// Append chunk to response body:
					$responseBody.=$line;

					// Keep track of total chunk/content length:
					$chunkLength+=$chunkSize;

					// Read next chunk size:
					$chunkSize = hexdec(trim(fgets($socket)));
				}
				$responseHeaders["content-length"] = $chunkLength;
			} else {
				while ($line=@fread($socket, 500000)){
					$responseBody .= $line;
				}
			}
			@fclose($socket); // Close our connection
		} else {
			return "Error fetching $url.  PHP Error No=$errno. PHP Error String=$errstr";
		}
	} else {
		// No cookies - no need for raw HTTP:
		if ($fp = @fopen($url, 'r')) {
			while (!@feof($fp))
				$responseBody .= @fgets($fp, 4096);

			@fclose($fp);
		}
	}

	// If no response from server or we were redirected with 30x response,
	// try cURL:
	if (
			($responseBody == "" && function_exists("curl_init"))
			||
			(preg_match("#HTTP/1\.[01] 30#", $responseBody) > 0 && function_exists("curl_init"))
		){

		// Give CURL a Try
		$ch = curl_init();

		if ($cookie != "")
			curl_setopt($ch, CURLOPT_COOKIE, $cookie);

		curl_setopt($ch, CURLOPT_PORT, $domain["port"]);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_VERBOSE, FALSE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 5);

		$responseBody = curl_exec($ch);

		curl_close($ch);
	}

	// Trim any extraneous linefeed chars:
	$responseBody = trim($responseBody, "\r\n");

	// If a filename is associated with this content, assign it to $cfg:
	if(isset($responseHeaders["content-disposition"]) && !empty($responseHeaders["content-disposition"])){
		// Content-disposition: attachment; filename="nameoffile":
		// Don't think single quotes can be used to escape filename here, but just in case check for ' and ":
		if(preg_match("/filename=(['\"])([^\\1]+)\\1/", $responseHeaders["content-disposition"], $matches)){
			if(isset($matches[2]) && !empty($matches[2])){
				$filename=$matches[2];

				// Only accept filenames, not paths:
				if(!preg_match("@/@", $filename)){
					$cfg["save_torrent_name"] = $filename;
				}
			}
		}
	}

	return $responseBody;
}

?>