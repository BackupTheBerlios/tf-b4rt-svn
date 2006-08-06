<?php
/* $Id: admin_configSettings.php 102 2006-07-31 05:01:28Z msn_exploder $ */
require_once("AliasFile.php");
require_once("RunningTorrent.php");
$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/admin_configSettings.tmpl");

$tmpl->setvar('head', getHead("Administration - Settings"));
$tmpl->setvar('menu', getMenu());
$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
$tmpl->setvar('table_data_bg', $cfg["table_data_bg"]);
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('path', $cfg["path"]);
$tmpl->setvar('validatePath', validatePath($cfg["path"]));
$tmpl->setvar('_AUTH_BASIC_REALM', _AUTH_BASIC_REALM);
$tmpl->setvar('auth_type', $cfg["auth_type"]);
$tmpl->setvar('btclient', $cfg["btclient"]);
$tmpl->setvar('metainfoclient', $cfg["metainfoclient"]);
$tmpl->setvar('btclient_tornado_bin', $cfg["btclient_tornado_bin"]);
$tmpl->setvar('validate_tornado_bin', validateFile($cfg["btclient_tornado_bin"]));
$tmpl->setvar('btclient_tornado_options', $cfg["btclient_tornado_options"]);
$tmpl->setvar('btclient_transmission_bin', $cfg["btclient_transmission_bin"]);
$tmpl->setvar('validate_transmission_bin', validateFile($cfg["btclient_transmission_bin"]));
$tmpl->setvar('btclient_transmission_options', $cfg["btclient_transmission_options"]);
$tmpl->setvar('btshowmetainfo', $cfg["btshowmetainfo"]);
$tmpl->setvar('validate_btshowmetainfo', validateFile($cfg["btshowmetainfo"]));
$tmpl->setvar('max_upload_rate', $cfg["max_upload_rate"]);
$tmpl->setvar('max_download_rate', $cfg["max_download_rate"]);
$tmpl->setvar('max_uploads', $cfg["max_uploads"]);
$tmpl->setvar('maxcons', $cfg["maxcons"]);
$tmpl->setvar('minport', $cfg["minport"]);
$tmpl->setvar('maxport', $cfg["maxport"]);
$tmpl->setvar('rerequest_interval', $cfg["rerequest_interval"]);
$tmpl->setvar('torrent_dies_when_done', $cfg["torrent_dies_when_done"]);
$tmpl->setvar('sharekill', $cfg["sharekill"]);
$tmpl->setvar('enable_file_priority', $cfg["enable_file_priority"]);
$tmpl->setvar('skiphashcheck', $cfg["skiphashcheck"]);
$tmpl->setvar('enable_umask', $cfg["enable_umask"]);
$tmpl->setvar('nice_adjust', $cfg["nice_adjust"]);

$nice_list = array();
for ($i = 0; $i < 20 ; $i++) {
	if ($cfg["nice_adjust"] == $i) {
		$nice_adjust_true = 1;
	}
	else {
		$nice_adjust_true = 0;
	}
	array_push($nice_list, array(
		'i' => $i,
		'nice_adjust_true' => $nice_adjust_true,
		)
	);
}
$tmpl->setloop('nice_list', $nice_list);

$tmpl->setvar('advanced_start', $cfg["advanced_start"]);
$tmpl->setvar('enable_multiops', $cfg["enable_multiops"]);
$tmpl->setvar('enable_bulkops', $cfg["enable_bulkops"]);
$tmpl->setvar('enable_dereferrer', $cfg["enable_dereferrer"]);
$tmpl->setvar('enable_search', $cfg["enable_search"]);
$tmpl->setvar('buildSearchEngineDDL', buildSearchEngineDDL($cfg["searchEngine"]));
$tmpl->setvar('enable_maketorrent', $cfg["enable_maketorrent"]);
$tmpl->setvar('btmakemetafile', $cfg["btmakemetafile"]);
$tmpl->setvar('validate_btmakemetafile', validateFile($cfg["btmakemetafile"]));
$tmpl->setvar('enable_torrent_download', $cfg["enable_torrent_download"]);
$tmpl->setvar('enable_file_download', $cfg["enable_file_download"]);
$tmpl->setvar('package_type', $cfg["package_type"]);
$tmpl->setvar('enable_view_nfo', $cfg["enable_view_nfo"]);
$tmpl->setvar('enable_mrtg', $cfg["enable_mrtg"]);
$tmpl->setvar('downloadhosts', $cfg["downloadhosts"]);
$tmpl->setvar('days_to_keep', $cfg["days_to_keep"]);
$tmpl->setvar('minutes_to_keep', $cfg["minutes_to_keep"]);
$tmpl->setvar('rss_cache_min', $cfg["rss_cache_min"]);
$tmpl->setvar('enable_rename', $cfg["enable_rename"]);

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

