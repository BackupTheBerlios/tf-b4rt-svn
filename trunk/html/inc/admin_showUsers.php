<?php
/* $Id$ */
# create new template
if (!ereg('^[^./][^/]*$', $cfg["theme"])) {
	$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/admin_showUsers.tmpl");
} else {
	$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/admin_showUsers.tmpl");
}

$tmpl->setvar('head', getHead(_ADMINISTRATION));
$tmpl->setvar('menu', getMenu());
$tmpl->setvar('userSection', getUserSection());
$tmpl->setvar('foot', getFoot(true,true));
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('main_bgcolor', $cfg["main_bgcolor"]);
$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
$tmpl->setvar('enable_xfer', $cfg["enable_xfer"]);
$tmpl->setvar('page', $_GET["page"]);
$tmpl->pparse();
?>