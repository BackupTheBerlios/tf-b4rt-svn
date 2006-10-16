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
if (!isset($cfg['user'])) {
	@ob_end_clean();
	header("location: ../../../index.php");
	exit();
}

/******************************************************************************/

// create template-instance
$tmpl = tmplGetInstance($cfg["theme"], "page.admin.fluxdRssadSettings.tmpl");

// message section
$message = getRequestVar('m');
if ((isset($message)) && ($message != "")) {
	$tmpl->setvar('new_msg', 1);
	$tmpl->setvar('message', urldecode($message));
} else {
	$tmpl->setvar('new_msg', 0);
}

// DEBUG
$message = getRequestVar('pageop');
if ((isset($message)) && ($message != "")) {
	$tmpl->setvar('new_msg', 1);
	$tmpl->setvar('message', urldecode($message));
} else {
	$tmpl->setvar('new_msg', 0);
}

// superadmin-links
$tmpl->setvar('SuperAdminLink1', getSuperAdminLink('?f=1','<font class="adminlink">log</font></a>'));

// MODS
$users = GetUsers();
$userCount = count($users);

// Rssad
$tmpl->setvar('fluxd_Rssad_enabled', $cfg["fluxd_Rssad_enabled"]);
if (($cfg["fluxd_Rssad_enabled"] == 1) && ($fluxdRunning))
	$tmpl->setvar('fluxd_Rssad_state', $fluxd->modState('Rssad'));
else
	$tmpl->setvar('fluxd_Rssad_state', 0);
$tmpl->setvar('fluxd_Rssad_interval', $cfg["fluxd_Rssad_interval"]);
$tmpl->setvar('fluxd_Rssad_jobs', $cfg["fluxd_Rssad_jobs"]);

//
tmplSetTitleBar("Administration - Fluxd Rssad Settings");
tmplSetAdminMenu();
tmplSetFoot();

// parse template
$tmpl->pparse();

?>