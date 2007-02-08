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
define('FLUAZU_STATE_NULL', 0);                                          // null
define('FLUAZU_STATE_RUNNING', 1);                                   //  running
define('FLUAZU_STATE_ERROR', -1);                                       // error

/**
 * class FluAzu for integration of fluazu
 */
class FluAzu
{
	// public fields

    // state
    var $state = FLUAZU_STATE_NULL;

    // messages-array
    var $messages = array();

    // private fields

    // pid
    var $_pid = "";

    // some path-vars for FluAzu
    var $_pathDataDir = "";
	var $_pathCommandFile = "";
    var $_pathPidFile = "";
    var $_pathLogFile = "";
    var $_pathLogFileError = "";
    var $_pathTransfers = "";
    var $_pathTransfersRun = "";
    var $_pathTransfersDel = "";

	// =========================================================================
	// public static methods
	// =========================================================================

    /**
     * accessor for singleton
     *
     * @return FluAzu
     */
    function getInstance() {
		global $instanceFluAzu;
		// initialize if needed
		if (!isset($instanceFluAzu))
			FluAzu::initialize();
		return $instanceFluAzu;
    }

    /**
     * initialize FluAzu.
     */
    function initialize() {
    	global $instanceFluAzu;
    	// create instance
    	if (!isset($instanceFluAzu))
    		$instanceFluAzu = new FluAzu();
    }

    /**
     * accessor for state
     *
     * @return int
     */
    function getState() {
		global $instanceFluAzu;
		return (isset($instanceFluAzu))
			? $instanceFluAzu->state
			: FLUAZU_STATE_NULL;
    }

    /**
     * getMessages
     *
     * @return array
     */
    function getMessages() {
    	global $instanceFluAzu;
		return (isset($instanceFluAzu))
			? $instanceFluAzu->messages
			: array();
    }

    /**
     * isRunning
     *
     * @return boolean
     */
    function isRunning() {
    	global $instanceFluAzu;
		// initialize if needed
		if (!isset($instanceFluAzu))
			FluAzu::initialize();
		return ($instanceFluAzu->state == FLUAZU_STATE_RUNNING);
    }

	/**
     * start
     *
     * @return boolean
     */
    function start() {
		global $instanceFluAzu;
		// initialize if needed
		if (!isset($instanceFluAzu))
			FluAzu::initialize();
		return $instanceFluAzu->instance_start();
    }

    /**
     * stop
     */
    function stop() {
		global $instanceFluAzu;
		// initialize if needed
		if (!isset($instanceFluAzu))
			FluAzu::initialize();
		return $instanceFluAzu->instance_stop();
    }

    /**
     * getPid
     *
     * @return int with pid
     */
    function getPid() {
    	global $instanceFluAzu;
		// initialize if needed
		if (!isset($instanceFluAzu))
			FluAzu::initialize();
		return $instanceFluAzu->instance_getPid();
    }

    /**
     * writes a message to the log
     *
     * @param $message
     * @param $withTS
     * @return boolean
     */
    function logMessage($message, $withTS = true) {
    	global $instanceFluAzu;
 		// initialize if needed
		if (!isset($instanceFluAzu))
			FluAzu::initialize();
		return $instanceFluAzu->instance_logMessage($message, $withTS);
    }

    /**
     * writes a message to the error-log
     *
     * @param $message
     * @param $withTS
     * @return boolean
     */
    function logError($message, $withTS = true) {
    	global $instanceFluAzu;
		// initialize if needed
		if (!isset($instanceFluAzu))
			FluAzu::initialize();
		return $instanceFluAzu->instance_logError($message, $withTS);
    }

    /**
     * send command
     *
     * @param $command
     * @return boolean
     */
    function sendCommand($command) {
    	global $instanceFluAzu;
		// initialize if needed
		if (!isset($instanceFluAzu))
			FluAzu::initialize();
		return $instanceFluAzu->instance_sendCommand($command);
    }

	// =========================================================================
	// ctor
	// =========================================================================

    /**
     * ctor
     */
    function FluAzu() {
    	global $cfg;
    	// paths
        $this->_pathDataDir = $cfg["path"] . '.fluazu/';
        $this->_pathPidFile = $this->_pathDataDir . 'fluazu.pid';
        $this->_pathCommandFile = $this->_pathDataDir . 'fluxd.cmd';
        $this->_pathLogFile = $this->_pathDataDir . 'fluxd.log';
        $this->_pathLogFileError = $this->_pathDataDir . 'fluxd-error.log';
        $this->_pathTransfers = $this->_pathDataDir . 'cur/';
        $this->_pathTransfersRun = $this->_pathDataDir . 'run/';
        $this->_pathTransfersDel = $this->_pathDataDir . 'del/';
        // check path
		if (!checkDirectory($this->_pathDataDir))
			@error("fluazu-Main-Path does not exist and cannot be created or is not writable", "admin.php?op=serverSettings", "Server-Settings", array("path : ".$this->_pathDataDir));
        // check if fluxd running
        if ($this->_isRunning())
        	$this->state = FLUAZU_STATE_RUNNING;
    }

	// =========================================================================
	// public methods
	// =========================================================================

