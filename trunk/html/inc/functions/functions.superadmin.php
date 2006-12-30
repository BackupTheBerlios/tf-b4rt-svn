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
 * superadminAuthentication
 */
function superadminAuthentication($message = "") {
	if (! IsSuperAdmin()) {
		@header("Content-Type: text/plain");
		echo "\nAccess Error"."\n\n";
		if ((isset($message)) && ($message != ""))
			echo $message."\n";
		else
			echo "Only SuperAdmin can access superadmin-page.\n";
		exit();
	}
}

/**
 * builds page
 */
function buildPage($action) {
	global $cfg, $statusImage, $statusMessage, $htmlTitle, $htmlTop, $htmlMain;
	// navi
	$htmlTop .= '<a href="' . _FILE_THIS . '?t=0">Torrents</a>';
	$htmlTop .= ' | ';
	$htmlTop .= '<a href="' . _FILE_THIS . '?p=0">Processes</a>';
	$htmlTop .= ' | ';
	$htmlTop .= '<a href="' . _FILE_THIS . '?m=0">Maintenance</a>';
	$htmlTop .= ' | ';
	$htmlTop .= '<a href="' . _FILE_THIS . '?b=0">Backup</a>';
	$htmlTop .= ' | ';
	$htmlTop .= '<a href="' . _FILE_THIS . '?l=0">Log</a>';
	$htmlTop .= ' | ';
	$htmlTop .= '<a href="' . _FILE_THIS . '?y=0">Misc</a>';
	$htmlTop .= ' | ';
	$htmlTop .= '<a href="' . _FILE_THIS . '?z=0">tf-b4rt</a>';
	// body
	switch($action) {
		case "b": // backup passthru
		case "-b": // backup-error passthru
			if ($action == "b")
				$statusImage = "yellow.gif";
			else
				$statusImage = "red.gif";
			//
			$htmlMain .= '<table width="100%" bgcolor="'.$cfg["table_data_bg"].'" border="0" cellpadding="4" cellspacing="0"><tr><td width="100%">';
			$htmlMain .= '<a href="' . _FILE_THIS . '?b=0">Create Backup</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?b=3">Backups on Server</a>';
			$htmlMain .= '</td><td align="right" nowrap><strong>Backup</strong></td>';
			$htmlMain .= '</tr></table>';
			break;
		case "-u": // update-error passthru
			$statusImage = "red.gif";
			$htmlTitle = "Update";
			$htmlMain = '<br><font color="red"><strong>Update from your Version not possible.</strong></font>';
			$htmlMain .= '<br><br>';
			$htmlMain .= 'Please use the most recent tarball and perform a manual update.';
			$htmlMain .= '<br>';
			break;
		case "t": // torrent passthru
			$statusImage = "black.gif";
			break;
		case "p": // processes passthru
			$statusImage = "black.gif";
			$htmlMain .= '<table width="100%" bgcolor="'.$cfg["table_data_bg"].'" border="0" cellpadding="4" cellspacing="0"><tr><td width="100%">';
			$htmlMain .= '<a href="' . _FILE_THIS . '?p=1">All</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?p=2">Transfers</a>';
			$htmlMain .= '</td><td align="right"><strong>Processes</strong></td>';
			$htmlMain .= '</tr></table>';
			break;
		case "m": // maintenance passthru
			$statusImage = "black.gif";
			$htmlMain .= '<table width="100%" bgcolor="'.$cfg["table_data_bg"].'" border="0" cellpadding="4" cellspacing="0"><tr><td width="100%">';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=1">Main</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=2">Kill</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=3">Clean</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=4">Repair</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=5">Reset</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=6">Lock</a>';
			$htmlMain .= '</td><td align="right"><strong>Maintenance</strong></td>';
			$htmlMain .= '</tr></table>';
			break;
		case "l": // log passthru
			$statusImage = "black.gif";
			$htmlMain .= '<table width="100%" bgcolor="'.$cfg["table_data_bg"].'" border="0" cellpadding="4" cellspacing="0"><tr><td width="100%">';
			$htmlMain .= '<a href="' . _FILE_THIS . '?l=1">fluxd</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?l=2">fluxd-error</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?l=5">mainline</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?l=8">transfers</a>';
			$htmlMain .= '</td><td align="right"><strong>Log</strong></td>';
			$htmlMain .= '</tr></table>';
			break;
		case "y": // misc passthru
			$statusImage = "black.gif";
			$htmlMain .= '<table width="100%" bgcolor="'.$cfg["table_data_bg"].'" border="0" cellpadding="4" cellspacing="0"><tr><td width="100%">';
			$htmlMain .= '<a href="' . _FILE_THIS . '?y=1">Lists</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?y=5">Check</a>';
			$htmlMain .= '</td><td align="right" nowrap><strong>Misc</strong></td>';
			$htmlMain .= '</tr></table>';
			break;
		case "z": // tf-b4rt passthru
			$statusImage = "black.gif";
			$htmlMain .= '<table width="100%" bgcolor="'.$cfg["table_data_bg"].'" border="0" cellpadding="4" cellspacing="0"><tr><td width="100%">';
			$htmlMain .= '<a href="' . _FILE_THIS . '?z=1">Version</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?z=2">News</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?z=3">Changelog</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?z=9">Misc</a>';
			$htmlMain .= '</td><td align="right" nowrap><strong>tf-b4rt</strong></td>';
			$htmlMain .= '</tr></table>';
			break;
		case "f": // fluxd passthru
			$htmlTop = "";
			$statusImage = "";
			$htmlMain .= '<table width="100%" bgcolor="'.$cfg["table_data_bg"].'" border="0" cellpadding="4" cellspacing="0"><tr><td width="100%">';
			$htmlMain .= '<a href="' . _FILE_THIS . '?f=1">log</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?f=2">error-log</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?f=3">ps</a>';
			if (Fluxd::isRunning()) {
				$htmlMain .= ' | ';
				$htmlMain .= '<a href="' . _FILE_THIS . '?f=4">status</a>';
			} else {
				$htmlMain .= ' | ';
				$htmlMain .= '<a href="' . _FILE_THIS . '?f=5">check</a>';
				$htmlMain .= ' | ';
				$htmlMain .= '<a href="' . _FILE_THIS . '?f=6">db-debug</a>';
				$htmlMain .= ' | ';
				$htmlMain .= '<a href="' . _FILE_THIS . '?f=9">version</a>';
			}
			$htmlMain .= '</td><td align="right"><strong>fluxd</strong>';
			$htmlMain .= '</tr></table>';
			break;
		case "_": // default
		default:
			$htmlTitle = "SuperAdmin";
			$statusImage = "black.gif";
			$htmlMain = '<br>';
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?t=0"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Torrents" border="0"> Torrents</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?p=0"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Processes" border="0"> Processes</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=0"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Maintenance" border="0"> Maintenance</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?b=0"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Backup" border="0"> Backup</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?l=0"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Log" border="0"> Log</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?y=0"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Misc" border="0"> Misc</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?z=0"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="tf-b4rt" border="0"> tf-b4rt</a>';
			$htmlMain .= '<br><br>';
			break;
	}
}

