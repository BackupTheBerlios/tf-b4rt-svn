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

include("config.php");
include("functions.php");

DisplayHead(_REN_TITLE, false);

if((isset($_GET['start'])) && ($_GET['start'] == true)) {
?>
    <form method="POST" action="renameFolder.php" name="move_form">
    <p><?php echo _REN_FILE; ?><input disabled="true" type="text" name="fileFromDis" size="91" value="<?php echo $_GET['file']; ?>"></p>
    <p><?php echo _REN_STRING; ?><input type="text" name="fileTo" size="91" value="<?php echo $_GET['file']; ?>"></p>
    <p><input type="submit" value="   OK   " name="OK">
    <input type="hidden" name="dir" value="<?php echo $_GET['dir']; ?>"/>
    <input type="hidden" name="fileFrom" value="<?php echo $_GET['file']; ?>"/>
    </p>
    </form>
<?php
} else {
    $cmd = "mv \"".$cfg["path"].$_POST['dir'].$_POST['fileFrom']."\" \"".$cfg["path"].$_POST['dir'].$_POST['fileTo']."\"";
    $cmd .= ' 2>&1';
	$handle = popen($cmd, 'r' );
    // get the output and print it.
    $gotError = -1;
    while(!feof($handle)) {
        $buff = fgets($handle,30);
        echo nl2br($buff) ;
        @ob_flush();
        @flush();
        $gotError = $gotError + 1;
    }
    pclose($handle);
    if($gotError <= 0) {
        echo _REN_DONE ."<br>";
        echo 'renamed <em>'.$_POST['fileFrom'].'</em> to <em>'.$_POST['fileTo'].'</em>';
    } else {
        echo _REN_ERROR;
    }
}
?>
     </td></tr>
    </table>
[<a href="#" onclick="window.opener.location.reload();window.close();">Close Window</a>]
    </td>
    </tr>
    </table>
<?php DisplayTorrentFluxLink(); ?>
   </body>
  </html>
</html>