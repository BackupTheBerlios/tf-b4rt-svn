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


include_once("config.php");
include_once("functions.php");
include_once("ClientHandler.php");

/* action */
$action = "---";
if (isset($_POST["action"]))
    $action = $_POST["action"];
switch ($action) {
    /* ---------------------------------------------------------------- dummy */
    case "---":
    break;
    /* ---------------------------------------------------------- dir-methods */
    case "fileDelete": /* fileDelete */
    	foreach($_POST['file'] as $key => $element) {
    		$element = urldecode($element);
    		delDirEntry($element);
    	}
    break;
    /* --------------------------------------------------------- all torrents */
    case "bulkStop": /* bulkStop */
    	$torrents = getTorrentListFromFS();
    	foreach ($torrents as $torrent) {
            $torrentRunningFlag = isTorrentRunning($torrent);
            if ($torrentRunningFlag != 0) {
                $owner = getOwner($torrent);
                if ((isset($owner)) && ($owner == $cfg["user"])) {
                    $alias = getAliasName($torrent).".stat";
                    $btclient = getTransferClient($torrent);
                    $clientHandler = ClientHandler::getClientHandlerInstance($cfg,$btclient);
                    $clientHandler->stopTorrentClient($torrent, $alias);
                    // just 2 sec..
                    sleep(2);
                }
            }
    	}
    break;
    case "bulkResume": /* bulkResume */
    	$torrents = getTorrentListFromDB();
    	foreach ($torrents as $torrent) {
            $torrentRunningFlag = isTorrentRunning($torrent);
            if ($torrentRunningFlag == 0) {
                $owner = getOwner($torrent);
                if ((isset($owner)) && ($owner == $cfg["user"])) {
                    $btclient = getTransferClient($torrent);
                    if ($cfg["enable_file_priority"]) {
                        include_once("setpriority.php");
                        // Process setPriority Request.
                        setPriority($torrent);
                    }
                    $clientHandler = ClientHandler::getClientHandlerInstance($cfg,$btclient);
                    $clientHandler->startTorrentClient($torrent, 0);
                    // just 2 sec..
                    sleep(2);
                }
            }
    	}
    break;
    case "bulkStart": /* bulkStart */
    	$torrents = getTorrentListFromFS();
    	foreach ($torrents as $torrent) {
            $torrentRunningFlag = isTorrentRunning($torrent);
            if ($torrentRunningFlag == 0) {
                $owner = getOwner($torrent);
                if ((isset($owner)) && ($owner == $cfg["user"])) {
                    $btclient = getTransferClient($torrent);
                    if ($cfg["enable_file_priority"]) {
                        include_once("setpriority.php");
                        // Process setPriority Request.
                        setPriority($torrent);
                    }
                    $clientHandler = ClientHandler::getClientHandlerInstance($cfg,$btclient);
                    $clientHandler->startTorrentClient($torrent, 0);
                    // just 2 sec..
                    sleep(2);
                }
            }
    	}
    break;
    /* ---------------------------------------------------- selected torrents */
    default:
       foreach($_POST['torrent'] as $key => $element) {
          $alias = getAliasName($element).".stat";
          $settingsAry = loadTorrentSettings(urldecode($element));
          $torrentRunningFlag = isTorrentRunning(urldecode($element));
          $btclient = $settingsAry["btclient"];
          switch ($action) {
             case "torrentStart": /* torrentStart */
                if ($torrentRunningFlag == 0) {
                   if ($cfg["enable_file_priority"]) {
                       include_once("setpriority.php");
                       // Process setPriority Request.
                       setPriority(urldecode($element));
                   }
                   $clientHandler = ClientHandler::getClientHandlerInstance($cfg,$btclient);
                   $clientHandler->startTorrentClient(urldecode($element), 0);
                   // just 2 sec..
                   sleep(2);
                }
             break;
             case "torrentStop": /* torrentStop */
                if ($torrentRunningFlag != 0) {
                   $clientHandler = ClientHandler::getClientHandlerInstance($cfg,$btclient);
                   $clientHandler->stopTorrentClient(urldecode($element), $alias);
                   // just 2 sec..
                   sleep(2);
                }
             break;
             case "torrentEnQueue": /* torrentEnQueue */
                if ($torrentRunningFlag == 0) {
                    // set queueing active
                    $_REQUEST['queue'] = 'on';
                    // enqueue it
                    if ($cfg["enable_file_priority"]) {
                        include_once("setpriority.php");
                        // Process setPriority Request.
                        setPriority(urldecode($element));
                    }
                    include_once("ClientHandler.php");
                    $clientHandler = ClientHandler::getClientHandlerInstance($cfg,$btclient);
                    $clientHandler->startTorrentClient(urldecode($element), 0);
                    // just a sec..
                    sleep(1);
                }
             break;
             case "torrentDeQueue": /* torrentDeQueue */
                if ($torrentRunningFlag == 0) {
                    // set request var
                    $_REQUEST['alias_file'] = getAliasName($element).".stat";;
                    // dequeue it
                    include_once("QueueManager.php");
                    $queueManager = QueueManager::getQueueManagerInstance($cfg);
                    $queueManager->dequeueTorrent($element);
                    // just a sec..
                    sleep(1);
                }
             break;
             case "torrentResetTotals": /* torrentResetTotals */
                resetTorrentTotals(urldecode($element), false);
             break;
             default:
                if ($torrentRunningFlag != 0) {
                   // stop torrent first
                   $clientHandler = ClientHandler::getClientHandlerInstance($cfg,$btclient);
                   $clientHandler->stopTorrentClient(urldecode($element), $alias);
                   // give the torrent some time to die
                   sleep(8);
                }
                // if it was running... hope the thing is down... rock on
                switch ($action) {
                   case "torrentWipe": /* torrentWipe */
                      deleteTorrentData(urldecode($element));
                      resetTorrentTotals(urldecode($element), true);
                      break;
                   case "torrentData": /* torrentData */
                      deleteTorrentData(urldecode($element));
                   case "torrent": /* torrent */
                      deleteTorrent(urldecode($element), $alias);
                }
          }
       }
}

/* redirect */
if (isset($_SERVER["HTTP_REFERER"]))
	header("location: ".$_SERVER["HTTP_REFERER"]);
else
	header("location: index.php");

?>