/**
 * echo a string. use echo or sendLine
 *
 * @param $string : string to echo
 * @param $mode : 0 = echo | 1 = sendLine
 */
function doEcho($string, $mode = 0) {
	switch ($mode) {
		case 0:
			echo $string;
			return;
		case 1:
			sendLine($string);
			return;
	}
}

/**
 * prints the page
 */
function printPage() {
	printPageStart(0);
	global $htmlMain;
	echo $htmlMain;
	printPageEnd(0);
}

/**
 * prints the page-start
 */
function printPageStart($echoMode = 0) {
	global $cfg, $statusImage, $statusMessage, $htmlTitle, $htmlTop, $htmlMain;
	doEcho('<HTML>',$echoMode);
	doEcho('<HEAD>',$echoMode);
	doEcho('<TITLE>torrentflux-b4rt - SuperAdmin</TITLE>',$echoMode);
	doEcho('<link rel="icon" href="themes/'.$cfg["theme"].'/images/favicon.ico" type="image/x-icon" />',$echoMode);
	doEcho('<link rel="shortcut icon" href="themes/'.$cfg["theme"].'/images/favicon.ico" type="image/x-icon" />',$echoMode);
	// theme-switch
	if ((strpos($cfg["theme"], '/')) === false)
		doEcho('<LINK REL="StyleSheet" HREF="themes/'.$cfg["theme"].'/css/default.css" TYPE="text/css">',$echoMode);
	else
		doEcho('<LINK REL="StyleSheet" HREF="themes/'.$cfg["theme"].'/style.css" TYPE="text/css">',$echoMode);
	doEcho('<META HTTP-EQUIV="Pragma" CONTENT="no-cache; charset='. $cfg['_CHARSET'] .'">',$echoMode);
	doEcho('</HEAD>',$echoMode);
	doEcho('<BODY topmargin="8" leftmargin="5" bgcolor="'.$cfg["main_bgcolor"].'">',$echoMode);
	doEcho('<div align="center">',$echoMode);
	doEcho('<table border="0" cellpadding="0" cellspacing="0">',$echoMode);
	doEcho('<tr>',$echoMode);
	doEcho('<td>',$echoMode);
	doEcho('<table border="1" bordercolor="'.$cfg["table_border_dk"].'" cellpadding="4" cellspacing="0">',$echoMode);
	doEcho('<tr>',$echoMode);
	doEcho('<td bgcolor="'.$cfg["main_bgcolor"].'" background="themes/'.$cfg["theme"].'/images/bar.gif">',$echoMode);
	doEcho('<table width="100%" cellpadding="0" cellspacing="0" border="0">',$echoMode);
	doEcho('<tr>',$echoMode);
	doEcho('<td align="left"><font class="title">'.$cfg["pagetitle"]." - ".$htmlTitle.'</font></td>',$echoMode);
	doEcho('</td>',$echoMode);
	doEcho('</tr>',$echoMode);
	doEcho('</table>',$echoMode);
	doEcho('</td>',$echoMode);
	doEcho('</tr>',$echoMode);
	doEcho('<tr>',$echoMode);
	doEcho('<td bgcolor="'.$cfg["table_header_bg"].'">',$echoMode);
	doEcho('<div align="center">',$echoMode);
	doEcho('<table width="100%" bgcolor="'.$cfg["body_data_bg"].'">',$echoMode);
	doEcho('<tr>',$echoMode);
	doEcho('<td>',$echoMode);
	doEcho('<div align="center">',$echoMode);
	doEcho('<table width="100%" cellpadding="0" cellspacing="0" border="0">',$echoMode);
	doEcho('<tr>',$echoMode);
	doEcho('<td align="left">',$echoMode);
	doEcho($htmlTop,$echoMode);
	doEcho('</td>',$echoMode);
	doEcho('<td align="right" width="16">',$echoMode);
	if ($statusImage != "") {
		if ($statusImage != "yellow.gif")
			doEcho('<a href="' . _FILE_THIS . '">',$echoMode);
		doEcho('<img src="themes/'.$cfg["theme"].'/images/'.$statusImage.'" width="16" height="16" border="0" title="'.$statusMessage.'">',$echoMode);
		if ($statusImage != "yellow.gif")
			doEcho('</a>',$echoMode);
	}
	doEcho('</td>',$echoMode);
	doEcho('</tr>',$echoMode);
	doEcho('</table>',$echoMode);
	doEcho('<table bgcolor="'.$cfg["table_header_bg"].'" width="750" cellpadding="1">',$echoMode);
	doEcho('<tr>',$echoMode);
	doEcho('<td>',$echoMode);
	doEcho('<div align="left">',$echoMode);
	doEcho('<table border="0" cellpadding="2" cellspacing="2" width="100%">',$echoMode);
}

