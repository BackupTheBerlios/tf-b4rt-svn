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

// common functions
require_once('inc/functions/functions.common.php');

# create new template
if ((strpos($cfg['theme'], '/')) === false)
	$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/who.tmpl");
else
	$tmpl = new vlibTemplate("themes/tf_standard_themes/tmpl/who.tmpl");

$result = shell_exec("w");
$result2 = shell_exec("free -mo");

$tmpl->setvar('head', getHead(_SERVERSTATS));
$tmpl->setvar('getDriveSpaceBar', getDriveSpaceBar(getDriveSpace($cfg["path"])));
$tmpl->setvar('main_bgcolor', $cfg["main_bgcolor"]);
$tmpl->setvar('result', $result);
$tmpl->setvar('result2', $result2);
if (IsAdmin()) {
	$tmpl->setvar('is_admin', 1);
	$tmpl->setvar('RunningProcessInfo', RunningProcessInfo());
}
$tmpl->setvar('foot', getFoot());
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('iid', $_GET["iid"]);
$tmpl->pparse();

/**
 * RunningProcessInfo
 *
 */
function RunningProcessInfo() {
	global $cfg;
	require_once("inc/classes/ClientHandler.php");
	# create new template
	if ((strpos($cfg['theme'], '/')) === false)
		$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/inc.RunningProcessInfo.tmpl");
	else
		$tmpl = new vlibTemplate("themes/tf_standard_themes/tmpl/inc.RunningProcessInfo.tmpl");
	// first we need an array with all clients
	$clients = array('tornado', 'transmission', 'mainline', 'wget');
	// get informations
	$process_list = array();
	foreach($clients as $client) {
		$clientHandler = ClientHandler::getClientHandlerInstance($cfg,$client);
		$RunningProcessInfo = $clientHandler->printRunningClientsInfo();
		$pinfo = shell_exec("ps auxww | ".$cfg['bin_grep']." ". $clientHandler->binClient ." | ".$cfg['bin_grep']." -v grep");
		array_push($process_list, array(
			'client' => $client,
			'RunningProcessInfo' => $RunningProcessInfo,
			'pinfo' => $pinfo,
			)
		);
	}
	$tmpl->setloop('process_list', $process_list);
	// grab the template
	$output = $tmpl->grab();
	return $output;
}

?>