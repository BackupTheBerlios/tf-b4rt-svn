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

// image-types
if (!defined("IMG_GIF")) define("IMG_GIF", 1);
if (!defined("IMG_JPG")) define("IMG_JPG", 2);
if (!defined("IMG_PNG")) define("IMG_PNG", 4);

// =============================================================================
// Image-class
// =============================================================================

/**
 * Image
 */
class Image
{
	// fields

	// content-types
	var $contentTypes = array(
		IMG_GIF => 'image/gif',
		IMG_JPG => 'image/png',
		IMG_PNG => 'image/jpeg');

    // imagetypes
    var $imagetypes = 0;

    // type
    var $type = 0;

    // dim
    var $width = 0;
    var $height = 0;

    // image
    var $image;

    // res
    var $res = "";

    /* factories */

    /**
     * getImage
     *
     * @param $t
     * @param $w
     * @param $h
     */
    function getImage($t = IMG_GIF, $w = 0, $h = 0) {
    	return new Image($t, $w, $h);
    }

    /**
     * getImageFromRessource
     *
     * @param $t
     * @param $r
     */
    function getImageFromRessource($t = IMG_GIF, $r) {
    	$img = new Image($t, $w, $h);
    	if (!$img)
    		return false;
    	// TODO
    }

    /* ctor */

    /**
     * do not use direct, use the factory-methods !
     *
     * @return Image
     */
    function Image($t = IMG_GIF, $w = 0, $h = 0) {

    	// GD required
		if (extension_loaded('gd')) {

	    	// types Supported
			$this->imagetypes = imagetypes();

	    	// type
	    	if ($this->imagetypes & $t)
	    		$this->type = $t;
	    	else
	    		return false;

	    	// dim
	    	$this->width = $w;
	    	$this->height = $h;

		} else {
			// return false
			return false;
		}

    }


    /* public meths */

    /**
     * paint
     */
    function paint() {
    	header("Content-type: ".$this->contentTypes[$this->type]);
		switch ($this->type) {
			case IMG_GIF:
				imagegif($this->image);
				break;
			case IMG_JPG:
				imagejpeg($this->image);
				break;
			case IMG_PNG:
				imagepng($this->image);
				break;
		}
		imagedestroy($this->image);
		exit();
    }


    /* private meths */

}

// =============================================================================
// generic (static) functions
// =============================================================================

/**
 * output image not supported image
 */
function imagePaintNotSupported() {
	$data  = 'R0lGODlhXAAkAIcAAAAAAP8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
	$data .= 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
	$data .= 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
	$data .= 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
	$data .= 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
	$data .= 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
	$data .= 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
	$data .= 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
	$data .= 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
	$data .= 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
	$data .= 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
	$data .= 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
	$data .= 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
	$data .= 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
	$data .= 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
	$data .= 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
	$data .= 'AAAAAAAAAAAAAAAAACH5BAMAAAIALAAAAABcACQAAAj/AAUIHEiwoMGDCBMqXMiw';
	$data .= 'ocAAAQ5CdEgQosWJFDNqxDjQokGODi+K1EiyIUgBFwueZHhyZcmXHSNW9DgzJk2J';
	$data .= 'MiuizPkwokiOP3nuvBl05M6jMXsa/Xgzac2iSpcGjQoVKVCfQl0OJZoVq0qvT7si';
	$data .= 'dUoW48SWQmemHNuT7VG0bs+Kran07dyFHuHqFbv3q9+kcv+yBAuYr2G/cOnaJNyW';
	$data .= 'KWKeeSEHthmXcWWmMqfi/Lk4ZdWqW2milYy5M961neWiDp1WamvRad3CRDjZ8cbY';
	$data .= 'WmfD1Jpbt2/fvGP/Hj47OPHjyJObXK28eUnOL6E7By78NvPp0auT7I2dInTX1xuz';
	$data .= 'g8baVPp406kle/6+VHBR9vCNagYtvu9psmzNPt4fVqX7w/eJl19OU9lXWFOKGZjQ';
	$data .= 'VYpZVaBh87VXH4AKMYhfbW3tpRlV//FXIYGkFabWgJYteNd5stk2YFgRxreeeZVx';
	$data .= '9eF6DV4F3mqcbYifiTNGVqNwxnUnZI8IDmlkakcmqeSSSQYEADs=';
    imagePaint('image/gif', base64_decode($data), 1190);
}

/**
 * output invalid referer image
 */
function imagePaintInvalidReferer() {
	$data  = 'R0lGODlhEAAQAOZpAJJzRlFFOU4vKOjPNOzTNOvTMkowJ0k5MmU3J1k6OEo8NO3S';
	$data .= 'KvDUM4VFKuLEMjciIe7WKz4iHVUxJNPAbUgwNKBTLY1HLPTbK/PaLezRMu3TMIVw';
	$data .= 'X8K5q+bOM75nL6yJO0QtM+7RN8+ILm86KeXLLYduZaCpyuXZWOrMMt+1MsFqMWEy';
	$data .= 'Ir/Ej4iEakM6RmxHNufiiO3PMdyqMdeZMOvSMqiusezPT3dEL7a2t+7gUKmFUHpK';
	$data .= 'OT8pJmQ3KFoxJz8rKPXcLpJiUu7QMXhIObJkLujQMpWBU+rSM+rLMbTApfHjQtCH';
	$data .= 'MWxWN+3VM5uPhkIrJuXGM+bIbEEqJ5SivsemMOrPL+W/LsO5nGlVQst5Mu3QMZ50';
	$data .= 'MdWZLF44M+3TNdK8POK7M8CTMUssJ3tWN8/Qin5XR725ofDVNFk4KwAAAAAAAAAA';
	$data .= 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
	$data .= 'AAAAAAAAAAAAAAAAACH5BAEAAGkALAAAAAAQABAAAAeSgGmCAAoHWIKIiYMAg0yK';
	$data .= 'ik6KRo+IjIkBV5QtG4oTXzCUAYJRNiFAF0pmjwEvKlxWQmdeEGQcimgPFllgWgwE';
	$data .= 'VTk1j08+HjJIMRoFCyw4ihI8FTMoGTRFJCdTijc/I0tQRwMdAxhJJolBAmJEKU0E';
	$data .= 'DlQfOo89Ug0iYVtjCSARZYo7BCAY0oWCC0qCSqwwgLAhwkAAOw==';
    imagePaint('image/gif', base64_decode($data), 565);
}

