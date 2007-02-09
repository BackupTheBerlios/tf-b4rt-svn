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


/**
 * transfers
 *
 * @param $action
 */
function sa_transfers($action = "") {
	global $cfg, $error, $statusImage, $statusMessage, $htmlTitle, $htmlTop, $htmlMain;
	if ($action == "")
		return;
	buildPage("t");
	switch ($action) {

		case "0": // Transfers-main
			$htmlTitle = "Transfers";
			$htmlMain .= '<br>';
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?t=1"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Stop All Transfers" border="0"> Stop All Transfers</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?t=2"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Start All Transfers" border="0"> Start All Transfers</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?t=3"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Resume All Transfers" border="0"> Resume All Transfers</a>';
			$htmlMain .= '<br><br>';
			break;

		case "1": // Transfers-Stop
			$htmlTitle = "Transfers - Stop";
			$htmlMain .= '<br><strong>Transfers Stopped :</strong><br>';
			$htmlMain .= '<pre>';
			$transferList = getTransferArray();
			foreach ($transferList as $transfer) {
				if (isTransferRunning($transfer)) {
					$ch = ClientHandler::getInstance(getTransferClient($transfer));
					$ch->stop($transfer);
					$htmlMain .=  ' - '.$transfer."";
					$htmlMain .=  "\n";
				}
			}
			$htmlMain .= '</pre>';
			$htmlMain .= '<hr><br>';
			break;

		case "2": // Transfers-Start
			$htmlTitle = "Transfers - Start";
			$htmlMain .= '<br><strong>Transfers Started :</strong><br>';
			$htmlMain .= '<pre>';
			$transferList = getTransferArray();
			foreach ($transferList as $transfer) {
				if (!isTransferRunning($transfer)) {
					$ch = ClientHandler::getInstance(getTransferClient($transfer));
					$ch->start($transfer, false, false);
					$htmlMain .=  ' - '.$transfer."";
					$htmlMain .=  "\n";
				}
			}
			$htmlMain .= '</pre>';
			$htmlMain .= '<hr><br>';
			break;

		case "3": // Transfers-Resume
			$htmlTitle = "Transfers - Resume";
			$htmlMain .= '<br><strong>Transfers Resumed :</strong><br>';
			$htmlMain .= '<pre>';
			$transferList = getTransferArray();
			$sf = new StatFile("");
			foreach ($transferList as $transfer) {
				$sf->init($transfer);
				if (((trim($sf->running)) == 0) && (!isTransferRunning($transfer))) {
					$ch = ClientHandler::getInstance(getTransferClient($transfer));
					$ch->start($transfer, false, false);
					$htmlMain .=  ' - '.$transfer."";
					$htmlMain .=  "\n";
				}
			}
			$htmlMain .= '</pre>';
			$htmlMain .= '<hr><br>';
			break;
	}
	$htmlMain .= '<br><strong>Transfers :</strong><br>';
	$htmlMain .= '<pre>';
	$transferList = getTransferArray();
	foreach ($transferList as $transfer) {
		$htmlMain .=  ' - '.$transfer."";
		if (isTransferRunning($transfer))
			$htmlMain .=  " (running)";
		$htmlMain .=  "\n";
	}
	$htmlMain .= '</pre>';
	printPage();
	exit();
}

/**
 * processes
 *
 * @param $action
 */
function sa_processes($action = "") {
	global $cfg, $error, $statusImage, $statusMessage, $htmlTitle, $htmlTop, $htmlMain;
	if ($action == "")
		return;
	buildPage("p");
	switch ($action) {

		case "0": // Processes-main
			$htmlTitle = "Processes";
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?p=1"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="All" border="0"> All</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?p=2"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Transfers" border="0"> Transfers</a>';
			$htmlMain .= '<br><br>';
			break;

		case "1": // Processes - All
			$htmlTitle = "Processes - All";
			$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
			$htmlMain .= '<br>';
			$htmlMain .= '<p><strong>fluxd</strong>';
			$htmlMain .= '<pre>';
			$htmlMain .= shell_exec("ps auxww | ".$cfg['bin_grep']." fluxd | ".$cfg['bin_grep']." -v grep");
			$htmlMain .= '</pre>';
			$htmlMain .= '<p><strong>fluazu</strong>';
			$htmlMain .= '<pre>';
			$htmlMain .= shell_exec("ps auxww | ".$cfg['bin_grep']." fluazu.py | ".$cfg['bin_grep']." -v grep");
			$htmlMain .= '</pre>';
			$clients = array('tornado', 'transmission', 'mainline', 'wget', 'nzbperl');
			foreach ($clients as $client) {
				$ch = ClientHandler::getInstance($client);
				$htmlMain .= '<p><strong>'.$client.'</strong>';
				$htmlMain .= '<br>';
				$htmlMain .= '<pre>';
				$htmlMain .= shell_exec("ps auxww | ".$cfg['bin_grep']." ".$ch->binClient." | ".$cfg['bin_grep']." -v grep");
				$htmlMain .= '</pre>';
				$htmlMain .= '<br>';
				$htmlMain .= '<pre>';
				$htmlMain .= $ch->runningProcessInfo();
				$htmlMain .= '</pre>';
			}
			$htmlMain .= '</div>';
			break;

		case "2": // Processes - Transfers
			$htmlTitle = "Processes - Transfers";
			$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
			$htmlMain .= '<br>
				<table width="700" border=1 bordercolor="'.$cfg["table_admin_border"].'" cellpadding="2" cellspacing="0" bgcolor="'.$cfg["table_data_bg"].'">
			    <tr><td colspan=6 bgcolor="'.$cfg["table_header_bg"].'" background="themes/'.$cfg["theme"].'/images/bar.gif">
			    	<table width="100%" cellpadding=0 cellspacing=0 border=0><tr><td><font class="title"> Running Items </font></td></tr></table>
			    </td></tr>
			    <tr>
			        <td bgcolor="'.$cfg["table_header_bg"].'" width="15%" nowrap><div align=center class="title">'.$cfg["_USER"].'</div></td>
			        <td bgcolor="'.$cfg["table_header_bg"].'" nowrap><div align=center class="title">'.$cfg["_FILE"].'</div></td>
			        <td bgcolor="'.$cfg["table_header_bg"].'" width="1%" nowrap><div align=center class="title">'.$cfg["_FORCESTOP"].'</div></td>
			    </tr>
			';
			$running = getRunningClientProcesses();
			foreach ($running as $rng) {
				$rt = RunningTransfer::getInstance($rng['pinfo'], $rng['client']);
			    $htmlMain .= '<tr bgcolor="'.$cfg["table_header_bg"].'">';
			    $htmlMain .= '<td nowrap><div class="tiny">';
			    $htmlMain .= $rt->transferowner;
			    $htmlMain .= '</div></td>';
			    $htmlMain .= '<td nowrap><div align=center><div class="tiny" align="left">';
			    $htmlMain .= $rt->transferFile;
			    $htmlMain .= '</div></td>';
			    $htmlMain .= '<td nowrap>';
			    $htmlMain .= '<a href="dispatcher.php?action=forceStop&riid=_referer_';
			    $htmlMain .= "&transfer=".urlencode($rt->transferFile);
			    $htmlMain .= "&pid=".$rt->processId;
			    $htmlMain .= '"><img src="themes/'.$cfg["theme"].'/images/kill.gif" width="16" height="16" title="'.$cfg['_FORCESTOP'].'" border="0"></a></td>';
			    $htmlMain .= '</tr>';
			}
			$htmlMain .= '</table>';
			$htmlMain .= '</div>';
			break;
	}
	printPage();
	exit();
}

/**
 * maintenance
 *
 * @param $action
 */
