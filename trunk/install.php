<?php

/* $Id: install.php 331 2006-08-18 21:09:36Z msn_exploder $ */

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
function check_binary($binary) {
	$paths = array("/bin/", "/usr/bin/", "/usr/local/bin");
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
	);
}

# check_extension
# checks if a php extensions exists
# op: name of the extension
function check_extension($extension) {
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
	);
}

# check_config
# checks if the php settings are proper
# op: name of the setting
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
	$return .= "</tr>";
	echo $return;
}


#################################################################################
## end functions
#################################################################################

# not used now
$_POST['op'] = 1;
if(!isset($_POST['op'])) {
?>
<form action="install.php" method="post">
	<input type="radio" name="op" value="1" checked="checked"> only check
	<input type="radio" name="op" value="2"> install
	<input type="submit" value="Go...">
</form>

<?php
}
else {
# only check
?>
<table>
	<tr>
		<td>
			<u><b>Check Requirements:</b></u>
		</td>
	</tr>
<?php
	# first check php extensions
	display_results("PHP Session Support:", check_extension("session"));
	display_results("PHP PCRE Support:", check_extension("pcre"));
	# now check settings
	display_results("Safe Mode:", check_config("safe_mode"));
	# next check binaries
	display_results("check for grep:", check_binary("grep"));
	display_results("check for cat:", check_binary("cat"));
	display_results("check for php:", check_binary("php"));
	display_results("check for python:", check_binary("python"));
	display_results("check for awk:", check_binary("awk"));
	display_results("check for du:", check_binary("du"));
	display_results("check for wget:", check_binary("wget"));
	display_results("check for unzip:", check_binary("unzip"));
	display_results("check for cksfv:", check_binary("cksfv"));
?>
</table>
<?php
if ($_POST['op'] == "2") {
# install

}
}

?>