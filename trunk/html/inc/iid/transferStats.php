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
	@header("location: ../../index.php");
	exit();
}

/******************************************************************************/

// common functions
require_once('inc/functions/functions.common.php');

// request-vars
$transfer = getRequestVar('transfer');
if (empty($transfer))
	@error("missing params", "index.php?iid=index", "", array('transfer'));

// validate transfer
if (isValidTransfer($transfer) !== true) {
	AuditAction($cfg["constants"]["error"], "INVALID TRANSFER: ".$transfer);
	@error("Invalid Transfer", "", "", array($transfer));
}

// init template-instance
tmplInitializeInstance($cfg["theme"], "page.transferStats.tmpl");

// set transfer vars
$tmpl->setvar('transfer', $transfer);
$transferLabel = (strlen($transfer) >= 39) ? substr($transfer, 0, 35)."..." : $transfer;
$tmpl->setvar('transferLabel', $transferLabel);

// stat
$transferowner = getOwner($transfer);
$sf = new StatFile($transfer, $transferowner);

// init ch-instance
$ch = ClientHandler::getInstance(getTransferClient($transfer));

// load settings
$loaded = $ch->settingsLoad($transfer);
// default-settings if settings could not be loaded (fresh transfer)
if ($loaded !== true)
	$ch->settingsDefault();

// totals
$afu = $sf->uptotal;
$afd = $sf->downtotal;
$totalsCurrent = $ch->getTransferCurrentOP($transfer, $ch->hash, $afu, $afd);
$totals = $ch->getTransferTotalOP($transfer, $ch->hash, $afu, $afd);
// owner
$tmpl->setvar('transferowner', $transferowner);

// size
$transferSize = floatval($sf->size);
$tmpl->setvar('size', @formatBytesTokBMBGBTB($transferSize));

// sharing
$tmpl->setvar('sharing', ($transferSize > 0) ? @number_format((($totals["uptotal"] / $transferSize) * 100), 2) : "0");

// totals
$tmpl->setvar('downTotal', @formatFreeSpace($totals["downtotal"] / 1048576));
$tmpl->setvar('upTotal', @formatFreeSpace($totals["uptotal"] / 1048576));

// more
if ($sf->running == 1) {

	// running
	$tmpl->setvar('running', 1);

	// current totals
	$tmpl->setvar('downTotalCurrent', formatFreeSpace($totalsCurrent["downtotal"] / 1048576));
	$tmpl->setvar('upTotalCurrent', formatFreeSpace($totalsCurrent["uptotal"] / 1048576));

	// seeds + peers
	$tmpl->setvar('seeds', $sf->seeds);
	$tmpl->setvar('peers', $sf->peers);

	// port + cons
	$transfer_pid = getTransferPid($transfer);
	$tmpl->setvar('port', netstatPortByPid($transfer_pid));
	$tmpl->setvar('cons', netstatConnectionsByPid($transfer_pid));

	// up speed
	$tmpl->setvar('up_speed', (trim($sf->up_speed) != "") ?  $sf->up_speed : '0.0 kB/s');

	// down speed
	$tmpl->setvar('down_speed', (trim($sf->down_speed) != "") ? $sf->down_speed : '0.0 kB/s');

	// sharekill
	$tmpl->setvar('sharekill', ($ch->sharekill != 0) ? $ch->sharekill.'%' : '&#8734');

} else {

	// running
	$tmpl->setvar('running', 0);

	// current totals
	$tmpl->setvar('downTotalCurrent', "");
	$tmpl->setvar('upTotalCurrent', "");

	// seeds + peers
	$tmpl->setvar('seeds', "");
	$tmpl->setvar('peers', "");

	// port + cons
	$tmpl->setvar('port', "");
	$tmpl->setvar('cons', "");

	// up speed
	$tmpl->setvar('up_speed', "");

	// down speed
	$tmpl->setvar('down_speed', "");

	// sharekill
	$tmpl->setvar('sharekill', "");
}

// percent and eta
if ($sf->percent_done < 0) {
	$sf->percent_done = round(($sf->percent_done*-1)-100,1);
	$sf->time_left = $cfg['_INCOMPLETE'];
}
$tmpl->setvar('time_left', $sf->time_left);

// graph width
if ($sf->percent_done < 1) {
	$tmpl->setvar('graph_width1', 3.5);
	$tmpl->setvar('graph_width2', 100 * 3.5);
} else {
	$tmpl->setvar('graph_width1', $sf->percent_done * 3.5);
	$tmpl->setvar('graph_width2', (100 - $sf->percent_done) * 3.5);
}
if ($sf->percent_done >= 100) {
	$sf->percent_done = 100;
	$tmpl->setvar('background', "#0000ff");
} else {
	$tmpl->setvar('background', "#000000");
}

// percentage
$tmpl->setvar('percent_done', $sf->percent_done);

// standard / ajax switch
$tmpl->setvar('transferStatsType', $cfg['transferStatsType']);
switch ($cfg['transferStatsType']) {
	default:
	case "standard":
		// refresh
		$tmpl->setvar('meta_refresh', $cfg['transferStatsUpdate'].';URL=index.php?iid=transferStats&transfer='.$transfer);
		break;
	case "ajax":
		$tmpl->setvar('_DOWNLOADDETAILS', $cfg['_DOWNLOADDETAILS']);
		// onload
		$tmpl->setvar('onLoad', "ajax_initialize(".(intval($cfg['transferStatsUpdate']) * 1000).",'".$cfg['stats_txt_delim']."','".$transfer."');");
		break;
}

// language vars
$tmpl->setvar('_USER', $cfg['_USER']);
$tmpl->setvar('_SHARING', $cfg['_SHARING']);
$tmpl->setvar('_ID_CONNECTIONS', $cfg['_ID_CONNECTIONS']);
$tmpl->setvar('_ID_PORT', $cfg['_ID_PORT']);
$tmpl->setvar('_DOWNLOADSPEED', $cfg['_DOWNLOADSPEED']);
$tmpl->setvar('_UPLOADSPEED', $cfg['_UPLOADSPEED']);
$tmpl->setvar('_PERCENTDONE', $cfg['_PERCENTDONE']);
$tmpl->setvar('_ESTIMATEDTIME', $cfg['_ESTIMATEDTIME']);

// title + foot
tmplSetFoot(false);
tmplSetTitleBar($transferLabel." - ".$cfg['_DOWNLOADDETAILS'], false);

// iid
$tmpl->setvar('iid', $_REQUEST["iid"]);

// parse template
$tmpl->pparse();

?>