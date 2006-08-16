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


# create new template
if (!ereg('^[^./][^/]*$', $cfg["theme"])) {
	$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/profile.tmpl");
}
else {
	$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/profile.tmpl");
}

$op = getRequestVar('op');

switch ($op) {
	default:
//******************************************************************************
// showIndex -- main view
//******************************************************************************
		$tmpl->setvar('showIndex', 1);
		global $cfg, $db;
		$hideChecked = "";
		if ($cfg["hide_offline"] == 1) {
			$hideChecked = "checked";
		}
		$tmpl->setvar('head', getHead($cfg["user"]."'s "._PROFILE));
		$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
		$tmpl->setvar('table_data_bg', $cfg["table_data_bg"]);
		$tmpl->setvar('theme', $cfg["theme"]);
		$tmpl->setvar('user', $cfg["user"]);
		$tmpl->setvar('_PROFILE', _PROFILE);

		$total_activity = GetActivityCount();

		$sql= "SELECT user_id, hits, last_visit, time_created, user_level FROM tf_users WHERE user_id=".$db->qstr($cfg["user"]);
		list($user_id, $hits, $last_visit, $time_created, $user_level) = $db->GetRow($sql);

		$user_type = _NORMALUSER;
		if (IsAdmin()) {
			$user_type = _ADMINISTRATOR;
		}
		if (IsSuperAdmin()) {
			$user_type = _SUPERADMIN;
		}

		$user_activity = GetActivityCount($cfg["user"]);

		if ($user_activity == 0) {
			$user_percent = 0;
		} else {
			$user_percent = number_format(($user_activity/$total_activity)*100);
		}
		$tmpl->setvar('_JOINED', _JOINED);
		$tmpl->setvar('time_created1', date(_DATETIMEFORMAT, $time_created));
		$tmpl->setvar('_UPLOADPARTICIPATION', _UPLOADPARTICIPATION);
		$tmpl->setvar('width1', $user_percent*2);
		$tmpl->setvar('width2', (200 - ($user_percent*2)));
		$tmpl->setvar('_UPLOADS', _UPLOADS);
		$tmpl->setvar('user_activity', $user_activity);
		$tmpl->setvar('_PERCENTPARTICIPATION', _PERCENTPARTICIPATION);
		$tmpl->setvar('user_percent', $user_percent);
		$tmpl->setvar('_PARTICIPATIONSTATEMENT', _PARTICIPATIONSTATEMENT);
		$tmpl->setvar('days_to_keep', $cfg["days_to_keep"]);
		$tmpl->setvar('_DAYS', _DAYS);
		$tmpl->setvar('_TOTALPAGEVIEWS', _TOTALPAGEVIEWS);
		$tmpl->setvar('hits', $hits);
		$tmpl->setvar('_USERTYPE', _USERTYPE);
		$tmpl->setvar('user_type', $user_type);
		$tmpl->setvar('_USER', _USER);
		$tmpl->setvar('user', $cfg["user"]);
		$tmpl->setvar('_NEWPASSWORD', _NEWPASSWORD);
		$tmpl->setvar('_CONFIRMPASSWORD', _CONFIRMPASSWORD);
		$tmpl->setvar('_THEME', _THEME);
		$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
		$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);

		$arThemes = GetThemes();
		$theme_list = array();
		for($inx = 0; $inx < sizeof($arThemes); $inx++) {
			$selected = "";
			if ($cfg["theme"] == $arThemes[$inx]) {
				$selected = "selected";
			}
			array_push($theme_list, array(
				'arThemes' => $arThemes[$inx],
				'selected' => $selected,
				)
			);
		}
		$tmpl->setloop('theme_list', $theme_list);
		
		# Old style themes
		$arThemes = Get_old_Themes();
		$old_theme_list = array();
		for($inx = 0; $inx < sizeof($arThemes); $inx++) {
			$selected = "";
			$arThemes2[$inx] = "old_style_themes/".$arThemes[$inx];
			if ($cfg["theme"] == $arThemes2[$inx]) {
				$selected = "selected";
			}
			array_push($old_theme_list, array(
				'arThemes' => $arThemes[$inx],
				'arThemes2' => $arThemes2[$inx],
				'selected' => $selected,
				)
			);
		}
		$tmpl->setloop('old_theme_list', $old_theme_list);
		$tmpl->setvar('_LANGUAGE', _LANGUAGE);

		$arLanguage = GetLanguages();
		$language_list = array();
		for($inx = 0; $inx < sizeof($arLanguage); $inx++) {
			$selected = "";
			if ($cfg["language_file"] == $arLanguage[$inx]) {
				$selected = "selected";
			}
			array_push($language_list, array(
				'arLanguage' => $arLanguage[$inx],
				'selected' => $selected,
				'language_file' => GetLanguageFromFile($arLanguage[$inx]),
				)
			);
		}
		$tmpl->setloop('language_list', $language_list);
		$tmpl->setvar('hideChecked', $hideChecked);
		$tmpl->setvar('_HIDEOFFLINEUSERS', _HIDEOFFLINEUSERS);
		$tmpl->setvar('_UPDATE', _UPDATE);
		$tmpl->setvar('table_border_dk', $cfg["table_border_dk"]);
		$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
		$tmpl->setvar('index_page', $cfg["index_page"]);
		$tmpl->setvar('ui_dim_main_w', $cfg["ui_dim_main_w"]);
		$tmpl->setvar('ui_displaylinks', $cfg["ui_displaylinks"]);
		$tmpl->setvar('ui_displayusers', $cfg["ui_displayusers"]);
		$tmpl->setvar('drivespacebar', $cfg["drivespacebar"]);
		$tmpl->setvar('index_page_stats', $cfg["index_page_stats"]);
		$tmpl->setvar('show_server_load', $cfg["show_server_load"]);
		$tmpl->setvar('index_page_connections', $cfg["index_page_connections"]);
		$tmpl->setvar('ui_indexrefresh', $cfg["ui_indexrefresh"]);
		$tmpl->setvar('pagerefresh', $cfg["page_refresh"]);
		$tmpl->setvar('enable_sorttable', $cfg["enable_sorttable"]);
		$tmpl->setvar('enable_bigboldwarning', $cfg["enable_bigboldwarning"]);
		$tmpl->setvar('enable_goodlookstats', $cfg["enable_goodlookstats"]);
		$tmpl->setvar('buildSearchEngineDDL', buildSearchEngineDDL($cfg["searchEngine"]));
		$tmpl->setvar('enable_move', $cfg["enable_move"]);
		$tmpl->setvar('_PASSWORDLENGTH', _PASSWORDLENGTH);
		$tmpl->setvar('_PASSWORDNOTMATCH', _PASSWORDNOTMATCH);
		$tmpl->setvar('_PLEASECHECKFOLLOWING', _PLEASECHECKFOLLOWING);
		$tmpl->setvar('foot', getFoot());
	break;

