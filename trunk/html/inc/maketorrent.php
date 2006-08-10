<?php

/* $Id: maketorrent.php 189 2006-08-06 20:03:40Z msn_exploder $ */

/*************************************************************
*  TorrentFlux PHP Torrent Manager
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

   /*****
	Usage: btmakemetafile.py <trackerurl> <file> [file...] [params...]

	--announce_list <arg>
			  a list of announce URLs - explained below (defaults to '')

	--httpseeds <arg>
			  a list of http seed URLs - explained below (defaults to '')

	--piece_size_pow2 <arg>
			  which power of 2 to set the piece size to (0 = automatic) (defaults
			  to 0)

	--comment <arg>
			  optional human-readable comment to put in .torrent (defaults to '')

	--filesystem_encoding <arg>
			  optional specification for filesystem encoding (set automatically in
			  recent Python versions) (defaults to '')

	--target <arg>
			  optional target file for the torrent (defaults to '')


	announce_list = optional list of redundant/backup tracker URLs, in the format:
		   url[,url...][|url[,url...]...]
				where URLs separated by commas are all tried first
				before the next group of URLs separated by the pipe is checked.
				If none is given, it is assumed you don't want one in the metafile.
				If announce_list is given, clients which support it
				will ignore the <announce> value.
		   Examples:
				http://tracker1.com|http://tracker2.com|http://tracker3.com
					 (tries trackers 1-3 in order)
				http://tracker1.com,http://tracker2.com,http://tracker3.com
					 (tries trackers 1-3 in a randomly selected order)
				http://tracker1.com|http://backup1.com,http://backup2.com
					 (tries tracker 1 first, then tries between the 2 backups randomly)

	httpseeds = optional list of http-seed URLs, in the format:
			url[|url...]
*****/

require_once("config.php");
require_once("functions.php");
require_once("lib/vlib/vlibTemplate.php");

# create new template
if (!ereg('^[^./][^/]*$', $cfg["theme"])) {
	$tmpl = new vlibTemplate("themes/old_style_themes/tmpl/maketorrent.tmpl");
}
else {
	$tmpl = new vlibTemplate("themes/".$cfg["theme"]."/tmpl/maketorrent.tmpl");
}

// Variable information
$tpath	  = $cfg["torrent_file_path"];
$tfile	  = @ $_POST['torrent'];
$file	  = @ $_GET['path'];
$torrent  = cleanFileName(StripFolders( trim($file) )) . ".torrent";
$announce = @ ( $_POST['announce'] ) ? $_POST['announce'] : "http://";
$ancelist = @ $_POST['announcelist'];
$comment  = @ $_POST['comments'];
$peice	  = @ $_POST['piecesize'];
$alert	  = @ ( $_POST['alert'] ) ? 1 : "";
$private  = @ ( $_POST['Private'] == "Private" ) ? true : false;
$dht	  = @ ( $_POST['DHT'] == "DHT" ) ? true : false;

