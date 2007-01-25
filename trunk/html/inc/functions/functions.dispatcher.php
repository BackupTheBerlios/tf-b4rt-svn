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

// upload-limit
define("_UPLOAD_LIMIT", 10000000);

/**
 * indexStartTransfer
 *
 * @param $transfer
 */
function indexStartTransfer($transfer) {
	global $cfg;
	$invalid = true;
	if (isValidTransfer($transfer) === true) {
		if (substr($transfer, -8) == ".torrent") {
			// this is a torrent-client
			$invalid = false;
			$interactiveStart = getRequestVar('interactive');
			if ((isset($interactiveStart)) && ($interactiveStart)) // interactive
				indexStartTorrent($transfer, 1);
			else // silent
				indexStartTorrent($transfer, 0);
		} else if (substr($transfer, -5) == ".wget") {
			// this is wget.
			$invalid = false;
			// is enabled ?
			if ($cfg["enable_wget"] == 0) {
				AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to start wget ".$transfer);
				@error("wget is disabled", "index.php?iid=index", "");
			} else if ($cfg["enable_wget"] == 1) {
				if (!$cfg['isAdmin']) {
					AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to start wget ".$transfer);
					@error("wget is disabled for users", "index.php?iid=index", "");
				}
			}
			$ch = ClientHandler::getInstance('wget');
			$ch->start($transfer, false, FluxdQmgr::isRunning());
			if ($ch->state == CLIENTHANDLER_STATE_ERROR) { // start failed
				$msgs = array();
				array_push($msgs, "transfer : ".$transfer);
				array_push($msgs, "\nmessages :");
				$msgs = array_merge($msgs, $ch->messages);
				AuditAction($cfg["constants"]["error"], "Start failed: ".$transfer."\n".implode("\n", $ch->messages));
				@error("Start failed", "", "", $msgs);
			} else {
				@header("location: index.php?iid=index");
				exit();
			}
		} else if (substr($transfer, -4) == ".nzb") {
			// This is nzbperl.
			$invalid = false;
			if ($cfg["enable_nzbperl"] == 0) {
				AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to start nzbperl ".$transfer);
				@error("nzbperl is disabled", "index.php?iid=index", "");
			} else if ($cfg["enable_nzbperl"] == 1) {
				if (!$cfg['isAdmin']) {
					AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to start nzbperl ".$transfer);
					@error("nzbperl is disabled for users", "index.php?iid=index", "");
				}
			}
			$ch = ClientHandler::getInstance('nzbperl');
			$ch->start($transfer, false, FluxdQmgr::isRunning());
			if ($ch->state == CLIENTHANDLER_STATE_ERROR) { // start failed
				$msgs = array();
				array_push($msgs, "transfer : ".$transfer);
				array_push($msgs, "\nmessages :");
				$msgs = array_merge($msgs, $ch->messages);
				AuditAction($cfg["constants"]["error"], "Start failed: ".$transfer."\n".implode("\n", $ch->messages));
				@error("Start failed", "", "", $msgs);
			} else {
				@header("location: index.php?iid=index");
				exit();
			}
		}
	}
	if ($invalid) {
		AuditAction($cfg["constants"]["error"], "INVALID TRANSFER: ".$transfer);
		@error("Invalid Transfer", "index.php?iid=index", "", array($transfer));
	}
}

/**
 * Function with which torrents are started in index-page
 *
 * @param $transfer
 * @param $interactive (1|0) : is this a interactive startup with dialog ?
 */
