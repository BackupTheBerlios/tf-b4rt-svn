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
$del = getRequestVar('del');
$down = getRequestVar('down');
$tar = getRequestVar('tar');
$dir = stripslashes(urldecode(getRequestVar('dir')));
$multidel = getRequestVar('multidel');

/*******************************************************************************
 * multi-del
 ******************************************************************************/
if ($multidel != "") {
	foreach($_POST['file'] as $key => $element) {
		$element = urldecode($element);
		if (isValidEntry(basename($element)))
			delDirEntry($element);
		else
			AuditAction($cfg["constants"]["error"], "ILLEGAL DELETE: ".$cfg["user"]." tried to delete ".$element);
	}
	header("Location: index.php?iid=dir&dir=".urlencode($dir));
	exit();
}

/*******************************************************************************
 * delete
 ******************************************************************************/
if ($del != "") {
	// only valid entry
	if (isValidEntry(basename($del))) {
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
 * download
 ******************************************************************************/
if ($down != "" && $cfg["enable_file_download"]) {
	// only valid entry
	if (isValidEntry(basename($down))) {
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
	// only valid entry
	if (isValidEntry(basename($tar))) {
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

if ($dir == "")
	unset($dir);

if (isset($dir)) {
	if (ereg("(\.\.)", $dir))
		unset($dir);
	else
		$dir = $dir."/";
}

if(!isset($dir))
	$dir = "";

$dirName = $cfg["path"].$dir;
$dirName = stripslashes($dirName);

// dir-check
if (!(@is_dir($dirName))) {
	// our dir is no dir but a file. use parent-directory.
	// setup default parent directory URL
	$parentURL = "index.php?iid=dir";
	// get the real parentURL
	if (preg_match("/^(.+)\/.+$/",$dir,$matches) == 1)
		$parentURL="index.php?iid=dir&dir=" . urlencode($matches[1]);
	header("Location: ".$parentURL);
	exit();
}


// create template-instance
$tmpl = getTemplateInstance($cfg["theme"], "dir.tmpl");

if (isset($dir)) {
	//setup default parent directory URL
	$parentURL = "index.php?iid=dir";
	//get the real parentURL
	if (preg_match("/^(.+)\/.+$/",$dir,$matches) == 1) {
		$parentURL="index.php?iid=dir&dir=" . urlencode($matches[1]);
	}
	$tmpl->setvar('parentURL', $parentURL);
	$tmpl->setvar('_BACKTOPARRENT', $cfg['_BACKTOPARRENT']);
}

// set some template-vars
$tmpl->setvar('dir', $dir);
$tmpl->setvar('parentURL', $parentURL);
$tmpl->setvar('parentURL', $parentURL);
$tmpl->setvar('enable_rename', $cfg["enable_rename"]);
$tmpl->setvar('enable_move', $cfg["enable_move"]);
$tmpl->setvar('enable_sfvcheck',  $cfg['enable_sfvcheck']);
$tmpl->setvar('enable_rar', $cfg["enable_rar"]);
$tmpl->setvar('enable_view_nfo', $cfg["enable_view_nfo"]);
$tmpl->setvar('enable_file_download', $cfg["enable_file_download"]);
$tmpl->setvar('package_type', $cfg["package_type"]);
$tmpl->setvar('enable_maketorrent', $cfg["enable_maketorrent"]);
$tmpl->setvar('_DELETE', $cfg['_DELETE']);
$tmpl->setvar('_DIR_REN_LINK', $cfg['_DIR_REN_LINK']);
$tmpl->setvar('_DIR_MOVE_LINK', $cfg['_DIR_MOVE_LINK']);
$tmpl->setvar('_ABOUTTODELETE', $cfg['_ABOUTTODELETE']);


// The following lines of code were suggested by Jody Steele jmlsteele@stfu.ca
// this is so only the owner of the file(s) or admin can delete
// only give admins and users who "own" this directory
// the ability to delete sub directories
if(IsAdmin($cfg["user"]) || preg_match("/^" . $cfg["user"] . "/",$dir))
	$tmpl->setvar('aclDelete', 1);
else
	$tmpl->setvar('aclDelete', 0);

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
	$dudir = $cfg['bin_du']." -ch ".$duArg." \"".$dirName."\" | ".$cfg['bin_grep']." \"total\"";
	$du = @shell_exec($dudir);
	$duTotal = @substr($du, 0, -7);
	$tmpl->setvar('_TDDU', $cfg['_TDDU']);
	$tmpl->setvar('duTotal', $duTotal);
} else {
	$tmpl->setvar('enable_dirstats', 0);
}

// read in entries
$entrys = array();
$handle = opendir($dirName);
while ($entry = readdir($handle))
	$entrys[] = $entry;
closedir($handle);
natsort($entrys);

// process entries and fill dir- + file-array
$dirlist = array();
$filelist = array();
$dirCtr = 0;
$fileCtr = 0;
foreach ($entrys as $entry) {
	// only valid entry
	if (isValidEntry($entry)) {
		if (@is_dir($dirName.$entry)) { // dir
			// dirstats
			if ($cfg['enable_dirstats'] == 1) {
				$dudir = @shell_exec($cfg['bin_du']." -sk -h ".$duArg." ".correctFileName($dirName.$entry));
				$dusize = @explode("\t", $dudir);
				$dusize = @array_shift($dusize);
				$arStat = @lstat($dirName.$entry);
				$timeStamp = @filemtime($dirName.$entry);
				$date = @date("m-d-Y h:i a", $timeStamp);
			} else {
				$dusize = 0;
				$date = "";
			}
			// sfv
			$sfvdir = "";
			$sfvsfv = "";
			if ($cfg['enable_sfvcheck'] == 1) {
				if(false !== ($sfv = findSFV($dirName.$entry))) {
					$is_sfv = 1;
					$sfvdir = $sfv['dir'];
					$sfvsfv = $sfv['sfv'];
				} else {
					$is_sfv = 0;
				}
			}
			// urlencode
			$urlencode1 = urlencode($dir.$entry);
			$urlencode2 = urlencode($dir);
			$urlencode3 = urlencode($entry);
			// bg
			if (($dirCtr % 2) == 0)
				$bg = $cfg["bgDark"];
			else
				$bg = $cfg["bgLight"];
			array_push($dirlist, array(
				'entry' => $entry,
				'urlencode1' => $urlencode1,
				'urlencode2' => $urlencode2,
				'urlencode3' => $urlencode3,
				'addslashes1' => addslashes($entry),
				'dusize' => $dusize,
				'date' => $date,
				'is_sfv' => $is_sfv,
				'sfvdir' => $sfvdir,
				'sfvsfv' => $sfvsfv,
				'bg' => $bg
				)
			);
			$dirCtr++;
		} else if (!@is_dir($dirName.$entry)) { // file
			$arStat = @lstat($dirName.$entry);
			$arStat[7] = ($arStat[7] == 0) ? @file_size($dirName.$entry ) : $arStat[7];
			$timeStamp = "";
			if (array_key_exists(10,$arStat))
				$timeStamp = @filemtime($dirName.$entry); // $timeStamp = $arStat[10];
			$fileSize = number_format(($arStat[7])/1024);
			// Code added by Remko Jantzen to assign an icon per file-type.
			// But when not available all stays the same.
			$image="themes/".$cfg['theme']."/images/time.gif";
			$imageOption="themes/".$cfg['theme']."/images/files/".getExtension($entry).".png";
			if (file_exists("./".$imageOption))
				$image = $imageOption;
			// dirstats
			$date = "";
			if ($cfg['enable_dirstats'] == 1)
				$date = @date("m-d-Y h:i a", $timeStamp);
			if ($cfg["enable_rar"] == 1) {
				$is_rar = 0;
				// R.D. - Display links for unzip/unrar
				if ((strpos($entry, '.rar') !== FALSE AND strpos($entry, '.Part') === FALSE) OR (strpos($entry, '.part01.rar') !== FALSE ) OR (strpos($entry, '.part1.rar') !== FALSE ))
					$is_rar = 1;
				if (strpos($dir.$entry, '.zip') !== FALSE)
					$is_rar = 2;
			} else {
				$is_rar = 0;
			}
			// nfo
			if ($cfg["enable_view_nfo"] && ((substr(strtolower($entry), -4 ) == ".nfo" ) || (substr(strtolower($entry), -4 ) == ".txt" ) || (substr(strtolower($entry), -4 ) == ".log" )))
				$is_nfo = 1;
			else
				$is_nfo = 0;
			// urlencode
			$urlencode1 = urlencode($dir.$entry);
			$urlencode2 = urlencode($dir);
			$urlencode3 = urlencode($entry);
			$urlencode4 = urlencode(addslashes($dir.$entry));
			// bg
			if (($fileCtr % 2) == 0)
				$bg = $cfg["bgDark"];
			else
				$bg = $cfg["bgLight"];
			array_push($filelist, array(
				'entry' => $entry,
				'urlencode1' => $urlencode1,
				'urlencode2' => $urlencode2,
				'urlencode3' => $urlencode3,
				'urlencode4' => $urlencode4,
				'addslashes1' => addslashes($entry),
				'image' => $image,
				'fileSize' => $fileSize,
				'date' => $date,
				'is_rar' => $is_rar,
				'is_nfo' => $is_nfo,
				'bg' => $bg
				)
			);
			$fileCtr++;
		}
	}
}
$tmpl->setloop('dirlist', $dirlist);
$tmpl->setloop('filelist', $filelist);

// define some things
$tmpl->setvar('head', getHead($cfg['_DIRECTORYLIST']));
$tmpl->setvar('driveSpaceBar', getDriveSpaceBar(getDriveSpace($cfg["path"])));
$tmpl->setvar('foot', getFoot());
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('iid', $_GET["iid"]);
// lets parse the hole thing
$tmpl->pparse();

?>