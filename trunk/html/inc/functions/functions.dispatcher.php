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
	if (isValidTransfer($transfer) === true) {
		if ((substr(strtolower($transfer), -8) == ".torrent")) {
			// this is a torrent-client
			$interactiveStart = getRequestVar('interactive');
			if ((isset($interactiveStart)) && ($interactiveStart)) // interactive
				indexStartTorrent($transfer, 1);
			else // silent
				indexStartTorrent($transfer, 0);
		} else if ((substr(strtolower($transfer), -5) == ".wget")) {
			// this is wget.
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
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg, 'wget');
			$clientHandler->startClient($transfer, 0, false);
			sleep(3);
			header("location: index.php?iid=index");
			exit();
		}
	} else {
		AuditAction($cfg["constants"]["error"], "Invalid Transfer for Start : ".$cfg["user"]." tried to start ".$transfer);
		showErrorPage("Invalid Transfer for Start : <br>".htmlentities($transfer, ENT_QUOTES));
	}
}

/**
 * Function with which torrents are started in index-page
 *
 * @param $torrent
 * @param $interactive (1|0) : is this a interactive startup with dialog ?
 */
function indexStartTorrent($torrent, $interactive) {
	global $cfg;
	if ($cfg["enable_file_priority"]) {
		include_once("inc/functions/functions.setpriority.php");
		// Process setPriority Request.
		setPriority($torrent);
	}
	$spo = getRequestVar('setPriorityOnly');
	if (!empty($spo)){
		// This is a setPriorityOnly Request.
		return 1;
	}
	switch ($interactive) {
		case 0:
			$btclient = getTransferClient($torrent);
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg,$btclient);
			$clientHandler->startClient($torrent, 0, FluxdQmgr::isRunning());
			// header + out
			header("location: index.php?iid=index");
			exit();
			break;
		case 1:
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg, getRequestVar('btclient'));
			$clientHandler->startClient($torrent, 1, FluxdQmgr::isRunning());
			if ($clientHandler->state == -1) { // start failed
				header("location: index.php?iid=index&messages=".urlencode($clientHandler->messages));
				exit();
			} else {
				if (array_key_exists("closeme",$_POST)) {
					echo '<script  language="JavaScript">';
					echo ' window.opener.location.reload(true);';
					echo ' window.close();';
					echo '</script>';
				} else {
					header("location: index.php?iid=index");
				}
			}
			exit();
			break;
	}
}

/**
 * indexStopTransfer
 *
 * @param $transfer
 */
function indexStopTransfer($transfer) {
	global $cfg;
	if (isValidTransfer($transfer) === true) {
		$return = getRequestVar('return');
		if ((substr(strtolower($transfer), -8) == ".torrent")) {
			// this is a torrent-client
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg, getTransferClient($transfer));
		} else if ((substr(strtolower($transfer), -5) == ".wget")) {
			// this is wget.
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg, 'wget');
		} else {
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg, 'tornado');
		}
		$clientHandler->stopClient($transfer, getRequestVar('alias_file'), "", $return);
		if (!empty($return))
			header("location: index.php?iid=".$return.".php?op=fluxdSettings");
		else
			header("location: index.php?iid=index");
		exit();
	} else {
		AuditAction($cfg["constants"]["error"], "Invalid Transfer for Stop : ".$cfg["user"]." tried to stop ".$transfer);
		showErrorPage("Invalid Transfer for Stop : <br>".htmlentities($transfer, ENT_QUOTES));
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
		deleteTransfer($transfer, getRequestVar('alias_file'));
		header("location: index.php?iid=index");
		exit();
	} else {
		AuditAction($cfg["constants"]["error"], "Invalid Transfer for Delete : ".$cfg["user"]." tried to delete ".$transfer);
		showErrorPage("Invalid Transfer for Delete : <br>".htmlentities($transfer, ENT_QUOTES));
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
		header("location: index.php?iid=index");
		exit();
	} else {
		AuditAction($cfg["constants"]["error"], "Invalid Transfer for DeQueue : ".$cfg["user"]." tried to deQueue ".$transfer);
		showErrorPage("Invalid Transfer for DeQueue : <br>".htmlentities($transfer, ENT_QUOTES));
	}
}

/**
 * Function with which torrents are downloaded and injected on index-page
 *
 * @param $url_upload url of torrent to download
 */