function indexStartTorrent($transfer, $interactive) {
	global $cfg, $transfers;
	$ch = ($interactive == 1)
		? ClientHandler::getInstance(getRequestVar('btclient'))
		: ClientHandler::getInstance(getTransferClient($transfer));
	if ($interactive == 1)
		$ch->start($transfer, true, (getRequestVar('queue') == 'true') ? FluxdQmgr::isRunning() : false);
	else
		$ch->start($transfer, false, FluxdQmgr::isRunning());
	if ($ch->state == CLIENTHANDLER_STATE_ERROR) { // start failed
		$msgs = array();
		array_push($msgs, "transfer : ".$transfer);
		array_push($msgs, "\nmessages :");
		$msgs = array_merge($msgs, $ch->messages);
		AuditAction($cfg["constants"]["error"], "Start failed: ".$transfer."\n".implode("\n", $ch->messages));
		@error("Start failed", "", "", $msgs);
	} else {
		if (array_key_exists("closeme", $_POST)) {
			echo '<script  language="JavaScript">';
			echo ' window.opener.location.reload(true);';
			echo ' window.close();';
			echo '</script>';
		} else {
			@header("location: index.php?iid=index");
		}
	}
	exit();
}

/**
 * indexStopTransfer
 *
 * @param $transfer
 */
function indexStopTransfer($transfer) {
	global $cfg;
	$invalid = true;
	if (isValidTransfer($transfer) === true) {
		if (substr($transfer, -8) == ".torrent") {
			$invalid = false;
		} else if (substr($transfer, -5) == ".wget") {
			// is enabled ?
			if ($cfg["enable_wget"] == 0) {
				AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to stop wget ".$transfer);
				@error("wget is disabled", "index.php?iid=index", "");
			} else if ($cfg["enable_wget"] == 1) {
				if (!$cfg['isAdmin']) {
					AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to stop wget ".$transfer);
					@error("wget is disabled for users", "index.php?iid=index", "");
				}
			}
			$invalid = false;
		} else if (substr($transfer, -4) == ".nzb") {
			if ($cfg["enable_nzbperl"] == 0) {
				AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to stop nzbperl ".$transfer);
				@error("nzbperl is disabled", "index.php?iid=index", "");
			} else if ($cfg["enable_nzbperl"] == 1) {
				if (!$cfg['isAdmin']) {
					AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to stop nzbperl ".$transfer);
					@error("nzbperl is disabled for users", "index.php?iid=index", "");
				}
			}
			$invalid = false;
		}
		if (!$invalid) {
			$ch = ClientHandler::getInstance(getTransferClient($transfer));
			$ch->stop($transfer);
			if (count($ch->messages) > 0)
	    		@error("There were Problems", "index.php?iid=index", "", $ch->messages);
		}
	}
	if ($invalid) {
		AuditAction($cfg["constants"]["error"], "INVALID TRANSFER: ".$transfer);
		@error("Invalid Transfer", "index.php?iid=index", "", array($transfer));
	}
}

/**
 * forceStopTransfer
 *
 * @param $transfer
 */
function forceStopTransfer($transfer, $pid) {
	global $cfg;
	$invalid = true;
	if (isValidTransfer($transfer) === true) {
		if (substr($transfer, -8) == ".torrent") {
			$invalid = false;
		} else if (substr($transfer, -5) == ".wget") {
			// is enabled ?
			if ($cfg["enable_wget"] == 0) {
				AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to force-stop wget ".$transfer);
				@error("wget is disabled", "index.php?iid=index", "");
			} else if ($cfg["enable_wget"] == 1) {
				if (!$cfg['isAdmin']) {
					AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to force-stop wget ".$transfer);
					@error("wget is disabled for users", "", "");
				}
			}
			$invalid = false;
		} else if (substr($transfer, -4) == ".nzb") {
			if ($cfg["enable_nzbperl"] == 0) {
				AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to force-stop nzbperl ".$transfer);
				@error("nzbperl is disabled", "index.php?iid=index", "");
			} else if ($cfg["enable_nzbperl"] == 1) {
				if (!$cfg['isAdmin']) {
					AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to force-stop nzbperl ".$transfer);
					@error("nzbperl is disabled for users", "", "");
				}
			}
			$invalid = false;
		}
		if (!$invalid) {
			$ch = ClientHandler::getInstance(getTransferClient($transfer));
			$ch->stop($transfer, true, $pid);
			if (count($ch->messages) > 0)
	    		@error("There were Problems", "index.php?iid=index", "", $ch->messages);
		}
	}
	if ($invalid) {
		AuditAction($cfg["constants"]["error"], "INVALID TRANSFER: ".$transfer);
		@error("Invalid Transfer", (isset($_SERVER["HTTP_REFERER"])) ? $_SERVER["HTTP_REFERER"] : "", "", array($transfer));
	}
}

