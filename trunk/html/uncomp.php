<?php

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

echo DisplayHead('Uncompressing File', false);
echo "<body bgcolor=".$cfg["main_bgcolor"]." leftmargin=0 topmargin=0 marginwidth=0 marginheight=0>";

if((isset($_GET['file'])) && ($_GET['file'] != "")) {
    echo '<form method="POST" name="pass_form">';
    echo '<p>Please enter password for the file: <input type="text" name="passwd" size="60"></p>';
    echo '<p><input type="submit" value="   OK   " name="OK"></p>';
	echo '<input type="hidden" name="file" value="'. str_replace('%2F', '/', urlencode($cfg['path'].$_GET['file'])).'">';
	echo '<input type="hidden" name="dir" value="'. str_replace('%2F', '/',urlencode($cfg['path'].$_GET['dir'])) .'">';
	echo '<input type="hidden" name="type" value="'. $_GET['type'] .'">';
	echo '<input type="hidden" name="exec" value="true">';
    echo '</form>';
}
if((isset($_POST['exec'])) && ($_POST['exec'] == true)) {
	$passwd = $_POST['passwd'];
	if( $passwd == "") {
		$passwd = "-";
	}
	// @usage	  ./uncompress.php "pathtofile" "extractdir" "typeofcompression" "uncompressor-bin" "password"
	$cmd = $cfg['bin_php']." uncompress.php " .$_POST['file'] ." ". $_POST['dir'] ." ". $_POST['type'];
	if (strcasecmp('rar', $_GET['type']) == 0) {
		$cmd .= " ". $cfg['bin_unrar'];
	} else if (strcasecmp('zip', $_GET['type']) == 0) {
		$cmd .= " ". $cfg['bin_unzip'];
	}
	$cmd .= " ". $passwd;

	// os-switch
	switch (_OS) {
        case 1: // linux
            $cmd .= ' 2>&1';
        break;
        case 2: // bsd (snip from khr0n0s)
            $cmd .= ' 2>&1 &';
        break;
	}
	$handle = popen($cmd, 'r' );
	while(!feof($handle)) {
	    $buff = fgets($handle,30);
	    echo nl2br($buff) ;
	    @ob_flush();
	    @flush();
	}
	pclose($handle);
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