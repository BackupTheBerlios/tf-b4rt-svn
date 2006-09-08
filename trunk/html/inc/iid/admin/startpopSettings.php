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

require_once("inc/classes/AliasFile.php");
require_once("inc/classes/RunningTransfer.php");

// create template-instance
$tmpl = getTemplateInstance($cfg["theme"], "admin/startpopSettings.tmpl");

$tmpl->setvar('head', getHead("Administration - StartPop Settings"));
$tmpl->setvar('menu', getMenu());
$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
$tmpl->setvar('table_data_bg', $cfg["table_data_bg"]);
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
$tmpl->setvar('theme', $cfg["theme"]);
//
$tmpl->setvar('enable_btclient_chooser', $cfg["enable_btclient_chooser"]);
$tmpl->setvar('enable_transfer_profile', $cfg["enable_transfer_profile"]);
$tmpl->setvar('transfer_profile_level', $cfg["transfer_profile_level"]);
$tmpl->setvar('transfer_customize_settings', $cfg["transfer_customize_settings"]);
$tmpl->setvar('advanced_start', $cfg["advanced_start"]);
$tmpl->setvar('showdirtree', $cfg["showdirtree"]);
$tmpl->setvar('maxdepth', $cfg["maxdepth"]);
//
$tmpl->setvar('foot', getFoot(true));
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('ui_displayfluxlink', $cfg["ui_displayfluxlink"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('iid', $_GET["iid"]);
$tmpl->pparse();

?>