function indexProcessDownload($url_upload) {
	global $cfg, $messages;
	// is enabled ?
	if ($cfg["enable_torrent_download"] != 1) {
		AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to use torrent download");
		showErrorPage("torrent download is disabled.");
	}
	if (!empty($url_upload)) {
		$messages = "";
		$arURL = explode("/", $url_upload);
		$file_name = urldecode($arURL[count($arURL)-1]); // get the file name
		$file_name = str_replace(array("'",","), "", $file_name);
		$file_name = stripslashes($file_name);
		$ext_msg = "";
		// Check to see if url has something like ?passkey=12345
		// If so remove it.
		if (($point = strrpos($file_name, "?")) !== false )
			$file_name = substr( $file_name, 0, $point );
		$ret = strrpos($file_name,".");
		if ($ret === false) {
			$file_name .= ".torrent";
		} else {
			if(!strcmp(strtolower(substr($file_name, strlen($file_name)-8, 8)), ".torrent") == 0)
				$file_name .= ".torrent";
		}
		$url_upload = str_replace(" ", "%20", $url_upload);
		// This is to support Sites that pass an id along with the url for torrent downloads.
		$tmpId = getRequestVar("id");
		if(!empty($tmpId))
			$url_upload .= "&id=".$tmpId;
		// retrieve the torrent file
		// require SimpleHTTP
		require_once("inc/classes/SimpleHTTP.php");
		$simpleHTTP = SimpleHTTP::getInstance($cfg);
		$content = $simpleHTTP->getTorrent($url_upload);

		if (($simpleHTTP->state == SIMPLEHTTP_STATE_OK) && (strlen($content) > 0)) {
			$file_name = ($simpleHTTP->filename != "")
				? cleanFileName($simpleHTTP->filename)
				: cleanFileName($file_name);
			// check if content contains html
			if ($cfg['debuglevel'] > 0) {
				if (strpos($content, "<br />") !== false)
					AuditAction($cfg["constants"]["debug"], "indexProcessDownload : content contained html : ".htmlentities(addslashes($url_upload), ENT_QUOTES));
			}
			if (is_file($cfg["transfer_file_path"].$file_name)) {
				// Error
				$messages .= "ERROR: the file ".$file_name." already exists on the server.";
				$ext_msg = "DUPLICATE :: ";
			} else {
				// write to file
				$handle = false;
				$handle = @fopen($cfg["transfer_file_path"].$file_name, "w");
				if (!$handle) {
					$messages .= "cannot open ".$file_name." for writing.";
					AuditAction($this->cfg["constants"]["error"], "File-Write-Error : ".$messages);
				} else {
					$result = @fwrite($handle, $content);
					@fclose($handle);
					if ($result === false) {
						$messages .= "cannot write content to ".$file_name.".";
						AuditAction($this->cfg["constants"]["error"], "File-Write-Error : ".$messages);
					}
				}
			}
		} else {
			if(!empty($simpleHTTP->messages)){
				// Tag on any messages found in $simpleHTTP->messages:
				$messages .= "Error downloading URL: ".$simpleHTTP->formatMessages();
			} else {
				$messages .= "ERROR: could not get the file ".$file_name.", could be a dead URL.";
			}
		}
		if ($messages != "") { // there was an error
			AuditAction($cfg["constants"]["error"], $cfg["constants"]["url_upload"]." :: ".$ext_msg.$file_name);
			header("location: index.php?iid=index&messages=".urlencode($messages));
			exit();
		} else {
			AuditAction($cfg["constants"]["url_upload"], $file_name);
			// init stat-file
			injectTorrent($file_name);
			// instant action ?
			$actionId = getRequestVar('aid');
			if (isset($actionId)) {
				if ($cfg["enable_file_priority"]) {
					include_once("inc/functions/functions.setpriority.php");
					// Process setPriority Request.
					setPriority(urldecode($file_name));
				}
				$clientHandler = ClientHandler::getClientHandlerInstance($cfg);
				switch ($actionId) {
					case 3:
						$clientHandler->startClient($file_name, 0, true);
						break;
					case 2:
						$clientHandler->startClient($file_name, 0, false);
						break;
				}
			}
			header("location: index.php?iid=index");
			exit();
		}
	}
}

/**
 * Function with which torrents are uploaded and injected on index-page
 */
