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
		return $instanceFluAzu->instance_isRunning();
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

    /**
     * del transfer
     *
     * @param $transfer
     * @return boolean
     */
    function delTransfer($transfer) {
    	global $instanceFluAzu;
		// initialize if needed
		if (!isset($instanceFluAzu))
			FluAzu::initialize();
		return $instanceFluAzu->instance_delTransfer($transfer);
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
        $this->_pathCommandFile = $this->_pathDataDir . 'fluazu.cmd';
        $this->_pathLogFile = $this->_pathDataDir . 'fluazu.log';
        $this->_pathLogFileError = $this->_pathDataDir . 'fluazu-error.log';
        $this->_pathTransfers = $this->_pathDataDir . 'cur/';
        $this->_pathTransfersRun = $this->_pathDataDir . 'run/';
        $this->_pathTransfersDel = $this->_pathDataDir . 'del/';
        // check path
		if (!checkDirectory($this->_pathDataDir))
			@error("fluazu-Main-Path does not exist and cannot be created or is not writable", "admin.php?op=serverSettings", "Server-Settings", array("path : ".$this->_pathDataDir));
        // check if fluazu running
        if ($this->instance_isRunning())
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
    	global $cfg;
        if ($this->state == FLUAZU_STATE_RUNNING) {
            AuditAction($cfg["constants"]["admin"], "fluazu already started");
            return false;
        } else {
            $startCommand = "cd ".$cfg["docroot"]."bin/clients/fluazu/ ; HOME=".$cfg["path"].";";
            $startCommand .= " export HOME;";
            $startCommand .= " nohup";
            $startCommand .= " ".$cfg["pythonCmd"]." -OO";
            $startCommand .= " ".escapeshellarg("fluazu.py");
            $startCommand .= " ".escapeshellarg($cfg["path"]);
            $startCommand .= " ".escapeshellarg($cfg["fluazu_host"]);
            $startCommand .= " ".escapeshellarg($cfg["fluazu_port"]);
            $startCommand .= " ".escapeshellarg($cfg["fluazu_secure"]);
            $startCommand .= ($cfg["fluazu_user"] == "")
            	? ' ""'
            	: " ".escapeshellarg($cfg["fluazu_user"]);
            $startCommand .= ($cfg["fluazu_pw"] == "")
            	? ' ""'
            	: " ".escapeshellarg($cfg["fluazu_pw"]);
	        $startCommand .= " 1>> ".escapeshellarg($this->_pathLogFile);
	        $startCommand .= " 2>> ".escapeshellarg($this->_pathLogFileError);
	        $startCommand .= " &";
			// log the command
        	$this->instance_logMessage("executing command : \n".$startCommand."\n", true);
        	// exec
            $result = exec($startCommand);
            // check if fluazu could be started
            $loop = true;
            $maxLoops = 125;
            $loopCtr = 0;
            $started = false;
            while ($loop) {
            	if ($this->instance_isRunning()) {
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
            	AuditAction($cfg["constants"]["admin"], "fluazu started");
            	// Set the state
            	$this->state = FLUAZU_STATE_RUNNING;
            	// return
            	return true;
            } else {
            	AuditAction($cfg["constants"]["admin"], "errors starting fluazu");
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
    	global $cfg;
        if ($this->state == FLUAZU_STATE_RUNNING) {
        	AuditAction($cfg["constants"]["admin"], "Stopping fluazu");
            $this->instance_sendCommand('q');
            // check if fluazu still running
            $maxLoops = 125;
            $loopCtr = 0;
            while (1) {
            	if ($this->instance_isRunning()) {
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
        	$msg = "errors stopping fluazu as was not running.";
        	AuditAction($cfg["constants"]["admin"], $msg);
        	array_push($this->messages , $msg);
            // Set the state
            $this->state = FLUAZU_STATE_ERROR;
			return 0;
        }
    }

    /**
     * isRunning
     *
     * @return boolean
     */
    function instance_isRunning() {
    	return file_exists($this->_pathPidFile);
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
     * @return boolean
     */
    function instance_sendCommand($command) {
    	return ($this->state == FLUAZU_STATE_RUNNING)
    		? $this->_writeCommandFile($command."\n")
    		: false;
    }

    /**
     * del transfer
     *
     * @param $transfer
     * @return boolean
     */
    function instance_delTransfer($transfer) {
    	global $cfg;
        if ($this->state == FLUAZU_STATE_RUNNING) {
        	AuditAction($cfg["constants"]["admin"], "fluazu deleting transfer ".$transfer);
        	$file = $this->_pathTransfersDel.$transfer;
			$handle = false;
			$handle = @fopen($file, "w");
			if (!$handle) {
	            $msg = "cannot open file ".$file." for writing.";
	            array_push($this->_messages , $msg);
	            AuditAction($cfg["constants"]["error"], "FluAzu instance_delTransfer-Error : ".$msg);
				return false;
			}
	        $result = @fwrite($handle, $cfg['user']);
			@fclose($handle);
			if ($result === false) {
	            $msg = "cannot write content to file ".$file.".";
	            array_push($this->_messages , $msg);
	            AuditAction($cfg["constants"]["error"], "FluAzu instance_delTransfer-Error : ".$msg);
				return false;
			}
			// send reload-command
			return $this->instance_sendCommand('r');
        } else {
        	$msg = "fluazu not running, cannot delete transfer ".$transfer;
        	AuditAction($cfg["constants"]["admin"], $msg);
        	array_push($this->messages , $msg);
            // Set the state
            $this->state = FLUAZU_STATE_ERROR;
			return false;
        }
    }

    // =========================================================================
	// private methods
	// =========================================================================

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

     /**
     * write the command-file
     *
	 * @param $content
     * @return boolean
     */
	function _writeCommandFile($content) {
		global $cfg;
		$handle = false;
		$handle = @fopen($this->_pathCommandFile, "w");
		if (!$handle) {
            $msg = "cannot open command-file ".$this->_pathCommandFile." for writing.";
            array_push($this->_messages , $msg);
            AuditAction($cfg["constants"]["error"], "FluAzu _writeCommandFile-Error : ".$msg);
			return false;
		}
        $result = @fwrite($handle, $content);
		@fclose($handle);
		if ($result === false) {
            $msg = "cannot write content to command-file ".$this->_pathCommandFile.".";
            array_push($this->_messages , $msg);
            AuditAction($cfg["constants"]["error"], "FluAzu _writeCommandFile-Error : ".$msg);
			return false;
		}
		return true;
    }

}

?>