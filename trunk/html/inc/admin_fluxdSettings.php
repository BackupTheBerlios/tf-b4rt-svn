<?php
/* $Id$ */
$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/admin_fluxdSettings.tmpl");
require_once("AliasFile.php");
require_once("RunningTorrent.php");
require_once('Fluxd.php');

// fluxd
$fluxd = new Fluxd(serialize($cfg));
$fluxdRunning = $fluxd->isFluxdRunning();

// some template vars
$tmpl->setvar('head', getHead("Administration - Fluxd Settings"));
$tmpl->setvar('menu', getMenu());
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
$tmpl->setvar('table_data_bg', $cfg["table_data_bg"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('fluxdRunning', $fluxdRunning);

// message section
$message = getRequestVar('m');
if ((isset($message)) && ($message != "")) {
	$tmpl->setvar('new_msg', 1);
	$tmpl->setvar('message', urldecode($message));
}
// fluxd Section
if ($fluxdRunning) {
	$fluxdPid = $fluxd->getFluxdPid();
	$tmpl->setvar('fluxdPid', $fluxdPid);
}

$tmpl->setvar('theme', $cfg["theme"]);

if ((isset($shutdown)) && ($shutdown == "1")) {
	$tmpl->setvar('shutdown', 1);
}

// more template vars
$tmpl->setvar('perlCmd', $cfg["perlCmd"]);
$tmpl->setvar('validateCmd', validateFile($cfg["perlCmd"]));
$tmpl->setvar('fluxd_path', $cfg["fluxd_path"]);
$tmpl->setvar('validatefluxd', validateFile($cfg["fluxd_path"]."/fluxd.pl"));
$tmpl->setvar('fluxd_path_fluxcli', $cfg["fluxd_path_fluxcli"]);
$tmpl->setvar('validate_fluxd_path_fluxcli', validateFile($cfg["fluxd_path_fluxcli"]."/fluxcli.php"));
$tmpl->setvar('fluxd_Qmgr_maxTotalTorrents', $cfg["fluxd_Qmgr_maxTotalTorrents"]);
$tmpl->setvar('fluxd_Qmgr_maxUserTorrents', $cfg["fluxd_Qmgr_maxUserTorrents"]);
$tmpl->setvar('fluxd_loglevel', $cfg["fluxd_loglevel"]);
$tmpl->setvar('fluxd_Qmgr_enabled', $cfg["fluxd_Qmgr_enabled"]);
$tmpl->setvar('fluxd_Fluxinet_enabled', $cfg["fluxd_Fluxinet_enabled"]);
$tmpl->setvar('fluxd_Watch_enabled', $cfg["fluxd_Watch_enabled"]);
$tmpl->setvar('fluxd_Clientmaint_enabled', $cfg["fluxd_Clientmaint_enabled"]);
$tmpl->setvar('fluxd_Clientmaint_interval', $cfg["fluxd_Clientmaint_interval"]);
$tmpl->setvar('fluxd_Trigger_enabled', $cfg["fluxd_Trigger_enabled"]);
$tmpl->setvar('fluxd_Fluxinet_port', $cfg["fluxd_Fluxinet_port"]);
$tmpl->setvar('_USER', _USER);
$tmpl->setvar('_FILE', _FILE);
$tmpl->setvar('_TIMESTAMP', _TIMESTAMP);
//$tmpl->setvar('formattedQueueList', $queueManager->formattedQueueList());
$tmpl->setvar('_FORCESTOP', str_replace(" ","<br>",_FORCESTOP));

// really messy
$output = "";
// get running tornado torrents and List them out.
$running = getRunningTransfers("tornado");
foreach ($running as $key => $value) {
	$rt = RunningTorrent::getRunningTorrentInstance($value,$cfg,"tornado");
	$output .= $rt->BuildAdminOutput();
}
// get running transmission torrents and List them out.
$running = getRunningTransfers("transmission");
foreach ($running as $key => $value) {
	$rt = RunningTorrent::getRunningTorrentInstance($value,$cfg,"transmission");
	$output .= $rt->BuildAdminOutput();
}
if( strlen($output) == 0 ) {
	$output = "<tr><td colspan=3><div class=\"tiny\" align=center>No Running Torrents</div></td></tr>";
}

$tmpl->setvar('output', $output);
$tmpl->setvar('foot', getFoot(true,true));

$tmpl->pparse();
?>