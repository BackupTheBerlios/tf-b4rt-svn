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

if ($_POST["fluxd_loglevel"] != $cfg["fluxd_loglevel"] ||
	$_POST["fluxd_Qmgr_enabled"] != $cfg["fluxd_Qmgr_enabled"] ||
	$_POST["fluxd_Fluxinet_enabled"] != $cfg["fluxd_Fluxinet_enabled"] ||
	$_POST["fluxd_Clientmaint_enabled"] != $cfg["fluxd_Clientmaint_enabled"] ||
	$_POST["fluxd_Trigger_enabled"] != $cfg["fluxd_Trigger_enabled"] ||
	$_POST["fluxd_Watch_enabled"] != $cfg["fluxd_Watch_enabled"] ||
	$_POST["fluxd_Qmgr_maxUserTorrents"] != $cfg["fluxd_Qmgr_maxUserTorrents"] ||
	$_POST["fluxd_Qmgr_maxTotalTorrents"] != $cfg["fluxd_Qmgr_maxTotalTorrents"] ||
	$_POST["fluxd_Fluxinet_port"] != $cfg["fluxd_Fluxinet_port"] ||
	$_POST["fluxd_Watch_jobs"] != $cfg["fluxd_Watch_jobs"] ||
	$_POST["fluxd_Clientmaint_interval"] != $cfg["fluxd_Clientmaint_interval"])
{
	$message = '<br>Settings changed.<br>';

	// fluxd Running?
	if ($fluxdRunning) {
		$needsRestart = false;
		$reloadModules = false;
		$needsInit = false;
		// TODO : add module-configs to trigger reload on config-change
		if ($_POST["fluxd_Qmgr_enabled"] != $cfg["fluxd_Qmgr_enabled"] ||
			$_POST["fluxd_Fluxinet_enabled"] != $cfg["fluxd_Fluxinet_enabled"] ||
			$_POST["fluxd_Clientmaint_enabled"] != $cfg["fluxd_Clientmaint_enabled"] ||
			$_POST["fluxd_Trigger_enabled"] != $cfg["fluxd_Trigger_enabled"] ||
			$_POST["fluxd_Watch_enabled"] != $cfg["fluxd_Watch_enabled"] ||
			$_POST["fluxd_Qmgr_maxTotalTorrents"] != $cfg["fluxd_Qmgr_maxTotalTorrents"] ||
			$_POST["fluxd_Qmgr_maxUserTorrents"] != $cfg["fluxd_Qmgr_maxUserTorrents"]
			) {
			$reloadModules = true;
		}
		if ($needsRestart) {
			$needsHUP = false;
			$message .= 'You have to restart fluxd to use the new settings.<br><br>';
		}
		// reconfig of running daemon :
		if ($_POST["fluxd_loglevel"] != $cfg["fluxd_loglevel"]) {
			$fluxd->setConfig('LOGLEVEL',$_POST["fluxd_loglevel"]);
			sleep(1);
		}
		// save settings
		$settings = $_POST;
		saveSettings('tf_settings', $settings);
		// reload fluxd-database-cache
		$fluxd->reloadDBCache();
		// reload fluxd-modules
		if ($reloadModules) {
			sleep(1);
			$fluxd->reloadModules();
		}
	} else {
		// save settings
		$settings = $_POST;
		saveSettings('tf_settings', $settings);
		$message .= 'fluxd is not currently running.<br><br>';
	}
	// log
	AuditAction($cfg["constants"]["admin"], " Updating fluxd Settings");
	// redir
	header("Location: index.php?iid=admin&op=fluxdSettings&m=".urlencode($message));
} else {
	// save settings
	$settings = $_POST;
	saveSettings('tf_settings', $settings);
	// log
	AuditAction($cfg["constants"]["admin"], " Updating fluxd Settings");
	// redir
	header("Location: index.php?iid=admin&op=fluxdSettings");
}

?>
