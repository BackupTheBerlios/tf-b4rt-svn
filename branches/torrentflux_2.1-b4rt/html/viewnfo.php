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

// contributed by NovaKing -- thanks duder!

include_once("config.php");
include_once("functions.php");

// target-file
$file = getRequestVar("path");
$fileIsValid = (isValidPath($file, ".nfo") || isValidPath($file, ".txt") || isValidPath($file, ".log"));
if (!$fileIsValid) {
	AuditAction($cfg["constants"]["error"], "Invalid NFO-file : ".$cfg["user"]." tried to access ".$file);
	showErrorPage("Invalid NFO-file : <br>".$file);
}

// get content
$folder = htmlspecialchars( substr( $file, 0, strrpos( $file, "/" ) ) );
if( ( $output = @file_get_contents( $cfg["path"] . $file ) ) === false )
    $output = "Error opening NFO File.";

// output
DisplayHead("View NFO");

?>
<div align="center" style="width: 740px;">
<a href="<?php echo "viewnfo.php?path=$file&dos=1"; ?>">DOS Format</a> :-:
<a href="<?php echo "viewnfo.php?path=$file&win=1"; ?>">WIN Format</a> :-:
<a href="dir.php?dir=<?=$folder;?>">Back</a>
</div>
<pre style="font-size: 10pt; font-family: 'Courier New', monospace;">
<?php
    if( ( empty( $_GET["dos"] ) && empty( $_GET["win"] ) ) || !empty( $_GET["dos"] ) )
        echo htmlentities( $output, ENT_COMPAT, "cp866" );
    else
        echo htmlentities( $output );
?>
</pre>
<?php
DisplayFoot();
?>