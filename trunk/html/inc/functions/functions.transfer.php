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
 * client-support-map
 */
$supportMap = array(
	'tornado' => array(
		'max_upload_rate'   => 1,
		'max_download_rate' => 1,
		'max_uploads'       => 1,
		'superseeder'       => 1,
		'die_when_done'     => 1,
		'sharekill'         => 1,
		'minport'           => 1,
		'maxport'           => 1,
		'maxcons'           => 1,
		'rerequest'         => 1,
		'file_priority'     => 1,
		'skip_hash_check'   => 1
	),
	'transmission' => array(
		'max_upload_rate'   => 1,
		'max_download_rate' => 1,
		'max_uploads'       => 0,
		'superseeder'       => 0,
		'die_when_done'     => 1,
		'sharekill'         => 1,
		'minport'           => 1,
		'maxport'           => 1,
		'maxcons'           => 0,
		'rerequest'         => 0,
		'file_priority'     => 0,
		'skip_hash_check'   => 0
	),
	'mainline' => array(
		'max_upload_rate'   => 1,
		'max_download_rate' => 1,
		'max_uploads'       => 1,
		'superseeder'       => 0,
		'die_when_done'     => 1,
		'sharekill'         => 1,
		'minport'           => 1,
		'maxport'           => 1,
		'maxcons'           => 1,
		'rerequest'         => 1,
		'file_priority'     => 0,
		'skip_hash_check'   => 1
	),
	'wget' => array(
		'max_upload_rate'   => 0,
		'max_download_rate' => 1,
		'max_uploads'       => 0,
		'superseeder'       => 0,
		'die_when_done'     => 0,
		'sharekill'         => 0,
		'minport'           => 0,
		'maxport'           => 0,
		'maxcons'           => 0,
		'rerequest'         => 0,
		'file_priority'     => 0,
		'skip_hash_check'   => 0
	),
	'nzbperl' => array(
		'max_upload_rate'   => 0,
		'max_download_rate' => 1,
		'max_uploads'       => 0,
		'superseeder'       => 0,
		'die_when_done'     => 0,
		'sharekill'         => 0,
		'minport'           => 0,
		'maxport'           => 0,
		'maxcons'           => 1,
		'rerequest'         => 0,
		'file_priority'     => 0,
		'skip_hash_check'   => 0
	)
);

/**
 * init
 */
function transfer_init() {
	global $cfg, $tmpl, $transfer, $transferLabel, $ch, $supportMap;
	// request-var
	$transfer = getRequestVar('transfer');
	if (empty($transfer))
		@error("missing params", "", "", array('transfer'));
	// validate transfer
	if (isValidTransfer($transfer) !== true) {
		AuditAction($cfg["constants"]["error"], "INVALID TRANSFER: ".$transfer);
		@error("Invalid Transfer", "", "", array($transfer));
	}
	// get label
	$transferLabel = (strlen($transfer) >= 39) ? substr($transfer, 0, 35)."..." : $transfer;
	// set transfer vars
	$tmpl->setvar('transfer', $transfer);
	$tmpl->setvar('transferLabel', $transferLabel);
}

/**
 * setCustomizeVars
 */
