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

// class ClientHandler for mainline-client
class ClientHandlerMainline extends ClientHandler
{
	// mainline-bin
	var $mainlineBin = "";

    /**
     * ctor
     */
    function ClientHandlerMainline($cfg) {
        $this->handlerName = "mainline";
		// initialize
        //
        $this->binSystem = "python";
        $this->binSocket = "python";
        $this->binClient = "tfmainline.py";
        //
        $this->initialize($cfg);
        //
        $this->mainlineBin = $this->cfg["docroot"]."bin/TF_Mainline/tfmainline.py";
    }

    /**
     * starts a client
     * @param $transfer name of the transfer
     * @param $interactive (1|0) : is this a interactive startup with dialog ?
     * @param $enqueue (boolean) : enqueue ?
     */
    function startClient($transfer, $interactive, $enqueue) {

        // do mainline special-pre-start-checks
        // check to see if the path to the python script is valid
        if (!is_file($this->mainlineBin)) {
            AuditAction($this->cfg["constants"]["error"], "Error  Path for ".$this->mainlineBin." is not valid");
            if ($this->cfg['isAdmin']) {
                $this->state = -1;
                header("location: admin.php?op=serverSettings");
                return;
            } else {
                $this->state = -1;
                $this->messages .= "Error TorrentFlux settings are not correct (path to python script is not valid) -- please contact an admin.";
                return;
            }
        }

        // prepare starting of client
        parent::prepareStartClient($transfer, $interactive, $enqueue);

		// only continue if prepare succeeded (skip start / error)
		if ($this->state != 2) {
			if ($this->state == -1)
				$this->messages .= "Error after call to parent::prepareStartClient(".$transfer.",".$interactive.",".$enqueue.")";
			return;
		}

		// pythonCmd
		$pyCmd = $this->cfg["pythonCmd"] . " -OO";

		// build the command-string

		// note :
		// order of args must not change for ps-parsing-code in
		// RunningTransferMainline

		$this->command = "cd ".escapeshellarg($this->savepath).";";
		$this->command .= " HOME=".escapeshellarg($this->cfg["path"]);
		$this->command .= "; export HOME;";
		$this->command .= $this->umask;
		$this->command .= " nohup ";
		$this->command .= $this->nice;
		$this->command .= $pyCmd . " " .escapeshellarg($this->mainlineBin);
		$this->command .= " --display_interval 5";
		$this->command .= " --tf_owner ".$this->owner;
		$this->command .= " --stat_file ".escapeshellarg($this->cfg["transfer_file_path"].$this->alias.".stat");
		$this->command .= " --save_incomplete_in ".escapeshellarg($this->savepath);
		$this->command .= " --save_in ".escapeshellarg($this->savepath);
		//$this->command .= " --language en";
		$this->command .= " --die_when_done ".escapeshellarg($this->runtime);
		$this->command .= " --seed_limit ".escapeshellarg($this->sharekill_param);
		if ($this->drate != 0)
			$this->command .= " --max_download_rate " . escapeshellarg($this->drate * 1024);
		else
			$this->command .= " --max_download_rate 125000000"; # 1 GBit local net = 125MB/s
		if ($this->rate != 0)
			$this->command .= " --max_upload_rate " . escapeshellarg($this->rate * 1024);
		else
			$this->command .= " --max_upload_rate 125000000"; # 1 GBit local net = 125MB/s
		$this->command .= " --max_uploads ".escapeshellarg($this->maxuploads);
		$this->command .= " --minport ".escapeshellarg($this->port);
		$this->command .= " --maxport ".escapeshellarg($this->maxport);
		$this->command .= " --rerequest_interval ".escapeshellarg($this->rerequest);
		$this->command .= " --max_initiate ".escapeshellarg($this->maxcons);
		if ((!(empty($this->skip_hash_check))) && (getTorrentDataSize($this->transfer) > 0))
			$this->command .= " --no_check_hashes";
		if (strlen($this->cfg["btclient_mainline_options"]) > 0)
			$this->command .= " ".$this->cfg["btclient_mainline_options"];
		$this->command .= " ".escapeshellarg($this->cfg["transfer_file_path"].$this->transfer);
        $this->command .= " 1>> ".escapeshellarg($this->logFile);
        $this->command .= " 2>> ".escapeshellarg($this->logFile);
        $this->command .= " &";

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
        $af = new AliasFile($aliasName.".stat", $owner);
        $retVal["uptotal"] += $af->uptotal;
        $retVal["downtotal"] += $af->downtotal;
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