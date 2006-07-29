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


// base class QueueManager
class QueueManager
{
    var $managerName = "";
    var $version = "";

    var $loglevel; // loglevel of daemon
    var $limitGlobal; // torrent limit global
    var $limitUser; // torrent limit user

    // command
    var $command = ""; // this is only to be generic tfQmanager-compatible as
                       // tfQmanager needs full start-command to enqueue a torrent

    // pid
    var $pid;

    // call-result
    var $callResult;
    // config-array
    var $cfg = array();
    // messages-string
    var $messages = "";
    // manager-status
    var $status = 0;    // status of the manager
                        //  0 : not initialized
                        //  1 : initialized
                        //  2 : started/running
                        // -1 : error

    //--------------------------------------------------------------------------
    /**
     * ctor
     */
    function QueueManager() {
        $this->status = -1;
        die('base class -- dont do this');
    }

    //--------------------------------------------------------------------------
    // factory
    /**
     * get QueueManager-instance
     *
     * @param $fluxCfg torrent-flux config-array
     * @return QueueManager-instance
     */
    function getQueueManagerInstance($fluxCfg, $managerType = '') {
        // damn dirty but does php (< 5) have reflection or something like
        // class-by-name ?
        if ((isset($managerType)) && ($managerType != '')) {
            $managerClass = $managerType;
            $fluxCfg["queuemanager"] = $managerClass;
        } else {
            $managerClass = $fluxCfg["queuemanager"];
        }
        $classFile = 'QueueManager.'.$managerClass.'.php';
        if (is_file($classFile)) {
            include_once($classFile);
            switch ($managerClass) {
                case "tfqmgr":
                    return new QueueManager_tfqmgr(serialize($fluxCfg));
                break;
                case "tfQManager":
                    return new QueueManager_tfQManager(serialize($fluxCfg));
                break;
                case "Qmgr":
                    return new QueueManager_Qmgr(serialize($fluxCfg));
                break;
            }
        }
    }

    //--------------------------------------------------------------------------
    // Initialize the QueueManager.
    function Initialize($cfg) {
        $this->cfg = unserialize($cfg);
        if (empty($this->cfg)) {
            $this->messages = "Config not passed";
            $this->status = -1;
            return;
        }
        $this->status = 1;
    }

    //--------------------------------------------------------------------------
    // abstract method : prepareQueueManager
    function prepareQueueManager() { return; }

    //--------------------------------------------------------------------------
    // abstract method : startQueueManager
    function startQueueManager() { return; }

    //--------------------------------------------------------------------------
    // abstract method : stopQueueManager
    function stopQueueManager() { return; }

    //--------------------------------------------------------------------------
    // abstract method : getQueueManagerPid
    function getQueueManagerPid() { return; }

    //--------------------------------------------------------------------------
    // abstract method : statusQueueManager
    function statusQueueManager() { return; }

    //--------------------------------------------------------------------------
    // abstract method : isQueueManagerRunning
    function isQueueManagerRunning() { return; }

    //--------------------------------------------------------------------------
    // abstract method : isQueueManagerReadyToStart
    function isQueueManagerReadyToStart() { return; }

    //--------------------------------------------------------------------------
    // abstract method : getQueuedTorrents
    function getQueuedTorrents($user = "") { return; }

    //--------------------------------------------------------------------------
    // abstract method : countQueuedTorrents
    function countQueuedTorrents($user = "") { return; }

    //--------------------------------------------------------------------------
    // abstract method : enqueueTorrent
    function enqueueTorrent($torrent) { return; }

    //--------------------------------------------------------------------------
    // abstract method : dequeueTorrent
    function dequeueTorrent($torrent) { return; }

    //--------------------------------------------------------------------------
    // abstract method : formattedQueueList
    function formattedQueueList() { return; }

    //--------------------------------------------------------------------------
    // abstract method : setConfig
    function setConfig($key,$val) { return; }

    /**
     * updateStatFile
     * @param $torrent name of the torrent
     * @param $alias_file name of the torrent
     */
    function updateStatFile($torrent,$alias_file) {
        include_once("AliasFile.php");
        $the_user = getOwner($torrent);
        $btclient = getTorrentClient($torrent);
        $modded = 0;
        // create AliasFile object
        $af = AliasFile::getAliasFileInstance($this->cfg["torrent_file_path"].$alias_file, $the_user, $this->cfg, $btclient);
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


} // end class


?>