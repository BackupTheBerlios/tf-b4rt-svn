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

/*******************************************************************************
 START FUNCTIONS
*******************************************************************************/

// check_binary
// checks if an binary exists
// op: name of the binary
function check_binary($binary) {
	$shell = shell_exec("which $binary");
	if (strstr($shell, "no")) {
		return array(
			'title' => $binary." NOT found",
			'status' => 0,
		);
	}
	else {
		return array(
			'title' => $binary." found in ".$shell,
			'status' => 1,
		);
	}
}

// check_extension
// checks if a php extensions exists
// op: name of the extension
function check_extension($extension) {
	$load_ext = get_loaded_extensions();
	if (in_array($extension, $load_ext)) {
		return array(
			'title' => "PHP extension ".$extension." found.",
			'status' => 1,
		);
	}
	return array(
		'title' => "PHP extension ".$extension." NOT found.",
		'status' => 0,
	);
}

// check_config
// checks if the php settings are proper
// op: name of the setting
function check_config($config) {
	if(!ini_get($config)) {
		return array(
			'title' => "Setting ".$config." is proper set.",
			'status' => 1,
		);
	}
	return array(
		'title' => "Setting ".$config." is NOT proper set.",
		'status' => 0,
	);
}

// display_results
// create table to display checking results
// op1: title
// op2: result
function display_results($title, $result) {
	$return = "<tr>";
	$return .= "<td>";
	$return .= "<b>";
	$return .= $title;
	$return .= "</b>";
	$return .= "</td>";
	$return .= "<td>";
	$return .= $result['title'];
	$return .= "</td>";
	$return .= "<td>";
	if ($result['status'] == 1) {
		$return .= "<img src='html/themes/default/images/green.gif'>";
	}
	else {
		$return .= "<img src='html/themes/default/images/red.gif'>";
	}
	$return .= "</td>";
	$return .= "<td>";
	$return .= "</td>";
	$return .= "</tr>";
	echo $return;
}

/*******************************************************************************
 END FUNCTIONS
*******************************************************************************/

/*******************************************************************************
 START PAGE
*******************************************************************************/

// array extensions
$extensions = array("session", "pcre");
// array settings
$settings = array("safe_mode");
// array binaries
$binaries = array("grep", "cat", "php", "python", "awk", "du", "wget", "unzip", "cksfv");

// start output
// form
if(!isset($_POST['page']) || $_POST['page'] == 1) {
?>
<form action="setup.php" method="post">
	<input type="hidden" name="page" value="2">
	<table border="0" cellspacing="0" cellpadding="2">
		<tr>
			<td>
				<u><b>Settings:</b></u>
			</td>
		</tr>
		<tr>
			<tr>
				<td>Select type of Database:</td>
				<td><input type="radio" name="db_type" value="mysql" checked="checked" />MySQL</td>
				<td><input type="radio" name="db_type" value="sqlite" />SQLite</td>
			</tr>
		</tr>
		<tr>
			<td>Name of the Database:</td>
			<td colspan="2"><input type="text" name="db_name"></td>
		</tr>
		<tr>
			<td>Database Host (usually localhost):</td>
			<td colspan="2"><input type="text" name="db_host"></td>
		</tr>
		<tr>
			<td>Database Username:</td>
			<td colspan="2"><input type="text" name="db_user"></td>
		</tr>
		<tr>
			<td>Database Password:</td>
			<td colspan="2"><input type="password" name="db_pass"></td>
		</tr>
		<tr>
			<td colspan="3" align="center"><input type="submit" value="Next"></td>
		</tr>
	</table>
</form>
<?php
}
elseif($_POST['page'] == 2) {
?>
<table border="0" cellspacing="0" cellpadding="2">
	<tr>
		<td>
			<u><b>Check PHP settings:</b></u>
		</td>
	</tr>
	<?php
	foreach($extensions as $extension) {
		display_results("PHP ".$extension." Support:", check_extension($extension));
	}
	foreach($settings as $setting) {
		display_results("PHP ".$setting.":", check_config($setting));
	}
	?>
	<tr>
		<td>
			<u><b>Check database settings:</b></u>
		</td>
	</tr>
	<?php
	if ($_POST['db_type'] == "mysql") {
		display_results("PHP MySQL Support:", check_extension("mysql"));
		$load_ext = get_loaded_extensions();
		if (in_array("mysql", $load_ext)) {
			$link = mysql_connect($_POST['db_host'], $_POST['db_user'], $_POST['db_pass']);
			if($link) {
				display_results("check MySQL Connection:", array(
					'title' => "Successfully connected.",
					'status' => 1,
				));
			}
			else {
				display_results("check MySQL Connection:", array(
					'title' => "Connection failed.",
					'status' => 0,
				));
			}
			if(mysql_select_db($_POST['db_name'])) {
				display_results("check MySQL Database:", array(
					'title' => "Successfully selected Database.",
					'status' => 1,
				));
			}
			else {
				display_results("check MySQL Database:", array(
					'title' => "Selecting Database failed.",
					'status' => 0,
				));
			}
		}
	}
	elseif ($_POST['db_type'] == "sqlite") {
		display_results("PHP SQLite Support:", check_extension("SQLite"));
		$load_ext = get_loaded_extensions();
		if (in_array("SQLite", $load_ext)) {
			if(is_file($_POST['db_name'])) {
				$exists = 1;
			}
			else {
				$exists = 0;
			}
			if(sqlite_open($_POST['db_name'])) {
				results("check SQLite Database:", array(
					'title' => "Database exists.",
					'status' => 1,
				));
			}
			else {
				display_results("check SQLite Database:", array(
					'title' => "No Database exists.",
					'status' => 0,
				));
			}
		}
	}
	?>
	<tr>
		<td>
			<u><b>Check binaries:</b></u>
		</td>
	</tr>
	<?php
	$osString = php_uname('s');
	if(isset($osString)) {
		if(!(stristr($osString, 'linux') === false)) { // linux
			display_results("check for loadavg:", check_binary("loadavg"));
			display_results("check for netstat:", check_binary("netstat"));
		}
		elseif(!(stristr($osString, 'bsd') === false)) { // bsd
			display_results("check for sockstat:", check_binary("sockstat"));
		}
	}
	foreach($binaries as $binary) {
		display_results("check for ".$binary.":", check_binary($binary));
	}
	?>
</table>
<?php
// write config
$config = fopen( "html/inc/config/config.db.php", "w" );
$content = '<?php

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

/******************************************************************************/
// YOUR DATABASE CONNECTION INFORMATION
/******************************************************************************/
$cfg["db_type"] = "'.$_POST["db_type"].'";  // Databae-Type : mysql/sqlite/postgres
$cfg["db_host"] = "'.$_POST["db_host"].'";  // Database host computer name or IP
$cfg["db_name"] = "'.$_POST["db_name"].'";  // Name of the Database
$cfg["db_user"] = "'.$_POST["db_user"].'";  // Username for Database
$cfg["db_pass"] = "'.$_POST["db_pass"].'";  // Password for Database
$cfg["db_pcon"] = false;                    // Persistent Connection enabled : true/false
/******************************************************************************/

?>';
fwrite( $config, $content );
fclose( $config );
}

/*******************************************************************************
 END PAGE
*******************************************************************************/

?>