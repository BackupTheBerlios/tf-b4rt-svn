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
if ((!isset($cfg['user'])) || (isset($_REQUEST['cfg']))) {
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
$isSave = (isset($_REQUEST['save'])) ? true : false;

// validate transfer
if (isValidTransfer($transfer) !== true) {
	AuditAction($cfg["constants"]["error"], "INVALID TRANSFER: ".$transfer);
	@error("Invalid Transfer", "", "", array($transfer));
}

// init template-instance
tmplInitializeInstance($cfg["theme"], "page.transferSettings.tmpl");

// set transfer vars
$tmpl->setvar('transfer', $transfer);
$transferLabel = (strlen($transfer) >= 39) ? substr($transfer, 0, 35)."..." : $transfer;
$tmpl->setvar('transferLabel', $transferLabel);

// init ch-instance
$ch = ClientHandler::getInstance(getTransferClient($transfer));

// load settings
$loaded = $ch->settingsLoad($transfer);
// default-settings if settings could not be loaded (fresh transfer)
if ($loaded !== true)
	$ch->settingsDefault();

// set running-field
$ch->running = isTransferRunning($transfer) ? 1 : 0;

if ($isSave) { /* save */

	// set save-var
	$tmpl->setvar('isSave', 1);

	// send to client
	$doSend = ((isset($_REQUEST['sendbox'])) && ($ch->running == 1))
		? true
		: false;

	// settings-keys
	$settingsKeys = array(
		'max_upload_rate',
		'max_download_rate',
		'max_uploads',
		'superseeder',
		'torrent_dies_when_done',
		'sharekill',
		'minport',
		'maxport',
		'maxcons'
	);

	// runtime-settings
	$settingsRuntime = array(
		'max_upload_rate',
		'max_download_rate',
		'torrent_dies_when_done'
	);

	// settings-labels
	$settingsLabels = array(
		'max_upload_rate' => 'Max Upload Rate',
		'max_download_rate' => 'Max Download Rate',
		'max_uploads' => 'Max Upload Connections',
		'superseeder' => 'Superseeder',
		'torrent_dies_when_done' => 'Torrent Completion Activity',
		'sharekill' => 'Percentage When Seeding should Stop',
		'minport' => 'Min-Port',
		'maxport' => 'Max-Port',
		'maxcons' => 'Max Cons'
	);

	// current settings
	$settingsCurrent = array();
	$settingsCurrent['max_upload_rate'] = $ch->rate;
	$settingsCurrent['max_download_rate'] = $ch->drate;
	$settingsCurrent['max_uploads'] = $ch->maxuploads;
	$settingsCurrent['superseeder'] = $ch->superseeder;
	$settingsCurrent['torrent_dies_when_done'] = $ch->runtime;
	$settingsCurrent['sharekill'] = $ch->sharekill;
	$settingsCurrent['minport'] = $ch->minport;
	$settingsCurrent['maxport'] = $ch->maxport;
	$settingsCurrent['maxcons'] = $ch->maxcons;

	// new settings
	$settingsNew = array();
	foreach ($settingsKeys as $settingsKey) {
		$settingsNew[$settingsKey] = getRequestVar($settingsKey);
		if ($settingsNew[$settingsKey] == "")
			$settingsNew[$settingsKey] = $settingsCurrent[$settingsKey];
	}

	// process changes
	$settingsChanged = array();
	foreach ($settingsKeys as $settingsKey) {
		if ($settingsNew[$settingsKey] != $settingsCurrent[$settingsKey])
			array_push($settingsChanged, $settingsKey);
	}
	if (empty($settingsChanged)) { /* no changes */

		// set message-var
		$tmpl->setvar('message', "no changes");

	} else { /* something changed */

		// fill lists
		$list_changes = array();
		$list_restart = array();
		$list_send = array();
		foreach ($settingsChanged as $settingsKey) {
			// value
			switch ($settingsKey) {
				case 'superseeder':
					$value = ($settingsNew[$settingsKey] == 1) ? "True" : "False";
					break;
				case 'torrent_dies_when_done':
					$value = ($settingsNew[$settingsKey] == "True") ? "Die When Done" : "Keep Seeding";
					break;
				default:
					$value = $settingsNew[$settingsKey];
			}
			// list
			array_push($list_changes, array(
				'lbl' => $settingsLabels[$settingsKey],
				'val' => $value
				)
			);
			// send
			if (($ch->running == 1) && ($doSend)) {
				// runtime
				if (in_array($settingsKey, $settingsRuntime))
					array_push($list_send, array(
						'lbl' => $settingsLabels[$settingsKey],
						'val' => $value
						)
					);
				// restart
				else
					array_push($list_restart, array(
						'lbl' => $settingsLabels[$settingsKey],
						'val' => $value
						)
					);
			}
		}
		$tmpl->setloop('list_changes', $list_changes);
		if (empty($list_send))
			$doSend = false;
		else
			$tmpl->setloop('list_send', $list_send);
		if (!empty($list_restart))
			$tmpl->setloop('list_restart', $list_restart);

		// save settings
		$ch->rate = $settingsNew['max_upload_rate'];
		$ch->drate = $settingsNew['max_download_rate'];
		$ch->maxuploads = $settingsNew['max_uploads'];
		$ch->superseeder = $settingsNew['superseeder'];
		$ch->runtime = $settingsNew['torrent_dies_when_done'];
		$ch->sharekill = $settingsNew['sharekill'];
		$ch->minport = $settingsNew['minport'];
		$ch->maxport = $settingsNew['maxport'];
		$ch->maxcons = $settingsNew['maxcons'];
		$ch->settingsSave();

		if ($doSend) { /* send changes */

			// upload-rate
			if ($settingsNew['max_upload_rate'] != $settingsCurrent['max_upload_rate'])
				$ch->setRateUpload($transfer, $settingsNew['max_upload_rate']);

			// upload-rate
			if ($settingsNew['max_download_rate'] != $settingsCurrent['max_download_rate'])
				$ch->setRateDownload($transfer, $settingsNew['max_download_rate']);

			// die-when-done
			if ($settingsNew['torrent_dies_when_done'] != $settingsCurrent['torrent_dies_when_done']) {
				$workload = ($settingsNew['torrent_dies_when_done'] == "True") ? "1" : "0";
				// add command to buffer
	        	CommandHandler::add($transfer, "r".$workload);
			}

			// send command-buffer to client
			CommandHandler::send($transfer);

			// set message-var
			$tmpl->setvar('message', "settings saved + changes sent to client");

		} else { /* dont send changes or no changes to send */

			// set message-var
			$tmpl->setvar('message', "settings saved");

		}

	}

} else { /* display */

	// set save-var
	$tmpl->setvar('isSave', 0);

	// set vars for transfer
	$tmpl->setvar('type', $ch->type);
	$tmpl->setvar('client', $ch->client);
	$tmpl->setvar('hash', $ch->hash);
	$tmpl->setvar('datapath', $ch->datapath);
	$tmpl->setvar('savepath', $ch->savepath);
	$tmpl->setvar('running', $ch->running);
	$tmpl->setvar('max_upload_rate', $ch->rate);
	$tmpl->setvar('max_download_rate', $ch->drate);
	$tmpl->setvar('max_uploads', $ch->maxuploads);
	$tmpl->setvar('superseeder', $ch->superseeder);
	$tmpl->setvar('torrent_dies_when_done', $ch->runtime);
	$tmpl->setvar('sharekill', $ch->sharekill);
	$tmpl->setvar('minport', $ch->minport);
	$tmpl->setvar('maxport', $ch->maxport);
	$tmpl->setvar('maxcons', $ch->maxcons);

	// send-box
	$tmpl->setvar('sendboxShow', ($ch->type == "wget") ? 0 : 1);
	$tmpl->setvar('sendboxAttr', ($ch->running == 1) ? "checked" : "disabled");
}

// title + foot
tmplSetFoot(false);
tmplSetTitleBar($transferLabel." - Settings", false);

// iid
$tmpl->setvar('iid', $_REQUEST["iid"]);

// parse template
$tmpl->pparse();

?>