/**
 * indexDeleteTransfer
 *
 * @param $transfer
 */
function indexDeleteTransfer($transfer) {
	global $cfg;
	if (isValidTransfer($transfer) === true) {
		if (substr($transfer, -5) == ".wget") {
			// is enabled ?
			if ($cfg["enable_wget"] == 0) {
				AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to delete wget-file ".$transfer);
				@error("wget is disabled", "index.php?iid=index", "");
			} else if ($cfg["enable_wget"] == 1) {
				if (!$cfg['isAdmin']) {
					AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to delete wget-file ".$transfer);
					@error("wget is disabled for users", "", "");
				}
			}
		} else if (substr($transfer, -4) == ".nzb") {
			// is enabled ?
			if ($cfg["enable_nzbperl"] == 0) {
				AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to delete nzb-file ".$transfer);
				@error("nzbperl is disabled", "index.php?iid=index", "");
			} else if ($cfg["enable_nzbperl"] == 1) {
				if (!$cfg['isAdmin']) {
					AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to delete nzb-file ".$transfer);
					@error("nzbperl is disabled for users", "", "");
				}
			}
		}
		$ch = ClientHandler::getInstance(getTransferClient($transfer));
		$ch->delete($transfer);
		if (count($ch->messages) > 0)
    		@error("There were Problems", "index.php?iid=index", "", $ch->messages);
		@header("location: index.php?iid=index");
		exit();
	} else {
		AuditAction($cfg["constants"]["error"], "INVALID TRANSFER: ".$transfer);
		@error("Invalid Transfer", "index.php?iid=index", "", array($transfer));
	}
}

/**
 * indexDeQueueTransfer
 *
 * @param $transfer
 */
function indexDeQueueTransfer($transfer) {
	global $cfg;
	if (isValidTransfer($transfer) === true) {
		if (substr($transfer, -5) == ".wget") {
			// is enabled ?
			if ($cfg["enable_wget"] == 0) {
				AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to dequeue wget-file ".$transfer);
				@error("wget is disabled", "index.php?iid=index", "");
			} else if ($cfg["enable_wget"] == 1) {
				if (!$cfg['isAdmin']) {
					AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to dequeue wget-file ".$transfer);
					@error("wget is disabled for users", "", "");
				}
			}
		} else if (substr($transfer, -4) == ".nzb") {
			// is enabled ?
			if ($cfg["enable_nzbperl"] == 0) {
				AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to dequeue nzb-file ".$transfer);
				@error("nzbperl is disabled", "index.php?iid=index", "");
			} else if ($cfg["enable_nzbperl"] == 1) {
				if (!$cfg['isAdmin']) {
					AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to dequeue nzb-file ".$transfer);
					@error("nzbperl is disabled for users", "", "");
				}
			}
		}
		FluxdQmgr::dequeueTransfer($transfer, $cfg['user']);
		@header("location: index.php?iid=index");
		exit();
	} else {
		AuditAction($cfg["constants"]["error"], "INVALID TRANSFER: ".$transfer);
		@error("Invalid Transfer", "index.php?iid=index", "", array($transfer));
	}
}

/**
 * indexInjectWget
 *
 * @param $url
 */
function indexInjectWget($url) {
	global $cfg;
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
}

/**
 * dispatcherSetFilePriority
 *
 * @param $transfer
 */
function dispatcherSetFilePriority($transfer) {
	global $cfg;
	if ($cfg["enable_file_priority"])
		setFilePriority($transfer);
}

/**
 * dispatcherSet
 *
 * @param $key
 * @param $val
 */
