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

// includes
include_once("config.php");
include_once("functions.php");

if (isset($_REQUEST["u"])) {
    echo DisplayHead("dereferrer",false,'0;URL='.$_REQUEST["u"]);
    ?>
    <br>
    <div align="left" id="BodyLayer" name="BodyLayer" style="border: thin solid <?php echo $cfg["main_bgcolor"] ?>; position:relative; width:740; height:500; padding-left: 5px; padding-right: 5px; z-index:1; overflow: scroll; visibility: visible">
    <?php
    echo '<br><br><strong>';
    echo 'forwarding to <a href="'.$_REQUEST["u"].'">'.$_REQUEST["u"].'</a> ...';
    echo '</strong><br><br>';
    echo DisplayFoot(false,false);
} else {
    header("location: index.php");
    exit();
}
?>