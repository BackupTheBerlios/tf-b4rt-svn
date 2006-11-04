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

// main.external
require_once('inc/main.external.php');

// init template-instance
tmplInitializeInstance($cfg["default_theme"], "page.login.tmpl");

// start session
@session_start();

// unregister globals
if (@ini_get('register_globals')) {
	require_once('inc/functions/functions.compat.php');
	unregister_GLOBALS();
}

// already got a session ?
if (isset($_SESSION['user'])) {
	header("location: index.php?iid=index");
	exit();
}

// start ob
@ob_start();

// authentication
switch ($cfg['auth_type']) {
	case 3: /* Basic-Passthru */
	case 2: /* Basic-Auth */
		if ((isset($_SERVER['PHP_AUTH_USER'])) && (isset($_SERVER['PHP_AUTH_PW']))) {
			$user = $_SERVER['PHP_AUTH_USER'];
			$iamhim = addslashes($_SERVER['PHP_AUTH_PW']);
		} else {
			header('WWW-Authenticate: Basic realm="'. $cfg["auth_basic_realm"] .'"');
			header('HTTP/1.0 401 Unauthorized');
			@ob_end_clean();
			exit();
		}
		break;
	case 1: /* Form-Based Auth + "Remember Me" */
		$user = getRequestVar('username');
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
			$user = $username;
			$iamhim = addslashes($password);
		}
		$tmpl->setvar('username', $user);
		$tmpl->setvar('password', $iamhim);
		$tmpl->setvar('check', $check);
		break;
	case 0: /* Form-Based Auth Standard */
	default:
		$user = getRequestVar('username');
		$iamhim = addslashes(getRequestVar('iamhim'));
		break;
}

// Check for user
if(!empty($user) && !empty($iamhim)) {
	// First User check
	$next_loc = "index.php?iid=index";
	$sql = "SELECT count(*) FROM tf_users";
	$user_count = $db->GetOne($sql);
	if ($user_count == 0) {
		firstLogin($user, $iamhim);
		$next_loc = "admin.php?op=serverSettings";
	}
	// perform auth
	if (performAuthentication($user,$iamhim) == 1) {
		header("location: ".$next_loc);
		exit();
	} else {
		$tmpl->setvar('login_failed', 1);
	}
}

// defines
$tmpl->setvar('auth_type', $cfg["auth_type"]);
$tmpl->setvar('iid', 'login');

// parse template
$tmpl->pparse();

?>