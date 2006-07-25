<?php
$newUser = strtolower($newUser);
if (IsUser($newUser)) {
	echo DisplayHead(_ADMINISTRATION);

	// Admin Menu
	displayMenu();

	echo "<br><div align=\"center\">"._TRYDIFFERENTUSERID."<br><strong>".$newUser."</strong> "._HASBEENUSED."</div><br><br><br>";

	echo DisplayFoot(true,true);
} else {
	addNewUser($newUser, $pass1, $userType);
	AuditAction($cfg["constants"]["admin"], _NEWUSER.": ".$newUser);
	header("location: admin.php?op=CreateUser");
}
?>