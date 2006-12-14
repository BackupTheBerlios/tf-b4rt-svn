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

// class AliasFile for wget-client
class AliasFileWget extends AliasFile
{
    //--------------------------------------------------------------------------
    // ctor
    function AliasFileWget($inFile,$user="",$cfg) {
		// initialize
        $this->Initialize($cfg);
        // init some vars
        $this->running = "1";
        $this->percent_done = "0.0";
        $this->theFile = $inFile;
        if ($user != "") {
            $this->transferowner = $user;
        }
        if (file_exists($inFile)) {
            // read the alias file
            $this->errors = file($inFile);
            $this->running = @trim(array_shift($this->errors));
            $this->percent_done = @trim(array_shift($this->errors));
            $this->time_left = @trim(array_shift($this->errors));
            $this->down_speed = @trim(array_shift($this->errors));
            $this->up_speed = @trim(array_shift($this->errors));
            $this->transferowner = @trim(array_shift($this->errors));
            $this->seeds = @trim(array_shift($this->errors));
            $this->peers = @trim(array_shift($this->errors));
            $this->sharing = @trim(array_shift($this->errors));
            $this->seedlimit = @trim(array_shift($this->errors));
            $this->uptotal = @trim(array_shift($this->errors));
            $this->downtotal = @trim(array_shift($this->errors));
            $this->size = @trim(array_shift($this->errors));
        }
    }

    //----------------------------------------------------------------
    // Call this when wanting to create a new alias and/or starting it
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

    //----------------------------------------------------------------
    // Call this when wanting to create a new alias and/or starting it
    function QueueTransferFile() {
        // Reset all the var to new state (all but transferowner)
        $this->running = "3";
        $this->time_left = "Waiting...";
        $this->down_speed = "";
        $this->up_speed = "";
        $this->seeds = "";
        $this->peers = "";
        //XFER: uptotal and downltotal must be reset to zero
        $this->uptotal = "";
        $this->downtotal = "";
        $this->errors = array();
        // Write to file
        $this->WriteFile();
    }

    //----------------------------------------------------------------
    // Common WriteFile Method
    function WriteFile() {
        $fw = fopen($this->theFile,"w");
        fwrite($fw, $this->BuildOutput());
        fclose($fw);
    }

    //----------------------------------------------------------------
    // Private Function to put the variables into a string for writing to file
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

    //----------------------------------------------------------------
    // Public Function to display real total download in MB
    function GetRealDownloadTotal() {
        return (($this->percent_done * $this->size)/100)/(1024*1024);
    }
}

?>