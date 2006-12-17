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
define('CLIENTHANDLER_STATE_NULL', 0);                                   // null
define('CLIENTHANDLER_STATE_READY', 1);                                 // ready
define('CLIENTHANDLER_STATE_OK', 2);                                  // started
define('CLIENTHANDLER_STATE_ERROR', -1);                                // error

/**
 * base class ClientHandler
 */
class ClientHandler
{
	// public fields

	// client-specific fields
    var $handlerName = "";
    var $binSystem = ""; // the system-binary of this client.
    var $binSocket = ""; // the binary this client uses for socket-connections.
    var $binClient = ""; // the binary of this client. (eg. python-script))

    // generic vars for a transfer-start
    var $rate = "";
    var $drate = "";
    var $superseeder = "";
    var $runtime = "";
    var $maxuploads = "";
    var $minport = "";
    var $maxport = "";
    var $port = "";
    var $maxcons = "";
    var $rerequest = "";
    var $sharekill = "";
    var $sharekill_param = "";
    var $savepath = "";
    var $skip_hash_check = "";

    // queue
    var $queue = false;

    // transfer
    var $transfer = "";
    var $transferFilePath = "";

    // alias
    var $alias = "";
    var $aliasFile = "";
    var $aliasFilePath = "";

    // pid
    var $pid = "";
    var $pidFilePath = "";

    // logfile
    var $logFilePath = "";

    // priofile
    var $prioFilePath = "";

    // owner
    var $owner = "";

    // command
    var $command = "";

    // umask
    var $umask = "";

    // nice
    var $nice = "";

    // call-result
    var $callResult;

    // messages-array
    var $messages = array();

    // handler-state
    var $state = CLIENTHANDLER_STATE_NULL;

	// =========================================================================
	// public static methods
	// =========================================================================

    /**
     * get ClientHandler-instance
     *
     * @return ClientHandler
     */
    function getInstance($clientType = '') {
    	// create and return object-instance
        switch ($clientType) {
            case "tornado":
            	require_once('inc/classes/ClientHandler.tornado.php');
                return new ClientHandlerTornado();
            case "transmission":
            	require_once('inc/classes/ClientHandler.transmission.php');
                return new ClientHandlerTransmission();
            case "mainline":
            	require_once('inc/classes/ClientHandler.mainline.php');
                return new ClientHandlerMainline();
            case "wget":
            	require_once('inc/classes/ClientHandler.wget.php');
                return new ClientHandlerWget();
            default:
            	global $cfg;
            	return ClientHandler::getInstance($cfg["btclient"]);
        }
    }

	// =========================================================================
	// ctor
	// =========================================================================

    /**
     * ctor
     */
    function ClientHandler() {
        die('base class -- dont do this');
    }

	// =========================================================================
	// public methods
	// =========================================================================

    /**
     * starts a client
     * @param $transfer name of the transfer
     * @param $interactive (boolean) : is this a interactive startup with dialog ?
     * @param $enqueue (boolean) : enqueue ?
     */
    function start($transfer, $interactive = false, $enqueue = false) { return; }

    /**
     * stops a client
     *
     * @param $transfer name of the transfer
     * @param $kill kill-param (optional)
     * @param $transferPid transfer Pid (optional)
     */
    function stop($transfer, $kill = false, $transferPid = 0) { return; }

	/**
	 * deletes a transfer
	 *
	 * @param $transfer name of the transfer
	 * @return boolean of success
	 */
	function delete($transfer) { return; }

    /**
     * gets current transfer-vals of a transfer
     *
     * @param $transfer
     * @return array with downtotal and uptotal
     */
    function getTransferCurrent($transfer)  { return; }

    /**
     * gets current transfer-vals of a transfer. optimized version
     *
     * @param $transfer
     * @param $tid of the transfer
     * @param $afu alias-file-uptotal of the transfer
     * @param $afd alias-file-downtotal of the transfer
     * @return array with downtotal and uptotal
     */
    function getTransferCurrentOP($transfer, $tid, $afu, $afd)  { return; }

    /**
     * gets total transfer-vals of a transfer
     *
     * @param $transfer
     * @return array with downtotal and uptotal
     */
    function getTransferTotal($transfer) { return; }

