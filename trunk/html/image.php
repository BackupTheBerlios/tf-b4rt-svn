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
		Image::checkReferer();
		// main.external
		require_once('inc/main.external.php');
		// output image
		$bgImage = ((strpos($cfg["default_theme"], '/')) === false)
			? 'themes/'.$cfg["default_theme"].'/images/code_bg'
			: 'themes/tf_standard_themes/images/code_bg';
		$rndCode = loginImageCode($cfg["db_user"], $_REQUEST["rnd"]);
		Image::paintLabelFromImage($bgImage, $rndCode, 5, 12, 2, 80, 80, 80);

	case "test":
		// check for valid referer
		Image::checkReferer();
		// main.internal
		require_once('inc/main.internal.php');
		// output image
		$bgImage = ((strpos($cfg["theme"], '/')) === false)
			? 'themes/'.$cfg["theme"].'/images/code_bg'
			: 'themes/tf_standard_themes/images/code_bg';
		Image::paintLabelFromImage($bgImage, 'tf-b4rt', 5, 8, 2, 0, 0, 0);

	case "pieTransferTotals":
		// check for valid referer
		Image::checkReferer();
		// main.internal
		require_once('inc/main.internal.php');
		// output image
		// transfer-id
		$transfer = getRequestVar('transfer');
		if (empty($transfer))
			Image::paintNoOp();
		// validate transfer
		if (isValidTransfer($transfer) !== true) {
			AuditAction($cfg["constants"]["error"], "INVALID TRANSFER: ".$transfer);
			Image::paintNoOp();
		}
		// client-handler + totals
		$clientHandler = ClientHandler::getInstance(getTransferClient($transfer));
		$totals = $clientHandler->getTransferTotal($transfer);
		// draw image
		Image::paintPie3D(
			202,
			160,
			100,
			50,
			200,
			100,
			20,
			Image::stringToRGBColor($cfg["body_data_bg"]),
			array($totals["uptotal"] + 1, $totals["downtotal"] + 1),
			array(array('r' => 0x00, 'g' => 0xEB, 'b' => 0x0C), array('r' => 0x10, 'g' => 0x00, 'b' => 0xFF)),
			array('Up : '.@formatFreeSpace($totals["uptotal"] / 1048576), 'Down : '.@formatFreeSpace($totals["downtotal"] / 1048576)),
			48,
			130,
			2,
			14
		);

	case "pieTransferPeers":
		// check for valid referer
		Image::checkReferer();
		// main.internal
		require_once('inc/main.internal.php');
		// output image
		// transfer-id
		$transfer = getRequestVar('transfer');
		if (empty($transfer))
			Image::paintNoOp();
		// validate transfer
		if (isValidTransfer($transfer) !== true) {
			AuditAction($cfg["constants"]["error"], "INVALID TRANSFER: ".$transfer);
			Image::paintNoOp();
		}
		// stat
		$sf = new StatFile($transfer);
		$seeds = trim($sf->seeds);
		$peers = trim($sf->peers);
		// client-switch + get peer-data
		$peerData = array();
		$peerData['seeds'] = 0;
		$peerData['peers'] = 0;
		$peerData['seedsLabel'] = ($seeds != "") ? $seeds : 0;
		$peerData['peersLabel'] = ($peers != "") ? $peers : 0;
		$clientType = getTransferClient($transfer);
        switch ($clientType) {
			case "tornado":
				if ($seeds != "") {
					if (strpos($seeds, "+") !== false)
						$seeds = preg_replace('/(\d+)\+.*/i', '${1}', $seeds);
					if (is_numeric($seeds))
						$peerData['seeds'] = $seeds;
					$peerData['seedsLabel'] = $seeds;
				}
				if ($peers != "") {
					if (strpos($peers, "+") !== false)
						$peers = preg_replace('/(\d+)\+.*/i', '${1}', $peers);
					if (is_numeric($peers))
						$peerData['peers'] = $peers;
					$peerData['peersLabel'] = $peers;
				}
            	break;
            case "transmission":
				if ($seeds != "") {
					if (strpos($seeds, "(") !== false)
						$seeds = preg_replace('/.*(\d+) .*/i', '${1}', $seeds);
					if (is_numeric($seeds))
						$peerData['seeds'] = $seeds;
					$peerData['seedsLabel'] = $seeds;
				}
				if ($peers != "") {
					if (strpos($peers, "(") !== false)
						$peers = preg_replace('/.*(\d+) .*/i', '${1}', $peers);
					if (is_numeric($peers))
						$peerData['peers'] = $peers;
					$peerData['peersLabel'] = $peers;
				}
            	break;
            case "mainline":
            	if (($seeds != "") && (is_numeric($seeds))) {
            		$peerData['seeds'] = $seeds;
            		$peerData['seedsLabel'] = $seeds;
            	}
            	if (($peers != "") && (is_numeric($peers))) {
            		$peerData['peers'] = $peers;
            		$peerData['peersLabel'] = $peers;
            	}
            	break;
            case "wget":
			case "nzbperl":
				$peerData['seeds'] = ($seeds != "") ? $seeds : 0;
				$peerData['peers'] = ($peers != "") ? $peers : 0;
				break;
            default:
            	AuditAction($cfg["constants"]["error"], "INVALID TRANSFER: ".$transfer);
				Image::paintNoOp();
        }
		// draw image
		Image::paintPie3D(
			202,
			160,
			100,
			50,
			200,
			100,
			20,
			Image::stringToRGBColor($cfg["body_data_bg"]),
			array($peerData['seeds'] + 0.00001, $peerData['peers'] + 0.00001),
			array(array('r' => 0x00, 'g' => 0xEB, 'b' => 0x0C), array('r' => 0x10, 'g' => 0x00, 'b' => 0xFF)),
			array('Seeds : '.$peerData['seedsLabel'], 'Peers : '.$peerData['peersLabel']),
			58,
			130,
			2,
			14
		);

	case "pieTransferScrape":
		// check for valid referer
		Image::checkReferer();
		// main.internal
		require_once('inc/main.internal.php');
		// output image
		// transfer-id
		$transfer = getRequestVar('transfer');
		if (empty($transfer))
			Image::paintNoOp();
		// validate transfer
		if (isValidTransfer($transfer) !== true) {
			AuditAction($cfg["constants"]["error"], "INVALID TRANSFER: ".$transfer);
			Image::paintNoOp();
		}
		// get scrape-data
		require_once('inc/functions/functions.common.php');
		$scrape = @trim(getTorrentScrapeInfo($transfer));
		if ((!empty($scrape)) && (preg_match("/(\d+) seeder\(s\), (\d+) leecher\(s\).*/i", $scrape, $reg))) {
			$seeder = $reg[1];
			$leecher = $reg[2];
			// draw image
			Image::paintPie3D(
				202,
				160,
				100,
				50,
				200,
				100,
				20,
				Image::stringToRGBColor($cfg["body_data_bg"]),
				array($seeder + 0.00001, $leecher + 0.00001),
				array(array('r' => 0x00, 'g' => 0xEB, 'b' => 0x0C), array('r' => 0x10, 'g' => 0x00, 'b' => 0xFF)),
				array('Seeder : '.$seeder, 'Leecher : '.$leecher),
				58,
				130,
				2,
				14
			);
		} else {
			// output image
			Image::paintNoOp();
		}

	case "spacer":
		// check for valid referer
		Image::checkReferer();
		// output image
		Image::paintSpacer();

	case "notsup":
		// check for valid referer
		Image::checkReferer();
		// output image
		Image::paintNotSupported();

	case "noop":
	default:
		// check for valid referer
		Image::checkReferer();
		// output image
		Image::paintNoOp();

}

?>