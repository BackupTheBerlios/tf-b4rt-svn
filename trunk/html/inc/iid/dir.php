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

// dir functions
require_once('inc/functions/functions.dir.php');

// config
loadSettings('tf_settings_dir');
initRestrictedDirEntries();

// check incoming path
checkIncomingPath();

// Setup some defaults if they are not set.
$chmod = getRequestVar('chmod');
$del = getRequestVar('del');
$down = getRequestVar('down');
$tar = getRequestVar('tar');
$dir = stripslashes(urldecode(getRequestVar('dir')));
$multidel = getRequestVar('multidel');

/*******************************************************************************
 * chmod
 ******************************************************************************/
if ($chmod != "") {
	// only valid entry with permission
	if ((isValidEntry(basename($dir))) && (hasPermission($dir, $cfg["user"], 'w')))
		chmodRecursive($cfg["path"].$dir);
	else
		AuditAction($cfg["constants"]["error"], "ILLEGAL CHNOD: ".$cfg["user"]." tried to chmod ".$dir);
	header("Location: index.php?iid=dir&dir=".urlencode($dir));
	exit();
}

/*******************************************************************************
 * delete
 ******************************************************************************/
if ($del != "") {
	// only valid entry with permission
	if ((isValidEntry(basename($del))) && (hasPermission($del, $cfg["user"], 'w'))) {
		$current = delDirEntry($del);
	} else {
		AuditAction($cfg["constants"]["error"], "ILLEGAL DELETE: ".$cfg["user"]." tried to delete ".$del);
		$current = $del;
		$del = stripslashes(stripslashes($del));
		if (!ereg("(\.\.\/)", $del)) {
			$arTemp = explode("/", $del);
			if (count($arTemp) > 1) {
				array_pop($arTemp);
				$current = implode("/", $arTemp);
			}
		}
	}
	header("Location: index.php?iid=dir&dir=".urlencode($current));
	exit();
}

/*******************************************************************************
 * multi-delete
 ******************************************************************************/
if ($multidel != "") {
	foreach($_POST['file'] as $key => $element) {
		$element = urldecode($element);
		// only valid entry with permission
		if ((isValidEntry(basename($element))) && (hasPermission($element, $cfg["user"], 'w')))
			delDirEntry($element);
		else
			AuditAction($cfg["constants"]["error"], "ILLEGAL DELETE: ".$cfg["user"]." tried to delete ".$element);
	}
	header("Location: index.php?iid=dir&dir=".urlencode($dir));
	exit();
}

/*******************************************************************************
 * download
 ******************************************************************************/
if ($down != "" && $cfg["enable_file_download"]) {
	// only valid entry with permission
	if ((isValidEntry(basename($down))) && (hasPermission($down, $cfg["user"], 'r'))) {
		$current = "";
		// Yes, then download it
		// we need to strip slashes twice in some circumstances
		// Ex.	If we are trying to download test/tester's file/test.txt
		// $down will be "test/tester\\\'s file/test.txt"
		// one strip will give us "test/tester\'s file/test.txt
		// the second strip will give us the correct
		//	"test/tester's file/test.txt"
		$down = stripslashes(stripslashes($down));
		if (!ereg("(\.\.\/)", $down)) {
			$path = $cfg["path"].$down;
			$p = explode(".", $path);
			$pc = count($p);
			$f = explode("/", $path);
			$file = array_pop($f);
			$arTemp = explode("/", $down);
			if (count($arTemp) > 1) {
				array_pop($arTemp);
				$current = implode("/", $arTemp);
			}
			if (file_exists($path)) {
				header("Content-type: application/octet-stream\n");
				header("Content-disposition: attachment; filename=\"".$file."\"\n");
				header("Content-transfer-encoding: binary\n");
				header("Content-length: " . file_size($path) . "\n");
				// write the session to close so you can continue to browse on the site.
				session_write_close("TorrentFlux");
				//$fp = fopen($path, "r");
				$fp = popen("cat \"$path\"", "r");
				fpassthru($fp);
				pclose($fp);
				// log
				AuditAction($cfg["constants"]["fm_download"], $down);
				exit();
			} else {
				AuditAction($cfg["constants"]["error"], "File Not found for download: ".$cfg["user"]." tried to download ".$down);
			}
		} else {
			AuditAction($cfg["constants"]["error"], "ILLEGAL DOWNLOAD: ".$cfg["user"]." tried to download ".$down);
		}
	} else {
		AuditAction($cfg["constants"]["error"], "ILLEGAL DOWNLOAD: ".$cfg["user"]." tried to download ".$down);
		$current = $down;
		$down = stripslashes(stripslashes($down));
		if (!ereg("(\.\.\/)", $down)) {
			$path = $cfg["path"].$down;
			$p = explode(".", $path);
			$pc = count($p);
			$f = explode("/", $path);
			$file = array_pop($f);
			$arTemp = explode("/", $down);
			if (count($arTemp) > 1) {
				array_pop($arTemp);
				$current = implode("/", $arTemp);
			}
		}
	}
	header("Location: index.php?iid=dir&dir=".urlencode($current));
	exit();
}

