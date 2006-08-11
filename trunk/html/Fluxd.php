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
    // some vars for Fluxd
    var $pathDataDir = "";
    var $pathPidFile = "";

    //--------------------------------------------------------------------------
    /**
     * ctor
     */
    function getFluxdInstance($cfg) {
        $managerName = "Fluxd";
        $version = array_shift(explode(" ",trim(array_pop(explode(":",'$Revision$')))));
        //Initialize($cfg);
        //
        $pathDataDir = $cfg["path"] . '.fluxd/';
        $pathPidFile = $pathDataDir . 'fluxd.pid';
    }

    /**
     * startFluxd
     * @return boolean
     */
    function startFluxd() {
        if (isFluxdRunning()) {
            AuditAction($cfg["constants"]["Fluxd"], "Fluxd already started");
            return true;
        } else {
            $fluxd = "cd ".$cfg["fluxd_path_fluxcli"]."; HOME=".$cfg["path"]."; export HOME; nohup " . $cfg["perlCmd"] . " -I " .$cfg["fluxd_path"] ." ".$cfg["fluxd_path"] . "/fluxd.pl ";
            $startCommand = $fluxd . "daemon-start" . $cfg["fluxd_path_fluxcli"] . " > /dev/null &";
            //echo $startCommand;
            $result = exec($startCommand);
            sleep(1);
            AuditAction($cfg["constants"]["Fluxd"], "Fluxd started");
            // Set the status
            $status = 2;
            return true;
        }
    }

    /**
     * stopFluxd
     */
    function stopFluxd() {
        AuditAction($cfg["constants"]["Fluxd"], "Stopping Fluxd");
        if (isFluxdRunning()) {
            $sendCommand('die');
        }
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
            $pid = $data;
            return $pid;
        } else {
            return "";
        }
    }

    /**
     * statusFluxd
     * @return string
     */
    function statusFluxd() {
        if (isFluxdRunning())
            return sendCommand('status');
        else
            return "";
    }

    /**
     * isFluxdRunning
     * @return boolean
     */
    function isFluxdRunning() {
        if ( isset($pathPidFile) && ($pathPidFile != "") ) {
            return file_exists($pathPidFile);
        } else {
            return false;
        }
    }

    /**
     * isFluxdReadyToStart
     * @return boolean
     */
    function isFluxdReadyToStart() {
        if (isFluxdRunning() != 0) {
            return false;
        } else { # pid-file exists, but is the daemon trying to shut down?
            return (!(sendCommand('worker') ));
        }
    }

    /**
     * setConfig
     * @param $key, $value
     * @return Null
     */
    function setConfig($key, $value) {
       if (isFluxdRunning()) {
           sendCommand('set '.$key.' '.$value);
       }
    }

    // private meths

    /**
     * send command
     * @param $command
     * @return string
     */
    function sendCommand($command) {
        if (isFluxdRunning()) {
            $socket = socket_create(AF_UNIX, SOCK_STREAM);
            if ($socket < 0) {
                echo "socket_create() failed: reason: " . socket_strerror($socket) . "\n";
            }
            $result = socket_connect($socket, '/download/torrents/.fluxd/fluxd.sock');
            if ($result < 0) {
                echo "socket_connect() failed: reason: " . socket_strerror($result) . "\n";
            }
            socket_write($socket, $command, strlen($command));
            while ($out = socket_read($socket, 2048)) {
                $return .= $out;
            }
            return $return;
        }
    }
}

?>
