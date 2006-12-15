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
 * class ClientHandler for mainline-client
 */
class ClientHandlerMainline extends ClientHandler
{

	// public fields

	// mainline-bin
	var $mainlineBin = "";

	// =========================================================================
	// ctor
	// =========================================================================

    /**
     * ctor
     */
    function ClientHandlerMainline() {
    	global $cfg;
        $this->handlerName = "mainline";
        $this->binSystem = "python";
        $this->binSocket = "python";
        $this->binClient = "tfmainline.py";
        $this->mainlineBin = $cfg["docroot"]."bin/TF_Mainline/tfmainline.py";
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
		$this->setVarsFromTransfer($transfer);

        // do mainline special-pre-start-checks
        // check to see if the path to the python script is valid
        if (!is_file($this->mainlineBin)) {
        	$this->state = CLIENTHANDLER_STATE_ERROR;
            $msg = "path for tfmainline.py is not valid : ".$this->mainlineBin;
            array_push($this->messages , $msg);
            AuditAction($cfg["constants"]["error"], $msg);
            if (empty($_REQUEST))
            	die($msg);
            else
				showErrorPage($msg);
        }

        // prepare starting of client
        $this->prepareStart($interactive, $enqueue);

		// only continue if prepare succeeded (skip start / error)
		if ($this->state != CLIENTHANDLER_STATE_READY) {
			if ($this->state == CLIENTHANDLER_STATE_ERROR)
				array_push($this->messages , "Error after call to prepareStart(".$transfer.",".$interactive.",".$enqueue.")");
			return;
		}

		// build the command-string
		// note : order of args must not change for ps-parsing-code in
		// RunningTransferMainline
		$this->command  = "cd ".escapeshellarg($this->savepath).";";
		$this->command .= " HOME=".escapeshellarg($cfg["path"]);
		$this->command .= "; export HOME;";
		$this->command .= $this->umask;
		$this->command .= " nohup ";
		$this->command .= $this->nice;
		$this->command .= $cfg["pythonCmd"] . " -OO" . " " .escapeshellarg($this->mainlineBin);
		$this->command .= " --display_interval 5";
		$this->command .= " --tf_owner ".$this->owner;
		$this->command .= " --stat_file ".escapeshellarg($this->aliasFilePath);
		$this->command .= " --save_incomplete_in ".escapeshellarg($this->savepath);
		$this->command .= " --save_in ".escapeshellarg($this->savepath);
		$this->command .= " --die_when_done ".escapeshellarg($this->runtime);
		$this->command .= " --seed_limit ".escapeshellarg($this->sharekill_param);
		$this->command .= ($this->drate != 0)
			? " --max_download_rate " . escapeshellarg($this->drate * 1024)
			: " --max_download_rate 125000000"; // 1 GBit local net = 125MB/s
		$this->command .= ($this->rate != 0)
			? " --max_upload_rate " . escapeshellarg($this->rate * 1024)
			: " --max_upload_rate 125000000"; // 1 GBit local net = 125MB/s
		$this->command .= " --max_uploads ".escapeshellarg($this->maxuploads);
		$this->command .= " --minport ".escapeshellarg($this->port);
		$this->command .= " --maxport ".escapeshellarg($this->maxport);
		$this->command .= " --rerequest_interval ".escapeshellarg($this->rerequest);
		$this->command .= " --max_initiate ".escapeshellarg($this->maxcons);
		if ((!(empty($this->skip_hash_check))) && (getTorrentDataSize($this->transfer) > 0))
			$this->command .= " --no_check_hashes";
		if (strlen($cfg["btclient_mainline_options"]) > 0)
			$this->command .= " ".$cfg["btclient_mainline_options"];
		$this->command .= " ".escapeshellarg($this->transferFilePath);
        $this->command .= " 1>> ".escapeshellarg($this->logFilePath);
        $this->command .= " 2>> ".escapeshellarg($this->logFilePath);
        $this->command .= " &";

		// start the client
		$this->execStart(true, true);
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
		$this->setVarsFromTransfer($transfer);
        // stop the client
        $this->execStop($kill, $transferPid);
    }

	/**
	 * deletes a transfer
	 *
	 * @param $transfer name of the transfer
	 * @return boolean of success
	 */
	function delete($transfer) {
    	// set vars
		$this->setVarsFromTransfer($transfer);
		// delete
		$this->execDelete(true);
	}

    /**
     * gets current transfer-vals of a transfer
     *
     * @param $transfer
     * @return array with downtotal and uptotal
     */
    function getTransferCurrent($transfer) {
        // transfer from stat-file
        $af = new AliasFile(getAliasName($transfer).".stat", getOwner($transfer));
        return array("uptotal" => $af->uptotal, "downtotal" => $af->downtotal);
    }

    /**
     * gets current transfer-vals of a transfer. optimized version
     *
     * @param $transfer
     * @param $tid of the transfer
     * @param $afu alias-file-uptotal of the transfer
     * @param $afd alias-file-downtotal of the transfer
     * @return array with downtotal and uptotal
     */
    function getTransferCurrentOP($transfer, $tid, $afu, $afd) {
        return array("uptotal" => $afu, "downtotal" => $afd);
    }

    /**
     * gets total transfer-vals of a transfer
     *
     * @param $transfer
     * @return array with downtotal and uptotal
     */
    function getTransferTotal($transfer) {
    	global $db;
        $retVal = array();
        // transfer from db
        $sql = "SELECT uptotal,downtotal FROM tf_torrent_totals WHERE tid = '".getTorrentHash($transfer)."'";
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
        $af = new AliasFile(getAliasName($transfer).".stat", getOwner($transfer));
        $retVal["uptotal"] += $af->uptotal;
        $retVal["downtotal"] += $af->downtotal;
        return $retVal;
    }

    /**
     * gets total transfer-vals of a transfer. optimized version
     *
     * @param $transfer
     * @param $tid of the transfer
     * @param $afu alias-file-uptotal of the transfer
     * @param $afd alias-file-downtotal of the transfer
     * @return array with downtotal and uptotal
     */
    function getTransferTotalOP($transfer, $tid, $afu, $afd) {
        global $db;
        $retVal = array();
        // transfer from db
        $sql = "SELECT uptotal,downtotal FROM tf_torrent_totals WHERE tid = '".$tid."'";
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
        $retVal["uptotal"] += $afu;
        $retVal["downtotal"] += $afd;
        return $retVal;
    }

}

?>