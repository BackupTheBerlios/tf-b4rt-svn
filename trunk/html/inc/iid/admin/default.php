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

// create template-instance
$tmpl = tmplGetInstance($cfg["theme"], "admin/default.tmpl");

// set vars
$tmpl->setvar('userSection', getUserSection());
$tmpl->setvar('activity', getActivity());
//
$tmpl->setvar('menu', getMenu());
tmplSetTitleBar($cfg['_ADMINISTRATION']);
$tmpl->setvar('foot', getFoot(true));
$tmpl->setvar('iid', $_GET["iid"]);

// parse template
$tmpl->pparse();

?>