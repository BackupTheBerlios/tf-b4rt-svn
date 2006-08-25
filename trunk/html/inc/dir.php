<?php

/* $Id$ */

/*************************************************************
*  TorrentFlux - PHP Torrent Manager
*  www.torrentflux.com
**************************************************************/
/*
	This file is part of TorrentFlux.

	TorrentFlux is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	TorrentFlux is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with TorrentFlux; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// -----------------------------------------------------------------------------

// restricted file-entries
$restrictedFileEntries = array(
	"lost+found",
	"CVS",
	"Temporary Items",
	"Network Trash Folder",
	"TheVolumeSettingsFolder"
);

// -----------------------------------------------------------------------------

// check user path
checkUserPath();

// Setup some defaults if they are not set.
$del = getRequestVar('del');
$down = getRequestVar('down');
$tar = getRequestVar('tar');
$dir = stripslashes(urldecode(getRequestVar('dir')));

// -----------------------------------------------------------------------------

# create new template
if (!ereg('^[^./][^/]*$', $cfg["theme"])) {
	$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/dir.tmpl");
} else {
	$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/dir.tmpl");
}

// Are we to delete something?
if ($del != "") {
	// only valid entry
	if (isValidEntry(basename($del))) {
		$current = delDirEntry($del);
	} else {
		AuditAction($cfg["constants"]["error"], "ILLEGAL DELETE: ".$cfg['user']." tried to delete ".$del);
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

// Are we to download something?
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
				AuditAction($cfg["constants"]["error"], "File Not found for download: ".$cfg['user']." tried to download ".$down);
			}
		} else {
			AuditAction($cfg["constants"]["error"], "ILLEGAL DOWNLOAD: ".$cfg['user']." tried to download ".$down);
		}
	} else {
		AuditAction($cfg["constants"]["error"], "ILLEGAL DOWNLOAD: ".$cfg['user']." tried to download ".$down);
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

// Are we to download something as archive ?
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
				AuditAction($cfg["constants"]["error"], "Illegal download: ".$cfg['user']." tried to download ".$tar);
			}
		} else {
			AuditAction($cfg["constants"]["error"], "ILLEGAL TAR DOWNLOAD: ".$cfg['user']." tried to download ".$tar);
		}
	} else {
		AuditAction($cfg["constants"]["error"], "ILLEGAL TAR DOWNLOAD: ".$cfg['user']." tried to download ".$tar);
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

// -----------------------------------------------------------------------------

if ($dir == "")
	unset($dir);

if (isset($dir)) {
	if (ereg("(\.\.)", $dir))
		unset($dir);
	else
		$dir = $dir."/";
}

if(!isset($dir)) $dir = "";
$dirName = $cfg["path"].$dir;

// -----------------------------------------------------------------------------
// -----------------------------------------------------------------------------

$bgLight = $cfg["bgLight"];
$bgDark = $cfg["bgDark"];
$entrys = array();
$bg = $bgLight;
$dirName = stripslashes($dirName);
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
if (isset($dir)) {
	//setup default parent directory URL
	$parentURL = "index.php?iid=dir";
	//get the real parentURL
	if (preg_match("/^(.+)\/.+$/",$dir,$matches) == 1) {
		$parentURL="index.php?iid=dir&dir=" . urlencode($matches[1]);
	}
	$tmpl->setvar('parentURL', $parentURL);
	$tmpl->setvar('_BACKTOPARRENT', _BACKTOPARRENT);
}
$handle = opendir($dirName);
while($entry = readdir($handle))
	$entrys[] = $entry;
natsort($entrys);
$dirlist1 = array();
foreach($entrys as $entry) {
	// only valid entry
	if (isValidEntry($entry)) {
		if (@is_dir($dirName.$entry)) {
			$is_dir = 1;
			// Some Stats dir hack
			$enable_dirstats = $cfg['enable_dirstats'];
			if ($enable_dirstats == 1) {
				$dudir = shell_exec($cfg['bin_du']." -sk -h ".correctFileName($dirName.$entry));
				$dusize = explode("\t", $dudir);
				$arStat = @lstat($dirName.$entry);
				$timeStamp = @filemtime($dirName.$entry); //$timeStamp = $arStat[10];
				$dusize0 = $dusize[0];
				$date1 = @date("m-d-Y h:i a", $timeStamp);
			} else {
				$dusize0 = 0;
				$date1 = "";
			}
			$enable_rename = $cfg["enable_rename"];
			$enable_move = $cfg["enable_move"];
			$enable_sfvcheck = $cfg['enable_sfvcheck'];
			$sfvdir = "";
			$sfvsfv = "";
			if ($enable_sfvcheck == 1) {
				$enable_sfvcheck = 1;
				if(false !== ($sfv = findSFV($dirName.$entry))) {
					$enable_sfvcheck = 2;
					$sfvdir = $sfv[dir];
					$sfvsfv = $sfv[sfv];
				}
			} else {
				$enable_sfvcheck = 0;
			}
			// The following lines of code were suggested by Jody Steele jmlsteele@stfu.ca
			// this is so only the owner of the file(s) or admin can delete
			// only give admins and users who "own" this directory
			// the ability to delete sub directories
			$IsAdmin1 = 0;
			if(IsAdmin($cfg["user"]) || preg_match("/^" . $cfg["user"] . "/",$dir)) {
				//echo "<a href=\"dir.php?del=".urlencode($dir.$entry)."\" onclick=\"return ConfirmDelete('".addslashes($entry)."')\"><img src=\"images/delete_on.gif\" width=16 height=16 title=\""._DELETE."\" border=0></a>";
				/* --- Multi Delete Hack --- */
				/* checkbox appended to line */
				$IsAdmin1 = 1;
				/* --- Multi Delete Hack --- */
			}
			$urlencode1 = urlencode($dir.$entry);
			$package_type = $cfg["package_type"];
			$urlencode2 = urlencode($dir);
			$urlencode3 = urlencode($entry);
			array_push($dirlist1, array(
				'is_dir' => $is_dir,
				'urlencode1' => $urlencode1,
				'entry' => $entry,
				'bg' => $bg,
				'enable_dirstats' => $enable_dirstats,
				'dusize0' => $dusize0,
				'date1' => $date1,
				'enable_rename' => $enable_rename,
				'urlencode2' => $urlencode2,
				'urlencode3' => $urlencode3,
				'_DIR_REN_LINK' => _DIR_REN_LINK,
				'enable_move' => $enable_move,
				'_DIR_MOVE_LINK' => _DIR_MOVE_LINK,
				'enable_sfvcheck' => $enable_sfvcheck,
				'sfvdir' => $sfvdir,
				'sfvsfv' => $sfvsfv,
				'enable_maketorrent' => $cfg["enable_maketorrent"],
				'enable_file_download' => $cfg["enable_file_download"],
				'package_type' => $package_type,
				'IsAdmin1' => $IsAdmin1,
				'addslashes1' => addslashes($entry),
				'_DELETE' => _DELETE,
				)
			);
			if ($bg == $bgLight)
				$bg = $bgDark;
			else
				$bg = $bgLight;
		}
	}
}
$tmpl->setloop('dirlist1', $dirlist1);

