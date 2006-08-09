<?php
/* $Id$ */
$message = "";
$action = getRequestVar('a');
switch($action) {
	case "start":
		$queuemanager = getRequestVar('queuemanager');
		if ((isset($queuemanager)) && ($queuemanager != "")) {
			// save settings
			$settings = array();
			$settings['queuemanager'] = $queuemanager;
			$settings['AllowQueing'] = 1;
			saveSettings($settings);
			AuditAction($cfg["constants"]["admin"], " Updating TorrentFlux QueueManager Settings");
			// start QueueManager
			include_once("QueueManager.php");
			$queueManager = QueueManager::getQueueManagerInstance($cfg,$queuemanager);
			if ($queueManager->isQueueManagerReadyToStart()) {
				if ($queueManager->prepareQueueManager()) {
					$queueManager->startQueueManager();
					$message = '<br><strong>QueueManager '. $queueManager->managerName .' started.</strong><br><br>';
					break;
				}
			}
			$message = '<br><font color="red">Error starting queuemanager '.$queuemanager.'</font><br><br>';
		} else {
			$message = '<br><font color="red">Error : queuemanager not set.</font><br><br>';
		}
	break;
	case "stop":
		// save settings
		$settings = array();
		$settings['AllowQueing'] = 0;
		saveSettings($settings);
		AuditAction($cfg["constants"]["admin"], " Updating TorrentFlux QueueManager Settings");
		// kill QueueManager
		include_once("QueueManager.php");
		$queueManager = QueueManager::getQueueManagerInstance($cfg);
		if ($queueManager->isQueueManagerRunning()) {
			$queueManager->stopQueueManager();
			$message = '<br><strong>Stop-Command sent. Wait until shutdown and dont click stop again now !</strong><br><br>';
			header("Location: index.php?page=admin&op=queueSettings&m=".urlencode($message).'&s=1');
			exit;
		}
	break;
	default:
		$message = '<br><font color="red">Error : no control-operation.</font><br><br>';
	break;
}
if ($message != "")
	header("Location: index.php?page=admin&op=queueSettings&m=".urlencode($message));
else
header("Location: index.php?page=admin&op=queueSettings");
?>