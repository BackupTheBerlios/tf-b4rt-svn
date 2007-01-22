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

// states
define('WGET_STATE_NULL', 0);                                            // null
define('WGET_STATE_RUNNING', 1);                                      // running
define('WGET_STATE_ERROR', -1);                                         // error

/**
 * class Wrapper for wget-client
 */
class WrapperWget
{
	// public fields
	var $name = "wget";

    // state
    var $state = WGET_STATE_NULL;

    // messages-array
    var $messages = array();

    // startup-command
    var $command = "";

	// runtime-vars
	var $percent_done = 0;
	var $time_left = "-";
	var $down_speed = "0.00 kB/s";
	var $downtotal = 0;
	var $size = 0;

	// pid
	var $pid = 0;

	// vars from args
	var $transfer = "";
	var $transferFile = "";
	var $commandFile = "";
	var $owner = "";
	var $path = "";
	var $drate = 0;
	var $retries = 0;
	var $pasv = 0;

	// private fields

    // running-flag
    var $_running = WGET_STATE_NULL;

    // done-flag
    var $_done = false;

	// statfile-object-instance
	var $_sf = null;

	// clienthandler-object-instance
	var $_ch = null;

	// process-handle
	var $_wget = null;

	// buffer
	var $_buffer = "";

	// speed as number
	var $_speed = 0;

	// =========================================================================
	// ctor
	// =========================================================================

    /**
     * ctor
     *
	 * @param $file
	 * @param $owner
	 * @param $path
	 * @param $drate
	 * @param $retries
	 * @param $pasv
     * @return WrapperWget
     */
    function WrapperWget($file, $owner, $path, $drate, $retries, $pasv) {
    	global $cfg;
        // set fields from params
		$this->transferFile = $file;
		$this->transfer = str_replace($cfg['transfer_file_path'], '', $file);
		$this->commandFile = $file.".cmd";
		$this->owner = $owner;
		$this->path = $path;
		$this->drate = $drate;
		$this->retries = $retries;
		$this->pasv = $pasv;
		// set admin-var
		$cfg['isAdmin'] = IsAdmin($this->owner);
		// init object-instances
		// sf
		$this->_sf = new StatFile($this->transfer, $this->owner);
		// ch
		$this->_ch = ClientHandler::getInstance('wget');
		$this->_ch->setVarsFromTransfer($this->transfer);
    }

	// =========================================================================
	// public methods
	// =========================================================================

	/**
	 * start client
	 *
	 * @return boolean
	 */
	function start() {
		return $this->_wrapperStart();
	}

	/**
	 * stop client
	 */
	function stop() {
		$this->_running = WGET_STATE_NULL;
	}

	// =========================================================================
	// private methods
	// =========================================================================

	/**
	 * main
	 */
	function _main() {

		// print
		$this->_outputMessage("wget up and running\n");

		// process header
		$this->_outputMessage("processing header...\n");
		$this->_processHeader();

		// main loop
		$this->_outputMessage("starting download and entering main-loop...\n");
		$tick = 0;
		while ($this->_running) {

			$this->_processBuffer();

			$this->_outputMessage("tick ".$tick."\n");

			if ($this->_clientIsRunning()) {
				$this->_outputMessage("proc running. pid : ".$this->pid."\n");
			} else {
				$this->_outputMessage("proc NOT running. pid : ".$this->pid."\n");
			}

			sleep(1);
			if ($tick == 60)
				$this->_running = WGET_STATE_NULL;
			else
				$tick++;
		}

		// stop
		$this->_wrapperStop();
	}

	/**
	 * process header
	 */
	function _processHeader() {

		// TODO

	}

	/**
	 * process buffer
	 */
	function _processBuffer() {

		// TODO

	}

	/**
	 * process command stack
	 *
	 * @return boolean
	 */
	function _processCommandStack() {

		// TODO

		return false;
	}

	/**
	 * exec a command
	 *
	 * @param $command
	 * @return boolean
	 */
	function _execCommand($command) {

		// TODO

		return false;
	}

