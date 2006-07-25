<?php
echo DisplayHead(_ADMINISTRATION);
// Admin Menu
displayMenu();
// Show User Section
displayUserSection();
echo "<br>";
// Display Activity
displayActivity($min);
echo DisplayFoot(true,true);
?>