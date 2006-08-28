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

// Start Session and grab user
session_start("TorrentFlux");

// user
if(isset($_SESSION['user']))
    $cfg["user"] = strtolower($_SESSION['user']);
else
    $cfg["user"] = "";

// functions
require_once('inc/functions/functions.php');

// Create Connection.
require_once('inc/db.php');
$db = getdb();

// load global settings
loadSettings();

// Free space in MB
$cfg["free_space"] = @disk_free_space($cfg["path"]) / (1048576);

// Path to where the torrent meta files will be stored... usually a sub of $cfg["path"]
// also, not the '.' to make this a hidden directory
$cfg["torrent_file_path"] = $cfg["path"].".torrents/";

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

// load per user settings
loadUserSettingsToConfig($cfg["uid"]);

// language and theme
require_once("inc/language/".$cfg["language_file"]);
require_once("themes/".$cfg["theme"]."/index.php");

// log the hit
AuditAction($cfg["constants"]["hit"], $_SERVER['PHP_SELF']);

// prune db
PruneDB();

// is there a stat and torrent dir?  If not then it will create it.
checkTorrentPath();


/*************************************************************
*  TorrentFlux xfer Statistics hack
*  blackwidow - matt@mattjanssen.net
**************************************************************/
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
      $rec = array('user'=>'',
                   'date'=>$db->DBDate(time()));
      $sTable = 'tf_xfer';
      $sql = $db->GetInsertSql($sTable, $rec);
      $db->Execute($sql);
      showError($db,$sql);
    }
  } elseif (empty($xferRecord)) {
    $rec = array('user'=>'',
                 'date'=>$db->DBDate(time()));
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


?>