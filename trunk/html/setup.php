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
define('_VERSION_LOCAL', '.version');
define('_VERSION_THIS', trim(getDataFromFile(_VERSION_LOCAL)));
define('_TITLE', _NAME.' '._VERSION_THIS.' - Setup');
define('_DIR', dirname($_SERVER["SCRIPT_FILENAME"])."/");
define('_FILE_DBCONF', 'inc/config/config.db.php');
define('_FILE_THIS', $_SERVER['SCRIPT_NAME']);

// Database-Types
$databaseTypes = array();
$databaseTypes['mysql'] = 'mysql_connect';
$databaseTypes['sqlite'] = 'sqlite_open';
$databaseTypes['postgres'] = 'pg_connect';

// sql-queries
$queries = array();

// -----------------------------------------------------------------------------
// mysql
// -----------------------------------------------------------------------------
$cdb = 'mysql';

// sql-queries : Test
$cqt = 'test';
$queries[$cqt][$cdb] = array();
array_push($queries[$cqt][$cdb], "CREATE TABLE tf_test ( tf_key VARCHAR(255) NOT NULL default '', tf_value TEXT NOT NULL, PRIMARY KEY (tf_key) ) TYPE=MyISAM");
array_push($queries[$cqt][$cdb], "DROP TABLE tf_test");

// sql-queries : Create
$cqt = 'create';
$queries[$cqt][$cdb] = array();
array_push($queries[$cqt][$cdb], "CREATE TABLE tf_test ( tf_key VARCHAR(255) NOT NULL default '', tf_value TEXT NOT NULL, PRIMARY KEY (tf_key) ) TYPE=MyISAM");
array_push($queries[$cqt][$cdb], "DROP TABLE tf_test");

// -----------------------------------------------------------------------------
// sqlite
// -----------------------------------------------------------------------------
$cdb = 'sqlite';

// sql-queries : Test
$cqt = 'test';
$queries[$cqt][$cdb] = array();
array_push($queries[$cqt][$cdb], "CREATE TABLE tf_test ( tf_key VARCHAR(255) NOT NULL default '', tf_value TEXT NOT NULL, PRIMARY KEY (tf_key) )");
array_push($queries[$cqt][$cdb], "DROP TABLE tf_test");

// sql-queries : Create
$cqt = 'create';
$queries[$cqt][$cdb] = array();
array_push($queries[$cqt][$cdb], "CREATE TABLE tf_test ( tf_key VARCHAR(255) NOT NULL default '', tf_value TEXT NOT NULL, PRIMARY KEY (tf_key) )");
array_push($queries[$cqt][$cdb], "DROP TABLE tf_test");

// -----------------------------------------------------------------------------
// postgres
// -----------------------------------------------------------------------------
$cdb = 'postgres';

// sql-queries : Test
$cqt = 'test';
$queries[$cqt][$cdb] = array();
array_push($queries[$cqt][$cdb], "CREATE TABLE tf_test ( tf_key VARCHAR(255) NOT NULL DEFAULT '', tf_value TEXT DEFAULT '' NOT NULL, PRIMARY KEY (tf_key) )");
array_push($queries[$cqt][$cdb], "DROP TABLE tf_test");

