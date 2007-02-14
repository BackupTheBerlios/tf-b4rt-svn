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

// FluAzu
require_once("inc/classes/FluAzu.php");

// init template-instance
tmplInitializeInstance($cfg["theme"], "page.admin.fluazuSettings.tmpl");

// superadmin-links
$tmpl->setvar('SuperAdminLink1', getSuperAdminLink('?a=1','<font class="adminlink">log</font></a>'));
$tmpl->setvar('SuperAdminLink3', getSuperAdminLink('?a=3','<font class="adminlink">ps</font></a>'));
$tmpl->setvar('SuperAdminLink9', getSuperAdminLink('?a=9','<font class="adminlink">version</font></a>'));

// message section
$message = getRequestVar('m');
if ($message != "")
	$tmpl->setvar('message', urldecode($message));

// fluazu core
if (FluAzu::isRunning()) {
	$tmpl->setvar('fluazuRunning', 1);
	$tmpl->setvar('fluazuPid', FluAzu::getPid());
	$status = FluAzu::getStatus();
	$tmpl->setvar('azu_host', $status['azu_host']);
	$tmpl->setvar('azu_port', $status['azu_port']);
	$tmpl->setvar('azu_version', $status['azu_version']);
	$tmpl->setvar('CORE_PARAM_INT_MAX_ACTIVE', $status['CORE_PARAM_INT_MAX_ACTIVE']);
	$tmpl->setvar('CORE_PARAM_INT_MAX_ACTIVE_SEEDING', $status['CORE_PARAM_INT_MAX_ACTIVE_SEEDING']);
	$tmpl->setvar('CORE_PARAM_INT_MAX_CONNECTIONS_GLOBAL', $status['CORE_PARAM_INT_MAX_CONNECTIONS_GLOBAL']);
	$tmpl->setvar('CORE_PARAM_INT_MAX_CONNECTIONS_PER_TORRENT', $status['CORE_PARAM_INT_MAX_CONNECTIONS_PER_TORRENT']);
	$tmpl->setvar('CORE_PARAM_INT_MAX_DOWNLOAD_SPEED_KBYTES_PER_SEC', $status['CORE_PARAM_INT_MAX_DOWNLOAD_SPEED_KBYTES_PER_SEC']);
	$tmpl->setvar('CORE_PARAM_INT_MAX_DOWNLOADS', $status['CORE_PARAM_INT_MAX_DOWNLOADS']);
	$tmpl->setvar('CORE_PARAM_INT_MAX_UPLOAD_SPEED_KBYTES_PER_SEC', $status['CORE_PARAM_INT_MAX_UPLOAD_SPEED_KBYTES_PER_SEC']);
	$tmpl->setvar('CORE_PARAM_INT_MAX_UPLOAD_SPEED_SEEDING_KBYTES_PER_SEC', $status['CORE_PARAM_INT_MAX_UPLOAD_SPEED_SEEDING_KBYTES_PER_SEC']);
	$tmpl->setvar('CORE_PARAM_INT_MAX_UPLOADS', $status['CORE_PARAM_INT_MAX_UPLOADS']);
	$tmpl->setvar('CORE_PARAM_INT_MAX_UPLOADS_SEEDING', $status['CORE_PARAM_INT_MAX_UPLOADS_SEEDING']);
} else {
	$tmpl->setvar('fluazuRunning', 0);
}

// settings
$tmpl->setvar('fluazu_host', $cfg['fluazu_host']);
$tmpl->setvar('fluazu_port', $cfg['fluazu_port']);
$tmpl->setvar('fluazu_secure', $cfg['fluazu_secure']);
$tmpl->setvar('fluazu_user', $cfg['fluazu_user']);
$tmpl->setvar('fluazu_pw', $cfg['fluazu_pw']);

// templ-calls
tmplSetTitleBar("Administration - fluazu Settings");
tmplSetAdminMenu();
tmplSetFoot();

// set iid-var
$tmpl->setvar('iid', $_REQUEST["iid"]);
$tmpl->setvar('mainMenu', mainMenu($_REQUEST["iid"]));

// parse template
$tmpl->pparse();

?>