function sa_maintenance($action = "") {
	global $cfg, $error, $statusImage, $statusMessage, $htmlTitle, $htmlTop, $htmlMain;
	if ($action == "")
		return;
	buildPage("m");
	switch ($action) {

		case "0": // Maintenance-main
			$htmlTitle = "Maintenance";
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=1"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Main" border="0"> Main</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=2"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Kill" border="0"> Kill</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=3"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Clean" border="0"> Clean</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=4"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Repair" border="0"> Repair</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=5"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Reset" border="0"> Reset</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=6"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Lock" border="0"> Lock</a>';
			$htmlMain .= '<br><br>';
			break;

		case "1": // Maintenance : Main
			$htmlTitle = "Maintenance - Main";
			$htmlMain .= '<p>';
			$htmlMain .= '<strong>Standard</strong><br>';
			$htmlMain .= 'Standard Maintenance-Run. (same as on index-page and automatic called on every login).<br>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=11"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Standard Maintenance-Run" border="0"> Standard Maintenance-Run</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<strong>Extended</strong><br>';
			$htmlMain .= 'Extended Maintenance-Run. Like a standard-run but will also restart all died Transfers.<br>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=12"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="vlc-kill" border="0"> Extended Maintenance-Run</a>';
			$htmlMain .= '<br><br>';
			break;

		case "11": // Maintenance : Main : Standard Maintenance-Run
			$htmlTitle = "Maintenance - Main - Standard Maintenance-Run";
			$htmlMain .= '<br>';
			$htmlMain .= 'Standard Maintenance-Run';
			require_once("inc/classes/MaintenanceAndRepair.php");
			MaintenanceAndRepair::maintenance(false);
			$htmlMain .= ' <font color="green">done</font>';
			$htmlMain .= '<br><br>';
			break;

		case "12": // Maintenance : Main
			$htmlTitle = "Maintenance - Main - Extended Maintenance-Run";
			$htmlMain .= '<br>';
			$htmlMain .= 'Extended Maintenance-Run';
			require_once("inc/classes/MaintenanceAndRepair.php");
			MaintenanceAndRepair::maintenance(true);
			$htmlMain .= ' <font color="green">done</font>';
			$htmlMain .= '<br><br>';
			break;

		case "2": // Maintenance-Kill
			$htmlTitle = "Maintenance - Kill";
			$htmlMain .= '<br>';
			$htmlMain .= '<font color="red"><strong>DONT</strong> do this or you will screw up things for sure !</font><br><br>';
			$htmlMain .= 'This is only meant as emergency-break if things go terrible wrong already.<br>Please use this only if you know what you are doing.';
			$htmlMain .= '<p>';
			$htmlMain .= '<strong>php</strong><br>';
			$htmlMain .= 'use this to kill all php processes.<br>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=21"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="php-kill" border="0"> php-kill</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<strong>python</strong><br>';
			$htmlMain .= 'use this to kill all python processes.<br>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=22"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="python-kill" border="0"> python-kill</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<strong>perl</strong><br>';
			$htmlMain .= 'use this to kill all perl processes.<br>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=23"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="perl-kill" border="0"> perl-kill</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<strong>transmissioncli</strong><br>';
			$htmlMain .= 'use this to kill all transmissioncli processes.<br>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=24"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="transmissioncli-kill" border="0"> transmissioncli-kill</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<strong>wget</strong><br>';
			$htmlMain .= 'use this to kill all wget processes.<br>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=25"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="wget-kill" border="0"> wget-kill</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<strong>vlc</strong><br>';
			$htmlMain .= 'use this to kill all vlc processes.<br>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=26"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="vlc-kill" border="0"> vlc-kill</a>';
			$htmlMain .= '<br><br>';
			break;

		case "21": // Maintenance-Kill : php
			$htmlTitle = "Maintenance - Kill - php";
			$htmlMain .= '<br>';
			$htmlMain .= '"kill all php processes" done.';
			$htmlMain .= '<br><br>';
			$htmlMain .= '<strong>process-list (filtered) before call :</strong><br>';
			$htmlMain .= '<pre>';
			$htmlMain .= trim(shell_exec("ps auxww | ".$cfg['bin_grep']." php | ".$cfg['bin_grep']." -v grep"));
			$htmlMain .= '</pre>';
			$htmlMain .= '<br>';
			$callResult = trim(shell_exec("killall -9 php 2> /dev/null"));
			if ((isset($callResult)) && ($callResult != "")) {
				$htmlMain .= '<br>';
				$htmlMain .= 'Call-Result : <br>';
				$htmlMain .= '<pre>'.$callResult.'</pre>';
				$htmlMain .= '<br>';
			}
			sleep(2); // just 2 sec
			$htmlMain .= '<strong>process-list (filtered) after call :</strong><br>';
			$htmlMain .= '<pre>';
			$htmlMain .= trim(shell_exec("ps auxww | ".$cfg['bin_grep']." php | ".$cfg['bin_grep']." -v grep"));
			$htmlMain .= '</pre>';
			$htmlMain .= '<br>';
			break;

		case "22": // Maintenance-Kill : python
			$htmlTitle = "Maintenance - Kill - python";
			$htmlMain .= '<br>';
			$htmlMain .= '"kill all python processes" done.';
			$htmlMain .= '<br><br>';
			$htmlMain .= '<strong>process-list (filtered) before call :</strong><br>';
			$htmlMain .= '<pre>';
			$htmlMain .= trim(shell_exec("ps auxww | ".$cfg['bin_grep']." python | ".$cfg['bin_grep']." -v grep"));
			$htmlMain .= '</pre>';
			$htmlMain .= '<br>';
			$callResult = trim(shell_exec("killall -9 python 2> /dev/null"));
			if ((isset($callResult)) && ($callResult != "")) {
				$htmlMain .= '<br>';
				$htmlMain .= 'Call-Result : <br>';
				$htmlMain .= '<pre>'.$callResult.'</pre>';
				$htmlMain .= '<br>';
			}
			sleep(2); // just 2 sec
			$htmlMain .= '<strong>process-list (filtered) after call :</strong><br>';
			$htmlMain .= '<pre>';
			$htmlMain .= trim(shell_exec("ps auxww | ".$cfg['bin_grep']." python | ".$cfg['bin_grep']." -v grep"));
			$htmlMain .= '</pre>';
			$htmlMain .= '<br>';
			break;

		case "23": // Maintenance-Kill : perl
			$htmlTitle = "Maintenance - Kill - perl";
			$htmlMain .= '<br>';
			$htmlMain .= '"kill all perl processes" done.';
			$htmlMain .= '<br><br>';
			$htmlMain .= '<strong>process-list (filtered) before call :</strong><br>';
			$htmlMain .= '<pre>';
			$htmlMain .= trim(shell_exec("ps auxww | ".$cfg['bin_grep']." perl | ".$cfg['bin_grep']." -v grep"));
			$htmlMain .= '</pre>';
			$htmlMain .= '<br>';
			$callResult = trim(shell_exec("killall -9 perl 2> /dev/null"));
			if ((isset($callResult)) && ($callResult != "")) {
				$htmlMain .= '<br>';
				$htmlMain .= 'Call-Result : <br>';
				$htmlMain .= '<pre>'.$callResult.'</pre>';
				$htmlMain .= '<br>';
			}
			sleep(2); // just 2 sec
			$htmlMain .= '<strong>process-list (filtered) after call :</strong><br>';
			$htmlMain .= '<pre>';
			$htmlMain .= trim(shell_exec("ps auxww | ".$cfg['bin_grep']." perl | ".$cfg['bin_grep']." -v grep"));
			$htmlMain .= '</pre>';
			$htmlMain .= '<br>';
			break;

		case "24": // Maintenance-Kill : transmissioncli
			$htmlTitle = "Maintenance - Kill - transmissioncli";
			$htmlMain .= '<br>';
			$htmlMain .= '"kill all transmissioncli processes" done.';
			$htmlMain .= '<br><br>';
			$htmlMain .= '<strong>process-list (filtered) before call :</strong><br>';
			$htmlMain .= '<pre>';
			$htmlMain .= trim(shell_exec("ps auxww | ".$cfg['bin_grep']." transmissioncli | ".$cfg['bin_grep']." -v grep"));
			$htmlMain .= '</pre>';
			$htmlMain .= '<br>';
			$callResult = trim(shell_exec("killall -9 transmissioncli 2> /dev/null"));
			if ((isset($callResult)) && ($callResult != "")) {
				$htmlMain .= '<br>';
				$htmlMain .= 'Call-Result : <br>';
				$htmlMain .= '<pre>'.$callResult.'</pre>';
				$htmlMain .= '<br>';
			}
			sleep(2); // just 2 sec
			$htmlMain .= '<strong>process-list (filtered) after call :</strong><br>';
			$htmlMain .= '<pre>';
			$htmlMain .= trim(shell_exec("ps auxww | ".$cfg['bin_grep']." transmissioncli | ".$cfg['bin_grep']." -v grep"));
			$htmlMain .= '</pre>';
			$htmlMain .= '<br>';
			break;

		case "25": // Maintenance-Kill : wget
			$htmlTitle = "Maintenance - Kill - wget";
			$htmlMain .= '<br>';
			$htmlMain .= '"kill all wget processes" done.';
			$htmlMain .= '<br><br>';
			$htmlMain .= '<strong>process-list (filtered) before call :</strong><br>';
			$htmlMain .= '<pre>';
			$htmlMain .= trim(shell_exec("ps auxww | ".$cfg['bin_grep']." wget | ".$cfg['bin_grep']." -v grep"));
			$htmlMain .= '</pre>';
			$htmlMain .= '<br>';
			$callResult = trim(shell_exec("killall -9 wget 2> /dev/null"));
			if ((isset($callResult)) && ($callResult != "")) {
				$htmlMain .= '<br>';
				$htmlMain .= 'Call-Result : <br>';
				$htmlMain .= '<pre>'.$callResult.'</pre>';
				$htmlMain .= '<br>';
			}
			sleep(2); // just 2 sec
			$htmlMain .= '<strong>process-list (filtered) after call :</strong><br>';
			$htmlMain .= '<pre>';
			$htmlMain .= trim(shell_exec("ps auxww | ".$cfg['bin_grep']." wget | ".$cfg['bin_grep']." -v grep"));
			$htmlMain .= '</pre>';
			$htmlMain .= '<br>';
			break;

		case "26": // Maintenance-Kill : vlc
			$htmlTitle = "Maintenance - Kill - vlc";
			$htmlMain .= '<br>';
			$htmlMain .= '"kill all vlc processes" done.';
			$htmlMain .= '<br><br>';
			$htmlMain .= '<strong>process-list (filtered) before call :</strong><br>';
			$htmlMain .= '<pre>';
			$htmlMain .= trim(shell_exec("ps auxww | ".$cfg['bin_grep']." vlc | ".$cfg['bin_grep']." -v grep"));
			$htmlMain .= '</pre>';
			$htmlMain .= '<br>';
			$callResult = trim(shell_exec("killall -9 vlc 2> /dev/null"));
			if ((isset($callResult)) && ($callResult != "")) {
				$htmlMain .= '<br>';
				$htmlMain .= 'Call-Result : <br>';
				$htmlMain .= '<pre>'.$callResult.'</pre>';
				$htmlMain .= '<br>';
			}
			sleep(2); // just 2 sec
			$htmlMain .= '<strong>process-list (filtered) after call :</strong><br>';
			$htmlMain .= '<pre>';
			$htmlMain .= trim(shell_exec("ps auxww | ".$cfg['bin_grep']." vlc | ".$cfg['bin_grep']." -v grep"));
			$htmlMain .= '</pre>';
			$htmlMain .= '<br>';
			break;

		case "3": // Maintenance-Clean
			$htmlTitle = "Maintenance - Clean";
			$htmlMain .= '<br>';
			$htmlMain .= '<strong>pid-file-leftovers</strong><br>';
			$htmlMain .= 'use this to delete pid-file-leftovers of deleted transfers.<br>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=31"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="pid-file-clean" border="0"> pid-file-clean</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<strong>tornado</strong><br>';
			$htmlMain .= 'use this to delete the cache of tornado. (stop your tornados first !)<br>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=32"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="tornado-clean" border="0"> tornado-clean</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<strong>transmission</strong><br>';
			$htmlMain .= 'use this to delete cache-leftovers of deleted transmission-torrents.<br>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=33"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="transmission-clean" border="0"> transmission-clean</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<strong>mainline</strong><br>';
			$htmlMain .= 'use this to delete the cache of mainline. (stop your mainlines first !)<br>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=34"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="mainline-clean" border="0"> mainline-clean</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<strong>template-cache</strong><br>';
			$htmlMain .= 'use this to delete the template-cache.<br>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=35"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="template-cache-clean" border="0"> template-cache-clean</a>';
			$htmlMain .= '<br><br>';
			break;

		case "31": // Maintenance-Clean : pid-file-clean
			$htmlTitle = "Maintenance - Clean - pid-file";
			$htmlMain .= '<br>';
			$result = "";
			$transferList = getTransferArrayFromDB();
			if ($dirHandle = @opendir($cfg["transfer_file_path"])) {
				while (false !== ($file = readdir($dirHandle))) {
					if ((strlen($file) > 3) && ((substr($file, -4, 4)) == ".pid")) {
						$tname = substr($file, 0, -4);
						if (!in_array($tname, $transferList)) {
							// transfer not in db. delete pid-file.
							$result .= $file."\n";
							@unlink($cfg["transfer_file_path"].$file);
						}
					}
				}
				closedir($dirHandle);
			}
			if (strlen($result) > 0)
				$htmlMain .= '<br>Deleted pid-leftovers : <br><pre>'.$result.'</pre><br>';
			else
				$htmlMain .= '<br>No pid-leftovers found.<br><br>';
			break;

		case "32": // Maintenance-Clean : tornado-clean
			$htmlTitle = "Maintenance - Clean - tornado";
			$htmlMain .= '<br>';
			$result = "";
			$result .= cleanDir($cfg["path"].'.BitTornado/datacache');
			$result .= cleanDir($cfg["path"].'.BitTornado/torrentcache');
			$result .= cleanDir($cfg["path"].'.BitTornado/piececache');
			$result .= cleanDir($cfg["path"].'.BitTornado/icons');
			if (strlen($result) > 0)
				$htmlMain .= '<br>Deleted  : <br><pre>'.$result.'</pre><br>';
			else
				$htmlMain .= '<br>Nothing found.<br><br>';
			break;

		case "33": // Maintenance-Clean : transmission-clean
			$htmlTitle = "Maintenance - Clean - transmission";
			$htmlMain .= '<br>';
			$result = "";
			$hashes = array();
			$transferList = getTransferArray();
			foreach ($transferList as $transfer)
				array_push($hashes, getTransferHash($transfer));
			if ($dirHandle = @opendir($cfg["path"].".transmission/cache/")) {
				while (false !== ($file = readdir($dirHandle))) {
					if ($file{0} == "r") {
						$thash = substr($file, -40);
						if (!in_array($thash, $hashes)) {
							// torrent not in db. delete cache-file.
							$result .= $file."\n";
							@unlink($cfg["path"].".transmission/cache/resume.".$thash);
						}
					}
				}
				closedir($dirHandle);
			}
			if (strlen($result) > 0)
				$htmlMain .= '<br>Deleted cache-leftovers : <br><pre>'.$result.'</pre><br>';
			else
				$htmlMain .= '<br>No cache-leftovers found.<br><br>';
			break;

		case "34": // Maintenance-Clean : mainline-clean
			$htmlTitle = "Maintenance - Clean - mainline";
			$htmlMain .= '<br>';
			$result = "";
			$result .= cleanDir($cfg["path"].'.bittorrent/console/resume');
			$result .= cleanDir($cfg["path"].'.bittorrent/console/metainfo');
			$result .= cleanDir($cfg["path"].'.bittorrent/console/torrents');
			$result .= cleanDir($cfg["path"].'.bittorrent/mutex');
			if (strlen($result) > 0)
				$htmlMain .= '<br>Deleted  : <br><pre>'.$result.'</pre><br>';
			else
				$htmlMain .= '<br>Nothing found.<br><br>';
			break;

		case "35": // Maintenance-Clean :template-cache-clean
			$htmlTitle = "Maintenance - Clean - template-cache";
			$htmlMain .= '<br>';
			$result = cleanDir($cfg["path"].'.templateCache');
			if (strlen($result) > 0)
				$htmlMain .= '<br>Deleted compiled templates : <br><pre>'.$result.'</pre><br>';
			else
				$htmlMain .= '<br>No compiled templates found.<br><br>';
			break;

		case "4": // Maintenance : Repair
			$htmlTitle = "Maintenance - Repair";
			$htmlMain .= '<br>';
			$htmlMain .= '<font color="red"><strong>DONT</strong> do this if your system is running as it should. You WILL break something.</font>';
			$htmlMain .= '<br>use this after server-reboot, if transfers were killed or if there are other problems with the webapp.';
			$htmlMain .= '<br><a href="' . _FILE_THIS . '?m=41"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Repair" border="0"> Repair</a>';
			$htmlMain .= '<br><br>';
			break;

		case "41": // Maintenance : Repair
			$htmlTitle = "Maintenance - Repair";
			$htmlMain .= '<br>';
			$htmlMain .= 'Repair';
			require_once("inc/classes/MaintenanceAndRepair.php");
			MaintenanceAndRepair::repair();
			$htmlMain .= ' <font color="green">done</font>';
			$htmlMain .= '<br><br>';
			break;

		case "5": // Maintenance : Reset
			$htmlTitle = "Maintenance - Reset";
			$htmlMain .= '<br>';
			$htmlMain .= '<strong>transfer-totals</strong><br>';
			$htmlMain .= 'use this to reset the transfer-totals.<br>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=51"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="transfer-totals" border="0"> transfer-totals-reset</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<strong>xfer-stats</strong><br>';
			$htmlMain .= 'use this to reset the xfer-stats.<br>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=52"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="xfer-stats" border="0"> xfer-stats-reset</a>';
			$htmlMain .= '<br><br>';
			break;

		case "51": // Maintenance : Reset - transfer-totals
			$htmlTitle = "Maintenance - Reset - transfer-totals";
			$htmlMain .= '<br>';
			$htmlMain .= 'Reset of transfer-totals';
			$result = resetAllTransferTotals();
			$htmlMain .= ($result === true)
				? ' <font color="green">done</font>'
				: '<br><font color="red">Error :</font><br>'.$result;
			$htmlMain .= '<br><br>';
			break;

		case "52": // Maintenance : Reset - xfer
			$htmlTitle = "Maintenance - Reset - xfer";
			$htmlMain .= '<br>';
			$htmlMain .= 'Reset of xfer-stats';
			$result = resetXferStats();
			$htmlMain .= ($result === true)
				? ' <font color="green">done</font>'
				: '<br><font color="red">Error :</font><br>'.$result;
			$htmlMain .= '<br><br>';
			break;

		case "6": // Maintenance : Lock
			$htmlTitle = "Maintenance - Lock";
			$htmlMain .= '<br>';
			switch ($cfg['webapp_locked']) {
				case 0:
					$htmlMain .= '<strong><font color="green">webapp currently unlocked.</font></strong>';
					break;
				case 1:
					$htmlMain .= '<strong><font color="red">webapp currently locked.</font></strong>';
					break;
			}
			$htmlMain .= '<p>';
			$htmlMain .= 'Use this to lock/unlock your webapp. only superadmin can access locked webapp.';
			$htmlMain .= '<br><a href="' . _FILE_THIS . '?m=61"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Repair" border="0"> ';
			if ($cfg['webapp_locked'] == 1)
				$htmlMain .= 'un';
			$htmlMain .= 'lock</a>';
			$htmlMain .= '<br><br>';
			break;

		case "61": // Maintenance : lock/unlock
			$htmlTitle = "Maintenance - Lock";
			$htmlMain .= '<br>';
			switch ($cfg['webapp_locked']) {
				case 0:
					$result = setWebappLock(1);
					$htmlMain .= ($result === true)
						? '<font color="red">webapp locked.</font>'
						: '<br><font color="red">Error :</font><br>'.$result;
					break;
				case 1:
					$result = setWebappLock(0);
					$htmlMain .= ($result === true)
						? '<font color="red">webapp unlocked.</font>'
						: '<br><font color="red">Error :</font><br>'.$result;
					break;
			}
			$htmlMain .= '<br><br>';
			break;
	}
	printPage();
	exit();
}

