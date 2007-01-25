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

// defines
define('_DUMP_DELIM', '*');
preg_match('|.*\s(\d+)\s.*|', '$Revision$', $revisionMatches);
define('_REVISION_FLUXCLI', $revisionMatches[1]);

/**
 * FluxCLI
 */
class FluxCLI
{
	// public fields

	// name
	var $name = "FluxCLI";

    // private fields

	// script
	var $_script = "fluxcli.php";

    // action
    var $_action = "";

    // args
    var $_args = array();
    var $_argc = 0;

    // arg-errors-array
    var $_argErrors = array();

    // messages-array
    var $_messages = array();

	// =========================================================================
	// public static methods
	// =========================================================================

    /**
     * accessor for singleton
     *
     * @return FluxCLI
     */
    function getInstance() {
		global $instanceFluxCLI;
		return (isset($instanceFluxCLI))
			? $instanceFluxCLI
			: false;
    }

    /**
     * getAction
     *
     * @return string
     */
    function getAction() {
		global $instanceFluxCLI;
		return (isset($instanceFluxCLI))
			? $instanceFluxCLI->_action
			: "";
    }

    /**
     * getArgs
     *
     * @return array
     */
    function getArgs() {
		global $instanceFluxCLI;
		return (isset($instanceFluxCLI))
			? $instanceFluxCLI->_args
			: array();
    }

    /**
     * getMessages
     *
     * @return array
     */
    function getMessages() {
		global $instanceFluxCLI;
		return (isset($instanceFluxCLI))
			? $instanceFluxCLI->_messages
			: array();
    }

	/**
	 * process a request
	 *
	 * @param $args
	 * @return mixed
	 */
    function processRequest($args) {
		global $instanceFluxCLI;
    	// create new instance
    	$instanceFluxCLI = new FluxCLI($args);
		// call instance-method
		return (!$instanceFluxCLI)
			? false
			: $instanceFluxCLI->instance_processRequest();
    }

	// =========================================================================
	// ctor
	// =========================================================================

    /**
     * do not use direct, use the public static methods !
     *
	 * @param $args
     * @return FluxCLI
     */
    function FluxCLI($args) {
    	global $cfg;

		// set user-var
		$cfg["user"] = GetSuperAdmin();

		// set admin-var
		$cfg['isAdmin'] = true;

		// set user-agent
		$cfg['user_agent'] = $this->name."/" . _REVISION_FLUXCLI;
		$_SERVER['HTTP_USER_AGENT'] = $this->name."/" . _REVISION_FLUXCLI;

		// parse args and set fields
		$argCount = count($args);
		if ($argCount < 1) {
			// invalid args
			$this->_outputError("invalid args.\n");
			return false;
		}
		$this->_script = basename($args[0]);
		$this->_action = (isset($args[1])) ? $args[1] : "";
		if ($argCount > 2) {
			$prm = array_splice($args, 2);
			$this->_args = array_map('trim', $prm);
			$this->_argc = count($this->_args);
		} else {
			$this->_args = array();
			$this->_argc = 0;
		}
    }

	// =========================================================================
	// public methods
	// =========================================================================