    /**
     * gets total transfer-vals of a transfer. optimized version
     *
     * @param $transfer
     * @param $tid of the transfer
     * @param $afu alias-file-uptotal of the transfer
     * @param $afd alias-file-downtotal of the transfer
     * @return array with downtotal and uptotal
     */
    function getTransferTotalOP($transfer, $tid, $afu, $afd) { return; }

    /**
     * prepares start of a client.
     * prepares vars and other generic stuff
     *
     * @param $interactive (boolean) : is this a interactive startup with dialog ?
     * @param $enqueue (boolean) : enqueue ?
     */
    function prepareStart($interactive, $enqueue = false) {
    	global $cfg;
        // umask
        $this->umask = ($cfg["enable_umask"] != 0)
        	? " umask 0000;"
        	: "";
        // nice
        $this->nice = ($cfg["nice_adjust"] != 0)
        	? "nice -n ".$cfg["nice_adjust"]." "
        	: "";
        // set start-vars
        // request-vars / defaults / database
        if ($interactive) { // interactive, get vars from request vars
        	// rate
        	$reqvar = getRequestVar('rate');
        	$this->rate = ($reqvar != "")
        		? $reqvar
        		: $cfg["max_upload_rate"];
			// drate
        	$reqvar = getRequestVar('drate');
        	$this->drate = ($reqvar != "")
        		? $reqvar
        		: $cfg["max_download_rate"];
			// superseeder
        	$reqvar = getRequestVar('superseeder');
        	$this->superseeder = (empty($reqvar))
        		? 0
        		: $reqvar;
			// maxuploads
        	$reqvar = getRequestVar('maxuploads');
        	$this->maxuploads = ($reqvar != "")
        		? $reqvar
        		: $cfg["max_uploads"];
			// minport
        	$reqvar = getRequestVar('minport');
        	$this->minport = (empty($reqvar))
        		? $cfg["minport"]
        		: $reqvar;
            // maxport
        	$reqvar = getRequestVar('maxport');
        	$this->maxport = (empty($reqvar))
        		? $cfg["maxport"]
        		: $reqvar;
			// maxcons
        	$reqvar = getRequestVar('maxcons');
        	$this->maxcons = ($reqvar != "")
        		? $reqvar
        		: $cfg["maxcons"];
			// rerequest
        	$reqvar = getRequestVar('rerequest');
        	$this->rerequest = ($reqvar != "")
        		? $reqvar
        		: $cfg["rerequest_interval"];
        	// runtime
        	$reqvar = getRequestVar('runtime');
        	$this->runtime = (empty($reqvar))
        		? $cfg["torrent_dies_when_done"]
        		: $reqvar;
			// sharekill
        	$reqvar = getRequestVar('sharekill');
        	$this->sharekill = ($reqvar != "")
        		? $reqvar
        		: $cfg["sharekill"];
            if ($this->runtime == "True" )
                $this->sharekill = "-1";
            // savepath
            $this->savepath = getRequestVar('savepath') ;
            // skip_hash_check
            $this->skip_hash_check = getRequestVar('skiphashcheck');
        } else { // non-interactive, load settings from db and set vars
            $this->rerequest = $cfg["rerequest_interval"];
            $this->skip_hash_check = $cfg["skiphashcheck"];
            $this->superseeder = 0;
            // load settings
            $settingsAry = loadTransferSettings($transfer);
            $this->rate = $settingsAry["max_upload_rate"];
            $this->drate = $settingsAry["max_download_rate"];
            $this->runtime = $settingsAry["torrent_dies_when_done"];
            $this->maxuploads = $settingsAry["max_uploads"];
            $this->minport = $settingsAry["minport"];
            $this->maxport = $settingsAry["maxport"];
            $this->maxcons = $settingsAry["maxcons"];
            $this->sharekill = $settingsAry["sharekill"];
            $this->savepath = $settingsAry["savepath"];
            // fallback-values if fresh-transfer is started non-interactive or
            // something else strange happened
            if ($this->rate == '') $this->rate = $cfg["max_upload_rate"];
            if ($this->drate == '') $this->drate = $cfg["max_download_rate"];
            if ($this->runtime == '') $this->runtime = $cfg["torrent_dies_when_done"];
            if ($this->maxuploads == '') $this->maxuploads = $cfg["max_uploads"];
            if ($this->minport == '') $this->minport = $cfg["minport"];
            if ($this->maxport == '') $this->maxport = $cfg["maxport"];
            if ($this->maxcons == '') $this->maxcons = $cfg["maxcons"];
            if ($this->sharekill == '') $this->sharekill = $cfg["sharekill"];
        }
		// queue
        if ($enqueue) {
            if ($cfg['isAdmin'])
            	$this->queue = ($enqueue) ? true : false;
            else
                $this->queue = true;
        } else {
            $this->queue = false;
        }
		// savepath
        if (empty($this->savepath))
        	$this->savepath = ($cfg["enable_home_dirs"] != 0)
        		? $cfg['path'].$this->owner."/"
        		: $cfg['path'].$cfg["path_incoming"]."/";
        else
			$this->savepath = checkDirPathString($this->savepath);
        // check target-directory, create if not present
		if (!(checkDirectory($this->savepath, 0777))) {
			$this->state = CLIENTHANDLER_STATE_ERROR;
			$msg = "Error checking savepath ".$this->savepath;
			AuditAction($cfg["constants"]["error"], $msg);
            array_push($this->messages, $msg);
            return false;
		}
        // create AliasFile object and write out the stat file
        $this->af = new AliasFile($this->aliasFile, $this->owner);
        $transferTotals = $this->getTransferCurrent($this->transfer);
        //XFER: before a transfer start/restart save upload/download xfer to SQL
        if ($cfg['enable_xfer'] == 1)
        	saveXfer($this->owner,($transferTotals["downtotal"]),($transferTotals["uptotal"]));
        // update totals for this transfer
        $this->execUpdateTransferTotals();
        // set param for sharekill
        $this->sharekill = intval($this->sharekill);
        if ($this->sharekill == 0) { // nice, we seed forever
            $this->sharekill_param = 0;
        } elseif ($this->sharekill > 0) { // recalc sharekill
            // sanity-check. catch "data-size = 0".
            $transferSize = intval($this->af->size);
            if ($transferSize > 0) {
				$totalAry = $this->getTransferTotal($this->transfer);
            	$upTotal = $totalAry["uptotal"] + 0;
            	$downTotal = $totalAry["downtotal"] + 0;
				$upWanted = ($this->sharekill / 100) * $transferSize;
				$sharePercentage = ($upTotal / $transferSize) * 100;
	            if (($upTotal >= $upWanted) && ($downTotal >= $transferSize)) {
	            	// we already have seeded at least wanted percentage.
	            	// skip start of client
	                // set state
        			$this->state = CLIENTHANDLER_STATE_NULL;
        			// message
		            $msg = "skipping start of transfer ".$this->transfer." due to share-ratio (has: ".@number_format($sharePercentage, 2)." ; set:".$this->sharekill.")";
		            array_push($this->messages , $msg);
					AuditAction($cfg["constants"]["debug"], $msg);
					// TODO : message
					// return
					return;
	            } else {
	            	// not done seeding wanted percentage
	                $this->sharekill_param = intval(ceil($this->sharekill - $sharePercentage));
	                // sanity-check.
	                if ($this->sharekill_param < 1)
	                    $this->sharekill_param = 1;
	            }
            } else {
    			// message
	            $msg = "data-size is 0 when recalcing share-kill for ".$this->transfer.". setting sharekill absolute to ".$this->sharekill;
	            array_push($this->messages , $msg);
				AuditAction($cfg["constants"]["error"], $msg);
				// TODO : message
				// set 1:1 to provided value
				$this->sharekill_param = $this->sharekill;
            }
		} else {
        	$this->sharekill_param = $this->sharekill;
        }
        // write stat-file
        if ($this->queue) {
            $this->af->queue();
        } else {
            if ($this->_setClientPort() === false)
                return;
            $this->af->start();
        }
        // set state
        $this->state = CLIENTHANDLER_STATE_READY;
    }