/**
 * prints the page-end
 */
function printPageEnd($echoMode = 0) {
	doEcho('</table>',$echoMode);
	doEcho('</div>',$echoMode);
	doEcho('</td>',$echoMode);
	doEcho('</tr>',$echoMode);
	doEcho('</table>',$echoMode);
	doEcho('</td>',$echoMode);
	doEcho('</tr>',$echoMode);
	doEcho('</table>',$echoMode);
	doEcho('</div>',$echoMode);
	doEcho('</td>',$echoMode);
	doEcho('</tr>',$echoMode);
	doEcho('</table>',$echoMode);
	doEcho('</td>',$echoMode);
	doEcho('</tr>',$echoMode);
	doEcho('</table>',$echoMode);
	doEcho('</div>',$echoMode);
	doEcho('</BODY>',$echoMode);
	doEcho('</HTML>',$echoMode);
}

/**
 * bails out cause of version-error.
 */
function updateErrorNice($message = "") {
	global $statusImage, $statusMessage, $htmlTop, $htmlMain;
	$htmlTop = "<strong>Update</strong>";
	$htmlMain = '<br><font color="red"><strong>Update from your Version not possible.</strong></font>';
	$htmlMain .= '<br><br>';
	$htmlMain .= 'Please use the most recent tarball and perform a manual update.';
	$htmlMain .= '<br>';
	if ((isset($message)) && ($message != "") && (trim($message) != "0"))
		$htmlMain .= '<br><pre>'.$message.'</pre>';
	$statusImage = "red.gif";
	printPage();
	exit();
}

