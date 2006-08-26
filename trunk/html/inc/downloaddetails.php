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

// require
require_once("inc/class/AliasFile.php");

# create new template
if (!ereg('^[^./][^/]*$', $cfg["theme"])) {
	$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/downloaddetails.tmpl");
}
else {
	$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/downloaddetails.tmpl");
}

$torrent = getRequestVar('torrent');
$error = "";
$transferowner = getOwner($torrent);
$graph_width = "";
$background = "#000000";
$alias = getRequestVar('alias');
if (!empty($alias)) {
	// create AliasFile object
	$af = AliasFile::getAliasFileInstance($cfg["torrent_file_path"].$alias, $transferowner, $cfg);
	for ($inx = 0; $inx < sizeof($af->errors); $inx++) {
		$error .= "<li style=\"font-size:10px;color:#ff0000;\">".$af->errors[$inx]."</li>";
	}
} else {
	die("fatal error torrent file not specified");
}

// Load saved settings
loadTorrentSettingsToConfig($torrent);

$torrentTotals = getTransferTotals($torrent);
$torrentTotalsCurrent = getTransferTotalsCurrent($torrent);
$upTotalCurrent = ($torrentTotalsCurrent["uptotal"]+0);
$downTotalCurrent = ($torrentTotalsCurrent["downtotal"]+0);
$upTotal =($torrentTotals["uptotal"]+0);
$downTotal = ($torrentTotals["downtotal"]+0);

// seeding-%
$torrentSize = $af->size+0;
if ($torrentSize == 0)
	$sharing = 0;
else
	$sharing = number_format((($upTotal / $torrentSize) * 100), 2);
$torrent_port = "";
$torrent_cons = "";
$label_max_download_rate = "";
$label_max_upload_rate = "";
$label_downTotal = formatFreeSpace($downTotal / 1048576);
$label_upTotal = formatFreeSpace($upTotal / 1048576);
$label_downTotalCurrent = "";
$label_upTotalCurrent = "";
$label_seeds = "";
$label_peers = "";
$label_maxcons = "";
$label_sharing = $sharing . '%';
if ($cfg["sharekill"] != 0)
	$label_sharekill = $cfg["sharekill"] . '%';
else
	$label_sharekill = '&#8734';
if (($af->running == 1) && ($alias != "")) {
	$label_downTotalCurrent = formatFreeSpace($downTotalCurrent / 1048576);
	$label_upTotalCurrent = formatFreeSpace($upTotalCurrent / 1048576);
	$label_seeds = $af->seeds;
	$label_peers = $af->peers;
	$torrent_pid = getTorrentPid($alias);
	$torrent_port = netstatPortByPid($torrent_pid);
	$torrent_cons = netstatConnectionsByPid($torrent_pid);
	if ($cfg["max_download_rate"] != 0)
		$label_max_download_rate = " (".number_format($cfg["max_download_rate"], 2).")";
	else
		$label_max_download_rate = ' (&#8734)';
	if ($cfg["max_upload_rate"] != 0)
		$label_max_upload_rate = " (".number_format($cfg["max_upload_rate"], 2).")";
	else
		$label_max_upload_rate = ' (&#8734)';
	$label_maxcons = " (".$cfg["maxcons"].")";
}
if ($af->percent_done < 0) {
	$af->percent_done = round(($af->percent_done*-1)-100,1);
	$af->time_left = _INCOMPLETE;
}
if($af->percent_done < 1)
	$graph_width = "1";
else
	$graph_width = $af->percent_done;
if($af->percent_done >= 100) {
	$af->percent_done = 100;
	$background = "#0000ff";
}
$torrentLabel = $torrent;
if(strlen($torrentLabel) >= 39)
	$torrentLabel = substr($torrent, 0, 35)."...";
$hd = getStatusImage($af);
$tmpl->setvar('head', getHead(_DOWNLOADDETAILS, false, "5", $af->percent_done."% "));
if ($error != "") {
	$tmpl->setvar('is_error', 1);
	$tmpl->setvar('error', $error);
}
$tmpl->setvar('torrentLabel', $torrentLabel);
$tmpl->setvar('formatBytesToKBMGGB', formatBytesToKBMGGB($af->size));
if ($af->running == 1) {
	$tmpl->setvar('running', 1);
	$tmpl->setvar('torrent', $torrent);
	$tmpl->setvar('alias', $alias);
}
$tmpl->setvar('hd_image', $hd->image);
$tmpl->setvar('hd_title', $hd->title);
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('background', $background);
$tmpl->setvar('graph_width1', $graph_width * 3.5);
$tmpl->setvar('graph_width2', (100 - $graph_width) * 3.5);
$tmpl->setvar('_ESTIMATEDTIME', _ESTIMATEDTIME);
$tmpl->setvar('body_data_bg', $cfg["body_data_bg"]);
$tmpl->setvar('time_left', $af->time_left);
$tmpl->setvar('_PERCENTDONE', _PERCENTDONE);
$tmpl->setvar('percent_done', $af->percent_done);
$tmpl->setvar('_USER', _USER);
$tmpl->setvar('transferowner', $transferowner);
$tmpl->setvar('_DOWNLOADSPEED', _DOWNLOADSPEED);
$tmpl->setvar('down_speed', $af->down_speed.$label_max_download_rate);
$tmpl->setvar('_UPLOADSPEED', _UPLOADSPEED);
$tmpl->setvar('up_speed', $af->up_speed.$label_max_upload_rate);
$tmpl->setvar('downTotalCurrent', $label_downTotalCurrent);
$tmpl->setvar('upTotalCurrent', $label_upTotalCurrent);
$tmpl->setvar('downTotal', $label_downTotal);
$tmpl->setvar('upTotal', $label_upTotal);
$tmpl->setvar('seeds', $label_seeds);
$tmpl->setvar('peers', $label_peers);
$tmpl->setvar('_ID_PORT', _ID_PORT);
$tmpl->setvar('port', $torrent_port);
$tmpl->setvar('_ID_CONNECTIONS', _ID_CONNECTIONS);
$tmpl->setvar('cons', $torrent_cons.$label_maxcons);
$tmpl->setvar('_SHARING', _SHARING);
$tmpl->setvar('label_sharing', $label_sharing);
$tmpl->setvar('label_sharekill', $label_sharekill);
$tmpl->setvar('foot', getFoot(false));
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('index_page', $cfg["index_page"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('refresh_details', 1);
$tmpl->setvar('iid', $_GET["iid"]);
$tmpl->pparse();
?>