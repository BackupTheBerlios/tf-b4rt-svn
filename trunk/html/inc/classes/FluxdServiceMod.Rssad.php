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

// class for the Fluxd-Service-module Rssad
class FluxdRssad extends FluxdServiceMod
{
	// public fields

	// version
	var $version = "0.2";

	// private fields

	// basedir
	var $_basedir = ".fluxd/rssad/";

	// jobs-delim
	var $_delimJobs = "|";

	// job-delim
	var $_delimJob = "#";

	// =========================================================================
	// public static methods
	// =========================================================================

    /**
     * accessor for singleton
     *
     * @return FluxdRssad
     */
    function getInstance() {
		global $instanceFluxdRssad;
		// initialize if needed
		if (!isset($instanceFluxdRssad))
			FluxdRssad::initialize();
		return $instanceFluxdRssad;
    }

    /**
     * initialize FluxdRssad.
     */
    function initialize() {
    	global $cfg, $instanceFluxdRssad;
    	// create instance
    	if (!isset($instanceFluxdRssad))
    		$instanceFluxdRssad = new FluxdRssad(serialize($cfg));
    }

	/**
	 * getState
	 *
	 * @return state
	 */
    function getState() {
		global $instanceFluxdRssad;
		return (isset($instanceFluxdRssad))
			? $instanceFluxdRssad->state
			: FLUXDMOD_STATE_NULL;
    }

    /**
     * getMessages
     *
     * @return array
     */
    function getMessages() {
		global $instanceFluxdRssad;
		return (isset($instanceFluxdRssad))
			? $instanceFluxdRssad->messages
			: array();
    }

	/**
	 * getModState
	 *
	 * @return state
	 */
	function getModState() {
		global $instanceFluxdRssad;
		return (isset($instanceFluxdRssad))
			? $instanceFluxdRssad->modstate
			: FLUXDMOD_STATE_NULL;
	}

    /**
     * isRunning
     *
     * @return boolean
     */
    function isRunning() {
		global $instanceFluxdRssad;
		return (isset($instanceFluxdRssad))
			? ($instanceFluxdRssad->modstate == FLUXDMOD_STATE_RUNNING)
			: false;
    }

	// =========================================================================
	// ctor
	// =========================================================================

    /**
     * ctor
     */
    function FluxdRssad($cfg) {
        $this->moduleName = "Rssad";
		// initialize
        $this->instance_initialize($cfg);
        // set modstate if mod enabled
        if ($this->_cfg["fluxd_Rssad_enabled"] == 1)
        	$this->modstate = $this->instance_getModState();
         // check our base-dir
        if (!(checkDirectory($this->_cfg["path"].$this->_basedir))) {
            $this->messages = "Rssad base-dir ".$this->_basedir." error.";
            $this->state = FLUXDMOD_STATE_ERROR;
        }
    }

	// =========================================================================
	// public methods
	// =========================================================================

	/**
	 * check if filter exists
	 *
	 * @param $filtername
	 * @return boolean
	 */
	function filterExists($filtername) {
		// filter-file
		$file = $this->_cfg["path"].$this->_basedir.$filtername.".dat";
		// return
		return file_exists($file);
	}

	/**
	 * checks if filter-id is a valid filter-id
	 *
	 * @param $id
	 * @param $new
	 * @param boolean
	 */
	function filterIdCheck($id, $new = false) {
		// sanity-checks
		if (strpos(urldecode($id), "/") !== false)
			return false;
		if (preg_match("/\\\/", urldecode($id)))
			return false;
		if (preg_match("/\.\./", urldecode($id)))
			return false;
		// check id
		if (!$new)
			return $this->filterExists($id);
		// looks ok
		return true;
	}

