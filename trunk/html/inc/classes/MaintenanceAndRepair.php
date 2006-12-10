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
 * MaintenanceAndRepair
 */
class MaintenanceAndRepair
{
	// fields

	// name
	var $name = "MaintenanceAndRepair";

	// version
    var $version = "0.1";

    // config-array
    var $cfg = array();

    // messages-array
    var $messages = array();

    // state
    //  0 : not initialized
    //  1 : initialized
    //  2 : done
    // -1 : error
    var $state = 0;

    // mode
    // 1 : cli
    // 2 : web
    var $mode = 0;

	// transfer fields
	var $bogusTransfers = array();
	var $fixedTransfers = array();
	var $restartTransfers = false;

	// counter
	var $count = 0;
	var $countProblems = 0;
	var $countFixed = 0;

    // factory + ctor

    /**
     * factory
     *
     * @param $cfg
     * @return Rssd
     */
    function getInstance($cfg) {
    	return new MaintenanceAndRepair(serialize($cfg));
    }

    /**
     * do not use direct, use the factory-method !
     *
     * @param $cfg (serialized)
     * @return Rssd
     */
    function MaintenanceAndRepair($cfg) {
        // messages
        $this->messages = array();
        // cfg
        $this->cfg = unserialize($cfg);
        if (empty($this->cfg)) {
        	$this->state = -1;
            array_push($this->messages, "Config not passed");
            return false;
        }
        // cli/web
		global $argv;
		if (isset($argv)) {
			$this->mode = 1;
		} else
			$this->mode = 2;
        // state
        $this->state = 1;
    }

    /* public meths */

	/**
	 * maintenance
	 *
	 * @param $trestart
	 */
	function maintenance($trestart = false) {
		// output
		$this->outputMessage("Running Maintenance...\n");
		// fluxd
		$this->maintenanceFluxd();
		// transfers
		$this->maintenanceTransfers($trestart);
		// database
		$this->maintenanceDatabase();
		// output
		$this->outputMessage("Maintenance done.\n");
		// state
		$this->state = 2;
	}

	/**
	 * repair
	 */
	function repair() {
		// output
		$this->outputMessage("Running Repair...\n");
		// fluxd
		$this->maintenanceFluxd();
		// repair app
		$this->repairApp();
		// database
		$this->maintenanceDatabase();
		// log
		AuditAction($this->cfg["constants"]["debug"], "Repair done.");
		/* done */
		$this->outputMessage("Repair done.\n");
		// state
		$this->state = 2;
	}

    /* private meths */

	// =========================================================================
	// maintenance-methods
	// =========================================================================

	/**
	 * maintenanceFluxd
	 * delete leftovers of fluxd (only do this if daemon is not running)
	 */
	function maintenanceFluxd() {
		// output
		$this->outputMessage("fluxd-maintenance...\n");
		// files
		$fdp = $this->cfg["path"].'.fluxd/fluxd.pid';
		$fds = $this->cfg["path"].'.fluxd/fluxd.sock';
		$fdpe = file_exists($fdp);
		$fdse = file_exists($fds);
		// pid or socket exists
		if (($fdpe || $fdse) && (
			("0" == @trim(shell_exec("ps aux 2> /dev/null | ".$this->cfg['bin_grep']." -v grep | ".$this->cfg['bin_grep']." -c ".$this->cfg["docroot"]."bin/fluxd/fluxd.pl"))))) {
			// problems
			$this->outputMessage("found and removing fluxd-leftovers...\n");
			// pid
			if ($fdpe)
				@unlink($fdp);
			// socket
			if ($fdse)
				@unlink($fds);
			// DEBUG : log the repair
			if ($this->cfg['debuglevel'] > 0)
				AuditAction($this->cfg["constants"]["debug"], "fluxd-maintenance : found and removed fluxd-leftovers.");
			// output
			$this->outputMessage("done.\n");
		} else {
			// no problems
			$this->outputMessage("no problems found.\n");
		}
		/* done */
		$this->outputMessage("fluxd-maintenance done.\n");
	}

