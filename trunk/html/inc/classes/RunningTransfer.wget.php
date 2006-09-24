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


// class RunningTransferTornado for wget-client
class RunningTransferWget extends RunningTransfer
{
    //--------------------------------------------------------------------------
    // ctor
    function RunningTransferWget($psLine,$cfg) {
        // version
        $this->version = array_shift(explode(" ",trim(array_pop(explode(":",'$Revision$')))));
        // init conf
        $this->Initialize($cfg);
        // ps-parse
        if (strlen($psLine) > 0) {
            while (strpos($psLine,"  ") > 0)
                $psLine = str_replace("  ",' ',trim($psLine));
            $arr = split(' ',$psLine);
            $count = count($arr);
            $this->processId = $arr[0];
            $this->args = "";
            $this->transferowner = $arr[($count - 5)];
            $this->filePath = substr($arr[($count - 7)], 0, strrpos($arr[($count - 7)], "/")+1);
            $this->statFile = str_replace($this->filePath,'',$arr[($count - 8)]);
            $this->transferFile = str_replace($this->filePath,'',$arr[($count - 8)]);
        }
    }

    //----------------------------------------------------------------
    // Function to put the variables into a string for writing to file
    function BuildAdminOutput($theme) {
        return parent::BuildAdminOutput($theme);
    }
}

?>