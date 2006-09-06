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

// -----------------------------------------------------------------------------
// pre-check
// -----------------------------------------------------------------------------

// we dont want to be used from web. as i dunno how to do it in a safe way
// i tried to do it in hopefully safe way ;)
$bail = 0;
if ((isset($_SERVER['REMOTE_ADDR'])) && ($_SERVER['REMOTE_ADDR'] != ""))
	$bail++;
if ((isset($_SERVER['HTTP_USER_AGENT'])) && ($_SERVER['HTTP_USER_AGENT'] != ""))
	$bail++;
if ($bail > 0) {
	@ob_end_clean();
	exit();
}

// -----------------------------------------------------------------------------
// init
// -----------------------------------------------------------------------------

// include path
ini_set('include_path', ini_get('include_path').':../:');

// all functions
require_once('inc/functions/functions.all.php');

// main.common
require_once('inc/main.common.php');

// load default-language
loadLanguageFile($cfg["default_language"]);

// client-handler-"interfaces"
require_once("inc/classes/ClientHandler.php");
require_once("inc/classes/AliasFile.php");
require_once("inc/classes/RunningTransfer.php");

// hold revision-number in a var
$REVISION_FLUXCLI = array_shift(explode(" ",trim(array_pop(explode(":",'$Revision$')))));

// config
$cfg["ip"] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = "fluxcli.php/".$REVISION_FLUXCLI;

// -----------------------------------------------------------------------------
// Main
// -----------------------------------------------------------------------------

$action = @$argv[1];
if ((isset($action)) && ($action != "")) {
	switch ($action) {
		case "torrents":
			printTorrents();
			break;
		case "netstat":
			printNetStat();
			break;
		case "start":
			cliStartTorrent(@$argv[2]);
			break;
		case "stop":
			cliStopTorrent(@$argv[2]);
			break;
		case "start-all":
			cliStartTorrents();
			break;
		case "resume-all":
			cliResumeTorrents();
			break;
		case "stop-all":
			cliStopTorrents();
			break;
		case "reset":
			cliResetTorrent(@$argv[2]);
			break;
		case "delete":
			cliDeleteTorrent(@$argv[2]);
			break;
		case "wipe":
			cliWipeTorrent(@$argv[2]);
			break;
		case "inject":
			cliInjectTorrent(@$argv[2],@$argv[3]);
			break;
		case "watch":
			cliWatchDir(@$argv[2],@$argv[3]);
			break;
		case "xfer":
			cliXferShutdown(@$argv[2]);
			break;
		case "repair":
		    echo "Repairing torrentflux-b4rt Installation...";
			repairTorrentflux();
        	echo "done\n";
        	exit;
			break;
		case "version":
		case "-version":
		case "--version":
		case "-v":
			printVersion();
			break;
		case "help":
		case "--help":
		case "-h":
		default:
			printUsage();
			break;
	}
} else {
	printUsage();
}
exit();

// -----------------------------------------------------------------------------
// functions
// -----------------------------------------------------------------------------

// -----------------------------------------------------------------------------
/*
 * printUsage
 *
 * @param $torrent name of the torrent
 * @return boolean if the settings could be loaded (were existent in db already)
 */