//******************************************************************************
// updateProfile -- update profile
//******************************************************************************
	case "updateProfile":
		$pass1 = getRequestVar('pass1');
		$pass2 = getRequestVar('pass2');
		$hideOffline = getRequestVar('hideOffline');
		$theme = getRequestVar('theme');
		$language = getRequestVar('language');
		global $cfg;
		$tmpl->setvar('updateProfile', 1);

		if ($pass1 != "")
		{
			$_SESSION['user'] = md5($cfg["pagetitle"]);
		}

		UpdateUserProfile($cfg["user"], $pass1, $hideOffline, $theme, $language);

		$tmpl->setvar('head', getHead($cfg["user"]."'s "._PROFILE));
		$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
		$tmpl->setvar('table_data_bg', $cfg["table_data_bg"]);
		$tmpl->setvar('theme', $cfg["theme"]);
		$tmpl->setvar('user', $cfg["user"]);
		$tmpl->setvar('_PROFILE', _PROFILE);
		$tmpl->setvar('_PROFILEUPDATEDFOR', _PROFILEUPDATEDFOR);
		$tmpl->setvar('foot', getFoot());
	break;

//******************************************************************************
// ShowCookies -- show cookies for user
//******************************************************************************
	case "showCookies":
	case "editCookies":
		global $cfg, $db;
		$tmpl->setvar('ShowCookies', 1);

		$tmpl->setvar('head', getHead($cfg["user"] . "'s "._PROFILE));

		$cid = @ $_GET["cid"]; // Cookie ID

		// Used for when editing a cookie
		$hostvalue = $datavalue = "";
		if( !empty( $cid ) ) {
			// Get cookie information from database
			$cookie = getCookie( $cid );
			$hostvalue = " value=\"" . $cookie['host'] . "\"";
			$datavalue = " value=\"" . $cookie['data'] . "\"";
		}

		(!empty( $cid )) ? $op2 = "modCookie" : $op2 = "addCookie";
		$tmpl->setvar('op', $op2);
		$tmpl->setvar('cid', $cid);
		$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
		$tmpl->setvar('table_data_bg', $cfg["table_data_bg"]);
		$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
		$tmpl->setvar('theme', $cfg["theme"]);
		$tmpl->setvar('hostvalue', $hostvalue);
		$tmpl->setvar('datavalue', $datavalue);
		(!empty( $cid )) ? $add1 = "_UPDATE" : $add1 = "Add";
		$tmpl->setvar('add1', $add1);

		// We are editing a cookie, so have a link back to cookie list
		if( !empty( $cid ) ) {
			$tmpl->setvar('empty_cid', 1);
		}
		else {
			// Output the list of cookies in the database
			$sql = "SELECT c.cid, c.host, c.data FROM tf_cookies AS c, tf_users AS u WHERE u.uid=c.uid AND u.user_id='" . $cfg["user"] . "'";
			$dat = $db->GetAll( $sql );
			if( empty( $dat ) ) {
				$tmpl->setvar('empty_dat', 1);
			}
			else {
				$cookie_data = array();
				$tmpl->setvar('_DELETE', _DELETE);
				$tmpl->setvar('_EDIT', _EDIT);
				foreach( $dat as $cookie ) {
					array_push($cookie_data, array(
						'cid' => $cookie["cid"],
						'host' => $cookie["host"],
						'data' => $cookie["data"],
						)
					);
				}
			$tmpl->setloop('cookie_data', $cookie_data);
			}
		}
		$tmpl->setvar('foot', getFoot());
	break;

