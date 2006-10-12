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

if(! isset($_SESSION['user'])) {
    header('location: login.php');
    exit();
}

// =============================================================================
// OUTPUT
// =============================================================================

echo '<body topmargin="8" bgcolor="'.$cfg["main_bgcolor"].'"';
if ($cfg['ui_indexrefresh'] != "0")
    echo 'onLoad="UpdateRefresh();"';
echo '>';
?>

<div align="center">

<?php
if ($messages != "")
{
?>
<table border="1" cellpadding="10" bgcolor="#ff9b9b">
<tr>
    <td><div align="center"><?php echo $messages ?></div></td>
</tr>
</table><br><br>
<?php
}
?>
<table border="0" cellpadding="0" cellspacing="0" width="<?php echo $cfg["ui_dim_main_w"] ?>"> <!-- b4rt-8 -->
<tr>
    <td>
<table border="1" bordercolor="<?php echo $cfg["table_border_dk"] ?>" cellpadding="4" cellspacing="0" width="100%">
<tr>
    <td colspan="2" background="themes/<?php echo $cfg["theme"] ?>/images/bar.gif">
    <?php DisplayTitleBar($cfg["pagetitle"]); ?>
    </td>
</tr>

<tr>
    <td bgcolor="<?php echo $cfg["table_header_bg"] ?>">
    <table width="100%" cellpadding="1" cellspacing="0" border="0">
    <tr>
        <form name="form_file" action="index.php" method="post" enctype="multipart/form-data">
        <td>
          <?php echo _SELECTFILE ?>:<br>
          <input type="File" name="upload_file" size="30">
          <select name="aid" size="1">
            <?php
                echo '<option value="1" selected>' .  _UPLOAD . '</option>';
                if(! $queueActive) {
                    echo '<option value="2">' .  _UPLOAD . '+Start</option>';
                } else {
                    if ( IsAdmin() ) {
                        echo '<option value="2">' .  _UPLOAD . '+Start</option>';
                        echo '<option value="3">' .  _UPLOAD . '+Queue</option>';
                    } else {
                        // Force Queuing if not an admin.
                        echo '<option value="3">' .  _UPLOAD . '+Queue</option>';
                    }
                }
            ?>
          </select><input type="Submit" value="Go">
          <br>
          <?php
            if ($cfg['enable_multiupload'] == 1) {
                echo '&nbsp;<a href="multiup.php"><img src="images/arrow.gif" width="9" height="9" title="' . _MULTIPLE_UPLOAD . '" border="0"></a>&nbsp;';
                echo '<a href="multiup.php">' . _MULTIPLE_UPLOAD . '</a>';
            }
          ?>
        </td>
        </form>
    </tr>

    <tr>
        <form name="form_url" action="index.php" method="post">
        <td>
        <hr>
        <?php echo _URLFILE ?>:<br>
        <input type="text" name="url_upload" size="40">
          <select name="aid" size="1">
            <?php
                echo '<option value="1" selected>' .  _GETFILE . '</option>';
                if(! $queueActive) {
                    echo '<option value="2">' .  _GETFILE . '+Start</option>';
                } else {
                    if ( IsAdmin() ) {
                        echo '<option value="2">' .  _GETFILE . '+Start</option>';
                        echo '<option value="3">' .  _GETFILE . '+Queue</option>';
                    } else {
                        // Force Queuing if not an admin.
                        echo '<option value="3">' .  _GETFILE . '+Queue</option>';
                    }
                }
            ?>
          </select><input type="Submit" value="Go">
        </td>
        </form>
    </tr>

