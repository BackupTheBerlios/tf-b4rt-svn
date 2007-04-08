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
 * This method Gets Download profiles for the actual user
 *
 * @param $user
 * @param $profile
 * @return array
 */
function GetProfiles($user, $profile) {
	global $cfg, $db;
	$profiles_array = array();
	$sql = "SELECT name FROM tf_trprofiles WHERE owner LIKE '".$user."' AND public='0'";
	$rs = $db->GetCol($sql);
	if ($rs) {
		foreach($rs as $arr) {
			array_push($profiles_array, array(
				'name' => $arr,
				'is_selected' => ($arr == $profile) ? 1 : 0
				)
			);
		}
	}
	if ($db->ErrorNo() != 0) dbError($sql);
	return $profiles_array;
}

/**
 * This method Gets public Download profiles
 *
 * @param $profile
 * @return array
 */
function GetPublicProfiles($profile) {
	global $cfg, $db;
	$profiles_array = array();
	$sql = "SELECT name FROM tf_trprofiles WHERE public= '1'";
	$rs = $db->GetCol($sql);
	if ($rs) {
		foreach($rs as $arr) {
			array_push($profiles_array, array(
				'name' => $arr,
				'is_selected' => ($arr == $profile) ? 1 : 0
				)
			);
		}
	}
	if ($db->ErrorNo() != 0) dbError($sql);
	return $profiles_array;
}

/**
 * This method fetch settings for an specific profile
 *
 * @param $profile
 * @return array
 */
function GetProfileSettings($profile) {
	global $cfg, $db;
	$sql = "SELECT minport, maxport, maxcons, rerequest, rate, maxuploads, drate, runtime, sharekill, superseeder from tf_trprofiles where name like '".$profile."'";
	$settings = $db->GetRow($sql);
	if ($db->ErrorNo() != 0) dbError($sql);
	return $settings;
}

/**
 * Add New Profile Information
 *
 * @param $newProfile
 */
function AddProfileInfo( $newProfile ) {
	global $db, $cfg;
	$sql = 'INSERT INTO tf_trprofiles ( name , owner , minport , maxport , maxcons , rerequest , rate , maxuploads , drate , runtime , sharekill , superseeder , public )'." VALUES ('".$newProfile["name"]."', '".$cfg['uid']."', '".$newProfile["minport"]."', '".$newProfile["maxport"]."', '".$newProfile["maxcons"]."', '".$newProfile["rerequest"]."', '".$newProfile["rate"]."', '".$newProfile["maxuploads"]."', '".$newProfile["drate"]."', '".$newProfile["runtime"]."', '".$newProfile["sharekill"]."', '".$newProfile["superseeder"]."', '".$newProfile["public"]."')";
	$db->Execute( $sql );
	if ($db->ErrorNo() != 0) dbError($sql);
}

/**
 * getProfile
 *
 * @param $pid
 * @return
 */
function getProfile($pid) {
	global $cfg, $db;
	$rtnValue = "";
	$sql = "SELECT id , name , minport , maxport , maxcons , rerequest , rate , maxuploads , drate , runtime , sharekill , superseeder , public FROM tf_trprofiles WHERE id LIKE '".$pid."'";
	$rtnValue = $db->GetAll($sql);
	return $rtnValue[0];
}

/**
 * Modify Profile Information
 *
 * @param $pid
 * @param $newProfile
 */
function modProfileInfo($pid, $newProfile) {
	global $cfg, $db;
	$sql = "UPDATE tf_trprofiles SET owner = '".$cfg['uid']."', name = '".$newProfile["name"]."', minport = '".$newProfile["minport"]."', maxport = '".$newProfile["maxport"]."', maxcons = '".$newProfile["maxcons"]."', rerequest = '".$newProfile["rerequest"]."', rate = '".$newProfile["rate"]."', maxuploads = '".$newProfile["maxuploads"]."', drate = '".$newProfile["drate"]."', runtime = '".$newProfile["runtime"]."', sharekill = '".$newProfile["sharekill"]."', superseeder = '".$newProfile["superseeder"]."', public = '".$newProfile["public"]."' WHERE id = '".$pid."'";
	$db->Execute($sql);
	if ($db->ErrorNo() != 0) dbError($sql);
}

/**
 * Delete Profile Information
 *
 * @param $pid
 */
function deleteProfileInfo($pid) {
	global $db;
	$sql = "DELETE FROM tf_trprofiles WHERE id=".$pid;
	$result = $db->Execute($sql);
	if ($db->ErrorNo() != 0) dbError($sql);
}

?>