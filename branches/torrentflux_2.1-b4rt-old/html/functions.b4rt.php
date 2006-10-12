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

/*
 * netstatConnectionsSum
 */
function netstatConnectionsSum() {
    global $cfg;
    include_once("ClientHandler.php");
    // messy...
    $nCount = 0;
    switch (_OS) {
        case 1: // linux
            $clientHandler = ClientHandler::getClientHandlerInstance($cfg,"tornado");
            $nCount += (int) trim(shell_exec($cfg['bin_netstat']." -e -p --tcp -n 2> /dev/null | ".$cfg['bin_grep']." -v root | ".$cfg['bin_grep']." -v 127.0.0.1 | ".$cfg['bin_grep']." -c ". $clientHandler->binSocket));
            unset($clientHandler);
            $clientHandler = ClientHandler::getClientHandlerInstance($cfg,"transmission");
            $nCount += (int) trim(shell_exec($cfg['bin_netstat']." -e -p --tcp -n 2> /dev/null | ".$cfg['bin_grep']." -v root | ".$cfg['bin_grep']." -v 127.0.0.1 | ".$cfg['bin_grep']." -c ". $clientHandler->binSocket));
        break;
        case 2: // bsd
            $processUser = posix_getpwuid(posix_geteuid());
            $webserverUser = $processUser['name'];
            $clientHandler = ClientHandler::getClientHandlerInstance($cfg,"tornado");
            $nCount += (int) trim(shell_exec($cfg['bin_fstat']." -u ".$webserverUser." | ".$cfg['bin_grep']." ". $clientHandler->binSocket . " | ".$cfg['bin_grep']." -c tcp"));
            unset($clientHandler);
            $clientHandler = ClientHandler::getClientHandlerInstance($cfg,"transmission");
            $nCount += (int) trim(shell_exec($cfg['bin_fstat']." -u ".$webserverUser." | ".$cfg['bin_grep']." ". $clientHandler->binSocket . " | ".$cfg['bin_grep']." -c tcp"));
        break;
    }
    return $nCount;
}

/*
 * netstatConnections
 */
function netstatConnections($torrentAlias) {
    return netstatConnectionsByPid(getTorrentPid($torrentAlias));
}

/*
 * netstatConnectionsByPid
 */
function netstatConnectionsByPid($torrentPid) {
    global $cfg;
    switch (_OS) {
        case 1: // linux
            return trim(shell_exec($cfg['bin_netstat']." -e -p --tcp --numeric-hosts --numeric-ports 2> /dev/null | ".$cfg['bin_grep']." -v root | ".$cfg['bin_grep']." -v 127.0.0.1 | ".$cfg['bin_grep']." -c \"".$torrentPid ."/\""));
        break;
        case 2: // bsd
            $processUser = posix_getpwuid(posix_geteuid());
            $webserverUser = $processUser['name'];
            // lord_nor :
            //return trim(shell_exec($cfg['bin_fstat']." -u ".$webserverUser." | ".$cfg['bin_grep']." -c \"".$torrentPid ."\""));
            // khr0n0s :
            $netcon = (int) trim(shell_exec($cfg['bin_fstat']." -u ".$webserverUser." | ".$cfg['bin_grep']." tcp | ".$cfg['bin_grep']." -c \"".$torrentPid ."\""));
            $netcon--;
            return $netcon;
        break;
    }
}

/*
 * netstatPortList
 */
function netstatPortList() {
    global $cfg;
    include_once("ClientHandler.php");
    // messy...
    $retStr = "";
    switch (_OS) {
        case 1: // linux
            $clientHandler = ClientHandler::getClientHandlerInstance($cfg,"tornado");
            $retStr .= shell_exec($cfg['bin_netstat']." -e -l -p --tcp --numeric-hosts --numeric-ports 2> /dev/null | ".$cfg['bin_grep']." -v root | ".$cfg['bin_grep']." ". $clientHandler->binSocket ." | ".$cfg['bin_awk']." '{print \$4}' | ".$cfg['bin_awk']." 'BEGIN{FS=\":\"}{print \$2}'");
            unset($clientHandler);
            $clientHandler = ClientHandler::getClientHandlerInstance($cfg,"transmission");
            $retStr .= shell_exec($cfg['bin_netstat']." -e -l -p --tcp --numeric-hosts --numeric-ports 2> /dev/null | ".$cfg['bin_grep']." -v root | ".$cfg['bin_grep']." ". $clientHandler->binSocket ." | ".$cfg['bin_awk']." '{print \$4}' | ".$cfg['bin_awk']." 'BEGIN{FS=\":\"}{print \$2}'");
        break;
        case 2: // bsd
            $processUser = posix_getpwuid(posix_geteuid());
            $webserverUser = $processUser['name'];
            $clientHandler = ClientHandler::getClientHandlerInstance($cfg,"tornado");
            $retStr .= shell_exec($cfg['bin_sockstat']." | ".$cfg['bin_grep']." ".substr($clientHandler->binSocket, 0, 9)." | ". $cfg['bin_awk']." '/tcp/ {print \$6}' | ".$cfg['bin_awk']." -F \":\" '{print \$2}'");
            unset($clientHandler);
            $clientHandler = ClientHandler::getClientHandlerInstance($cfg,"transmission");
            $retStr .= shell_exec($cfg['bin_sockstat']." | ".$cfg['bin_grep']." ".substr($clientHandler->binSocket, 0, 9)." | ". $cfg['bin_awk']." '/tcp/ {print \$6}' | ".$cfg['bin_awk']." -F \":\" '{print \$2}'");
        break;
    }
    return $retStr;
}

/*
 * netstatPort
 */
function netstatPort($torrentAlias) {
  return netstatPortByPid(getTorrentPid($torrentAlias));
}

/*
 * netstatPortByPid
 */
function netstatPortByPid($torrentPid) {
    global $cfg;
    switch (_OS) {
        case 1: // linux
            return trim(shell_exec($cfg['bin_netstat']." -l -e -p --tcp --numeric-hosts --numeric-ports 2> /dev/null | ".$cfg['bin_grep']." -v root | ".$cfg['bin_grep']." \"".$torrentPid ."/\" | ".$cfg['bin_awk']." '{print \$4}' | ".$cfg['bin_awk']." 'BEGIN{FS=\":\"}{print \$2}'"));
        break;
        case 2: // bsd
            $processUser = posix_getpwuid(posix_geteuid());
            $webserverUser = $processUser['name'];
            return (shell_exec($cfg['bin_sockstat']." | ".$cfg['bin_awk']." '/".$webserverUser.".*".$torrentPid.".*tcp.*\*:\*/ {split(\$6, a, \":\");print a[2]}'"));
        break;
    }
}

/*
 * netstatHostList
 */
function netstatHostList() {
    global $cfg;
    include_once("ClientHandler.php");
    // messy...
    $retStr = "";
    switch (_OS) {
        case 1: // linux
            $clientHandler = ClientHandler::getClientHandlerInstance($cfg,"tornado");
            $retStr .= shell_exec($cfg['bin_netstat']." -e -p --tcp --numeric-hosts --numeric-ports 2> /dev/null | ".$cfg['bin_grep']." -v root | ".$cfg['bin_grep']." -v 127.0.0.1 | ".$cfg['bin_grep']." ". $clientHandler->binSocket ." | ".$cfg['bin_awk']." '{print \$5}'");
            unset($clientHandler);
            $clientHandler = ClientHandler::getClientHandlerInstance($cfg,"transmission");
            $retStr .= shell_exec($cfg['bin_netstat']." -e -p --tcp --numeric-hosts --numeric-ports 2> /dev/null | ".$cfg['bin_grep']." -v root | ".$cfg['bin_grep']." -v 127.0.0.1 | ".$cfg['bin_grep']." ". $clientHandler->binSocket ." | ".$cfg['bin_awk']." '{print \$5}'");
        break;
        case 2: // bsd
            $processUser = posix_getpwuid(posix_geteuid());
            $webserverUser = $processUser['name'];
            $clientHandler = ClientHandler::getClientHandlerInstance($cfg,"tornado");
            $retStr .= shell_exec($cfg['bin_sockstat']." | ".$cfg['bin_grep']." -v '*.*' | ".$cfg['bin_awk']." '/".$webserverUser.".*".substr($clientHandler->binSocket, 0, 9).".*tcp/ {print \$7}'");
            unset($clientHandler);
            $clientHandler = ClientHandler::getClientHandlerInstance($cfg,"transmission");
            $retStr .= shell_exec($cfg['bin_sockstat']." | ".$cfg['bin_grep']." -v '*.*' | ".$cfg['bin_awk']." '/".$webserverUser.".*".substr($clientHandler->binSocket, 0, 9).".*tcp/ {print \$7}'");
        break;
    }
    return $retStr;
}

/*
 * netstatHosts
 */
function netstatHosts($torrentAlias) {
  return netstatHostsByPid(getTorrentPid($torrentAlias));
}

/*
 * netstatHostsByPid
 */
function netstatHostsByPid($torrentPid) {
    global $cfg;
    $hostHash = null;
    switch (_OS) {
        case 1: // linux
            $hostList = shell_exec($cfg['bin_netstat']." -e -p --tcp --numeric-hosts --numeric-ports 2> /dev/null | ".$cfg['bin_grep']." -v root | ".$cfg['bin_grep']." -v 127.0.0.1 | ".$cfg['bin_grep']." \"".$torrentPid."/\" | ".$cfg['bin_awk']." '{print \$5}'");
            $hostAry = explode("\n",$hostList);
            foreach ($hostAry as $line) {
                $hostLineAry = explode(':',trim($line));
                $hostHash[$hostLineAry[0]] = @ $hostLineAry[1];
            }
        break;
        case 2: // bsd
            $processUser = posix_getpwuid(posix_geteuid());
            $webserverUser = $processUser['name'];
            // lord_nor :
            //$hostList = shell_exec($cfg['bin_sockstat']." | ".$cfg['bin_grep']." '*.*' | ".$cfg['bin_awk']." '/".$webserverUser.".*".$torrentPid.".*tcp/ {print \$7}'");
            // khr0n0s :
            $hostList = shell_exec($cfg['bin_sockstat']." | ".$cfg['bin_grep']." 'tcp4' | ".$cfg['bin_awk']." '/".$webserverUser.".*".$torrentPid.".*tcp/ {print \$7}'");
            $hostAry = explode("\n",$hostList);
            foreach ($hostAry as $line) {
                $hostLineAry = explode(':',trim($line));
                if ((trim($hostLineAry[0])) != "*") /* exclude non wanted entry */
                    $hostHash[$hostLineAry[0]] = @ $hostLineAry[1];
            }
        break;
    }
    return $hostHash;
}

/*
 * getTorrentPid
 */
function getTorrentPid($torrentAlias) {
    global $cfg;
    return trim(shell_exec($cfg['bin_cat']." ".$cfg["torrent_file_path"].$torrentAlias.".pid"));
}

/**
 * Returns sum of max numbers of connections of all running torrents.
 *
 * @return int with max cons
 */
function getSumMaxCons() {
  global $db;
  $retVal = $db->GetOne("SELECT SUM(maxcons) AS maxcons FROM tf_torrents WHERE running = '1'");
  if ($retVal > 0)
    return $retVal;
  else
    return 0;
}

/**
 * Returns sum of max upload-speed of all running torrents.
 *
 * @return int with max upload-speed
 */
function getSumMaxUpRate() {
  global $db;
  $retVal = $db->GetOne("SELECT SUM(rate) AS rate FROM tf_torrents WHERE running = '1'");
  if ($retVal > 0)
    return $retVal;
  else
    return 0;
}

