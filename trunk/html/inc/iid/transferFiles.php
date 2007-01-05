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

// request-vars
$transfer = getRequestVar('transfer');
if (empty($transfer))
	@error("missing params", "index.php?iid=index", "", array('transfer'));

// validate transfer
if (isValidTransfer($transfer) !== true) {
	AuditAction($cfg["constants"]["error"], "INVALID TRANSFER: ".$transfer);
	@error("Invalid Transfer", "", "", array($transfer));
}

// init template-instance
tmplInitializeInstance($cfg["theme"], "page.transferFiles.tmpl");

// set transfer vars
$tmpl->setvar('transfer', $transfer);
$tmpl->setvar('transferLabel', (strlen($transfer) >= 39) ? substr($transfer, 0, 35)."..." : $transfer);

$tmpl->setvar('transferFileCount', '0 Files');

// client-switch
$transferFilesList = array();
if (substr($transfer, -8) == ".torrent") {
	// this is a t-client
	require_once("inc/classes/BDecode.php");
	$tFile = $cfg["transfer_file_path"].$transfer;
	if ($fd = @fopen($tFile, "rd")) {
		$alltorrent = @fread($fd, @filesize($tFile));
		$btmeta = @BDecode($alltorrent);
		@fclose($fd);
	}
	if ((isset($btmeta)) && (is_array($btmeta)) && (isset($btmeta['info'])) && (array_key_exists('files', $btmeta['info']))) {
		foreach ($btmeta['info']['files'] as $filenum => $file) {
			array_push($transferFilesList, array(
				'name' => (is_array($file['path'])) ? $file['path'][0] : $file['path'],
				'size' => ((isset($file['length'])) && (is_numeric($file['length']))) ? formatBytesTokBMBGBTB($file['length']) : 0
				)
			);
		}
		if (empty($transferFilesList)) {
			$tmpl->setvar('statusImage', "red.gif");
			$tmpl->setvar('transferFilesString', "Empty");
		} else {
			$tmpl->setvar('statusImage', "green.gif");
			$tmpl->setloop('transferFilesList', $transferFilesList);
		}
	} else {
		$tmpl->setvar('statusImage', "red.gif");
		$tmpl->setvar('transferFilesString', "Empty");
	}
} else if (substr($transfer, -5) == ".wget") {
	// this is wget.
	$fileContent = @file_get_contents($cfg["transfer_file_path"].$transfer);
	$tList = explode("\n", $fileContent);
	if ((isset($tList)) && (is_array($tList))) {
		foreach ($tList as $tLine) {
			$tfile = trim($tLine);
			array_push($transferFilesList, array(
				'name' => $tfile,
				'size' => "unknown"
				)
			);
		}
		if (empty($transferFilesList)) {
			$tmpl->setvar('statusImage', "red.gif");
			$tmpl->setvar('transferFilesString', "Empty");
		} else {
			$tmpl->setvar('statusImage', "green.gif");
			$tmpl->setloop('transferFilesList', $transferFilesList);
		}
	} else {
		$tmpl->setvar('statusImage', "red.gif");
		$tmpl->setvar('transferFilesString', "Empty");
	}
} else if (substr($transfer, -4) == ".nzb") {
	// this is nzbperl.
	$tmpl->setvar('statusImage', "green.gif");
	$fileContent = @file_get_contents($cfg["transfer_file_path"].$transfer);
	$tList = explode("\n", $fileContent);
	if ((isset($tList)) && (is_array($tList))) {
		$isFile = false;
		$name = "";
		$size = 0;
		foreach ($tList as $tLine) {
			// file-start
			if (strpos($tLine, "<file") !== false) {
				$name = preg_replace('/<file.*subject="(.*)">/i', '${1}', $tLine);
				$size = 0;
			}
			// segments
			if (strpos($tLine, "<segment bytes") !== false) {
				$bytes = preg_replace('/<segment bytes="(\d+)".*/i', '${1}', $tLine);
				if (is_numeric($bytes))
					$size += $bytes;
			}
			// file end
			if (strpos($tLine, "</file>") !== false) {
				array_push($transferFilesList, array(
					'name' => $name,
					'size' => ($size != 0) ? formatBytesTokBMBGBTB($size) : 0
					)
				);
			}
		}
		if (empty($transferFilesList)) {
			$tmpl->setvar('statusImage', "red.gif");
			$tmpl->setvar('transferFilesString', "Empty");
		} else {
			$tmpl->setvar('statusImage', "green.gif");
			$tmpl->setloop('transferFilesList', $transferFilesList);
		}
	} else {
		$tmpl->setvar('statusImage', "red.gif");
		$tmpl->setvar('transferFilesString', "Empty");
	}
} else {
	AuditAction($cfg["constants"]["error"], "INVALID TRANSFER: ".$transfer);
	@error("Invalid Transfer", "", "", array($transfer));
}

// count
$tmpl->setvar('transferFileCount', (((isset($transferFilesList)) && (is_array($transferFilesList))) ? count($transferFilesList) : 0).' Files');

// title + foot
tmplSetFoot(false);
tmplSetTitleBar($cfg["pagetitle"]." - Files", false);

// parse template
$tmpl->pparse();

?>