/**
 * backup
 *
 * @param $action
 */
function sa_backup($action = "") {
	global $cfg, $error, $statusImage, $statusMessage, $htmlTitle, $htmlTop, $htmlMain;
	if ($action == "")
		return;
	switch ($action) {

		case "0": // choose backup-type
			buildPage("b");
			$htmlTitle = "Backup - Create";
			$htmlMain .= '<br>';
			$htmlMain .= '<form name="backupServer" action="' . _FILE_THIS . '" method="post">';
			$htmlMain .= '<select name="c">';
			$htmlMain .= '<option value="0">none</option>';
			$htmlMain .= '<option value="1" selected>gzip</option>';
			$htmlMain .= '<option value="2">bzip2</option>';
			$htmlMain .= '</select>&nbsp;&nbsp;';
			$htmlMain .= '<input type="Hidden" name="b" value="1">';
			$htmlMain .= '<input type="submit" value="Backup on Server">';
			$htmlMain .= '</form><p>';
			$htmlMain .= '<form name="backupClient" action="' . _FILE_THIS . '" method="post">';
			$htmlMain .= '<select name="c">';
			$htmlMain .= '<option value="0">none</option>';
			$htmlMain .= '<option value="1" selected>gzip</option>';
			$htmlMain .= '<option value="2">bzip2</option>';
			$htmlMain .= '</select>&nbsp;&nbsp;';
			$htmlMain .= '<input type="Hidden" name="b" value="2">';
			$htmlMain .= '<input type="submit" value="Backup to Client">';
			$htmlMain .= '</form><p>';
			$htmlMain .= 'In case you choose "Backup on Server" the archive will be located in : <br>';
			$htmlMain .= '<em>'.$cfg["path"]. _DIR_BACKUP . '/</em>';
			$htmlMain .= '<br><br>';
			$htmlMain .= 'Be patient until "its done" and dont click stuff while backup is created.<br>';
			$htmlMain .= 'This script will tell you if things go wrong so no need to stress it.<br>';
			printPage();
			exit();

		case "1": // server-backup
			buildPage("b");
			$htmlTitle = "Backup - Create - Server";
			printPageStart(1);
			echo $htmlMain;
			$backupArchive = backupCreate(true,$_REQUEST["c"]);
			if ($backupArchive == "") {
				sendLine('<br>');
				sendLine('<font color="red"><strong>Backup - Error</strong></font><br><br>');
				sendLine($error);
			} else {
				sendLine('<br>');
				sendLine('<strong>Backup Created</strong>');
				sendLine('<br><br>Archive of backup is <em>'.$backupArchive.'</em>');
				sendLine('<br><br>');
				sendLine(backupListDisplay());
			}
			printPageEnd(1);
			exit();

		case "2": // client-backup
			$backupArchive = backupCreate(false,$_REQUEST["c"]);
			if ($backupArchive == "") {
				buildPage("-b");
				$htmlTitle = "Backup - Create - Client";
				$htmlMain .= '<br><br>';
				$htmlMain .= '<font color="red"><strong>Backup - Error</strong></font><br><br>';
				$htmlMain .= $error;
				printPage();
			} else {
				backupSend($backupArchive,true);
			}
			exit();

		case "3": // backup-list
			$htmlTitle = "Backup - Backups on Server";
			buildPage("b");
			$htmlMain .= '<br>';
			$htmlMain .= backupListDisplay();
			printPage();
			exit();

		case "4": // download backup
			$backupArchive = trim($_REQUEST["f"]);
			if (backupParamCheck($backupArchive)) {
				backupSend($backupArchive,false);
			} else {
				buildPage("-b");
				$htmlTitle = "Backup - Download";
				$htmlMain .= '<br><br>';
				$htmlMain .= '<font color="red"><strong>Backup - Error</strong></font><br><br>';
				$htmlMain .= $backupArchive.' is not a valid Backup-ID';
				printPage();
			}
			exit();

		case "5": // delete backup
			$backupArchive = trim($_REQUEST["f"]);
			if (backupParamCheck($backupArchive)) {
				backupDelete($backupArchive);
				buildPage("b");
				$htmlTitle = "Backup - Delete";
				$htmlMain .= '<br>';
				$htmlMain .= '<em>'.$backupArchive.'</em> deleted.';
				$htmlMain .= '<br><br>';
				$htmlMain .= backupListDisplay();
			} else {
				buildPage("-b");
				$htmlTitle = "Backup - Delete";
				$htmlMain .= '<br><br>';
				$htmlMain .= '<font color="red"><strong>Backup - Error</strong></font><br><br>';
				$htmlMain .= $backupArchive.' is not a valid Backup-ID';
			}
			printPage();
			exit();

	}
	exit();
}

/**
 * log
 *
 * @param $action
 */
function sa_log($action = "") {
	global $cfg, $error, $statusImage, $statusMessage, $htmlTitle, $htmlTop, $htmlMain;
	if ($action == "")
		return;
	buildPage("l");
	switch ($action) {

		case "0": // log-main
			$htmlTitle = "log";
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?l=1"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="fluxd" border="0"> fluxd</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?l=2"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="fluxd-error" border="0"> fluxd-error</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?l=5"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="mainline" border="0"> mainline</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?l=8"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="transfers" border="0"> transfers</a>';
			$htmlMain .= '<br><br>';
			break;

		case "1": // fluxd-log
			$htmlTitle = "log - fluxd";
			$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
			$htmlMain .= '<pre>';
			$htmlMain .= @file_get_contents($cfg["path"].'.fluxd/fluxd.log');
			$htmlMain .= '</pre>';
			$htmlMain .= '</div>';
			break;

		case "2": // fluxd-error-log
			$htmlTitle = "log - fluxd - error-log";
			$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
			$htmlMain .= '<pre>';
			$htmlMain .= @file_get_contents($cfg["path"].'.fluxd/fluxd-error.log');
			$htmlMain .= '</pre>';
			$htmlMain .= '</div>';
			break;

		case "5": // mainline-log
			$htmlTitle = "log - mainline";
			$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
			$htmlMain .= '<pre>';
			$mainlineLog = $cfg["path"].'.bittorrent/tfmainline.log';
			if (is_file($mainlineLog))
				$htmlMain .= @file_get_contents($mainlineLog);
			else
				$htmlMain .= "mainline-log not found.";
			$htmlMain .= '</pre>';
			$htmlMain .= '</div>';
			break;

		case "8": // transfers
			$htmlTitle = "log - transfers";
			$logList = getTransferArray('na');
			if ((isset($logList)) && (is_array($logList))) {
				$htmlMain .= '<ul>';
				foreach ($logList as $logFile) {
					if ((isset($logFile)) && ($logFile != "")) {
						$htmlMain .= '<li>';
						$htmlMain .= '<a href="'. _FILE_THIS .'?l=9&transfer='.$logFile.'">';
						$htmlMain .= $logFile;
						$htmlMain .= '</a>';
						$htmlMain .= '</li>';
					}
				}
				$htmlMain .= '</ul>';
			}
			break;

		case "9": // transfer-log
			if (isset($_REQUEST["transfer"])) {
				$transfer = trim(htmlentities($_REQUEST["transfer"], ENT_QUOTES));
				// shorten name if too long
				if(strlen($transfer) >= 70)
					$htmlTitle = "log - transfer-log - ".substr($transfer, 0, 67)."...";
				else
					$htmlTitle = "log - transfer-log - ".$transfer;
				$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
				$htmlMain .= '<pre>';
				$htmlMain .= getTransferLog($transfer);
				$htmlMain .= '</pre>';
				$htmlMain .= '</div>';
			} else {
				$htmlTitle = "log - transfer-log";
				$htmlMain .= '<font color="red">Error. missing params</font>';
			}
			break;
	}
	printPage();
	exit();
}

/**
 * misc
 *
 * @param $action
 */
function sa_misc($action = "") {
	global $cfg, $error, $statusImage, $statusMessage, $htmlTitle, $htmlTop, $htmlMain;
	if ($action == "")
		return;
	buildPage("y");
	switch ($action) {

		case "0": // misc-main
			$htmlTitle = "Misc";
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?y=1"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Check" border="0"> Lists</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?y=5"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Check" border="0"> Check</a>';
			$htmlMain .= '<br><br>';
			break;

		case "1": // misc - Lists
			$htmlTitle = "Misc - Lists";
			$htmlMain .= '<p>';
			$htmlMain .= '<img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Files" border="0"> Files (';
			$htmlMain .= '<a href="' . _FILE_THIS . '?y=11" target="_blank">html</a>';
			$htmlMain .= ' / ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?y=12" target="_blank">text</a>';
			$htmlMain .= ')';
			$htmlMain .= '<p>';
			$htmlMain .= '<img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Checksums" border="0"> Checksums (';
			$htmlMain .= '<a href="' . _FILE_THIS . '?y=13" target="_blank">html</a>';
			$htmlMain .= ' / ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?y=14" target="_blank">text</a>';
			$htmlMain .= ')';
			$htmlMain .= '<br><br>';
			break;

		case "11": // Misc - File-List - html
			printFileList($cfg['docroot'], 1, 2);
			exit();

		case "12": // Misc - File-List - text
			@header("Content-Type: text/plain");
			printFileList($cfg['docroot'], 1, 1);
			exit();

		case "13": // Misc - Checksums - html
			printFileList($cfg['docroot'], 2, 2);
			exit();

		case "14": // Misc - Checksums - text
			@header("Content-Type: text/plain");
			printFileList($cfg['docroot'], 2, 1);
			exit();

		case "5": // misc - Check
			$htmlTitle = "Misc - Check";
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?y=51"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="php-web" border="0"> php-web</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?y=52"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="php-cli" border="0"> php-cli</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?y=53"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Perl" border="0"> Perl</a>';
			$htmlMain .= '<br><br>';
			break;

		case "51": // misc - Check - php-web
			$htmlTitle = "Misc - Check - php-web";
			$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
			$htmlMain .= phpCheckWeb();
			$htmlMain .= '</div>';
			break;

		case "52": // misc - Check - php-cli
			$htmlTitle = "Misc - Check - php-cli";
			$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
			$htmlMain .= '<pre>';
			$htmlMain .= shell_exec($cfg["bin_php"]." ".$cfg["docroot"]."bin/check/check-cli.php");
			$htmlMain .= '</pre>';
			$htmlMain .= '</div>';
			break;

		case "53": // misc - Check - Perl
			$htmlTitle = "Misc - Check - Perl";
			$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
			$htmlMain .= '<pre>';
			$htmlMain .= shell_exec($cfg["perlCmd"]." ".$cfg["docroot"]."bin/check/check.pl all");
			$htmlMain .= '</pre>';
			$htmlMain .= '</div>';
			break;

		case "531": // misc - Check - Perl - nzbperl
			$htmlTitle = "Misc - Check - Perl";
			$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
			$htmlMain .= '<pre>';
			$htmlMain .= shell_exec($cfg["perlCmd"]." ".$cfg["docroot"]."bin/check/check.pl nzbperl");
			$htmlMain .= '</pre>';
			$htmlMain .= '</div>';
			break;

	}
	printPage();
	exit();
}

