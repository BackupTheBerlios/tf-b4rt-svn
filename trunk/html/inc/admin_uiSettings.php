<?php
/* $Id$ */
// load global settings + overwrite per-user settings
loadSettings();
# create new template
if (!ereg('^[^./][^/]*$', $cfg["theme"])) {
	$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/admin_uiSettings.tmpl");
}
else {
	$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/admin_uiSettings.tmpl");
}
$tmpl->setvar('head', getHead("Administration - UI Settings"));
$tmpl->setvar('menu', getMenu());
$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
$tmpl->setvar('table_data_bg', $cfg["table_data_bg"]);
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('getIndexPageSelectForm', getIndexPageSelectForm());
$tmpl->setvar('getIndexPageSettingsForm', getIndexPageSettingsForm());
$tmpl->setvar('ui_dim_main_w', $cfg["ui_dim_main_w"]);
$tmpl->setvar('ui_displaylinks', $cfg["ui_displaylinks"]);
$tmpl->setvar('ui_displayusers', $cfg["ui_displayusers"]);
$tmpl->setvar('drivespacebar', $cfg["drivespacebar"]);
$tmpl->setvar('index_page_stats', $cfg["index_page_stats"]);
$tmpl->setvar('show_server_load', $cfg["show_server_load"]);
$tmpl->setvar('index_page_connections', $cfg["index_page_connections"]);
$tmpl->setvar('ui_indexrefresh', $cfg["ui_indexrefresh"]);
$tmpl->setvar('page_refresh', $cfg["page_refresh"]);
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
$tmpl->setvar('index_page', $cfg["index_page"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->pparse();
?>