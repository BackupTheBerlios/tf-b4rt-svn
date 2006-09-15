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

// common functions
require_once('inc/functions/functions.common.php');

// require
require_once("inc/classes/AliasFile.php");

// request-vars
$torrent = getRequestVar('torrent');
$alias = getRequestVar('alias');

// alias
$transferowner = getOwner($torrent);
if (!empty($alias))
	$af = AliasFile::getAliasFileInstance($cfg["transfer_file_path"].$alias, $transferowner, $cfg);
else
	showErrorPage("torrent file not specified");

// create template-instance
$tmpl = tmplGetInstance($cfg["theme"], "page.downloaddetails_".$cfg['details_type'].".tmpl");

// set vars
$tmpl->setvar('torrent', $torrent);
$tmpl->setvar('alias', $alias);
if (strlen($torrent) >= 39)
	$tmpl->setvar('torrentLabel', substr($torrent, 0, 35)."...");
else
	$tmpl->setvar('torrentLabel', $torrent);

// set common vars
$tmpl->setvar('_USER', $cfg['_USER']);
$tmpl->setvar('_SHARING', $cfg['_SHARING']);
$tmpl->setvar('_ID_CONNECTIONS', $cfg['_ID_CONNECTIONS']);
$tmpl->setvar('_ID_PORT', $cfg['_ID_PORT']);
$tmpl->setvar('_DOWNLOADSPEED', $cfg['_DOWNLOADSPEED']);
$tmpl->setvar('_UPLOADSPEED', $cfg['_UPLOADSPEED']);
$tmpl->setvar('_PERCENTDONE', $cfg['_PERCENTDONE']);
$tmpl->setvar('_ESTIMATEDTIME', $cfg['_ESTIMATEDTIME']);
//
$tmpl->setvar('iid', $_GET["iid"]);

// include details-type
require_once("inc/iid/downloaddetails.".$cfg['details_type'].".php");

// parse template
$tmpl->pparse();

?>