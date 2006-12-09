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

/**
 * cliPrintUsage
 */
function cliPrintUsage() {
	echo "\n";
    echo "fluxcli.php Revision " . _REVISION_FLUXCLI . "\n";
	echo "\n";
	echo "Usage: fluxcli.php action [extra-args]\n";
	echo "\naction: \n";
	echo " <transfers>  : print transfers.\n";
	echo " <netstat>    : print netstat.\n";
	echo " <start>      : start a transfer.\n";
	echo "                extra-arg : name of transfer as known inside torrentflux\n";
	echo " <stop>       : stop a transfer.\n";
	echo "                extra-arg : name of transfer as known inside torrentflux\n";
    echo " <start-all>  : start all transfers.\n";
    echo " <resume-all> : resume all transfers.\n";
	echo " <stop-all>   : stop all running transfers.\n";
	echo " <reset>      : reset totals of a transfer.\n";
	echo "                extra-arg : name of transfer as known inside torrentflux\n";
	echo " <delete>     : delete a transfer.\n";
	echo "                extra-arg : name of transfer as known inside torrentflux\n";
	echo " <wipe>       : reset totals, delete torrent, delete torrent-data.\n";
	echo "                extra-arg : name of torrent as known inside torrentflux\n";
	echo " <inject>     : injects a transfer-file into tflux.\n";
	echo "                extra-arg 1 : path to transfer-meta-file\n";
	echo "                extra-arg 2 : username of fluxuser\n";
	echo " <watch>      : watch a dir and inject+start transfers into tflux.\n";
	echo "                extra-arg 1 : path to users watch-dir\n";
	echo "                extra-arg 2 : username of fluxuser\n";
	echo " <rss>        : download torrents matching filter-rules from a rss-feed.\n";
	echo "                extra-arg 1 : save-dir\n";
	echo "                extra-arg 2 : filter-file\n";
	echo "                extra-arg 3 : history-file\n";
	echo "                extra-arg 4 : rss-feed-url\n";
	echo " <xfer>       : xfer-Limit-Shutdown. stop all transfers if xfer-limit is met.\n";
	echo "                extra-arg 1 : time-delta of xfer to use : <all|total|month|week|day>\n";
	echo " <repair>     : repair of torrentflux. DONT do this unless you have to.\n";
	echo "                Doing this on a running ok flux _will_ screw up things.\n";
	echo " <care>       : call clientCare and repair all died transfers.\n";
	echo " <dump>       : dump database.\n";
	echo "                extra-arg 1 : type : settings/users\n";
	echo "\n";
	echo "examples: \n";
	echo "fluxcli.php transfers\n";
	echo "fluxcli.php netstat\n";
	echo "fluxcli.php start foo.torrent\n";
	echo "fluxcli.php stop foo.torrent\n";
	echo "fluxcli.php start-all\n";
	echo "fluxcli.php resume-all\n";
	echo "fluxcli.php stop-all\n";
	echo "fluxcli.php reset foo.torrent\n";
	echo "fluxcli.php delete foo.torrent\n";
	echo "fluxcli.php wipe foo.torrent\n";
	echo "fluxcli.php inject /path/to/foo.torrent fluxuser\n";
    echo "fluxcli.php watch /path/to/watch-dir/ fluxuser\n";
    echo "fluxcli.php rss /path/to/rss-torrents/ /path/to/filter.dat /path/to/filter.hist http://www.example.com/rss.xml\n";
    echo "fluxcli.php xfer month\n";
	echo "fluxcli.php repair\n";
	echo "fluxcli.php care\n";
	echo "fluxcli.php dump settings\n";
	echo "fluxcli.php dump users\n";
	echo "\n";
}

/**
 * cliPrintVersion
 */
function cliPrintVersion() {
    echo "fluxcli.php Revision " . _REVISION_FLUXCLI . "\n";
}

/**
 * cliPrintNetStat
 */
