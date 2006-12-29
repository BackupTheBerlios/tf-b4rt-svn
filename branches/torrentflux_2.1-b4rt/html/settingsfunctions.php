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

//******************************************************************************
// This file contains methods used by both the login.php and the
// main application
//******************************************************************************

//******************************************************************************
// getRequestVar
//******************************************************************************
function getRequestVar($varName) {
    if (array_key_exists($varName, $_REQUEST))
        return htmlentities(trim($_REQUEST[$varName]), ENT_QUOTES);
    else
        return '';
}

//******************************************************************************
// AuditAction
//******************************************************************************
function AuditAction($action, $file="") {
    global $_SERVER, $cfg, $db;
    $create_time = time();
	if (isset($_SERVER['HTTP_USER_AGENT']))
	   $user_agent = $_SERVER['HTTP_USER_AGENT'];
	if ((! isset($user_agent)) || ($user_agent == ""))
			$user_agent = "fluxcli.php/0.1";
	if ((! isset($action)) || ($action == ""))
			$action = "unset";
    $rec = array(
    	'user_id' => $cfg['user'],
    	'file' => $file,
    	'action' => $action,
    	'ip' => $cfg['ip'],
    	'ip_resolved' => $cfg['ip'],
    	'user_agent' => $user_agent,
    	'time' => $create_time
        );
    $sTable = 'tf_log';
    $sql = $db->GetInsertSql($sTable, $rec);
    // add record to the log
    //$result = $db->Execute($sql);
    $db->Execute($sql);
    showError($db,$sql);
}

//******************************************************************************
// loadSettings
//******************************************************************************
function loadSettings() {
    global $cfg, $db;
    // pull the config params out of the db
    $sql = "SELECT tf_key, tf_value FROM tf_settings";
    $recordset = $db->Execute($sql);
    showError($db, $sql);
    while(list($key, $value) = $recordset->FetchRow()) {
        $tmpValue = '';
		if (strpos($key,"Filter")>0) {
		  $tmpValue = unserialize($value);
		} elseif ($key == 'searchEngineLinks') {
            $tmpValue = unserialize($value);
    	}
    	if(is_array($tmpValue))
            $value = $tmpValue;
        $cfg[$key] = $value;
    }
}

//******************************************************************************
// insertSetting
//******************************************************************************
function insertSetting($key,$value) {
    global $cfg, $db;
    $update_value = $value;
    if (is_array($value))
        $update_value = serialize($value);
    $sql = "INSERT INTO tf_settings VALUES ('".$key."', '".$update_value."')";
    if ( $sql != "" ) {
        //$result = $db->Execute($sql);
        $db->Execute($sql);
        showError($db,$sql);
        // update the Config.
        $cfg[$key] = $value;
    }
}

//******************************************************************************
// updateSetting
//******************************************************************************
function updateSetting($key,$value) {
    global $cfg, $db;
    $update_value = $value;
	if (is_array($value))
        $update_value = serialize($value);
    $sql = "UPDATE tf_settings SET tf_value = '".$update_value."' WHERE tf_key = '".$key."'";
    if ( $sql != "" ) {
        //$result = $db->Execute($sql);
        $db->Execute($sql);
        showError($db,$sql);
        // update the Config.
        $cfg[$key] = $value;
    }
}

//******************************************************************************
// saveSettings
//******************************************************************************
function saveSettings($settings) {
    global $cfg, $db;
    foreach ($settings as $key => $value) {
        if (array_key_exists($key, $cfg)) {
            if(is_array($cfg[$key]) || is_array($value)) {
                if(serialize($cfg[$key]) != serialize($value)) {
                    updateSetting($key, $value);
                }
            } elseif ($cfg[$key] != $value) {
                updateSetting($key, $value);
            } else {
                // Nothing has Changed..
            }
        } else {
            insertSetting($key,$value);
        }
    }
}

/**
 * isFile
 *
 * @param $file
 * @return boolean
 */
function isFile($file) {
    $rtnValue = False;
    if (@is_file($file)) {
        $rtnValue = True;
    } else {
        if ($file == trim(shell_exec("ls 2>/dev/null ".escapeshellarg($file))))
            $rtnValue = True;
    }
    return $rtnValue;
}

?>