/*******************************************************************************
 * download as archive
 ******************************************************************************/
if ($tar != "" && $cfg["enable_file_download"]) {
	// only valid entry with permission
	if ((isValidEntry(basename($tar))) && (hasPermission($tar, $cfg["user"], 'r'))) {
		$current = "";
		// Yes, then tar and download it
		// we need to strip slashes twice in some circumstances
		// Ex.	If we are trying to download test/tester's file/test.txt
		// $down will be "test/tester\\\'s file/test.txt"
		// one strip will give us "test/tester\'s file/test.txt
		// the second strip will give us the correct
		//	"test/tester's file/test.txt"
		$tar = stripslashes(stripslashes($tar));
		if (!ereg("(\.\.\/)", $tar)) {
			// This prevents the script from getting killed off when running lengthy tar jobs.
			ini_set("max_execution_time", 3600);
			$tar = $cfg["path"].$tar;
			$arTemp = explode("/", $tar);
			if (count($arTemp) > 1) {
				array_pop($arTemp);
				$current = implode("/", $arTemp);
			}
			// Find out if we're really trying to access a file within the
			// proper directory structure. Sadly, this way requires that $cfg["path"]
			// is a REAL path, not a symlinked one. Also check if $cfg["path"] is part
			// of the REAL path.
			if (is_dir($tar)) {
				$sendname = basename($tar);
				switch ($cfg["package_type"]) {
					Case "tar":
						$command = "tar cf - \"".addslashes($sendname)."\"";
						break;
					Case "zip":
						$command = "zip -0r - \"".addslashes($sendname)."\"";
						break;
					default:
						$cfg["package_type"] = "tar";
						$command = "tar cf - \"".addslashes($sendname)."\"";
						break;
				}
				// HTTP/1.0
				header("Pragma: no-cache");
				header("Content-Description: File Transfer");
				header("Content-Type: application/force-download");
				header('Content-Disposition: attachment; filename="'.$sendname.'.'.$cfg["package_type"].'"');
				// write the session to close so you can continue to browse on the site.
				session_write_close("TorrentFlux");
				// Make it a bit easier for tar/zip.
				chdir(dirname($tar));
				passthru($command);
				AuditAction($cfg["constants"]["fm_download"], $sendname.".".$cfg["package_type"]);
				exit();
			} else {
				AuditAction($cfg["constants"]["error"], "Illegal download: ".$cfg["user"]." tried to download ".$tar);
			}
		} else {
			AuditAction($cfg["constants"]["error"], "ILLEGAL TAR DOWNLOAD: ".$cfg["user"]." tried to download ".$tar);
		}
	} else {
		AuditAction($cfg["constants"]["error"], "ILLEGAL TAR DOWNLOAD: ".$cfg["user"]." tried to download ".$tar);
		$current = $tar;
		$del = stripslashes(stripslashes($tar));
		if (!ereg("(\.\.\/)", $tar)) {
			$arTemp = explode("/", $tar);
			if (count($arTemp) > 1) {
				array_pop($arTemp);
				$current = implode("/", $arTemp);
			}
		}
	}
	header("Location: index.php?iid=dir&dir=".urlencode($current));
	exit();
}

