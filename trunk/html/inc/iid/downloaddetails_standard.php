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

// Load saved settings
loadTorrentSettingsToConfig($torrent);

// totals
$afu = $af->uptotal;
$afd = $af->downtotal;
$totalsCurrent = getTransferTotalsCurrentOP($torrent, $cfg['hash'], $cfg['btclient'], $afu, $afd);
$totals = getTransferTotalsOP($torrent, $cfg['hash'], $cfg['btclient'], $afu, $afd);

// set vars

// size
$torrentSize = $af->size;
$tmpl->setvar('size', formatBytesTokBMBGBTB($torrentSize));

// sharing
if ($torrentSize == 0)
	$tmpl->setvar('label_sharing', "0%");
else
	$tmpl->setvar('label_sharing', (number_format((($totals["uptotal"] / $torrentSize) * 100), 2)).'%');

// totals
$tmpl->setvar('downTotal', formatFreeSpace($totals["downtotal"] / 1048576));
$tmpl->setvar('upTotal', formatFreeSpace($totals["uptotal"] / 1048576));

// sharekill
if ($cfg["sharekill"] != 0)
	$tmpl->setvar('label_sharekill', $cfg["sharekill"].'%');
else
	$tmpl->setvar('label_sharekill', '&#8734');

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
	$tmpl->setvar('cons', netstatConnectionsByPid($torrent_pid)." (".$cfg["maxcons"].")");

	// down speed
	if ($cfg["max_download_rate"] != 0)
		$tmpl->setvar('down_speed', $af->down_speed." (".number_format($cfg["max_download_rate"], 2).")");
	else
		$tmpl->setvar('down_speed', $af->down_speed.' (&#8734)');

	// up speed
	if ($cfg["max_upload_rate"] != 0)
		$tmpl->setvar('up_speed', $af->up_speed." (".number_format($cfg["max_upload_rate"], 2).")");
	else
		$tmpl->setvar('up_speed', $af->up_speed.' (&#8734)');

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

	// down speed
	$tmpl->setvar('down_speed', "");

	// up speed
	$tmpl->setvar('up_speed', "");
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

// refresh
$tmpl->setvar('meta_refresh', $cfg['details_update'].';URL=index.php?iid=downloaddetails&torrent='.$torrent.'&alias='.$alias);

// title + foot
tmplSetTitleBar($cfg['_DOWNLOADDETAILS'], false);
tmplSetFoot(false);

?>