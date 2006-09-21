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
$tmpl = tmplGetInstance($cfg["theme"], "page.admin.statsSettings.tmpl");

// set vars
$list = array();
for ($i = 0; $i <= 9 ; $i++) {
	if ($cfg["stats_deflate_level"] == $i)
		$is_selected = 1;
	else
		$is_selected = 0;
	array_push($list, array(
		'i' => $i,
		'is_selected' => $is_selected,
		)
	);
}
$tmpl->setloop('deflate_list', $list);
$tmpl->setvar('stats_enable_public', $cfg["stats_enable_public"]);
$tmpl->setvar('stats_show_usage', $cfg["stats_show_usage"]);
$tmpl->setvar('stats_txt_delim', $cfg["stats_txt_delim"]);
$tmpl->setvar('stats_default_header', $cfg["stats_default_header"]);
$tmpl->setvar('stats_default_type', $cfg["stats_default_type"]);
$tmpl->setvar('stats_default_format', $cfg["stats_default_format"]);
$tmpl->setvar('stats_default_compress', $cfg["stats_default_compress"]);
$tmpl->setvar('stats_default_attach', $cfg["stats_default_attach"]);
//
tmplSetTitleBar("Administration - Stats Settings");
tmplSetAdminMenu();
tmplSetFoot();
$tmpl->setvar('iid', $_REQUEST["iid"]);

// parse template
$tmpl->pparse();

?>