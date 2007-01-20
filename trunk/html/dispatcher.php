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
    	forceStopTransfer(urldecode(getRequestVar('transfer')), getRequestVar('pid'));
    	break;

/*******************************************************************************
 * file-upload
 ******************************************************************************/
	case "fileUpload":
		processFileUpload();
    	break;

/*******************************************************************************
 * metafile-download
 ******************************************************************************/
	case "metafileDownload":
		sendMetafile(getRequestVar('transfer'));
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
			$ch = ClientHandler::getInstance('wget');
			$ch->inject($url);
			// instant action ?
			$actionId = getRequestVar('aid');
			if ($actionId > 1) {
				switch ($actionId) {
					case 3:
						$ch->start($ch->transfer, false, true);
						break;
					case 2:
						$ch->start($ch->transfer, false, false);
						break;
				}
				if ($ch->state == CLIENTHANDLER_STATE_ERROR) { // start failed
					$msgs = array();
					array_push($msgs, "url : ".$url);
					array_push($msgs, "\nmessages :");
					$msgs = array_merge($msgs, $ch->messages);
					AuditAction($cfg["constants"]["error"], "Start failed: ".$url."\n".implode("\n", $ch->messages));
					@error("Start failed", "", "", $msgs);
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
    	$transferList = getTransferArray();
    	foreach ($transferList as $transfer) {
            if (isTransferRunning($transfer)) {
                if (($cfg['isAdmin']) || (IsOwner($cfg["user"], getOwner($transfer)))) {
                    $ch = ClientHandler::getInstance(getTransferClient($transfer));
                    $ch->stop($transfer);
                    if (count($ch->messages) > 0)
                    	$dispatcherMessages = array_merge($dispatcherMessages, $ch->messages);
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
    	$transferList = getTransferArrayFromDB();
    	foreach ($transferList as $transfer) {
            if (!isTransferRunning($transfer)) {
                if (($cfg['isAdmin']) || (IsOwner($cfg["user"], getOwner($transfer)))) {
                    if ($cfg["enable_file_priority"]) {
                        include_once("inc/functions/functions.setpriority.php");
                        // Process setPriority Request.
                        setPriority($transfer);
                    }
                    $ch = ClientHandler::getInstance(getTransferClient($transfer));
                    $ch->start($transfer, false, false);
                    if (count($ch->messages) > 0)
                    	$dispatcherMessages = array_merge($dispatcherMessages, $ch->messages);
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
    	$transferList = getTransferArray();
    	foreach ($transferList as $transfer) {
            if (!isTransferRunning($transfer)) {
                if (($cfg['isAdmin']) || (IsOwner($cfg["user"], getOwner($transfer)))) {
                    if ($cfg["enable_file_priority"]) {
                        include_once("inc/functions/functions.setpriority.php");
                        // Process setPriority Request.
                        setPriority($transfer);
                    }
                    $ch = ClientHandler::getInstance(getTransferClient($transfer));
                    $ch->start($transfer, false, false);
                    if (count($ch->messages) > 0)
                    	$dispatcherMessages = array_merge($dispatcherMessages, $ch->messages);
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
		foreach ($_POST['transfer'] as $key => $element) {

			// url-decode
			$transfer = urldecode($element);

			// is valid transfer ? + check permissions
			$invalid = true;
			if (isValidTransfer($transfer) === true) {
				if (substr($transfer, -8) == ".torrent") {
					// this is a torrent-client
					$invalid = false;
				} else if (substr($transfer, -5) == ".wget") {
					// this is wget.
					$invalid = false;
					// is enabled ?
					if ($cfg["enable_wget"] == 0) {
						$invalid = true;
						AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to use wget");
						array_push($dispatcherMessages, "wget is disabled : ".$transfer);
					} else if ($cfg["enable_wget"] == 1) {
						if (!$cfg['isAdmin']) {
							$invalid = true;
							AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to use wget");
							array_push($dispatcherMessages, "wget is disabled for users : ".$transfer);
						}
					}
				} else if (substr($transfer, -4) == ".nzb") {
					// This is nzbperl.
					$invalid = false;
					if ($cfg["enable_nzbperl"] == 0) {
						$invalid = true;
						AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to use nzbperl");
						array_push($dispatcherMessages, "nzbperl is disabled : ".$transfer);
					} else if ($cfg["enable_nzbperl"] == 1) {
						if (!$cfg['isAdmin']) {
							$invalid = true;
							AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to use nzbperl");
							array_push($dispatcherMessages, "nzbperl is disabled for users : ".$transfer);
						}
					}
				}
			}
			if ($invalid) {
				AuditAction($cfg["constants"]["error"], "INVALID TRANSFER: ".$cfg["user"]." tried to ".$action." ".$transfer);
				array_push($dispatcherMessages, "Invalid Transfer : ".$transfer);
				continue;
			}

			// client
			$client = getTransferClient($transfer);

			// is transfer running ?
			$tRunningFlag = isTransferRunning($transfer);

			// action switch
			switch ($action) {

				case "transferStart": /* transferStart */
					if (!$tRunningFlag) {
						if ($cfg["enable_file_priority"]) {
							include_once("inc/functions/functions.setpriority.php");
							// Process setPriority Request.
							setPriority($transfer);
						}
						$ch = ClientHandler::getInstance($client);
						$ch->start($transfer, false, FluxdQmgr::isRunning());
						if (count($ch->messages) > 0)
                    		$dispatcherMessages = array_merge($dispatcherMessages, $ch->messages);
					}
					break;

				case "transferStop": /* transferStop */
					if ($tRunningFlag) {
						$ch = ClientHandler::getInstance($client);
						$ch->stop($transfer);
						if (count($ch->messages) > 0)
                    		$dispatcherMessages = array_merge($dispatcherMessages, $ch->messages);
					}
					break;

				case "transferEnQueue": /* transferEnQueue */
					if (!$tRunningFlag) {
						// enqueue it
						if ($cfg["enable_file_priority"]) {
							include_once("inc/functions/functions.setpriority.php");
							// Process setPriority Request.
							setPriority($transfer);
						}
						$ch = ClientHandler::getInstance($client);
						$ch->start($transfer, false, true);
						if (count($ch->messages) > 0)
                    		$dispatcherMessages = array_merge($dispatcherMessages, $ch->messages);
					}
					break;

				case "transferDeQueue": /* transferDeQueue */
					if (!$tRunningFlag) {
						// dequeue it
						FluxdQmgr::dequeueTransfer($transfer, $cfg['user']);
					}
					break;

				case "transferResetTotals": /* transferResetTotals */
					$msgs = resetTransferTotals($transfer, false);
					if (count($msgs) > 0)
                    	$dispatcherMessages = array_merge($dispatcherMessages, $msgs);
					break;

				default:
					if ($tRunningFlag) {
						// stop first
						$ch = ClientHandler::getInstance($client);
						$ch->stop($transfer);
						if (count($ch->messages) > 0)
                    		$dispatcherMessages = array_merge($dispatcherMessages, $ch->messages);
						// is transfer running ?
						$tRunningFlag = isTransferRunning($transfer);
					}
					// if it was running... hope the thing is down...
					// only continue if it is
					if (!$tRunningFlag) {
						switch ($action) {
							case "transferWipe": /* transferWipe */
								deleteTransferData($transfer);
								$msgs = resetTransferTotals($transfer, true);
								if (count($msgs) > 0)
                					$dispatcherMessages = array_merge($dispatcherMessages, $msgs);
								break;
							case "transferData": /* transferData */
								deleteTransferData($transfer);
							case "transfer": /* transfer */
								$ch = ClientHandler::getInstance($client);
								$ch->delete($transfer);
								if (count($ch->messages) > 0)
                    				$dispatcherMessages = array_merge($dispatcherMessages, $ch->messages);
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