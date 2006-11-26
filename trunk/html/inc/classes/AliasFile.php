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
    // common fields
    var $version = "";
    // the file
    var $theFile;
    // File Properties
    var $running = "";
    var $percent_done = "";
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
    // config-array
    var $cfg = array();

    //--------------------------------------------------------------------------
    // ctor
    function AliasFile() {
        die('base class -- dont do this');
    }

    //--------------------------------------------------------------------------
    // factory
    /**
     * get AliasFile-instance
     *
     * @param $aliasname name of the stat-file
     * @param $user the user
     * @param $fluxCfg torrent-flux config-array
     * @param $clientType client-type
     * @return $aliasFileInstance AliasFile-instance
     */
    function getAliasFileInstance($aliasname, $user = "", $fluxCfg, $clientType = '') {
    	// check if aliasname is valid
    	if (!preg_match('/^[a-zA-Z0-9._]+(stat)$/', $aliasname)) {
    		AuditAction($fluxCfg["constants"]["error"], "Invalid AliasFile : ".$fluxCfg["user"]." tried to access ".$aliasname);
    		global $argv;
    		if (isset($argv))
    			die("Invalid AliasFile : ".$aliasname);
    		else
    			showErrorPage("Invalid AliasFile : <br>".htmlentities($aliasname, ENT_QUOTES));
    	}
    	// alias-file-path
    	$inFile = $fluxCfg["transfer_file_path"].$aliasname;
        // damn dirty but does php (< 5) have reflection or something like
        // class-by-name ?
        if ((isset($clientType)) && ($clientType != '')) {
            $clientClass = $clientType;
            $fluxCfg["btclient"] = $clientType;
        } else {
            $clientClass = $fluxCfg["btclient"];
        }
        $classFile = 'inc/classes/AliasFile.'.$clientClass.'.php';
        if (is_file($classFile)) {
            require_once($classFile);
            switch ($clientClass) {
                case "tornado":
                    return new AliasFileTornado($inFile, $user, serialize($fluxCfg));
                	break;
                case "transmission":
                    return new AliasFileTransmission($inFile, $user, serialize($fluxCfg));
                	break;
                case "mainline":
                    return new AliasFileMainline($inFile, $user, serialize($fluxCfg));
                	break;
                case "wget":
                    return new AliasFileWget($inFile, $user, serialize($fluxCfg));
                	break;
            }
        }
    }

    //--------------------------------------------------------------------------
    // initialize the AliasFile.
    function initialize($cfg) {
        $this->cfg = unserialize($cfg);
    }

    //--------------------------------------------------------------------------
    // abstract method : StartTransferFile
    // Call this when wanting to create a new alias and/or starting it
    function StartTransferFile() { return; }

    //--------------------------------------------------------------------------
    // abstract method : QueueTransferFile
    // Call this when wanting to create a new alias and/or starting it
    function QueueTransferFile() { return; }

    //--------------------------------------------------------------------------
    // abstract method : WriteFile
    // Common WriteFile Method
    function WriteFile() { return; }

    //--------------------------------------------------------------------------
    // abstract method : BuildOutput
    // Private Function to put the variables into a string for writing to file
    function BuildOutput() { return; }

    //--------------------------------------------------------------------------
    // abstract method : GetRealDownloadTotal
    // Public Function to display real total download in MB
    function GetRealDownloadTotal() { return; }
}

?>