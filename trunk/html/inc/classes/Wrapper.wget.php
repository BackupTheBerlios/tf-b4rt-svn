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

    // state
    var $state = WGET_STATE_NULL;

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
	 * start
	 *
	 * @return boolean
	 */
	function start() {
		// start
		$this->_wrapperStart();
		// main
		$mainRet = $this->_wrapperMain();
		// stop
		$this->_wrapperStop(!$mainRet);
	}

	/**
	 * stop
	 */
	function stop() {
		// stop
		$this->_wrapperStop(false);
	}

	// =========================================================================
	// private methods
	// =========================================================================

	/**
	 * wrapper main
	 *
	 * @return boolean
	 */
	function _wrapperMain() {

		// print
		$this->_outputMessage("wget up and running\n");

		// process header
		$this->_processHeader();

		// flush buffer
		$this->_buffer = "";

		// main loop
		$this->_outputMessage("download started, entering main-loop...\n");
		$tick = 1;
		for (;;) {

			// read to buffer
			if (!@feof($this->wget))
				$this->_buffer .= @fread($this->wget, 8192);

			// process buffer
			$this->_done = $this->_processBuffer();

			// return if done
			if ($this->_done)
				return true;

			// _processCommandStack, return if quit
			if ($this->_processCommandStack())
				return true;

			// write stat-file every 5 secs
			if (($tick % 5) == 0)
				$this->_statRunning();

			// check if client is still up once a minute
			if (($tick % 60) == 0) {
				if ($this->_clientIsRunning() === false) {
					$this->_outputMessage("wget-client not running. initializing shutdown... (pid: ".$this->pid.")\n");
					return false;
				}
			}

			// check buffer-size, truncate if needed
			if (strlen($this->_buffer) > 16384)
				$this->_buffer = substr($this->_buffer, -1024);

			// sleep 1 second and increment tick-counter
			sleep(1);
			$tick++;
			if ($tick < 0) $tick = 1;
		}

		// return
		return true;
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
			$this->_outputMessage("setting up signal-handler...\n");
			pcntl_signal(SIGHUP, array($this, "_sigHandler"));
			pcntl_signal(SIGINT, array($this, "_sigHandler"));
			pcntl_signal(SIGTERM, array($this, "_sigHandler"));
			pcntl_signal(SIGQUIT, array($this, "_sigHandler"));
		}

		// start client
		if (!$this->_clientStart()) {
			// stop
			$this->_wrapperStop(true);
			// return
			return false;
		}

		// return
		return true;
	}

	/**
	 * stop wrapper
	 *
	 * @param $error
	 */
	function _wrapperStop($error = false) {

		// output
		$this->_outputMessage("wget-wrapper shutting down...\n");

		// state
		$this->state = WGET_STATE_NULL;

		// stop client
		$this->_clientStop();

		// transfer settings
		stopTransferSettings($this->transfer);

		// stat
		$this->_statShutdown($error);

		// pid
		$this->_pidFileDelete();

		// output
		$this->_outputMessage("wget-wrapper exit.\n");

		// exit
		exit();
	}

	/**
	 * start client
	 *
	 * @return boolean
	 */
	function _clientStart() {
		global $cfg;

		// print startup
		$this->_outputMessage("starting up wget-client...\n");

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
		$this->wget = @popen($this->command, 'r');

		// wait for 1 second
		sleep(1);

		// get + set pid
		$this->pid = getTransferPid($this->transfer);

		// check for error
		if (($this->wget === false) || ($this->_clientIsRunning() === false)) {
			// error
			$this->_outputError("error starting up wget-client, shutting down...\n");
			// return
			return false;
		}

		// state
		$this->state = WGET_STATE_RUNNING;

		// output
		$this->_outputMessage("wget-client started. (pid: ".$this->pid.")\n");

		// return
		return true;
	}

	/**
	 * stop client
	 *
	 * @return boolean
	 */
	function _clientStop() {

		// close handle
		// weird : instance-field is not set in sig-call if field is not set in ctor
		if ((!empty($this->wget)) && (is_resource($this->wget))) {
			$this->_outputMessage("closing process-handle...\n");
			@pclose($this->wget);
		}

		// try to kill if running
		// weird : instance-field is not set in sig-call if field is not set in ctor
		if (empty($this->pid))
			$this->pid = getTransferPid($this->transfer);
		if ($this->_clientIsRunning()) {
			// send SIGTERM
			$this->_outputMessage("sending SIGTERM to wget-client... (pid: ".$this->pid.")\n");
			posix_kill($this->pid, SIGTERM);
			// give it 1 second
			sleep(1);
			// check if running
			if ($this->_clientIsRunning()) {
				$this->_outputMessage("wget-client still running 1 second after SIGTERM. waiting another second... (pid: ".$this->pid.")\n");
				sleep(1);
				if ($this->_clientIsRunning()) {
					// send SIGKILL
					$this->_outputMessage("wget-client still running after another second. sending SIGKILL... (pid: ".$this->pid.")\n");
					posix_kill($this->pid, SIGKILL);
					// give it 2 seconds
					sleep(2);
					// check if running
					if ($this->_clientIsRunning()) {
						$this->_outputMessage("wget-client still running 2 seconds after SIGKILL. giving up. (pid: ".$this->pid.")\n");
						return false;
					}
				}
			}
			// output
			$this->_outputMessage("wget-client stopped. (pid: ".$this->pid.")\n");
		} else {
			$this->_outputMessage("wget-client not running. (pid: ".$this->pid.")\n");
		}

		// return
		return true;
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
	 * process header
	 *
	 * @return boolean
	 */
	function _processHeader() {

		// output
		$this->_outputMessage("starting download...\n");

		// flush buffer
		$this->_buffer = "";

		// read until we find the Length-string which indicates dl-start
		$ctr = 0;
		while (($ctr < 32) && (!@feof($this->wget))) {

			// read
			$this->_buffer .= @fread($this->wget, 256);

			// check for Length
			if (preg_match("/.*Length:\s(.+)\s\[.*/i", $this->_buffer, $matches)) {
				// set size
				$this->size = str_replace(',','', $matches[1]);
				// set size in stat-file
				$this->_sf->size = $this->size;
				// return
				return true;
			}

			// wait for 0.25 seconds
			usleep(250000);

			// increment counter
			$ctr++;
		}

		// there were problems
		$this->_outputMessage("problems when processing header...\n");

		// set size from sf
		$this->_outputMessage("try to set size from stat-file...\n");
		if (!empty($this->_sf->size)) {
			$this->_outputMessage("set size from stat-file :".formatBytesTokBMBGBTB($this->_sf->size)."\n");
			$this->size = $this->_sf->size;
			// return
			return true;
		}

		// give up, then we got no size
		$this->_outputError("failed to get size for download.\n");

		// set size to 0
		$this->size = 0;

		// set size in stat-file
		$this->_sf->size = $this->size;

		// return
		return false;
	}

	/**
	 * process buffer
	 *
	 * @return boolean
	 */
	function _processBuffer() {

		// downtotal
		if (preg_match_all("/(\d*)K\s\./i", $this->_buffer, $matches, PREG_SET_ORDER))
			$this->downtotal = $matches[count($matches) - 1][1] << 10;

		// percent_done + down_speed + _speed
		if (preg_match_all("/(\d*)%(\s*)(.*)\/s/i", $this->_buffer, $matches, PREG_SET_ORDER)) {
			$matchIdx = count($matches) - 1;
			// percentage
			$this->percent_done = $matches[$matchIdx][1];
			// speed
			$this->down_speed = $matches[$matchIdx][3]."/s";
			// we dont want upper-case k
			$this->down_speed = str_replace("KB/s", "kB/s", $this->down_speed);
			// size as int + convert MB/s
			$sizeTemp = substr($this->down_speed, 0, -5);
			if (is_numeric($sizeTemp)) {
				$this->_speed = intval($sizeTemp);
				if (substr($this->down_speed, -4) == "MB/s") {
					$this->_speed = $this->_speed << 10;
					$this->down_speed = $this->_speed." kB/s";
				}
			} else {
				$this->_speed = 0;
				$this->down_speed = "0.00 kB/s";
			}
		}

		// time left
		$this->time_left = (($this->size > 0) && ($this->_speed > 0))
			? convertTime((($this->size - $this->downtotal) >> 10) / $this->_speed)
			: '-';

		// download done
		if (preg_match("/.*saved\s\[.*/", $this->_buffer)) {
			$this->_outputMessage("download complete. initializing shutdown...\n");
			// return
			return true;
		}

		// return
		return false;
	}

	/**
	 * process command stack
	 *
	 * @return boolean
	 */
	function _processCommandStack() {

		// check for command-file
		if (@is_file($this->commandFile)) {

			// print
			$this->_outputMessage("processing command-file ".$this->commandFile."...\n");


			/* DEBUG */
			$cmd = 'foo';
			return $this->_execCommand($cmd);
			/* DEBUG */
		}

		// return
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
	 * stat-file at startup
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
	 * stat-file at shutdown
	 *
	 * @param $error
	 * @return boolean
	 */
	function _statShutdown($error = false) {
		// set some values
		$this->_sf->running = 0;
		if ($this->_done) {
			$this->_sf->percent_done = 100;
			$this->_sf->time_left = "Download Succeeded!";
		} else {
			$this->_sf->percent_done = ($this->size == 0) ? "-100" : ((((int)(100.0 * $this->downtotal / $this->size)) + 100) * (-1));
			$this->_sf->time_left = "Transfer Stopped";
		}
		if ($error)
			$this->_sf->time_left = "Error";
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
				$this->_outputMessage("got SIGINT, shutting down...\n");
				$this->_wrapperStop(false);
				break;
			// TERM
			case SIGTERM:
				$this->_outputMessage("got SIGTERM, shutting down...\n");
				$this->_wrapperStop(false);
				break;
			// QUIT
			case SIGQUIT:
				$this->_outputMessage("got SIGQUIT, shutting down...\n");
				$this->_wrapperStop(false);
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