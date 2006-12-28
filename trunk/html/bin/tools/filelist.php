#!/usr/bin/env php
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

// prevent invocation from web (hopefully on all the php-config-permutations)
if (!empty($_REQUEST)) die();
if (!empty($_GET)) die();
if (!empty($_POST)) die();
if (empty($argv[0])) die();
if (empty($_SERVER['argv'][0])) die();
if ($argv[0] != $_SERVER['argv'][0]) die();

/******************************************************************************/

// tools-functions
$functionsDir = dirname(__FILE__);
$functionsDir = str_replace("bin/tools", '', $functionsDir);
if (!is_file($functionsDir.'inc/functions/functions.tools.php'))
	exit('error: functions-file missing.\n');
require_once($functionsDir.'inc/functions/functions.tools.php');

// action
if (isset($argv[1]))
	printFileList($argv[1], 1, 1);
else
	echo "missing param : dir\n";

// exit
exit();

?>