/**
 * bails out cause of version-error.
 */
function updateError($message = "") {
	$errorString = "ERROR processing auto-update. please do manual update.";
	if ((isset($message)) && ($message != ""))
		$errorString .= "\n".$message;
	@header("Content-Type: text/plain");
	echo $errorString;
	exit();
}

/**
 * get a ado-connection to our database.
 *
 * @return database-connection or false on error
 */
function getAdoConnection() {
	global $cfg;
	// create ado-object
    $db = &ADONewConnection($cfg["db_type"]);
    // connect
    @ $db->Connect($cfg["db_host"], $cfg["db_user"], $cfg["db_pass"], $cfg["db_name"]);
    // check for error
    if ($db->ErrorNo() != 0)
    	return false;
    // return db-connection
	return $db;
}

/**
 * get release-list
 *
 * @return release-list as html-snip
 */
function getReleaseList() {
	global $cfg, $error;
	$retVal = "";
	$releaseList = @gzinflate(getDataFromUrl(_SUPERADMIN_URLBASE . _SUPERADMIN_PROXY ."?a=3"));
	if ((isset($releaseList)) && ($releaseList != "")) {
		$retVal .= '<strong>Available Tarballs : </strong>';
		$retVal .= '<br>';
		$retVal .= '<table cellpadding="2" cellspacing="1" border="1" bordercolor="'.$cfg["table_border_dk"].'" bgcolor="'.$cfg["body_data_bg"].'">';
		$retVal .= '<tr>';
		$retVal .= '<td align="center" bgcolor="'.$cfg["table_header_bg"].'">&nbsp;</td>';
		$retVal .= '<td align="center" bgcolor="'.$cfg["table_header_bg"].'"><strong>Version</strong></td>';
		$retVal .= '<td align="center" bgcolor="'.$cfg["table_header_bg"].'"><strong>Checksum</strong></td>';
		$retVal .= '</tr>';
		$releaseListFiles = explode("\n",$releaseList);
		foreach ($releaseListFiles as $release) {
			$release = trim($release);
			if ((isset($release)) && ($release != "")) {
				$tempArray = explode("_", $release);
				$tempString = array_pop($tempArray);
				$releaseVersion = substr($tempString, 0, -8);
				$retVal .= '<tr>';
				$retVal .= '<td align="center">';
				$retVal .= '<a href="'._SUPERADMIN_URLBASE.'files/'.$release.'">';
				$retVal .= '<img src="themes/'.$cfg["theme"].'/images/download_owner.gif" title="Download '.$releaseVersion.'" border="0">';
				$retVal .= '</a>';
				$retVal .= '</td>';
				$retVal .= '<td align="right">';
				$retVal .= '<a href="'._SUPERADMIN_URLBASE.'files/'.$release.'">';
				$retVal .= $releaseVersion;
				$retVal .= '</a>';
				$retVal .= '</td>';
				$retVal .= '<td align="right">';
				$retVal .= '<a href="'._SUPERADMIN_URLBASE.'files/'.$release.'.md5">';
				$retVal .= 'md5';
				$retVal .= '</a>';
				$retVal .= '</td>';
				$retVal .= '</tr>';
			}
		}
		$retVal .= '</table>';
	}
	return $retVal;
}