	/**
	 * maintenanceTransfers
	 *
	 * @param $trestart
	 * @return boolean
	 */
	function maintenanceTransfers($trestart = false) {
		global $db, $queueActive;
		// set var
		$this->restartTransfers = $trestart;
		// output
		$this->outputMessage("transfers-maintenance...\n");
		// sanity-check for transfers-dir
		if (!is_dir($this->cfg["transfer_file_path"])) {
			$this->state = -1;
            $msg = "invalid dir-settings. no dir : ".$this->cfg["transfer_file_path"];
            array_push($this->messages , $msg);
			$this->outputError($msg."\n");
			return false;
		}
		// pid-files of transfer-clients
		$pidFiles = array();
		if ($dirHandle = @opendir($this->cfg["transfer_file_path"])) {
			while (false !== ($file = @readdir($dirHandle))) {
				if ((strlen($file) > 3) && ((substr($file, -4, 4)) == ".pid"))
					array_push($pidFiles, $file);
			}
			@closedir($dirHandle);
		}
		// return if no pid-files found
		if (count($pidFiles) < 1) {
			$this->outputMessage("no pid-files found.\n");
			$this->outputMessage("transfers-maintenance done.\n");
			return true;
		}
		// get process-list
		$psString = trim(shell_exec("ps x -o pid='' -o ppid='' -o command='' -ww"));
		// test if client for pid is still up
		$this->bogusTransfers = array();
		foreach ($pidFiles as $pidFile) {
			$alias = substr($pidFile, 0, -4);
			$transfer = (substr($alias, 0, -5));
			if (stristr($psString, $transfer) === false)
				array_push($this->bogusTransfers, $transfer);
		}
		// return if no stale pid-files
		$this->countProblems = count($this->bogusTransfers);
		if ($this->countProblems < 1) {
			$this->outputMessage("no stale pid-files found.\n");
			$this->outputMessage("transfers-maintenance done.\n");
			return true;
		}

		/* repair the bogus clients */
		$this->countFixed = 0;
		$this->outputMessage("repairing died clients...\n");
		require_once("inc/classes/AliasFile.php");
		foreach ($this->bogusTransfers as $bogusTransfer) {
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
			// output
			$this->outputMessage("repairing ".$transfer." ...\n");
			// get owner
			$transferowner = getOwner($transfer);
			// rewrite stat-file
			$af = AliasFile::getAliasFileInstance($alias, $transferowner, $this->cfg, $settingsAry['btclient']);
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
			@unlink($this->cfg["transfer_file_path"].$pidFile);
			// DEBUG : log the repair of the bogus transfer
			if ($this->cfg['debuglevel'] > 0)
				AuditAction($this->cfg["constants"]["debug"], "transfers-maintenance : transfer repaired : ".$transfer);
			// output
			$this->outputMessage("done.\n");
			// count
			$this->countFixed++;
		}
		// output
		if ($this->countProblems > 0)
			$this->outputMessage("repaired transfers : ".$this->countFixed."/".$this->countProblems."\n");

		/* restart transfers */
		if ($this->restartTransfers) {
			$this->fixedTransfers = array();
			$this->outputMessage("restarting died clients...\n");
			// hold current user
			$whoami = ($this->mode == 1) ? GetSuperAdmin() : $this->cfg["user"];
			foreach ($this->bogusTransfers as $bogusTransfer) {
				$transfer = $bogusTransfer.".torrent";
				$alias = $bogusTransfer.".stat";
				$pidFile = $alias.".pid";
				$settingsAry = loadTorrentSettings($transfer);
				if (!((isset($settingsAry)) && (is_array($settingsAry)))) {
					// this is a wget-client, skip it
					continue;
				}
				// output
				$this->outputMessage("Starting ".$transfer." ...\n");
				// get owner
				$transferowner = getOwner($transfer);
				// set current user to transfer-owner
				$this->cfg["user"] = $transferowner;
				// file-prio
	            if ($this->cfg["enable_file_priority"]) {
	                include_once("inc/functions/functions.setpriority.php");
	                // Process setPriority Request.
	                setPriority($transfer);
	            }
				// clientHandler + start
				$clientHandler = ClientHandler::getClientHandlerInstance($this->cfg, $settingsAry['btclient']);
				$clientHandler->startClient($transfer, 0, $queueActive);
				// DEBUG : log the restart of the died transfer
				if ($this->cfg['debuglevel'] > 0) {
					$staret = ($clientHandler->state == 3) ? "OK" : "FAILED";
					AuditAction($this->cfg["constants"]["debug"], "transfers-maintenance : restarted transfer ".$transfer." for ".$whoami." : ".$staret);
				}
				//
				if ($clientHandler->state == 3) {
					// output
					$this->outputMessage("done.\n");
					// add to ary
					array_push($this->fixedTransfers, $transfer);
					// count
					$this->countFixed++;
				} else {
		            array_push($this->messages , $clientHandler->messages);
					$this->outputError($clientHandler->messages."\n");
				}
			}
			// set user back
			$this->cfg["user"] = $whoami;
			// output
			$this->countFixed = count($this->fixedTransfers);
			if ($this->countFixed > 0)
				$this->outputMessage("restarted transfers : ".$this->countFixed."/".$this->countProblems."\n");
		}

		/* done */
		$this->outputMessage("transfers-maintenance done.\n");
		// return
		return true;
	}

