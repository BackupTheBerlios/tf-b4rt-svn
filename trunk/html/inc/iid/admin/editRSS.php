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
$tmpl = getTemplateInstance($cfg["theme"], "admin/editRSS.tmpl");

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
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('iid', $_GET["iid"]);
$tmpl->pparse();

?>