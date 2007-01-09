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
 * class ClientHandler for transmission-client
 */
class ClientHandlerTransmission extends ClientHandler
{

	// =========================================================================
	// ctor
	// =========================================================================

    /**
     * ctor
     */
    function ClientHandlerTransmission() {
        $this->handlerName = "transmission";
        $this->binSystem = "transmissioncli";
        $this->binSocket = "transmissionc";
        $this->binClient = "transmissioncli";
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

    	// log
    	$this->logMessage($this->handlerName."-start : ".$transfer."\n", true);

        // do transmission special-pre-start-checks
        // check to see if the path to the transmission-bin is valid
        if (!is_executable($cfg["btclient_transmission_bin"])) {
        	$this->state = CLIENTHANDLER_STATE_ERROR;
        	$msg = "transmissioncli cannot be executed";
        	AuditAction($cfg["constants"]["error"], $msg);
        	$this->logMessage($msg."\n", true);
        	array_push($this->messages, $msg);
            array_push($this->messages, "btclient_transmission_bin : ".$cfg["btclient_transmission_bin"]);
            return false;
        }

        // prepare starting of client
        $this->prepareStart($interactive, $enqueue);

		// only continue if prepare succeeded (skip start / error)
		if ($this->state != CLIENTHANDLER_STATE_READY) {
			if ($this->state == CLIENTHANDLER_STATE_ERROR) {
				$msg = "Error after call to prepareStart(".$transfer.",".$interactive.",".$enqueue.")";
				array_push($this->messages , $msg);
				$this->logMessage($msg."\n", true);
			}
			return false;
		}

        // transmission wants -1 for no seeding.
        if ($this->sharekill == -1)
            $this->sharekill_param = -1;

        // workaround for bsd-pid-file-problem : touch file first
        if ((!$this->queue) && ($cfg["_OS"] == 2))
        	@touch($this->pidFilePath);

        // build the command-string
		// note : order of args must not change for ps-parsing-code in
		// RunningTransferTransmission
        $this->command  = "cd ".escapeshellarg($this->savepath).";";
        $this->command .= " HOME=".escapeshellarg($cfg["path"])."; export HOME;".
        $this->command .= $this->umask;
        $this->command .= " nohup ";
        $this->command .= $this->nice;
        $this->command .= escapeshellarg($cfg["btclient_transmission_bin"]);
        $this->command .= " -o ".$this->owner;
        $this->command .= " -e 5";
        $this->command .= " -c ".escapeshellarg($this->sharekill_param);
        $this->command .= " -d ".escapeshellarg($this->drate);
        $this->command .= " -u ".escapeshellarg($this->rate);
        $this->command .= " -p ".escapeshellarg($this->port);
        if (strlen($cfg["btclient_transmission_options"]) > 0)
        	$this->command .= " ".$cfg["btclient_transmission_options"];
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
		$this->execDelete(true, true);
	}

    /**
     * deletes cache of a transfer
     */
    function execDeleteCache() {
    	global $cfg;
        @unlink($cfg["path"].".transmission/cache/resume.".getTorrentHash($this->transfer));
        return;
    }

    /**
     * gets current transfer-vals of a transfer
     *
     * @param $transfer
     * @return array with downtotal and uptotal
     */
    function getTransferCurrent($transfer) {
    	global $db, $transfers;
        $retVal = array();
        // transfer from stat-file
		$af = new AliasFile(getAliasName($transfer).".stat", getOwner($transfer));
        $retVal["uptotal"] = $af->uptotal;
        $retVal["downtotal"] = $af->downtotal;
        // transfer from db
        $torrentId = getTorrentHash($transfer);
        $sql = "SELECT uptotal,downtotal FROM tf_torrent_totals WHERE tid = '".$torrentId."'";
        $result = $db->Execute($sql);
        $row = $result->FetchRow();
        if (!empty($row)) {
            $retVal["uptotal"] -= $row["uptotal"];
            $retVal["downtotal"] -= $row["downtotal"];
        }
        return $retVal;
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
        global $transfers;
        $retVal = array();
        $retVal["uptotal"] = (isset($transfers['totals'][$tid]['uptotal']))
        	? $afu - $transfers['totals'][$tid]['uptotal']
        	: $afu;
        $retVal["downtotal"] = (isset($transfers['totals'][$tid]['downtotal']))
        	? $afd - $transfers['totals'][$tid]['downtotal']
        	: $afd;
        return $retVal;
    }

    /**
     * gets total transfer-vals of a transfer
     *
     * @param $transfer
     * @return array with downtotal and uptotal
     */
    function getTransferTotal($transfer) {
    	global $transfers;
        // transfer from stat-file
        $af = new AliasFile(getAliasName($transfer).".stat", getOwner($transfer));
        return array("uptotal" => $af->uptotal, "downtotal" => $af->downtotal);
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
        return array("uptotal" => $afu, "downtotal" => $afd);
    }
}

?>