/**
 * Returns sum of max download-speed of all running torrents.
 *
 * @return int with max download-speed
 */
function getSumMaxDownRate() {
  global $db;
  $retVal = $db->GetOne("SELECT SUM(drate) AS drate FROM tf_torrents WHERE running = '1'");
  if ($retVal > 0)
    return $retVal;
  else
    return 0;
}

/*
 * Function to delete saved Torrent Settings
 */
function deleteTorrentSettings($torrent) {
    //if ( !isset($torrent) || !preg_match('/^[a-zA-Z0-9._]+$/', $torrent) )
    //    return false;
    global $db;
    $sql = "DELETE FROM tf_torrents WHERE torrent = '".$torrent."'";
    $db->Execute($sql);
	showError($db, $sql);
    return true;
}

/*
 * Function for saving Torrent Settings
 */
function saveTorrentSettings($torrent, $running, $rate, $drate, $maxuploads, $runtime, $sharekill, $minport, $maxport, $maxcons, $savepath, $btclient = 'tornado') {
    // Messy - a not exists would prob work better
    deleteTorrentSettings($torrent);
    global $db;
	$sql = "INSERT INTO tf_torrents ( torrent , running ,rate , drate, maxuploads , runtime , sharekill , minport , maxport, maxcons , savepath , btclient)
            VALUES (
                    '".$torrent."',
                    '".$running."',
                    '".$rate."',
                    '".$drate."',
                    '".$maxuploads."',
                    '".$runtime."',
                    '".$sharekill."',
                    '".$minport."',
                    '".$maxport."',
                    '".$maxcons."',
                    '".$savepath."',
                    '".$btclient."'
                   )";
    $db->Execute($sql);
		showError($db, $sql);
    return true;
}

/*
 * Function to load the settings for a torrent. returns array with settings
 */
function loadTorrentSettings($torrent) {
    global $cfg, $db;
    //if ( !isset($torrent) || !preg_match('/^[a-zA-Z0-9._]+$/', $torrent) )
    //    return;
    $sql = "SELECT * FROM tf_torrents WHERE torrent = '".$torrent."'";
    $result = $db->Execute($sql);
		showError($db, $sql);
    $row = $result->FetchRow();
    if (!empty($row)) {
        $retAry = array();
        $retAry["running"]                 = $row["running"];
        $retAry["max_upload_rate"]         = $row["rate"];
        $retAry["max_download_rate"]       = $row["drate"];
        $retAry["torrent_dies_when_done"]  = $row["runtime"];
        $retAry["max_uploads"]             = $row["maxuploads"];
        $retAry["minport"]                 = $row["minport"];
        $retAry["maxport"]                 = $row["maxport"];
        $retAry["sharekill"]               = $row["sharekill"];
        $retAry["maxcons"]                 = $row["maxcons"];
        $retAry["savepath"]                = $row["savepath"];
        $retAry["btclient"]                = $row["btclient"];
        $retAry["hash"]                    = $row["hash"];
        return $retAry;
    }
    return;
}

/*
 * Function to load the settings for a torrent to global cfg-array
 *
 * @param $torrent name of the torrent
 * @return boolean if the settings could be loaded (were existent in db already)
 */
function loadTorrentSettingsToConfig($torrent) {
    global $cfg, $db, $superseeder;
    //if ( !isset($torrent) || !preg_match('/^[a-zA-Z0-9._]+$/', $torrent) )
    //    return false;
    $sql = "SELECT * FROM tf_torrents WHERE torrent = '".$torrent."'";
    $result = $db->Execute($sql);
		showError($db, $sql);
    $row = $result->FetchRow();
    if (!empty($row)) {
        $cfg["running"]                 = $row["running"];
        $cfg["max_upload_rate"]         = $row["rate"];
        $cfg["max_download_rate"]       = $row["drate"];
        $cfg["torrent_dies_when_done"]  = $row["runtime"];
        $cfg["max_uploads"]             = $row["maxuploads"];
        $cfg["minport"]                 = $row["minport"];
        $cfg["maxport"]                 = $row["maxport"];
        $cfg["sharekill"]               = $row["sharekill"];
        $cfg["maxcons"]                 = $row["maxcons"];
        $cfg["savepath"]                = $row["savepath"];
        $cfg["btclient"]                = $row["btclient"];
        $cfg["hash"]                    = $row["hash"];
        return true;
    } else {
        return false;
    }
}

/**
 * sets the running flag in the db to stopped.
 *
 * @param $torrent name of the torrent
 */
function stopTorrentSettings($torrent) {
  //if ( !isset($torrent) || !preg_match('/^[a-zA-Z0-9._]+$/', $torrent) )
  //  return false;
  global $db;
  $sql = "UPDATE tf_torrents SET running = '0' WHERE torrent = '".$torrent."'";
  $db->Execute($sql);
  return true;
}

/**
 * gets the running flag of the torrent out of the the db.
 *
 * @param $torrent name of the torrent
 * @return value of running-flag in db
 */
function isTorrentRunning($torrent) {
	//if ( !isset($torrent) || !preg_match('/^[a-zA-Z0-9._]+$/', $torrent) )
	//	return 0;
	// b4rt-8: make this pid-file-parsed.. maybe we got some "zombies" (torrents that stopped themselves)
	/*
	global $db;
	$retVal = $db->GetOne("SELECT running FROM tf_torrents WHERE torrent = '".$torrent."'");
	if ($retVal > 0)
		return $retVal;
	else
		return 0;
	*/
    global $cfg;
    if (file_exists($cfg["torrent_file_path"].substr($torrent,0,-8).'.stat.pid'))
        return 1;
    else
        return 0;
}

/**
 * gets the btclient of the torrent out of the the db.
 *
 * @param $torrent name of the torrent
 * @return btclient
 */
function getTorrentClient($torrent) {
  //if ( !isset($torrent) || !preg_match('/^[a-zA-Z0-9._]+$/', $torrent) )
  //  return 0;
  global $db;
  return $db->GetOne("SELECT btclient FROM tf_torrents WHERE torrent = '".$torrent."'");
}

/**
 * gets hash of a torrent
 *
 * @param $torrent name of the torrent
 * @return var with torrent-hash
 */
function getTorrentHash($torrent) {
    //info = metainfo['info']
    //info_hash = sha(bencode(info))
    //print 'metainfo file.: %s' % basename(metainfo_name)
    //print 'info hash.....: %s' % info_hash.hexdigest()
    global $cfg, $db;
    // check if we got a cached value in the db
    $tHash = $db->GetOne("SELECT hash FROM tf_torrents WHERE torrent = '".$torrent."'");
    if (isset($tHash) && $tHash != "") { // hash already in db
        return $tHash;
    } else { // hash is not in db
        // get hash via metainfoclient-call
        $result = getTorrentMetaInfo($torrent);
        if (! isset($result))
            return "";
        $resultAry = explode("\n",$result);
        $hashAry = array();
        switch ($cfg["metainfoclient"]) {
            case "transmissioncli":
                //$hashAry = explode(":",trim($resultAry[2]));
                // transmissioncli Revision 1.4 or higher does not print out
                // version-string on meta-info.
                $hashAry = explode(":",trim($resultAry[0]));
            break;
            case "btshowmetainfo.py":
            default:
                $hashAry = explode(":",trim($resultAry[3]));
            break;
        }
        $tHash = @trim($hashAry[1]);
        // insert hash into db
        if (isset($tHash) && $tHash != "") {
            $db->Execute("UPDATE tf_torrents SET hash = '".$tHash."' WHERE torrent = '".$torrent."'");
            // return hash
            return $tHash;
        } else {
            return "";
        }
    }
}

// TOTALS =======================================================================================================================

/**
 * updates totals of a torrent
 *
 * @param $torrent name of the torrent
 * @param $uptotal uptotal of the torrent
 * @param $downtotal downtotal of the torrent
 */
function updateTorrentTotals($torrent) {
    global $cfg, $db;
    //if ( !isset($torrent) || !preg_match('/^[a-zA-Z0-9._]+$/', $torrent) )
    //    return;
    /*
    $torrentId = getTorrentHash($torrent);
    $sql = "SELECT uptotal,downtotal FROM tf_torrent_totals WHERE tid = '".$torrentId."'";
    $result = $db->Execute($sql);
		showError($db, $sql);
    $row = $result->FetchRow();
    if (!empty($row)) {
        $currentUp           = $row["uptotal"];
        $currentDown         = $row["downtotal"];
        $upSum = $currentUp + $uptotal;
        $downSum = $currentDown + $downtotal;
        $sql = "UPDATE tf_torrent_totals SET uptotal = '".($upSum+0)."', downtotal = '".($downSum+0)."' WHERE tid = '".$torrentId."'";
        $db->Execute($sql);
    } else {
        $sql = "INSERT INTO tf_torrent_totals ( tid , uptotal ,downtotal )
		          VALUES (
                    '".$torrentId."',
                    '".$uptotal."',
                    '".$downtotal."'
                   )";
        $db->Execute($sql);
    }
	showError($db, $sql);
	*/
    $torrentId = getTorrentHash($torrent);
    $torrentTotals = getTorrentTotals($torrent);
    // very ugly exists check... too lazy now
    $sql = "SELECT uptotal,downtotal FROM tf_torrent_totals WHERE tid = '".$torrentId."'";
    $result = $db->Execute($sql);
		showError($db, $sql);
    $row = $result->FetchRow();
    if (!empty($row)) {
        $sql = "UPDATE tf_torrent_totals SET uptotal = '".($torrentTotals["uptotal"]+0)."', downtotal = '".($torrentTotals["downtotal"]+0)."' WHERE tid = '".$torrentId."'";
        $db->Execute($sql);
    } else {
        $sql = "INSERT INTO tf_torrent_totals ( tid , uptotal ,downtotal )
		          VALUES (
                    '".$torrentId."',
                    '".($torrentTotals["uptotal"]+0)."',
                    '".($torrentTotals["downtotal"]+0)."'
                   )";
        $db->Execute($sql);
    }
	showError($db, $sql);
}

/**
 * gets totals of a torrent
 *
 * @param $torrent name of the torrent
 * @return array with torrent-totals
 */
function getTorrentTotals($torrent) {
    global $cfg, $db;
    //if ( !isset($torrent) || !preg_match('/^[a-zA-Z0-9._]+$/', $torrent) )
    //    return;
    /*
    $torrentId = getTorrentHash($torrent);
    $sql = "SELECT uptotal,downtotal FROM tf_torrent_totals WHERE tid = '".$torrentId."'";
    $result = $db->Execute($sql);
		showError($db, $sql);
    $row = $result->FetchRow();
    $retVal = array();
    if (!empty($row)) {
        $retVal["uptotal"] = $row["uptotal"];
        $retVal["downtotal"] = $row["downtotal"];
    } else {
        $retVal["uptotal"] = 0;
        $retVal["downtotal"] = 0;
    }
    return $retVal;
    */
    $btclient = getTorrentClient($torrent);
    include_once("ClientHandler.php");
    $clientHandler = ClientHandler::getClientHandlerInstance($cfg, $btclient);
    return $clientHandler->getTorrentTransferTotal($torrent);
}

/**
 * gets totals of a torrent
 *
 * @param $torrent name of the torrent
 * @param $btclient client of the torrent
 * @param $afu alias-file-uptotal of the torrent
 * @param $afd alias-file-downtotal of the torrent
 * @return array with torrent-totals
 */
function getTorrentTotalsOP($torrent,$btclient,$afu,$afd) {
    global $cfg;
    include_once("ClientHandler.php");
    $clientHandler = ClientHandler::getClientHandlerInstance($cfg, $btclient);
    return $clientHandler->getTorrentTransferTotalOP($torrent,$afu,$afd);
}

