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

// require SimpleHTTP
require_once("inc/classes/SimpleHTTP.php");

// require lastRSS
require_once("inc/classes/lastRSS.php");

/**
 * Rssd
 */
class Rssd
{
	// fields

	// version
    var $version = "0.1";

    // config-array
    var $cfg = array();

    // messages-string
    var $messages = "";

    // state
    //  0 : not initialized
    //  1 : initialized
    //  2 : done
    // -1 : error
    var $state = 0;

    // mode
    // 1 : cli
    // 2 : web
    var $mode = 0;

	// job-vars
	var $fileFilters = "";
	var $fileHistory = "";
	var $dirSave = "";
	var $urlRSS = "";

	// filters
	var $filters = array();

	// history
	var $history = array();
	var $historyNew = array();

	// data
	var $data = array();

	// saved files
	var $filesSaved = array();

	// lastRSS-instance
	var $rss;

    // SimpleHTTP-instance
	var $simpleHTTP;

    // factory + ctor

    /**
     * factory
     *
     * @param $cfg
     * @return Rssd
     */
    function getInstance($cfg) {
    	return new Rssd(serialize($cfg));
    }

    /**
     * do not use direct, use the factory-method !
     *
     * @param $cfg (serialized)
     * @return Rssd
     */
    function Rssd($cfg) {
        $this->cfg = unserialize($cfg);
        if (empty($this->cfg)) {
        	$this->state = -1;
            $this->messages = "Config not passed";
            return false;
        }
        // cli/web
		global $argv;
		if (isset($argv)) {
			$this->mode = 1;
		} else
			$this->mode = 2;
        // init lastRSS-instance
		$this->rss = lastRSS::getInstance($this->cfg);
		$this->rss->cache_dir = '';
		$this->rss->stripHTML = false;
		// init SimpleHTTP-instance
		$this->simpleHTTP = SimpleHTTP::getInstance($this->cfg);
        // state
        $this->state = 1;
    }

    // public meths

	/**
	 * process a feed
	 *
	 * @param $tdir
	 * @param $filter
	 * @param $history
	 * @param $url
	 * @return boolean
	 */
    function processFeed($sdir, $filter, $history, $url) {
   		// validate
   		if (!checkDirectory($sdir, 0777)) {
            $this->state = -1;
            $this->messages = "Save-Dir ".$sdir." not valid.";
            $this->printError($this->messages."\n");
            return false;
   		}
   		if (!is_file($filter)) {
            $this->state = -1;
            $this->messages = "Filter-File ".$filter." not valid.";
            $this->printError($this->messages."\n");
            return false;
   		}
		// print
		$this->printMessage("Processing feed ".$url." ...\n");
    	// set vars
    	$this->dirSave = checkDirPathString($sdir);
    	$this->fileFilters = $filter;
    	$this->fileHistory = $history;
    	$this->urlRSS = $url;
    	$this->filters = array();
    	$this->history = array();
    	$this->historyNew = array();
    	$this->data = array();
    	$this->filesSaved = array();
		// load filters
		if (!$this->loadFilters())
			return false;
		// load history
		if (!$this->loadHistory())
			return false;
		// load data
		if (!$this->loadData())
			return false;
		// something to do ?
		if ($this->data['items_count'] <= 0) { // no
			// state
			$this->state = 2;
			return true;
		}
		// process data
		if (!$this->processData())
			return false;
		// update history
		if (!$this->updateHistory())
			return false;
		// state
		$this->state = 2;
		// print
		$this->printMessage("feed processed. downloaded and saved ".count($this->filesSaved)." torrents.\n");
		// return
		return true;
    }

    // private meths

    /**
     * load filters
     * @return boolean
     */
	function loadFilters() {
		$fifi = file($this->fileFilters);
		$this->filters = array_map('rtrim', $fifi);
		return true;
    }

    /**
     * load history
     * @return boolean
     */
	function loadHistory() {
		if (is_file($this->fileHistory)) {
			$fihi = file($this->fileHistory);
			$this->history = array_map('rtrim', $fihi);
		} else {
			$this->history = array();
		}
		return true;
    }

    /**
     * load data
     * @return boolean
     */
	function loadData() {
		if ($this->data = $this->rss->Get($this->urlRSS)) {
			return (empty($this->data) === false);
		} else {
			$this->state = -1;
			$this->messages = "Problem getting feed-data from ".$this->urlRSS;
            $this->printError($this->messages."\n");
            return false;
		}
    }

