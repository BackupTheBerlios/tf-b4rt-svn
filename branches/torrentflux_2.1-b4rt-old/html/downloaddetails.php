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
$graph_width = "";
$background = "#000000";
$alias = getRequestVar('alias');
if (!empty($alias)) {
    // create AliasFile object
    $af = AliasFile::getAliasFileInstance($cfg["torrent_file_path"].$alias, $torrentowner, $cfg);
    for ($inx = 0; $inx < sizeof($af->errors); $inx++) {
        $error .= "<li style=\"font-size:10px;color:#ff0000;\">".$af->errors[$inx]."</li>";
    }
} else {
    die("fatal error torrent file not specified");
}

// Load saved settings
loadTorrentSettingsToConfig($torrent);

$torrentTotals = getTorrentTotals($torrent);
$torrentTotalsCurrent = getTorrentTotalsCurrent($torrent);
$upTotalCurrent = ($torrentTotalsCurrent["uptotal"]+0);
$downTotalCurrent = ($torrentTotalsCurrent["downtotal"]+0);
$upTotal =($torrentTotals["uptotal"]+0);
$downTotal = ($torrentTotals["downtotal"]+0);

// seeding-%
$torrentSize = $af->size+0;
$sharing = number_format((($upTotal / $torrentSize) * 100), 2);
$torrent_port = "";
$torrent_cons = "";
$label_max_download_rate = "";
$label_max_upload_rate = "";
$label_downTotal = formatFreeSpace($downTotal / 1048576);
$label_upTotal = formatFreeSpace($upTotal / 1048576);
$label_downTotalCurrent = "";
$label_upTotalCurrent = "";
$label_seeds = "";
$label_peers = "";
$label_maxcons = "";
$label_sharing = $sharing . '%';
if ($cfg["sharekill"] != 0)
    $label_sharekill = $cfg["sharekill"] . '%';
else
    $label_sharekill = '&#8734';
if (($af->running == 1) && ($alias != "")) {
    $label_downTotalCurrent = formatFreeSpace($downTotalCurrent / 1048576);
    $label_upTotalCurrent = formatFreeSpace($upTotalCurrent / 1048576);
    $label_seeds = $af->seeds;
    $label_peers = $af->peers;
    $torrent_pid = getTorrentPid($alias);
    $torrent_port = netstatPortByPid($torrent_pid);
    $torrent_cons = netstatConnectionsByPid($torrent_pid);
    if ($cfg["max_download_rate"] != 0)
        $label_max_download_rate = " (".number_format($cfg["max_download_rate"], 2).")";
    else
        $label_max_download_rate = ' (&#8734)';
    if ($cfg["max_upload_rate"] != 0)
        $label_max_upload_rate = " (".number_format($cfg["max_upload_rate"], 2).")";
    else
        $label_max_upload_rate = ' (&#8734)';
    $label_maxcons = " (".$cfg["maxcons"].")";
}

if ($af->percent_done < 0) {
    $af->percent_done = round(($af->percent_done*-1)-100,1);
    $af->time_left = _INCOMPLETE;
}

if($af->percent_done < 1)
    $graph_width = "1";
else
    $graph_width = $af->percent_done;

if($af->percent_done >= 100) {
    $af->percent_done = 100;
    $background = "#0000ff";
}

$torrentLabel = $torrent;
if(strlen($torrentLabel) >= 39)
    $torrentLabel = substr($torrent, 0, 35)."...";

$hd = getStatusImage($af);

DisplayHead(_DOWNLOADDETAILS, false, "5", $af->percent_done."% ");

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
    echo $torrentLabel."<font class=\"tiny\"> (".formatBytesToKBMGGB($af->size).")</font>";