<?php
if ($cfg['enable_wget'] == 1) {
?>
    <tr>
        <form name="form_wget" action="index.php" method="post">
        <td>
        <hr>
        URL for the File to wget :<br>
        <input type="text" name="url_wget" size="40">
        <input type="Submit" value="wget File">
        </td>
        </form>
    </tr>
<?php
}
if ($cfg["enable_search"])
{
?>
    <tr>
        <form name="form_search" action="torrentSearch.php" method="get">
        <td>
        <hr>
        Torrent <?php echo _SEARCH ?>:<br>
        <input type="text" name="searchterm" size="30" maxlength="50">
        <?php echo buildSearchEngineDDL($cfg["searchEngine"]); ?>
        <input type="Submit" value="<?php echo _SEARCH ?>">
        </td>
        </form>
    </tr>
<?php
}
?>
    </table>
    </td>
    <td bgcolor="<?php echo $cfg["table_data_bg"] ?>" width="310" valign="top">
        <table width="100%" cellpadding="1" border="0">
        <tr>
<?php
        // links
        if ($cfg["ui_displaylinks"] != "0") {
            echo '<td valign="top">';
            echo '<b>'. _TORRENTLINKS .':</b><br>';
            // Link Mod
            $arLinks = array();
            $arLinks = GetLinks();
            if ((isset($arLinks)) && (is_array($arLinks))) {
                foreach($arLinks as $link) {
                    if ($cfg["enable_dereferrer"] != "0")
                        echo "<a href=\"". _URL_DEREFERRER .$link['url']."\" target=\"_blank\"><img src=\"images/arrow.gif\" width=9 height=9 title=\"".$link['url']."\" border=0 align=\"baseline\">".$link['sitename']."</a><br>\n";
                    else
                        echo "<a href=\"".$link['url']."\" target=\"_blank\"><img src=\"images/arrow.gif\" width=9 height=9 title=\"".$link['url']."\" border=0 align=\"baseline\">".$link['sitename']."</a><br>\n";
                }
            }
            // Link Mod
            echo "</td>";
        }
        echo "<td bgcolor=\"".$cfg["table_data_bg"]."\" valign=\"top\">";
        //Good looking statistics hack by FLX : b4rt-82
        if ($cfg["enable_goodlookstats"] != "0") {
            $settingsHackStats = convertByteToArray($cfg["hack_goodlookstats_settings"]);
            if ($settingsHackStats[0] == 1) {
                echo "<b>". _DOWNLOADSPEED .":</b><br>";
                echo '<a href="who.php"><img src="images/download.gif" width="16" height="16" border="0" title="" align="absmiddle">'.@number_format($cfg["total_download"], 2).' kB/s</a><br>';
            }
            if ($settingsHackStats[1] == 1) {
                echo "<b>". _UPLOADSPEED .":</b><br>";
                echo '<a href="who.php"><img src="images/download.gif" width="16" height="16" border="0" title="" align="absmiddle">'.@number_format($cfg["total_upload"], 2).' kB/s</a><br>';
            }
            if ($settingsHackStats[2] == 1) {
                echo "<b>". _TOTALSPEED .":</b><br>";
                echo '<a href="who.php"><img src="images/download.gif" width="16" height="16" border="0" title="" align="absmiddle">'.@number_format($cfg["total_download"]+$cfg["total_upload"], 2).' kB/s</a><br>';
            }
            if ($settingsHackStats[3] == 1) {
                echo "<b>". _ID_CONNECTIONS .":</b><br>";
                echo '<a href="all_services.php"><img src="images/all.gif" width="16" height="16" border="0" title="" align="absmiddle">'.$netstatConnectionsSum.'</a><br>';
            }
            if ($settingsHackStats[4] == 1) {
                echo "<b>"._DRIVESPACE.":</b><br>";
                echo '<a href="drivespace.php"><img src="images/hdd.gif" width="16" height="16" border="0" title="" align="absmiddle">'.@formatFreeSpace($cfg["free_space"]).'</a><br>';
            }
            if ($settingsHackStats[5] == 1) {
                echo "<b>"._SERVERLOAD.":</b><br>";
                echo '<a href="who.php"><img src="images/who.gif" width="16" height="16" border="0" title="" align="absmiddle">'.$loadavgString.'</a><br>';
            }
        }
        //END

        // users
        if ($cfg["ui_displayusers"] != "0") {
            $arUsers = GetUsers();
            $arOnlineUsers = array();
            $arOfflineUsers = array();
            for($inx = 0; $inx < count($arUsers); $inx++) {
                if(IsOnline($arUsers[$inx]))
                    array_push($arOnlineUsers, $arUsers[$inx]);
                else
                    array_push($arOfflineUsers, $arUsers[$inx]);
            }
            echo "<b>"._ONLINE.":</b><br>";
            for($inx = 0; $inx < count($arOnlineUsers); $inx++) {
                echo "<a href=\"message.php?to_user=".$arOnlineUsers[$inx]."\">";
                echo "<img src=\"images/user.gif\" width=17 height=14 title=\"\" border=0 align=\"bottom\">". $arOnlineUsers[$inx];
                echo "</a><br>\n";
            }
            // Does the user want to see offline users?
            if ($cfg["hide_offline"] == false) {
                echo "<b>"._OFFLINE.":</b></br>";
                // Show offline users
                for($inx = 0; $inx < count($arOfflineUsers); $inx++) {
                    echo "<a href=\"message.php?to_user=".$arOfflineUsers[$inx]."\">";
                    echo "<img src=\"images/user_offline.gif\" width=17 height=14 title=\"\" border=0 align=\"bottom\">".$arOfflineUsers[$inx];
                    echo "</a><br>\n";
                }
            }
        }
        echo "</td>";
