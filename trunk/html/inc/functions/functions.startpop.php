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
 * setVarsFromPersistentSettings
 */
function setVarsFromPersistentSettings() {
	global $cfg, $tmpl, $transfer, $transfers;
	// Load saved settings
	loadTransferSettingsToConfig($transfer);
	// set settings
	$tmpl->setvar('max_upload_rate', $cfg["max_upload_rate"]);
	$tmpl->setvar('max_uploads', $cfg["max_uploads"]);
	$tmpl->setvar('max_download_rate', $cfg["max_download_rate"]);
	$tmpl->setvar('maxcons', $cfg["maxcons"]);
	$tmpl->setvar('rerequest_interval', $cfg["rerequest_interval"]);
	$tmpl->setvar('minport', $cfg["minport"]);
	$tmpl->setvar('maxport', $cfg["maxport"]);
	$tmpl->setvar('sharekill', $cfg["sharekill"]);
	$tmpl->setvar('selected', ($cfg["torrent_dies_when_done"] == "False") ? "selected" : "");
	// btclient-chooser
	if ($cfg["enable_btclient_chooser"] != 0)
		tmplSetClientSelectForm($cfg["btclient"]);
	else
		$tmpl->setvar('btclientDefault', $cfg["btclient"]);
	// savepath
	if ((! isset($cfg["savepath"])) || (empty($cfg["savepath"]))) {
		$cfg["savepath"] = ($cfg["enable_home_dirs"] != 0)
			? $cfg["path"].getOwner($transfer).'/'
			: $cfg["path"].$cfg["path_incoming"].'/';
	}
	$tmpl->setvar('savepath', $cfg["savepath"]);
}

?>