<?php
/* $Id: admin_addUser.php 102 2006-07-31 05:01:28Z msn_exploder $ */

# create new template
$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/admin_addUser.tmpl");
$newUser = strtolower($newUser);
if (IsUser($newUser)) {
	$tmpl->setvar('head', getHead(_ADMINISTRATION));
	$tmpl->setvar('menu', getMenu());
	$tmpl->setvar('_TRYDIFFERENTUSERID', _TRYDIFFERENTUSERID);
	$tmpl->setvar('newUser', $newUser);
	$tmpl->setvar('_HASBEENUSED', _HASBEENUSED);
	$tmpl->setvar('foot', getFoot(true,true));
} else {
	addNewUser($newUser, $pass1, $userType);
	AuditAction($cfg["constants"]["admin"], _NEWUSER.": ".$newUser);
	header("location: admin.php?op=CreateUser");
}
$tmpl->pparse();
?>