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

# check_binary
# checks if an binary exists
# op: name of the binary
function check_binary($binary, $fatal) {
	$paths = array("/bin/", "/usr/bin/", "/usr/local/bin/", "/proc/");
	foreach($paths as $path) {
		if (is_file($path.$binary)) {
			return array(
				'title' => $binary." found in ".$path.$binary.".",
				'status' => 1,
			);
		}
	}
	return array(
		'title' => $binary." NOT found.",
		'status' => 0,
		'fatal' => $fatal,
	);
}

# check_extension
# checks if a php extensions exists
# op: name of the extension
function check_extension($extension, $fatal) {
	$load_ext = get_loaded_extensions();
	if (in_array($extension, $load_ext)) {
		return array(
			'title' => "php extension ".$extension." found.",
			'status' => 1,
		);
	}
	return array(
		'title' => "php extension ".$extension." NOT found.",
		'status' => 0,
		'fatal' => $fatal,
	);
}

# check_config
# checks if the php settings are proper
# op: name of the setting
function check_config($config, $fatal) {
	if(!ini_get($config)) {
		return array(
			'title' => "Setting ".$config." is proper set.",
			'status' => 1,
		);
	}
	return array(
		'title' => "Setting ".$config." is NOT proper set.",
		'status' => 0,
		'fatal' => $fatal,
	);
}

# display_results
# create table to display checking results
# op1: title
# op2: result
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
		$return .= "Done";
	}
	else {
		$return .= "<b>Failed!</b>";
	}
	$return .= "</td>";
	$return .= "<td>";
	if ($result['fatal'] == 1) {
		$return .= "<b>Needed!!!!</b>";
	}
	$return .= "</td>";
	$return .= "</tr>";
	echo $return;
}


