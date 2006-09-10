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

// ***************************************************************************
// Returns true if user has message from admin with force_read
function IsForceReadMsg() {
	global $cfg, $db;
	$rtnValue = false;
	$sql = "SELECT count(*) FROM tf_messages WHERE to_user=".$db->qstr($cfg["user"])." AND force_read=1";
	$count = $db->GetOne($sql);
	showError($db,$sql);
	if ($count >= 1)
		$rtnValue = true;
	return $rtnValue;
}

/*
 * Function with which torrents are started in index-page
 *
 * @param $torrent torrent-name
 * @param $interactive (1|0) : is this a interactive startup with dialog ?
 */
function indexStartTorrent($torrent,$interactive) {
	global $cfg, $queueActive;
	if ($cfg["enable_file_priority"]) {
		include_once("inc/setpriority.php");
		// Process setPriority Request.
		setPriority($torrent);
	}
	switch ($interactive) {
		case 0:
			require_once("inc/classes/ClientHandler.php");
			$btclient = getTransferClient($torrent);
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg,$btclient);
			$clientHandler->startClient($torrent, 0, $queueActive);
			// just 2 sec..
			sleep(2);
			// header + out
			header("location: index.php?iid=index");
			exit();
			break;
		case 1:
			$spo = getRequestVar('setPriorityOnly');
			if (!empty($spo)){
				// This is a setPriorityOnly Request.
			} else {
				require_once("inc/classes/ClientHandler.php");
				$clientHandler = ClientHandler::getClientHandlerInstance($cfg, getRequestVar('btclient'));
				$clientHandler->startClient($torrent, 1, $queueActive);
				if ($clientHandler->status == 3) { // hooray
					// wait another sec
					sleep(1);
					if (array_key_exists("closeme",$_POST)) {
						echo '<script  language="JavaScript">';
						echo ' window.opener.location.reload(true);';
						echo ' window.close();';
						echo '</script>';
					} else {
						header("location: index.php?iid=index");
					}
				} else { // start failed
					echo $clientHandler->messages;
				}
				exit();
			}
			break;
	}
}

/*
 * Function with which torrents are downloaded and injected on index-page
 *
 * @param $url_upload url of torrent to download
 */
function indexProcessDownload($url_upload) {
	global $cfg, $messages, $queueActive;
	$arURL = explode("/", $url_upload);
	$file_name = urldecode($arURL[count($arURL)-1]); // get the file name
	$file_name = str_replace(array("'",","), "", $file_name);
	$file_name = stripslashes($file_name);
	$ext_msg = "";
	// Check to see if url has something like ?passkey=12345
	// If so remove it.
	if( ( $point = strrpos( $file_name, "?" ) ) !== false )
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
	// Call fetchtorrent to retrieve the torrent file
	$output = FetchTorrent( $url_upload );
	if (array_key_exists("save_torrent_name",$cfg)) {
		if ($cfg["save_torrent_name"] != "")
			$file_name = $cfg["save_torrent_name"];
	}
	$file_name = cleanFileName($file_name);
	// if the output had data then write it to a file
	if ((strlen($output) > 0) && (strpos($output, "<br />") === false)) {
		if (is_file($cfg["transfer_file_path"].$file_name)) {
			// Error
			$messages .= "<b>Error</b> with (<b>".$file_name."</b>), the file already exists on the server.<br><center><a href=\"".$_SERVER['PHP_SELF']."\">[Refresh]</a></center>";
			$ext_msg = "DUPLICATE :: ";
		} else {
			// open a file to write to
			$fw = fopen($cfg["transfer_file_path"].$file_name,'w');
			fwrite($fw, $output);
			fclose($fw);
		}
	} else {
		$messages .= "<b>Error</b> Getting the File (<b>".$file_name."</b>), Could be a Dead URL.<br><center><a href=\"".$_SERVER['PHP_SELF']."\">[Refresh]</a></center>";
	}
	if($messages != "") { // there was an error
		AuditAction($cfg["constants"]["error"], $cfg["constants"]["url_upload"]." :: ".$ext_msg.$file_name);
	} else {
		AuditAction($cfg["constants"]["url_upload"], $file_name);
		// init stat-file
		injectTorrent($file_name);
		// instant action ?
		$actionId = getRequestVar('aid');
		if (isset($actionId)) {
			if ($cfg["enable_file_priority"]) {
				include_once("inc/setpriority.php");
				// Process setPriority Request.
				setPriority(urldecode($file_name));
			}
			require_once("inc/classes/ClientHandler.php");
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg);
			switch ($actionId) {
				case 3:
					$clientHandler->startClient($file_name, 0, true);
					break;
				case 2:
					$clientHandler->startClient($file_name, 0, false);
					break;
			}
			// just a sec..
			sleep(1);
		}
		header("location: index.php?iid=index");
		exit();
	}
}

/*
 * Function with which torrents are uploaded and injected on index-page
 *
 */
function indexProcessUpload() {
	global $cfg, $messages;
	$file_name = stripslashes($_FILES['upload_file']['name']);
	$file_name = str_replace(array("'",","), "", $file_name);
	$file_name = cleanFileName($file_name);
	$ext_msg = "";
	if($_FILES['upload_file']['size'] <= 1000000 && $_FILES['upload_file']['size'] > 0) {
		if (ereg(getFileFilter($cfg["file_types_array"]), $file_name)) {
			//FILE IS BEING UPLOADED
			if (is_file($cfg["transfer_file_path"].$file_name)) {
				// Error
				$messages .= "<b>Error</b> with (<b>".$file_name."</b>), the file already exists on the server.<br><center><a href=\"".$_SERVER['PHP_SELF']."\">[Refresh]</a></center>";
				$ext_msg = "DUPLICATE :: ";
			} else {
				if(move_uploaded_file($_FILES['upload_file']['tmp_name'], $cfg["transfer_file_path"].$file_name)) {
					chmod($cfg["transfer_file_path"].$file_name, 0644);
					AuditAction($cfg["constants"]["file_upload"], $file_name);
					// init stat-file
					injectTorrent($file_name);
					// instant action ?
					$actionId = getRequestVar('aid');
					if (isset($actionId)) {
						if ($cfg["enable_file_priority"]) {
							include_once("inc/setpriority.php");
							// Process setPriority Request.
							setPriority(urldecode($file_name));
						}
						require_once("inc/classes/ClientHandler.php");
						$clientHandler = ClientHandler::getClientHandlerInstance($cfg);
						switch ($actionId) {
							case 3:
								$clientHandler->startClient($file_name, 0, true);
								break;
							case 2:
								$clientHandler->startClient($file_name, 0, false);
								break;
						}
						// just a sec..
						sleep(1);
					}
				} else {
					$messages .= "<font color=\"#ff0000\" size=3>ERROR: File not uploaded, file could not be found or could not be moved:<br>".$cfg["transfer_file_path"] . $file_name."</font><br>";
				}
			}
		} else {
			$messages .= "<font color=\"#ff0000\" size=3>ERROR: The type of file you are uploading is not allowed.</font><br>";
		}
	} else {
		$messages .= "<font color=\"#ff0000\" size=3>ERROR: File not uploaded, check file size limit.</font><br>";
	}
	if($messages != "") { // there was an error
		AuditAction($cfg["constants"]["error"], $cfg["constants"]["file_upload"]." :: ".$ext_msg.$file_name);
	} else {
		header("location: index.php?iid=index");
		exit();
	}
}

?>