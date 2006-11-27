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
	header("location: ../../index.php");
	exit();
}

/******************************************************************************/

// init template-instance
tmplInitializeInstance($cfg["theme"], "page.allServices.tmpl");

// set vars
$tmpl->setvar('result1', shell_exec("df -h ".$cfg["path"]));
$tmpl->setvar('result2', shell_exec("du -sh ".$cfg["path"]."*"));
$tmpl->setvar('result3', shell_exec("w"));
$tmpl->setvar('result4', shell_exec("free -mo"));
$tmpl->setvar('netstatConnectionsSum', netstatConnectionsSum());
$tmpl->setvar('netstatPortList', netstatPortList());
$tmpl->setvar('netstatHostList', netstatHostList());
//
$tmpl->setvar('_DRIVESPACE', $cfg['_DRIVESPACE']);
$tmpl->setvar('_ID_HOSTS', $cfg['_ID_HOSTS']);
$tmpl->setvar('_ID_PORTS', $cfg['_ID_PORTS']);
$tmpl->setvar('_ID_CONNECTIONS', $cfg['_ID_CONNECTIONS']);
$tmpl->setvar('_SERVERSTATS', $cfg['_SERVERSTATS']);
//
tmplSetTitleBar($cfg["pagetitle"].' - All Services');
tmplSetDriveSpaceBar();
tmplSetFoot();
$tmpl->setvar('iid', $_REQUEST["iid"]);
$tmpl->pparse();

?>