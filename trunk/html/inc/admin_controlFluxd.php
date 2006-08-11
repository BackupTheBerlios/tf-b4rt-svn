<?php
/* $Id$ */
$message = "";
$action = getRequestVar('a');
include_once('Fluxd.php');

switch($action) {
	case "start":
		// save settings
		$settings = array();
		saveSettings($settings);
		AuditAction($cfg["constants"]["admin"], " Updating Fluxd Settings");
		// start Fluxd
		$fluxd = new Fluxd($cfg);
		if ($fluxd->isFluxdReadyToStart()) {
			$fluxd->startFluxd();
			$message = '<br><strong>Fluxd started.</strong><br><br>';
			break;
		}
		$message = '<br><font color="red">Error starting fluxd</font><br><br>';
	break;

	case "stop":
		// save settings
		$settings = array();
		saveSettings($settings);
		AuditAction($cfg["constants"]["admin"], " Updating Fluxd Settings");
		// kill Fluxd
		$fluxd = new Fluxd($cfg);
		if ($fluxd->isFluxdRunning()) {
			$fluxd->stopFluxd();
			$message = '<br><strong>Stop-Command sent. Wait until shutdown and dont click stop again now !</strong><br><br>';
			header("Location: admin.php?op=fluxdSettings&m=".urlencode($message).'&s=1');
			exit;
		}
	break;

	default:
		$message = '<br><font color="red">Error : no control-operation.</font><br><br>';
	break;
}
if ($message != "")
	header("Location: index.php?page=admin&op=fluxdSettings&m=".urlencode($message));
else
header("Location: index.php?page=admin&op=fluxdSettings");
?>
