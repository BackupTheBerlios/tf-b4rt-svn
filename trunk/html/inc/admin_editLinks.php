<?php
/* $Id$ */

# create new template
if (!ereg('^[^./][^/]*$', $cfg["theme"])) {
	$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/admin_editLinks.tmpl");
}
else {
	$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/admin_editLinks.tmpl");
}
$tmpl->setvar('head', getHead(_ADMINEDITLINKS));
$tmpl->setvar('menu', getMenu());
$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
$tmpl->setvar('table_data_bg', $cfg["table_data_bg"]);
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('_ADMINEDITLINKS', _ADMINEDITLINKS);
$tmpl->setvar('_FULLURLLINK', _FULLURLLINK);
$tmpl->setvar('_FULLSITENAME', _FULLSITENAME);
$tmpl->setvar('_UPDATE', _UPDATE);
$tmpl->setvar('_DELETE', _DELETE);
$tmpl->setvar('_EDIT', _EDIT);

$arLinks = GetLinks();
$arLid = Array_Keys($arLinks);
$inx = 0;
$link_count = count($arLinks);
$link_list = array();
foreach($arLinks as $link) {
	$lid = $arLid[$inx++];
	if ( isset($_GET["edit"]) && $_GET["edit"] == $link['lid']) {
		$is_edit = 1;
	} else {
		$is_edit = 0;
		if ($inx > 1 ) {
			$counter = 2;
		}
		if ( $inx == 1 ) {
			$counter = 1;
		}
		if ($inx != count($arLinks)) {
			$counter2 = 1;
		}
		else {
			$counter2 = 0;
		}
	}
	array_push($link_list, array(
		'is_edit' => $is_edit,
		'url' => $link['url'],
		'sitename' => $link['sitename'],
		'lid' => $lid,
		'counter' => $counter,
		'counter2' => $counter2,
		)
	);
}
$tmpl->setloop('link_list', $link_list);
$tmpl->setvar('foot', getFoot(true,true));

$tmpl->pparse();
?>