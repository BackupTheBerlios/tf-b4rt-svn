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

// create template-instance
$tmpl = tmplGetInstance($cfg["theme"], "admin/webappSettings.tmpl");

// set vars
$tmpl->setvar('auth_type', $cfg["auth_type"]);
$tmpl->setvar('auth_basic_realm', $cfg["auth_basic_realm"]);
$tmpl->setvar('enable_tmpl_cache', $cfg["enable_tmpl_cache"]);
$tmpl->setvar('SuperAdminLink_tmplCache', getSuperAdminLink('?m=23','clean template-cache'));
$tmpl->setvar('enable_dereferrer', $cfg["enable_dereferrer"]);
$tmpl->setvar('days_to_keep', $cfg["days_to_keep"]);
$tmpl->setvar('minutes_to_keep', $cfg["minutes_to_keep"]);
$tmpl->setvar('rss_cache_min', $cfg["rss_cache_min"]);
$tmpl->setvar('servermon_update', $cfg["servermon_update"]);
$tmpl->setvar('debug_sql', $cfg["debug_sql"]);
$tmpl->setvar('ui_displayfluxlink', $cfg["ui_displayfluxlink"]);
$tmpl->setvar('downloadhosts', $cfg["downloadhosts"]);
$tmpl->setvar('details_type', $cfg["details_type"]);
$tmpl->setvar('details_update', $cfg["details_update"]);
// themes
$theme_list = array();
$arThemes = GetThemes();
for($inx = 0; $inx < sizeof($arThemes); $inx++) {
	$selected = "";
	if ($cfg["default_theme"] == $arThemes[$inx]) {
		$selected = "selected";
	}
	array_push($theme_list, array(
		'arThemes' => $arThemes[$inx],
		'selected' => $selected,
		)
	);
}
$tmpl->setloop('theme_list', $theme_list);
// tf standard themes
$arThemes = GetThemesStandard();
$tfstandard_theme_list = array();
for($inx = 0; $inx < sizeof($arThemes); $inx++) {
	$selected = "";
	$arThemes2[$inx] = "tf_standard_themes/".$arThemes[$inx];
	if ($cfg["theme"] == $arThemes2[$inx]) {
		$selected = "selected";
	}
	array_push($tfstandard_theme_list, array(
		'arThemes' => $arThemes[$inx],
		'arThemes2' => $arThemes2[$inx],
		'selected' => $selected,
		)
	);
}
$tmpl->setloop('tfstandard_theme_list', $tfstandard_theme_list);
// languages
$lang_list = array();
$arLanguage = GetLanguages();
for($inx = 0; $inx < sizeof($arLanguage); $inx++) {
	$selected = "";
	if ($cfg["default_language"] == $arLanguage[$inx]) {
		$selected = "selected";
	}
	array_push($lang_list, array(
		'arLanguage' => $arLanguage[$inx],
		'selected' => $selected,
		'GetLanguageFromFile' => GetLanguageFromFile($arLanguage[$inx]),
		)
	);
}
$tmpl->setloop('lang_list', $lang_list);
//
$tmpl->setvar('menu', getMenu());
tmplSetTitleBar("Administration - WebApp Settings");
$tmpl->setvar('foot', getFoot(true));
$tmpl->setvar('iid', $_GET["iid"]);

// parse template
$tmpl->pparse();

?>