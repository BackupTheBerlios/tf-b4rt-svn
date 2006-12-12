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

// main.internal
require_once("inc/main.internal.php");

// common functions
require_once('inc/functions/functions.common.php');

// dispatcher functions
require_once("inc/functions/functions.dispatcher.php");

// time-limit
@set_time_limit(0);

// action-switch
$action = (isset($_REQUEST['action'])) ? $_REQUEST['action'] : "---";
switch ($action) {

/*******************************************************************************
 * dummy
 ******************************************************************************/
    case "---":
    	break;

/*******************************************************************************
 * index-page ops
 ******************************************************************************/
    case "indexStart":
		indexStartTransfer(getRequestVar('transfer'));
    	break;
    case "indexUrlUpload":
		indexProcessDownload(getRequestVar('url'));
    	break;
    case "indexDelete":
    	indexDeleteTransfer(getRequestVar('transfer'));
    	break;
    case "indexStop":
    	indexStopTransfer(getRequestVar('transfer'));
    	break;
    case "indexDeQueue":
    	indexDeQueueTransfer(getRequestVar('transfer'));
    	break;

/*******************************************************************************
 * file-upload
 ******************************************************************************/
	case "fileUpload":
		processFileUpload();
    	break;

/*******************************************************************************
 * wget
 ******************************************************************************/
    case "wget":
		// is enabled ?
		if ($cfg["enable_wget"] == 0) {
			AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to use wget");
			showErrorPage("wget is disabled.");
		} elseif ($cfg["enable_wget"] == 1) {
			if (!$cfg['isAdmin']) {
				AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to use wget");
				showErrorPage("wget is disabled for users.");
			}
		}
		$url = getRequestVar('url');
		if (!empty($url)) {
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg, 'wget');
			$clientHandler->inject($url);
			$wget_start = getRequestVar('wget_start');
			if ($wget_start == 1) {
				$clientHandler->startClient($url, 0, false);
				sleep(3);
			}
		}
    	break;

/*******************************************************************************
 * set
 ******************************************************************************/
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


/*******************************************************************************
 * Maintenance
 ******************************************************************************/
    case "maintenance":
		require_once("inc/classes/MaintenanceAndRepair.php");
		MaintenanceAndRepair::maintenance((getRequestVar('trestart') == "true") ? true : false);
    	break;

/*******************************************************************************
 * Cookie-Flush
 ******************************************************************************/
    case "cookieFlush":
		@setcookie("autologin", "", time() - 3600);
    	break;

/*******************************************************************************
 * bulk operations
 ******************************************************************************/
    case "bulkStop": /* bulkStop *///
    	// is enabled ?
		if ($cfg["enable_bulkops"] != 1) {
			AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to use bulkStop");
			showErrorPage("bulkops are disabled.");
		}
		// stop all
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
                }
            }
    	}
    	break;
    case "bulkResume": /* bulkResume */
    	// is enabled ?
		if ($cfg["enable_bulkops"] != 1) {
			AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to use bulkResume");
			showErrorPage("bulkops are disabled.");
		}
		// resume all
    	$transfers = getTorrentListFromDB();
    	foreach ($transfers as $transfer) {
            $tRunningFlag = isTransferRunning($transfer);
            if ($tRunningFlag == 0) {
                $owner = getOwner($transfer);
                if ((isset($owner)) && ($owner == $cfg["user"])) {
                    $btclient = getTransferClient($transfer);
                    if ($cfg["enable_file_priority"]) {
                        include_once("inc/functions/functions.setpriority.php");
                        // Process setPriority Request.
                        setPriority($transfer);
                    }
                    $clientHandler = ClientHandler::getClientHandlerInstance($cfg,$btclient);
                    $clientHandler->startClient($transfer, 0, false);
                }
            }
    	}
    	break;
    case "bulkStart": /* bulkStart */
    	// is enabled ?
		if ($cfg["enable_bulkops"] != 1) {
			AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to use bulkStart");
			showErrorPage("bulkops are disabled.");
		}
		// start all
    	$transfers = getTorrentListFromFS();
    	foreach ($transfers as $transfer) {
            $tRunningFlag = isTransferRunning($transfer);
            if ($tRunningFlag == 0) {
                $owner = getOwner($transfer);
                if ((isset($owner)) && ($owner == $cfg["user"])) {
                    $btclient = getTransferClient($transfer);
                    if ($cfg["enable_file_priority"]) {
                        include_once("inc/functions/functions.setpriority.php");
                        // Process setPriority Request.
                        setPriority($transfer);
                    }
                    $clientHandler = ClientHandler::getClientHandlerInstance($cfg,$btclient);
                    $clientHandler->startClient($transfer, 0, false);
                }
            }
    	}
    	break;

/*******************************************************************************
 * selected transfers (index-page)
 ******************************************************************************/
    default:

    	// is enabled ?
		if ($cfg["enable_multiops"] != 1) {
			AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to use multi-op ".$action);
			showErrorPage("bulkops are disabled.");
		}

		foreach($_POST['transfer'] as $key => $element) {

			// is valid transfer ?
			if (isValidTransfer(urldecode($element)) !== true) {
				AuditAction($cfg["constants"]["error"], "Invalid Transfer for ".$action." : ".$cfg["user"]." tried to ".$action." ".$element);
				showErrorPage("Invalid Transfer for ".htmlentities($action, ENT_QUOTES)." : <br>".htmlentities($element, ENT_QUOTES));
			}

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
								include_once("inc/functions/functions.setpriority.php");
								// Process setPriority Request.
								setPriority(urldecode($element));
							}
							$clientHandler = ClientHandler::getClientHandlerInstance($cfg, $tclient);
							$clientHandler->startClient(urldecode($element), 0, $queueActive);
						} else {
							$clientHandler = ClientHandler::getClientHandlerInstance($cfg, $tclient);
							$clientHandler->startClient(urldecode($element), 0, false);
						}
					}
					break;

				case "transferStop": /* transferStop */
					if (($isTorrent) && ($tRunningFlag != 0)) {
						$clientHandler = ClientHandler::getClientHandlerInstance($cfg, $tclient);
						$clientHandler->stopClient(urldecode($element), $alias);
					}
					break;

				case "transferEnQueue": /* transferEnQueue */
					if (($isTorrent) && ($tRunningFlag == 0)) {
						// enqueue it
						if ($cfg["enable_file_priority"]) {
							include_once("inc/functions/functions.setpriority.php");
							// Process setPriority Request.
							setPriority(urldecode($element));
						}
						$clientHandler = ClientHandler::getClientHandlerInstance($cfg, $tclient);
						$clientHandler->startClient(urldecode($element), 0, true);
					}
					break;

				case "transferDeQueue": /* transferDeQueue */
					if (($isTorrent) && ($tRunningFlag == 0)) {
						// set request var
						$_REQUEST['alias_file'] = getAliasName($element).".stat";;
						// dequeue it
						$fluxdQmgr->dequeueTorrent($element, $cfg["user"]);
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

/*******************************************************************************
 * redirect
 ******************************************************************************/

if (isset($_SERVER["HTTP_REFERER"]))
	header("location: ".$_SERVER["HTTP_REFERER"]);
else
	header("location: index.php?iid=index");

?>