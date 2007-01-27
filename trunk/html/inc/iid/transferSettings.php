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

// transfer functions
require_once('inc/functions/functions.transfer.php');

// init template-instance
tmplInitializeInstance($cfg["theme"], "page.transferSettings.tmpl");

// init transfer
transfer_init();

// request-vars
$isSave = (isset($_REQUEST['save'])) ? true : false;

// init ch-instance
$ch = ClientHandler::getInstance(getTransferClient($transfer));

// customize-vars
transfer_setCustomizeVars();

// load settings, default if settings could not be loaded (fresh transfer)
if ($ch->settingsLoad($transfer) !== true)
	$ch->settingsDefault();

// set running-field
$ch->running = isTransferRunning($transfer) ? 1 : 0;

// save/display
if ($isSave) {                                                        /* save */

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
		'die_when_done',
		'sharekill',
		'minport',
		'maxport',
		'maxcons',
		'rerequest'
	);

	// runtime-settings
	$settingsRuntime = array(
		'max_upload_rate',
		'max_download_rate',
		'die_when_done',
		'sharekill'
	);

	// settings-labels
	$settingsLabels = array(
		'max_upload_rate' => 'Max Upload Rate',
		'max_download_rate' => 'Max Download Rate',
		'max_uploads' => 'Max Upload Connections',
		'superseeder' => 'Superseeder',
		'die_when_done' => 'Torrent Completion Activity',
		'sharekill' => 'Percentage When Seeding should Stop',
		'minport' => 'Min-Port',
		'maxport' => 'Max-Port',
		'maxcons' => 'Max Cons',
		'rerequest' => 'Rerequest Intervall'
	);

	// current settings
	$settingsCurrent = array();
	$settingsCurrent['max_upload_rate'] = $ch->rate;
	$settingsCurrent['max_download_rate'] = $ch->drate;
	$settingsCurrent['max_uploads'] = $ch->maxuploads;
	$settingsCurrent['superseeder'] = $ch->superseeder;
	$settingsCurrent['die_when_done'] = $ch->runtime;
	$settingsCurrent['sharekill'] = $ch->sharekill;
	$settingsCurrent['minport'] = $ch->minport;
	$settingsCurrent['maxport'] = $ch->maxport;
	$settingsCurrent['maxcons'] = $ch->maxcons;
	$settingsCurrent['rerequest'] = $ch->rerequest;

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
				case 'die_when_done':
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
		$ch->runtime = $settingsNew['die_when_done'];
		$ch->sharekill = $settingsNew['sharekill'];
		$ch->minport = $settingsNew['minport'];
		$ch->maxport = $settingsNew['maxport'];
		$ch->maxcons = $settingsNew['maxcons'];
		$ch->rerequest = $settingsNew['rerequest'];
		$ch->settingsSave();

		if ($doSend) { /* send changes */

			// upload-rate
			if ($settingsNew['max_upload_rate'] != $settingsCurrent['max_upload_rate'])
				$ch->setRateUpload($transfer, $settingsNew['max_upload_rate']);

			// upload-rate
			if ($settingsNew['max_download_rate'] != $settingsCurrent['max_download_rate'])
				$ch->setRateDownload($transfer, $settingsNew['max_download_rate']);

			// runtime
			if ($settingsNew['die_when_done'] != $settingsCurrent['die_when_done'])
				$ch->setRuntime($transfer, $settingsNew['die_when_done']);

			// sharekill
			if ($settingsNew['sharekill'] != $settingsCurrent['sharekill'])
				$ch->setSharekill($transfer, $settingsNew['sharekill']);

			// send command-buffer to client
			CommandHandler::send($transfer);

			// set message-var
			$tmpl->setvar('message', "settings saved + changes sent to client");

		} else { /* dont send changes or no changes to send */

			// set message-var
			$tmpl->setvar('message', "settings saved");

		}

	}

} else {                                                           /* display */

	// set save-var
	$tmpl->setvar('isSave', 0);

	// set vars for transfer from ch
	transfer_setVarsFromCHSettings();

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