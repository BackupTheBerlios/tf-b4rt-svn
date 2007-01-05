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

// init template-instance
tmplInitializeInstance($cfg["theme"], "page.transferLog.tmpl");

// set vars
$tmpl->setvar('transfer', $transfer);
$tmpl->setvar('transferLog', getTransferLog($transfer));

// refresh
// $tmpl->setvar('meta_refresh', '15;URL=index.php?iid=transferLog&transfer='.$transfer);

// shorten name if too long
if(strlen($transfer) >= 70)
	$transfer = substr($transfer, 0, 67)."...";

// more vars
tmplSetTitleBar($cfg["pagetitle"]." - Transfer-Log - ".$transfer, false);
tmplSetFoot(false);
$tmpl->setvar('iid', $_REQUEST["iid"]);

// parse template
$tmpl->pparse();

?>