	/**
	 * start wrapper
	 *
	 * @return boolean
	 */
	function _wrapperStart() {
		global $cfg;

		// print startup
		$this->_outputMessage("wget-wrapper starting up :\n");
		$this->_outputMessage(" - transfer : ".$this->transfer."\n");
		$this->_outputMessage(" - owner : ".$this->owner."\n");
		$this->_outputMessage(" - path : ".$this->path."\n");
		$this->_outputMessage(" - drate : ".$this->drate."\n");
		$this->_outputMessage(" - retries : ".$this->retries."\n");
		$this->_outputMessage(" - pasv : ".$this->pasv."\n");

		// write stat-file
		$this->_statStartup();

		// signal-handler (unsure if this is common so it is in a conditional)
		if (function_exists("pcntl_signal")) {
			$this->_outputMessage("setting up signal-handlers...\n");
			pcntl_signal(SIGHUP, array($this, "_sigHandler"));
			pcntl_signal(SIGINT, array($this, "_sigHandler"));
			pcntl_signal(SIGTERM, array($this, "_sigHandler"));
			pcntl_signal(SIGQUIT, array($this, "_sigHandler"));
		}

		// print startup
		$this->_outputMessage("wget-wrapper starting up wget...\n");

		// start client
		if (!$this->_clientStart()) {
			// error
			$this->_outputError("error starting up wget, shutting down...\n");
			// stop
			$this->_wrapperStop();
			// return
			return false;
		}

		// return
		return true;
	}

	/**
	 * start client
	 *
	 * @return boolean
	 */
	function _clientStart() {
		global $cfg;

		// command-string
		$this->command = "cd ".$this->path.";";
		$this->command .= " HOME=".$this->path."; export HOME;";
		if ($cfg["enable_umask"] != 0)
		    $this->command .= " umask 0000;";
		if ($cfg["nice_adjust"] != 0)
		    $this->command .= " nice -n ".$cfg["nice_adjust"];
		$this->command .= " ".$cfg['bin_wget'];
		$this->command .= " -c";
		if (($this->drate != "") && ($this->drate != "0"))
			$this->command .= " --limit-rate=" . $this->drate;
		if ($this->retries != "")
			$this->command .= " -t ".$this->retries;
		if ($this->pasv == 1)
			$this->command .= " --passive-ftp";
		$this->command .= " -i ".escapeshellarg($cfg['transfer_file_path'].$this->transfer);
		$this->command .= " 2>&1"; // direct STDERR to STDOUT
		$this->command .= " & echo $! > ".$cfg['transfer_file_path'].$this->transfer.".pid"; // write pid-file

		// print startup
		$this->_outputMessage("executing command : \n".$this->command."\n", true);

		// start process
		$this->wget = popen($this->command, 'r');

		// check for error
		if ($this->wget === false) {
			// error
			return false;
		}

		// running-flag
		$this->_running = WGET_STATE_RUNNING;

		// state
		$this->state = WGET_STATE_RUNNING;

		// wait for 0.5 seconds
		usleep(500000);

		// get + set pid
		$this->pid = getTransferPid($this->transfer);

		// enter main
		$this->_main();

		// return
		return true;
	}

	/**
	 * stop wrapper
	 */
	function _wrapperStop() {

		// output
		$this->_outputMessage("wget-wrapper shutting down...\n");

		if ($this->state == WGET_STATE_RUNNING) {

			// stop client
			$this->_clientStop();

			// state
			$this->state = WGET_STATE_NULL;
		}

		// stop transfer settings
		stopTransferSettings($this->transfer);

		// stat
		$this->_statShutdown();

		// pid
		$this->_pidFileDelete();

		// output
		$this->_outputMessage("wget-wrapper exit.\n");
	}

	/**
	 * stop client
	 */
	function _clientStop() {


		// TODO

		// close process
		$this->_outputMessage("closing wget-handle...\n");
		pclose($this->wget);

	}

