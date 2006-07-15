<?php

/*************************************************************
*  TorrentFlux - PHP Torrent Manager
*  www.torrentflux.com
**************************************************************/
/*
    This file is part of TorrentFlux.

    TorrentFlux is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    TorrentFlux is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with TorrentFlux; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

if(! isset($_SESSION['user'])) {
    header('location: login.php');
    exit();
}

// =============================================================================

$transferList = getDirList($cfg["torrent_file_path"]);

// =============================================================================
// OUTPUT
// =============================================================================

?>
<html>
<head>
    <title><?php echo $cfg["pagetitle"] ?></title>
    <link rel="icon" href="images/favicon.ico" type="image/x-icon" />
    <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon" />
    <LINK REL="StyleSheet" HREF="themes/<?php echo $cfg["theme"] ?>/style.css" TYPE="text/css">
    <link rel="alternate" title="Transfer-Stats" href="stats.php?f=rss" type="application/rss+xml">
    <META HTTP-EQUIV="Pragma" CONTENT="no-cache; charset=<?php echo _CHARSET ?>">
<?php
    if ($cfg['ui_indexrefresh'] != "0") {
        if(!isset($_SESSION['prefresh']) || ($_SESSION['prefresh'] == true)) {
            echo "<meta http-equiv=\"REFRESH\" content=\"".$cfg["page_refresh"].";URL=index.php\">";
?>
<script language="JavaScript">
    var var_refresh = <?php echo $cfg["page_refresh"] ?>;
    function UpdateRefresh() {
        span_refresh.innerHTML = String(var_refresh--);
        setTimeout("UpdateRefresh();", 1000);
    }
</script>
<?php
        }
    }
?>
<div id="overDiv" style="position:absolute;visibility:hidden;z-index:1000;"></div>
<script language="JavaScript">
    var ol_closeclick = "1";
    var ol_close = "<font color=#ffffff><b>X</b></font>";
    var ol_fgclass = "fg";
    var ol_bgclass = "bg";
    var ol_captionfontclass = "overCaption";
    var ol_closefontclass = "overClose";
    var ol_textfontclass = "overBody";
    var ol_cap = "&nbsp;Torrent Status";
</script>
<script src="overlib.js" type="text/javascript"></script>
<script language="JavaScript">
function ShowDetails(name_file)
{
  window.open (name_file,'_blank','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=<?php echo $cfg["ui_dim_details_w"] ?>,height=<?php echo $cfg["ui_dim_details_h"] ?>')
}
function StartTorrent(name_file)
{
  <?php if ($cfg["showdirtree"])
  { ?>
     window.open (name_file,'_blank','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=700,height=675')
  <?php }
  else
  { ?>
     window.open (name_file,'_blank','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=700,height=555')
  <?php } ?>
}
function ConfirmDelete(file)
{
    return confirm("<?php echo _ABOUTTODELETE ?>: " + file)
}
</script>
<style>
    form {margin: 0px; padding: 0px; display: inline;}
</style>
<?php
if ($cfg["enable_sorttable"] != 0)
    include('inc.index.sorttable.php');
?>
</head>