/**
 * tfb
 *
 * @param $action
 */
function sa_tfb($action = "") {
	global $cfg, $error, $statusImage, $statusMessage, $htmlTitle, $htmlTop, $htmlMain;
	if ($action == "")
		return;
	buildPage("z");
	switch ($action) {

		case "0": // main
			$htmlTitle = "tf-b4rt";
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?z=1"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Version" border="0"> Version</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?z=2"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="News" border="0"> News</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?z=3"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Changelog" border="0"> Changelog</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?z=9"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Misc" border="0"> Misc</a>';
			$htmlMain .= '<br><br>';
			break;

		case "1": // Version
			$htmlTitle = "tf-b4rt - Version";
			// version-check
			$versionAvailable = trim(getDataFromUrl(_SUPERADMIN_URLBASE._SUPERADMIN_PROXY));
			if ((isset($versionAvailable)) && ($versionAvailable != "")) {
				// set image
				if ($versionAvailable == _VERSION || (substr(_VERSION, 0, 3)) == "svn")
					$statusImage = "green.gif";
				else
					$statusImage = "red.gif";
				// version-text
				$htmlMain .= '<br>';
				if (strpos(_VERSION, "svn") !== false) {
				        $htmlMain .= '<strong>This Version : </strong>'._VERSION;
    					$htmlMain .= '<br><br>';
    					$htmlMain .= '<strong>Latest Release : </strong>';
    					$htmlMain .= $versionAvailable;
    					$htmlMain .= '<br><br>';
    					$htmlMain .= '<font color="blue">This Version is a svn-Version.</font>';
				} else {
    				if ($versionAvailable != _VERSION) {
    					$htmlMain .= '<strong>This Version : </strong>';
    					$htmlMain .= '<font color="red">'._VERSION.'</font>';
    					$htmlMain .= '<br><br>';
    					$htmlMain .= '<strong>Available Version : </strong>';
    					$htmlMain .= $versionAvailable;
    					$htmlMain .= '<br><br>';
    					$htmlMain .= '<strong><font color="red">There is a new Version available !</font></strong>';
    					$htmlMain .= '<br><br>';
						$htmlMain .= '<form name="update" action="' . _FILE_THIS . '" method="post">';
						$htmlMain .= '<input type="Hidden" name="u" value="0">';
						$htmlMain .= '<input type="submit" value="Update to Version '.$versionAvailable.'">';
						$htmlMain .= '</form>';
    					$htmlMain .= '<strong>Current Release : </strong>';
    					$htmlMain .= '<br>';
    					$htmlMain .= '<a href="'._URL_RELEASE.'" target="_blank"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Current Release" border="0"> '._URL_RELEASE.'</a>';
    					$htmlMain .= '<br><br>';
    					$htmlMain .= '<strong>Homepage : </strong>';
    					$htmlMain .= '<br>';
    					$htmlMain .= '<a href="'._URL_HOME.'" target="_blank"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Homepage on berliOS" border="0"> '._URL_HOME.'</a>';
    					$htmlMain .= '<br>';
    				} else {
    					$htmlMain .= '<strong>This Version : </strong>'._VERSION;
    					$htmlMain .= '<br><br>';
    					$htmlMain .= '<strong>Available Version : </strong>';
    					$htmlMain .= $versionAvailable;
    					$htmlMain .= '<br><br>';
    					$htmlMain .= '<font color="green">This Version looks good.</font>';
    				}
				}
				$htmlMain .= '<br><br>';
			} else { // could not get the version
				$statusImage = "black.gif";
				$htmlTop = '<strong><font color="red">Error.</font></strong>';
				$htmlMain = '<br>';
				$htmlMain .= '<font color="red">Error getting available version.</font>';
				$htmlMain .= '<br><br>';
    			$htmlMain .= '<strong>Current Release : </strong>';
    			$htmlMain .= '<br>';
    			$htmlMain .= '<a href="'._URL_RELEASE.'" target="_blank"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Current Release" border="0"> '._URL_RELEASE.'</a>';
				$htmlMain .= '<br><br>';
				$htmlMain .= '<strong>Homepage : </strong>';
				$htmlMain .= '<br>';
				$htmlMain .= '<a href="'._URL_HOME.'" target="_blank"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Homepage on berliOS" border="0"> '._URL_HOME.'</a>';
				$htmlMain .= '<br>';
			}
			break;

		case "2": // News
			$htmlTitle = "tf-b4rt - News";
			$htmlMain .= '<br>';
			$htmlMain .= @gzinflate(getDataFromUrl(_SUPERADMIN_URLBASE . _SUPERADMIN_PROXY ."?a=0"));
			$htmlMain .= '<br><br>';
			break;

		case "3": // Changelog;
			$htmlTitle = "tf-b4rt - Changelog";
			$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
			$htmlMain .= '<pre>';
			$htmlMain .= @gzinflate(getDataFromUrl(_SUPERADMIN_URLBASE . _SUPERADMIN_PROXY ."?a=1"));
			$htmlMain .= '</pre>';
			$htmlMain .= '</div>';
			break;

		case "9": // Misc-main
			$htmlTitle = "tf-b4rt - Misc";
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?z=95" target="_blank"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Checksums of '._VERSION.'" border="0"> Checksums of '._VERSION.'</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?z=96" target="_blank"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Validate local files" border="0"> Validate local files</a>';
			$htmlMain .= '<br><br>';
			break;

		case "95": // Misc - Checksums - Latest
			@header("Content-Type: text/plain");
			echo getDataFromUrl(_SUPERADMIN_URLBASE._FILE_CHECKSUMS_PRE._VERSION._FILE_CHECKSUMS_SUF);
			exit();

		case "96": // Misc - Validate
			validateLocalFiles();
			exit();

	}
	printPage();
	exit();
}

/**
 * update
 *
 * @param $action
 */
function sa_update($action = "") {
	global $cfg, $error, $statusImage, $statusMessage, $htmlTitle, $htmlTop, $htmlMain;
	if ($action == "")
		return;
	switch ($action) {

		case "0":
			// get updateIndex to check if update from this version possible
			$updateIndexData = trim(getDataFromUrl(_SUPERADMIN_URLBASE . _SUPERADMIN_PROXY ."?u=0&v=" . _VERSION));
			if ((isset($updateIndexData)) && ($updateIndexData != "")) {
				$updateIndexVars = explode("\n",$updateIndexData);
				$updatePossible = trim($updateIndexVars[0]);
				if ($updatePossible != "1") {
					buildPage("-u");
					printPage();
					exit();
				} else {
					$htmlTop = "<strong>Update - Check</strong>";
					$htmlMain = "<br>Update from your Version possible.";
					$htmlMain .= '<br><br>';
					$htmlMain .= '<form name="update" action="' . _FILE_THIS . '" method="post">';
					$htmlMain .= '<input type="Hidden" name="u" value="1">';
					$htmlMain .= '<input type="submit" value="Next Step - Database-Update">';
					$htmlMain .= '</form>';
					$htmlMain .= '<br>';
					$statusImage = "yellow.gif";
					$htmlTitle = "Update";
					printPage();
					exit();
				}
			} else {
				buildPage("-u");
				printPage();
				exit();
			}
			break;

		case "1":
			// get db-settings
			$updateDBData = trim(getDataFromUrl(_SUPERADMIN_URLBASE . _SUPERADMIN_PROXY ."?u=1&v=" . _VERSION));
			if ((isset($updateDBData)) && ($updateDBData != "")) {
				$updateDBVars = explode("\n",$updateDBData);
				$updateNeeded = trim($updateDBVars[0]);
				if ($updateNeeded == "0") {
					$htmlTop = "<strong>Update - Database</strong>";
					$htmlMain = "<br>Database-Update in this Update not needed.";
					$htmlMain .= '<br><br>';
					$htmlMain .= '<form name="update" action="' . _FILE_THIS . '" method="post">';
					$htmlMain .= '<input type="Hidden" name="u" value="3">';
					$htmlMain .= '<input type="submit" value="Next Step - File-Update">';
					$htmlMain .= '</form>';
					$htmlMain .= '<br>';
					$statusImage = "yellow.gif";
					$htmlTitle = "Update";
					printPage();
					exit();
				} else if ($updateNeeded == "1") {
					$htmlTop = "<strong>Update - Database</strong>";
					$htmlMain = "<br>Database-Update in this Update is needed.";
					$htmlMain .= '<br><br>';
					$htmlMain .= 'Type : <em>'.$cfg["db_type"].'</em>';
					$htmlMain .= '<br><br>';
					$htmlMain .= '<form name="update" action="' . _FILE_THIS . '" method="post">';
					$htmlMain .= '<input type="Hidden" name="u" value="2">';
					$htmlMain .= '<input type="submit" value="Next Step - Perform Database-Update">';
					$htmlMain .= '</form>';
					$htmlMain .= '<br>';
					$statusImage = "yellow.gif";
					$htmlTitle = "Update";
					printPage();
					exit();
				} else {
					updateError($updateDBData);
					exit();
				}
			} else {
				updateError();
			}
			break;

		case "2":
			// get sql-data
			$updateSQLData = @trim(gzinflate(getDataFromUrl(_SUPERADMIN_URLBASE . _SUPERADMIN_PROXY ."?u=2&v=" . _VERSION . "&d=".$cfg["db_type"])));
			if ((isset($updateSQLData)) && ($updateSQLData != "")) {
				sendLine('<strong>Update - Database</strong><br><br><em>Updating Database... Please Wait...</em><ul>');
				$updateSQLStates = explode("\n",$updateSQLData);
				// get ado-connection
				$dbCon = getAdoConnection();
				if (!$dbCon) {
					echo '</em></li></ul><font color="red"><strong>Error updating Database.</strong></font><br><br>Please restore backup and try again (or do manual update).<br><br>';
					echo $dbCon->ErrorMsg();
					exit();
				} else {
					foreach ($updateSQLStates as $sqlState) {
						$sqlState = trim($sqlState);
						if ((isset($sqlState)) && ($sqlState != "") && ((substr($sqlState, 0, 2)) != "--")) {
							sendLine('<li>'.$sqlState);
							$dbCon->Execute($sqlState);
							if($dbCon->ErrorNo() == 0) {
								sendLine(' <font color="green">Ok</font></li>');
							} else { // damn there was an error
								// close ado-connection
								$dbCon->Close();
								// talk and out
								echo '</em></li></ul><font color="red"><strong>Error updating Database.</strong></font><br><br>Please restore backup and try again (or do manual update).<br><br>';
								exit();
							}
						}
					}
					// close ado-connection
					$dbCon->Close();
					// talk and continue
					sendLine('</ul><p><font color="green">Database-Update done.</font><br><br>');
					sendLine('<form name="update" action="' . _FILE_THIS . '" method="post"><input type="Hidden" name="u" value="3"><input type="submit" value="Next Step - File-Update"></form><br>');
					exit();
				}
			} else {
				updateError("\n"."cant get update-sql."."\n".$updateSQLData);
			}
			break;

		case "3":
			// get file-list
			$updateFileList = @trim(gzinflate(getDataFromUrl(_SUPERADMIN_URLBASE . _SUPERADMIN_PROXY ."?u=3&v=" . _VERSION)));
			if ((isset($updateFileList)) && ($updateFileList != "")) {
				sendLine('<strong>Update - Files</strong>');
				sendLine('<br><br>');
				sendLine('Files that require an update in this Version :');
				sendLine('<pre>');
				sendLine($updateFileList);
				sendLine('</pre>');
				sendLine('<form name="update" action="' . _FILE_THIS . '" method="post">');
				sendLine('<input type="Hidden" name="u" value="4">');
				sendLine('<input type="submit" value="Next Step - Perform File-Update">');
				sendLine('</form>');
				sendLine('<strong>Ensure script can write to docroot <em>'.$cfg['docroot'].'</em> now !</strong>');
				exit();
			} else {
				updateError("\n"."cant get file-list."."\n".$updateFileList);
			}
			break;

		case "4":
			sendLine('<strong>Update - Files</strong><br><br><em>Updating Files... Please Wait...</em><br><ul>');
			sendLine('<li>Getting Update-Archive :<br>');
			@ini_set("allow_url_fopen", "1");
			@ini_set("user_agent", "torrentflux-b4rt/". _VERSION);
			// get md5
			$md5hash = getDataFromUrl(_SUPERADMIN_URLBASE . _SUPERADMIN_PROXY ."?u=4&v=" . _VERSION);
			if ((!isset($md5hash)) || (strlen($md5hash) != 32)) {
				sendLine('</li></ul><br><br><font color="red"><strong>Error getting Update-Archive.</strong></font><br><br>Please restore backup and try again (or do manual update).<br><br>');
				exit();
			}
			// download archive
			$fileHandle = @fopen($cfg['docroot']._UPDATE_ARCHIVE, "w");
			$urlHandle = @fopen(_SUPERADMIN_URLBASE . _SUPERADMIN_PROXY ."?u=5&v=" . _VERSION, 'r');
			if (($fileHandle) && ($urlHandle)) {
				$results = array();
				$i = 0;
				stream_set_timeout($urlHandle, 15);
				$info = stream_get_meta_data($urlHandle);
				while ((!feof($urlHandle)) && (!$info['timed_out'])) {
					$data = @fgets($urlHandle, 8192);
					$results[$i] = @fwrite($fileHandle, $data);
					$info = stream_get_meta_data($urlHandle);
					sendLine('.');
					$i++;
				}
				@fclose($fileHandle);
				@fclose($urlHandle);
				$done = true;
				foreach ($results as $result) {
					if ($result === false)
						$done = false;
				}
				if ($done) {
					sendLine('<font color="green">done</font></li>');
				} else {
					sendLine('<br></li></ul><br><br><strong><font color="red">Error writing archive <em>'.$cfg['docroot']._UPDATE_ARCHIVE.'</em>.</font></strong><br><br>Please restore backup and try again (or do manual update).<br><br>');
					exit();
				}
			} else {
				sendLine('</li></ul><br><br><strong><font color="red">Error updating files.</font></strong><br><br>Please restore backup and try again (or do manual update).<br><br>');
				exit();
			}
			// validate archive
			sendLine('<li>Validating Update-Archive : ');
			if ((file_exists($cfg['docroot']._UPDATE_ARCHIVE))
				&& ($md5hash == @md5_file($cfg['docroot']._UPDATE_ARCHIVE))) {
				sendLine('<font color="green">Ok</font> (<em>'.$md5hash.'</em>)<br></li>');
			} else {
				sendLine('<font color="red">failed</font></ul><br><br>Please restore backup and try again (or do manual update).</strong><br><br>');
				exit();
			}
			// extract archive
			sendLine('<li>Extracting Update-Archive : <br>');
			sendLine('<em>');
			$cmd  = 'cd '.escapeshellarg($cfg['docroot']).' && tar jxvf '._UPDATE_ARCHIVE;
			$cmd .= ' 2>&1';
			$handle = @popen($cmd, 'r');
			while (!@feof($handle)) {
				$read = @fread($handle, 64);
				sendLine(nl2br($read));
			}
			@pclose($handle);
			sendLine('</em>');
			sendLine('<font color="green">done</font></li>');
			// delete archive
			sendLine('<li>Deleting Update-Archive : ');
			if (@unlink($cfg['docroot']._UPDATE_ARCHIVE))
				sendLine('<font color="green">done</font></li>');
			else
				sendLine('<font color="red">failed</font><br>remove archive '.$cfg['docroot']._UPDATE_ARCHIVE.' manual now.</li>');

			// version-file
			sendLine('<li>Updating Version-Information : ');
			$versionAvailable = trim(getDataFromUrl(_SUPERADMIN_URLBASE._SUPERADMIN_PROXY));
			if ((isset($versionAvailable)) && ($versionAvailable != "")) {
				if ($handle = @fopen("version.php", "w")) {
					if (@fwrite($handle, '<?php define("_VERSION", "'.$versionAvailable.'"); ?>')) {
						@fclose($handle);
						sendLine(' <font color="green">done</font></li>');
					} else {
						@fclose($handle);
						sendLine('</li></ul><br><br><font color="red"><strong>Error writing version-file</strong></font><br><br>Please restore backup and try again (or do manual update).<br><br>');
						exit();
					}
				} else {
					sendLine('<br><br>');
					sendLine('</li></ul><font color="red"><strong>Error writing version-file</strong></font><br><br>Please restore backup and try again (or do manual update).<br><br>');
					exit();
				}
			} else {
				sendLine('</li></ul><br><br><font color="red"><strong>Error getting version-file</strong></font><br><br>Please restore backup and try again (or do manual update).<br><br>');
				exit();
			}
			sendLine('</ul>');
			// done
			sendLine('<p><em>Done Updating Files.</em></p>');
			sendLine('<hr><br><strong>Update to '.$versionAvailable.' completed.</strong><br><br>');
			sendLine('<form name="update" action="#" method="get"><input type="submit" onClick="window.close()" value="Close"></form>');
			sendLine('<br>');
			// flush cache
			cacheFlush();
			// exit
			exit();

	}
	exit();
}