	/**
	 * maintenanceDatabase
	 */
	function maintenanceDatabase() {
		global $db;
		// output
		$this->outputMessage("database-maintenance...\n");

		/* tf_torrents */
		$this->countProblems = 0;
		$this->countFixed = 0;
		// output
		$this->outputMessage("table-maintenance : tf_torrents\n");
		// running-flag
		$sql = "SELECT torrent FROM tf_torrents WHERE running = '1'";
		$recordset = $db->Execute($sql);
		showError($db, $sql);
		$rc = $recordset->RecordCount();
		if ($rc > 0) {
			while (list($tname) = $recordset->FetchRow()) {
				if (isTransferRunning($tname) == 0) {
					$this->countProblems++;
					// t is not running, reset running-flag
					$this->outputMessage("reset of running-flag for transfer which is not running : ".$tname."\n");
					$sql = "UPDATE tf_torrents SET running = '0' WHERE torrent = '".$tname."'";
					$db->Execute($sql);
					$this->countFixed++;
					// output
					$this->outputMessage("done.\n");
				}
			}
		}
		// empty hash
		$sql = "SELECT torrent FROM tf_torrents WHERE hash = ''";
		$recordset = $db->Execute($sql);
		showError($db, $sql);
		$rc = $recordset->RecordCount();
		if ($rc > 0) {
			$this->countProblems += $rc;
			while (list($tname) = $recordset->FetchRow()) {
				// t has no hash, update
				$this->outputMessage("updating transfer which has empty hash : ".$tname."\n");
				// get hash
				$thash = getTorrentHash($tname);
				// update
				if (!empty($thash)) {
					$sql = "UPDATE tf_torrents SET hash = '".$thash."' WHERE torrent = '".$tname."'";
					$db->Execute($sql);
					$this->countFixed++;
					// output
					$this->outputMessage("done.\n");
				}
			}
		}
		// empty datapath
		$sql = "SELECT torrent FROM tf_torrents WHERE datapath = ''";
		$recordset = $db->Execute($sql);
		showError($db, $sql);
		$rc = $recordset->RecordCount();
		if ($rc > 0) {
			$this->countProblems += $rc;
			while (list($tname) = $recordset->FetchRow()) {
				// t has no datapath, update
				$this->outputMessage("updating transfer which has empty datapath : ".$tname."\n");
				// get datapath
				$tDatapath = getTorrentDatapath($tname);
				// update
				if (!empty($tDatapath)) {
					$sql = "UPDATE tf_torrents SET datapath = ".$db->qstr($tDatapath)." WHERE torrent = '".$tname."'";
					$db->Execute($sql);
					$this->countFixed++;
					// output
					$this->outputMessage("done.\n");
				}
			}
		}
		// output + log
		if ($this->countProblems == 0) {
			// output
			$this->outputMessage("no problems found.\n");
		} else {
			// DEBUG : log
			$msg = "found and fixed problems in tf_torrents : ".$this->countFixed."/".$this->countProblems;
			if ($this->cfg['debuglevel'] > 0)
				AuditAction($this->cfg["constants"]["debug"], "database-maintenance : table-maintenance : ".$msg);
			// output
			$this->outputMessage($msg."\n");
		}

		/* tf_torrent_totals */
		$this->countProblems = 0;
		$this->countFixed = 0;
		// output
		$this->outputMessage("table-maintenance : tf_torrent_totals\n");
		$this->countProblems = $db->GetOne("SELECT COUNT(*) FROM tf_torrent_totals WHERE tid = ''");
		if (($this->countProblems !== false) && ($this->countProblems > 0)) {
			// output
			$this->outputMessage("found ".$this->countProblems." invalid entries, deleting...\n");
			$sql = "DELETE FROM tf_torrent_totals WHERE tid = ''";
			$result = $db->Execute($sql);
			showError($db, $sql);
			$this->countFixed = $db->Affected_Rows();
			// output
			$this->outputMessage("done.\n");
			$rCount = ($this->countFixed !== false) ? $this->countFixed : $this->countProblems;
			// DEBUG : log
			$msg = "found and removed invalid totals-entries from tf_torrent_totals : ".$rCount."/".$this->countProblems;
			if ($this->cfg['debuglevel'] > 0)
				AuditAction($this->cfg["constants"]["debug"], "database-maintenance : table-maintenance : ".$msg);
			// output
			$this->outputMessage($msg."\n");
		} else {
			// output
			$this->outputMessage("no problems found.\n");
		}

		// prune db
		$this->maintenanceDatabasePrune();

		/* done */
		$this->outputMessage("database-maintenance done.\n");

	}

