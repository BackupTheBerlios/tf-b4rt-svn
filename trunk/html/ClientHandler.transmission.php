<?php

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
    //--------------------------------------------------------------------------
    /**
     * ctor
     */
    function ClientHandlerTransmission($cfg) {
        $this->handlerName = "transmission";
        $this->version = array_shift(explode(" ",trim(array_pop(explode(":",'$Revision$')))));
        //
        $this->binSocket = "transmissionc";
        //
        $this->Initialize($cfg);
        // efficient code :
        //$bin = array_pop(explode("/",$this->cfg["btclient_transmission_bin"]));
        // compatible code (should work on flawed phps like 5.0.5+) :
        $uselessVar = explode("/",$this->cfg["btclient_transmission_bin"]);
        $bin = array_pop($uselessVar);
        //
        $this->binSystem = $bin;
        $this->binClient = $bin;
    }

    //--------------------------------------------------------------------------
    /**
     * starts a bittorrent-client
     * @param $torrent name of the torrent
     * @param $interactive (1|0) : is this a interactive startup with dialog ?
     */
    function startTorrentClient($torrent, $interactive) {

        // do transmission special-pre-start-checks
        // check to see if the path to the transmission-bin is valid
        if (!is_file($this->cfg["btclient_transmission_bin"])) {
            AuditAction($this->cfg["constants"]["error"], "Error Path for ".$this->cfg["btclient_transmission_bin"]." is not valid");
            $this->status = -1;
            if (IsAdmin()) {
                header("location: admin.php?op=configSettings");
                return;
            } else {
                $this->messages .= "<b>Error</b> TorrentFlux settings are not correct (path to transmission-bin is not valid) -- please contact an admin.<br>";
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

        // included in transmissioncli
        // quick-hack for transmission--1
        //if ($this->rate == 0)
        //    $this->rate = -1;
        //if ($this->drate == 0)
        //    $this->drate = -1;
        // included in transmissioncli

        // pid-file
        $this->pidFile = "\"" . $this->cfg["torrent_file_path"].$this->alias .".stat.pid\"";
        // build the command-string
        $this->command = "-t \"".$this->cfg["torrent_file_path"].$this->alias .".stat\" -w ".$this->owner;
        // "new" transmission-patch has pid-file included
        $this->command .= " -z ". $this->pidFile; /* - bsd-workaround */
        $this->command .= " -e 5 -p ".$this->port ." -u ".$this->rate ." -c ". $this->sharekill_param ." -d ".$this->drate;
        $this->command .= " ".$this->cfg["btclient_transmission_options"]. "\"". $this->cfg["torrent_file_path"]. $this->torrent;
        // standard, no shell trickery ("new" transmission-patch has pid-file included) :
        $this->command .= '" &> /dev/null &'; /* - bsd-workaround */
        // <begin shell-trickery> to write the pid of the client into the pid-file
        // * b4rt :
        //$this->command .= '" &> /dev/null & echo $! > "'. $this->pidFile .'"';
        // * lord_nor :
        //$this->command .= '" > /dev/null & echo $! & > "'. $this->pidFile .'"'; /* + bsd-workaround */
        // <end shell-trickery>
        if (($this->cfg["AllowQueing"]) && ($this->queue == "1")) {
            //  This file is queued.
		} else {
            // This file is started manually.
            $this->command = "cd " . $this->savepath . "; HOME=".$this->cfg["path"]."; export HOME;". $this->umask ." nohup " . $this->nice . $this->cfg["btclient_transmission_bin"] . " " . $this->command;
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
        // delete the pid file
        // included in transmissioncli
        @unlink($this->pidFile);
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
        $torrentId = getTorrentHash($torrent);
        @unlink($this->cfg["path"].".transmission/cache/resume.".$torrentId);
        return;
    }

    //--------------------------------------------------------------------------
    /**
     * gets current transfer-vals of a torrent
     *
     * @param $db ref to db-object
     * @param $torrent
     * @return array with downtotal and uptotal
     */
    function getTorrentTransferCurrent(&$db, $torrent) {
        $retVal = array();
        // transfer from stat-file
        $aliasName = getAliasName($torrent);
        $owner = getOwner($torrent);
        $af = AliasFile::getAliasFileInstance($this->cfg["torrent_file_path"].$aliasName.".stat", $owner, $this->cfg, $this->handlerName);
        $retVal["uptotal"] = $af->uptotal+0;
        $retVal["downtotal"] = $af->downtotal+0;
        // transfer from db
        $torrentId = getTorrentHash($torrent);
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
     * gets current transfer-vals of a torrent. optimized index-page-version
     *
     * @param $torrent
     * @param $afu alias-file-uptotal of the torrent
     * @param $afd alias-file-downtotal of the torrent
     * @return array with downtotal and uptotal
     */
    function getTorrentTransferCurrentOP($torrent,$afu,$afd)  {
        global $db;
        $retVal = array();
        // transfer from stat-file
        $retVal["uptotal"] = $afu;
        $retVal["downtotal"] = $afd;
        // transfer from db
        $torrentId = getTorrentHash($torrent);
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

    //--------------------------------------------------------------------------
    /**
     * gets total transfer-vals of a torrent
     *
     * @param $db ref to db-object
     * @param $torrent
     * @return array with downtotal and uptotal
     */
    function getTorrentTransferTotal(&$db, $torrent) {
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
     * gets total transfer-vals of a torrent. optimized index-page-version
     *
     * @param $torrent
     * @param $afu alias-file-uptotal of the torrent
     * @param $afd alias-file-downtotal of the torrent
     * @return array with downtotal and uptotal
     */
    function getTorrentTransferTotalOP($torrent,$afu,$afd) {
        $retVal = array();
        // transfer from stat-file
        $retVal["uptotal"] = $afu;
        $retVal["downtotal"] = $afd;
        return $retVal;
    }
}

?>