/**
 * fluxd
 *
 * @param $action
 */
function sa_fluxd($action = "") {
	global $cfg, $error, $statusImage, $statusMessage, $htmlTitle, $htmlTop, $htmlMain;
	if ($action == "")
		return;
	switch ($action) {

		case "0": // fluxd-main
			$htmlTitle = "fluxd";
			break;

		case "1": // fluxd-log
			$htmlTitle = "fluxd - log";
			$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
			$htmlMain .= '<pre>';
			$htmlMain .= @file_get_contents($cfg["path"].'.fluxd/fluxd.log');
			$htmlMain .= '</pre>';
			$htmlMain .= '</div>';
			break;

		case "2": // fluxd-error-log
			$htmlTitle = "fluxd - error-log";
			$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
			$htmlMain .= '<pre>';
			$htmlMain .= @file_get_contents($cfg["path"].'.fluxd/fluxd-error.log');
			$htmlMain .= '</pre>';
			$htmlMain .= '</div>';
			break;

		case "3": // fluxd-ps
			$htmlTitle = "fluxd - ps";
			$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
			$htmlMain .= '<pre>';
			$htmlMain .= shell_exec("ps auxww | ".$cfg['bin_grep']." fluxd | ".$cfg['bin_grep']." -v grep");
			$htmlMain .= '</pre>';
			$htmlMain .= '</div>';
			break;

		case "4": // fluxd-status
			$htmlTitle = "fluxd - status";
			if (Fluxd::isRunning()) {
				$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
				$htmlMain .= '<pre>';
				$htmlMain .= Fluxd::status();
				$htmlMain .= '</pre>';
				$htmlMain .= '</div>';
			} else {
				$htmlMain .= '<br><strong>fluxd not running</strong>';
			}
			break;

		case "5": // fluxd-check
			$htmlTitle = "fluxd - check";
			$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
			$htmlMain .= '<pre>';
			$htmlMain .= shell_exec($cfg["perlCmd"]." -I ".$cfg["docroot"]."bin/fluxd -I ".$cfg["docroot"]."bin/lib ".$cfg["docroot"]."bin/fluxd/fluxd.pl check");
			$htmlMain .= '</pre>';
			$htmlMain .= '</div>';
			break;

		case "6": // fluxd-db-debug
			$htmlTitle = "fluxd - db-debug";
			$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
			$htmlMain .= '<pre>';
			$htmlMain .= shell_exec($cfg["perlCmd"]." -I ".$cfg["docroot"]."bin/fluxd -I ".$cfg["docroot"]."bin/lib ".$cfg["docroot"]."bin/fluxd/fluxd.pl debug db ".$cfg["docroot"]." ".$cfg["path"]." ".$cfg["bin_php"]);
			$htmlMain .= '</pre>';
			$htmlMain .= '</div>';
			break;

		case "9": // fluxd-version
			$htmlTitle = "fluxd - version";
			$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
			$htmlMain .= '<pre>';
			$htmlMain .= shell_exec($cfg["perlCmd"]." -I ".$cfg["docroot"]."bin/fluxd -I ".$cfg["docroot"]."bin/lib ".$cfg["docroot"]."bin/fluxd/fluxd.pl version");
			$htmlMain .= '</pre>';
			$htmlMain .= '</div>';
			break;
	}
	printPage();
	exit();
}

/**
 * fluazu
 *
 * @param $action
 */
function sa_fluazu($action = "") {
	global $cfg, $error, $statusImage, $statusMessage, $htmlTitle, $htmlTop, $htmlMain;
	if ($action == "")
		return;
	switch ($action) {

		case "0": // fluazu-main
			$htmlTitle = "fluazu";
			break;

		case "1": // fluazu-log
			$htmlTitle = "fluazu - log";
			$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
			$htmlMain .= '<pre>';
			$htmlMain .= @file_get_contents($cfg["path"].'.fluazu/fluazu.log');
			$htmlMain .= '</pre>';
			$htmlMain .= '</div>';
			break;

		case "2": // fluazu-error-log
			$htmlTitle = "fluazu - error-log";
			$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
			$htmlMain .= '<pre>';
			$htmlMain .= @file_get_contents($cfg["path"].'.fluazu/fluazu-error.log');
			$htmlMain .= '</pre>';
			$htmlMain .= '</div>';
			break;

		case "3": // fluazu-ps
			$htmlTitle = "fluazu - ps";
			$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
			$htmlMain .= '<pre>';
			$htmlMain .= shell_exec("ps auxww | ".$cfg['bin_grep']." fluazu.py | ".$cfg['bin_grep']." -v grep");
			$htmlMain .= '</pre>';
			$htmlMain .= '</div>';
			break;

		case "9": // fluazu-version
			$htmlTitle = "fluazu - version";
			$htmlMain .= '<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid '.$cfg['main_bgcolor'].'; position:relative; width:740; height:498; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">';
			$htmlMain .= '<pre>';
			$htmlMain .= shell_exec("cd ".$cfg["docroot"]."bin/clients/fluazu/; ".$cfg["pythonCmd"]." -OO fluazu.py --version");
			$htmlMain .= '</pre>';
			$htmlMain .= '</div>';
			break;
	}
	printPage();
	exit();
}

/**
 * superadminAuthentication
 *
 * @param $message
 */
function superadminAuthentication($message = "") {
	if (!IsSuperAdmin()) {
		@header("Content-Type: text/plain");
		echo "\nAccess Error"."\n\n";
		if ((isset($message)) && ($message != ""))
			echo $message."\n";
		else
			echo "Only SuperAdmin can access superadmin-page.\n";
		exit();
	}
}

/**
 * builds page
 *
 * @param $action
 */