    /**
     * start a client.
     *
     * @param $wait
     * @param $save
     */
    function execStart($wait = true, $save = true) {
    	global $cfg;
        if ($this->state != CLIENTHANDLER_STATE_READY) {
            $this->state = CLIENTHANDLER_STATE_ERROR;
            array_push($this->messages , "Error. ClientHandler in wrong state on execStart-request.");
            return;
        }
        // write the session to close so older version of PHP will not hang
        @session_write_close();
        // queue or start ?
        if ($this->queue) { // queue
			if (FluxdQmgr::isRunning()) {
				FluxdQmgr::enqueueTransfer($this->transfer, $cfg['user']);
				AuditAction($cfg["constants"]["queued_transfer"], $this->transfer);
			} else {
	            $msg = "queue-request (".$this->transfer."/".$cfg['user'].") but Qmgr not active";
	            array_push($this->messages , $msg);
				AuditAction($cfg["constants"]["error"], $msg);
			}
			// set flag
            $transferRunningFlag = 0;
        } else { // start
        	// log the command
        	$this->logMessage("executing command : \n".$this->command."\n", true);
            // startup
            $this->callResult = exec($this->command);
            AuditAction($cfg["constants"]["start_torrent"], $this->transfer);
            // wait until transfer is up
            if ($wait)
            	waitForTransfer($this->transfer, 1, 15);
            // set flag
            $transferRunningFlag = 1;
        }
        if (empty($this->messages)) {
            // Save transfer settings
            if ($save)
            	saveTransferSettings($this->transfer, $transferRunningFlag, $this->rate, $this->drate, $this->maxuploads, $this->runtime, $this->sharekill, $this->minport, $this->maxport, $this->maxcons, $this->savepath, $this->handlerName);
            $this->state = CLIENTHANDLER_STATE_OK;
        } else {
            $this->state = CLIENTHANDLER_STATE_ERROR;
            // TODO : message
        }
    }