//******************************************************************************
// updateSettingsUser -- update per user settings
//******************************************************************************
	case "updateSettingsUser":
		global $cfg;
		$settings = processSettingsParams();
		saveUserSettings($cfg["uid"],$settings);
		AuditAction( $cfg["constants"]["admin"], "updated per user settings for ".$cfg["user"]);
		header( "location: index.php?page=profile" );
	break;

//******************************************************************************
// addCookie -- adding a Cookie Host Information
//******************************************************************************
	case "addCookie":
		$newCookie["host"] = getRequestVar('host');
		$newCookie["data"] = getRequestVar('data');
		if( !empty( $newCookie ) ) {
			global $cfg;
			AddCookieInfo( $newCookie );
			AuditAction( $cfg["constants"]["admin"], "New Cookie: " . $newCookie["host"] . " | " . $newCookie["data"] );
		}
		header( "location: index.php?page=profile&op=showCookies" );
	break;

//******************************************************************************
// deleteCookie -- delete a Cookie Host Information
//******************************************************************************
	case "deleteCookie":
		$cid = $_GET["cid"];
		global $cfg;
		$cookie = getCookie( $cid );
		deleteCookieInfo( $cid );
		AuditAction( $cfg["constants"]["admin"], _DELETE . " Cookie: " . $cookie["host"] );
		header( "location: index.php?page=profile&op=showCookies" );
	break;

//******************************************************************************
// modCookie -- edit a Cookie Host Information
//******************************************************************************
	case "modCookie":
		$newCookie["host"] = getRequestVar( 'host' );
		$newCookie["data"] = getRequestVar( 'data' );
		$cid = getRequestVar( 'cid' );
		global $cfg;
		modCookieInfo($cid,$newCookie);
		AuditAction($cfg["constants"]["admin"], "Modified Cookie: ".$newCookie["host"]." | ".$newCookie["data"]);
		header("location: index.php?page=profile&op=showCookies");
	break;

//******************************************************************************
// ShowProfiles -- show cookies for user
//******************************************************************************
	case "showProfiles":
	case "editProfiles":
		global $cfg, $db;
		$tmpl->setvar('ShowProfiles', 1);
		
		$pid = @ $_GET["pid"];
		
		(!empty( $pid )) ? $add1 = "_UPDATE" : $add1 = "Add";
		$tmpl->setvar('add1', $add1);
		(!empty( $pid )) ? $op2 = "modProfile" : $op2 = "addProfile";
		$tmpl->setvar('op', $op2);
		
		$title = $minport = $maxport = $maxcons = $rerequest_interval = $max_upload_rate = $max_uploads = $max_download_rate = $dont_stop = $sharekill = $btclient = "";
		if( !empty( $pid ) ) {
			$profile = getProfile( $pid );
			$title = " value=\"" . $profile['title'] . "\"";
			$minport = " value=\"" . $profile['minport'] . "\"";
			$maxport = " value=\"" . $profile['maxport'] . "\"";
			$maxcons = " value=\"" . $profile['maxcons'] . "\"";
			$rerequest_interval = " value=\"" . $profile['rerequest_interval'] . "\"";
			$max_upload_rate = " value=\"" . $profile['max_upload_rate'] . "\"";
			$max_uploads = " value=\"" . $profile['max_uploads'] . "\"";
			$max_download_rate = " value=\"" . $profile['max_download_rate'] . "\"";
			$dont_stop = $profile['dont_stop'];
			$sharekill = " value=\"" . $profile['sharekill'] . "\"";
			$btclient = $profile['btclient'];
		}
		$tmpl->setvar('title', $title);
		$tmpl->setvar('minport', $minport);
		$tmpl->setvar('maxport', $maxport);
		$tmpl->setvar('maxcons', $maxcons);
		$tmpl->setvar('rerequest_interval', $rerequest_interval);
		$tmpl->setvar('max_upload_rate', $max_upload_rate);
		$tmpl->setvar('max_uploads', $max_uploads);
		$tmpl->setvar('max_download_rate', $max_download_rate);
		$tmpl->setvar('dont_stop', $dont_stop);
		$tmpl->setvar('sharekill', $sharekill);
		$tmpl->setvar('btclient', $btclient);

		$tmpl->setvar('head', getHead($cfg["user"] . "'s "._PROFILE));
		$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
		$tmpl->setvar('table_data_bg', $cfg["table_data_bg"]);
		$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
		$tmpl->setvar('theme', $cfg["theme"]);
		$tmpl->setvar('pid', $pid);
		if( !empty( $pid ) ) {
			$tmpl->setvar('empty_pid', 1);
		}
		else {
			// Output the list of profiles in the database
			$sql = "SELECT id, title FROM tf_dlprofiles WHERE user_id LIKE '" . $cfg["user"] . "'";
			$dat = $db->GetAll( $sql );
			if( empty( $dat ) ) {
				$tmpl->setvar('empty_dat', 1);
			}
			else {
				$profile_data = array();
				$tmpl->setvar('_DELETE', _DELETE);
				$tmpl->setvar('_EDIT', _EDIT);
				foreach( $dat as $profile ) {
					array_push($profile_data, array(
						'pid' => $profile["id"],
						'title' => $profile["title"],
						)
					);
				}
				$tmpl->setloop('profile_data', $profile_data);
			}
		}
		$tmpl->setvar('pid', $pid);
		$tmpl->setvar('foot', getFoot());
	break;
	
