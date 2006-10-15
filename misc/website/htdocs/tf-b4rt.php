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
define('_FILE_NEWS','newshtml.txt');
define('_FILE_CHANGELOG','changelog-torrentflux-b4rt.txt');
define('_FILE_ISSUES','issues.txt');
define('_FILE_VERSION_CURRENT','version.txt');
define('_UPDATE_BASEDIR','update');
define('_UPDATE_DATADIR','data');
define('_UPDATE_SQLDIR','sql');
define('_UPDATE_HTMLDIR','html');
define('_UPDATE_INDEX','update.txt');
define('_UPDATE_DB','db.txt');
define('_UPDATE_MYSQL','mysql.txt');
define('_UPDATE_SQLITE','sqlite.txt');

// -----------------------------------------------------------------------------
// Main
// -----------------------------------------------------------------------------

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
            break;
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
            break;
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
            break;
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
            break;
        }
    } else {
        bailOut(false);
    }
}

// standard-action
$action = @trim($_REQUEST["a"]);
switch($action) {
    case "1": // changelog
        outputData(getDataFromFile(_FILE_CHANGELOG));
        exit;
    break;
    case "2": // issues
        outputData(getDataFromFile(_FILE_ISSUES));
        exit;
    //case "3": // release-list
    //    outputData(getReleaseList());
    //    exit;
    //break;
    case "0": // news
    default:
        outputData(rewriteNews(getDataFromFile(_FILE_NEWS)));
        exit;
}

exit;

// -----------------------------------------------------------------------------
// functions
// -----------------------------------------------------------------------------

/**
 * load data of file
 *
 * @param $file the file
 * @return data
 */
function getDataFromFile($file) {
    // read content
    if($fileHandle = @fopen($file,'r')) {
        $data = null;
        while (!@feof($fileHandle))
            $data .= @fgets($fileHandle, 4096);
        @fclose ($fileHandle);
    }
    return $data;
}

/**
 * output data
 *
 * @param $data the data
 */
function outputData($data) {
    // spit out compressed string
    echo(gzdeflate($data, 9));
}

/**
 * bails out cause of errors.
 *
 */
function bailOut($compressed = false) {
    $errorString = "0"."\n"."cant auto-update from your version. please do manual update.";
    if ($compressed) {
        echo(gzdeflate($errorString, 9));
    } else {
        header("Content-Type: text/plain");
        echo $errorString;
    }
    exit;
}

/**
 * get file-list
 *
 * @return filelist as string
 */
function getFileList($currentVersion, $remoteVersion) {
    // file list
    $dirName = "./". _UPDATE_BASEDIR . "/" . $currentVersion . "/" . $remoteVersion . "/" ._UPDATE_DATADIR . "/" . _UPDATE_HTMLDIR;
    if (file_exists($dirName)) {
        if ($dirHandle = opendir($dirName)) {
            $updateFileList = "";
            while (false !== ($file = readdir($dirHandle))) {
                if ((substr($file, 0, 1)) != ".")
                    $updateFileList .= $file . "\n";
            }
            closedir($dirHandle);
            return $updateFileList;
        } else {
            return "0";
        }
    } else {
        return "0";
    }
}

/**
 * get release-list
 *
 * @return releaselist as string
 */
/*
function getReleaseList() {
    // release list
    $dirName = "./". _FILESDIR;
    if (file_exists($dirName)) {
        if ($dirHandle = opendir($dirName)) {
            $releaseList = "";
            while (false !== ($file = readdir($dirHandle))) {
                if ((substr($file, -1, 1)) == "2")
                    $releaseList .= $file . "\n";
            }
            closedir($dirHandle);
            return $releaseList;
        } else {
            die("error open dir ".$dirName);
            return "";
        }
    } else {
        die("dir ".$dirName."does not exist");
        return "";
    }
}
*/

/**
 * rewrite berliOS-news-export-HTML to fitting xhtml
 *
 * @param $string string with berliOS-news-export
 * @return string with news
 */
function rewriteNews($string) {
	// remove <hr>-tags
	$retVal = eregi_replace("<hr[[:space:]]*([^>]*)[[:space:]]*>", '', $string);
	// create list-elements from news-entries
	$retVal = eregi_replace("<a[[:space:]]*", '<li><a ', $retVal);
	$retVal = eregi_replace("<b>", '', $retVal);
	$retVal = eregi_replace("</b>", '', $retVal);
	$retVal = eregi_replace("<i>", '<em>', $retVal);
	$retVal = eregi_replace("</i>", '</em></li>', $retVal);
	// spacer
	$retVal = eregi_replace("&nbsp;&nbsp;&nbsp;", '&nbsp;&nbsp;', $retVal);
	// remove news-archive-link
	$retVal = eregi_replace("<div.*</div>", '', $retVal);
	// return
	return $retVal;
}

?>