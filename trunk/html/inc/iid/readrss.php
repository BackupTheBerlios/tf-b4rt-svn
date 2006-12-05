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
	header("location: ../../index.php");
	exit();
}

/******************************************************************************/

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

// init template-instance
tmplInitializeInstance($cfg["theme"], "page.readrss.tmpl");

// set vars
// Loop through each RSS feed
$rss_list = array();
foreach ($arURL as $rid => $url) {
	if(isset($_REQUEST["debug"])){$rss->cache_time=0;}
	if (($rs = $rss->get($url))) {
		if(!empty( $rs["items"])) {
			// Check this feed has a title tag:
			if(!isset($rs["title"]) || empty($rs["title"])){
				$rs["title"] = "Feed URL ".htmlentities($url, ENT_QUOTES)." Note: this feed does not have a valid 'title' tag";
			}

			// Check each item in this feed has link, title and publication date:
			for ($i=0; $i < count($rs["items"]); $i++) {
				// Don't include feed items without a link:
				if(!isset($rs["items"][$i]["link"]) || empty($rs["items"][$i]["link"])){
					array_splice ($rs["items"], $i, 1);

					// Continue to next feed item:
					continue;
				}

				// Check item's pub date:
				if(empty($rs["items"][$i]["pubDate"]) || empty($rs["items"][$i]["pubDate"])){
					$rs["items"][$i]["pubDate"] = "Unknown publication date";
				}

				// Check item's description:
				if(empty($rs["items"][$i]["description"]) || !isset($rs["items"][$i]["description"])){
					$rs["items"][$i]["description"] = "Unknown feed item: ".html_entity_decode($rs["items"][$i]["link"]);
				}
			}
			$stat = 1;
		} else {
			// feed URL is valid and active, but no feed items were found:
			$stat = 2;
		}
	} else {
		// Unable to grab RSS feed, must of timed out
		$stat = 3;
	}

	array_push($rss_list, array(
		'stat' => $stat,
		'rid' => $rid,
		'title' => (isset($rs["title"]) ? $rs["title"] : ""),
		'url' => $url,
		'feedItems' => $rs['items']
		)
	);
}
//var_dump($rss_list);exit;
$tmpl->setloop('rss_list', $rss_list);

//
$tmpl->setvar('_TRANSFERFILE',$cfg['_TRANSFERFILE']);
$tmpl->setvar('_TIMESTAMP', $cfg['_TIMESTAMP']);
//
$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
//
tmplSetTitleBar($cfg["pagetitle"].' - RSS Torrents');
tmplSetFoot();
$tmpl->setvar('iid', $_REQUEST["iid"]);

// parse template
$tmpl->pparse();

?>