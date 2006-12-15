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

// class ClientHandler for transmission-client
class ClientHandlerTransmission extends ClientHandler
{

    /**
     * ctor
     */
    function ClientHandlerTransmission() {
        $this->handlerName = "transmission";
        $this->binSystem = "transmissioncli";
        $this->binSocket = "transmissionc";
        $this->binClient = "transmissioncli";
    }

    /**
     * starts a client
     *
     * @param $transfer name of the transfer
     * @param $interactive (boolean) : is this a interactive startup with dialog ?
     * @param $enqueue (boolean) : enqueue ?
     */
    function start($transfer, $interactive = false, $enqueue = false) {
    	global $cfg;

        // do transmission special-pre-start-checks
        // check to see if the path to the transmission-bin is valid
        if (!is_executable($cfg["btclient_transmission_bin"])) {
        	$this->state = CLIENTHANDLER_STATE_ERROR;
            $msg = "transmissioncli cannot be executed : ".$cfg["btclient_transmission_bin"];
            array_push($this->messages , $msg);
            AuditAction($cfg["constants"]["error"], $msg);
            if (empty($_REQUEST))
            	die($msg);
            else
				showErrorPage($msg);
        }

        // prepare starting of client
        $this->prepareStart($transfer, $interactive, $enqueue);

		// only continue if prepare succeeded (skip start / error)
		if ($this->state != CLIENTHANDLER_STATE_READY) {
			if ($this->state == CLIENTHANDLER_STATE_ERROR)
				array_push($this->messages , "Error after call to prepareStart(".$transfer.",".$interactive.",".$enqueue.")");
			return;
		}

        // transmission wants -1 for no seeding.
        if ($this->sharekill == -1)
            $this->sharekill_param = -1;

        // pid-file
        $this->pidFile = $cfg["transfer_file_path"].$this->alias.".stat.pid";

        // workaround for bsd-pid-file-problem : touch file first
        if (($this->queue == 0) && ($cfg["_OS"] == 2))
        	@touch($this->pidFile);

        // build the command-string
		// note : order of args must not change for ps-parsing-code in
		// RunningTransferTransmission
        $this->command  = "cd ".escapeshellarg($this->savepath).";";
        $this->command .= " HOME=".escapeshellarg($cfg["path"])."; export HOME;".
        $this->command .= $this->umask;
        $this->command .= " nohup ";
        $this->command .= $this->nice;
        $this->command .= escapeshellarg($cfg["btclient_transmission_bin"]);
        $this->command .= " -t ".escapeshellarg($cfg["transfer_file_path"].$this->alias.".stat");
        $this->command .= " -w ".$this->owner;
        $this->command .= " -z ".escapeshellarg($this->pidFile);
        $this->command .= " -e 5";
        $this->command .= " -c ".escapeshellarg($this->sharekill_param);
        $this->command .= " -d ".escapeshellarg($this->drate);
        $this->command .= " -u ".escapeshellarg($this->rate);
        $this->command .= " -p ".escapeshellarg($this->port);
        if (strlen($cfg["btclient_transmission_options"]) > 0)
        	$this->command .= " ".$cfg["btclient_transmission_options"];
        $this->command .= " ".escapeshellarg($cfg["transfer_file_path"].$this->transfer);
        $this->command .= " 1>> ".escapeshellarg($this->logFile);
        $this->command .= " 2>> ".escapeshellarg($this->logFile);
        $this->command .= " &";

        // <begin shell> to write the pid of the client into the pid-file
        // * b4rt :
        //$this->command .= " &> /dev/null & echo $! > ".escapeshellarg($this->pidFile);
        // * lord_nor :
        //$this->command .= " > /dev/null & echo $! & > ".escapeshellarg($this->pidFile);
        // <end shell>
        // start the client
        $this->execStart(true, true);
    }

    /**
     * stops a client
     *
     * @param $transfer name of the transfer
     * @param $aliasFile alias-file of the transfer
     * @param $kill kill-param (optional)
     * @param $transferPid transfer Pid (optional)
     */
    function stop($transfer, $aliasFile, $kill = false, $transferPid = 0) {
    	global $cfg;
        $this->pidFile = $cfg["transfer_file_path"].$aliasFile.".pid";
        // stop the client
        $this->execStop($transfer, $aliasFile, $kill, $transferPid);
        // delete the pid file
        // included in transmissioncli
        @unlink($this->pidFile);
    }

    /**
     * deletes cache of a transfer
     *
     * @param $transfer
     */
    function deleteCache($transfer) {
    	global $cfg;
        $torrentId = getTorrentHash($transfer);
        @unlink($cfg["path"].".transmission/cache/resume.".$torrentId);
        return;
    }

    /**
     * gets current transfer-vals of a transfer
     *
     * @param $transfer
     * @return array with downtotal and uptotal
     */
    function getTransferCurrent($transfer) {
    	global $db;
        $retVal = array();
        // transfer from stat-file
        $aliasName = getAliasName($transfer);
        $owner = getOwner($transfer);
        $af = new AliasFile($aliasName.".stat", $owner);
        $retVal["uptotal"] = $af->uptotal;
        $retVal["downtotal"] = $af->downtotal;
        // transfer from db
        $torrentId = getTorrentHash($transfer);
        $sql = "SELECT uptotal,downtotal FROM tf_torrent_totals WHERE tid = '".$torrentId."'";
        $result = $db->Execute($sql);
    	showError($db, $sql);
        $row = $result->FetchRow();
        if (! empty($row)) {
            $retVal["uptotal"] -= $row["uptotal"];
            $retVal["downtotal"] -= $row["downtotal"];
        }
        return $retVal;
    }

    /**
     * gets current transfer-vals of a transfer. optimized index-page-version
     *
     * @param $transfer
     * @param $tid of the transfer
     * @param $afu alias-file-uptotal of the transfer
     * @param $afd alias-file-downtotal of the transfer
     * @return array with downtotal and uptotal
     */
    function getTransferCurrentOP($transfer, $tid, $afu, $afd) {
        global $db;
        $retVal = array();
        // transfer from stat-file
        $retVal["uptotal"] = $afu;
        $retVal["downtotal"] = $afd;
        // transfer from db
        $sql = "SELECT uptotal,downtotal FROM tf_torrent_totals WHERE tid = '".$tid."'";
        $result = $db->Execute($sql);
    	showError($db, $sql);
        $row = $result->FetchRow();
        if (! empty($row)) {
            $retVal["uptotal"] -= $row["uptotal"];
            $retVal["downtotal"] -= $row["downtotal"];
        }
        return $retVal;
    }

    /**
     * gets total transfer-vals of a transfer
     *
     * @param $transfer
     * @return array with downtotal and uptotal
     */
    function getTransferTotal($transfer) {
        $retVal = array();
        // transfer from stat-file
        $aliasName = getAliasName($transfer);
        $owner = getOwner($transfer);
        $af = new AliasFile($aliasName.".stat", $owner);
        $retVal["uptotal"] = $af->uptotal;
        $retVal["downtotal"] = $af->downtotal;
        return $retVal;
    }

    /**
     * gets total transfer-vals of a transfer. optimized index-page-version
     *
     * @param $transfer
     * @param $tid of the transfer
     * @param $afu alias-file-uptotal of the transfer
     * @param $afd alias-file-downtotal of the transfer
     * @return array with downtotal and uptotal
     */
    function getTransferTotalOP($transfer, $tid, $afu, $afd) {
        $retVal = array();
        // transfer from stat-file
        $retVal["uptotal"] = $afu;
        $retVal["downtotal"] = $afd;
        return $retVal;
    }
}

?>