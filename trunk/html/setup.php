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

// -----------------------------------------------------------------------------
// Main
// -----------------------------------------------------------------------------

// ob-start
if (@ob_get_level() == 0)
	@ob_start();

if (isset($_REQUEST["1"])) {                                     // 1 - Database
	sendHead(" - Database");
	send("<h1>"._TITLE."</h1>");
	send("<h2>Database</h2>");
	// TODO
	send("<h2>Next : Configuration</h2>");
	sendButton(2);
} elseif (isset($_REQUEST["2"])) {                          // 2 - Configuration
	sendHead(" - Configuration");
	send("<h1>"._TITLE."</h1>");
	send("<h2>Configuration</h2>");
	// TODO
	send("<h2>Next : End</h2>");
	sendButton(3);
} elseif (isset($_REQUEST["3"])) {                                    // 3 - End
	sendHead(" - End");
	send("<h1>"._TITLE."</h1>");
	send("<h2>End</h2>");
	send("<p>Install completed.</p>");
	// TODO : del files
	send("<h2>Next : Login</h2>");
	send('<a href="login.php" title="Login">Login</a>');
} else {                                                              // default
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
	send('p {font-family: Verdana,Helvetica; font-size: 12px}');
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