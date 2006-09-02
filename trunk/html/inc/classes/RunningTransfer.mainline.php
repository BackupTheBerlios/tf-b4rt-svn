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
    function RunningTransferTornado($psLine,$cfg) {
        // version
        $this->version = array_shift(explode(" ",trim(array_pop(explode(":",'$Revision$')))));
        // init conf
        $this->Initialize($cfg);
        //
        $this->mainlineBin = dirname($_SERVER["SCRIPT_FILENAME"])."/bin/TF_Mainline/bittorrent-console.py";
        //
        if (strlen($psLine) > 0) {
            while (strpos($psLine,"  ") > 0)
                $psLine = str_replace("  ",' ',trim($psLine));
            $arr = split(' ',$psLine);
            $this->processId = $arr[0];
            foreach($arr as $key =>$value) {
                if ($key == 0)
                    $startArgs = false;
                if ($value == $this->mainlineBin) {
                	$this->transferowner = $arr[4];
                	$this->filePath = substr($arr[6], 0, strrpos($arr[6], "/") + 1);
                	$this->statFile = str_replace($this->filePath,'',$arr[5]);
                	/*
                    $offset = 2;
                    if(! @strpos($arr[$key+$offset],"/",1) > 0)
                        $offset += 1;
                    if(! @strpos($arr[$key+$offset],"/",1) > 0)
                        $offset += 1;
                    $this->filePath = substr($arr[$key+$offset],0,strrpos($arr[$key+$offset],"/")+1);
                    $this->statFile = str_replace($this->filePath,'',$arr[$key+$offset]);
                    $this->transferowner = $arr[$key+$offset+1];
                    */
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
                                //$this->args .= "";
                            	$this->transferFile = str_replace($this->filePath,'',$value);
                            }
                        }
                    }
                }
                //if ($value == '--responsefile')
                //    $this->transferFile = str_replace($this->filePath,'',$arr[$key+1]);
            }
            $this->args = str_replace("--","",$this->args);
            $this->args = substr($this->args,0,strlen($this->args));
        }
    }

    //----------------------------------------------------------------
    // Function to put the variables into a string for writing to file
    function BuildAdminOutput() {
        return parent::BuildAdminOutput();
    }
}

?>