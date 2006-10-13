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

include_once("config.php");
include_once("functions.php");

$result = shell_exec("w");
$result2 = shell_exec("free -mo");

DisplayHead(_SERVERSTATS);
echo "<table width=\"740\" border=0 cellpadding=0 cellspacing=0><tr><td>";
echo displayDriveSpaceBar(getDriveSpace($cfg["path"]));
echo "</td></tr></table>";
?>

<br>

<div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid <?php echo $cfg["main_bgcolor"] ?>; position:relative; width:740; height:500; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">

<?php

echo "<pre>";
echo $result;
echo "<br><hr><br>";
echo $result2;
echo "</pre>";

if (IsAdmin())
{
    echo "<br><hr><br>";
    echo "<pre>";
    RunningProcessInfo();
    echo "</pre>";
}

echo "</div>";

DisplayFoot();

?>