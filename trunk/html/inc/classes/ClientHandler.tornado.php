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

// class ClientHandler for tornado-client
class ClientHandlerTornado extends ClientHandler
{
	// tornado-bin
	var $tornadoBin = "";

    /**
     * ctor
     */
    function ClientHandlerTornado($cfg) {
        $this->handlerName = "tornado";
        // version
		$uselessVar = array_shift(explode(" ",trim(array_pop(explode(":",'$Revision$')))));
		$this->version = $uselessVar;
        //
        $this->binSystem = "python";
        $this->binSocket = "python";
        $this->binClient = "btphptornado.py";
        //
        $this->initialize($cfg);
        //
        $this->tornadoBin = $this->cfg["docroot"]."bin/TF_BitTornado/btphptornado.py";
    }

    /**
     * starts a client
     * @param $transfer name of the transfer
     * @param $interactive (1|0) : is this a interactive startup with dialog ?
     * @param $enqueue (boolean) : enqueue ?
     */
    function startClient($transfer, $interactive, $enqueue) {

        // do tornado special-pre-start-checks
        // check to see if the path to the python script is valid
        if (!is_file($this->tornadoBin)) {
            AuditAction($this->cfg["constants"]["error"], "Error  Path for ".$this->tornadoBin." is not valid");
            if ($this->cfg['isAdmin']) {
                $this->status = -1;
                header("location: admin.php?op=serverSettings");
                return;
            } else {
                $this->status = -1;
                $this->messages .= "Error TorrentFlux settings are not correct (path to python script is not valid) -- please contact an admin.";
                return;
            }
        }

        // prepare starting of client
        parent::prepareStartClient($transfer, $interactive, $enqueue);
        // prepare succeeded ?
        if ($this->status != 2) {
            $this->status = -1;
            $this->messages .= "Error parent::prepareStartClient(".$transfer.",".$interactive.",".$enqueue.") failed";
            return;
        }

		// pythonCmd
		$pyCmd = $this->cfg["pythonCmd"] . " -OO";

        // build the command-string
        $skipHashCheck = "";
        if ((!(empty($this->skip_hash_check))) && (getTorrentDataSize($transfer) > 0))
            $skipHashCheck = " --check_hashes 0";
        $filePrio = "";
        if(file_exists($this->cfg["transfer_file_path"].$this->alias.".prio")) {
            $priolist = explode(',',file_get_contents($this->cfg["transfer_file_path"].$this->alias .".prio"));
            $priolist = implode(',',array_slice($priolist,1,$priolist[0]));
            $filePrio = " --priority ".$priolist;
        }

		// note :
		// order of args must not change for ps-parsing-code in
		// RunningTransferTornado

		$this->command = "cd ".escapeshellarg($this->savepath).";";
		$this->command .= " HOME=".escapeshellarg($this->cfg["path"]);
		$this->command .= "; export HOME;";
		$this->command .= $this->umask;
		$this->command .= " nohup ";
		$this->command .= $this->nice;
		$this->command .= $pyCmd . " " .escapeshellarg($this->tornadoBin);
        $this->command .= " ".$this->runtime;
        $this->command .= " ".$this->sharekill_param;
        $this->command .= " ".escapeshellarg($this->cfg["transfer_file_path"].$this->alias .".stat");
        $this->command .= " ".$this->owner;
        $this->command .= " --responsefile ".escapeshellarg($this->cfg["transfer_file_path"].$this->transfer);
        $this->command .= " --display_interval 5";
        $this->command .= " --max_download_rate ".$this->drate;
        $this->command .= " --max_upload_rate ".$this->rate;
        $this->command .= " --max_uploads ".$this->maxuploads;
        $this->command .= " --minport ".$this->port;
        $this->command .= " --maxport ".$this->maxport;
        $this->command .= " --rerequest_interval ".$this->rerequest;
        $this->command .= " --super_seeder ".$this->superseeder;
        $this->command .= " --max_connections ".$this->maxcons;
        $this->command .= $skipHashCheck;
		$this->command .= $filePrio;
		if (strlen($this->cfg["btclient_tornado_options"]) > 0)
			$this->command .= " ".$this->cfg["btclient_tornado_options"];
        $this->command .= " > /dev/null &";

        // start the client
        parent::doStartClient();
    }

    /**
     * stops a client
     *
     * @param $transfer name of the transfer
     * @param $aliasFile alias-file of the transfer
     * @param $transferPid transfer Pid (optional)
     * @param $return return-param (optional)
     */
    function stopClient($transfer, $aliasFile, $transferPid = "", $return = "") {
        $this->pidFile = $this->cfg["transfer_file_path"].$aliasFile.".pid";
        // stop the client
        parent::doStopClient($transfer, $aliasFile, $transferPid, $return);
    }

    /**
     * get info of running clients
     *
     */
    function getRunningClientsInfo()  {
        return parent::getRunningClientsInfo();
    }

    /**
     * gets count of running clients
     *
     * @return client-count
     */
    function getRunningClientCount()  {
        return parent::getRunningClientCount();
    }

    /**
     * gets ary of running clients
     *
     * @return client-ary
     */
    function getRunningClients() {
        return parent::getRunningClients();
    }

    /**
     * deletes cache of a transfer
     *
     * @param $transfer
     */
    function deleteCache($transfer) {
        return;
    }

    /**
     * gets current transfer-vals of a transfer
     *
     * @param $transfer
     * @return array with downtotal and uptotal
     */
    function getTransferCurrent($transfer) {
        $retVal = array();
        // transfer from stat-file
        $aliasName = getAliasName($transfer);
        $owner = getOwner($transfer);
        $af = AliasFile::getAliasFileInstance($this->cfg["transfer_file_path"].$aliasName.".stat", $owner, $this->cfg, $this->handlerName);
        $retVal["uptotal"] = $af->uptotal+0;
        $retVal["downtotal"] = $af->downtotal+0;
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
        $retVal = array();
        // transfer from stat-file
        $retVal["uptotal"] = $afu;
        $retVal["downtotal"] = $afd;
        return $retVal;
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
        $torrentId = getTorrentHash($transfer);
        $sql = "SELECT uptotal,downtotal FROM tf_torrent_totals WHERE tid = '".$torrentId."'";
        $result = $db->Execute($sql);
    	showError($db, $sql);
        $row = $result->FetchRow();
        if (!empty($row)) {
            $retVal["uptotal"] = $row["uptotal"];
            $retVal["downtotal"] = $row["downtotal"];
        } else {
            $retVal["uptotal"] = 0;
            $retVal["downtotal"] = 0;
        }
        // transfer from stat-file
        $aliasName = getAliasName($transfer);
        $owner = getOwner($transfer);
        $af = AliasFile::getAliasFileInstance($this->cfg["transfer_file_path"].$aliasName.".stat", $owner, $this->cfg, $this->handlerName);
        $retVal["uptotal"] += ($af->uptotal+0);
        $retVal["downtotal"] += ($af->downtotal+0);
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
        global $db;
        $retVal = array();
        // transfer from db
        $sql = "SELECT uptotal,downtotal FROM tf_torrent_totals WHERE tid = '".$tid."'";
        $result = $db->Execute($sql);
    	showError($db, $sql);
        $row = $result->FetchRow();
        if (!empty($row)) {
            $retVal["uptotal"] = $row["uptotal"];
            $retVal["downtotal"] = $row["downtotal"];
        } else {
            $retVal["uptotal"] = 0;
            $retVal["downtotal"] = 0;
        }
        // transfer from stat-file
        $retVal["uptotal"] += $afu;
        $retVal["downtotal"] += $afd;
        return $retVal;
    }

}

?>