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
define('_URL_DTD_XMLOLD','http://'.$_SERVER['SERVER_NAME'].'/tf_xml.dtd');

// -----------------------------------------------------------------------------
// init
// -----------------------------------------------------------------------------

include_once("config.php");
switch (_PUBLIC_STATS) {
    case 0:
        // includes
        include_once("functions.php");
        break;
    case 1:
        include_once('db.php');
        include_once("settingsfunctions.php");
        // tf-functions
        include_once('functions.tf.php');
        // hacks-functions
        include_once('functions.hacks.php');
        // b4rt-functions
        include_once('functions.b4rt.php');
        // Create Connection.
        $db = getdb();
        // load settings
        loadSettings();
        // Free space in MB
        $cfg["free_space"] = @disk_free_space($cfg["path"])/(1024*1024);
        // Path to where the torrent meta files will be stored.
        $cfg["torrent_file_path"] = $cfg["path"].".torrents/";
        // public stats... show all .. we set the user to superadmin
        $superAdm = $db->GetOne("SELECT user_id FROM tf_users WHERE uid = '1'");
        if($db->ErrorNo() != 0) {
            @ob_end_clean();
            exit();
        }
        if ((isset($superAdm)) && ($superAdm != "")) {
            $cfg["user"] = $superAdm;
        } else {
            @ob_end_clean();
            exit();
        }
        break;
    default:
       @ob_end_clean();
	   exit();
}
// client-handler-"interfaces"
include_once("AliasFile.php");

// -----------------------------------------------------------------------------
// Main
// -----------------------------------------------------------------------------

// send as attachment ? (default)
$sendAsAttachment = 0;

// format (default)
$format = "xml";

// read params
if (isset($_REQUEST["f"]))
    $format = trim($_REQUEST["f"]);
if (isset($_REQUEST["a"]))
    $sendAsAttachment = (int) trim($_REQUEST["a"]);

// action
switch ($format) {
    case "rss":
        sendRss();
        break;
    case "xml_old":
        sendXmlOld();
        break;
    case "xml":
    default:
        sendXML();
        break;
}
exit();

// -----------------------------------------------------------------------------
// functions
// -----------------------------------------------------------------------------

/**
 * This method sends transfer-list and stats as xml
 * xml-schema defined in tfbstats.xsd.
 *
 */
