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
      "server" : server-stats
      "transfers" : transfer-stats
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

* '._URL_THIS.'?t=all&f=xml            :  all stats sent as xml.
* '._URL_THIS.'?t=server&f=xml&a=1     :  server stats as xml sent as attachment.
* '._URL_THIS.'?t=transfers&f=xml&c=1  :  transfer stats as xml sent compressed.
* '._URL_THIS.'?t=all&f=rss            :  all stats sent as rss.
* '._URL_THIS.'?t=all&f=txt            :  all stats sent as txt.
* '._URL_THIS.'?t=all&f=txt&h=0        :  all stats sent as txt without headers.
* '._URL_THIS.'?t=all&f=txt&a=1&c=1    :  all stats as text sent as compressed attachment.

* '._URL_THIS.'?t=all&f=xml&username=admin&iamhim=seceret  :  all stats sent as xml. use auth-credentials "admin/seceret".
* '._URL_THIS.'?t=all&f=rss&username=admin&iamhim=seceret  :  all stats sent as rss.  use auth-credentials "admin/seceret".

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
 * xml-schema defined in tfbstats.xsd/tfbserver.xsd/tdbtransfers.xsd
 *
 * @param $type
 */
function sendXML($type) {
	global $cfg, $transferList, $transferHeads, $serverStats, $indent;
    // build content
    $content = "";
	$content .= '<?xml version="1.0" encoding="utf-8"?>'."\n";
	switch ($type) {
		case "all":
			$content .= '<tfbstats>'."\n";
	}
	// server stats
	switch ($type) {
	    case "all":
	    case "server":
			$content .= $indent.'<server>'."\n";
			$content .= $indent.' <serverStat name="speedDown">'.$serverStats['speedDown'].'</serverStat>'."\n";
			$content .= $indent.' <serverStat name="speedUp">'.$serverStats['speedUp'].'</serverStat>'."\n";
			$content .= $indent.' <serverStat name="speedTotal">'.$serverStats['speedTotal'].'</serverStat>'."\n";
			$content .= $indent.' <serverStat name="connections">'.$serverStats['connections'].'</serverStat>'."\n";
			$content .= $indent.' <serverStat name="freeSpace">'.$serverStats['freeSpace'].'</serverStat>'."\n";
			$content .= $indent.' <serverStat name="loadavg">'.$serverStats['loadavg'].'</serverStat>'."\n";
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
    global $cfg, $transferList, $transferHeads, $serverStats;
    // build content
    $content = "";
    $content .= "<?xml version='1.0' ?>\n\n";
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
		    $content .= "Speed Down: ".$serverStats['speedDown']." || ";
		    $content .= "Speed Up: ".$serverStats['speedUp']." || ";
		    $content .= "Speed Total: ".$serverStats['speedTotal']." || ";
		    $content .= "Connections: ".$serverStats['connections']." || ";
		    $content .= "Free Space: ".$serverStats['freeSpace']." || ";
		    $content .= "Load: ".$serverStats['loadavg'];
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
    global $cfg, $header, $transferList, $transferHeads, $serverStats;
    // build content
    $content = "";
	// server stats
	switch ($type) {
	    case "all":
	    case "server":
	    	if ($header == 1) {
				$content .= 'speedDown' . $cfg['stats_txt_delim'];
				$content .= 'speedUp' . $cfg['stats_txt_delim'];
				$content .= 'speedTotal' . $cfg['stats_txt_delim'];
				$content .= 'connections' . $cfg['stats_txt_delim'];
				$content .= 'freeSpace' . $cfg['stats_txt_delim'];
				$content .= 'loadavg';
				$content .= "\n";
	    	}
			$content .= $serverStats['speedDown'] . $cfg['stats_txt_delim'];
			$content .= $serverStats['speedUp'] . $cfg['stats_txt_delim'];
			$content .= $serverStats['speedTotal'] . $cfg['stats_txt_delim'];
			$content .= $serverStats['connections'] . $cfg['stats_txt_delim'];
			$content .= $serverStats['freeSpace'] . $cfg['stats_txt_delim'];
			$content .= $serverStats['loadavg'];
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
	global $cfg, $serverStats;
	$serverStats = array();
	// speedDown
    $speedDown = "n/a";
	$speedDown = @number_format($cfg["total_download"], 2);
	$serverStats['speedDown'] = $speedDown;
	// speedUp
    $speedUp = "n/a";
	$speedUp =  @number_format($cfg["total_upload"], 2);
	$serverStats['speedUp'] = $speedUp;
	// speedTotal
    $speedTotal = "n/a";
	$speedTotal = @number_format($cfg["total_download"] + $cfg["total_upload"], 2);
	$serverStats['speedTotal'] = $speedTotal;
	// connections
    $connections = "n/a";
	$connections = @netstatConnectionsSum();
	$serverStats['connections'] = $connections;
	// freeSpace
    $freeSpace = "n/a";
	$freeSpace = @formatFreeSpace($cfg["free_space"]);
	$serverStats['freeSpace'] = $freeSpace;
	// loadavg
	$loadavg = "n/a";
	$loadavg = @getLoadAverageString();
	$serverStats['loadavg'] = $loadavg;
}

?>