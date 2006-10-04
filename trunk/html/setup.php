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

// defines
define('_NAME', 'torrentflux-b4rt');
define('_REVISION', array_shift(explode(" ",trim(array_pop(explode(":",'$Revision$'))))));
define('_VERSION_LOCAL','.version');
define('_VERSION_THIS', trim(getDataFromFile(_VERSION_LOCAL)));
define('_TITLE', _NAME.' '._VERSION_THIS.' - Setup');
define('_FILE_THIS',$_SERVER['SCRIPT_NAME']);

// Database-Types
$databaseTypes = array();
$databaseTypes['mysql'] = 'mysql_connect';
$databaseTypes['sqlite'] = 'sqlite_open';
$databaseTypes['postgres'] = 'pg_connect';

// sql-queries test
$databaseQueriesTest = array();

// mysql
$databaseQueriesTest['mysql'] = array();
array_push($databaseQueriesTest['mysql'], "CREATE TABLE tf_test ( tf_key VARCHAR(255) NOT NULL default '', tf_value TEXT NOT NULL, PRIMARY KEY (tf_key) ) TYPE=MyISAM");
array_push($databaseQueriesTest['mysql'], "DROP TABLE tf_test");

// sqlite
$databaseQueriesTest['sqlite'] = array();
array_push($databaseQueriesTest['sqlite'], "CREATE TABLE tf_test ( tf_key VARCHAR(255) NOT NULL default '', tf_value TEXT NOT NULL, PRIMARY KEY (tf_key) )");
array_push($databaseQueriesTest['sqlite'], "DROP TABLE tf_test");

// postgres
$databaseQueriesTest['postgres'] = array();
array_push($databaseQueriesTest['postgres'], "CREATE TABLE tf_test ( tf_key VARCHAR(255) NOT NULL DEFAULT '', tf_value TEXT DEFAULT '' NOT NULL, PRIMARY KEY (tf_key) )");
array_push($databaseQueriesTest['postgres'], "DROP TABLE tf_test");


// -----------------------------------------------------------------------------
// Main
// -----------------------------------------------------------------------------

// ob-start
if (@ob_get_level() == 0)
	@ob_start();

if (isset($_REQUEST["1"])) {                                                    // 1 - Database
	sendHead(" - Database");
	send("<h1>"._TITLE."</h1>");
	send("<h2>Database</h2>");
	sendButton(11);
} elseif (isset($_REQUEST["11"])) {                                             // 11 - Database - type
	sendHead(" - Database");
	send("<h1>"._TITLE."</h1>");
	send("<h2>Database - Type</h2>");
	send('<form name="setup" action="' . _FILE_THIS . '" method="post">');
	send('<select name="db_type">');
	foreach ($databaseTypes as $databaseTypeName => $databaseTypeFunction) {
		$option = '<option value="'.$databaseTypeName.'"';
		if ((isset($_REQUEST["db_type"])) && ($_REQUEST["db_type"] == $databaseTypeName))
			$option .= ' selected';
		$option .= '>'.$databaseTypeName.'</option>';
		$option .= '</option>';
		send($option);
	}
	send('</select>');
	send('<input type="Hidden" name="12" value="">');
	send('<input type="submit" value="Continue">');
	send('</form>');
} elseif (isset($_REQUEST["12"])) {                                             // 12 - Database - type check
	if ((isset($_REQUEST["db_type"])) && ($databaseTypes[$_REQUEST["db_type"]] != "")) {
		$type = $_REQUEST["db_type"];
		sendHead(" - Database");
		send("<h1>"._TITLE."</h1>");
		send("<h2>Database - Type Check</h2>");
		if (function_exists($databaseTypes[$type])) {
			send('<font color="green"><strong>Ok</strong></font><br>');
			send('This PHP does support <em>'.$type.'</em>.<p>');
			send('<form name="setup" action="' . _FILE_THIS . '" method="post">');
			send('<input type="Hidden" name="db_type" value="'.$type.'">');
			send('<input type="Hidden" name="13" value="">');
			send('<input type="submit" value="Continue">');
			send('</form>');
		} else {
			send('<font color="red"><strong>Error</strong></font><br>');
			send('This PHP does not support <em>'.$type.'</em>.<p>');
			send('<form name="setup" action="' . _FILE_THIS . '" method="post">');
			send('<input type="Hidden" name="11" value="">');
			send('<input type="submit" value="Back">');
			send('</form>');
		}
	} else {
		header("location: setup.php?11");
		exit();
	}
} elseif (isset($_REQUEST["13"])) {                                             // 13 - Database - config
	if ((isset($_REQUEST["db_type"])) && ($databaseTypes[$_REQUEST["db_type"]] != "")) {
		$type = $_REQUEST["db_type"];
		sendHead(" - Database");
		send("<h1>"._TITLE."</h1>");
		send("<h2>Database - Config - ".$type."</h2>");
		send('<form name="setup" action="' . _FILE_THIS . '" method="post">');
		send('<table border="0">');
		switch ($type) {
			case "mysql":
			case "postgres":
				// host
				$line = '<tr><td>Host : </td>';
				$line .= '<td><input name="db_host" type="Text" maxlength="254" size="40"';
				if (isset($_REQUEST["db_host"]))
					$line .= ' value="'.$_REQUEST["db_host"].'">';
				else
					$line .= '>';
				$line .= '</td></tr>';
				send($line);
				// name
				$line = '<tr><td>Name : </td>';
				$line .= '<td><input name="db_name" type="Text" maxlength="254" size="40"';
				if (isset($_REQUEST["db_name"]))
					$line .= ' value="'.$_REQUEST["db_name"].'">';
				else
					$line .= '>';
				$line .= '</td></tr>';
				send($line);
				// user
				$line = '<tr><td>Username : </td>';
				$line .= '<td><input name="db_user" type="Text" maxlength="254" size="40"';
				if (isset($_REQUEST["db_user"]))
					$line .= ' value="'.$_REQUEST["db_user"].'">';
				else
					$line .= '>';
				$line .= '</td></tr>';
				send($line);
				// pass
				$line = '<tr><td>Password : </td>';
				$line .= '<td><input name="db_pass" type="Password" maxlength="254" size="40"';
				if (isset($_REQUEST["db_pass"]))
					$line .= ' value="'.$_REQUEST["db_pass"].'">';
				else
					$line .= '>';
				$line .= '</td></tr>';
				send($line);
				// pcon
				$line = '<tr><td colspan="2">Persistent Connection :';
				$line .= '<input name="db_pcon" type="Checkbox" value="true"';
				if (isset($_REQUEST["db_pcon"]))
					$line .= ' checked">';
				else
					$line .= '>';
				$line .= '</td></tr>';
				send($line);

				break;
			case "sqlite":
				// file
				$line = '<tr><td>Database-File : </td>';
				$line .= '<td><input name="db_host" type="Text" maxlength="254" size="40"';
				if (isset($_REQUEST["db_host"]))
					$line .= ' value="'.$_REQUEST["db_host"].'">';
				else
					$line .= '>';
				$line .= '</td></tr>';
				send($line);
		}
		send('</table>');
		send('<input type="Hidden" name="db_type" value="'.$type.'">');
		send('<input type="Hidden" name="14" value="">');
		send('<input type="submit" value="Continue">');
		send('</form>');
	} else {
		header("location: setup.php?11");
		exit();
	}
} elseif (isset($_REQUEST["2"])) {                                              // 2 - Configuration
	sendHead(" - Configuration");
	send("<h1>"._TITLE."</h1>");
	send("<h2>Configuration</h2>");
	// TODO
	send("<h2>Next : End</h2>");
	sendButton(3);
} elseif (isset($_REQUEST["3"])) {                                              // 3 - End
	sendHead(" - End");
	send("<h1>"._TITLE."</h1>");
	send("<h2>End</h2>");
	send("<p>Install completed.</p>");
	// TODO : del files
	//@unlink("setup.php")
	//@unlink("upgrade.php")
	send("<h2>Next : Login</h2>");
	send('<a href="login.php" title="Login">Login</a>');
} else {                                                                        // default
	sendHead();
	send("<h1>"._TITLE."</h1>");
	send("<p>This script will install "._NAME."</p>");
	send("<h2>Next : Database</h2>");
	sendButton(1);
}