	/**
	 * process a request
	 *
	 * @return mixed
	 */
    function instance_processRequest() {
    	global $cfg;

		// action-switch
		switch ($this->_action) {

			/* netstat */
			case "netstat":
				return $this->_netstat();

			/* transfers */
			case "transfers":
				return $this->_transfers();

			/* start */
			case "start":
				if (empty($this->_args[0])) {
					array_push($this->_argErrors, "missing argument: name of transfer. (extra-arg 1)");
					break;
				} else {
					return $this->_transferStart($this->_args[0]);
				}

			/* stop */
			case "stop":
				if (empty($this->_args[0])) {
					array_push($this->_argErrors, "missing argument: name of transfer. (extra-arg 1)");
					break;
				} else {
					return $this->_transferStop($this->_args[0]);
				}

			/* reset */
			case "reset":
				if (empty($this->_args[0])) {
					array_push($this->_argErrors, "missing argument: name of transfer. (extra-arg 1)");
					break;
				} else {
					return $this->_transferReset($this->_args[0]);
				}

			/* delete */
			case "delete":
				if (empty($this->_args[0])) {
					array_push($this->_argErrors, "missing argument: name of transfer. (extra-arg 1)");
					break;
				} else {
					return $this->_transferDelete($this->_args[0]);
				}

			/* wipe */
			case "wipe":
				if (empty($this->_args[0])) {
					array_push($this->_argErrors, "missing argument: name of transfer. (extra-arg 1)");
					break;
				} else {
					return $this->_transferWipe($this->_args[0]);
				}

			/* start-all */
			case "start-all":
				return $this->_transfersStart();

			/* resume-all */
			case "resume-all":
				return $this->_transfersResume();

			/* stop-all */
			case "stop-all":
				return $this->_transfersStop();

			/* inject */
			case "inject":
				if ($this->_argc < 2) {
					array_push($this->_argErrors, "missing argument(s) for inject.");
					break;
				} else {
					return $this->_inject($this->_args[0], $this->_args[1]);
				}

			/* watch */
			case "watch":
				if ($this->_argc < 2) {
					array_push($this->_argErrors, "missing argument(s) for watch.");
					break;
				} else {
					return $this->_watch($this->_args[0], $this->_args[1]);
				}

			/* rss */
			case "rss":
				if ($this->_argc < 4) {
					array_push($this->_argErrors, "missing argument(s) for rss.");
					break;
				} else {
					return $this->_rss(
						$this->_args[0], $this->_args[1],
						$this->_args[2], $this->_args[3],
						empty($this->_args[4]) ? "" : $this->_args[4]
					);
				}

			/* xfer */
			case "xfer":
				if (empty($this->_args[0])) {
					array_push($this->_argErrors, "missing argument: time-delta of xfer to use : (all/total/month/week/day) (extra-arg 1)");
					break;
				} else {
					return $this->_xfer($this->_args[0]);
				}

			/* repair */
			case "repair":
				return $this->_repair();

	        /* maintenance */
			case "maintenance":
				return $this->_maintenance(((isset($this->_args[0])) && ($this->_args[0] == "true")) ? true : false);
	        	return true;

	        /* dump */
			case "dump":
				if (empty($this->_args[0])) {
					array_push($this->_argErrors, "missing argument: type. (settings/users) (extra-arg 1)");
					break;
				} else {
					return $this->_dump($this->_args[0]);
				}

			/* filelist */
			case "filelist":
				printFileList((empty($this->_args[0])) ? $cfg['docroot'] : $this->_args[0], 1, 1);
				return true;

			/* checksums */
			case "checksums":
				printFileList((empty($this->_args[0])) ? $cfg['docroot'] : $this->_args[0], 2, 1);
				return true;

			/* version */
			case "version":
			case "-version":
			case "--version":
			case "-v":
				return $this->_printVersion();

			/* help */
			case "help":
			case "-help":
			case "--help":
			case "-h":
			default:
				return $this->_printUsage();

		}

		// help
		return $this->_printUsage();
    }

	// =========================================================================
	// private methods
	// =========================================================================

	/**
	 * Print Net Stat
	 *
	 * @return mixed
	 */
	function _netstat() {
		global $cfg;
		echo $cfg['_ID_CONNECTIONS'].":\n";
		echo netstatConnectionsSum()."\n";
		echo $cfg['_ID_PORTS'].":\n";
		echo netstatPortList();
		echo $cfg['_ID_HOSTS'].":\n";
		echo netstatHostList();
		return true;
	}

	/**
	 * Show Transfers
	 *
	 * @return mixed
	 */
	function _transfers() {
		global $cfg;
		// print out transfers
		echo "Transfers:\n";
		$transferHeads = getTransferListHeadArray();
		echo "* Name * ".implode(" * ", $transferHeads)."\n";
		$transferList = getTransferListArray();
		foreach ($transferList as $transferAry)
			echo "- ".implode(" - ", $transferAry)."\n";
		// print out stats
		echo "Server:\n";
	    if (! array_key_exists("total_download", $cfg))
	        $cfg["total_download"] = 0;
	    if (! array_key_exists("total_upload", $cfg))
	        $cfg["total_upload"] = 0;
		echo $cfg['_UPLOADSPEED']."\t".': '.number_format($cfg["total_upload"], 2).' kB/s'."\n";
		echo $cfg['_DOWNLOADSPEED']."\t".': '.number_format($cfg["total_download"], 2).' kB/s'."\n";
		echo $cfg['_TOTALSPEED']."\t".': '.number_format($cfg["total_download"]+$cfg["total_upload"], 2).' kB/s'."\n";
		echo $cfg['_ID_CONNECTIONS']."\t".': '.netstatConnectionsSum()."\n";
		return true;
	}

