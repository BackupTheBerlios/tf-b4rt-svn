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

# always good to have a session started
session_start("TorrentFlux");

# require some things
require_once("config.php");
require_once('db.php');
require_once("settingsfunctions.php");
require_once("functions.b4rt.php");

# get connected
$db = getdb();
loadSettings();

# create new template
if (!ereg('^[^./][^/]*$', $cfg["default_theme"])) {
	$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/login.tmpl");
} else {
	$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/login.tmpl");
}

// include default theme
include("themes/".$cfg["default_theme"]."/index.php");

// already got a session ?
if(isset($_SESSION['user'])) {
	header("location: index.php?iid=index");
	exit();
}

// start ob
ob_start();

# authentication
switch ($cfg['auth_type']) {
	case 3: /* Basic-Passthru */
	case 2: /* Basic-Auth */
		if ((isset($_SERVER['PHP_AUTH_USER'])) && (isset($_SERVER['PHP_AUTH_PW']))) {
			$user = strtolower($_SERVER['PHP_AUTH_USER']);
			$iamhim = addslashes($_SERVER['PHP_AUTH_PW']);
		} else {
			header('WWW-Authenticate: Basic realm="'. _AUTH_BASIC_REALM .'"');
			header('HTTP/1.0 401 Unauthorized');
			@ob_end_clean();
			exit();
		}
		break;
	case 1: /* Form-Based Auth + "Remember Me" */
		$user = strtolower(getRequestVar('username'));
		$iamhim = addslashes(getRequestVar('iamhim'));
		$check = @$HTTP_COOKIE_VARS["check"];
		$password = @$HTTP_COOKIE_VARS["iamhim"];
		$username = @$HTTP_COOKIE_VARS["username"];
		if ((isset($_POST['check'])) && ($_POST['check'] == "true")) {
			setcookie("check", "true", time()+60*60*24*30);
			if ($_POST['username'] != "")
				setcookie("username", $_POST['username'], time()+60*60*24*30);
			if ($_POST['iamhim'] != "")
				setcookie("iamhim", $_POST['iamhim'], time()+60*60*24*30);
		}
		if(empty($user) && empty($iamhim) && !empty($username) && !empty($password)) {
			$user = strtolower($username);
			$iamhim = addslashes($password);
		}
		$tmpl->setvar('username', $user);
		$tmpl->setvar('password', $iamhim);
		$tmpl->setvar('check', $check);
		break;
	case 0: /* Form-Based Auth Standard */
	default:
		$user = strtolower(getRequestVar('username'));
		$iamhim = addslashes(getRequestVar('iamhim'));
		break;
}

// Check for user
if(!empty($user) && !empty($iamhim)) {
	// First User check
	$next_loc = "index.php?iid=index";
	$sql = "SELECT count(*) FROM tf_users";
	$user_count = $db->GetOne($sql);
	if($user_count == 0) {
		firstLogin($user,$iamhim);
		$next_loc = "index.php?iid=admin&op=configSettings";
	}
	// perform auth
	if (performAuthentication($user,$iamhim) == 1) {
		header("location: ".$next_loc);
		exit();
	} else {
		$tmpl->setvar('login_failed', 1);
	}
}

# define some things
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('default_theme', $cfg["default_theme"]);
$tmpl->setvar('main_bgcolor', $cfg["main_bgcolor"]);
$tmpl->setvar('table_border_dk', $cfg["table_border_dk"]);
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
$tmpl->setvar('body_data_bg', $cfg["body_data_bg"]);
$tmpl->setvar('auth_type', $cfg["auth_type"]);
$tmpl->setvar('iid', 'login');
# lets parse the hole thing
$tmpl->pparse();
?>