?>
        </tr>
        </table>
    </td>
</tr>
<tr>
<tr>
    <td bgcolor="<?php echo $cfg["table_data_bg"] ?>" colspan="2" nowrap>
    <div align="center">
    <font face="Arial" size="2">
    <a href="readrss.php">
    <img src="images/download_owner.gif" width="16" height="16" border="0" title="RSS Torrents" align="absmiddle">RSS Torrents</a>
     |
    <a href="drivespace.php">
    <img src="images/hdd.gif" width="16" height="16" border="0" title="<?php echo $drivespace ?>% Used" align="absmiddle"><?php echo _DRIVESPACE ?></a>
     |
<?php
//XFER
  if (($cfg['enable_xfer'] == 1) && ($cfg['enable_public_xfer'] == 1)) echo '<a href="xfer.php"><img src="images/download.gif" width="16" height="16" border="0" title="" align="absmiddle">'._XFER_USAGE.'</a> | ';
?>
<?php
  if ($cfg['enable_mrtg'] != 0) {
    echo '<a href="mrtg.php">';
    echo '<img src="images/mrtg.gif" width="16" height="16" title="" border="0" align="absmiddle">';
    echo _ID_MRTG;
    echo '</a> |';
  }
?>
    <a href="who.php">
    <img src="images/who.gif" width="16" height="16" title="" border="0" align="absmiddle"><?php echo _SERVERSTATS ?></a>
     |
    <a href="all_services.php">
    <img src="images/all.gif" width="16" height="16" title="" border="0" align="absmiddle"><?php echo _ALL ?></a>
     |
    <a href="dir.php">
    <img src="images/folder.gif" width="16" height="16" title="" border="0" align="absmiddle"><?php echo _DIRECTORYLIST ?></a>
     |
    <a href="dir.php?dir=<?php echo $cfg["user"] ?>"><img src="images/folder.gif" width="16" height="16" title="My Directory" border="0" align="absmiddle">My Directory</a>
     |
    <a href="stats.php?f=rss" title="RSS - Stats" target="_blank">
    <img src="images/rss.png" width="14" height="14" border="0" alt="RSS - Stats" title="RSS - Stats" align="absmiddle"></a>
    </font>
    </div>
    </td>
