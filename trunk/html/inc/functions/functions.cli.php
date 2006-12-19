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
	$content = ""
	. "\n"
    . "fluxcli.php Revision " . _REVISION_FLUXCLI . "\n"
	. "\n"
	. "Usage: fluxcli.php action [extra-args]\n"
	. "\naction: \n"
	. " <transfers>   : print transfers.\n"
	. " <netstat>     : print netstat.\n"
	. " <start>       : start a transfer.\n"
	. "                 extra-arg : name of transfer as known inside torrentflux\n"
	. " <stop>        : stop a transfer.\n"
	. "                 extra-arg : name of transfer as known inside torrentflux\n"
    . " <start-all>   : start all transfers.\n"
    . " <resume-all>  : resume all transfers.\n"
	. " <stop-all>    : stop all running transfers.\n"
	. " <reset>       : reset totals of a transfer.\n"
	. "                 extra-arg : name of transfer as known inside torrentflux\n"
	. " <delete>      : delete a transfer.\n"
	. "                 extra-arg : name of transfer as known inside torrentflux\n"
	. " <wipe>        : reset totals, delete torrent, delete torrent-data.\n"
	. "                 extra-arg : name of torrent as known inside torrentflux\n"
	. " <inject>      : injects a transfer-file into tflux.\n"
	. "                 extra-arg 1 : path to transfer-meta-file\n"
	. "                 extra-arg 2 : username of fluxuser\n"
	. " <watch>       : watch a dir and inject+start transfers into tflux.\n"
	. "                 extra-arg 1 : path to users watch-dir\n"
	. "                 extra-arg 2 : username of fluxuser\n"
	. " <rss>         : download torrents matching filter-rules from a rss-feed.\n"
	. "                 extra-arg 1 : save-dir\n"
	. "                 extra-arg 2 : filter-file\n"
	. "                 extra-arg 3 : history-file\n"
	. "                 extra-arg 4 : rss-feed-url\n"
	. " <xfer>        : xfer-Limit-Shutdown. stop all transfers if xfer-limit is met.\n"
	. "                 extra-arg 1 : time-delta of xfer to use : <all|total|month|week|day>\n"
	. " <repair>      : repair of torrentflux. DONT do this unless you have to.\n"
	. "                 Doing this on a running ok flux _will_ screw up things.\n"
	. " <maintenance> : call maintenance and repair all died transfers.\n"
	. "                 extra-arg 1 : restart died transfers (true/false)\n"
	. " <dump>        : dump database.\n"
	. "                 extra-arg 1 : type : settings/users\n"
	. " <filelist>    : print file-list.\n"
	. "                 extra-arg 1 : dir (if empty docroot is used)\n"
	. " <checksums>   : print checksum-list.\n"
	. "                 extra-arg 1 : dir (if empty docroot is used)\n"
	. "\n"
	. "examples: \n"
	. "fluxcli.php transfers\n"
	. "fluxcli.php netstat\n"
	. "fluxcli.php start foo.torrent\n"
	. "fluxcli.php stop foo.torrent\n"
	. "fluxcli.php start-all\n"
	. "fluxcli.php resume-all\n"
	. "fluxcli.php stop-all\n"
	. "fluxcli.php reset foo.torrent\n"
	. "fluxcli.php delete foo.torrent\n"
	. "fluxcli.php wipe foo.torrent\n"
	. "fluxcli.php inject /path/to/foo.torrent fluxuser\n"
    . "fluxcli.php watch /path/to/watch-dir/ fluxuser\n"
    . "fluxcli.php rss /path/to/rss-torrents/ /path/to/filter.dat /path/to/filter.hist http://www.example.com/rss.xml\n"
    . "fluxcli.php xfer month\n"
	. "fluxcli.php repair\n"
	. "fluxcli.php maintenance true\n"
	. "fluxcli.php dump settings\n"
	. "fluxcli.php dump users\n"
	. "fluxcli.php filelist /var/www\n"
	. "fluxcli.php checksums /var/www\n"
	. "\n";
	echo $content;
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
	global $cfg, $db, $transfers;
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
	echo "\n";
	echo $cfg['_DOWNLOADSPEED']."\t".': '.number_format($cfg["total_download"], 2).' ('.number_format($transfers['sum']['drate'], 2).') kB/s'."\n";
	echo $cfg['_UPLOADSPEED']."\t".': '.number_format($cfg["total_upload"], 2).' ('.number_format($transfers['sum']['rate'], 2).') kB/s'."\n";
	echo $cfg['_TOTALSPEED']."\t".': '.number_format($cfg["total_download"]+$cfg["total_upload"], 2).' ('.number_format($transfers['sum']['rate'] + $transfers['sum']['drate'], 2).') kB/s'."\n";
	echo $cfg['_ID_CONNECTIONS']."\t".': '.netstatConnectionsSum().' ('.$transfers['sum']['maxcons'].')'."\n";
	echo "\n";
}