function cliPrintNetStat() {
	global $cfg;
	echo "\n";
    echo "---------------------------------------\n";
	echo "      torrentflux-b4rt-NetStat         \n";
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

/**
 * cliPrintTransfers
 */
function cliPrintTransfers() {
	global $cfg, $db;
	echo "\n";
    echo "----------------------------------------\n";
	echo "      torrentflux-b4rt-Transfers        \n";
    echo "----------------------------------------\n";
    echo "\n";
	// show all .. we set the user to superadmin
    $superAdm = GetSuperAdmin();
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

/**
 * cliStartTransfer
 *
 * @param $transfer name of the Transfer
 */
function cliStartTransfer($transfer = "") {
	global $cfg;
	if ((isset($transfer)) && ($transfer != "")) {
		$tRunningFlag = isTransferRunning($transfer);
		if ($tRunningFlag == 0) {
			$btclient = getTransferClient($transfer);
			$cfg["user"] = getOwner($transfer);
			echo "Starting ".$transfer." ...";
			if ($cfg["enable_file_priority"]) {
				include_once("inc/setpriority.php");
				// Process setPriority Request.
				setPriority($transfer);
			}
			// clientHandler
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg,$btclient);
			// force start, dont queue
			$clientHandler->startClient($transfer, 0, false);
			if ($clientHandler->state == 3) { // hooray
				echo " done\n";
			} else {
				echo "\n" . $clientHandler->messages;
			}
		} else {
			echo "Transfer already running.\n";
		}
	} else {
		cliPrintUsage();
	}
	exit();
}

/**
 * cliStartTransfers
 */
function cliStartTransfers() {
    global $cfg;
    echo "Starting all transfers ...\n";
	$transfers = getTorrentListFromFS();
	foreach ($transfers as $transfer) {
        $tRunningFlag = isTransferRunning($transfer);
        if ($tRunningFlag == 0) {
            echo " - ".$transfer."...";
            $cfg["user"] = getOwner($transfer);
            $btclient = getTransferClient($transfer);
            if ($cfg["enable_file_priority"]) {
                include_once("inc/setpriority.php");
                // Process setPriority Request.
                setPriority($transfer);
            }
            $clientHandler = ClientHandler::getClientHandlerInstance($cfg,$btclient);
            $clientHandler->startClient($transfer, 0, false);
			if ($clientHandler->state == 3) // hooray
				echo " done\n";
			else
				echo "\n" . $clientHandler->messages;
        }
	}
}

/**
 * cliResumeTransfers
 */
function cliResumeTransfers() {
    global $cfg;
    echo "Resuming all transfers ...\n";
	$transfers = getTorrentListFromDB();
	foreach ($transfers as $transfer) {
        $tRunningFlag = isTransferRunning($transfer);
        if ($tRunningFlag == 0) {
            echo " - ".$transfer."...";
            $cfg["user"] = getOwner($transfer);
            $btclient = getTransferClient($transfer);
            if ($cfg["enable_file_priority"]) {
                include_once("inc/setpriority.php");
                // Process setPriority Request.
                setPriority($transfer);
            }
            $clientHandler = ClientHandler::getClientHandlerInstance($cfg,$btclient);
            $clientHandler->startClient($transfer, 0, false);
			if ($clientHandler->state == 3) // hooray
				echo " done\n";
			else
				echo "\n" . $clientHandler->messages;
        }
	}
}

/**
 * cliStopTransfers
 */
function cliStopTransfers() {
	$transfers = getTorrentListFromFS();
	foreach ($transfers as $transfer) {
		if (isTransferRunning($transfer))
			cliStopTransfer($transfer);
	}
}

/**
 * cliStopTransfer
 *
 * @param $transfer name of the Transfer
 */
function cliStopTransfer($transfer = "") {
	global $cfg;
	if ((isset($transfer)) && ($transfer != "")) {
		$tRunningFlag = isTransferRunning($transfer);
		if ($tRunningFlag == 0) {
			echo "Transfer not running.\n";
		} else {
			echo "Stopping ".$transfer." ...";
			$btclient = getTransferClient($transfer);
			$cfg["user"] = getOwner($transfer);
			$alias = getAliasName($transfer).".stat";
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg,$btclient);
            $clientHandler->stopClient($transfer,$alias);
			echo "done\n";
		}
	} else {
		cliPrintUsage();
		exit();
	}
}

/**
 * cliResetTransfer
 *
 * @param $transfer name of the Transfer
 */