	/**
	 * Start Transfer
	 *
	 * @param $transfer
	 * @return mixed
	 */
	function _transferStart($transfer) {
		global $cfg;
		// check transfer
		if (!transferExists($transfer)) {
			$this->_outputError("transfer does not exist.\n");
			return false;
		}
		// check running
		if (isTransferRunning($transfer)) {
			$this->_outputError("transfer already running.\n");
			return false;
		}
		// set user
		$cfg["user"] = getOwner($transfer);
		// output
		$this->_outputMessage("Starting ".$transfer." ...\n");
		// force start, dont queue
		$ch = ClientHandler::getInstance(getTransferClient($transfer));
		$ch->start($transfer, false, false);
		if ($ch->state == CLIENTHANDLER_STATE_OK) { /* hooray */
			$this->_outputMessage("done.\n");
			return true;
		} else {
			$this->_messages = array_merge($this->_messages, $ch->messages);
			$this->_outputError("failed:\n".implode("\n", $ch->messages)."\n");
			return false;
		}
	}

	/**
	 * Stop Transfer
	 *
	 * @param $transfer
	 * @return mixed
	 */
	function _transferStop($transfer) {
		global $cfg;
		// check transfer
		if (!transferExists($transfer)) {
			$this->_outputError("transfer does not exist.\n");
			return false;
		}
		// check running
		if (!isTransferRunning($transfer)) {
			$this->_outputError("transfer not running.\n");
			return false;
		}
		// set user
		$cfg["user"] = getOwner($transfer);
		// output
		$this->_outputMessage("Stopping ".$transfer." ...\n");
		// stop
		$ch = ClientHandler::getInstance(getTransferClient($transfer));
        $ch->stop($transfer);
        $this->_outputMessage("done.\n");
		return true;
	}

	/**
	 * Reset Transfer
	 *
	 * @param $transfer
	 * @return mixed
	 */
	function _transferReset($transfer) {
		$this->_outputMessage("Resetting totals of ".$transfer." ...\n");
		$msgs = resetTransferTotals($transfer, false);
		if (count($msgs) == 0) {
			$this->_outputMessage("done.\n");
			return true;
		} else {
			$this->_messages = array_merge($this->_messages, $msgs);
			$this->_outputError("failed:\n".implode("\n", $msgs)."\n");
			return false;
		}
	}

	/**
	 * Delete Transfer
	 *
	 * @param $transfer
	 * @return mixed
	 */
	function _transferDelete($transfer) {
		global $cfg;
		// check transfer
		if (!transferExists($transfer)) {
			$this->_outputError("transfer does not exist.\n");
			return false;
		}
		$this->_outputMessage("Delete ".$transfer." ...\n");
		// set user
		$cfg["user"] = getOwner($transfer);
		// delete
		$ch = ClientHandler::getInstance(getTransferClient($transfer));
		$tRunningFlag = isTransferRunning($transfer);
		if ($tRunningFlag) {
			// stop transfer first
			$this->_outputMessage("transfer is running, stopping first...\n");
			$ch->stop($transfer);
			$tRunningFlag = isTransferRunning($transfer);
        }
        if (!$tRunningFlag) {
        	$this->_outputMessage("Deleting...\n");
        	$ch->delete($transfer);
			$this->_outputMessage("done.\n");
			return true;
        } else {
        	$this->_outputError("transfer still up... cannot delete\n");
        	return false;
        }
	}

