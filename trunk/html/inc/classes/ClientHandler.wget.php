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
		$this->setVarsFromTransfer($transfer.".wget");
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
	    $data = "";
	    if ($fileHandle = @fopen($this->transferFilePath,'r')) {
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

		// inject stat
		$sf = new StatFile($this->transfer);
		$sf->running = "2"; // file is new
		$sf->size = "0";
		if (!$sf->write()) {
			$this->state = CLIENTHANDLER_STATE_ERROR;
            $msg = "wget-inject-error when writing stat-file for transfer : ".$this->transfer;
            array_push($this->messages , $msg);
            AuditAction($cfg["constants"]["error"], $msg);
            $this->logMessage($msg."\n", true);
            return false;
		}

		// write meta-file
		$resultSuccess = false;
		if ($handle = @fopen($this->transferFilePath, "w")) {
	        $resultSuccess = (@fwrite($handle, $this->url) !== false);
			@fclose($handle);
		}

		// log
		if ($resultSuccess) {
			// Make an entry for the owner
			AuditAction($cfg["constants"]["file_upload"], basename($this->transferFilePath));
		} else {
			$this->state = CLIENTHANDLER_STATE_ERROR;
            $msg = "wget-metafile cannot be written : ".$this->transferFilePath;
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

		// savepath
		$this->savepath = ($cfg["enable_home_dirs"] != 0)
        		? $cfg['path'].$this->owner."/"
        		: $cfg['path'].$cfg["path_incoming"]."/";

        // check target-directory, create if not present
		if (!(checkDirectory($this->savepath, 0777))) {
			$this->state = CLIENTHANDLER_STATE_ERROR;
			$msg = "Error checking savepath ".$this->savepath;
			array_push($this->messages, $msg);
			AuditAction($cfg["constants"]["error"], $msg);
            $this->logMessage($msg."\n", true);
            return false;
		}

		// build the command-string
		// note : order of args must not change for ps-parsing-code in
		// RunningTransferWget
        $this->command  = "nohup ".$cfg['bin_php']." -f bin/wget.php";
        $this->command .= " " . escapeshellarg($this->transfer);
        $this->command .= " " . $this->owner;
        $this->command .= " " . escapeshellarg($this->savepath);
        $this->command .= " " . $cfg["wget_limit_rate"];
        $this->command .= " " . $cfg["wget_limit_retries"];
        $this->command .= " " . $cfg["wget_ftp_pasv"];
        $this->command .= " 1>> ".escapeshellarg($this->transferFilePath.".log");
        $this->command .= " 2>> ".escapeshellarg($this->transferFilePath.".log");
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
        $sf = new StatFile($transfer);
        return array("uptotal" => $sf->uptotal, "downtotal" => $sf->downtotal);
    }

    /**
     * gets current transfer-vals of a transfer. optimized version
     *
     * @param $transfer
     * @param $tid of the transfer
     * @param $sfu stat-file-uptotal of the transfer
     * @param $sfd stat-file-downtotal of the transfer
     * @return array with downtotal and uptotal
     */
    function getTransferCurrentOP($transfer, $tid, $sfu, $sfd) {
        return array("uptotal" => $sfu, "downtotal" => $sfd);
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
        $sf = new StatFile($transfer);
        return array("uptotal" => $sf->uptotal, "downtotal" => $sf->downtotal);
    }

    /**
     * gets total transfer-vals of a transfer. optimized version
     *
     * @param $transfer
     * @param $tid of the transfer
     * @param $sfu stat-file-uptotal of the transfer
     * @param $sfd stat-file-downtotal of the transfer
     * @return array with downtotal and uptotal
     */
    function getTransferTotalOP($transfer, $tid, $sfu, $sfd) {
        return array("uptotal" => $sfu, "downtotal" => $sfd);
    }
}

?>