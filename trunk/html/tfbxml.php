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

// defines
define('_FILE_THIS',$_SERVER['SCRIPT_NAME']);
define('_URL_THIS','http://'.$_SERVER['SERVER_NAME']. _FILE_THIS);

// -----------------------------------------------------------------------------
// init
// -----------------------------------------------------------------------------

// includes
include_once("config.php");
include_once("functions.php");
include_once("AliasFile.php");

// -----------------------------------------------------------------------------
// Main
// -----------------------------------------------------------------------------

// send as attachment ? (default)
$sendAsAttachment = 0;

// type (default)
$getType = "stats";

// read params
if (isset($_REQUEST["g"]))
    $getType = trim($_REQUEST["g"]);
if (isset($_REQUEST["a"]))
    $sendAsAttachment = (int) trim($_REQUEST["a"]);

// action
switch ($getType) {
    case "server":
        sendServer();
        break;
    case "transfers":
        sendTransfers();
        break;
    case "stats":
        sendStats();
        break;
}
exit();

// -----------------------------------------------------------------------------
// functions
// -----------------------------------------------------------------------------

/**
 * This method sends server as xml
 * xml-schema defined in tfbserver.xsd.
 *
 */
function sendServer() {
    global $cfg, $sendAsAttachment;
    // prepare some vars
    // lists
    $transferList = getTransferListArray();
    // server-stats
    $speedDown = "";
	$speedDown = @number_format($cfg["total_download"], 2);
    $speedUp = "";
	$speedUp =  @number_format($cfg["total_upload"], 2);
    $speedTotal = "";
	$speedTotal = @number_format($cfg["total_download"]+$cfg["total_upload"], 2);
    $netstatConnectionsSum = "";
	$netstatConnectionsSum = @netstatConnectionsSum();
    $freeSpace = "";
	$freeSpace = @formatFreeSpace($cfg["free_space"]);
	$loadavgString = "";
	$loadavgString = @getLoadAverageString();
    // build content
    $content = "";
	$content .= '<?xml version="1.0" encoding="utf-8"?>'."\n";
	// server stats
	$content .= '<server>'."\n";
	$content .= ' <serverStat name="speedDown">'.$speedDown.'</serverStat>'."\n";
	$content .= ' <serverStat name="speedUp">'.$speedUp.'</serverStat>'."\n";
	$content .= ' <serverStat name="speedTotal">'.$speedTotal.'</serverStat>'."\n";
	$content .= ' <serverStat name="connections">'.$netstatConnectionsSum.'</serverStat>'."\n";
	$content .= ' <serverStat name="freeSpace">'.$freeSpace.'</serverStat>'."\n";
	$content .= ' <serverStat name="loadavg">'.$loadavgString.'</serverStat>'."\n";
	$content .= '</server>'."\n";
    // send content
    header("Cache-Control: ");
    header("Pragma: ");
    header("Content-Type: text/xml");
    if ($sendAsAttachment != 0) {
        header("Content-Length: ".strlen($content));
        header('Content-Disposition: attachment; filename="stats.xml"');
    }
    echo $content;
}

/**
 * This method sends transfers as xml
 * xml-schema defined in tfbtransfers.xsd.
 *
 */
function sendTransfers() {
    global $cfg, $sendAsAttachment;
    // prepare some vars
    // lists
    $transferHeads = getTransferListHeadArray();
    $transferList = getTransferListArray();
    // build content
    $content = "";
	$content .= '<?xml version="1.0" encoding="utf-8"?>'."\n";
    // transfer-list
    $content .= '<transfers>'."\n";
	foreach ($transferList as $transferAry) {
		$content .= ' <transfer name="'.$transferAry[0].'">'."\n";
		$size = count($transferAry);
		for ($i = 1; $i < $size; $i++)
			$content .= '  <transferStat name="'.$transferHeads[$i-1].'">'.$transferAry[$i].'</transferStat>'."\n";
		$content .= ' </transfer>'."\n";
	}
    $content .= '</transfers>'."\n";
    // send content
    header("Cache-Control: ");
    header("Pragma: ");
    header("Content-Type: text/xml");
    if ($sendAsAttachment != 0) {
        header("Content-Length: ".strlen($content));
        header('Content-Disposition: attachment; filename="stats.xml"');
    }
    echo $content;
}

/**
 * This method sends stats as xml
 * xml-schema defined in tfbstats.xsd.
 *
 */
function sendStats() {
    global $cfg, $sendAsAttachment;
    // prepare some vars
    // lists
    $transferHeads = getTransferListHeadArray();
    $transferList = getTransferListArray();
    // server-stats
    $speedDown = "";
	$speedDown = @number_format($cfg["total_download"], 2);
    $speedUp = "";
	$speedUp =  @number_format($cfg["total_upload"], 2);
    $speedTotal = "";
	$speedTotal = @number_format($cfg["total_download"]+$cfg["total_upload"], 2);
    $netstatConnectionsSum = "";
	$netstatConnectionsSum = @netstatConnectionsSum();
    $freeSpace = "";
	$freeSpace = @formatFreeSpace($cfg["free_space"]);
	$loadavgString = "";
	$loadavgString = @getLoadAverageString();
    // build content
    $content = "";
	$content .= '<?xml version="1.0" encoding="utf-8"?>'."\n";
	$content .= '<tfbstats>'."\n";
	// server stats
	$content .= ' <server>'."\n";
	$content .= '  <serverStat name="speedDown">'.$speedDown.'</serverStat>'."\n";
	$content .= '  <serverStat name="speedUp">'.$speedUp.'</serverStat>'."\n";
	$content .= '  <serverStat name="speedTotal">'.$speedTotal.'</serverStat>'."\n";
	$content .= '  <serverStat name="connections">'.$netstatConnectionsSum.'</serverStat>'."\n";
	$content .= '  <serverStat name="freeSpace">'.$freeSpace.'</serverStat>'."\n";
	$content .= '  <serverStat name="loadavg">'.$loadavgString.'</serverStat>'."\n";
	$content .= ' </server>'."\n";
    // transfer-list
    $content .= ' <transfers>'."\n";
	foreach ($transferList as $transferAry) {
		$content .= '  <transfer name="'.$transferAry[0].'">'."\n";
		$size = count($transferAry);
		for ($i = 1; $i < $size; $i++)
			$content .= '   <transferStat name="'.$transferHeads[$i-1].'">'.$transferAry[$i].'</transferStat>'."\n";
		$content .= '  </transfer>'."\n";
	}
    $content .= ' </transfers>'."\n";
    // end document
    $content .= '</tfbstats>'."\n";
    // send content
    header("Cache-Control: ");
    header("Pragma: ");
    header("Content-Type: text/xml");
    if ($sendAsAttachment != 0) {
        header("Content-Length: ".strlen($content));
        header('Content-Disposition: attachment; filename="stats.xml"');
    }
    echo $content;
}

?>