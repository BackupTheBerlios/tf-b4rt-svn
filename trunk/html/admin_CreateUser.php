<?php
/* $Id: admin_CreateUser.php 102 2006-07-31 05:01:28Z msn_exploder $ */

$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/admin_CreateUser.tmpl");
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

$tmpl->pparse();
?>