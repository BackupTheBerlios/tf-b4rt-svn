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

// init template-instance
tmplInitializeInstance($cfg["theme"], "page.who.tmpl");

// set vars
$tmpl->setvar('result1', shell_exec("w"));
$tmpl->setvar('result2', shell_exec("free -mo"));
if ($cfg['isAdmin']) {
	// array with all clients
	$clients = array('tornado', 'transmission', 'mainline', 'wget', 'nzbperl');
	// get informations
	$process_list = array();
	foreach ($clients as $client) {
		$clientHandler = ClientHandler::getInstance($client);
		array_push($process_list, array(
			'client' => $client,
			'RunningProcessInfo' => $clientHandler->runningProcessInfo(),
			'pinfo' => shell_exec("ps auxww | ".$cfg['bin_grep']." ".$clientHandler->binClient." | ".$cfg['bin_grep']." -v grep")
			)
		);
	}
	$tmpl->setloop('process_list', $process_list);
}
//
tmplSetTitleBar($cfg["pagetitle"].' - '.$cfg['_SERVERSTATS']);
tmplSetDriveSpaceBar();
tmplSetFoot();
$tmpl->setvar('iid', $_REQUEST["iid"]);

// parse template
$tmpl->pparse();

?>