/**
 * gets current totals of a torrent
 *
 * @param $torrent name of the torrent
 * @return array with torrent-totals
 */
function getTorrentTotalsCurrent($torrent) {
    global $cfg, $db;
    $btclient = getTorrentClient($torrent);
    include_once("ClientHandler.php");
    $clientHandler = ClientHandler::getClientHandlerInstance($cfg, $btclient);
    return $clientHandler->getTorrentTransferCurrent($torrent);
}

/**
 * gets current totals of a torrent
 *
 * @param $torrent name of the torrent
 * @param $btclient client of the torrent
 * @param $afu alias-file-uptotal of the torrent
 * @param $afd alias-file-downtotal of the torrent
 * @return array with torrent-totals
 */
function getTorrentTotalsCurrentOP($torrent,$btclient,$afu,$afd) {
    global $cfg;
    include_once("ClientHandler.php");
    $clientHandler = ClientHandler::getClientHandlerInstance($cfg, $btclient);
    return $clientHandler->getTorrentTransferCurrentOP($torrent,$afu,$afd);
}

// TOTALS =======================================================================================================================

/**
 * resets totals of a torrent
 *
 * @param $torrent name of the torrent
 * @param $delete boolean if to delete torrent-file
 * @return boolean of success
 */
function resetTorrentTotals($torrent, $delete = false) {
    global $cfg, $db;
    if ( !isset($torrent) || !preg_match('/^[a-zA-Z0-9._]+$/', $torrent) )
        return false;
    // vars
    $torrentId = getTorrentHash($torrent);
    $alias = getAliasName($torrent);
    $owner = getOwner($torrent);
    // delete torrent
    if ($delete == true) {
        deleteTorrent($torrent, $alias);
        // delete the stat file. shouldnt be there.. but...
        @unlink($cfg["torrent_file_path"].$alias.".stat");
    } else {
        // reset in stat-file
        include_once("AliasFile.php");
        $af = AliasFile::getAliasFileInstance($cfg["torrent_file_path"].$alias.".stat", $owner, $cfg);
        if (isset($af)) {
            $af->uptotal = 0;
            $af->downtotal = 0;
            $af->WriteFile();
        }
    }
    // reset in db
    $sql = "DELETE FROM tf_torrent_totals WHERE tid = '".$torrentId."'";
    $db->Execute($sql);
		showError($db, $sql);
    return true;
}

/**
 * deletes a torrent
 *
 * @param $torrent name of the torrent
 * @param $alias_file alias-file of the torrent
 * @return boolean of success
 */
function deleteTorrent($torrent,$alias_file) {
    $delfile = $torrent;
    global $cfg;
    //$alias_file = getRequestVar('alias_file');
    $torrentowner = getOwner($delfile);
    if (($cfg["user"] == $torrentowner) || IsAdmin()) {
        include_once("AliasFile.php");
        // we have more meta-files than .torrent. handle this.
        //$af = AliasFile::getAliasFileInstance($cfg['torrent_file_path'].$alias_file, 0, $cfg);
        if ((substr( strtolower($torrent),-8 ) == ".torrent")) {
            // this is a torrent-client
            $btclient = getTorrentClient($delfile);
            $af = AliasFile::getAliasFileInstance($cfg['torrent_file_path'].$alias_file, $torrentowner, $cfg, $btclient);
// TOTALS =======================================================================================================================
            // update totals for this torrent
            //updateTorrentTotals($delfile, $af->uptotal+0, $af->downtotal+0);
            updateTorrentTotals($delfile);
// TOTALS =======================================================================================================================
            // remove torrent-settings from db
            deleteTorrentSettings($delfile);
			// client-proprietary leftovers
			include_once("ClientHandler.php");
			$clientHandler = ClientHandler::getClientHandlerInstance($cfg,$btclient);
			$clientHandler->deleteTorrentCache($torrent);
        } else if ((substr( strtolower($torrent),-4 ) == ".url")) {
            // this is wget. use tornado statfile
            $alias_file = str_replace(".url", "", $alias_file);
            $af = AliasFile::getAliasFileInstance($cfg['torrent_file_path'].$alias_file, $cfg['user'], $cfg, 'tornado');
        } else {
            // this is "something else". use tornado statfile as default
            $af = AliasFile::getAliasFileInstance($cfg['torrent_file_path'].$alias_file, $cfg['user'], $cfg, 'tornado');
        }

// TOTALS =======================================================================================================================
        //XFER: before torrent deletion save upload/download xfer data to SQL
        //if ($af->downtotal || $af->uptotal)
        //    saveXfer($af->torrentowner,$af->downtotal,$af->uptotal);
		$torrentTotals = getTorrentTotalsCurrent($delfile);
		saveXfer($torrentowner,($torrentTotals["downtotal"]+0),($torrentTotals["uptotal"]+0));
// TOTALS =======================================================================================================================

        // torrent+stat
        @unlink($cfg["torrent_file_path"].$delfile);
        @unlink($cfg["torrent_file_path"].$alias_file);
        // try to remove the QInfo if in case it was queued.
        @unlink($cfg["torrent_file_path"]."queue/".$alias_file.".Qinfo");
        // try to remove the pid file
        @unlink($cfg["torrent_file_path"].$alias_file.".pid");
        @unlink($cfg["torrent_file_path"].getAliasName($delfile).".prio");
        AuditAction($cfg["constants"]["delete_torrent"], $delfile);
        return true;
    } else {
        AuditAction($cfg["constants"]["error"], $cfg["user"]." attempted to delete ".$delfile);
        return false;
    }
}

/**
 * deletes data of a torrent
 *
 * @param $torrent name of the torrent
 */
function deleteTorrentData($torrent) {
    $element = $torrent;
    global $cfg;
    if (($cfg["user"] == getOwner($element)) || IsAdmin()) {
        # the user is the owner of the torrent -> delete it
        require_once('BDecode.php');
        $ftorrent=$cfg["torrent_file_path"].$element;
        $fd = fopen($ftorrent, "rd");
        $alltorrent = fread($fd, filesize($ftorrent));
        $btmeta = BDecode($alltorrent);
        $delete = $btmeta['info']['name'];
        if(trim($delete) != "") {
            // load torrent-settings from db to get data-location
            loadTorrentSettingsToConfig(urldecode($torrent));
            if ((! isset($cfg["savepath"])) || (empty($cfg["savepath"])))
                $cfg["savepath"] = $cfg["path"].getOwner($torrent).'/';
            $delete = $cfg["savepath"].$delete;
            # this is from dir.php - its not a function, and we need to call it several times
            $del = stripslashes(stripslashes($delete));
            if (!ereg("(\.\.\/)", $del)) {
                 avddelete($del);
                 $arTemp = explode("/", $del);
                 if (count($arTemp) > 1) {
                     array_pop($arTemp);
                     $current = implode("/", $arTemp);
                 }
                 AuditAction($cfg["constants"]["fm_delete"], $del);
            } else {
                 AuditAction($cfg["constants"]["error"], "ILLEGAL DELETE: ".$cfg['user']." tried to delete ".$del);
            }
        }
    } else {
        AuditAction($cfg["constants"]["error"], $cfg["user"]." attempted to delete ".$element);
    }
}

/**
 * gets size of data of a torrent
 *
 * @param $torrent name of the torrent
 * @return int with size of data of torrent.
 *         -1 if error
 *         4096 if dir (lol ~)
 *         string with file/dir-name if doesnt exist. (lol~)
 */
function getTorrentDataSize($torrent) {
    global $cfg;
    require_once('BDecode.php');
    $ftorrent=$cfg["torrent_file_path"].$torrent;
    $fd = fopen($ftorrent, "rd");
    $alltorrent = fread($fd, filesize($ftorrent));
    $btmeta = BDecode($alltorrent);
    $name = $btmeta['info']['name'];
    if(trim($name) != "") {
        // load torrent-settings from db to get data-location
        loadTorrentSettingsToConfig($torrent);
        if ((! isset($cfg["savepath"])) || (empty($cfg["savepath"])))
            $cfg["savepath"] = $cfg["path"].getOwner($torrent).'/';
        $name = $cfg["savepath"].$name;
        # this is from dir.php - its not a function, and we need to call it several times
        $tData = stripslashes(stripslashes($name));
        if (!ereg("(\.\.\/)", $tData)) {
            $fileSize = file_size($tData);
            return $fileSize;
        }
    }
    return -1;
}

/**
 * deletes a dir-entry. recursive process via avddelete
 *
 * @param $del entry to delete
 * @return string with current
 */
function delDirEntry($del) {
	global $cfg;
    $current = "";
    // The following lines of code were suggested by Jody Steele jmlsteele@stfu.ca
    // this is so only the owner of the file(s) or admin can delete
    if(IsAdmin($cfg["user"]) || preg_match("/^" . $cfg["user"] . "/",$del)) {
        // Yes, then delete it
        // we need to strip slashes twice in some circumstances
        // Ex.  If we are trying to delete test/tester's file/test.txt
        //    $del will be "test/tester\\\'s file/test.txt"
        //    one strip will give us "test/tester\'s file/test.txt
        //    the second strip will give us the correct
        //        "test/tester's file/test.txt"
        $del = stripslashes(stripslashes($del));
        if (!ereg("(\.\.\/)", $del)) {
            avddelete($cfg["path"].$del);
            $arTemp = explode("/", $del);
            if (count($arTemp) > 1) {
                array_pop($arTemp);
                $current = implode("/", $arTemp);
            }
            AuditAction($cfg["constants"]["fm_delete"], $del);
        } else {
            AuditAction($cfg["constants"]["error"], "ILLEGAL DELETE: ".$cfg['user']." tried to delete ".$del);
        }
    } else {
        AuditAction($cfg["constants"]["error"], "ILLEGAL DELETE: ".$cfg['user']." tried to delete ".$del);
    }
    return $current;
}

//******************************************************************************
function RunningProcessInfo() {
    global $cfg;
    include_once("ClientHandler.php");
    // messy...
    echo " ---=== tornado ===---\n\n";
    $clientHandler = ClientHandler::getClientHandlerInstance($cfg,"tornado");
    $clientHandler->printRunningClientsInfo();
    $pinfo = shell_exec("ps auxww | ".$cfg['bin_grep']." ". $clientHandler->binClient ." | ".$cfg['bin_grep']." -v grep | ".$cfg['bin_grep']." -v ".$cfg["tfQManager"]);
    echo "\n\n --- Process-List --- \n\n".$pinfo;
    unset($clientHandler);
    unset($pinfo);
    echo "\n\n ---=== transmission ===---\n\n";
    $clientHandler = ClientHandler::getClientHandlerInstance($cfg,"transmission");
    $clientHandler->printRunningClientsInfo();
    $pinfo = shell_exec("ps auxww | ".$cfg['bin_grep']." ". $clientHandler->binSystem ." | ".$cfg['bin_grep']." -v grep");
    echo "\n\n --- Process-List --- \n\n".$pinfo;
}

//******************************************************************************
function getRunningTorrentCount() {
	global $cfg;
	/*
	include_once("ClientHandler.php");
	// messy...
	$tCount = 0;
	$clientHandler = ClientHandler::getClientHandlerInstance($cfg,"tornado");
	$tCount += $clientHandler->getRunningClientCount();
	unset($clientHandler);
	$clientHandler = ClientHandler::getClientHandlerInstance($cfg,"transmission");
	$tCount += $clientHandler->getRunningClientCount();
	return $tCount;
	*/
	// use pid-files-direct-access for now because all clients of currently
	// available handlers write one. then its faster and correct meanwhile.
	if ($dirHandle = opendir($cfg["torrent_file_path"])) {
		$tCount = 0;
		while (false !== ($file = readdir($dirHandle))) {
			//if ((substr($file, -1, 1)) == "d")
			if ((substr($file, -4, 4)) == ".pid")
				$tCount++;
		}
		closedir($dirHandle);
		return $tCount;
	} else {
		return 0;
	}
}