	/**
	 * Wipe Transfer
	 *
	 * @param $transfer
	 * @return mixed
	 */
	function _transferWipe($transfer) {
		global $cfg;
		// check transfer
		if (!transferExists($transfer)) {
			$this->_outputError("transfer does not exist.\n");
			return false;
		}
		$this->_outputMessage("Wipe ".$transfer." ...\n");
		// set user
		$cfg["user"] = getOwner($transfer);
		// wipe
		$ch = ClientHandler::getInstance(getTransferClient($transfer));
		$tRunningFlag = isTransferRunning($transfer);
		if ($tRunningFlag) {
			// stop transfer first
			$this->_outputMessage("transfer is running, stopping first...\n");
			$ch->stop($transfer);
			$tRunningFlag = isTransferRunning($transfer);
        }
        if (!$tRunningFlag) {
        	$this->_outputMessage("Deleting...\n");
    		deleteTransferData($transfer);
			$msgs = resetTransferTotals($transfer, true);
			if (count($msgs) > 0)
				$this->_messages = array_merge($this->_messages, $msgs);
        	$ch->delete($transfer);
        	if (count($ch->messages) > 0)
				$this->_messages = array_merge($this->_messages, $ch->messages);
        	if ((count($msgs) + count($ch->messages)) == 0) {
				$this->_outputMessage("done.\n");
				return true;
        	} else {
				$this->_outputError("failed: ".$transfer."\n".implode("\n", $msgs)."\n".implode("\n", $ch->messages));
				return false;
        	}
        } else {
        	$this->_outputError("transfer still up... cannot delete\n");
        	return false;
        }
	}

	/**
	 * Start Transfers
	 *
	 * @return mixed
	 */
	function _transfersStart() {
	    $this->_outputMessage("Starting all transfers ...\n");
		$transferList = getTransferArray();
		foreach ($transferList as $transfer) {
	        if (!isTransferRunning($transfer))
	        	$this->_transferStart($transfer);
		}
	}

	/**
	 * Resume Transfers
	 *
	 * @return mixed
	 */
	function _transfersResume() {
	    $this->_outputMessage("Resuming all transfers ...\n");
		$transferList = getTransferArray();
		$sf = new StatFile("");
		foreach ($transferList as $transfer) {
			$sf->init($transfer);
			if (trim($sf->running) == 0)
				$this->_transferStart($transfer);
		}
	}

	/**
	 * Stop Transfers
	 *
	 * @return mixed
	 */
	function _transfersStop() {
		$this->_outputMessage("Stopping all transfers ...\n");
		$transferList = getTransferArray();
		foreach ($transferList as $transfer) {
			if (isTransferRunning($transfer))
				$this->_transferStop($transfer);
		}
	}

	/**
	 * Inject Transfer
	 *
	 * @param $transferFile
	 * @param $username
	 * @return mixed
	 */
	function _inject($transferFile, $username) {
		global $cfg;
		// check file
		if (!@is_file($transferFile)) {
			$this->_outputError("transfer-file ".$transferFile." is no file.\n");
			return false;
		}
		// check username
		if (!IsUser($username)) {
			$this->_outputError("username ".$username." is no valid user.\n");
			return false;
		}
		$this->_outputMessage("Inject ".$transferFile." for user ".$username." ...\n");
		// set user
	    $cfg["user"] = $username;
	    // set filename
	    $fileName = basename($transferFile);
        $fileName = cleanFileName($fileName, false);
        // only inject valid transfers
        $msgs = array();
        if (($fileName !== false) && (isValidTransfer($fileName))) {
        	$targetFile = $cfg["transfer_file_path"].$fileName;
            if (is_file($targetFile)) {
            	array_push($msgs, "transfer ".$fileName.", already exists.");
            } else {
            	$this->_outputMessage("copy ".$transferFile." to ".$targetFile." ...\n");
                if (@copy($transferFile, $targetFile)) {
                	// chmod
                    @chmod($cfg["transfer_file_path"].$fileName, 0644);
                    // make owner entry
                    AuditAction($cfg["constants"]["file_upload"], $fileName);
                    // inject
                    $this->_outputMessage("injecting ".$fileName." ...\n");
                    injectTransfer($fileName);
                } else {
                	array_push($msgs, "File could not be copied: ".$transferFile);
                }
            }
        } else {
        	array_push($msgs, "The type of file you are injecting is not allowed.");
			array_push($msgs, "valid file-extensions: ");
			array_push($msgs, $cfg["file_types_label"]);
        }
		if (count($msgs) == 0) {
			$this->_outputMessage("done.\n");
			return $fileName;
		} else {
			$this->_messages = array_merge($this->_messages, $msgs);
			$this->_outputError("failed: ".$transfer."\n".implode("\n", $msgs));
			return false;
		}
	}

