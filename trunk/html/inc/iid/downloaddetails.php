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

// alias-file
require_once("inc/classes/AliasFile.php");

// request-vars
$transfer = getRequestVar('torrent');
$alias = getRequestVar('alias');

// create template-instance
$tmpl = tmplGetInstance($cfg["theme"], "page.downloaddetails.tmpl");

// check
if ((!empty($transfer)) && (!empty($alias))) {
	$tmpl->setvar('torrent', $transfer);
	$tmpl->setvar('alias', $alias);
	if (strlen($transfer) >= 39)
		$tmpl->setvar('torrentLabel', substr($transfer, 0, 35)."...");
	else
		$tmpl->setvar('torrentLabel', $transfer);
} else {
	showErrorPage("missing params");
}

// alias / stat
if ((substr(strtolower($transfer), -8) == ".torrent")) {
	// this is a torrent-client
	$transferowner = getOwner($transfer);
	$transferExists = loadTorrentSettingsToConfig($transfer);
	if (!$transferExists) {
		// new torrent
		$cfg['hash'] = $transfer;
	}
	$af = AliasFile::getAliasFileInstance($cfg["transfer_file_path"].$alias, $transferowner, $cfg, $cfg['btclient']);
} else if ((substr(strtolower($transfer), -5) == ".wget")) {
	// this is wget.
	$transferowner = getOwner($transfer);
	$cfg['btclient'] = "wget";
	$cfg['hash'] = $transfer;
	$af = AliasFile::getAliasFileInstance($cfg["transfer_file_path"].$alias, $transferowner, $cfg, "wget");
} else {
	// this is "something else". use tornado statfile as default
	$transferowner = $cfg["user"];
	$cfg['btclient'] = "tornado";
	$cfg['hash'] = $transfer;
	$af = AliasFile::getAliasFileInstance($cfg["transfer_file_path"].$alias, $cfg["user"], $cfg, 'tornado');
}

// totals
$afu = $af->uptotal;
$afd = $af->downtotal;
$totalsCurrent = getTransferTotalsCurrentOP($transfer, $cfg['hash'], $cfg['btclient'], $afu, $afd);
$totals = getTransferTotalsOP($transfer, $cfg['hash'], $cfg['btclient'], $afu, $afd);

// owner
$tmpl->setvar('transferowner', $transferowner);

// size
$torrentSize = $af->size;
$tmpl->setvar('size', formatBytesTokBMBGBTB($torrentSize));

// sharing
if ($torrentSize == 0)
	$tmpl->setvar('sharing', "0");
else
	$tmpl->setvar('sharing', (number_format((($totals["uptotal"] / $torrentSize) * 100), 2)));

// totals
$tmpl->setvar('downTotal', formatFreeSpace($totals["downtotal"] / 1048576));
$tmpl->setvar('upTotal', formatFreeSpace($totals["uptotal"] / 1048576));

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
	$torrent_pid = getTransferPid($alias);
	$tmpl->setvar('port', netstatPortByPid($torrent_pid));
	$tmpl->setvar('cons', netstatConnectionsByPid($torrent_pid));
	$tmpl->setvar('maxcons', ' ('.$cfg["maxcons"].')');

	// down speed
	if (trim($af->down_speed) != "")
		$tmpl->setvar('down_speed', $af->down_speed);
	else
		$tmpl->setvar('down_speed', '0.0 kB/s');
	if ($cfg["max_download_rate"] != 0)
		$tmpl->setvar('max_download_rate', ' ('.number_format($cfg["max_download_rate"], 2).')');
	else
		$tmpl->setvar('max_download_rate', ' (&#8734)');

	// up speed
	if (trim($af->up_speed) != "")
		$tmpl->setvar('up_speed', $af->up_speed);
	else
		$tmpl->setvar('up_speed', '0.0 kB/s');
	if ($cfg["max_upload_rate"] != 0)
		$tmpl->setvar('max_upload_rate', ' ('.number_format($cfg["max_upload_rate"], 2).')');
	else
		$tmpl->setvar('max_upload_rate', ' (&#8734)');

	// sharekill
	if ($cfg["sharekill"] != 0)
		$tmpl->setvar('sharekill', $cfg["sharekill"].'%');
	else
		$tmpl->setvar('sharekill', '&#8734');

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
		$tmpl->setvar('meta_refresh', $cfg['details_update'].';URL=index.php?iid=downloaddetails&torrent='.$transfer.'&alias='.$alias);
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