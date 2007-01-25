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
	$settings = loadTransferSettings($transfer);
	if (!is_array($settings)) {
		$settings = array();
		$settings["type"]                    = "torrent";
		$settings["client"]                  = $cfg["btclient"];
		$settings["hash"]                    = "";
		$settings["datapath"]                = "";
		$settings["savepath"]                = ($cfg["enable_home_dirs"] != 0)
			? $cfg["path"].getOwner($transfer).'/'
			: $cfg["path"].$cfg["path_incoming"].'/';
		$settings["running"]                 = "0";
		$settings["max_upload_rate"]         = $cfg["max_upload_rate"];
		$settings["max_download_rate"]		 = $cfg["max_download_rate"];
		$settings["die_when_done"]	         = $cfg["die_when_done"];
		$settings["max_uploads"]			 = $cfg["max_uploads"];
		$settings["superseeder"]			 = $cfg["superseeder"];
		$settings["minport"]				 = $cfg["minport"];
		$settings["maxport"]				 = $cfg["maxport"];
		$settings["sharekill"]				 = $cfg["sharekill"];
		$settings["maxcons"]				 = $cfg["maxcons"];
	}
	// set settings
	$tmpl->setvar('max_upload_rate', $settings["max_upload_rate"]);
	$tmpl->setvar('max_uploads', $settings["max_uploads"]);
	$tmpl->setvar('max_download_rate', $settings["max_download_rate"]);
	$tmpl->setvar('maxcons', $settings["maxcons"]);
	$tmpl->setvar('rerequest_interval', $cfg["rerequest_interval"]);
	$tmpl->setvar('superseeder', ($settings['superseeder'] == 1) ? "checked" : "");
	$tmpl->setvar('superseederValue', $settings['superseeder']);
	$tmpl->setvar('minport', $settings["minport"]);
	$tmpl->setvar('maxport', $settings["maxport"]);
	$tmpl->setvar('sharekill', $settings["sharekill"]);
	$tmpl->setvar('selected', ($settings["die_when_done"] == "False") ? "selected" : "");
	$tmpl->setvar('savepath', $settings["savepath"]);
	// btclient-chooser
	if ($cfg["enable_btclient_chooser"] != 0)
		tmplSetClientSelectForm($settings["client"]);
	else
		$tmpl->setvar('btclientDefault', $settings["client"]);

}

?>