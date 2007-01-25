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
 * class ClientHandler for tornado-client
 */
class ClientHandlerTornado extends ClientHandler
{

	// public fields

	// tornado-bin
	var $tornadoBin = "";

	// =========================================================================
	// ctor
	// =========================================================================

    /**
     * ctor
     */
    function ClientHandlerTornado() {
    	global $cfg;
    	$this->type = "torrent";
        $this->client = "tornado";
        $this->binSystem = "python";
        $this->binSocket = "python";
        $this->binClient = "tftornado.py";
        $this->tornadoBin = $cfg["docroot"]."bin/clients/tornado/tftornado.py";
    }

	// =========================================================================
	// public methods
	// =========================================================================

    /**
     * starts a client
     *
     * @param $transfer name of the transfer
     * @param $interactive (boolean) : is this a interactive startup with dialog ?
     * @param $enqueue (boolean) : enqueue ?
     */
    function start($transfer, $interactive = false, $enqueue = false) {
		global $cfg;

    	// set vars
		$this->_setVarsForTransfer($transfer);

    	// log
    	$this->logMessage($this->client."-start : ".$transfer."\n", true);

        // do tornado special-pre-start-checks
        // check to see if the path to the python script is valid
        if (!is_file($this->tornadoBin)) {
        	$this->state = CLIENTHANDLER_STATE_ERROR;
        	$msg = "path for tftornado.py is not valid";
        	AuditAction($cfg["constants"]["error"], $msg);
        	$this->logMessage($msg."\n", true);
        	array_push($this->messages, $msg);
            array_push($this->messages, "tornadoBin : ".$this->tornadoBin);
            // write error to stat
			$sf = new StatFile($this->transfer, $this->owner);
			$sf->time_left = 'Error';
			$sf->write();
			// return
            return false;
        }

        // init starting of client
        $this->_init($interactive, $enqueue, true, ($cfg['enable_sharekill'] == 1));

		// only continue if init succeeded (skip start / error)
		if ($this->state != CLIENTHANDLER_STATE_READY) {
			if ($this->state == CLIENTHANDLER_STATE_ERROR) {
				$msg = "Error after init (".$transfer.",".$interactive.",".$enqueue.",true,".$cfg['enable_sharekill'].")";
				array_push($this->messages , $msg);
				$this->logMessage($msg."\n", true);
			}
			// return
			return false;
		}

		// file-prio
		if ($cfg["enable_file_priority"])
			setFilePriority($transfer);

		// pythonCmd
		$pyCmd = $cfg["pythonCmd"] . " -OO";

        // build the command-string
        $skipHashCheck = "";
        if ((!(empty($this->skip_hash_check))) && (getTorrentDataSize($transfer) > 0))
            $skipHashCheck = " --check_hashes 0";
        $filePrio = "";
        if (@file_exists($this->transferFilePath.".prio")) {
            $priolist = explode(',', file_get_contents($this->transferFilePath.".prio"));
            $priolist = implode(',', array_slice($priolist, 1, $priolist[0]));
            $filePrio = " --priority ".escapeshellarg($priolist);
        }

        // build the command-string
		// note : order of args must not change for ps-parsing-code in
		// RunningTransferTornado
		$this->command  = "cd ".escapeshellarg($this->savepath).";";
		$this->command .= " HOME=".escapeshellarg($cfg["path"]);
		$this->command .= "; export HOME;";
		$this->command .= $this->umask;
		$this->command .= " nohup ";
		$this->command .= $this->nice;
		$this->command .= $pyCmd . " " .escapeshellarg($this->tornadoBin);
        $this->command .= " ".escapeshellarg($this->runtime);
        $this->command .= " ".escapeshellarg($this->sharekill_param);
        $this->command .= " ".escapeshellarg($this->owner);
        $this->command .= " ".escapeshellarg($this->transferFilePath);
        $this->command .= " --responsefile ".escapeshellarg($this->transferFilePath);
        $this->command .= " --display_interval 1";
        $this->command .= " --max_download_rate ".escapeshellarg($this->drate);
        $this->command .= " --max_upload_rate ".escapeshellarg($this->rate);
        $this->command .= " --max_uploads ".escapeshellarg($this->maxuploads);
        $this->command .= " --minport ".escapeshellarg($this->port);
        $this->command .= " --maxport ".escapeshellarg($this->maxport);
        $this->command .= " --rerequest_interval ".escapeshellarg($this->rerequest);
        $this->command .= " --super_seeder ".escapeshellarg($this->superseeder);
        $this->command .= " --max_connections ".escapeshellarg($this->maxcons);
        $this->command .= $skipHashCheck;
		$this->command .= $filePrio;
		if (strlen($cfg["btclient_tornado_options"]) > 0)
			$this->command .= " ".$cfg["btclient_tornado_options"];
        $this->command .= " 1>> ".escapeshellarg($this->transferFilePath.".log");
        $this->command .= " 2>> ".escapeshellarg($this->transferFilePath.".log");
        $this->command .= " &";

        // start the client
        $this->_start();
    }

    /**
     * stops a client
     *
     * @param $transfer name of the transfer
     * @param $kill kill-param (optional)
     * @param $transferPid transfer Pid (optional)
     */
    function stop($transfer, $kill = false, $transferPid = 0) {
    	// set vars
		$this->_setVarsForTransfer($transfer);
        // stop the client
        $this->_stop($kill, $transferPid);
    }