	/**
	 * prune database
	 */
	function maintenanceDatabasePrune() {
		global $db;
		// output
		$this->outputMessage("pruning database...\n");
		$this->outputMessage("table : tf_log\n");
		// Prune LOG
		$this->count = 0;
		$testTime = time() - ($this->cfg['days_to_keep'] * 86400); // 86400 is one day in seconds
		$sql = "delete from tf_log where time < " . $db->qstr($testTime);
		$result = $db->Execute($sql);
		showError($db,$sql);
		$this->count += $db->Affected_Rows();
		unset($result);
		$testTime = time() - ($this->cfg['minutes_to_keep'] * 60);
		$sql = "delete from tf_log where time < " . $db->qstr($testTime). " and action=".$db->qstr($this->cfg["constants"]["hit"]);
		$result = $db->Execute($sql);
		showError($db,$sql);
		$this->count += $db->Affected_Rows();
		unset($result);
		/* done */
		if ($this->count > 0)
			$this->outputMessage("deleted entries from tf_log : ".$this->count."\n");
		else
			$this->outputMessage("no entries deleted.\n");
		$this->outputMessage("prune database done.\n");
	}

	// =========================================================================
	// repair-methods
	// =========================================================================

	/**
	 * repairApp
	 */
	function repairApp() {
		global $db;
		// output
		$this->outputMessage("repairing app...\n");
		// sanity-check for transfers-dir
		if (!is_dir($this->cfg["transfer_file_path"])) {
			$this->state = -1;
            $msg = "invalid dir-settings. no dir : ".$this->cfg["transfer_file_path"];
            array_push($this->messages , $msg);
			$this->outputError($msg."\n");
			return false;
		}
		// delete pid-files of torrent-clients
		if ($dirHandle = opendir($this->cfg["transfer_file_path"])) {
			while (false !== ($file = readdir($dirHandle))) {
				if ((strlen($file) > 3) && ((substr($file, -4, 4)) == ".pid"))
					@unlink($this->cfg["transfer_file_path"].$file);
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
			$af = AliasFile::getAliasFileInstance($alias.".stat", $owner, $this->cfg, $btclient);
			if (isset($af)) {
				// output
				$this->outputMessage("rewrite stat-file for ".$torrent." ...\n");
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
				// output
				$this->outputMessage("done.\n");
			}
		}
		// set flags in db
		$this->outputMessage("reset running-flag in database...\n");
		$db->Execute("UPDATE tf_torrents SET running = '0'");
		// output
		$this->outputMessage("done.\n");
		/* done */
		$this->outputMessage("repair app done.\n");
	}

	// =========================================================================
	// output-methods
	// =========================================================================

    /**
     * output message
     *
     * @param $message
     */
	function outputMessage($message) {
        // only in cli-mode
		if ($this->mode == 1)
			printMessage($this->name, $message);
    }

    /**
     * output error
     *
     * @param $message
     */
	function outputError($message) {
        // only in cli-mode
		if ($this->mode == 1)
			printError($this->name, $message);
    }

}

?>