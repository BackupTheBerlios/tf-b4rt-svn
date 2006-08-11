<?php
/* $Id$ */

# create new template
if (!ereg('^[^./][^/]*$', $cfg["theme"])) {
	$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/admin_editRSS.tmpl");
}
else {
	$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/admin_editRSS.tmpl");
}
$tmpl->setvar('head', getHead("Administration - RSS"));
$tmpl->setvar('menu', getMenu());
$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
$tmpl->setvar('table_data_bg', $cfg["table_data_bg"]);
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('_FULLURLLINK', _FULLURLLINK);
$tmpl->setvar('_UPDATE', _UPDATE);
$tmpl->setvar('_DELETE', _DELETE);

$arLinks = GetRSSLinks();
$arRid = Array_Keys($arLinks);
$inx = 0;
$link_rss = array();
foreach($arLinks as $link) {
	$rid = $arRid[$inx++];
	array_push($link_rss, array(
		'true' => true,
		'rid' => $rid,
		'link' => $link,
		)
	);
}
$tmpl->setloop('link_rss', $link_rss);
$tmpl->setvar('foot', getFoot(true,true));

$tmpl->pparse();
?>