function sendXML() {
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

/*
 * This method sends transfer-list and stats as xml
 * xml-format and dtd from IHateMyISP
 *
 */
function sendXmlOld() {
    global $cfg, $sendAsAttachment;
    $content = "";
    // build content
	$content .= "<?xml version='1.0' ?>\n\n";
	$content .= "<!DOCTYPE rss SYSTEM \"". _URL_DTD_XMLOLD ."\">\n";
	$content .= "<rss version=\"0.91\">\n";
	$content .= "<torrent_flux>\n";
	$content .= "<torrents>\n";
    // transfer-list
    $arList = getTransferArray($cfg["index_page_sortorder"]);
    foreach($arList as $entry) {
		$transferTotals = getTransferTotals($entry);
        // alias / stat
        $alias = getAliasName($entry).".stat";
        if ((substr( strtolower($entry),-8 ) == ".torrent")) {
            // this is a torrent-client
            $btclient = getTransferClient($entry);
            $transferowner = getOwner($entry);
            $af = AliasFile::getAliasFileInstance($cfg["torrent_file_path"].$alias, $transferowner, $cfg, $btclient);
        } else if ((substr( strtolower($entry),-4 ) == ".url")) {
            // this is wget.
            $alias = str_replace(".url", "", $alias);
            $transferowner = $cfg["user"];
            $af = AliasFile::getAliasFileInstance($cfg["torrent_file_path"].$alias, $cfg['user'], $cfg, 'wget');
        } else {
        	$transferowner = $cfg["user"];
            // this is "something else". use tornado statfile as default
            $af = AliasFile::getAliasFileInstance($cfg["torrent_file_path"].$alias, $cfg['user'], $cfg, 'tornado');
        }
        // increment the totals
        if(!isset($cfg["total_upload"])) $cfg["total_upload"] = 0;
        if(!isset($cfg["total_download"])) $cfg["total_download"] = 0;
        $cfg["total_upload"] = $cfg["total_upload"] + GetSpeedValue($af->up_speed);
        $cfg["total_download"] = $cfg["total_download"] + GetSpeedValue($af->down_speed);
        // xml-string
        $speed = "";
    	if($af->running == 1)
            $speed = $af->down_speed." - ".$af->up_speed;
    	else
            $speed = "Torrent Not Running";
        $sharing = number_format((($transferTotals['uptotal'] / ($af->size+0)) * 100), 2);
		$content .= "<torrent>\n";
		$content .= "<name><![CDATA[".$entry."]]></name>\n";
		$content .= "<speeds><![CDATA[".$speed."]]></speeds>\n";
		$content .= "<size><![CDATA[".formatBytesToKBMGGB($af->size)."]]></size>\n";
		$content .= "<percent><![CDATA[".$af->percent_done."]]></percent>\n";
		$content .= "<sharing><![CDATA[". $sharing ."]]></sharing>\n";
		$content .= "<remaining><![CDATA[".str_replace('&#8734', 'Unknown', $af->time_left)."]]></remaining>\n";
		$content .= "<transfered><![CDATA[".formatBytesToKBMGGB($transferTotals['downtotal'])." - ".formatBytesToKBMGGB($transferTotals['uptotal'])."]]></transfered>\n";
		$content .= "</torrent>\n";
    }
	$content .= "</torrents>\n";
	$content .= "<tf_details>\n";
	$content .= "<total_speeds><![CDATA[".number_format($cfg["total_download"], 2)." - ".number_format($cfg["total_upload"], 2)."]]></total_speeds>\n";
	$content .= "<free_space><![CDATA[".formatFreeSpace($cfg['free_space'])."]]></free_space>\n";
	$content .= "</tf_details>\n";
	$content .= "</torrent_flux>\n";
	$content .= "</rss>";
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

/*
 * This method sends transfer-list and stats as xml (rss).
 * xml-format + code from khr0n0s
 *
 */
function sendRss() {
    global $cfg, $sendAsAttachment;
    $content = "";
    $run = 0;
    // build content
    $content .= "<?xml version='1.0' ?>\n\n";
    $content .= "<rss version=\"0.91\">\n";
    $content .= "<channel>\n";
    $content .= "<title>TorrentFlux Status</title>\n";
    // transfer-list
    $arList = getTransferArray($cfg["index_page_sortorder"]);
    foreach($arList as $entry) {
        $transferTotals = getTransferTotals($entry);
        // alias / stat
        $alias = getAliasName($entry).".stat";
        if ((substr( strtolower($entry),-8 ) == ".torrent")) {
            // this is a torrent-client
            $alias = getAliasName($entry).".stat";
            $btclient = getTransferClient($entry);
            $transferowner = getOwner($entry);
            $af = AliasFile::getAliasFileInstance($cfg["torrent_file_path"].$alias, $transferowner, $cfg, $btclient);
        } else if ((substr( strtolower($entry),-4 ) == ".url")) {
            // this is wget. use wget statfile
            $alias = str_replace(".url", "", $alias);
            $transferowner = $cfg["user"];
            $af = AliasFile::getAliasFileInstance($cfg["torrent_file_path"].$alias, $cfg['user'], $cfg, 'wget');
        } else {
            // this is "something else". use tornado statfile as default
            $transferowner = $cfg["user"];
            $af = AliasFile::getAliasFileInstance($cfg["torrent_file_path"].$alias, $cfg['user'], $cfg, 'tornado');
        }
        // increment the totals
        if(!isset($cfg["total_upload"])) $cfg["total_upload"] = 0;
        if(!isset($cfg["total_download"])) $cfg["total_download"] = 0;
        $cfg["total_upload"] = $cfg["total_upload"] + GetSpeedValue($af->up_speed);
        $cfg["total_download"] = $cfg["total_download"] + GetSpeedValue($af->down_speed);
        // xml-string
        $remaining = str_replace('&#8734', 'Unknown', $af->time_left);
        if($af->running == 1)
            $run++;
        else
            $remaining = "Torrent Not Running";
        $sharing = number_format(($transferTotals['uptotal'] / ($af->size+0)), 2);
        $content .= "<item>\n";
        $content .= "<title>".$entry." (".$remaining.")</title>\n";
        $content .= "<description>Down Speed: ".$af->down_speed." || Up Speed: ".$af->up_speed." || Size: ".formatBytesToKBMGGB($af->size)." || Percent: ".$af->percent_done." || Sharing: ". $sharing ." || Remaining: ".$remaining." || Transfered Down: ".formatBytesToKBMGGB($transferTotals['downtotal'])." || Transfered Up: ".formatBytesToKBMGGB($transferTotals['uptotal'])."</description>\n";
        $content .= "</item>\n";
    }
    $content .= "<item>\n";
    $content .= "<title>Total (".$run.")</title>\n";
    $content .= "<description>Down Speed: ".number_format($cfg["total_download"], 2)." || Up Speed: ".number_format($cfg["total_upload"], 2)." || Free Space: ".formatFreeSpace($cfg['free_space'])."</description>\n";
    $content .= "</item>\n";
    $content .= "</channel>\n";
    $content .= "</rss>";
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