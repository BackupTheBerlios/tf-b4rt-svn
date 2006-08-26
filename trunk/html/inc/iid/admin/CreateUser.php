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
if (!ereg('^[^./][^/]*$', $cfg["theme"])) {
	$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/admin/CreateUser.tmpl");
} else {
	$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/admin/CreateUser.tmpl");
}
$tmpl->setvar('head', getHead(_USERADMIN));
$tmpl->setvar('menu', getMenu());
$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
$tmpl->setvar('table_data_bg', $cfg["table_data_bg"]);
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('_NEWUSER', _NEWUSER);
$tmpl->setvar('_USER', _USER);
$tmpl->setvar('_PASSWORD', _PASSWORD);
$tmpl->setvar('_CONFIRMPASSWORD', _CONFIRMPASSWORD);
$tmpl->setvar('_USERTYPE', _USERTYPE);
$tmpl->setvar('_NORMALUSER', _NORMALUSER);
$tmpl->setvar('_ADMINISTRATOR', _ADMINISTRATOR);
$tmpl->setvar('_CREATE', _CREATE);
$tmpl->setvar('_USERIDREQUIRED', _USERIDREQUIRED);
$tmpl->setvar('_PASSWORDLENGTH', _PASSWORDLENGTH);
$tmpl->setvar('_PASSWORDNOTMATCH', _PASSWORDNOTMATCH);
$tmpl->setvar('_PLEASECHECKFOLLOWING', _PLEASECHECKFOLLOWING);
$tmpl->setvar('userSection', getUserSection());
$tmpl->setvar('foot', getFoot(true,true));
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('index_page', $cfg["index_page"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('iid', $_GET["iid"]);
$tmpl->pparse();

?>