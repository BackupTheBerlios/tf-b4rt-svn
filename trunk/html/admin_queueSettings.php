<?php
/* $Id: admin_queueSettings.php 102 2006-07-31 05:01:28Z msn_exploder $ */

$tmpl = new vlibTemplate("themes/".$cfg["default_theme"]."/tmpl/admin_queueSettings.tmpl");
require_once("AliasFile.php");
require_once("RunningTorrent.php");
require_once("QueueManager.php");
$queueManager = QueueManager::getQueueManagerInstance($cfg);
// QueueManager Running ?
$queueManagerRunning = false;
$shutdown = getRequestVar('s');
if ((isset($shutdown)) && ($shutdown == "1")) {
	$queueManagerRunning = false;
} else {
	if ($queueManager->isQueueManagerRunning()) {
		$queueManagerRunning = true;
	} else {
		if ($queueManager->managerName == "tfqmgr") {
			if ($queueManager->isQueueManagerReadyToStart()) {
				$queueManagerRunning = false;
			} else {
				$queueManagerRunning = true;
			}
		} else {
			$queueManagerRunning = false;
		}
	}
}
$tmpl->setvar('head', getHead("Administration - Queue Settings"));
$tmpl->setvar('menu', getMenu());
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
$tmpl->setvar('table_data_bg', $cfg["table_data_bg"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('queueManagerRunning', $queueManagerRunning);

// message section
$message = getRequestVar('m');
if ((isset($message)) && ($message != "")) {
	$tmpl->setvar('new_msg', 1);
	$tmpl->setvar('message', urldecode($message));
}
// Queue Manager Section
if ($queueManagerRunning) {
	$tmpl->setvar('managerName', $queueManager->managerName);
	$tmpl->setvar('managerPid', $queueManager->getQueueManagerPid());
}

$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('SuperAdminLink1', getSuperAdminLink('?q=1','log'));
$tmpl->setvar('SuperAdminLink2', getSuperAdminLink('?q=2','ps'));
$tmpl->setvar('SuperAdminLink3', getSuperAdminLink('?q=3','status'));

if ((isset($shutdown)) && ($shutdown == "1")) {
	$tmpl->setvar('shutdown', 1);
}
else {
	if ($queueManagerRunning && ($queueManager->managerName == "tfqmgr")) {
		$tmpl->setvar('tfqmgr_manager', 1);
	}
}

$tmpl->setvar('perlCmd', $cfg["perlCmd"]);
$tmpl->setvar('validateCmd', validateFile($cfg["perlCmd"]));
$tmpl->setvar('tfqmgr_path', $cfg["tfqmgr_path"]);
$tmpl->setvar('validatetfqmgr', validateFile($cfg["tfqmgr_path"]."/tfqmgr.pl"));
$tmpl->setvar('tfqmgr_path_fluxcli', $cfg["tfqmgr_path_fluxcli"]);
$tmpl->setvar('validate_tfqmgr_fluxcli', validateFile($cfg["tfqmgr_path_fluxcli"]."/fluxcli.php"));
$tmpl->setvar('tfqmgr_limit_global', $cfg["tfqmgr_limit_global"]);
$tmpl->setvar('tfqmgr_limit_user', $cfg["tfqmgr_limit_user"]);
$tmpl->setvar('tfqmgr_loglevel', $cfg["tfqmgr_loglevel"]);
$tmpl->setvar('Qmgr_path', $cfg["Qmgr_path"]);
$tmpl->setvar('validateQmgrd', validateFile($cfg["Qmgr_path"]."/Qmgrd.pl"));
$tmpl->setvar('Qmgr_maxUserTorrents', $cfg["Qmgr_maxUserTorrents"]);
$tmpl->setvar('Qmgr_maxTotalTorrents', $cfg["Qmgr_maxTotalTorrents"]);
$tmpl->setvar('Qmgr_perl', $cfg["Qmgr_perl"]);
$tmpl->setvar('validate_Qmgr_perl', validateFile($cfg["Qmgr_perl"]));
$tmpl->setvar('Qmgr_fluxcli', $cfg["Qmgr_fluxcli"]);
$tmpl->setvar('validate_Qmgr_fluxcli', validateFile($cfg["Qmgr_fluxcli"]."/fluxcli.php"));
$tmpl->setvar('Qmgr_host', $cfg["Qmgr_host"]);
$tmpl->setvar('Qmgr_port', $cfg["Qmgr_port"]);
$tmpl->setvar('Qmgr_loglevel', $cfg["Qmgr_loglevel"]);
$tmpl->setvar('tfQManager', $cfg["tfQManager"]);
$tmpl->setvar('validatetfQManager', validateFile($cfg["tfQManager"]));
$tmpl->setvar('maxServerThreads', $cfg["maxServerThreads"]);
$tmpl->setvar('maxUserThreads', $cfg["maxUserThreads"]);
$tmpl->setvar('sleepInterval', $cfg["sleepInterval"]);
$tmpl->setvar('_USER', _USER);
$tmpl->setvar('_FILE', _FILE);
$tmpl->setvar('_TIMESTAMP', _TIMESTAMP);
$tmpl->setvar('formattedQueueList', $queueManager->formattedQueueList());
$tmpl->setvar('_FORCESTOP', str_replace(" ","<br>",_FORCESTOP));

$displayQueue = True;
$displayRunningTorrents = True;
// Its a timming thing.
if ($displayRunningTorrents) {
	// get Running Torrents.
	$runningTorrents = getRunningTorrents();
}
// really messy
$output = "";
// get running tornado torrents and List them out.
$runningTorrents = getRunningTorrents("tornado");
foreach ($runningTorrents as $key => $value) {
	$rt = RunningTorrent::getRunningTorrentInstance($value,$cfg,"tornado");
	$output .= $rt->BuildAdminOutput();
}
// get running transmission torrents and List them out.
$runningTorrents = getRunningTorrents("transmission");
foreach ($runningTorrents as $key => $value) {
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