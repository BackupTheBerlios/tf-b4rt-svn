<?php
echo DisplayHead(_ADMINUSERACTIVITY);
// Admin Menu
displayMenu();
// display Activity for user
displayActivity($min, $user_id, $srchFile, $srchAction);
echo DisplayFoot(true,true);
?>