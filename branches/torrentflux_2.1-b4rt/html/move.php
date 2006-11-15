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

DisplayHead(_MOVE_FILE_TITLE, false);

if((isset($_GET['start'])) && ($_GET['start'] == true)) {
?>
    <form method="POST" action="move.php" name="move_form">
    <p><?php echo _MOVE_FILE; ?><input disabled="true" type="text" name="T1" size="91" value="<?php echo $_GET['path']; ?>"></p>
    <p>
    <?php echo _MOVE_STRING; ?>
    <br>
    <?php
    if ((isset($cfg["move_paths"])) && (strlen($cfg["move_paths"]) > 0)) {
        echo '<select size="1" name="selector">';
        $dirs = split(":", trim($cfg["move_paths"]));
        foreach ($dirs as $dir) {
            $target = trim($dir);
            if ((strlen($target) > 0) && ((substr($target, 0, 1)) != ";"))
                echo "<option value=\"$target\">".$target."</option>\n";
        }
        echo '</select>';
        echo '<br>/<br>';
    }
    ?>
    <input name="dest" type="Text" maxlength="254" value="" size="55">
    <br><br>
    <input type="submit" value="   OK   " name="OK">
    <input type="hidden" name="file" value="<?php echo $_GET['path']; ?>"/>
    </p>
    </form>
<?php
} else {
	$targetDir = "";
	if (isset($_POST['dest'])) {
	    $tempDir = trim(urldecode($_POST['dest']));
	    if (strlen($tempDir) > 0)
	       $targetDir = $tempDir;
	}
    if (($targetDir == "") && (isset($_POST['selector'])))
	    $targetDir = trim(urldecode($_POST['selector']));
	$dirValid = true;
	if (strlen($targetDir) <= 0) {
	    $dirValid = false;
	} else {
        // we need absolute paths or stuff will end up in docroot
        // inform user .. dont move it into a fallback-dir which may be a hastle
        if ($targetDir{0} != '/') {
            echo "Target-dirs must be specified with absolute and not relative paths. <br>";
            $dirValid = false;
        }
	}
	// check dir
	if (($dirValid) && (checkDirectory($targetDir,0777))) {
	    $targetDir = checkDirPathString($targetDir);
    	// move
    	$cmd = "mv ".escapeshellarg($cfg["path"].$_POST['file'])." ".escapeshellarg($targetDir);
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
            echo "Done<br>";
            echo 'moved <em>'.$_POST['file'].'</em> to <em>'.$targetDir.'</em>';
        } else {
            echo "An error accured.";
        }
	} else {
	   echo "Invalid Target-dir : ".$targetDir;
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