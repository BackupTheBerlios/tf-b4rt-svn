<?php
/* $Id$ */

# create new template
if (!ereg('^[^./][^/]*$', $cfg["theme"])) {
	$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/admin_addUser.tmpl");
}
else {
	$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/admin_addUser.tmpl");
}
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
	header("location: index.php?page=admin&op=CreateUser");
}
$tmpl->pparse();
?>