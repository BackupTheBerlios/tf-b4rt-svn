<?php
/* $Id: admin_showUserActivity.php 102 2006-07-31 05:01:28Z msn_exploder $ */
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