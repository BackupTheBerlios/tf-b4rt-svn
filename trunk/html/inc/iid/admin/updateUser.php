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

$user_id = getRequestVar('user_id');
$org_user_id = getRequestVar('org_user_id');
$pass1 = getRequestVar('pass1');
$userType = getRequestVar('userType');
$hideOffline = getRequestVar('hideOffline');
$user_id = strtolower($user_id);

if (!(IsUser($user_id) && ($user_id != $org_user_id))) {
	// Admin is changing id or password through edit screen
	if (($user_id == $cfg["user"] || $cfg["user"] == $org_user_id) && $pass1 != "") {
		// this will expire the user
		$_SESSION['user'] = md5($cfg["pagetitle"]);
	}
	updateThisUser($user_id, $org_user_id, $pass1, $userType, $hideOffline);
	AuditAction($cfg["constants"]["admin"], $cfg['_EDITUSER'].": ".$user_id);
	header("location: index.php?iid=admin");
	exit();
}

// create template-instance
$tmpl = getTemplateInstance($cfg["theme"], "admin/updateUser.tmpl");

// set vars
$tmpl->setvar('user_id', $user_id);
$tmpl->setvar('org_user_id', $org_user_id);
//
$tmpl->setvar('_TRYDIFFERENTUSERID', $cfg['_TRYDIFFERENTUSERID']);
$tmpl->setvar('_HASBEENUSED', $cfg['_HASBEENUSED']);
$tmpl->setvar('_RETURNTOEDIT', $cfg['_RETURNTOEDIT']);
//
$tmpl->setvar('menu', getMenu());
$tmpl->setvar('head', getHead($cfg['_ADMINISTRATION']));
$tmpl->setvar('foot', getFoot(true));
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('iid', $_GET["iid"]);

// parse template
$tmpl->pparse();

?>