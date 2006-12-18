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
 * class ClientHandler for wget-client
 */
class ClientHandlerWget extends ClientHandler
{

	// public fields
	var $url = "";
	var $urlFile = "";

	// =========================================================================
	// ctor
	// =========================================================================

    /**
     * ctor
     */
    function ClientHandlerWget() {
        $this->handlerName = "wget";
        $this->binSystem = "wget";
        $this->binSocket = "wget";
        $this->binClient = "wget.php";
    }

	// =========================================================================
	// public methods
	// =========================================================================

    /**
     * setVarsFromUrl
     *
     * @param $transferUrl
     */
    function setVarsFromUrl($transferUrl) {
    	global $cfg;
    	$this->url = $transferUrl;
        $transfer = strrchr($transferUrl,'/');
        if ($transfer{0} == '/')
        	$transfer = substr($transfer, 1);
		$this->setVarsFromTransfer($transfer);
        $this->urlFile = $cfg["transfer_file_path"].$this->alias.".wget";
        if (empty($this->owner) || (strtolower($this->owner) == "n/a"))
        	$this->owner = $cfg['user'];
    }

    /**
     * setVarsFromFile
     *
     * @param $transfer
     */
    function setVarsFromFile($transfer) {
    	global $cfg;
		$this->setVarsFromTransfer($transfer);
    	$this->urlFile = $cfg["transfer_file_path"].$this->alias.".wget";
	    $data = "";
	    if ($fileHandle = @fopen($this->urlFile,'r')) {
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

		// inject alias
		$af = new AliasFile($this->aliasFile);
		$af->running = "2"; // file is new
		$af->size = "0";
		if (!$af->write()) {
			$this->state = CLIENTHANDLER_STATE_ERROR;
            $msg = "wget-inject-error when writing alias-file : ".$this->aliasFile;
            array_push($this->messages , $msg);
            AuditAction($cfg["constants"]["error"], $msg);
            $this->logMessage($msg."\n", true);
            return false;
		}

		// write meta-file
		$resultSuccess = false;
		if ($handle = @fopen($this->urlFile, "w")) {
	        $resultSuccess = (@fwrite($handle, $this->url) !== false);
			@fclose($handle);
		}

		// log
		if ($resultSuccess) {
			// Make an entry for the owner
			AuditAction($cfg["constants"]["file_upload"], basename($this->urlFile));
		} else {
			$this->state = CLIENTHANDLER_STATE_ERROR;
            $msg = "wget-metafile cannot be written : ".$this->urlFile;
            array_push($this->messages , $msg);
            AuditAction($cfg["constants"]["error"], $msg);
            $this->logMessage($msg."\n", true);
		}

		// set transfers-cache
		cacheTransfersSet();

		// return
		return $resultSuccess;
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

        // set vars from the wget-file
		$this->setVarsFromFile($transfer);

    	// log
    	$this->logMessage($this->handlerName."-start : ".$transfer."\n", true);

        // do wget special-pre-start-checks
        // check to see if the path to the wget-bin is valid
        if (!is_executable($cfg["bin_wget"])) {
        	$this->state = CLIENTHANDLER_STATE_ERROR;
        	$msg = "wget cannot be executed";
        	AuditAction($cfg["constants"]["error"], $msg);
        	$this->logMessage($msg."\n", true);
        	array_push($this->messages, $msg);
            array_push($this->messages, "bin_wget : ".$cfg["bin_wget"]);
            return false;
        }

		// queue false
		$this->queue = false;

		// build the command-string
		// note : order of args must not change for ps-parsing-code in
		// RunningTransferWget
        $this->command  = "nohup ".$cfg['bin_php']." -f bin/wget.php";
        $this->command .= " " . escapeshellarg($this->urlFile);
        $this->command .= " " . escapeshellarg($this->aliasFile);
        $this->command .= " " . escapeshellarg($this->pidFilePath);
        $this->command .= " " . $this->owner;
        $this->command .= ($cfg["enable_home_dirs"] != 0)
        	? " " . $this->owner
        	: " " . escapeshellarg($cfg["path_incoming"]);
        $this->command .= " " . $cfg["wget_limit_rate"];
        $this->command .= " " . $cfg["wget_limit_retries"];
        $this->command .= " " . $cfg["wget_ftp_pasv"];
        $this->command .= " 1>> ".escapeshellarg($this->logFilePath);
        $this->command .= " 2>> ".escapeshellarg($this->logFilePath);
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
     * @param $kill kill-param (optional)
     * @param $transferPid transfer Pid (optional)
     */
    function stop($transfer, $kill = false, $transferPid = 0) {
        // stop the client
    }

	/**
	 * deletes a transfer
	 *
	 * @param $transfer name of the transfer
	 * @return boolean of success
	 */
	function delete($transfer) {
        // set vars from the wget-file
		$this->setVarsFromTransfer($transfer);
		// delete
		$this->execDelete(false, false);
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
        $af = new AliasFile(getAliasName($transfer).".stat", getOwner($transfer));
        return array("uptotal" => $af->uptotal, "downtotal" => $af->downtotal);
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
        return array("uptotal" => $afu, "downtotal" => $afd);
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