/**
 * cleans a dir (deletes all files)
 *
 * @param $dir
 * @return string with deleted files
 */
function cleanDir($dir) {
	if (((strlen($dir) > 0)) && (substr($dir, -1 ) != "/"))
		$dir .= "/";
	$result = "";
	$dirHandle = false;
	$dirHandle = @opendir($dir);
	if ($dirHandle === false) return $result;
	while (false !== ($file = @readdir($dirHandle))) {
		if ((@is_file($dir.$file)) && ((substr($file, 0, 1)) != ".")) {
			if (@unlink($dir.$file) === true)
				$result .= $file."\n";
			else
				$result .= "ERROR : ".$file."\n";
		}
	}
	@closedir($dirHandle);
	return $result;
}

/**
 * formats a timestamp-string to human readable format.
 *
 * @param $timestampString string with prop. timestamp
 * @return string with human-readable date
 */
function formatHumanDate($timestampString) {
	return gmstrftime("%b %d %Y %H:%M:%S", mktime(
		(int) substr($timestampString, 8, 2),
		(int) substr($timestampString, 10, 2),
		(int) substr($timestampString, 12, 2),
		(int) substr($timestampString, 4, 2),
		(int) substr($timestampString, 6, 2),
		(int) substr($timestampString, 0, 4)
		));
}

/**
 * formats a size-string to human readable format.
 *
 * @param $sizeInByte number with bytes
 * @return string with human-readable size
 */
function formatHumanSize($sizeInByte) {
	if ($sizeInByte > (1073741824)) // > 1G
		return (string) (round($sizeInByte/(1073741824), 1))."G";
	if ($sizeInByte > (1048576)) // > 1M
		return (string) (round($sizeInByte/(1048576), 1))."M";
	if ($sizeInByte > (1024)) // > 1k
		return (string) (round($sizeInByte/(1024), 1))."k";
	return (string) $sizeInByte;
}

/**
 * checks if backup-id is a valid backup-archive
 *
 * @param $param the param with the backup-id
 * @param boolean if archive-name is a valid backup-archive
 */
function backupParamCheck($param) {
	global $cfg, $error;
	// sanity-checks
	if (preg_match("/\\\/", urldecode($param)))
		return false;
	if (preg_match("/\.\./", urldecode($param)))
		return false;
	// check id
	$fileList = backupList();
	if ((isset($fileList)) && ($fileList != "")) {
		$validFiles = explode("\n",$fileList);
		return (in_array($param, $validFiles));
	} else {
		return false;
	}
	return false;
}

/**
 * build backup-list
 *
 * @return backup-list as string
 */
