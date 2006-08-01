<?php
/* $Id: admin_showUserActivity.php 102 2006-07-31 05:01:28Z msn_exploder $ */
echo getHead(_ADMINUSERACTIVITY);
// Admin Menu
echo getMenu();
// display Activity for user
echo getActivity($min, $user_id, $srchFile, $srchAction);
echo getFoot(true,true);
?>