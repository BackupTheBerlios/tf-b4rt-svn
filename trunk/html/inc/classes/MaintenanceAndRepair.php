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

// states
define('MAINTENANCEANDREPAIR_STATE_NULL', 0);          // null (not initialized)
define('MAINTENANCEANDREPAIR_STATE_INITIALIZED', 1);              // initialized
define('MAINTENANCEANDREPAIR_STATE_DONE', 2);                            // done
define('MAINTENANCEANDREPAIR_STATE_ERROR', -1);                         // error

// modes
define('MAINTENANCEANDREPAIR_MODE_CLI', 1);                               // cli
define('MAINTENANCEANDREPAIR_MODE_WEB', 2);                               // web

/**
 * MaintenanceAndRepair
 */
class MaintenanceAndRepair
{
	// public fields

	// version
    var $version = "0.2";

    // state
    var $state = MAINTENANCEANDREPAIR_STATE_NULL;

    // messages-array
    var $messages = array();

	// private fields

    // config-array
    var $_cfg = array();

    // mode
    var $_mode = 0;

	// transfer fields
	var $_bogusTransfers = array();
	var $_fixedTransfers = array();
	var $_restartTransfers = false;

	// counter
	var $_count = 0;
	var $_countProblems = 0;
	var $_countFixed = 0;

	// =========================================================================
	// public static methods
	// =========================================================================

    /**
     * accessor for singleton
     *
     * @return MaintenanceAndRepair
     */
    function getInstance() {
		global $instanceMaintenanceAndRepair;
		// initialize
		MaintenanceAndRepair::initialize();
		// return instance
		return $instanceMaintenanceAndRepair;
    }

    /**
     * initialize MaintenanceAndRepair.
     */
    function initialize() {
    	global $cfg, $instanceMaintenanceAndRepair;
    	// create instance
    	if (!isset($instanceMaintenanceAndRepair))
    		$instanceMaintenanceAndRepair = new MaintenanceAndRepair(serialize($cfg));
    }

    /**
     * accessor for state
     *
     * @return int
     */
    function getState() {
		global $instanceMaintenanceAndRepair;
		return (isset($instanceMaintenanceAndRepair))
			? $instanceMaintenanceAndRepair->state
			: MAINTENANCEANDREPAIR_STATE_NULL;
    }

    /**
     * accessor for messages
     *
     * @return array
     */
    function getMessages() {
		global $instanceMaintenanceAndRepair;
		return (isset($instanceMaintenanceAndRepair))
			? $instanceMaintenanceAndRepair->messages
			: array();
    }

	/**
	 * maintenance
	 *
	 * @param $trestart
	 */
	function maintenance($trestart = false) {
		global $instanceMaintenanceAndRepair;
		// initialize
		MaintenanceAndRepair::initialize();
		// maintenance run
		$instanceMaintenanceAndRepair->instance_maintenance($trestart);
	}

	/**
	 * repair
	 */
	function repair() {
		global $instanceMaintenanceAndRepair;
		// initialize
		MaintenanceAndRepair::initialize();
		// repair run
		$instanceMaintenanceAndRepair->instance_repair();
	}

	// =========================================================================
	// ctor
	// =========================================================================

    /**
     * do not use direct, use the factory-method !
     *
     * @param $cfg (serialized)
     * @return Rssd
     */
    function MaintenanceAndRepair($cfg) {
        // messages
        $this->messages = array();
        // _cfg
        $this->_cfg = unserialize($cfg);
        if (empty($this->_cfg)) {
        	$this->state = MAINTENANCEANDREPAIR_STATE_ERROR;
            array_push($this->messages, "Config not passed");
            return false;
        }
        // cli/web
		global $argv;
		if (isset($argv)) {
			$this->_mode = MAINTENANCEANDREPAIR_MODE_CLI;
		} else
			$this->_mode = MAINTENANCEANDREPAIR_MODE_WEB;
        // state
        $this->state = MAINTENANCEANDREPAIR_STATE_INITIALIZED;
    }

	// =========================================================================
	// public methods
	// =========================================================================

