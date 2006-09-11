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
 *
 */
function setVarsFromPersistentSettings() {
	global $cfg, $tmpl, $torrent, $queueActive;
	// Load saved settings
	loadTorrentSettingsToConfig($torrent);
	// set settings
	$tmpl->setvar('max_upload_rate', $cfg["max_upload_rate"]);
	$tmpl->setvar('max_uploads', $cfg["max_uploads"]);
	$tmpl->setvar('max_download_rate', $cfg["max_download_rate"]);
	$tmpl->setvar('maxcons', $cfg["maxcons"]);
	$tmpl->setvar('rerequest_interval', $cfg["rerequest_interval"]);
	// btclient-chooser
	if ($cfg["enable_btclient_chooser"] != 0)
		$tmpl->setvar('btClientSelect', getBTClientSelect($cfg["btclient"]));
	else
		$tmpl->setvar('btclientDefault', $cfg["btclient"]);
	// more vars
	$selected = "";
	if ($cfg["torrent_dies_when_done"] == "False") {
		$selected = "selected";
	}
	$tmpl->setvar('selected', $selected);
	$tmpl->setvar('minport', $cfg["minport"]);
	$tmpl->setvar('maxport', $cfg["maxport"]);
	$tmpl->setvar('sharekill', $cfg["sharekill"]);
	// savepath
	if ((! isset($cfg["savepath"])) || (empty($cfg["savepath"]))) {
		switch ($cfg["enable_home_dirs"]) {
		    case 1:
		    default:
				$cfg["savepath"] = $cfg["path"].getOwner($torrent).'/';
				break;
		    case 0:
		    	$cfg["savepath"] = $cfg["path"].$cfg["path_incoming"].'/';
		    	break;
		}
	}
	$tmpl->setvar('savepath', $cfg["savepath"]);
	// Force Queuing if not an admin.
	if($queueActive)
		$tmpl->setvar('is_queue', 1);
	else
		$tmpl->setvar('is_queue', 0);
	// admin
	if (IsAdmin())
		$tmpl->setvar('is_admin', 1);
}

?>