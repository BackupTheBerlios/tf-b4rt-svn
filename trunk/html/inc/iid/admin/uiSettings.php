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

// load global settings + overwrite per-user settings
loadSettings();

# create new template
if ((strpos($cfg['theme'], '/')) === false)
	$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/admin/uiSettings.tmpl");
else
	$tmpl = new vlibTemplate("themes/tf_standard_themes/tmpl/admin/uiSettings.tmpl");

$tmpl->setvar('head', getHead("Administration - UI Settings"));
$tmpl->setvar('menu', getMenu());
$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
$tmpl->setvar('table_data_bg', $cfg["table_data_bg"]);
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('getIndexPageSettingsForm', getIndexPageSettingsForm());
$tmpl->setvar('ui_dim_main_w', $cfg["ui_dim_main_w"]);
$tmpl->setvar('ui_displaylinks', $cfg["ui_displaylinks"]);
$tmpl->setvar('ui_displayusers', $cfg["ui_displayusers"]);
$tmpl->setvar('drivespacebar', $cfg["drivespacebar"]);
$tmpl->setvar('ui_displaybandwidthbars', $cfg["ui_displaybandwidthbars"]);
$tmpl->setvar('index_page_stats', $cfg["index_page_stats"]);
$tmpl->setvar('show_server_load', $cfg["show_server_load"]);
$tmpl->setvar('index_page_connections', $cfg["index_page_connections"]);
$tmpl->setvar('ui_indexrefresh', $cfg["ui_indexrefresh"]);
$tmpl->setvar('pagerefresh', $cfg["page_refresh"]);
$tmpl->setvar('getSortOrderSettingsForm', getSortOrderSettingsForm());
$tmpl->setvar('enable_sorttable', $cfg["enable_sorttable"]);
$tmpl->setvar('enable_goodlookstats', $cfg["enable_goodlookstats"]);
$tmpl->setvar('getGoodLookingStatsForm', getGoodLookingStatsForm());
$tmpl->setvar('enable_bigboldwarning', $cfg["enable_bigboldwarning"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('ui_displayfluxlink', $cfg["ui_displayfluxlink"]);
$tmpl->setvar('foot', getFoot(true,true));
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('iid', $_GET["iid"]);
$tmpl->pparse();

?>