	/**
	 * instance_maintenance
	 *
	 * @param $trestart
	 */
	function instance_maintenance($trestart = false) {
		// output
		$this->_outputMessage("Running Maintenance...\n");
		// fluxd
		$this->_maintenanceFluxd();
		// transfers
		$this->_maintenanceTransfers($trestart);
		// database
		$this->_maintenanceDatabase();
		// output
		$this->_outputMessage("Maintenance done.\n");
		// state
		$this->state = MAINTENANCEANDREPAIR_STATE_DONE;
	}

	/**
	 * instance_repair
	 */
	function instance_repair() {
		// output
		$this->_outputMessage("Running Repair...\n");
		// fluxd
		$this->_maintenanceFluxd();
		// repair app
		$this->_repairApp();
		// database
		$this->_maintenanceDatabase();
		// log
		AuditAction($this->_cfg["constants"]["debug"], "Repair done.");
		/* done */
		$this->_outputMessage("Repair done.\n");
		// state
		$this->state = MAINTENANCEANDREPAIR_STATE_DONE;
	}

	// =========================================================================
	// private methods
	// =========================================================================

	/* maintenance-methods */

	/**
	 * _maintenance
	 *
	 * @param $trestart
	 */
	function _maintenance($trestart = false) {
		// output
		$this->_outputMessage("Running Maintenance...\n");
		// fluxd
		$this->_maintenanceFluxd();
		// transfers
		$this->_maintenanceTransfers($trestart);
		// database
		$this->_maintenanceDatabase();
		// output
		$this->_outputMessage("Maintenance done.\n");
		// state
		$this->state = MAINTENANCEANDREPAIR_STATE_DONE;
	}

	/**
	 * _maintenanceFluxd
	 * delete leftovers of fluxd (only do this if daemon is not running)
	 */
	function _maintenanceFluxd() {
		// output
		$this->_outputMessage("fluxd-maintenance...\n");
		// files
		$fdp = $this->_cfg["path"].'.fluxd/fluxd.pid';
		$fds = $this->_cfg["path"].'.fluxd/fluxd.sock';
		$fdpe = file_exists($fdp);
		$fdse = file_exists($fds);
		// pid or socket exists
		if (($fdpe || $fdse) && (
			("0" == @trim(shell_exec("ps aux 2> /dev/null | ".$this->_cfg['bin_grep']." -v grep | ".$this->_cfg['bin_grep']." -c ".$this->_cfg["docroot"]."bin/fluxd/fluxd.pl"))))) {
			// problems
			$this->_outputMessage("found and removing fluxd-leftovers...\n");
			// pid
			if ($fdpe)
				@unlink($fdp);
			// socket
			if ($fdse)
				@unlink($fds);
			// DEBUG : log the repair
			if ($this->_cfg['debuglevel'] > 0)
				AuditAction($this->_cfg["constants"]["debug"], "fluxd-maintenance : found and removed fluxd-leftovers.");
			// output
			$this->_outputMessage("done.\n");
		} else {
			// no problems
			$this->_outputMessage("no problems found.\n");
		}
		/* done */
		$this->_outputMessage("fluxd-maintenance done.\n");
	}

