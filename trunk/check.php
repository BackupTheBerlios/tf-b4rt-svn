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

// counter
$warnings = 0;
$errors = 0;
$dbsupported = 0;

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


// PHP-Extensions
send('<h2>2. PHP-Extensions</h2>');


// PHP-Configuration
send('<h2>3. PHP-Configuration</h2>');


// PHP-Database-Support
send('<h2>4. PHP-Database-Support</h2>');


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
		break;
	case 2: // bsd
		break;
	case 0: // unknown
	default:
		send("OS not supported.<br>");
		break;
}

// summary
send('<h1>Summary</h1>');


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