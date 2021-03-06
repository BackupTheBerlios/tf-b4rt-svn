<?php

/*************************************************************
*  TorrentFlux - PHP Torrent Manager
*  www.torrentflux.com
**************************************************************/
/*
    This file is part of TorrentFlux.

    TorrentFlux is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    TorrentFlux is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with TorrentFlux; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

include_once("config.php");
include_once("functions.php");

// global fields
$messages = "";

// =============================================================================
// set refresh option into the session cookie
if(array_key_exists("pagerefresh", $_GET)) {
    if($_GET["pagerefresh"] == "false") {
        $_SESSION['prefresh'] = false;
        header("location: index.php");
        exit();
    }
    if($_GET["pagerefresh"] == "true") {
        $_SESSION["prefresh"] = true;
        header("location: index.php");
        exit();
    }
}

// =============================================================================
// queue-check
$queueActive = false;
if ($cfg["AllowQueing"]) {
    include_once("QueueManager.php");
    $queueManager = QueueManager::getQueueManagerInstance($cfg);
    if (! $queueManager->isQueueManagerRunning()) {
        if (($queueManager->prepareQueueManager()) && ($queueManager->startQueueManager())) {
            $queueActive = true;
        } else {
            AuditAction($cfg["constants"]["error"], "Error starting Queue Manager");
            if (IsAdmin())
                header("location: admin.php?op=queueSettings");
            else
                header("location: index.php");
            exit();
        }
    } else {
        $queueActive = true;
    }
}

// =============================================================================
// start
$torrent = getRequestVar('torrent');
if(! empty($torrent)) {
    $interactiveStart = getRequestVar('interactive');
    if ((isset($interactiveStart)) && ($interactiveStart)) /* interactive */
        indexStartTorrent($torrent,1);
    else /* silent */
        indexStartTorrent($torrent,0);
}

// =============================================================================
// wget
if ($cfg['enable_wget'] == 1) {
    $url_wget = getRequestVar('url_wget');
    // <DD32>:
    if(! $url_wget == '') {
        exec("nohup ".$cfg['bin_php']." -f wget.php ".$url_wget." ".$cfg['user']." > /dev/null &");
        sleep(2); //sleep so that hopefully the other script has time to write out the stat files.
        header("location: index.php");
        exit();
    }
    // </DD32>
}

// =============================================================================
// Do they want us to get a torrent via a URL?
$url_upload = getRequestVar('url_upload');
if(! $url_upload == '')
    indexProcessDownload($url_upload);

// =============================================================================
// Handle the file upload if there is one
if(!empty($_FILES['upload_file']['name']))
    indexProcessUpload();

// =============================================================================
// if a file was set to be deleted then delete it
$delfile = getRequestVar('delfile');
if(! $delfile == '') {
    deleteTorrent($delfile, getRequestVar('alias_file'));
    header("location: index.php");
    exit();
}

// =============================================================================
// Did the user select the option to kill a running torrent?
$killTorrent = getRequestVar('kill_torrent');
if(! $killTorrent == '') {
    $return = getRequestVar('return');
    include_once("ClientHandler.php");
    $clientHandler = ClientHandler::getClientHandlerInstance($cfg, getTorrentClient($killTorrent));
    $clientHandler->stopTorrentClient($killTorrent, getRequestVar('alias_file'), getRequestVar('kill'), $return);
    if (!empty($return))
        header("location: ".$return.".php?op=queueSettings");
    else
        header("location: index.php");
    exit();
}

// =============================================================================
// Did the user select the option to remove a torrent from the Queue?
if(isset($_REQUEST["dQueue"])) {
    $QEntry = getRequestVar('QEntry');
    include_once("QueueManager.php");
    $queueManager = QueueManager::getQueueManagerInstance($cfg);
    $queueManager->dequeueTorrent($QEntry);
    header("location: index.php");
    exit();
}

// =============================================================================
// init some vars
// =============================================================================
// drivespace
$drivespace = getDriveSpace($cfg["path"]);
// connections
$netstatConnectionsSum = "n/a";
if ($cfg["index_page_connections"] != 0)
    $netstatConnectionsSum = @netstatConnectionsSum();
// loadavg
$loadavgString = "n/a";
if ($cfg["show_server_load"] != 0)
    $loadavgString = @getLoadAverageString();

// =============================================================================
// output
// =============================================================================
include('inc.index.head.'.$cfg["index_page"].'.php');
include('inc.index.main.php');
exit();

?>