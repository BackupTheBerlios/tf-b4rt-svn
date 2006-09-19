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

// core functions
require_once('inc/functions/functions.core.php');

// common functions
require_once('inc/functions/functions.common.php');

// ids of server-details
$cfg['fieldIDs']['server'] = array(
	"speedDown",          /*  0 */
	"speedUp",            /*  1 */
	"speedTotal",         /*  2 */
	"cons",               /*  3 */
	"freeSpace",          /*  4 */
	"loadavg",            /*  5 */
	"running",            /*  6 */
	"queued",             /*  7 */
	"speedDownPercent",   /*  8 */
	"speedUpPercent",     /*  9 */
	"driveSpacePercent"   /* 10 */
);

// ids of transfer-details
$cfg['fieldIDs']['transfer'] = array(
	"running",            /*  0 */
	"speedDown",          /*  1 */
	"speedUp",            /*  2 */
	"downCurrent",        /*  3 */
	"upCurrent",          /*  4 */
	"downTotal",          /*  5 */
	"upTotal",            /*  6 */
	"percentDone",        /*  7 */
	"sharing",            /*  8 */
	"eta",                /*  9 */
	"seeds",              /* 10 */
	"peers",              /* 11 */
	"cons"                /* 12 */
);

// ids of xfer-details
$cfg['fieldIDs']['xfer'] = array(
	"xferGlobalTotal",    /* 0 */
	"xferGlobalMonth",    /* 1 */
	"xferGlobalWeek",     /* 2 */
	"xferGlobalDay",      /* 3 */
	"xferUserTotal",      /* 4 */
	"xferUserMonth",      /* 5 */
	"xferUserWeek",       /* 6 */
	"xferUserDay"         /* 7 */
);

// constants
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
if ($fileHandle = @fopen('.version','r')) {
	$data = "";
    while (!@feof($fileHandle))
        $data .= @fgets($fileHandle, 64);
    @fclose ($fileHandle);
    $cfg["version"] = trim($data);
} else {
  $cfg["version"] =  "Error getting local Version";
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

// db-config
require_once('inc/config/config.db.php');

// db
require_once('inc/db.php');

// Create Connection.
$db = getdb();

// load global settings
loadSettings('tf_settings');

// load stats-settings
loadSettings('tf_settings_stats');

// Path to where the meta files will be stored... usually a sub of $cfg["path"]
$cfg["transfer_file_path"] = $cfg["path"].".transfers/";

// Free space in MB
$cfg["free_space"] = @disk_free_space($cfg["path"]) / (1048576);

?>