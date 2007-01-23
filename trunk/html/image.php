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

// image functions
require_once('inc/functions/functions.image.php');

// Image class
require_once('inc/classes/Image.php');

// image-op-switch
$imageOp = (isset($_REQUEST['i'])) ? $_REQUEST['i'] : "noop";
switch ($imageOp) {

	case "login":
		image_login();

	case "test":
		image_test();

	case "pieTransferTotals":
		image_pieTransferTotals();

	case "pieTransferPeers":
		image_pieTransferPeers();

	case "pieTransferScrape":
		image_pieTransferScrape();

	case "spacer":
		image_spacer();

	case "notsup":
		image_notsup();

	case "noop":
	default:
		image_noop();

}

?>