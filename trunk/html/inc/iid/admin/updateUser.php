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

# create new template
if ((strpos($cfg['theme'], '/')) === false)
	$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/admin/updateUser.tmpl");
else
	$tmpl = new vlibTemplate("themes/tf_standard_themes/tmpl/admin/updateUser.tmpl");

$user_id = strtolower($user_id);
if (IsUser($user_id) && ($user_id != $org_user_id)) {
	$tmpl->setvar('head', getHead(_ADMINISTRATION));
	$tmpl->setvar('menu', getMenu());
	$tmpl->setvar('_TRYDIFFERENTUSERID', _TRYDIFFERENTUSERID);
	$tmpl->setvar('user_id', $user_id);
	$tmpl->setvar('_HASBEENUSED', _HASBEENUSED);
	$tmpl->setvar('org_user_id', $org_user_id);
	$tmpl->setvar('_RETURNTOEDIT', _RETURNTOEDIT);
	$tmpl->setvar('foot', getFoot(true,true));
} else {
	// Admin is changing id or password through edit screen
	if(($user_id == $cfg["user"] || $cfg["user"] == $org_user_id) && $pass1 != "") {
		// this will expire the user
		$_SESSION['user'] = md5($cfg["pagetitle"]);
	}
	updateThisUser($user_id, $org_user_id, $pass1, $userType, $hideOffline);
	AuditAction($cfg["constants"]["admin"], _EDITUSER.": ".$user_id);
	header("location: index.php?iid=admin");
}
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('iid', $_GET["iid"]);
$tmpl->pparse();

?>