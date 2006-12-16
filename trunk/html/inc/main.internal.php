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

// start session
@session_start();

// unregister globals
if (@ini_get('register_globals')) {
	require_once('inc/functions/functions.compat.php');
	unregister_GLOBALS();
}

// cache
require_once('inc/main.cache.php');

// init
if (isset($_SESSION['user'])) {
	$currentUser = $_SESSION['user'];
	if (cacheIsSet($currentUser)) {
		// core classes
		require_once("inc/classes/CoreClasses.php");
		// core functions
		require_once('inc/functions/functions.core.php');
		// init cache
		cacheInit($currentUser);
		// initialize database
		dbInitialize();
		// Free space in MB
		$cfg["free_space"] = @disk_free_space($cfg["path"]) / (1048576);
	} else {
		// main.core
		require_once('inc/main.core.php');
	}
    $cfg["user"] = $currentUser;
} else {
	// main.core
	require_once('inc/main.core.php');
	// reset user
    $cfg["user"] = "";
    $currentUser = "";
}

// authenticate
if (isAuthenticated() == 1) {
	// check if we are locked
	if ($cfg["webapp_locked"] == 1) {
		// only superadmin can login when we are locked
		if (! IsSuperAdmin()) {
			@header('location: locked.php');
			exit();
		}
	}
} else {
	// try to auth with supplied credentials
	$credentials = getCredentials();
	if ($credentials !== false) {
		if (performAuthentication($credentials['username'], $credentials['password'], $credentials['md5pass']) == 1) {
			if (isAuthenticated() != 1) {
				@header('location: login.php');
				exit();
			}
			$currentUser = $cfg["user"];
			// check if we are locked
			if ($cfg["webapp_locked"] == 1) {
				// only superadmin can login when we are locked
				if (! IsSuperAdmin()) {
					@header('location: locked.php');
					exit();
				}
			}
		} else {
			@header('location: login.php');
			exit();
		}
	} else {
		@header('location: login.php');
		exit();
	}
}

// log the hit
AuditAction($cfg["constants"]["hit"], $_SERVER['PHP_SELF']);

// login-tasks
if (!(cacheIsSet($currentUser))) {

	// check for setup.php and upgrade.php
	if (@file_exists("setup.php") === true)
		@error("setup.php must be deleted", "setup.php", "Setup");
	if (@file_exists("upgrade.php") === true)
		@error("upgrade.php must be deleted", "upgrade.php", "Upgrade");

	// set admin-var
	$cfg['isAdmin'] = IsAdmin();

	// load some settings from users-table
	$sql = "SELECT hide_offline, theme, language_file FROM tf_users WHERE user_id=".$db->qstr($cfg["user"]);
	$recordset = $db->Execute($sql);
	if ($db->ErrorNo() != 0) dbError($sql);
	list ($cfg["hide_offline"], $cfg["theme"], $cfg["language_file"]) = $recordset->FetchRow();

	// Check for valid theme
	if (!ereg('^[^./][^/]*$', $cfg["theme"]) && strpos($cfg["theme"], "tf_standard_themes")) {
		AuditAction($cfg["constants"]["error"], "THEME VARIABLE CHANGE ATTEMPT: ".$cfg["theme"]." from ".$cfg["user"]);
		$cfg["theme"] = $cfg["default_theme"];
	}
	if (!is_dir("themes/".$cfg["theme"]))
		$cfg["theme"] = $cfg["default_theme"];

	// Check for valid language file
	if (!ereg('^[^./][^/]*$', $cfg["language_file"])) {
		AuditAction($cfg["constants"]["error"], "LANGUAGE VARIABLE CHANGE ATTEMPT: ".$cfg["language_file"]." from ".$cfg["user"]);
		$cfg["language_file"] = $cfg["default_language"];
	}
	if (!is_file("inc/language/".$cfg["language_file"]))
		$cfg["language_file"] = $cfg["default_language"];

	// load per user settings
	loadUserSettingsToConfig($cfg["uid"]);

	// theme
	require_once("themes/".$cfg["theme"]."/index.php");

	// load language
	loadLanguageFile($cfg["language_file"]);

	// set cache
	cacheSet($currentUser);

	// check main-directories.
	checkMainDirectories();

	// maintenance-run
	require_once("inc/classes/MaintenanceAndRepair.php");
	MaintenanceAndRepair::maintenance(false);

	// set session-settings
	$_SESSION['settings']['index_meta_refresh'] = ($cfg["enable_index_meta_refresh"] != 0) ? 1 : 0;
	$_SESSION['settings']['index_ajax_update'] = ($cfg["enable_index_ajax_update"] != 0) ? 1 : 0;

}

// drivespace-var
$cfg['driveSpace'] = getDriveSpace($cfg["path"]);

// free space-var
$cfg['freeSpaceFormatted'] = formatFreeSpace($cfg["free_space"]);

// vlib
require_once("inc/lib/vlib/vlibTemplate.php");

/*******************************************************************************
 *  TorrentFlux xfer Statistics hack
 *  blackwidow - matt@mattjanssen.net
 ******************************************************************************/
/*
	TorrentFlux xfer Statistics hack is free code; you can redistribute it
	and/or modify it under the terms of the GNU General Public License as
	published by the Free Software Foundation; either version 2 of the License,
	or (at your option) any later version.
*/

// if xfer is empty, insert a zero record for today
if ($cfg['enable_xfer'] == 1) {
	// xfer functions
	require_once('inc/functions/functions.xfer.php');
	// xfer-init
	$xferRecord = $db->GetRow("SELECT 1 FROM tf_xfer");
	if (empty($xferRecord)) {
		$rec = array('user_id'=>'', 'date'=>$db->DBDate(time()));
		$sTable = 'tf_xfer';
		$sql = $db->GetInsertSql($sTable, $rec);
		$db->Execute($sql);
	}
	$newday = !$db->GetOne('SELECT 1 FROM tf_xfer WHERE date = '.$db->DBDate(time()));
	$lastDate = $db->GetOne('SELECT date FROM tf_xfer ORDER BY date DESC');
}

/*******************************************************************************
 *  fluxd
 ******************************************************************************/

// Fluxd
Fluxd::initialize();

// Qmgr
FluxdServiceMod::initializeServiceMod('Qmgr');

?>