//******************************************************************************
function getRunningTorrents($clientType = '') {
    global $cfg;
    include_once("ClientHandler.php");
    // get only torrents of a particular client
    if ((isset($clientType)) && ($clientType != '')) {
        $clientHandler = ClientHandler::getClientHandlerInstance($cfg,$clientType);
        return $clientHandler->getRunningClients();
    }
    // get torrents of all clients
    // messy...
    $retAry = array();
    $clientHandler = ClientHandler::getClientHandlerInstance($cfg,"tornado");
    $tempAry = $clientHandler->getRunningClients();
    foreach ($tempAry as $val)
        array_push($retAry,$val);
    unset($clientHandler);
    unset($tempAry);
    $clientHandler = ClientHandler::getClientHandlerInstance($cfg,"transmission");
    $tempAry = $clientHandler->getRunningClients();
    foreach ($tempAry as $val)
        array_push($retAry,$val);
    return $retAry;
}

/**
 * prints btclient-select-form-snip. messy but too lazy until now to make
 * clienthandler-integration easier by reading in all available handlers from
 * filesystem
 *
 */
function printBTClientSelect($btclient = 'tornado') {
    global $cfg;
    echo '<select name="btclient">';
    echo '<option value="tornado"';
    if ($btclient == "tornado")
        echo " selected";
    echo '>tornado</option>';
    echo '<option value="transmission"';
    if ($btclient == "transmission")
        echo " selected";
    echo '>transmission</option>';
    echo '</select>';
}

/**
 * prints superadmin-popup-link-html-snip.
 *
 */
function printSuperAdminLink($param = "", $linkText = "") {
	global $cfg;
	?>
	<script language="JavaScript">
	function SuperAdmin(name_file) {
			window.open (name_file,'_blank','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=<?php echo $cfg["ui_dim_superadmin_w"] ?>,height=<?php echo $cfg["ui_dim_superadmin_h"] ?>')
	}
	</script>
	<?php
	echo "<a href=\"JavaScript:SuperAdmin('superadmin.php".$param."')\">";
	if ((isset($linkText)) && ($linkText != ""))
		echo $linkText;
	else
		echo '<img src="images/arrow.gif" width="9" height="9" title="Version" border="0">';
	echo '</a>';
}

/**
 * gets metainfo of a torrent as string
 *
 * @param $torrent name of the torrent
 * @return string with torrent-meta-info
 */
function getTorrentMetaInfo($torrent) {
    global $cfg;
	switch ($cfg["metainfoclient"]) {
		case "transmissioncli":
			return shell_exec($cfg["btclient_transmission_bin"] . " -i \"".$cfg["torrent_file_path"].$torrent."\"");
		break;
		case "btshowmetainfo.py":
		default:
			return shell_exec("cd " . $cfg["torrent_file_path"]."; " . $cfg["pythonCmd"] . " -OO " . $cfg["btshowmetainfo"]." \"".$torrent."\"");
	}
}

/**
 * gets scrape-info of a torrent as string
 *
 * @param $torrent name of the torrent
 * @return string with torrent-scrape-info
 */
function getTorrentScrapeInfo($torrent) {
    global $cfg;
	switch ($cfg["metainfoclient"]) {
		case "transmissioncli":
			return shell_exec($cfg["btclient_transmission_bin"] . " -s \"".$cfg["torrent_file_path"].$torrent."\"");
		break;
		case "btshowmetainfo.py":
		default:
			return "error. torrent-scrape needs transmissioncli.";
	}
}

/**
 * gets torrent-list from file-system. (never-started are included here)
 * @return array with torrents
 */
function getTorrentListFromFS() {
    global $cfg;
	$retVal = array();
	if ($dirHandle = opendir($cfg["torrent_file_path"])) {
		while (false !== ($file = readdir($dirHandle))) {
			if ((substr($file, -2)) == "nt")
				array_push($retVal, $file);
		}
		closedir($dirHandle);
	}
	return $retVal;
}

/**
 * gets torrent-list from database.
 * @return array with torrents
 */
function getTorrentListFromDB() {
	global $db;
	$retVal = array();
	$sql = "SELECT torrent FROM tf_torrents ORDER BY torrent ASC";
	$recordset = $db->Execute($sql);
	showError($db, $sql);
	while(list($torrent) = $recordset->FetchRow())
		array_push($retVal, $torrent);
    return $retVal;
}

/*
 * Function for saving user Settings
 *
 * @param $uid uid of the user
 * @param $settings settings-array
 */
function saveUserSettings($uid, $settings) {
	if (! isset($uid))
		return false;
    // Messy - a not exists would prob work better. but would have to be done
    // on every key/value pair so lots of extra-statements.
    deleteUserSettings($uid);
	// insert new settings
    foreach ($settings as $key => $value)
        insertUserSettingPair($uid,$key,$value);
    return true;
}

/*
 * insert setting-key/val pair for user into db
 *
 * @param $uid uid of the user
 * @param $key
 * @param $value
 * @return boolean
 */
function insertUserSettingPair($uid,$key,$value) {
	if (! isset($uid))
		return false;
    global $cfg, $db;
    $update_value = $value;
    if (is_array($value)) {
        $update_value = serialize($value);
    } else {
		// only insert if setting different from global settings or has changed
		if ($cfg[$key] == $value)
			return true;
	}
    $sql = "INSERT INTO tf_settings_user VALUES ('".$uid."', '".$key."', '".$update_value."')";
    if ( $sql != "" ) {
        $result = $db->Execute($sql);
        showError($db,$sql);
        // update the Config.
        $cfg[$key] = $value;
    }
	return true;
}

/*
 * Function to delete saved user Settings
 *
 * @param $uid uid of the user
 */
function deleteUserSettings($uid) {
    if ( !isset($uid))
        return false;
    global $db;
    $sql = "DELETE FROM tf_settings_user WHERE uid = '".$uid."'";
    $db->Execute($sql);
		showError($db, $sql);
    return true;
}

/*
 * Function to load the settings for a user to global cfg-array
 *
 * @param $uid uid of the user
 * @return boolean
 */
function loadUserSettingsToConfig($uid) {
    if ( !isset($uid))
        return false;
    global $cfg, $db;
    // get user-settings from db and set in global cfg-array
    $sql = "SELECT tf_key, tf_value FROM tf_settings_user WHERE uid = '".$uid."'";
    $recordset = $db->Execute($sql);
    showError($db, $sql);
    if ((isset($recordset)) && ($recordset->NumRows() > 0)) {
    	while(list($key, $value) = $recordset->FetchRow())
    		$cfg[$key] = $value;
    }
    return true;
}

/*
 * Function to convert bit-array to (unsigned) byte
 *
 * @param bit-array
 * @return byte
 */
function convertArrayToByte($dataArray) {
   if (count($dataArray) > 8) return false;
   foreach ($dataArray as $key => $value) {
       if ($value) $dataArray[$key] = 1;
       if (!$value) $dataArray[$key] = 0;
   }
   $binString = strrev(implode('', $dataArray));
   $bitByte = bindec($binString);
   return $bitByte;
}

/*
 * Function to convert (unsigned) byte to bit-array
 *
 * @param byte
 * @return bit-array
 */
function convertByteToArray($dataByte) {
   if (($dataByte > 255) || ($dataByte < 0)) return false;
   $binString = strrev(str_pad(decbin($dataByte),8,"0",STR_PAD_LEFT));
   $bitArray = explode(":",chunk_split($binString, 1, ":"));
   return $bitArray;
}

/*
 * Function to convert bit-array to (unsigned) integer
 *
 * @param bit-array
 * @return integer
 */
function convertArrayToInteger($dataArray) {
   if (count($dataArray) > 31) return false;
   foreach ($dataArray as $key => $value) {
       if ($value) $dataArray[$key] = 1;
       if (!$value) $dataArray[$key] = 0;
   }
   $binString = strrev(implode('', $dataArray));
   $bitInteger = bindec($binString);
   return $bitInteger;
}

/*
 * Function to convert (unsigned) integer to bit-array
 *
 * @param integer
 * @return bit-array
 */
function convertIntegerToArray($dataInt) {
   if (($dataInt > 2147483647) || ($dataInt < 0)) return false;
   $binString = strrev(str_pad(decbin($dataInt),31,"0",STR_PAD_LEFT));
   $bitArray = explode(":",chunk_split($binString, 1, ":"));
   return $bitArray;
}

/*
 * Function with which torrents are started in index-page
 *
 * @param $torrent torrent-name
 * @param $interactive (1|0) : is this a interactive startup with dialog ?
 */
function indexStartTorrent($torrent,$interactive) {
	global $cfg;
    if ($cfg["enable_file_priority"]) {
        include_once("setpriority.php");
        // Process setPriority Request.
        setPriority($torrent);
    }
    switch ($interactive) {
        case 0:
            include_once("ClientHandler.php");
            $btclient = getTorrentClient($torrent);
            $clientHandler = ClientHandler::getClientHandlerInstance($cfg,$btclient);
            $clientHandler->startTorrentClient($torrent, 0);
            // just 2 sec..
            sleep(2);
            // header + out
            header("location: index.php");
            exit();
        break;
        case 1:
            $spo = getRequestVar('setPriorityOnly');
            if (!empty($spo)){
            	// This is a setPriorityOnly Request.
            } else {
                include_once("ClientHandler.php");
                $clientHandler = ClientHandler::getClientHandlerInstance($cfg, getRequestVar('btclient'));
                $clientHandler->startTorrentClient($torrent, 1);
                if ($clientHandler->status == 3) { // hooray
                    // wait another sec
                    sleep(1);
                    if (array_key_exists("closeme",$_POST)) {
                        echo '<script  language="JavaScript">';
                        echo ' window.opener.location.reload(true);';
                        echo ' window.close();';
                        echo '</script>';
                    } else {
                        header("location: index.php");
                    }
                } else { // start failed
                    echo $clientHandler->messages;
                }
                exit();
            }
        break;
    }
}

/*
 * Function with which torrents are downloaded and injected on index-page
 *
 * @param $url_upload url of torrent to download
 */
