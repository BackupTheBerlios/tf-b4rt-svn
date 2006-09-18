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

// ids of server-details
$serverIds = array(
	"speedDown",
	"speedUp",
	"speedTotal",
	"cons",
	"freeSpace",
	"loadavg",
	"running",
	"queued",
	"speedDownPercent",
	"speedUpPercent",
	"driveSpacePercent"
);
$serverIdCount = count($serverIds);

// ids of xfer-details
$xferIds = array(
	"xferGlobalTotal",
	"xferGlobalMonth",
	"xferGlobalWeek",
	"xferGlobalDay",
	"xferUserTotal",
	"xferUserMonth",
	"xferUserWeek",
	"xferUserDay"
);
$xferIdCount = count($xferIds);

// ids of transfer-details
$transferIds = array(
	"running",
	"speedDown",
	"speedUp",
	"downCurrent",
	"upCurrent",
	"downTotal",
	"upTotal",
	"percentDone",
	"sharing",
	"eta",
	"seeds",
	"peers",
	"cons"
);
$transferIdCount = count($transferIds);

/**
 * sends usage to client.
 *
 */
function sendUsage() {
	global $cfg;
	//
	$content = '

Params :

"t" : type : optional, default is "'.$cfg['stats_default_type'].'".
      "all" : server + xfer + transfers
      "home" : server + xfer
      "server" : server-stats
      "xfer" : xfer-stats
      "transfers" : transfer-stats
      "transfer" : transfer-stats of a single transfer. needs extra-param "i" with the
                   name of the transfer.
"f" : format : optional, default is "'.$cfg['stats_default_format'].'".
      "xml" : new xml-formats, see xml-schemas in dir "xml"
      "rss" : rss 0.91
      "txt" : csv-formatted text
"h" : header : optional, only used in txt-format, default is "'.$cfg['stats_default_header'].'".
      "0" : send header
      "1" : dont send header.
"a" : send as attachment : optional, default is "'.$cfg['stats_default_attach'].'".
      "0" : dont send as attachment
      "1" : send as attachment
"c" : send compressed (deflate) : optional, default is "'.$cfg['stats_default_compress'].'".
      "0" : dont send compressed
      "1" : send compressed (deflate)

Examples :

* '._URL_THIS.'?t=all&f=xml              :  all stats sent as xml.
* '._URL_THIS.'?t=server&f=xml&a=1       :  server stats as xml sent as attachment.
* '._URL_THIS.'?t=transfers&f=xml&c=1    :  transfer stats as xml sent compressed.
* '._URL_THIS.'?t=all&f=rss              :  all stats sent as rss.
* '._URL_THIS.'?t=all&f=txt&h=1          :  all stats sent as txt with headers.
* '._URL_THIS.'?t=home&f=txt&h=0         :  home stats sent as txt without headers.
* '._URL_THIS.'?t=xfer&f=txt&a=1&c=1     :  xfer stats as text sent as compressed attachment.

* '._URL_THIS.'?t=transfer&i=foo.torrent        :  transfer-stats of foo sent in default-format.
* '._URL_THIS.'?t=transfer&i=bar.torrent&f=xml  :  transfer-stats of bar sent as xml.

* '._URL_THIS.'?t=all&f=xml&username=admin&iamhim=seceret  :  all stats sent as xml. use auth-credentials "admin/seceret".
* '._URL_THIS.'?t=all&f=rss&username=admin&iamhim=seceret  :  all stats sent as rss. use auth-credentials "admin/seceret".

	';
    // send content
    header("Content-Type: text/plain");
    echo $content;
    exit();
}

/**
 * sends content to client.
 *
 * @param $content
 */
function sendContent($content, $contentType, $fileName) {
	global $cfg, $sendAsAttachment, $sendCompressed;
    // send content
    header("Cache-Control: ");
    header("Pragma: ");
    if ($sendCompressed != 0) {
    	$contentCompressed = gzdeflate($content, $cfg['stats_deflate_level']);
		header("Content-Type: application/octet-stream");
		if ($sendAsAttachment != 0) {
			header("Content-Length: " .(string)(strlen($contentCompressed)) );
			header('Content-Disposition: attachment; filename="'.$fileName.'"');
		}
		header("Content-Transfer-Encoding: binary\n");
		echo $contentCompressed;
    } else {
	    header("Content-Type: ".$contentType);
	    if ($sendAsAttachment != 0) {
	        header("Content-Length: ".(string)strlen($content));
	        header('Content-Disposition: attachment; filename="'.$fileName.'"');
	    }
	    echo $content;
    }
    exit();
}

