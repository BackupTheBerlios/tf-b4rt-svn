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
        $this->version = array_shift(explode(" ",trim(array_pop(explode(":",'$Revision$')))));
        //
        $this->binSystem = "python";
        $this->binSocket = "python";
        $this->binClient = "bittorrent-console.py";
        $this->mainlineBin = dirname($_SERVER["SCRIPT_FILENAME"])."/bin/TF_Mainline/bittorrent-console.py";
        //
        $this->initialize($cfg);
    }

    /**
     * starts a client
     * @param $transfer name of the transfer
     * @param $interactive (1|0) : is this a interactive startup with dialog ?
     * @param $enqueue (boolean) : enqueue ?
     */
    function startClient($transfer, $interactive, $enqueue = false) {

        // do mainline special-pre-start-checks
        // check to see if the path to the python script is valid
        if (!is_file($this->mainlineBin)) {
            AuditAction($this->cfg["constants"]["error"], "Error  Path for ".$this->mainlineBin." is not valid");
            if (IsAdmin()) {
                $this->status = -1;
                header("location: index.php?iid=admin&op=configSettings");
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

		/* Skip file priority stuff, as its not in the CL client, that I can see
		$filePrio = "";
		if(file_exists($this->cfg["transfer_file_path"].$this->alias.".prio")) {
		$priolist = explode(',',file_get_contents($this->cfg["transfer_file_path"].$this->alias .".prio"));
		$priolist = implode(',',array_slice($priolist,1,$priolist[0]));
		$filePrio = " --priority ".$priolist;
		}
		<-- end file priority --> */

		/*

		some args :

--max_upload_rate <arg>
          maximum B/s to upload at (defaults to 125000000)

--max_download_rate <arg>
          average maximum B/s to download at (defaults to 125000000)


--max_files_open <arg>
          the maximum number of files in a multifile torrent to keep open at a
          time, 0 means no limit. Used to avoid running out of file
          descriptors. (defaults to 50)

--start_trackerless_client, --no_start_trackerless_client
          Initialize a trackerless client. This must be enabled in order to
          download trackerless torrents. (defaults to True)

--upnp, --no_upnp
          Enable automatic port mapping (UPnP) (defaults to True)

--xmlrpc_port <arg>
          Start the XMLRPC interface on the specified port. This XML-RPC-based
          RPC allows a remote program to control the client to enable automated
          hosting, conformance testing, and benchmarking. (defaults to -1)

--save_in <arg>
          local directory where the torrent contents will be saved. The file
          (single-file torrents) or directory (batch torrents) will be created
          under this directory using the default name specified in the .torrent
          file. See also --save_as. (defaults to u'')

--save_incomplete_in <arg>
          local directory where the incomplete torrent downloads will be stored
          until completion. Upon completion, downloads will be moved to the
          directory specified by --save_in. (defaults to u'')

--save_as <arg>
          file name (for single-file torrents) or directory name (for batch
          torrents) to save the torrent as, overriding the default name in the
          torrent. See also --save_in (defaults to u'')


--bad_libc_workaround, --no_bad_libc_workaround
          enable workaround for a bug in BSD libc that makes file reads very
          slow. (defaults to False)


--twisted <arg>
          Use Twisted network libraries for network connections. 1 means use
          twisted, 0 means do not use twisted, -1 means autodetect, and prefer
          twisted (defaults to -1)

		*/

		// note :
		// order of args must not change for ps-parsing-code in
		// RunningTransferMainline

		$this->command = "cd " . $this->savepath .";";
		$this->command .= " HOME=".$this->cfg["path"];
		$this->command .= "; export HOME;";
		$this->command .= $this->umask;
		$this->command .= " nohup ";
		$this->command .= $this->nice;
		$this->command .= $pyCmd . " " .$this->mainlineBin;
		$this->command .= " --display_interval 5";
		$this->command .= " --tf_owner ".$this->owner;
		$this->command .= " --stat_file ".$this->cfg["transfer_file_path"].$this->alias .".stat";
		$this->command .= " --save_incomplete_in ".$this->savepath;
		$this->command .= " --save_in ".$this->savepath;
		$this->command .= " --language en";
		$this->command .= " --seed_limit ".$this->sharekill_param;
		if ($this->drate != 0) {
			$this->command .= " --max_download_rate " . $this->drate * 1024;
		} else {
			$this->command .= " --max_download_rate -1";
		}
		if ($this->rate != 0) {
			$this->command .= " --max_upload_rate " . $this->rate * 1024;
		} else {
			$this->command .= " --max_upload_rate -1";
		}
		$this->command .= " --max_uploads ".$this->maxuploads;
		$this->command .= " --minport ".$this->port;
		$this->command .= " --maxport ".$this->maxport;
		$this->command .= " --rerequest_interval ".$this->rerequest;
		$this->command .= " --max_initiate ".$this->maxcons;
		if ((!(empty($this->skip_hash_check))) && (getTorrentDataSize($this->transfer) > 0))
			$this->command .= " --no_check_hashes";
		$this->command .= " ".$this->cfg["btclient_mainline_options"];
		$this->command .= " ".$this->cfg["transfer_file_path"].$this->transfer;
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
        // give it some extra time, it needs it.
        sleep(2);
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