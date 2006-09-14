<?php

/* $Id$ */

/*******************************************************************************

 LICENSE

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License (GPL)
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.

 To read the license please visit http://www.gnu.org/copyleft/gpl.html

*******************************************************************************/

// create template-instance
$tmpl = getTemplateInstance($cfg["theme"], "admin/editLinks.tmpl");

// set vars
$arLinks = GetLinks();
$arLid = Array_Keys($arLinks);
$inx = 0;
$link_count = count($arLinks);
$link_list = array();
foreach($arLinks as $link) {
	$lid = $arLid[$inx++];
	if (isset($_REQUEST["edit"]) && $_REQUEST["edit"] == $link['lid']) {
		$is_edit = 1;
	} else {
		$is_edit = 0;
		if ($inx > 1 )
			$counter = 2;
		if ($inx == 1)
			$counter = 1;
		if ($inx != count($arLinks))
			$counter2 = 1;
		else
			$counter2 = 0;
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
//
$tmpl->setvar('_ADMINEDITLINKS', $cfg['_ADMINEDITLINKS']);
$tmpl->setvar('_FULLURLLINK', $cfg['_FULLURLLINK']);
$tmpl->setvar('_FULLSITENAME', $cfg['_FULLSITENAME']);
$tmpl->setvar('_UPDATE', $cfg['_UPDATE']);
$tmpl->setvar('_DELETE', $cfg['_DELETE']);
$tmpl->setvar('_EDIT', $cfg['_EDIT']);
//
$tmpl->setvar('menu', getMenu());
$tmpl->setvar('head', getHead($cfg['_ADMINEDITLINKS']));
$tmpl->setvar('foot', getFoot(true));
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
$tmpl->setvar('table_data_bg', $cfg["table_data_bg"]);
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
$tmpl->setvar('iid', $_GET["iid"]);

// parse template
$tmpl->pparse();

?>