function dispatcherSet($key, $val) {
	if (!empty($key)) {
		if ($key == "_all_") {
			$keys = array_keys($_SESSION['settings']);
			foreach ($keys as $settingKey)
				$_SESSION['settings'][$settingKey] = $val;
		} else {
			$_SESSION['settings'][$key] = $val;
		}
	}
}

/**
 * dispatcherBulk
 *
 * @param $op
 */
function dispatcherBulk($op) {
	global $cfg;
	// is enabled ?
	if ($cfg["enable_bulkops"] != 1) {
		AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to use ".$op);
		@error("bulkops are disabled", "index.php?iid=index", "");
	}
	// messages
	$dispatcherMessages = array();
	// op-switch
	switch ($op) {
	    case "stop":
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
	    	break;
	    case "resume":
	    	$transferList = getTransferArray();
	    	$sf = new StatFile("");
	    	foreach ($transferList as $transfer) {
				$sf->init($transfer);
		        if (((trim($sf->running)) == 0) && (!isTransferRunning($transfer))) {
	                if (($cfg['isAdmin']) || (IsOwner($cfg["user"], getOwner($transfer)))) {
	                    $ch = ClientHandler::getInstance(getTransferClient($transfer));
	                    $ch->start($transfer, false, false);
	                    if (count($ch->messages) > 0)
	                    	$dispatcherMessages = array_merge($dispatcherMessages, $ch->messages);
	                }
	            }
	    	}
	    	break;
	    case "start":
	    	$transferList = getTransferArray();
	    	foreach ($transferList as $transfer) {
	            if (!isTransferRunning($transfer)) {
	                if (($cfg['isAdmin']) || (IsOwner($cfg["user"], getOwner($transfer)))) {
	                    $ch = ClientHandler::getInstance(getTransferClient($transfer));
	                    $ch->start($transfer, false, false);
	                    if (count($ch->messages) > 0)
	                    	$dispatcherMessages = array_merge($dispatcherMessages, $ch->messages);
	                }
	            }
	    	}
	    	break;
	}
	// error if messages
	if (count($dispatcherMessages) > 0)
		@error("There were Problems", (isset($_SERVER["HTTP_REFERER"])) ? $_SERVER["HTTP_REFERER"] : "index.php?iid=index", "", $dispatcherMessages);
}

/**
 * dispatcherMulti
 *
 * @param $action
 */
function dispatcherMulti($action) {
	global $cfg;

	// is enabled ?
	if ($cfg["enable_multiops"] != 1) {
		AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to use multi-op ".$action);
		@error("multiops are disabled", (isset($_SERVER["HTTP_REFERER"])) ? $_SERVER["HTTP_REFERER"] : "index.php?iid=index", "");
	}

	// messages-ary
	$dispatcherMessages = array();

	// loop
	if (empty($_POST['transfer'])) return;
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
							$msgsDelete = deleteTransferData($transfer);
							if (count($msgsDelete) > 0)
                				$dispatcherMessages = array_merge($dispatcherMessages, $msgsDelete);
							$msgsReset = resetTransferTotals($transfer, true);
							if (count($msgsReset) > 0)
            					$dispatcherMessages = array_merge($dispatcherMessages, $msgsReset);
							break;
						case "transferData": /* transferData */
							$msgsDelete = deleteTransferData($transfer);
							if (count($msgsDelete) > 0)
                				$dispatcherMessages = array_merge($dispatcherMessages, $msgsDelete);
						case "transfer": /* transfer */
							$ch = ClientHandler::getInstance($client);
							$ch->delete($transfer);
							if (count($ch->messages) > 0)
                				$dispatcherMessages = array_merge($dispatcherMessages, $ch->messages);
					}
				}

		} // end switch

	} // end loop

	// error if messages
	if (count($dispatcherMessages) > 0)
		@error("There were Problems", (isset($_SERVER["HTTP_REFERER"])) ? $_SERVER["HTTP_REFERER"] : "index.php?iid=index", "", $dispatcherMessages);
}

