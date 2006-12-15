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
	header("location: ../../index.php");
	exit();
}

/******************************************************************************/

// request-vars
$transfer = getRequestVar('transfer');
$aliasFile = getAliasName($transfer).".stat";

// init template-instance
tmplInitializeInstance($cfg["theme"], "page.downloaddetails.tmpl");

// check
if ((!empty($transfer)) && (!empty($aliasFile))) {
	$tmpl->setvar('transfer', $transfer);
	$tmpl->setvar('transferLabel', (strlen($transfer) >= 39) ? substr($transfer, 0, 35)."..." : $transfer);
} else {
	showErrorPage("missing params");
}

// alias / stat
if ((substr(strtolower($transfer), -8) == ".torrent")) {
	// this is a t-client
	$transferowner = getOwner($transfer);
	$transferExists = loadTorrentSettingsToConfig($transfer);
	if (!$transferExists) {
		// new t
		$cfg['hash'] = $transfer;
	}
	$af = new AliasFile($aliasFile, $transferowner);
} else if ((substr(strtolower($transfer), -5) == ".wget")) {
	// this is wget.
	$transferowner = getOwner($transfer);
	$cfg['btclient'] = "wget";
	$cfg['hash'] = $transfer;
	$af = new AliasFile($aliasFile, $transferowner);
} else {
	// this is "something else". use tornado statfile as default
	$transferowner = $cfg["user"];
	$cfg['btclient'] = "tornado";
	$cfg['hash'] = $transfer;
	$af = new AliasFile($aliasFile, $cfg["user"]);
}

// totals
$afu = $af->uptotal;
$afd = $af->downtotal;
$clientHandler = ClientHandler::getInstance($cfg['btclient']);
$totalsCurrent = $clientHandler->getTransferCurrentOP($transfer, $cfg['hash'], $afu, $afd);
$totals = $clientHandler->getTransferTotalOP($transfer, $cfg['hash'], $afu, $afd);
// owner
$tmpl->setvar('transferowner', $transferowner);

// size
$transferSize = (int) $af->size;
$tmpl->setvar('size', @formatBytesTokBMBGBTB($transferSize));

// sharing
$tmpl->setvar('sharing', ($transferSize > 0) ? @number_format((($totals["uptotal"] / $transferSize) * 100), 2) : "0");

// totals
$tmpl->setvar('downTotal', @formatFreeSpace($totals["downtotal"] / 1048576));
$tmpl->setvar('upTotal', @formatFreeSpace($totals["uptotal"] / 1048576));

// more
if ($af->running == 1) {

	// running
	$tmpl->setvar('running', 1);

	// current totals
	$tmpl->setvar('downTotalCurrent', formatFreeSpace($totalsCurrent["downtotal"] / 1048576));
	$tmpl->setvar('upTotalCurrent', formatFreeSpace($totalsCurrent["uptotal"] / 1048576));

	// seeds + peers
	$tmpl->setvar('seeds', $af->seeds);
	$tmpl->setvar('peers', $af->peers);

	// port + cons
	$transfer_pid = getTransferPid($aliasFile);
	$tmpl->setvar('port', netstatPortByPid($transfer_pid));
	$tmpl->setvar('cons', netstatConnectionsByPid($transfer_pid));
	$tmpl->setvar('maxcons', '('.$cfg["maxcons"].')');

	// down speed
	$tmpl->setvar('down_speed', (trim($af->down_speed) != "") ? $af->down_speed : '0.0 kB/s');
	$tmpl->setvar('max_download_rate', ($cfg["max_download_rate"] != 0) ? ' ('.number_format($cfg["max_download_rate"], 2).')' : ' (&#8734)');

	// up speed
	$tmpl->setvar('up_speed', (trim($af->up_speed) != "") ?  $af->up_speed : '0.0 kB/s');
	$tmpl->setvar('max_upload_rate', ($cfg["max_upload_rate"] != 0) ? ' ('.number_format($cfg["max_upload_rate"], 2).')' : ' (&#8734)');

	// sharekill
	$tmpl->setvar('sharekill', ($cfg["sharekill"] != 0) ? $cfg["sharekill"].'%' : '&#8734');

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
	$tmpl->setvar('maxcons', "");

	// down speed
	$tmpl->setvar('down_speed', "");
	$tmpl->setvar('max_download_rate', "");

	// up speed
	$tmpl->setvar('up_speed', "");
	$tmpl->setvar('max_upload_rate', "");

	// sharekill
	$tmpl->setvar('sharekill', "");
}

// percent and eta
if ($af->percent_done < 0) {
	$af->percent_done = round(($af->percent_done*-1)-100,1);
	$af->time_left = $cfg['_INCOMPLETE'];
}
$tmpl->setvar('time_left', $af->time_left);

// graph width
if ($af->percent_done < 1) {
	$tmpl->setvar('graph_width1', 3.5);
	$tmpl->setvar('graph_width2', 100 * 3.5);
} else {
	$tmpl->setvar('graph_width1', $af->percent_done * 3.5);
	$tmpl->setvar('graph_width2', (100 - $af->percent_done) * 3.5);
}
if ($af->percent_done >= 100) {
	$af->percent_done = 100;
	$tmpl->setvar('background', "#0000ff");
} else {
	$tmpl->setvar('background', "#000000");
}

// percentage
$tmpl->setvar('percent_done', $af->percent_done);

// hd
$hd = getStatusImage($af);
$tmpl->setvar('hd_image', $hd->image);
$tmpl->setvar('hd_title', $hd->title);

// errors
$errorCount = sizeof($af->errors);
if ($errorCount > 0) {
	$error = "";
	for ($inx = 0; $inx < $errorCount; $inx++)
		$error .= "<li style=\"font-size:10px;color:#ff0000;\">".$af->errors[$inx]."</li>";
	$tmpl->setvar('is_error', 1);
	$tmpl->setvar('error', $error);
} else {
	$tmpl->setvar('is_error', 0);
}

// standard / ajax switch
$tmpl->setvar('details_type', $cfg['details_type']);
switch ($cfg['details_type']) {
	default:
	case "standard":
		// refresh
		$tmpl->setvar('meta_refresh', $cfg['details_update'].';URL=index.php?iid=downloaddetails&transfer='.$transfer);
		break;
	case "ajax":
		$tmpl->setvar('_DOWNLOADDETAILS', $cfg['_DOWNLOADDETAILS']);
		// onload
		$tmpl->setvar('onLoad', "ajax_initialize(".(((int) $cfg['details_update']) * 1000).",'".$cfg['stats_txt_delim']."','".$transfer."');");
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
tmplSetTitleBar($cfg["pagetitle"]." - ".$cfg['_DOWNLOADDETAILS'], false);

// iid
$tmpl->setvar('iid', $_REQUEST["iid"]);

// parse template
$tmpl->pparse();

?>