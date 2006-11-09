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

// class Fluxd for integration of fluxd
class Fluxd
{
	// fields

	// version
    var $version = "";

    // pid
    var $pid;

    // config-array
    var $cfg = array();

    // messages-string
    var $messages = "";

    // state
    //  0 : not initialized
    //  1 : initialized
    //  2 : started/running
    // -1 : error
    var $state = 0;

    // some path-vars for Fluxd
    var $pathDataDir = "";
    var $pathPidFile = "";
    var $pathSocket = "";

    // socket-timeout
    var $socketTimeout = 5;

    // ctor

    /**
     * ctor
     */
    function Fluxd($cfg) {
        // version
		$this->version = "0.1";
		//
        $this->cfg = unserialize($cfg);
        if (empty($this->cfg)) {
            $this->messages = "Config not passed";
            $this->state = -1;
            return null;
        }
        $this->pathDataDir = $this->cfg["path"] . '.fluxd/';
        $this->pathPidFile = $this->pathDataDir . 'fluxd.pid';
        $this->pathSocket = $this->pathDataDir . 'fluxd.sock';
        $this->state = 1;
    }

    // public meths

    /**
     * startFluxd
     * @return boolean
     */
    function startFluxd() {
        if ($this->isFluxdRunning()) {
            AuditAction($this->cfg["constants"]["fluxd"], "fluxd already started");
            return true;
        } else {
            $startCommand = "cd ".$this->cfg["docroot"]." ; HOME=".$this->cfg["path"].";";
            $startCommand .= " export HOME;";
            $startCommand .= " nohup " . $this->cfg["perlCmd"];
            $startCommand .= " -I ".escapeshellarg($this->cfg["docroot"]."bin/fluxd");
            $startCommand .= " ".escapeshellarg($this->cfg["docroot"]."bin/fluxd/fluxd.pl");
            $startCommand .= " daemon-start";
            $startCommand .= " ".escapeshellarg($this->cfg["docroot"]);
            $startCommand .= " ".escapeshellarg($this->cfg["bin_php"]);
            $startCommand .= " ".escapeshellarg($this->cfg["fluxd_dbmode"]);
            $startCommand .= " > /dev/null &";
            $result = exec($startCommand);
            // check if fluxd could be started
            $loop = true;
            $maxLoops = 75;
            $loopCtr = 0;
            $started = false;
            while ($loop) {
            	if ($this->isFluxdRunning()) {
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
            	AuditAction($this->cfg["constants"]["fluxd"], "fluxd started");
            	// Set the state
            	$this->state = 2;
            	// return
            	return true;
            } else {
            	AuditAction($this->cfg["constants"]["fluxd"], "errors starting fluxd");
            	// set messages to startcommand for debug
            	$this->messages = $startCommand;
            	// Set the state
            	$this->state = -1;
            	// return
            	return false;
            }
        }
    }

    /**
     * stopFluxd
     */
    function stopFluxd() {
        AuditAction($this->cfg["constants"]["fluxd"], "Stopping fluxd");
        if ($this->isFluxdRunning()) {
            $this->sendCommand('die', 0);
            // check if fluxd still running
            $maxLoops = 75;
            $loopCtr = 0;
            while (1) {
            	if ($this->isFluxdRunning()) {
	            	$loopCtr++;
	            	if ($loopCtr > $maxLoops)
	            		return 0;
	            	else
	            		usleep(200000); // wait for 0.2 seconds
            	} else {
            		return 1;
            	}
            }
            return 0;
        }
    }

    /**
     * getFluxdPid
     * @return int with pid
     */
    function getFluxdPid() {
        if($fileHandle = @fopen($this->pathPidFile,'r')) {
            $data = "";
            while (!@feof($fileHandle))
                $data .= @fgets($fileHandle, 1024);
            @fclose ($fileHandle);
            $this->pid = trim($data);
            return $this->pid;
        } else {
            return "";
        }
    }

    /**
     * statusFluxd
     * @return string
     */
    function statusFluxd() {
        if ($this->isFluxdRunning())
            return $this->sendCommand('status', 1);
        else
            return "";
    }

    /**
     * modState
     * @param name of service-module (Qmgr|Fluxinet|Trigger|Watch|Clientmaint)
     * @return int with mod-state
     */
    function modState($mod) {
        if ($this->isFluxdRunning())
            return (int) $this->sendCommand('modstate '.$mod, 1);
        else
            return 0;
    }

    /**
     * isFluxdRunning
     * @return boolean
     */
    function isFluxdRunning() {
        if (isset($this->pathPidFile) && ($this->pathPidFile != ""))
            return file_exists($this->pathPidFile);
        else
            return false;
    }

    /**
     * isFluxdReadyToStart
     * @return boolean
     */
    function isFluxdReadyToStart() {
        if ($this->isFluxdRunning() != 0)
            return false;
        else # pid-file exists, but is the daemon trying to shut down?
            return (!($this->sendCommand('worker', 0)));
    }

    /**
     * setConfig
     * @param $key, $value
     * @return Null
     */
    function setConfig($key, $value) {
       if ($this->isFluxdRunning())
           $this->sendCommand('set '.$key.' '.$value, 0);
    }

	/**
	 * reloadDBCache
	 *
	 */
    function reloadDBCache() {
		if ($this->isFluxdRunning())
			$this->sendCommand('reloadDBCache', 0);
    }

	/**
	 * reloadModules
	 *
	 */
    function reloadModules() {
		if ($this->isFluxdRunning())
			$this->sendCommand('reloadModules', 0);
    }

    // private meths

    /**
     * send command
     * @param $command
     * @param $read does this command return something ?
     * @return string with retval or null if error
     */
    function sendCommand($command, $read = 0) {
        if ($this->isFluxdRunning()) {

        	// create socket
        	$socket = -1;
            $socket = @socket_create(AF_UNIX, SOCK_STREAM, 0);
            if ($socket < 0) {
            	$this->messages = "socket_create() failed: reason: " . @socket_strerror($socket);
            	$this->state = -1;
                return null;
            }

            //timeout after 3 seconds
    		@socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $this->socketTimeout, 'usec' => 0));

            // connect
            $result = -1;
            $result = @socket_connect($socket, $this->pathSocket);
            if ($result < 0) {
            	$this->messages = "socket_connect() failed: reason: " . @socket_strerror($result);
            	$this->state = -1;
                return null;
            }

            // write command
            @socket_write($socket, $command."\n");

            // read retval
            $return = "";
            if ($read != 0) {
				do {
					// read data
					$data = @socket_read($socket, 4096, PHP_BINARY_READ);
					$return .= $data;
				} while (isset($data) && ($data != ""));
            }

            // close socket
            @socket_close($socket);

            // return
            return $return;

        } else { // fluxd not running
        	return null;
        }
    }
}

?>