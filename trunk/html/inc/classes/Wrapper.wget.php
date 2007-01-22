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

	// speed
	var $speed = 0;

	// vars from args
	var $transfer = "";
	var $transferFile = "";
	var $owner = "";
	var $path = "";
	var $drate = 0;
	var $retries = 0;
	var $pasv = "";

	// private fields

    // running-flag
    var $_running = WGET_STATE_NULL;

	// statfile-object-instance
	var $_sf = undef;

	// clienthandler-object-instance
	var $_ch = undef;

	// process-handle
	var $_wget = undef;

	// buffer
	var $_buffer = "";

    // stat-file-vars
	var $_s_running = 1;
	var $_s_size = 0;
	var $_s_downtotal = 0;
	var $_s_percent_done = 0;
	var $_s_down_speed = "0.00 kB/s";
	var $_s_time_left = '-';

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
    	// init fields
    	$this->state = WGET_STATE_NULL;
        $this->messages = array();
        $this->_buffer = "";
        // set fields from params
		$this->transferFile = $file;
		$this->transfer = str_replace($cfg['transfer_file_path'], '', $file);
		$this->owner = $owner;
		$this->path = $path;
		$this->drate = $drate;
		$this->retries = $retries;
		$this->pasv = $pasv;
		// init object-instances
		//$this->_sf
		//$this->_ch

    }

	// =========================================================================
	// public methods
	// =========================================================================

	/**
	 * start client
	 *
	 */
	function start() {
		$this->_clientStartup();
	}

	/**
	 * stop client
	 */
	function stop() {
		$this->_clientShutdown();
	}

	// =========================================================================
	// private methods
	// =========================================================================

	/**
	 * main
	 */
	function _main() {
		// TODO
	}

	/**
	 * process header
	 */
	function _processHeader() {
		// TODO
	}

	/**
	 * processBuffer
	 *
	 * @param $data
	 */
	function processBuffer() {
		// TODO
	}

	/**
	 * process the command stack
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
	 * stat-file before startup
	 */
	function _writeStatStartup() {
		// TODO
	}

	/**
	 * stat-file while running
	 */
	function _writeStatRunning() {
		// TODO
	}

	/**
	 * stat-file after shutdown
	 */
	function _writeStatShutdown() {
		// TODO
	}

	/**
	 * delete the pid-file
	 */
	function _pidFileDelete() {
		global $cfg;
		$this->_outputMessage("removing pid-file : ".$cfg['transfer_file_path'].$this->transfer.".pid\n");
		@unlink($cfg['transfer_file_path'].$this->transfer.".pid");
	}

	/**
	 * startup client
	 */
	function _clientStartup() {
		// TODO
	}

	/**
	 * shutdown client
	 */
	function _clientShutdown() {
		$this->_running = WGET_STATE_NULL;
	}

	/**
	 * signal-handler
	 *
	 * @param $signal
	 */
	function _sigHandler($signal) {
		// TODO
	}

    /**
     * output message
     *
     * @param $message
     */
	function _outputMessage($message) {
		printMessage($message);
    }

    /**
     * output error
     *
     * @param $message
     */
	function _outputError($message) {
		printError($message);
    }

}

?>