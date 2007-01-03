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
if (!isset($cfg['user'])) {
	@ob_end_clean();
	@header("location: ../../index.php");
	exit();
}

/******************************************************************************/

// option-lists
$vidcList = array('DIV3', 'DIV4', 'WMV1', 'WMV2', 'RV10', 'mp1v', 'mp4v');
$vbitList = array('192', '256', '384', '512', '768', '1024', '1280', '1536', '1792', '2048');
$audcList = array('mp3', 'mp4a', 'mpga', 'vorb', 'flac');
$abitList = array('64', '96', '128', '192', '256', '384');

// common functions
require_once('inc/functions/functions.common.php');

// dir functions
require_once('inc/functions/functions.dir.php');

// vlc functions
require_once('inc/functions/functions.vlc.php');

// is enabled ?
if ($cfg["enable_vlc"] != 1) {
	AuditAction($cfg["constants"]["error"], "ILLEGAL ACCESS: ".$cfg["user"]." tried to use vlc");
	@error("vlc is disabled", "index.php?iid=index", "");
}

// init template-instance
tmplInitializeInstance($cfg["theme"], "page.vlc.tmpl");

// pageop
//
// * default
// * start
// * stop
//
$pageop = getRequestVar('pageop');
$tmpl->setvar('pageop', (empty($pageop)) ? "default" : $pageop);
// op-switch
switch ($pageop) {
	default:
	case "default":
		// fill lists
		// vidc
		$list_vidc = array();
		foreach ($vidcList as $vidcT)
			array_push($list_vidc, array('name' => $vidcT));
		$tmpl->setloop('list_vidc', $list_vidc);
		// vbit
		$list_vbit = array();
		foreach ($vbitList as $vbitT)
			array_push($list_vbit, array('name' => $vbitT));
		$tmpl->setloop('list_vbit', $list_vbit);
		// audc
		$list_audc = array();
		foreach ($audcList as $audcT)
			array_push($list_audc, array('name' => $audcT));
		$tmpl->setloop('list_audc', $list_audc);
		// abit
		$list_abit = array();
		foreach ($abitList as $abitT)
			array_push($list_abit, array('name' => $abitT));
		$tmpl->setloop('list_abit', $list_abit);
		// requested file
		$dirName = urldecode($_REQUEST['dir']);
		$fileName = urldecode(stripslashes($_REQUEST['file']));
		$tmpl->setvar('file', $fileName);
		$tmpl->setvar('target', urlencode(addslashes($dirName.$fileName)));
		// host vars
		$tmpl->setvar('host', $_SERVER['SERVER_ADDR']);
		$tmpl->setvar('port', $cfg['vlc_port']);
		// already streaming
		if (vlcIsRunning("127.0.0.1", $cfg['vlc_port']) === true) {
			$tmpl->setvar('is_streaming', 1);
			$tmpl->setvar('current_stream', vlcGetRunningCurrent());
		} else {
			$tmpl->setvar('is_streaming', 0);
		}
		break;
	case "start":
		// get vars
		$fileName = urldecode(stripslashes($_REQUEST['file']));
		$targetFile = urldecode(stripslashes($_POST['target']));
		$target_vidc = $_POST['vidc'];
		$target_vbit = $_POST['vbit'];
		$target_audc = $_POST['audc'];
		$target_abit = $_POST['abit'];
		// set template vars
		$tmpl->setvar('file', $fileName);
		$tmpl->setvar('vidc', $target_vidc);
		$tmpl->setvar('vbit', $target_vbit);
		$tmpl->setvar('audc', $target_audc);
		$tmpl->setvar('abit', $target_abit);
		$tmpl->setvar('host', $_SERVER['SERVER_NAME']);
		$tmpl->setvar('port', $cfg['vlc_port']);
		// start vlc
		@vlcStart($_SERVER['SERVER_ADDR'], $cfg['vlc_port'], $cfg["path"].$targetFile, $target_vidc, $target_vbit, $target_audc, $target_abit);
		break;
	case "stop":
		// stop vlc
		@vlcStop();
		break;
}

//
tmplSetTitleBar($cfg["pagetitle"]." - "."vlc", false);
$tmpl->setvar('getTorrentFluxLink', getTorrentFluxLink());
//
$tmpl->setvar('iid', $_REQUEST["iid"]);

// parse template
$tmpl->pparse();

?>