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
        // create and return object-instance
        if ($clientType != '') {
            $clientClass = $clientType;
            $fluxCfg["btclient"] = $clientType;
        } else {
            $clientClass = $fluxCfg["btclient"];
        }
        $classFile = 'inc/classes/AliasFile.'.$clientClass.'.php';
        switch ($clientClass) {
            case "tornado":
            	require_once($classFile);
                return new AliasFileTornado($fluxCfg["transfer_file_path"].$aliasname, $user, serialize($fluxCfg));
            case "transmission":
            	require_once($classFile);
                return new AliasFileTransmission($fluxCfg["transfer_file_path"].$aliasname, $user, serialize($fluxCfg));
            case "mainline":
            	require_once($classFile);
                return new AliasFileMainline($fluxCfg["transfer_file_path"].$aliasname, $user, serialize($fluxCfg));
            case "wget":
            	require_once($classFile);
                return new AliasFileWget($fluxCfg["transfer_file_path"].$aliasname, $user, serialize($fluxCfg));
            default:
            	AuditAction($fluxCfg["constants"]["error"], "Invalid AliasFile-Class : ".$clientClass);
				global $argv;
    			if (isset($argv))
    				die("Invalid AliasFile-Class : ".$clientClass);
    			else
    				showErrorPage("Invalid AliasFile-Class : <br>".htmlentities($clientClass, ENT_QUOTES));
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