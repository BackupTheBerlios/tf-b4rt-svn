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


// class Fluxd for managing the Fluxd Daemon
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

    // ctor

    /**
     * ctor
     */
    function Fluxd($cfg) {
    	$this->version = array_shift(explode(" ",trim(array_pop(explode(":",'$Revision$')))));
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
            $startCommand .= " -I ".$this->cfg["docroot"]."bin/fluxd";
            $startCommand .= " ".$this->cfg["docroot"]."bin/fluxd/fluxd.pl";
            $startCommand .= " daemon-start";
            $startCommand .= " ".escapeshellarg($this->cfg["docroot"]);
            $startCommand .= " ".escapeshellarg($this->cfg["bin_php"]);
            $startCommand .= " > /dev/null &";
            $result = exec($startCommand);
            // give fluxd some time
            sleep(8);
            // check if started
            if ($this->isFluxdRunning()) {
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
            // give fluxd some time
            sleep(6);
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
            $socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
            if ($socket < 0) {
            	$this->messages = "socket_create() failed: reason: " . socket_strerror($socket);
            	$this->state = -1;
                return null;
            }

            //timeout after 3 seconds
    		socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>3,'usec'=>0));

            // connect
            $result = socket_connect($socket, $this->pathSocket);
            if ($result < 0) {
            	$this->messages = "socket_connect() failed: reason: " . socket_strerror($result);
            	$this->state = -1;
                return null;
            }

            // write command
            socket_write($socket, $command."\n");

            // read retval
            $return = "";
            if ($read != 0) {
	            // read data
				$data = socket_read($socket, 4096, PHP_BINARY_READ);
				while (isset($data) && ($data != "")) {
					$return .= $data;
					$data = socket_read($socket, 4096, PHP_BINARY_READ);
				}
            }

            // close socket
            socket_close($socket);

            // return
            return $return;

        } else { // fluxd not running
        	return null;
        }
    }
}

?>