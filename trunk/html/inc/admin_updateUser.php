<?php
/* $Id$ */
# create new template
if (!ereg('^[^./][^/]*$', $cfg["theme"])) {
	$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/admin_updateUser.tmpl");
}
else {
	$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/admin_updateUser.tmpl");
}
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
$tmpl->setvar('index_page', $cfg["index_page"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('page', $_GET["page"]);
$tmpl->pparse();
?>