	/**
	 * Watch Dir
	 *
	 * @param $watchDir
	 * @param $username
	 * @return mixed
	 */
	function _watch($watchDir, $username) {
		global $cfg;
		// check dir
		if (!@is_dir($transferFile)) {
			$this->_outputError("watch-dir ".$watchDir." is no dir.\n");
			return false;
		}
		// check username
		if (!IsUser($username)) {
			$this->_outputError("username ".$username." is no valid user.\n");
			return false;
		}
		// trailing slash
        $watchDir = checkDirPathString($watchDir);
        // process dir
        $this->_outputMessage("Processing watch-dir ".$watchDir." for user ".$username." ...\n");
        if ($dirHandle = @opendir($watchDir)) {
        	// read input-files
        	$input = array();
			while (false !== ($file = @readdir($dirHandle)))
        		array_push($input, $file);
            @closedir($dirHandle);
            if (empty($input)) {
            	$this->_outputMessage("done. no files found.\n");
            	return true;
            }
            // process files
            $ctr = array('files' => count($input), 'injects' => 0, 'starts' => 0);
            foreach ($input as $file) {
            	// source-file
            	$sourceFile = $watchDir.$file;
            	// inject
            	$transfer = $this->_inject();
            	// continue if inject failed
            	if ($transfer === false) {
            		$this->_outputError("skip file ".$sourceFile." as inject failed.\n");
					continue;
            	}
            	// ctr
            	$ctr['injects']++;
            	// delete source-file
            	$this->_outputMessage("deleting source-file ".$sourceFile." ...\n");
            	@unlink($sourceFile);
            	// start
				if ($this->_transferStart($transfer))
					$ctr['starts']++;
            }
            if ($ctr['files'] == $ctr['starts']) {
            	$this->_outputMessage("done. files: ".$ctr['files']."; injects: ".$ctr['injects']."; starts: ".$ctr['starts']."\n");
            	return true;
            } else {
            	$this->_outputError("done with errors. files: ".$ctr['files']."; injects: ".$ctr['injects']."; starts: ".$ctr['starts']."\n");
            	return false;
            }
        } else {
        	$this->_outputError("failed to open watch-dir ".$watchDir.".\n");
			return false;
        }
	}

	/**
	 * Xfer Shutdown
	 *
	 * @param $delta
	 * @return mixed
	 */
	function _xfer($delta) {
		global $cfg, $db, $xfer_total;
		// check xfer
		if ($cfg['enable_xfer'] != 1) {
			$this->_outputError("xfer must be enabled.\n");
			return false;
		}
		// check arg
		if (($delta != "all") && ($delta != "total") && ($delta != "month") && ($delta != "week") && ($delta != "day")) {
			$this->_outputMessage('invalid delta : "'.$delta.'"'."\n");
			return false;
		}
		$this->_outputMessage('checking xfer-limit(s) for "'.$delta.'" ...'."\n");
    	// xfer-init
		$cfg['xfer_realtime'] = 1;
		$cfg['xfer_newday'] = 0;
		$cfg['xfer_newday'] = !$db->GetOne('SELECT 1 FROM tf_xfer WHERE date = '.$db->DBDate(time()));
    	// getTransferListArray to update xfer-stats
		$transferList = @getTransferListArray();
		// check if break needed
		// total
		if (($delta == "total") || ($delta == "all")) {
			// only do if a limit is set
			if ($cfg["xfer_total"] > 0) {
				if ($xfer_total['total']['total'] >= $cfg["xfer_total"]) {
					// limit met, stop all Transfers now.
					$this->_outputMessage('Limit met for "total" : '.formatFreeSpace($xfer_total['total']['total'] / (1048576))." / ".formatFreeSpace($cfg["xfer_total"] / (1048576))."\n");
					return $this->_transfersStop();
				} else {
					$this->_outputMessage('Limit not met for "total" : '.formatFreeSpace($xfer_total['total']['total'] / (1048576))." / ".formatFreeSpace($cfg["xfer_total"] / (1048576))."\n");
				}
			} else {
				$this->_outputMessage('no limit set for "total"'."\n");
			}
		}
		// month
		if (($delta == "month") || ($delta == "all")) {
			// only do if a limit is set
			if ($cfg["xfer_month"] > 0) {
				if ($xfer_total['month']['total'] >= $cfg["xfer_month"]) {
					// limit met, stop all Transfers now.
					$this->_outputMessage('Limit met for "month" : '.formatFreeSpace($xfer_total['month']['total'] / (1048576))." / ".formatFreeSpace($cfg["xfer_month"] / (1048576))."\n");
					return $this->_transfersStop();
				} else {
					$this->_outputMessage('Limit not met for "month" : '.formatFreeSpace($xfer_total['month']['total'] / (1048576))." / ".formatFreeSpace($cfg["xfer_month"] / (1048576))."\n");
				}
			} else {
				$this->_outputMessage('no limit set for "month"'."\n");
			}
		}
		// week
		if (($delta == "week") || ($delta == "all")) {
			// only do if a limit is set
			if ($cfg["xfer_week"] > 0) {
				if ($xfer_total['week']['total'] >= $cfg["xfer_week"]) {
					// limit met, stop all Transfers now.
					$this->_outputMessage('Limit met for "week" : '.formatFreeSpace($xfer_total['week']['total'] / (1048576))." / ".formatFreeSpace($cfg["xfer_week"] / (1048576))."\n");
					return $this->_transfersStop();
				} else {
					$this->_outputMessage('Limit not met for "week" : '.formatFreeSpace($xfer_total['week']['total'] / (1048576))." / ".formatFreeSpace($cfg["xfer_week"] / (1048576))."\n");
				}
			} else {
				$this->_outputMessage('no limit set for "week"'."\n");
			}
		}
		// day
		if (($delta == "day") || ($delta == "all")) {
			// only do if a limit is set
			if ($cfg["xfer_day"] > 0) {
				if ($xfer_total['day']['total'] >= $cfg["xfer_day"]) {
					// limit met, stop all Transfers now.
					$this->_outputMessage('Limit met for "day" : '.formatFreeSpace($xfer_total['day']['total'] / (1048576))." / ".formatFreeSpace($cfg["xfer_day"] / (1048576))."\n");
					return $this->_transfersStop();
				} else {
					$this->_outputMessage('Limit not met for "day" : '.formatFreeSpace($xfer_total['day']['total'] / (1048576))." / ".formatFreeSpace($cfg["xfer_day"] / (1048576))."\n");
				}
			} else {
				$this->_outputMessage('no limit set for "day"'."\n");
			}
		}
		// done
		$this->_outputMessage("done.\n");
		return true;
	}

