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

// ADODB
require_once('inc/lib/adodb/adodb.inc.php');

/**
 * get ado-connection
 *
 * @return ado-connection
 */
function getdb() {
	global $cfg;
	// 2004-12-09 PFM: connect to database.
	$db = NewADOConnection($cfg["db_type"]);
	$db->Connect($cfg["db_host"], $cfg["db_user"], $cfg["db_pass"], $cfg["db_name"]);
	if(!$db)
		die ('Could not connect to database: '.$db->ErrorMsg().'<br>Check your database settings in the config.db.php file.');
	return $db;
}

/**
 * prints nice db-error
 *
 * @param $db
 * @param $sql
 */
function showError($db, $sql) {
	global $cfg, $tmpl;
	if($db->ErrorNo() != 0) {
		// vlib
		require_once("inc/lib/vlib/vlibTemplate.php");
		$tmpl = getTemplateInstance($cfg["default_theme"], "db.tmpl");
		$tmpl->setvar('error', 1);
		include("themes/default/index.php");
		$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
		$tmpl->setvar('main_bgcolor', $cfg["main_bgcolor"]);
		$tmpl->setvar('table_border_dk', $cfg["table_border_dk"]);
		$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
		$tmpl->setvar('body_data_bg', $cfg["body_data_bg"]);
		$tmpl->setvar('debug_sql', $cfg["debug_sql"]);
		$tmpl->setvar('sql', $sql);
		$tmpl->setvar('ErrorMsg', $db->ErrorMsg());
		$tmpl->pparse();
	}
}

?>