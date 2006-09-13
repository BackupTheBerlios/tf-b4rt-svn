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

// common functions
require_once('inc/functions/functions.common.php');

// load stats-settings
loadSettings('tf_settings_stats');

// create template-instance
$tmpl = getTemplateInstance($cfg["theme"], "servermon.tmpl");

// set vars
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('main_bgcolor', $cfg["main_bgcolor"]);
$tmpl->setvar('table_border_dk', $cfg["table_border_dk"]);
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
$tmpl->setvar('body_data_bg', $cfg["body_data_bg"]);
$tmpl->setvar('getTitleBar', getTitleBar($cfg["pagetitle"]." - Server Monitor", false));
//
$statsUrl = "http://";
$statsUrl .= $_SERVER['SERVER_NAME'];
$statsUrl .= preg_replace('/index\.php.*/', 'stats.php', $_SERVER['REQUEST_URI']);
$timer = ((int) $cfg['servermon_update']) * 1000;
$tmpl->setvar('onLoad', "initialize('".$statsUrl."',".$timer.",'".$cfg['stats_txt_delim']."');");
//
$tmpl->setvar('getTorrentFluxLink', getTorrentFluxLink());
$tmpl->setvar('_DOWNLOADSPEED', $cfg['_DOWNLOADSPEED']);
$tmpl->setvar('_UPLOADSPEED', $cfg['_UPLOADSPEED']);
$tmpl->setvar('_TOTALSPEED', $cfg['_TOTALSPEED']);
$tmpl->setvar('_ID_CONNECTIONS', $cfg['_ID_CONNECTIONS']);
$tmpl->setvar('_DRIVESPACE', $cfg['_DRIVESPACE']);
$tmpl->setvar('_SERVERLOAD', $cfg['_SERVERLOAD']);
$tmpl->setvar('iid', $_GET["iid"]);
$tmpl->pparse();

?>