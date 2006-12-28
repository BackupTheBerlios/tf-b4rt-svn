<?php

/* $Id: ClientHandler.nzbperl.php 1544 2006-11-10 14:55:04Z b4rt $ */

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

// class ClientHandler for nzbperl-client
class ClientHandlerNzbperl extends ClientHandler
{

	// public fields

	// nzbperl bin
	var $nzbbin = "";

	// =====================================================================
	// ctor
	// =====================================================================

    /**
     * ctor
     */
    function ClientHandlerNzbperl() {
	global $cfg;
	$this->handlerName = "nzbperl";
	$this->version = "0.6";
        $this->binSocket = "perl";
	$this->nzbbin = $cfg["docroot"]."bin/nzbperl/nzbperl.pl";
    }

	// =====================================================================
	// Public Methods
	// =====================================================================

    /**
     * injects a torrent
     *
     * @param $nzb
     * @return boolean
     */
    function inject($nzb) {

	// write out aliasfile
	require_once("inc/classes/AliasFile.php");
	$af = AliasFile::getAliasFileInstance($this->cfg["transfer_file_path"].$this->alias,	$this->cfg['user'], $this->cfg);
	$af->running = "2"; // file is new
	$af->size = 0;
	$af->WriteFile();

	// Make an entry for the owner
	AuditAction($this->cfg["constants"]["file_upload"], basename($this->nzbFile));

	// return
	return true;
    }

    /**
     * starts a client
     * @param $transfer name of the transfer
     * @param $interactive (1|0) : is this a interactive startup with dialog ?
     * @param $enqueue (boolean) : enqueue ?
     */
    function start($transfer, $interactive = false, $enqueue = false) {
		global $cfg;

	// set vars
		$this->setVarsFromTransfer($transfer);

	// log
	$this->logMessage($this->handlerName."-start : ".$transfer."\n", true);

	// do nzbperl special-pre-start-checks
	// check to see if the path to the nzbperl script is valid
	if (!is_file($this->nzbbin)) {
	    $this->state = CLIENTHANDLER_STATE_ERROR;
	    $msg = "path for nzbperl.pl is not valid";
	    AuditAction($cfg["constants"]["error"], $msg);
	    $this->logMessage($msg."\n", true);
	    array_push($this->messages, $msg);
	    array_push($this->messages, "nzbbin : ".$this->nzbbin);
	    return false;
	}

	// Prepare to start it
	$this->prepareStart($interactive, $enqueue);

	// Only continue if prepare succeeded.
	if ($this->state != CLIENTHANDLER_STATE_READY) {
	    if ($this->state == CLIENTHANDLER_STATE_ERROR) {
		$msg = "Error after call to prepareStart(".$transfer.",".$interactive.",".$enqueue.")";
		array_push($this->messages, $msg);
		$this->logMessage($msg."\n", true);
	    }
	    return false;
	}

	// Build Command String
	$this->command = "nohup ".$cfg['perlCmd']." ".$this->nzbbin;
	$this->command .= " --dthreadct ".$cfg['nzbperl_threads'];
	if ($cfg['nzbperl_badAction']) {
	    $this->command .= " --insane";
	} else {
	    $this->command .= " --dropbad";
	}
	$this->command .= " --tfuser ".$this->owner;
	$this->command .= " --statfile ".$cfg['transfer_file_path'].$this->alias.".stat";
	$this->command .= " --pidfile ".$cfg['transfer_file_path'].$this->alias.".stat.pid";
	$this->command .= " --server ".$cfg['nzbperl_server'];
	switch ($cfg["enable_home_dirs"]) {
	    case 1:
		default:
		    $this->command .= " --dlpath ".$cfg['path'].$this->owner;
		    break;
		case 0:
		    $this->command .= " --dlpath ".$cfg["path_incoming"];
		    break;
	}
	$this->command .= " --conn ".$cfg['nzbperl_conn'];
	$this->command .= " --log ".$cfg['transfer_file_path'].$this->alias.".log";
	$this->command .= " ".$cfg['nzbperl_options'];
	$this->command .= " ".$cfg["transfer_file_path"].$this->transfer;
	$this->command .= " &";

	// Start the client
	//print "Debug: command is - $this->command.";
	$this->execStart(false, true);
    }

    /**
     * stops a client
     *
     * @param $transfer name of the transfer
     * @param $aliasFile alias-file of the transfer
     * @param $transferPid transfer Pid (optional)
     * @param $return return-param (optional)
     */
    function stopClient($transfer, $kill = false, $transferpid = 0) {
	// set vars
	$this->setVarsFromTransfer($transfer);
        // stop the client
	$this->execStop($kill, $transferpid);
    }

    /**
     * deletes the transfer
     * 
     * @param $transfer name of the transfer
     * @return boolean on success
     */
    function delete($transfer) {
	//set vars
	$this->setVarsFromTransfer($transfer);
	// delete
	$this->execDelete(true, true);
    }

    /**
     * gets current transfer-vals of a transfer
     *
     * @param $transfer name of the transfer
     * @return array with downtotal and uptotal
     */
    function getTransferCurrent($transfer) {
	global $transfers;
	// Transfer from stat-file
	$af = new AliasFile(getAliasName($transfer).".stat", getOwner($transfer));
	return array("uptotal" => $af->uptotal, "downtotal" => $af->downtotal);
    }

    /**
     * Gets current transfer-vals of a transfer (optimized version)
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
     * Gets total transfer-vals of a transfer
     *
     * @param $transfer
     * @return array with downtotal and uptotal
     */
    function getTransferTotal($transfer) {
	global $db, $transfers;
	$retval = array();
	// transfer from DB
	$sql = "SELECT uptotal, downtotal FROM tf_torrent_totals WHERE tid = '".getTorrentHash($transfer)."'";
	$result = $db->Execute($sql);
	$row = $result->FetchRow();
	if (empty($row)) {
	    $retval["uptotal"] = 0;
	    $retval["downtotal"] = 0;
	} else {
	    $retval["uptotal"] = $row["uptotal"];
	    $retval["downtotal"] = $row["downtotal"];
	}
	// transfer from stat-file
	$af = new AliasFile(getAliasName($transfer).".stat", getOwner($transfer));
	$retval["uptotal"] += $af->uptotal;
	$retval["downtotal"] += $af->downtotal;
	return $retval;
    }

    /**
     * Gets total transfer-vals of a transfer (optimized version)
     *
     * @param $transfer
     * @param $tid
     * @param $afu alias-file-uptotal of the transfer
     * @param $afd alias-file-downtotal of the transfer
     * @return array with downtotal and uptotal
     */
    function getTransferTotalOP($transfer, $tid, $afu, $afd) {
	global $transfers;
	$retval = array();
	$retval["uptotal"] = (isset($transfers['totals'][$tid]['uptotal']))
	    ? $transfers['totals'][$tid]['uptotal'] + $afu
	    : $afu;
	$retval["downtotal"] = (isset($transfers['totals'][$tid]['downtotal']))
	    ? $transfers['totals'][$tid]['downtotal'] + $afd
	    : $afd;
	return $retval;
    }
}

?>