function indexProcessDownload($url_upload) {
	global $cfg, $messages;
    $arURL = explode("/", $url_upload);
    $file_name = urldecode($arURL[count($arURL)-1]); // get the file name
    $file_name = str_replace(array("'",","), "", $file_name);
    $file_name = stripslashes($file_name);
    $ext_msg = "";
    // Check to see if url has something like ?passkey=12345
    // If so remove it.
    if( ( $point = strrpos( $file_name, "?" ) ) !== false )
        $file_name = substr( $file_name, 0, $point );
    $ret = strrpos($file_name,".");
    if ($ret === false) {
        $file_name .= ".torrent";
    } else {
        if(!strcmp(strtolower(substr($file_name, strlen($file_name)-8, 8)), ".torrent") == 0)
            $file_name .= ".torrent";
    }
    $url_upload = str_replace(" ", "%20", $url_upload);
    // This is to support Sites that pass an id along with the url for torrent downloads.
    $tmpId = getRequestVar("id");
    if(!empty($tmpId))
        $url_upload .= "&id=".$tmpId;
    // Call fetchtorrent to retrieve the torrent file
    $output = FetchTorrent( $url_upload );
    if (array_key_exists("save_torrent_name",$cfg)) {
        if ($cfg["save_torrent_name"] != "")
            $file_name = $cfg["save_torrent_name"];
    }
    $file_name = cleanFileName($file_name);
    // if the output had data then write it to a file
    if ((strlen($output) > 0) && (strpos($output, "<br />") === false)) {
        if (is_file($cfg["torrent_file_path"].$file_name)) {
            // Error
            $messages .= "<b>Error</b> with (<b>".$file_name."</b>), the file already exists on the server.<br><center><a href=\"".$_SERVER['PHP_SELF']."\">[Refresh]</a></center>";
            $ext_msg = "DUPLICATE :: ";
        } else {
            // open a file to write to
            $fw = fopen($cfg["torrent_file_path"].$file_name,'w');
            fwrite($fw, $output);
            fclose($fw);
        }
    } else {
        $messages .= "<b>Error</b> Getting the File (<b>".$file_name."</b>), Could be a Dead URL.<br><center><a href=\"".$_SERVER['PHP_SELF']."\">[Refresh]</a></center>";
    }
    if($messages != "") { // there was an error
        AuditAction($cfg["constants"]["error"], $cfg["constants"]["url_upload"]." :: ".$ext_msg.$file_name);
    } else {
        AuditAction($cfg["constants"]["url_upload"], $file_name);
        // init stat-file
        injectTorrent($file_name);
        // instant action ?
        $actionId = getRequestVar('aid');
        if (isset($actionId)) {
            switch ($actionId) {
                case 3:
                   $_REQUEST['queue'] = 'on';
                case 2:
                   if ($cfg["enable_file_priority"]) {
                       include_once("setpriority.php");
                       // Process setPriority Request.
                       setPriority(urldecode($file_name));
                   }
                   include_once("ClientHandler.php");
                   $clientHandler = ClientHandler::getClientHandlerInstance($cfg);
                   $clientHandler->startTorrentClient($file_name, 0);
                   // just a sec..
                   sleep(1);
                   break;
            }
        }
        header("location: index.php");
        exit();
    }
}

/*
 * Function with which torrents are uploaded and injected on index-page
 *
 */
function indexProcessUpload() {
	global $cfg, $messages;
    $file_name = stripslashes($_FILES['upload_file']['name']);
    $file_name = str_replace(array("'",","), "", $file_name);
    $file_name = cleanFileName($file_name);
    $ext_msg = "";
    if($_FILES['upload_file']['size'] <= 1000000 && $_FILES['upload_file']['size'] > 0) {
        if (ereg(getFileFilter($cfg["file_types_array"]), $file_name)) {
            //FILE IS BEING UPLOADED
            if (is_file($cfg["torrent_file_path"].$file_name)) {
                // Error
                $messages .= "<b>Error</b> with (<b>".$file_name."</b>), the file already exists on the server.<br><center><a href=\"".$_SERVER['PHP_SELF']."\">[Refresh]</a></center>";
                $ext_msg = "DUPLICATE :: ";
            } else {
                if(move_uploaded_file($_FILES['upload_file']['tmp_name'], $cfg["torrent_file_path"].$file_name)) {
                    chmod($cfg["torrent_file_path"].$file_name, 0644);
                    AuditAction($cfg["constants"]["file_upload"], $file_name);
                    // init stat-file
                    injectTorrent($file_name);
                    // instant action ?
                    $actionId = getRequestVar('aid');
                    if (isset($actionId)) {
                        switch ($actionId) {
                            case 3:
                               $_REQUEST['queue'] = 'on';
                            case 2:
                               if ($cfg["enable_file_priority"]) {
                                   include_once("setpriority.php");
                                   // Process setPriority Request.
                                   setPriority(urldecode($file_name));
                               }
                               include_once("ClientHandler.php");
                               $clientHandler = ClientHandler::getClientHandlerInstance($cfg);
                               $clientHandler->startTorrentClient($file_name, 0);
                               // just a sec..
                               sleep(1);
                               break;
                        }
                    }
                } else {
                    $messages .= "<font color=\"#ff0000\" size=3>ERROR: File not uploaded, file could not be found or could not be moved:<br>".$cfg["torrent_file_path"] . $file_name."</font><br>";
                }
            }
        } else {
            $messages .= "<font color=\"#ff0000\" size=3>ERROR: The type of file you are uploading is not allowed.</font><br>";
        }
    } else {
        $messages .= "<font color=\"#ff0000\" size=3>ERROR: File not uploaded, check file size limit.</font><br>";
    }
    if($messages != "") { // there was an error
        AuditAction($cfg["constants"]["error"], $cfg["constants"]["file_upload"]." :: ".$ext_msg.$file_name);
    } else {
        header("location: index.php");
        exit();
    }
}

/*
 * This method gets transfers in an array
 *
 * @param $sortOrder
 * @return array with transfers
 */
function getTransferArray($sortOrder = '') {
    global $cfg;
    $arList = array();
    $file_filter = getFileFilter($cfg["file_types_array"]);
    if (is_dir($cfg["torrent_file_path"]))
        $handle = opendir($cfg["torrent_file_path"]);
    else
        return null;
    while($entry = readdir($handle)) {
        if ($entry != "." && $entry != "..") {
            if (is_dir($cfg["torrent_file_path"]."/".$entry)) {
                // don''t do a thing
            } else {
                if (ereg($file_filter, $entry)) {
                    $key = filemtime($cfg["torrent_file_path"]."/".$entry).md5($entry);
                    $arList[$key] = $entry;
                }
            }
        }
    }
    closedir($handle);
    // sort transfer-array
    $sortId = "";
    if ((isset($sortOrder)) && ($sortOrder != ""))
        $sortId = $sortOrder;
    else
        $sortId = $cfg["index_page_sortorder"];
    switch ($sortId) {
        case 'da': // sort by date ascending
            ksort($arList);
            break;
        case 'dd': // sort by date descending
            krsort($arList);
            break;
        case 'na': // sort alphabetically by name ascending
            natcasesort($arList);
            break;
        case 'nd': // sort alphabetically by name descending
            natcasesort($arList);
   			$arList = array_reverse($arList, true);
            break;
    }
    return $arList;
}

/*
 * This method Builds the Transfers Section of the Index Page
 *
 * @return transfer-list as string
 */
