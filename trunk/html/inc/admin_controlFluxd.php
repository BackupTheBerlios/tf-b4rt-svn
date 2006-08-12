<?php
/* $Id$ */

$message = "";
$action = getRequestVar('a');
include_once('Fluxd.php');

switch($action) {
	case "start":
		// start fluxd
		$fluxd = new Fluxd(serialize($cfg));
		if ($fluxd->isFluxdReadyToStart()) {
			$fluxd->startFluxd();
			if ($fluxd->state == 2) {
				$message = '<br><strong>fluxd started.</strong><br><br>';
			} else {
				$message = '<br><font color="red">Error starting fluxd</font><br>';
				$message .= 'Error : '.$fluxd->messages . '<br>';
			}
			break;
		}
		$message = '<br><font color="red">Error starting fluxd</font><br><br>';
	break;

	case "stop":
		// kill fluxd
		$fluxd = new Fluxd(serialize($cfg));
		if ($fluxd->isFluxdRunning()) {
			$fluxd->stopFluxd();
			$message = '<br><strong>Stop-Command sent. Wait until shutdown and dont click stop again now !</strong><br><br>';
			header("Location: index.php?page=admin&op=fluxdSettings&m=".urlencode($message).'&s=1');
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