	/**
	 * _maintenanceTransfers
	 *
	 * @param $trestart
	 * @return boolean
	 */
	function _maintenanceTransfers($trestart = false) {
		global $db, $queueActive;
		// set var
		$this->_restartTransfers = $trestart;
		// output
		$this->_outputMessage("transfers-maintenance...\n");
		// sanity-check for transfers-dir
		if (!is_dir($this->_cfg["transfer_file_path"])) {
			$this->state = MAINTENANCEANDREPAIR_STATE_ERROR;
            $msg = "invalid dir-settings. no dir : ".$this->_cfg["transfer_file_path"];
            array_push($this->messages , $msg);
			$this->_outputError($msg."\n");
			return false;
		}
		// pid-files of transfer-clients
		$pidFiles = array();
		if ($dirHandle = @opendir($this->_cfg["transfer_file_path"])) {
			while (false !== ($file = @readdir($dirHandle))) {
				if ((strlen($file) > 3) && ((substr($file, -4, 4)) == ".pid"))
					array_push($pidFiles, $file);
			}
			@closedir($dirHandle);
		}
		// return if no pid-files found
		if (count($pidFiles) < 1) {
			$this->_outputMessage("no pid-files found.\n");
			$this->_outputMessage("transfers-maintenance done.\n");
			return true;
		}
		// get process-list
		$psString = trim(shell_exec("ps x -o pid='' -o ppid='' -o command='' -ww"));
		// test if client for pid is still up
		$this->_bogusTransfers = array();
		foreach ($pidFiles as $pidFile) {
			$alias = substr($pidFile, 0, -4);
			$transfer = (substr($alias, 0, -5));
			if (stristr($psString, $transfer) === false)
				array_push($this->_bogusTransfers, $transfer);
		}
		// return if no stale pid-files
		$this->_countProblems = count($this->_bogusTransfers);
		if ($this->_countProblems < 1) {
			$this->_outputMessage("no stale pid-files found.\n");
			$this->_outputMessage("transfers-maintenance done.\n");
			return true;
		}

		/* repair the bogus clients */
		$this->_countFixed = 0;
		$this->_outputMessage("repairing died clients...\n");
		foreach ($this->_bogusTransfers as $bogusTransfer) {
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
			$this->_outputMessage("repairing ".$transfer." ...\n");
			// get owner
			$transferowner = getOwner($transfer);
			// rewrite stat-file
			$af = AliasFile::getAliasFileInstance($alias, $transferowner, $this->_cfg, $settingsAry['btclient']);
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
			@unlink($this->_cfg["transfer_file_path"].$pidFile);
			// DEBUG : log the repair of the bogus transfer
			if ($this->_cfg['debuglevel'] > 0)
				AuditAction($this->_cfg["constants"]["debug"], "transfers-maintenance : transfer repaired : ".$transfer);
			// output
			$this->_outputMessage("done.\n");
			// count
			$this->_countFixed++;
		}
		// output
		if ($this->_countProblems > 0)
			$this->_outputMessage("repaired transfers : ".$this->_countFixed."/".$this->_countProblems."\n");

		/* restart transfers */
		if ($this->_restartTransfers) {
			$this->_fixedTransfers = array();
			$this->_outputMessage("restarting died clients...\n");
			// hold current user
			$whoami = ($this->_mode == MAINTENANCEANDREPAIR_MODE_CLI) ? GetSuperAdmin() : $this->_cfg["user"];
			foreach ($this->_bogusTransfers as $bogusTransfer) {
				$transfer = $bogusTransfer.".torrent";
				$alias = $bogusTransfer.".stat";
				$pidFile = $alias.".pid";
				$settingsAry = loadTorrentSettings($transfer);
				if (!((isset($settingsAry)) && (is_array($settingsAry)))) {
					// this is a wget-client, skip it
					continue;
				}
				// output
				$this->_outputMessage("Starting ".$transfer." ...\n");
				// get owner
				$transferowner = getOwner($transfer);
				// set current user to transfer-owner
				$this->_cfg["user"] = $transferowner;
				// file-prio
	            if ($this->_cfg["enable_file_priority"]) {
	                include_once("inc/functions/functions.setpriority.php");
	                // Process setPriority Request.
	                setPriority($transfer);
	            }
				// clientHandler + start
				$clientHandler = ClientHandler::getClientHandlerInstance($this->_cfg, $settingsAry['btclient']);
				$clientHandler->startClient($transfer, 0, $queueActive);
				// DEBUG : log the restart of the died transfer
				if ($this->_cfg['debuglevel'] > 0) {
					$staret = ($clientHandler->state == 3) ? "OK" : "FAILED";
					AuditAction($this->_cfg["constants"]["debug"], "transfers-maintenance : restarted transfer ".$transfer." for ".$whoami." : ".$staret);
				}
				//
				if ($clientHandler->state == 3) {
					// output
					$this->_outputMessage("done.\n");
					// add to ary
					array_push($this->_fixedTransfers, $transfer);
					// count
					$this->_countFixed++;
				} else {
		            array_push($this->messages , $clientHandler->messages);
					$this->_outputError($clientHandler->messages."\n");
				}
			}
			// set user back
			$this->_cfg["user"] = $whoami;
			// output
			$this->_countFixed = count($this->_fixedTransfers);
			if ($this->_countFixed > 0)
				$this->_outputMessage("restarted transfers : ".$this->_countFixed."/".$this->_countProblems."\n");
		}

		/* done */
		$this->_outputMessage("transfers-maintenance done.\n");
		// return
		return true;
	}