#################################################################################
## end functions
#################################################################################
if(!isset($_POST['page']) || $_POST['page'] == 1) {
	# first page, check some basics
	?>
	<form action="setup.php" method="post">
	<table>
	<tr>
		<td>
			<u><b>Step one: Check some Basics</b></u>
		</td>
	</tr>
	<?php
	display_results("PHP Session Support:", check_extension("session", 1));
	display_results("PHP PCRE Support:", check_extension("pcre", 1));
	display_results("Safe Mode:", check_config("safe_mode", 1));
	?>
	</table>
	<br>
	<input type="hidden" name="page" value="2">
	<table border="0" cellspacing="0" cellpadding="0">
		<tr>
			<td>Select type of Database:</td>
			<td><input type="radio" name="db_type" value="mysql" checked="checked" />Mysql</td>
			<td><input type="radio" name="db_type" value="sqlite" />Sqlite</td>
		</tr>
		<tr>
			<td>Install or just check?</td>
			<td><input type="radio" name="op" value="1" checked="checked"> only check</td>
		<!--not used now	<td><input type="radio" name="op" value="2"> install</td> -->
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
	<form action="setup.php" method="post">
	<table>
	<tr>
		<td>
			<u><b>Step two: Check some more Settings</b></u>
		</td>
	</tr>
	<?php
	if($_POST['db_type'] == "mysql") {
		display_results("PHP MySQL Support:", check_extension("mysql", 1));
	}
	elseif($_POST['db_type'] == "sqlite") {
		display_results("PHP SQLite Support:", check_extension("SQLite", 1));
	}
	$osString = php_uname('s');
	if(isset($osString)) {
		if(!(stristr($osString, 'linux') === false)) { // linux
			display_results("check for loadavg:", check_binary("loadavg", 1));
			display_results("check for netstat:", check_binary("netstat", 1));
		}
		elseif(!(stristr($osString, 'bsd') === false)) { // bsd
			display_results("check for sockstat:", check_binary("sockstat", 1));
		}
	}
	display_results("check for grep:", check_binary("grep", 1));
	display_results("check for cat:", check_binary("cat", 1));
	display_results("check for php:", check_binary("php", 1));
	display_results("check for python:", check_binary("python", 1));
	display_results("check for awk:", check_binary("awk", 1));
	display_results("check for du:", check_binary("du", 1));
	display_results("check for wget:", check_binary("wget", 0));
	display_results("check for unzip:", check_binary("unzip", 0));
	display_results("check for cksfv:", check_binary("cksfv", 0));
	?>
	</table>
	<br>
	<input type="hidden" name="page" value="3">
	<input type="hidden" name="db_type" value="<?php echo $_POST['db_type'] ?>">
	<input type="hidden" name="op" value="<?php echo $_POST['op'] ?>">
	<table border="0" cellspacing="0" cellpadding="0">
		<tr>
			<td>Also check Database connection? (optional)</td>
			<td><input type="radio" name="check_db" value="1" checked="checked"> Don't check</td>
			<td><input type="radio" name="check_db" value="2"> check</td>
		</tr>
		<tr>
			<td colspan="3" align="center"><input type="submit" value="Next"></td>
		</tr>
	</table>
	</form>
	<?php
}
elseif($_POST['page'] == 3) {
	if($_POST['check_db'] == 1) {
	?>
		<table>
		<tr>
			<td>
				<u><b>Finished!!!!</b></u>
			</td>
		</tr>
		</table>
	<?php
	}
	elseif($_POST['check_db'] == 2) {
			?>
	<form action="setup.php" method="post">
	<table>
	<tr>
		<td>
			<u><b>Step three: Provide Database Informations:</b></u>
		</td>
	</tr>
	<?php
	if($_POST['db_type'] == "mysql") {
	?>
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
	<?php
	}
	elseif($_POST['db_type'] == "sqlite") {
	?>
		<tr>
			<td>Path to Database:</td>
			<td colspan="2"><input type="text" name="db_name"></td>
		</tr>
	<?php
	}
	?>
	<input type="hidden" name="page" value="4">
	<input type="hidden" name="db_type" value="<?php echo $_POST['db_type'] ?>">
	<input type="hidden" name="op" value="<?php echo $_POST['op'] ?>">
	<tr>
		<td colspan="3" align="center"><input type="submit" value="Next"></td>
	</tr>
	</table>
	</form>
	<?php
	}
}
elseif($_POST['page'] == 4) {
?>
	<form action="setup.php" method="post">
	<table>
	<tr>
		<td>
			<u><b>Step four: Check Database</b></u>
		</td>
	</tr>
	<?php
	if($_POST['db_type'] == "mysql") {
		$load_ext = get_loaded_extensions();
		if (in_array("mysql", $load_ext)) {
			$link = mysql_connect($_POST['db_host'], $_POST['db_user'], $_POST['db_pass']);
			if($link) {
				display_results("check MySQL Connection:", array(
					'title' => "Successfully connected.",
					'status' => 1,
					'fatal' => 0,
				));
			}
			else {
				display_results("check MySQL Connection:", array(
					'title' => "Connection failed.",
					'status' => 0,
					'fatal' => 1,
				));
			}
			if(mysql_select_db($_POST['db_name'])) {
				display_results("check MySQL Database:", array(
					'title' => "Successfully selected Database.",
					'status' => 1,
					'fatal' => 0,
				));
			}
			else {
				display_results("check MySQL Database:", array(
					'title' => "Selecting Database failed.",
					'status' => 0,
					'fatal' => 1,
				));
			}
		}
	}
	elseif($_POST['db_type'] == "sqlite") {
		$load_ext = get_loaded_extensions();
		if (in_array("SQLite", $load_ext)) {
			if(is_file($_POST['db_name'])) {
				$exists = 1;
			}
			else {
				$exists = 0;
			}
			if(sqlite_open($_POST['db_name'])) {
				# delete database if not needed
				if ($exists == "0" && $_POST['op'] == "1") {
					unlink($_POST['db_name']);
				}
				display_results("check SQLite Database:", array(
					'title' => "Database exists.",
					'status' => 1,
					'fatal' => 0,
				));
			}
			else {
				display_results("check SQLite Database:", array(
					'title' => "No Database exists.",
					'status' => 0,
					'fatal' => 1,
				));
			}
		}
	}
	?>
	<input type="hidden" name="page" value="5">
	<input type="hidden" name="db_type" value="<?php echo $_POST['db_type'] ?>">
	<input type="hidden" name="op" value="<?php echo $_POST['op'] ?>">
	<tr>
		<td colspan="3" align="center"><input type="submit" value="Next"></td>
	</tr>
	</table>
	<?php
}
elseif($_POST['page'] == 5) {
?>
	<table>
	<tr>
		<td>
			<u><b>Finished!!!!</b></u>
		</td>
	</tr>
	</table>
<?php
}
?>
