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

$min = getRequestVar('min');
if (empty($min))
	$min=0;
$user_id = getRequestVar('user_id');
$srchFile = getRequestVar('srchFile');
$srchAction = getRequestVar('srchAction');

// create template-instance
$tmpl = getTemplateInstance($cfg["theme"], "admin/showUserActivity.tmpl");

// set vars
$tmpl->setvar('Activity', getActivity($min, $user_id, $srchFile, $srchAction));
$tmpl->setvar('menu', getMenu());
$tmpl->setvar('head', getHead($cfg['_ADMINUSERACTIVITY']));
$tmpl->setvar('foot', getFoot(true));
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('iid', $_GET["iid"]);

// parse template
$tmpl->pparse();

?>