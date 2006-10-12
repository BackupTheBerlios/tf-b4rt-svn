<?php

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

// ADODB support.
require_once('db.php');
require_once("settingsfunctions.php");

// Create Connection.
$db = getdb();
loadSettings();
session_start("TorrentFlux");
require_once("config.php");
include("themes/".$cfg["default_theme"]."/index.php");
global $cfg;
if(isset($_SESSION['user'])) {
	header("location: index.php");
	exit();
}
ob_start();

// authentication
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
		break;
	case 0: /* Form-Based Auth Standard */
	default:
		$user = strtolower(getRequestVar('username'));
		$iamhim = addslashes(getRequestVar('iamhim'));
		break;
}

// time
$create_time = time();
// Check for user
if(!empty($user) && !empty($iamhim)) {
	/* First User check */
	$next_loc = "index.php";
	$sql = "SELECT count(*) FROM tf_users";
	$user_count = $db->GetOne($sql);
	if($user_count == 0) {
		// This user is first in DB.  Make them super admin.
		// this is The Super USER, add them to the user table
		$record = array(
						'user_id'=>$user,
						'password'=>md5($iamhim),
						'hits'=>1,
						'last_visit'=>$create_time,
						'time_created'=>$create_time,
						'user_level'=>2,
						'hide_offline'=>0,
						'theme'=>$cfg["default_theme"],
						'language_file'=>$cfg["default_language"]
						);
		$sTable = 'tf_users';
		$sql = $db->GetInsertSql($sTable, $record);
		$result = $db->Execute($sql);
		showError($db,$sql);
		// Test and setup some paths for the TF settings
		$pythonCmd = $cfg["pythonCmd"];
		$btphpbin = getcwd() . "/TF_BitTornado/btphptornado.py";
		$tfQManager = getcwd() . "/TF_BitTornado/tfQManager.py";
		$maketorrent = getcwd() . "/TF_BitTornado/btmakemetafile.py";
		$btshowmetainfo = getcwd() . "/TF_BitTornado/btshowmetainfo.py";
		$tfPath = getcwd() . "/downloads/";
		if (!isFile($cfg["pythonCmd"])) {
			$pythonCmd = trim(shell_exec("which python"));
			if ($pythonCmd == "")
				$pythonCmd = $cfg["pythonCmd"];
		}
		$settings = array(
							"pythonCmd" => $pythonCmd,
							"btphpbin" => $btphpbin,
							"tfQManager" => $tfQManager,
							"btmakemetafile" => $maketorrent,
							"btshowmetainfo" => $btshowmetainfo,
							"path" => $tfPath,
							"btclient_tornado_bin" => $btphpbin
						);
		saveSettings($settings);
		AuditAction($cfg["constants"]["update"], "Initial Settings Updated for first login.");
		$next_loc = "admin.php?op=configSettings";
	}
	$sql = "SELECT uid, hits, hide_offline, theme, language_file FROM tf_users WHERE user_id=".$db->qstr($user)." AND password=".$db->qstr(md5($iamhim));
	$result = $db->Execute($sql);
	showError($db,$sql);
	list($uid,$hits,$cfg["hide_offline"],$cfg["theme"],$cfg["language_file"]) = $result->FetchRow();
	if(!array_key_exists("shutdown",$cfg))
		$cfg['shutdown'] = '';
	if(!array_key_exists("upload_rate",$cfg))
		$cfg['upload_rate'] = '';
	if($result->RecordCount() == 1) { // suc. auth.
		// Add a hit to the user
		$hits++;
		$sql = 'select * from tf_users where uid = '.$uid;
		$rs = $db->Execute($sql);
		showError($db, $sql);
		$rec = array(
						'hits'=>$hits,
						'last_visit'=>$db->DBDate($create_time),
						'theme'=>$cfg['theme'],
						'language_file'=>$cfg['language_file'],
						'shutdown'=>$cfg['shutdown'],
						'upload_rate'=>$cfg['upload_rate']
					);
		$sql = $db->GetUpdateSQL($rs, $rec);
		$result = $db->Execute($sql);
		showError($db, $sql);
		$_SESSION['user'] = $user;
		session_write_close();
		header("location: ".$next_loc);
		exit();
	} else { // wrong credentials
		$login_failed = 1;
		AuditAction($cfg["constants"]["access_denied"], "FAILED AUTH: ".$user);
	}
} else {
    $login_failed = 0;
}
// html part
?>
<!DOCTYPE html
PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php echo $cfg["pagetitle"] ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<link rel="stylesheet" type="text/css" href="themes/<?php echo $cfg["default_theme"] ?>/style.css" />
	<meta http-equiv="pragma" content="no-cache" />