// foot
sendFoot();

// ob-end + exit
@ob_end_flush();
exit();

// -----------------------------------------------------------------------------
// functions
// -----------------------------------------------------------------------------

/**
 * get a ado-connection to our database.
 *
 * @return database-connection or false on error
 */
function getAdoConnection($type, $host, $user, $pass, $name) {
	require_once('inc/lib/adodb/adodb.inc.php');
	// build DSN
	switch ($type) {
		case "mysql":
			$dsn = 'mysql://'.$user.':'.$pass.'@'.$host.'/'.$name;
			break;
		case "sqlite":
			$dsn = 'sqlite://'.$host;
			break;
		case "postgres":
			$dsn = 'postgres://'.$user.':'.$pass.'@'.$host.'/'.$name;
			break;
		default:
			return false;
	}
	// connect
	$db = @ ADONewConnection($dsn);
	// return db-connection
	return $db;
}

/**
 * load data of file
 *
 * @param $file the file
 * @return data
 */
function getDataFromFile($file) {
	if ($fileHandle = @fopen($file, 'r')) {
		$data = null;
		while (!@feof($fileHandle))
			$data .= @fgets($fileHandle, 8192);
		@fclose ($fileHandle);
		return $data;
	} else {
		return false;
	}
}

/**
 * send button
 */
function sendButton($name = "", $value = "") {
	send('<form name="setup" action="' . _FILE_THIS . '" method="post"><input type="Hidden" name="'.$name.'" value="'.$value.'"><input type="submit" value="Continue"></form><br>');
}

/**
 * send error
 */
function sendError($error = "") {
	send('<h2>Error</h2>');
	send('<font color="red"><strong>'.$error.'</strong></font>');
}

/**
 * send head
 */
function sendHead($title = "") {
	send('<html>');
	send('<head>');
	send('<title>'._TITLE.$title.'</title>');
	send('<style type="text/css">');
	send('font {font-family: Verdana,Helvetica; font-size: 12px}');
	send('body {font-family: Verdana,Helvetica; font-size: 12px}');
	send('p,td {font-family: Verdana,Helvetica; font-size: 12px}');
	send('h1 {font-family: Verdana,Helvetica; font-size: 15px}');
	send('h2 {font-family: Verdana,Helvetica; font-size: 14px}');
	send('h3 {font-family: Verdana,Helvetica; font-size: 13px}');
	send('</style>');
	send('</head>');
	send('<body topmargin="8" leftmargin="5" bgcolor="#FFFFFF">');
}

/**
 * send foot
 */
function sendFoot() {
	send('</body>');
	send('</html>');
}

/**
 * send - sends a string to the client
 */
function send($string = "") {
	echo $string;
	echo str_pad('', 4096)."\n";
	@ob_flush();
	@flush();
}

?>