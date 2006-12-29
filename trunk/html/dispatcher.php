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

// all functions
require_once('inc/functions/functions.all.php');

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
		indexStartTransfer(urldecode(getRequestVar('transfer')));
    	break;
    case "indexUrlUpload":
		indexProcessDownload(urldecode(getRequestVar('url')), getRequestVar('type'));
    	break;
    case "indexDelete":
    	indexDeleteTransfer(urldecode(getRequestVar('transfer')));
    	break;
    case "indexStop":
    	indexStopTransfer(urldecode(getRequestVar('transfer')));
    	break;
    case "indexDeQueue":
    	indexDeQueueTransfer(urldecode(getRequestVar('transfer')));
    	break;

/*******************************************************************************
 * force-Stop
 ******************************************************************************/
    case "forceStop":
    	$transfer=urldecode(getRequestVar('transfer'));
    	preg_match("@.*/(.*)@", $transfer, $matches);
    	if(isset($matches[1]) && !empty($matches[1])){
			forceStopTransfer($matches[1], getRequestVar('pid'));
		} else {
			AuditAction($cfg["constants"]["error"], "FORCE STOP ERROR: ".$cfg["user"]." - could not determine file name from path: $transfer");
			@error(
				"Could not determine filename from file path:",
				(isset($_SERVER["HTTP_REFERER"])) ? $_SERVER["HTTP_REFERER"] : "",
				"",
				array($transfer)
			);
		}
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
			@error("wget is disabled", (isset($_SERVER["HTTP_REFERER"])) ? $_SERVER["HTTP_REFERER"] : "index.php?iid=index", "");
		} elseif ($cfg["enable_wget"] == 1) {
			if (!$cfg['isAdmin']) {
				AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to use wget");
				@error("wget is disabled for users", (isset($_SERVER["HTTP_REFERER"])) ? $_SERVER["HTTP_REFERER"] : "index.php?iid=index", "");
			}
		}
		$url = getRequestVar('url');
		if (!empty($url)) {
			$clientHandler = ClientHandler::getInstance('wget');
			$clientHandler->inject($url);
			if (getRequestVar('wget_start') == 1) {
				$clientHandler->start($url, false, false);
				if ($clientHandler->state == CLIENTHANDLER_STATE_ERROR) { // start failed
					$msgs = array();
					array_push($msgs, "transfer : ".$transfer);
					array_push($msgs, "\nmessages :");
					$msgs = array_merge($msgs, $clientHandler->messages);
					AuditAction($cfg["constants"]["error"], "Start failed: ".$transfer."\n".implode("\n", $clientHandler->messages));
					@error("Start failed", "", "", $msgs);
				} else {
					sleep(3);
				}
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
		// set transfers-cache
		cacheTransfersSet();
    	break;

/*******************************************************************************
 * Cache-Flush
 ******************************************************************************/
    case "cacheFlush":
    	// flush session-cache
		cacheFlush();
		// flush transfers-cache (not really needed as reload is triggered)
		cacheTransfersFlush();
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
			@error("bulkops are disabled", "index.php?iid=index", "");
		}
		// stop all
		$dispatcherMessages = array();
    	$transferList = getTorrentListFromFS();
    	foreach ($transferList as $transfer) {
            $tRunningFlag = isTransferRunning($transfer);
            if ($tRunningFlag != 0) {
                $owner = getOwner($transfer);
                if ((isset($owner)) && ($owner == $cfg["user"])) {
                    $btclient = getTransferClient($transfer);
                    $clientHandler = ClientHandler::getInstance($btclient);
                    $clientHandler->stop($transfer);
                    if (count($clientHandler->messages) > 0)
                    	$dispatcherMessages = array_merge($dispatcherMessages, $clientHandler->messages);
                }
            }
    	}
    	if (count($dispatcherMessages) > 0)
    		@error("There were Problems", (isset($_SERVER["HTTP_REFERER"])) ? $_SERVER["HTTP_REFERER"] : "index.php?iid=index", "", $dispatcherMessages);
    	break;

    case "bulkResume": /* bulkResume */
    	// is enabled ?
		if ($cfg["enable_bulkops"] != 1) {
			AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to use bulkResume");
			@error("bulkops are disabled", (isset($_SERVER["HTTP_REFERER"])) ? $_SERVER["HTTP_REFERER"] : "index.php?iid=index", "");
		}
		// resume all
		$dispatcherMessages = array();
    	$transferList = getTorrentListFromDB();
    	foreach ($transferList as $transfer) {
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
                    $clientHandler = ClientHandler::getInstance($btclient);
                    $clientHandler->start($transfer, false, false);
                    if (count($clientHandler->messages) > 0)
                    	$dispatcherMessages = array_merge($dispatcherMessages, $clientHandler->messages);
                }
            }
    	}
    	if (count($dispatcherMessages) > 0)
    		@error("There were Problems", (isset($_SERVER["HTTP_REFERER"])) ? $_SERVER["HTTP_REFERER"] : "index.php?iid=index", "", $dispatcherMessages);
    	break;

    case "bulkStart": /* bulkStart */
    	// is enabled ?
		if ($cfg["enable_bulkops"] != 1) {
			AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to use bulkStart");
			@error("bulkops are disabled", (isset($_SERVER["HTTP_REFERER"])) ? $_SERVER["HTTP_REFERER"] : "index.php?iid=index", "");
		}
		// start all
		$dispatcherMessages = array();
    	$transferList = getTorrentListFromFS();
    	foreach ($transferList as $transfer) {
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
                    $clientHandler = ClientHandler::getInstance($btclient);
                    $clientHandler->start($transfer, false, false);
                    if (count($clientHandler->messages) > 0)
                    	$dispatcherMessages = array_merge($dispatcherMessages, $clientHandler->messages);
                }
            }
    	}
    	if (count($dispatcherMessages) > 0)
    		@error("There were Problems", (isset($_SERVER["HTTP_REFERER"])) ? $_SERVER["HTTP_REFERER"] : "index.php?iid=index", "", $dispatcherMessages);
    	break;

