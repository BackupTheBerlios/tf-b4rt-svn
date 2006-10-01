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
define('_FILE_THIS',$_SERVER['SCRIPT_NAME']);
define('_REVISION', array_shift(explode(" ",trim(array_pop(explode(":",'$Revision$'))))));

// fields
$errors = 0;
$errorsMessages = array();
$warnings = 0;
$warningsMessages = array();
$dbsupported = 0;
$dbsupportedMessages = array();

// -----------------------------------------------------------------------------
// Main
// -----------------------------------------------------------------------------

// ob-start
if (@ob_get_level() == 0)
	@ob_start();

// head
sendHead();

// header
send('<h1>torrentflux-b4rt - check - Revision '._REVISION.'</h1>');

// PHP-Version
send('<h2>1. PHP-Version</h2>');
$phpVersion = 'PHP-Version : <em>'.PHP_VERSION.'</em> ';
if (PHP_VERSION < 4.3) {
	$phpVersion .= '<font color="red">Failed</font>';
	$errors++;
} else {
	$phpVersion .= '<font color="green">Passed</font>';
}
send($phpVersion);

// PHP-Extensions
send('<h2>2. PHP-Extensions</h2>');
send("<ul>");
$loadedExtensions = get_loaded_extensions();
// session
$session = '<li>session ';
if (in_array("session", $loadedExtensions)) {
	$session .= '<font color="green">Passed</font>';
} else {
	$session .= '<font color="red">Failed</font>';
	$errors++;
}
send($session.'</li>');
// pcre
$pcre = '<li>pcre ';
if (in_array("pcre", $loadedExtensions)) {
	$pcre .= '<font color="green">Passed</font>';
} else {
	$pcre .= '<font color="red">Failed</font>';
	$errors++;
}
send($pcre.'</li>');
// sockets
$sockets = '<li>sockets ';
if (in_array("sockets", $loadedExtensions)) {
	$sockets .= '<font color="green">Passed</font>';
} else {
	$sockets .= '<font color="red">Failed</font>';
	$warnings++;
}
send($sockets.'</li>');
//
send("</ul>");

// PHP-Configuration
send('<h2>3. PHP-Configuration</h2>');
send("<ul>");
// safe_mode
$safe_mode = '<li>safe_mode ';
if ((ini_get("safe_mode")) == 0) {
	$safe_mode .= '<font color="green">Passed</font>';
} else {
	$safe_mode .= '<font color="red">Failed</font>';
	$errors++;
}
send($safe_mode.'</li>');
// allow_url_fopen
$allow_url_fopen = '<li>allow_url_fopen ';
if ((ini_get("allow_url_fopen")) == 1) {
	$allow_url_fopen .= '<font color="green">Passed</font>';
} else {
	$allow_url_fopen .= '<font color="red">Failed</font>';
	$warnings++;
}
send($allow_url_fopen.'</li>');
//
send("</ul>");

// PHP-Database-Support
send('<h2>4. PHP-Database-Support</h2>');
send("<ul>");
// mysql
$mysql = '<li>mysql ';
if (function_exists('mysql_connect')) {
	$mysql .= '<font color="green">Passed</font>';
	$dbsupported++;
} else {
	$mysql .= '<font color="red">Failed</font>';
}
send($mysql.'</li>');
// sqlite
$sqlite = '<li>sqlite ';
if (function_exists('sqlite_open')) {
	$sqlite .= '<font color="green">Passed</font>';
	$dbsupported++;
} else {
	$sqlite .= '<font color="red">Failed</font>';
}
send($sqlite.'</li>');
// postgres
$postgres = '<li>postgres ';
if (function_exists('pg_connect')) {
	$postgres .= '<font color="green">Passed</font>';
	$dbsupported++;
} else {
	$postgres .= '<font color="red">Failed</font>';
}
send($postgres.'</li>');
send("</ul>");

// OS-Specific
// get os
$osString = php_uname('s');
if (isset($osString)) {
    if (!(stristr($osString, 'linux') === false)) /* linux */
    	define('_OS', 1);
    else if (!(stristr($osString, 'bsd') === false)) /* bsd */
    	define('_OS', 2);
    else
    	define('_OS', 0);
} else {
	define('_OS', 0);
}
send('<h2>5. OS-Specific ('.$osString.' '.php_uname('r').')</h2>');
switch (_OS) {
	case 1: // linux
		send('No Special Requirements on Linux-OS. <font color="green">Passed</font>');
		break;
	case 2: // bsd
		send("<ul>");
		// posix
		$posix = '<li>posix ';
		if ((function_exists('posix_geteuid')) && (function_exists('posix_getpwuid'))) {
			$posix .= '<font color="green">Passed</font>';
		} else {
			$posix .= '<font color="red">Failed</font>';
			$warnings++;
		}
		send($posix.'</li>');
		send("</ul>");
		break;
	case 0: // unknown
	default:
		send("OS not supported.<br>");
		break;
}

// summary
send('<h1>Summary</h1>');

send("Warnings : ".$warnings."<br>");
send("Errors : ".$errors."<br>");
send("Databases supported : ".$dbsupported."<br>");


// foot
sendFoot();

// ob-end + exit
@ob_end_flush();
exit();

// -----------------------------------------------------------------------------
// functions
// -----------------------------------------------------------------------------

/**
 * send head-portion
 *
 */
function sendHead() {
	send('<html>');
	send('<head>');
	send('<title>torrentflux-b4rt - check - Revision '._REVISION.'</title>');
	send('<style type="text/css">');
	send('FONT {FONT-FAMILY: Verdana,Helvetica; FONT-SIZE: 12px}');
	send('BODY {FONT-FAMILY: Verdana,Helvetica; FONT-SIZE: 12px}');
	send('P {FONT-FAMILY: Verdana,Helvetica; FONT-SIZE: 12px}');
	send('H1 {FONT-FAMILY: Verdana,Helvetica; FONT-SIZE: 15px}');
	send('H2 {FONT-FAMILY: Verdana,Helvetica; FONT-SIZE: 14px}');
	send('H3 {FONT-FAMILY: Verdana,Helvetica; FONT-SIZE: 13px}');
	send('</style>');
	send('</head>');
	send('<body topmargin="8" leftmargin="5" bgcolor="#DEDEDE">');
}

/**
 * send foot-portion
 *
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