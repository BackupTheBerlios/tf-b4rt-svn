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
    var $version = "";
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
    // handler-status
    var $status = 0;    // status of the handler
                        //  0 : not initialized
                        //  1 : initialized
                        //  2 : ready to start
                        //  3 : transfer-client started successfull
                        // -1 : error

    //--------------------------------------------------------------------------
    /**
     * ctor
     */
    function ClientHandler() {
        $this->status = -1;
        die('base class -- dont do this');
    }

    //--------------------------------------------------------------------------
    // factory
    /**
     * get ClientHandler-instance
     *
     * @param $fluxCfg torrent-flux config-array
     * @return $clientHandler ClientHandler-instance
     */
    function getClientHandlerInstance($fluxCfg, $clientType = '') {
        // damn dirty but does php (< 5) have reflection or something like
        // class-by-name ?
        if ((isset($clientType)) && ($clientType != '')) {
            $clientClass = $clientType;
            $fluxCfg["btclient"] = $clientType;
        } else {
            $clientClass = $fluxCfg["btclient"];
        }
        $classFile = 'ClientHandler.'.$clientClass.'.php';
        if (is_file($classFile)) {
            include_once($classFile);
            switch ($clientClass) {
                case "tornado":
                    return new ClientHandlerTornado(serialize($fluxCfg));
                break;
                case "transmission":
                    return new ClientHandlerTransmission(serialize($fluxCfg));
                break;
                case "wget":
                    return new ClientHandlerWget(serialize($fluxCfg));
                break;
            }
        }
    }

    //--------------------------------------------------------------------------
    // Initialize the Client Handler.
    function Initialize($cfg) {
        $this->cfg = unserialize($cfg);
        if (empty($this->cfg)) {
            $this->messages = "Config not passed";
            $this->status = -1;
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
        $this->status = 1;
    }

    //--------------------------------------------------------------------------
    /**
     * prepares start of a client.
     * prepares vars and other generic stuff
     * @param $transfer name of the transfer
     * @param $interactive (1|0) : is this a interactive startup with dialog ?
     * @param $enqueue (boolean) : enqueue ?
     */
    function prepareStartClient($transfer, $interactive, $enqueue = false) {
        if ($this->status < 1) {
            $this->status = -1;
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
	            if(IsAdmin()) {
	                $this->queue = getRequestVar('queue');
	                if($this->queue == 'on')
	                    $this->queue = "1";
	                else
	                    $this->queue = "0";
	            } else {
	                $this->queue = "1";
	            }
	        } else {
	            $this->queue = "0";
	        }
        } else { // non-interactive, load settings from db and set vars
            $this->rerequest = $this->cfg["rerequest_interval"];
            $this->skip_hash_check = $this->cfg["skiphashcheck"];
            $this->superseeder = 0;
			// queue
	        if ($enqueue) {
	            if(IsAdmin()) {
	                if($enqueue)
	                    $this->queue = "1";
	                else
	                    $this->queue = "0";
	            } else {
	                $this->queue = "1";
	            }
	        } else {
	            $this->queue = "0";
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
        $this->owner = getOwner($this->transfer);
        if (empty($this->savepath))
          $this->savepath = $this->cfg['path'].$this->owner."/";
        // ensure path has trailing slash
        $this->savepath = checkDirPathString($this->savepath);
        // The following lines of code were suggested by Jody Steele jmlsteele@stfu.ca
        // This is to help manage user downloads by their user names
        // if the user's path doesnt exist, create it
        if (!is_dir($this->cfg["path"]."/".$this->owner)) {
            if (is_writable($this->cfg["path"])) {
                mkdir($this->cfg["path"]."/".$this->owner, 0777);
            } else {
                AuditAction($this->cfg["constants"]["error"], "Error -- " . $this->cfg["path"] . " is not writable.");
                if (IsAdmin()) {
                    $this->status = -1;
                    header("location: index.php?iid=admin&op=configSettings");
                    return;
                } else {
                    $this->status = -1;
                    $this->messages .= "Error. TorrentFlux settings are not correct (path is not writable) -- please contact an admin.";
                }
            }
        }
        // create AliasFile object and write out the stat file
        include_once("AliasFile.php");
        $this->af = AliasFile::getAliasFileInstance($this->cfg["torrent_file_path"].$this->alias.".stat", $this->owner, $this->cfg, $this->handlerName);
        //XFER: before a transfer start/restart save upload/download xfer to SQL
        $transferTotals = getTransferTotals($this->transfer);
        saveXfer($this->owner,($transferTotals["downtotal"]+0),($transferTotals["uptotal"]+0));
        // update totals for this transfer
        updateTransferTotals($this->transfer);
        // set param for sharekill
        if ($this->sharekill <= 0) { // nice, we seed forever
            $this->sharekill_param = 0;
        } else { // recalc sharekill
            $totalAry = getTransferTotals(urldecode($transfer));
            $upTotal = $totalAry["uptotal"]+0;
            $transferSize = $this->af->size+0;
            $upWanted = ($this->sharekill / 100) * $transferSize;
            if ($upTotal >= $upWanted) { // we already have seeded at least
                                         // wanted percentage. continue to seed
                                         // forever is suitable in this case ~~
                $this->sharekill_param = 0;
            } else { // not done seeding wanted percentage
                $this->sharekill_param = (int) ($this->sharekill - (($upTotal / $transferSize) * 100));
                // the type-cast may have floored the value. (tornado lacks
                // precision because only (really?) accepting percentage-values)
                // better to seed more than less so we add a percent in case ;)
                if (($upWanted % $upTotal) != 0)
                    $this->sharekill_param += 1;
                // sanity-check.
                if ($this->sharekill_param <= -1)
                    $this->sharekill_param = 0;
            }
        }
        // write stat-file
        if($this->queue == "1") {
            $this->af->QueueTransferFile();  // this only writes out the stat file (does not start transfer)
        } else {
            if ($this->setClientPort() === false)
                return;
            $this->af->StartTransferFile();  // this only writes out the stat file (does not start transfer)
        }
        // set status
        $this->status = 2;
    }

    //--------------------------------------------------------------------------
    /**
     * do start of a client.
     */
    function doStartClient() {
        include_once("AliasFile.php");
        if ($this->status != 2) {
            $this->status = -1;
            $this->messages .= "Error. ClientHandler in wrong state on execStart-request.";
            return;
        }
        // write the session to close so older version of PHP will not hang
        session_write_close("TorrentFlux");
        $transferRunningFlag = 1;
        if ($this->queue == "1") { // queue
			require_once("Fluxd.php");
			require_once("Fluxd.ServiceMod.php");
			$fluxd = new Fluxd(serialize($this->cfg));
			$fluxdRunning = $fluxd->isFluxdRunning();
			if (($fluxdRunning) && ($fluxd->modState('Qmgr') == 1)) {
				$fluxdQmgr = FluxdServiceMod::getFluxdServiceModInstance($this->cfg, $fluxd, 'Qmgr');
				$fluxdQmgr->enqueueTorrent($this->transfer, $this->cfg['user']);
				AuditAction($this->cfg["constants"]["queued_torrent"], $this->transfer ."<br>Die:".$this->runtime .", Sharekill:".$this->sharekill .", MaxUploads:".$this->maxuploads .", DownRate:".$this->drate .", UploadRate:".$this->rate .", Ports:".$this->minport ."-".$this->maxport .", SuperSeed:".$this->superseeder .", Rerequest Interval:".$this->rerequest);
			} else {
				$this->messages = "Qmgr not active";
			}
            $transferRunningFlag = 0;
        } else { // start
            // The following command starts the transfer running! w00t!
            //system('echo command >> /tmp/fluxi.debug; echo "'. $this->command .'" >> /tmp/fluxi.debug');
            $this->callResult = exec($this->command);
            AuditAction($this->cfg["constants"]["start_torrent"], $this->transfer. "<br>Die:".$this->runtime .", Sharekill:".$this->sharekill .", MaxUploads:".$this->maxuploads .", DownRate:".$this->drate .", UploadRate:".$this->rate .", Ports:".$this->minport ."-".$this->maxport .", SuperSeed:".$this->superseeder .", Rerequest Interval:".$this->rerequest);
            // slow down and wait for thread to kick off.
            // otherwise on fast servers it will kill stop it before it gets a chance to run.
            sleep(1);
            $transferRunningFlag = 1;
        }
        if ($this->messages == "") {
            // Save transfer settings
            saveTorrentSettings($this->transfer, $transferRunningFlag, $this->rate, $this->drate, $this->maxuploads, $this->runtime, $this->sharekill, $this->minport, $this->maxport, $this->maxcons, $this->savepath, $this->handlerName);
            $this->status = 3;
        } else {
            AuditAction($this->cfg["constants"]["error"], $this->messages);
            $this->status = -1;
        }
    }

    //--------------------------------------------------------------------------
    /**
     * stops a client
     *
     * @param $transfer name of the transfer
     * @param $aliasFile alias-file of the transfer
     * @param $kill kill-param
     * @param $return return-param
     */
    function doStopClient($transfer, $aliasFile, $transferPid = "", $return = "") {
        // set some vars
        $this->transfer = $transfer;
        $this->alias = $aliasFile;
        // set pidfile
        if ($this->pidFile == "") // pid-file not set in subclass. use a default
            $this->pidFile = $this->cfg["torrent_file_path"].$this->alias.".pid";
        // We are going to write a '0' on the front of the stat file so that
        // the client will no to stop -- this will report stats when it dies
        $this->owner = getOwner($this->transfer);
        include_once("AliasFile.php");
        // read the alias file + create AliasFile object
        $this->af = AliasFile::getAliasFileInstance($this->cfg["torrent_file_path"].$this->alias, $this->owner, $this->cfg, $this->handlerName);
        if($this->af->percent_done < 100) {
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
        include_once("RunningTransfer.php");
        // see if the transfer process is hung.
        if (!is_file($this->pidFile)) {
            $running = getRunningTransfers();
            foreach ($running as $key => $value) {
                $rt = RunningTransfer::getRunningTransferInstance($value,$this->cfg,$this->handlerName);
                if ($rt->statFile == $this->alias) {
                    AuditAction($this->cfg["constants"]["error"], "Posible Hung Process " . $rt->processId);
                //    $callResult = exec("kill ".$rt->processId);
                }
            }
        }
        // Write out the new Stat File
        $this->af->WriteFile();
        // flag the transfer as stopped (in db)
        // blame me for this dirty shit, i am lazy. of course this should be
        // hooked into the place where client really dies.
        stopTorrentSettings($this->transfer);
        //
        AuditAction($this->cfg["constants"]["kill_torrent"], $this->transfer);
        if (!empty($return)) {
            sleep(3);
            // set pid
            if ((isset($transferPid)) && ($transferPid != ""))
                $this->pid = $transferPid;
            else
                $this->pid = trim(shell_exec($this->cfg['bin_cat']." ".$this->pidFile));
            // kill it
            $this->callResult = exec("kill ".$this->pid);
            // try to remove the pid file
            @unlink($this->pidFile);
        }
    }

    //--------------------------------------------------------------------------
    /**
     * print info of running clients
     *
     */
    function printRunningClientsInfo() {
        // action
        include_once("RunningTransfer.php");
        // ps-string
        $screenStatus = shell_exec("ps x -o pid='' -o ppid='' -o command='' -ww | ".$this->cfg['bin_grep']." ". $this->binClient ." | ".$this->cfg['bin_grep']." ".$this->cfg["torrent_file_path"]." | ".$this->cfg['bin_grep']." -v grep");
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
                            $rt = RunningTransfer::getRunningTransferInstance($pinfo->pid . " " . $pinfo->cmdline, $this->cfg, $this->handlerName);
                            array_push($ProcessCmd,$rt->transferowner . "\t". str_replace(array(".stat"),"",$rt->statFile));
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
        $printRunningClientsInfo = " --- Running Processes ---\n";
        $printRunningClientsInfo .= " Parents  : " . count($pProcess) . "\n";
        $printRunningClientsInfo .= " Children : " . count($cProcess) . "\n";
        $printRunningClientsInfo .= "\n";
        $printRunningClientsInfo .= " PID \tOwner\tTorrent File\n";
        foreach($pProcess as $key => $value) {
            $printRunningClientsInfo .= " " . $value . "\t" . $ProcessCmd[$key] . "\n";
            foreach($cpProcess as $cKey => $cValue)
                if (intval($value) == intval($cValue))
                    $printRunningClientsInfo .= "\t" . $cProcess[$cKey] . "\n";
        }
        $printRunningClientsInfo .= "\n";
        return $printRunningClientsInfo;
    }

    //--------------------------------------------------------------------------
    /**
     * gets count of running clients
     *
     * @return client-count
     */
    function getRunningClientCount() {
        return count($this->getRunningClients());
    }

    //--------------------------------------------------------------------------
    /**
     * gets ary of running clients
     *
     * @return client-ary
     */
    function getRunningClients() {
        // ps-string
        $screenStatus = shell_exec("ps x -o pid='' -o ppid='' -o command='' -ww | ".$this->cfg['bin_grep']." ". $this->binClient ." | ".$this->cfg['bin_grep']." ".$this->cfg["torrent_file_path"]." | ".$this->cfg['bin_grep']." -v grep");
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

    //--------------------------------------------------------------------------
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
                $this->status = -1;
                $this->messages .= "<b>Error</b> All ports in use.<br>";
                return false;
            }
        }
        return false;
    }

    //--------------------------------------------------------------------------
    /**
     * deletes cache of a transfer
     *
     * @param $transfer
     */
    function deleteCache($transfer) { return; }

    //--------------------------------------------------------------------------
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

    //--------------------------------------------------------------------------
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