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

// prevent direct invocation
if (!isset($cfg['user'])) {
	@ob_end_clean();
	@header("location: ../../index.php");
	exit();
}

/******************************************************************************/

// common functions
require_once('inc/functions/functions.common.php');

// request-vars
$transfer = getRequestVar('transfer');
if (empty($transfer))
	@error("missing params", "index.php?iid=index", "", array('transfer'));

// validate transfer
if (isValidTransfer($transfer) !== true) {
	AuditAction($cfg["constants"]["error"], "INVALID TRANSFER: ".$transfer);
	@error("Invalid Transfer", "", "", array($transfer));
}

// init template-instance
tmplInitializeInstance($cfg["theme"], "page.transferSettings.tmpl");

// set transfer vars
$tmpl->setvar('transfer', $transfer);
$transferLabel = (strlen($transfer) >= 39) ? substr($transfer, 0, 35)."..." : $transfer;
$tmpl->setvar('transferLabel', $transferLabel);












// title + foot
tmplSetFoot(false);
tmplSetTitleBar($transferLabel." - Settings", false);

// iid
$tmpl->setvar('iid', $_REQUEST["iid"]);

// parse template
$tmpl->pparse();

?>