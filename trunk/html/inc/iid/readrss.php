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

// require
require_once("inc/classes/lastRSS.php");

// create template-instance
$tmpl = getTemplateInstance($cfg["theme"], "readrss.tmpl");

// check http://varchars.com/rss/ for feeds

// The following is for PHP < 4.3
if (!function_exists('html_entity_decode'))
{
	function html_entity_decode($string, $opt = ENT_COMPAT)
	{
		$trans_tbl = get_html_translation_table (HTML_ENTITIES);
		$trans_tbl = array_flip ($trans_tbl);

		if ($opt & 1)
		{
			// Translating single quotes
			// Add single quote to translation table;
			// doesn't appear to be there by default
			$trans_tbl["&apos;"] = "'";
		}

		if (!($opt & 2))
		{
			// Not translating double quotes
			// Remove double quote from translation table
			unset($trans_tbl["&quot;"]);
		}

		return strtr ($string, $trans_tbl);
	}
}

// Just to be safe ;o)
if (!defined("ENT_COMPAT")) define("ENT_COMPAT", 2);
if (!defined("ENT_NOQUOTES")) define("ENT_NOQUOTES", 0);
if (!defined("ENT_QUOTES")) define("ENT_QUOTES", 3);

$tmpl->setvar('head', getHead("RSS Torrents"));
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);

// Get RSS feeds from Database
$arURL = GetRSSLinks();

// create lastRSS object
$rss = new lastRSS();

// setup transparent cache
$rss->cache_dir = $cfg["torrent_file_path"];
$rss->cache_time = $cfg["rss_cache_min"] * 60; // 1200 = 20 min.  3600 = 1 hour
$rss->strip_html = false; // don't remove HTML from the description

// Loop through each RSS feed
$rss_list = array();
foreach( $arURL as $rid => $url ) {
	if( $rs = $rss->get( $url ) ) {
		if( !empty( $rs["items"] ) ) {
			// Cache rss feed so we don't have to call it again
			$rssfeed[] = $rs;
			$stat = 1;
		}
		else {
			$rssfeed[] = "";
			$stat = 2;
		}
	}
	else {
		// Unable to grab RSS feed, must of timed out
		$rssfeed[] = "";
		$stat = 3;
	}
	array_push($rss_list, array(
		'stat' => $stat,
		'rid' => $rid,
		'title' => $rs["title"],
		'url' => $url,
		)
	);
}
$tmpl->setloop('rss_list', $rss_list);

// Parse through cache RSS feed
if (isset($rssfeed) && is_array($rssfeed)) {
	$news_list = array();
	foreach( $rssfeed as $rid => $rs ) {
		$title = "";
		$content = "";
		$pageUrl = "";
		if( !empty( $rs["items"] ) ) {
			// get Site title and Page Link
			$title = $rs["title"];
			$pageUrl = $rs["link"];
			$content = "";
			for ($i=0; $i < count($rs["items"]); $i++) {
				$link = $rs["items"][$i]["link"];
				$title2 = $rs["items"][$i]["title"];
				$pubDate = (!empty($rs["items"][$i]["pubDate"])) ? $rs["items"][$i]["pubDate"] : "Unknown";
				// RSS entry needs to have a link, otherwise pointless
				if( empty( $link ) )
					continue;
				if($link != "" && $title2 !="")
					$content .= "<tr><td><img src=\"themes/".$cfg['theme']."/images/download_owner.gif\" width=\"16\" height=\"16\" title=\"".$link."\"><a href=\"index.php?iid=index&url_upload=".$link."\">".$title2."</a></td><td> ".$pubDate."</td></tr>\n";
				else
					$content .= "<tr><td  class=\"tiny\"><img src=\"themes/".$cfg['theme']."/images/download_owner.gif\" width=\"16\" height=\"16\">".ScrubDescription(str_replace("Torrent: <a href=\"", "Torrent: <a href=\"index.php?iid=index&url_upload=", html_entity_decode($rs["items"][$i]["description"])), $title2)."</td><td valign=\"top\">".$pubDate."</td></tr>";
			}
		} else {
			// Request timed out, display timeout message
			$tmpl->setvar('timeout_rss', 1);
			$tmpl->setvar('url', $url);
		}
		if ($content != "") // Close the content and add a line break
			$content .= "<br>";
		array_push($news_list, array(
			'content' => $content,
			)
		);
	}
}
if (isset($news_list))
	$tmpl->setloop('news_list', $news_list);
$tmpl->setvar('foot', getFoot());
if (isset($rid))
	$tmpl->setvar('rid', $rid);
if (isset($pageUrl))
	$tmpl->setvar('pageUrl', $pageUrl);
if (isset($title))
	$tmpl->setvar('title', $title);

$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
$tmpl->setvar('table_data_bg', $cfg["table_data_bg"]);
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('_TRANSFERFILE',_TRANSFERFILE);
$tmpl->setvar('_TIMESTAMP', _TIMESTAMP);

// Scrub the description to take out the ugly long URLs
function ScrubDescription($desc, $title) {
	$rtnValue = "";
	$parts = explode("</a>", $desc);
	$replace = ereg_replace('">.*$', '">'.$title."</a>", $parts[0]);
	if (strpos($parts[1], "Search:") !== false)
	{
		$parts[1] = $parts[1]."</a>\n";
	}
	for($inx = 2; $inx < count($parts); $inx++)
	{
		if (strpos($parts[$inx], "Info: <a ") !== false)
		{
			// We have an Info: and URL to clean
			$parts[$inx] = ereg_replace('">.*$', '" target="_blank">Read More...</a>', $parts[$inx]);
		}
	}
	$rtnValue = $replace;
	for ($inx = 1; $inx < count($parts); $inx++)
	{
		$rtnValue .= $parts[$inx];
	}
	return $rtnValue;
}

$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('iid', $_GET["iid"]);
# lets parse the hole thing
$tmpl->pparse();

?>