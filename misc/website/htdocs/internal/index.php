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

/* defines */
define('_FILE_HITS_OLD','hits-torrentflux_2.1-b4rt.txt');
define('_FILE_HITS_NEW','hits-torrentflux-b4rt.txt');
define('_FILE_HITS','hits.txt');

// functions
require_once('../functions.php');

// -----------------------------------------------------------------------------
// Main
// -----------------------------------------------------------------------------

// print page head
printPageHead();

// hit-stats
echo "<h2>Hits</h2>";
echo "<strong>Website</strong> : ".trim(getDataFromFile(_FILE_HITS))."<br>";
echo "<strong>superadmin-proxy old</strong> : ".trim(getDataFromFile(_FILE_HITS_OLD))."<br>";
echo "<strong>superadmin-proxy new</strong> : ".trim(getDataFromFile(_FILE_HITS_NEW))."<br>";

// proxy-stats
echo "<h2>superadmin-proxy</h2>";
require_once('dbconf.php');
$db = mysql_connect($db_host, $db_user, $db_pass) or die('connect failed: ' . mysql_error());	
if (!isset($db)) {
	echo '<font color="red">Error connecting to database.</font>';
} else {
	mysql_select_db($db_name, $db) or die('select db failed: ' . mysql_error());	
	$query = 'SELECT * FROM tfb4rt_proxystats ORDER BY ct DESC';
	$result = mysql_query($query) or die('query failed: ' . mysql_error());	
	echo '<table border="1">';
	echo "<tr>";
	echo "<th>client</th>";
	echo "<th>access-count</th>";
	echo "<th>last access</th>";
	echo "</tr>";
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		echo "<tr>";
		foreach ($line as $col_value) {
			echo "<td>".htmlentities($col_value, ENT_QUOTES)."</td>";
		}
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