/**
 * output no-op image
 */
function imagePaintNoOp() {
	$data  = 'R0lGODlhDQAMAOZeAP///44LDoYLDWMJC18XGKNmZ6cNEXQJDFsHCsUQFbwPFLkP';
	$data .= 'FKQNEaENEZMMEIoLDokLD4cLD34KDXIJDHAJDG4JDGYIC7YPFJ0NEZoNEYILDncK';
	$data .= 'DXMKDWcJDMgXHpAaHmwXGlokJsRpbK5maKxqbdmoqrkaIr4hLOHQ0cYkMc0sO7kp';
	$data .= 'NswxQkoTGXYhKtuvtOvh4lAWHdE6TmgdJ4YtOeSxt784S8Y+UkQYH5o9TLxAVdNK';
	$data .= 'YkgaIk0cJdy2vdZPaVQfKtZQal0jMMZPadhaecpYdtpggZhFXOW8x9tlh9ZjhHs6';
	$data .= 'Tttpjd5vlYVJYYtTbrV2nplliLqAqqJzmZltkqh9pquEqpyrxp2tx196o6e3zr3J';
	$data .= '28bQ393Q0AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
	$data .= 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
	$data .= 'AAAAAAAAAAAAAAAAACH5BAEAAF4ALAAAAAANAAwAAAeHgF6CXlZVU1RRg4NSTUxJ';
	$data .= 'REE7MkeDUEpFSEM7NjUrKTSCRpgAPjcvACUmCV5RPzo5AAAwsR8GF0JPMiwrLrMw';
	$data .= 'IBgGDEJOKiknJLEABRgNGUBLHgoisSGxIxEQPV4LDA8oIAcEXQMTG4IuDQEUBxoW';
	$data .= 'CBYVMYMzDhACEhwVHS2Kgj09eODAoSgQADs=';
	imagePaint('image/gif', base64_decode($data), 554);
}

/**
 * paint image-data
 *
 * @param $type
 * @param $data
 * @param $len
 */
function imagePaint($type, $data, $len) {
	header('Accept-Ranges: bytes');
    header('Content-Length: '.$len);
    header('Content-Type: '.$type);
    echo $data;
    exit();
}

/**
 * check image support of PHP + GD
 *
 * @return boolean
 */
function imageIsSupported() {
	if (extension_loaded('gd')) {
		// gd is there but we also need support for at least one image-type
		$imageTypes = imagetypes();
		// gif
		if ($imageTypes & IMG_GIF)
			return true;
		// png
		if ($imageTypes & IMG_PNG)
		   return true;
		// jpg
		if ($imageTypes & IMG_JPG)
		   return true;
	}
	return false;
}

/**
 * check image-type support of PHP + GD
 *
 * @return boolean
 */
function imageIsTypeSupported($type) {
	return ((extension_loaded('gd')) && (imagetypes() & $type));
}

/**
 * check referer
 */
function imageCheckReferer() {
	if (!((isset($_SERVER["HTTP_REFERER"])) &&
		(stristr($_SERVER["HTTP_REFERER"], $_SERVER["SERVER_NAME"]) !== false)))
		imagePaintInvalidReferer();
}

/**
 * paint label-image created with a existing image-file
 *
 * @param $bgimage
 * @param $label
 * @param $font
 * @param $x
 * @param $y
 * @param $r
 * @param $g
 * @param $b
 */
function imagePaintLabelFromImage($bgimage, $label,
		$font = 1,
		$x = 0, $y = 0,
		$r = 0, $g = 0, $b = 0) {
	// only if gd available
	if (extension_loaded('gd')) {
		$imageTypes = imagetypes();
		// gif
		if ($imageTypes & IMG_GIF) {
			$img = @imagecreatefromgif($bgimage.".gif");
			if ($img !== false) {
				$textcolor = imagecolorallocate($img, $r, $g, $b);
				imagestring($img, $font, $x, $y, $label, $textcolor);
				header("Content-type: image/gif");
				imagegif($img);
				imagedestroy($img);
				exit();
			}
		}
		// png
		if ($imageTypes & IMG_PNG) {
			$imp = @imagecreatefrompng($bgimage.".png");
			if ($imp !== false) {
				$textcolor = imagecolorallocate($imp, $r, $g, $b);
				imagestring($imp, $font, $x, $y, $label, $textcolor);
				header("Content-type: image/png");
				imagepng($imp);
				imagedestroy($imp);
				exit();
			}
		}
		// jpg
		if ($imageTypes & IMG_JPG) {
			$imj = @imagecreatefromjpeg($bgimage.".jpg");
			if ($imj !== false) {
				$textcolor = imagecolorallocate($imj, $r, $g, $b);
				imagestring($imj, $font, $x, $y, $label, $textcolor);
				header("Content-type: image/jpeg");
				imagejpeg($imj, '', 75);
				imagedestroy($imj);
				exit();
			}
		}
	}
	// no gd or supported type found, bail out
	imagePaintNotSupported();
}

?>