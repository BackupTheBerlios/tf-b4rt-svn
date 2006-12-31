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

// class QueueManager_tfqmgr for tfqmgr
class QueueManager_tfqmgr extends QueueManager
{
    // some vars for tfqmgr
    var $pathDataDir = "";
    var $pathPidFile = "";
    var $pathCommandFifo = "";
    var $pathTransportFifo = "";

    //--------------------------------------------------------------------------
    /**
     * ctor
     */
    function QueueManager_tfqmgr($cfg) {
        $this->managerName = "tfqmgr";
		// version
		$this->version = "0.2";
		// initialize
        $this->Initialize($cfg);
        //
        $this->limitGlobal = $this->cfg["tfqmgr_limit_global"];
        $this->limitUser = $this->cfg["tfqmgr_limit_user"];
        //
        $this->pathDataDir = $this->cfg["path"] . '.tfqmgr/';
        $this->pathPidFile = $this->pathDataDir . 'tfqmgr.pid';
        $this->pathCommandFifo = $this->pathDataDir . 'COMMAND';
        $this->pathTransportFifo = $this->pathDataDir . 'TRANSPORT';
    }

    /**
     * prepareQueueManager (not needed for tfqmgr)
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
            AuditAction($this->cfg["constants"]["QManager"], "tfqmgr already started");
            return true;
        } else {
            $tfqmgr = "cd ".$this->cfg["tfqmgr_path_fluxcli"]."; HOME=".$this->cfg["path"]."; export HOME; nohup ";
			$tfqmgr .=  $this->cfg["perlCmd"] . " -I " .$this->cfg["tfqmgr_path"] ." ".$this->cfg["tfqmgr_path"] . "/tfqmgr.pl";
            $startCommand = $tfqmgr . " start " . $this->cfg["path"] . " ".$this->cfg["tfqmgr_limit_global"]." ".$this->cfg["tfqmgr_limit_user"]." ".$this->cfg["bin_php"]." &> /dev/null &";
            $result = exec($startCommand);
            sleep(1); // dont hurry
            AuditAction($this->cfg["constants"]["QManager"], "tfqmgr started");
            // set state
            $this->state = 2;
            return true;
        }
    }

    /**
     * stopQueueManager
     */
    function stopQueueManager() {
        AuditAction($this->cfg["constants"]["QManager"], "Stopping tfqmgr");
        if ($this->isQueueManagerRunning())
            $this->sendQueueCommand('!:stop',0);
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
            return $this->sendQueueCommand('!:status',1);
        else
            return "";
    }

    /**
     * isQueueManagerRunning
     * @return boolean
     */
    function isQueueManagerRunning()  {
        if (isset($this->pathPidFile) && ($this->pathPidFile != ""))
            return file_exists($this->pathPidFile);
        else
            return false;
    }

    /**
     * isQueueManagerReadyToStart
     * @return boolean
     */
    function isQueueManagerReadyToStart() {
        if ($this->isQueueManagerRunning() != 0) {
            return false;
        } else { # pid-file exists, but is daemon in shutdown ?
            if (isset($this->pathTransportFifo) && ($this->pathTransportFifo != "")) {
                if (file_exists($this->pathTransportFifo))
                    return false;
                else
                    return true;
            } else {
                return false;
            }
        }
    }

    /**
     * getQueuedTorrents
     * @param $user
     * @return string
     */
    function getQueuedTorrents($user = "") {
        if ($this->isQueueManagerRunning())
            return $this->sendQueueCommand('!:list-queue',1);
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
            return $this->sendQueueCommand('!:count-queue',1);
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
            $this->sendQueueCommand('add:'.substr($torrent,0,-8).':'.getOwner($torrent),0);
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
            header("location: index.php?alias_file=".$alias_file."&kill=true&kill_torrent=".urlencode($torrent));
            exit();
        } else {
            if ($this->isQueueManagerRunning()) {
                // send command to daemon
                $this->sendQueueCommand('remove:'.substr($torrent,0,-8).':'.getOwner($torrent),0);
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

    /**
     * sets a config of daemon
     * @param $key
     * @param $key
     */
    function setConfig($key,$val) {
        $this->sendQueueCommand("set:".$key.":".$val,0);
    }

    // private meths

    /**
     * send command
     * @param $command
     * @param $read
     * @return string
     */
    function sendQueueCommand($command, $read = 0) {
        // another sanity-check. dont fuck up the command-fifo-pipe.
        if (file_exists($this->pathCommandFifo)) {
            if ($handle = fopen($this->pathCommandFifo, "a")) {
                if (fwrite($handle, $command."\n")) {
                    fclose($handle);
                } else {
                    return "";
                }
            } else {
                return "";
            }
            if ($read == 0) {
                return "";
            } else {
                if($fileHandle = @fopen($this->pathTransportFifo,'r')) {
                	  $data = "";
                    while (!@feof($fileHandle))
                        $data .= @fgets($fileHandle, 1024);
                    @fclose ($fileHandle);
                    return $data;
                } else {
                    return "";
                }
            }
        } else {
            return "";
        }
    }

}

?>