    /**
     * stop a client
     *
     * @param $kill kill-param (optional)
     * @param $transferPid transfer Pid (optional)
     */
    function execStop($kill = false, $transferPid = 0) {
    	global $cfg;
        // log
        AuditAction($cfg["constants"]["stop_transfer"], $this->transfer);
        // We are going to write a '0' on the front of the stat file so that
        // the client will no to stop -- this will report stats when it dies
        // read the alias file + create AliasFile object
        $this->af = new AliasFile($this->aliasFile, $this->owner);
        if ($this->af->percent_done < 100) {
            // The transfer is being stopped but is not completed dowloading
            $this->af->percent_done = ($this->af->percent_done + 100)*-1;
            $this->af->running = "0";
            $this->af->time_left = "Torrent Stopped";
        } else {
            // transfer was seeding and is now being stopped
            $this->af->percent_done = 100;
            $this->af->running = "0";
            $this->af->time_left = "Download Succeeded!";
        }
        // Write out the new Stat File
        $this->af->write();
        // wait until transfer is down
        waitForTransfer($this->transfer, 0, 15);
        // see if the transfer process is hung.
        $running = $this->runningProcesses();
        $isHung = false;
        foreach ($running as $rng) {
            $rt = RunningTransfer::getInstance($rng['pinfo'], $this->handlerName);
            if ($rt->statFile == $this->aliasFile) {
            	$isHung = true;
                AuditAction($cfg["constants"]["error"], "Possible Hung Process for ".$rt->statFile." (".$rt->processId.")");
            	//$this->callResult = exec("kill ".escapeshellarg($rt->processId));
            }
        }
        // flag the transfer as stopped (in db)
        // blame me for this dirty shit, i am lazy. of course this should be
        // hooked into the place where client really dies.
        stopTransferSettings($this->transfer);
        // kill-request
        if ($kill && $isHung) {
        	AuditAction($cfg["constants"]["kill_transfer"], $this->transfer);
            // set pid
            if (!empty($transferPid)) {
            	// test for valid pid-var
            	if (is_numeric($transferPid)) {
                	$this->pid = $transferPid;
            	} else {
            		$this->state = CLIENTHANDLER_STATE_ERROR;
		    		AuditAction($cfg["constants"]["error"], "INVALID PID: ".$transferPid);
		    		array_push($this->messages, "INVALID PID: ".$transferPid);
		    		return false;
            	}
            } else {
            	$data = "";
				if ($fileHandle = @fopen($this->pidFilePath,'r')) {
					while (!@feof($fileHandle))
						$data .= @fgets($fileHandle, 64);
					@fclose ($fileHandle);
				}
                $this->pid = rtrim($data);
            }
            // kill it
            $this->callResult = exec("kill ".escapeshellarg($this->pid));
            // try to remove the pid file
            @unlink($this->pidFilePath);
        }
    }

