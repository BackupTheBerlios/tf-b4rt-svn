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

// Image class
require_once('inc/classes/Image.php');

// image-op-switch
$imageOp = (isset($_REQUEST['i'])) ? $_REQUEST['i'] : "noop";
switch ($imageOp) {

	case "login":
		// check for valid referer
		imageCheckReferer();
		// main.external
		require_once('inc/main.external.php');
		// output image
		$bgImage = ((strpos($cfg["default_theme"], '/')) === false)
			? 'themes/'.$cfg["default_theme"].'/images/code_bg'
			: 'themes/tf_standard_themes/images/code_bg';
		$rndCode = loginImageCode($cfg["db_user"], $_REQUEST["rnd"]);
		imagePaintLabelFromImage($bgImage, $rndCode, 5, 12, 2, 80, 80, 80);

	case "test":
		// check for valid referer
		imageCheckReferer();
		// main.internal
		require_once('inc/main.internal.php');
		// output image
		$bgImage = ((strpos($cfg["theme"], '/')) === false)
			? 'themes/'.$cfg["theme"].'/images/code_bg'
			: 'themes/tf_standard_themes/images/code_bg';
		imagePaintLabelFromImage($bgImage, 'tf-b4rt', 5, 8, 2, 0, 0, 0);

	case "notsup":
		// check for valid referer
		imageCheckReferer();
		// output image
		imagePaintNotSupported();

	case "noop":
	default:
		/*
		// create Image-instance
		$image = Image::getImage();
		*/
		// check for valid referer
		imageCheckReferer();
		// output image
		imagePaintNoOp();

}

?>