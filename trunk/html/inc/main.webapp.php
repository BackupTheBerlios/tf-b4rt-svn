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

// vlib
require_once("lib/vlib/vlibTemplate.php");

// main.common
require_once('inc/main.common.php');

/* -------------------------------------------------------------------------- */

# start session
@session_start();

// user
if(isset($_SESSION['user']))
    $cfg["user"] = strtolower($_SESSION['user']);
else
    $cfg["user"] = "";

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

// prune db
PruneDB();

// is there a stat and torrent dir?  If not then it will create it.
checkTorrentPath();

// load per user settings
loadUserSettingsToConfig($cfg["uid"]);

// language and theme
require_once("inc/language/".$cfg["language_file"]);
require_once("themes/".$cfg["theme"]."/index.php");

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

//XFER: create tf_xfer if it doesn't already exist. if xfer is empty, insert a zero record for today
if ($cfg['enable_xfer'] == 1) {
	if (($xferRecord = $db->GetRow("SELECT 1 FROM tf_xfer")) === false) {
		if ($db->Execute('CREATE TABLE tf_xfer (user varchar(32) NOT NULL default "", date date NOT NULL default "0000-00-00", download bigint(20) NOT NULL default "0", upload bigint(20) NOT NULL default "0", PRIMARY KEY (user,date))') === false) {
			if (IsAdmin()) echo '<b>ERROR:</b> tf_xfer table is missing. Trying to create the table for you <b>FAILED</b>.<br>Create using:<br>CREATE TABLE tf_xfer (<br>user varchar(32) NOT NULL default "",<br>date date NOT NULL default "0000-00-00",<br>download bigint(20) NOT NULL default "0",<br>upload bigint(20) NOT NULL default "0",<br>PRIMARY KEY  (user,date)<br>);<br>';
			else echo '<b>ERROR:</b> Contact an admin: tf_xfer table is missing.<br>';
			$cfg['enable_xfer'] = 0;
		} else {
			$rec = array('user'=>'', 'date'=>$db->DBDate(time()));
			$sTable = 'tf_xfer';
			$sql = $db->GetInsertSql($sTable, $rec);
			$db->Execute($sql);
			showError($db,$sql);
		}
	} elseif (empty($xferRecord)) {
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

?>