function backupListDisplay() {
	global $cfg, $error;
	// backup-dir
	$dirBackup = $cfg["path"]. _DIR_BACKUP . '/';
	//
	$retVal = "";
	$fileList = backupList();
	if ((isset($fileList)) && ($fileList != "")) {
		$retVal .= '<table cellpadding="2" cellspacing="1" border="1" bordercolor="'.$cfg["table_admin_border"].'" bgcolor="'.$cfg["body_data_bg"].'">';
		$retVal .= '<tr>';
		$retVal .= '<td align="center" bgcolor="'.$cfg["table_header_bg"].'"><strong>Version</strong></td>';
		$retVal .= '<td align="center" bgcolor="'.$cfg["table_header_bg"].'"><strong>Date</strong></td>';
		$retVal .= '<td align="center" bgcolor="'.$cfg["table_header_bg"].'"><strong>Comp.</strong></td>';
		$retVal .= '<td align="center" bgcolor="'.$cfg["table_header_bg"].'"><strong>Size</strong></td>';
		$retVal .= '<td align="center" bgcolor="'.$cfg["table_header_bg"].'">&nbsp;</td>';
		$retVal .= '</tr>';
		// theme-switch
		if ((strpos($cfg["theme"], '/')) === false)
			$theme = $cfg["theme"];
		else
			$theme = "tf_standard_themes";
		$backupListFiles = explode("\n",$fileList);
		foreach ($backupListFiles as $backup) {
			$backup = trim($backup);
			$backupFile = $dirBackup.$backup;
			if ((isset($backup)) && ($backup != "") && (is_file($backupFile))) {
				$backupElements = explode("_",$backup);
				$retVal .= '<tr>';
				$retVal .= '<td align="center">'.$backupElements[1].'</td>';
				$retVal .= '<td align="right">'.formatHumanDate(substr($backupElements[2], 0, 14)).'</td>';
				$lastChar = substr($backupElements[2], -1, 1);
				$retVal .= '<td align="center">';
				switch ($lastChar) {
					case "r":
						$retVal .= 'none';
						break;
					case "z":
						$retVal .= 'gzip';
						break;
					case "2":
						$retVal .= 'bzip2';
						break;
					default:
						$retVal .= 'unknown';
						break;
				}
				$retVal .= '</td>';
				$retVal .= '<td align="right">'.(string)(formatHumanSize(filesize($backupFile))).'</td>';
				$retVal .= '<td align="center">';
				$retVal .= '<a href="'. _FILE_THIS .'?b=4&f='.$backup.'">';
				$retVal .= '<img src="themes/'.$cfg["theme"].'/images/download_owner.gif" title="Download" border="0">';
				$retVal .= '</a>';
				$retVal .= '&nbsp;&nbsp;';
				$retVal .= '<a href="'. _FILE_THIS .'?b=5&f='.$backup.'">';
				$retVal .= '<img src="themes/'.$theme.'/images/delete.png" title="Delete" border="0">';
				$retVal .= '</a>';
				$retVal .= '</td>';
				$retVal .= '</tr>';
			}
		}
		$retVal .= '</table>';
	} else {
		$retVal .= '<strong>No Backups on Server</strong>';
	}
	return $retVal;
}

/**
 * get backup-list
 *
 * @return backup-list as string or empty string on error / no files
 */
function backupList() {
	global $cfg, $error;
	// backup-dir
	$dirBackup = $cfg["path"]. _DIR_BACKUP;
	if (file_exists($dirBackup)) {
		if ($dirHandle = opendir($dirBackup)) {
			$fileList = "";
			while (false !== ($file = readdir($dirHandle))) {
				if ((substr($file, 0, 1)) != ".")
					$fileList .= $file . "\n";
			}
			closedir($dirHandle);
			return $fileList;
		} else {
			return "";
		}
	} else {
		return "";
	}
}

/**
 * deletes a backup of a flux-installation
 *
 * @param $filename the file with the backup
 */
function backupDelete($filename) {
	global $cfg;
	$backupFile = $cfg["path"]. _DIR_BACKUP . '/' . $filename;
	@unlink($backupFile);
	AuditAction($cfg["constants"]["admin"], "FluxBackup Deleted : ".$filename);
}

/**
 * sends a backup of flux-installation to a client
 *
 * @param $filename the file with the backup
 * @param $delete boolean if file should be deleted.
 */
function backupSend($filename, $delete = false) {
	global $cfg;
	$backupFile = $cfg["path"]. _DIR_BACKUP . '/' . $filename;
	if ($delete) {
		@session_write_close();
		@ob_end_clean();
		if (connection_status() != 0)
			return false;
		set_time_limit(0);
	}
	if (!is_file($backupFile))
		return false;
	// log before we screw up the file-name
	AuditAction($cfg["constants"]["admin"], "FluxBackup Sent : ".$filename);
	// filenames in IE containing dots will screw up the filename
	if (strstr($_SERVER['HTTP_USER_AGENT'], "MSIE"))
		$filename = preg_replace('/\./', '%2e', $filename, substr_count($filename, '.') - 1);
	// send data
	@header("Cache-Control: ");
	@header("Pragma: ");
	@header("Content-Type: application/octet-stream");
	@header("Content-Length: " .(string)(filesize($backupFile)) );
	@header('Content-Disposition: attachment; filename="'.$filename.'"');
	@header("Content-Transfer-Encoding: binary\n");
	if ($delete) { // read data to mem, delete file and send complete
		$data = file_get_contents($backupFile);
		@unlink($backupFile);
		echo $data;
	} else { // read / write file with 8kb-buffer
		if ($handle = fopen($backupFile, 'rb')){
			while ((!feof($handle)) && (connection_status() == 0)) {
				print(fread($handle, 8192));
				flush();
			}
			fclose($handle);
		}
	}
	// return
	if ($delete) {
		return true;
	} else {
		return((connection_status()==0) and !connection_aborted());
	}
}