</tr>
<?php
//XFER
  echo '<tr><td bgcolor="'.$cfg['table_header_bg'].'" colspan="2">';
    displayDriveSpaceBar($drivespace);
    if (($cfg['enable_xfer'] != 0) && ($cfg['xfer_realtime'] != 0)) {
      echo '<table width="100%" border="0" cellpadding="0" cellspacing="0"><tr>';
        if ($cfg['xfer_day']) echo displayXferBar($cfg['xfer_day'],$xfer_total['day']['total'],_XFERTHRU.' Today:');
        if ($cfg['xfer_week']) echo displayXferBar($cfg['xfer_week'],$xfer_total['week']['total'],_XFERTHRU.' '.$cfg['week_start'].':');
        $monthStart = strtotime(date('Y-m-').$cfg['month_start']);
        $monthText = (date('j') < $cfg['month_start']) ? date('M j',strtotime('-1 Day',$monthStart)) : date('M j',strtotime('+1 Month -1 Day',$monthStart));
        if ($cfg['xfer_month']) echo displayXferBar($cfg['xfer_month'],$xfer_total['month']['total'],_XFERTHRU.' '.$monthText.':');
        if ($cfg['xfer_total']) echo displayXferBar($cfg['xfer_total'],$xfer_total['total']['total'],_TOTALXFER.':');
      echo '</tr></table>';
    }
  echo '</td></tr>';
?>

<?php
if ($cfg['enable_bigboldwarning'] != "0") {
    //Big bold warning hack by FLX
    if($drivespace >= 98) {
        echo '<tr>
        <td bgcolor="'.$cfg['table_data_bg'].'" colspan="2" nowrap>
        <div align="center">
        <font face="Arial" size="2" color="red">
        <strong>
        Warning! ';
        echo $drivespace;
        echo '% drivespace is used! You have ';
        echo formatFreeSpace($cfg["free_space"]);
        echo ' left!
        </strong>
        </font>
        </div>
        </td>
        </tr> ';
    }
}
?>

</table>

<?php
echo '<form action="multi.php" method="POST">';
echo $transferList;
echo "<table bgcolor=\"".$cfg["table_data_bg"]."\" width=\"100%\" bordercolor=\"".$cfg["table_border_dk"]."\" border=\"0\" cellpadding=3 cellspacing=0>";
?>

<tr><td bgcolor="<?php echo $cfg["table_header_bg"] ?>">

<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr>
    <td valign="top">
    <div align="center">
    <table>
    <tr>
        <td><img src="images/properties.png" width="18" height="13" title="<?php echo _TORRENTDETAILS ?>"></td>
        <td class="tiny"><?php echo _TORRENTDETAILS ?>&nbsp;&nbsp;&nbsp;</td>
        <td><img src="images/run_on.gif" width="16" height="16" title="<?php echo _RUNTORRENT ?>"></td>
        <td class="tiny"><?php echo _RUNTORRENT ?>&nbsp;&nbsp;&nbsp;</td>
        <td><img src="images/kill.gif" width="16" height="16" title="<?php echo _STOPDOWNLOAD ?>"></td>
        <td class="tiny"><?php echo _STOPDOWNLOAD ?>&nbsp;&nbsp;&nbsp;</td>
        <?php if ($cfg["AllowQueing"]) { ?>
        <td><img src="images/queued.gif" width="16" height="16" title="<?php echo _DELQUEUE ?>"></td>
        <td class="tiny"><?php echo _DELQUEUE ?>&nbsp;&nbsp;&nbsp;</td>
        <?php } ?>
        <td><img src="images/seed_on.gif" width="16" height="16" title="<?php echo _SEEDTORRENT ?>"></td>
        <td class="tiny"><?php echo _SEEDTORRENT ?>&nbsp;&nbsp;&nbsp;</td>
        <td><img src="images/delete_on.gif" width="16" height="16" title="<?php echo _DELETE ?>"></td>
        <td class="tiny"><?php echo _DELETE ?></td>
        <?php if ($cfg["enable_torrent_download"]) { ?>
        <td>&nbsp;&nbsp;&nbsp;<img src="images/down.gif" width="9" height="9" title="<?php echo _DELQUEUE ?>"></td>
        <td class="tiny">Download Torrent</td>
        <?php } ?>
