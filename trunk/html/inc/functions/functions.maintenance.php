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
 * maintenance
 *
 * @param : $cliMode
 * @param $restartTransfers
 * @return boolean
 */
function maintenance($cliMode = false, $restartTransfers = false) {
	// totals
	maintenanceTotals($cliMode);
	// transfers
	maintenanceTransfers($cliMode, $restartTransfers);
}

/**
 * repair
 *
 * @param : $cliMode
 * @return boolean
 */
function repair($cliMode = false) {
	// totals
	maintenanceTotals($cliMode);
	// repair app
	repairApp();
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
		if ($cliMode)
			printMessage("fluxcli.php", "no pid-files found.\n");
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
	if (count($bogusTransfers) < 1) {
		if ($cliMode)
			printMessage("fluxcli.php", "no stale pid-files found.\n");
		return true;
	}
	// repair the bogus clients
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
			AuditAction($cfg["constants"]["debug"], "maintenance : transfer repaired : ".$transfer);
		// print
		if ($cliMode)
			printMessage("fluxcli.php", "done.\n");
	}
	// restart transfers
	if ($restartTransfers) {
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
                include_once("inc/setpriority.php");
                // Process setPriority Request.
                setPriority($transfer);
            }
			// clientHandler + start
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg, $settingsAry['btclient']);
			$clientHandler->startClient($transfer, 0, $queueActive);
			// DEBUG : log the restart of the died transfer
			if ($cfg['debuglevel'] > 0) {
				$staret = ($clientHandler->state == 3) ? "OK" : "FAILED";
				AuditAction($cfg["constants"]["debug"], "maintenance : transfer ".$transfer." restarted by ".$whoami." (".$staret.")");
			}
			// print
			if ($cliMode) {
				if ($clientHandler->state == 3)
					printMessage("fluxcli.php", "done.\n");
				else
					printError("fluxcli.php", $clientHandler->messages."\n");
			}
		}
		// set user back
		$cfg["user"] = $whoami;
	}
	// return
	return true;
}

/**
 * maintenanceTotals
 *
 * @param $cliMode : boolean
 */
function maintenanceTotals($cliMode = false) {
	global $cfg, $db;
	if ($cliMode)
		printMessage("fluxcli.php", "repairing totals...\n");
	$bogusCount = 0;
	$bogusCount = $db->GetOne("SELECT COUNT(*) FROM tf_torrent_totals WHERE tid = ''");
	if (($bogusCount !== false) && ($bogusCount > 0)) {
		if ($cliMode)
			printMessage("fluxcli.php", "found ".$bogusCount." invalid entries, deleting...\n");
		$sql = "DELETE FROM tf_torrent_totals WHERE tid = ''";
		$result = $db->Execute($sql);
		showError($db, $sql);
		// DEBUG : log the repair
		if ($cfg['debuglevel'] > 0)
			AuditAction($cfg["constants"]["debug"], "maintenanceTotals : found and removed ".$bogusCount." invalid totals-entries");
	} else {
		if ($cliMode)
			printMessage("fluxcli.php", "no problems found.\n");
	}
}

/**
 * repairApp
 */
function repairApp() {
	global $cfg, $db;
	// sanity-check for transfers-dir
	if (!is_dir($cfg["transfer_file_path"]))
		return false;
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
		}
	}
	// set flags in db
	$db->Execute("UPDATE tf_torrents SET running = '0'");
	// delete leftovers of fluxd (only do this if daemon is not running)
	$fluxdRunning = trim(shell_exec("ps aux 2> /dev/null | ".$cfg['bin_grep']." -v grep | ".$cfg['bin_grep']." -c ".$cfg["docroot"]."bin/fluxd/fluxd.pl"));
	if ($fluxdRunning == "0") {
		// pid
		if (file_exists($cfg["path"].'.fluxd/fluxd.pid'))
			@unlink($cfg["path"].'.fluxd/fluxd.pid');
		// socket
		if (file_exists($cfg["path"].'.fluxd/fluxd.sock'))
			@unlink($cfg["path"].'.fluxd/fluxd.sock');
	}
}

/**
 * prune db
 */
function maintenancePruneDB() {
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

?>