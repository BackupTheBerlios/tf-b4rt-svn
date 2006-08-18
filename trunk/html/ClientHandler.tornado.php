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
    //--------------------------------------------------------------------------
    /**
     * ctor
     */
    function ClientHandlerTornado($cfg) {
        $this->handlerName = "tornado";
        $this->version = array_shift(explode(" ",trim(array_pop(explode(":",'$Revision$')))));
        //
        $this->binSystem = "python";
        $this->binSocket = "python";
        //
        $this->Initialize($cfg);
        // efficient code :
        //$this->binClient = array_pop(explode("/",$this->cfg["btclient_tornado_bin"]));
        // compatible code (should work on flawed phps like 5.0.5+) :
        $uselessVar = explode("/",$this->cfg["btclient_tornado_bin"]);
        $this->binClient = array_pop($uselessVar);
    }

    //--------------------------------------------------------------------------
    /**
     * starts a client
     * @param $transfer name of the transfer
     * @param $interactive (1|0) : is this a interactive startup with dialog ?
     */
    function startClient($transfer, $interactive) {

        // do tornado special-pre-start-checks
        // check to see if the path to the python script is valid
        if (!is_file($this->cfg["btclient_tornado_bin"])) {
            AuditAction($this->cfg["constants"]["error"], "Error  Path for ".$this->cfg["btclient_tornado_bin"]." is not valid");
            if (IsAdmin()) {
                $this->status = -1;
                header("location: index.php?page=admin&op=configSettings");
                return;
            } else {
                $this->status = -1;
                $this->messages .= "Error TorrentFlux settings are not correct (path to python script is not valid) -- please contact an admin.";
                return;
            }
        }

        // prepare starting of client
        parent::prepareStartClient($transfer, $interactive);
        // prepare succeeded ?
        if ($this->status != 2) {
            $this->status = -1;
            $this->messages .= "Error parent::prepareStartClient(".$transfer.",".$interactive.") failed";
            return;
        }

        // build the command-string
        $skipHashCheck = "";
        if ((!(empty($this->skip_hash_check))) && (getTorrentDataSize($transfer) > 0))
            $skipHashCheck = " --check_hashes 0";
        $this->command = $this->runtime ." ".$this->sharekill_param ." ".$this->cfg["torrent_file_path"].$this->alias .".stat ".$this->owner ." --responsefile '".$this->cfg["torrent_file_path"].$this->transfer ."' --display_interval 5 --max_download_rate ".$this->drate ." --max_upload_rate ".$this->rate ." --max_uploads ".$this->maxuploads ." --minport ".$this->port ." --maxport ".$this->maxport ." --rerequest_interval ".$this->rerequest ." --super_seeder ".$this->superseeder ." --max_initiate ".$this->maxcons .$skipHashCheck;
        if(file_exists($this->cfg["torrent_file_path"].$this->alias.".prio")) {
            $priolist = explode(',',file_get_contents($this->cfg["torrent_file_path"].$this->alias .".prio"));
            $priolist = implode(',',array_slice($priolist,1,$priolist[0]));
            $this->command .= " --priority ".$priolist;
        }
        $this->command .= " ".$this->cfg["btclient_tornado_options"]." > /dev/null &";
        if (($this->cfg["AllowQueing"]) && ($this->queue == "1")) {
            //  This file is queued.
        } else {
    		// This file is started manually.
    		if (! array_key_exists("pythonCmd", $this->cfg)) {
    				insertSetting("pythonCmd","/usr/bin/python");
    		}
    		if (! array_key_exists("debugTorrents", $this->cfg)) {
    				insertSetting("debugTorrents", "0");
    		}
            $pyCmd = "";
			if (!$this->cfg["debugTorrents"]) {
					$pyCmd = $this->cfg["pythonCmd"] . " -OO";
			} else {
					$pyCmd = $this->cfg["pythonCmd"];
			}
			$this->command = "cd " . $this->savepath . "; HOME=".$this->cfg["path"]."; export HOME;". $this->umask ." nohup " . $this->nice . $pyCmd . " " .$this->cfg["btclient_tornado_bin"] . " " . $this->command;
		}
        // start the client
        parent::doStartClient();
    }

    //--------------------------------------------------------------------------
    /**
     * stops a client
     *
     * @param $transfer name of the transfer
     * @param $aliasFile alias-file of the transfer
     * @param $transferPid transfer Pid (optional)
     * @param $return return-param (optional)
     */
    function stopClient($transfer, $aliasFile, $transferPid = "", $return = "") {
        $this->pidFile = $this->cfg["torrent_file_path"].$aliasFile.".pid";
        // stop the client
        parent::doStopClient($transfer, $aliasFile, $transferPid, $return);
    }

    //--------------------------------------------------------------------------
    /**
     * print info of running clients
     *
     */
    function printRunningClientsInfo()  {
        return parent::printRunningClientsInfo();
    }

    //--------------------------------------------------------------------------
    /**
     * gets count of running clients
     *
     * @return client-count
     */
    function getRunningClientCount()  {
        return parent::getRunningClientCount();
    }

    //--------------------------------------------------------------------------
    /**
     * gets ary of running clients
     *
     * @return client-ary
     */
    function getRunningClients() {
        return parent::getRunningClients();
    }

    //--------------------------------------------------------------------------
    /**
     * deletes cache of a transfer
     *
     * @param $transfer
     */
    function deleteCache($transfer) {
        return;
    }

    //--------------------------------------------------------------------------
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
        $af = AliasFile::getAliasFileInstance($this->cfg["torrent_file_path"].$aliasName.".stat", $owner, $this->cfg, $this->handlerName);
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

    //--------------------------------------------------------------------------
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
        $af = AliasFile::getAliasFileInstance($this->cfg["torrent_file_path"].$aliasName.".stat", $owner, $this->cfg, $this->handlerName);
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