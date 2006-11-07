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


// class RunningTransferMainline for mainline-client
class RunningTransferMainline extends RunningTransfer
{
	// tornado-bin
	var $mainlineBin = "";

    //--------------------------------------------------------------------------
    // ctor
    function RunningTransferMainline($psLine,$cfg) {
        // version
		$this->version = "0.4";
        // init conf
        $this->Initialize($cfg);
        //
        $this->mainlineBin = $this->cfg["docroot"]."bin/TF_Mainline/tfmainline.py";
        //
        if (strlen($psLine) > 0) {
            while (strpos($psLine,"  ") > 0)
                $psLine = str_replace("  ",' ',trim($psLine));
            $arr = split(' ',$psLine);
            $this->processId = $arr[0];
            $arrC = count($arr);
            foreach($arr as $key =>$value) {
                if ($key == 0)
                    $startArgs = false;
                if ($value == $this->mainlineBin) {
                	$this->transferowner = $arr[7];
                	$this->filePath = substr($arr[$arrC - 1], 0, strrpos($arr[$arrC - 1], "/") + 1);
                	$this->statFile = str_replace($this->filePath,'', $arr[9]);
                }
                if ($value == '--language')
                    $startArgs = true;
                if ($startArgs) {
                    if (!empty($value)) {
                        if (strpos($value,"-",1) > 0) {
                            if(array_key_exists($key+1,$arr)) {
                                if (strpos($value,"priority") > 0)
                                    $this->args .= "\n file ".$value." set";
                                else
                                    $this->args .= $value.":".$arr[$key+1].",";
                            } else {
                            	$this->transferFile = str_replace($this->filePath,'',$value);
                            }
                        }
                    }
                }
            }
            $this->args = str_replace("--","",$this->args);
            $this->args = substr($this->args,0,strlen($this->args));
        }
    }

    //----------------------------------------------------------------
    // Function to put the variables into a string for writing to file
    function BuildAdminOutput($theme) {
        return parent::BuildAdminOutput($theme);
    }
}

?>