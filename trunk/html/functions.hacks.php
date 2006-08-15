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


//XFER:****************************************************
//XFER: getUsage(timestamp, usage_array)
//XFER: Gets upload/download usage for all users starting at timestamp from SQL
function getUsage($start, $period)
{
  global $xfer, $xfer_total, $db;
  $sql = 'SELECT user, SUM(download) AS download, SUM(upload) AS upload FROM tf_xfer WHERE date >= "'.$start.'" AND user != "" GROUP BY user';
  $rtnValue = $db->GetAll($sql);
  showError($db,$sql);
  foreach ($rtnValue as $row) sumUsage($row[0], $row[1], $row[2], $period);
}

//XFER:****************************************************
//XFER: sumUsage(user, downloaded, uploaded, usage_array)
//XFER: Adds download/upload into correct usage_array (total, month, etc)
function sumUsage($user, $download, $upload, $period) {
  global $xfer, $xfer_total;
  @ $xfer[$user][$period]['download'] += $download;
  @ $xfer[$user][$period]['upload'] += $upload;
  @ $xfer[$user][$period]['total'] += $download + $upload;
  @ $xfer_total[$period]['download'] += $download;
  @ $xfer_total[$period]['upload'] += $upload;
  @ $xfer_total[$period]['total'] += $download + $upload;
}

//XFER:****************************************************
//XFER: saveXfer(user, download, upload)
//XFER: Inserts or updates SQL upload/download for user
function saveXfer($user, $down, $up) {
  global $db;
  $sql = 'SELECT 1 FROM tf_xfer WHERE user = "'.$user.'" AND date = '.$db->DBDate(time());
  if ($db->GetRow($sql)) {
    $sql = 'UPDATE tf_xfer SET download = download+'.($down+0).', upload = upload+'.($up+0).' WHERE user = "'.$user.'" AND date = '.$db->DBDate(time());
    $db->Execute($sql);
    showError($db,$sql);
  } else {
    showError($db,$sql);
    // b4rt-8
    // blackwidow :
    //$sql = 'INSERT INTO tf_xfer SET user = "'.$user.'", date = '.$db->DBDate(time()).', download = '.($down+0).', upload = '.($up+0);
    // carlo1234 :
    $sql = 'INSERT INTO tf_xfer (user,date,download,upload) values ("'.$user.'",'.$db->DBDate(time()).','.($down+0).','.($up+0).')';
    // b4rt-8
    $db->Execute($sql);
    showError($db,$sql);
  }
}

// Link Mod
function getLinkSortOrder($lid) {
    global $db;
    // Get Current sort order index of link with this link id:
    $sql="SELECT sort_order FROM tf_links WHERE lid=$lid";
    $rtnValue=$db->GetOne($sql);
    showError($db,$sql);
    return $rtnValue;
}

//*********************************************************
function getSite($lid) {
    global $cfg, $db;
    $rtnValue = "";
    $sql = "SELECT sitename FROM tf_links WHERE lid=".$lid;
    $rtnValue = $db->GetOne($sql);
    return $rtnValue;
}
// Link Mod

// Some Stats dir hack
//*************************************************************************
// correctFileName()
// Adds backslashes above special characters to obtain attainable directory
// names for disk usage
function correctFileName ($inName) {
       $replaceItems = array("'", ",", "#", "%", "!", "+", ":", "/", " ", "@", "$", "&", "?", "\"", "(", ")");
       $replacedItems = array("\'", "\,", "\#", "\%", "\!", "\+", "\:", "\/", "\ ", "\@", "\$", "\&", "\?", "\\\"", "\(", "\)");
       $cleanName = str_replace($replaceItems, $replacedItems, $inName);
       return $cleanName;
}

// Specific save path
function dirTree2($dir, $maxdepth)
{
        $dirTree2 = "<option value=\"".$dir."\">".$dir."</option>\n" ;
        if (is_numeric ($maxdepth))
        {
                if ($maxdepth == 0)
                {
                        //$last = exec ("du ".$dir." | cut -f 2- | sort", $retval);
                        $last = exec ("find ".$dir." -type d | sort", $retval);
                        for ($i = 1; $i < (count ($retval) - 1); $i++)
                        {
                                $dirTree2 .= "<option value=\"".$retval[$i]."\">".$retval[$i]."</option>\n" ;
                        }
                }
                else if ($maxdepth > 0)
                {
                        //$last = exec ("du --max-depth=".$maxdepth." ".$dir." | cut -f 2- | sort", $retval);
                        $last = exec ("find ".$dir." -maxdepth ".$maxdepth." -type d | sort", $retval);
                        for ($i = 1; $i < (count ($retval) - 1); $i++)
                        {
                                $dirTree2 .= "<option value=\"".$retval[$i]."\">".$retval[$i]."</option>\n" ;
                        }
                }
                else
                {
                        $dirTree2 .= "<option value=\"".$dir."\">".$dir."</option>\n" ;
                }
        }
        else
        {
                $dirTree2 .= "<option value=\"".$dir."\">".$dir."</option>\n" ;
        }
        return $dirTree2;
}

// SFV Check hack
//*************************************************************************
// findSVF()
// This method Builds and displays the Torrent Section of the Index Page
function findSFV($dirName) {
	$sfv = false;
	$d = dir($dirName);
	while (false !== ($entry = $d->read())) {
   		if($entry != '.' && $entry != '..' && !empty($entry) ) {
				// b4rt-5
				//if(is_file($dirName.'/'.$entry) && substr($entry, -4, 4) == '.sfv') {
	   		//		$sfv[dir] = $dirName;
				//		$sfv[sfv] = $dirName.'/'.$entry;
	   		//}
				if((is_file($dirName.'/'.$entry)) && (strtolower(substr($entry, -4, 4)) == '.sfv')) {
	   				$sfv[dir] = $dirName;
						$sfv[sfv] = $dirName.'/'.$entry;
	   		}
				// b4rt-5
	   	}
	}
	$d->close();
	return $sfv;
}
// Profiles hack
//*************************************************************************
// GetProfiles()
// This method Gets Download profiles for the actual user

function GetProfiles($user) {
	global $cfg, $db;
	$profiles_array = array();
	$profiles_array = $db->GetArray("select title from tf_dlprofiles where user_id like '".$user."'");
	return $profiles_array;
}

?>