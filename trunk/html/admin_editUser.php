<?php
/* $Id: admin_editUser.php 102 2006-07-31 05:01:28Z msn_exploder $ */
echo DisplayHead(_USERADMIN);
$editUserImage = "images/user.gif";
$selected_n = "selected";
$selected_a = "";
$hide_checked = "";
// Admin Menu
echo displayMenu();
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
echo "<div align=\"center\">";
echo "<table width=\"100%\" border=1 bordercolor=\"".$cfg["table_admin_border"]."\" cellpadding=\"2\" cellspacing=\"0\" bgcolor=\"".$cfg["table_data_bg"]."\">";
echo "<tr><td colspan=6 bgcolor=\"".$cfg["table_header_bg"]."\" background=\"themes/".$cfg["theme"]."/images/bar.gif\">";
echo "<img src=\"".$editUserImage."\" width=17 height=14 border=0>&nbsp;&nbsp;&nbsp;<font class=\"title\">"._EDITUSER.": ".$user_id."</font>";
echo "</td></tr><tr><td align=\"center\">";
?>
<table width="100%" border="0" cellpadding="3" cellspacing="0">
<tr>
	<td width="50%" bgcolor="<?php echo $cfg["table_data_bg"]?>" valign="top">
	<div align="center">
	<table border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td align="right"><?php echo $user_id." "._JOINED ?>:&nbsp;</td>
		<td><strong><?php echo date(_DATETIMEFORMAT, $time_created) ?></strong></td>
	</tr>
	<tr>
		<td align="right"><?php echo _LASTVISIT ?>:&nbsp;</td>
		<td><strong><?php echo date(_DATETIMEFORMAT, $last_visit) ?></strong></td>
	</tr>
	<tr>
		<td colspan="2" align="center">&nbsp;</td>
	</tr>
	<tr>
		<td align="right"><?php echo _UPLOADPARTICIPATION ?>:&nbsp;</td>
		<td>
			<table width="200" border="0" cellpadding="0" cellspacing="0">
			<tr>
				<td background="themes/<?php echo $cfg["theme"] ?>/images/proglass.gif" width="<?php echo $user_percent*2 ?>"><img src="images/blank.gif" width="1" height="12" border="0"></td>
				<td background="themes/<?php echo $cfg["theme"] ?>/images/noglass.gif" width="<?php echo (200 - ($user_percent*2)) ?>"><img src="images/blank.gif" width="1" height="12" border="0"></td>
			</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td align="right"><?php echo _UPLOADS ?>:&nbsp;</td>
		<td><strong><?php echo $user_activity ?></strong></td>
	</tr>
	<tr>
		<td align="right"><?php echo _PERCENTPARTICIPATION ?>:&nbsp;</td>
		<td><strong><?php echo $user_percent ?>%</strong></td>
	</tr>
	<tr>
		<td colspan="2" align="center"><div align="center" class="tiny">(<?php echo _PARTICIPATIONSTATEMENT. " ".$cfg['days_to_keep']." "._DAYS ?>)</div><br></td>
	</tr>
	<tr>
		<td align="right"><?php echo _TOTALPAGEVIEWS ?>:&nbsp;</td>
		<td><strong><?php echo $hits ?></strong></td>
	</tr>
	<tr>
		<td align="right" valign="top"><?php echo _THEME ?>:&nbsp;</td>
		<td valign="top"><strong><?php echo $theme ?></strong><br></td>
	</tr>
	<tr>
		<td align="right" valign="top"><?php echo _LANGUAGE ?>:&nbsp;</td>
		<td valign="top"><strong><?php echo GetLanguageFromFile($language_file) ?></strong><br><br></td>
	</tr>
	<tr>
		<td align="right" valign="top"><?php echo _USERTYPE ?>:&nbsp;</td>
		<td valign="top"><strong><?php echo $user_type ?></strong><br></td>
	</tr>
	<tr>
		<td colspan="2" align="center"><div align="center">[<a href="admin.php?op=showUserActivity&user_id=<?php echo $user_id ?>"><?php echo _USERSACTIVITY ?></a>]</div></td>
	</tr>
	</table>
	</div>
	</td>
	<td valign="top" bgcolor="<?php echo $cfg["body_data_bg"] ?>">
	<div align="center">
	<table cellpadding="5" cellspacing="0" border="0">
	<form name="theForm" action="admin.php?op=updateUser" method="post" onsubmit="return validateUser()">
	<tr>
		<td align="right"><?php echo _USER ?>:</td>
		<td>
		<input name="user_id" type="Text" value="<?php echo $user_id ?>" size="15">
		<input name="org_user_id" type="Hidden" value="<?php echo $user_id ?>">
		</td>
	</tr>
	<tr>
		<td align="right"><?php echo _NEWPASSWORD ?>:</td>
		<td>
		<input name="pass1" type="Password" value="" size="15">
		</td>
	</tr>
	<tr>
		<td align="right"><?php echo _CONFIRMPASSWORD ?>:</td>
		<td>
		<input name="pass2" type="Password" value="" size="15">
		</td>
	</tr>
	<tr>
		<td align="right"><?php echo _USERTYPE ?>:</td>
		<td>
<?php if ($user_level <= 1) { ?>
		<select name="userType">
			<option value="0" <?php echo $selected_n ?>><?php echo _NORMALUSER ?></option>
			<option value="1" <?php echo $selected_a ?>><?php echo _ADMINISTRATOR ?></option>
		</select>
<?php } else { ?>
		<strong><?php echo _SUPERADMIN ?></strong>
		<input type="Hidden" name="userType" value="2">
<?php } ?>
		</td>
	</tr>
	<tr>
		<td colspan="2">
		<input name="hideOffline" type="Checkbox" value="1" <?php echo $hide_checked ?>> <?php echo _HIDEOFFLINEUSERS ?><br>
		</td>
	</tr>
	<tr>
		<td align="center" colspan="2">
		<input type="Submit" value="<?php echo _UPDATE ?>">
		</td>
	</tr>
	</form>
	</table>
	</div>
	</td>
</tr>
</table>

<script language="JavaScript">
function validateUser() {
	var msg = ""
	if (theForm.user_id.value == "") {
		msg = msg + "* <?php echo _USERIDREQUIRED ?>\n";
		theForm.user_id.focus();
	}
	if (theForm.pass1.value != "" || theForm.pass2.value != "") {
		if (theForm.pass1.value.length <= 5 || theForm.pass2.value.length <= 5) {
			msg = msg + "* <?php echo _PASSWORDLENGTH ?>\n";
			theForm.pass1.focus();
		}
		if (theForm.pass1.value != theForm.pass2.value) {
			msg = msg + "* <?php echo _PASSWORDNOTMATCH ?>\n";
			theForm.pass1.value = "";
			theForm.pass2.value = "";
			theForm.pass1.focus();
		}
	}
	if (msg != "") {
		alert("<?php echo _PLEASECHECKFOLLOWING ?>:\n\n" + msg);
		return false;
	} else {
		return true;
	}
}
</script>

<?php
echo "</td></tr>";
echo "</table></div>";
echo "<br><br>";
// Show User Section
echo displayUserSection();
echo "<br><br>";
echo DisplayFoot(true,true);
?>