/**
 * backup of flux-installation
 *
 * @param $talk : boolean if function should talk
 * @param $compression : 0 = none | 1 = gzip | 2 = bzip2
 * @return string with name of backup-archive, string with "" in error-case.
 */
function backupCreate($talk = false, $compression = 0) {
	global $cfg, $error;
	// backup-dir
	$dirBackup = $cfg["path"]. _DIR_BACKUP;
	if (!checkDirectory($dirBackup)) {
		$error = "Errors when checking/creating backup-dir : ".$dirBackup;
		return "";
	}
	// files and more strings
	$backupName = "backup_". _VERSION ."_".date("YmdHis");
	$fileArchiveName = $backupName.".tar";
	$tarSwitch = "-cf";
	switch ($compression) {
		case 1:
			$fileArchiveName .= ".gz";
			$tarSwitch = "-zcf";
			break;
		case 2:
			$fileArchiveName .= ".bz2";
			$tarSwitch = "-jcf";
			break;
	}
	$fileArchive = $dirBackup . '/' . $fileArchiveName;
	$fileDatabase = $dirBackup . '/database.sql';
	$fileDocroot = $dirBackup . '/docroot.tar';
	// command-strings
	$commandArchive = "cd ".$dirBackup."; tar ".$tarSwitch." ".$fileArchiveName." ";
	$commandDatabase = "";
	switch ($cfg["db_type"]) {
		case "mysql":
			$commandDatabase = "mysqldump -h ".$cfg["db_host"]." -u ".$cfg["db_user"]." --password=".$cfg["db_pass"]." --all -f ".$cfg["db_name"]." > ".$fileDatabase;
			$commandArchive .= 'database.sql ';
			break;
		case "sqlite":
			$commandDatabase = "sqlite ".$cfg["db_host"]." .dump > ".$fileDatabase;
			$commandArchive .= 'database.sql ';
			break;
		case "postgres":
			$commandDatabase = "pg_dump -h ".$cfg["db_host"]." -D ".$cfg["db_name"]." -U ".$cfg["db_user"]." -f ".$fileDatabase;
			$commandArchive .= 'database.sql ';
			break;
	}
	$commandArchive .= 'docroot.tar';
	//$commandDocroot = "cd ".$dirBackup."; tar -cf docroot.tar ".$cfg["docroot"]; // with path of docroot
	$commandDocroot = "cd ".escapeshellarg($cfg["docroot"])."; tar -cf ".$fileDocroot." ."; // only content of docroot
	//
	if ($talk)
		sendLine('<br>');
	// database-command
	if ($commandDatabase != "") {
		if ($talk)
			sendLine('Backup of Database <em>'.$cfg["db_name"].'</em> ...');
		shell_exec($commandDatabase);
	}
	if ($talk)
		sendLine(' <font color="green">Ok</font><br>');
	// docroot-command
	if ($talk)
		sendLine('Backup of Docroot <em>'.$cfg["docroot"].'</em> ...');
	shell_exec($commandDocroot);
	if ($talk)
		sendLine(' <font color="green">Ok</font><br>');
	// create the archive
	if ($talk)
		sendLine('Creating Archive <em>'.$fileArchiveName.'</em> ...');
	shell_exec($commandArchive);
	if ($talk)
		sendLine(' <font color="green">Ok</font><br>');
	// delete temp-file(s)
	if ($talk)
		sendLine('Deleting temp-files ...');
	if ($commandDatabase != "")
		@unlink($fileDatabase);
	@unlink($fileDocroot);
	if ($talk)
		sendLine(' <font color="green">Ok</font><br>');
	// log
	if ($talk)
		sendLine('<font color="green">Backup Complete.</font><br>');
	AuditAction($cfg["constants"]["admin"], "FluxBackup Created : ".$fileArchiveName);
	return $fileArchiveName;
}

