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

// configs
require_once("inc/config/config.php");

// core functions
require_once('inc/functions/functions.core.php');

// common functions
require_once('inc/functions/functions.common.php');

// db
require_once('inc/db.php');

// Create Connection.
$db = getdb();

// load global settings
loadSettings('tf_settings');

// Path to where the meta files will be stored... usually a sub of $cfg["path"]
$cfg["transfer_file_path"] = $cfg["path"].".transfers/";

// Free space in MB
$cfg["free_space"] = @disk_free_space($cfg["path"]) / (1048576);

?>