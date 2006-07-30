<?php

/* $Id$ */

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

require_once("config.php");
require_once("functions.php");
require_once("lib/vlib/vlibTemplate.php");

# create new template
$tmpl = new vlibTemplate("themes/".$cfg["default_theme"]."/tmpl/drivespace.tmpl");

$result = shell_exec("df -h ".$cfg["path"]);
$result2 = shell_exec("du -sh ".$cfg["path"]."*");

# Nothing without set some vars
$tmpl->setvar('displayDriveSpaceBar', displayDriveSpaceBar(getDriveSpace($cfg["path"])));
$tmpl->setvar('main_bgcolor', $cfg["main_bgcolor"]);
$tmpl->setvar('result', $result);
$tmpl->setvar('result2', $result2);
$tmpl->setvar('DisplayHead', DisplayHead(_DRIVESPACE));
$tmpl->setvar('DisplayFoot', DisplayFoot());

# lets parse the hole thing
$tmpl->pparse();
?>