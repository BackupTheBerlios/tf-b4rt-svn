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
	var $type = "";
    var $client = "";
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
    var $skip_hash_check = "";

    // queue
    var $queue = false;

    // transfer
    var $transfer = "";
    var $transferFilePath = "";

    // running
    var $running = 0;

    // hash
    var $hash = "";

    // datapath
    var $datapath = "";

    // savepath
    var $savepath = "";

    // pid
    var $pid = "";

    // owner
    var $owner = "";

    // command (startup)
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
    function getInstance($client = '') {
    	// create and return object-instance
        switch ($client) {
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
			case "nzbperl":
				require_once('inc/classes/ClientHandler.nzbperl.php');
				return new ClientHandlerNzbperl();
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
	// public methods (abstract)
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
     * deletes cache of a transfer
     */
    function execDeleteCache() { return; }

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
     * @param $sfu stat-file-uptotal of the transfer
     * @param $sfd stat-file-downtotal of the transfer
     * @return array with downtotal and uptotal
     */
    function getTransferCurrentOP($transfer, $tid, $sfu, $sfd)  { return; }

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
     * @param $sfu stat-file-uptotal of the transfer
     * @param $sfd stat-file-downtotal of the transfer
     * @return array with downtotal and uptotal
     */
    function getTransferTotalOP($transfer, $tid, $sfu, $sfd) { return; }

    /**
     * set upload rate of a transfer
     *
     * @param $transfer
     * @param $uprate
     * @param $autosend
     */
    function setRateUpload($transfer, $uprate, $autosend = false) { return; }

    /**
     * set download rate of a transfer
     *
     * @param $transfer
     * @param $downrate
     * @param $autosend
     */
    function setRateDownload($transfer, $downrate, $autosend = false) { return; }

    /**
     * sets fields from default-vals
     */
    function settingsDefault() { return; }

	// =========================================================================
	// public methods
	// =========================================================================

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
        	$this->superseeder = ($reqvar != "")
        		? $reqvar
        		: $cfg["superseeder"];
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
        } else { // non-interactive, load settings from db
            $this->rerequest = $cfg["rerequest_interval"];
            $this->skip_hash_check = $cfg["skiphashcheck"];
            // load settings
            $loaded = $this->settingsLoad();
            // default-settings if settings could not be loaded (fresh transfer)
            if ($loaded !== true)
        		$this->settingsDefault();
        }
		// queue
        if ($enqueue) {
        	$this->queue = ($cfg['isAdmin'])
        		? $enqueue
        		: true;
        } else {
            $this->queue = false;
        }
		// savepath-check
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
			array_push($this->messages, $msg);
			AuditAction($cfg["constants"]["error"], $msg);
            $this->logMessage($msg."\n", true);
            return false;
		}
        // set param for sharekill
        $this->sharekill = intval($this->sharekill);
        // torrent-only-section
        if ($this->type == "torrent") {
        	// recalc sharekill ?
	        if ($cfg['enable_sharekill'] == 1) { // sharekill enabled
	        	$this->logMessage("recalc sharekill for ".$this->transfer."\n", true);
		        if ($this->sharekill == 0) { // nice, we seed forever
		            $this->sharekill_param = 0;
		            $this->logMessage("seed forever\n", true);
		        } elseif ($this->sharekill > 0) { // recalc sharekill
		            // sanity-check. catch "data-size = 0".
		            $transferSize = intval(getDownloadSize($this->transfer));
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
							$this->logMessage($msg."\n", true);
							// return
							return;
			            } else {
			            	// not done seeding wanted percentage
			                $this->sharekill_param = intval(ceil($this->sharekill - $sharePercentage));
			                // sanity-check.
			                if ($this->sharekill_param < 1)
			                    $this->sharekill_param = 1;
			                $this->logMessage("setting sharekill-param to ".$this->sharekill_param."\n", true);
			            }
		            } else {
						// set state
			            $this->state = CLIENTHANDLER_STATE_ERROR;
		    			// message
			            $msg = "data-size = '".$transferSize."' when recalcing share-kill for ".$this->transfer.", skipping start.";
			            array_push($this->messages , $msg);
						AuditAction($cfg["constants"]["error"], $msg);
						$this->logMessage($msg."\n", true);
			            // return
			            return;
		            }
				} else {
		        	$this->sharekill_param = $this->sharekill;
		        	$this->logMessage("setting sharekill-param to ".$this->sharekill_param."\n", true);
		        }
	        } else { // sharekill disabled
	        	$this->sharekill_param = $this->sharekill;
	        	$this->logMessage("setting sharekill-param to ".$this->sharekill_param."\n", true);
	        }
			// set port if start (only if not queue)
			if (!$this->queue) {
				if ($this->_setClientPort() === false)
					return;
			}
        }
        // get current transfer
		$transferTotals = $this->getTransferCurrent($this->transfer);
        //XFER: before a transfer start/restart save upload/download xfer to SQL
        if ($cfg['enable_xfer'] == 1)
        	saveXfer($this->owner,($transferTotals["downtotal"]),($transferTotals["uptotal"]));
        // update totals for this transfer
        $this->execUpdateTransferTotals();
        // set state
        $this->state = CLIENTHANDLER_STATE_READY;
    }

    /**
     * start a client.
     */
    function execStart() {
    	global $cfg;
        if ($this->state != CLIENTHANDLER_STATE_READY) {
            $this->state = CLIENTHANDLER_STATE_ERROR;
            array_push($this->messages , "Error. ClientHandler in wrong state on execStart-request.");
            // write error to stat
			$sf = new StatFile($this->transfer, $this->owner);
			$sf->time_left = 'Error';
			$sf->write();
			// return
            return;
        }
        // flush session-cache (trigger transfers-cache-set on next page-load)
		cacheFlush($cfg['user']);
        // write the session to close so older version of PHP will not hang
        @session_write_close();
        // sf
        $sf = new StatFile($this->transfer, $this->owner);
        // queue or start ?
        if ($this->queue) { // queue
			if (FluxdQmgr::isRunning()) {
		        // write stat-file
		        $sf->queue();
		        // send command
				FluxdQmgr::enqueueTransfer($this->transfer, $cfg['user']);
				// log
				AuditAction($cfg["constants"]["queued_transfer"], $this->transfer);
				$this->logMessage("transfer enqueued : ".$this->transfer."\n", true);
			} else {
	            $msg = "queue-request (".$this->transfer."/".$cfg['user'].") but Qmgr not active";
	            array_push($this->messages , $msg);
				AuditAction($cfg["constants"]["error"], $msg);
				$this->logMessage($msg."\n", true);
			}
			// set flag
            $this->running = 0;
        } else { // start
        	// write stat-file
        	$sf->start();
        	// log the command
        	$this->logMessage("executing command : \n".$this->command."\n", true);
            // startup
            $this->callResult = exec($this->command);
            AuditAction($cfg["constants"]["start_torrent"], $this->transfer);
            // wait until transfer is up
            waitForTransfer($this->transfer, true, 20);
            // set flag
            $this->running = 1;
        }
        if (empty($this->messages)) {
            // Save transfer settings
            $this->settingsSave();
            // set state
            $this->state = CLIENTHANDLER_STATE_OK;
        } else {
        	// error
            $this->state = CLIENTHANDLER_STATE_ERROR;
            $msg = "error starting client. messages :\n";
            $msg .= implode("\n", $this->messages);
            $this->logMessage($msg."\n", true);
            // write error to stat
			$sf = new StatFile($this->transfer, $this->owner);
			$sf->time_left = 'Error';
			$sf->write();
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
        // send quit-command to client
        CommandHandler::add($this->transfer, "q");
		CommandHandler::send($this->transfer);
        // wait until transfer is down
        waitForTransfer($this->transfer, false, 25);
        // see if the transfer process is hung.
        $running = $this->runningProcesses();
        $isHung = false;
        foreach ($running as $rng) {
            $rt = RunningTransfer::getInstance($rng['pinfo'], $this->client);
            if ($rt->transferFile == $this->transfer) {
            	$isHung = true;
                AuditAction($cfg["constants"]["error"], "Possible Hung Process for ".$rt->transferFile." (".$rt->processId.")");
            	//$this->callResult = exec("kill ".escapeshellarg($rt->processId));
            }
        }
        // flag the transfer as stopped (in db)
        // blame me for this dirty shit, i am lazy. of course this should be
        // hooked into the place where client really dies.
        stopTransferSettings($this->transfer);
		// set transfers-cache
		cacheTransfersSet();
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
                $this->pid = getTransferPid($this->transfer);;
            }
            // kill it
            $this->callResult = exec("kill ".escapeshellarg($this->pid));
            // try to remove the pid file
            @unlink($this->transferFilePath.".pid");
        }
    }

	/**
	 * updates totals of a transfer
	 */
	function execUpdateTransferTotals() {
		global $db;
		$tid = getTransferHash($this->transfer);
		$transferTotals = $this->getTransferTotal($this->transfer);
		$sql = ($db->GetOne("SELECT 1 FROM tf_transfer_totals WHERE tid = '".$tid."'"))
			? "UPDATE tf_transfer_totals SET uptotal = '".$transferTotals["uptotal"]."', downtotal = '".$transferTotals["downtotal"]."' WHERE tid = '".$tid."'"
			: "INSERT INTO tf_transfer_totals (tid,uptotal,downtotal) VALUES ('".$tid."','".$transferTotals["uptotal"]."','".$transferTotals["downtotal"]."')";
		$db->Execute($sql);
		// set transfers-cache
		cacheTransfersSet();
	}

	/**
	 * deletes a transfer
	 *
	 * @return boolean
	 */
	function execDelete() {
		global $cfg;
        // delete
		if (($cfg["user"] == $this->owner) || $cfg['isAdmin']) {
			// XFER: before deletion save upload/download xfer data to SQL
			if ($cfg['enable_xfer'] == 1) {
				$transferTotals = $this->getTransferCurrent($this->transfer);
				saveXfer($this->owner, $transferTotals["downtotal"], $transferTotals["uptotal"]);
			}
			// update totals
			$this->execUpdateTransferTotals();
			// remove settings from db
			deleteTransferSettings($this->transfer);
			// client-cache
			$this->execDeleteCache();
			// command-clean
       		CommandHandler::clean($this->transfer);
			// remove meta-file
			if (@file_exists($this->transferFilePath))
				@unlink($this->transferFilePath);
			// remove stat-file
			if (@file_exists($this->transferFilePath.".stat"))
				@unlink($this->transferFilePath.".stat");
			// if exist remove pid file
			if (@file_exists($this->transferFilePath.".pid"))
				@unlink($this->transferFilePath.".pid");
			// if exist remove log-file
			if (@file_exists($this->transferFilePath.".log"))
				@unlink($this->transferFilePath.".log");
			// if exist remove prio-file
			if (@file_exists($this->transferFilePath.".prio"))
				@unlink($this->transferFilePath.".prio");
			AuditAction($cfg["constants"]["delete_transfer"], $this->transfer);
			return true;
		} else {
			AuditAction($cfg["constants"]["error"], "ILLEGAL DELETE: ".$this->transfer);
			return false;
		}
	}

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
								'client' => $this->client,
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
	    for ($i = 0; $i < sizeof($arScreen); $i++) {
	        if (strpos($arScreen[$i], $this->binClient) !== false) {
	            $pinfo = new ProcessInfo($arScreen[$i]);
	            if (intval($pinfo->ppid) == 1) {
	                if (!strpos($pinfo->cmdline, "rep ". $this->binSystem) > 0) {
	                    if (!strpos($pinfo->cmdline, "ps x") > 0) {
	                        array_push($pProcess,$pinfo->pid);
	                        $rt = RunningTransfer::getInstance($pinfo->pid." ".$pinfo->cmdline, $this->client);
	                        array_push($ProcessCmd, $rt->transferowner."\t".$rt->transferFile);
	                    }
	                }
	            } else {
	                if (!strpos($pinfo->cmdline, "rep ". $this->binSystem) > 0) {
	                    if (!strpos($pinfo->cmdline, "ps x") > 0) {
	                        array_push($cProcess, $pinfo->pid);
	                        array_push($cpProcess, $pinfo->ppid);
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
	    foreach ($pProcess as $key => $value)
	        $retVal .= " " . $value . "\t" . $ProcessCmd[$key] . "\n";
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
    	// return if transfer-file-field not set
    	if ($this->transferFilePath == "") return false;
    	// log
		if ($handle = @fopen($this->transferFilePath.".log", "a+")) {
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
    	global $cfg, $transfers;
        $this->transfer = $transfer;
        $this->transferFilePath = $cfg["transfer_file_path"].$this->transfer;
        $this->owner = getOwner($transfer);
    }

    /**
     * load settings
     *
     * @return boolean
     */
    function settingsLoad() {
        $settingsAry = loadTransferSettings($this->transfer);
        if (is_array($settingsAry)) {
        	$this->hash        = $settingsAry["hash"];
        	$this->datapath    = $settingsAry["datapath"];
            $this->savepath    = $settingsAry["savepath"];
            $this->running     = $settingsAry["running"];
            $this->rate        = $settingsAry["max_upload_rate"];
            $this->drate       = $settingsAry["max_download_rate"];
            $this->maxuploads  = $settingsAry["max_uploads"];
            $this->superseeder = $settingsAry["superseeder"];
            $this->runtime     = $settingsAry["torrent_dies_when_done"];
            $this->sharekill   = $settingsAry["sharekill"];
            $this->minport     = $settingsAry["minport"];
            $this->maxport     = $settingsAry["maxport"];
            $this->maxcons     = $settingsAry["maxcons"];
            // loaded
            return true;
    	} else {
    		// not loaded
    		return false;
    	}
    }

    /**
     * save settings
     */
    function settingsSave() {
        saveTransferSettings(
        	$this->transfer,
        	$this->type,
        	$this->client,
        	$this->hash,
        	$this->datapath,
        	$this->savepath,
        	$this->running,
        	$this->rate,
        	$this->drate,
        	$this->maxuploads,
        	$this->superseeder,
        	$this->runtime,
        	$this->sharekill,
        	$this->minport,
        	$this->maxport,
        	$this->maxcons
        );
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
				$this->logMessage($msg."\n", true);
				// return
                return false;
            }
        }
        return false;
    }

} // end class

?>