function getTransferList() {
    global $cfg, $db;
    include_once("AliasFile.php");
    $kill_id = "";
    $lastUser = "";
    $arUserTorrent = array();
    $arListTorrent = array();
    // settings
    $settings = convertIntegerToArray($cfg["index_page_settings"]);
    // sortOrder
    $sortOrder = getRequestVar("so");
    if ($sortOrder == "")
        $sortOrder = $cfg["index_page_sortorder"];
    // t-list
    $arList = getTransferArray($sortOrder);
    foreach($arList as $entry) {

        // ---------------------------------------------------------------------
        // init some vars
        $displayname = $entry;
        $show_run = true;
        $torrentowner = getOwner($entry);
        $owner = IsOwner($cfg["user"], $torrentowner);
        if(strlen($entry) >= 47) {
            // needs to be trimmed
            $displayname = substr($entry, 0, 44);
            $displayname .= "...";
        }
        if ($cfg["enable_torrent_download"])
            $torrentfilelink = "<a href=\"maketorrent.php?download=".urlencode($entry)."\"><img src=\"images/down.gif\" width=9 height=9 title=\"Download Torrent File\" border=0 align=\"absmiddle\"></a>";
        else
            $torrentfilelink = "";

        // ---------------------------------------------------------------------
        // alias / stat
        $alias = getAliasName($entry).".stat";
        if ((substr( strtolower($entry),-8 ) == ".torrent")) {
            // this is a torrent-client
            $btclient = getTorrentClient($entry);
            $af = AliasFile::getAliasFileInstance($cfg["torrent_file_path"].$alias, $torrentowner, $cfg, $btclient);
        } else if ((substr( strtolower($entry),-4 ) == ".url")) {
            // this is wget. use tornado statfile
            $btclient = "wget";
            $alias = str_replace(".url", "", $alias);
            $af = AliasFile::getAliasFileInstance($cfg["torrent_file_path"].$alias, $cfg['user'], $cfg, 'tornado');
        } else {
            $btclient = "tornado";
            // this is "something else". use tornado statfile as default
            $af = AliasFile::getAliasFileInstance($cfg["torrent_file_path"].$alias, $cfg['user'], $cfg, 'tornado');
        }
        // cache running-flag in local var. we will access that often
        $transferRunning = (int) $af->running;
        // cache percent-done in local var. ...
        $percentDone = $af->percent_done;

        // more vars
        $detailsLinkString = "<a style=\"font-size:9px; text-decoration:none;\" href=\"JavaScript:ShowDetails('downloaddetails.php?alias=".$alias."&torrent=".urlencode($entry)."')\">";

        // ---------------------------------------------------------------------
		//XFER: add upload/download stats to the xfer array
        if (($cfg['enable_xfer'] == 1) && ($cfg['xfer_realtime'] == 1)) {
            if (($btclient) != "wget") {
                $torrentTotalsCurrent = getTorrentTotalsCurrentOP($entry,$btclient,$af->uptotal,$af->downtotal);
            } else {
                $torrentTotalsCurrent["uptotal"] = $af->uptotal;
                $torrentTotalsCurrent["downtotal"] = $af->downtotal;
            }
            $sql = 'SELECT 1 FROM tf_xfer WHERE date = '.$db->DBDate(time());
            $newday = !$db->GetOne($sql);
            showError($db,$sql);
            sumUsage($torrentowner, ($torrentTotalsCurrent["downtotal"]+0), ($torrentTotalsCurrent["uptotal"]+0), 'total');
            sumUsage($torrentowner, ($torrentTotalsCurrent["downtotal"]+0), ($torrentTotalsCurrent["uptotal"]+0), 'month');
            sumUsage($torrentowner, ($torrentTotalsCurrent["downtotal"]+0), ($torrentTotalsCurrent["uptotal"]+0), 'week');
            sumUsage($torrentowner, ($torrentTotalsCurrent["downtotal"]+0), ($torrentTotalsCurrent["uptotal"]+0), 'day');
            //XFER: if new day add upload/download totals to last date on record and subtract from today in SQL
            if ($newday) {
                $newday = 2;
                $sql = 'SELECT date FROM tf_xfer ORDER BY date DESC';
                $lastDate = $db->GetOne($sql);
                showError($db,$sql);
                // MySQL 4.1.0 introduced 'ON DUPLICATE KEY UPDATE' to make this easier
                $sql = 'SELECT 1 FROM tf_xfer WHERE user_id = "'.$torrentowner.'" AND date = "'.$lastDate.'"';
                if ($db->GetOne($sql)) {
                    $sql = 'UPDATE tf_xfer SET download = download+'.($torrentTotalsCurrent["downtotal"]+0).', upload = upload+'.($torrentTotalsCurrent["uptotal"]+0).' WHERE user_id = "'.$torrentowner.'" AND date = "'.$lastDate.'"';
                    $db->Execute($sql);
                    showError($db,$sql);
                } else {
                    showError($db,$sql);
                    $sql = 'INSERT INTO tf_xfer (user_id,date,download,upload) values ("'.$torrentowner.'","'.$lastDate.'",'.($torrentTotalsCurrent["downtotal"]+0).','.($torrentTotalsCurrent["uptotal"]+0).')';
                    $db->Execute($sql);
                    showError($db,$sql);
                }
                $sql = 'SELECT 1 FROM tf_xfer WHERE user_id = "'.$torrentowner.'" AND date = '.$db->DBDate(time());
                if ($db->GetOne($sql)) {
                    $sql = 'UPDATE tf_xfer SET download = download-'.($torrentTotalsCurrent["downtotal"]+0).', upload = upload-'.($torrentTotalsCurrent["uptotal"]+0).' WHERE user_id = "'.$torrentowner.'" AND date = '.$db->DBDate(time());
                    $db->Execute($sql);
                    showError($db,$sql);
                } else {
                    showError($db,$sql);
                    $sql = 'INSERT INTO tf_xfer (user_id,date,download,upload) values ("'.$torrentowner.'",'.$db->DBDate(time()).',-'.($torrentTotalsCurrent["downtotal"]+0).',-'.($torrentTotalsCurrent["uptotal"]+0).')';
                    $db->Execute($sql);
                    showError($db,$sql);
                }
            }
        }

        // ---------------------------------------------------------------------
        // injects
        if(! file_exists($cfg["torrent_file_path"].$alias)) {
            $transferRunning = 2;
            $af->running = "2";
            $af->size = getDownloadSize($cfg["torrent_file_path"].$entry);
            $af->WriteFile();
        }

        // ---------------------------------------------------------------------
        // preprocess alias-file and get some vars
        $estTime = "&nbsp;";
        $statusStr = "&nbsp;";
        switch ($transferRunning) {
            case 2: // new
                // $statusStr
                $statusStr = $detailsLinkString."<font color=\"#32cd32\">New</font></a>";
                break;
            case 3: // queued
                // $statusStr
                $statusStr = $detailsLinkString."Queued</a>";
                // $estTime
                $estTime = "Waiting...";
                break;
            default: // running
                // increment the totals
                if(!isset($cfg["total_upload"])) $cfg["total_upload"] = 0;
                if(!isset($cfg["total_download"])) $cfg["total_download"] = 0;
                $cfg["total_upload"] = $cfg["total_upload"] + GetSpeedValue($af->up_speed);
                $cfg["total_download"] = $cfg["total_download"] + GetSpeedValue($af->down_speed);
                // $estTime
                if ($af->time_left != "" && $af->time_left != "0")
                    $estTime = $af->time_left;
                // $lastUser
                $lastUser = $torrentowner;
                // $show_run + $statusStr
                if($percentDone >= 100) {
                    if(trim($af->up_speed) != "" && $transferRunning == 1) {
                        $statusStr = $detailsLinkString.'Seeding</a>';
                    } else {
                        $statusStr = $detailsLinkString.'Done</a>';
                    }
                    $show_run = false;
                } else if ($percentDone < 0) {
                    $statusStr = $detailsLinkString."Stopped</a>";
                    $show_run = true;
                } else {
                    $statusStr = $detailsLinkString."Leeching</a>";
                }
                break;
        }
        // totals-preparation
        // if downtotal + uptotal + progress > 0
        if (($settings[2] + $settings[3] + $settings[5]) > 0) {
            if (($btclient) != "wget") {
                $torrentTotals = getTorrentTotalsOP($entry,$btclient,$af->uptotal,$af->downtotal);
            } else {
                $torrentTotals["uptotal"] = $af->uptotal;
                $torrentTotals["downtotal"] = $af->downtotal;
            }
        }

        // ---------------------------------------------------------------------
        // output-string
        $output = "<tr>";

        // ========================================================== led + meta
        $output .= '<td valign="bottom" align="center">';
        // led
        $hd = getStatusImage($af);
        if ($transferRunning == 1)
            $output .= "<a href=\"JavaScript:ShowDetails('downloadhosts.php?alias=".$alias."&torrent=".urlencode($entry)."')\">";
        $output .= "<img src=\"images/".$hd->image."\" width=\"16\" height=\"16\" title=\"".$hd->title.$entry."\" border=\"0\" align=\"absmiddle\">";
        if ($transferRunning == 1)
            $output .= "</a>";
        // meta
        $output .= $torrentfilelink;
        $output .= "</td>";

        // ================================================================ name
        $output .= "<td valign=\"bottom\">".$detailsLinkString.$displayname."</a></td>";

		// =============================================================== owner
		if ($settings[0] != 0)
            $output .= "<td valign=\"bottom\" align=\"center\"><a href=\"message.php?to_user=".$torrentowner."\"><font class=\"tiny\">".$torrentowner."</font></a></td>";

        // ================================================================ size
        if ($settings[1] != 0)
		  $output .= "<td valign=\"bottom\" align=\"right\" nowrap>".$detailsLinkString.formatBytesToKBMGGB($af->size)."</a></td>";

        // =========================================================== downtotal
        if ($settings[2] != 0)
            $output .= "<td valign=\"bottom\" align=\"right\" nowrap>".$detailsLinkString.formatBytesToKBMGGB($torrentTotals["downtotal"]+0)."</a></td>";

        // ============================================================= uptotal
        if ($settings[3] != 0)
            $output .= "<td valign=\"bottom\" align=\"right\" nowrap>".$detailsLinkString.formatBytesToKBMGGB($torrentTotals["uptotal"]+0)."</a></td>";

        // ============================================================== status
        if ($settings[4] != 0)
            $output .= "<td valign=\"bottom\" align=\"center\">".$detailsLinkString.$statusStr."</a></td>";

        // ============================================================ progress
        if ($settings[5] != 0) {
            $graph_width = 1;
            $progress_color = "#00ff00";
            $background = "#000000";
            $bar_width = "4";
            $percentage = "";
            if (($percentDone >= 100) && (trim($af->up_speed) != "")) {
                $graph_width = -1;
                $percentage = @number_format((($torrentTotals["uptotal"] / $af->size) * 100), 2) . '%';
            } else {
                if ($percentDone >= 1) {
                    $graph_width = $percentDone;
                    $percentage = $graph_width . '%';
                } else if ($percentDone < 0) {
                    $graph_width = round(($percentDone*-1)-100,1);
                    $percentage = $graph_width . '%';
                } else {
                    $graph_width = 0;
                    $percentage = '0%';
                }
            }
            if($graph_width == 100)
                $background = $progress_color;
            $output .= "<td valign=\"bottom\" align=\"center\" nowrap>";
            if ($graph_width == -1) {
                $output .= $detailsLinkString.'<strong>'.$percentage.'</strong></a>';
            } else if ($graph_width > 0) {
                $output .= $detailsLinkString.'<strong>'.$percentage.'</strong></a>';
                $output .= "<br>";
                $output .= "<table width=\"100\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr>";
                $output .= "<td background=\"themes/".$cfg["theme"]."/images/progressbar.gif\" bgcolor=\"".$progress_color."\">".$detailsLinkString."<img src=\"images/blank.gif\" width=\"".$graph_width."\" height=\"".$bar_width."\" border=\"0\"></a></td>";
                $output .= "<td bgcolor=\"".$background."\">".$detailsLinkString."<img src=\"images/blank.gif\" width=\"".(100 - $graph_width)."\" height=\"".$bar_width."\" border=\"0\"></a></td>";
                $output .= "</tr></table>";
            } else {
                if ($transferRunning == 2) {
                    $output .= '&nbsp;';
                } else {
                    $output .= $detailsLinkString.'<strong>'.$percentage.'</strong></a>';
                    $output .= "<br>";
                    $output .= "<table width=\"100\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr>";
                    $output .= "<td background=\"themes/".$cfg["theme"]."/images/progressbar.gif\" bgcolor=\"".$progress_color."\">".$detailsLinkString."<img src=\"images/blank.gif\" width=\"".$graph_width."\" height=\"".$bar_width."\" border=\"0\"></a></td>";
                    $output .= "<td bgcolor=\"".$background."\">".$detailsLinkString."<img src=\"images/blank.gif\" width=\"".(100 - $graph_width)."\" height=\"".$bar_width."\" border=\"0\"></a></td>";
                    $output .= "</tr></table>";
                }
            }
            $output .= "</td>";
        }

        // ================================================================ down
        if ($settings[6] != 0) {
            $output .= '<td valign="bottom" align="right" class="tiny" nowrap>';
            if ($transferRunning == 1) {
                $output .= $detailsLinkString;
                if (trim($af->down_speed) != "")
                    $output .= $af->down_speed;
                else
                    $output .= '0.0 kB/s';
                $output .= '</a>';
            } else {
                 $output .= '&nbsp;';
            }
            $output .= '</td>';
        }

        // ================================================================== up
        if ($settings[7] != 0) {
            $output .= '<td valign="bottom" align="right" class="tiny" nowrap>';
            if ($transferRunning == 1) {
                $output .= $detailsLinkString;
                if (trim($af->up_speed) != "")
                    $output .= $af->up_speed;
                else
                    $output .= '0.0 kB/s';
                $output .= '</a>';
            } else {
                 $output .= '&nbsp;';
            }
            $output .= '</td>';
        }

        // =============================================================== seeds
        if ($settings[8] != 0) {
            $output .= '<td valign="bottom" align="right" class="tiny" nowrap>';
            if ($transferRunning == 1) {
                $output .= $detailsLinkString;
                $output .= $af->seeds;
                $output .= '</a>';
            } else {
                 $output .= '&nbsp;';
            }
            $output .= '</td>';
        }

        // =============================================================== peers
        if ($settings[9] != 0) {
            $output .= '<td valign="bottom" align="right" class="tiny" nowrap>';
            if ($transferRunning == 1) {
                $output .= $detailsLinkString;
                $output .= $af->peers;
                $output .= '</a>';
            } else {
                 $output .= '&nbsp;';
            }
            $output .= '</td>';
        }

        // ================================================================= ETA
        if ($settings[10] != 0)
            $output .= "<td valign=\"bottom\" align=\"center\">".$detailsLinkString.$estTime."</a></td>";

        // ============================================================== client
        if ($settings[11] != 0) {
            switch ($btclient) {
                case "tornado":
                    $output .= "<td valign=\"bottom\" align=\"center\">B</a></td>";
                break;
                case "transmission":
                    $output .= "<td valign=\"bottom\" align=\"center\">T</a></td>";
                break;
                case "wget":
                    $output .= "<td valign=\"bottom\" align=\"center\">W</a></td>";
                break;
                default:
                    $output .= "<td valign=\"bottom\" align=\"center\">U</a></td>";
            }
        }

        // =============================================================== admin
        $output .= "<td><div align=center>";
        $torrentDetails = _TORRENTDETAILS;
        if ($lastUser != "")
            $torrentDetails .= "\n"._USER.": ".$lastUser;
        $output .= "<a href=\"details.php?torrent=".urlencode($entry);
        if($transferRunning == 1)
            $output .= "&als=false";
        $output .= "\"><img src=\"images/properties.png\" width=18 height=13 title=\"".$torrentDetails."\" border=0></a>";
        if ($owner || IsAdmin($cfg["user"])) {
			if($percentDone >= 0 && $transferRunning == 1) {
                $output .= "<a href=\"index.php?alias_file=".$alias."&kill=".$kill_id."&kill_torrent=".urlencode($entry)."\"><img src=\"images/kill.gif\" width=16 height=16 title=\""._STOPDOWNLOAD."\" border=0></a>";
                $output .= "<img src=\"images/delete_off.gif\" width=16 height=16 border=0>";
                if ($cfg['enable_multiops'] != 0)
				    $output .= "<input type=\"checkbox\" name=\"torrent[]\" value=\"".urlencode($entry)."\">";
            } else {
                if($torrentowner == "n/a") {
                    $output .= "<img src=\"images/run_off.gif\" width=16 height=16 border=0 title=\""._NOTOWNER."\">";
                } else {
                    if ($transferRunning == 3) {
                        $output .= "<a href=\"index.php?alias_file=".$alias."&dQueue=".$kill_id."&QEntry=".urlencode($entry)."\"><img src=\"images/queued.gif\" width=16 height=16 title=\""._DELQUEUE."\" border=0></a>";
                    } else {
                        if (!is_file($cfg["torrent_file_path"].$alias.".pid")) {
                            // Allow Avanced start popup?
                            if ($cfg["advanced_start"] != 0) {
                                if($show_run)
                                    $output .= "<a href=\"#\" onclick=\"StartTorrent('startpop.php?torrent=".urlencode($entry)."')\"><img src=\"images/run_on.gif\" width=16 height=16 title=\""._RUNTORRENT."\" border=0></a>";
                                else
                                    $output .= "<a href=\"#\" onclick=\"StartTorrent('startpop.php?torrent=".urlencode($entry)."')\"><img src=\"images/seed_on.gif\" width=16 height=16 title=\""._SEEDTORRENT."\" border=0></a>";
                            } else {
                                // Quick Start
                                if($show_run)
                                    $output .= "<a href=\"".$_SERVER['PHP_SELF']."?torrent=".urlencode($entry)."\"><img src=\"images/run_on.gif\" width=16 height=16 title=\""._RUNTORRENT."\" border=0></a>";
                                else
                                    $output .= "<a href=\"".$_SERVER['PHP_SELF']."?torrent=".urlencode($entry)."\"><img src=\"images/seed_on.gif\" width=16 height=16 title=\""._SEEDTORRENT."\" border=0></a>";
                            }
                        } else {
                            // pid file exists so this may still be running or dieing.
                            $output .= "<img src=\"images/run_off.gif\" width=16 height=16 border=0 title=\""._STOPPING."\">";
                        }
                    }
                }
                if (!is_file($cfg["torrent_file_path"].$alias.".pid")) {
                    $deletelink = $_SERVER['PHP_SELF']."?alias_file=".$alias."&delfile=".urlencode($entry);
                    $output .= "<a href=\"".$deletelink."\" onclick=\"return ConfirmDelete('".$entry."')\"><img src=\"images/delete_on.gif\" width=16 height=16 title=\""._DELETE."\" border=0></a>";
                    if ($cfg['enable_multiops'] != 0)
					   $output .= "<input type=\"checkbox\" name=\"torrent[]\" value=\"".urlencode($entry)."\">";
                } else {
                    // pid file present so process may be still running. don't allow deletion.
                    $output .= "<img src=\"images/delete_off.gif\" width=16 height=16 title=\""._STOPPING."\" border=0>";
					if ($cfg['enable_multiops'] != 0)
					   $output .= "<input type=\"checkbox\" name=\"torrent[]\" value=\"".urlencode($entry)."\">";
                }
            }
        } else {
            $output .= "<img src=\"images/locked.gif\" width=16 height=16 border=0 title=\""._NOTOWNER."\">";
            $output .= "<img src=\"images/locked.gif\" width=16 height=16 border=0 title=\""._NOTOWNER."\">";
			$output .= "<input type=\"checkbox\" disabled=\"disabled\">";
        }
        $output .= "</div>";
        $output .= "</td>";
        $output .= "</tr>\n";

        // ---------------------------------------------------------------------
        // Is this torrent for the user list or the general list?
        if ($cfg["user"] == getOwner($entry))
            array_push($arUserTorrent, $output);
        else
            array_push($arListTorrent, $output);
    }

	//XFER: if a new day but no .stat files where found put blank entry into the DB for today to indicate accounting has been done for the new day
    if (($cfg['enable_xfer'] == 1) && ($cfg['xfer_realtime'] == 1)) {
      if ((isset($newday)) && ($newday == 1)) {
        $sql = 'INSERT INTO tf_xfer (user_id,date) values ( "",'.$db->DBDate(time()).')';
        $db->Execute($sql);
        showError($db,$sql);
      }
      getUsage(0, 'total');
      $month_start = (date('j')>=$cfg['month_start']) ? date('Y-m-').$cfg['month_start'] : date('Y-m-',strtotime('-1 Month')).$cfg['month_start'];
      getUsage($month_start, 'month');
      $week_start = date('Y-m-d',strtotime('last '.$cfg['week_start']));
      getUsage($week_start, 'week');
      $day_start = date('Y-m-d');
      getUsage($day_start, 'day');
    }

    // -------------------------------------------------------------------------
    // build output-string
    $output = '<table bgcolor="'.$cfg["table_data_bg"].'" width="100%" bordercolor="'.$cfg["table_border_dk"].'" border="1" cellpadding="3" cellspacing="0" class="sortable" id="transfer_table">';
    if (sizeof($arUserTorrent) > 0) {
        $output .= getTransferTableHead($settings, $sortOrder, $cfg["user"]." : ");
        foreach($arUserTorrent as $torrentrow)
            $output .= $torrentrow;
    }
    $boolCond = true;
    if ($cfg['enable_restrictivetview'] == 1)
        $boolCond = IsAdmin();
    if (($boolCond) && (sizeof($arListTorrent) > 0)) {
        $output .= getTransferTableHead($settings, $sortOrder);
        foreach($arListTorrent as $torrentrow)
            $output .= $torrentrow;
    }
    $output .= "</tr></table>\n";
    return $output;
}

