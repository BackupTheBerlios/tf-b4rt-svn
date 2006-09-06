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
require_once("inc/searchEngines/SearchEngineBase.php");

// create template-instance
$tmpl = getTemplateInstance($cfg["theme"], "torrentSearch.tmpl");

// Go get the if this is a search request. go get the data and produce output.

$hideSeedless = getRequestVar('hideSeedless');
if(!empty($hideSeedless)) {
	$_SESSION['hideSeedless'] = $hideSeedless;
}
if (!isset($_SESSION['hideSeedless'])) {
	$_SESSION['hideSeedless'] = 'no';
}
$hideSeedless = $_SESSION['hideSeedless'];
$pg = getRequestVar('pg');
$searchEngine = getRequestVar('searchEngine');
if (empty($searchEngine)) $searchEngine = $cfg["searchEngine"];
$searchterm = getRequestVar('searchterm');
if(empty($searchterm)) {
	$searchterm = getRequestVar('query');
}
$searchterm = str_replace(" ", "+",$searchterm);
// Check to see if there was a searchterm.
// if not set the get latest flag.
if (strlen($searchterm) == 0) {
	if (! array_key_exists("LATEST",$_REQUEST)) {
		$_REQUEST["LATEST"] = "1";
	}
}
$tmpl->setvar('head', getHead("TorrentSearch ".$cfg['_SEARCH']));
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
$tmpl->setvar('_SEARCH', $cfg['_SEARCH']);
$tmpl->setvar('searchterm', str_replace("+", " ",$searchterm));
$tmpl->setvar('buildSearchEngineDDL', buildSearchEngineDDL($searchEngine));
$tmpl->setloop('buildSearchEngineArray', buildSearchEngineArray($searchEngine));
$tmpl->setvar('searchEngine', $searchEngine);
if (is_file('inc/searchEngines/'.$searchEngine.'Engine.php')) {
	$tmpl->setvar('is_searchEngine', 1);
	include_once('inc/searchEngines/'.$searchEngine.'Engine.php');
	$sEngine = new SearchEngine(serialize($cfg));
	if ($sEngine->initialized) {
		$tmpl->setvar('is_initialized', 1);
		$mainStart = true;
		$catLinks = '';
		$tmpCatLinks = '';
		$tmpLen = 0;
		foreach ($sEngine->getMainCategories() as $mainId => $mainName) {
			if (strlen($tmpCatLinks) >= 500 && $mainStart == false) {
				$catLinks .= $tmpCatLinks . "<br>";
				$tmpCatLinks = '';
				$mainStart = true;
			}
			if ($mainStart == false) $tmpCatLinks .= " | ";
			$tmpCatLinks .= "<a href=\"index.php?iid=torrentSearch&searchEngine=".$searchEngine."&mainGenre=".$mainId."\">".$mainName."</a>";
			$mainStart = false;
		}
		$tmpl->setvar('links_list', $catLinks.$tmpCatLinks);
		if ($mainStart == false) {
			$tmpl->setvar('no_mainStart', 1);
		}
		$mainGenre = getRequestVar('mainGenre');
		if (!empty($mainGenre) && !array_key_exists("subGenre",$_REQUEST)) {
			$tmpl->setvar('no_empty_genre', 1);
			$subCats = $sEngine->getSubCategories($mainGenre);
			if (count($subCats) > 0) {
				$tmpl->setvar('count_subCats', 1);
				$mainGenreName = $sEngine->GetMainCatName($mainGenre);
				$tmpl->setvar('mainGenreName', $mainGenreName);
				$list_cats = array();
				foreach ($subCats as $subId => $subName) {
					array_push($list_cats, array(
						'subId' => $subId,
						'subName' => $subName,
						)
					);
				}
				$tmpl->setloop('list_cats', $list_cats);
			}
			else {
				// Set the Sub to equal the main for groups that don't have subs.
				$_REQUEST["subGenre"] = $mainGenre;
				$tmpl->setvar('getLatest', $sEngine->getLatest());
			}
		}
		else {
			if (array_key_exists("LATEST",$_REQUEST) && $_REQUEST["LATEST"] == "1") {
				$tmpl->setvar('is_latest', 1);
				$tmpl->setvar('getLatest', $sEngine->getLatest());
			}
			else {
				$tmpl->setvar('performSearch', $sEngine->performSearch($searchterm));
			}
		}
	}
	else {
		// there was an error connecting
		$tmpl->setvar('sEngine_msg', $sEngine->msg);
	}
}
$tmpl->setvar('foot', getFoot());
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('iid', $_GET["iid"]);
$tmpl->pparse();

?>