/**
 * Function with which metafiles are downloaded and injected on index-page
 *
 * @param $url url of metafile to download
 */
function indexProcessDownload($url, $type = 'torrent') {
	global $cfg;
	switch ($type) {
		default:
		case 'torrent':
			// is enabled ?
			if ($cfg["enable_metafile_download"] != 1) {
				AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to use metafile download");
				@error("metafile download is disabled", "index.php?iid=index", "");
			}
			// process download
			_indexProcessDownload($url, 'torrent', '.torrent');
			break;
		case 'nzb':
			// is enabled ?
			if ($cfg["enable_nzbperl"] == 0) {
				AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to use nzb-download");
				@error("nzbperl is disabled", "index.php?iid=index", "");
			} else if ($cfg["enable_nzbperl"] == 1) {
				if (!$cfg['isAdmin']) {
					AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to use nzb-download");
					@error("nzbperl is disabled for users", "index.php?iid=index", "");
				}
			}
			// process download
			_indexProcessDownload($url, 'nzb', '.nzb');
			break;
	}
}

/**
 * (internal) Function with which metafiles are downloaded and injected on index-page
 *
 * @param $url url to download
 * @param $type
 */
function _indexProcessDownload($url, $type = 'torrent', $ext = '.torrent') {
	global $cfg;
	$filename = "";
	$downloadMessages = array();
	if (!empty($url)) {
		$arURL = explode("/", $url);
		$filename = urldecode($arURL[count($arURL)-1]); // get the file name
		$filename = str_replace(array("'",","), "", $filename);
		$filename = stripslashes($filename);
		// Check to see if url has something like ?passkey=12345
		// If so remove it.
		if (($point = strrpos($filename, "?")) !== false )
			$filename = substr($filename, 0, $point);
		$ret = strrpos($filename, ".");
		if ($ret === false) {
			$filename .= $ext;
		} else {
			if (!strcmp(strtolower(substr($filename, -(strlen($ext)))), $ext) == 0)
				$filename .= $ext;
		}
		$url = str_replace(" ", "%20", $url);
		// This is to support Sites that pass an id along with the url for downloads.
		$tmpId = getRequestVar("id");
		if(!empty($tmpId))
			$url .= "&id=".$tmpId;
		// retrieve the file
		require_once("inc/classes/SimpleHTTP.php");
		$content = "";
		switch ($type) {
			default:
			case 'torrent':
				$content = SimpleHTTP::getTorrent($url);
				break;
			case 'nzb':
				$content = SimpleHTTP::getNzb($url);
				break;
		}
		if ((SimpleHTTP::getState() == SIMPLEHTTP_STATE_OK) && (strlen($content) > 0)) {
			$fileNameBackup = $filename;
			$filename = SimpleHTTP::getFilename();
			if ($filename != "") {
				$filename = ((strpos($filename, $ext) !== false))
					? cleanFileName($filename)
					: cleanFileName($filename.$ext);
			}
			if (($filename == "") || ($filename === false) || (transferExists($filename))) {
				$filename = cleanFileName($fileNameBackup);
				if (($filename === false) || (transferExists($filename))) {
					$filename = cleanFileName($url.$ext);
					if (($filename === false) || (transferExists($filename))) {
						$filename = cleanFileName(md5($url.strval(@microtime())).$ext);
						if (($filename === false) || (transferExists($filename))) {
							// Error
							array_push($downloadMessages , "failed to get a valid transfer-filename for ".$url);
						}
					}
				}
			}
			if (empty($downloadMessages)) { // no messages
				// check if content contains html
				if ($cfg['debuglevel'] > 0) {
					if (strpos($content, "<br />") !== false)
						AuditAction($cfg["constants"]["debug"], "download-content contained html : ".htmlentities(addslashes($url), ENT_QUOTES));
				}
				if (is_file($cfg["transfer_file_path"].$filename)) {
					// Error
					array_push($downloadMessages, "the file ".$filename." already exists on the server.");
				} else {
					// write to file
					$handle = false;
					$handle = @fopen($cfg["transfer_file_path"].$filename, "w");
					if (!$handle) {
						array_push($downloadMessages, "cannot open ".$filename." for writing.");
					} else {
						$result = @fwrite($handle, $content);
						@fclose($handle);
						if ($result === false)
							array_push($downloadMessages, "cannot write content to ".$filename.".");
					}
				}
			}
		} else {
			$msgs = SimpleHTTP::getMessages();
			if (count($msgs) > 0)
				$downloadMessages = array_merge($downloadMessages, $msgs);
		}
		if (empty($downloadMessages)) { // no messages
			AuditAction($cfg["constants"]["url_upload"], $filename);
			// inject
			injectTransfer($filename);
			// instant action ?
			$actionId = getRequestVar('aid');
			if ($actionId > 1) {
				$ch = ClientHandler::getInstance(getTransferClient($filename));
				switch ($actionId) {
					case 3:
						$ch->start($filename, false, true);
						break;
					case 2:
						$ch->start($filename, false, false);
						break;
				}
				if (count($ch->messages) > 0)
               		$downloadMessages = array_merge($downloadMessages, $ch->messages);
			}
		}
	} else {
		array_push($downloadMessages, "Invalid Url : ".$url);
	}
	if (count($downloadMessages) > 0) {
		AuditAction($cfg["constants"]["error"], $cfg["constants"]["url_upload"]." :: ".$filename);
		@error("There were Problems", "index.php?iid=index", "", $downloadMessages);
	} else {
		@header("location: index.php?iid=index");
		exit();
	}
}

