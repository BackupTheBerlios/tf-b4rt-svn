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
include_once("AliasFile.php");

$torrent = getRequestVar('torrent');
$error = "";
$torrentowner = getOwner($torrent);
$background = "#000000";
$alias = getRequestVar('alias');
if (!empty($alias)) {
    // create AliasFile object
	$af = AliasFile::getAliasFileInstance($cfg["torrent_file_path"].$alias, $torrentowner, $cfg);
    for ($inx = 0; $inx < sizeof($af->errors); $inx++)
        $error .= "<li style=\"font-size:10px;color:#ff0000;\">".$af->errors[$inx]."</li>";
} else {
    die("fatal error torrent file not specified");
}

$torrent_cons = "";
if (($af->running == 1) && ($alias != "")) {
	$torrent_pid = getTorrentPid($alias);
	$torrent_cons = netstatConnectionsByPid($torrent_pid);
	$torrent_hosts = netstatHostsByPid($torrent_pid);
}

$torrentLabel = $torrent;
if(strlen($torrentLabel) >= 39)
  $torrentLabel = substr($torrent, 0, 35)."...";


$hd = getStatusImage($af);

DisplayHead(_ID_HOSTS, false, "15", $af->percent_done."% ");

?>
    <div align="center">
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td align="center">
<?php
    if ($error != "")
    {
        echo "<img src=\"images/error.gif\" width=16 height=16 border=0 title=\"ERROR\" align=\"absmiddle\">";
    }
    echo $torrentLabel."<font class=\"tiny\"> (".$torrent_cons." "._ID_HOSTS.")</font>";
?>
        </td>
        <td align="right" width="16">
        	<a href="downloaddetails.php?torrent=<?php echo $torrent ?>&alias=<?php echo $alias ?>">
        	 <img src="images/<?php echo $hd->image ?>" width=16 height=16 border=0 title="<?php echo $hd->title ?>">
        	</a>
        </td>
    </tr>
    </table>
    <table bgcolor="<?php echo $cfg["table_header_bg"] ?>" width=352 cellpadding=1>

     <tr><td>
        <div align="center">
        <table border="0" cellpadding="2" cellspacing="2" width="70%">

<?php

if (($torrent_hosts != null) && ($torrent_hosts != "")) {
	echo '<tr>';
	echo '<td><div class="tiny"><strong>';
	echo _ID_HOST;
	echo '</strong></div></td>';
	echo '<td><div class="tiny"><strong>';
	echo _ID_PORT;
	echo '</strong></div></td>';
	echo '</tr>';
	$hostAry = array_keys($torrent_hosts);
	foreach ($hostAry as $host) {
		$host = trim($host);
		if ($host != "") {
			echo '<tr>';
			echo '<td bgcolor="'.$cfg["body_data_bg"].'"><div class="tiny">';
			echo $host;
			echo '</div></td>';
			echo '<td bgcolor="'.$cfg["body_data_bg"].'"><div class="tiny">';
			echo $torrent_hosts[$host];
			echo '</div></td>';
			echo "</tr>\n";
		}
	}
}

?>



<?php
    if ($error != "")
    {
?>
        <tr>
            <td align="right" valign="top"><div class="tiny">Error(s):</div></td>
            <td colspan="3" width="66%"><div class="tiny"><?php echo "<strong class=\"tiny\">".$error."</strong>" ?></div></td>
        </tr>
<?php
    }
?>
        </table>
    </div>
</td></tr></table>
<?php

DisplayFoot(false,false);

?>