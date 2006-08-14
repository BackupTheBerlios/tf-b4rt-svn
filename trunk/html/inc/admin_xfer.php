<?php
/* $Id: admin_xfer.php 242 2006-08-11 18:26:19Z msn_exploder $ */

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
	getDirList($cfg["torrent_file_path"],0);
	$tmpl->setvar('displayXfer', displayXfer());
}
$tmpl->setvar('foot', getFoot(true,true));
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('index_page', $cfg["index_page"]);
$tmpl->setvar('ui_dim_details_w', $cfg["ui_dim_details_w"]);
$tmpl->setvar('ui_dim_details_h', $cfg["ui_dim_details_h"]);
$tmpl->pparse();
?>