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
	// create ado-object
    $db = &ADONewConnection($cfg["db_type"]);   
    // connect
    if ($cfg["db_pcon"])
    	@ $db->PConnect($cfg["db_host"], $cfg["db_user"], $cfg["db_pass"], $cfg["db_name"]);
    else
    	@ $db->Connect($cfg["db_host"], $cfg["db_user"], $cfg["db_pass"], $cfg["db_name"]);
    // check for error	
    if ($db->ErrorNo() != 0)
    	showErrorPage('Could not connect to database.<br>Check your database settings in the config.db.php file.');
    // return db-connection
	return $db;
}

/**
 * prints nice db-error
 *
 * @param $db
 * @param $sql
 */
function showError($db, $sql) {
	global $cfg;
	if ($db->ErrorNo() != 0) {
		// theme
		if (isset($cfg["theme"]))
			$theme = $cfg["theme"];
		else if (isset($cfg["default_theme"]))
			$theme = $cfg["default_theme"];
		else
			$theme = "default";
		// template
		require_once("themes/".$theme."/index.php");
		require_once("inc/lib/vlib/vlibTemplate.php");
		$tmpl = @ tmplGetInstance($theme, "page.db.tmpl");
		@ $tmpl->setvar('debug_sql', $cfg["debug_sql"]);
		@ $tmpl->setvar('sql', $sql);
		@ $tmpl->setvar('ErrorMsg', $db->ErrorMsg());
		@ $tmpl->pparse();
	}
}

?>