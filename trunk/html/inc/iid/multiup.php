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

// common functions
require_once('inc/functions/functions.common.php');

// create template-instance
$tmpl = getTemplateInstance($cfg["theme"], "multiup.tmpl");

if (!empty($_FILES['upload_files'])) {
	//echo '<pre>'; var_dump($_FILES); echo '</pre>';
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
		$file_name = str_replace(array("'",","), "", $file_name);
		$file_name = cleanFileName($file_name);
		$ext_msg = "";
		$messages = "";
		if($_FILES['upload_files']['size'][$id] <= 1000000 &&
				$_FILES['upload_files']['size'][$id] > 0) {
			if (ereg(getFileFilter($cfg["file_types_array"]), $file_name)) {
			//FILE IS BEING UPLOADED
			if (is_file($cfg["transfer_file_path"].$file_name)) {
				// Error
				$messages .= "<b>Error</b> with (<b>".$file_name."</b>), the file already exists on the server.<br><center><a href=\"".$_SERVER['PHP_SELF']."\">[Refresh]</a></center>";
				$ext_msg = "DUPLICATE :: ";
			} else {
				if(move_uploaded_file($_FILES['upload_files']['tmp_name'][$id], $cfg["transfer_file_path"].$file_name)) {
				chmod($cfg["transfer_file_path"].$file_name, 0644);
				AuditAction($cfg["constants"]["file_upload"], $file_name);
				// instant action ?
				if ((isset($actionId)) && ($actionId > 1))
					array_push($tStack,$file_name);
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
		if ((isset($messages)) && ($messages != "")) {
		  // there was an error
			AuditAction($cfg["constants"]["error"], $cfg["constants"]["file_upload"]." :: ".$ext_msg.$file_name);
		}
	} // End File Upload

	// instant action ?
	if (isset($actionId)) {
		require_once("inc/classes/ClientHandler.php");
		foreach ($tStack as $torrent) {
			// init stat-file
			injectTorrent($torrent);
			// file prio
			if ($cfg["enable_file_priority"]) {
				include_once("inc/setpriority.php");
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
			// just a sec..
			sleep(1);
		}
	}
	// back to index if no errors
	if ((isset($messages)) && ($messages == "")) {
		header("location: index.php?iid=index");
		exit();
	}
}

$tmpl->setvar('head', getHead($cfg['_MULTIPLE_UPLOAD']));
if ((isset($messages)) && ($messages != "")) {
	$tmpl->setvar('messages', $messages);
}
$tmpl->setvar('table_border_dk', $cfg["table_border_dk"]);
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
$tmpl->setvar('_SELECTFILE', $cfg['_SELECTFILE']);
$row_list = array();
for($j = 0; $j < $cfg["hack_multiupload_rows"]; ++$j) {
	array_push($row_list, array());
}
$tmpl->setloop('row_list', $row_list);
$tmpl->setvar('_UPLOAD', $cfg['_UPLOAD']);
$tmpl->setvar('queueActive', $queueActive);
$tmpl->setvar('IsAdmin', IsAdmin());
$tmpl->setvar('foot', getFoot());
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('iid', $_GET["iid"]);
$tmpl->pparse();

?>