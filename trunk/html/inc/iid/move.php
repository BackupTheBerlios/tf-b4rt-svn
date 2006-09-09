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

// common functions
require_once('inc/functions/functions.common.php');

// config
loadSettings('tf_settings_dir');

// create template-instance
$tmpl = getTemplateInstance($cfg["theme"], "move.tmpl");

$tmpl->setvar('head', getHead($cfg['_MOVE_FILE_TITLE'], false));

if((isset($_GET['start'])) && ($_GET['start'] == true)) {
	$tmpl->setvar('is_start', 1);
	$tmpl->setvar('_MOVE_FILE', $cfg['_MOVE_FILE']);
	$tmpl->setvar('path', $_GET['path']);
	$tmpl->setvar('_MOVE_STRING', $cfg['_MOVE_STRING']);
	if ((isset($cfg["move_paths"])) && (strlen($cfg["move_paths"]) > 0)) {
		$tmpl->setvar('move_start', 1);
		$dirs = split(":", trim($cfg["move_paths"]));
		$dir_list = array();
		foreach ($dirs as $dir) {
			$target = trim($dir);
			if ((strlen($target) > 0) && ((substr($target, 0, 1)) != ";")) {
				array_push($dir_list, array(
					'target' => $target,
					)
				);
			}
		}
		$tmpl->setloop('dir_list', $dir_list);
	}
} else {
	$targetDir = "";
	if (isset($_POST['dest'])) {
		 $tempDir = trim(urldecode($_POST['dest']));
		 if (strlen($tempDir) > 0)
			$targetDir = $tempDir;
	}
	if (($targetDir == "") && (isset($_POST['selector'])))
		 $targetDir = trim(urldecode($_POST['selector']));
	$dirValid = true;
	if (strlen($targetDir) <= 0) {
		 $dirValid = false;
	} else {
		// we need absolute paths or stuff will end up in docroot
		// inform user .. dont move it into a fallback-dir which may be a hastle
		if ($targetDir{0} != '/') {
			$tmpl->setvar('not_absolute', 1);
			$dirValid = false;
		}
	}
	$tmpl->setvar('targetDir', $targetDir);
	// check dir
	if (($dirValid) && (checkDirectory($targetDir,0777))) {
		$tmpl->setvar('is_valid', 1);
		 $targetDir = checkDirPathString($targetDir);
		// move
		$cmd = "mv \"".$cfg["path"].$_POST['file']."\" \"".$targetDir."\"";
		$cmd .= ' 2>&1';
		$handle = popen($cmd, 'r' );
		// get the output and print it.
		$gotError = -1;
		$buff= "";
		while(!feof($handle)) {
			$buff .= fgets($handle,30);
			$gotError = $gotError + 1;
		}
		$tmpl->setvar('buff', nl2br($buff));
		pclose($handle);
		if($gotError <= 0) {
			$tmpl->setvar('got_no_error', 1);
			$tmpl->setvar('file', $_POST['file']);
		}
	}
}
// $tmpl->setloop('dir_list', $dir_list);
$tmpl->setvar('getTorrentFluxLink', getTorrentFluxLink());
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('iid', $_GET["iid"]);
$tmpl->pparse();

?>