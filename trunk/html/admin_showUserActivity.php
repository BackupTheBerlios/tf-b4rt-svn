<?php
/* $Id: admin_showUserActivity.php 102 2006-07-31 05:01:28Z msn_exploder $ */
echo DisplayHead(_ADMINUSERACTIVITY);
// Admin Menu
echo displayMenu();
// display Activity for user
echo displayActivity($min, $user_id, $srchFile, $srchAction);
echo DisplayFoot(true,true);
?>