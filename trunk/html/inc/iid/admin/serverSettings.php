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
$tmpl = getTemplateInstance($cfg["theme"], "admin/serverSettings.tmpl");

// set vars
// path
$tmpl->setvar('path', $cfg["path"]);
if (is_dir($cfg["path"])) {
	$tmpl->setvar('is_path', 1);
	if (is_writable($cfg["path"]))
		$tmpl->setvar('is_writable', 1);
	else
		$tmpl->setvar('is_writable', 0);
} else {
	$tmpl->setvar('is_path', 0);
}
// homedirs + incoming
$tmpl->setvar('enable_home_dirs', $cfg["enable_home_dirs"]);
$tmpl->setvar('path_incoming', $cfg["path_incoming"]);
if (checkDirectory($cfg["path"].$cfg["path_incoming"], 0777))
	$tmpl->setvar('path_incoming_ok', 1);
else
	$tmpl->setvar('path_incoming_ok', 0);
// bins
$tmpl->setvar('btclient_transmission_bin', $cfg["btclient_transmission_bin"]);
$tmpl->setvar('validate_transmission_bin', validateFile($cfg["btclient_transmission_bin"]));
$tmpl->setvar('perlCmd', $cfg["perlCmd"]);
$tmpl->setvar('validate_perl', validateFile($cfg["perlCmd"]));
$tmpl->setvar('bin_grep', $cfg["bin_grep"]);
$tmpl->setvar('validate_grep', validateFile($cfg["bin_grep"]));
$tmpl->setvar('bin_php', $cfg["bin_php"]);
$tmpl->setvar('validate_php', validateFile($cfg["bin_php"]));
$tmpl->setvar('pythonCmd', $cfg["pythonCmd"]);
$tmpl->setvar('validate_python', validateFile($cfg["pythonCmd"]));
$tmpl->setvar('bin_awk', $cfg["bin_awk"]);
$tmpl->setvar('validate_awk', validateFile($cfg["bin_awk"]));
$tmpl->setvar('bin_du', $cfg["bin_du"]);
$tmpl->setvar('validate_du', validateFile($cfg["bin_du"]));
$tmpl->setvar('bin_wget', $cfg["bin_wget"]);
$tmpl->setvar('validate_wget', validateFile($cfg["bin_wget"]));
$tmpl->setvar('bin_unzip', $cfg["bin_unzip"]);
$tmpl->setvar('validate_unzip', validateFile($cfg["bin_unzip"]));
$tmpl->setvar('bin_cksfv', $cfg["bin_cksfv"]);
$tmpl->setvar('validate_cksfv', validateFile($cfg["bin_cksfv"]));
$tmpl->setvar('php_uname1', php_uname('s'));
$tmpl->setvar('php_uname2', php_uname('r'));
$tmpl->setvar('bin_unrar', $cfg["bin_unrar"]);
$tmpl->setvar('validate_unrar', validateFile($cfg["bin_unrar"]));
switch ($cfg["_OS"]) {
	case 1:
		$tmpl->setvar('loadavg_path', $cfg["loadavg_path"]);
		$tmpl->setvar('validate_loadavg', validateFile($cfg["loadavg_path"]));
		$tmpl->setvar('bin_netstat', $cfg["bin_netstat"]);
		$tmpl->setvar('validate_netstat', validateFile($cfg["bin_netstat"]));
		break;
	case 2:
		$tmpl->setvar('bin_sockstat', $cfg["bin_sockstat"]);
		$tmpl->setvar('validate_sockstat', validateFile($cfg["bin_sockstat"]));
		break;
}
//
$tmpl->setvar('_OS', $cfg["_OS"]);
//
$tmpl->setvar('menu', getMenu());
$tmpl->setvar('head', getHead("Administration - Server Settings"));
$tmpl->setvar('foot', getFoot(true));
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
$tmpl->setvar('table_data_bg', $cfg["table_data_bg"]);
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('iid', $_GET["iid"]);

// parse template
$tmpl->pparse();

?>