    /**
     * instance_start
     *
     * @return boolean
     */
    function instance_start() {

    	return true;

    	global $cfg;
        if ($this->state == FLUAZU_STATE_RUNNING) {
            AuditAction($cfg["constants"]["fluxd"], "fluxd already started");
            return true;
        } else {
            $startCommand = "cd ".$cfg["docroot"]." ; HOME=".$cfg["path"].";";
            $startCommand .= " export HOME;";
            $startCommand .= " nohup " . $cfg["perlCmd"];
            $startCommand .= " -I ".escapeshellarg($cfg["docroot"]."bin/fluxd");
            $startCommand .= " -I ".escapeshellarg($cfg["docroot"]."bin/lib");
            $startCommand .= " ".escapeshellarg($cfg["docroot"]."bin/fluxd/fluxd.pl");
            $startCommand .= " start";
            $startCommand .= " ".escapeshellarg($cfg["docroot"]);
            $startCommand .= " ".escapeshellarg($cfg["path"]);
            $startCommand .= " ".escapeshellarg($cfg["bin_php"]);
            $startCommand .= " ".escapeshellarg($cfg["fluxd_dbmode"]);
	        $startCommand .= " 1>> ".escapeshellarg($this->_pathLogFile);
	        $startCommand .= " 2>> ".escapeshellarg($this->_pathLogFileError);
	        $startCommand .= " &";
            $result = exec($startCommand);
            // check if fluxd could be started
            $loop = true;
            $maxLoops = 125;
            $loopCtr = 0;
            $started = false;
            while ($loop) {
            	if ($this->_isRunning()) {
            		$started = true;
            		$loop = false;
            	} else {
	            	$loopCtr++;
	            	if ($loopCtr > $maxLoops)
	            		$loop = false;
	            	else
	            		usleep(200000); // wait for 0.2 seconds
            	}
            }
            // check if started
            if ($started) {
            	AuditAction($cfg["constants"]["fluxd"], "fluxd started");
            	// Set the state
            	$this->state = FLUAZU_STATE_RUNNING;
            	// return
            	return true;
            } else {
            	AuditAction($cfg["constants"]["fluxd"], "errors starting fluxd");
            	// add startcommand to messages for debug
            	// TODO : set better message
            	array_push($this->messages , $startCommand);
            	// Set the state
            	$this->state = FLUAZU_STATE_ERROR;
            	// return
            	return false;
            }
        }
    }

    /**
     * instance_stop
     */
    function instance_stop() {

    	return true;

    	global $cfg;
        if ($this->state == FLUAZU_STATE_RUNNING) {
        	AuditAction($cfg["constants"]["fluxd"], "Stopping fluxd");
            $this->instance_sendCommand('die', 0);
            // check if fluxd still running
            $maxLoops = 125;
            $loopCtr = 0;
            while (1) {
            	if ($this->_isRunning()) {
	            	$loopCtr++;
	            	if ($loopCtr > $maxLoops)
	            		return 0;
	            	else
	            		usleep(200000); // wait for 0.2 seconds
            	} else {
            		// Set the state
            		$this->state = FLUAZU_STATE_NULL;
            		// return
            		return 1;
            	}
            }
            return 0;
        } else {
        	$msg = "errors stopping fluxd as was not running.";
        	AuditAction($cfg["constants"]["fluxd"], $msg);
        	array_push($this->messages , $msg);
            // Set the state
            $this->state = FLUAZU_STATE_ERROR;
			return 0;
        }
    }

    /**
     * instance_getPid
     *
     * @return string with pid
     */
    function instance_getPid() {
    	if ($this->_pid != "") {
    		return $this->_pid;
    	} else {
    		$this->_pid = @rtrim(file_get_contents($this->_pathPidFile));
    		return $this->_pid;
    	}
    }

    /**
     * writes a message to the log
     *
     * @param $message
     * @param $withTS
     * @return boolean
     */
    function instance_logMessage($message, $withTS = true) {
		return $this->_log($this->_pathLogFile, $message, $withTS);
    }

    /**
     * writes a message to the error-log
     *
     * @param $message
     * @param $withTS
     * @return boolean
     */
    function instance_logError($message, $withTS = true) {
		return $this->_log($this->_pathLogFileError, $message, $withTS);
    }

    /**
     * send command
     *
     * @param $command
     * @param $read does this command return something ?
     * @return string with retval or null if error
     */
    function instance_sendCommand($command) {
        if ($this->state == FLUAZU_STATE_RUNNING) {

        	// TODO

            // return
            return true;
        } else { // fluazu not running
        	return null;
        }
    }

    // =========================================================================
	// private methods
	// =========================================================================

    /**
     * _isRunning
     *
     * @return boolean
     */
    function _isRunning() {
    	return file_exists($this->_pathPidFile);
    }

    /**
     * log a message
     *
     * @param $logFile
     * @param $message
     * @param $withTS
     * @return boolean
     */
    function _log($logFile, $message, $withTS = false) {
    	$content = "";
    	if ($withTS)
    		$content .= @date("[Y/m/d - H:i:s]");
    	$content .= '[FRONTEND] ';
    	$content .= $message;
		$fp = false;
		$fp = @fopen($logFile, "a+");
		if (!$fp)
			return false;
		$result = @fwrite($fp, $content);
		@fclose($fp);
		if ($result === false)
			return false;
		return true;
    }

}

?>