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


require_once("config.php");
require_once("functions.php");


# create new template
if (!ereg('^[^./][^/]*$', $cfg["theme"])) {
	$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/multiup.tmpl");
}
else {
	$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/multiup.tmpl");
}

if (!empty($_FILES['upload_files'])) {
	//echo '<pre>'; var_dump($_FILES); echo '</pre>';
	// instant action ?
	$actionId = getRequestVar('aid');
	$tStack = array();
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
			if (is_file($cfg["torrent_file_path"].$file_name)) {
				// Error
				$messages .= "<b>Error</b> with (<b>".$file_name."</b>), the file already exists on the server.<br><center><a href=\"".$_SERVER['PHP_SELF']."\">[Refresh]</a></center>";
				$ext_msg = "DUPLICATE :: ";
			} else {
				if(move_uploaded_file($_FILES['upload_files']['tmp_name'][$id], $cfg["torrent_file_path"].$file_name)) {
				chmod($cfg["torrent_file_path"].$file_name, 0644);
				AuditAction($cfg["constants"]["file_upload"], $file_name);
				// instant action ?
				if ((isset($actionId)) && ($actionId > 1))
					array_push($tStack,$file_name);
				} else {
				$messages .= "<font color=\"#ff0000\" size=3>ERROR: File not uploaded, file could not be found or could not be moved:<br>".$cfg["torrent_file_path"] . $file_name."</font><br>";
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
		switch ($actionId) {
			case 3:
				$_REQUEST['queue'] = 'on';
			case 2:
				include_once("ClientHandler.php");
				foreach ($tStack as $torrent) {
					// init stat-file
					injectTorrent($torrent);
					//
					if ($cfg["enable_file_priority"]) {
					include_once("setpriority.php");
						// Process setPriority Request.
						setPriority(urldecode($torrent));
					}
					$clientHandler = ClientHandler::getClientHandlerInstance($cfg);
					$clientHandler->startClient($torrent, 0);
					// just a sec..
					sleep(1);
				}
				break;
		}
	}
	// back to index if no errors
	if ((isset($messages)) && ($messages == "")) {
		header("location: index.php?page=index");
		exit();
	}
}
$tmpl->setvar('head', getHead(_MULTIPLE_UPLOAD));
if ((isset($messages)) && ($messages != "")) {
	$tmpl->setvar('messages', $messages);
}
$tmpl->setvar('table_border_dk', $cfg["table_border_dk"]);
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
$tmpl->setvar('_SELECTFILE', _SELECTFILE);
$row_list = array();
for($j = 0; $j < $cfg["hack_multiupload_rows"]; ++$j) {
	array_push($row_list, array());
}
$tmpl->setloop('row_list', $row_list);
$tmpl->setvar('_UPLOAD', _UPLOAD);
$tmpl->setvar('AllowQueing', $cfg["AllowQueing"]);
$tmpl->setvar('IsAdmin', IsAdmin());
$tmpl->setvar('foot', getFoot());

$tmpl->pparse();
?>