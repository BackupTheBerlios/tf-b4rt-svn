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

// default-type
define('_DEFAULT_TYPE', 'mrtg');

// default-target
define('_DEFAULT_TARGET', 'traffic');

// input-dir mrtg
define('_MRTG_DIR_INPUT', $cfg["path"].'.mrtg');

// image-defines
define('_IMAGE_URL', "image.php");
define('_IMAGE_PREFIX_MRTG', "?i=mrtg&f=");

// init template-instance
tmplInitializeInstance($cfg["theme"], "page.images.tmpl");

// request-vars
$type = (isset($_REQUEST['type'])) ? getRequestVar('type') : _DEFAULT_TYPE;
$target = (isset($_REQUEST['target'])) ? getRequestVar('target') : _DEFAULT_TARGET;

// set vars
$tmpl->setvar('type', $type);
$tmpl->setvar('target', $target);

// types
$types = array("mrtg");
$type_list = array();
foreach ($types as $_type) {
	array_push($type_list, array(
		'name' => $_type,
		'selected' => ($type == $_type) ? 1 : 0
		)
	);
}
$tmpl->setloop('type_list', $type_list);

// type-switch
switch ($type) {

	// mrtg
	case "mrtg":

		// targets
		$target_list = array();
		if ((@is_dir(_MRTG_DIR_INPUT)) && ($dirHandle = @opendir(_MRTG_DIR_INPUT))) {
			while (false !== ($file = @readdir($dirHandle))) {
				if ((strlen($file) > 4) && (substr($file, -4) == ".inc")) {
		      		$targetName = (substr($file, 0, -4));
					array_push($target_list, array(
						'name' => $targetName,
						'selected' => ($target == $targetName) ? 1 : 0
						)
					);
				}
			}
			@closedir($dirHandle);
		}

		// stop here if no targets found
		if (empty($target_list)) {
			$tmpl->setvar('content', "<br><p><strong>No Targets found.</strong></p>");
			break;
		}

		// set target-list
		$tmpl->setloop('target_list', $target_list);

		// target-content
		$targetFile = _MRTG_DIR_INPUT."/".$target.".inc";
		// check target
		if (isValidPath($targetFile) !== true) {
			AuditAction($cfg["constants"]["error"], "ILLEGAL MRTG-TARGET: ".$cfg["user"]." tried to access ".$targetFile);
			@error("Invalid Target", "", "", array($targetFile));
		}
		if (@is_file($targetFile)) {
			$content = @file_get_contents($targetFile);
			// we are only interested in the "real" content
			$tempAry = explode("_CONTENT_BEGIN_", $content);
			if (is_array($tempAry)) {
				$tempVar = array_pop($tempAry);
				$tempAry = explode("_CONTENT_END_", $tempVar);
				if (is_array($tempAry)) {
					$content = array_shift($tempAry);
					// rewrite image-links
					$content = preg_replace('/(.*")(.*)(png".*)/i', '${1}'._IMAGE_URL._IMAGE_PREFIX_MRTG.'${2}${3}', $content);
					// set var
					$tmpl->setvar('content', $content);
				}
			}
		} else {
			AuditAction($cfg["constants"]["error"], "ILLEGAL MRTG-TARGET: ".$cfg["user"]." tried to access ".$targetFile);
			@error("Invalid Target", "", "", array($targetFile));
		}

		break;



	default:
		$tmpl->setvar('content', "Invalid Type");
		break;
}

// more vars
tmplSetTitleBar($cfg["pagetitle"].' - '.$cfg['_ID_IMAGES']);
tmplSetFoot();
$tmpl->setvar('iid', $_REQUEST["iid"]);

// parse template
$tmpl->pparse();

?>