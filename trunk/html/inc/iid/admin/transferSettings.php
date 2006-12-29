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

// prevent direct invocation
if (!isset($cfg['user'])) {
	@ob_end_clean();
	@header("location: ../../../index.php");
	exit();
}

/******************************************************************************/

// init template-instance
tmplInitializeInstance($cfg["theme"], "page.admin.transferSettings.tmpl");

// torrent
$tmpl->setvar('btclient', $cfg["btclient"]);
$tmpl->setvar('metainfoclient', $cfg["metainfoclient"]);
$tmpl->setvar('btclient_tornado_options', $cfg["btclient_tornado_options"]);
$tmpl->setvar('btclient_transmission_options', $cfg["btclient_transmission_options"]);
$tmpl->setvar('btclient_mainline_options', $cfg["btclient_mainline_options"]);
$tmpl->setvar('max_upload_rate', $cfg["max_upload_rate"]);
$tmpl->setvar('max_download_rate', $cfg["max_download_rate"]);
$tmpl->setvar('max_uploads', $cfg["max_uploads"]);
$tmpl->setvar('maxcons', $cfg["maxcons"]);
$tmpl->setvar('minport', $cfg["minport"]);
$tmpl->setvar('maxport', $cfg["maxport"]);
$tmpl->setvar('rerequest_interval', $cfg["rerequest_interval"]);
$tmpl->setvar('torrent_dies_when_done', $cfg["torrent_dies_when_done"]);
$tmpl->setvar('sharekill', $cfg["sharekill"]);
$tmpl->setvar('enable_file_priority', $cfg["enable_file_priority"]);
$tmpl->setvar('skiphashcheck', $cfg["skiphashcheck"]);
$tmpl->setvar('enable_sharekill', $cfg["enable_sharekill"]);
// wget
$tmpl->setvar('enable_wget', $cfg["enable_wget"]);
$tmpl->setvar('wget_limit_rate', $cfg["wget_limit_rate"]);
$tmpl->setvar('wget_limit_retries', $cfg["wget_limit_retries"]);
$tmpl->setvar('wget_ftp_pasv', $cfg["wget_ftp_pasv"]);
// nzbperl
$tmpl->setvar('enable_nzbperl', $cfg['enable_nzbperl']);
$tmpl->setvar('nzbperl_badAction', $cfg['nzbperl_badAction']);
$tmpl->setvar('nzbperl_server', $cfg['nzbperl_server']);
$tmpl->setvar('nzbperl_user', $cfg['nzbperl_user']);
$tmpl->setvar('nzbperl_pw', $cfg['nzbperl_pw']);
$tmpl->setvar('nzbperl_threads', $cfg['nzbperl_threads']);
$tmpl->setvar('nzbperl_conn', $cfg['nzbperl_conn']);
// common
$tmpl->setvar('enable_umask', $cfg["enable_umask"]);
$tmpl->setvar('nice_adjust', $cfg["nice_adjust"]);
$nice_list = array();
for ($i = 0; $i < 20 ; $i++) {
	array_push($nice_list, array(
		'i' => $i,
		'nice_adjust_true' => ($cfg["nice_adjust"] == $i) ? 1 : 0
		)
	);
}
$tmpl->setloop('nice_list', $nice_list);
//
tmplSetTitleBar("Administration - Transfer Settings");
tmplSetAdminMenu();
tmplSetFoot();

// set iid-var
$tmpl->setvar('iid', $_REQUEST["iid"]);

// parse template
$tmpl->pparse();

?>