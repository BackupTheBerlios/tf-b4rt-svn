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

// constants
$cfg["constants"] = array();
$cfg["constants"]["url_upload"] = "URL Upload";
$cfg["constants"]["reset_owner"] = "Reset Owner";
$cfg["constants"]["start_torrent"] = "Started Transfer";
$cfg["constants"]["stop_transfer"] = "Stopped Transfer";
$cfg["constants"]["queued_torrent"] = "Queued Transfer";
$cfg["constants"]["unqueued_torrent"] = "Removed from Queue";
$cfg["constants"]["QManager"] = "QManager";
$cfg["constants"]["fluxd"] = "fluxd";
$cfg["constants"]["access_denied"] = "ACCESS DENIED";
$cfg["constants"]["delete_torrent"] = "Delete Transfer";
$cfg["constants"]["fm_delete"] = "File Manager Delete";
$cfg["constants"]["fm_download"] = "File Download";
$cfg["constants"]["kill_transfer"] = "Kill Transfer";
$cfg["constants"]["file_upload"] = "File Upload";
$cfg["constants"]["error"] = "ERROR";
$cfg["constants"]["hit"] = "HIT";
$cfg["constants"]["update"] = "UPDATE";
$cfg["constants"]["admin"] = "ADMIN";
$cfg["constants"]["debug"] = "DEBUG";
asort($cfg["constants"]);

// Add file extensions here that you will allow to be uploaded
$cfg["file_types_array"] = array("torrent","wget");

// Capture username
$cfg["user"] = "";

// Capture ip
@ $cfg["ip"] = $_SERVER['REMOTE_ADDR'];

// torrentflux-b4rt Version
if (is_file('version.php')) {
	require_once('version.php');
	$cfg["version"] = _VERSION;
} else {
	$cfg["version"] =  "Error getting local Version";
}

// get os
$osString = @php_uname('s');
if (isset($osString)) {
    if (!(stristr($osString, 'linux') === false)) /* linux */
    	$cfg["_OS"] = 1;
    elseif (!(stristr($osString, 'bsd') === false)) /* bsd */
    	$cfg["_OS"] = 2;
    elseif (!(stristr($osString, 'darwin') === false)) /* darwin */
        $cfg["_OS"] = 2;
    else /* well... linux ;) */
    	$cfg["_OS"] = 1;
} else { /* well... linux ;) */
	$cfg["_OS"] = 1;
}

// db
if (is_file('inc/config/config.db.php')) {

	// db-config
	require_once('inc/config/config.db.php');

	// db
	require_once('inc/db.php');

	// initialize database
	initializeDatabase();

	// load global settings
	loadSettings('tf_settings');

	// load stats-settings
	loadSettings('tf_settings_stats');

	// Path to where the meta files will be stored... usually a sub of $cfg["path"]
	$cfg["transfer_file_path"] = $cfg["path"].".transfers/";

	// Free space in MB
	$cfg["free_space"] = @disk_free_space($cfg["path"]) / (1048576);

} else {

	// die in cli-mode, send redir in webapp
    if (isset($argv)) {
    	die("Error.\nCould not find database-settings-file config.db.php.\n");
    } else {
		// redir to login ... (which may redir to upgrade.php / setup.php)
		@ob_end_clean();
		@header("location: login.php");
		exit();
    }
}

?>