<?php if ($cfg['enable_multiops'] != 0) { ?>
        <td class="tiny" nowrap>
        <select name="action" size="1">
            <option value="---" selected>---</option>
            <optgroup label="Selected" style="background-color: #00EC00">
                <option value="torrentStart" style="background-color: #FFFFFF">Start Torrents</option>
                <option value="torrentStop" style="background-color: #FFFFFF">Stop Torrents</option>
            </optgroup>
            <?php if ($queueActive) { ?>
            <optgroup label="Selected" style="background-color: #FFFC00">
                <option value="torrentEnQueue" style="background-color: #FFFFFF">EnQueue Torrents</option>
                <option value="torrentDeQueue" style="background-color: #FFFFFF">DeQueue Torrents</option>
            </optgroup>
            <?php } ?>
            <optgroup label="Selected" style="background-color: #DDAAAA">
                <option value="torrentResetTotals" style="background-color: #FFFFFF">Reset Torrents Totals</option>
                <option value="torrent" style="background-color: #FFFFFF">Delete Torrents</option>
                <option value="torrentData" style="background-color: #FFFFFF">Delete Torrents + Data</option>
                <option value="torrentWipe" style="background-color: #FFFFFF">Wipe Torrents</option>
            </optgroup>
            <?php if ($cfg['enable_bulkops'] != 0) { ?>
            <optgroup label="All" style="background-color: #94ABC0">
                <option value="bulkStart" style="background-color: #FFFFFF">Start All Torrents</option>
                <option value="bulkStop" style="background-color: #FFFFFF">Stop All Torrents</option>
                <option value="bulkResume" style="background-color: #FFFFFF">Resume All Torrents</option>
            </optgroup>
            <?php } ?>
        </select><input type="submit" value="Go">
        </td>
<?php } ?>
    </tr>
    </table>
</form>


