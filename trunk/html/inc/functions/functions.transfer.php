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
		'supports_max_upload_rate' => 1,
		'supports_max_download_rate' => 1,
		'supports_max_uploads' => 1,
		'supports_superseeder' => 1,
		'supports_die_when_done' => 1,
		'supports_sharekill' => 1,
		'supports_minport' => 1,
		'supports_maxport' => 1,
		'supports_maxcons' => 1,
		'supports_rerequest_interval' => 1,
		'supports_file_priority' => 1
	),
	'transmission' => array(
		'supports_max_upload_rate' => 1,
		'supports_max_download_rate' => 1,
		'supports_max_uploads' => 0,
		'supports_superseeder' => 0,
		'supports_die_when_done' => 1,
		'supports_sharekill' => 1,
		'supports_minport' => 1,
		'supports_maxport' => 1,
		'supports_maxcons' => 0,
		'supports_rerequest_interval' => 0,
		'supports_file_priority' => 0
	),
	'mainline' => array(
		'supports_max_upload_rate' => 1,
		'supports_max_download_rate' => 1,
		'supports_max_uploads' => 1,
		'supports_superseeder' => 0,
		'supports_die_when_done' => 1,
		'supports_sharekill' => 1,
		'supports_minport' => 1,
		'supports_maxport' => 1,
		'supports_maxcons' => 1,
		'supports_rerequest_interval' => 1,
		'supports_file_priority' => 0
	),
	'wget' => array(
		'supports_max_upload_rate' => 0,
		'supports_max_download_rate' => 1,
		'supports_max_uploads' => 0,
		'supports_superseeder' => 0,
		'supports_die_when_done' => 0,
		'supports_sharekill' => 0,
		'supports_minport' => 0,
		'supports_maxport' => 0,
		'supports_maxcons' => 0,
		'supports_rerequest_interval' => 0,
		'supports_file_priority' => 0
	),
	'nzbperl' => array(
		'supports_max_upload_rate' => 0,
		'supports_max_download_rate' => 1,
		'supports_max_uploads' => 0,
		'supports_superseeder' => 0,
		'supports_die_when_done' => 0,
		'supports_sharekill' => 0,
		'supports_minport' => 0,
		'supports_maxport' => 0,
		'supports_maxcons' => 1,
		'supports_rerequest_interval' => 0,
		'supports_file_priority' => 0
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
 * setSupportsVars
 */
function transfer_setSupportsVars() {
	global $cfg, $tmpl, $transfer, $transferLabel, $ch, $supportMap;
	// set vars for transfer
	$tmpl->setvar('supports_max_upload_rate', $supportMap[$ch->client]['supports_max_upload_rate']);
	$tmpl->setvar('supports_max_download_rate', $supportMap[$ch->client]['supports_max_download_rate']);
	$tmpl->setvar('supports_max_uploads', $supportMap[$ch->client]['supports_max_uploads']);
	$tmpl->setvar('supports_superseeder', $supportMap[$ch->client]['supports_superseeder']);
	$tmpl->setvar('supports_die_when_done', $supportMap[$ch->client]['supports_die_when_done']);
	$tmpl->setvar('supports_sharekill', $supportMap[$ch->client]['supports_sharekill']);
	$tmpl->setvar('supports_minport', $supportMap[$ch->client]['supports_minport']);
	$tmpl->setvar('supports_maxport', $supportMap[$ch->client]['supports_maxport']);
	$tmpl->setvar('supports_maxcons', $supportMap[$ch->client]['supports_maxcons']);
	// rerequest
	$tmpl->setvar('supports_rerequest_interval', $supportMap[$ch->client]['supports_rerequest_interval']);
	// rerequest
	$tmpl->setvar('supports_file_priority', $supportMap[$ch->client]['supports_file_priority']);
}

/**
 * setVars
 *
 * @param $with_profiles
 */
function transfer_setVars($with_profiles = 0) {
	global $cfg, $tmpl, $transfer, $transferLabel, $ch, $supportMap;
	if ($with_profiles == 0) {
		// set from profile
		transfer_setVarsFromProfileSettings();
	} else {
		// set from ch
		transfer_setVarsFromCHSettings();
		// rerequest
		$tmpl->setvar('rerequest_interval', $cfg["rerequest_interval"]);
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
	// rerequest
	$tmpl->setvar('rerequest_interval', $settings["rerequest"]);
}

?>