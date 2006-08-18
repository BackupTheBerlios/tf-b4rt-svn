<?php

/*************************************************************
*  TorrentFlux PHP Torrent Manager
*  www.torrentflux.com
**************************************************************/
/*
    This file is part of TorrentFlux.

    TorrentFlux is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    TorrentFlux is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with TorrentFlux; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// base class RunningTorrent
class RunningTorrent
{
    // common fields
    var $version = "";
    // running torrent fields
    var $statFile = "";
    var $torrentFile = "";
    var $filePath = "";
    var $torrentOwner = "";
    var $processId = "";
    var $args = "";
    // config-array
    var $cfg = array();

    //--------------------------------------------------------------------------
    // ctor
    function RunningTorrent() {
        die('base class -- dont do this');
    }

    //--------------------------------------------------------------------------
    // factory
    /**
     * get RunningTorrent-instance
     *
     * @param $psLine ps-line
     * @param $fluxCfg torrent-flux config-array
     * @param $clientType client-type
     * @return $runningTorrentInstance RunningTorrent-instance
     */
    function getRunningTorrentInstance($psLine, $fluxCfg, $clientType = '') {
        // damn dirty but does php (< 5) have reflection or something like
        // class-by-name ?
        if ((isset($clientType)) && ($clientType != '')) {
            $clientClass = $clientType;
            $fluxCfg["btclient"] = $clientType;
        } else {
            $clientClass = $fluxCfg["btclient"];
        }
        $classFile = 'RunningTorrent.'.$clientClass.'.php';
        if (is_file($classFile)) {
            include_once($classFile);
            switch ($clientClass) {
                case "tornado":
                    return new RunningTorrentTornado($psLine,serialize($fluxCfg));
                break;
                case "transmission":
                    return new RunningTorrentTransmission($psLine,serialize($fluxCfg));
                break;
            }
        }
    }

    //--------------------------------------------------------------------------
    // Initialize the RunningTorrent.
    function Initialize($cfg) {
        $this->cfg = unserialize($cfg);
    }

    //--------------------------------------------------------------------------
    // Function to put the variables into a string for writing to file
    function BuildAdminOutput() {
        $output = "<tr>";
        $output .= "<td><div class=\"tiny\">";
        $output .= $this->torrentOwner;
        $output .= "</div></td>";
        $output .= "<td><div align=center><div class=\"tiny\" align=\"left\">";
        $output .= str_replace(array(".stat"),"",$this->statFile);
        $output .= "<br>".$this->args."</div></td>";
        $output .= "<td><a href=\"index.php?alias_file=".$this->statFile;
        $output .= "&kill=".$this->processId;
        $output .= "&kill_torrent=".urlencode($this->torrentFile);
        $output .= "&return=admin\">";
        $output .= "<img src=\"images/kill.gif\" width=16 height=16 title=\""._FORCESTOP."\" border=0></a></td>";
        $output .= "</tr>";
        $output .= "\n";
        return $output;
    }
}


?>