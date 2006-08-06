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

// will need include of config.php
require_once('config.php');
require_once('lib/adodb/adodb.inc.php');
require_once("lib/vlib/vlibTemplate.php");

$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/db.tmpl");

function getdb() {
	global $cfg;
	// 2004-12-09 PFM: connect to database.
	$db = NewADOConnection($cfg["db_type"]);
	$db->Connect($cfg["db_host"], $cfg["db_user"], $cfg["db_pass"], $cfg["db_name"]);
	if(!$db)
		die ('Could not connect to database: '.$db->ErrorMsg().'<br>Check your database settings in the config.php file.');
	return $db;
}

function showError($db, $sql) {
	global $cfg;
	if($db->ErrorNo() != 0) {
		$tmpl->setvar('error', 1);
		include("themes/matrix/index.php");
		$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
		$tmpl->setvar('main_bgcolor', $cfg["main_bgcolor"]);
		$tmpl->setvar('table_border_dk', $cfg["table_border_dk"]);
		$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
		$tmpl->setvar('body_data_bg', $cfg["body_data_bg"]);
		$tmpl->setvar('debug_sql', $cfg["debug_sql"]);
		$tmpl->setvar('sql', $sql);
		$tmpl->setvar('ErrorMsg', $db->ErrorMsg());
	}
}
$tmpl->pparse();
?>