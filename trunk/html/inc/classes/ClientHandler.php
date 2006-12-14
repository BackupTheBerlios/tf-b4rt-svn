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


// base class ClientHandler
class ClientHandler
{
    var $handlerName = "";
    var $binSystem = ""; // the sys-binary of this client
    var $binClient = ""; // the binary of this client.. stay a bit flux-comp.
                         // its not using the sys-bin for some ops
    var $binSocket = ""; // the binary this client uses for socket-connections
                         // used in netstat hack to identify connections
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
    //
    var $queue = "";
    //
    var $transfer = "";
    var $alias = "";
    var $owner = "";
    // alias-file (object)
    var $af;
    // pid
    var $pid = "";
    var $pidFile = "";
    // logfile
    var $logFile = "";
    // command
    var $command = "";
    // umask
    var $umask = "";
    // nice
    var $nice = "";
    // call-result
    var $callResult;
    // config-array
    var $cfg = array();
    // messages-string
    var $messages = "";
    // handler-state
    var $state = 0;    // state of the handler
                       //  0 : not initialized
                       //  1 : initialized
                       //  2 : ready to start
                       //  3 : transfer-client started successfull
                       // -1 : error

    /**
     * ctor
     */
    function ClientHandler() {
        $this->state = -1;
        die('base class -- dont do this');
    }

    /**
     * get ClientHandler-instance
     *
     * @param $fluxCfg torrent-flux config-array
     * @return ClientHandler
     */
    function getInstance($fluxCfg, $clientType = '') {
    	// create and return object-instance
        if ($clientType != '') {
            $clientClass = $clientType;
            $fluxCfg["btclient"] = $clientType;
        } else {
            $clientClass = $fluxCfg["btclient"];
        }
        switch ($clientClass) {
            case "tornado":
            	require_once('inc/classes/ClientHandler.tornado.php');
                return new ClientHandlerTornado(serialize($fluxCfg));
            case "transmission":
            	require_once('inc/classes/ClientHandler.transmission.php');
                return new ClientHandlerTransmission(serialize($fluxCfg));
            case "mainline":
            	require_once('inc/classes/ClientHandler.mainline.php');
                return new ClientHandlerMainline(serialize($fluxCfg));
            case "wget":
            	require_once('inc/classes/ClientHandler.wget.php');
                return new ClientHandlerWget(serialize($fluxCfg));
            default:
            	AuditAction($fluxCfg["constants"]["error"], "Invalid ClientHandler-Class : ".$clientClass);
				global $argv;
    			if (isset($argv))
    				die("Invalid ClientHandler-Class : ".$clientClass);
    			else
    				showErrorPage("Invalid ClientHandler-Class : <br>".htmlentities($clientClass, ENT_QUOTES));
        }
    }

    /**
     * initialize the Client Handler.
     *
     * @param $cfg
     */
    function initialize($cfg) {
        $this->cfg = unserialize($cfg);
        if (empty($this->cfg)) {
            $this->messages = "Config not passed";
            $this->state = -1;
            return;
        }
        // umask
        $this->umask = "";
        if ($this->cfg["enable_umask"] != 0)
            $this->umask = " umask 0000;";
        // nice
        $this->nice = "";
        if ($this->cfg["nice_adjust"] != 0)
            $this->nice = "nice -n ".$this->cfg["nice_adjust"]." ";
        // state ok
        $this->state = 1;
    }