function buildPage($action) {
	global $cfg, $statusImage, $statusMessage, $htmlTitle, $htmlTop, $htmlMain;
	// navi
	$htmlTop .= '<a href="' . _FILE_THIS . '?t=0">Transfers</a>';
	$htmlTop .= ' | ';
	$htmlTop .= '<a href="' . _FILE_THIS . '?p=0">Processes</a>';
	$htmlTop .= ' | ';
	$htmlTop .= '<a href="' . _FILE_THIS . '?m=0">Maintenance</a>';
	$htmlTop .= ' | ';
	$htmlTop .= '<a href="' . _FILE_THIS . '?b=0">Backup</a>';
	$htmlTop .= ' | ';
	$htmlTop .= '<a href="' . _FILE_THIS . '?l=0">Log</a>';
	$htmlTop .= ' | ';
	$htmlTop .= '<a href="' . _FILE_THIS . '?y=0">Misc</a>';
	$htmlTop .= ' | ';
	$htmlTop .= '<a href="' . _FILE_THIS . '?z=0">tf-b4rt</a>';
	// body
	switch($action) {
		case "b": // backup passthru
		case "-b": // backup-error passthru
			if ($action == "b")
				$statusImage = "yellow.gif";
			else
				$statusImage = "red.gif";
			//
			$htmlMain .= '<table width="100%" bgcolor="'.$cfg["table_data_bg"].'" border="0" cellpadding="4" cellspacing="0"><tr><td width="100%">';
			$htmlMain .= '<a href="' . _FILE_THIS . '?b=0">Create Backup</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?b=3">Backups on Server</a>';
			$htmlMain .= '</td><td align="right" nowrap><strong>Backup</strong></td>';
			$htmlMain .= '</tr></table>';
			break;
		case "-u": // update-error passthru
			$statusImage = "red.gif";
			$htmlTitle = "Update";
			$htmlMain = '<br><font color="red"><strong>Update from your Version not possible.</strong></font>';
			$htmlMain .= '<br><br>';
			$htmlMain .= 'Please use the most recent tarball and perform a manual update.';
			$htmlMain .= '<br>';
			break;
		case "t": // transfers passthru
			$statusImage = "black.gif";
			break;
		case "p": // processes passthru
			$statusImage = "black.gif";
			$htmlMain .= '<table width="100%" bgcolor="'.$cfg["table_data_bg"].'" border="0" cellpadding="4" cellspacing="0"><tr><td width="100%">';
			$htmlMain .= '<a href="' . _FILE_THIS . '?p=1">All</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?p=2">Transfers</a>';
			$htmlMain .= '</td><td align="right"><strong>Processes</strong></td>';
			$htmlMain .= '</tr></table>';
			break;
		case "m": // maintenance passthru
			$statusImage = "black.gif";
			$htmlMain .= '<table width="100%" bgcolor="'.$cfg["table_data_bg"].'" border="0" cellpadding="4" cellspacing="0"><tr><td width="100%">';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=1">Main</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=2">Kill</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=3">Clean</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=4">Repair</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=5">Reset</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=6">Lock</a>';
			$htmlMain .= '</td><td align="right"><strong>Maintenance</strong></td>';
			$htmlMain .= '</tr></table>';
			break;
		case "l": // log passthru
			$statusImage = "black.gif";
			$htmlMain .= '<table width="100%" bgcolor="'.$cfg["table_data_bg"].'" border="0" cellpadding="4" cellspacing="0"><tr><td width="100%">';
			$htmlMain .= '<a href="' . _FILE_THIS . '?l=1">fluxd</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?l=2">fluxd-error</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?l=5">mainline</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?l=8">transfers</a>';
			$htmlMain .= '</td><td align="right"><strong>Log</strong></td>';
			$htmlMain .= '</tr></table>';
			break;
		case "y": // misc passthru
			$statusImage = "black.gif";
			$htmlMain .= '<table width="100%" bgcolor="'.$cfg["table_data_bg"].'" border="0" cellpadding="4" cellspacing="0"><tr><td width="100%">';
			$htmlMain .= '<a href="' . _FILE_THIS . '?y=1">Lists</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?y=5">Check</a>';
			$htmlMain .= '</td><td align="right" nowrap><strong>Misc</strong></td>';
			$htmlMain .= '</tr></table>';
			break;
		case "z": // tf-b4rt passthru
			$statusImage = "black.gif";
			$htmlMain .= '<table width="100%" bgcolor="'.$cfg["table_data_bg"].'" border="0" cellpadding="4" cellspacing="0"><tr><td width="100%">';
			$htmlMain .= '<a href="' . _FILE_THIS . '?z=1">Version</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?z=2">News</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?z=3">Changelog</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?z=9">Misc</a>';
			$htmlMain .= '</td><td align="right" nowrap><strong>tf-b4rt</strong></td>';
			$htmlMain .= '</tr></table>';
			break;
		case "f": // fluxd passthru
			$htmlTop = "";
			$statusImage = "";
			$htmlMain .= '<table width="100%" bgcolor="'.$cfg["table_data_bg"].'" border="0" cellpadding="4" cellspacing="0"><tr><td width="100%">';
			$htmlMain .= '<a href="' . _FILE_THIS . '?f=1">log</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?f=2">error-log</a>';
			$htmlMain .= ' | ';
			$htmlMain .= '<a href="' . _FILE_THIS . '?f=3">ps</a>';
			if (Fluxd::isRunning()) {
				$htmlMain .= ' | ';
				$htmlMain .= '<a href="' . _FILE_THIS . '?f=4">status</a>';
			} else {
				$htmlMain .= ' | ';
				$htmlMain .= '<a href="' . _FILE_THIS . '?f=5">check</a>';
				$htmlMain .= ' | ';
				$htmlMain .= '<a href="' . _FILE_THIS . '?f=6">db-debug</a>';
				$htmlMain .= ' | ';
				$htmlMain .= '<a href="' . _FILE_THIS . '?f=9">version</a>';
			}
			$htmlMain .= '</td><td align="right"><strong>fluxd</strong>';
			$htmlMain .= '</tr></table>';
			break;
		case "_": // default
		default:
			$htmlTitle = "SuperAdmin";
			$statusImage = "black.gif";
			$htmlMain = '<br>';
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?t=0"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Transfers" border="0"> Transfers</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?p=0"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Processes" border="0"> Processes</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?m=0"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Maintenance" border="0"> Maintenance</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?b=0"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Backup" border="0"> Backup</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?l=0"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Log" border="0"> Log</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?y=0"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="Misc" border="0"> Misc</a>';
			$htmlMain .= '<p>';
			$htmlMain .= '<a href="' . _FILE_THIS . '?z=0"><img src="themes/'.$cfg["theme"].'/images/arrow.gif" width="9" height="9" title="tf-b4rt" border="0"> tf-b4rt</a>';
			$htmlMain .= '<br><br>';
			break;
	}
}

/**
 * echo a string. use echo or sendLine
 *
 * @param $string : string to echo
 * @param $mode : 0 = echo | 1 = sendLine
 */
function doEcho($string, $mode = 0) {
	switch ($mode) {
		case 0:
			echo $string;
			return;
		case 1:
			sendLine($string);
			return;
	}
}

/**
 * prints the page
 */
function printPage() {
	printPageStart(0);
	global $htmlMain;
	echo $htmlMain;
	printPageEnd(0);
}

/**
 * prints the page-start
 */
function printPageStart($echoMode = 0) {
	global $cfg, $statusImage, $statusMessage, $htmlTitle, $htmlTop, $htmlMain;
	doEcho('<HTML>',$echoMode);
	doEcho('<HEAD>',$echoMode);
	doEcho('<TITLE>torrentflux-b4rt - SuperAdmin</TITLE>',$echoMode);
	doEcho('<link rel="icon" href="themes/'.$cfg["theme"].'/images/favicon.ico" type="image/x-icon" />',$echoMode);
	doEcho('<link rel="shortcut icon" href="themes/'.$cfg["theme"].'/images/favicon.ico" type="image/x-icon" />',$echoMode);
	// theme-switch
	if ((strpos($cfg["theme"], '/')) === false)
		doEcho('<LINK REL="StyleSheet" HREF="themes/'.$cfg["theme"].'/css/default.css" TYPE="text/css">',$echoMode);
	else
		doEcho('<LINK REL="StyleSheet" HREF="themes/'.$cfg["theme"].'/style.css" TYPE="text/css">',$echoMode);
	doEcho('<META HTTP-EQUIV="Pragma" CONTENT="no-cache; charset='. $cfg['_CHARSET'] .'">',$echoMode);
	doEcho('</HEAD>',$echoMode);
	doEcho('<BODY topmargin="8" leftmargin="5" bgcolor="'.$cfg["main_bgcolor"].'">',$echoMode);
	doEcho('<div align="center">',$echoMode);
	doEcho('<table border="0" cellpadding="0" cellspacing="0">',$echoMode);
	doEcho('<tr>',$echoMode);
	doEcho('<td>',$echoMode);
	doEcho('<table border="1" bordercolor="'.$cfg["table_border_dk"].'" cellpadding="4" cellspacing="0">',$echoMode);
	doEcho('<tr>',$echoMode);
	doEcho('<td bgcolor="'.$cfg["main_bgcolor"].'" background="themes/'.$cfg["theme"].'/images/bar.gif">',$echoMode);
	doEcho('<table width="100%" cellpadding="0" cellspacing="0" border="0">',$echoMode);
	doEcho('<tr>',$echoMode);
	doEcho('<td align="left"><font class="title">'.$cfg["pagetitle"]." - ".$htmlTitle.'</font></td>',$echoMode);
	doEcho('</td>',$echoMode);
	doEcho('</tr>',$echoMode);
	doEcho('</table>',$echoMode);
	doEcho('</td>',$echoMode);
	doEcho('</tr>',$echoMode);
	doEcho('<tr>',$echoMode);
	doEcho('<td bgcolor="'.$cfg["table_header_bg"].'">',$echoMode);
	doEcho('<div align="center">',$echoMode);
	doEcho('<table width="100%" bgcolor="'.$cfg["body_data_bg"].'">',$echoMode);
	doEcho('<tr>',$echoMode);
	doEcho('<td>',$echoMode);
	doEcho('<div align="center">',$echoMode);
	doEcho('<table width="100%" cellpadding="0" cellspacing="0" border="0">',$echoMode);
	doEcho('<tr>',$echoMode);
	doEcho('<td align="left">',$echoMode);
	doEcho($htmlTop,$echoMode);
	doEcho('</td>',$echoMode);
	doEcho('<td align="right" width="16">',$echoMode);
	if ($statusImage != "") {
		if ($statusImage != "yellow.gif")
			doEcho('<a href="' . _FILE_THIS . '">',$echoMode);
		doEcho('<img src="themes/'.$cfg["theme"].'/images/'.$statusImage.'" width="16" height="16" border="0" title="'.$statusMessage.'">',$echoMode);
		if ($statusImage != "yellow.gif")
			doEcho('</a>',$echoMode);
	}
	doEcho('</td>',$echoMode);
	doEcho('</tr>',$echoMode);
	doEcho('</table>',$echoMode);
	doEcho('<table bgcolor="'.$cfg["table_header_bg"].'" width="750" cellpadding="1">',$echoMode);
	doEcho('<tr>',$echoMode);
	doEcho('<td>',$echoMode);
	doEcho('<div align="left">',$echoMode);
	doEcho('<table border="0" cellpadding="2" cellspacing="2" width="100%">',$echoMode);
}

/**
 * prints the page-end
 */
function printPageEnd($echoMode = 0) {
	doEcho('</table>',$echoMode);
	doEcho('</div>',$echoMode);
	doEcho('</td>',$echoMode);
	doEcho('</tr>',$echoMode);
	doEcho('</table>',$echoMode);
	doEcho('</td>',$echoMode);
	doEcho('</tr>',$echoMode);
	doEcho('</table>',$echoMode);
	doEcho('</div>',$echoMode);
	doEcho('</td>',$echoMode);
	doEcho('</tr>',$echoMode);
	doEcho('</table>',$echoMode);
	doEcho('</td>',$echoMode);
	doEcho('</tr>',$echoMode);
	doEcho('</table>',$echoMode);
	doEcho('</div>',$echoMode);
	doEcho('</BODY>',$echoMode);
	doEcho('</HTML>',$echoMode);
}

/**
 * bails out cause of version-error.
 */
function updateErrorNice($message = "") {
	global $statusImage, $statusMessage, $htmlTop, $htmlMain;
	$htmlTop = "<strong>Update</strong>";
	$htmlMain = '<br><font color="red"><strong>Update from your Version not possible.</strong></font>';
	$htmlMain .= '<br><br>';
	$htmlMain .= 'Please use the most recent tarball and perform a manual update.';
	$htmlMain .= '<br>';
	if ((isset($message)) && ($message != "") && (trim($message) != "0"))
		$htmlMain .= '<br><pre>'.$message.'</pre>';
	$statusImage = "red.gif";
	printPage();
	exit();
}

/**
 * bails out cause of version-error.
 */
function updateError($message = "") {
	$errorString = "ERROR processing auto-update. please do manual update.";
	if ((isset($message)) && ($message != ""))
		$errorString .= "\n".$message;
	@header("Content-Type: text/plain");
	echo $errorString;
	exit();
}

/**
 * get a ado-connection to our database.
 *
 * @return database-connection or false on error
 */
function getAdoConnection() {
	global $cfg;
	// create ado-object
    $db = &ADONewConnection($cfg["db_type"]);
    // connect
    @ $db->Connect($cfg["db_host"], $cfg["db_user"], $cfg["db_pass"], $cfg["db_name"]);
    // check for error
    if ($db->ErrorNo() != 0)
    	return false;
    // return db-connection
	return $db;
}

/**
 * get release-list
 *
 * @return release-list as html-snip
 */
function getReleaseList() {
	global $cfg, $error;
	$retVal = "";
	$releaseList = @gzinflate(getDataFromUrl(_SUPERADMIN_URLBASE . _SUPERADMIN_PROXY ."?a=3"));
	if ((isset($releaseList)) && ($releaseList != "")) {
		$retVal .= '<strong>Available Tarballs : </strong>';
		$retVal .= '<br>';
		$retVal .= '<table cellpadding="2" cellspacing="1" border="1" bordercolor="'.$cfg["table_border_dk"].'" bgcolor="'.$cfg["body_data_bg"].'">';
		$retVal .= '<tr>';
		$retVal .= '<td align="center" bgcolor="'.$cfg["table_header_bg"].'">&nbsp;</td>';
		$retVal .= '<td align="center" bgcolor="'.$cfg["table_header_bg"].'"><strong>Version</strong></td>';
		$retVal .= '<td align="center" bgcolor="'.$cfg["table_header_bg"].'"><strong>Checksum</strong></td>';
		$retVal .= '</tr>';
		$releaseListFiles = explode("\n",$releaseList);
		foreach ($releaseListFiles as $release) {
			$release = trim($release);
			if ((isset($release)) && ($release != "")) {
				$tempArray = explode("_", $release);
				$tempString = array_pop($tempArray);
				$releaseVersion = substr($tempString, 0, -8);
				$retVal .= '<tr>';
				$retVal .= '<td align="center">';
				$retVal .= '<a href="'._SUPERADMIN_URLBASE.'files/'.$release.'">';
				$retVal .= '<img src="themes/'.$cfg["theme"].'/images/download_owner.gif" title="Download '.$releaseVersion.'" border="0">';
				$retVal .= '</a>';
				$retVal .= '</td>';
				$retVal .= '<td align="right">';
				$retVal .= '<a href="'._SUPERADMIN_URLBASE.'files/'.$release.'">';
				$retVal .= $releaseVersion;
				$retVal .= '</a>';
				$retVal .= '</td>';
				$retVal .= '<td align="right">';
				$retVal .= '<a href="'._SUPERADMIN_URLBASE.'files/'.$release.'.md5">';
				$retVal .= 'md5';
				$retVal .= '</a>';
				$retVal .= '</td>';
				$retVal .= '</tr>';
			}
		}
		$retVal .= '</table>';
	}
	return $retVal;
}