/*******************************************************************************
 * dir-page
 ******************************************************************************/

// check dir-var
if (isset($dir)) {
	if ($dir != "") {
		if (ereg("(\.\.)", $dir))
			$dir = "";
		else
			$dir = $dir."/";
	}
} else {
	$dir = "";
}

// dir-name
$dirName = $cfg["path"].$dir;
$dirName = stripslashes($dirName);

// dir-check
if (!(@is_dir($dirName))) {
	// our dir is no dir but a file. use parent-directory.
	if (preg_match("/^(.+)\/.+$/", $dir, $matches) == 1)
		header("Location: index.php?iid=dir&dir=".urlencode($matches[1]));
	else
		header("Location: index.php?iid=dir");
	exit();
}

// create template-instance
$tmpl = tmplGetInstance($cfg["theme"], "dir.tmpl");

// dirstats
if ($cfg['enable_dirstats'] == 1) {
	$tmpl->setvar('enable_dirstats', 1);
	switch ($cfg["_OS"]) {
		case 1: //Linux
			$duArg = "-D";
			break;
		case 2: //BSD
			$duArg = "-L";
			break;
	}
	$du = @shell_exec($cfg['bin_du']." -ch ".$duArg." ".escapeshellarg($dirName)." | ".$cfg['bin_grep']." \"total\"");
	$tmpl->setvar('duTotal', @substr($du, 0, -7));
	$tmpl->setvar('_TDDU', $cfg['_TDDU']);
} else {
	$tmpl->setvar('enable_dirstats', 0);
}

// read in entries
$entrys = array();
$handle = opendir($dirName);
while (false !== ($entry = readdir($handle))) {
	if ((isValidEntry($entry)) && (hasPermission($entry, $cfg["user"], 'r')))
		array_push($entrys, $entry);
}
closedir($handle);
natsort($entrys);

// process entries and fill dir- + file-array
$list = array();
$filelist = array();
foreach ($entrys as $entry) {
	// acl-write-check
	if (hasPermission($entry, $cfg["user"], 'w'))
		$aclWrite = 1;
	else
		$aclWrite = 0;
	if (@is_dir($dirName.$entry)) { // dir
		// dirstats
		if ($cfg['enable_dirstats'] == 1) {
			$dudir = @shell_exec($cfg['bin_du']." -sk -h ".$duArg." ".escapeshellarg($dirName.$entry));
			$size = @explode("\t", $dudir);
			$size = @array_shift($size);
			$arStat = @lstat($dirName.$entry);
			$timeStamp = @filemtime($dirName.$entry);
			$date = @date("m-d-Y h:i a", $timeStamp);
		} else {
			$size = 0;
			$date = "";
		}
		// sfv
		if (($cfg['enable_sfvcheck'] == 1) && (false !== ($sfv = findSFV($dirName.$entry)))) {
			$show_sfv = 1;
			$sfvdir = $sfv['dir'];
			$sfvsfv = $sfv['sfv'];
		} else {
			$show_sfv = 0;
			$sfvdir = "";
			$sfvsfv = "";
		}
		// add entry to dir-array
		array_push($list, array(
			'is_dir' => 1,
			'aclWrite' => $aclWrite,
			'entry' => $entry,
			'urlencode1' => urlencode($dir.$entry),
			'urlencode2' => urlencode($dir),
			'urlencode3' => urlencode($entry),
			'addslashes1' => addslashes($entry),
			'size' => $size,
			'date' => $date,
			'show_sfv' => $show_sfv,
			'sfvdir' => $sfvdir,
			'sfvsfv' => $sfvsfv
			)
		);
	} else if (!@is_dir($dirName.$entry)) { // file
		// image
		$image="themes/".$cfg['theme']."/images/time.gif";
		$imageOption="themes/".$cfg['theme']."/images/files/".getExtension($entry).".png";
		if (file_exists("./".$imageOption))
			$image = $imageOption;
		// dirstats
		if ($cfg['enable_dirstats'] == 1) {
			$arStat = @lstat($dirName.$entry);
			$arStat[7] = ($arStat[7] == 0) ? @file_size($dirName.$entry) : $arStat[7];
			$timeStamp = "";
			if (array_key_exists(10,$arStat))
				$timeStamp = @filemtime($dirName.$entry);
			$size = @number_format(($arStat[7]) / 1024);
			$date = @date("m-d-Y h:i a", $timeStamp);
		} else {
			$size = 0;
			$date = "";
		}
		// nfo
		if ($cfg["enable_view_nfo"] == 1)
			$show_nfo = isNfo($entry);
		else
			$show_nfo = 0;
		// rar
		if (($cfg["enable_rar"] == 1) && ($aclWrite == 1))
			$show_rar = isRar($entry);
		else
			$show_rar = 0;
		// add entry to file-array
		array_push($filelist, array(
			'is_dir' => 0,
			'aclWrite' => $aclWrite,
			'entry' => $entry,
			'urlencode1' => urlencode($dir.$entry),
			'urlencode2' => urlencode($dir),
			'urlencode3' => urlencode($entry),
			'urlencode4' => urlencode(addslashes($dir.$entry)),
			'addslashes1' => addslashes($entry),
			'image' => $image,
			'size' => $size,
			'date' => $date,
			'show_nfo' => $show_nfo,
			'show_rar' => $show_rar
			)
		);
	}
}

