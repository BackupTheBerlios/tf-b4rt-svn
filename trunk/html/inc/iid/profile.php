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
if ((!isset($cfg['user'])) || (isset($_REQUEST['cfg']))) {
	@ob_end_clean();
	@header("location: ../../index.php");
	exit();
}

/******************************************************************************/

// common functions
require_once('inc/functions/functions.common.php');

// init template-instance
tmplInitializeInstance($cfg["theme"], "page.profile.tmpl");

// op-switch
$op = getRequestVar('op');
switch ($op) {

//******************************************************************************
// addProfile -- adding a Profile Information
//******************************************************************************
	case "addProfile":
		$newProfile["name"] = getRequestVar('name');
		$newProfile["minport"] = getRequestVar('minport');
		$newProfile["maxport"] = getRequestVar('maxport');
		$newProfile["maxcons"] = getRequestVar('maxcons');
		$newProfile["rerequest"] = getRequestVar('rerequest');
		$newProfile["rate"] = getRequestVar('rate');
		$newProfile["maxuploads"] = getRequestVar('maxuploads');
		$newProfile["drate"] = getRequestVar('drate');
		$newProfile["runtime"] = getRequestVar('runtime');
		$newProfile["sharekill"] = getRequestVar('sharekill');
		$supsed = getRequestVar('superseeder');
		$newProfile["superseeder"] = ($supsed == "") ? 0 : $supsed;
		$pub = getRequestVar('public');
		$newProfile["public"] = ($pub == "") ? 0 : $pub;
		if (!empty( $newProfile)) {
			AddProfileInfo($newProfile);
			AuditAction( $cfg["constants"]["admin"], "New Profile: " . $newProfile["name"] );
		}
		@header( "location: index.php?iid=profile&op=showProfiles" );
		exit();

//******************************************************************************
// modProfile -- edit Profile Information
//******************************************************************************
	case "modProfile":
		$newProfile["name"] = getRequestVar('name');
		$newProfile["minport"] = getRequestVar('minport');
		$newProfile["maxport"] = getRequestVar('maxport');
		$newProfile["maxcons"] = getRequestVar('maxcons');
		$newProfile["rerequest"] = getRequestVar('rerequest');
		$newProfile["rate"] = getRequestVar('rate');
		$newProfile["maxuploads"] = getRequestVar('maxuploads');
		$newProfile["drate"] = getRequestVar('drate');
		$newProfile["runtime"] = getRequestVar('runtime');
		$newProfile["sharekill"] = getRequestVar('sharekill');
		$supsed = getRequestVar('superseeder');
		$newProfile["superseeder"] = ($supsed == "") ? 0 : $supsed;
		$pub = getRequestVar('public');
		$newProfile["public"] = ($pub == "") ? 0 : $pub;
		$pid = getRequestVar('pid');
		modProfileInfo($pid,$newProfile);
		AuditAction($cfg["constants"]["admin"], "Modified Profile: ".$newProfile["name"]);
		@header("location: index.php?iid=profile&op=showProfiles");
		exit();

//******************************************************************************
// deleteProfile -- delete a Profile Information
//******************************************************************************
	case "deleteProfile":
		$pid = $_REQUEST["pid"];
		$profile = getProfile($pid);
		deleteProfileInfo($pid);
		AuditAction( $cfg["constants"]["admin"], $cfg['_DELETE'] . " Profile: " . $profile["name"] );
		@header("location: index.php?iid=profile&op=showProfiles" );
		exit();

//******************************************************************************
// updateSettingsUser -- update per user settings
//******************************************************************************
	case "updateSettingsUser":
		// TODO
		//$settings = processSettingsParams(true,true);
		//saveUserSettings($cfg["uid"],$settings);
		AuditAction($cfg["constants"]["admin"], "updated per user settings for ".$cfg["user"]);
		@header( "location: index.php?iid=profile" );
		exit();

//******************************************************************************
// addCookie -- adding a Cookie Host Information
//******************************************************************************
	case "addCookie":
		$newCookie["host"] = getRequestVar('host');
		$newCookie["data"] = getRequestVar('data');
		if (!empty($newCookie)) {
			AddCookieInfo($newCookie);
			AuditAction($cfg["constants"]["admin"], "New Cookie: " . $newCookie["host"] . " | " . $newCookie["data"]);
		}
		@header("location: index.php?iid=profile&op=showCookies");
		exit();

//******************************************************************************
// deleteCookie -- delete a Cookie Host Information
//******************************************************************************
	case "deleteCookie":
		$cid = $_REQUEST["cid"];
		$cookie = getCookie($cid);
		deleteCookieInfo($cid);
		AuditAction($cfg["constants"]["admin"], $cfg['_DELETE'] . " Cookie: " . $cookie["host"]);
		@header("location: index.php?iid=profile&op=showCookies");
		exit();

//******************************************************************************
// modCookie -- edit a Cookie Host Information
//******************************************************************************
	case "modCookie":
		$newCookie["host"] = getRequestVar('host');
		$newCookie["data"] = getRequestVar('data');
		$cid = getRequestVar('cid');
		modCookieInfo($cid,$newCookie);
		AuditAction($cfg["constants"]["admin"], "Modified Cookie: ".$newCookie["host"]." | ".$newCookie["data"]);
		@header("location: index.php?iid=profile&op=showCookies");
		exit();


//******************************************************************************
// updateProfile -- update profile
//******************************************************************************
	case "updateProfile":
		$tmpl->setvar('updateProfile', 1);
		$pass1 = getRequestVar('pass1');
		$pass2 = getRequestVar('pass2');
		$hideOffline = getRequestVar('hideOffline');
		$theme = getRequestVar('theme');
		$language = getRequestVar('language');
		if ($pass1 != "")
			$_SESSION['user'] = md5($cfg["pagetitle"]);
		UpdateUserProfile($cfg["user"], $pass1, $hideOffline, $theme, $language);
		$tmpl->setvar('_PROFILEUPDATEDFOR', $cfg['_PROFILEUPDATEDFOR']);
		break;

//******************************************************************************
// ShowCookies
//******************************************************************************
	case "showCookies":
	case "editCookies":
		$tmpl->setvar('ShowCookies', 1);
		$cid = @ $_REQUEST["cid"]; // Cookie ID
		// Used for when editing a cookie
		$hostvalue = $datavalue = "";
		if (!empty($cid)) {
			// Get cookie information from database
			$cookie = getCookie( $cid );
			$hostvalue = " value=\"" . $cookie['host'] . "\"";
			$datavalue = " value=\"" . $cookie['data'] . "\"";
		}
		(!empty($cid)) ? $op2 = "modCookie" : $op2 = "addCookie";
		$tmpl->setvar('op', $op2);
		$tmpl->setvar('cid', $cid);
		$tmpl->setvar('hostvalue', $hostvalue);
		$tmpl->setvar('datavalue', $datavalue);
		(!empty( $cid )) ? $add1 = $cfg['_UPDATE'] : $add1 = "Add";
		$tmpl->setvar('add1', $add1);
		// We are editing a cookie, so have a link back to cookie list
		if( !empty( $cid ) ) {
			$tmpl->setvar('empty_cid', 1);
		} else {
			$tmpl->setvar('empty_cid', 0);
			// Output the list of cookies in the database
			$sql = "SELECT c.cid, c.host, c.data FROM tf_cookies AS c, tf_users AS u WHERE u.uid=c.uid AND u.user_id='" . $cfg["user"] . "'";
			$dat = $db->GetAll( $sql );
			if( empty( $dat ) ) {
				$tmpl->setvar('empty_dat', 1);
			} else {
				$tmpl->setvar('empty_dat', 0);
				$cookie_data = array();
				$tmpl->setvar('_DELETE', $cfg['_DELETE']);
				$tmpl->setvar('_EDIT', $cfg['_EDIT']);
				foreach( $dat as $cookie ) {
					array_push($cookie_data, array(
						'cid' => $cookie["cid"],
						'host' => $cookie["host"],
						'data' => $cookie["data"]
						)
					);
				}
				$tmpl->setloop('cookie_data', $cookie_data);
			}
		}
		break;

//******************************************************************************
// ShowProfiles
//******************************************************************************
	case "showProfiles":
	case "editProfiles":
		$tmpl->setvar('ShowProfiles', 1);
		$pid = @ $_REQUEST["pid"];
		(!empty( $pid )) ? $add1 = $cfg['_UPDATE'] : $add1 = "Add";
		$tmpl->setvar('add1', $add1);
		(!empty( $pid )) ? $op2 = "modProfile" : $op2 = "addProfile";
		$tmpl->setvar('op', $op2);
		$name = $minport = $maxport = $maxcons = $rerequest = $rate = $maxuploads = $drate = $runtime = $sharekill = $superseeder = $public = "";
		if (!empty($pid)) {
			$profile = getProfile( $pid );
			$name = " value=\"" . $profile['name'] . "\"";
			$minport = " value=\"" . $profile['minport'] . "\"";
			$maxport = " value=\"" . $profile['maxport'] . "\"";
			$maxcons = " value=\"" . $profile['maxcons'] . "\"";
			$rerequest = " value=\"" . $profile['rerequest'] . "\"";
			$rate = " value=\"" . $profile['rate'] . "\"";
			$maxuploads = " value=\"" . $profile['maxuploads'] . "\"";
			$drate = " value=\"" . $profile['drate'] . "\"";
			$runtime = $profile['runtime'];
			$sharekill = " value=\"" . $profile['sharekill'] . "\"";
			if ($profile['superseeder'] == 1)
				$superseeder = "checked";
			if ($profile['public'] == 1)
				$public = "checked";
		}
		$tmpl->setvar('name', $name);
		$tmpl->setvar('minport', $minport);
		$tmpl->setvar('maxport', $maxport);
		$tmpl->setvar('maxcons', $maxcons);
		$tmpl->setvar('rerequest', $rerequest);
		$tmpl->setvar('rate', $rate);
		$tmpl->setvar('maxuploads', $maxuploads);
		$tmpl->setvar('drate', $drate);
		$tmpl->setvar('runtime', $runtime);
		$tmpl->setvar('sharekill', $sharekill);
		$tmpl->setvar('superseeder', $superseeder);
		$tmpl->setvar('public', $public);
		$tmpl->setvar('default_name', "TransferProfile");
		$tmpl->setvar('default_minport', $cfg['minport']);
		$tmpl->setvar('default_maxport', $cfg['maxport']);
		$tmpl->setvar('default_maxcons', $cfg['maxcons']);
		$tmpl->setvar('default_rerequest', $cfg['rerequest_interval']);
		$tmpl->setvar('default_rate', $cfg['max_upload_rate']);
		$tmpl->setvar('default_maxuploads', $cfg['max_uploads']);
		$tmpl->setvar('default_drate', $cfg['max_download_rate']);
		$tmpl->setvar('default_sharekill', $cfg['sharekill']);
		$tmpl->setvar('default_btclient', $cfg['btclient']);
		$tmpl->setvar('pid', $pid);
		if (!empty($pid)) {
			$tmpl->setvar('empty_pid', 1);
		} else {
			$tmpl->setvar('empty_pid', 0);
			// Output the list of profiles in the database
			$sql = "SELECT id, name FROM tf_trprofiles WHERE owner LIKE '" . $cfg["uid"] . "'";
			$dat = $db->GetAll( $sql );
			if (empty($dat)) {
				$tmpl->setvar('empty_dat', 1);
			} else {
				$tmpl->setvar('empty_dat', 0);
				$profile_data = array();
				$tmpl->setvar('_DELETE', $cfg['_DELETE']);
				$tmpl->setvar('_EDIT', $cfg['_EDIT']);
				foreach( $dat as $profile ) {
					array_push($profile_data, array(
						'pid' => $profile["id"],
						'name' => $profile["name"]
						)
					);
				}
				$tmpl->setloop('profile_data', $profile_data);
			}
		}
		$tmpl->setvar('pid', $pid);
		break;

	default:
//******************************************************************************
// showIndex -- main view
//******************************************************************************
		$tmpl->setvar('showIndex', 1);
		$hideChecked = "";
		if ($cfg["hide_offline"] == 1)
			$hideChecked = "checked";
		$total_activity = GetActivityCount();
		$sql= "SELECT user_id, hits, last_visit, time_created, user_level FROM tf_users WHERE user_id=".$db->qstr($cfg["user"]);
		list ($user_id, $hits, $last_visit, $time_created, $user_level) = $db->GetRow($sql);
		$user_type = $cfg['_NORMALUSER'];
		if ($cfg['isAdmin'])
			$user_type = $cfg['_ADMINISTRATOR'];
		if (IsSuperAdmin())
			$user_type = $cfg['_SUPERADMIN'];
		$user_activity = GetActivityCount($cfg["user"]);
		if ($user_activity == 0)
			$user_percent = 0;
		else
			$user_percent = number_format(($user_activity/$total_activity)*100);
		$tmpl->setvar('time_created1', date($cfg['_DATETIMEFORMAT'], $time_created));
		$tmpl->setvar('width1', $user_percent*2);
		$tmpl->setvar('width2', (200 - ($user_percent*2)));
		$tmpl->setvar('user_activity', $user_activity);
		$tmpl->setvar('user_percent', $user_percent);
		$tmpl->setvar('days_to_keep', $cfg["days_to_keep"]);
		$tmpl->setvar('hits', $hits);
		$tmpl->setvar('user_type', $user_type);
		$tmpl->setvar('_UPLOADS', $cfg['_UPLOADS']);
		$tmpl->setvar('_DAYS', $cfg['_DAYS']);
		$tmpl->setvar('_JOINED', $cfg['_JOINED']);
		$tmpl->setvar('_UPLOADPARTICIPATION', $cfg['_UPLOADPARTICIPATION']);
		$tmpl->setvar('_PARTICIPATIONSTATEMENT', $cfg['_PARTICIPATIONSTATEMENT']);
		$tmpl->setvar('_USERTYPE', $cfg['_USERTYPE']);
		$tmpl->setvar('_TOTALPAGEVIEWS', $cfg['_TOTALPAGEVIEWS']);
		$tmpl->setvar('_PERCENTPARTICIPATION', $cfg['_PERCENTPARTICIPATION']);
		$tmpl->setvar('_USER', $cfg['_USER']);
		$tmpl->setvar('_NEWPASSWORD', $cfg['_NEWPASSWORD']);
		$tmpl->setvar('_CONFIRMPASSWORD', $cfg['_CONFIRMPASSWORD']);
		$tmpl->setvar('_THEME', $cfg['_THEME']);
		$tmpl->setvar('_HIDEOFFLINEUSERS', $cfg['_HIDEOFFLINEUSERS']);
		$tmpl->setvar('_USERIDREQUIRED', $cfg['_USERIDREQUIRED']);
		$tmpl->setvar('_PASSWORDLENGTH', $cfg['_PASSWORDLENGTH']);
		$tmpl->setvar('_PASSWORDNOTMATCH', $cfg['_PASSWORDNOTMATCH']);
		$tmpl->setvar('_PLEASECHECKFOLLOWING', $cfg['_PLEASECHECKFOLLOWING']);
		$tmpl->setvar('_UPDATE', $cfg['_UPDATE']);
		$tmpl->setvar('_LANGUAGE', $cfg['_LANGUAGE']);
		// themes
		$arThemes = GetThemes();
		$theme_list = array();
		for($inx = 0; $inx < sizeof($arThemes); $inx++) {
			$selected = "";
			if ($cfg["theme"] == $arThemes[$inx])
				$selected = "selected";
			array_push($theme_list, array(
				'arThemes' => $arThemes[$inx],
				'selected' => $selected
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
			if ($cfg["theme"] == $arThemes2[$inx])
				$selected = "selected";
			array_push($tfstandard_theme_list, array(
				'arThemes' => $arThemes[$inx],
				'arThemes2' => $arThemes2[$inx],
				'selected' => $selected
				)
			);
		}
		$tmpl->setloop('tfstandard_theme_list', $tfstandard_theme_list);
		// languages
		$arLanguage = GetLanguages();
		$language_list = array();
		for($inx = 0; $inx < sizeof($arLanguage); $inx++) {
			$selected = "";
			if ($cfg["language_file"] == $arLanguage[$inx])
				$selected = "selected";
			array_push($language_list, array(
				'arLanguage' => $arLanguage[$inx],
				'selected' => $selected,
				'language_file' => GetLanguageFromFile($arLanguage[$inx])
				)
			);
		}
		$tmpl->setloop('language_list', $language_list);
		$tmpl->setvar('hideChecked', $hideChecked);
		break;
}

// set defines
if ($cfg['transfer_profiles'] <= 0) {
	$tmpl->setvar('with_profiles', 0);
} else {
	if ($cfg['transfer_profiles'] >= 2)
		$tmpl->setvar('with_profiles', 1);
	else
		$tmpl->setvar('with_profiles', ($cfg['isAdmin']) ? 1 : 0);
}
$tmpl->setvar('user', $cfg["user"]);
//
$tmpl->setvar('_PROFILE', $cfg['_PROFILE']);
//
$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
//
tmplSetTitleBar($cfg["user"]."'s ".$cfg['_PROFILE']);
tmplSetFoot();
$tmpl->setvar('iid', $_REQUEST["iid"]);
$tmpl->setvar('mainMenu', mainMenu($_REQUEST["iid"]));

// parse template
$tmpl->pparse();

?>