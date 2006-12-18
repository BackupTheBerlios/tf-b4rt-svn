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
				AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to use wget");
				@error("wget is disabled", "index.php?iid=index", "");
			} elseif ($cfg["enable_wget"] == 1) {
				if (!$cfg['isAdmin']) {
					AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to use wget");
					@error("wget is disabled for users", "index.php?iid=index", "");
				}
			}
			$clientHandler = ClientHandler::getInstance('wget');
			$clientHandler->start($transfer, false, false);
			if ($clientHandler->state == CLIENTHANDLER_STATE_ERROR) { // start failed
				$msgs = array();
				array_push($msgs, "transfer : ".$transfer);
				array_push($msgs, "\nmessages :");
				$msgs = array_merge($msgs, $clientHandler->messages);
				AuditAction($cfg["constants"]["error"], "Start failed: ".$transfer."\n".implode("\n", $clientHandler->messages));
				@error("Start failed", "", "", $msgs);
			} else {
				sleep(3);
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
	if ($cfg["enable_file_priority"]) {
		include_once("inc/functions/functions.setpriority.php");
		// Process setPriority Request.
		setPriority($transfer);
	}
	$spo = getRequestVar('setPriorityOnly');
	if (!empty($spo)){
		// This is a setPriorityOnly Request.
		return 1;
	}
	switch ($interactive) {
		case 0:
			$btclient = getTransferClient($transfer);
			$clientHandler = ClientHandler::getInstance($btclient);
			$clientHandler->start($transfer, false, FluxdQmgr::isRunning());
			// header + out
			@header("location: index.php?iid=index");
		case 1:
			$clientHandler = ClientHandler::getInstance(getRequestVar('btclient'));
			$clientHandler->start($transfer, true, FluxdQmgr::isRunning());
			if ($clientHandler->state == CLIENTHANDLER_STATE_ERROR) { // start failed
				$msgs = array();
				array_push($msgs, "transfer : ".$transfer);
				array_push($msgs, "\nmessages :");
				$msgs = array_merge($msgs, $clientHandler->messages);
				AuditAction($cfg["constants"]["error"], "Start failed: ".$transfer."\n".implode("\n", $clientHandler->messages));
				@error("Start failed", "", "", $msgs);
			} else {
				if (array_key_exists("closeme",$_POST)) {
					echo '<script  language="JavaScript">';
					echo ' window.opener.location.reload(true);';
					echo ' window.close();';
					echo '</script>';
				} else {
					@header("location: index.php?iid=index");
				}
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
			$clientHandler = ClientHandler::getInstance(getTransferClient($transfer));
		} else if (substr($transfer, -5) == ".wget") {
			$invalid = false;
			$clientHandler = ClientHandler::getInstance('wget');
		}
		$clientHandler->stop($transfer);
		if (count($clientHandler->messages) > 0)
    		@error("There were Problems", "index.php?iid=index", "", $clientHandler->messages);
	}
	if ($invalid) {
		AuditAction($cfg["constants"]["error"], "INVALID TRANSFER: ".$transfer);
		@error("Invalid Transfer", "index.php?iid=index", "", array($transfer));
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
		$clientHandler = ClientHandler::getInstance(getTransferClient($transfer));
		$clientHandler->delete($transfer);
		if (count($clientHandler->messages) > 0)
    		@error("There were Problems", "index.php?iid=index", "", $clientHandler->messages);
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
		FluxdQmgr::dequeueTransfer($transfer, $cfg['user']);
		@header("location: index.php?iid=index");
		exit();
	} else {
		AuditAction($cfg["constants"]["error"], "INVALID TRANSFER: ".$transfer);
		@error("Invalid Transfer", "index.php?iid=index", "", array($transfer));
	}
}

/**
 * Function with which torrents are downloaded and injected on index-page
 *
 * @param $url_upload url of torrent to download
 */
