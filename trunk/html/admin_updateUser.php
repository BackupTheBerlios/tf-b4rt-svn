<?php
/* $Id: admin_updateUser.php 102 2006-07-31 05:01:28Z msn_exploder $ */
$tmpl = new vlibTemplate("themes/".$cfg["default_theme"]."/tmpl/admin_updateUser.tmpl");
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
	header("location: admin.php");
}


$tmpl->pparse();
?>