</head>
<body bgcolor="<?php echo $cfg["main_bgcolor"] ?>">

<script type="text/javascript">
<!--
function loginvalidate() {
	msg = "";
	pass = document.theForm.iamhim.value;
	user = document.theForm.username.value;
	if (user.length < 1)
	{
		msg = msg + "* Username is required\n";
		document.theForm.username.focus();
	}
	if(pass.length<1)
	{
		msg = msg + "* Password is required\n";
		if (user.length > 0)
		{
			document.theForm.iamhim.focus();
		}
	}
	if (msg != "")
	{
		alert("Check the following:\n\n" + msg);
		return false;
	}
}
-->
</script>
<br /><br /><br />
<div align="center">
	<table border="1" bordercolor="<?php echo $cfg["table_border_dk"] ?>" cellpadding="0" cellspacing="0">
	<tr>
		<td>
		<table border="0" cellpadding="4" cellspacing="0" width="100%">
			<tr>
					<td align="left" background="themes/<?php echo $cfg["default_theme"] ?>/images/bar.gif" bgcolor="<?php echo $cfg["main_bgcolor"] ?>">
					<font class="title"><?php echo $cfg["pagetitle"] ?> Login</font>
					</td>
			</tr>
		</table>
		</td>
	</tr>
	<tr>
		<td bgcolor="<?php echo $cfg["table_header_bg"] ?>">
		<div align="center">
		<table width="100%" bgcolor="<?php echo $cfg["body_data_bg"] ?>">
			<tr>
				<td>
				<table bgcolor="<?php echo $cfg["body_data_bg"] ?>" width="352 pixels" cellpadding="1">
				<tr>
					<td>
					<div align="center">
					<table border="0" cellpadding="4" cellspacing="0">
						<tr>
						<td>
						<?php
							if ($login_failed == 1) {
								?>
								<div align="center">Login failed.<br>Please try again.</div>
							<?php
							}
							?>
						<form name="theForm" action="login.php" method="post" onsubmit="return loginvalidate()">
						<table width="100%" cellpadding="5" cellspacing="0" border="0">
							<tr>
								<td align="right">Username: </td>
								<td><input type="text" name="username" value="<?PHP if ($cfg['auth_type'] == 1) echo $username; ?>" size="15" style="font-family:verdana,helvetica,sans-serif; font-size:9px; color:#000;" /></td>

							</tr>
							<tr>
								<td align="right">Password:</td>
								<td><input type="password" name="iamhim" value="<?PHP if ($cfg['auth_type'] == 1) echo $password; ?>" size="15" style="font-family:verdana,helvetica,sans-serif; font-size:9px; color:#000" /></td>
							</tr>
							<?php
							if ($cfg['auth_type'] == 1) {
							?>
								<tr><td align="right">Remember me:</td><td align="left"><input type="checkbox" name="check" value="true"
								<?php
								if ($check == "true") {
									echo " checked ";
								}
								?>
								size="15" style="font-family:verdana,helvetica,sans-serif; font-size:9px; color:#000" /></td></tr>
							<?php
							}
							?>
							<tr>
								<td colspan="2" align="center"><input class="button" type="submit" value="Login"  /></td>
							</tr>
						</table>
						</form>
					</td>
					</tr>
					</table>
					</div>
				</td>
			</tr>
			</table>
			</td>
		</tr>
		</table>
		</div>
		</td>
	</tr>
	</table>
</div>
<script type="text/javascript">
	document.theForm.username.focus();
</script>
</body>
</html>
<?php
ob_end_flush();
?>