function indexProcessDownload($url_upload) {
	global $cfg;
	// is enabled ?
	if ($cfg["enable_torrent_download"] != 1) {
		AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to use torrent download");
		@error("torrent download is disabled", "index.php?iid=index", "");
	}
	$ext_msg = "";
	$file_name = "";
	$downloadMessages = array();
	if (!empty($url_upload)) {
		$arURL = explode("/", $url_upload);
		$file_name = urldecode($arURL[count($arURL)-1]); // get the file name
		$file_name = str_replace(array("'",","), "", $file_name);
		$file_name = stripslashes($file_name);
		// Check to see if url has something like ?passkey=12345
		// If so remove it.
		if (($point = strrpos($file_name, "?")) !== false )
			$file_name = substr( $file_name, 0, $point );
		$ret = strrpos($file_name,".");
		if ($ret === false) {
			$file_name .= ".torrent";
		} else {
			if (!strcmp(strtolower(substr($file_name, -8)), ".torrent") == 0)
				$file_name .= ".torrent";
		}
		$url_upload = str_replace(" ", "%20", $url_upload);
		// This is to support Sites that pass an id along with the url for torrent downloads.
		$tmpId = getRequestVar("id");
		if(!empty($tmpId))
			$url_upload .= "&id=".$tmpId;
		// retrieve the torrent file
		require_once("inc/classes/SimpleHTTP.php");
		$content = SimpleHTTP::getTorrent($url_upload);
		if ((SimpleHTTP::getState() == SIMPLEHTTP_STATE_OK) && (strlen($content) > 0)) {
			$filename = SimpleHTTP::getFilename();
			$file_name = ($filename != "")
				? cleanFileName($filename)
				: cleanFileName($file_name);
			// check if content contains html
			if ($cfg['debuglevel'] > 0) {
				if (strpos($content, "<br />") !== false)
					AuditAction($cfg["constants"]["debug"], "indexProcessDownload : content contained html : ".htmlentities(addslashes($url_upload), ENT_QUOTES));
			}
			if (is_file($cfg["transfer_file_path"].$file_name)) {
				// Error
				array_push($downloadMessages, "the file ".$file_name." already exists on the server.");
				$ext_msg = "DUPLICATE :: ";
			} else {
				// write to file
				$handle = false;
				$handle = @fopen($cfg["transfer_file_path"].$file_name, "w");
				if (!$handle) {
					array_push($downloadMessages, "cannot open ".$file_name." for writing.");
				} else {
					$result = @fwrite($handle, $content);
					@fclose($handle);
					if ($result === false)
						array_push($downloadMessages, "cannot write content to ".$file_name.".");
				}
			}
		} else {
			array_push($downloadMessages, "could not get the file ".$file_name.", could be a dead URL.");
			$msgs = SimpleHTTP::getMessages();
			if (count($msgs) > 0)
				$downloadMessages = array_merge($downloadMessages, $msgs);
		}
		if (empty($downloadMessages)) { // no errors
			AuditAction($cfg["constants"]["url_upload"], $file_name);
			// init stat-file
			injectAlias($file_name);
			// instant action ?
			$actionId = getRequestVar('aid');
			if (isset($actionId)) {
				if ($cfg["enable_file_priority"]) {
					include_once("inc/functions/functions.setpriority.php");
					// Process setPriority Request.
					setPriority(urldecode($file_name));
				}
				$clientHandler = ClientHandler::getInstance();
				switch ($actionId) {
					case 3:
						$clientHandler->start($file_name, false, true);
						break;
					case 2:
						$clientHandler->start($file_name, false, false);
						break;
				}
				if (count($clientHandler->messages) > 0)
               		$downloadMessages = array_merge($downloadMessages, $clientHandler->messages);
			}
		}
	} else {
		array_push($downloadMessages, "Invalid Url : ".$url_upload);
	}
	if (count($downloadMessages) > 0) {
		AuditAction($cfg["constants"]["error"], $cfg["constants"]["url_upload"]." :: ".$ext_msg.$file_name);
		@error("There were Problems", "index.php?iid=index", "", $downloadMessages);
	} else {
		@header("location: index.php?iid=index");
		exit();
	}
}

/**
 * Function with which torrents are uploaded and injected on index-page
 */
function indexProcessUpload() {
	global $cfg;
	$uploadLimit = 5000000;
	$ext_msg = "";
	$file_name = "";
	$uploadMessages = array();
	if ((isset($_FILES['upload_file'])) && (!empty($_FILES['upload_file']['name']))) {
		$file_name = stripslashes($_FILES['upload_file']['name']);
		$file_name = cleanFileName($file_name);
		if ($_FILES['upload_file']['size'] <= $uploadLimit && $_FILES['upload_file']['size'] > 0) {
			if (isValidTransfer($file_name)) {
				//FILE IS BEING UPLOADED
				if (is_file($cfg["transfer_file_path"].$file_name)) {
					// Error
					array_push($uploadMessages, "the file ".$file_name." already exists on the server.");
					$ext_msg = "DUPLICATE :: ";
				} else {
					if (move_uploaded_file($_FILES['upload_file']['tmp_name'], $cfg["transfer_file_path"].$file_name)) {
						chmod($cfg["transfer_file_path"].$file_name, 0644);
						AuditAction($cfg["constants"]["file_upload"], $file_name);
						// init stat-file
						injectAlias($file_name);
						// instant action ?
						$actionId = getRequestVar('aid');
						if (isset($actionId)) {
							if ($cfg["enable_file_priority"]) {
								include_once("inc/functions/functions.setpriority.php");
								// Process setPriority Request.
								setPriority(urldecode($file_name));
							}
							$clientHandler = ClientHandler::getInstance();
							switch ($actionId) {
								case 3:
									$clientHandler->start($file_name, false, true);
									break;
								case 2:
									$clientHandler->start($file_name, false, false);
									break;
							}
							if (count($clientHandler->messages) > 0)
               					$uploadMessages = array_merge($uploadMessages, $clientHandler->messages);
						}
					} else {
						array_push($uploadMessages, "File not uploaded, file could not be found or could not be moved: ".$cfg["transfer_file_path"].$file_name);
					}
				}
			} else {
				array_push($uploadMessages, "The type of file you are uploading is not allowed.");
				array_push($uploadMessages, "\nvalid file-extensions :");
				$uploadMessages = array_merge($uploadMessages, $cfg['file_types_array']);
			}
		} else {
			array_push($uploadMessages, "File not uploaded, file size limit is ".$uploadLimit.". file has ".$_FILES['upload_file']['size']);
		}
	}
	if (count($uploadMessages) > 0) {
		AuditAction($cfg["constants"]["error"], $cfg["constants"]["file_upload"]." :: ".$ext_msg.$file_name);
		@error("There were Problems", "index.php?iid=index", "", $uploadMessages);
	} else {
		@header("location: index.php?iid=index");
		exit();
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
			$clientHandler = ClientHandler::getInstance(getTransferClient($transfer));
		} else if (substr($transfer, -5) == ".wget") {
			$invalid = false;
			$clientHandler = ClientHandler::getInstance('wget');
		}
		$clientHandler->stop($transfer, true, $pid);
		if (count($clientHandler->messages) > 0)
    		@error("There were Problems", "index.php?iid=index", "", $clientHandler->messages);
	}
	if ($invalid) {
		AuditAction($cfg["constants"]["error"], "INVALID TRANSFER: ".$transfer);
		@error("Invalid Transfer", (isset($_SERVER["HTTP_REFERER"])) ? $_SERVER["HTTP_REFERER"] : "", "", array($transfer));
	}
}

