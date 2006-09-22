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

// main.internal
require_once("inc/main.internal.php");

// common functions
require_once('inc/functions/functions.common.php');

// admin functions
require_once('inc/functions/functions.admin.php');

// access-check
if ((!isset($cfg['isAdmin'])) || (!$cfg['isAdmin'])) {
	 // the user probably hit this page direct
	AuditAction($cfg["constants"]["access_denied"], $_SERVER['PHP_SELF']);
	header("location: index.php?iid=index");
}

// op-switch
if (isset($_REQUEST['op']))
	$op = $_REQUEST['op'];
else
	$op = "default";
switch ($op) {

	case "updateServerSettings":
		$settings = processSettingsParams(false,false);
		saveSettings('tf_settings', $settings);
		AuditAction($cfg["constants"]["admin"], " Updating Server Settings");
		$continue = getRequestVar('continue');
		header("location: admin.php?op=serverSettings");
		exit();

	case "updateTransferSettings":
		$settings = processSettingsParams(false,false);
		saveSettings('tf_settings', $settings);
		AuditAction($cfg["constants"]["admin"], " Updating Transfer Settings");
		$continue = getRequestVar('continue');
		header("location: admin.php?op=transferSettings");
		exit();

	case "updateWebappSettings":
		$settings = processSettingsParams(false,false);
		saveSettings('tf_settings', $settings);
		AuditAction($cfg["constants"]["admin"], " Updating WebApp Settings");
		$continue = getRequestVar('continue');
		header("location: admin.php?op=webappSettings");
		exit();

	case "updateIndexSettings":
		$settings = processSettingsParams(true,true);
		saveSettings('tf_settings', $settings);
		AuditAction($cfg["constants"]["admin"], " Updating Index Settings");
		header("location: admin.php?op=indexSettings");
		exit();

	case "updateStartpopSettings":
		$settings = processSettingsParams(false,false);
		saveSettings('tf_settings', $settings);
		AuditAction($cfg["constants"]["admin"], " Updating StartPop Settings");
		header("location: admin.php?op=startpopSettings");
		exit();

	case "updateDirSettings":
		$settings = processSettingsParams(false,false);
		loadSettings('tf_settings_dir');
		saveSettings('tf_settings_dir', $settings);
		AuditAction($cfg["constants"]["admin"], " Updating Dir Settings");
		header("location: admin.php?op=dirSettings");
		exit();

	case "updateStatsSettings":
		$settings = processSettingsParams(false,false);
		saveSettings('tf_settings_stats', $settings);
		AuditAction($cfg["constants"]["admin"], " Updating Stats Settings");
		header("location: admin.php?op=statsSettings");
		exit();

	case "updateXferSettings":
		$settings = processSettingsParams(false,false);
		saveSettings('tf_settings', $settings);
		AuditAction($cfg["constants"]["admin"], " Updating Xfer Settings");
		header("location: admin.php?op=xferSettings");
		exit();

	case "updateFluxdSettings":
		if ($_POST["fluxd_loglevel"] != $cfg["fluxd_loglevel"] ||
			$_POST["fluxd_Qmgr_enabled"] != $cfg["fluxd_Qmgr_enabled"] ||
			$_POST["fluxd_Fluxinet_enabled"] != $cfg["fluxd_Fluxinet_enabled"] ||
			$_POST["fluxd_Clientmaint_enabled"] != $cfg["fluxd_Clientmaint_enabled"] ||
			$_POST["fluxd_Trigger_enabled"] != $cfg["fluxd_Trigger_enabled"] ||
			$_POST["fluxd_Watch_enabled"] != $cfg["fluxd_Watch_enabled"] ||
			$_POST["fluxd_Qmgr_maxUserTorrents"] != $cfg["fluxd_Qmgr_maxUserTorrents"] ||
			$_POST["fluxd_Qmgr_maxTotalTorrents"] != $cfg["fluxd_Qmgr_maxTotalTorrents"] ||
			$_POST["fluxd_Fluxinet_port"] != $cfg["fluxd_Fluxinet_port"] ||
			$_POST["fluxd_Watch_jobs"] != $cfg["fluxd_Watch_jobs"] ||
			$_POST["fluxd_Clientmaint_interval"] != $cfg["fluxd_Clientmaint_interval"])
		{
			$message = '<br>Settings changed.<br>';
			// fluxd Running?
			if ($fluxdRunning) {
				$needsRestart = false;
				$reloadModules = false;
				$needsInit = false;
				if ($_POST["fluxd_Qmgr_enabled"] != $cfg["fluxd_Qmgr_enabled"] ||
					$_POST["fluxd_Fluxinet_enabled"] != $cfg["fluxd_Fluxinet_enabled"] ||
					$_POST["fluxd_Clientmaint_enabled"] != $cfg["fluxd_Clientmaint_enabled"] ||
					$_POST["fluxd_Trigger_enabled"] != $cfg["fluxd_Trigger_enabled"] ||
					$_POST["fluxd_Watch_enabled"] != $cfg["fluxd_Watch_enabled"] ||
					$_POST["fluxd_Qmgr_maxTotalTorrents"] != $cfg["fluxd_Qmgr_maxTotalTorrents"] ||
					$_POST["fluxd_Qmgr_maxUserTorrents"] != $cfg["fluxd_Qmgr_maxUserTorrents"]
					) {
					$reloadModules = true;
				}
				if ($needsRestart) {
					$needsHUP = false;
					$message .= 'You have to restart fluxd to use the new settings.<br><br>';
				}
				// reconfig of running daemon :
				if ($_POST["fluxd_loglevel"] != $cfg["fluxd_loglevel"]) {
					$fluxd->setConfig('LOGLEVEL',$_POST["fluxd_loglevel"]);
					sleep(1);
				}
				// save settings
				$settings = $_POST;
				saveSettings('tf_settings', $settings);
				// reload fluxd-database-cache
				$fluxd->reloadDBCache();
				// reload fluxd-modules
				if ($reloadModules) {
					sleep(1);
					$fluxd->reloadModules();
				}
			} else {
				// save settings
				$settings = $_POST;
				saveSettings('tf_settings', $settings);
				$message .= 'fluxd is not currently running.<br><br>';
			}
			// log
			AuditAction($cfg["constants"]["admin"], " Updating fluxd Settings");
			// redir
			header("Location: admin.php?op=fluxdSettings&m=".urlencode($message));
		} else {
			// save settings
			$settings = $_POST;
			saveSettings('tf_settings', $settings);
			// log
			AuditAction($cfg["constants"]["admin"], " Updating fluxd Settings");
			// redir
			header("Location: admin.php?op=fluxdSettings");
		}
		exit();

	case "controlFluxd":
		$message = "";
		$action = getRequestVar('a');
		switch($action) {
			case "start":
				// start fluxd
				if ($fluxd->isFluxdReadyToStart()) {
					$fluxd->startFluxd();
					if ($fluxd->state == 2) {
						$message = '<br><strong>fluxd started.</strong><br><br>';
					} else {
						$message = '<br><font color="red">Error starting fluxd</font><br>';
						$message .= 'Error : '.$fluxd->messages . '<br>';
					}
					break;
				}
				$message = '<br><font color="red">Error starting fluxd</font><br><br>';
				break;
			case "stop":
				// kill fluxd
				if ($fluxd->isFluxdRunning()) {
					$fluxd->stopFluxd();
					if ($fluxd->isFluxdRunning())
						$message = '<br><strong>Stop-Command sent.</strong><br><br>';
					else
						$message = '<br><strong>fluxd stopped.</strong><br><br>';
					header("Location: admin.php?op=fluxdSettings&m=".urlencode($message).'&s=1');
					exit;
				}
				break;
			default:
				$message = '<br><font color="red">Error : no control-operation.</font><br><br>';
				break;
		}
		if ($message != "")
			header("Location: admin.php?op=fluxdSettings&m=".urlencode($message));
		else
			header("Location: admin.php?op=fluxdSettings");
		exit();

	case "updateSearchSettings":
		foreach ($_POST as $key => $value) {
			if ($key != "searchEngine")
				$settings[$key] = $value;
		}
		saveSettings('tf_settings', $settings);
		AuditAction($cfg["constants"]["admin"], " Updating Search Settings");
		$searchEngine = getRequestVar('searchEngine');
		if (empty($searchEngine))
			$searchEngine = $cfg["searchEngine"];
		header("location: admin.php?op=searchSettings&searchEngine=".$searchEngine);
		exit();

	case "addLink":
		$newLink = getRequestVar('newLink');
		$newSite = getRequestVar('newSite');
		if (!empty($newLink)){
			if (strpos($newLink, "http://" ) !== 0 && strpos($newLink, "https://" ) !== 0 && strpos($newLink, "ftp://" ) !== 0)
				$newLink = "http://".$newLink;
			empty($newSite) && $newSite = $newLink;
			addNewLink($newLink, $newSite);
			AuditAction($cfg["constants"]["admin"], "New ".$cfg['_LINKS_MENU'].": ".$newSite." [".$newLink."]");
		}
		header("location: admin.php?op=editLinks");
		exit();

	case "editLink":
		$lid = getRequestVar('lid');
		$editLink = getRequestVar('editLink');
		$editSite = getRequestVar('editSite');
		if (!empty($newLink)){
			if(strpos($newLink, "http://" ) !== 0 && strpos($newLink, "https://" ) !== 0 && strpos($newLink, "ftp://" ) !== 0)
				$newLink = "http://".$newLink;
			empty($newSite) && $newSite = $newLink;
			$oldLink=getLink($lid);
			$oldSite=getSite($lid);
			alterLink($lid,$newLink,$newSite);
			AuditAction($cfg["constants"]["admin"], "Change Link: ".$oldSite." [".$oldLink."] -> ".$newSite." [".$newLink."]");
		}
		header("location: admin.php?op=editLinks");
		exit();

	case "moveLink":
		$lid = getRequestVar('lid');
		$direction = getRequestVar('direction');
		if (!isset($lid) && !isset($direction) && $direction !== "up" && $direction !== "down") {
			header("location: admin.php?op=editLinks");
			exit();
		}
		$idx=getLinkSortOrder($lid);
		$position = array("up"=>-1, "down"=>1);
		$new_idx = $idx + $position[$direction];
		$sql = "UPDATE tf_links SET sort_order = $idx WHERE sort_order = $new_idx";
		$db->Execute($sql);
		showError($db, $sql);
		$sql = "UPDATE tf_links SET sort_order = $new_idx WHERE lid = $lid";
		$db->Execute($sql);
		showError($db, $sql);
		header("Location: admin.php?op=editLinks");
		exit();

	case "deleteLink":
		$lid = getRequestVar('lid');
		AuditAction($cfg["constants"]["admin"], $cfg['_DELETE']." Link: ".getSite($lid)." [".getLink($lid)."]");
		deleteOldLink($lid);
		header("location: admin.php?op=editLinks");
		exit();

	case "addRSS":
		$newRSS = getRequestVar('newRSS');
		if(!empty($newRSS)){
			addNewRSS($newRSS);
			AuditAction($cfg["constants"]["admin"], "New RSS: ".$newRSS);
		}
		header("location: admin.php?op=editRSS");
		exit();

	case "deleteRSS":
		$rid = getRequestVar('rid');
		AuditAction($cfg["constants"]["admin"], $cfg['_DELETE']." RSS: ".getRSS($rid));
		deleteOldRSS($rid);
		header("location: admin.php?op=editRSS");
		exit();

	case "deleteUser":
		$user_id = getRequestVar('user_id');
		if (!IsSuperAdmin($user_id)) {
			DeleteThisUser($user_id);
			AuditAction($cfg["constants"]["admin"], $cfg['_DELETE']." ".$cfg['_USER'].": ".$user_id);
		}
		header("location: admin.php");
		exit();

	case "setUserState":
		setUserState();
		header("location: admin.php?op=showUsers");
		exit();

	default:
		require_once("inc/iid/admin/".$op.".php");
		exit();
}

?>