<?php
/* $Id$ */
if ($_POST["AllowQueing"] != $cfg["AllowQueing"] ||
	$_POST["maxServerThreads"] != $cfg["maxServerThreads"] ||
	$_POST["maxUserThreads"] != $cfg["maxUserThreads"] ||
	$_POST["sleepInterval"] != $cfg["sleepInterval"] ||
	$_POST["debugTorrents"] != $cfg["debugTorrents"] ||
	$_POST["tfQManager"] != $cfg["tfQManager"] ||
	$_POST["btphpbin"] != $cfg["btphpbin"] ||
	$_POST["queuemanager"] != $cfg["queuemanager"] ||
	$_POST["perlCmd"] != $cfg["perlCmd"] ||
	$_POST["tfqmgr_path"] != $cfg["tfqmgr_path"] ||
	$_POST["tfqmgr_path_fluxcli"] != $cfg["tfqmgr_path_fluxcli"] ||
	$_POST["tfqmgr_limit_global"] != $cfg["tfqmgr_limit_global"] ||
	$_POST["tfqmgr_limit_user"] != $cfg["tfqmgr_limit_user"] ||
	$_POST["tfqmgr_loglevel"] != $cfg["tfqmgr_loglevel"] ||
	$_POST["Qmgr_path"] != $cfg["Qmgr_path"] ||
	$_POST["Qmgr_maxUserTorrents"] != $cfg["Qmgr_maxUserTorrents"] ||
	$_POST["Qmgr_maxTotalTorrents"] != $cfg["Qmgr_maxTotalTorrents"] ||
	$_POST["Qmgr_perl"] != $cfg["Qmgr_perl"] ||
	$_POST["Qmgr_fluxcli"] != $cfg["Qmgr_fluxcli"] ||
	$_POST["Qmgr_host"] != $cfg["Qmgr_host"] ||
	$_POST["Qmgr_port"] != $cfg["Qmgr_port"] ||
	$_POST["Qmgr_loglevel"] != $cfg["Qmgr_loglevel"])
{
	$message = '<br>Settings changed.<br>';
	if ($cfg["AllowQueing"] != 0) {
		include_once("QueueManager.php");
		$queueManager = QueueManager::getQueueManagerInstance($cfg);
		// QueueManager Running ?
		if ($queueManager->isQueueManagerRunning()) {
			if (($queueManager->managerName == "tfqmgr") || ($queueManager->managerName == "Qmgr")) {
				$needsRestart = false;
				//
				if ($_POST["perlCmd"] != $cfg["perlCmd"])
					$needsRestart = true;
				if ($_POST["tfqmgr_path"] != $cfg["tfqmgr_path"])
					$needsRestart = true;
				if ($_POST["tfqmgr_path_fluxcli"] != $cfg["tfqmgr_path_fluxcli"])
					$needsRestart = true;
				//
				if ($_POST["Qmgr_path"] != $cfg["Qmgr_path"])
					$needsRestart = true;
				if ($_POST["Qmgr_perl"] != $cfg["Qmgr_perl"])
					$needsRestart = true;
				if ($_POST["Qmgr_fluxcli"] != $cfg["Qmgr_fluxcli"])
					$needsRestart = true;
				if ($_POST["Qmgr_host"] != $cfg["Qmgr_host"])
					$needsRestart = true;
				if ($_POST["Qmgr_port"] != $cfg["Qmgr_port"])
					$needsRestart = true;
				//
				if ($needsRestart)
				   $message .= 'You have to restart '. $queueManager->managerName .' to use the new Settings.<br><br>';
				// reconfig of running daemon
				switch ($queueManager->managerName) {
					case "tfqmgr":
						if ($_POST["tfqmgr_limit_global"] != $cfg["tfqmgr_limit_global"]) {
							$queueManager->setConfig('MAX_TORRENTS',$_POST["tfqmgr_limit_global"]);
							sleep(1);
						}
						if ($_POST["tfqmgr_limit_user"] != $cfg["tfqmgr_limit_user"]) {
						   $queueManager->setConfig('MAX_TORRENTS_PER_USER',$_POST["tfqmgr_limit_user"]);
						   sleep(1);
						}
						if ($_POST["tfqmgr_loglevel"] != $cfg["tfqmgr_loglevel"]) {
						   $queueManager->setConfig('LOGLEVEL',$_POST["tfqmgr_loglevel"]);
						}
						break;
					case "Qmgr":
						if ($_POST["Qmgr_maxUserTorrents"] != $cfg["Qmgr_maxUserTorrents"]) {
							$queueManager->setConfig('MAX_TORRENTS_USR',$_POST["Qmgr_maxUserTorrents"]);
							sleep(1);
						}
						if ($_POST["Qmgr_maxTotalTorrents"] != $cfg["Qmgr_maxTotalTorrents"]) {
							$queueManager->setConfig('MAX_TORRENTS_SYS',$_POST["Qmgr_maxTotalTorrents"]);
							sleep(1);
						}
						if ($_POST["Qmgr_loglevel"] != $cfg["Qmgr_loglevel"]) {
							$queueManager->setConfig('LOGLEVEL',$_POST["Qmgr_loglevel"]);
						}
						break;
				}
			} else {
			   $message .= 'You have to restart '. $queueManager->managerName .' to use the new Settings.<br><br>';
			}
		}
	} else {
		$message .= '<br><br>';
	}
	$settings = $_POST;
	saveSettings($settings);
	AuditAction($cfg["constants"]["admin"], " Updating TorrentFlux Queue Settings");
	header("Location: index.php?iid=admin&op=queueSettings&m=".urlencode($message));
} else {
	$settings = $_POST;
	saveSettings($settings);
	AuditAction($cfg["constants"]["admin"], " Updating TorrentFlux Queue Settings");
	header("Location: index.php?iid=admin&op=queueSettings");
}
?>