/**
 * This method sends stats as xml.
 * xml-schema defined in tfbstats.xsd/tfbhome.xsd/tfbserver.xsd/tfbxfer.xsd/tfbtransfers.xsd/tfbtransfer.xsd
 *
 * @param $type
 */
function sendXML($type) {
	global $cfg, $serverIdCount, $xferIdCount, $transferIdCount, $serverIds, $serverLabels, $xferIds, $xferLabels, $transferIds, $transferList, $transferHeads, $serverStats, $xferStats, $transferID, $transferDetails, $indent;
    // build content
	$content = '<?xml version="1.0" encoding="utf-8"?>'."\n";
	switch ($type) {
		case "all":
			$content .= '<tfbstats>'."\n";
			break;
		case "home":
			$content .= '<tfbhome>'."\n";
			break;
	}
	// server stats
	switch ($type) {
	    case "all":
	    case "home":
	    case "server":
	    	$content .= $indent.'<server>'."\n";
			for ($i = 0; $i < $serverIdCount; $i++)
				$content .= $indent.' <serverStat name="'.$serverIds[$i].'">'.$serverStats[$i].'</serverStat>'."\n";
			$content .= $indent.'</server>'."\n";
	}
	// xfer stats
	switch ($type) {
	    case "all":
	    case "home":
	    case "xfer":
	    	$content .= $indent.'<xfer>'."\n";
			for ($i = 0; $i < $xferIdCount; $i++)
				$content .= $indent.' <xferStat name="'.$xferIds[$i].'">'.$xferStats[$i].'</xferStat>'."\n";
			$content .= $indent.'</xfer>'."\n";
	}
    // transfer-list
	switch ($type) {
	    case "all":
	    case "transfers":
		    $content .= $indent.'<transfers>'."\n";
			foreach ($transferList as $transferAry) {
				$content .= $indent.' <transfer name="'.$transferAry[0].'">'."\n";
				$size = count($transferAry);
				for ($i = 1; $i < $size; $i++)
					$content .= $indent.'  <transferStat name="'.$transferHeads[$i-1].'">'.$transferAry[$i].'</transferStat>'."\n";
				$content .= $indent.' </transfer>'."\n";
			}
		    $content .= $indent.'</transfers>'."\n";
	}
	// transfer-details
	switch ($type) {
	    case "transfer":
			$content .= $indent.'<transfer name="'.$transferID.'">'."\n";
			for ($i = 0; $i < $transferIdCount; $i++)
				$content .= $indent.' <transferStat name="'.$transferIds[$i].'">'.$transferDetails[$transferIds[$i]].'</transferStat>'."\n";
			$content .= $indent.'</transfer>'."\n";
	}
    // end document
	switch ($type) {
		case "all":
			$content .= '</tfbstats>'."\n";
			break;
		case "home":
			$content .= '</tfbhome>'."\n";
			break;
	}
    // send content
    sendContent($content, "text/xml", "stats.xml");
}

/**
 * This method sends stats as rss 0.91.
 *
 * @param $type
 */
function sendRSS($type) {
    global $cfg, $serverIdCount, $xferIdCount, $transferIdCount, $serverIds, $serverLabels, $xferIds, $xferLabels, $transferIds, $transferList, $transferHeads, $serverStats, $xferStats, $transferID, $transferDetails;
    // build content
    $content = "<?xml version='1.0' ?>\n\n";
    $content .= "<rss version=\"0.91\">\n";
    $content .= " <channel>\n";
    $content .= "  <title>torrentflux Stats</title>\n";
    // server stats
	switch ($type) {
	    case "all":
	    case "home":
	    case "server":
		    $content .= "   <item>\n";
		    $content .= "    <title>Server Stats</title>\n";
		    $content .= "    <description>";
			for ($i = 0; $i < $serverIdCount; $i++) {
				$content .= $serverLabels[$i].": ".$serverStats[$i];
				if ($i < ($serverIdCount - 1))
					$content .= " || ";
			}
		    $content .= "    </description>\n";
		    $content .= "   </item>\n";
	}
	// xfer stats
	switch ($type) {
	    case "all":
	    case "home":
	    case "xfer":
		    $content .= "   <item>\n";
		    $content .= "    <title>Xfer Stats</title>\n";
		    $content .= "    <description>";
			for ($i = 0; $i < $xferIdCount; $i++) {
				$content .= $xferLabels[$i].": ".$xferStats[$i];
				if ($i < ($xferIdCount - 1))
					$content .= " || ";
			}
		    $content .= "    </description>\n";
		    $content .= "   </item>\n";
	}
	// transfer-list
	switch ($type) {
	    case "all":
	    case "transfers":
			foreach ($transferList as $transferAry) {
				$content .= "   <item>\n";
				$content .= "    <title>Transfer: ".$transferAry[0]."</title>\n";
				$content .= "    <description>";
				$size = count($transferAry);
				for ($i = 1; $i < $size; $i++) {
					$content .= $transferHeads[$i-1].': '.$transferAry[$i];
					if ($i < ($size - 1))
						$content .= " || ";
				}
				$content .= "    </description>\n";
				$content .= "   </item>\n";
			}
	}
	// transfer-details
	switch ($type) {
	    case "transfer":
			$content .= "   <item>\n";
			$content .= "    <title>Transfer: ".$transferID."</title>\n";
			$content .= "    <description>";
			for ($i = 0; $i < $transferIdCount; $i++) {
				$content .= $transferIds[$i].': '.$transferDetails[$transferIds[$i]];
				if ($i < ($transferIdCount - 1))
					$content .= " || ";
			}
			$content .= "    </description>\n";
			$content .= "   </item>\n";
	}
    // end document
    $content .= " </channel>\n";
    $content .= "</rss>";
    // send content
    sendContent($content, "text/xml", "stats.xml");
}

