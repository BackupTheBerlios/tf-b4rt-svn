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

// dir functions
require_once('inc/functions/functions.dir.php');

// config
loadSettings('tf_settings_dir');
initRestrictedDirEntries();

// create template-instance
$tmpl = getTemplateInstance($cfg["theme"], "admin/dirSettings.tmpl");

//
$tmpl->setvar('head', getHead("Administration - Dir Settings"));
$tmpl->setvar('menu', getMenu());
$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
$tmpl->setvar('table_data_bg', $cfg["table_data_bg"]);
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
$tmpl->setvar('theme', $cfg["theme"]);
// restricted entries
$dir_list = array();
foreach ($restrictedFileEntries as $entry) {
	$target = trim($entry);
	if ((strlen($target) > 0) && ((substr($target, 0, 1)) != ";")) {
		array_push($dir_list, array(
			'target' => $target,
			)
		);
	}
}
$tmpl->setloop('dir_restricted_list', $dir_list);
$tmpl->setvar('dir_restricted', $cfg["dir_restricted"]);
//
$tmpl->setvar('dir_public_read', $cfg["dir_public_read"]);
$tmpl->setvar('dir_public_write', $cfg["dir_public_write"]);
$tmpl->setvar('enable_maketorrent', $cfg["enable_maketorrent"]);
$tmpl->setvar('enable_file_download', $cfg["enable_file_download"]);
$tmpl->setvar('package_type', $cfg["package_type"]);
$tmpl->setvar('enable_view_nfo', $cfg["enable_view_nfo"]);
$tmpl->setvar('enable_dirstats', $cfg["enable_dirstats"]);
$tmpl->setvar('enable_rar', $cfg["enable_rar"]);
$tmpl->setvar('enable_sfvcheck', $cfg["enable_sfvcheck"]);
$tmpl->setvar('enable_rename', $cfg["enable_rename"]);
$tmpl->setvar('enable_move', $cfg["enable_move"]);
$tmpl->setvar('getMoveSettings', getMoveSettings());
//
$tmpl->setvar('foot', getFoot(true));
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('ui_displayfluxlink', $cfg["ui_displayfluxlink"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('iid', $_GET["iid"]);
$tmpl->pparse();

?>