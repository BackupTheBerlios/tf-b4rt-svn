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

// readrss functions
require_once('inc/functions/functions.readrss.php');

// require
require_once("inc/classes/lastRSS.php");

// Just to be safe ;o)
if (!defined("ENT_COMPAT")) define("ENT_COMPAT", 2);
if (!defined("ENT_NOQUOTES")) define("ENT_NOQUOTES", 0);
if (!defined("ENT_QUOTES")) define("ENT_QUOTES", 3);

// Get RSS feeds from Database
$arURL = GetRSSLinks();

// create lastRSS object
$rss = new lastRSS();

// setup transparent cache
$cacheDir = $cfg['path'].".rsscache";
if (!checkDirectory($cacheDir, 0777))
	showErrorPage("Error with rss-cache-dir ".$cacheDir);
$rss->cache_dir = $cacheDir;
$rss->cache_time = $cfg["rss_cache_min"] * 60; // 1200 = 20 min.  3600 = 1 hour
$rss->strip_html = false; // don't remove HTML from the description

// create template-instance
$tmpl = tmplGetInstance($cfg["theme"], "page.readrss.tmpl");

// set vars
// Loop through each RSS feed
$rss_list = array();
foreach ($arURL as $rid => $url) {
	if ($rs = $rss->get($url)) {
		if(!empty( $rs["items"])) {
			// Cache rss feed so we don't have to call it again
			$rssfeed[] = $rs;
			$stat = 1;
		} else {
			$rssfeed[] = "";
			$stat = 2;
		}
	} else {
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
		if (!empty($rs["items"])) {
			// get Site title and Page Link
			$title = $rs["title"];
			$pageUrl = $rs["link"];
			$content = "";
			for ($i=0; $i < count($rs["items"]); $i++) {
				$link = $rs["items"][$i]["link"];
				$title2 = $rs["items"][$i]["title"];
				$pubDate = (!empty($rs["items"][$i]["pubDate"])) ? $rs["items"][$i]["pubDate"] : "Unknown";
				// RSS entry needs to have a link, otherwise pointless
				if (empty($link))
					continue;
				if ($link != "" && $title2 !="")
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
if (isset($pageUrl))
	$tmpl->setvar('pageUrl', $pageUrl);
else
	$tmpl->setvar('pageUrl', "");
if (isset($title))
	$tmpl->setvar('title', $title);
else
	$tmpl->setvar('title', "");
if (isset($rid))
	$tmpl->setvar('rid', $rid);
else
	$tmpl->setvar('rid', "");
//
$tmpl->setvar('_TRANSFERFILE',$cfg['_TRANSFERFILE']);
$tmpl->setvar('_TIMESTAMP', $cfg['_TIMESTAMP']);
//
$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
//
tmplSetTitleBar($cfg["pagetitle"].' - RSS Torrents');
tmplSetFoot();
$tmpl->setvar('iid', $_GET["iid"]);

// parse template
$tmpl->pparse();

?>