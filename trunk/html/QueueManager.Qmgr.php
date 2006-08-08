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


// class QueueManager_Qmgr for Qmgr
class QueueManager_Qmgr extends QueueManager
{
    // some vars for Qmgr
    var $pathDataDir = "";
    var $pathPidFile = "";
    var $host = "";
    var $port = 0;

    //--------------------------------------------------------------------------
    /**
     * ctor
     */
    function QueueManager_Qmgr($cfg) {
        $this->managerName = "Qmgr";
        $this->version = array_shift(explode(" ",trim(array_pop(explode(":",'$Revision$')))));
        $this->Initialize($cfg);
        //
        $this->limitGlobal = $this->cfg["Qmgr_maxTotalTorrents"];
        $this->limitUser = $this->cfg["Qmgr_maxUserTorrents"];
        //
        $this->pathDataDir = $this->cfg["path"] . '.Qmgr/';
        $this->pathPidFile = $this->pathDataDir . 'Qmgr.pid';
        $this->host = $this->cfg["Qmgr_host"];
        $this->port = $this->cfg["Qmgr_port"];
    }

    /**
     * prepareQueueManager (not needed for Qmgr)
     * @return boolean
     */
    function prepareQueueManager() {
        return true;
    }

    /**
     * startQueueManager
     * @return boolean
     */
    function startQueueManager() {
        if ($this->isQueueManagerRunning()) {
            AuditAction($this->cfg["constants"]["QManager"], "Qmgr already started");
            return true;
        } else {
            $Qmgr = "cd ".$this->cfg["Qmgr_fluxcli"]."; HOME=".$this->cfg["path"]."; export HOME; nohup " . $this->cfg["Qmgr_perl"] . " -I " .$this->cfg["Qmgr_path"] ." ".$this->cfg["Qmgr_path"] . "/Qmgrd.pl ";
            $startCommand = $Qmgr . $this->cfg["path"] . " " .$this->cfg["Qmgr_maxTotalTorrents"]." ".$this->cfg["Qmgr_maxUserTorrents"]." ".$this->cfg["Qmgr_host"]." ".$this->cfg["Qmgr_port"]." > /dev/null &";
            //echo $startCommand;
            $result = exec($startCommand);
            sleep(1);
            AuditAction($this->cfg["constants"]["QManager"], "Qmgr started");
            // Set the status
            $this->status = 2;
            return true;
        }
    }

    /**
     * stopQueueManager
     */
    function stopQueueManager() {
        AuditAction($this->cfg["constants"]["QManager"], "Stopping Qmgr");
        if ($this->isQueueManagerRunning()) {
            $this->sendQueueCommand('stop');
        }
    }

    /**
     * getQueueManagerPid
     * @return int with pid
     */
    function getQueueManagerPid() {
        if($fileHandle = @fopen($this->pathPidFile,'r')) {
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
     * statusQueueManager
     * @return string
     */
    function statusQueueManager() {
        if ($this->isQueueManagerRunning())
            return $this->sendQueueCommand('status');
        else
            return "";
    }

    /**
     * isQueueManagerRunning
     * @return boolean
     */
    function isQueueManagerRunning()  {
        if (isset($this->pathPidFile) && ($this->pathPidFile != "")) {
            return file_exists($this->pathPidFile);
        } else {
            return false;
      }
    }

    /**
     * isQueueManagerReadyToStart
     * @return boolean
     */
    function isQueueManagerReadyToStart() {
        if ($this->isQueueManagerRunning() != 0) {
            return false;
        } else { # pid-file exists, but is the daemon trying to shut down?
            return (!($this->sendQueueCommand('worker') ));
        }
    }

    /**
     * getQueuedTorrents
     * @param $user
     * @return string
     */
    function getQueuedTorrents($user = "") {
        if ($this->isQueueManagerRunning())
            return $this->sendQueueCommand('list');
        else
            return "";
    }

    /**
     * countQueuedTorrents
     * @param $user
     * @return int
     */
    function countQueuedTorrents($user = "") {
        if ($this->isQueueManagerRunning())
            return $this->sendQueueCommand('queue');
        else
            return 0;
    }

    /**
     * enqueueTorrent
     * @param $torrent name of the torrent
     */
    function enqueueTorrent($torrent) {
        if ($this->isQueueManagerRunning()) {
            $torrent = urldecode($torrent);
            $this->sendQueueCommand('add '.substr($torrent,0,-8).' '.getOwner($torrent));
        }
    }

    /**
     * dequeueTorrent
     * @param $torrent name of the torrent
     */
    function dequeueTorrent($torrent) {
        $torrent = urldecode($torrent);
        $alias_file = getRequestVar('alias_file');
        if (isTorrentRunning($torrent)) {
            // torrent has been started... try and kill it.
            AuditAction($this->cfg["constants"]["unqueued_torrent"], $torrent . "has been started -- TRY TO KILL IT");
            header("location: index.php?page=index&alias_file=".$alias_file."&kill=true&kill_torrent=".urlencode($torrent));
            exit();
        } else {
            if ($this->isQueueManagerRunning()) {
                // send command to daemon
                $this->sendQueueCommand('remove '.substr($torrent,0,-8));
                // flag the torrent as stopped (in db)
                stopTorrentSettings($torrent);
                // update the stat file.
                parent::updateStatFile($torrent,$alias_file);
                // log
                AuditAction($this->cfg["constants"]["unqueued_torrent"], $torrent);
            } else {
                header("location: admin.php?op=queueSettings");
                exit;
            }
        }
    }

    /**
     * setConfig
     * @param $key, $value
     * @return Null
     */
    function setConfig($key, $value) {
       if ($this->isQueueManagerRunning()) {
           $this->sendQueueCommand('set '.$key.' '.$value);
       }
    }

    /**
     * formattedQueueList. dont want to rewrite more tf-mvc-"issues"...
     * @return html-snip
     */
    function formattedQueueList() {
        if ($this->isQueueManagerRunning()) {
            $output = "";
            $torrentList = trim($this->getQueuedTorrents());
            $torrentAry = explode("\n",$torrentList);
            foreach ($torrentAry as $torrent) {
                if ($torrent != "") {
                    $output .= "<tr>";
                    $output .= "<td><div class=\"tiny\">";
                    $output .= getOwner($torrent);
                    $output .= "</div></td>";
                    $output .= "<td><div align=center><div class=\"tiny\" align=\"left\">".$torrent."</div></td>";
                    $output .= "<td><div class=\"tiny\" align=\"center\">".date(_DATETIMEFORMAT, strval(filemtime($this->cfg["torrent_file_path"].getAliasName($torrent).".stat")))."</div></td>";
                    $output .= "</tr>";
                    $output .= "\n";
                }
            }
            if( strlen($output) == 0 )
                return "<tr><td colspan=3><div class=\"tiny\" align=center>Queue is Empty</div></td></tr>";
            else
                return $output;
        } else {
            return "";
        }
    }

    // private meths

    /**
     * send command
     * @param $command
     * @param $read
     * @return string
     */
    function sendQueueCommand($command) {
        if ($this->isQueueManagerRunning()) {
            $Qmgr = "cd ".$this->cfg["Qmgr_fluxcli"]."; HOME=".$this->cfg["path"]."; export HOME; nohup " . $this->cfg["Qmgr_perl"] . " -I ".$this->cfg["Qmgr_path"] ." ".$this->cfg["Qmgr_path"] . "/Qmgr.pl ".$command;
            $return = exec($Qmgr);
            return $return;
        }
    }
}

?>