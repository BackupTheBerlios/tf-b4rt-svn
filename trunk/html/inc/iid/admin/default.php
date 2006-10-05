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

// prevent direct invocation
if (!isset($cfg['user'])) {
	@ob_end_clean();
	header("location: ../../../index.php");
	exit();
}

/******************************************************************************/

// create template-instance
$tmpl = tmplGetInstance($cfg["theme"], "page.admin.default.tmpl");

// set vars
$tmpl->setvar('enable_xfer', $cfg["enable_xfer"]);
tmplSetTitleBar($cfg['_ADMINISTRATION']);
tmplSetAdminMenu();
$tmpl->setvar('db_type', $cfg["db_type"]);
$tmpl->setvar('db_host', $cfg["db_host"]);
$tmpl->setvar('db_name', $cfg["db_name"]);
$tmpl->setvar('db_user', $cfg["db_user"]);
if ($cfg["db_pcon"])
	$tmpl->setvar('db_pcon', "true");
else
	$tmpl->setvar('db_pcon', "false");
tmplSetFoot();

// parse template
$tmpl->pparse();

?>