function printUsage() {
	global $REVISION_FLUXCLI;
	echo "\n";
    echo "fluxcli.php Revision ".$REVISION_FLUXCLI."\n";
	echo "\n";
	echo "Usage: fluxcli.php action [extra-args]\n";
	echo "\naction: \n";
	echo " <torrents>   : print torrents. \n";
	echo " <netstat>    : print netstat. \n";
	echo " <start>      : start a torrent. \n";
	echo "                extra-arg : name of torrent as known inside torrentflux \n";
	echo " <stop>       : stop a torrent. \n";
	echo "                extra-arg : name of torrent as known inside torrentflux \n";
    echo " <start-all>  : start all torrents. \n";
    echo " <resume-all> : resume all torrents. \n";
	echo " <stop-all>   : stop all running torrents. \n";
	echo " <reset>      : reset totals of a torrent. \n";
	echo "                extra-arg : name of torrent as known inside torrentflux \n";
	echo " <delete>     : delete a torrent. \n";
	echo "                extra-arg : name of torrent as known inside torrentflux \n";
	echo " <wipe>       : reset totals, delete torrent, delete torrent-data. \n";
	echo "                extra-arg : name of torrent as known inside torrentflux \n";
	echo " <inject>     : injects a torrent-file into tflux. \n";
	echo "                extra-arg 1 : path to torrent-meta-file \n";
	echo "                extra-arg 2 : username of fluxuser \n";
	echo " <watch>      : watch a dir and inject+start torrents into tflux. \n";
	echo "                extra-arg 1 : path to users watch-dir \n";
	echo "                extra-arg 2 : username of fluxuser \n";
	echo " <xfer>       : xfer-Limit-Shutdown. stop all torrents if xfer-limit is met.\n";
	echo "                extra-arg 1 : time-delta of xfer to use : <all|total|month|week|day> \n";
	echo " <repair>     : repair of torrentflux. DONT do this unless you have to. \n";
	echo "                Doing this on a running ok flux _will_ screw up things. \n";
	echo "\n";
	echo "examples: \n";
	echo "fluxcli.php torrents\n";
	echo "fluxcli.php status\n";
	echo "fluxcli.php netstat\n";
	echo "fluxcli.php start foo.torrent\n";
	echo "fluxcli.php stop foo.torrent\n";
	echo "fluxcli.php start-all\n";
	echo "fluxcli.php resume-all\n";
	echo "fluxcli.php stop-all\n";
	echo "fluxcli.php reset foo.torrent\n";
	echo "fluxcli.php delete foo.torrent\n";
	echo "fluxcli.php wipe foo.torrent\n";
	echo "fluxcli.php inject /bar/foo.torrent fluxuser\n";
    echo "fluxcli.php watch /bar/foo/ fluxuser\n";
    echo "fluxcli.php xfer month\n";
	echo "fluxcli.php repair\n";
	echo "\n";
}

// -----------------------------------------------------------------------------
/*
 * printVersion
 *
 */
function printVersion() {
	global $REVISION_FLUXCLI;
    echo "fluxcli.php Revision ".$REVISION_FLUXCLI."\n";
}

// -----------------------------------------------------------------------------
/*
 * printNetStat
 *
 */
function printNetStat() {
	echo "\n";
    echo "---------------------------------------\n";
	echo "          TorrentFlux-NetStat          \n";
    echo "---------------------------------------\n";
	echo "\n";
	echo " --- ".$cfg['_ID_CONNECTIONS']." --- \n";
	echo netstatConnectionsSum();
	echo "\n\n";
	echo " --- ".$cfg['_ID_PORTS']." --- \n";
	echo netstatPortList();
	echo "\n";
	echo " --- ".$cfg['_ID_HOSTS']." --- \n";
	echo netstatHostList();
	echo "\n";
}

// -----------------------------------------------------------------------------
/*
 * printTorrents
 *
 */
function printTorrents() {
	echo "\n";
    echo "----------------------------------------\n";
	echo "          TorrentFlux-Torrents          \n";
    echo "----------------------------------------\n";
    echo "\n";
	global $cfg, $db, $REVISION_FLUXCLI;
	// show all .. we set the user to superadmin
    $superAdm = $db->GetOne("SELECT user_id FROM tf_users WHERE uid = '1'");
    if($db->ErrorNo() != 0) {
        @ob_end_clean();
        exit();
    }
    if ((isset($superAdm)) && ($superAdm != "")) {
        $cfg["user"] = $superAdm;
    } else {
        @ob_end_clean();
        exit();
    }
	// print out transfers
	$transferHeads = getTransferListHeadArray();
	echo " * Name";
	foreach ($transferHeads as $transferHead) {
		echo " * ";
		echo $transferHead;
	}
	echo "\n\n";
	$transferList = getTransferListArray();
	foreach ($transferList as $transferAry) {
		foreach ($transferAry as $transfer) {
			echo " - ";
			echo $transfer;
		}
		echo "\n";
	}
	// print out stats
    if (! array_key_exists("total_download", $cfg))
        $cfg["total_download"] = 0;
    if (! array_key_exists("total_upload", $cfg))
        $cfg["total_upload"] = 0;
	$sumMaxUpRate = getSumMaxUpRate();
	$sumMaxDownRate = getSumMaxDownRate();
	$sumMaxRate = $sumMaxUpRate + $sumMaxDownRate;
	echo "\n";
	echo $cfg['_DOWNLOADSPEED']."\t".': '.number_format($cfg["total_download"], 2).' ('.number_format($sumMaxDownRate, 2).') kB/s'."\n";
	echo $cfg['_UPLOADSPEED']."\t".': '.number_format($cfg["total_upload"], 2).' ('.number_format($sumMaxUpRate, 2).') kB/s'."\n";
	echo $cfg['_TOTALSPEED']."\t".': '.number_format($cfg["total_download"]+$cfg["total_upload"], 2).' ('.number_format($sumMaxRate, 2).') kB/s'."\n";
	echo $cfg['_ID_CONNECTIONS']."\t".': '.netstatConnectionsSum().' ('.getSumMaxCons().')'."\n";
	echo "\n";
}