function cliResetTransfer($transfer = "") {
	if ((isset($transfer)) && ($transfer != "")) {
		echo "Resetting totals of ".$transfer." ...";
		resetTorrentTotals($transfer, false);
		echo "done\n";
	} else {
		cliPrintUsage();
	}
	exit();
}

/**
 * cliDeleteTransfer
 *
 * @param $transfer name of the transfer
 */
function cliDeleteTransfer($transfer = "") {
	global $cfg;
	if ((isset($transfer)) && ($transfer != "")) {
		echo "Deleting ".$transfer." ...";
        $tRunningFlag = isTransferRunning($transfer);
        $btclient = getTransferClient($transfer);
    	$cfg["user"] = getOwner($transfer);
    	$alias = getAliasName($transfer).".stat";
		if ($tRunningFlag == 1) {
			// stop transfer first
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg,$btclient);
			$clientHandler->stopClient($transfer, $alias);
			$tRunningFlag = isTransferRunning($transfer);
        }
        if ($tRunningFlag == 0) {
        	deleteTransfer($transfer, $alias);
        	echo "done\n";
        } else {
        	echo "transfer still up... cannot delete\n";
        }
	} else {
		cliPrintUsage();
	}
	exit();
}

/**
 * cliWipeTransfer
 *
 * @param $transfer name of the Transfer
 */
function cliWipeTransfer($transfer = "") {
	global $cfg;
	if ((isset($transfer)) && ($transfer != "")) {
		echo "Wipe ".$transfer." ...";
        $tRunningFlag = isTransferRunning($transfer);
        $btclient = getTransferClient($transfer);
		$cfg["user"] = getOwner($transfer);
		$alias = getAliasName($transfer).".stat";
		if ($tRunningFlag == 1) {
			// stop transfer first
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg,$btclient);
			$clientHandler->stopClient($transfer, $alias);
			$tRunningFlag = isTransferRunning($transfer);
        }
        if ($tRunningFlag == 0) {
	        deleteTransfer($transfer);
	        resetTorrentTotals($transfer, true);
			echo "done\n";
        } else {
        	echo "transfer still up... cannot wipe\n";
        }
	} else {
		cliPrintUsage();
	}
	exit();
}

/**
 * cliInjectTransfer
 *
 * @param $tpath path to the Transfer
 * @param $username
 */
