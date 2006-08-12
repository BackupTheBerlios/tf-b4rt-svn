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
	if ($cfg["fluxd_Qmgr_enabled"] != 0) {
		// fluxd Running?
		include_once("Fluxd.php");
		$fluxd = new Fluxd($cfg);
		if ($fluxd->isFluxdRunning()) {
			$needsRestart = false;
			$needsHUP = false;
			$needsInit = false;
			if ($_POST["perlCmd"] != $cfg["perlCmd"] ||
				$_POST["fluxd_path_fluxcli"] != $cfg["fluxd_path_fluxcli"] ||
				$_POST["fluxd_path"] != $cfg["fluxd_path"]) {
					$needsRestart = true;
			}
			if ($_POST["fluxd_Qmgr_enabled"] != $cfg["fluxd_Qmgr_enabled"] ||
				$_POST["fluxd_Fluxinet_enabled"] != $cfg["fluxd_Fluxinet_enabled"] ||
				$_POST["fluxd_Clientmaint_enabled"] != $cfg["fluxd_Clientmaint_enabled"] ||
				$_POST["fluxd_Trigger_enabled"] != $cfg["fluxd_Trigger_enabled"] ||
				$_POST["fluxd_Watch_enabled"] != $cfg["fluxd_Watch_enabled"]) {
					$needsHUP = true;
			}
			if ($needsRestart) {
				$needsHUP = false;
				$message .= 'You have to restart fluxd to use the new settings.<br><br>';
			}
			if ($needsHUP) {
				$fluxd->sendSigHUP();
			}
			// reconfig of running daemon
			if ($_POST["fluxd_loglevel"] != $cfg["fluxd_loglevel"]) {
				$fluxd->setConfig('LOGLEVEL',$_POST["fluxd_loglevel"]);
				sleep(1);
			}
			if ($_POST["fluxd_Qmgr_maxTotalTorrents"] != $cfg["fluxd_Qmgr_maxTotalTorrents"]) {
			   $fluxd->setConfig('Qmgr::MAX_TORRENTS',$_POST["fluxd_Qmgr_maxTotalTorrents"]);
			   sleep(1);
			}
			if ($_POST["fluxd_Qmgr_maxUserTorrents"] != $cfg["fluxd_Qmgr_maxUserTorrents"]) {
			   $fluxd->setConfig('Qmgr::MAX_USER',$_POST["fluxd_Qmgr_maxUserTorrents"]);
			}
		} else {
		   $message .= 'fluxd is not currently running.<br><br>';
		}
	} else {
		$message .= '<br><br>';
	}
	$settings = $_POST;
	saveSettings($settings);
	AuditAction($cfg["constants"]["admin"], " Updating fluxd Settings");
	header("Location: index.php?page=admin&op=fluxdSettings&m=".urlencode($message));
} else {
	$settings = $_POST;
	saveSettings($settings);
	AuditAction($cfg["constants"]["admin"], " Updating fluxd Settings");
	header("Location: index.php?page=admin&op=fluxdSettings");
}
?>