/**
 * file-upload
 */
function processFileUpload() {
	global $cfg;
	$uploadLimit = 5000000;
	$ext_msg = "";
	$file_name = "";
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
				//no or empty file, skip it
				continue;
			}
			$file_name = stripslashes($_FILES['upload_files']['name'][$id]);
			$file_name = cleanFileName($file_name);
			if ($_FILES['upload_files']['size'][$id] <= $uploadLimit && $_FILES['upload_files']['size'][$id] > 0) {
				if (isValidTransfer($file_name)) {
					//FILE IS BEING UPLOADED
					if (is_file($cfg["transfer_file_path"].$file_name)) {
						// Error
						array_push($uploadMessages, "the file ".$file_name." already exists on the server.");
						$ext_msg = "DUPLICATE :: ";
					} else {
						if (move_uploaded_file($_FILES['upload_files']['tmp_name'][$id], $cfg["transfer_file_path"].$file_name)) {
							chmod($cfg["transfer_file_path"].$file_name, 0644);
							AuditAction($cfg["constants"]["file_upload"], $file_name);
							// instant action ?
							if ((isset($actionId)) && ($actionId > 1))
								array_push($tStack,$file_name);
						} else {
							array_push($uploadMessages, "File not uploaded, file could not be found or could not be moved: ".$cfg["transfer_file_path"].$file_name);
					  	}
					}
				} else {
					array_push($uploadMessages, "The type of file you are uploading is not allowed.");
					array_push($uploadMessages, "\nvalid file-extensions :");
					$uploadMessages = array_merge($uploadMessages, $cfg['file_types_array']);
				}
			} else {
				array_push($uploadMessages, "File not uploaded, file size limit is ".$uploadLimit.". file has ".$size);
			}
			if (count($uploadMessages) > 0) {
			  // there was an error
				AuditAction($cfg["constants"]["error"], $cfg["constants"]["file_upload"]." :: ".$ext_msg.$file_name);
			}
		} // End File Upload
		// instant action ?
		if (!empty($actionId)) {
			foreach ($tStack as $transfer) {
				// init stat-file
				injectAlias($transfer);
				// file prio
				if ($cfg["enable_file_priority"]) {
					include_once("inc/functions/functions.setpriority.php");
					// Process setPriority Request.
					setPriority(urldecode($transfer));
				}
				$clientHandler = ClientHandler::getInstance();
				switch ($actionId) {
					case 3:
						$clientHandler->start($transfer, false, true);
						break;
					case 2:
						$clientHandler->start($transfer, false, false);
						break;
				}
				if (count($clientHandler->messages) > 0)
               		$uploadMessages = array_merge($uploadMessages, $clientHandler->messages);
			}
		}
	}
	if (count($uploadMessages) > 0) {
		@error("There were Problems", (isset($_SERVER["HTTP_REFERER"])) ? $_SERVER["HTTP_REFERER"] : "index.php?iid=index", "", $uploadMessages);
	} else {
		if (isset($_SERVER["HTTP_REFERER"]))
			@header("location: ".$_SERVER["HTTP_REFERER"]);
		else
			@header("location: index.php?iid=index");
		exit();
	}
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
		indexProcessDownload(getRequestVar('url_upload'));
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