	/**
	 * deletes a transfer
	 *
	 * @param $transfer name of the transfer
	 * @return boolean of success
	 */
	function delete($transfer) {
    	// set vars
		$this->_setVarsForTransfer($transfer);
		// delete
		$this->_delete();
	}

    /**
     * gets current transfer-vals of a transfer
     *
     * @param $transfer
     * @return array with downtotal and uptotal
     */
    function getTransferCurrent($transfer) {
    	global $transfers;
        // transfer from stat-file
        $sf = new StatFile($transfer);
        return array("uptotal" => $sf->uptotal, "downtotal" => $sf->downtotal);
    }

    /**
     * gets current transfer-vals of a transfer. optimized version
     *
     * @param $transfer
     * @param $tid of the transfer
     * @param $sfu stat-file-uptotal of the transfer
     * @param $sfd stat-file-downtotal of the transfer
     * @return array with downtotal and uptotal
     */
    function getTransferCurrentOP($transfer, $tid, $sfu, $sfd) {
        return array("uptotal" => $sfu, "downtotal" => $sfd);
    }

    /**
     * gets total transfer-vals of a transfer
     *
     * @param $transfer
     * @return array with downtotal and uptotal
     */
    function getTransferTotal($transfer) {
    	global $db, $transfers;
        $retVal = array();
        // transfer from db
        $sql = "SELECT uptotal,downtotal FROM tf_transfer_totals WHERE tid = '".getTransferHash($transfer)."'";
        $result = $db->Execute($sql);
        $row = $result->FetchRow();
        if (empty($row)) {
        	$retVal["uptotal"] = 0;
            $retVal["downtotal"] = 0;
        } else {
            $retVal["uptotal"] = $row["uptotal"];
            $retVal["downtotal"] = $row["downtotal"];
        }
        // transfer from stat-file
        $sf = new StatFile($transfer);
        $retVal["uptotal"] += $sf->uptotal;
        $retVal["downtotal"] += $sf->downtotal;
        return $retVal;
    }

    /**
     * gets total transfer-vals of a transfer. optimized version
     *
     * @param $transfer
     * @param $tid of the transfer
     * @param $sfu stat-file-uptotal of the transfer
     * @param $sfd stat-file-downtotal of the transfer
     * @return array with downtotal and uptotal
     */
    function getTransferTotalOP($transfer, $tid, $sfu, $sfd) {
        global $transfers;
        $retVal = array();
        $retVal["uptotal"] = (isset($transfers['totals'][$tid]['uptotal']))
        	? $transfers['totals'][$tid]['uptotal'] + $sfu
        	: $sfu;
        $retVal["downtotal"] = (isset($transfers['totals'][$tid]['downtotal']))
        	? $transfers['totals'][$tid]['downtotal'] + $sfd
        	: $sfd;
        return $retVal;
    }

    /**
     * set upload rate of a transfer
     *
     * @param $transfer
     * @param $uprate
     * @param $autosend
     */
    function setRateUpload($transfer, $uprate, $autosend = false) {
    	// set rate-field
    	$this->rate = $uprate;
    	// add command
		CommandHandler::add($transfer, "u".$uprate);
		// send command to client
        if ($autosend)
			CommandHandler::send($transfer);
    }

    /**
     * set download rate of a transfer
     *
     * @param $transfer
     * @param $downrate
     * @param $autosend
     */
    function setRateDownload($transfer, $downrate, $autosend = false) {
    	// set rate-field
    	$this->drate = $downrate;
    	// add command
		CommandHandler::add($transfer, "d".$downrate);
		// send command to client
        if ($autosend)
			CommandHandler::send($transfer);
    }

    /**
     * set runtime of a transfer
     *
     * @param $transfer
     * @param $runtime
     * @param $autosend
     * @return boolean
     */
    function setRuntime($transfer, $runtime, $autosend = false) {
    	// set runtime-field
    	$this->runtime = $runtime;
    	// add command
		CommandHandler::add($transfer, "r".(($this->runtime == "True") ? "1" : "0"));
		// send command to client
        if ($autosend)
			CommandHandler::send($transfer);
    }

    /**
     * set sharekill of a transfer
     *
     * @param $transfer
     * @param $sharekill
     * @param $autosend
     * @return boolean
     */
    function setSharekill($transfer, $sharekill, $autosend = false) {
		// set sharekill
        $this->sharekill = intval($sharekill);
        // recalc sharekill
		if ($this->_recalcSharekill() === false)
			return false;
    	// add command
		CommandHandler::add($transfer, "s".$this->sharekill_param);
		// send command to client
        if ($autosend)
			CommandHandler::send($transfer);
    	// return
    	return true;
    }

    /**
     * sets fields from default-vals
     *
     * @param $transfer
     */
    function settingsDefault($transfer = "") {
    	global $cfg;
    	// set vars
        if ($transfer != "")
        	$this->_setVarsForTransfer($transfer);
        $this->hash        = getTransferHash($this->transfer);
        $this->datapath    = getTransferDatapath($this->transfer);
    	$this->savepath    = getTransferSavepath($this->transfer);
    	$this->running     = 0;
		$this->rate        = $cfg["max_upload_rate"];
		$this->drate       = $cfg["max_download_rate"];
		$this->maxuploads  = $cfg["max_uploads"];
		$this->superseeder = $cfg["superseeder"];
		$this->runtime     = $cfg["torrent_dies_when_done"];
		$this->sharekill   = $cfg["sharekill"];
		$this->minport     = $cfg["minport"];
		$this->maxport     = $cfg["maxport"];
		$this->maxcons     = $cfg["maxcons"];
    }

}

?>