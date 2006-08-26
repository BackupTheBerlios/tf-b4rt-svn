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
        // damn dirty but does php (< 5) have reflection or something like
        // class-by-name ?
        if ((isset($clientType)) && ($clientType != '')) {
            $clientClass = $clientType;
            $fluxCfg["btclient"] = $clientType;
        } else {
            $clientClass = $fluxCfg["btclient"];
        }
        $classFile = 'inc/classes/RunningTransfer.'.$clientClass.'.php';
        if (is_file($classFile)) {
            include_once($classFile);
            switch ($clientClass) {
                case "tornado":
                    return new RunningTransferTornado($psLine,serialize($fluxCfg));
                break;
                case "transmission":
                    return new RunningTransferTransmission($psLine,serialize($fluxCfg));
                break;
                case "wget":
                    return new RunningTransferWget($psLine,serialize($fluxCfg));
                break;
            }
        }
    }

    //--------------------------------------------------------------------------
    // Initialize the RunningTransfer.
    function Initialize($cfg) {
        $this->cfg = unserialize($cfg);
    }

    //--------------------------------------------------------------------------
    // Function to put the variables into a string for writing to file
    function BuildAdminOutput() {
        $output = "<tr>";
        $output .= "<td><div class=\"tiny\">";
        $output .= $this->transferowner;
        $output .= "</div></td>";
        $output .= "<td><div align=center><div class=\"tiny\" align=\"left\">";
        $output .= str_replace(array(".stat"),"",$this->statFile);
        $output .= "<br>".$this->args."</div></td>";
        $output .= "<td><a href=\"index.php?iid=index&alias_file=".$this->statFile;
        $output .= "&kill=".$this->processId;
        $output .= "&kill_torrent=".urlencode($this->transferFile);
        $output .= "&return=admin\">";
        $output .= "<img src=\"images/kill.gif\" width=16 height=16 title=\""._FORCESTOP."\" border=0></a></td>";
        $output .= "</tr>";
        $output .= "\n";
        return $output;
    }
}


?>