closedir($handle);
$entrys = array();
$handle = opendir($dirName);
while($entry = readdir($handle))
	$entrys[] = $entry;
natsort($entrys);
$dirlist2 = array();
foreach($entrys as $entry) {
	// only valid entry
	if (isValidEntry($entry)) {
		if (!@is_dir($dirName.$entry)) {
			$no_dir = 1;
			$arStat = @lstat($dirName.$entry);
			$arStat[7] = ($arStat[7] == 0) ? @file_size($dirName.$entry ) : $arStat[7];
			$timeStamp = "";
			if (array_key_exists(10,$arStat))
				$timeStamp = @filemtime($dirName.$entry); // $timeStamp = $arStat[10];
			$fileSize = number_format(($arStat[7])/1024);
			// Code added by Remko Jantzen to assign an icon per file-type. But when not
			// available all stays the same.
			$image="images/time.gif";
			$imageOption="images/files/".getExtension($entry).".png";
			if (file_exists("./".$imageOption))
				$image = $imageOption;
			// Can users download files?
			// Yes, let them download
			$enable_file_download = $cfg["enable_file_download"];
			//
			$enable_dirstats = $cfg['enable_dirstats'];
			$date = "";
			if ($enable_dirstats == 1)
				$date = @date("m-d-Y h:i a", $timeStamp);
			$enable_rename = $cfg["enable_rename"];
			$enable_move = $cfg["enable_move"];
			$enable_rar = $cfg["enable_rar"];
			if ($enable_rar == 1) {
				$enable_rar2 = 0;
				// R.D. - Display links for unzip/unrar
				if(IsAdmin($cfg["user"]) || preg_match("/^" . $cfg["user"] . "/",$dir)) {
					if ((strpos($entry, '.rar') !== FALSE AND strpos($entry, '.Part') === FALSE) OR (strpos($entry, '.part01.rar') !== FALSE ) OR (strpos($entry, '.part1.rar') !== FALSE )) {
						$enable_rar2 = 1;
					}
					if (strpos($dir.$entry, '.zip') !== FALSE) {
						$enable_rar2 = 2;
					}
				}
			}
			// nfo
			if ($cfg["enable_view_nfo"] && ((substr(strtolower($entry), -4 ) == ".nfo" ) || (substr(strtolower($entry), -4 ) == ".txt" ) || (substr(strtolower($entry), -4 ) == ".log" ))) {
				$enable_view_nfo = 1;
			} else {
				$enable_view_nfo = 0;
			}
			$admin1 = 0;
			if(IsAdmin($cfg["user"]) || preg_match("/^" . $cfg["user"] . "/",$dir)) {
				/* --- Multi Delete Hack --- */
				/* checkbox appended to line */
				$admin1 = 1;
				/* --- Multi Delete Hack --- */
			}
			$urlencode1 = urlencode($dir.$entry);
			$urlencode2 = urlencode($dir);
			$urlencode3 = urlencode($entry);
			$urlencode4 = urlencode(addslashes($dir.$entry));
			array_push($dirlist2, array(
				'no_dir' => $no_dir,
				'bg' => $bg,
				'enable_file_download' => $enable_file_download,
				'urlencode1' => $urlencode1,
				'entry' => $entry,
				'image' => $image,
				'fileSize' => $fileSize,
				'enable_dirstats' => $enable_dirstats,
				'date' => $date,
				'enable_rename' => $enable_rename,
				'urlencode2' => $urlencode2,
				'urlencode3' => $urlencode3,
				'_DIR_REN_LINK' => _DIR_REN_LINK,
				'enable_move' => $enable_move,
				'_DIR_MOVE_LINK' => _DIR_MOVE_LINK,
				'enable_rar2' => $enable_rar2,
				'enable_view_nfo' => $enable_view_nfo,
				'urlencode4' => $urlencode4,
				'enable_maketorrent' => $cfg["enable_maketorrent"],
				'enable_file_download' => $cfg["enable_file_download"],
				'addslashes1' => addslashes($entry),
				'_DELETE' => _DELETE,
				'admin1' => $admin1,
				)
			);
			if ($bg == $bgLight)
				$bg = $bgDark;
			else
				$bg = $bgLight;
		}
	}
}
$tmpl->setloop('dirlist2', $dirlist2);
closedir($handle);