// Let's create the torrent
if( !empty( $announce ) && $announce != "http://" )
{
	// Create maketorrent directory if it doesn't exist
	if( !is_dir( $tpath ) )
	{
		@mkdir( $tpath );
	}

	// Clean up old files
	if( @file_exists( $tpath . $tfile ) )
	{
		@unlink( $tpath . $tfile );
	}

	// This is the command to execute
	$app = "nohup " . $cfg["pythonCmd"] . " -OO " . $cfg["btmakemetafile"] . " " . $announce . " " . escapeshellarg( $cfg['path'] . $file ) . " ";

	// Is there comments to add?
	if( !empty( $comment ) )
	{
		$app .= "--comment " . escapeshellarg( $comment ) . " ";
	}

	// Set the piece size
	if( !empty( $peice ) )
	{
		$app .= "--piece_size_pow2 " . $peice . " ";
	}

	if( !empty( $ancelist ) )
	{
		$check = "/" . str_replace( "/", "\/", quotemeta( $announce ) ) . "/i";
		// if they didn't add the primary tracker in, we will add it for them
		if( preg_match( $check, $ancelist, $result ) )
			$app .= "--announce_list " . escapeshellarg( $ancelist ) . " ";
		else
			$app .= "--announce_list " . escapeshellarg ( $announce . "," . $ancelist ) . " ";
	}

	// Set the target torrent fiel
	$app .= "--target " . escapeshellarg( $tpath . $tfile );

	// Set to never timeout for large torrents
	set_time_limit( 0 );

	// Let's see how long this takes...
	$time_start = microtime( true );

	// Execute the command -- w00t!
	exec( $app );

	// We want to check to make sure the file was successful
	$success = false;
	$raw = @file_get_contents( $tpath . $tfile );
	if( preg_match( "/6:pieces([^:]+):/i", $raw, $results ) )
	{
		// This means it is a valid torrent
		$success = true;

		// Make an entry for the owner
		AuditAction($cfg["constants"]["file_upload"], $tfile);

		// Check to see if one of the flags were set
		if( $private || $dht )
		{
			// Add private/dht Flags
			// e7:privatei1e
			// e17:dht_backup_enablei1e
			// e20:dht_backup_requestedi1e
			if( preg_match( "/6:pieces([^:]+):/i", $raw, $results ) )
			{
				$pos = strpos( $raw, "6:pieces" ) + 9 + strlen( $results[1] ) + $results[1];
				$fp = @fopen( $tpath . $tfile, "r+" );
				@fseek( $fp, $pos, SEEK_SET );
				// b4rt-81
				/*
				if( $private ) {
					@fwrite( $fp, "7:privatei1e17:dht_backup_enablei0e20:dht_backup_requestedi0eee" );
				} else {
					@fwrite( $fp, "e7:privatei0e17:dht_backup_enablei1e20:dht_backup_requestedi1eee" );
				}
				*/
				if($private)
					@fwrite($fp,"7:privatei1eee");
				else
					@fwrite($fp,"e7:privatei0e17:dht_backup_enablei1e20:dht_backup_requestedi1eee");
				// b4rt-81
				@fclose( $fp );
			}
		}
	}
	else
	{
		// Something went wrong, clean up
		if( @file_exists( $tpath . $tfile ) )
		{
			@unlink( $tpath . $tfile );
		}
	}

	// We are done! how long did we take?
	$time_end = microtime( true );
	$diff = duration($time_end - $time_start);

	// make path URL friendly to support non-standard characters
	$downpath = urlencode( $tfile );

	// Depending if we were successful, display the required information
	if( $success )
	{
		$onLoad = "completed( '" . $downpath . "', " . $alert. ", '" . $diff . "' );";
	}
	else
	{
		$onLoad = "failed( '" . $downpath . "', " . $alert . " );";
	}
}

// This is the torrent download prompt
if( !empty( $_GET["download"] ) )
{
	$tfile = $_GET["download"];

	// ../ is not allowed in the file name
	if (!ereg("(\.\.\/)", $tfile))
	{
		// Does the file exist?
		if (file_exists($tpath . $tfile))
		{
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
		}
		else
		{
			AuditAction($cfg["constants"]["error"], "File Not found for download: ".$cfg['user']." tried to download ".$tfile);
		}
	}
	else
	{
		AuditAction($cfg["constants"]["error"], "ILLEGAL DOWNLOAD: ".$cfg['user']." tried to download ".$tfile);
	}
	exit();
}

// Strip the folders from the path
function StripFolders( $path )
{
	$pos = strrpos( $path, "/" ) + 1;
	$path = substr( $path, $pos );
	return $path;
}

// Convert a timestamp to a duration string
function duration( $timestamp )
{

	$years = floor( $timestamp / ( 60 * 60 * 24 * 365 ) );
	$timestamp %= 60 * 60 * 24 * 365;

	$weeks = floor( $timestamp / ( 60 * 60 * 24 * 7 ) );
	$timestamp %= 60 * 60 * 24 * 7;

	$days = floor( $timestamp / ( 60 * 60 * 24 ) );
	$timestamp %= 60 * 60 * 24;

	$hrs = floor( $timestamp / ( 60 * 60 ) );
	$timestamp %= 60 * 60;

	$mins = floor( $timestamp / 60 );
	$secs = $timestamp % 60;

	$str = "";

	if( $years >= 1 )
		$str .= "{$years} years ";
	if( $weeks >= 1 )
		$str .= "{$weeks} weeks ";
	if( $days >= 1 )
		$str .= "{$days} days ";
	if( $hrs >= 1 )
		$str .= "{$hrs} hours ";
	if( $mins >= 1 )
		$str .= "{$mins} minutes ";
	if( $secs >= 1 )
		$str.="{$secs} seconds ";

	return $str;
}
$tmpl->setvar('pagetitle', $cfg["pagetitle"]);
$tmpl->setvar('theme', $cfg["theme"]);
$tmpl->setvar('main_bgcolor', $cfg["main_bgcolor"]);
$tmpl->setvar('theme', $cfg["theme"]);
if( !empty( $private ) ) {
	$tmpl->setvar('is_private', 1);
}
else {
	$tmpl->setvar('is_private', 0);
}
if( !empty( $onLoad ) ) {
	$tmpl->setvar('onLoad', $onLoad);
}
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
$tmpl->pparse();
?>