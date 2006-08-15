<?php
/* $Id$ */
if ($_POST["perlCmd"] != $cfg["perlCmd"] ||
	$_POST["fluxd_path"] != $cfg["fluxd_path"] ||
	$_POST["fluxd_path_fluxcli"] != $cfg["fluxd_path_fluxcli"] ||
	$_POST["fluxd_loglevel"] != $cfg["fluxd_loglevel"] ||
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
	include_once("Fluxd.php");
	$fluxd = new Fluxd(serialize($cfg));
	$fluxRunning = $fluxd->isFluxdRunning();
	if ($fluxRunning) {
		$needsRestart = false;
		$reloadModules = false;
		$needsInit = false;
		if ($_POST["perlCmd"] != $cfg["perlCmd"] ||
			$_POST["fluxd_path_fluxcli"] != $cfg["fluxd_path_fluxcli"] ||
			$_POST["fluxd_path"] != $cfg["fluxd_path"]) {
			$needsRestart = true;
		}
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
		saveSettings($settings);
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
		saveSettings($settings);
		$message .= 'fluxd is not currently running.<br><br>';
	}
	// log
	AuditAction($cfg["constants"]["admin"], " Updating fluxd Settings");
	// redir
	header("Location: index.php?page=admin&op=fluxdSettings&m=".urlencode($message));
} else {
	// save settings
	$settings = $_POST;
	saveSettings($settings);
	// log
	AuditAction($cfg["constants"]["admin"], " Updating fluxd Settings");
	// redir
	header("Location: index.php?page=admin&op=fluxdSettings");
}
?>