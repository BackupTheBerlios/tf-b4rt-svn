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
	$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/admin/editUser.tmpl");
else
	$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/admin/editUser.tmpl");

$tmpl->setvar('head', getHead("Administration - RSS"));
$tmpl->setvar('menu', getMenu());

$editUserImage = "images/user.gif";
$selected_n = "selected";
$selected_a = "";
$hide_checked = "";

$total_activity = GetActivityCount();
$sql= "SELECT user_id, hits, last_visit, time_created, user_level, hide_offline, theme, language_file FROM tf_users WHERE user_id=".$db->qstr($user_id);
list($user_id, $hits, $last_visit, $time_created, $user_level, $hide_offline, $theme, $language_file) = $db->GetRow($sql);
$user_type = _NORMALUSER;
if ($user_level == 1) {
	$user_type = _ADMINISTRATOR;
	$selected_n = "";
	$selected_a = "selected";
	$editUserImage = "images/admin_user.gif";
}
if ($user_level >= 2) {
	$user_type = _SUPERADMIN;
	$editUserImage = "images/superadmin.gif";
}
if ($hide_offline == 1)
	$hide_checked = "checked";
$user_activity = GetActivityCount($user_id);
if ($user_activity == 0)
	$user_percent = 0;
else
	$user_percent = number_format(($user_activity/$total_activity)*100);

$tmpl->setvar('table_admin_border', $cfg["table_admin_border"]);
$tmpl->setvar('table_data_bg', $cfg["table_data_bg"]);
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('editUserImage', $editUserImage);
$tmpl->setvar('_EDITUSER', _EDITUSER);
$tmpl->setvar('user_id', $user_id);
$tmpl->setvar('_JOINED', _JOINED);
$tmpl->setvar('time_created', date(_DATETIMEFORMAT, $time_created));
$tmpl->setvar('_LASTVISIT', _LASTVISIT);
$tmpl->setvar('last_visit', date(_DATETIMEFORMAT, $last_visit));
$tmpl->setvar('_UPLOADPARTICIPATION', _UPLOADPARTICIPATION);
$tmpl->setvar('percent1', $user_percent*2);
$tmpl->setvar('percent2', (200 - ($user_percent*2)));
$tmpl->setvar('_UPLOADS', _UPLOADS);
$tmpl->setvar('user_activity', $user_activity);
$tmpl->setvar('_PERCENTPARTICIPATION', _PERCENTPARTICIPATION);
$tmpl->setvar('user_percent', $user_percent);
$tmpl->setvar('_PARTICIPATIONSTATEMENT', _PARTICIPATIONSTATEMENT);
$tmpl->setvar('days_to_keep', $cfg["days_to_keep"]);
$tmpl->setvar('_DAYS', _DAYS);
$tmpl->setvar('_TOTALPAGEVIEWS', _TOTALPAGEVIEWS);
$tmpl->setvar('hits', $hits);
$tmpl->setvar('_THEME', _THEME);
$tmpl->setvar('theme', $theme);
$tmpl->setvar('_LANGUAGE', _LANGUAGE);
$tmpl->setvar('language_file', GetLanguageFromFile($language_file));
$tmpl->setvar('_USERTYPE', _USERTYPE);
$tmpl->setvar('user_type', $user_type);
$tmpl->setvar('_USERSACTIVITY', _USERSACTIVITY);
$tmpl->setvar('user_id', $user_id);
$tmpl->setvar('body_data_bg', $cfg["body_data_bg"]);
$tmpl->setvar('_USER', _USER);
$tmpl->setvar('_NEWPASSWORD', _NEWPASSWORD);
$tmpl->setvar('_CONFIRMPASSWORD', _CONFIRMPASSWORD);
$tmpl->setvar('user_level', $user_level);
$tmpl->setvar('_NORMALUSER', _NORMALUSER);
$tmpl->setvar('_ADMINISTRATOR', _ADMINISTRATOR);
$tmpl->setvar('selected_n', $selected_n);
$tmpl->setvar('selected_a', $selected_a);
$tmpl->setvar('_SUPERADMIN', _SUPERADMIN);
$tmpl->setvar('hide_checked', $hide_checked);
$tmpl->setvar('_HIDEOFFLINEUSERS', _HIDEOFFLINEUSERS);
$tmpl->setvar('_UPDATE', _UPDATE);
$tmpl->setvar('_USERIDREQUIRED', _USERIDREQUIRED);
$tmpl->setvar('_PASSWORDLENGTH', _PASSWORDLENGTH);
$tmpl->setvar('_PASSWORDNOTMATCH', _PASSWORDNOTMATCH);
$tmpl->setvar('_PLEASECHECKFOLLOWING', _PLEASECHECKFOLLOWING);
$tmpl->setvar('getUserSection', getUserSection());
$tmpl->setvar('foot', getFoot(true,true));
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('index_page', $cfg["index_page"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('iid', $_GET["iid"]);
$tmpl->pparse();

?>