/**
 * Function with which metafiles are uploaded and injected on index-page
 */
function indexProcessUpload() {
	global $cfg;
	$filename = "";
	$uploadMessages = array();
	if ((isset($_FILES['upload_file'])) && (!empty($_FILES['upload_file']['name']))) {
		$filename = stripslashes($_FILES['upload_file']['name']);
		$filename = cleanFileName($filename);
		if ($filename === false) {
			// invalid file
			array_push($uploadMessages, "The type of file you are uploading is not allowed.");
			array_push($uploadMessages, "\nvalid file-extensions: ");
			array_push($uploadMessages, $cfg["file_types_label"]);
		} else {
			// file is valid
			if (substr($filename, -5) == ".wget") {
				// is enabled ?
				if ($cfg["enable_wget"] == 0) {
					AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to upload wget-file ".$filename);
					@error("wget is disabled", "index.php?iid=index", "");
				} else if ($cfg["enable_wget"] == 1) {
					if (!$cfg['isAdmin']) {
						AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to upload wget-file ".$filename);
						@error("wget is disabled for users", "", "");
					}
				}
			} else if (substr($filename, -4) == ".nzb") {
				// is enabled ?
				if ($cfg["enable_nzbperl"] == 0) {
					AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to upload nzb-file ".$filename);
					@error("nzbperl is disabled", "index.php?iid=index", "");
				} else if ($cfg["enable_nzbperl"] == 1) {
					if (!$cfg['isAdmin']) {
						AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to upload nzb-file ".$filename);
						@error("nzbperl is disabled for users", "", "");
					}
				}
			}
			if ($_FILES['upload_file']['size'] <= _UPLOAD_LIMIT && $_FILES['upload_file']['size'] > 0) {
				//FILE IS BEING UPLOADED
				if (@is_file($cfg["transfer_file_path"].$filename)) {
					// Error
					array_push($uploadMessages, "the file ".$filename." already exists on the server.");
				} else {
					if (@move_uploaded_file($_FILES['upload_file']['tmp_name'], $cfg["transfer_file_path"].$filename)) {
						@chmod($cfg["transfer_file_path"].$filename, 0644);
						AuditAction($cfg["constants"]["file_upload"], $filename);
						// inject
						injectTransfer($filename);
						// instant action ?
						$actionId = getRequestVar('aid');
						if ($actionId > 1) {
							$ch = ClientHandler::getInstance(getTransferClient($filename));
							switch ($actionId) {
								case 3:
									$ch->start($filename, false, true);
									break;
								case 2:
									$ch->start($filename, false, false);
									break;
							}
							if (count($ch->messages) > 0)
	           					$uploadMessages = array_merge($uploadMessages, $ch->messages);
						}
					} else {
						array_push($uploadMessages, "File not uploaded, file could not be found or could not be moved: ".$cfg["transfer_file_path"].$filename);
					}
				}
			} else {
				array_push($uploadMessages, "File not uploaded, file size limit is "._UPLOAD_LIMIT.". file has ".$_FILES['upload_file']['size']);
			}
		}
	}
	if (count($uploadMessages) > 0) {
		AuditAction($cfg["constants"]["error"], $cfg["constants"]["file_upload"]." :: ".$filename);
		@error("There were Problems", "index.php?iid=index", "", $uploadMessages);
	} else {
		@header("location: index.php?iid=index");
		exit();
	}
}