	/**
	 * _maintenanceDatabase
	 */
	function _maintenanceDatabase() {
		global $db;
		// output
		$this->_outputMessage("database-maintenance...\n");

		/* tf_torrents */
		$this->_countProblems = 0;
		$this->_countFixed = 0;
		// output
		$this->_outputMessage("table-maintenance : tf_torrents\n");
		// running-flag
		$sql = "SELECT torrent FROM tf_torrents WHERE running = '1'";
		$recordset = $db->Execute($sql);
		showError($db, $sql);
		$rc = $recordset->RecordCount();
		if ($rc > 0) {
			while (list($tname) = $recordset->FetchRow()) {
				if (isTransferRunning($tname) == 0) {
					$this->_countProblems++;
					// t is not running, reset running-flag
					$this->_outputMessage("reset of running-flag for transfer which is not running : ".$tname."\n");
					$sql = "UPDATE tf_torrents SET running = '0' WHERE torrent = '".$tname."'";
					$db->Execute($sql);
					$this->_countFixed++;
					// output
					$this->_outputMessage("done.\n");
				}
			}
		}
		// empty hash
		$sql = "SELECT torrent FROM tf_torrents WHERE hash = ''";
		$recordset = $db->Execute($sql);
		showError($db, $sql);
		$rc = $recordset->RecordCount();
		if ($rc > 0) {
			$this->_countProblems += $rc;
			while (list($tname) = $recordset->FetchRow()) {
				// t has no hash, update
				$this->_outputMessage("updating transfer which has empty hash : ".$tname."\n");
				// get hash
				$thash = getTorrentHash($tname);
				// update
				if (!empty($thash)) {
					$sql = "UPDATE tf_torrents SET hash = '".$thash."' WHERE torrent = '".$tname."'";
					$db->Execute($sql);
					$this->_countFixed++;
					// output
					$this->_outputMessage("done.\n");
				}
			}
		}
		// empty datapath
		$sql = "SELECT torrent FROM tf_torrents WHERE datapath = ''";
		$recordset = $db->Execute($sql);
		showError($db, $sql);
		$rc = $recordset->RecordCount();
		if ($rc > 0) {
			$this->_countProblems += $rc;
			while (list($tname) = $recordset->FetchRow()) {
				// t has no datapath, update
				$this->_outputMessage("updating transfer which has empty datapath : ".$tname."\n");
				// get datapath
				$tDatapath = getTorrentDatapath($tname);
				// update
				if (!empty($tDatapath)) {
					$sql = "UPDATE tf_torrents SET datapath = ".$db->qstr($tDatapath)." WHERE torrent = '".$tname."'";
					$db->Execute($sql);
					$this->_countFixed++;
					// output
					$this->_outputMessage("done.\n");
				}
			}
		}
		// output + log
		if ($this->_countProblems == 0) {
			// output
			$this->_outputMessage("no problems found.\n");
		} else {
			// DEBUG : log
			$msg = "found and fixed problems in tf_torrents : ".$this->_countFixed."/".$this->_countProblems;
			if ($this->_cfg['debuglevel'] > 0)
				AuditAction($this->_cfg["constants"]["debug"], "database-maintenance : table-maintenance : ".$msg);
			// output
			$this->_outputMessage($msg."\n");
		}

		/* tf_torrent_totals */
		$this->_countProblems = 0;
		$this->_countFixed = 0;
		// output
		$this->_outputMessage("table-maintenance : tf_torrent_totals\n");
		$this->_countProblems = $db->GetOne("SELECT COUNT(*) FROM tf_torrent_totals WHERE tid = ''");
		if (($this->_countProblems !== false) && ($this->_countProblems > 0)) {
			// output
			$this->_outputMessage("found ".$this->_countProblems." invalid entries, deleting...\n");
			$sql = "DELETE FROM tf_torrent_totals WHERE tid = ''";
			$result = $db->Execute($sql);
			showError($db, $sql);
			$this->_countFixed = $db->Affected_Rows();
			// output
			$this->_outputMessage("done.\n");
			$rCount = ($this->_countFixed !== false) ? $this->_countFixed : $this->_countProblems;
			// DEBUG : log
			$msg = "found and removed invalid totals-entries from tf_torrent_totals : ".$rCount."/".$this->_countProblems;
			if ($this->_cfg['debuglevel'] > 0)
				AuditAction($this->_cfg["constants"]["debug"], "database-maintenance : table-maintenance : ".$msg);
			// output
			$this->_outputMessage($msg."\n");
		} else {
			// output
			$this->_outputMessage("no problems found.\n");
		}

		// prune db
		$this->_maintenanceDatabasePrune();

		/* done */
		$this->_outputMessage("database-maintenance done.\n");

	}