/**
 * This method sends stats as txt.
 *
 * @param $type
 */
function sendTXT($type) {
    global $cfg, $serverIdCount, $xferIdCount, $transferIdCount, $serverIds, $serverLabels, $xferIds, $xferLabels, $transferIds, $header, $transferList, $transferHeads, $serverStats, $xferStats, $transferID, $transferDetails;
    // build content
    $content = "";
	// server stats
	switch ($type) {
	    case "all":
	    case "home":
	    case "server":
	    	if ($header == 1) {
				for ($j = 0; $j < $serverIdCount; $j++) {
					$content .= $serverLabels[$j];
					if ($j < ($serverIdCount - 1))
						$content .= $cfg['stats_txt_delim'];
				}
				$content .= "\n";
	    	}
			for ($i = 0; $i < $serverIdCount; $i++) {
				$content .= $serverStats[$i];
				if ($i < ($serverIdCount - 1))
					$content .= $cfg['stats_txt_delim'];
			}
			$content .= "\n";
	}
	// xfer stats
	switch ($type) {
	    case "all":
	    case "home":
	    case "xfer":
	    	if ($header == 1) {
				for ($j = 0; $j < $xferIdCount; $j++) {
					$content .= $xferLabels[$j];
					if ($j < ($xferIdCount - 1))
						$content .= $cfg['stats_txt_delim'];
				}
				$content .= "\n";
	    	}
			for ($i = 0; $i < $xferIdCount; $i++) {
				$content .= $xferStats[$i];
				if ($i < ($xferIdCount - 1))
					$content .= $cfg['stats_txt_delim'];
			}
			$content .= "\n";
	}
    // transfer-list
	switch ($type) {
	    case "all":
	    case "transfers":
	    	if ($header == 1) {
		    	$content .= "Name" . $cfg['stats_txt_delim'];
		    	$sizeHead = count($transferHeads);
				for ($j = 0; $j < $sizeHead; $j++) {
					$content .= $transferHeads[$j];
					if ($j < ($sizeHead - 1))
						$content .= $cfg['stats_txt_delim'];
				}
		    	$content .= "\n";
	    	}
			foreach ($transferList as $transferAry) {
				$size = count($transferAry);
				for ($i = 0; $i < $size; $i++) {
					$content .= $transferAry[$i];
					if ($i < ($size - 1))
						$content .= $cfg['stats_txt_delim'];
				}
				$content .= "\n";
			}
	}
	// transfer-details
	switch ($type) {
	    case "transfer":
	    	if ($header == 1) {
				for ($j = 0; $j < $transferIdCount; $j++) {
					$content .= $transferIds[$j];
					if ($j < ($transferIdCount - 1))
						$content .= $cfg['stats_txt_delim'];
				}
		    	$content .= "\n";
	    	}
			for ($i = 0; $i < $transferIdCount; $i++) {
				$content .= $transferDetails[$transferIds[$i]];
				if ($i < ($transferIdCount - 1))
					$content .= $cfg['stats_txt_delim'];
			}
			$content .= "\n";
	}
    // send content
    sendContent($content, "text/plain", "stats.txt");
}

/**
 * init server stats
 * note : this can only be used after a call to update transfer-values in cfg-
 *        array (eg by getTransferListArray)
 */