// -----------------------------------------------------------------------------
/*
 * cliStartTorrent
 *
 * @param $torrent name of the torrent
 */
function cliStartTorrent($torrent = "") {
	global $cfg;
	if ((isset($torrent)) && ($torrent != "")) {
		$tRunningFlag = isTransferRunning($torrent);
		if ($tRunningFlag == 0) {
			$btclient = getTransferClient($torrent);
			$cfg["user"] = getOwner($torrent);
			echo "Starting ".$torrent." ...";
			if ($cfg["enable_file_priority"]) {
				include_once("inc/setpriority.php");
				// Process setPriority Request.
				setPriority($torrent);
			}
			// clientHandler
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg,$btclient);
			// force start, dont queue
			$clientHandler->startClient($torrent, 0, false);
			if ($clientHandler->status == 3) { // hooray
				echo "done\n";
			} else { // start failed
				echo "\n" . $clientHandler->messages;
			}
		} else {
			echo "Torrent already running.\n";
		}
	} else {
		printUsage();
	}
	exit();
}

// -----------------------------------------------------------------------------
/*
 * cliStartTorrents
 *
 */
function cliStartTorrents() {
    global $cfg;
    echo "Starting all torrents ...\n";
	$torrents = getTorrentListFromFS();
	foreach ($torrents as $torrent) {
        $tRunningFlag = isTransferRunning($torrent);
        if ($tRunningFlag == 0) {
            echo " - ".$torrent."...";
            $cfg["user"] = getOwner($torrent);
            $btclient = getTransferClient($torrent);
            if ($cfg["enable_file_priority"]) {
                include_once("inc/setpriority.php");
                // Process setPriority Request.
                setPriority($torrent);
            }
            $clientHandler = ClientHandler::getClientHandlerInstance($cfg,$btclient);
            $clientHandler->startClient($torrent, 0, false);
            // just 2 sec..
            sleep(2);
            //
			if ($clientHandler->status == 3) { // hooray
				echo " done\n";
			} else { // start failed
				echo "\n" . $clientHandler->messages;
			}
        }
	}
}

// -----------------------------------------------------------------------------
/*
 * cliResumeTorrents
 *
 */
function cliResumeTorrents() {
    global $cfg;
    echo "Resuming all torrents ...\n";
	$torrents = getTorrentListFromDB();
	foreach ($torrents as $torrent) {
        $tRunningFlag = isTransferRunning($torrent);
        if ($tRunningFlag == 0) {
            echo " - ".$torrent."...";
            $cfg["user"] = getOwner($torrent);
            $btclient = getTransferClient($torrent);
            if ($cfg["enable_file_priority"]) {
                include_once("inc/setpriority.php");
                // Process setPriority Request.
                setPriority($torrent);
            }
            $clientHandler = ClientHandler::getClientHandlerInstance($cfg,$btclient);
            $clientHandler->startClient($torrent, 0, false);
            // just 2 sec..
            sleep(2);
            //
			if ($clientHandler->status == 3) { // hooray
				echo " done\n";
			} else { // start failed
				echo "\n" . $clientHandler->messages;
			}
        }
	}
}

// -----------------------------------------------------------------------------
/*
 * cliStopTorrents
 *
 */
function cliStopTorrents() {
	$torrents = getTorrentListFromFS();
	foreach ($torrents as $torrent) {
		if (isTransferRunning($torrent))
			cliStopTorrent($torrent);
	}
}

// -----------------------------------------------------------------------------
/*
 * cliStopTorrent
 *
 * @param $torrent name of the torrent
 */
function cliStopTorrent($torrent = "") {
	global $cfg;
	if ((isset($torrent)) && ($torrent != "")) {
		$tRunningFlag = isTransferRunning($torrent);
		if ($tRunningFlag == 0) {
			echo "Torrent not running.\n";
		} else {
			echo "Stopping ".$torrent." ...";
			$btclient = getTransferClient($torrent);
			$cfg["user"] = getOwner($torrent);
			$alias = getAliasName($torrent).".stat";
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg,$btclient);
            $clientHandler->stopClient($torrent,$alias);
			// give the torrent some time to die
            sleep(2);
			echo "done\n";
		}
	} else {
		printUsage();
		exit();
	}
}

