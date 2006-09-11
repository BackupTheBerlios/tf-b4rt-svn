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

// maketorrent
require_once("inc/functions/functions.maketorrent.php");

// Variable information
$tpath	  = $cfg["transfer_file_path"];
$tfile	  = @ $_POST['torrent'];
$file	  = @ $_GET['path'];
$torrent  = @ cleanFileName(StripFolders(trim($file))).".torrent";
$announce = @ ($_POST['announce']) ? $_POST['announce'] : "http://";
$ancelist = @ $_POST['announcelist'];
$comment  = @ $_POST['comments'];
$piece	  = @ $_POST['piecesize'];
$alert	  = @ ($_POST['alert']) ? 1 : "";
$private  = @ ($_POST['Private'] == "Private") ? true : false;
$dht	  = @ ($_POST['DHT'] == "DHT") ? true : false;

/*******************************************************************************
 * create the torrent
 ******************************************************************************/
if(!empty($announce) && $announce != "http://" ) {
	// check dir
	checkDirectory($tpath);
	// Clean up old files
	if(@file_exists($tpath.$tfile ))
		@unlink( $tpath . $tfile );

	// This is the command to execute
	$app = "nohup ".$cfg["pythonCmd"]." -OO ".dirname($_SERVER["SCRIPT_FILENAME"])."/bin/TF_BitTornado/btmakemetafile.py ".$announce." ".escapeshellarg($cfg["path"].$file)." ";
	// Is there comments to add?
	if(!empty($comment))
		$app .= "--comment " . escapeshellarg($comment) . " ";
	// Set the piece size
	if(!empty($piece))
		$app .= "--piece_size_pow2 " . $piece . " ";
	if(!empty($ancelist)) {
		$check = "/" . str_replace("/", "\/", quotemeta($announce)) . "/i";
		// if they didn't add the primary tracker in, we will add it for them
		if( preg_match( $check, $ancelist, $result ) )
			$app .= "--announce_list " . escapeshellarg($ancelist) . " ";
		else
			$app .= "--announce_list " . escapeshellarg ($announce . "," . $ancelist) . " ";
	}
	// Set the target torrent field
	$app .= "--target " . escapeshellarg($tpath . $tfile);

	// Set to never timeout for large torrents
	set_time_limit(0);
	// Let's see how long this takes...
	$time_start = microtime(true);
	// Execute the command -- w00t!
	exec($app);
	// We want to check to make sure the file was successful
	$success = false;
	$raw = @file_get_contents($tpath.$tfile );
	if(preg_match( "/6:pieces([^:]+):/i", $raw, $results)) {
		// This means it is a valid torrent
		$success = true;
		// Make an entry for the owner
		AuditAction($cfg["constants"]["file_upload"], $tfile);
		// Check to see if one of the flags were set
		if($private || $dht) {
			// Add private/dht Flags
			// e7:privatei1e
			// e17:dht_backup_enablei1e
			// e20:dht_backup_requestedi1e
			if(preg_match( "/6:pieces([^:]+):/i", $raw, $results)) {
				$pos = strpos( $raw, "6:pieces" ) + 9 + strlen( $results[1] ) + $results[1];
				$fp = @fopen( $tpath . $tfile, "r+" );
				@fseek( $fp, $pos, SEEK_SET );
				if($private)
					@fwrite($fp,"7:privatei1eee");
				else
					@fwrite($fp,"e7:privatei0e17:dht_backup_enablei1e20:dht_backup_requestedi1eee");

				@fclose( $fp );
			}
		}
	} else {
		// Something went wrong, clean up
		if(@file_exists($tpath.$tfile))
			@unlink($tpath.$tfile);
	}
	// We are done! how long did we take?
	$time_end = microtime( true );
	$diff = duration($time_end - $time_start);
	// make path URL friendly to support non-standard characters
	$downpath = urlencode($tfile);
	// Depending if we were successful, display the required information
	if($success)
		$onLoad = "completed( '" . $downpath . "', " . $alert. ", '" . $diff . "' );";
	else
		$onLoad = "failed( '" . $downpath . "', " . $alert . " );";
}

/*******************************************************************************
 * torrent download prompt
 ******************************************************************************/
if(!empty($_GET["download"] ) ) {
	$tfile = $_GET["download"];
	// ../ is not allowed in the file name
	if (!ereg("(\.\.\/)", $tfile)) {
		// Does the file exist?
		if (file_exists($tpath . $tfile)) {
			// Prompt the user to download the new torrent file.
			header( "Content-type: application/octet-stream\n" );
			header( "Content-disposition: attachment; filename=\"" . $tfile . "\"\n" );
			header( "Content-transfer-encoding: binary\n");
			header( "Content-length: " . @filesize( $tpath . $tfile ) . "\n" );
			// Send the torrent file
			$fp = @fopen( $tpath . $tfile, "r" );
			@fpassthru( $fp );
			@fclose( $fp );
			AuditAction($cfg["constants"]["fm_download"], $tfile);
		} else {
			AuditAction($cfg["constants"]["error"], "File Not found for download: ".$cfg["user"]." tried to download ".$tfile);
		}
	} else {
		AuditAction($cfg["constants"]["error"], "ILLEGAL DOWNLOAD: ".$cfg["user"]." tried to download ".$tfile);
	}
	exit();
}

/*******************************************************************************
 * page
 ******************************************************************************/

// create template-instance
$tmpl = getTemplateInstance($cfg["theme"], "maketorrent.tmpl");
// set vars
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('main_bgcolor', $cfg["main_bgcolor"]);
$tmpl->setvar('theme', $cfg["theme"]);
if ((!empty($private)) && ($private))
	$tmpl->setvar('is_private', 1);
else
	$tmpl->setvar('is_private', 0);
if (!empty($onLoad))
	$tmpl->setvar('onLoad', $onLoad);
$tmpl->setvar('table_border_dk', $cfg["table_border_dk"]);
$tmpl->setvar('getTitleBar', getTitleBar($cfg["pagetitle"]." - Torrent Maker", false));
$tmpl->setvar('table_header_bg', $cfg["table_header_bg"]);
$tmpl->setvar('body_data_bg', $cfg["body_data_bg"]);
$tmpl->setvar('REQUEST_URI', $_SERVER['REQUEST_URI']);
$tmpl->setvar('torrent', $torrent);
$tmpl->setvar('announce', $announce);
$tmpl->setvar('ancelist', $ancelist);
$tmpl->setvar('comment', $comment);
$tmpl->setvar('dht', $dht);
$tmpl->setvar('alert', $alert);
$tmpl->setvar('getTorrentFluxLink', getTorrentFluxLink());
$tmpl->setvar('iid', $_GET["iid"]);
$tmpl->pparse();

?>