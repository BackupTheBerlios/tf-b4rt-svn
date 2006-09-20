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
	var $url = "";
	var $urlFile = "";

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
        $this->initialize($cfg);
        // efficient code :
        //$bin = array_pop(explode("/",$this->cfg["btclient_transmission_bin"]));
        // compatible code (should work on flawed phps like 5.0.5+) :
        $uselessVar = explode("/",$this->cfg["bin_wget"]);
        $bin = array_pop($uselessVar);
        //
        $this->binSystem = $bin;
    }

    /**
     * setVarsFromUrl
     *
     * @param $transferUrl
     */
    function setVarsFromUrl($transferUrl) {
    	$this->url = $transferUrl;
        $this->transfer = strrchr($transferUrl,'/');
        if ($this->transfer{0} == '/')
        	$this->transfer = substr($this->transfer, 1);
        $aliasName = getAliasName($this->transfer);
        $this->urlFile = $this->cfg["transfer_file_path"].$aliasName.".wget";
        $this->alias = $aliasName.".stat";
        $this->owner = $this->cfg['user'];
        $this->pidFile = $this->cfg["transfer_file_path"].$this->alias.".pid";
    }

    /**
     * setVarsFromFile
     *
     * @param $transfer
     */
    function setVarsFromFile($transfer) {
    	$aliasName = getAliasName($transfer);
    	$uf = $this->cfg["transfer_file_path"].$aliasName.".wget";
	    $data = "";
	    if($fileHandle = @fopen($uf,'r')) {
	        while (!@feof($fileHandle))
	            $data .= @fgets($fileHandle, 2048);
	        @fclose ($fileHandle);
	        $this->setVarsFromUrl(trim($data));
	    }
    }

	/**
	 * injects a atorrent
	 *
	 * @param $url
	 * @return boolean
	 */
	function inject($url) {

		// set vars from the url
		$this->setVarsFromUrl($url);

		// write out aliasfile
		require_once("inc/classes/AliasFile.php");
		$af = AliasFile::getAliasFileInstance($this->cfg["transfer_file_path"].$this->alias,	$this->cfg['user'], $this->cfg);
		$af->running = "2"; // file is new
		$af->size = 0;
		$af->WriteFile();

		// write wget-file
		$fp = fopen($this->urlFile, 'w');
		fwrite($fp, $this->url);
		fclose($fp);

		// Make an entry for the owner
		AuditAction($this->cfg["constants"]["file_upload"], basename($this->urlFile));

		// return
		return true;
	}

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
            if ($this->cfg['isAdmin']) {
                header("location: index.php?iid=admin&op=configSettings");
                return;
            } else {
                $this->messages .= "Error TorrentFlux settings are not correct (path to wget-bin is not valid) -- please contact an admin.";
                return;
            }
        }

        // set vars from the wget-file
		$this->setVarsFromFile($transfer);

        // start it
        $this->command = "nohup ".$this->cfg['bin_php']." -f bin/wget.php";
        $this->command .= " " . escapeshellarg($this->urlFile);
        $this->command .= " " . escapeshellarg($this->cfg["transfer_file_path"].$this->alias);
        $this->command .= " " . escapeshellarg($this->pidFile);
        $this->command .= " " . $this->owner;
        switch ($this->cfg["enable_home_dirs"]) {
        	case 1:
        	default:
        		$this->command .= " " . $this->owner;
        		break;
        	case 0:
        		$this->command .= " " . escapeshellarg($this->cfg["path_incoming"]);
        		break;
        }
        $this->command .= " " . $this->cfg["wget_limit_rate"];
        $this->command .= " " . $this->cfg["wget_limit_retries"];
        $this->command .= " " . $this->cfg["wget_ftp_pasv"];
        $this->command .= " > /dev/null &";
        //system('echo command >> /tmp/tflux.debug; echo "'. $this->command .'" >> /tmp/tflux.debug');
        exec($this->command);
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
        // stop the client
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
    	global $db;
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
        global $db;
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