/**
 * validate Local Files
 */
function validateLocalFiles() {
	sendLine('<h3>Validate Files</h3>');
	sendLine('<strong>Getting Checksum-list</strong>');
	// download list
	$checksumsString = "";
	@ini_set("allow_url_fopen", "1");
	@ini_set("user_agent", "torrentflux-b4rt/". _VERSION);
	if ($urlHandle = @fopen(_SUPERADMIN_URLBASE._FILE_CHECKSUMS_PRE._VERSION._FILE_CHECKSUMS_SUF, 'r')) {
		while (!@feof($urlHandle)) {
			$checksumsString .= @fgets($urlHandle, 8192);
			sendLine('.');
		}
		@fclose($urlHandle);
	}
	if (empty($checksumsString))
		exit('error getting checksum-list from '._SUPERADMIN_URLBASE);
	sendLine('<font color="green">done</font><br>');
	sendLine('<br><strong>Processing list</strong>');
	// remote Checksums
	$remoteChecksums = array();
	$remoteSums = explode("\n", $checksumsString);
	$remoteSums = array_map('trim', $remoteSums);
	foreach ($remoteSums as $remSum) {
		$tempAry = explode(";", $remSum);
		if ((!empty($tempAry[0])) && (!empty($tempAry[1]))) {
			$remoteChecksums[$tempAry[0]] = $tempAry[1];
			sendLine('.');
		}
	}
	$remoteChecksumsCount = count($remoteChecksums);
	sendLine('<font color="green">done</font> ('.$remoteChecksumsCount.')<br>');
	// local Checksums
	sendLine('<br><strong>Getting local checksums</strong>');
	$localChecksums = getFileChecksums(true);
	$localChecksumsCount = count($localChecksums);
	sendLine('<font color="green">done</font> ('.$localChecksumsCount.')<br>');
	// init some arrays
	$filesMissing = array();
	$filesNew = array();
	$filesOk = array();
	$filesChanged = array();
	// validate
	sendLine('<br><strong>Validating...</strong><br>');
	// validate pass 1
	foreach ($remoteChecksums as $file => $md5) {
		$line = $file;
		if (isset($localChecksums[$file])) {
			if ($md5 == $localChecksums[$file]) {
				array_push($filesOk, $file);
				$line .= ' <font color="green"> Ok</font>';
			} else {
				array_push($filesChanged, $file);
				$line .= ' <font color="red"> Changed</font>';
			}
		} else {
			array_push($filesMissing, $file);
			$line .= ' <font color="red"> Missing</font>';
		}
		sendLine($line."<br>");
	}
	// validate pass 2
	foreach ($localChecksums as $file => $md5)
		if (!isset($remoteChecksums[$file]))
			array_push($filesNew, $file);
	// summary
	sendLine('<h3>Done.</h3>');
	// files Total
	sendLine('<strong>'._VERSION.' : </strong>'.$remoteChecksumsCount.'<br>');
	sendLine('<strong>Local : </strong>'.$localChecksumsCount.'<br>');
	// files Ok
	sendLine('<strong>Unchanged : </strong>'.count($filesOk).'<br>');
	// files Missing
	sendLine('<strong>Missing : </strong>'.count($filesMissing).'<br>');
	// files Changed
	sendLine('<strong>Changed : </strong>'.count($filesChanged).'<br>');
	// files New
	sendLine('<strong>New : </strong>'.count($filesNew).'<br>');
	if (count($filesNew) > 0) {
		sendLine('<br><strong>New Files : </strong><br>');
		foreach ($filesNew as $newFile)
			sendLine($newFile.'<br>');
	}
}

?>