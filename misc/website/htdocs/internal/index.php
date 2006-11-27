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

// functions
require_once('../functions.php');

// -----------------------------------------------------------------------------
// Main
// -----------------------------------------------------------------------------

// print page head
printPageHead();

// proxy-stats
echo "<h2>superadmin</h2>";
require_once('dbconf.php');
$db = mysql_connect($db_host, $db_user, $db_pass) or die('connect failed: ' . mysql_error());
if (!isset($db)) {
	echo '<font color="red">Error connecting to database.</font>';
} else {
	mysql_select_db($db_name, $db) or die('select db failed: ' . mysql_error());
	echo '<table border="1">';
	// torrentflux-b4rt
	$result = mysql_query("SELECT SUM(ct) FROM tfb4rt_proxystats WHERE ua LIKE '%torrentflux-b4rt%'", $db);
	$row = mysql_fetch_row($result);
	$ct = $row[0];
	mysql_free_result($result);
	echo "<tr><td>torrentflux-b4rt</td><td>".$ct."</td></tr>";
	// torrentflux_2.1-b4rt
	$result = mysql_query("SELECT SUM(ct) FROM tfb4rt_proxystats WHERE ua LIKE '%TorrentFlux/%'", $db);
	$row = mysql_fetch_row($result);
	$ct = $row[0];
	mysql_free_result($result);
	echo "<tr><td>torrentflux_2.1-b4rt</td><td>".$ct."</td></tr>";
	// unknown
	$result = mysql_query("SELECT SUM(ct) FROM tfb4rt_proxystats WHERE ua NOT LIKE '%orrent%'", $db);
	$row = mysql_fetch_row($result);
	$ct = $row[0];
	mysql_free_result($result);
	echo "<tr><td>unknown</td><td>".$ct."</td></tr>";
	// sum
	$result = mysql_query("SELECT SUM(ct) FROM tfb4rt_proxystats", $db);
	$row = mysql_fetch_row($result);
	$ct = $row[0];
	mysql_free_result($result);
	echo "<tr><td><strong>sum</strong></td><td><strong>".$ct."</strong></td></tr>";
	//
	echo "</table>";
	// details-table
	echo "<br>";
	$sort = "10"; // ct DESC
	if (isset($_REQUEST['s'])) {
		if (strlen($_REQUEST['s']) == 2)
			$sort = $_REQUEST['s'];
	}
	$sortColumn = $sort{0};
	$sortOrder = $sort{1};
	$query = 'SELECT * FROM tfb4rt_proxystats ORDER BY';
	switch ($sortColumn) {
		case 0:
			$query .= " ua";
			break;
		case 1:
			$query .= " ct";
			break;
		case 2:
			$query .= " ts";
			break;
	}
	switch ($sortOrder) {
		case 0:
			$query .= " DESC";
			break;
		case 1:
			$query .= " ASC";
			break;
	}
	echo '<table border="1">';
	echo "<tr>";
	// client
	echo '<th>';
	echo '<a href="'.$_SERVER['SCRIPT_NAME'].'?s=0';
	if ($sortOrder == 0)
		echo '1';
	else
		echo '0';
	echo '">client';
	if ($sortColumn == 0) {
		if ($sortOrder == 0)
			echo ' &uarr;';
		else
			echo ' &darr;';
	}
	echo '</a>';
	echo '</th>';
	// access-count
	echo '<th>';
	echo '<a href="'.$_SERVER['SCRIPT_NAME'].'?s=1';
	if ($sortOrder == 0)
		echo '1';
	else
		echo '0';
	echo '">access-count';
	if ($sortColumn == 1) {
		if ($sortOrder == 0)
			echo ' &uarr;';
		else
			echo ' &darr;';
	}
	echo '</a>';
	echo '</th>';
	// last access
	echo '<th>';
	echo '<a href="'.$_SERVER['SCRIPT_NAME'].'?s=2';
	if ($sortOrder == 0)
		echo '1';
	else
		echo '0';
	echo '">last access';
	if ($sortColumn == 2) {
		if ($sortOrder == 0)
			echo ' &uarr;';
		else
			echo ' &darr;';
	}
	echo '</a>';
	echo '</th>';
	//
	echo "</tr>";
	$result = mysql_query($query) or die('query failed: ' . mysql_error());
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		echo "<tr>";
		foreach ($line as $col_value)
			echo "<td>".htmlentities($col_value, ENT_QUOTES)."</td>";
		echo "</tr>";
	}
	echo "</table>";
	// close
	mysql_close($db);
}

// print page foot
printPageFoot();

// exit
exit();

// -----------------------------------------------------------------------------
// content
// -----------------------------------------------------------------------------

/**
 * prints page-head
 *
 */
function printPageHead() {
	global $version;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>torrentflux-b4rt - internal</title>
</head>
<body>
<?php
}

/**
 * prints page-foot
 *
 */
function printPageFoot() {
?>
</body>
</html>
<?php
}

/* EOF */ ?>