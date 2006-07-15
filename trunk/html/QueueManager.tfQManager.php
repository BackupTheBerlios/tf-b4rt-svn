<?php

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


// class QueueManager_tfQManager for tfQManager
class QueueManager_tfQManager extends QueueManager
{
    //--------------------------------------------------------------------------
    /**
     * ctor
     */
    function QueueManager_tfQManager($cfg) {
        $this->managerName = "tfQManager";
        $this->version = "1.00";
        $this->Initialize($cfg);
        //
        $this->limitGlobal = $this->cfg["maxServerThreads"];
        $this->limitUser = $this->cfg["maxUserThreads"];
    }

    /**
     * prepareQueueManager (not needed for tfqmgr)
     * @return boolean
     */
    function prepareQueueManager() {
        if (is_dir($this->cfg["path"]) && is_writable($this->cfg["path"])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * startQueueManager
     */
    function startQueueManager() {
        // is there a stat and torrent dir?
        if (is_dir($this->cfg["torrent_file_path"])) {
            if (is_writable($this->cfg["torrent_file_path"]) && !is_dir($this->cfg["torrent_file_path"]."queue/")) {
                //Then create it
                mkdir($this->cfg["torrent_file_path"]."queue/", 0777);
            }
        }
        if ($this->checkQManager() == 0) {
            $cmd1 = "cd " . $this->cfg["path"] . "TFQUSERNAME";
            if (! array_key_exists("pythonCmd",$this->cfg)) {
                insertSetting("pythonCmd","/usr/bin/python");
            }
            if (! array_key_exists("debugTorrents",$this->cfg)) {
                insertSetting("debugTorrents",false);
            }
            if (!$this->cfg["debugTorrents"]) {
                $pyCmd = $this->cfg["pythonCmd"] . " -OO";
            } else {
                $pyCmd = $this->cfg["pythonCmd"];
            }
            $btphp = "'" . $cmd1. "; HOME=".$this->cfg["path"]."; export HOME; nohup " . $pyCmd . " " .$this->cfg["btclient_tornado_bin"] . " '";
            $startCommand = $pyCmd . " " . $this->cfg["tfQManager"] . " ".$this->cfg["torrent_file_path"]."queue/ ". $this->limitGlobal ." ". $this->limitUser ." ".$this->cfg["sleepInterval"]." ".$btphp." > /dev/null &";
            $result = exec($startCommand);
            sleep(2); // wait for it to start prior to getting pid
            $this->pid = $this->getQManagerPID();
            AuditAction($this->cfg["constants"]["QManager"], "Started PID:" .  $this->pid);
            // set status
            $this->status = 2;
            return true;
        } else{
            AuditAction($this->cfg["constants"]["QManager"], "QManager Already Started  PID:" . $this->getQManagerPID());
            return true;
        }
    }

    /**
     * stopQueueManager
     */
    function stopQueueManager() {
        $QmgrPID = $this->getQManagerPID();
        if($QmgrPID != "") {
            AuditAction($this->cfg["constants"]["QManager"], "Stopping PID:" . $QmgrPID);
            $result = exec("kill ".$QmgrPID);
            unlink($this->cfg["torrent_file_path"] . "queue/tfQManager.pid");
        }
    }

    /**
     * getQueueManagerPid
     * @return int with pid
     */
    function getQueueManagerPid() {
        $this->pid = $this->getQManagerPID();
        return $this->pid;
    }

    /**
     * statusQueueManagert
     */
    function statusQueueManager() { return; }

    /**
     * isQueueManagerRunning
     * @return boolean
     */
    function isQueueManagerRunning() {
        if ($this->checkQManager() == 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * isQueueManagerReadyToStart
     * @return boolean
     */
    function isQueueManagerReadyToStart() {
        if ($this->checkQManager() == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * getQueuedTorrents
     * @param $user
     */
    function getQueuedTorrents($user = "") { }

    /**
     * countQueuedTorrents
     * @return int
     */
    function countQueuedTorrents($user = "") {
        $rtnValue = 0;
        $dirName = $this->cfg["torrent_file_path"] . "queue/";
        $handle = @opendir($dirName);
        if ($handle) {
            while($entry = readdir($handle)) {
                if ($entry != "." && $entry != "..") {
                    if (!(@is_dir($dirName.$entry)) && (substr($entry, -6) == ".Qinfo")) {
                        $rtnValue = $rtnValue + 1;
                    }
                }
            }
        }
        return $rtnValue;
    }

    /**
     * enqueueTorrent
     * @param $torrent name of the torrent
     */
    function enqueueTorrent($torrent) {
        $alias = getAliasName($torrent);
        $this->writeQinfo($this->cfg["torrent_file_path"]."queue/".$alias.".stat",$this->command);
    }

    /**
     * dequeueTorrent
     * @param $torrent name of the torrent
     */
    function dequeueTorrent($torrent) {
        $torrent = urldecode($torrent);
        $alias_file = getRequestVar('alias_file');
        // Is the Qinfo file still there?
        if (file_exists($this->cfg["torrent_file_path"]."queue/".$alias_file.".Qinfo")) {
            // flag the torrent as stopped (in db)
            stopTorrentSettings($torrent);
            // update the stat file.
            parent::updateStatFile($torrent,$alias_file);
            // Remove Qinfo file.
            @unlink($this->cfg["torrent_file_path"]."queue/".$alias_file.".Qinfo");
            // log
            AuditAction($this->cfg["constants"]["unqueued_torrent"], $torrent);
        } else {
            // torrent has been started... try and kill it.
            AuditAction($this->cfg["constants"]["unqueued_torrent"], $torrent . "has been started -- TRY TO KILL IT");
            header("location: index.php?alias_file=".$alias_file."&kill=true&kill_torrent=".urlencode($torrent));
            exit();
        }
    }

    /**
     * formattedQueueList. dont want to rewrite more tf-mvc-"issues"...
     * @return html-snip
     */
    function formattedQueueList() {
        $output = "";
        $qDir = $this->cfg["torrent_file_path"]."queue/";
        if (is_dir($this->cfg["torrent_file_path"]))
        {
            if (is_writable($this->cfg["torrent_file_path"]) && !is_dir($qDir)) {
                @mkdir($qDir, 0777);
            }
            // get Queued Items and List them out.
            $output .= "";
            $handle = @opendir($qDir);
            while($filename = readdir($handle)) {
                if ($filename != "tfQManager.log") {
                    if ($filename != "." && $filename != ".." && strpos($filename,".pid") == 0) {
                        $output .= "<tr>";
                        $output .= "<td><div class=\"tiny\">";
                        // only tornado
                        $af = AliasFile::getAliasFileInstance(str_replace("queue/","",$qDir).str_replace(".Qinfo","",$filename), "", $this->cfg, "tornado");
                        $output .= $af->torrentowner;
                        $output .= "</div></td>";
                        $output .= "<td><div align=center><div class=\"tiny\" align=\"left\">".str_replace(array(".Qinfo",".stat"),"",$filename)."</div></td>";
                        $output .= "<td><div class=\"tiny\" align=\"center\">".date(_DATETIMEFORMAT, strval(filectime($qDir.$filename)))."</div></td>";
                        $output .= "</tr>";
                        $output .= "\n";
                    }
                }
            }
            closedir($handle);
        }
        if( strlen($output) == 0 ) {
            $output = "<tr><td colspan=3><div class=\"tiny\" align=center>Queue is Empty</div></td></tr>";
        }
        return $output;
    }

    // private meths

    //**************************************************************************
    function checkQManager()
    {
        $x = $this->getQManagerPID();
        if (strlen($x) > 0) {
            $y = $x;
            $arScreen = array();
            $tok = strtok(shell_exec("ps -p " . $x . " | grep " . $y), "\n");
            while ($tok) {
                array_push($arScreen, $tok);
                $tok = strtok("\n");
            }
            $QMgrCount = sizeOf($arScreen);
        }
        else {
            $QMgrCount = 0;
        }
        return $QMgrCount;
    }

    //**************************************************************************
    function getQManagerPID()
    {
        $rtnValue = "";
        $pidFile = $this->cfg["torrent_file_path"] . "queue/tfQManager.pid";
        if(file_exists($pidFile)) {
            $fp = fopen($pidFile,"r");
            if ($fp) {
                while (!feof($fp)) {
                    $tmpValue = fread($fp,1);
                    if($tmpValue != "\n")
                        $rtnValue .= $tmpValue;
                }
                fclose($fp);
            }
        }
        return $rtnValue;
    }

    //**************************************************************************
    function writeQinfo($fileName,$command)
    {
        $fp = fopen($fileName.".Qinfo","w");
        fwrite($fp, $command);
        fflush($fp);
        fclose($fp);
    }

    /**
     * sets a config of daemon
     * @param $key
     * @param $key
     */
    function setConfig($key,$val) { return; }

}

?>