/**
 * file-upload
 */
function processFileUpload() {
	global $cfg;
	$filename = "";
	$uploadMessages = array();
	// file upload
	if (!empty($_FILES['upload_files'])) {
		// action-id
		$actionId = getRequestVar('aid');
		// stack
		$tStack = array();
		// process upload
		foreach($_FILES['upload_files']['size'] as $id => $size) {
			if ($size == 0) {
				// no or empty file, skip it
				continue;
			}
			$filename = stripslashes($_FILES['upload_files']['name'][$id]);
			$filename = cleanFileName($filename);
			if ($filename === false) {
				// invalid file
				array_push($uploadMessages, "The type of file ".stripslashes($_FILES['upload_files']['name'][$id])." is not allowed.");
				array_push($uploadMessages, "\nvalid file-extensions: ");
				array_push($uploadMessages, $cfg["file_types_label"]);
				continue;
			} else {
				// file is valid
				if (substr($filename, -5) == ".wget") {
					// is enabled ?
					if ($cfg["enable_wget"] == 0) {
						AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to upload wget-file ".$filename);
						array_push($uploadMessages, "wget is disabled  : ".$filename);
						continue;
					} else if ($cfg["enable_wget"] == 1) {
						if (!$cfg['isAdmin']) {
							AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to upload wget-file ".$filename);
							array_push($uploadMessages, "wget is disabled for users : ".$filename);
							continue;
						}
					}
				} else if (substr($filename, -4) == ".nzb") {
					// is enabled ?
					if ($cfg["enable_nzbperl"] == 0) {
						AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to upload nzb-file ".$filename);
						array_push($uploadMessages, "nzbperl is disabled  : ".$filename);
						continue;
					} else if ($cfg["enable_nzbperl"] == 1) {
						if (!$cfg['isAdmin']) {
							AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to upload nzb-file ".$filename);
							array_push($uploadMessages, "nzbperl is disabled for users : ".$filename);
							continue;
						}
					}
				}
				if ($_FILES['upload_files']['size'][$id] <= _UPLOAD_LIMIT && $_FILES['upload_files']['size'][$id] > 0) {
					//FILE IS BEING UPLOADED
					if (@is_file($cfg["transfer_file_path"].$filename)) {
						// Error
						array_push($uploadMessages, "the file ".$filename." already exists on the server.");
						continue;
					} else {
						if (@move_uploaded_file($_FILES['upload_files']['tmp_name'][$id], $cfg["transfer_file_path"].$filename)) {
							@chmod($cfg["transfer_file_path"].$filename, 0644);
							AuditAction($cfg["constants"]["file_upload"], $filename);
							// inject
							injectTransfer($filename);
							// instant action ?
							if ($actionId > 1)
								array_push($tStack,$filename);
						} else {
							array_push($uploadMessages, "File not uploaded, file could not be found or could not be moved: ".$cfg["transfer_file_path"].$filename);
							continue;
					  	}
					}
				} else {
					array_push($uploadMessages, "File not uploaded, file size limit is "._UPLOAD_LIMIT.". file has ".$size);
					continue;
				}
			}
		} // End File Upload
		// instant action ?
		if (($actionId > 1) && (!empty($tStack))) {
			foreach ($tStack as $transfer) {
				$ch = ClientHandler::getInstance(getTransferClient($transfer));
				switch ($actionId) {
					case 3:
						$ch->start($transfer, false, true);
						break;
					case 2:
						$ch->start($transfer, false, false);
						break;
				}
				if (count($ch->messages) > 0)
           			$uploadMessages = array_merge($uploadMessages, $ch->messages);
			}
		}
	}
	if (count($uploadMessages) > 0) {
		@error("There were Problems", (isset($_SERVER["HTTP_REFERER"])) ? $_SERVER["HTTP_REFERER"] : "index.php?iid=index", "", $uploadMessages);
	} else {
		@header("location: index.php?iid=index");
		exit();
	}
}