?>
        </td>
        <td align="right" width="16">
        <?php
        if ($af->running == 1)
        	echo "<a href=\"downloadhosts.php?torrent=".$torrent."&alias=".$alias."\">";
        echo "<img src=\"images/".$hd->image."\" width=\"16\" height=\"16\" border=\"0\" title=\"".$hd->title."\">";
        if ($af->running == 1)
        	echo "</a>";
        ?>
        </td>
    </tr>
    </table>
    <table bgcolor="<?php echo $cfg["table_header_bg"] ?>" width="352" cellpadding="1">
     <tr>
         <td>
        <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td background="themes/<?php echo $cfg["theme"] ?>/images/proglass.gif"><img src="images/blank.gif" width="<?php echo $graph_width * 3.5 ?>" height="13" border="0"></td>
            <td background="themes/<?php echo $cfg["theme"] ?>/images/noglass.gif" bgcolor="<?php echo $background ?>"><img src="images/blank.gif" width="<?php echo (100 - $graph_width) * 3.5 ?>" height="13" border="0"></td>
        </tr>
        </table>
        </td>
     </tr>
     <tr><td>
        <div align="center">
        <table border="0" cellpadding="2" cellspacing="2" width="90%">
        <tr>
            <td align="right"><div class="tiny"><?php echo _ESTIMATEDTIME ?>:</div></td>
            <td colspan="3" bgcolor="<?php echo $cfg["body_data_bg"] ?>"><div class="tiny"><?php echo "<strong>".$af->time_left."</strong>" ?></div></td>
        </tr>
        <tr>
            <td align="right"><div class="tiny"><?php echo _PERCENTDONE ?>:</div></td>
            <td bgcolor="<?php echo $cfg["body_data_bg"] ?>" nowrap><div class="tiny"><?php echo "<strong>".$af->percent_done."%</strong>" ?></div></td>
            <td align="right"><div class="tiny"><?php echo _USER ?>:</div></td>
            <td bgcolor="<?php echo $cfg["body_data_bg"] ?>" nowrap><div class="tiny"><?php echo "<strong>".$torrentowner."</strong>" ?></div></td>
        </tr>
        <tr>
            <td align="right"><div class="tiny"><?php echo _DOWNLOADSPEED ?>:</div></td>
            <td bgcolor="<?php echo $cfg["body_data_bg"] ?>" nowrap><div class="tiny"><?php echo "<strong>".$af->down_speed.$label_max_download_rate."</strong>" ?></div></td>
            <td align="right"><div class="tiny"><?php echo _UPLOADSPEED ?>:</div></td>
            <td bgcolor="<?php echo $cfg["body_data_bg"] ?>" nowrap><div class="tiny"><?php echo "<strong>".$af->up_speed.$label_max_upload_rate."</strong>" ?></div></td>
        </tr>
        <tr>
            <td align="right"><div class="tiny">Down:</div></td>
            <td bgcolor="<?php echo $cfg["body_data_bg"] ?>" nowrap><div class="tiny"><?php echo "<strong>".$label_downTotalCurrent."</strong>" ?></div></td>
            <td align="right"><div class="tiny">Up:</div></td>
            <td bgcolor="<?php echo $cfg["body_data_bg"] ?>" nowrap><div class="tiny"><?php echo "<strong>".$label_upTotalCurrent."</strong>" ?></div></td>
        </tr>
        <tr>
            <td align="right"><div class="tiny">Down-Total:</div></td>
            <td bgcolor="<?php echo $cfg["body_data_bg"] ?>" nowrap><div class="tiny"><?php echo "<strong>".$label_downTotal."</strong>" ?></div></td>
            <td align="right"><div class="tiny">Up-Total:</div></td>
            <td bgcolor="<?php echo $cfg["body_data_bg"] ?>" nowrap><div class="tiny"><?php echo "<strong>".$label_upTotal."</strong>" ?></div></td>
        </tr>
        <tr>
            <td align="right"><div class="tiny">Seeds:</div></td>
            <td bgcolor="<?php echo $cfg["body_data_bg"] ?>" nowrap><div class="tiny"><?php echo "<strong>".$label_seeds."</strong>" ?></div></td>
            <td align="right"><div class="tiny">Peers:</div></td>
            <td bgcolor="<?php echo $cfg["body_data_bg"] ?>" nowrap><div class="tiny"><?php echo "<strong>".$label_peers."</strong>" ?></div></td>
        </tr>
        <tr>
            <td align="right"><div class="tiny"><?php echo _ID_PORT ?>:</div></td>
            <td bgcolor="<?php echo $cfg["body_data_bg"] ?>" nowrap><div class="tiny"><?php echo "<strong>".$torrent_port."</strong>" ?></div></td>
            <td align="right"><div class="tiny"><?php echo _ID_CONNECTIONS ?>:</div></td>
            <td bgcolor="<?php echo $cfg["body_data_bg"] ?>" nowrap><div class="tiny"><?php echo "<strong>".$torrent_cons.$label_maxcons."</strong>" ?></div></td>
        </tr>
        <tr>
            <td align="right"><div class="tiny"><?php echo _SHARING ?>:</div></td>
            <td bgcolor="<?php echo $cfg["body_data_bg"] ?>" nowrap><div class="tiny"><?php echo "<strong>".$label_sharing."</strong>" ?></div></td>
            <td align="right"><div class="tiny">Seed Until:</div></td>
            <td bgcolor="<?php echo $cfg["body_data_bg"] ?>" nowrap><div class="tiny"><?php echo "<strong>".$label_sharekill."</strong>" ?></div></td>
        </tr>
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

DisplayFoot(false);

?>