	/**
	 * check if client-process is running
	 *
	 * @return boolean
	 */
	function _clientIsRunning() {
		return (strpos(exec('ps --pid '.escapeshellarg($this->pid)), $this->pid) !== false);
	}

	/**
	 * stat-file before startup
	 *
	 * @return boolean
	 */
	function _statStartup() {
		// set some values
		$this->_sf->running = 1;
		$this->_sf->percent_done = 0;
		$this->_sf->time_left = "Starting...";
		$this->_sf->down_speed = "0.00 kB/s";
		$this->_sf->up_speed = "0.00 kB/s";
		$this->_sf->transferowner = $this->owner;
		$this->_sf->seeds = 1;
		$this->_sf->peers = 1;
		$this->_sf->sharing = "";
		$this->_sf->seedlimit = "";
		$this->_sf->uptotal = 0;
		$this->_sf->downtotal = 0;
		// write
		return $this->_sf->write();
	}

	/**
	 * stat-file while running
	 *
	 * @return boolean
	 */
	function _statRunning() {
		// set some values
		$this->_sf->percent_done = $this->percent_done;
		$this->_sf->time_left = $this->time_left;
		$this->_sf->down_speed = $this->down_speed;
		$this->_sf->downtotal = $this->downtotal;
		// write
		return $this->_sf->write();
	}

	/**
	 * stat-file after shutdown
	 *
	 * @return boolean
	 */
	function _statShutdown() {
		// set some values
		$this->_sf->running = 0;
		if ($this->_done) {
			$this->_sf->percent_done = 100;
			$this->_sf->time_left = "Download Succeeded!";
		} else {
			$this->_sf->percent_done = ($this->size == 0) ? "-100" : (((int(100.0 * $this->downtotal / $this->size)) + 100) * (-1));
			$this->_sf->time_left = "Transfer Stopped";
		}
		$this->_sf->down_speed = "";
		$this->_sf->up_speed = "";
		$this->_sf->transferowner = $this->owner;
		$this->_sf->seeds = "";
		$this->_sf->peers = "";
		$this->_sf->sharing = "";
		$this->_sf->seedlimit = "";
		$this->_sf->uptotal = 0;
		$this->_sf->downtotal = $this->downtotal;
		$this->_sf->size = $this->size;
		// write
		return $this->_sf->write();
	}

	/**
	 * delete the pid-file
	 */
	function _pidFileDelete() {
		global $cfg;
		if (@file_exists($cfg['transfer_file_path'].$this->transfer.".pid")) {
			$this->_outputMessage("removing pid-file : ".$cfg['transfer_file_path'].$this->transfer.".pid\n");
			@unlink($cfg['transfer_file_path'].$this->transfer.".pid");
		}
	}

	/**
	 * signal-handler
	 *
	 * @param $signal
	 */
	function _sigHandler($signal) {
		switch ($signal) {
			// HUP
			case SIGHUP:
				$this->_outputMessage("got SIGHUP, ignoring...\n");
				break;
			// INT
			case SIGINT:
				$this->_outputMessage("got SIGINT, setting shutdown-flag...\n");
				$this->_running = WGET_STATE_NULL;
				break;
			// TERM
			case SIGTERM:
				$this->_outputMessage("got SIGTERM, setting shutdown-flag...\n");
				$this->_running = WGET_STATE_NULL;
				break;
			// QUIT
			case SIGQUIT:
				$this->_outputMessage("got SIGQUIT, setting shutdown-flag...\n");
				$this->_running = WGET_STATE_NULL;
				break;
		}
	}

    /**
     * output message
     *
     * @param $message
     */
	function _outputMessage($message) {
		@fwrite(STDOUT, @date("[Y/m/d - H:i:s]")." ".$message);
    }

    /**
     * output error
     *
     * @param $message
     */
	function _outputError($message) {
		@fwrite(STDERR, @date("[Y/m/d - H:i:s]")." ".$message);
    }

}

?>