/*
 * This method get html-snip of table-head
 *
 * @param $settings array holding index-page-settings
 * @param $sortOrder
 * @param $nPrefix prefix of name-column
 * @return string
 */
function getTransferTableHead($settings, $sortOrder = '', $nPrefix = '') {
    global $cfg;
    $output = "<tr>";
    //
    // ============================================================== led + meta
    $output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">";
    switch ($sortOrder) {
        case 'da': // sort by date ascending
            $output .= '<a href="?so=dd"><font class="adminlink">#</font></a>';
            $output .= '&nbsp;';
            $output .= '<a href="?so=dd"><img src="images/s_down.gif" width="9" height="9" border="0"></a>';
            break;
        case 'dd': // sort by date descending
            $output .= '<a href="?so=da"><font class="adminlink">#</font></a>';
            $output .= '&nbsp;';
            $output .= '<a href="?so=da"><img src="images/s_up.gif" width="9" height="9" border="0"></a>';
            break;
        default:
            $output .= '<a href="?so=dd"><font class="adminlink">#</font></a>';
            break;
    }
    $output .= "</div></td>";
    // ==================================================================== name
    $output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">";
    switch ($sortOrder) {
        case 'na': // sort alphabetically by name ascending
            $output .= '<a href="?so=nd"><font class="adminlink">' .$nPrefix. _TORRENTFILE .'</font></a>';
            $output .= '&nbsp;';
            $output .= '<a href="?so=nd"><img src="images/s_down.gif" width="9" height="9" border="0"></a>';
            break;
        case 'nd': // sort alphabetically by name descending
            $output .= '<a href="?so=na"><font class="adminlink">' .$nPrefix. _TORRENTFILE .'</font></a>';
            $output .= '&nbsp;';
            $output .= '<a href="?so=na"><img src="images/s_up.gif" width="9" height="9" border="0"></a>';
            break;
        default:
            $output .= '<a href="?so=na"><font class="adminlink">' .$nPrefix. _TORRENTFILE .'</font></a>';
            break;
    }
    $output .= "</div></td>";
    // =================================================================== owner
    if ($settings[0] != 0)
        $output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">"._USER."</div></td>";
    // ==================================================================== size
    if ($settings[1] != 0)
        $output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">Size</div></td>";
    // =============================================================== downtotal
    if ($settings[2] != 0)
        $output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">T. Down</div></td>";
    // ================================================================= uptotal
    if ($settings[3] != 0)
        $output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">T. Up</div></td>";
    // ================================================================== status
    if ($settings[4] != 0)
        $output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">"._STATUS."</div></td>";
    // ================================================================ progress
    if ($settings[5] != 0)
        $output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">Progress</div></td>";
    // ==================================================================== down
    if ($settings[6] != 0)
        $output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">Down</div></td>";
    // ====================================================================== up
    if ($settings[7] != 0)
        $output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">Up</div></td>";
    // =================================================================== seeds
    if ($settings[8] != 0)
        $output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">Seeds</div></td>";
    // =================================================================== peers
    if ($settings[9] != 0)
        $output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">Peers</div></td>";
    // ===================================================================== ETA
    if ($settings[10] != 0)
        $output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">"._ESTIMATEDTIME."</div></td>";
    // ================================================================== client
    if ($settings[11] != 0)
        $output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">C</div></td>";
    // =================================================================== admin
    $output .= "<td background=\"themes/".$cfg["theme"]."/images/bar.gif\" bgcolor=\"".$cfg["table_header_bg"]."\" nowrap><div align=\"center\" class=\"title\">"._ADMIN."</div></td>";
    //
    $output .= "</tr>\n";
    // return
    return $output;
}

/**
 * checks a dir. recursive process to emulate "mkdir -p" if dir not present
 *
 * @param $dir the name of the dir
 * @param $mode the mode of the dir if created. default is 0755
 * @return boolean if dir exists/could be created
 */
function checkDirectory($dir, $mode = 0755) {
  if ((is_dir($dir) && is_writable ($dir)) || @mkdir($dir,$mode))
    return true;
  if (! checkDirectory(dirname($dir),$mode))
    return false;
  return @mkdir($dir,$mode);
}

/*
 * repairTorrentflux
 *
 */
function repairTorrentflux() {
	global $cfg, $db;
    // delete pid-files of torrent-clients
	if ($dirHandle = opendir($cfg["torrent_file_path"])) {
		while (false !== ($file = readdir($dirHandle))) {
			if ((substr($file, -1, 1)) == "d")
				@unlink($cfg["torrent_file_path"].$file);
		}
		closedir($dirHandle);
	}
	// rewrite stat-files
	include_once("AliasFile.php");
	$torrents = getTorrentListFromFS();
	foreach ($torrents as $torrent) {
		$alias = getAliasName($torrent);
		$owner = getOwner($torrent);
		$btclient = getTorrentClient($torrent);
		$af = AliasFile::getAliasFileInstance($cfg["torrent_file_path"].$alias.".stat", $owner, $cfg, $btclient);
        if (isset($af)) {
            $af->running = 0;
			$af->percent_done = -100.0;
			$af->time_left = 'Torrent Stopped';
			$af->down_speed = 0;
			$af->up_speed = 0;
			$af->seeds = 0;
			$af->peers = 0;
            $af->WriteFile();
        }
	}
	// set flags in db
	$db->Execute("UPDATE tf_torrents SET running = '0'");
	// delete leftovers of tfqmgr.pl (only do this if daemon is not running)
	$tfqmgrRunning = trim(shell_exec("ps aux 2> /dev/null | ".$cfg['bin_grep']." -v grep | ".$cfg['bin_grep']." -c tfqmgr.pl"));
	if ($tfqmgrRunning == "0") {
		if (file_exists($cfg["path"].'.tfqmgr/tfqmgr.pid'))
			@unlink($cfg["path"].'.tfqmgr/tfqmgr.pid');
		if (file_exists($cfg["path"].'.tfqmgr/COMMAND'))
			@unlink($cfg["path"].'.tfqmgr/COMMAND');
		if (file_exists($cfg["path"].'.tfqmgr/TRANSPORT'))
			@unlink($cfg["path"].'.tfqmgr/TRANSPORT');
	}
}

/**
 * getLoadAverageString
 *
 * @return string with load-average
 */
