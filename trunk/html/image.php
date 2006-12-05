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

// image-op-switch
if (isset($_REQUEST['i']))
	$imageOp = $_REQUEST['i'];
else
	$imageOp = "noop";

switch ($imageOp) {

	case "login":
		// check for valid referer
		imageCheckReferer();
		// main.external
		require_once('inc/main.external.php');
		// output image
		if ((strpos($cfg["default_theme"], '/')) === false)
			$bgImage = 'themes/'.$cfg["default_theme"].'/images/code_bg';
		else
			$bgImage = 'themes/tf_standard_themes/images/code_bg';
		$rndCode = loginImageCode($cfg["db_user"], $_REQUEST["rnd"]);
		imageOutputLabelFromImage($bgImage, $rndCode, 5, 12, 2, 80, 80, 80);

	case "test":
		// check for valid referer
		imageCheckReferer();
		// main.internal
		require_once('inc/main.internal.php');
		// output image
		if ((strpos($cfg["theme"], '/')) === false)
			$bgImage = 'themes/'.$cfg["theme"].'/images/code_bg';
		else
			$bgImage = 'themes/tf_standard_themes/images/code_bg';
		imageOutputLabelFromImage($bgImage, 'tf-b4rt', 5, 8, 2, 0, 0, 0);

	case "notsup":
		// check for valid referer
		imageCheckReferer();
		// output image
		imageOutputNotSupported();

	case "noop":
	default:
		// check for valid referer
		imageCheckReferer();
		// output image
		imageOutputNoOp();

}

?>