function cliInjectTransfer($tpath = "", $username = "") {
	global $cfg;
	if ((isset($tpath)) && ($tpath != "") && (isset($username)) && ($username != "")) {
	    $cfg["user"] = $username;
	    $file_name = basename($tpath);
        $file_name = stripslashes($file_name);
        $file_name = cleanFileName($file_name);
        $ext_msg = "";
        $messages = "";
        if (ereg(getFileFilter($cfg["file_types_array"]), $file_name)) {
            if (is_file($cfg["transfer_file_path"].$file_name)) {
                $messages .= "Error with ".$file_name.", the file already exists on the server.\n";
                $ext_msg = "DUPLICATE :: ";
            } else {
                if ((is_file($tpath)) && (copy($tpath, $cfg["transfer_file_path"].$file_name))) {
                    chmod($cfg["transfer_file_path"].$file_name, 0644);
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
            echo "Injected ".$tpath." as ".$file_name." for user ".$cfg["user"]."\n";
        }
	} else {
		cliPrintUsage();
	}
	exit();
}

/**
 * cliWatchDir
 *
 * @param $tpath path to a watch-dir
 * @param $username
 */
function cliWatchDir($tpath = "", $username = "") {
	global $cfg;
	if ((isset($tpath)) && ($tpath != "") && (isset($username)) && ($username != "")) {
	    if (is_dir($tpath)) {
            $cfg["user"] = $username;
            $watchDir = checkDirPathString($tpath);
            if ($dirHandle = opendir($tpath)) {
                while (false !== ($file = readdir($dirHandle))) {
                    if ((strtolower((substr($file, -8)))) == ".torrent") {
                        $file_name = stripslashes($file);
                        $file_name = cleanFileName($file_name);
                        echo "Injecting and Starting ".$watchDir.$file." as ".$file_name." for user ".$cfg["user"]."...";
                        if ((is_file($watchDir.$file)) && (copy($watchDir.$file, $cfg["transfer_file_path"].$file_name))) {
                            @unlink($watchDir.$file);
                            chmod($cfg["transfer_file_path"].$file_name, 0644);
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
                            if ($clientHandler->state == 3) // hooray
                                echo " done\n";
                            else
                                echo "\n". $clientHandler->messages ."\n";
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
		cliPrintUsage();
	}
	exit();
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
					// limit met, stop all Transfers now.
					echo 'Limit met for "total" : '.formatFreeSpace($xfer_total['total']['total']/(1048576))." / ".formatFreeSpace($cfg["xfer_total"]/(1048576))."\n";
					echo "Stopping all transfers...\n";
					cliStopTransfers();
					return;
				}
			}
		}
		// month
		if (($delta == "all") || ($delta == "month")) {
			// only do if a limit is set
			if ($cfg["xfer_month"] > 0) {
				if ($xfer_total['month']['total'] >= $cfg["xfer_month"]) {
					// limit met, stop all Transfers now.
					echo 'Limit met for "month" : '.formatFreeSpace($xfer_total['month']['total']/(1048576))." / ".formatFreeSpace($cfg["xfer_month"]/(1048576))."\n";
					echo "Stopping all transfers...\n";
					cliStopTransfers();
					return;
				}
			}
		}
		// week
		if (($delta == "all") || ($delta == "week")) {
			// only do if a limit is set
			if ($cfg["xfer_week"] > 0) {
				if ($xfer_total['week']['total'] >= $cfg["xfer_week"]) {
					// limit met, stop all Transfers now.
					echo 'Limit met for "week" : '.formatFreeSpace($xfer_total['week']['total']/(1048576))." / ".formatFreeSpace($cfg["xfer_week"]/(1048576))."\n";
					echo "Stopping all transfers...\n";
					cliStopTransfers();
					return;
				}
			}
		}
		// day
		if (($delta == "all") || ($delta == "day")) {
			// only do if a limit is set
			if ($cfg["xfer_day"] > 0) {
				if ($xfer_total['day']['total'] >= $cfg["xfer_day"]) {
					// limit met, stop all Transfers now.
					echo 'Limit met for "day" : '.formatFreeSpace($xfer_total['day']['total']/(1048576))." / ".formatFreeSpace($cfg["xfer_day"]/(1048576))."\n";
					echo "Stopping all transfers...\n";
					cliStopTransfers();
					return;
				}
			}
		}
	} else {
		cliPrintUsage();
	}
}

/**
 * cliDumpDatabase
 *
 * @param $type settings|users
 */
function cliDumpDatabase($type = "") {
	global $cfg, $db;
	if ((isset($type)) && ($type != "")) {
		switch ($type) {
			case "settings":
			    $sql = "SELECT tf_key, tf_value FROM tf_settings";
				break;
			case "users":
				$sql = "SELECT uid, user_id FROM tf_users";
				break;
			default:
				cliPrintUsage();
				exit();
		}
	    $recordset = $db->Execute($sql);
	    showError($db, $sql);
	    while (list($foo, $bar) = $recordset->FetchRow())
	    	echo $foo . _DUMP_DELIM . $bar . "\n";
	} else {
		cliPrintUsage();
	}
	exit();
}

/**
 * download torrents matching filter-rules from a rss-feed
 *
 * @param $sdir
 * @param $filter
 * @param $history
 * @param $url
 */
function cliProcessRssFeed($sdir = "", $filter = "", $history = "", $url = "") {
	global $cfg;
	$gotArgs = 0;
	if ((isset($sdir)) && ($sdir != ""))
		$gotArgs++;
	if ((isset($filter)) && ($filter != ""))
		$gotArgs++;
	if ((isset($history)) && ($history != ""))
		$gotArgs++;
	if ((isset($url)) && ($url != ""))
		$gotArgs++;
	if ($gotArgs == 4) {
		$rssd = Rssd::getInstance($cfg);
		$rssd->processFeed($sdir, $filter, $history, $url);
	} else {
		cliPrintUsage();
	}
	exit();
}

?>