function initServerStats() {
	global $cfg, $serverIds, $serverLabels, $serverStats;
	// init labels
	$serverLabels = array(
		"Speed Down",
		"Speed Up",
		"Speed Total",
		"Connections",
		"Free Space",
		"Load",
		"Running",
		"Queued",
		"Speed Down (Percent)",
		"Speed Up (Percent)",
		"Drive Space (Percent)"
	);
	$serverStats = array();
	// speedDown
    $speedDown = "n/a";
	$speedDown = @number_format($cfg["total_download"], 2);
	array_push($serverStats, $speedDown);
	// speedUp
    $speedUp = "n/a";
	$speedUp =  @number_format($cfg["total_upload"], 2);
	array_push($serverStats, $speedUp);
	// speedTotal
    $speedTotal = "n/a";
	$speedTotal = @number_format($cfg["total_download"] + $cfg["total_upload"], 2);
	array_push($serverStats, $speedTotal);
	// cons
    $cons = "n/a";
	$cons = @netstatConnectionsSum();
	array_push($serverStats, $cons);
	// freeSpace
    $freeSpace = "n/a";
	$freeSpace = @formatFreeSpace($cfg["free_space"]);
	array_push($serverStats, $freeSpace);
	// loadavg
	$loadavg = "n/a";
	$loadavg = @getLoadAverageString();
	array_push($serverStats, $loadavg);
	// running
	$running = "n/a";
	$running = @getRunningTransferCount();
	array_push($serverStats, $running);
	// queued
	$queued = "n/a";
	if ((isset($queueActive)) && ($queueActive) && (isset($fluxdQmgr)))
	    $queued = @ $fluxdQmgr->countQueuedTorrents();
	array_push($serverStats, $queued);
	// speedDownPercent
	$percentDownload = 0;
	$maxDownload = $cfg["bandwidth_down"] / 8;
	if ($maxDownload > 0)
		$percentDownload = @number_format(($cfg["total_download"] / $maxDownload) * 100, 0);
	else
		$percentDownload = 0;
	array_push($serverStats, $percentDownload);
	// speedUpPercent
	$percentUpload = 0;
	$maxUpload = $cfg["bandwidth_up"] / 8;
	if ($maxUpload > 0)
		$percentUpload = @number_format(($cfg["total_upload"] / $maxUpload) * 100, 0);
	else
		$percentUpload = 0;
	array_push($serverStats, $percentUpload);
	// driveSpacePercent
    $driveSpacePercent = 0;
	$driveSpacePercent = @getDriveSpace($cfg["path"]);
	array_push($serverStats, $driveSpacePercent);
}

/**
 * init xfer stats
 * note : this can only be used after a call to update transfer-values in cfg-
 *        array (eg by getTransferListArray)
 */
function initXferStats() {
	global $cfg, $xferIds, $xferLabels, $xferStats, $xfer_total, $xfer;
	// init labels
	$xferLabels = array(
		'Server : '.$cfg['_TOTALXFER'],
		'Server : '.$cfg['_MONTHXFER'],
		'Server : '.$cfg['_WEEKXFER'],
		'Server : '.$cfg['_DAYXFER'],
		'User : '.$cfg['_TOTALXFER'],
		'User : '.$cfg['_MONTHXFER'],
		'User : '.$cfg['_WEEKXFER'],
		'User : '.$cfg['_DAYXFER']
	);
	$xferStats = array();
	// global
    $xferGlobalTotal = "n/a";
	$xferGlobalTotal =  @formatFreeSpace($xfer_total['total']['total'] / 1048576);
	array_push($xferStats, $xferGlobalTotal);
    $xferGlobalMonth = "n/a";
	$xferGlobalMonth =  @formatFreeSpace($xfer_total['month']['total'] / 1048576);
	array_push($xferStats, $xferGlobalMonth);
    $xferGlobalWeek = "n/a";
	$xferGlobalWeek =  @formatFreeSpace($xfer_total['week']['total'] / 1048576);
	array_push($xferStats, $xferGlobalWeek);
    $xferGlobalDay = "n/a";
	$xferGlobalDay =  @formatFreeSpace($xfer_total['day']['total'] / 1048576);
	array_push($xferStats, $xferGlobalDay);
	// user
    $xferUserTotal = "n/a";
	$xferUserTotal =  @formatFreeSpace($xfer[$cfg["user"]]['total']['total'] / 1048576);
	array_push($xferStats, $xferUserTotal);
    $xferUserMonth = "n/a";
	$xferUserMonth =  @formatFreeSpace($xfer[$cfg["user"]]['month']['total'] / 1048576);
	array_push($xferStats, $xferUserMonth);
    $xferUserWeek = "n/a";
	$xferUserWeek =  @formatFreeSpace($xfer[$cfg["user"]]['week']['total'] / 1048576);
	array_push($xferStats, $xferUserWeek);
    $xferUserDay = "n/a";
	$xferUserDay =  @formatFreeSpace($xfer[$cfg["user"]]['day']['total'] / 1048576);
	array_push($xferStats, $xferUserDay);
}

?>