/**
 * send meta-file
 *
 * @param $mfile
 */
function sendMetafile($mfile) {
	global $cfg;
	// is enabled ?
	if ($cfg["enable_metafile_download"] != 1) {
		AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to download a metafile");
		@error("metafile download is disabled", (isset($_SERVER["HTTP_REFERER"])) ? $_SERVER["HTTP_REFERER"] : "index.php?iid=index", "");
	}
	if (isValidTransfer($mfile) === true) {
		// Does the file exist?
		if (file_exists($cfg["transfer_file_path"].$mfile)) {
			// filenames in IE containing dots will screw up the filename
			$headerName = (strstr($_SERVER['HTTP_USER_AGENT'], "MSIE"))
				? preg_replace('/\./', '%2e', $mfile, substr_count($mfile, '.') - 1)
				: $mfile;
			// Prompt the user to download file.
			if (substr($mfile, -8) == ".torrent")
				@header("Content-type: application/x-bittorrent\n");
			else
				@header( "Content-type: application/octet-stream\n" );
			@header("Content-disposition: attachment; filename=\"".$headerName."\"\n");
			@header("Content-transfer-encoding: binary\n");
			@header("Content-length: ".@filesize($cfg["transfer_file_path"].$mfile)."\n");
			// write the session to close so you can continue to browse on the site.
			@session_write_close();
			// Send the file
			$fp = @fopen($cfg["transfer_file_path"].$mfile, "r");
			@fpassthru($fp);
			@fclose($fp);
			AuditAction($cfg["constants"]["fm_download"], $mfile);
		} else {
			AuditAction($cfg["constants"]["error"], "File Not found for download: ".$mfile);
			@error("File Not found for download", "index.php?iid=index", "", array($mfile));
		}
	} else {
		AuditAction($cfg["constants"]["error"], "ILLEGAL DOWNLOAD: ".$mfile);
		@error("Invalid File", "index.php?iid=index", "", array($mfile));
	}
	exit();
}

/**
 * tf 2.x compat function
 */
function compatIndexDispatch() {
	// transfer-start
	if (isset($_REQUEST['torrent']))
		indexStartTransfer(urldecode(getRequestVar('torrent')));
	// get torrent via url
	if (isset($_REQUEST['url_upload']))
		indexProcessDownload(getRequestVar('url_upload'), 'torrent');
	// file upload
	if ((isset($_FILES['upload_file'])) && (!empty($_FILES['upload_file']['name'])))
		indexProcessUpload();
	// del file
	if (isset($_REQUEST['delfile']))
		indexDeleteTransfer(urldecode(getRequestVar('delfile')));
	// kill
	if (isset($_REQUEST["kill_torrent"]))
		indexStopTransfer(urldecode(getRequestVar('kill_torrent')));
	// deQueue
	if (isset($_REQUEST["QEntry"]))
		indexDeQueueTransfer(urldecode(getRequestVar('QEntry')));
}

?>