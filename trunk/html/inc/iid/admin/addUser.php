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

// create template-instance
$tmpl = getTemplateInstance($cfg["theme"], "admin/addUser.tmpl");

$newUser = strtolower($newUser);
if (IsUser($newUser)) {
	$tmpl->setvar('head', getHead($cfg['_ADMINISTRATION']));
	$tmpl->setvar('menu', getMenu());
	$tmpl->setvar('_TRYDIFFERENTUSERID', $cfg['_TRYDIFFERENTUSERID']);
	$tmpl->setvar('newUser', $newUser);
	$tmpl->setvar('_HASBEENUSED', $cfg['_HASBEENUSED']);
	$tmpl->setvar('foot', getFoot(true,true));
} else {
	addNewUser($newUser, $pass1, $userType);
	AuditAction($cfg["constants"]["admin"], $cfg['_NEWUSER'].": ".$newUser);
	header("location: index.php?iid=admin&op=CreateUser");
}
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('iid', $_GET["iid"]);
$tmpl->pparse();

?>