	/**
	 * updates totals of a transfer
	 */
	function execUpdateTransferTotals() {
		global $db;
		$tid = getTorrentHash($this->transfer);
		$transferTotals = $this->getTransferTotal($this->transfer);
		$sql = ($db->GetOne("SELECT 1 FROM tf_torrent_totals WHERE tid = '".$tid."'"))
			? "UPDATE tf_torrent_totals SET uptotal = '".$transferTotals["uptotal"]."', downtotal = '".$transferTotals["downtotal"]."' WHERE tid = '".$tid."'"
			: "INSERT INTO tf_torrent_totals ( tid , uptotal ,downtotal ) VALUES ('".$tid."', '".$transferTotals["uptotal"]."', '".$transferTotals["downtotal"]."')";
		$db->Execute($sql);
	}

	/**
	 * deletes a transfer
	 *
	 * @param $updateTotals
	 * @param $deleteSettings
	 * @return boolean
	 */
	function execDelete($updateTotals = true, $deleteSettings = true) {
		global $cfg;
        // delete
		if (($cfg["user"] == $this->owner) || $cfg['isAdmin']) {
			// update totals for this torrent
			if ($updateTotals)
				$this->execUpdateTransferTotals();
			// remove torrent-settings from db
			if ($deleteSettings)
				deleteTransferSettings($this->transfer);
			// client-cache
			$this->execDeleteCache();
			if ($cfg['enable_xfer'] != 0) {
				// XFER: before torrent deletion save upload/download xfer data to SQL
				$transferTotals = $this->getTransferCurrent($this->transfer);
				saveXfer($this->owner, $transferTotals["downtotal"], $transferTotals["uptotal"]);
			}
			// remove meta-file
			if (@file_exists($this->transferFilePath))
				@unlink($this->transferFilePath);
			// remove alias-file
			if (@file_exists($this->aliasFilePath))
				@unlink($this->aliasFilePath);
			// if exist remove pid file
			if (@file_exists($this->pidFilePath))
				@unlink($this->pidFilePath);
			// if exist remove log-file
			if (@file_exists($this->logFilePath))
				@unlink($this->logFilePath);
			// if exist remove prio-file
			if (@file_exists($this->prioFilePath))
				@unlink($this->prioFilePath);
			AuditAction($cfg["constants"]["delete_transfer"], $this->transfer);
			return true;
		} else {
			AuditAction($cfg["constants"]["error"], "ILLEGAL DELETE: ".$this->transfer);
			return false;
		}
	}

    /**
     * deletes cache of a transfer
     */
    function execDeleteCache() { return; }

	/**
	 * gets ary of running clients (via call to ps)
	 *
	 * @return array
	 */
	function runningProcesses() {
		global $cfg;
		$retAry = array();
	    $screenStatus = shell_exec("ps x -o pid='' -o ppid='' -o command='' -ww | ".$cfg['bin_grep']." ".$this->binClient." | ".$cfg['bin_grep']." ".$cfg["transfer_file_path"]." | ".$cfg['bin_grep']." -v grep");
	    $arScreen = array();
	    $tok = strtok($screenStatus, "\n");
	    while ($tok) {
	        array_push($arScreen, $tok);
	        $tok = strtok("\n");
	    }
	    $arySize = sizeof($arScreen);
		for ($i = 0; $i < $arySize; $i++) {
			if(strpos($arScreen[$i], $this->binClient) !== false) {
				$pinfo = new ProcessInfo($arScreen[$i]);
				if (intval($pinfo->ppid) == 1) {
					if (!strpos($pinfo->cmdline, "rep ". $this->binSystem) > 0) {
						if (!strpos($pinfo->cmdline, "ps x") > 0) {
							array_push($retAry, array(
								'client' => $this->handlerName,
								'pinfo' => $pinfo->pid." ".$pinfo->cmdline
								)
							);
						}
					}
				}
	        }
	    }
		return $retAry;
	}

