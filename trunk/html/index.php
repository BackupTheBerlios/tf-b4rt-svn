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

// main.internal
require_once("inc/main.internal.php");

// iid-check
if (!isset($_REQUEST['iid'])) {
	// this is for tf 2.1 compat
	require_once("inc/functions/functions.dispatcher.php");
	compatIndexDispatch();
	// set iid-var
	$_REQUEST['iid'] = "index";
}

// include page
if (preg_match('/^[a-z]+$/', $_REQUEST['iid'])) {
	require_once("inc/iid/".$_REQUEST['iid'].".php");
} else {
	AuditAction($cfg["constants"]["error"], "Invalid Page-ID : ".htmlentities($_REQUEST['iid'], ENT_QUOTES));
	showErrorPage("Invalid Page-ID : <br>".htmlentities($_REQUEST['iid'], ENT_QUOTES));
}

?>