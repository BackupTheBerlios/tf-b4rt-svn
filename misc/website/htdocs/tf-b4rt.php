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

// defines
define('_FILE_CHANGELOG','changelog-torrentflux-b4rt.txt');
define('_FILE_HITS','./internal/hits-torrentflux-b4rt.txt');
define('_FILE_NEWS','newshtml.txt');
define('_FILE_VERSION_CURRENT','version.txt');
define('_UPDATE_BASEDIR','update');
define('_UPDATE_DATADIR','data');
define('_UPDATE_SQLDIR','sql');
define('_UPDATE_HTMLDIR','html');
define('_UPDATE_INDEX','update.txt');
define('_UPDATE_DB','db.txt');
define('_UPDATE_MYSQL','mysql.txt');
define('_UPDATE_SQLITE','sqlite.txt');
define('_UPDATE_POSTGRES','postgres.txt');
define('_REVISION', array_shift(explode(" ",trim(array_pop(explode(":",'$Revision$'))))));

// functions
require_once('functions.php');

// -----------------------------------------------------------------------------
// Main
// -----------------------------------------------------------------------------
@logHit();
@logProxy();

// update
$update = @trim($_REQUEST["u"]);
if ((isset($update)) && ($update != "")) {
    // hold current version
    $currentVersion = trim(getDataFromFile(_FILE_VERSION_CURRENT));
    // hold remote version
    $remoteVersion = @trim($_REQUEST["v"]);
    if ((isset($remoteVersion)) && ($remoteVersion != "")) {
        // pre-sanity-checks
        if( preg_match("/\\\/", urldecode($remoteVersion)) ) {
            header("Content-Type: text/plain");
            echo "no exploits pls. thx.";
            exit;
        }
        if( preg_match("/\.\./", urldecode($remoteVersion)) ) {
            header("Content-Type: text/plain");
            echo "no exploits pls. thx.";
            exit;
        }
        switch($update) {
            case "0":
                // load index-file
                $updateIndexData = trim(getDataFromFile("./". _UPDATE_BASEDIR . "/" . $currentVersion . "/" . $remoteVersion . "/" . _UPDATE_INDEX));
                if ((isset($updateIndexData)) && ($updateIndexData != "")) {
                    header("Content-Type: text/plain");
                    echo $updateIndexData;
                    exit;
                } else {
                    bailOut(false);
                }
                exit;
            case "1":
                // load db-file and spit out
                $updateDBData = trim(getDataFromFile("./". _UPDATE_BASEDIR . "/" . $currentVersion . "/" . $remoteVersion . "/" . _UPDATE_DB));
                if ((isset($updateDBData)) && ($updateDBData != "")) {
                    header("Content-Type: text/plain");
                    echo $updateDBData;
                } else {
                    bailOut(true);
                }
                exit;
            case "2":
                // hold remote database-version
                $remoteDb = @trim($_REQUEST["d"]);
                if ((isset($remoteDb)) && ($remoteDb != "")) {
                    $sqlFile = "";
                    switch($remoteDb) {
                        case "mysql":
                          $sqlFile = _UPDATE_MYSQL;
                          break;
                        case "sqlite":
                          $sqlFile = _UPDATE_SQLITE;
                          break;
                        case "postgres":
                          $sqlFile = _UPDATE_POSTGRES;
                          break;                          
                        default:
                            bailOut(true);
                            break;
                    }
                    // load sql-file and spit out
                    $updateSQLData = trim(getDataFromFile("./". _UPDATE_BASEDIR . "/" . $currentVersion . "/" . $remoteVersion . "/" ._UPDATE_DATADIR . "/" . _UPDATE_SQLDIR . "/" . $sqlFile));
                    if ((isset($updateSQLData)) && ($updateSQLData != "")) {
                        outputData($updateSQLData);
                    } else {
                        bailOut(true);
                    }
                } else {
                    bailOut(true);
                }
                exit;
            case "3":
                // file list
                $updateFileList = getFileList($currentVersion, $remoteVersion);
                if ((isset($updateFileList)) && ($updateFileList != "0")) {
                    header("Content-Type: text/plain");
                    echo $updateFileList;
                } else {
                    bailOut(false);
                }
                exit;
            case "4":
                // serve a file
                $requestFile = @trim($_REQUEST["f"]);
                if ((isset($requestFile)) && ($requestFile != "")) {
                    // file list (no exploits, only deliver valid data)
                    $updateFileList = getFileList($currentVersion, $remoteVersion);
                    if ((isset($updateFileList)) && ($updateFileList != "0")) {
                        $validFiles = explode("\n",$updateFileList);
                        if (in_array($requestFile, $validFiles)) {
                            outputData(getDataFromFile("./". _UPDATE_BASEDIR . "/" . $currentVersion . "/" . $remoteVersion . "/" ._UPDATE_DATADIR . "/" . _UPDATE_HTMLDIR . "/" . $requestFile));
                            exit;
                        } else {
                            header("Content-Type: text/plain");
                            echo $requestFile." is not a valid file-ressource-id. no exploits pls. thx.";
                            exit;
                        }
                    } else {
                        bailOut(true);
                    }
                } else {
                    bailOut(true);
                }
                exit;
        }
    } else {
        bailOut(false);
    }
}

// standard-action
$action = @trim($_REQUEST["a"]);
switch($action) {
    case "0": // news
        outputData(rewriteNews(getDataFromFile(_FILE_NEWS)));
        exit; 	
    case "1": // changelog
        outputData(getDataFromFile(_FILE_CHANGELOG));
        exit;
    default:
		header("Content-Type: text/plain");
		echo basename($_SERVER['SCRIPT_FILENAME']) . " " . _REVISION;
		exit;
}

exit;

?>