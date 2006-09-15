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

// readrss functions
require_once('inc/functions/functions.readrss.php');

// create template-instance
$tmpl = tmplGetInstance($cfg["theme"], "page.admin.editRSS.tmpl");

// set vars
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
//
$tmpl->setvar('_FULLURLLINK', $cfg['_FULLURLLINK']);
$tmpl->setvar('_UPDATE', $cfg['_UPDATE']);
$tmpl->setvar('_DELETE', $cfg['_DELETE']);
//
$tmpl->setvar('menu', getMenu());
tmplSetTitleBar("Administration - RSS");
tmplSetFoot();
$tmpl->setvar('iid', $_GET["iid"]);

// parse template
$tmpl->pparse();

?>