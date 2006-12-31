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

/******************************************************************************/
// YOUR DATABASE CONNECTION INFORMATION
// is now in the file config.db.php !
/******************************************************************************/
// we want to die when db-conf-file is missing. so lets use require on this
require_once('config.db.php');

/*****************************************************************************
    TorrentFlux
    Torrent (n.) A violent or rapid flow; a strong current; a flood;
            as, a torrent vices; a torrent of eloquence.
    Flux    (n.) The act of flowing; a continuous moving on or passing by,
            as of a flowing stream; constant succession; change.
*****************************************************************************/

// ***************************************************************************
// ***************************************************************************
// DO NOT Edit below this line unless you know what you're doing.
// ***************************************************************************
// ***************************************************************************

$cfg["pagetitle"] = "torrentflux-b4rt";

// CONSTANTS
$cfg["constants"] = array();
$cfg["constants"]["url_upload"] = "URL Upload";
$cfg["constants"]["reset_owner"] = "Reset Owner";
$cfg["constants"]["start_torrent"] = "Started Torrent";
$cfg["constants"]["queued_torrent"] = "Queued Torrent";
$cfg["constants"]["unqueued_torrent"] = "Removed from Queue";
$cfg["constants"]["QManager"] = "QManager";
$cfg["constants"]["access_denied"] = "ACCESS DENIED";
$cfg["constants"]["delete_torrent"] = "Delete Torrent";
$cfg["constants"]["fm_delete"] = "File Manager Delete";
$cfg["constants"]["fm_download"] = "File Download";
$cfg["constants"]["kill_torrent"] = "Kill Torrent";
$cfg["constants"]["file_upload"] = "File Upload";
$cfg["constants"]["error"] = "ERROR";
$cfg["constants"]["hit"] = "HIT";
$cfg["constants"]["update"] = "UPDATE";
$cfg["constants"]["admin"] = "ADMIN";
$cfg["constants"]["debug"] = "DEBUG";

asort($cfg["constants"]);

// Add file extensions here that you will allow to be uploaded
$cfg["file_types_array"] = array("torrent","url");

// Capture username
$cfg["user"] = "";
// Capture ip
@ $cfg["ip"] = $_SERVER['REMOTE_ADDR'];

//XFER: LANGUAGE CONSTANTS
define('_TOTALXFER','Total Transfer');
define('_MONTHXFER','Month\'s Transfer');
define('_WEEKXFER','Week\'s Transfer');
define('_DAYXFER','Today\'s Transfer');
define('_XFERTHRU','Transfer thru');
define('_REMAINING','Remaining');
define('_TOTALSPEED','Total Speed');
define('_SERVERXFERSTATS','Server Transfer Stats');
define('_YOURXFERSTATS','Your Transfer Stats');
define('_OTHERSERVERSTATS','Other Server Stats');
define('_TOTAL','Total');
define('_DOWNLOAD','Download');
define('_MONTHSTARTING','Month Starting');
define('_WEEKSTARTING','Week Starting');
define('_DAY','Day');
define('_XFER','transfer');
define('_XFER_USAGE','Transfer Usage');
define('_QUEUEMANAGER','Queue Manager');

// multiple Upload
define('_MULTIPLE_UPLOAD','Multiple Upload');

# Some Stats dir hack
define('_TDDU','Directory Size:');

// Link Mod
define("_FULLSITENAME", "Site Name");

// Move Hack
define('_MOVE_STRING','Move File/Folder to: ');
define('_DIR_MOVE_LINK', 'Move File/Folder');
define('_MOVE_FILE', 'File/Folder: ');
define('_MOVE_FILE_TITLE', 'Move Data...');

// Rename Hack
define('_REN_STRING','Rename File/Folder to: ');
define('_DIR_REN_LINK', 'Rename File/Folder');
define('_REN_FILE', 'File/Folder: ');
define('_REN_DONE', 'Done!');
define('_REN_ERROR', 'An error accured, please try again!');
define('_REN_ERR_ARG', 'Wrong argument supplied!');
define('_REN_TITLE', 'Rename Folder');

// TorrentFlux Version
$cfg["version"] = getLocalVersion();

// string-constants
define('_ID_PORT','Port');
define('_ID_PORTS','Ports');
define('_ID_CONNECTIONS','Connections');
define('_ID_HOST','Host');
define('_ID_HOSTS','Hosts');
define('_ID_MRTG','Graph');

// url constants
define('_URL_DEREFERRER','dereferrer.php?u=');

// auth-constants
define('_AUTH_BASIC_REALM','TorrentFlux');

// public stats (xml|rss)
define('_PUBLIC_STATS', 0);

/**
 * gets version-info of local version
 *
 * @return string with version-info
 */
function getLocalVersion() {
    $data = "";
    if($fileHandle = @fopen('version','r')) {
        while (!@feof($fileHandle))
            $data .= @fgets($fileHandle, 4096);
        @fclose ($fileHandle);
    } else {
      return "Error getting local Version";
    }
    return trim($data);
}

// get os
$osString = php_uname('s');
if (isset($osString)) {
    //$osFound = 0;
    if (!(stristr($osString, 'linux') === false)) { // linux
        define('_OS', 1);
        //$osFound++;
    } else if (!(stristr($osString, 'bsd') === false)) { // bsd
        define('_OS', 2);
        //$osFound++;
    } else { // well... linux
    	define('_OS', 1);
    }
    //if ($osFound == 0)
    //    die("unkown os\n");
}

?>