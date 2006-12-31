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
    // generic vars for a torrent-start
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
    var $torrent = "";
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
    // handler-state
    var $state = 0;    // state of the handler
                       //  0 : not initialized
                       //  1 : initialized
                       //  2 : ready to start
                       //  3 : torrent-client started successfull
                       // -1 : error

    //--------------------------------------------------------------------------
    /**
     * ctor
     */
    function ClientHandler() {
        $this->state = -1;
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
        switch ($clientClass) {
            case "tornado":
            	require_once('ClientHandler.tornado.php');
                return new ClientHandlerTornado(serialize($fluxCfg));
            case "transmission":
            	require_once('ClientHandler.transmission.php');
                return new ClientHandlerTransmission(serialize($fluxCfg));
        }
    }

    //--------------------------------------------------------------------------
    // Initialize the Client Handler.
    function Initialize($cfg) {
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
        $this->state = 1;
    }

    //--------------------------------------------------------------------------
    /**
     * prepares start of a bittorrent-client.
     * prepares vars and other generic stuff
     * @param $torrent name of the torrent
     * @param $interactive (1|0) : is this a interactive startup with dialog ?
     */
    function prepareStartTorrentClient($torrent, $interactive) {
        if ($this->state < 1) {
            $this->state = -1;
            $this->messages .= "Error. ClientHandler in wrong state on prepare-request.";
            return;
        }
        $this->skip_hash_check = "";
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
        } else { // non-interactive, load settings from db and set vars
            $this->rerequest = $this->cfg["rerequest_interval"];
            $this->skip_hash_check = $this->cfg["skiphashcheck"];
            $this->superseeder = 0;
            // load settings
            $settingsAry = loadTorrentSettings(urldecode($torrent));
            $this->rate = $settingsAry["max_upload_rate"];
            $this->drate = $settingsAry["max_download_rate"];
            $this->runtime = $settingsAry["torrent_dies_when_done"];
            $this->maxuploads = $settingsAry["max_uploads"];
            $this->minport = $settingsAry["minport"];
            $this->maxport = $settingsAry["maxport"];
            $this->maxcons = $settingsAry["maxcons"];
            $this->sharekill = $settingsAry["sharekill"];
            $this->savepath = $settingsAry["savepath"];
            // fallback-values if fresh-torrent is started non-interactive or
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
        // queue
        if ($this->cfg["AllowQueing"]) {
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
        //
        $this->torrent = urldecode($torrent);
        $this->alias = getAliasName($this->torrent);
        $this->owner = getOwner($this->torrent);
        if (empty($this->savepath))
          $this->savepath = $this->cfg['path'].$this->owner."/";
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
				if (IsAdmin()) {
					@header("location: admin.php?op=configSettings");
					exit();
				} else {
					$this->messages .= " please contact an admin.";
					showErrorPage($this->messages);
				}
            }
		}
        // create AliasFile object and write out the stat file
        include_once("AliasFile.php");
        $this->af = AliasFile::getAliasFileInstance($this->cfg["torrent_file_path"].$this->alias.".stat", $this->owner, $this->cfg, $this->handlerName);
        // set param for sharekill
        $this->sharekill = intval($this->sharekill);
        if ($this->sharekill == 0) { // nice, we seed forever
            $this->sharekill_param = 0;
        } elseif ($this->sharekill > 0) { // recalc sharekill
            // sanity-check. catch "data-size = 0".
            $transferSize = intval($this->af->size);
            if ($transferSize > 0) {
				$totalAry = getTorrentTotals($this->torrent);
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
        			$this->messages = "skipping start of transfer ".$this->torrent." due to share-ratio (has: ".@number_format($sharePercentage, 2)." ; set:".$this->sharekill.")";
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
				$this->messages = "data-size is 0 when recalcing share-kill for ".$this->torrent.". setting sharekill absolute to ".$this->sharekill;
				AuditAction($this->cfg["constants"]["error"], $this->messages);
				$this->sharekill_param = $this->sharekill;
            }
        } else {
        	$this->sharekill_param = $this->sharekill;
        }
        // set port if start (not queue)
        if (!(($this->cfg["AllowQueing"]) && ($this->queue == "1"))) {
        	if ($this->setClientPort() === false)
                return;
        }
        //XFER: before a torrent start/restart save upload/download xfer to SQL
        $torrentTotals = getTorrentTotalsCurrent($this->torrent);
        saveXfer($this->owner,($torrentTotals["downtotal"]+0),($torrentTotals["uptotal"]+0));
        // update totals for this torrent
        updateTorrentTotals($this->torrent);
        // write stat-file
        if (($this->cfg["AllowQueing"]) && ($this->queue == "1"))
			$this->af->QueueTorrentFile();
        else
            $this->af->StartTorrentFile();
        // set state
        $this->state = 2;
    }

    //--------------------------------------------------------------------------
    /**
     * do start of a bittorrent-client.
     */
    function doStartTorrentClient() {
        include_once("AliasFile.php");
        if ($this->state != 2) {
            $this->state = -1;
            $this->messages .= "Error. ClientHandler in wrong state on execStart-request.";
            return;
        }
        // write the session to close so older version of PHP will not hang
        @session_write_close();
        $torrentRunningFlag = 1;
        if($this->af->running == "3") {
            // _queue_
            //writeQinfo($this->cfg["torrent_file_path"]."queue/".$this->alias.".stat",$this->command);
            include_once("QueueManager.php");
            $queueManager = QueueManager::getQueueManagerInstance($this->cfg);
            $queueManager->command = $this->command; // tfQmanager...
            $queueManager->enqueueTorrent($this->torrent);
            AuditAction($this->cfg["constants"]["queued_torrent"], $this->torrent ." : Die:".$this->runtime .", Sharekill:".$this->sharekill .", MaxUploads:".$this->maxuploads .", DownRate:".$this->drate .", UploadRate:".$this->rate .", Ports:".$this->minport ."-".$this->maxport .", SuperSeed:".$this->superseeder .", Rerequest Interval:".$this->rerequest);
            AuditAction($this->cfg["constants"]["queued_torrent"], $this->command);
            $torrentRunningFlag = 0;
        } else {
            // The following command starts the torrent running! w00t!
            //system('echo command >> /tmp/fluxi.debug; echo "'. $this->command .'" >> /tmp/fluxi.debug');
            $this->callResult = exec($this->command);
            AuditAction($this->cfg["constants"]["start_torrent"], $this->torrent. " : Die:".$this->runtime .", Sharekill:".$this->sharekill .", MaxUploads:".$this->maxuploads .", DownRate:".$this->drate .", UploadRate:".$this->rate .", Ports:".$this->minport ."-".$this->maxport .", SuperSeed:".$this->superseeder .", Rerequest Interval:".$this->rerequest);
            // slow down and wait for thread to kick off.
            // otherwise on fast servers it will kill stop it before it gets a chance to run.
            sleep(1);
            $torrentRunningFlag = 1;
        }
        if ($this->messages == "") {
            // Save torrent settings
            saveTorrentSettings($this->torrent, $torrentRunningFlag, $this->rate, $this->drate, $this->maxuploads, $this->runtime, $this->sharekill, $this->minport, $this->maxport, $this->maxcons, $this->savepath, $this->handlerName);
            $this->state = 3;
        } else {
            AuditAction($this->cfg["constants"]["error"], $this->messages);
            $this->state = -1;
        }
    }

    //--------------------------------------------------------------------------
    /**
     * stops a bittorrent-client
     *
     * @param $torrent name of the torrent
     * @param $aliasFile alias-file of the torrent
     * @param $torrentPid
     * @param $return return-param
     */
    function doStopTorrentClient($torrent, $aliasFile, $torrentPid = "", $return = "") {
        // set some vars
        $this->torrent = $torrent;
        $this->alias = $aliasFile;
        // set pidfile
        if ($this->pidFile == "") // pid-file not set in subclass. use a default
            $this->pidFile = $this->cfg["torrent_file_path"].$this->alias.".pid";
        // We are going to write a '0' on the front of the stat file so that
        // the BT client will no to stop -- this will report stats when it dies
        $this->owner = getOwner($this->torrent);
        include_once("AliasFile.php");
        // read the alias file + create AliasFile object
        $this->af = AliasFile::getAliasFileInstance($this->cfg["torrent_file_path"].$this->alias, $this->owner, $this->cfg, $this->handlerName);
        if($this->af->percent_done < 100) {
            // The torrent is being stopped but is not completed dowloading
            $this->af->percent_done = ($this->af->percent_done + 100)*-1;
            $this->af->running = "0";
            $this->af->time_left = "Torrent Stopped";
        } else {
            // Torrent was seeding and is now being stopped
            $this->af->percent_done = 100;
            $this->af->running = "0";
            $this->af->time_left = "Download Succeeded!";
        }
        include_once("RunningTorrent.php");
        // see if the torrent process is hung.
        if (!is_file($this->pidFile)) {
            $runningTorrents = getRunningTorrents();
            foreach ($runningTorrents as $key => $value) {
                $rt = RunningTorrent::getRunningTorrentInstance($value,$this->cfg,$this->handlerName);
                if ($rt->statFile == $this->alias) {
                    AuditAction($this->cfg["constants"]["error"], "Posible Hung Process " . $rt->processId);
                //    $callResult = exec("kill ".$rt->processId);
                }
            }
        }
        // Write out the new Stat File
        $this->af->WriteFile();
        // flag the torrent as stopped (in db)
        // blame me for this dirty shit, i am lazy. of course this should be
        // hooked into the place where client really dies.
        stopTorrentSettings($this->torrent);
        //
        if (!empty($return)) {
        	AuditAction($this->cfg["constants"]["kill_torrent"], $this->torrent);
            sleep(3);
            // set pid
            if ((isset($torrentPid)) && ($torrentPid != "")) {
            	// test for valid pid-var
            	if (is_numeric($torrentPid)) {
                	$this->pid = $torrentPid;
            	} else {
		    		AuditAction($this->cfg["constants"]["error"], "Invalid kill-param : ".$this->cfg["user"]." tried to kill ".$torrentPid);
		    		global $argv;
		    		if (isset($argv))
		    			die("Invalid kill-param : ".$torrentPid);
		    		else
		    			showErrorPage("Invalid kill-param : <br>".htmlentities($torrentPid, ENT_QUOTES));
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

    //--------------------------------------------------------------------------
    /**
     * print info of running bittorrent-clients
     *
     */
    function printRunningClientsInfo() {
        // action
        include_once("RunningTorrent.php");
        // ps-string
        $screenStatus = shell_exec("ps x -o pid='' -o ppid='' -o command='' -ww | ".$this->cfg['bin_grep']." ". $this->binClient ." | ".$this->cfg['bin_grep']." ".$this->cfg["torrent_file_path"]." | ".$this->cfg['bin_grep']." -v grep | ".$this->cfg['bin_grep']." -v ".$this->cfg['tfQManager']);
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
        // $QLine = "";
        for($i = 0; $i < sizeof($arScreen); $i++) {
            if(strpos($arScreen[$i], $this->binClient) !== false) {
                $pinfo = new ProcessInfo($arScreen[$i]);
                if (intval($pinfo->ppid) == 1) {
                    if(!strpos($pinfo->cmdline, "rep ". $this->binSystem) > 0) {
                        if(!strpos($pinfo->cmdline, "ps x") > 0) {
                            array_push($pProcess,$pinfo->pid);
                            $rt = RunningTorrent::getRunningTorrentInstance($pinfo->pid . " " . $pinfo->cmdline, $this->cfg, $this->handlerName);
                            //array_push($ProcessCmd,$pinfo->cmdline);
                            array_push($ProcessCmd,$rt->torrentOwner . "\t". str_replace(array(".stat"),"",$rt->statFile));
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
        echo " --- Running Processes ---\n";
        echo " Parents  : " . count($pProcess) . "\n";
        echo " Children : " . count($cProcess) . "\n";
        echo "\n";
        echo " PID \tOwner\tTorrent File\n";
        foreach($pProcess as $key => $value) {
            echo " " . $value . "\t" . $ProcessCmd[$key] . "\n";
            foreach($cpProcess as $cKey => $cValue)
                if (intval($value) == intval($cValue))
                    echo "\t" . $cProcess[$cKey] . "\n";
        }
        echo "\n";
    }

    //--------------------------------------------------------------------------
    /**
     * gets count of running bittorrent-clients
     *
     * @return client-count
     */
    function getRunningClientCount() {
        return count($this->getRunningClients());
    }

    //--------------------------------------------------------------------------
    /**
     * gets ary of running bittorrent-clients
     *
     * @return client-ary
     */
    function getRunningClients() {
        // ps-string
        $screenStatus = shell_exec("ps x -o pid='' -o ppid='' -o command='' -ww | ".$this->cfg['bin_grep']." ". $this->binClient ." | ".$this->cfg['bin_grep']." ".$this->cfg["torrent_file_path"]." | ".$this->cfg['bin_grep']." -v grep | ".$this->cfg['bin_grep']." -v ".$this->cfg['tfQManager']);
        $arScreen = array();
        $tok = strtok($screenStatus, "\n");
        while ($tok) {
            array_push($arScreen, $tok);
            $tok = strtok("\n");
        }
        $artorrent = array();
        for($i = 0; $i < sizeof($arScreen); $i++) {
            if(strpos($arScreen[$i], $this->binClient) !== false) {
                $pinfo = new ProcessInfo($arScreen[$i]);
                if (intval($pinfo->ppid) == 1) {
                     if(!strpos($pinfo->cmdline, "rep ". $this->binSystem) > 0) {
                         if(!strpos($pinfo->cmdline, "ps x") > 0) {
                             array_push($artorrent,$pinfo->pid . " " . $pinfo->cmdline);
                         }
                     }
                }
            }
        }
        return $artorrent;
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
                $this->state = -1;
                $this->messages .= "All ports in use.";
                return false;
            }
        }
        return false;
    }

    //--------------------------------------------------------------------------
    /**
     * deletes cache of a torrent
     *
     * @param $torrent
     */
    function deleteTorrentCache($torrent) { return; }

    //--------------------------------------------------------------------------
    /**
     * gets current transfer-vals of a torrent
     *
     * @param $torrent
     * @return array with downtotal and uptotal
     */
    function getTorrentTransferCurrent($torrent)  { return; }

    /**
     * gets current transfer-vals of a torrent. optimized index-page-version
     *
     * @param $torrent
     * @param $afu alias-file-uptotal of the torrent
     * @param $afd alias-file-downtotal of the torrent
     * @return array with downtotal and uptotal
     */
    function getTorrentTransferCurrentOP($torrent, $afu, $afd)  { return; }

    //--------------------------------------------------------------------------
    /**
     * gets total transfer-vals of a torrent
     *
     * @param $torrent
     * @return array with downtotal and uptotal
     */
    function getTorrentTransferTotal($torrent) { return; }

    /**
     * gets total transfer-vals of a torrent. optimized index-page-version
     *
     * @param $torrent
     * @param $afu alias-file-uptotal of the torrent
     * @param $afd alias-file-downtotal of the torrent
     * @return array with downtotal and uptotal
     */
    function getTorrentTransferTotalOP($torrent, $afu, $afd) { return; }


} // end class

?>