    /**
     * process data
     * @return boolean
     */
	function processData() {
		$itemCount = count($this->data["items"]);
		// filter-loop
		foreach ($this->filters as $filter) {
			// print filtername
			$this->printMessage("***** ".$filter." *****\n");
			// item-loop
			for ($i = 0; $i < $itemCount; $i++) {
				// skip feed items without a link or title:
				if (!isset($this->data["items"][$i]["link"]) || empty($this->data["items"][$i]["link"]))
					continue;
				if (!isset($this->data["items"][$i]["title"]) || empty($this->data["items"][$i]["title"]))
					continue;
				// local vars
				$link = $this->data["items"][$i]["link"];
				$title = $this->data["items"][$i]["title"];
				// check if we have a match
				if (preg_match('/'.$filter.'/i', $title)) {
					// if not in history, process it
					if (!in_array($title, $this->history)) {
						// print
						$this->printMessage("new match for filter '".$filter."' : ".$title."\n");
						// download and save
						if ($this->saveTorrent($link, $title) === true) {
							// add to history
							array_push($this->historyNew, $title);
						}
					}
				}
			}
		}
		// return
		return true;
    }

    /**
     * update history
     * @return boolean
     */
	function updateHistory() {
		if (count($this->historyNew) > 0) {
			// write file
			$handle = false;
			$handle = @fopen($this->fileHistory, "a");
			if (!$handle) {
				$this->state = -1;
				$this->messages = "cannot open history ".$this->fileHistory." for writing.";
				AuditAction($this->cfg["constants"]["error"], "Rssd updateHistory-Error : ".$this->messages);
				$this->printError($this->messages."\n");
				return false;
			}
	        $result = @fwrite($handle, implode("\n", $this->historyNew)."\n");
			@fclose($handle);
			if ($result === false) {
				$this->state = -1;
				$this->messages = "cannot write content to history ".$this->fileHistory.".";
				AuditAction($this->cfg["constants"]["error"], "Rssd updateHistory-Error : ".$this->messages);
				$this->printError($this->messages."\n");
				return false;
			}
		}
		// return
		return true;
    }

    /**
     * download and save a torrent-file
     * @return boolean
     */
	function saveTorrent($url, $title) {
		$content = $this->simpleHTTP->getTorrent($url);
		if ($this->simpleHTTP->state == 2) {
			// filename
			$filename = $this->simpleHTTP->filename;
			if (($filename != "") && ($filename != "unknown.torrent") && (strpos($filename, ".torrent") !== false))
				$filename = cleanFileName($filename);
			else
				$filename = cleanFileName($title);
			// file
			$file = $this->dirSave.$filename;
			// check if file already exists
			if (is_file($file)) {
				// Error
				$this->messages = "the file ".$file." already exists in ".$this->dirSave;
				AuditAction($this->cfg["constants"]["error"], "Rssd downloadMetafile-Error : ".$this->messages);
				$this->printError($this->messages."\n");
				return false;
			}
			// write file
			$handle = false;
			$handle = @fopen($file, "w");
			if (!$handle) {
				$this->messages = "cannot open ".$file." for writing.";
				AuditAction($this->cfg["constants"]["error"], "Rssd downloadMetafile-Error : ".$this->messages);
				$this->printError($this->messages."\n");
				return false;
			}
	        $result = @fwrite($handle, $content);
			@fclose($handle);
			if ($result === false) {
				$this->messages = "cannot write content to ".$file.".";
				AuditAction($this->cfg["constants"]["error"], "Rssd downloadMetafile-Error : ".$this->messages);
				$this->printError($this->messages."\n");
				return false;
			}
			// add to file-array
			array_push($this->filesSaved, array(
				'url' => $url,
				'title' => $title,
				'filename' => $filename,
				'file' => $file
				)
			);
			// print
			$this->printMessage("torrent saved : \n url: ".$url."\n file: ".$file."\n");
			// return
			return true;
		} else {
			// last op was not ok
			$this->printError("could not download torrent with title ".$title." from url ".$url." : \n".implode("\n", $this->simpleHTTP->messages));
			return false;
		}
    }

    /**
     * print message
     *
     * @param $message
     */
	function printMessage($message) {
        // only in cli-mode
		if ($this->mode == 1)
			@fwrite(STDOUT, @date("[Y/m/d - H:i:s]")."[Rssd] ".$message);
    }

    /**
     * print error
     *
     * @param $message
     */
	function printError($message) {
        // only in cli-mode
		if ($this->mode == 1)
			@fwrite(STDERR, @date("[Y/m/d - H:i:s]")."[Rssd] ".$message);
    }

}

?>