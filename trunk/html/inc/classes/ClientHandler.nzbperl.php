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
 * class ClientHandler for nzbperl-client
 */
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
        $this->binSystem = "perl";
        $this->binSocket = "perl";
        $this->binClient = "nzbperl.pl";
		$this->nzbbin = $cfg["docroot"]."bin/nzbperl/nzbperl.pl";
	}

	// =====================================================================
	// Public Methods
	// =====================================================================

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

        // umask
        $this->umask = ($cfg["enable_umask"] != 0)
        	? " umask 0000;"
        	: "";
        // nice
        $this->nice = ($cfg["nice_adjust"] != 0)
        	? "nice -n ".$cfg["nice_adjust"]." "
        	: "";

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

		// Build Command String (do not change order of last args !)
		$this->command  = "cd ".escapeshellarg($this->savepath).";";
		$this->command .= " HOME=".escapeshellarg(substr($cfg["path"], 0, -1));
		$this->command .= "; export HOME;";
		$this->command .= $this->umask;
		$this->command .= " nohup ";
		$this->command .= $this->nice;
		$this->command .= $cfg['perlCmd'];
		$this->command .= " -I ".$cfg["docroot"]."bin/fluxd";
		$this->command .= " ".escapeshellarg($this->nzbbin);
		$this->command .= " --dthreadct ".$cfg['nzbperl_threads'];
		$this->command .= ($cfg['nzbperl_badAction'])
			? " --insane"
			: " --dropbad";
		$this->command .= " --conn ".$cfg['nzbperl_conn'];
		$this->command .= " --log ".$this->logFilePath;
		$this->command .= " --uudeview ".$cfg["bin_uudeview"];
		$this->command .= " --server ".$cfg['nzbperl_server'];
		if ($cfg['nzbperl_user'] != "")
			$this->command .= " --user ".$cfg['nzbperl_user'];
		if ($cfg['nzbperl_pw'] != "")
			$this->command .= " --pw ".$cfg['nzbperl_pw'];
		if (strlen($cfg["nzbperl_options"]) > 0)
			$this->command .= " ".$cfg['nzbperl_options'];
		$this->command .= " --pidfile ".$this->pidFilePath;
		// do NOT change anything below (not even order)
		$this->command .= " --dlpath ".$this->savepath;
		$this->command .= " --statfile ".$this->aliasFilePath;
		$this->command .= " --tfuser ".$this->owner;
		$this->command .= " ".$this->transferFilePath;
        $this->command .= " 1>> ".escapeshellarg($this->logFilePath);
        $this->command .= " 2>> ".escapeshellarg($this->logFilePath);
        $this->command .= " &";

		// state
		$this->state = CLIENTHANDLER_STATE_READY;

		// Start the client
		$this->execStart(false, false);
	}

    /**
     * stops a client
     *
     * @param $transfer name of the transfer
     * @param $aliasFile alias-file of the transfer
     * @param $transferPid transfer Pid (optional)
     * @param $return return-param (optional)
     */
    function stop($transfer, $kill = false, $transferpid = 0) {
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