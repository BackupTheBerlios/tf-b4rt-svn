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
define('_FILE_VERSION_CURRENT','version-torrentflux-b4rt.txt');
define('_FILE_CHECKSUMS_CURRENT','checksums-torrentflux-b4rt.txt');
define('_UPDATE_BASEDIR','update_new');
//
define('_FILE_NEWS','newshtml.txt');
define('_UPDATE_DATADIR','data');
define('_UPDATE_SQLDIR','sql');
define('_UPDATE_HTMLDIR','html');
define('_UPDATE_INDEX','update.txt');
define('_UPDATE_DB','db.txt');
define('_UPDATE_MYSQL','mysql.txt');
define('_UPDATE_SQLITE','sqlite.txt');
define('_UPDATE_POSTGRES','postgres.txt');
define('_UPDATE_FILES','update.list');
define('_UPDATE_ARCHIVE','update.tar.bz2');

// functions
require_once('functions.php');

// -----------------------------------------------------------------------------
// Main
// -----------------------------------------------------------------------------
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
            echo "invalid version";
            exit;
        }
        if( preg_match("/\.\./", urldecode($remoteVersion)) ) {
            header("Content-Type: text/plain");
            echo "invalid version";
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
                    bailOut(false);
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
                $updateFileList = getFileListNEW($currentVersion, $remoteVersion);
                if ((isset($updateFileList)) && ($updateFileList != "0"))
                    outputData($updateFileList);
                else
                    bailOut(true);
                exit;

            case "4":
                // serve md5 of update-file
            	$updateFile = "./". _UPDATE_BASEDIR."/".$currentVersion."/".$remoteVersion."/"._UPDATE_DATADIR."/". _UPDATE_HTMLDIR."/"._UPDATE_ARCHIVE;
				if (file_exists($updateFile)) {
					header("Content-Type: text/plain");
                    echo md5_file($updateFile);
				} else {
					bailOut(false);
				}
                exit;

            case "5":
                // serve the update-file
            	$updateFile = "./". _UPDATE_BASEDIR."/".$currentVersion."/".$remoteVersion."/"._UPDATE_DATADIR."/". _UPDATE_HTMLDIR."/"._UPDATE_ARCHIVE;
				// send data. read / write file with 8kb-buffer
				if ($handle = @fopen($updateFile, 'rb')) {
					@header("Cache-Control: ");
					@header("Pragma: ");
					@header("Content-Type: application/octet-stream");
					@header("Content-Length: " .(string)(filesize($updateFile)) );
					@header('Content-Disposition: attachment; filename="'._UPDATE_ARCHIVE.'"');
					@header("Content-Transfer-Encoding: binary\n");
					while((!@feof($handle)) && (connection_status() == 0)) {
						print(@fread($handle, 8192));
						@flush();
					}
					@fclose($handle);
				} else {
					bailOut(false);
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

    case "9": // checksums
		header("Content-Type: text/plain");
		echo getDataFromFile(_FILE_CHECKSUMS_CURRENT);
        exit;

    case "9c": // checksums compressed
        outputData(getDataFromFile(_FILE_CHECKSUMS_CURRENT));
        exit;

    default:
		header("Content-Type: text/plain");
		echo trim(getDataFromFile(_FILE_VERSION_CURRENT));
		exit;
}

exit;

?>