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

$message = "";
$action = getRequestVar('a');

switch($action) {
	case "start":
		// start fluxd
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
		if ($fluxd->isFluxdRunning()) {
			$fluxd->stopFluxd();
			if ($fluxd->isFluxdRunning())
				$message = '<br><strong>Stop-Command sent.</strong><br><br>';
			else
				$message = '<br><strong>fluxd stopped.</strong><br><br>';
			header("Location: index.php?iid=admin&op=fluxdSettings&m=".urlencode($message).'&s=1');
			exit;
		}
	break;

	default:
		$message = '<br><font color="red">Error : no control-operation.</font><br><br>';
	break;
}
if ($message != "")
	header("Location: index.php?iid=admin&op=fluxdSettings&m=".urlencode($message));
else
header("Location: index.php?iid=admin&op=fluxdSettings");

?>