// -----------------------------------------------------------------------------
/*
 * cliResetTorrent
 *
 * @param $torrent name of the torrent
 */
function cliResetTorrent($torrent = "") {
	if ((isset($torrent)) && ($torrent != "")) {
		echo "Resetting totals of ".$torrent." ...";
		resetTorrentTotals($torrent, false);
		echo "done\n";
	} else {
		printUsage();
	}
	exit();
}

// -----------------------------------------------------------------------------
/*
 * cliDeleteTorrent
 *
 * @param $torrent name of the torrent
 */
function cliDeleteTorrent($torrent = "") {
	global $cfg;
	if ((isset($torrent)) && ($torrent != "")) {
		echo "Deleting ".$torrent." ...";
        $tRunningFlag = isTransferRunning($torrent);
        $btclient = getTransferClient($torrent);
    	$cfg["user"] = getOwner($torrent);
    	$alias = getAliasName($torrent).".stat";
		if ($tRunningFlag == 1) {
			// stop torrent first
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg,$btclient);
			$clientHandler->stopClient($torrent, $alias);
			// give the torrent some time to die
			sleep(8);
        }
        deleteTransfer($torrent, $alias);
		echo "done\n";
	} else {
		printUsage();
	}
	exit();
}

// -----------------------------------------------------------------------------
/*
 * cliWipeTorrent
 *
 * @param $torrent name of the torrent
 */
function cliWipeTorrent($torrent = "") {
	global $cfg;
	if ((isset($torrent)) && ($torrent != "")) {
		echo "Wipe ".$torrent." ...";
        $tRunningFlag = isTransferRunning($torrent);
        $btclient = getTransferClient($torrent);
		$cfg["user"] = getOwner($torrent);
		$alias = getAliasName($torrent).".stat";
		if ($tRunningFlag == 1) {
			// stop torrent first
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg,$btclient);
			$clientHandler->stopClient($torrent, $alias);
			// give the torrent some time to die
			sleep(6);
        }
        deleteTransfer($torrent);
        resetTorrentTotals($torrent, true);
		echo "done\n";
	} else {
		printUsage();
	}
	exit();
}

// -----------------------------------------------------------------------------
/*
 * cliInjectTorrent
 *
 * @param $tpath path to the torrent
 * @param $username
 */
function cliInjectTorrent($tpath = "", $username = "") {
	global $cfg;
	if ((isset($tpath)) && ($tpath != "") && (isset($username)) && ($username != "")) {
	    $cfg['user'] = $username;
	    $file_name = basename($tpath);
        $file_name = stripslashes($file_name);
        $file_name = str_replace(array("'",","), "", $file_name);
        $file_name = cleanFileName($file_name);
        $ext_msg = "";
        $messages = "";
        if (ereg(getFileFilter($cfg["file_types_array"]), $file_name)) {
            if (is_file($cfg["torrent_file_path"].$file_name)) {
                $messages .= "Error with ".$file_name.", the file already exists on the server.\n";
                $ext_msg = "DUPLICATE :: ";
            } else {
                if ((is_file($tpath)) && (copy($tpath, $cfg["torrent_file_path"].$file_name))) {
                    chmod($cfg["torrent_file_path"].$file_name, 0644);
                    AuditAction($cfg["constants"]["file_upload"], $file_name);
                    // init stat-file
                    injectTorrent($file_name);
                } else {
                    $messages .= "ERROR: File could not be found or could not be copied: ".$tpath."\n";
                }
            }
        } else {
            $ext_msg = "NOT ALLOWED :: ";
            $messages .= "ERROR: The type of file you are injecting is not allowed.\n";
        }
        if($messages != "") { // there was an error
            AuditAction($cfg["constants"]["error"], $cfg["constants"]["file_upload"]." :: ".$ext_msg.$file_name);
            echo $messages;
        } else {
            echo "Injected ".$tpath." as ".$file_name." for user ".$cfg['user']."\n";
        }
	} else {
		printUsage();
	}
	exit();
}

// -----------------------------------------------------------------------------
/*
 * cliWatchDir
 *
 * @param $tpath path to a watch-dir
 * @param $username
 */
