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

// config
require_once('config.php');
// db
require_once('db.php');
// functions
require_once("inc/functions/functions.php");

// Start Session and grab user
session_start("TorrentFlux");
if (! isset($_SESSION['user'])) {
    @ob_end_clean();
    exit();
} else {
    $cfg["user"] = strtolower($_SESSION['user']);
}

// Create Connection.
$db = getdb();

// load settings
loadSettings();

// somehow there is a bug when disabling rememberme-hack while cookie is set.
// (auto-login and cookie cant be deleted)
// so cookies are deleted always for now .. if remember_me is active or not
$cookieTime = time() - 3600;
setcookie("check", "", $cookieTime);
setcookie("username", "", $cookieTime);
setcookie("iamhim", "", $cookieTime);

// logout user
logoutUser();

// destroy session
session_destroy();

// final logout-step
if ($cfg["auth_type"] == 2) { /* Basic-Auth */
    header('WWW-Authenticate: Basic realm="'. _AUTH_BASIC_REALM .'"');
    header('HTTP/1.0 401 Unauthorized');
    @ob_end_clean();
} else {
    header('location: login.php');
}
exit();

// Remove history for user so they are logged off from screen
function logoutUser() {
    global $cfg, $db;
    $sql = "DELETE FROM tf_log WHERE user_id=".$db->qstr($cfg["user"])." and action=".$db->qstr($cfg["constants"]["hit"]);
    // do the SQL
    $result = $db->Execute($sql);
    showError($db, $sql);
}
?>