    /**
     * prepares start of a client.
     * prepares vars and other generic stuff
     * @param $transfer name of the transfer
     * @param $interactive (1|0) : is this a interactive startup with dialog ?
     * @param $enqueue (boolean) : enqueue ?
     */
    function prepareStartClient($transfer, $interactive, $enqueue = false) {
        if ($this->state < 1) {
            $this->state = -1;
            $this->messages .= "Error. ClientHandler in wrong state on prepare-request.";
            return;
        }
        if ($interactive == 1) { // interactive, get vars from request vars
            $this->rate = getRequestVar('rate');
            if (empty($this->rate)) {
                if ($this->rate != "0")
                    $this->rate = $this->cfg["max_upload_rate"];
            }
            $this->drate = getRequestVar('drate');
            if (empty($this->drate)) {
                if ($this->drate != "0")
                    $this->drate = $this->cfg["max_download_rate"];
            }
            $this->superseeder = getRequestVar('superseeder');
            if (empty($this->superseeder))
                $this->superseeder = "0"; // should be 0 in most cases
            $this->runtime = getRequestVar('runtime');
            if (empty($this->runtime))
                $this->runtime = $this->cfg["torrent_dies_when_done"];
            $this->maxuploads = getRequestVar('maxuploads');
            if (empty($this->maxuploads)) {
                if ($this->maxuploads != "0")
                    $this->maxuploads = $this->cfg["max_uploads"];
            }
            $this->minport = getRequestVar('minport');
            if (empty($this->minport))
                $this->minport = $this->cfg["minport"];
            $this->maxport = getRequestVar('maxport');
            if (empty($this->maxport))
                $this->maxport = $this->cfg["maxport"];
            $this->maxcons = getRequestVar('maxcons');
            if (empty($this->maxcons))
              $this->maxcons = $this->cfg["maxcons"];
            $this->rerequest = getRequestVar("rerequest");
            if (empty($this->rerequest))
                $this->rerequest = $this->cfg["rerequest_interval"];
            $this->sharekill = getRequestVar('sharekill');
            if ($this->runtime == "True" )
                $this->sharekill = "-1";
            if (empty($this->sharekill)) {
                if ($this->sharekill != "0")
                    $this->sharekill = $this->cfg["sharekill"];
            }
            $this->savepath = getRequestVar('savepath') ;
            $this->skip_hash_check = getRequestVar('skiphashcheck');
	        // queue
	        if ($enqueue) {
	            if ($this->cfg['isAdmin']) {
	                $queueTemp = getRequestVar('queue');
	                if ($queueTemp == "true")
	                    $this->queue = 1;
	                else
	                    $this->queue = 0;
	            } else {
	                $this->queue = 1;
	            }
	        } else {
	            $this->queue = 0;
	        }
        } else { // non-interactive, load settings from db and set vars
            $this->rerequest = $this->cfg["rerequest_interval"];
            $this->skip_hash_check = $this->cfg["skiphashcheck"];
            $this->superseeder = 0;
			// queue
	        if ($enqueue) {
	            if ($this->cfg['isAdmin']) {
	                if ($enqueue)
	                    $this->queue = 1;
	                else
	                    $this->queue = 0;
	            } else {
	                $this->queue = 1;
	            }
	        } else {
	            $this->queue = 0;
	        }
            // load settings
            $settingsAry = loadTorrentSettings(urldecode($transfer));
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
            if ($this->rate == '') $this->rate = $this->cfg["max_upload_rate"];
            if ($this->drate == '') $this->drate = $this->cfg["max_download_rate"];
            if ($this->runtime == '') $this->runtime = $this->cfg["torrent_dies_when_done"];
            if ($this->maxuploads == '') $this->maxuploads = $this->cfg["max_uploads"];
            if ($this->minport == '') $this->minport = $this->cfg["minport"];
            if ($this->maxport == '') $this->maxport = $this->cfg["maxport"];
            if ($this->maxcons == '') $this->maxcons = $this->cfg["maxcons"];
            if ($this->sharekill == '') $this->sharekill = $this->cfg["sharekill"];
        }
        $this->transfer = urldecode($transfer);
        $this->alias = getAliasName($this->transfer);
        $this->logFile = $this->cfg["transfer_file_path"].$this->alias.".log";
        $this->owner = getOwner($this->transfer);
        if (empty($this->savepath)) {
	        switch ($this->cfg["enable_home_dirs"]) {
	        	case 1:
	        	default:
	        		$this->savepath = $this->cfg['path'].$this->owner."/";
	        		break;
	        	case 0:
	        		$this->savepath = $this->cfg['path'].$this->cfg["path_incoming"]."/";
	        		break;
	        }
        }
        // ensure path has trailing slash
        $this->savepath = checkDirPathString($this->savepath);
        // check target-directory, create if not present
		if (!(checkDirectory($this->savepath, 0777))) {
            AuditAction($this->cfg["constants"]["error"], "Error checking " . $this->savepath . ".");
            $this->state = -1;
            $this->messages .= "Error. TorrentFlux settings are not correct (path-setting).";
            global $argv;
            if (isset($argv)) {
            	die($this->messages);
            } else {
				if ($this->cfg['isAdmin']) {
					@header("location: admin.php?op=serverSettings");
					exit();
				} else {
					$this->messages .= " please contact an admin.";
					showErrorPage($this->messages);
				}
            }
		}
        // create AliasFile object and write out the stat file
        $this->af = new AliasFile($this->alias.".stat", $this->owner);
        $transferTotals = getTransferTotalsCurrent($this->transfer);
        //XFER: before a transfer start/restart save upload/download xfer to SQL
        if ($this->cfg['enable_xfer'] == 1)
        	saveXfer($this->owner,($transferTotals["downtotal"]),($transferTotals["uptotal"]));
        // update totals for this transfer
        updateTransferTotals($this->transfer);
        // set param for sharekill
        $this->sharekill = intval($this->sharekill);
        if ($this->sharekill == 0) { // nice, we seed forever
            $this->sharekill_param = 0;
        } elseif ($this->sharekill > 0) { // recalc sharekill
            // sanity-check. catch "data-size = 0".
            $transferSize = intval($this->af->size);
            if ($transferSize > 0) {
				$totalAry = getTransferTotals($this->transfer);
            	$upTotal = $totalAry["uptotal"] + 0;
            	$downTotal = $totalAry["downtotal"] + 0;
				$upWanted = ($this->sharekill / 100) * $transferSize;
				$sharePercentage = ($upTotal / $transferSize) * 100;
	            if (($upTotal >= $upWanted) && ($downTotal >= $transferSize)) {
	            	// we already have seeded at least wanted percentage.
	            	// skip start of client
	                // set state
        			$this->state = 1;
        			// message
        			$this->messages = "skipping start of transfer ".$this->transfer." due to share-ratio (has: ".@number_format($sharePercentage, 2)." ; set:".$this->sharekill.")";
					// DEBUG : log the messages
					AuditAction($this->cfg["constants"]["debug"], $this->messages);
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
				$this->messages = "data-size is 0 when recalcing share-kill for ".$this->transfer.". setting sharekill absolute to ".$this->sharekill;
				AuditAction($this->cfg["constants"]["error"], $this->messages);
				$this->sharekill_param = $this->sharekill;
            }
		} else {
        	$this->sharekill_param = $this->sharekill;
        }
        // write stat-file
        if ($this->queue == 1) {
            $this->af->queue();
        } else {
            if ($this->setClientPort() === false)
                return;
            $this->af->start();
        }
        // set state
        $this->state = 2;
    }

    /**
     * do start of a client.
     */
    function doStartClient() {
        if ($this->state != 2) {
            $this->state = -1;
            $this->messages .= "Error. ClientHandler in wrong state on execStart-request.";
            return;
        }
        // write the session to close so older version of PHP will not hang
        @session_write_close();
        $transferRunningFlag = 1;
        if ($this->queue == 1) { // queue
			if (FluxdQmgr::isRunning()) {
				FluxdQmgr::enqueueTransfer($this->transfer, $this->cfg['user']);
				AuditAction($this->cfg["constants"]["queued_transfer"], $this->transfer ." : Die:".$this->runtime .", Sharekill:".$this->sharekill .", MaxUploads:".$this->maxuploads .", DownRate:".$this->drate .", UploadRate:".$this->rate .", Ports:".$this->minport ."-".$this->maxport .", SuperSeed:".$this->superseeder .", Rerequest Intervall:".$this->rerequest);
			} else {
				$this->messages = "queue-request (".$this->transfer."/".$this->cfg['user'].") but Qmgr not active";
				AuditAction($this->cfg["constants"]["error"], $this->messages);
			}
            $transferRunningFlag = 0;
        } else { // start
        	// log the command
        	$this->transferLog("ClientHandler::doStartClient : \n".$this->command."\n", true);
            // startup
            $this->callResult = exec($this->command);
            AuditAction($this->cfg["constants"]["start_torrent"], $this->transfer. " : Die:".$this->runtime .", Sharekill:".$this->sharekill .", MaxUploads:".$this->maxuploads .", DownRate:".$this->drate .", UploadRate:".$this->rate .", Ports:".$this->minport ."-".$this->maxport .", SuperSeed:".$this->superseeder .", Rerequest Intervall:".$this->rerequest);
            $transferRunningFlag = 1;
            // wait until transfer is up
            waitForTransfer($this->transfer, 1, 15);
        }
        if ($this->messages == "") {
            // Save transfer settings
            saveTorrentSettings($this->transfer, $transferRunningFlag, $this->rate, $this->drate, $this->maxuploads, $this->runtime, $this->sharekill, $this->minport, $this->maxport, $this->maxcons, $this->savepath, $this->handlerName);
            $this->state = 3;
        } else {
            AuditAction($this->cfg["constants"]["error"], $this->messages);
            $this->state = -1;
        }
    }

    /**
     * stops a client
     *
     * @param $transfer name of the transfer
     * @param $aliasFile alias-file of the transfer
     * @param $transferPid
     * @param $return return-param
     */
    function doStopClient($transfer, $aliasFile, $transferPid = "", $return = "") {
        // set some vars
        $this->transfer = $transfer;
        $this->alias = $aliasFile;
        // log
        AuditAction($this->cfg["constants"]["stop_transfer"], $this->transfer);
        // set pidfile
        if ($this->pidFile == "") // pid-file not set in subclass. use a default
            $this->pidFile = $this->cfg["transfer_file_path"].$this->alias.".pid";
        // We are going to write a '0' on the front of the stat file so that
        // the client will no to stop -- this will report stats when it dies
        $this->owner = getOwner($this->transfer);
        // read the alias file + create AliasFile object
        $this->af = new AliasFile($this->alias, $this->owner);
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
        /*
        $running = getRunningTransfers();
        $isHung = 0;
        foreach ($running as $key => $value) {
            $rt = RunningTransfer::getInstance($value, $this->handlerName);
            if ($rt->statFile == $this->alias) {
            	$isHung = 1;
                AuditAction($this->cfg["constants"]["error"], "Posible Hung Process " . $rt->processId);
            	// $callResult = exec("kill ".$rt->processId);
            }
        }
		*/
        // flag the transfer as stopped (in db)
        // blame me for this dirty shit, i am lazy. of course this should be
        // hooked into the place where client really dies.
        stopTorrentSettings($this->transfer);
        // kill
        if (!empty($return)) { // fluxd-page
        	AuditAction($this->cfg["constants"]["kill_transfer"], $this->transfer);
            // set pid
            if ((isset($transferPid)) && ($transferPid != "")) {
            	// test for valid pid-var
            	if (is_numeric($transferPid)) {
                	$this->pid = $transferPid;
            	} else {
		    		AuditAction($this->cfg["constants"]["error"], "Invalid kill-param : ".$this->cfg["user"]." tried to kill ".$transferPid);
		    		global $argv;
		    		if (isset($argv))
		    			die("Invalid kill-param : ".$transferPid);
		    		else
		    			showErrorPage("Invalid kill-param : <br>".htmlentities($transferPid, ENT_QUOTES));
            	}
            } else {
            	$data = "";
				if ($fileHandle = @fopen($this->pidFile,'r')) {
					while (!@feof($fileHandle))
						$data .= @fgets($fileHandle, 64);
					@fclose ($fileHandle);
				}
                $this->pid = trim($data);
            }
            // kill it
            $this->callResult = exec("kill ".escapeshellarg($this->pid));
            // try to remove the pid file
            @unlink($this->pidFile);
        }
    }

    /**
     * get info of running clients
     *
     */
    function getRunningClientsInfo() {
        // ps-string
        $screenStatus = shell_exec("ps x -o pid='' -o ppid='' -o command='' -ww | ".$this->cfg['bin_grep']." ". $this->binClient ." | ".$this->cfg['bin_grep']." ".$this->cfg["transfer_file_path"]." | ".$this->cfg['bin_grep']." -v grep");
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
                    if(!strpos($pinfo->cmdline, "rep ". $this->binSystem) > 0) {
                        if(!strpos($pinfo->cmdline, "ps x") > 0) {
                            array_push($pProcess,$pinfo->pid);
                            $rt = RunningTransfer::getInstance($pinfo->pid." ".$pinfo->cmdline, $this->handlerName);
                            array_push($ProcessCmd, $rt->transferowner."\t".str_replace(array(".stat"), "", $rt->statFile));
                        }
                    }
                } else {
                    if(!strpos($pinfo->cmdline, "rep ". $this->binSystem) > 0) {
                        if(!strpos($pinfo->cmdline, "ps x") > 0) {
                            array_push($cProcess,$pinfo->pid);
                            array_push($cpProcess,$pinfo->ppid);
                        }
                    }
                }
            }
        }
        $runningClientsInfo = " --- Running Processes ---\n";
        $runningClientsInfo .= " Parents  : " . count($pProcess) . "\n";
        $runningClientsInfo .= " Children : " . count($cProcess) . "\n";
        $runningClientsInfo .= "\n";
        $runningClientsInfo .= " PID \tOwner\tTransfer File\n";
        foreach($pProcess as $key => $value) {
            $runningClientsInfo .= " " . $value . "\t" . $ProcessCmd[$key] . "\n";
            foreach($cpProcess as $cKey => $cValue)
                if (intval($value) == intval($cValue))
                    $runningClientsInfo .= "\t" . $cProcess[$cKey] . "\n";
        }
        $runningClientsInfo .= "\n";
        return $runningClientsInfo;
    }

    /**
     * gets count of running clients
     *
     * @return client-count
     */
    function getRunningClientCount() {
        return count($this->getRunningClients());
    }

    /**
     * gets ary of running clients
     *
     * @return client-ary
     */
    function getRunningClients() {
        // ps-string
        $screenStatus = shell_exec("ps x -o pid='' -o ppid='' -o command='' -ww | ".$this->cfg['bin_grep']." ". $this->binClient ." | ".$this->cfg['bin_grep']." ".$this->cfg["transfer_file_path"]." | ".$this->cfg['bin_grep']." -v grep");
        $arScreen = array();
        $tok = strtok($screenStatus, "\n");
        while ($tok) {
            array_push($arScreen, $tok);
            $tok = strtok("\n");
        }
        $artransfer = array();
        for($i = 0; $i < sizeof($arScreen); $i++) {
            if(strpos($arScreen[$i], $this->binClient) !== false) {
                $pinfo = new ProcessInfo($arScreen[$i]);
                if (intval($pinfo->ppid) == 1) {
                     if(!strpos($pinfo->cmdline, "rep ". $this->binSystem) > 0) {
                         if(!strpos($pinfo->cmdline, "ps x") > 0) {
                             array_push($artransfer,$pinfo->pid . " " . $pinfo->cmdline);
                         }
                     }
                }
            }
        }
        return $artransfer;
    }

    /**
     * gets available port and sets field port
     *
     * @return boolean
     */
    function setClientPort() {
        $portString = netstatPortList();
        $portAry = explode("\n",$portString);
        $this->port = (int) $this->minport;
        while (1) {
            if (!(in_array($this->port,$portAry)))
                return true;
            else
                $this->port += 1;
            if ($this->port > $this->maxport) {
                $this->state = -1;
                $this->messages .= "All ports in use.";
                return false;
            }
        }
        return false;
    }

    /**
     * writes a message to the per-transfer-logfile
     *
     * @param $message
     * @param $withTS
     */
    function transferLog($message, $withTS = true) {
    	$content = "";
    	if ($withTS)
    		$content .= @date("[Y/m/d - H:i:s]")." ";
    	$content .= $message;
		$fp = false;
		$fp = @fopen($this->logFile, "a+");
		if (!$fp)
			return false;
		$result = @fwrite($fp, $content);
		@fclose($fp);
		if ($result === false)
			return false;
		return true;
    }

    /**
     * deletes cache of a transfer
     *
     * @param $transfer
     */
    function deleteCache($transfer) { return; }

    /**
     * gets current transfer-vals of a transfer
     *
     * @param $transfer
     * @return array with downtotal and uptotal
     */
    function getTransferCurrent($transfer)  { return; }

    /**
     * gets current transfer-vals of a transfer. optimized index-page-version
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
     * gets total transfer-vals of a transfer. optimized index-page-version
     *
     * @param $transfer
     * @param $tid of the transfer
     * @param $afu alias-file-uptotal of the transfer
     * @param $afd alias-file-downtotal of the transfer
     * @return array with downtotal and uptotal
     */
    function getTransferTotalOP($transfer, $tid, $afu, $afd) { return; }


} // end class

?>