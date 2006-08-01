<?php
/* $Id: admin_CreateUser.php 102 2006-07-31 05:01:28Z msn_exploder $ */

$tmpl = new vlibTemplate("themes/".$cfg["default_theme"]."/tmpl/admin_CreateUser.tmpl");
$tmpl->setvar('DisplayHead', DisplayHead(_USERADMIN));
$tmpl->setvar('displayMenu', displayMenu());
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
$tmpl->setvar('displayUserSection', displayUserSection());
$tmpl->setvar('DisplayFoot', DisplayFoot(true,true));

$tmpl->pparse();
?>