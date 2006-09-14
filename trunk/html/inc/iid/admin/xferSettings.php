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
$tmpl = getTemplateInstance($cfg["theme"], "admin/xferSettings.tmpl");

// set vars
$tmpl->setvar('enable_xfer', $cfg["enable_xfer"]);
$tmpl->setvar('xfer_realtime', $cfg["xfer_realtime"]);
$tmpl->setvar('enable_public_xfer', $cfg["enable_public_xfer"]);
$tmpl->setvar('xfer_total', $cfg["xfer_total"]);
$tmpl->setvar('xfer_month', $cfg["xfer_month"]);
$tmpl->setvar('xfer_week', $cfg["xfer_week"]);
$tmpl->setvar('xfer_day', $cfg["xfer_day"]);
$tmpl->setvar('week_start', $cfg["week_start"]);
$month_list = array();
for ($i = 1; $i <= 31 ; $i++) {
	if ($cfg["month_start"] == $i) {
		$month_start_true = 1;
	} else {
		$month_start_true = 0;
	}
	array_push($month_list, array(
		'i' => $i,
		'month_start_true' => $month_start_true,
		)
	);
}
$tmpl->setloop('month_list', $month_list);
//
$tmpl->setvar('menu', getMenu());
$tmpl->setvar('head', getHead("Administration - Xfer Settings"));
$tmpl->setvar('foot', getFoot(true));
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
$tmpl->setvar('table_data_bg', $cfg["table_data_bg"]);
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
$tmpl->setvar('ui_displayfluxlink', $cfg["ui_displayfluxlink"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('iid', $_GET["iid"]);

// parse template
$tmpl->pparse();

?>