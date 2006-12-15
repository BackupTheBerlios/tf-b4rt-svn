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

	// public fields
	var $url = "";
	var $urlFile = "";

    /**
     * ctor
     */
    function ClientHandlerWget() {
        $this->handlerName = "wget";
        $this->binSystem = "wget";
        $this->binSocket = "wget";
        $this->binClient = "wget.php";
    }

    /**
     * setVarsFromUrl
     *
     * @param $transferUrl
     */
    function setVarsFromUrl($transferUrl) {
    	global $cfg;
    	$this->url = $transferUrl;
        $this->transfer = strrchr($transferUrl,'/');
        if ($this->transfer{0} == '/')
        	$this->transfer = substr($this->transfer, 1);
        $aliasName = getAliasName($this->transfer);
        $this->urlFile = $cfg["transfer_file_path"].$aliasName.".wget";
        $this->alias = $aliasName.".stat";
        $this->logFile = $cfg["transfer_file_path"].$aliasName.".log";
        $this->owner = $cfg['user'];
        $this->pidFile = $cfg["transfer_file_path"].$this->alias.".pid";
    }

    /**
     * setVarsFromFile
     *
     * @param $transfer
     */
    function setVarsFromFile($transfer) {
    	global $cfg;
    	$aliasName = getAliasName($transfer);
    	$uf = $cfg["transfer_file_path"].$aliasName.".wget";
	    $data = "";
	    if ($fileHandle = @fopen($uf,'r')) {
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
		global $cfg;

		// set vars from the url
		$this->setVarsFromUrl($url);

		// write out aliasfile
		$af = new AliasFile($this->alias, $cfg['user']);
		$af->running = "2"; // file is new
		$af->size = 0;
		$af->write();

		// write wget-file
		$fp = fopen($this->urlFile, 'w');
		fwrite($fp, $this->url);
		fclose($fp);

		// Make an entry for the owner
		AuditAction($cfg["constants"]["file_upload"], basename($this->urlFile));

		// return
		return true;
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

        // do wget special-pre-start-checks
        // check to see if the path to the wget-bin is valid
        if (!is_executable($cfg["bin_wget"])) {
        	$this->state = CLIENTHANDLER_STATE_ERROR;
            $msg = "wget cannot be executed : ".$cfg["bin_wget"];
            array_push($this->messages , $msg);
            AuditAction($cfg["constants"]["error"], $msg);
            if (empty($_REQUEST))
            	die($msg);
            else
				showErrorPage($msg);
        }

        // set vars from the wget-file
		$this->setVarsFromFile($transfer);

		// more vars
		$this->queue = 0;

		// build the command-string
		// note : order of args must not change for ps-parsing-code in
		// RunningTransferWget
        $this->command  = "nohup ".$cfg['bin_php']." -f bin/wget.php";
        $this->command .= " " . escapeshellarg($this->urlFile);
        $this->command .= " " . escapeshellarg($this->alias);
        $this->command .= " " . escapeshellarg($this->pidFile);
        $this->command .= " " . $this->owner;
        $this->command .= ($cfg["enable_home_dirs"] != 0)
        	? " " . $this->owner
        	: " " . escapeshellarg($cfg["path_incoming"]);
        $this->command .= " " . $cfg["wget_limit_rate"];
        $this->command .= " " . $cfg["wget_limit_retries"];
        $this->command .= " " . $cfg["wget_ftp_pasv"];
        $this->command .= " 1>> ".escapeshellarg($this->logFile);
        $this->command .= " 2>> ".escapeshellarg($this->logFile);
        $this->command .= " &";

		// state
		$this->state = CLIENTHANDLER_STATE_READY;

		// start the client
		$this->execStart(false, false);
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
        // stop the client
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
        $af = new AliasFile($aliasName.".stat", $owner);
        $retVal["uptotal"] = $af->uptotal;
        $retVal["downtotal"] = $af->downtotal;
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