/**
 * cleans a dir (deletes all files)
 *
 * @param $dir
 * @return string with deleted files
 */
function cleanDir($dir) {
	if (((strlen($dir) > 0)) && (substr($dir, -1 ) != "/"))
		$dir .= "/";
	$result = "";
	$dirHandle = false;
	$dirHandle = @opendir($dir);
	if ($dirHandle === false) return $result;
	while (false !== ($file = @readdir($dirHandle))) {
		if ((@is_file($dir.$file)) && ((substr($file, 0, 1)) != ".")) {
			if (@unlink($dir.$file) === true)
				$result .= $file."\n";
			else
				$result .= "ERROR : ".$file."\n";
		}
	}
	@closedir($dirHandle);
	return $result;
}

/**
 * formats a timestamp-string to human readable format.
 *
 * @param $timestampString string with prop. timestamp
 * @return string with human-readable date
 */
function formatHumanDate($timestampString) {
	return gmstrftime("%b %d %Y %H:%M:%S", mktime(
		intval(substr($timestampString, 8, 2)),
		intval(substr($timestampString, 10, 2)),
		intval(substr($timestampString, 12, 2)),
		intval(substr($timestampString, 4, 2)),
		intval(substr($timestampString, 6, 2)),
		intval(substr($timestampString, 0, 4))
		));
}

/**
 * formats a size-string to human readable format.
 *
 * @param $sizeInByte number with bytes
 * @return string with human-readable size
 */
function formatHumanSize($sizeInByte) {
	if ($sizeInByte > (1073741824)) // > 1G
		return (string) (round($sizeInByte/(1073741824), 1))."G";
	if ($sizeInByte > (1048576)) // > 1M
		return (string) (round($sizeInByte/(1048576), 1))."M";
	if ($sizeInByte > (1024)) // > 1k
		return (string) (round($sizeInByte/(1024), 1))."k";
	return (string) $sizeInByte;
}

/**
 * checks if backup-id is a valid backup-archive
 *
 * @param $param the param with the backup-id
 * @param boolean if archive-name is a valid backup-archive
 */
function backupParamCheck($param) {
	global $cfg, $error;
	// sanity-checks
	if (preg_match("/\\\/", urldecode($param)))
		return false;
	if (preg_match("/\.\./", urldecode($param)))
		return false;
	// check id
	$fileList = backupList();
	if ((isset($fileList)) && ($fileList != "")) {
		$validFiles = explode("\n",$fileList);
		return (in_array($param, $validFiles));
	} else {
		return false;
	}
	return false;
}

/**
 * build backup-list
 *
 * @return backup-list as string
 */
function backupListDisplay() {
	global $cfg, $error;
	// backup-dir
	$dirBackup = $cfg["path"]. _DIR_BACKUP . '/';
	//
	$retVal = "";
	$fileList = backupList();
	if ((isset($fileList)) && ($fileList != "")) {
		$retVal .= '<table cellpadding="2" cellspacing="1" border="1" bordercolor="'.$cfg["table_admin_border"].'" bgcolor="'.$cfg["body_data_bg"].'">';
		$retVal .= '<tr>';
		$retVal .= '<td align="center" bgcolor="'.$cfg["table_header_bg"].'"><strong>Version</strong></td>';
		$retVal .= '<td align="center" bgcolor="'.$cfg["table_header_bg"].'"><strong>Date</strong></td>';
		$retVal .= '<td align="center" bgcolor="'.$cfg["table_header_bg"].'"><strong>Comp.</strong></td>';
		$retVal .= '<td align="center" bgcolor="'.$cfg["table_header_bg"].'"><strong>Size</strong></td>';
		$retVal .= '<td align="center" bgcolor="'.$cfg["table_header_bg"].'">&nbsp;</td>';
		$retVal .= '</tr>';
		// theme-switch
		if ((strpos($cfg["theme"], '/')) === false)
			$theme = $cfg["theme"];
		else
			$theme = "tf_standard_themes";
		$backupListFiles = explode("\n",$fileList);
		foreach ($backupListFiles as $backup) {
			$backup = trim($backup);
			$backupFile = $dirBackup.$backup;
			if ((isset($backup)) && ($backup != "") && (is_file($backupFile))) {
				$backupElements = explode("_",$backup);
				$retVal .= '<tr>';
				$retVal .= '<td align="center">'.$backupElements[1].'</td>';
				$retVal .= '<td align="right">'.formatHumanDate(substr($backupElements[2], 0, 14)).'</td>';
				$lastChar = substr($backupElements[2], -1, 1);
				$retVal .= '<td align="center">';
				switch ($lastChar) {
					case "r":
						$retVal .= 'none';
						break;
					case "z":
						$retVal .= 'gzip';
						break;
					case "2":
						$retVal .= 'bzip2';
						break;
					default:
						$retVal .= 'unknown';
						break;
				}
				$retVal .= '</td>';
				$retVal .= '<td align="right">'.(string)(formatHumanSize(filesize($backupFile))).'</td>';
				$retVal .= '<td align="center">';
				$retVal .= '<a href="'. _FILE_THIS .'?b=4&f='.$backup.'">';
				$retVal .= '<img src="themes/'.$cfg["theme"].'/images/download_owner.gif" title="Download" border="0">';
				$retVal .= '</a>';
				$retVal .= '&nbsp;&nbsp;';
				$retVal .= '<a href="'. _FILE_THIS .'?b=5&f='.$backup.'">';
				$retVal .= '<img src="themes/'.$theme.'/images/delete.png" title="Delete" border="0">';
				$retVal .= '</a>';
				$retVal .= '</td>';
				$retVal .= '</tr>';
			}
		}
		$retVal .= '</table>';
	} else {
		$retVal .= '<strong>No Backups on Server</strong>';
	}
	return $retVal;
}

/**
 * get backup-list
 *
 * @return backup-list as string or empty string on error / no files
 */
function backupList() {
	global $cfg, $error;
	// backup-dir
	$dirBackup = $cfg["path"]. _DIR_BACKUP;
	if (file_exists($dirBackup)) {
		if ($dirHandle = opendir($dirBackup)) {
			$fileList = "";
			while (false !== ($file = readdir($dirHandle))) {
				if ((substr($file, 0, 1)) != ".")
					$fileList .= $file . "\n";
			}
			closedir($dirHandle);
			return $fileList;
		} else {
			return "";
		}
	} else {
		return "";
	}
}

/**
 * deletes a backup of a flux-installation
 *
 * @param $filename the file with the backup
 */
function backupDelete($filename) {
	global $cfg;
	$backupFile = $cfg["path"]. _DIR_BACKUP . '/' . $filename;
	@unlink($backupFile);
	AuditAction($cfg["constants"]["admin"], "Backup Deleted : ".$filename);
}

/**
 * sends a backup of flux-installation to a client
 *
 * @param $filename the file with the backup
 * @param $delete boolean if file should be deleted.
 */
function backupSend($filename, $delete = false) {
	global $cfg;
	$backupFile = $cfg["path"]. _DIR_BACKUP . '/' . $filename;
	if ($delete) {
		@session_write_close();
		@ob_end_clean();
		if (connection_status() != 0)
			return false;
		set_time_limit(0);
	}
	if (!is_file($backupFile))
		return false;
	// log before we screw up the file-name
	AuditAction($cfg["constants"]["admin"], "Backup Sent : ".$filename);
	// filenames in IE containing dots will screw up the filename
	if (strstr($_SERVER['HTTP_USER_AGENT'], "MSIE"))
		$filename = preg_replace('/\./', '%2e', $filename, substr_count($filename, '.') - 1);
	// send data
	@header("Cache-Control: ");
	@header("Pragma: ");
	@header("Content-Type: application/octet-stream");
	@header("Content-Length: " .(string)(filesize($backupFile)) );
	@header('Content-Disposition: attachment; filename="'.$filename.'"');
	@header("Content-Transfer-Encoding: binary\n");
	if ($delete) { // read data to mem, delete file and send complete
		$data = file_get_contents($backupFile);
		@unlink($backupFile);
		echo $data;
	} else { // read / write file with 8kb-buffer
		if ($handle = fopen($backupFile, 'rb')){
			while ((!feof($handle)) && (connection_status() == 0)) {
				print(fread($handle, 8192));
				flush();
			}
			fclose($handle);
		}
	}
	// return
	if ($delete) {
		return true;
	} else {
		return((connection_status()==0) and !connection_aborted());
	}
}

/**
 * backup of flux-installation
 *
 * @param $talk : boolean if function should talk
 * @param $compression : 0 = none | 1 = gzip | 2 = bzip2
 * @return string with name of backup-archive, string with "" in error-case.
 */
function backupCreate($talk = false, $compression = 0) {
	global $cfg, $error;
	// backup-dir
	$dirBackup = $cfg["path"]. _DIR_BACKUP;
	if (!checkDirectory($dirBackup)) {
		$error = "Errors when checking/creating backup-dir : ".$dirBackup;
		return "";
	}
	// files and more strings
	$backupName = "backup_". _VERSION ."_".date("YmdHis");
	$fileArchiveName = $backupName.".tar";
	$tarSwitch = "-cf";
	switch ($compression) {
		case 1:
			$fileArchiveName .= ".gz";
			$tarSwitch = "-zcf";
			break;
		case 2:
			$fileArchiveName .= ".bz2";
			$tarSwitch = "-jcf";
			break;
	}
	// files
	$files = array();
	$files['archive'] = $dirBackup . '/' . $fileArchiveName;
	$files['db'] = $dirBackup . '/database.sql';
	$files['docroot'] = $dirBackup . '/docroot.tar';
	$files['transfers'] = $dirBackup . '/transfers.tar';
	$files['fluxd'] = $dirBackup . '/fluxd.tar';
	$files['mrtg'] = $dirBackup . '/mrtg.tar';
	// exec
	$exec = array();
	$exec['transfers'] = ((@is_dir($cfg["transfer_file_path"])) === true);
	$exec['fluxd'] = ((@is_dir($cfg["path"].'.fluxd')) === true);
	$exec['mrtg'] = ((@is_dir($cfg["path"].'.mrtg')) === true);
	// commands
	$commands = array();
	$commands['archive'] = "cd ".$dirBackup."; tar ".$tarSwitch." ".$fileArchiveName." ";
	$commands['db'] = "";
	switch ($cfg["db_type"]) {
		case "mysql":
			$commands['db'] = "mysqldump -h ".$cfg["db_host"]." -u ".$cfg["db_user"]." --password=".$cfg["db_pass"]." --all -f ".$cfg["db_name"]." > ".$files['db'];
			$commands['archive'] .= 'database.sql ';
			break;
		case "sqlite":
			$commands['db'] = "sqlite ".$cfg["db_host"]." .dump > ".$files['db'];
			$commands['archive'] .= 'database.sql ';
			break;
		case "postgres":
			$commands['db'] = "pg_dump -h ".$cfg["db_host"]." -D ".$cfg["db_name"]." -U ".$cfg["db_user"]." -f ".$files['db'];
			$commands['archive'] .= 'database.sql ';
			break;
	}
	$commands['archive'] .= 'docroot.tar';
	if ($exec['transfers'] === true)
		$commands['archive'] .= ' transfers.tar';
	if ($exec['fluxd'] === true)
		$commands['archive'] .= ' fluxd.tar';
	if ($exec['mrtg'] === true)
		$commands['archive'] .= ' mrtg.tar';
	//$commands['docroot'] = "cd ".$dirBackup."; tar -cf docroot.tar ".$cfg["docroot"]; // with path of docroot
	$commands['docroot'] = "cd ".escapeshellarg($cfg["docroot"])."; tar -cf ".$files['docroot']." ."; // only content of docroot
	$commands['transfers'] = "cd ".escapeshellarg($cfg["transfer_file_path"])."; tar -cf ".$files['transfers']." .";
	$commands['fluxd'] = "cd ".escapeshellarg($cfg["path"].'.fluxd')."; tar -cf ".$files['fluxd']." .";
	$commands['mrtg'] = "cd ".escapeshellarg($cfg["path"].'.mrtg')."; tar -cf ".$files['mrtg']." .";
	// action
	if ($talk)
		sendLine('<br>');
	// database-command
	if ($commands['db'] != "") {
		if ($talk)
			sendLine('Backup of Database <em>'.$cfg["db_name"].'</em> ...');
		shell_exec($commands['db']);
	}
	if ($talk)
		sendLine(' <font color="green">Ok</font><br>');
	// docroot-command
	if ($talk)
		sendLine('Backup of Docroot <em>'.$cfg["docroot"].'</em> ...');
	shell_exec($commands['docroot']);
	if ($talk)
		sendLine(' <font color="green">Ok</font><br>');
	// transfers-command
	if ($exec['transfers'] === true) {
		if ($talk)
			sendLine('Backup of transfers <em>'.$cfg["transfer_file_path"].'</em> ...');
		shell_exec($commands['transfers']);
		if ($talk)
			sendLine(' <font color="green">Ok</font><br>');
	}
	// fluxd-command
	if ($exec['fluxd'] === true) {
		if ($talk)
			sendLine('Backup of fluxd <em>'.$cfg["path"].'.fluxd'.'</em> ...');
		shell_exec($commands['fluxd']);
		if ($talk)
			sendLine(' <font color="green">Ok</font><br>');
	}
	// mrtg-command
	if ($exec['mrtg'] === true) {
		if ($talk)
			sendLine('Backup of mrtg <em>'.$cfg["path"].'.mrtg'.'</em> ...');
		shell_exec($commands['mrtg']);
		if ($talk)
			sendLine(' <font color="green">Ok</font><br>');
	}
	// create the archive
	if ($talk)
		sendLine('Creating Archive <em>'.$fileArchiveName.'</em> ...');
	shell_exec($commands['archive']);
	if ($talk)
		sendLine(' <font color="green">Ok</font><br>');
	// delete temp-file(s)
	if ($talk)
		sendLine('Deleting temp-files ...');
	if ($commands['db'] != "")
		@unlink($files['db']);
	@unlink($files['docroot']);
	@unlink($files['transfers']);
	@unlink($files['fluxd']);
	@unlink($files['mrtg']);
	if ($talk)
		sendLine(' <font color="green">Ok</font><br>');
	// log
	if ($talk)
		sendLine('<font color="green">Backup Complete.</font><br>');
	AuditAction($cfg["constants"]["admin"], "Backup Created : ".$fileArchiveName);
	return $fileArchiveName;
}