// sql-queries : Create
$cqt = 'create';
$queries[$cqt][$cdb] = array();
array_push($queries[$cqt][$cdb], "CREATE TABLE tf_test ( tf_key VARCHAR(255) NOT NULL DEFAULT '', tf_value TEXT DEFAULT '' NOT NULL, PRIMARY KEY (tf_key) )");
array_push($queries[$cqt][$cdb], "DROP TABLE tf_test");

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
			//
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
	// create
	$line = '<tr><td colspan="2">Create Database :';
	$line .= '<input name="db_create" type="Checkbox" value="true"';
	if (isset($_REQUEST["db_create"]))
		$line .= ' checked">';
	else
		$line .= '>';
	$line .= '</td></tr>';
	send($line);
	send('</table>');
	send('<input type="Hidden" name="db_type" value="'.$type.'">');
	send('<input type="Hidden" name="14" value="">');
	send('<input type="submit" value="Continue">');
	send('</form>');
} elseif (isset($_REQUEST["14"])) {                                             // 14 - Database - creation + test
	$type = $_REQUEST["db_type"];
	sendHead(" - Database");
	send("<h1>"._TITLE."</h1>");
	send("<h2>Database - Creation + Test - ".$type."</h2>");
	$paramsOk = true;
	if (isset($_REQUEST["db_host"]))
		$host = $_REQUEST["db_host"];
	else
		$paramsOk = false;
	if (isset($_REQUEST["db_create"]))
		$create = true;
	else
		$create = false;
	if (isset($_REQUEST["db_pcon"]))
		$pcon = "true";
	else
		$pcon = "false";
	switch ($type) {
		case "mysql":
		case "postgres":
			if (isset($_REQUEST["db_name"]))
				$name = $_REQUEST["db_name"];
			else
				$paramsOk = false;
			if (isset($_REQUEST["db_user"]))
				$user = $_REQUEST["db_user"];
			else
				$paramsOk = false;
			if (isset($_REQUEST["db_pass"]))
				$pass = $_REQUEST["db_pass"];
			else
				$paramsOk = false;
			break;
		case "sqlite":
			$name = "";
			$user = "";
			$pass = "";
	}
	$databaseTestOk = false;
	$databaseError = "";
	// create + test
	if ($paramsOk) {
		$databaseExists = true;
		if (($create) && ($type != "sqlite")) {
			$dbCon = getAdoConnection($type, $host, $user, $pass);
			if (!$dbCon) {
				$databaseExists = false;
				$databaseTestOk = false;
				$databaseError = "cannot connect to database.";
			} else {
				$sqlState = "CREATE DATABASE ".$name;
				$dbCon->Execute($sqlState);
				if ($dbCon->ErrorNo() == 0) {
					send("created database <em>".$name."</em>.<br>");
					$databaseExists = true;
				} else { // damn there was an error
					$databaseExists = false;
					$databaseTestOk = false;
					$databaseError = "cannot create database <em>".$name."</em>.";
				}
				// close ado-connection
				$dbCon->Close();
			}
			unset($dbCon);
		}
		if ($databaseExists) {
			$dbCon = getAdoConnection($type, $host, $user, $pass, $name);
			if (!$dbCon) {
				$databaseTestOk = false;
				$databaseError = "cannot connect to database.";
			} else {
				send('<ul>');
				foreach ($queries['test'][$type] as $databaseTypeName => $databaseQuery) {
					send('<li><em>'.$databaseQuery.'</em> : ');
					$dbCon->Execute($databaseQuery);
					if ($dbCon->ErrorNo() == 0) {
						send('<font color="green">Ok</font></li>');
						$databaseTestOk = true;
					} else { // damn there was an error
						send('<font color="red">Error</font></li>');
						// close ado-connection
						$dbCon->Close();
						$databaseTestOk = false;
						break;
					}
				}
				send('</ul>');
			}
		}
	} else {
		$databaseTestOk = false;
		$databaseError = "config error.";
	}
	// output
	if ($databaseTestOk) {
		send('<font color="green"><strong>Ok</strong></font><br>');
		send("<h2>Next : Write Config File</h2>");
		send("Please ensure this script can write to the dir <em>"._DIR."inc/config/</em><p>");
		send('<form name="setup" action="' . _FILE_THIS . '" method="post">');
		send('<input type="Hidden" name="db_type" value="'.$type.'">');
		send('<input type="Hidden" name="db_host" value="'.$host.'">');
		send('<input type="Hidden" name="db_name" value="'.$name.'">');
		send('<input type="Hidden" name="db_user" value="'.$user.'">');
		send('<input type="Hidden" name="db_pass" value="'.$pass.'">');
		send('<input type="Hidden" name="db_pcon" value="'.$pcon.'">');
		send('<input type="Hidden" name="15" value="">');
		send('<input type="submit" value="Continue">');
	} else {
		send('<font color="red"><strong>Error</strong></font><br>');
		send($databaseError."<p>");
		send('<form name="setup" action="' . _FILE_THIS . '" method="post">');
		send('<input type="Hidden" name="db_type" value="'.$type.'">');
		send('<input type="Hidden" name="13" value="">');
		if (isset($_REQUEST["db_name"]))
			send('<input type="Hidden" name="db_host" value="'.$_REQUEST["db_host"].'">');
		if (isset($_REQUEST["db_name"]))
			send('<input type="Hidden" name="db_name" value="'.$_REQUEST["db_name"].'">');
		if (isset($_REQUEST["db_user"]))
			send('<input type="Hidden" name="db_user" value="'.$_REQUEST["db_user"].'">');
		if (isset($_REQUEST["db_pass"]))
			send('<input type="Hidden" name="db_pass" value="'.$_REQUEST["db_pass"].'">');
		if (isset($_REQUEST["db_pcon"]))
			send('<input type="Hidden" name="db_pcon" value="'.$_REQUEST["db_pcon"].'">');
		if (isset($_REQUEST["db_create"]))
			send('<input type="Hidden" name="db_create" value="'.$_REQUEST["db_create"].'">');
		send('<input type="submit" value="Back">');
	}
	send('</form>');
} elseif (isset($_REQUEST["15"])) {                                             // 15 - Database - config-file
	sendHead(" - Database");
	send("<h1>"._TITLE."</h1>");
	send("<h2>Database - Config-File</h2>");
	$type = $_REQUEST["db_type"];
	$host = $_REQUEST["db_host"];
	$name = $_REQUEST["db_name"];
	$user = $_REQUEST["db_user"];
	$pass = $_REQUEST["db_pass"];
	$pcon = $_REQUEST["db_pcon"];
	// write file
	$databaseConfWriteOk = false;
	$databaseConfWriteError = "";
	$databaseConfContent = "";
	writeDatabaseConfig($type, $host, $user, $pass, $name, $pcon);
	// output
	if ($databaseConfWriteOk) {
		send('<font color="green"><strong>Ok</strong></font><br>');
		send('database-config-file <em>'._DIR._FILE_DBCONF.'</em> written.');
	} else {
		send('<font color="red"><strong>Error</strong></font><br>');
		send($databaseConfWriteError."<p>");
		send('to perform this step manual paste the following content to the database-config-file <em>'._DIR._FILE_DBCONF.'</em> : <p>');
		send('<textarea cols="81" rows="33">'.$databaseConfContent.'</textarea>');
		send("<p>Note : You must write this file before you can continue !");
	}
	send("<h2>Next : Create Tables</h2>");
	sendButton(16);
} elseif (isset($_REQUEST["16"])) {                                             // 16 - Database - table-creation
	sendHead(" - Database");
	send("<h1>"._TITLE."</h1>");
	send("<h2>Database - Create Tables</h2>");
	if (is_file(_FILE_DBCONF)) {
		require_once(_FILE_DBCONF);
		$databaseTableCreation = false;
		$databaseError = "";
		$dbCon = getAdoConnection($cfg["db_type"], $cfg["db_host"], $cfg["db_user"], $cfg["db_pass"], $cfg["db_name"]);
		if (!$dbCon) {
			$databaseTableCreation = false;
			$databaseError = "cannot connect to database.";
		} else {
			send('<ul>');
			foreach ($queries['create'][$cfg["db_type"]] as $databaseTypeName => $databaseQuery) {
				send('<li><em>'.$databaseQuery.'</em> : ');
				$dbCon->Execute($databaseQuery);
				if ($dbCon->ErrorNo() == 0) {
					send('<font color="green">Ok</font></li>');
					$databaseTableCreation = true;
				} else { // damn there was an error
					send('<font color="red">Error</font></li>');
					// close ado-connection
					$dbCon->Close();
					$databaseTableCreation = false;
					break;
				}
			}
			send('</ul>');
		}
		if ($databaseTableCreation) {
			send('<font color="green"><strong>Ok</strong></font><br>');
			send('database-tables created.');
			send("<h2>Next : Insert Data</h2>");
			sendButton(17);
		} else {
			send('<font color="red"><strong>Error</strong></font><br>');
			send($databaseError."<p>");
		}
	} else {
		send('<font color="red"><strong>Error</strong></font><br>');
		send('database-config-file <em>'._DIR._FILE_DBCONF.'</em> missing. setup cannot continue.');
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
 * write the db-conf file.
 *
 * @param $type
 * @param $host
 * @param $user
 * @param $pass
 * @param $name
 * @param $pcon
 * @return boolean
 */
function writeDatabaseConfig($type, $host, $user, $pass, $name, $pcon) {
	global $databaseConfWriteOk, $databaseConfWriteError, $databaseConfContent;
	$databaseConfContent = '<?php

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
$cfg["db_type"] = "'.$type.'";  // Databae-Type : mysql/sqlite/postgres
$cfg["db_host"] = "'.$host.'";  // Database host computer name or IP
$cfg["db_name"] = "'.$name.'";  // Name of the Database
$cfg["db_user"] = "'.$user.'";  // Username for Database
$cfg["db_pass"] = "'.$pass.'";  // Password for Database
$cfg["db_pcon"] = '.$pcon.';  // Persistent Connection enabled : true/false
/******************************************************************************/

?>';
	$configFile = false;
	$configFile = @fopen(_DIR._FILE_DBCONF, "w");
	if (!$configFile) {
		$databaseConfWriteOk = false;
		$databaseConfWriteError = "cannot open config-file <em>"._DIR._FILE_DBCONF."</em> for writing.";
		return false;
	}
	$result = @fwrite($configFile, $databaseConfContent);
	@fclose($configFile);
	if ($result === false) {
		$databaseConfWriteOk = false;
		$databaseConfWriteError = "cannot write content to config-file <em>"._DIR._FILE_DBCONF."</em>.";
		return false;
	}
	$databaseConfWriteOk = true;
	return true;
}

/**
 * get a ado-connection to our database.
 *
 * @param $type
 * @param $host
 * @param $user
 * @param $pass
 * @param $name
 * @return database-connection or false on error
 */
function getAdoConnection($type, $host, $user, $pass, $name = "") {
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