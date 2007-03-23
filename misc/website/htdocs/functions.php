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
 * load data of file
 *
 * @param $file the file
 * @return data
 */
function getDataFromFile($file) {
	// check for native php-function
	if (function_exists('file_get_contents'))
		return (file_exists($file))
			? file_get_contents($file)
			: "Could not open file: ".$file;
    // read manual
    if ($fileHandle = @fopen($file,'r')) {
        $data = null;
        while (!@feof($fileHandle))
            $data .= @fgets($fileHandle, 4096);
        @fclose($fileHandle);
	} else {
		$data = "Could not open file: ".$file;
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
    $errorString = "0"."\n"."web-update from your version not possible. please perform manual update.";
    if ($compressed) {
        echo(gzdeflate($errorString, 9));
    } else {
        header("Content-Type: text/plain");
        echo $errorString;
    }
    exit;
}

/**
 * get file-list for old branch
 *
 * @return filelist as string
 */
function getFileListOLD($currentVersion, $remoteVersion) {
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
 * get file-list for new branch
 *
 * @return filelist as string
 */
function getFileListNEW($currentVersion, $remoteVersion) {
	return getDataFromFile("./". _UPDATE_BASEDIR."/".$currentVersion."/".$remoteVersion."/"._UPDATE_DATADIR."/". _UPDATE_HTMLDIR."/"._UPDATE_FILES);
}

/**
 * proxy-log
 */
function logProxy() {
	// ua
	$ua = ((isset($_SERVER['HTTP_USER_AGENT'])) && ($_SERVER['HTTP_USER_AGENT'] != ""))
		? addslashes($_SERVER['HTTP_USER_AGENT'])
		: "unknown";
	// db
	require_once('internal/dbconf.php');
	$db = @mysql_connect($db_host, $db_user, $db_pass);
	if (!isset($db))
		return;
	@mysql_select_db($db_name, $db);
	$result = @mysql_query("SELECT ct FROM tfb4rt_proxystats WHERE ua LIKE '".$ua."'", $db);
	$num_rows = 0;
	$num_rows = @mysql_num_rows($result);
	if ($num_rows > 0) { // update
		$row = @mysql_fetch_row($result);
		$ct = $row[0];
		@mysql_free_result($result);
		$ct++;
		@mysql_query("UPDATE tfb4rt_proxystats SET ct = '".$ct."' WHERE ua LIKE '".$ua."' LIMIT 1", $db);
	} else { // insert
		$ct = 1;
		@mysql_query("INSERT INTO tfb4rt_proxystats (ua,ct) values ( '".$ua."','".$ct."')", $db);
	}
	// close
	@mysql_close($db);
}

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

/**
 * Fetch the list of authors from the current svn webserver AUTHORS file.
 * Parse the list and create an HTML list with obfuscated (via javascript) links
 * to each authors mail address
 *
 * @return string authors
 */
function getAuthors() {
	$authors_html = "";
	$authors = array_slice(preg_split("/\n\n/", getDataFromFile(_FILE_AUTHORS)), 2);
	// $authors array size will be just one entry if the authors file couldn't be fetched for some reason:
	if (count($authors) > 1) {
		$authors_list = "";
		foreach ($authors as $author) {
			if (preg_match("/^\*\s(.*)\s<(.*)>$/", $author, $matches)) {
				$username = trim($matches[1]);
				$mailAd = trim($matches[2]);
				$mailAd = str_replace('@', '[AT]', $mailAd);
				$mailAd = str_replace('.', '[DOT]', $mailAd);
				$authors_list .= "<li>";
				$authors_list .= "<script>printMailLink('".$mailAd."', '".$username."');</script>";
				$authors_list .= "<noscript>".$mailAd."</noscript>";
				$authors_list .= "</li>\n";
			}
		}
		if (strlen($authors_list) > 0)
			$authors_html = "<ul>\n".$authors_list."</ul>\n";
	}
	return $authors_html;
}

/**
 * get list of screenshots
 *
 * @param $string path
 * @return string screenshot
 */
function getScreenshotList($path) {
	$basepath = dirname(__FILE__).'/';
	$spath = $basepath.$path;
	// images
	$images = array();
	if ((@is_dir($spath)) && ($dirHandle = @opendir($spath))) {
		while (false !== ($file = @readdir($dirHandle))) {
			if ((strlen($file) > 4) && (substr($file, -4) == ".png")) {
				$images[$file] = array(
					'file' => $file,
					'path' => $path.$file
				);
			}
		}
		@closedir($dirHandle);
	}
	// content
	$retVal = "";
	if (count($images) > 1) {
		ksort($images);
		$image_list = "";
		foreach ($images as $image) {
			$image_list .= "<li>";
			$image_list .= "<a href=\"".$image['path']."\" target=\"_blank\"";
			$image_list .= "\">";
			$image_list .= $image['file']."</a>";
			$image_list .= "</li>\n";
		}
		if (strlen($image_list) > 0)
			$retVal = "<ul>\n".$image_list."</ul>\n";
	}
	// return
	return $retVal;
}

function cssSwitcher() {
	global $css;

	// valid css sheets:
	$valid_css=array('default', 'new');

	session_start();

	// store css type in session if passed in request:
	isset($_REQUEST["css"]) && !empty($_REQUEST["css"]) && in_array($_REQUEST["css"], $valid_css) && $_SESSION["css"]=$_REQUEST["css"];

	// use css sheet type stored in session if there:
	isset($_SESSION["css"]) && !empty($_SESSION["css"]) && $css=$_SESSION["css"];
}

/**
 * Inserts Google Analytics javascript for tracking and analysis via:
 * https://www.google.com/analytics/
 */
function googleAnalytics(){
?>
<script src="http://www.google-analytics.com/urchin.js" type="text/javascript">
</script>
	<script type="text/javascript">
_uacct = "UA-1343358-1";
urchinTracker();
</script>
<?php
}

?>