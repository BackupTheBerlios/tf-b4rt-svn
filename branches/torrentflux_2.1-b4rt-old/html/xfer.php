<?php

/* $Id$ */

/******************************************************************************/

/*************************************************************
*  TorrentFlux xfer Statistics hack
*  blackwidow - matt@mattjanssen.net
**************************************************************/
/*
    TorrentFlux xfer Statistics hack is free code; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.
*/

include_once('config.php');
include_once('functions.php');
DisplayHead(_XFER);
if ($cfg['enable_xfer'] == 1) {
    $cfg['xfer_realtime'] = 1;
    getDirList($cfg['torrent_file_path']);
    echo '<table width="100%" border="0" cellpadding="0" cellspacing="0"><tr>';
    if ($cfg['xfer_day']) echo displayXferBar($cfg['xfer_day'],$xfer_total['day']['total'],_XFERTHRU.' Today:');
    if ($cfg['xfer_week']) echo displayXferBar($cfg['xfer_week'],$xfer_total['week']['total'],_XFERTHRU.' '.$cfg['week_start'].':');
    $monthStart = strtotime(date('Y-m-').$cfg['month_start']);
    $monthText = (date('j') < $cfg['month_start']) ? date('M j',strtotime('-1 Day',$monthStart)) : date('M j',strtotime('+1 Month -1 Day',$monthStart));
    if ($cfg['xfer_month']) echo displayXferBar($cfg['xfer_month'],$xfer_total['month']['total'],_XFERTHRU.' '.$monthText.':');
    if ($cfg['xfer_total']) echo displayXferBar($cfg['xfer_total'],$xfer_total['total']['total'],_TOTALXFER.':');
    echo '</tr></table>';
    echo '<br>';
    if (($cfg['enable_public_xfer'] == 1 ) || IsAdmin())
        displayXfer();
}
DisplayFoot();
?>