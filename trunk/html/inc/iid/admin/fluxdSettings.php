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
$tmpl = tmplGetInstance($cfg["theme"], "page.admin.fluxdSettings.tmpl");

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
if ((isset($shutdown)) && ($shutdown == "1"))
	$tmpl->setvar('shutdown', 1);
else
	$tmpl->setvar('shutdown', 0);
// superadmin-links
$tmpl->setvar('SuperAdminLink1', getSuperAdminLink('?f=1','<font class="adminlink">log</font>'));
$tmpl->setvar('SuperAdminLink2', getSuperAdminLink('?f=2','<font class="adminlink">error-log</font>'));
$tmpl->setvar('SuperAdminLink3', getSuperAdminLink('?f=3','<font class="adminlink">ps</font>'));
$tmpl->setvar('SuperAdminLink4', getSuperAdminLink('?f=4','<font class="adminlink">status</font>'));
$tmpl->setvar('SuperAdminLink5', getSuperAdminLink('?f=5','<font class="adminlink">check</font>'));
$tmpl->setvar('SuperAdminLink6', getSuperAdminLink('?f=6','<font class="adminlink">db-debug</font>'));
// loglevel
$tmpl->setvar('fluxd_loglevel', $cfg["fluxd_loglevel"]);
// MODS
// Qmgr
$tmpl->setvar('fluxd_Qmgr_enabled', $cfg["fluxd_Qmgr_enabled"]);
if (($cfg["fluxd_Qmgr_enabled"] == 1) && ($fluxdRunning))
	$tmpl->setvar('fluxd_Qmgr_state', $fluxd->modState('Qmgr'));
else
	$tmpl->setvar('fluxd_Qmgr_state', 0);
$tmpl->setvar('fluxd_Qmgr_maxTotalTorrents', $cfg["fluxd_Qmgr_maxTotalTorrents"]);
$tmpl->setvar('fluxd_Qmgr_maxUserTorrents', $cfg["fluxd_Qmgr_maxUserTorrents"]);
// Fluxinet
$tmpl->setvar('fluxd_Fluxinet_enabled', $cfg["fluxd_Fluxinet_enabled"]);
if (($cfg["fluxd_Fluxinet_enabled"] == 1) && ($fluxdRunning))
	$tmpl->setvar('fluxd_Fluxinet_state', $fluxd->modState('Fluxinet'));
else
	$tmpl->setvar('fluxd_Fluxinet_state', 0);
$tmpl->setvar('fluxd_Fluxinet_port', $cfg["fluxd_Fluxinet_port"]);
// Watch
$tmpl->setvar('fluxd_Watch_enabled', $cfg["fluxd_Watch_enabled"]);
if (($cfg["fluxd_Watch_enabled"] == 1) && ($fluxdRunning))
	$tmpl->setvar('fluxd_Watch_state', $fluxd->modState('Watch'));
else
	$tmpl->setvar('fluxd_Watch_state', 0);
// TODO : process watch-jobs-settings-string
$tmpl->setvar('fluxd_Watch_jobs', $cfg["fluxd_Watch_jobs"]);
// Clientmaint
$tmpl->setvar('fluxd_Clientmaint_enabled', $cfg["fluxd_Clientmaint_enabled"]);
if (($cfg["fluxd_Clientmaint_enabled"] == 1) && ($fluxdRunning))
	$tmpl->setvar('fluxd_Clientmaint_state', $fluxd->modState('Clientmaint'));
else
	$tmpl->setvar('fluxd_Clientmaint_state', 0);
$tmpl->setvar('fluxd_Clientmaint_interval', $cfg["fluxd_Clientmaint_interval"]);
// Trigger
$tmpl->setvar('fluxd_Trigger_enabled', $cfg["fluxd_Trigger_enabled"]);
if (($cfg["fluxd_Trigger_enabled"] == 1) && ($fluxdRunning))
	$tmpl->setvar('fluxd_Trigger_state', $fluxd->modState('Trigger'));
else
	$tmpl->setvar('fluxd_Trigger_state', 0);
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
//
$tmpl->setvar('_USER', $cfg['_USER']);
$tmpl->setvar('_FILE', $cfg['_FILE']);
$tmpl->setvar('_TIMESTAMP', $cfg['_TIMESTAMP']);
$tmpl->setvar('_FORCESTOP', str_replace(" ","<br>",$cfg['_FORCESTOP']));
//
tmplSetTitleBar("Administration - Fluxd Settings");
tmplSetAdminMenu();
tmplSetFoot();
$tmpl->setvar('iid', $_REQUEST["iid"]);

// parse template
$tmpl->pparse();

?>