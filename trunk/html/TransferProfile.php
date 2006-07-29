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

// class TransferProfile
class TransferProfile
{
	// id
	var $id = "";

	// name
	var $name = "";

	// owner-uid
	var $uid = "";

	// public-flag
	var $public = false;

	// transfer-fields
    var $rate = "";
    var $drate = "";
    var $superseeder = "";
    var $runtime = "";
    var $maxuploads = "";
    var $minport = "";
    var $maxport = "";
    var $maxcons = "";
    var $rerequest = "";
    var $sharekill = "";

    // config-array
    var $cfg = array();

    // messages-string
    var $messages = "";

    // state
    // -1 = error
    //  0 = not initialized
    //  1 = initialized
    var $state = 0;

    /**
     * ctor
     */
    function TransferProfile($cfg) {
        $this->Initialize($cfg);
    }

    //
    /**
     * Initialize the object.
     *
     * @param $cfg
     */
    function Initialize($cfg) {
        $this->cfg = unserialize($cfg);
        if (empty($this->cfg)) {
            $this->msg = "Config not passed";
            $this->state = -1;
            return false;
        }
    }

    /**
     * load profile
     *
     * @param $id
     */
    function load($id) {
		global $db;
		$sql = "SELECT * FROM tf_trprofiles WHERE id = '".$id."'";
		$result = $db->Execute($sql);
			showError($db, $sql);
		$row = $result->FetchRow();
		if (!empty($row)) {

			// TODO : set vars

			$this->state = 1;
			return true;
		}
    	$this->state = 0;
    	return false;
    }

    /**
     * safe profile
     *
     */
    function save() {
    	if ($this->state == 1) {

    		// TODO : save profile

			return true;
    	} else {
			$this->msg = "Wrong state. cant save";
            $this->state = -1;
            return false;
    	}
    }

    /**
     * delete profile
     *
     * @param $id
     */
    function delete($id) {
    	global $db;
		$sql = "DELETE FROM tf_trprofiles WHERE id = '".$id."'";
		$db->Execute($sql);
		showError($db, $sql);
		return true;
    }



} // end class

?>