	/**
	 * prune database
	 */
	function _maintenanceDatabasePrune() {
		global $db;
		// output
		$this->_outputMessage("pruning database...\n");
		$this->_outputMessage("table : tf_log\n");
		// Prune LOG
		$this->_count = 0;
		$testTime = time() - ($this->_cfg['days_to_keep'] * 86400); // 86400 is one day in seconds
		$sql = "delete from tf_log where time < " . $db->qstr($testTime);
		$result = $db->Execute($sql);
		showError($db,$sql);
		$this->_count += $db->Affected_Rows();
		unset($result);
		$testTime = time() - ($this->_cfg['minutes_to_keep'] * 60);
		$sql = "delete from tf_log where time < " . $db->qstr($testTime). " and action=".$db->qstr($this->_cfg["constants"]["hit"]);
		$result = $db->Execute($sql);
		showError($db,$sql);
		$this->_count += $db->Affected_Rows();
		unset($result);
		/* done */
		if ($this->_count > 0)
			$this->_outputMessage("deleted entries from tf_log : ".$this->_count."\n");
		else
			$this->_outputMessage("no entries deleted.\n");
		$this->_outputMessage("prune database done.\n");
	}

	/* repair-methods */

	/**
	 * _repair
	 */
	function _repair() {
		// output
		$this->_outputMessage("Running Repair...\n");
		// fluxd
		$this->_maintenanceFluxd();
		// repair app
		$this->_repairApp();
		// database
		$this->_maintenanceDatabase();
		// log
		AuditAction($this->_cfg["constants"]["debug"], "Repair done.");
		/* done */
		$this->_outputMessage("Repair done.\n");
		// state
		$this->state = MAINTENANCEANDREPAIR_STATE_DONE;
	}

	/**
	 * _repairApp
	 */
	function _repairApp() {
		global $db;
		// output
		$this->_outputMessage("repairing app...\n");
		// sanity-check for transfers-dir
		if (!is_dir($this->_cfg["transfer_file_path"])) {
			$this->state = MAINTENANCEANDREPAIR_STATE_ERROR;
            $msg = "invalid dir-settings. no dir : ".$this->_cfg["transfer_file_path"];
            array_push($this->messages , $msg);
			$this->_outputError($msg."\n");
			return false;
		}
		// delete pid-files of torrent-clients
		if ($dirHandle = opendir($this->_cfg["transfer_file_path"])) {
			while (false !== ($file = readdir($dirHandle))) {
				if ((strlen($file) > 3) && ((substr($file, -4, 4)) == ".pid"))
					@unlink($this->_cfg["transfer_file_path"].$file);
			}
			closedir($dirHandle);
		}
		// rewrite stat-files
		$torrents = getTorrentListFromFS();
		foreach ($torrents as $torrent) {
			$alias = getAliasName($torrent);
			$owner = getOwner($torrent);
			$btclient = getTransferClient($torrent);
			$af = AliasFile::getAliasFileInstance($alias.".stat", $owner, $this->_cfg, $btclient);
			if (isset($af)) {
				// output
				$this->_outputMessage("rewrite stat-file for ".$torrent." ...\n");
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
				$this->_outputMessage("done.\n");
			}
		}
		// set flags in db
		$this->_outputMessage("reset running-flag in database...\n");
		$db->Execute("UPDATE tf_torrents SET running = '0'");
		// output
		$this->_outputMessage("done.\n");
		/* done */
		$this->_outputMessage("repair app done.\n");
	}

	/* output-methods */

    /**
     * output message
     *
     * @param $message
     */
	function _outputMessage($message) {
        // only in cli-mode
		if ($this->_mode == MAINTENANCEANDREPAIR_MODE_CLI)
			printMessage($this->name, $message);
    }

    /**
     * output error
     *
     * @param $message
     */
	function _outputError($message) {
        // only in cli-mode
		if ($this->_mode == MAINTENANCEANDREPAIR_MODE_CLI)
			printError($this->name, $message);
    }

}

?>