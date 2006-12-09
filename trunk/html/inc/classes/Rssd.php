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

	// job-vars
	var $pathFilters = "";
	var $pathHistory = "";
	var $pathSave = "";
	var $urlRSS = "";

	// filters
	var $filters = array();

	// history
	var $history = array();
	var $historyNew = array();

	// data
	var $data = array();

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
            $this->messages = "Config not passed";
            $this->state = -1;
            return false;
        }
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
   			$this->messages = "Save-Dir ".$sdir." not valid.";
            $this->state = -1;
            return false;
   		}
   		if (!is_file($filter)) {
   			$this->messages = "Filter-File ".$filter." not valid.";
            $this->state = -1;
            return false;
   		}
    	// job-vars
    	$this->pathSave = $sdir;
    	$this->pathFilters = $filter;
    	$this->pathHistory = $history;
    	$this->urlRSS = $url;
		// load filters
		if (!$this->loadFilters())
			return false;
		// load history
		if (!$this->loadHistory())
			return false;
		// load data
		if (!$this->loadData())
			return false;
		// process data
		if (!$this->processData())
			return false;
		// update history
		if (!$this->updateHistory())
			return false;
		// state
		$this->state = 2;
		// return
		return true;
    }


    // private meths

    /**
     * load filters
     * @return boolean
     */
	function loadFilters() {
		$this->filters = file($this->pathFilters);
		return true;
    }

    /**
     * load history
     * @return boolean
     */
	function loadHistory() {
		if (is_file($this->pathHistory)) {
            $this->history = file($this->pathHistory);
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
			return true;
		} else {
			$this->messages = "Problem getting feed-data from ".$this->urlRSS;
            $this->state = -1;
            return false;
		}
    }

    /**
     * process data
     * @return boolean
     */
	function processData() {
		return true;
    }

    /**
     * update history
     * @return boolean
     */
	function updateHistory() {
		return true;
    }

    /**
     * download a metafile
     * @return boolean
     */
	function downloadMetafile($url) {
		$content = $this->simpleHTTP->getTorrent($url);
		if ($this->simpleHTTP->state == 2) {
			// write file
			$file = $pathSave.cleanFileName($this->simpleHTTP->filename);
			$handle = false;
			$handle = @fopen($file, "w");
			if (!$handle) {
				$this->messages = "cannot open ".$file." for writing.";
				AuditAction($this->cfg["constants"]["error"], "Rssd File-Write-Error : ".$this->messages);
				return false;
			}
	        $result = @fwrite($handle, $content);
			@fclose($handle);
			if ($result === false) {
				$this->messages = "cannot write content to ".$handle.".";
				AuditAction($this->cfg["constants"]["error"], "Rssd File-Write-Error : ".$this->messages);
				return false;
			}
			// return
			return true;
		} else {
			// last op was not ok
			// TODO :
			return false;
		}
    }

}

?>