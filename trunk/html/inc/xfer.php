<?php

/* $Id$ */

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

require_once('config.php');
require_once('functions.php');


# create new template
if (!ereg('^[^./][^/]*$', $cfg["theme"])) {
	$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/xfer.tmpl");
}
else {
	$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/xfer.tmpl");
}

$tmpl->setvar('head', getHead(_XFER));
if ($cfg['enable_xfer'] == 1) {
	$tmpl->setvar('is_xfer', 1);
	// getTransferListArray to update xfer-stats
	$cfg['xfer_realtime'] = 1;
	@getTransferListArray();
	if ($cfg['xfer_day']) {
		$tmpl->setvar('xfer_day', displayXferBar($cfg['xfer_day'],$xfer_total['day']['total'],_XFERTHRU.' Today:'));
	}
	if ($cfg['xfer_week']) {
		$tmpl->setvar('xfer_week', displayXferBar($cfg['xfer_week'],$xfer_total['week']['total'],_XFERTHRU.' '.$cfg['week_start'].':'));
	}
	$monthStart = strtotime(date('Y-m-').$cfg['month_start']);
	$monthText = (date('j') < $cfg['month_start']) ? date('M j',strtotime('-1 Day',$monthStart)) : date('M j',strtotime('+1 Month -1 Day',$monthStart));
	if ($cfg['xfer_month']) {
		$tmpl->setvar('xfer_month', displayXferBar($cfg['xfer_month'],$xfer_total['month']['total'],_XFERTHRU.' '.$monthText.':'));
	}
	if ($cfg['xfer_total']) {
		$tmpl->setvar('xfer_total', displayXferBar($cfg['xfer_total'],$xfer_total['total']['total'],_TOTALXFER.':'));
	}
	if (($cfg['enable_public_xfer'] == 1 ) || IsAdmin()) {
		$tmpl->setvar('displayXfer', displayXfer());
	}
}
$tmpl->setvar('foot', getFoot());
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('index_page', $cfg["index_page"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->pparse();
?>