$tmpl->setvar('debug_sql', $cfg["debug_sql"]);
$tmpl->setvar('enable_xfer', $cfg["enable_xfer"]);
$tmpl->setvar('xfer_realtime', $cfg["xfer_realtime"]);
$tmpl->setvar('enable_public_xfer', $cfg["enable_public_xfer"]);
$tmpl->setvar('xfer_total', $cfg["xfer_total"]);
$tmpl->setvar('xfer_month', $cfg["xfer_month"]);
$tmpl->setvar('xfer_week', $cfg["xfer_week"]);
$tmpl->setvar('xfer_day', $cfg["xfer_day"]);
$tmpl->setvar('week_start', $cfg["week_start"]);

$month_list = array();
for ($i = 1; $i <= 31 ; $i++) {
	if ($cfg["month_start"] == $i) {
		$month_start_true = 1;
	}
	else {
		$month_start_true = 0;
	}
	array_push($month_list, array(
		'i' => $i,
		'month_start_true' => $month_start_true,
		)
	);
}
$tmpl->setloop('month_list', $month_list);

$tmpl->setvar('enable_multiupload', $cfg["enable_multiupload"]);
$tmpl->setvar('hack_multiupload_rows', $cfg["hack_multiupload_rows"]);
$tmpl->setvar('enable_dirstats', $cfg["enable_dirstats"]);
$tmpl->setvar('enable_rar', $cfg["enable_rar"]);
$tmpl->setvar('enable_sfvcheck', $cfg["enable_sfvcheck"]);
$tmpl->setvar('enable_wget', $cfg["enable_wget"]);
$tmpl->setvar('showdirtree', $cfg["showdirtree"]);
$tmpl->setvar('maxdepth', $cfg["maxdepth"]);
$tmpl->setvar('enable_restrictivetview', $cfg["enable_restrictivetview"]);
$tmpl->setvar('enable_move', $cfg["enable_move"]);
$tmpl->setvar('getMoveSettingsForm', getMoveSettingsForm());
$tmpl->setvar('bin_grep', $cfg["bin_grep"]);
$tmpl->setvar('validate_grep', validateFile($cfg["bin_grep"]));
$tmpl->setvar('bin_cat', $cfg["bin_cat"]);
$tmpl->setvar('validate_cat', validateFile($cfg["bin_cat"]));
$tmpl->setvar('bin_php', $cfg["bin_php"]);
$tmpl->setvar('validate_php', validateFile($cfg["bin_php"]));
$tmpl->setvar('pythonCmd', $cfg["pythonCmd"]);
$tmpl->setvar('validate_python', validateFile($cfg["pythonCmd"]));
$tmpl->setvar('bin_awk', $cfg["bin_awk"]);
$tmpl->setvar('validate_awk', validateFile($cfg["bin_awk"]));
$tmpl->setvar('bin_du', $cfg["bin_du"]);
$tmpl->setvar('validate_du', validateFile($cfg["bin_du"]));
$tmpl->setvar('bin_wget', $cfg["bin_wget"]);
$tmpl->setvar('validate_wget', validateFile($cfg["bin_wget"]));
$tmpl->setvar('bin_unzip', $cfg["bin_unzip"]);
$tmpl->setvar('validate_unzip', validateFile($cfg["bin_unzip"]));
$tmpl->setvar('bin_cksfv', $cfg["bin_cksfv"]);
$tmpl->setvar('validate_cksfv', validateFile($cfg["bin_cksfv"]));
$tmpl->setvar('php_uname1', php_uname('s'));
$tmpl->setvar('php_uname2', php_uname('r'));
$tmpl->setvar('_OS', _OS);
$tmpl->setvar('bin_unrar', $cfg["bin_unrar"]);
$tmpl->setvar('validate_unrar', validateFile($cfg["bin_unrar"]));
switch (_OS) {
case 1:
	$tmpl->setvar('loadavg_path', $cfg["loadavg_path"]);
	$tmpl->setvar('validate_loadavg', validateFile($cfg["loadavg_path"]));
	$tmpl->setvar('bin_netstat', $cfg["bin_netstat"]);
	$tmpl->setvar('validate_netstat', validateFile($cfg["bin_netstat"]));
break;
case 2:
	$tmpl->setvar('bin_fstat', $cfg["bin_fstat"]);
	$tmpl->setvar('validate_fstat', validateFile($cfg["bin_fstat"]));
	$tmpl->setvar('bin_sockstat', $cfg["bin_sockstat"]);
	$tmpl->setvar('validate_sockstat', validateFile($cfg["bin_sockstat"]));
break;
}
$tmpl->setvar('foot', getFoot(true,true));

$tmpl->pparse();
?>