	/**
	 * rss download
	 *
	 * @param $saveDir
	 * @param $filterFile
	 * @param $historyFile
	 * @param $url
	 * @param $username
	 * @return mixed
	 */
	function _rss($saveDir, $filterFile, $historyFile, $url, $username = "") {
		global $cfg;
		// set user
		if (!empty($username)) {
			// check first
			if (IsUser($username)) {
				$cfg["user"] = $username;
			} else {
				$this->_outputError("username ".$username." is no valid user.\n");
				return false;
			}
		}
		// process Feed
		require_once("inc/classes/Rssd.php");
		return Rssd::processFeed($saveDir, $filterFile, $historyFile, $url);
	}

	/**
	 * Repair
	 *
	 * @return mixed
	 */
	function _repair() {
		require_once("inc/classes/MaintenanceAndRepair.php");
		MaintenanceAndRepair::repair();
		return true;
	}

	/**
	 * Maintenance
	 *
	 * @param $trestart
	 * @return mixed
	 */
	function _maintenance($trestart) {
		require_once("inc/classes/MaintenanceAndRepair.php");
		MaintenanceAndRepair::maintenance($trestart);
		return true;
	}

	/**
	 * Dump Database
	 *
	 * @param $type
	 * @return mixed
	 */
	function _dump($type) {
		global $cfg, $db;
		switch ($type) {
			case "settings":
			    $sql = "SELECT tf_key, tf_value FROM tf_settings";
				break;
			case "users":
				$sql = "SELECT uid, user_id FROM tf_users";
				break;
			default:
				$this->_outputError("invalid type : ".$type."\n");
				return false;
		}
	    $recordset = $db->Execute($sql);
	    if ($db->ErrorNo() != 0) dbError($sql);
	    $content = "";
	    while (list($a, $b) = $recordset->FetchRow())
	    	 $content .= $a._DUMP_DELIM.$b."\n";
	    echo $content;
		return ($content != "");
	}

    /**
     * output message
     *
     * @param $message
     */
	function _outputMessage($message) {
		printMessage($this->name, $message);
    }

    /**
     * output error
     *
     * @param $message
     */
	function _outputError($message) {
		printError($this->name, $message);
    }

