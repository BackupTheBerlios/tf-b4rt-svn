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
	header("location: ../../../index.php");
	exit();
}

/******************************************************************************/

// readrss functions
require_once('inc/functions/functions.readrss.php');

// init template-instance
tmplInitializeInstance($cfg["theme"], "page.admin.default.tmpl");

// set vars
$tmpl->setvar('enable_xfer', $cfg["enable_xfer"]);
tmplSetTitleBar($cfg['_ADMINISTRATION']);
tmplSetAdminMenu();
// L: server-stats 1
// transfers
$arTransfers = getTorrentListFromFS();
$countTransfers = count($arTransfers);
$tmpl->setvar('server_transfers_total', $countTransfers);
// users
$arUsers = GetUsers();
$countUsers = count($arUsers);
$tmpl->setvar('server_users_total', $countUsers);
// activity
$arActivity = GetActivityCount();
$countActivity = count($arActivity);
$tmpl->setvar('server_activity_total', $countActivity);
// hits
$hits = $db->GetOne("SELECT SUM(hits) AS hits FROM tf_users");
$tmpl->setvar('server_hits_total', $hits);
// log
$log = $db->GetOne("SELECT COUNT(cid) AS cid FROM tf_log");
$tmpl->setvar('server_logs_total', $log);
// messages
$messages = $db->GetOne("SELECT COUNT(mid) AS mid FROM tf_messages");
$tmpl->setvar('server_messages_total', $messages);
// links
$arLinks = GetLinks();
$countLinks = count($arLinks);
$tmpl->setvar('server_links_total', $countLinks);
// rss
$arRss = GetRSSLinks();
$countRss = count($arRss);
$tmpl->setvar('server_rss_total', $countRss);
// cookies
$cookies = $db->GetOne("SELECT COUNT(cid) AS cid FROM tf_cookies");
$tmpl->setvar('server_cookies_total', $cookies);
// profiles
$profiles = $db->GetOne("SELECT COUNT(id) AS id FROM tf_trprofiles");
$tmpl->setvar('server_profiles_total', $profiles);
// M: server-stats 2
// search-engines
$arSearchEngines = buildSearchEngineArray();
$countSearchEngines = count($arSearchEngines);
$tmpl->setvar('server_searchengines_total', $countSearchEngines);
// themes
$arThemes = GetThemes();
$countThemes = count($arThemes);
$tmpl->setvar('server_themes_total', $countThemes);
// themes standard
$arThemesStandard = GetThemesStandard();
$countThemesStandard = count($arThemesStandard);
$tmpl->setvar('server_themes_standard_total', $countThemesStandard);
// languages
$arLang = GetLanguages();
$countLang = count($arLang);
$tmpl->setvar('server_lang_total', $countLang);
// du
switch ($cfg["_OS"]) {
	case 1: //Linux
		$duArg = "-D";
		break;
	case 2: //BSD
		$duArg = "-L";
		break;
}
$du = @shell_exec($cfg['bin_du']." -ch ".$duArg." ".escapeshellarg($cfg['docroot'])." | ".$cfg['bin_grep']." \"total\"");
$tmpl->setvar('server_du_total', substr($du, 0, -7));
// version
$tmpl->setvar('server_version', $cfg["version"]);
// R: db-settings
$tmpl->setvar('db_type', $cfg["db_type"]);
$tmpl->setvar('db_host', $cfg["db_host"]);
$tmpl->setvar('db_name', $cfg["db_name"]);
$tmpl->setvar('db_user', $cfg["db_user"]);
if ($cfg["db_pcon"])
	$tmpl->setvar('db_pcon', "true");
else
	$tmpl->setvar('db_pcon', "false");
// foot
tmplSetFoot();

// set iid-var
$tmpl->setvar('iid', $_REQUEST["iid"]);

// parse template
$tmpl->pparse();

?>