function cliWatchDir($tpath = "", $username = "") {
	global $cfg;
	if ((isset($tpath)) && ($tpath != "") && (isset($username)) && ($username != "")) {
	    if (is_dir($tpath)) {
            $cfg['user'] = $username;
            $watchDir = checkDirPathString($tpath);
            if ($dirHandle = opendir($tpath)) {
                while (false !== ($file = readdir($dirHandle))) {
                    if ((strtolower((substr($file, -8)))) == ".torrent") {
                        $file_name = stripslashes($file);
                        $file_name = str_replace(array("'",","), "", $file_name);
                        $file_name = cleanFileName($file_name);
                        echo "Injecting and Starting ".$watchDir.$file." as ".$file_name." for user ".$cfg['user']."...";
                        if ((is_file($watchDir.$file)) && (copy($watchDir.$file, $cfg["torrent_file_path"].$file_name))) {
                            @unlink($watchDir.$file);
                            chmod($cfg["torrent_file_path"].$file_name, 0644);
                            AuditAction($cfg["constants"]["file_upload"], $file_name);
                            // init stat-file
                            injectTorrent($file_name);
                            // file-prio
                            if ($cfg["enable_file_priority"]) {
                                include_once("inc/setpriority.php");
                                // Process setPriority Request.
                                setPriority($file_name);
                            }
                            // start
                            $clientHandler = ClientHandler::getClientHandlerInstance($cfg);
                            $clientHandler->startClient($file_name, 0, false);
                            // just 2 secs..
                            sleep(2);
                            if ($clientHandler->status == 3) // hooray
                                echo " done\n";
                            else  // start failed
                                echo "\n ERROR : ". $clientHandler->messages ."\n";
                        } else {
                            echo "\n ERROR: File could not be found or could not be copied: ".$watchDir.$file."\n";
                        }
                    }
                }
                closedir($dirHandle);
            }
	    } else {
	        echo "ERROR: ".$tpath." is not a dir.\n";
	        exit();
	    }
	} else {
		printUsage();
	}
	exit;
}

/**
 * cliXferShutdown
 *
 * @param string $delta
 */
function cliXferShutdown($delta = '') {
	global $cfg, $xfer_total;
	if ($cfg['enable_xfer'] != 1) {
		echo "Error, xfer-Hack must be enabled. \n";
		return;
	}
	if ((isset($delta)) && ($delta != "")) {
		// getTransferListArray to update xfer-stats
		$cfg['xfer_realtime'] = 1;
		$dirList = @getTransferListArray();
		// check if break needed
		// total
		if (($delta == "all") || ($delta == "total")) {
			// only do if a limit is set
			if ($cfg["xfer_total"] > 0) {
				if ($xfer_total['total']['total'] >= $cfg["xfer_total"]) {
					// limit met, stop all torrents now.
					echo 'Limit met for "total" : '.formatFreeSpace($xfer_total['total']['total']/(1048576))." / ".formatFreeSpace($cfg["xfer_total"]/(1048576))."\n";
					echo "Stopping all torrents...\n";
					cliStopTorrents();
					return;
				}
			}
		}
		// month
		if (($delta == "all") || ($delta == "month")) {
			// only do if a limit is set
			if ($cfg["xfer_month"] > 0) {
				if ($xfer_total['month']['total'] >= $cfg["xfer_month"]) {
					// limit met, stop all torrents now.
					echo 'Limit met for "month" : '.formatFreeSpace($xfer_total['month']['total']/(1048576))." / ".formatFreeSpace($cfg["xfer_month"]/(1048576))."\n";
					echo "Stopping all torrents...\n";
					cliStopTorrents();
					return;
				}
			}
		}
		// week
		if (($delta == "all") || ($delta == "week")) {
			// only do if a limit is set
			if ($cfg["xfer_week"] > 0) {
				if ($xfer_total['week']['total'] >= $cfg["xfer_week"]) {
					// limit met, stop all torrents now.
					echo 'Limit met for "week" : '.formatFreeSpace($xfer_total['week']['total']/(1048576))." / ".formatFreeSpace($cfg["xfer_week"]/(1048576))."\n";
					echo "Stopping all torrents...\n";
					cliStopTorrents();
					return;
				}
			}
		}
		// day
		if (($delta == "all") || ($delta == "day")) {
			// only do if a limit is set
			if ($cfg["xfer_day"] > 0) {
				if ($xfer_total['day']['total'] >= $cfg["xfer_day"]) {
					// limit met, stop all torrents now.
					echo 'Limit met for "day" : '.formatFreeSpace($xfer_total['day']['total']/(1048576))." / ".formatFreeSpace($cfg["xfer_day"]/(1048576))."\n";
					echo "Stopping all torrents...\n";
					cliStopTorrents();
					return;
				}
			}
		}
	} else {
		printUsage();
	}
}

?>