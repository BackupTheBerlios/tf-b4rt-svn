<?php
/* $Id: admin_default.php 102 2006-07-31 05:01:28Z msn_exploder $ */
# create new template
if (!ereg('^[^./][^/]*$', $cfg["theme"])) {
	$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/admin_default.tmpl");
}
else {
	$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/admin_default.tmpl");
}

$tmpl->setvar('head', getHead(_ADMINISTRATION));
$tmpl->setvar('menu', getMenu());
$tmpl->setvar('userSection', getUserSection());
$tmpl->setvar('activity', getActivity($min));
$tmpl->setvar('foot', getFoot(true,true));

$tmpl->pparse();
?>