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
 * AliasFile
 */
class AliasFile
{
    // public fields

    // file
    var $theFile;

    // af-props
    var $running = "1";
    var $percent_done = "0.0";
    var $time_left = "";
    var $down_speed = "";
    var $up_speed = "";
    var $sharing = "";
    var $transferowner = "";
    var $seeds = "";
    var $peers = "";
    var $seedlimit = "";
    var $uptotal = "";
    var $downtotal = "";
    var $size = "";
    var $errors = array();

	// =========================================================================
	// public static methods
	// =========================================================================

    /**
     * factory
     *
     * @param $aliasname
     * @param $user
     * @return AliasFile
     */
    function getInstance($aliasname, $user = '') {
        return new AliasFile($aliasname, $user);
    }

	// =========================================================================
	// ctor
	// =========================================================================

    /**
     * ctor
     *
     * @param $aliasname
     * @param $user
     * @return AliasFile
     */
    function AliasFile($aliasname, $user = '') {
    	global $cfg;
    	// file
    	$this->theFile = $cfg["transfer_file_path"].$aliasname;
        // set user
        if ($user != '')
            $this->transferowner = $user;
        // load file
        if (@file_exists($this->theFile)) {
            // read the alias file
            $this->errors = @file($this->theFile);
            $this->errors = @array_map('rtrim', $this->errors);
            $this->running = @array_shift($this->errors);
            $this->percent_done = @array_shift($this->errors);
            $this->time_left = @array_shift($this->errors);
            $this->down_speed = @array_shift($this->errors);
            $this->up_speed = @array_shift($this->errors);
            $this->transferowner = @array_shift($this->errors);
            $this->seeds = @array_shift($this->errors);
            $this->peers = @array_shift($this->errors);
            $this->sharing = @array_shift($this->errors);
            $this->seedlimit = @array_shift($this->errors);
            $this->uptotal = @array_shift($this->errors);
            $this->downtotal = @array_shift($this->errors);
            $this->size = @array_shift($this->errors);
        }
    }

	// =========================================================================
	// public methods
	// =========================================================================

    /**
     * call this on start
     *
     * @return boolean
     */
    function start() {
        // Reset all the var to new state (all but transferowner)
        $this->running = "1";
        $this->percent_done = "0.0";
        $this->time_left = "Starting...";
        $this->down_speed = "";
        $this->up_speed = "";
        $this->sharing = "";
        $this->seeds = "";
        $this->peers = "";
        $this->seedlimit = "";
        $this->uptotal = "";
        $this->downtotal = "";
        $this->errors = array();
        // Write to file
        return $this->write();
    }

    /**
     * call this on enqueue
     *
     * @return boolean
     */
    function queue() {
        // Reset all the var to new state (all but transferowner)
        $this->running = "3";
        $this->time_left = "Waiting...";
        $this->down_speed = "";
        $this->up_speed = "";
        $this->seeds = "";
        $this->peers = "";
        $this->uptotal = "";
        $this->downtotal = "";
        $this->errors = array();
        // Write to file
        return $this->write();
    }

    /**
     * Common write Method
     *
     * @return boolean
     */
    function write() {
		// content
        $content  = $this->running."\n";
        $content .= $this->percent_done."\n";
        $content .= $this->time_left."\n";
        $content .= $this->down_speed."\n";
        $content .= $this->up_speed."\n";
        $content .= $this->transferowner."\n";
        $content .= $this->seeds."\n";
        $content .= $this->peers."\n";
        $content .= $this->sharing."\n";
        $content .= $this->seedlimit."\n";
        $content .= $this->uptotal."\n";
        $content .= $this->downtotal."\n";
        $content .= $this->size;
        // errors
        $errCtr = count($this->errors);
        if ($errCtr > 0) {
			for ($i = 0; $i < $errCtr; $ii) {
				if ($this->errors[$i] != "") {
					$output .= ($i == 0)
						? "\n".$this->errors[$i]."\n"
						: $this->errors[$i]."\n";
				}
			}
        }
		// write file
		if ($handle = @fopen($this->theFile, "w")) {
	        $resultSuccess = (@fwrite($handle, $content) !== false);
			@fclose($handle);
			return $resultSuccess;
		}
		return false;
    }

}

?>