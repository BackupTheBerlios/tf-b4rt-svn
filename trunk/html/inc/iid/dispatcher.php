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

// clienthandler
require_once("inc/classes/ClientHandler.php");

/* action */
$action = "---";
if (isset($_REQUEST["action"]))
    $action = $_REQUEST["action"];
switch ($action) {

    /* ---------------------------------------------------------------- dummy */
    case "---":
    	break;

    /* ----------------------------------------------------------------- wget */
    case "wget":
		$url_wget = getRequestVar('url_wget');
		if (!empty($url_wget)) {
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg, 'wget');
			$clientHandler->inject($url_wget);
			$wget_start = getRequestVar('wget_start');
			if ($wget_start == 1) {
				sleep(2);
				$clientHandler->startClient($url_wget, 0, false);
				sleep(5);
			}
		}
    	break;

    /* ------------------------------------------------------------------ set */
    case "set":
    	$key = getRequestVar('key');
    	$val = getRequestVar('val');
    	if (!empty($key)) {
    		if ($key == "_all_") {
    			$keys = array_keys($_SESSION['settings']);
    			foreach ($keys as $settingKey)
    				$_SESSION['settings'][$settingKey] = $val;
    		} else {
    			$_SESSION['settings'][$key] = $val;
    		}
    	}
    	break;

    /* --------------------------------------------------------- all torrents */
    case "bulkStop": /* bulkStop */
    	$transfers = getTorrentListFromFS();
    	foreach ($transfers as $transfer) {
            $tRunningFlag = isTransferRunning($transfer);
            if ($tRunningFlag != 0) {
                $owner = getOwner($transfer);
                if ((isset($owner)) && ($owner == $cfg["user"])) {
                    $alias = getAliasName($transfer).".stat";
                    $btclient = getTransferClient($transfer);
                    $clientHandler = ClientHandler::getClientHandlerInstance($cfg,$btclient);
                    $clientHandler->stopClient($transfer, $alias);
                    // just 2 sec..
                    sleep(2);
                }
            }
    	}
    	break;
    case "bulkResume": /* bulkResume */
    	$transfers = getTorrentListFromDB();
    	foreach ($transfers as $transfer) {
            $tRunningFlag = isTransferRunning($transfer);
            if ($tRunningFlag == 0) {
                $owner = getOwner($transfer);
                if ((isset($owner)) && ($owner == $cfg["user"])) {
                    $btclient = getTransferClient($transfer);
                    if ($cfg["enable_file_priority"]) {
                        include_once("inc/setpriority.php");
                        // Process setPriority Request.
                        setPriority($transfer);
                    }
                    $clientHandler = ClientHandler::getClientHandlerInstance($cfg,$btclient);
                    $clientHandler->startClient($transfer, 0, false);
                    // just 2 sec..
                    sleep(2);
                }
            }
    	}
    	break;
    case "bulkStart": /* bulkStart */
    	$transfers = getTorrentListFromFS();
    	foreach ($transfers as $transfer) {
            $tRunningFlag = isTransferRunning($transfer);
            if ($tRunningFlag == 0) {
                $owner = getOwner($transfer);
                if ((isset($owner)) && ($owner == $cfg["user"])) {
                    $btclient = getTransferClient($transfer);
                    if ($cfg["enable_file_priority"]) {
                        include_once("inc/setpriority.php");
                        // Process setPriority Request.
                        setPriority($transfer);
                    }
                    $clientHandler = ClientHandler::getClientHandlerInstance($cfg,$btclient);
                    $clientHandler->startClient($transfer, 0, false);
                    // just 2 sec..
                    sleep(2);
                }
            }
    	}
    	break;

    /* --------------------------------------------------- selected transfers */
    default:

		foreach($_POST['transfer'] as $key => $element) {

			// alias
			$alias = getAliasName($element).".stat";
			if ((substr(strtolower($element), -8) == ".torrent")) {
				// this is a torrent-client
				$isTorrent = true;
				$tclient = getTransferClient(urldecode($element));
			} else if ((substr(strtolower($element), -5) == ".wget")) {
				// this is wget.
				$isTorrent = false;
				$tclient = "wget";
			} else {
				// this is "something else". use tornado statfile as default
				$isTorrent = false;
				$tclient = "tornado";
			}

			// is transfer running ?
			$tRunningFlag = isTransferRunning(urldecode($element));

			// action switch
			switch ($action) {

				case "transferStart": /* transferStart */
					if ($tRunningFlag == 0) {
						if ($isTorrent) {
							if ($cfg["enable_file_priority"]) {
								include_once("inc/setpriority.php");
								// Process setPriority Request.
								setPriority(urldecode($element));
							}
							$clientHandler = ClientHandler::getClientHandlerInstance($cfg, $tclient);
							$clientHandler->startClient(urldecode($element), 0, $queueActive);
						} else {
							$clientHandler = ClientHandler::getClientHandlerInstance($cfg, $tclient);
							$clientHandler->startClient(urldecode($element), 0, false);
						}
						// just 2 sec..
						sleep(2);
					}
					break;

				case "transferStop": /* transferStop */
					if (($isTorrent) && ($tRunningFlag != 0)) {
						$clientHandler = ClientHandler::getClientHandlerInstance($cfg, $tclient);
						$clientHandler->stopClient(urldecode($element), $alias);
						// just 2 sec..
						sleep(2);
					}
					break;

				case "transferEnQueue": /* transferEnQueue */
					if (($isTorrent) && ($tRunningFlag == 0)) {
						// enqueue it
						if ($cfg["enable_file_priority"]) {
							include_once("inc/setpriority.php");
							// Process setPriority Request.
							setPriority(urldecode($element));
						}
						require_once("inc/classes/ClientHandler.php");
						$clientHandler = ClientHandler::getClientHandlerInstance($cfg, $tclient);
						$clientHandler->startClient(urldecode($element), 0, true);
						// just a sec..
						sleep(1);
					}
					break;

				case "transferDeQueue": /* transferDeQueue */
					if (($isTorrent) && ($tRunningFlag == 0)) {
						// set request var
						$_REQUEST['alias_file'] = getAliasName($element).".stat";;
						// dequeue it
						$fluxdQmgr->dequeueTorrent($element, $cfg["user"]);
						// just a sec..
						sleep(1);
					}
					break;

				case "transferResetTotals": /* transferResetTotals */
					resetTorrentTotals(urldecode($element), false);
					break;

				default:
					if (($isTorrent) && ($tRunningFlag != 0)) {
						// stop torrent first
						$clientHandler = ClientHandler::getClientHandlerInstance($cfg, $tclient);
						$clientHandler->stopClient(urldecode($element), $alias);
						// give the torrent some time to die
						sleep(8);
						// is transfer running ?
						$tRunningFlag = isTransferRunning(urldecode($element));
					}
					// if it was running... hope the thing is down...
					// only continue if it is
					if ($tRunningFlag == 0) {
						switch ($action) {
							case "transferWipe": /* transferWipe */
								if ($isTorrent) {
									deleteTorrentData(urldecode($element));
									resetTorrentTotals(urldecode($element), true);
								}
								break;
							case "transferData": /* transferData */
								if ($isTorrent)
									deleteTorrentData(urldecode($element));
							case "transfer": /* transfer */
								deleteTransfer(urldecode($element), $alias);
						}
					}

			} // end switch
		} // end loop
}

/* redirect */
if (isset($_SERVER["HTTP_REFERER"]))
	header("location: ".$_SERVER["HTTP_REFERER"]);
else
	header("location: index.php?iid=index");

?>