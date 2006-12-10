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

require_once("inc/classes/AliasFile.php");
require_once("inc/classes/RunningTransfer.php");

// init template-instance
tmplInitializeInstance($cfg["theme"], "page.admin.fluxdSettings.tmpl");

// message section
$message = getRequestVar('m');
if ((isset($message)) && ($message != "")) {
	$tmpl->setvar('new_msg', 1);
	$tmpl->setvar('message', urldecode($message));
} else {
	$tmpl->setvar('new_msg', 0);
}

// fluxd Section
if ($fluxdRunning) {
	$fluxdPid = $fluxd->getFluxdPid();
	$tmpl->setvar('fluxdPid', $fluxdPid);
}

// superadmin-links
$tmpl->setvar('SuperAdminLink1', getSuperAdminLink('?f=1','<font class="adminlink">log</font></a>'));
$tmpl->setvar('SuperAdminLink2', getSuperAdminLink('?f=2','<font class="adminlink">error-log</font></a>'));
$tmpl->setvar('SuperAdminLink3', getSuperAdminLink('?f=3','<font class="adminlink">ps</font></a>'));
$tmpl->setvar('SuperAdminLink4', getSuperAdminLink('?f=4','<font class="adminlink">status</font></a>'));
$tmpl->setvar('SuperAdminLink5', getSuperAdminLink('?f=5','<font class="adminlink">check</font></a>'));
$tmpl->setvar('SuperAdminLink6', getSuperAdminLink('?f=6','<font class="adminlink">db-debug</font></a>'));
$tmpl->setvar('SuperAdminLink9', getSuperAdminLink('?f=9','<font class="adminlink">version</font></a>'));

// core
$tmpl->setvar('fluxd_dbmode', $cfg["fluxd_dbmode"]);
$tmpl->setvar('fluxd_loglevel', $cfg["fluxd_loglevel"]);

// MODS
$users = GetUsers();
$userCount = count($users);

// Qmgr
$tmpl->setvar('fluxd_Qmgr_enabled', $cfg["fluxd_Qmgr_enabled"]);
$tmpl->setvar('fluxd_Qmgr_state', (($cfg["fluxd_Qmgr_enabled"] == 1) && ($fluxdRunning)) ? $fluxd->modState('Qmgr') : 0);
$tmpl->setvar('fluxd_Qmgr_interval', $cfg["fluxd_Qmgr_interval"]);
$tmpl->setvar('fluxd_Qmgr_maxTotalTorrents', $cfg["fluxd_Qmgr_maxTotalTorrents"]);
$tmpl->setvar('fluxd_Qmgr_maxUserTorrents', $cfg["fluxd_Qmgr_maxUserTorrents"]);

// Watch
$tmpl->setvar('fluxd_Watch_enabled', $cfg["fluxd_Watch_enabled"]);
$tmpl->setvar('fluxd_Watch_state', (($cfg["fluxd_Watch_enabled"] == 1) && ($fluxdRunning)) ? $fluxd->modState('Watch') : 0);
$tmpl->setvar('fluxd_Watch_interval', $cfg["fluxd_Watch_interval"]);
if ((isset($cfg["fluxd_Watch_jobs"])) && (strlen($cfg["fluxd_Watch_jobs"]) > 0)) {
	$watchlist = array();
	$jobs = explode(";", trim($cfg["fluxd_Watch_jobs"]));
	foreach ($jobs as $job) {
		$jobAry = explode(":", trim($job));
		$user = trim(array_shift($jobAry));
		$dir = trim(array_shift($jobAry));
		if ((strlen($user) > 0) && (strlen($dir) > 0)) {
			array_push($watchlist, array(
				'user' => $user,
				'dir' => $dir
				)
			);
		}
	}
	$tmpl->setloop('fluxd_Watch_jobs_list', $watchlist);
}
$watchuser = array();
for ($i = 0; $i < $userCount; $i++)
	array_push($watchuser, array('user' => $users[$i]));
$tmpl->setloop('watch_user', $watchuser);
$tmpl->setvar('fluxd_Watch_jobs', $cfg["fluxd_Watch_jobs"]);

// Rssad
$tmpl->setvar('fluxd_Rssad_enabled', $cfg["fluxd_Rssad_enabled"]);
$tmpl->setvar('fluxd_Rssad_state', (($cfg["fluxd_Rssad_enabled"] == 1) && ($fluxdRunning)) ? $fluxd->modState('Rssad') : 0);
$tmpl->setvar('fluxd_Rssad_interval', $cfg["fluxd_Rssad_interval"]);

// Fluxinet
$tmpl->setvar('fluxd_Fluxinet_enabled', $cfg["fluxd_Fluxinet_enabled"]);
$tmpl->setvar('fluxd_Fluxinet_state', (($cfg["fluxd_Fluxinet_enabled"] == 1) && ($fluxdRunning)) ? $fluxd->modState('Fluxinet') : 0);
$tmpl->setvar('fluxd_Fluxinet_port', $cfg["fluxd_Fluxinet_port"]);

// Maintenance
$tmpl->setvar('fluxd_Maintenance_enabled', $cfg["fluxd_Maintenance_enabled"]);
$tmpl->setvar('fluxd_Maintenance_state', (($cfg["fluxd_Maintenance_enabled"] == 1) && ($fluxdRunning)) ? $fluxd->modState('Maintenance') : 0);
$tmpl->setvar('fluxd_Maintenance_interval', $cfg["fluxd_Maintenance_interval"]);
$tmpl->setvar('fluxd_Maintenance_trestart', $cfg["fluxd_Maintenance_trestart"]);

// Trigger
$tmpl->setvar('fluxd_Trigger_enabled', $cfg["fluxd_Trigger_enabled"]);
$tmpl->setvar('fluxd_Trigger_state', (($cfg["fluxd_Trigger_enabled"] == 1) && ($fluxdRunning)) ? $fluxd->modState('Trigger') : 0);
$tmpl->setvar('fluxd_Trigger_interval', $cfg["fluxd_Trigger_interval"]);

// array with all clients
$clients = array('tornado', 'transmission', 'mainline', 'wget');
// get informations
$output = "";
foreach($clients as $client) {
	$running = getRunningTransfers($client);
	foreach ($running as $key => $value) {
		$rt = RunningTransfer::getRunningTransferInstance($value, $cfg, $client);
		$output .= $rt->BuildAdminOutput($cfg['theme']);
		unset($rt);
	}
}
if(strlen($output) == 0)
	$output = "<tr><td colspan=3><div class=\"tiny\" align=center>No Running Transfers</div></td></tr>";
$tmpl->setvar('output', $output);
$tmpl->setvar('fluxdRunning', $fluxdRunning);
$tmpl->setvar('showTransfers', (($cfg["fluxd_Qmgr_enabled"] == 1) && ($fluxdRunning)) ? 1 : 0);
//
$tmpl->setvar('_USER', $cfg['_USER']);
$tmpl->setvar('_FILE', $cfg['_FILE']);
$tmpl->setvar('_TIMESTAMP', $cfg['_TIMESTAMP']);
$tmpl->setvar('_FORCESTOP', str_replace(" ","<br>",$cfg['_FORCESTOP']));
//
tmplSetTitleBar("Administration - Fluxd Settings");
tmplSetAdminMenu();
tmplSetFoot();

// set iid-var
$tmpl->setvar('iid', $_REQUEST["iid"]);

// parse template
$tmpl->pparse();

?>