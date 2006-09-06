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
require_once('inc/config/config.db.php');

/*****************************************************************************
    TorrentFlux
    Torrent (n.) A violent or rapid flow; a strong current; a flood;
            as, a torrent vices; a torrent of eloquence.
    Flux    (n.) The act of flowing; a continuous moving on or passing by,
            as of a flowing stream; constant succession; change.
*****************************************************************************/

// url constants
$cfg["_URL_DEREFERRER"] = 'index.php?iid=dereferrer&u=';

// auth-constants
$cfg["_AUTH_BASIC_REALM"] = 'torrentflux-b4rt';

// CONSTANTS
$cfg["constants"] = array();
$cfg["constants"]["url_upload"] = "URL Upload";
$cfg["constants"]["reset_owner"] = "Reset Owner";
$cfg["constants"]["start_torrent"] = "Started Torrent";
$cfg["constants"]["queued_torrent"] = "Queued Torrent";
$cfg["constants"]["unqueued_torrent"] = "Removed from Queue";
$cfg["constants"]["QManager"] = "QManager";
$cfg["constants"]["fluxd"] = "fluxd";
$cfg["constants"]["access_denied"] = "ACCESS DENIED";
$cfg["constants"]["delete_torrent"] = "Delete Torrent";
$cfg["constants"]["fm_delete"] = "File Manager Delete";
$cfg["constants"]["fm_download"] = "File Download";
$cfg["constants"]["kill_transfer"] = "Kill Transfer";
$cfg["constants"]["file_upload"] = "File Upload";
$cfg["constants"]["error"] = "ERROR";
$cfg["constants"]["hit"] = "HIT";
$cfg["constants"]["update"] = "UPDATE";
$cfg["constants"]["admin"] = "ADMIN";
asort($cfg["constants"]);

// Add file extensions here that you will allow to be uploaded
$cfg["file_types_array"] = array("torrent","wget");

// Capture username
$cfg["user"] = "";
// Capture ip
@ $cfg["ip"] = $_SERVER['REMOTE_ADDR'];

// torrentflux-b4rt Version
$cfg["version"] = getLocalVersion();

/**
 * gets version-info of local version
 *
 * @return string with version-info
 */
function getLocalVersion() {
    $data = "";
    if($fileHandle = @fopen('.version','r')) {
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
    if (!(stristr($osString, 'linux') === false)) /* linux */
    	$cfg["_OS"] = 1;
    else if (!(stristr($osString, 'bsd') === false)) /* bsd */
    	$cfg["_OS"] = 2;
    //else if (!(stristr($osString, 'darwin') === false)) /* darwin */
    //    $cfg["_OS"] = 3;
    else /* well... linux ;) */
    	define('_OS',1);
} else { /* well... linux ;) */
	$cfg["_OS"] = 1;
}

?>