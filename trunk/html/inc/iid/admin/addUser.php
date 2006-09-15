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

$newUser = getRequestVar('newUser');
$pass1 = getRequestVar('pass1');
$userType = getRequestVar('userType');

// new user ?
$newUser = strtolower($newUser);
if (!(IsUser($newUser))) {
	addNewUser($newUser, $pass1, $userType);
	AuditAction($cfg["constants"]["admin"], $cfg['_NEWUSER'].": ".$newUser);
	header("location: index.php?iid=admin&op=CreateUser");
	exit();
}

// create template-instance
$tmpl = tmplGetInstance($cfg["theme"], "admin/addUser.tmpl");

// set vars
$tmpl->setvar('newUser', $newUser);
//
$tmpl->setvar('_TRYDIFFERENTUSERID', $cfg['_TRYDIFFERENTUSERID']);
$tmpl->setvar('_HASBEENUSED', $cfg['_HASBEENUSED']);
//
$tmpl->setvar('menu', getMenu());
tmplSetTitleBar("Administration - Add User");
$tmpl->setvar('foot', getFoot(true));
$tmpl->setvar('iid', $_GET["iid"]);

// parse template
$tmpl->pparse();

?>