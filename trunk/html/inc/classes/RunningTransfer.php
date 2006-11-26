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

// base class RunningTransfer
class RunningTransfer
{
    // common fields
    var $version = "";
    // running transfer fields
    var $statFile = "";
    var $transferFile = "";
    var $filePath = "";
    var $transferowner = "";
    var $processId = "";
    var $args = "";
    // config-array
    var $cfg = array();

    //--------------------------------------------------------------------------
    // ctor
    function RunningTransfer() {
        die('base class -- dont do this');
    }

    //--------------------------------------------------------------------------
    // factory
    /**
     * get RunningTransfer-instance
     *
     * @param $psLine ps-line
     * @param $fluxCfg torrent-flux config-array
     * @param $clientType client-type
     * @return $runningTorrentInstance RunningTransfer-instance
     */
    function getRunningTransferInstance($psLine, $fluxCfg, $clientType = '') {
    	// create and return object-instance
        if ($clientType != '') {
            $clientClass = $clientType;
            $fluxCfg["btclient"] = $clientType;
        } else {
            $clientClass = $fluxCfg["btclient"];
        }
        $classFile = 'inc/classes/RunningTransfer.'.$clientClass.'.php';
        switch ($clientClass) {
            case "tornado":
            	require_once($classFile);
                return new RunningTransferTornado($psLine,serialize($fluxCfg));
            case "transmission":
            	require_once($classFile);
                return new RunningTransferTransmission($psLine,serialize($fluxCfg));
            case "mainline":
            	require_once($classFile);
                return new RunningTransferMainline($psLine,serialize($fluxCfg));
            case "wget":
            	require_once($classFile);
                return new RunningTransferWget($psLine,serialize($fluxCfg));
            default:
            	AuditAction($fluxCfg["constants"]["error"], "Invalid RunningTransfer-Class : ".$clientClass);
				global $argv;
    			if (isset($argv))
    				die("Invalid RunningTransfer-Class : ".$clientClass);
    			else
    				showErrorPage("Invalid RunningTransfer-Class : <br>".htmlentities($clientClass, ENT_QUOTES));
        }
    }

    //--------------------------------------------------------------------------
    // Initialize the RunningTransfer.
    function Initialize($cfg) {
        $this->cfg = unserialize($cfg);
    }

    //--------------------------------------------------------------------------
    // Function to put the variables into a string for writing to file
    function BuildAdminOutput($theme) {
    	global $cfg;
        $output = "<tr>";
        $output .= "<td><div class=\"tiny\">";
        $output .= $this->transferowner;
        $output .= "</div></td>";
        $output .= "<td><div align=center><div class=\"tiny\" align=\"left\">";
        $output .= str_replace(array(".stat"),"",$this->statFile);
        $output .= "</div></td>";
        $output .= "<td>";
        $output .= "<a href=\"dispatcher.php?action=indexStop";
        $output .= "&transfer=".urlencode($this->transferFile);
        $output .= "&alias_file=".$this->statFile;
        $output .= "&kill=".$this->processId;
        $output .= "&return=admin\">";
        $output .= "<img src=\"themes/".$theme."/images/kill.gif\" width=16 height=16 title=\"".$cfg['_FORCESTOP']."\" border=0></a></td>";
        $output .= "</tr>";
        $output .= "\n";
        return $output;
    }
}

?>