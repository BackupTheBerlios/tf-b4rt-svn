<?php
/* $Id$ */

# create new template
if (!ereg('^[^./][^/]*$', $cfg["theme"])) {
	$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/admin_xfer.tmpl");
}
else {
	$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/admin_xfer.tmpl");
}
$tmpl->setvar('head', getHead(_XFER));
$tmpl->setvar('menu', getMenu());
if ($cfg['enable_xfer'] == 1) {
	$tmpl->setvar('enable_xfer', $cfg["enable_xfer"]);
	// getTransferListArray to update xfer-stats
	$cfg['xfer_realtime'] = 1;
	@getTransferListArray();
	$tmpl->setvar('displayXfer', getXfer());
}
$tmpl->setvar('foot', getFoot(true,true));
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('index_page', $cfg["index_page"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->setvar('iid', $_GET["iid"]);
$tmpl->pparse();
?>