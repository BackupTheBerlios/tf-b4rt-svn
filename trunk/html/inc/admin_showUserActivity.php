<?php
/* $Id$ */
# create new template
if (!ereg('^[^./][^/]*$', $cfg["theme"])) {
	$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/admin_showUserActivity.tmpl");
}
else {
	$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/admin_showUserActivity.tmpl");
}

$tmpl->setvar('head', getHead(_ADMINUSERACTIVITY));
$tmpl->setvar('menu', getMenu());
$tmpl->setvar('Activity', getActivity($min, $user_id, $srchFile, $srchAction));
$tmpl->setvar('foot', getFoot(true,true));

$tmpl->pparse();
?>