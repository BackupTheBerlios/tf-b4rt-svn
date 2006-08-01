<?php
/* $Id: admin_addUser.php 102 2006-07-31 05:01:28Z msn_exploder $ */

# create new template
$tmpl = new vlibTemplate("themes/".$cfg["default_theme"]."/tmpl/admin_addUser.tmpl");
$newUser = strtolower($newUser);
if (IsUser($newUser)) {
	$tmpl->setvar('DisplayHead', DisplayHead(_ADMINISTRATION));
	$tmpl->setvar('displayMenu', displayMenu());
	$tmpl->setvar('_TRYDIFFERENTUSERID', _TRYDIFFERENTUSERID);
	$tmpl->setvar('newUser', $newUser);
	$tmpl->setvar('_HASBEENUSED', _HASBEENUSED);
	$tmpl->setvar('DisplayFoot', DisplayFoot(true,true));
} else {
	addNewUser($newUser, $pass1, $userType);
	AuditAction($cfg["constants"]["admin"], _NEWUSER.": ".$newUser);
	header("location: admin.php?op=CreateUser");
}
$tmpl->pparse();
?>