function transfer_setCustomizeVars() {
	global $cfg, $tmpl, $transfer, $transferLabel, $ch, $supportMap;
	// customize settings
	if ($cfg['transfer_customize_settings'] == "2")
		$customize_settings = 1;
	elseif ($cfg['transfer_customize_settings'] == "1" && $cfg['isAdmin'])
		$customize_settings = 1;
	else
		$customize_settings = 0;
	$tmpl->setvar('customize_settings', $customize_settings);
	// set supported-vars for transfer
	if ($customize_settings == 0) {
		$tmpl->setvar('max_upload_rate_enabled', 0);
		$tmpl->setvar('max_download_rate_enabled', 0);
		$tmpl->setvar('max_uploads_enabled', 0);
		$tmpl->setvar('superseeder_enabled', 0);
		$tmpl->setvar('die_when_done_enabled', 0);
		$tmpl->setvar('sharekill_enabled', 0);
		$tmpl->setvar('minport_enabled', 0);
		$tmpl->setvar('maxport_enabled', 0);
		$tmpl->setvar('maxcons_enabled', 0);
		$tmpl->setvar('rerequest_enabled', 0);
	} else {
		$tmpl->setvar('max_upload_rate_enabled', $supportMap[$ch->client]['max_upload_rate']);
		$tmpl->setvar('max_download_rate_enabled', $supportMap[$ch->client]['max_download_rate']);
		$tmpl->setvar('max_uploads_enabled', $supportMap[$ch->client]['max_uploads']);
		$tmpl->setvar('superseeder_enabled', $supportMap[$ch->client]['superseeder']);
		$tmpl->setvar('die_when_done_enabled', $supportMap[$ch->client]['die_when_done']);
		$tmpl->setvar('sharekill_enabled', $supportMap[$ch->client]['sharekill']);
		$tmpl->setvar('minport_enabled', $supportMap[$ch->client]['minport']);
		$tmpl->setvar('maxport_enabled', $supportMap[$ch->client]['maxport']);
		$tmpl->setvar('maxcons_enabled', $supportMap[$ch->client]['maxcons']);
		$tmpl->setvar('rerequest_enabled', $supportMap[$ch->client]['rerequest']);
	}
}

/**
 * setGenericVarsFromCH
 */
function transfer_setGenericVarsFromCH() {
	global $cfg, $tmpl, $transfer, $transferLabel, $ch, $supportMap;
	// set generic vars for transfer
	$tmpl->setvar('type', $ch->type);
	$tmpl->setvar('client', $ch->client);
	$tmpl->setvar('hash', $ch->hash);
	$tmpl->setvar('datapath', $ch->datapath);
	$tmpl->setvar('savepath', $ch->savepath);
	$tmpl->setvar('running', $ch->running);
}

/**
 * setVarsFromCHSettings
 */
function transfer_setVarsFromCHSettings() {
	global $cfg, $tmpl, $transfer, $transferLabel, $ch, $supportMap;
	// set generic vars for transfer
	transfer_setGenericVarsFromCH();
	// set vars for transfer
	$tmpl->setvar('max_upload_rate', $ch->rate);
	$tmpl->setvar('max_download_rate', $ch->drate);
	$tmpl->setvar('max_uploads', $ch->maxuploads);
	$tmpl->setvar('superseeder', $ch->superseeder);
	$tmpl->setvar('die_when_done', $ch->runtime);
	$tmpl->setvar('sharekill', $ch->sharekill);
	$tmpl->setvar('minport', $ch->minport);
	$tmpl->setvar('maxport', $ch->maxport);
	$tmpl->setvar('maxcons', $ch->maxcons);
	$tmpl->setvar('rerequest', $ch->rerequest);
}

/**
 * setVarsFromProfileSettings
 */
function transfer_setVarsFromProfileSettings() {
	global $cfg, $tmpl, $transfer, $transferLabel, $ch, $supportMap;
	//load custom settings
	$settings = GetProfileSettings($profile);
	// set vars for transfer
	$tmpl->setvar('max_upload_rate', $settings["rate"]);
	$tmpl->setvar('max_download_rate', $settings["drate"]);
	$tmpl->setvar('max_uploads', $settings["maxuploads"]);
	$tmpl->setvar('superseeder', $settings['superseeder']);
	$tmpl->setvar('die_when_done', $settings["runtime"]);
	$tmpl->setvar('sharekill', $settings["sharekill"]);
	$tmpl->setvar('minport', $settings["minport"]);
	$tmpl->setvar('maxport', $settings["maxport"]);
	$tmpl->setvar('maxcons', $settings["maxcons"]);
	$tmpl->setvar('rerequest', $settings["rerequest"]);
}

?>