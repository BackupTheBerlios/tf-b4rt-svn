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

// class ClientHandler for wget-client
class ClientHandlerWget extends ClientHandler
{
    //--------------------------------------------------------------------------
    /**
     * ctor
     */
    function ClientHandlerWget($cfg) {
        $this->handlerName = "wget";
        $this->version = array_shift(explode(" ",trim(array_pop(explode(":",'$Revision$')))));
        //
        $this->binSocket = "wget";
        $this->binClient = "wget.php";
        //
        $this->Initialize($cfg);
        // efficient code :
        //$bin = array_pop(explode("/",$this->cfg["btclient_transmission_bin"]));
        // compatible code (should work on flawed phps like 5.0.5+) :
        $uselessVar = explode("/",$this->cfg["bin_wget"]);
        $bin = array_pop($uselessVar);
        //
        $this->binSystem = $bin;
    }

    //--------------------------------------------------------------------------
    /**
     * starts a client
     * @param $transfer name of the transfer
     * @param $interactive (1|0) : is this a interactive startup with dialog ?
     * @param $enqueue (boolean) : enqueue ?
     */
    function startClient($transfer, $interactive, $enqueue = false) {

        // do wget special-pre-start-checks
        // check to see if the path to the wget-bin is valid
        if (!is_file($this->cfg["bin_wget"])) {
            AuditAction($this->cfg["constants"]["error"], "Error Path for ".$this->cfg["bin_wget"]." is not valid");
            $this->status = -1;
            if (IsAdmin()) {
                header("location: index.php?iid=admin&op=configSettings");
                return;
            } else {
                $this->messages .= "Error TorrentFlux settings are not correct (path to wget-bin is not valid) -- please contact an admin.";
                return;
            }
        }

        // set some vars
        $this->transfer = strrchr($transfer,'/');
        if ($this->transfer{0} == '/')
        	$this->transfer = substr($this->transfer, 1);
        $aliasName = getAliasName($this->transfer);
        $urlFile = $this->cfg["torrent_file_path"].$aliasName.".wget";
        $this->alias = $aliasName.".stat";
        $this->owner = $this->cfg['user'];
        $this->pidFile = $this->cfg["torrent_file_path"].$this->alias.".pid";

		// write url-file
		$fp = fopen($urlFile, 'w');
		fwrite($fp, $transfer);
		fclose($fp);

        // start it
        $this->command = "nohup ".$this->cfg['bin_php']." -f wget.php";
        $this->command .= " " . escapeshellarg($urlFile);
        $this->command .= " " . escapeshellarg($this->cfg["torrent_file_path"].$this->alias);
        $this->command .= " " . escapeshellarg($this->pidFile);
        $this->command .= " " . $this->owner;
        $this->command .= " > /dev/null &";
        //system('echo command >> /tmp/tflux.debug; echo "'. $this->command .'" >> /tmp/tflux.debug');
        exec($this->command);
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
        // stop the client
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
    	global $db;
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
        global $db;
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