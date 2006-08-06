<?php

/* $Id$ */

/*************************************************************
*  TorrentFlux - PHP Torrent Manager
*  www.torrentflux.com
**************************************************************/
/*
	This file is part of TorrentFlux.

	TorrentFlux is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	TorrentFlux is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with TorrentFlux; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

require_once("config.php");
require_once("functions.php");
require_once("searchEngines/SearchEngineBase.php");
require_once("lib/vlib/vlibTemplate.php");

$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/torrentSearch.tmpl");

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
$tmpl->setvar('head', getHead("TorrentSearch "._SEARCH));
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
$tmpl->setvar('_SEARCH', _SEARCH);
$tmpl->setvar('searchterm', str_replace("+", " ",$searchterm));
$tmpl->setvar('buildSearchEngineDDL', buildSearchEngineDDL($searchEngine));
$tmpl->setvar('buildSearchEngineLinks', buildSearchEngineLinks($searchEngine));
$tmpl->setvar('searchEngine', $searchEngine);
if (is_file('searchEngines/'.$searchEngine.'Engine.php')) {
	$tmpl->setvar('is_searchEngine', 1);
	include_once('searchEngines/'.$searchEngine.'Engine.php');
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
			$tmpCatLinks .= "<a href=\"torrentSearch.php?searchEngine=".$searchEngine."&mainGenre=".$mainId."\">".$mainName."</a>";
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

$tmpl->pparse();
?>