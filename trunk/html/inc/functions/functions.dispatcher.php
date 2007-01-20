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
	$ext_msg = "";
	$file_name = "";
	$downloadMessages = array();
	if (!empty($url)) {
		$arURL = explode("/", $url);
		$file_name = urldecode($arURL[count($arURL)-1]); // get the file name
		$file_name = str_replace(array("'",","), "", $file_name);
		$file_name = stripslashes($file_name);
		// Check to see if url has something like ?passkey=12345
		// If so remove it.
		if (($point = strrpos($file_name, "?")) !== false )
			$file_name = substr( $file_name, 0, $point );
		$ret = strrpos($file_name, ".");
		if ($ret === false) {
			$file_name .= $ext;
		} else {
			if (!strcmp(strtolower(substr($file_name, -(strlen($ext)))), $ext) == 0)
				$file_name .= $ext;
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
			$filename = SimpleHTTP::getFilename();
			$file_name = ($filename != "")
				? cleanFileName($filename)
				: cleanFileName($file_name);
			// check if content contains html
			if ($cfg['debuglevel'] > 0) {
				if (strpos($content, "<br />") !== false)
					AuditAction($cfg["constants"]["debug"], "download-content contained html : ".htmlentities(addslashes($url), ENT_QUOTES));
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
			$msgs = SimpleHTTP::getMessages();
			if (count($msgs) > 0)
				$downloadMessages = array_merge($downloadMessages, $msgs);
		}
		if (empty($downloadMessages)) { // no messages
			AuditAction($cfg["constants"]["url_upload"], $file_name);
			// inject
			injectTransfer($file_name);
			// instant action ?
			$actionId = getRequestVar('aid');
			if ($actionId > 1) {
				$ch = ClientHandler::getInstance(getTransferClient($file_name));
				switch ($actionId) {
					case 3:
						$ch->start($file_name, false, true);
						break;
					case 2:
						$ch->start($file_name, false, false);
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
		AuditAction($cfg["constants"]["error"], $cfg["constants"]["url_upload"]." :: ".$ext_msg.$file_name);
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
	$ext_msg = "";
	$file_name = "";
	$uploadMessages = array();
	if ((isset($_FILES['upload_file'])) && (!empty($_FILES['upload_file']['name']))) {
		$file_name = stripslashes($_FILES['upload_file']['name']);
		$file_name = cleanFileName($file_name);
		if (substr($file_name, -5) == ".wget") {
			// is enabled ?
			if ($cfg["enable_wget"] == 0) {
				AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to upload wget-file ".$file_name);
				@error("wget is disabled", "index.php?iid=index", "");
			} else if ($cfg["enable_wget"] == 1) {
				if (!$cfg['isAdmin']) {
					AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to upload wget-file ".$file_name);
					@error("wget is disabled for users", "", "");
				}
			}
		} else if (substr($file_name, -4) == ".nzb") {
			// is enabled ?
			if ($cfg["enable_nzbperl"] == 0) {
				AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to upload nzb-file ".$file_name);
				@error("nzbperl is disabled", "index.php?iid=index", "");
			} else if ($cfg["enable_nzbperl"] == 1) {
				if (!$cfg['isAdmin']) {
					AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to upload nzb-file ".$file_name);
					@error("nzbperl is disabled for users", "", "");
				}
			}
		}
		if ($_FILES['upload_file']['size'] <= _UPLOAD_LIMIT && $_FILES['upload_file']['size'] > 0) {
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
						// inject
						injectTransfer($file_name);
						// instant action ?
						$actionId = getRequestVar('aid');
						if ($actionId > 1) {
							$ch = ClientHandler::getInstance(getTransferClient($file_name));
							switch ($actionId) {
								case 3:
									$ch->start($file_name, false, true);
									break;
								case 2:
									$ch->start($file_name, false, false);
									break;
							}
							if (count($ch->messages) > 0)
               					$uploadMessages = array_merge($uploadMessages, $ch->messages);
						}
					} else {
						array_push($uploadMessages, "File not uploaded, file could not be found or could not be moved: ".$cfg["transfer_file_path"].$file_name);
					}
				}
			} else {
				array_push($uploadMessages, "The type of file you are uploading is not allowed.");
				array_push($uploadMessages, "\nvalid file-extensions: ");
				array_push($uploadMessages, $cfg["file_types_label"]);
			}
		} else {
			array_push($uploadMessages, "File not uploaded, file size limit is "._UPLOAD_LIMIT.". file has ".$_FILES['upload_file']['size']);
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
 * file-upload
 */
function processFileUpload() {
	global $cfg;
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
				// no or empty file, skip it
				continue;
			}
			$file_name = stripslashes($_FILES['upload_files']['name'][$id]);
			$file_name = cleanFileName($file_name);
			if (substr($file_name, -5) == ".wget") {
				// is enabled ?
				if ($cfg["enable_wget"] == 0) {
					AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to upload wget-file ".$file_name);
					array_push($uploadMessages, "wget is disabled  : ".$file_name);
					continue;
				} else if ($cfg["enable_wget"] == 1) {
					if (!$cfg['isAdmin']) {
						AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to upload wget-file ".$file_name);
						array_push($uploadMessages, "wget is disabled for users : ".$file_name);
						continue;
					}
				}
			} else if (substr($file_name, -4) == ".nzb") {
				// is enabled ?
				if ($cfg["enable_nzbperl"] == 0) {
					AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to upload nzb-file ".$file_name);
					array_push($uploadMessages, "nzbperl is disabled  : ".$file_name);
					continue;
				} else if ($cfg["enable_nzbperl"] == 1) {
					if (!$cfg['isAdmin']) {
						AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to upload nzb-file ".$file_name);
						array_push($uploadMessages, "nzbperl is disabled for users : ".$file_name);
						continue;
					}
				}
			}
			if ($_FILES['upload_files']['size'][$id] <= _UPLOAD_LIMIT && $_FILES['upload_files']['size'][$id] > 0) {
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
							// inject
							injectTransfer($file_name);
							// instant action ?
							if ($actionId > 1)
								array_push($tStack,$file_name);
						} else {
							array_push($uploadMessages, "File not uploaded, file could not be found or could not be moved: ".$cfg["transfer_file_path"].$file_name);
					  	}
					}
				} else {
					array_push($uploadMessages, "The type of file you are uploading is not allowed.");
					array_push($uploadMessages, "\nvalid file-extensions: ");
					array_push($uploadMessages, $cfg["file_types_label"]);
				}
			} else {
				array_push($uploadMessages, "File not uploaded, file size limit is "._UPLOAD_LIMIT.". file has ".$size);
			}
			if (count($uploadMessages) > 0) {
			  // there was an error
				AuditAction($cfg["constants"]["error"], $cfg["constants"]["file_upload"]." :: ".$ext_msg.$file_name);
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