/**
 * cliStartTransfer
 *
 * @param $transfer name of the Transfer
 */
function cliStartTransfer($transfer = "") {
	global $cfg, $transfers;
	if ((isset($transfer)) && ($transfer != "")) {
		$tRunningFlag = isTransferRunning($transfer);
		if ($tRunningFlag == 0) {
			$btclient = getTransferClient($transfer);
			$cfg["user"] = getOwner($transfer);
			printMessage("fluxcli.php", "Starting ".$transfer." ...\n");
			if ($cfg["enable_file_priority"]) {
				include_once("inc/functions/functions.setpriority.php");
				// Process setPriority Request.
				setPriority($transfer);
			}
			// clientHandler
			$clientHandler = ClientHandler::getInstance($btclient);
			// force start, dont queue
			$clientHandler->start($transfer, false, false);
			if ($clientHandler->state == CLIENTHANDLER_STATE_OK) /* hooray */
				printMessage("fluxcli.php", "done.\n");
			else
				printError("fluxcli.php", "messages:\n".implode("\n", $clientHandler->messages)."\n");
		} else {
			printError("fluxcli.php", "Transfer already running.\n");
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
    global $cfg, $transfers;
    printMessage("fluxcli.php", "Starting all transfers ...\n");
	$transferList = getTorrentListFromFS();
	foreach ($transferList as $transfer) {
        $tRunningFlag = isTransferRunning($transfer);
        if ($tRunningFlag == 0) {
            printMessage("fluxcli.php", "Starting ".$transfer." ...\n");
            $cfg["user"] = getOwner($transfer);
            $btclient = getTransferClient($transfer);
            if ($cfg["enable_file_priority"]) {
                include_once("inc/functions/functions.setpriority.php");
                // Process setPriority Request.
                setPriority($transfer);
            }
            $clientHandler = ClientHandler::getInstance($btclient);
            $clientHandler->start($transfer, false, false);
			if ($clientHandler->state == CLIENTHANDLER_STATE_OK) /* hooray */
				printMessage("fluxcli.php", "done.\n");
			else
				printError("fluxcli.php", "messages:\n".implode("\n", $clientHandler->messages)."\n");
        }
	}
}

/**
 * cliResumeTransfers
 */
function cliResumeTransfers() {
    global $cfg, $transfers;
    printMessage("fluxcli.php", "Resuming all transfers ...\n");
	$transferList = getTorrentListFromDB();
	foreach ($transferList as $transfer) {
        $tRunningFlag = isTransferRunning($transfer);
        if ($tRunningFlag == 0) {
            printMessage("fluxcli.php", "Starting ".$transfer." ...\n");
            $cfg["user"] = getOwner($transfer);
            $btclient = getTransferClient($transfer);
            if ($cfg["enable_file_priority"]) {
                include_once("inc/functions/functions.setpriority.php");
                // Process setPriority Request.
                setPriority($transfer);
            }
            $clientHandler = ClientHandler::getInstance($btclient);
            $clientHandler->start($transfer, false, false);
			if ($clientHandler->state == CLIENTHANDLER_STATE_OK) /* hooray */
				printMessage("fluxcli.php", "done.\n");
			else
				printError("fluxcli.php", "messages:\n".implode("\n", $clientHandler->messages)."\n");
        }
	}
}

/**
 * cliStopTransfers
 */
function cliStopTransfers() {
	$transferList = getTorrentListFromFS();
	foreach ($transferList as $transfer) {
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
	global $cfg, $transfers;
	if ((isset($transfer)) && ($transfer != "")) {
		$tRunningFlag = isTransferRunning($transfer);
		if ($tRunningFlag == 0) {
			printError("fluxcli.php", "Transfer not running.\n");
		} else {
			printMessage("fluxcli.php", "Stopping ".$transfer." ...\n");
			$cfg["user"] = getOwner($transfer);
			$clientHandler = ClientHandler::getInstance(getTransferClient($transfer));
            $clientHandler->stop($transfer);
			printMessage("fluxcli.php", "done.\n");
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
		printMessage("fluxcli.php", "Resetting totals of ".$transfer." ...\n");
		$msgs = resetTorrentTotals($transfer, false);
		if (count($msgs) > 0)
        	printMessage("fluxcli.php", "failed: ".$transfer."\n".implode("\n", $msgs));
		else
			printMessage("fluxcli.php", "done.\n");
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
	global $cfg, $transfers;
	if ((isset($transfer)) && ($transfer != "")) {
		printMessage("fluxcli.php", "Deleting ".$transfer." ...\n");
		$cfg["user"] = getOwner($transfer);
		$clientHandler = ClientHandler::getInstance(getTransferClient($transfer));
		$tRunningFlag = isTransferRunning($transfer);
		if ($tRunningFlag == 1) {
			// stop transfer first
			$clientHandler->stop($transfer);
			$tRunningFlag = isTransferRunning($transfer);
        }
        if ($tRunningFlag == 0) {
        	$clientHandler->delete($transfer);
        	printMessage("fluxcli.php", "done.\n");
        } else {
        	printError("fluxcli.php", "transfer still up... cannot delete\n");
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
	global $cfg, $transfers;
	if ((isset($transfer)) && ($transfer != "")) {
		printMessage("fluxcli.php", "Wipe ".$transfer." ...\n");
		$cfg["user"] = getOwner($transfer);
		$clientHandler = ClientHandler::getInstance(getTransferClient($transfer));
		$tRunningFlag = isTransferRunning($transfer);
		if ($tRunningFlag == 1) {
			// stop transfer first
			$clientHandler->stop($transfer);
			$tRunningFlag = isTransferRunning($transfer);
        }
        if ($tRunningFlag == 0) {
        	if (substr($transfer, -8) == ".torrent") {
        		deleteTorrentData($transfer);
				$msgs = resetTorrentTotals($transfer, true);
				if (count($msgs) > 0) {
		        	printMessage("fluxcli.php", "failed: ".$transfer."\n".implode("\n", $msgs));
		        	exit;
				}
        	}
        	$clientHandler->delete($transfer);
        	if (count($clientHandler->messages) > 0)
    			printMessage("fluxcli.php", "failed: ".$transfer."\n".implode("\n", $clientHandler->messages));
    		else
        		printMessage("fluxcli.php", "done.\n");
        } else {
        	printError("fluxcli.php", "transfer still up... cannot wipe\n");
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
        if (isValidTransfer($file_name)) {
            if (is_file($cfg["transfer_file_path"].$file_name)) {
                $messages .= "Error with ".$file_name.", the file already exists on the server.\n";
                $ext_msg = "DUPLICATE :: ";
            } else {
                if ((is_file($tpath)) && (copy($tpath, $cfg["transfer_file_path"].$file_name))) {
                    chmod($cfg["transfer_file_path"].$file_name, 0644);
                    AuditAction($cfg["constants"]["file_upload"], $file_name);
                    // init stat-file
                    injectAlias($file_name);
                } else {
                    $messages .= "ERROR: File could not be found or could not be copied: ".$tpath."\n";
                }
            }
        } else {
            $ext_msg = "NOT ALLOWED :: ";
            $messages .= "ERROR: The type of file you are injecting is not allowed.\n";
        }
        if ($messages != "") { // there was an error
            AuditAction($cfg["constants"]["error"], $cfg["constants"]["file_upload"]." :: ".$ext_msg.$file_name);
            printError("fluxcli.php", $messages);
        } else {
        	printMessage("fluxcli.php", "Injected ".$tpath." as ".$file_name." for user ".$cfg["user"]."\n");
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
                    if (substr($file, -8) == ".torrent") {
                        $file_name = stripslashes($file);
                        $file_name = cleanFileName($file_name);
                        printMessage("fluxcli.php", "Injecting and Starting ".$watchDir.$file." as ".$file_name." for user ".$cfg["user"]."...\n");
                        if ((is_file($watchDir.$file)) && (copy($watchDir.$file, $cfg["transfer_file_path"].$file_name))) {
                            @unlink($watchDir.$file);
                            chmod($cfg["transfer_file_path"].$file_name, 0644);
                            AuditAction($cfg["constants"]["file_upload"], $file_name);
                            // init stat-file
                            injectAlias($file_name);
                            // file-prio
                            if ($cfg["enable_file_priority"]) {
                                include_once("inc/functions/functions.setpriority.php");
                                // Process setPriority Request.
                                setPriority($file_name);
                            }
                            // start
                            $clientHandler = ClientHandler::getInstance();
                            $clientHandler->start($file_name, false, false);
                            if ($clientHandler->state == CLIENTHANDLER_STATE_OK) /* hooray */
                            	printMessage("fluxcli.php", "done.\n");
                            else
                            	printError("fluxcli.php", "messages:\n".implode("\n", $clientHandler->messages)."\n");
                        } else {
                        	printError("fluxcli.php", "ERROR: File could not be found or could not be copied: ".$watchDir.$file."\n");
                        }
                    }
                }
                closedir($dirHandle);
            }
	    } else {
	    	printError("fluxcli.php", "ERROR: ".$tpath." is not a dir.\n");
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
		printError("fluxcli.php", "Error, xfer-Hack must be enabled.\n");
		return;
	}
	if ((isset($delta)) && ($delta != "")) {
		// getTransferListArray to update xfer-stats
    	// xfer-init
    	if ($cfg['xfer_realtime'] == 0) {
			$cfg['xfer_realtime'] = 1;
			$cfg['xfer_newday'] = 0;
			$cfg['xfer_newday'] = !$db->GetOne('SELECT 1 FROM tf_xfer WHERE date = '.$db->DBDate(time()));
    	}
		$dirList = @getTransferListArray();
		// check if break needed
		// total
		if (($delta == "all") || ($delta == "total")) {
			// only do if a limit is set
			if ($cfg["xfer_total"] > 0) {
				if ($xfer_total['total']['total'] >= $cfg["xfer_total"]) {
					// limit met, stop all Transfers now.
					printMessage("fluxcli.php", 'Limit met for "total" : '.formatFreeSpace($xfer_total['total']['total']/(1048576))." / ".formatFreeSpace($cfg["xfer_total"]/(1048576))."\n");
					printMessage("fluxcli.php", "Stopping all transfers...\n");
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
					printMessage("fluxcli.php", 'Limit met for "month" : '.formatFreeSpace($xfer_total['month']['total']/(1048576))." / ".formatFreeSpace($cfg["xfer_month"]/(1048576))."\n");
					printMessage("fluxcli.php", "Stopping all transfers...\n");
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
					printMessage("fluxcli.php", 'Limit met for "week" : '.formatFreeSpace($xfer_total['week']['total']/(1048576))." / ".formatFreeSpace($cfg["xfer_week"]/(1048576))."\n");
					printMessage("fluxcli.php", "Stopping all transfers...\n");
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
					printMessage("fluxcli.php", 'Limit met for "day" : '.formatFreeSpace($xfer_total['day']['total']/(1048576))." / ".formatFreeSpace($cfg["xfer_day"]/(1048576))."\n");
					printMessage("fluxcli.php", "Stopping all transfers...\n");
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
	    if ($db->ErrorNo() != 0) dbError($sql);
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
		require_once("inc/classes/Rssd.php");
		Rssd::processFeed($sdir, $filter, $history, $url);
	} else {
		cliPrintUsage();
	}
	exit();
}

?>