function getLoadAverageString() {
    global $cfg;
    switch (_OS) {
        case 1: // linux
            if (isFile($cfg["loadavg_path"])) {
                $loadavg_array = explode(" ", exec($cfg['bin_cat']." ".$cfg["loadavg_path"]));
                return $loadavg_array[2];
            } else {
                return 'n/a';
            }
        break;
        case 2: // bsd
            $loadavg = preg_replace("/.*load averages:(.*)/", "$1", exec("uptime"));
            return $loadavg;
        break;
        default:
            return 'n/a';
    }
    return 'n/a';
}

/**
 * injects a atorrent
 *
 * @param $torrent
 * @return boolean
 */
function injectTorrent($torrent) {
    global $cfg;
    include_once("AliasFile.php");
    $af = AliasFile::getAliasFileInstance($cfg["torrent_file_path"].getAliasName($torrent).".stat",  $cfg['user'], $cfg);
    $af->running = "2"; // file is new
    $af->size = getDownloadSize($cfg["torrent_file_path"].$torrent);
    $af->WriteFile();
    return true;
}

/**
 * process post-params on config-update and init settings-array
 *
 * @return array with settings
 */
function processSettingsParams() {
    // move hack
	unset($_POST['addCatButton']);
	unset($_POST['remCatButton']);
	unset($_POST['categorylist']);
	unset($_POST['category']);
    // init settings array from params
    // process and handle all specials and exceptions while doing this.
    $settings = array();
    // good-look-stats
    $hackStatsPrefix = "hack_goodlookstats_settings_";
    $hackStatsStringLen = strlen($hackStatsPrefix);
    $settingsHackAry = array();
    for ($i = 0; $i <= 5; $i++)
        $settingsHackAry[$i] = 0;
    $hackStatsUpdate = false;
    // index-page
    $indexPageSettingsPrefix = "index_page_settings_";
    $indexPageSettingsPrefixLen = strlen($indexPageSettingsPrefix);
    $settingsIndexPageAry = array();
    for ($j = 0; $j <= 10; $j++)
        $settingsIndexPageAry[$j] = 0;
    $indexPageSettingsUpdate = false;
    //
    foreach ($_POST as $key => $value) {
        if ((substr($key, 0, $hackStatsStringLen)) == $hackStatsPrefix) {
            // good-look-stats
            $idx = (int) substr($key, -1, 1);
            if ($value != "0")
                $settingsHackAry[$idx] = 1;
            else
                $settingsHackAry[$idx] = 0;
            $hackStatsUpdate = true;
        } else if ((substr($key, 0, $indexPageSettingsPrefixLen)) == $indexPageSettingsPrefix) {
            // index-page
            $idx = (int) substr($key, ($indexPageSettingsPrefixLen - (strlen($key))));
            if ($value != "0")
                $settingsIndexPageAry[$idx] = 1;
            else
                $settingsIndexPageAry[$idx] = 0;
            $indexPageSettingsUpdate = true;
        } else {
            switch ($key) {
                case "path": // tf-path
                    $settings[$key] = trim(checkDirPathString($value));
                    break;
                case "move_paths": // move-hack-paths
                    $dirAry = explode(":",$value);
                    $val = "";
                    for ($idx = 0; $idx < count($dirAry); $idx++) {
                        if ($idx > 0)
                            $val .= ':';
                        $val .= trim(checkDirPathString($dirAry[$idx]));
                    }
                    $settings[$key] = trim($val);
                    break;
                default: // "normal" key-val-pair
                    $settings[$key] = $value;
            }
        }
    }
    // good-look-stats
    if ($hackStatsUpdate)
        $settings['hack_goodlookstats_settings'] = convertArrayToByte($settingsHackAry);
    // index-page
    if ($indexPageSettingsUpdate)
        $settings['index_page_settings'] = convertArrayToInteger($settingsIndexPageAry);
    // return
    return $settings;
}

/**
 * checks if a path-string has a trailing slash. concat if it hasnt
 *
 * @param $dirPath
 * @return string with dirPath
 */
function checkDirPathString($dirPath) {
    if (((strlen($dirPath) > 0)) && (substr($dirPath, -1 ) != "/"))
        $dirPath .= "/";
    return $dirPath;
}

/**
 * print form of good looking stats hack (0-63)
 *
 */
function printGoodLookingStatsForm() {
    global $cfg;
    $settingsHackStats = convertByteToArray($cfg["hack_goodlookstats_settings"]);
    echo '<table>';
    echo '<tr><td align="right" nowrap>Download Speed: <input name="hack_goodlookstats_settings_0" type="Checkbox" value="1"';
    if ($settingsHackStats[0] == 1)
        echo ' checked';
    echo '></td>';
    echo '<td align="right" nowrap>Upload Speed: <input name="hack_goodlookstats_settings_1" type="Checkbox" value="1"';
    if ($settingsHackStats[1] == 1)
        echo ' checked';
    echo '></td>';
    echo '<td align="right" nowrap>Total Speed: <input name="hack_goodlookstats_settings_2" type="Checkbox" value="1"';
    if ($settingsHackStats[2] == 1)
        echo ' checked';
    echo '></td></tr>';
    echo '<tr><td align="right" nowrap>Connections: <input name="hack_goodlookstats_settings_3" type="Checkbox" value="1"';
    if ($settingsHackStats[3] == 1)
        echo ' checked';
    echo '></td>';
    echo '<td align="right" nowrap>Drive Space: <input name="hack_goodlookstats_settings_4" type="Checkbox" value="1"';
    if ($settingsHackStats[4] == 1)
        echo ' checked';
    echo '></td>';
    echo '<td align="right" nowrap>Server Load: <input name="hack_goodlookstats_settings_5" type="Checkbox" value="1"';
    if ($settingsHackStats[5] == 1)
        echo ' checked';
    echo '></td></tr>';
    echo '</table>';
}

/**
 * print form of index page settings (0-2047)
 *
 * #
 * Torrent
 *
 * User           [0]
 * Size           [1]
 * DLed           [2]
 * ULed           [3]
 *
 * Status         [4]
 * Progress       [5]
 * DL Speed       [6]
 * UL Speed       [7]
 *
 * Seeds          [8]
 * Peers          [9]
 * ETA           [10]
 * TorrentClient [11]
 *
 */
function printIndexPageSettingsForm() {
    global $cfg;
    $settingsIndexPage = convertIntegerToArray($cfg["index_page_settings"]);
    echo '<table>';
    echo '<tr>';
    echo '<td align="right" nowrap>Owner: <input name="index_page_settings_0" type="Checkbox" value="1"';
    if ($settingsIndexPage[0] == 1)
        echo ' checked';
    echo '></td>';
    echo '<td align="right" nowrap>Size: <input name="index_page_settings_1" type="Checkbox" value="1"';
    if ($settingsIndexPage[1] == 1)
        echo ' checked';
    echo '></td>';
    echo '<td align="right" nowrap>Total Down: <input name="index_page_settings_2" type="Checkbox" value="1"';
    if ($settingsIndexPage[2] == 1)
        echo ' checked';
    echo '></td>';
    echo '<td align="right" nowrap>Total Up: <input name="index_page_settings_3" type="Checkbox" value="1"';
    if ($settingsIndexPage[3] == 1)
        echo ' checked';
    echo '></td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td align="right" nowrap>Status : <input name="index_page_settings_4" type="Checkbox" value="1"';
    if ($settingsIndexPage[4] == 1)
        echo ' checked';
    echo '></td>';
    echo '<td align="right" nowrap>Progress : <input name="index_page_settings_5" type="Checkbox" value="1"';
    if ($settingsIndexPage[5] == 1)
        echo ' checked';
    echo '></td>';
    echo '<td align="right" nowrap>Down-Speed : <input name="index_page_settings_6" type="Checkbox" value="1"';
    if ($settingsIndexPage[6] == 1)
        echo ' checked';
    echo '></td>';
    echo '<td align="right" nowrap>Up-Speed : <input name="index_page_settings_7" type="Checkbox" value="1"';
    if ($settingsIndexPage[7] == 1)
        echo ' checked';
    echo '></td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td align="right" nowrap>Seeds : <input name="index_page_settings_8" type="Checkbox" value="1"';
    if ($settingsIndexPage[8] == 1)
        echo ' checked';
    echo '></td>';
    echo '<td align="right" nowrap>Peers : <input name="index_page_settings_9" type="Checkbox" value="1"';
    if ($settingsIndexPage[9] == 1)
        echo ' checked';
    echo '></td>';
    echo '<td align="right" nowrap>Estimated Time : <input name="index_page_settings_10" type="Checkbox" value="1"';
    if ($settingsIndexPage[10] == 1)
        echo ' checked';
    echo '></td>';
    echo '<td align="right" nowrap>Client : <input name="index_page_settings_11" type="Checkbox" value="1"';
    if ($settingsIndexPage[11] == 1)
        echo ' checked';
    echo '></td>';
    echo '</tr>';
    echo '</table>';
}

/**
 * print form of move-settings
 *
 */
function printMoveSettingsForm() {
    global $cfg;
    echo '<table>';
    echo '<tr>';
    echo '<td valign="top" align="left">Target-Dirs:</td>';
    echo '<td valign="top" align="left">';
    echo '<select name="categorylist" size="5">';
    if ((isset($cfg["move_paths"])) && (strlen($cfg["move_paths"]) > 0)) {
        $dirs = split(":", trim($cfg["move_paths"]));
        foreach ($dirs as $dir) {
            $target = trim($dir);
            if ((strlen($target) > 0) && ((substr($target, 0, 1)) != ";"))
                echo "<option value=\"$target\">".$target."</option>\n";
        }
    }
    echo '</select>';
    echo '<input type="button" name="remCatButton" value="remove" onclick="removeEntry()">';
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td valign="top" align="left">New Target-Dir:</td>';
    echo '<td valign="top" align="left">';
    echo '<input type="text" name="category" size="30">';
    echo '<input type="button" name="addCatButton" value="add" onclick="addEntry()" size="30">';
    echo '<input type="hidden" name="move_paths" value="'.$cfg["move_paths"].'">';
    echo '</td>';
    echo '</tr>';
    echo '</table>';
}

/**
 * print form of index-page-selection
 *
 */
function printIndexPageSelectForm() {
    global $cfg;
    echo '<select name="index_page">';
    echo '<option value="tf"';
    if ($cfg["index_page"] == "tf")
        echo " selected";
    echo '>tf</option>';
    echo '<option value="b4rt"';
    if ($cfg["index_page"] == "b4rt")
        echo " selected";
    echo '>b4rt</option>';
    echo '</select>';
}

/**
 * print form of sort-order-settings
 *
 */
function printSortOrderSettingsForm() {
    global $cfg;
    echo '<select name="index_page_sortorder">';
    echo '<option value="da"';
    if ($cfg['index_page_sortorder'] == "da")
        echo " selected";
    echo '>Date - Ascending</option>';
    echo '<option value="dd"';
    if ($cfg['index_page_sortorder'] == "dd")
        echo " selected";
    echo '>Date - Descending</option>';
    echo '<option value="na"';
    if ($cfg['index_page_sortorder'] == "na")
        echo " selected";
    echo '>Name - Ascending</option>';
    echo '<option value="nd"';
    if ($cfg['index_page_sortorder'] == "nd")
        echo " selected";
    echo '>Name - Descending</option>';
    echo '</select>';
}

/**
 * print form of drivespacebar-selection
 *
 */
function printDrivespacebarSelectForm() {
    global $cfg;
    echo '<select name="drivespacebar">';
    echo '<option value="tf"';
    if ($cfg["drivespacebar"] == "tf")
        echo " selected";
    echo '>tf</option>';
    echo '<option value="xfer"';
    if ($cfg["drivespacebar"] == "xfer")
        echo " selected";
    echo '>xfer</option>';
    echo '</select>';
}


?>