    /**
     * prints version
     *
	 * @return mixed
     */
    function _printVersion() {
    	echo $this->name." Revision "._REVISION_FLUXCLI."\n";
    	return (_REVISION_FLUXCLI > 0);
    }

    /**
     * prints usage
     *
	 * @return mixed
     */
    function _printUsage() {
		$this->_printVersion();
		echo "\n"
		. "Usage: ".$this->_script." action [extra-args]\n"
		. "\n"
		. "action: \n"
		. "  transfers   : show transfers.\n"
		. "  netstat     : show netstat.\n"
		. "  start       : start a transfer.\n"
		. "                extra-arg : name of transfer as known inside webapp\n"
		. "  stop        : stop a transfer.\n"
		. "                extra-arg : name of transfer as known inside webapp\n"
		. "  reset       : reset totals of a transfer.\n"
		. "                extra-arg : name of transfer as known inside webapp\n"
		. "  delete      : delete a transfer.\n"
		. "                extra-arg : name of transfer as known inside webapp\n"
		. "  wipe        : reset totals, delete metafile, delete data.\n"
		. "                extra-arg : name of transfer as known inside webapp\n"
	    . "  start-all   : start all transfers.\n"
	    . "  resume-all  : resume all transfers.\n"
		. "  stop-all    : stop all running transfers.\n"
		. "  inject      : injects a transfer-file into the application.\n"
		. "                extra-arg 1 : path to transfer-meta-file\n"
		. "                extra-arg 2 : username of fluxuser\n"
		. "  watch       : watch a dir and inject + start transfers into the app.\n"
		. "                extra-arg 1 : path to users watch-dir\n"
		. "                extra-arg 2 : username of fluxuser\n"
		. "  rss         : download torrents matching filter-rules from a rss-feed.\n"
		. "                extra-arg 1 : save-dir\n"
		. "                extra-arg 2 : filter-file\n"
		. "                extra-arg 3 : history-file\n"
		. "                extra-arg 4 : rss-feed-url\n"
		. "                extra-arg 5 : use cookies from this torrentflux user (optional, default is superadmin)\n"
		. "  xfer        : xfer-Limit-Shutdown. stop all transfers if xfer-limit is met.\n"
		. "                extra-arg 1 : time-delta of xfer to use : (all/total/month/week/day)\n"
		. "  repair      : repair of torrentflux. DONT do this unless you have to.\n"
		. "                Doing this on a running ok flux _will_ screw up things.\n"
		. "  maintenance : call maintenance and repair all died transfers.\n"
		. "                extra-arg 1 : restart died transfers (true/false. optional, default is false)\n"
		. "  dump        : dump database.\n"
		. "                extra-arg 1 : type. (settings/users)\n"
		. "  filelist    : print file-list.\n"
		. "                extra-arg 1 : dir (optional, default is docroot)\n"
		. "  checksums   : print checksum-list.\n"
		. "                extra-arg 1 : dir (optional, default is docroot)\n"
		. "\n"
		. "examples:\n"
		. $this->_script." transfers\n"
		. $this->_script." netstat\n"
		. $this->_script." start foo.torrent\n"
		. $this->_script." stop foo.torrent\n"
		. $this->_script." start-all\n"
		. $this->_script." resume-all\n"
		. $this->_script." stop-all\n"
		. $this->_script." reset foo.torrent\n"
		. $this->_script." delete foo.torrent\n"
		. $this->_script." wipe foo.torrent\n"
		. $this->_script." inject /path/to/foo.torrent fluxuser\n"
	    . $this->_script." watch /path/to/watch-dir/ fluxuser\n"
	    . $this->_script." rss /path/to/rss-torrents/ /path/to/filter.dat /path/to/filter.hist http://www.example.com/rss.xml fluxuser\n"
	    . $this->_script." xfer month\n"
		. $this->_script." repair\n"
		. $this->_script." maintenance true\n"
		. $this->_script." dump settings\n"
		. $this->_script." dump users\n"
		. $this->_script." filelist /var/www\n"
		. $this->_script." checksums /var/www\n"
		. "\n";
		if (count($this->_argErrors) > 0) {
			echo "arg-error(s) :\n"
			. implode("\n", $this->_argErrors)
			. "\n\n";
			return false;
		}
		return true;
    }


}

?>