/*******************************************************************************
 * selected transfers (index-page)
 ******************************************************************************/
    default:

    	// is enabled ?
		if ($cfg["enable_multiops"] != 1) {
			AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to use multi-op ".$action);
			@error("multiops are disabled", (isset($_SERVER["HTTP_REFERER"])) ? $_SERVER["HTTP_REFERER"] : "index.php?iid=index", "");
		}

		// messages-ary
		$dispatcherMessages = array();

		// loop
		foreach($_POST['transfer'] as $key => $element) {

			// url-decode
			$element = urldecode($element);

			// is valid transfer ?
			$invalid = true;
			if (isValidTransfer($element) === true) {
				// client
				if (substr($element, -8) == ".torrent") {
					// this is a torrent-client
					$clientType = "torrent";
					$invalid = false;
					$tclient = getTransferClient($element);
				} else if (substr($element, -5) == ".wget") {
					// this is wget.
					$clientType = "wget";
					$invalid = false;
					$tclient = "wget";
				} else if (substr($element, -4) == ".nzb") {
					// This is nzbperl.
					$clientType = "nzb";
					$invalid = false;
					$tclient = "nzbperl";
				}
			}
			if ($invalid) {
				AuditAction($cfg["constants"]["error"], "INVALID TRANSFER: ".$cfg["user"]." tried to ".$action." ".$element);
				array_push($dispatcherMessages, "Invalid Transfer : ".$transfer);
				continue;
			}

			// is transfer running ?
			$tRunningFlag = isTransferRunning($element);

			// action switch
			switch ($action) {

				case "transferStart": /* transferStart */
					if ($tRunningFlag == 0) {
						$clientHandler = ClientHandler::getInstance($tclient);
						if ($clientType == "torrent") {
							if ($cfg["enable_file_priority"]) {
								include_once("inc/functions/functions.setpriority.php");
								// Process setPriority Request.
								setPriority($element);
							}
							$clientHandler->start($element, false, FluxdQmgr::isRunning());
						} else {
							$clientHandler->start($element, false, false);
						}
						if (count($clientHandler->messages) > 0)
                    		$dispatcherMessages = array_merge($dispatcherMessages, $clientHandler->messages);
					}
					break;

				case "transferStop": /* transferStop */
					if (($clientType != "wget") && ($tRunningFlag != 0)) {
						$clientHandler = ClientHandler::getInstance($tclient);
						$clientHandler->stop($element);
						if (count($clientHandler->messages) > 0)
                    		$dispatcherMessages = array_merge($dispatcherMessages, $clientHandler->messages);
					}
					break;

				case "transferEnQueue": /* transferEnQueue */
					if (($clientType == "torrent") && ($tRunningFlag == 0)) {
						// enqueue it
						if ($cfg["enable_file_priority"]) {
							include_once("inc/functions/functions.setpriority.php");
							// Process setPriority Request.
							setPriority($element);
						}
						$clientHandler = ClientHandler::getInstance($tclient);
						$clientHandler->start($element, false, true);
						if (count($clientHandler->messages) > 0)
                    		$dispatcherMessages = array_merge($dispatcherMessages, $clientHandler->messages);
					}
					break;

				case "transferDeQueue": /* transferDeQueue */
					if (($clientType == "torrent") && ($tRunningFlag == 0)) {
						// dequeue it
						FluxdQmgr::dequeueTransfer($element, $cfg['user']);
					}
					break;

				case "transferResetTotals": /* transferResetTotals */
					if ($clientType == "torrent") {
						$msgs = resetTorrentTotals($element, false);
						if (count($msgs) > 0)
	                    	$dispatcherMessages = array_merge($dispatcherMessages, $msgs);
					}
					break;

				default:
					if (($clientType != "wget") && ($tRunningFlag != 0)) {
						// stop torrent first
						$clientHandler = ClientHandler::getInstance($tclient);
						$clientHandler->stop($element);
						if (count($clientHandler->messages) > 0)
                    		$dispatcherMessages = array_merge($dispatcherMessages, $clientHandler->messages);
						// is transfer running ?
						$tRunningFlag = isTransferRunning($element);
					}
					// if it was running... hope the thing is down...
					// only continue if it is
					if ($tRunningFlag == 0) {
						switch ($action) {
							case "transferWipe": /* transferWipe */
								if ($clientType == "torrent") {
									deleteTorrentData($element);
									$msgs = resetTorrentTotals($element, true);
									if (count($msgs) > 0)
                    					$dispatcherMessages = array_merge($dispatcherMessages, $msgs);
								}
								break;
							case "transferData": /* transferData */
								if ($clientType == "torrent")
									deleteTorrentData($element);
							case "transfer": /* transfer */
								$clientHandler = ClientHandler::getInstance($tclient);
								$clientHandler->delete($element);
								if (count($clientHandler->messages) > 0)
                    				$dispatcherMessages = array_merge($dispatcherMessages, $clientHandler->messages);
						}
					}

			} // end switch
		} // end loop
		if (count($dispatcherMessages) > 0)
			@error("There were Problems", (isset($_SERVER["HTTP_REFERER"])) ? $_SERVER["HTTP_REFERER"] : "index.php?iid=index", "", $dispatcherMessages);
}

/*******************************************************************************
 * redirect
 ******************************************************************************/
if (isset($_SERVER["HTTP_REFERER"]))
	@header("location: ".$_SERVER["HTTP_REFERER"]);
else
	@header("location: index.php?iid=index");

?>