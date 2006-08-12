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
            $fluxd = "cd ".$this->cfg["fluxd_path_fluxcli"]."; HOME=".$this->cfg["path"]."; export HOME; nohup " . $this->cfg["perlCmd"] . " -I " .$this->cfg["fluxd_path"] ." ".$this->cfg["fluxd_path"] . "/fluxd.pl ";
            $startCommand = $fluxd . "daemon-start " . $this->cfg["fluxd_path_fluxcli"] . " > /dev/null &";
            $result = exec($startCommand);
            // give fluxd some time
            sleep(2);
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
        if ($this->isFluxdRunning())
            $this->sendCommand('die');
    }

    /**
     * getFluxdPid
     * @return int with pid
     */
    function getFluxdPid() {
        if($fileHandle = @fopen($pathPidFile,'r')) {
            $data = "";
            while (!@feof($fileHandle))
                $data .= @fgets($fileHandle, 1024);
            @fclose ($fileHandle);
            $this->pid = $data;
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
            return $this->sendCommand('status');
        else
            return "";
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
            return (!($this->sendCommand('worker')));
    }

    /**
     * setConfig
     * @param $key, $value
     * @return Null
     */
    function setConfig($key, $value) {
       if ($this->isFluxdRunning())
           $this->sendCommand('set '.$key.' '.$value);
    }


    // private meths

    /**
     * send command
     * @param $command
     * @return string with retval or null if error
     */
    function sendCommand($command) {
        if ($this->isFluxdRunning()) {
        	// create socket
            $socket = socket_create(AF_UNIX, SOCK_STREAM);
            if ($socket < 0) {
            	$this->messages = "socket_create() failed: reason: " . socket_strerror($socket);
            	$this->state = -1;
                return null;
            }
            // connect
            $result = socket_connect($socket, $this->pathSocket);
            if ($result < 0) {
            	$this->messages = "socket_connect() failed: reason: " . socket_strerror($result);
            	$this->state = -1;
                return null;
            }
            // write command
            socket_write($socket, $command, strlen($command));
            // read retval
            $return = "";
            while ($out = socket_read($socket, 2048))
                $return .= $out;
            // close socket
            socket_close($socket);
            // return
            return $return;
        }
    }
}

?>