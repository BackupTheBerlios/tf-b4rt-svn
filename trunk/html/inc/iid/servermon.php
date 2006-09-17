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

// create template-instance
$tmpl = tmplGetInstance($cfg["theme"], "page.servermon.tmpl");

// set vars
$statsUrl = "http://";
$statsUrl .= $_SERVER['SERVER_NAME'];
$statsUrl .= preg_replace('/index\.php.*/', 'stats.php', $_SERVER['REQUEST_URI']);
$timer = ((int) $cfg['servermon_update']) * 1000;
$tmpl->setvar('onLoad', "ajax_initialize('".$statsUrl."',".$timer.",'".$cfg['stats_txt_delim']."');");
//
$tmpl->setvar('_DOWNLOADSPEED', $cfg['_DOWNLOADSPEED']);
$tmpl->setvar('_UPLOADSPEED', $cfg['_UPLOADSPEED']);
$tmpl->setvar('_TOTALSPEED', $cfg['_TOTALSPEED']);
$tmpl->setvar('_ID_CONNECTIONS', $cfg['_ID_CONNECTIONS']);
$tmpl->setvar('_DRIVESPACE', $cfg['_DRIVESPACE']);
$tmpl->setvar('_SERVERLOAD', $cfg['_SERVERLOAD']);
//
tmplSetTitleBar($cfg["pagetitle"]." - Server Monitor", false);
$tmpl->setvar('torrentFluxLink', getTorrentFluxLink());
$tmpl->setvar('iid', $_GET["iid"]);

// parse template
$tmpl->pparse();

?>