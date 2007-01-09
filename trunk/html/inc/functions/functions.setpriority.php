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
 * getFile
 *
 * @param $var
 * @return boolean
 */
function getFile($var) {
	return ($var < 65535);
}

/**
 * setPriority
 *
 * @param $torrent
 */
function setPriority($torrent) {
    global $cfg;
    // we will use this to determine if we should create a prio file.
    // if the user passes all 1's then they want the whole thing.
    // so we don't need to create a prio file.
    // if there is a -1 in the array then they are requesting
    // to skip a file. so we will need to create the prio file.
    $okToCreate = false;
    if (!empty($torrent)) {
        $fileName = $cfg["transfer_file_path"].$torrent.".prio";
        $result = array();
        $files = array();
        if (isset($_REQUEST['files']))
			$files = array_filter($_REQUEST['files'],"getFile");
        // if there are files to get then process and create a prio file.
        if (count($files) > 0) {
            for($i=0;$i<getRequestVar('count');$i++) {
                if (in_array($i,$files)) {
                    array_push($result,1);
                } else {
                    $okToCreate = true;
                    array_push($result,-1);
                }
            }
            if ($okToCreate) {
                $fp = fopen($fileName, "w");
                fwrite($fp,getRequestVar('filecount').",");
                fwrite($fp,implode($result,','));
                fclose($fp);
            } else {
                // No files to skip so must be wanting them all.
                // So we will remove the prio file.
                @unlink($fileName);
            }
        } else {
            // No files selected so must be wanting them all.
            // So we will remove the prio file.
            @unlink($fileName);
        }
    }
}

?>