if ($cfg['enable_dirstats'] == 1) {
	$tmpl->setvar('enable_dirstats', 1);
	$cmd = $cfg['bin_du']." -ch \"".$dirName."\" | ".$cfg['bin_grep']." \"total\"";
	$du = shell_exec($cmd);
	$du2 = substr($du, 0, -7);
	$tmpl->setvar('_TDDU', _TDDU);
	$tmpl->setvar('du2', $du2);
} else {
	$tmpl->setvar('enable_dirstats', 0);
}

// ***************************************************************************
// ***************************************************************************
// Checks for the location of the users directory
// If it does not exist, then it creates it.
function checkUserPath() {
	global $cfg;
	// is there a user dir?
	if (!is_dir($cfg["path"].$cfg["user"])) {
		//Then create it
		mkdir($cfg["path"].$cfg["user"], 0777);
	}
}

// This function returns the extension of a given file.
// Where the extension is the part after the last dot.
// When no dot is found the noExtensionFile string is
// returned. This should point to a 'unknown-type' image
// time by default. This string is also returned when the
// file starts with an dot.
function getExtension($fileName) {
	$noExtensionFile="unknown"; // The return when no extension is found
	//Prepare the loop to find an extension
	$length = -1*(strlen($fileName)); // The maximum negative value for $i
	$i=-1; //The counter which counts back to $length
	//Find the last dot in an string
	while (substr($fileName,$i,1) != "." && $i > $length) {$i -= 1; }
	//Get the extension (with dot)
	$ext = substr($fileName,$i);
	//Decide what to return.
	if (substr($ext,0,1)==".") {$ext = substr($ext,((-1 * strlen($ext))+1)); } else {$ext = $noExtensionFile;}
	//Return the extension
	return strtolower($ext);
}

/**
 * checks if file/dir is valid.
 *
 * @param $fileEntry
 * @return true/false
 */
function isValidEntry($entry) {
	global $restrictedFileEntries;
	// check if dot-entry
	if (substr($entry, 0, 1) == ".")
		return false;
	// check if weirdo macos-entry
	if (substr($entry, 0, 1) == ":")
		return false;
	// check if in restricted array
	if (in_array($entry, $restrictedFileEntries))
		return false;
	// entry ok
	return true;
}

# define some things
$tmpl->setvar('head', getHead(_DIRECTORYLIST));
$tmpl->setvar('_ABOUTTODELETE', _ABOUTTODELETE);
$tmpl->setvar('driveSpaceBar', getDriveSpaceBar(getDriveSpace($cfg["path"])));
$tmpl->setvar('foot', getFoot());
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('index_page', $cfg["index_page"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('iid', $_GET["iid"]);
# lets parse the hole thing
$tmpl->pparse();
?>