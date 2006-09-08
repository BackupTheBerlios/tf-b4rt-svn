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

// create template-instance
$tmpl = getTemplateInstance($cfg["theme"], "admin/indexSettings.tmpl");

$tmpl->setvar('head', getHead("Administration - Index Settings"));
$tmpl->setvar('menu', getMenu());
$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
$tmpl->setvar('table_data_bg', $cfg["table_data_bg"]);
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('enable_multiupload', $cfg["enable_multiupload"]);
$tmpl->setvar('hack_multiupload_rows', $cfg["hack_multiupload_rows"]);
$tmpl->setvar('IndexPageSettingsForm', IndexPageSettingsForm());
$tmpl->setvar('ui_dim_main_w', $cfg["ui_dim_main_w"]);
$tmpl->setvar('ui_displaylinks', $cfg["ui_displaylinks"]);
$tmpl->setvar('ui_displayusers', $cfg["ui_displayusers"]);
$tmpl->setvar('enable_mrtg', $cfg["enable_mrtg"]);
$tmpl->setvar('drivespacebar', $cfg["drivespacebar"]);
$tmpl->setvar('ui_displaybandwidthbars', $cfg["ui_displaybandwidthbars"]);
$tmpl->setvar('bandwidthbar', $cfg["bandwidthbar"]);
$tmpl->setvar('bandwidth_up', $cfg["bandwidth_up"]);
$tmpl->setvar('bandwidth_down', $cfg["bandwidth_down"]);
$tmpl->setvar('enable_goodlookstats', $cfg["enable_goodlookstats"]);
$tmpl->setvar('GoodLookingStatsForm', GoodLookingStatsForm());
$tmpl->setvar('enable_bigboldwarning', $cfg["enable_bigboldwarning"]);
$tmpl->setvar('enable_search', $cfg["enable_search"]);
fillSearchEngineDDL($cfg["searchEngine"]);
$tmpl->setvar('index_page_stats', $cfg["index_page_stats"]);
$tmpl->setvar('show_server_load', $cfg["show_server_load"]);
$tmpl->setvar('index_page_connections', $cfg["index_page_connections"]);
$tmpl->setvar('ui_indexrefresh', $cfg["ui_indexrefresh"]);
$tmpl->setvar('pagerefresh', $cfg["page_refresh"]);
$tmpl->setvar('enable_restrictivetview', $cfg["enable_restrictivetview"]);
$tmpl->setvar('enable_torrent_download', $cfg["enable_torrent_download"]);
$tmpl->setvar('enable_sorttable', $cfg["enable_sorttable"]);
$tmpl->setvar('getSortOrderSettings', getSortOrderSettings());
$tmpl->setvar('enable_multiops', $cfg["enable_multiops"]);
$tmpl->setvar('enable_bulkops', $cfg["enable_bulkops"]);
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('display_seeding_time', $cfg["display_seeding_time"]);
$tmpl->setvar('foot', getFoot(true));
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('iid', $_GET["iid"]);
$tmpl->pparse();

?>