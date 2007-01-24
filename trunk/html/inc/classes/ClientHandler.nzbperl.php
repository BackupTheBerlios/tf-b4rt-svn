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
 * class ClientHandler for nzbperl-client
 */
class ClientHandlerNzbperl extends ClientHandler
{

	// public fields

	// nzbperl bin
	var $nzbbin = "";

	// =====================================================================
	// ctor
	// =====================================================================

    /**
     * ctor
     */
	function ClientHandlerNzbperl() {
		global $cfg;
		$this->type = "nzb";
		$this->client = "nzbperl";
        $this->binSystem = "perl";
        $this->binSocket = "perl";
        $this->binClient = "tfnzbperl.pl";
		$this->nzbbin = $cfg["docroot"]."bin/clients/nzbperl/tfnzbperl.pl";
	}

	// =====================================================================
	// Public Methods
	// =====================================================================

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

		// do nzbperl special-pre-start-checks
		// check to see if the path to the nzbperl script is valid
		if (!is_file($this->nzbbin)) {
			$this->state = CLIENTHANDLER_STATE_ERROR;
			$msg = "path for tfnzbperl.pl is not valid";
			AuditAction($cfg["constants"]["error"], $msg);
			$this->logMessage($msg."\n", true);
			array_push($this->messages, $msg);
			array_push($this->messages, "nzbbin : ".$this->nzbbin);
            // write error to stat
			$sf = new StatFile($this->transfer, $this->owner);
			$sf->time_left = 'Error';
			$sf->write();
			// return
			return false;
		}

		// init starting of client
        $this->_init($interactive, $enqueue, false, false);

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

		// Build Command String (do not change order of last args !)
		$this->command  = "cd ".escapeshellarg($this->savepath).";";
		$this->command .= " HOME=".escapeshellarg(substr($cfg["path"], 0, -1));
		$this->command .= "; export HOME;";
		$this->command .= $this->umask;
		$this->command .= " nohup ";
		$this->command .= $this->nice;
		$this->command .= $cfg['perlCmd'];
		$this->command .= " -I ".$cfg["docroot"]."bin/lib";
		$this->command .= " ".escapeshellarg($this->nzbbin);
		$this->command .= " --conn ".escapeshellarg($cfg['nzbperl_conn']);
		$this->command .= " --uudeview ".escapeshellarg($cfg["bin_uudeview"]);
		$this->command .= ($cfg['nzbperl_badAction'])
			? " --insane"
			: " --dropbad";
		switch ($cfg['nzbperl_create']) {
			case 1:
				$this->command .= " --dlcreate";
				break;
			case 2:
				$this->command .= " --dlcreategrp";
				break;
		}
		$this->command .= " --dthreadct ".escapeshellarg($cfg['nzbperl_threads']);
		$this->command .= " --speed ".escapeshellarg($this->drate);
		$this->command .= " --server ".escapeshellarg($cfg['nzbperl_server']);
		if ($cfg['nzbperl_user'] != "") {
			$this->command .= " --user ".escapeshellarg($cfg['nzbperl_user']);
			$this->command .= " --pw ".escapeshellarg($cfg['nzbperl_pw']);
		}
		if (strlen($cfg["nzbperl_options"]) > 0)
			$this->command .= " ".$cfg['nzbperl_options'];
		// do NOT change anything below (not even order)
		$this->command .= " --dlpath ".escapeshellarg($this->savepath);
		$this->command .= " --tfuser ".escapeshellarg($this->owner);
		$this->command .= " ".escapeshellarg($this->transferFilePath);
        $this->command .= " 1>> ".escapeshellarg($this->transferFilePath.".log");
        $this->command .= " 2>> ".escapeshellarg($this->transferFilePath.".log");
        $this->command .= " &";

		// state
		$this->state = CLIENTHANDLER_STATE_READY;

		// Start the client
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
     * deletes the transfer
     *
     * @param $transfer name of the transfer
     * @return boolean on success
     */
    function delete($transfer) {
		//set vars
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
		$this->rate        = 0;
		$this->drate       = $cfg["nzbperl_rate"];
		$this->maxuploads  = 1;
		$this->superseeder = 0;
		$this->runtime     = "True";
		$this->sharekill   = 0;
		$this->minport     = 1;
		$this->maxport     = 65535;
		$this->maxcons     = $cfg["nzbperl_conn"];
    }

}

?>