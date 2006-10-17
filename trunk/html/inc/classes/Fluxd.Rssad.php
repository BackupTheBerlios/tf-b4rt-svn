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
	 * checks if filter-id is a valid filter-file
	 *
	 * @param $param
	 * @param boolean
	 */
	function filterParamCheck($param) {
		// sanity-checks
		if (strpos(urldecode($param), "/") !== false)
			return false;		
		if (preg_match("/\\\/", urldecode($param)))
			return false;
		if (preg_match("/\.\./", urldecode($param)))
			return false;
		// check id
		$fileList = getFilterList();
		if ($fileList !== false) {
			if (in_array($param, $fileList))
				return true;
			else
				return false;
		} else {
			return false;
		}
		return false;
	}
	
	/**
	 * get filter-list
	 *
	 * @return filter-list as error or false on error / no files
	 */
	function getFilterList() {
		$dirBackup = $this->cfg["path"].$this->basedir;
		if (file_exists($dirBackup)) {
			if ($dirHandle = opendir($dirBackup)) {
				$retVal = array();
				while (false !== ($file = readdir($dirHandle))) {
					if ((strlen($file) > 4) && ((substr($file, -4)) == ".dat"))
						array_push($retVal, substr($file, 0, -4));
				}
				closedir($dirHandle);
				return $retVal;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	/**
	 * deletes a filter
	 *
	 * @param $filename the file with the filter
	 */
	function filterDelete($filename) {
		$backupFile = $this->cfg["path"].$this->basedir.$filename;
		@unlink($backupFile);
		AuditAction($cfg["constants"]["admin"], "fluxd Rssad Filter Deleted : ".$filename);
	}

}

?>