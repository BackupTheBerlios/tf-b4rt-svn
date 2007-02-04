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

// prevent direct invocation
if ((!isset($cfg['user'])) || (isset($_REQUEST['cfg']))) {
	@ob_end_clean();
	@header("location: ../../../index.php");
	exit();
}

/******************************************************************************/

// load global settings + overwrite per-user settings
loadSettings('tf_settings');

// init template-instance
tmplInitializeInstance($cfg["theme"], "page.admin.indexSettings.tmpl");

// set vars
$tmpl->setvar('enable_index_meta_refresh', $cfg["enable_index_meta_refresh"]);
$tmpl->setvar('page_refresh', $cfg["page_refresh"]);
$tmpl->setvar('enable_index_ajax_update', $cfg["enable_index_ajax_update"]);
$tmpl->setvar('enable_index_ajax_update_title', $cfg["enable_index_ajax_update_title"]);
$tmpl->setvar('enable_index_ajax_update_users', $cfg["enable_index_ajax_update_users"]);
$tmpl->setvar('enable_index_ajax_update_list', $cfg["enable_index_ajax_update_list"]);
$tmpl->setvar('enable_index_ajax_update_silent', $cfg["enable_index_ajax_update_silent"]);
$tmpl->setvar('index_ajax_update', $cfg["index_ajax_update"]);
$tmpl->setvar('enable_multiupload', $cfg["enable_multiupload"]);
$tmpl->setvar('hack_multiupload_rows', $cfg["hack_multiupload_rows"]);
$tmpl->setvar('ui_dim_main_w', $cfg["ui_dim_main_w"]);
$tmpl->setvar('ui_displaylinks', $cfg["ui_displaylinks"]);
$tmpl->setvar('ui_displayusers', $cfg["ui_displayusers"]);
$tmpl->setvar('ui_displaybandwidthbars', $cfg["ui_displaybandwidthbars"]);
$tmpl->setvar('bandwidthbar', $cfg["bandwidthbar"]);
$tmpl->setvar('bandwidth_up', $cfg["bandwidth_up"]);
$tmpl->setvar('bandwidth_down', $cfg["bandwidth_down"]);
$tmpl->setvar('enable_goodlookstats', $cfg["enable_goodlookstats"]);
$tmpl->setvar('enable_bigboldwarning', $cfg["enable_bigboldwarning"]);
$tmpl->setvar('enable_search', $cfg["enable_search"]);
$tmpl->setvar('index_page_stats', $cfg["index_page_stats"]);
$tmpl->setvar('show_server_load', $cfg["show_server_load"]);
$tmpl->setvar('index_page_connections', $cfg["index_page_connections"]);
$tmpl->setvar('enable_restrictivetview', $cfg["enable_restrictivetview"]);
$tmpl->setvar('enable_metafile_download', $cfg["enable_metafile_download"]);
$tmpl->setvar('enable_sorttable', $cfg["enable_sorttable"]);
$tmpl->setvar('enable_multiops', $cfg["enable_multiops"]);
$tmpl->setvar('enable_bulkops', $cfg["enable_bulkops"]);
$tmpl->setvar('display_seeding_time', $cfg["display_seeding_time"]);
$tmpl->setvar('index_page_sortorder', $cfg["index_page_sortorder"]);
$tmpl->setloop('Engine_List', tmplSetSearchEngineDDL($cfg["searchEngine"]));
$transferWindowDefaultList = array();
array_push($transferWindowDefaultList, array(
	'name' => 'Stats',
	'value' => 'transferStats',
	'is_selected' => ('transferStats' == $cfg["transfer_window_default"]) ? 1 : 0
	)
);
array_push($transferWindowDefaultList, array(
	'name' => 'Hosts',
	'value' => 'transferHosts',
	'is_selected' => ('transferHosts' == $cfg["transfer_window_default"]) ? 1 : 0
	)
);
array_push($transferWindowDefaultList, array(
	'name' => 'Scrape',
	'value' => 'transferScrape',
	'is_selected' => ('transferScrape' == $cfg["transfer_window_default"]) ? 1 : 0
	)
);
array_push($transferWindowDefaultList, array(
	'name' => 'Images',
	'value' => 'transferImages',
	'is_selected' => ('transferImages' == $cfg["transfer_window_default"]) ? 1 : 0
	)
);
array_push($transferWindowDefaultList, array(
	'name' => 'Log',
	'value' => 'transferLog',
	'is_selected' => ('transferLog' == $cfg["transfer_window_default"]) ? 1 : 0
	)
);
array_push($transferWindowDefaultList, array(
	'name' => 'Details',
	'value' => 'transferDetails',
	'is_selected' => ('transferDetails' == $cfg["transfer_window_default"]) ? 1 : 0
	)
);
array_push($transferWindowDefaultList, array(
	'name' => 'Files',
	'value' => 'transferFiles',
	'is_selected' => ('transferFiles' == $cfg["transfer_window_default"]) ? 1 : 0
	)
);
array_push($transferWindowDefaultList, array(
	'name' => 'Settings',
	'value' => 'transferSettings',
	'is_selected' => ('transferSettings' == $cfg["transfer_window_default"]) ? 1 : 0
	)
);
array_push($transferWindowDefaultList, array(
	'name' => 'Control',
	'value' => 'transferControl',
	'is_selected' => ('transferControl' == $cfg["transfer_window_default"]) ? 1 : 0
	)
);
$tmpl->setloop('transfer_window_default_list', $transferWindowDefaultList);
//
tmplSetTitleBar("Administration - Index Settings");
tmplSetAdminMenu();
tmplSetGoodLookingStatsForm();
tmplSetIndexPageSettingsForm();
tmplSetFoot();

// set iid-var
$tmpl->setvar('iid', $_REQUEST["iid"]);
$tmpl->setvar('mainMenu', mainMenu($_REQUEST["iid"]));

// parse template
$tmpl->pparse();

?>