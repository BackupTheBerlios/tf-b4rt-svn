<?php
/* $Id$ */
echo DisplayHead(_USERADMIN);
// Admin Menu
displayMenu();
echo "<div align=\"center\">";
echo "<table border=1 bordercolor=\"".$cfg["table_admin_border"]."\" cellpadding=\"2\" cellspacing=\"0\" bgcolor=\"".$cfg["table_data_bg"]."\">";
echo "<tr><td colspan=6 bgcolor=\"".$cfg["table_header_bg"]."\" background=\"themes/".$cfg["theme"]."/images/bar.gif\">";
echo "<img src=\"images/user.gif\" width=17 height=14 border=0>&nbsp;&nbsp;&nbsp;<font class=\"title\">"._NEWUSER."</font>";
echo "</td></tr><tr><td align=\"center\">";
?>
<div align="center">
	<table cellpadding="5" cellspacing="0" border="0">
	<form name="theForm" action="admin.php?op=addUser" method="post" onsubmit="return validateProfile()">
	<tr>
		<td align="right"><?php echo _USER ?>:</td>
		<td>
		<input name="newUser" type="Text" value="" size="15">
		</td>
	</tr>
	<tr>
		<td align="right"><?php echo _PASSWORD ?>:</td>
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
		<select name="userType">
			<option value="0"><?php echo _NORMALUSER ?></option>
			<option value="1"><?php echo _ADMINISTRATOR ?></option>
		</select>
		</td>
	</tr>
	<tr>
		<td align="center" colspan="2">
		<input type="Submit" value="<?php echo _CREATE ?>">
		</td>
	</tr>
	</form>
	</table>
	</div>
<br>
<script language="JavaScript">
function validateProfile() {
	var msg = ""
	if (theForm.newUser.value == "") {
		msg = msg + "* <?php echo _USERIDREQUIRED ?>\n";
		theForm.newUser.focus();
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
	} else {
		msg = msg + "* <?php echo _PASSWORDLENGTH ?>\n";
		theForm.pass1.focus();
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
displayUserSection();
echo "<br><br>";
echo DisplayFoot(true,true);
?>