	/**
	 * get filter-list
	 *
	 * @return filter-list as array or false on error / no files
	 */
	function filterGetList() {
		$dirFilter = $this->_cfg["path"].$this->_basedir;
		if (is_dir($dirFilter)) {
			$dirHandle = false;
			$dirHandle = @opendir($dirFilter);
			if ($dirHandle !== false) {
				$retVal = array();
				while (false !== ($file = @readdir($dirHandle))) {
					if ((strlen($file) > 4) && ((substr($file, -4)) == ".dat"))
						array_push($retVal, substr($file, 0, -4));
				}
				@closedir($dirHandle);
				return $retVal;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * get filter-content
	 *
	 * @param $filtername
	 * @return filter as string or false on error / no files
	 */
	function filterGetContent($filtername) {
		// filter-file
		$file = $this->_cfg["path"].$this->_basedir.$filtername.".dat";
		// check
		if (!(file_exists($file)))
			return false;
		// open
		$handle = false;
		$handle = @fopen($file, "r");
		if (!$handle) {
			$this->messages = "cannot open ".$file.".";
			AuditAction($this->_cfg["constants"]["fluxd"], "Rssad Filter Load-Error : ".$this->messages);
			return false;
		}
		$data = "";
		while (!@feof($handle))
			$data .= @fgets($handle, 8192);
		@fclose ($handle);
		return $data;
	}

	/**
	 * saves a filter
	 *
	 * @param $filtername
	 * @param $content
	 * @return boolean
	 */
	function filterSave($filtername, $content) {
		// filter-file
		$file = $this->_cfg["path"].$this->_basedir.$filtername.".dat";
		$handle = false;
		$handle = @fopen($file, "w");
		if (!$handle) {
			$this->messages = "cannot open ".$file." for writing.";
			AuditAction($this->_cfg["constants"]["fluxd"], "Rssad Filter Save-Error : ".$this->messages);
			return false;
		}
        $result = @fwrite($handle, str_replace("\r\n", "\n", $content));
		@fclose($handle);
		if ($result === false) {
			$this->messages = "cannot write content to ".$file.".";
			AuditAction($this->_cfg["constants"]["fluxd"], "Rssad Filter Save-Error : ".$this->messages);
			return false;
		}
		// log
		AuditAction($this->_cfg["constants"]["fluxd"], "Rssad Filter Saved : ".$filtername);
		// return
		return true;
	}

	/**
	 * deletes a filter
	 *
	 * @param $filtername
	 * @return boolean
	 */
	function filterDelete($filtername) {
		$extAry = array('.dat', '.hist', '.log');
		// count files
		$fileCount = 0;
		for ($i = 0; $i < 3; $i++) {
			$file = $this->_cfg["path"].$this->_basedir.$filtername.$extAry[$i];
			if (file_exists($file))
				$fileCount++;
		}
		// delete files
		$deleted = 0;
		for ($i = 0; $i < 3; $i++) {
			$file = $this->_cfg["path"].$this->_basedir.$filtername.$extAry[$i];
			if (file_exists($file)) {
				@unlink($file);
				if (!(file_exists($file)))
					$deleted++;
			}
		}
		if ($fileCount == $deleted) {
			// log + return
			AuditAction($this->_cfg["constants"]["fluxd"], "Rssad Filter Deleted : ".$filtername." (".$deleted."/".$fileCount.")");
			return true;
		} else {
			// log + return
			AuditAction($this->_cfg["constants"]["fluxd"], "Rssad Filter Delete Error : ".$filtername." (".$deleted."/".$fileCount.")");
			return false;
		}
	}


	/**
	 * get job-list
	 *
	 * @return job-list as array or false on error / no files
	 */
	function jobsGetList() {
		// job1|job2|job3
		// savedir#url#filtername
		if ((isset($this->_cfg["fluxd_Rssad_jobs"])) && (strlen($this->_cfg["fluxd_Rssad_jobs"]) > 0)) {
			$joblist = array();
			$jobs = explode($this->_delimJobs, trim($this->_cfg["fluxd_Rssad_jobs"]));
			if (count($jobs) > 0) {
				foreach ($jobs as $job) {
					$jobAry = explode($this->_delimJob, trim($job));
					$savedir = trim(array_shift($jobAry));
					$url = trim(array_shift($jobAry));
					$filtername = trim(array_shift($jobAry));
					if ((strlen($savedir) > 0) && (strlen($url) > 0) && (strlen($filtername) > 0)) {
						array_push($joblist, array(
							'savedir' => $savedir,
							'url' => $url,
							'filtername' => $filtername
							)
						);
					}
				}
				return $joblist;
			}
		}
		return false;
	}

	/**
	 * updates jobs
	 *
	 * @param $content
	 * @return boolean
	 */
	function jobsUpdate($content) {
		$jobsSane = array();
		$jobs = explode($this->_delimJobs, trim($content));
		if (($jobs !== false) && (count($jobs) > 0)) {
			while (count($jobs) > 0) {
				$job = array_shift($jobs);
				$jobAry = explode($this->_delimJob, trim($job));
				$savedir = trim(array_shift($jobAry));
				$url = trim(array_shift($jobAry));
				$filtername = trim(array_shift($jobAry));
				if ((strlen($savedir) > 0) && (strlen($url) > 0) && (strlen($filtername) > 0))
					array_push($jobsSane, array(
						'savedir' => trim(checkDirPathString($savedir)),
						'url' => $url,
						'filtername' => $filtername
						)
					);
			}
			$jobsString = "";
			$resultCount = count($jobsSane);
			for ($i = 0; $i < $resultCount; $i++) {
				$jobsString .= $jobsSane[$i]["savedir"].$this->_delimJob;
				$jobsString .= $jobsSane[$i]["url"].$this->_delimJob;
				$jobsString .= $jobsSane[$i]["filtername"];
				if ($i < ($resultCount - 1))
					$jobsString .= $this->_delimJobs;
			}
			// update setting
			updateSetting("tf_settings", "fluxd_Rssad_jobs", $jobsString);
			// log
			AuditAction($this->_cfg["constants"]["fluxd"], "Rssad Jobs Saved : \n".$jobsString);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * get jobs-content
	 *
	 * @param $jobnumber
	 * @return job as array or false on error
	 */
	function jobGetContent($jobnumber) {
		$jobInt = (int) $jobnumber;
		if ($jobInt > 0) {
			$jobs = $this->jobsGetList();
			if (($jobs !== false) && (count($jobs) > ($jobInt - 1)))
				return $jobs[$jobInt - 1];
			else
				return false;
		} else {
			return false;
		}
	}

	/**
	 * adds a job
	 *
	 * @param $jobNumber
	 * @param $savedir
	 * @param $url
	 * @param $filtername
	 * @return boolean
	 */
	function jobAdd($savedir, $url, $filtername, $checkdir = false) {
		if ((strlen($savedir) > 0) && (strlen($url) > 0) && (strlen($filtername) > 0)) {
			$jobsString = "";
			$jobs = $this->jobsGetList();
			if (($jobs !== false) && (count($jobs) > 0)) {
				foreach ($jobs as $job) {
					$jobsString .= $job["savedir"].$this->_delimJob;
					$jobsString .= $job["url"].$this->_delimJob;
					$jobsString .= $job["filtername"];
					$jobsString .= $this->_delimJobs;
				}
			}
			$jobsString .= trim(checkDirPathString($savedir)).$this->_delimJob;
			$jobsString .= $url.$this->_delimJob;
			$jobsString .= $filtername;
			// check dir
			if ($checkdir) {
				$check = checkDirectory($savedir);
				if (!$check)
					$this->messages = "dir ".$savedir." does not exist and could not be created.";
			} else {
				$check = true;
			}
			// update setting
			return ($check && $this->jobsUpdate($jobsString));
		} else {
			return false;
		}
	}

	/**
	 * updates a single job
	 *
	 * @param $jobNumber
	 * @param $savedir
	 * @param $url
	 * @param $filtername
	 * @return boolean
	 */
	function jobUpdate($jobNumber, $savedir, $url, $filtername, $checkdir = false) {
		if (($jobNumber > 0) && (strlen($savedir) > 0) && (strlen($url) > 0) && (strlen($filtername) > 0)) {
			$jobs = $this->jobsGetList();
			if (($jobs !== false) && (count($jobs) > 0)) {
				$result = array();
				$idx = 1;
				while (count($jobs) > 0) {
					$job = array_shift($jobs);
					if ($idx != $jobNumber)
						array_push($result, $job);
					else
						array_push($result, array(
							'savedir' => trim(checkDirPathString($savedir)),
							'url' => $url,
							'filtername' => $filtername
							)
						);
					$idx++;
				}
				$jobsString = "";
				$resultCount = count($result);
				for ($i = 0; $i < $resultCount; $i++) {
					$jobsString .= $result[$i]["savedir"].$this->_delimJob;
					$jobsString .= $result[$i]["url"].$this->_delimJob;
					$jobsString .= $result[$i]["filtername"];
					if ($i < ($resultCount - 1))
						$jobsString .= $this->_delimJobs;
				}
				// check dir
				if ($checkdir) {
					$check = checkDirectory($savedir);
					if (!$check)
						$this->messages = "dir ".$savedir." does not exist and could not be created.";
				} else {
					$check = true;
				}
				// update setting
				return ($check && $this->jobsUpdate($jobsString));
			}
			return false;
		} else {
			return false;
		}
	}

	/**
	 * deletes a single job
	 *
	 * @param $jobNumber
	 * @return boolean
	 */
	function jobDelete($jobNumber) {
		if ($jobNumber > 0) {
			$jobs = $this->jobsGetList();
			if (($jobs !== false) && (count($jobs) > 0)) {
				$result = array();
				$idx = 1;
				while (count($jobs) > 0) {
					$job = array_shift($jobs);
					if ($idx != $jobNumber)
						array_push($result, $job);
					$idx++;
				}
				$jobsString = "";
				$resultCount = count($result);
				for ($i = 0; $i < $resultCount; $i++) {
					$jobsString .= $result[$i]["savedir"].$this->_delimJob;
					$jobsString .= $result[$i]["url"].$this->_delimJob;
					$jobsString .= $result[$i]["filtername"];
					if ($i < ($resultCount - 1))
						$jobsString .= $this->_delimJobs;
				}
				// update setting
				return $this->jobsUpdate($jobsString);
			}
			return false;
		} else {
			return false;
		}
	}

}

?>