// add files to list
foreach ($filelist as $entry)
	array_push($list, $entry);

// set template-loop
$tmpl->setloop('list', $list);

// define some things

// dir
$tmpl->setvar('dir', $dir);
// parent url
if (preg_match("/^(.+)\/.+$/", $dir, $matches) == 1)
	$tmpl->setvar('parentURL', "index.php?iid=dir&dir=" . urlencode($matches[1]));
else
	$tmpl->setvar('parentURL', "index.php?iid=dir");
// chmod, parent-dir cannot be chmodded
if ($dir == "") {
	$tmpl->setvar('show_chmod', 0);
} else {
	if (($cfg["dir_enable_chmod"] == 1) && (hasPermission($dir, $cfg['user'], 'w')))
		$tmpl->setvar('show_chmod', 1);
	else
		$tmpl->setvar('show_chmod', 0);
}
//
$tmpl->setvar('enable_rename', $cfg["enable_rename"]);
$tmpl->setvar('enable_move', $cfg["enable_move"]);
$tmpl->setvar('enable_sfvcheck',  $cfg['enable_sfvcheck']);
$tmpl->setvar('enable_rar', $cfg["enable_rar"]);
$tmpl->setvar('enable_view_nfo', $cfg["enable_view_nfo"]);
$tmpl->setvar('enable_file_download', $cfg["enable_file_download"]);
$tmpl->setvar('package_type', $cfg["package_type"]);
$tmpl->setvar('enable_maketorrent', $cfg["enable_maketorrent"]);
$tmpl->setvar('bgDark', $cfg['bgDark']);
$tmpl->setvar('bgLight', $cfg['bgLight']);
//
$tmpl->setvar('_DELETE', $cfg['_DELETE']);
$tmpl->setvar('_DIR_REN_LINK', $cfg['_DIR_REN_LINK']);
$tmpl->setvar('_DIR_MOVE_LINK', $cfg['_DIR_MOVE_LINK']);
$tmpl->setvar('_ABOUTTODELETE', $cfg['_ABOUTTODELETE']);
$tmpl->setvar('_BACKTOPARRENT', $cfg['_BACKTOPARRENT']);
//
$tmpl->setvar('driveSpaceBar', getDriveSpaceBar(getDriveSpace($cfg["path"])));
tmplSetTitleBar($cfg["pagetitle"].' - '.$cfg['_DIRECTORYLIST']);
$tmpl->setvar('foot', getFoot());
$tmpl->setvar('iid', $_GET["iid"]);

// parse template
$tmpl->pparse();

?>