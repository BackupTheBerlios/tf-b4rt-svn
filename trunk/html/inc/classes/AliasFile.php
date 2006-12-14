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

// base class AliasFile
class AliasFile
{
    // public fields
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
     * @param $aliasname name of the stat-file
     * @param $user the user
     * @return $aliasFileInstance AliasFile-instance
     */
    function getAliasFileInstance($aliasname, $user = '') {
    	global $cfg;
    	// check if aliasname is valid
    	if (!preg_match('/^[a-zA-Z0-9._-]+(stat)$/', $aliasname)) {
    		AuditAction($cfg["constants"]["error"], "Invalid AliasFile : ".$cfg["user"]." tried to access ".$aliasname);
    		global $argv;
    		if (isset($argv))
    			die("Invalid AliasFile : ".$aliasname);
    		else
    			showErrorPage("Invalid AliasFile : <br>".htmlentities($aliasname, ENT_QUOTES));
    	}
    	// create and return new aliasfile-instance
        return new AliasFile($cfg["transfer_file_path"].$aliasname, $user);
    }

	// =========================================================================
	// ctor
	// =========================================================================

    /**
     * do not use direct, use the factory-method or you bypass security !
     *
     * @param $inFile
     * @param $user
     * @return AliasFile
     */
    function AliasFile($inFile, $user = '') {
        // set user
        if ($user != '')
            $this->transferowner = $user;
        // load file
        if (file_exists($inFile)) {
            // read the alias file
            $this->errors = file($inFile);
            $this->errors = array_map('rtrim', $this->errors);
            $this->running = array_shift($this->errors);
            $this->percent_done = array_shift($this->errors);
            $this->time_left = array_shift($this->errors);
            $this->down_speed = array_shift($this->errors);
            $this->up_speed = array_shift($this->errors);
            $this->transferowner = array_shift($this->errors);
            $this->seeds = array_shift($this->errors);
            $this->peers = array_shift($this->errors);
            $this->sharing = array_shift($this->errors);
            $this->seedlimit = array_shift($this->errors);
            $this->uptotal = array_shift($this->errors);
            $this->downtotal = array_shift($this->errors);
            $this->size = array_shift($this->errors);
        }
    }

    /**
     * Call this when wanting to create a new alias and/or starting it
     *
     * @return boolean
     */
    function StartTransferFile() {
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
        $this->WriteFile();
    }

    /**
     * Call this when wanting to create a new alias and/or starting it
     *
     * @return boolean
     */
    function QueueTransferFile() {
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
        $this->WriteFile();
    }

    /**
     * Common WriteFile Method
     *
     * @return boolean
     */
    function WriteFile() {
        $fw = fopen($this->theFile,"w");
        fwrite($fw, $this->BuildOutput());
        fclose($fw);
    }

    /**
     * Private Function to put the variables into a string for writing to file
     *
     * @return string
     */
    function BuildOutput() {
        $output  = $this->running."\n";
        $output .= $this->percent_done."\n";
        $output .= $this->time_left."\n";
        $output .= $this->down_speed."\n";
        $output .= $this->up_speed."\n";
        $output .= $this->transferowner."\n";
        $output .= $this->seeds."\n";
        $output .= $this->peers."\n";
        $output .= $this->sharing."\n";
        $output .= $this->seedlimit."\n";
        $output .= $this->uptotal."\n";
        $output .= $this->downtotal."\n";
        $output .= $this->size;
        for ($inx = 0; $inx < sizeof($this->errors); $inx++) {
            if ($this->errors[$inx] != "") {
                $output .= "\n".$this->errors[$inx];
            }
        }
        return $output;
    }

    /**
     * Public Function to display real total download in MB
     *
     * @return int
     */
    function GetRealDownloadTotal() {
        return (($this->percent_done * $this->size) / 100) / (1048576);
    }
}

?>