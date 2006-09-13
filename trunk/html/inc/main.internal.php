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

// init
if (isset($_SESSION['user'])) {
	$currentUser = strtolower($_SESSION['user']);
	if (isset($_SESSION['cache'][$currentUser])) {
		// set cfg-array from session-cache
		$cfg = $_SESSION['cache'][$currentUser];
		// core functions
		require_once('inc/functions/functions.core.php');
		// db
		require_once('inc/db.php');
		// Create Connection.
		$db = getdb();
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
}

// authenticate
if (isAuthenticated() == 1) {
	// check if we are locked
	if ($cfg["webapp_locked"] == 1) {
	// only superadmin can login when we are locked
		if (! IsSuperAdmin()) {
			header('location: locked.php');
			exit();
		}
	}
} else {
	// try to auth with supplied credentials
	$credentials = getCredentials();
	if (isset($credentials)) {
		if (performAuthentication($credentials['username'],$credentials['password']) == 1) {
			if (isAuthenticated() != 1) {
				header('location: login.php');
				exit();
			}
			$currentUser = $cfg["user"];
		} else {
			header('location: login.php');
			exit();
		}
	} else {
		header('location: login.php');
		exit();
	}
}

// log the hit
AuditAction($cfg["constants"]["hit"], $_SERVER['PHP_SELF']);

// load last things to config and cache if not already done.
// prune db and perform path-check
if (!(isset($_SESSION['cache'][$currentUser]))) {

	// load per user settings
	loadUserSettingsToConfig($cfg["uid"]);

	// theme
	require_once("themes/".$cfg["theme"]."/index.php");

	// load language
	loadLanguageFile($cfg["language_file"]);

	// add cfg-array to session-cache
	$_SESSION['cache'][$currentUser] = $cfg;

	// prune db
	PruneDB();

	// is there a stat and meta-file dir?  If not then it will create it.
	checkDirectory($cfg["transfer_file_path"], 0777);

}

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

// create tf_xfer if it doesn't already exist. if xfer is empty,
// insert a zero record for today
if ($cfg['enable_xfer'] == 1) {
	// xfer functions
	require_once('inc/functions/functions.xfer.php');
	// xfer-init
	$xferRecord = $db->GetRow("SELECT 1 FROM tf_xfer");
	if (empty($xferRecord)) {
		$rec = array('user'=>'', 'date'=>$db->DBDate(time()));
		$sTable = 'tf_xfer';
		$sql = $db->GetInsertSql($sTable, $rec);
		$db->Execute($sql);
		showError($db,$sql);
	}
	$sql = 'SELECT 1 FROM tf_xfer WHERE date = '.$db->DBDate(time());
	$newday = !$db->GetOne($sql);
	showError($db,$sql);
	$sql = 'SELECT date FROM tf_xfer ORDER BY date DESC';
	$lastDate = $db->GetOne($sql);
	showError($db,$sql);
}

/*******************************************************************************
 *  fluxd
 ******************************************************************************/
/*
 * allways use this instance of Fluxd in included pages.
 * allways use this boolean for "is fluxd up and running" in included pages.
 * allways use this instance of FluxdQmgr in included pages.
 * allways use this boolean for "is queue up and running" in included pages.
 */
require_once("inc/classes/Fluxd.php");
require_once("inc/classes/Fluxd.ServiceMod.php");
$fluxd = new Fluxd(serialize($cfg));
$fluxdRunning = $fluxd->isFluxdRunning();
$fluxdQmgr = null;
$queueActive = false;
if($cfg["fluxd_Qmgr_enabled"] == 1) {
	if ($fluxd->modState('Qmgr') == 1) {
		$fluxdQmgr = FluxdServiceMod::getFluxdServiceModInstance($cfg, $fluxd, 'Qmgr');
		$queueActive = true;
	}
}

/*******************************************************************************
 *  DEBUG
 ******************************************************************************/
if ($cfg["version"] != "svn") {
	// turn off error_reporting
	error_reporting(0);
} else {
	// turn on error_reporting
	error_reporting(E_ALL);
}

?>