	/**
	 * get info of running clients (via call to ps)
	 *
	 * @return string
	 */
	function runningProcessInfo() {
		global $cfg;
	    // ps-string
	    $screenStatus = shell_exec("ps x -o pid='' -o ppid='' -o command='' -ww | ".$cfg['bin_grep']." ". $this->binClient ." | ".$cfg['bin_grep']." ".$cfg["transfer_file_path"]." | ".$cfg['bin_grep']." -v grep");
	    $arScreen = array();
	    $tok = strtok($screenStatus, "\n");
	    while ($tok) {
	        array_push($arScreen, $tok);
	        $tok = strtok("\n");
	    }
	    $cProcess = array();
	    $cpProcess = array();
	    $pProcess = array();
	    $ProcessCmd = array();
	    for($i = 0; $i < sizeof($arScreen); $i++) {
	        if(strpos($arScreen[$i], $this->binClient) !== false) {
	            $pinfo = new ProcessInfo($arScreen[$i]);
	            if (intval($pinfo->ppid) == 1) {
	                if (!strpos($pinfo->cmdline, "rep ". $this->binSystem) > 0) {
	                    if (!strpos($pinfo->cmdline, "ps x") > 0) {
	                        array_push($pProcess,$pinfo->pid);
	                        $rt = RunningTransfer::getInstance($pinfo->pid." ".$pinfo->cmdline, $this->handlerName);
	                        array_push($ProcessCmd, $rt->transferowner."\t".str_replace(array(".stat"), "", $rt->statFile));
	                    }
	                }
	            } else {
	                if (!strpos($pinfo->cmdline, "rep ". $this->binSystem) > 0) {
	                    if (!strpos($pinfo->cmdline, "ps x") > 0) {
	                        array_push($cProcess,$pinfo->pid);
	                        array_push($cpProcess,$pinfo->ppid);
	                    }
	                }
	            }
	        }
	    }
	    $retVal  = " --- Running Processes ---\n";
	    $retVal .= " Parents  : " . count($pProcess) . "\n";
	    $retVal .= " Children : " . count($cProcess) . "\n";
	    $retVal .= "\n";
	    $retVal .= " PID \tOwner\tTransfer File\n";
	    foreach($pProcess as $key => $value) {
	        $retVal .= " " . $value . "\t" . $ProcessCmd[$key] . "\n";
	        foreach($cpProcess as $cKey => $cValue)
	            if (intval($value) == intval($cValue))
	                $retVal .= "\t" . $cProcess[$cKey] . "\n";
	    }
	    $retVal .= "\n";
	    return $retVal;
	}

    /**
     * writes a message to the per-transfer-logfile
     *
     * @param $message
     * @param $withTS
     */
    function logMessage($message, $withTS = true) {
    	// return if log-file-field not set
    	if ($this->logFilePath == "") return false;
    	// log
		if ($handle = @fopen($this->logFilePath, "a+")) {
			$content = ($withTS)
				? @date("[Y/m/d - H:i:s]")." ".$message
				: $message;
	        $resultSuccess = (@fwrite($handle, $content) !== false);
			@fclose($handle);
			return $resultSuccess;
		}
		return false;
    }

    /**
     * sets all fields depending on "transfer"-value
     *
     * @param $transfer
     */
    function setVarsFromTransfer($transfer) {
    	global $cfg;
        $this->transfer = $transfer;
        $this->alias = getAliasName($this->transfer);
        $this->aliasFile = $this->alias.".stat";
        $this->aliasFilePath = $cfg["transfer_file_path"].$this->aliasFile;
		$this->pidFilePath = $cfg["transfer_file_path"].$this->alias.".stat.pid";
        $this->logFilePath = $cfg["transfer_file_path"].$this->alias.".log";
        $this->prioFilePath = $cfg["transfer_file_path"].$this->alias.".prio";
        $this->transferFilePath = $cfg["transfer_file_path"].$this->transfer;
        $this->owner = getOwner($this->transfer);
    }

    // =========================================================================
	// private methods
	// =========================================================================

    /**
     * gets available port and sets port field
     *
     * @return boolean
     */
    function _setClientPort() {
        $portString = netstatPortList();
        $portAry = explode("\n", $portString);
        $this->port = (int) $this->minport;
        while (1) {
            if (in_array($this->port, $portAry))
                $this->port += 1;
            else
                return true;
            if ($this->port > $this->maxport) {
            	// state
                $this->state = CLIENTHANDLER_STATE_ERROR;
    			// message
	            $msg = "All ports in use.";
	            array_push($this->messages , $msg);
				AuditAction($cfg["constants"]["error"], $msg);
				// return
                return false;
            }
        }
        return false;
    }

} // end class

?>