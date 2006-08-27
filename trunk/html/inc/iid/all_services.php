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

# create new template
if ((strpos($cfg['theme'], '/')) === false)
	$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/all_services.tmpl");
else
	$tmpl = new vlibTemplate("themes/tf_standard_themes/tmpl/all_services.tmpl");

$result = shell_exec("df -h ".$cfg["path"]);
$result2 = shell_exec("du -sh ".$cfg["path"]."*");
$result4 = shell_exec("w");
$result5 = shell_exec("free -mo");

$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('index_page', $cfg["index_page"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('head', getHead(_ALL));
$tmpl->setvar('getDriveSpaceBar', getDriveSpaceBar(getDriveSpace($cfg["path"])));
$tmpl->setvar('main_bgcolor', $cfg["main_bgcolor"]);
$tmpl->setvar('_DRIVESPACE', _DRIVESPACE);
$tmpl->setvar('result', $result);
$tmpl->setvar('result2', $result2);
$tmpl->setvar('_SERVERSTATS', _SERVERSTATS);
$tmpl->setvar('result4', $result4);
$tmpl->setvar('result5', $result5);
$tmpl->setvar('_ID_CONNECTIONS', _ID_CONNECTIONS);
$tmpl->setvar('netstatConnectionsSum', netstatConnectionsSum());
$tmpl->setvar('_ID_PORTS', _ID_PORTS);
$tmpl->setvar('netstatPortList', netstatPortList());
$tmpl->setvar('_ID_HOSTS', _ID_HOSTS);
$tmpl->setvar('netstatHostList', netstatHostList());
$tmpl->setvar('foot', getFoot());
$tmpl->setvar('iid', $_GET["iid"]);
$tmpl->pparse();

?>