<?php

    // indexrefresh
    if ($cfg['ui_indexrefresh'] != "0") {
        if(!isset($_SESSION['prefresh']) || ($_SESSION['prefresh'] == true)) {
            echo "*** "._PAGEWILLREFRESH." <span id='span_refresh'>".$cfg["page_refresh"]."</span> "._SECONDS." ***<br>";
            echo "<a href=\"".$_SERVER['PHP_SELF']."?pagerefresh=false\"><font class=\"tiny\">"._TURNOFFREFRESH."</font></a>";
        } else {
            echo "<a href=\"".$_SERVER['PHP_SELF']."?pagerefresh=true\"><font class=\"tiny\">"._TURNONREFRESH."</font></a>";
        }
    }

    // bigboldwarning
    if ($cfg['enable_bigboldwarning'] != "1") {
        if($drivespace >= 98)
            echo "\n\n<script  language=\"JavaScript\">\n alert(\""._WARNING.": ".$drivespace."% "._DRIVESPACEUSED."\")\n </script>";
    }

    // index_page_stats
    if ($cfg['index_page_stats'] != 0) {
        if (!array_key_exists("total_download",$cfg)) $cfg["total_download"] = 0;
        if (!array_key_exists("total_upload",$cfg)) $cfg["total_upload"] = 0;
        echo '<table width="100%"><tr>';
        if (($cfg['enable_xfer'] != 0) && ($cfg['xfer_realtime'] != 0)) {
            echo '<td class="tiny" align="left" valign="bottom">';
            echo '<b>'._SERVERXFERSTATS.'</b><br>';
            echo _TOTALXFER.': <strong>'.formatFreeSpace($xfer_total['total']['total']/(1024*1024)).'</strong><br>';
            echo _MONTHXFER.': <strong>'.formatFreeSpace($xfer_total['month']['total']/(1024*1024)).'</strong><br>';
            echo _WEEKXFER.': <strong>'.formatFreeSpace($xfer_total['week']['total']/(1024*1024)).'</strong><br>';
            echo _DAYXFER.': <strong>'.formatFreeSpace($xfer_total['day']['total']/(1024*1024)).'</strong><br>';
            echo '</td>';
        }
        if ($queueActive) {
            include_once("QueueManager.php");
            $queueManager = QueueManager::getQueueManagerInstance($cfg);
            echo '<td align="center" valign="bottom">';
            echo '<div class="tiny">';
            echo '<b>'. _QUEUEMANAGER. ' ('. $queueManager->managerName .')</b><br>';
            echo '<strong>'.strval(getRunningTorrentCount()).'</strong> torrent(s) running and <strong>'.strval($queueManager->countQueuedTorrents()).'</strong> queued.<br>';
            echo 'Total torrents server will run: <strong>'. $queueManager->limitGlobal .'</strong><br>';
            echo 'Total torrents a user may run: <strong>'. $queueManager->limitUser .'</strong><br>';
            echo '</div>';
            echo '</td>';
        }
        echo '<td class="tiny" align="center" valign="bottom">';
        echo '<b>'._OTHERSERVERSTATS.'</b><br>';
        $sumMaxUpRate = getSumMaxUpRate();
        $sumMaxDownRate = getSumMaxDownRate();
        $sumMaxRate = $sumMaxUpRate + $sumMaxDownRate;
        echo _DOWNLOADSPEED.': <strong>'.number_format($cfg["total_download"], 2).' ('.number_format($sumMaxDownRate, 2).')</strong> kB/s<br>';
        echo _UPLOADSPEED.': <strong>'.number_format($cfg["total_upload"], 2).' ('.number_format($sumMaxUpRate, 2).')</strong> kB/s<br>';
        echo _TOTALSPEED.': <strong>'.number_format($cfg["total_download"]+$cfg["total_upload"], 2).' ('.number_format($sumMaxRate, 2).')</strong> kB/s<br>';
        if ($cfg["index_page_connections"] != 0)
            echo _ID_CONNECTIONS.': <strong>'.$netstatConnectionsSum.' ('.getSumMaxCons().')</strong><br>';
        echo _DRIVESPACE.': <strong>'.formatFreeSpace($cfg["free_space"]).'</strong><br>';
        if ($cfg["show_server_load"] != 0)
            echo _SERVERLOAD . ': <strong>'.$loadavgString.'</strong>';
        echo '</td>';
        if (($cfg['enable_xfer'] != 0) && ($cfg['xfer_realtime'] != 0)) {
            echo '<td class="tiny" align="right" valign="bottom">';
            echo '<b>'._YOURXFERSTATS.'</b><br>';
            echo _TOTALXFER.': <strong>'.formatFreeSpace($xfer[$cfg['user']]['total']['total']/(1024*1024)).'</strong><br>';
            echo _MONTHXFER.': <strong>'.formatFreeSpace($xfer[$cfg['user']]['month']['total']/(1024*1024)).'</strong><br>';
            echo _WEEKXFER.': <strong>'.formatFreeSpace($xfer[$cfg['user']]['week']['total']/(1024*1024)).'</strong><br>';
            echo _DAYXFER.': <strong>'.formatFreeSpace($xfer[$cfg['user']]['day']['total']/(1024*1024)).'</strong><br>';
            echo '</td>';
        }
        echo '</tr></table>';
    }

?>

    </div>
    </td>

</tr>
</table>

</td></tr>
</table>

<?php
    echo DisplayTorrentFluxLink(true);
    // At this point Any User actions should have taken place
    // Check to see if the user has a force_read message from an admin
    if (IsForceReadMsg()) {
        // Yes, then warn them
?>
        <script  language="JavaScript">
        if (confirm("<?php echo _ADMINMESSAGE ?>"))
        {
            document.location = "readmsg.php";
        }
        </script>
<?php
    }
?>

    </td>
</tr>
</table>
</body>
</html>