/**
 * validate Local Files
 */
function validateLocalFiles() {
	sendLine('<h3>Validate Files</h3>');
	sendLine('<strong>Getting Checksum-list</strong>');
	// download list
	$checksumsString = "";
	@ini_set("allow_url_fopen", "1");
	@ini_set("user_agent", "torrentflux-b4rt/". _VERSION);
	if ($urlHandle = @fopen(_SUPERADMIN_URLBASE._FILE_CHECKSUMS_PRE._VERSION._FILE_CHECKSUMS_SUF, 'r')) {
		stream_set_timeout($urlHandle, 15);
		$info = stream_get_meta_data($urlHandle);
		while ((!feof($urlHandle)) && (!$info['timed_out'])) {
			$checksumsString .= @fgets($urlHandle, 8192);
			$info = stream_get_meta_data($urlHandle);
			sendLine('.');
		}
		@fclose($urlHandle);
	}
	if (empty($checksumsString))
		exit('error getting checksum-list from '._SUPERADMIN_URLBASE);
	sendLine('<font color="green">done</font><br>');
	sendLine('<br><strong>Processing list</strong>');
	// remote Checksums
	$remoteChecksums = array();
	$remoteSums = explode("\n", $checksumsString);
	$remoteSums = array_map('trim', $remoteSums);
	foreach ($remoteSums as $remSum) {
		$tempAry = explode(";", $remSum);
		if ((!empty($tempAry[0])) && (!empty($tempAry[1]))) {
			$remoteChecksums[$tempAry[0]] = $tempAry[1];
			sendLine('.');
		}
	}
	$remoteChecksumsCount = count($remoteChecksums);
	sendLine('<font color="green">done</font> ('.$remoteChecksumsCount.')<br>');
	// local Checksums
	sendLine('<br><strong>Getting local checksums</strong>');
	$localChecksums = getFileChecksums(true);
	$localChecksumsCount = count($localChecksums);
	sendLine('<font color="green">done</font> ('.$localChecksumsCount.')<br>');
	// init some arrays
	$filesMissing = array();
	$filesNew = array();
	$filesOk = array();
	$filesChanged = array();
	// validate
	sendLine('<br><strong>Validating...</strong><br>');
	// validate pass 1
	foreach ($remoteChecksums as $file => $md5) {
		$line = $file;
		if (isset($localChecksums[$file])) {
			if ($md5 == $localChecksums[$file]) {
				array_push($filesOk, $file);
				$line .= ' <font color="green"> Ok</font>';
			} else {
				array_push($filesChanged, $file);
				$line .= ' <font color="red"> Changed</font>';
			}
		} else {
			array_push($filesMissing, $file);
			$line .= ' <font color="red"> Missing</font>';
		}
		sendLine($line."<br>");
	}
	// validate pass 2
	foreach ($localChecksums as $file => $md5)
		if (!isset($remoteChecksums[$file]))
			array_push($filesNew, $file);
	// summary
	sendLine('<h3>Done.</h3>');
	// files Total
	sendLine('<strong>'._VERSION.' : </strong>'.$remoteChecksumsCount.'<br>');
	sendLine('<strong>Local : </strong>'.$localChecksumsCount.'<br>');
	// files Ok
	sendLine('<strong>Unchanged : </strong>'.count($filesOk).'<br>');
	// files Missing
	sendLine('<strong>Missing : </strong>'.count($filesMissing).'<br>');
	// files Changed
	sendLine('<strong>Changed : </strong>'.count($filesChanged).'<br>');
	// files New
	sendLine('<strong>New : </strong>'.count($filesNew).'<br>');
	if (count($filesNew) > 0) {
		sendLine('<br><strong>New Files : </strong><br>');
		foreach ($filesNew as $newFile)
			sendLine($newFile.'<br>');
	}
}

/**
 * phpCheckWeb
 *
 * @return string
 */
function phpCheckWeb() {
	$retVal = "<br>";
	$errors = 0;
	$warnings = 0;
	$dbsupported = 0;
	$errorsMessages = array();
	$warningsMessages = array();
	// PHP-Version
	$retVal .= '<p><strong>1. PHP-Version</strong></p>';
	$phpVersion = 'PHP-Version : <em>'.PHP_VERSION.'</em> ';
	if (PHP_VERSION < 4.3) {
		$phpVersion .= '<font color="red">Failed</font>';
		$errors++;
		array_push($errorsMessages, "PHP-Version : 4.3 or higher required.");
	} else {
		$phpVersion .= '<font color="green">Passed</font>';
	}
	$retVal .= $phpVersion;
	// PHP-Extensions
	$retVal .= '<p><strong>2. PHP-Extensions</strong></p>';
	$retVal .= "<ul>";
	$loadedExtensions = get_loaded_extensions();
	// session
	$session = '<li>session ';
	if (in_array("session", $loadedExtensions)) {
		$session .= '<font color="green">Passed</font>';
	} else {
		$session .= '<font color="red">Failed</font>';
		$errors++;
		array_push($errorsMessages, "PHP-Extensions : session required.");
	}
	$retVal .= $session.'</li>';
	// pcre
	$pcre = '<li>pcre ';
	if (in_array("pcre", $loadedExtensions)) {
		$pcre .= '<font color="green">Passed</font>';
	} else {
		$pcre .= '<font color="red">Failed</font>';
		$errors++;
		array_push($errorsMessages, "PHP-Extensions : pcre required.");
	}
	$retVal .= $pcre.'</li>';
	// sockets
	$sockets = '<li>sockets ';
	if (in_array("sockets", $loadedExtensions)) {
		$sockets .= '<font color="green">Passed</font>';
	} else {
		$sockets .= '<font color="red">Failed</font>';
		$warnings++;
		array_push($warningsMessages, "PHP-Extensions : sockets required for communication with fluxd. fluxd cannot work without sockets.");
	}
	$retVal .= $sockets.'</li>';
	//
	$retVal .= "</ul>";
	// PHP-Configuration
	$retVal .= '<p><strong>3. PHP-Configuration</strong></p>';
	$retVal .= "<ul>";
	// safe_mode
	$safe_mode = '<li>safe_mode ';
	if ((ini_get("safe_mode")) == 0) {
		$safe_mode .= '<font color="green">Passed</font>';
	} else {
		$safe_mode .= '<font color="red">Failed</font>';
		$errors++;
		array_push($errorsMessages, "PHP-Configuration : safe_mode must be turned off.");
	}
	$retVal .= $safe_mode.'</li>';
	// allow_url_fopen
	$allow_url_fopen = '<li>allow_url_fopen ';
	if ((ini_get("allow_url_fopen")) == 1) {
		$allow_url_fopen .= '<font color="green">Passed</font>';
	} else {
		$allow_url_fopen .= '<font color="red">Failed</font>';
		array_push($warningsMessages, "PHP-Configuration : allow_url_fopen must be turned on. some features wont work if it is turned off.");
		$warnings++;
	}
	$retVal .= $allow_url_fopen.'</li>';
	// register_globals
	$register_globals = '<li>register_globals ';
	if ((ini_get("register_globals")) == 0) {
		$register_globals .= '<font color="green">Passed</font>';
	} else {
		$register_globals .= '<font color="red">Failed</font>';
		$errors++;
		array_push($errorsMessages, "PHP-Configuration : register_globals must be turned off.");
	}
	$retVal .= $register_globals.'</li>';
	//
	$retVal .= "</ul>";
	// PHP-Database-Support
	$retVal .= '<p><strong>4. PHP-Database-Support</strong></p>';
	$retVal .= "<ul>";
	// define valid db-types
	$databaseTypes = array();
	$databaseTypes['mysql'] = 'mysql_connect';
	$databaseTypes['sqlite'] = 'sqlite_open';
	$databaseTypes['postgres'] = 'pg_connect';
	// test db-types
	foreach ($databaseTypes as $databaseTypeName => $databaseTypeFunction) {
		$dbtest = '<li>'.$databaseTypeName.' ';
		if (function_exists($databaseTypeFunction)) {
			$dbtest .= '<font color="green">Passed</font>';
			$dbsupported++;
		} else {
			$dbtest .= '<font color="red">Failed</font>';
		}
		$retVal .= $dbtest.'</li>';
	}
	$retVal .= "</ul>";
	// db-state
	if ($dbsupported == 0) {
		$errors++;
		array_push($errorsMessages, "PHP-Database-Support : no supported database-type found.");
	}
	// OS-Specific
	// get os
	$osString = php_uname('s');
	if (isset($osString)) {
	    if (!(stristr($osString, 'linux') === false)) /* linux */
	    	define('_OS', 1);
	    else if (!(stristr($osString, 'bsd') === false)) /* bsd */
	    	define('_OS', 2);
	    else
	    	define('_OS', 0);
	} else {
		define('_OS', 0);
	}
	$retVal .= '<p><strong>5. OS-Specific ('.$osString.' '.php_uname('r').')</strong></p>';
	switch (_OS) {
		case 1: // linux
			$retVal .= 'No Special Requirements on Linux-OS. <font color="green">Passed</font>';
			break;
		case 2: // bsd
			$retVal .= "<ul>";
			// posix
			$posix = '<li>posix ';
			if ((function_exists('posix_geteuid')) && (function_exists('posix_getpwuid'))) {
				$posix .= '<font color="green">Passed</font>';
			} else {
				$posix .= '<font color="red">Failed</font>';
				$warnings++;
				array_push($warningsMessages, "OS-Specific : PHP-extension posix missing. some netstat-features wont work without.");
			}
			$retVal .= $posix.'</li>';
			$retVal .= "</ul>";
			break;
		case 0: // unknown
		default:
			$retVal .= "OS not supported.<br>";
			$errors++;
			array_push($errorsMessages, "OS-Specific : ".$osString." not supported.");
			break;
	}
	// summary
	$retVal .= '<p><strong>Summary</strong></p>';
	// state
	$state = "<strong>State : ";
	if (($warnings + $errors) == 0) {
		// good
		$state .= '<font color="green">Ok</font>';
		$state .= "</strong><br>";
		$retVal .= $state;
		$retVal .= "torrentflux-b4rt should run on this system.";
	} else {
		if (($errors == 0) && ($warnings > 0)) {
			// may run with flaws
			$state .= '<font color="orange">Warning</font>';
			$state .= "</strong><br>";
			$retVal .= $state;
			$retVal .= "torrentflux-b4rt may run on this system, but there may be problems.";
		} else {
			// not ok
			$state .= '<font color="red">Failed</font>';
			$state .= "</strong><br>";
			$retVal .= $state;
			$retVal .= "torrentflux-b4rt cannot run on this system.";
		}
	}
	// errors
	if (count($errorsMessages) > 0) {
		$retVal .= '<p><strong><font color="red">Errors : </font></strong><br>';
		$retVal .= "<ul>";
		foreach ($errorsMessages as $errorsMessage) {
			$retVal .= "<li>".$errorsMessage."</li>";
		}
		$retVal .= "</ul>";
	}
	// warnings
	if (count($warningsMessages) > 0) {
		$retVal .= '<p><strong><font color="orange">Warnings : </font></strong><br>';
		$retVal .= "<ul>";
		foreach ($warningsMessages as $warningsMessage) {
			$retVal .= "<li>".$warningsMessage."</li>";
		}
		$retVal .= "</ul>";
	}
	// return
	return $retVal;
}

?>