//******************************************************************************
// addProfile -- adding a Profile Information
//******************************************************************************
	case "addProfile":
		$newProfile["title"] = getRequestVar('title');
		$newProfile["minport"] = getRequestVar('minport');
		$newProfile["maxport"] = getRequestVar('maxport');
		$newProfile["maxcons"] = getRequestVar('maxcons');
		$newProfile["rerequest_interval"] = getRequestVar('rerequest_interval');
		$newProfile["max_upload_rate"] = getRequestVar('max_upload_rate');
		$newProfile["max_uploads"] = getRequestVar('max_uploads');
		$newProfile["max_download_rate"] = getRequestVar('max_download_rate');
		$newProfile["dont_stop"] = getRequestVar('dont_stop');
		$newProfile["sharekill"] = getRequestVar('sharekill');
		$newProfile["btclient"] = getRequestVar('btclient');
		if( !empty( $newProfile ) ) {
			global $cfg;
			AddProfileInfo( $newProfile );
			AuditAction( $cfg["constants"]["admin"], "New Profile: " . $newProfile["title"] );
		}
		header( "location: index.php?page=profile&op=showProfiles" );
	break;
	
//******************************************************************************
// modProfile -- edit Profile Information
//******************************************************************************
	case "modProfile":
		$newProfile["title"] = getRequestVar('title');
		$newProfile["minport"] = getRequestVar('minport');
		$newProfile["maxport"] = getRequestVar('maxport');
		$newProfile["maxcons"] = getRequestVar('maxcons');
		$newProfile["rerequest_interval"] = getRequestVar('rerequest_interval');
		$newProfile["max_upload_rate"] = getRequestVar('max_upload_rate');
		$newProfile["max_uploads"] = getRequestVar('max_uploads');
		$newProfile["max_download_rate"] = getRequestVar('max_download_rate');
		$newProfile["dont_stop"] = getRequestVar('dont_stop');
		$newProfile["sharekill"] = getRequestVar('sharekill');
		$newProfile["btclient"] = getRequestVar('btclient');
		$pid = getRequestVar('pid');
		global $cfg;
		modProfileInfo($pid,$newProfile);
		AuditAction($cfg["constants"]["admin"], "Modified Profile: ".$newProfile["title"]);
		header("location: index.php?page=profile&op=showProfiles");
	break;
	
//******************************************************************************
// deleteProfile -- delete a Profile Information
//******************************************************************************
	case "deleteProfile":
		$pid = $_GET["pid"];
		global $cfg;
		$profile = getProfile( $pid );
		deleteProfileInfo( $pid );
		AuditAction( $cfg["constants"]["admin"], _DELETE . " Profile: " . $profile["title"] );
		header( "location: index.php?page=profile&op=showProfiles" );
	break;


}

#some good looking vars
$tmpl->setvar('indexPageSettingsForm', getIndexPageSettingsForm());
$tmpl->setvar('sortOrderSettingsForm', getSortOrderSettingsForm());
$tmpl->setvar('goodLookingStatsForm', getGoodLookingStatsForm());
$tmpl->setvar('moveSettingsForm', getMoveSettingsForm());
$tmpl->setvar('ui_displayfluxlink', $cfg["ui_displayfluxlink"]);
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('index_page', $cfg["index_page"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
# lets parse the hole thing
$tmpl->pparse();
?>