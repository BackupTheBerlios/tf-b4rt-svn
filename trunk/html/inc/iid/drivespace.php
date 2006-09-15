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

// create template-instance
$tmpl = tmplGetInstance($cfg["theme"], "drivespace.tmpl");

// set vars
$tmpl->setvar('result1', shell_exec("df -h ".$cfg["path"]));
$tmpl->setvar('result2', shell_exec("du -sh ".$cfg["path"]."*"));
//
$tmpl->setvar('driveSpaceBar', getDriveSpaceBar(getDriveSpace($cfg["path"])));
tmplSetTitleBar($cfg['_DRIVESPACE']);
tmplSetFoot();
$tmpl->setvar('iid', $_GET["iid"]);

// parse template
$tmpl->pparse();

?>