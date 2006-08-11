<?php
/* $Id$ */
require_once("AliasFile.php");
require_once("RunningTorrent.php");
require_once("searchEngines/SearchEngineBase.php");

# create new template
if (!ereg('^[^./][^/]*$', $cfg["theme"])) {
	$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/admin_searchSettings.tmpl");
}
else {
	$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/admin_searchSettings.tmpl");
}
$tmpl->setvar('head', getHead("Administration - Search Settings"));
$tmpl->setvar('menu', getMenu());
$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
$tmpl->setvar('table_data_bg', $cfg["table_data_bg"]);
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
$tmpl->setvar('theme', $cfg["theme"]);

$searchEngine = getRequestVar('searchEngine');
if (empty($searchEngine)) {
	$searchEngine = $cfg["searchEngine"];
}
$tmpl->setvar('buildSearchEngineDDL', buildSearchEngineDDL($searchEngine,true));

if (is_file('searchEngines/'.$searchEngine.'Engine.php')) {
	include_once('searchEngines/'.$searchEngine.'Engine.php');
	$sEngine = new SearchEngine(serialize($cfg));
	if ($sEngine->initialized) {
		$tmpl->setvar('is_file', 1);
		$tmpl->setvar('mainTitle', $sEngine->mainTitle);
		$tmpl->setvar('searchEngine', $searchEngine);
		$tmpl->setvar('mainURL', $sEngine->mainURL);
		$tmpl->setvar('author', $sEngine->author);
		$tmpl->setvar('version', $sEngine->version);

		if(strlen($sEngine->updateURL)>0) {
			$tmpl->setvar('update_pos', 1);
			$tmpl->setvar('updateURL', $sEngine->updateURL);
		}
		if (! $sEngine->catFilterName == '') {
			$tmpl->setvar('cat_pos', 1);
			$tmpl->setvar('catFilterName', $sEngine->catFilterName);
			$cats = array();
			foreach ($sEngine->getMainCategories(false) as $mainId => $mainName) {
				if (@in_array($mainId, $sEngine->catFilter)) {
					$in_array = 1;
				}
				else {
					$in_array = 0;
				}
				array_push($cats, array(
					'mainId' => $mainId,
					'in_array' => $in_array,
					'mainName' => $mainName,
					)
				);
			}
			$tmpl->setloop('cats', $cats);
		}
	}
}
$tmpl->setvar('foot', getFoot(true,true));

$tmpl->pparse();
?>