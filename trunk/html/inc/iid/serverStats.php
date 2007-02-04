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
if ((!isset($cfg['user'])) || (isset($_REQUEST['cfg']))) {
	@ob_end_clean();
	@header("location: ../../index.php");
	exit();
}

/******************************************************************************/

// default-type
define('_DEFAULT_TYPE', 'all');

// init template-instance
tmplInitializeInstance($cfg["theme"], "page.serverStats.tmpl");

// request-vars
$type = (isset($_REQUEST['type'])) ? getRequestVar('type') : _DEFAULT_TYPE;

// types
$type_list = array();
array_push($type_list, array(
	'name' => "all",
	'selected' => ($type == "all") ? 1 : 0
	)
);
array_push($type_list, array(
	'name' => "drivespace",
	'selected' => ($type == "drivespace") ? 1 : 0
	)
);
array_push($type_list, array(
	'name' => "who",
	'selected' => ($type == "who") ? 1 : 0
	)
);
if ($cfg['enable_xfer'] == 1)
	array_push($type_list, array(
		'name' => "xfer",
		'selected' => ($type == "xfer") ? 1 : 0
		)
	);
$tmpl->setloop('type_list', $type_list);

// type-switch
switch ($type) {

	// all
	case "all":
		break;

	// drivespace
	case "drivespace":
		break;

	// who
	case "who":
		break;

	// xfer
	case "xfer":
		if ($cfg['enable_xfer'] != 1)
			exit();
		break;

	// default
	default:
		$tmpl->setvar('content', "Invalid Type");
		break;
}

// set vars
$tmpl->setvar('type', $type);

// more vars
tmplSetTitleBar($cfg["pagetitle"].' - Server Stats');
tmplSetFoot();
$tmpl->setvar('iid', $_REQUEST["iid"]);
$tmpl->setvar('mainMenu', mainMenu($_REQUEST["iid"]));

// parse template
$tmpl->pparse();

?>