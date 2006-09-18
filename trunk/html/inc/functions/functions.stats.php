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

// labels of server-details
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

// ids of xfer-details
$xferIds = array(
	"xferGlobalTotal",
	"xferGlobalMonth",
	"xferGlobalWeek",
	"xferGlobalDay",
	"xferUserTotal",
	"xferUserMonth",
	"percentDone",
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
      "all" : server-stats + transfer-stats
      "home" : server-stats + xfer-stats
      "server" : server-stats
      "transfers" : transfer-stats
      "xfer" : xfer-stats
      "transfer" : transfer-stats of a single transfer. needs extra-param "i" with the
                   name of the transfer.
"f" : format : optional, default is "'.$cfg['stats_default_format'].'".
      "xml" : new xml-formats, see xml-schemas in dir "xml"
      "rss" : rss 0.91
      "txt" : csv-formatted text
"h" : header : optional and only valid for txt-format, default is "'.$cfg['stats_default_header'].'".
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
* '._URL_THIS.'?t=all&f=txt              :  all stats sent as txt.
* '._URL_THIS.'?t=all&f=txt&h=0          :  all stats sent as txt without headers.
* '._URL_THIS.'?t=all&f=txt&a=1&c=1      :  all stats as text sent as compressed attachment.

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
 * xml-schema defined in tfbstats.xsd/tfbserver.xsd/tfbtransfers.xsd/tfbtransfer.xsd
 *
 * @param $type
 */
function sendXML($type) {
	global $cfg, $serverIdCount, $xferIdCount, $transferIdCount, $serverIds, $serverLabels, $xferIds, $transferIds, $transferList, $transferHeads, $serverStats, $xferStats, $transferID, $transferDetails, $indent;
    // build content
	$content = '<?xml version="1.0" encoding="utf-8"?>'."\n";
	switch ($type) {
		case "all":
			$content .= '<tfbstats>'."\n";
	}
	// server stats
	switch ($type) {
	    case "all":
	    case "server":
	    	$content .= $indent.'<server>'."\n";
			for ($i = 0; $i < $serverIdCount; $i++)
				$content .= $indent.' <serverStat name="'.$serverIds[$i].'">'.$serverStats[$serverIds[$i]].'</serverStat>'."\n";
			$content .= $indent.'</server>'."\n";
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
    global $cfg, $serverIdCount, $xferIdCount, $transferIdCount, $serverIds, $serverLabels, $xferIds, $transferIds, $transferList, $transferHeads, $serverStats, $xferStats, $transferID, $transferDetails;
    // build content
    $content = "<?xml version='1.0' ?>\n\n";
    $content .= "<rss version=\"0.91\">\n";
    $content .= " <channel>\n";
    $content .= "  <title>torrentflux Stats</title>\n";
    // server stats
	switch ($type) {
	    case "all":
	    case "server":
		    $content .= "   <item>\n";
		    $content .= "    <title>Server Stats</title>\n";
		    $content .= "    <description>";
			for ($i = 0; $i < $serverIdCount; $i++) {
				$content .= $serverLabels[$i].": ".$serverStats[$serverIds[$i]];
				if ($i < ($serverIdCount - 1))
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
    global $cfg, $serverIdCount, $xferIdCount, $transferIdCount, $serverIds, $serverLabels, $xferIds, $transferIds, $header, $transferList, $transferHeads, $serverStats, $xferStats, $transferID, $transferDetails;
    // build content
    $content = "";
	// server stats
	switch ($type) {
	    case "all":
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
				$content .= $serverStats[$serverIds[$i]];
				if ($i < ($serverIdCount - 1))
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
 *

 */
function initServerStats() {
	global $cfg, $serverIds, $serverStats;
	$serverStats = array();
	// speedDown
    $speedDown = "n/a";
	$speedDown = @number_format($cfg["total_download"], 2);
	$serverStats[$serverIds[0]] = $speedDown;
	// speedUp
    $speedUp = "n/a";
	$speedUp =  @number_format($cfg["total_upload"], 2);
	$serverStats[$serverIds[1]] = $speedUp;
	// speedTotal
    $speedTotal = "n/a";
	$speedTotal = @number_format($cfg["total_download"] + $cfg["total_upload"], 2);
	$serverStats[$serverIds[2]] = $speedTotal;
	// cons
    $cons = "n/a";
	$cons = @netstatConnectionsSum();
	$serverStats[$serverIds[3]] = $cons;
	// freeSpace
    $freeSpace = "n/a";
	$freeSpace = @formatFreeSpace($cfg["free_space"]);
	$serverStats[$serverIds[4]] = $freeSpace;
	// loadavg
	$loadavg = "n/a";
	$loadavg = @getLoadAverageString();
	$serverStats[$serverIds[5]] = $loadavg;
	// running
	$serverStats[$serverIds[6]] = getRunningTransferCount();
	// queued
	if ((isset($queueActive)) && ($queueActive) && (isset($fluxdQmgr)))
	    $serverStats[$serverIds[7]] = $fluxdQmgr->countQueuedTorrents();
	else
		$serverStats[$serverIds[7]] = "0";
	// speedDownPercent
	$percentDownload = 0;
	$maxDownload = $cfg["bandwidth_down"] / 8;
	if ($maxDownload > 0)
		$percentDownload = @number_format(($cfg["total_download"] / $maxDownload) * 100, 0);
	else
		$percentDownload = 0;
	$serverStats[$serverIds[8]] = $percentDownload;
	// speedUpPercent
	$percentUpload = 0;
	$maxUpload = $cfg["bandwidth_up"] / 8;
	if ($maxUpload > 0)
		$percentUpload = @number_format(($cfg["total_upload"] / $maxUpload) * 100, 0);
	else
		$percentUpload = 0;
	$serverStats[$serverIds[9]] = $percentUpload;
	// driveSpacePercent
    $driveSpacePercent = 0;
	$driveSpacePercent = @getDriveSpace($cfg["path"]);
	$serverStats[$serverIds[10]] = $driveSpacePercent;
}

/**
 * init xfer stats
 * note : this can only be used after a call to update transfer-values in cfg-
 *        array (eg by getTransferListArray)
 *
 */
function initXferStats() {
	global $cfg, $xferIds, $xferStats, $xfer_total;
	$xferStats = array();
}

?>