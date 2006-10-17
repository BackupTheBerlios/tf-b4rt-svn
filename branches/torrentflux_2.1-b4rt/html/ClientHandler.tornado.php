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
     * starts a bittorrent-client
     * @param $torrent name of the torrent
     * @param $interactive (1|0) : is this a interactive startup with dialog ?
     */
    function startTorrentClient($torrent, $interactive) {

        // do tornado special-pre-start-checks
        // check to see if the path to the python script is valid
        if (!is_file($this->cfg["btclient_tornado_bin"])) {
            AuditAction($this->cfg["constants"]["error"], "Error  Path for ".$this->cfg["btclient_tornado_bin"]." is not valid");
            if (IsAdmin()) {
                $this->status = -1;
                header("location: admin.php?op=configSettings");
                return;
            } else {
                $this->status = -1;
                $this->messages .= "<b>Error</b> TorrentFlux settings are not correct (path to python script is not valid) -- please contact an admin.<br>";
                return;
            }
        }

        // prepare starting of client
        parent::prepareStartTorrentClient($torrent, $interactive);
        // prepare succeeded ?
        if ($this->status != 2) {
            $this->status = -1;
            $this->messages .= "<b>Error</b> parent::prepareStartTorrentClient(".$torrent.",".$interactive.") failed<br>";
            return;
        }

        // build the command-string
        $skipHashCheck = "";
        if ((!(empty($this->skip_hash_check))) && (getTorrentDataSize($torrent) > 0))
            $skipHashCheck = " --check_hashes 0";
        $this->command = $this->runtime;
        $this->command .= " ".$this->sharekill_param;
        $this->command .= " ".escapeshellarg($this->cfg["torrent_file_path"].$this->alias.".stat");
        $this->command .= " ".$this->owner;
        $this->command .= " --responsefile ".escapeshellarg($this->cfg["torrent_file_path"].$this->torrent);
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
        if(file_exists($this->cfg["torrent_file_path"].$this->alias.".prio")) {
            $priolist = explode(',',file_get_contents($this->cfg["torrent_file_path"].$this->alias .".prio"));
            $priolist = implode(',',array_slice($priolist,1,$priolist[0]));
            $this->command .= " --priority ".$priolist;
        }
		if (strlen($this->cfg["btclient_tornado_options"]) > 0)
			$this->command .= " ".$this->cfg["btclient_tornado_options"];
        $this->command .= " > /dev/null &";
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
			$this->command = "cd " . escapeshellarg($this->savepath) . "; HOME=".escapeshellarg($this->cfg["path"])."; export HOME;". $this->umask ." nohup " . $this->nice . $pyCmd . " " .escapeshellarg($this->cfg["btclient_tornado_bin"]) . " " . $this->command;
		}
        // start the client
        parent::doStartTorrentClient();
    }

    //--------------------------------------------------------------------------
    /**
     * stops a bittorrent-client
     *
     * @param $torrent name of the torrent
     * @param $aliasFile alias-file of the torrent
     * @param $torrentPid torrent Pid (optional)
     * @param $return return-param (optional)
     */
    function stopTorrentClient($torrent, $aliasFile, $torrentPid = "", $return = "") {
        $this->pidFile = $this->cfg["torrent_file_path"].$aliasFile.".pid";
        // stop the client
        parent::doStopTorrentClient($torrent, $aliasFile, $torrentPid, $return);
    }

    //--------------------------------------------------------------------------
    /**
     * print info of running bittorrent-clients
     *
     */
    function printRunningClientsInfo()  {
        return parent::printRunningClientsInfo();
    }

    //--------------------------------------------------------------------------
    /**
     * gets count of running bittorrent-clients
     *
     * @return client-count
     */
    function getRunningClientCount()  {
        return parent::getRunningClientCount();
    }

    //--------------------------------------------------------------------------
    /**
     * gets ary of running bittorrent-clients
     *
     * @return client-ary
     */
    function getRunningClients() {
        return parent::getRunningClients();
    }

    //--------------------------------------------------------------------------
    /**
     * deletes cache of a torrent
     *
     * @param $torrent
     */
    function deleteTorrentCache($torrent) {
        return;
    }

    //--------------------------------------------------------------------------
    /**
     * gets current transfer-vals of a torrent
     *
     * @param $torrent
     * @return array with downtotal and uptotal
     */
    function getTorrentTransferCurrent($torrent) {
        $retVal = array();
        // transfer from stat-file
        $aliasName = getAliasName($torrent);
        $owner = getOwner($torrent);
        $af = AliasFile::getAliasFileInstance($this->cfg["torrent_file_path"].$aliasName.".stat", $owner, $this->cfg, $this->handlerName);
        $retVal["uptotal"] = $af->uptotal+0;
        $retVal["downtotal"] = $af->downtotal+0;
        return $retVal;
    }

    /**
     * gets current transfer-vals of a torrent. optimized index-page-version
     *
     * @param $torrent
     * @param $afu alias-file-uptotal of the torrent
     * @param $afd alias-file-downtotal of the torrent
     * @return array with downtotal and uptotal
     */
    function getTorrentTransferCurrentOP($torrent, $afu, $afd)  {
        $retVal = array();
        // transfer from stat-file
        $retVal["uptotal"] = $afu;
        $retVal["downtotal"] = $afd;
        return $retVal;
    }

    //--------------------------------------------------------------------------
    /**
     * gets total transfer-vals of a torrent
     *
     * @param $torrent
     * @return array with downtotal and uptotal
     */
    function getTorrentTransferTotal($torrent) {
    	global $db;
        $retVal = array();
        // transfer from db
        $torrentId = getTorrentHash($torrent);
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
        $aliasName = getAliasName($torrent);
        $owner = getOwner($torrent);
        $af = AliasFile::getAliasFileInstance($this->cfg["torrent_file_path"].$aliasName.".stat", $owner, $this->cfg, $this->handlerName);
        $retVal["uptotal"] += ($af->uptotal+0);
        $retVal["downtotal"] += ($af->downtotal+0);
        return $retVal;
    }

    /**
     * gets total transfer-vals of a torrent. optimized index-page-version
     *
     * @param $torrent
     * @param $afu alias-file-uptotal of the torrent
     * @param $afd alias-file-downtotal of the torrent
     * @return array with downtotal and uptotal
     */
    function getTorrentTransferTotalOP($torrent, $afu, $afd) {
        global $db;
        $retVal = array();
        // transfer from db
        $torrentId = getTorrentHash($torrent);
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
        $retVal["uptotal"] += $afu;
        $retVal["downtotal"] += $afd;
        return $retVal;
    }

}

?>