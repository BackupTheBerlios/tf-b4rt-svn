<?php
/* $Id: admin_updateUser.php 102 2006-07-31 05:01:28Z msn_exploder $ */
$user_id = strtolower($user_id);
if (IsUser($user_id) && ($user_id != $org_user_id)) {
	echo DisplayHead(_ADMINISTRATION);

	// Admin Menu
	echo displayMenu();

	echo "<br><div align=\"center\">"._TRYDIFFERENTUSERID."<br><strong>".$user_id."</strong> "._HASBEENUSED."<br><br><br>";

	echo "[<a href=\"admin.php?op=editUser&user_id=".$org_user_id."\">"._RETURNTOEDIT." ".$org_user_id."</a>]</div><br><br><br>";

	echo DisplayFoot(true,true);
} else {
	// Admin is changing id or password through edit screen
	if(($user_id == $cfg["user"] || $cfg["user"] == $org_user_id) && $pass1 != "")
	{
		// this will expire the user
		$_SESSION['user'] = md5($cfg["pagetitle"]);
	}
	updateThisUser($user_id, $org_user_id, $pass1, $userType, $hideOffline);
	AuditAction($cfg["constants"]["admin"], _EDITUSER.": ".$user_id);
	header("location: admin.php");
}
?>