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

// class for the Fluxd-Service-module Qmgr
class FluxdQmgr extends FluxdServiceMod
{
    /**
     * ctor
     */
    function FluxdQmgr($cfg, $fluxd) {
        $this->moduleName = "Qmgr";
        $this->version = array_shift(explode(" ",trim(array_pop(explode(":",'$Revision$')))));
        $this->initialize($cfg, $fluxd);
    }

    /**
     * getQueuedTorrents
     *
     * @param $user
     * @return Queued Torrents
     */
    function getQueuedTorrents($user = "") {
    	return parent::sendServiceCommand("list-queue", 1);
    }

    /**
     * countQueuedTorrents
     *
     * @param $user
     * @return int with sum of Queued Torrents
     */
    function countQueuedTorrents($user = "") {
    	return (int) parent::sendServiceCommand("count-queue", 1);
    }

    /**
     * enqueueTorrent
     *
     * @param $torrent
     * @param $user
     */
    function enqueueTorrent($torrent, $user) {
    	$torrent = urldecode($torrent);
    	// send command to Qmgr
    	parent::sendServiceCommand("enqueue;".$torrent.";".$user, 0);
    }

    /**
     * dequeueTorrent
     *
     * @param $torrent
     * @param $user
     */
    function dequeueTorrent($torrent, $user) {
    	$torrent = urldecode($torrent);
        $alias_file = getRequestVar('alias_file');
        if (isTransferRunning($torrent)) {
            // torrent has been started... try and kill it.
            AuditAction($this->cfg["constants"]["unqueued_torrent"], $torrent . "has been started -- TRY TO KILL IT");
            header("location: dispatcher.php?action=indexStop&transfer=".urlencode($torrent)."&alias_file=".$alias_file."&kill=true");
            exit();
        } else {
            // send command to Qmgr
            parent::sendServiceCommand("dequeue;".$torrent.";".$user, 0);
            // flag the torrent as stopped (in db)
            stopTorrentSettings($torrent);
            // update the stat file.
            $this->updateStatFile($torrent, $alias_file);
            // log
            AuditAction($this->cfg["constants"]["unqueued_torrent"], $torrent);
        }
    }

    /**
     * updateStatFile
     * @param $torrent name of the torrent
     * @param $alias_file alias_file of the torrent
     */
    function updateStatFile($torrent, $alias_file) {
        require_once("inc/classes/AliasFile.php");
        $the_user = getOwner($torrent);
        $btclient = getTransferClient($torrent);
        $modded = 0;
        // create AliasFile object
        $af = AliasFile::getAliasFileInstance($this->cfg["transfer_file_path"].$alias_file, $the_user, $this->cfg, $btclient);
        if($af->percent_done > 0 && $af->percent_done < 100) {
            // has downloaded something at some point, mark it is incomplete
            $af->running = "0";
            $af->time_left = "Torrent Stopped";
            $modded++;
        }
        if ($modded == 0) {
            if ($af->percent_done == 0 || $af->percent_done == "") {
                // We are going to write a '2' on the front of the stat file so that it will be set back to New Status
                $af->running = "2";
                $af->time_left = "";
                $modded++;
            }
        }
        if ($modded == 0) {
            if ($af->percent_done == 100) {
                // Torrent was seeding and is now being stopped
                $af->running = "0";
                $af->time_left = "Download Succeeded!";
                $modded++;
            }
        }
        if ($modded == 0) {
            // hmmm this stat-file is quite strange... just rewrite it stopped.
            $af->running = "0";
            $af->time_left = "Torrent Stopped";
        }
        // Write out the new Stat File
        $af->WriteFile();
    }

}

?>