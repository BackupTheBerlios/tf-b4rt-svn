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
	// basedir
	var $basedir = ".fluxd/rssad/";
	
    /**
     * ctor
     */
    function FluxdRssad($cfg, $fluxd) {
        $this->moduleName = "Rssad";
        $this->version = array_shift(explode(" ",trim(array_pop(explode(":",'$Revision$')))));
        $this->initialize($cfg, $fluxd);
    }

	/**
	 * check if filter exists
	 *
	 * @param $filtername
	 * @return boolean
	 */
	function filterExists($filtername) {
		// filter-file
		$file = $this->cfg["path"].$this->basedir.$filtername.".dat";
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
		$dirFilter = $this->cfg["path"].$this->basedir;
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
		$file = $this->cfg["path"].$this->basedir.$filtername.".dat";
		// check
		if (!(file_exists($file)))
			return false;
		// open
		$handle = false;
		$handle = @fopen($file, "r");
		if (!$handle) {
			$this->messages = "cannot open ".$file.".";
			AuditAction($cfg["constants"]["admin"], "fluxd Rssad Filter Load-Error : ".$this->messages);
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
	 * @param $$content
	 * @return boolean
	 */
	function filterSave($filtername, $content) {
		// filter-file
		$file = $this->cfg["path"].$this->basedir.$filtername.".dat";
		$handle = false;
		$handle = @fopen($file, "w");
		if (!$handle) {
			$this->messages = "cannot open ".$file." for writing.";
			AuditAction($cfg["constants"]["admin"], "fluxd Rssad Filter Save-Error : ".$this->messages);
			return false;
		}
		$result = @fwrite($handle, $content);
		@fclose($handle);
		if ($result === false) {
			$this->messages = "cannot write content to ".$handle.".";
			AuditAction($cfg["constants"]["admin"], "fluxd Rssad Filter Save-Error : ".$this->messages);
			return false;
		}
		// log
		AuditAction($cfg["constants"]["admin"], "fluxd Rssad Filter Saved : ".$filtername);
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
			$file = $this->cfg["path"].$this->basedir.$filtername.$extAry[$i];
			if (file_exists($file))
				$fileCount++;
		}
		// delete files
		$deleted = 0;
		for ($i = 0; $i < 3; $i++) {
			$file = $this->cfg["path"].$this->basedir.$filtername.$extAry[$i];
			if (file_exists($file)) {
				@unlink($file);
				if (!(file_exists($file)))
					$deleted++;
			}
		}
		if ($fileCount == $deleted) {
			// log + return
			AuditAction($cfg["constants"]["admin"], "fluxd Rssad Filter Deleted : ".$filtername." (".$deleted."/".$fileCount.")");
			return true;
		} else {
			// log + return
			AuditAction($cfg["constants"]["admin"], "fluxd Rssad Filter Delete Error : ".$filtername." (".$deleted."/".$fileCount.")");
			return false;			
		}
	}

}

?>