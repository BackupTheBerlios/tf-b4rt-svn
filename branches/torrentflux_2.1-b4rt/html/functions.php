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
@session_start("TorrentFlux");

if(isset($_SESSION['user']))
    $cfg["user"] = strtolower($_SESSION['user']);
else
    $cfg["user"] = "";

include_once('db.php');
include_once("settingsfunctions.php");


//******************************************************************************
// include functions
//******************************************************************************

// tf-functions
include_once('functions.tf.php');

// hacks-functions
include_once('functions.hacks.php');

// b4rt-functions
include_once('functions.b4rt.php');

//******************************************************************************

// Create Connection.
$db = getdb();

// load global settings
loadSettings();

// Free space in MB
$cfg["free_space"] = @disk_free_space($cfg["path"])/(1024*1024);

// Path to where the torrent meta files will be stored... usually a sub of $cfg["path"]
// also, not the '.' to make this a hidden directory
$cfg["torrent_file_path"] = $cfg["path"].".torrents/";

// authenticate
Authenticate();

// load per user settings
loadUserSettingsToConfig($cfg["uid"]);

//
include_once("language/".$cfg["language_file"]);
include_once("themes/".$cfg["theme"]."/index.php");
AuditAction($cfg["constants"]["hit"], $_SERVER['PHP_SELF']);
PruneDB();

// is there a stat and torrent dir?  If not then it will create it.
checkTorrentPath();

/*************************************************************
*  TorrentFlux xfer Statistics hack
*  blackwidow - matt@mattjanssen.net
**************************************************************/
/*
    TorrentFlux xfer Statistics hack is free code; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.
*/

//XFER: if xfer is empty, insert a zero record for today
if ($cfg['enable_xfer'] == 1) {
	$xferRecord = $db->GetRow("SELECT 1 FROM tf_xfer");
	if (empty($xferRecord)) {
		$rec = array('user_id'=>'', 'date'=>$db->DBDate(time()));
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