function indexProcessUpload() {
	global $cfg;
	$messages = "";
	$ext_msg = "";
	if (isset($_FILES['upload_file'])) {
		if (!empty($_FILES['upload_file']['name'])) {
			$file_name = stripslashes($_FILES['upload_file']['name']);
			$file_name = cleanFileName($file_name);
			if ($_FILES['upload_file']['size'] <= 1000000 && $_FILES['upload_file']['size'] > 0) {
				if (ereg(getFileFilter($cfg["file_types_array"]), $file_name)) {
					//FILE IS BEING UPLOADED
					if (is_file($cfg["transfer_file_path"].$file_name)) {
						// Error
						$messages .= "ERROR: the file ".$file_name." already exists on the server.";
						$ext_msg = "DUPLICATE :: ";
					} else {
						if (move_uploaded_file($_FILES['upload_file']['tmp_name'], $cfg["transfer_file_path"].$file_name)) {
							chmod($cfg["transfer_file_path"].$file_name, 0644);
							AuditAction($cfg["constants"]["file_upload"], $file_name);
							// init stat-file
							injectTorrent($file_name);
							// instant action ?
							$actionId = getRequestVar('aid');
							if (isset($actionId)) {
								if ($cfg["enable_file_priority"]) {
									include_once("inc/functions/functions.setpriority.php");
									// Process setPriority Request.
									setPriority(urldecode($file_name));
								}
								$clientHandler = ClientHandler::getClientHandlerInstance($cfg);
								switch ($actionId) {
									case 3:
										$clientHandler->startClient($file_name, 0, true);
										break;
									case 2:
										$clientHandler->startClient($file_name, 0, false);
										break;
								}
							}
						} else {
							$messages .= "ERROR: File not uploaded, file could not be found or could not be moved: ".$cfg["transfer_file_path"].$file_name;
						}
					}
				} else {
					$messages .= "ERROR: The type of file you are uploading is not allowed.";
				}
			} else {
				$messages .= "ERROR: File not uploaded, check file size limit.";
			}
		}
	}
	if ($messages != "") { // there was an error
		AuditAction($cfg["constants"]["error"], $cfg["constants"]["file_upload"]." :: ".$ext_msg.$file_name);
		header("location: index.php?iid=index&messages=".urlencode($messages));
		exit();
	} else {
		header("location: index.php?iid=index");
		exit();
	}
}

/**
 * file-upload
 */
function processFileUpload() {
	global $cfg;
	$messages = "";
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
			$ext_msg = "";
			$messages = "";
			if($_FILES['upload_files']['size'][$id] <= 1000000 && $_FILES['upload_files']['size'][$id] > 0) {
				if (ereg(getFileFilter($cfg["file_types_array"]), $file_name)) {
					//FILE IS BEING UPLOADED
					if (is_file($cfg["transfer_file_path"].$file_name)) {
						// Error
						$messages .= "ERROR: the file ".$file_name." already exists on the server.";
						$ext_msg = "DUPLICATE :: ";
					} else {
						if (move_uploaded_file($_FILES['upload_files']['tmp_name'][$id], $cfg["transfer_file_path"].$file_name)) {
							chmod($cfg["transfer_file_path"].$file_name, 0644);
							AuditAction($cfg["constants"]["file_upload"], $file_name);
							// instant action ?
							if ((isset($actionId)) && ($actionId > 1))
								array_push($tStack,$file_name);
						} else {
							$messages .= "ERROR: File not uploaded, file could not be found or could not be moved: ".$cfg["transfer_file_path"].$file_name;
					  	}
					}
				} else {
					$messages .= "ERROR: The type of file you are uploading is not allowed.";
				}
			} else {
				$messages .= "ERROR: File not uploaded, check file size limit.";
			}
			if ((isset($messages)) && ($messages != "")) {
			  // there was an error
				AuditAction($cfg["constants"]["error"], $cfg["constants"]["file_upload"]." :: ".$ext_msg.$file_name);
			}
		} // End File Upload
		// instant action ?
		if (!empty($actionId)) {
			foreach ($tStack as $torrent) {
				// init stat-file
				injectTorrent($torrent);
				// file prio
				if ($cfg["enable_file_priority"]) {
					include_once("inc/functions/functions.setpriority.php");
					// Process setPriority Request.
					setPriority(urldecode($torrent));
				}
				$clientHandler = ClientHandler::getClientHandlerInstance($cfg);
				switch ($actionId) {
					case 3:
						$clientHandler->startClient($torrent, 0, true);
						break;
					case 2:
						$clientHandler->startClient($torrent, 0, false);
						break;
				}
			}
		}
		if ((isset($messages)) && ($messages == "")) {
			// back to index if no errors
			header("location: index.php?iid=index");
			exit();
		} else {
			// push errors to referrer
			if (isset($_SERVER["HTTP_REFERER"]))
				header("location: ".$_SERVER["HTTP_REFERER"]."&messages=".urlencode($messages));
			else
				header("location: index.php?iid=index&messages=".urlencode($messages));
			exit();
		}
	}
}

/**
 * tf 2.1 compat function
 */
function compatIndexDispatch() {
	// transfer-start
	if (isset($_REQUEST['torrent']))
		indexStartTransfer(getRequestVar('torrent'));
	// get torrent via url
	if (isset($_REQUEST['url_upload']))
		indexProcessDownload(getRequestVar('url_upload'));
	// file upload
	if (isset($_FILES['upload_file'])) {
		if (!empty($_FILES['upload_file']['name']))
			indexProcessUpload();
	}
	// del file
	if (isset($_REQUEST['delfile']))
		indexDeleteTransfer(getRequestVar('delfile'));
	// kill
	if (isset($_REQUEST["kill_torrent"]))
		indexStopTransfer(getRequestVar('kill_torrent'));
	// deQueue
	if (isset($_REQUEST["QEntry"]))
		indexDeQueueTransfer(getRequestVar('QEntry'));
}

?>