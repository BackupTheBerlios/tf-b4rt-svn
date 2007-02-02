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

// defines
define('_FILE_THIS', $_SERVER['SCRIPT_NAME']);
define('_URL_THIS', 'http://'.$_SERVER['SERVER_NAME']. _FILE_THIS);

/**
 * Stats
 */
class Stats
{
    // private fields

	// ids of server-details
	var $_serverIds = array(
		"speedDown",          /*  0 */
		"speedUp",            /*  1 */
		"speedTotal",         /*  2 */
		"cons",               /*  3 */
		"freeSpace",          /*  4 */
		"loadavg",            /*  5 */
		"running",            /*  6 */
		"queued",             /*  7 */
		"speedDownPercent",   /*  8 */
		"speedUpPercent",     /*  9 */
		"driveSpacePercent"   /* 10 */
	);
	var $_serverIdCount = 11;

	// ids of transfer-details
	var $_transferIds = array(
		"running",            /*  0 */
		"speedDown",          /*  1 */
		"speedUp",            /*  2 */
		"downCurrent",        /*  3 */
		"upCurrent",          /*  4 */
		"downTotal",          /*  5 */
		"upTotal",            /*  6 */
		"percentDone",        /*  7 */
		"sharing",            /*  8 */
		"eta",                /*  9 */
		"seeds",              /* 10 */
		"peers",              /* 11 */
		"cons"                /* 12 */
	);
	var $_transferIdCount = 13;

	// ids of xfer-details
	var $_xferIds = array(
		"xferGlobalTotal",    /* 0 */
		"xferGlobalMonth",    /* 1 */
		"xferGlobalWeek",     /* 2 */
		"xferGlobalDay",      /* 3 */
		"xferUserTotal",      /* 4 */
		"xferUserMonth",      /* 5 */
		"xferUserWeek",       /* 6 */
		"xferUserDay"         /* 7 */
	);
	var $_xferIdCount = 8;

	// ids of user-details
	var $_userIds = array(
		"state"               /* 0 */
	);
	var $_userIdCount = 1;

    // stats-fields
   	var $_serverLabels = "";
   	var $_xferLabels = "";
   	var $_transferList = "";
   	var $_transferHeads = "";
   	var $_serverStats = "";
   	var $_xferStats = "";
   	var $_transferID = "";
   	var $_transferDetails = "";
   	var $_userList = "";

    // options
    var $_type = "";
    var $_format = "";
    var $_header = 0;
    var $_attachment = 0;
    var $_compressed = 0;

    // content
    var $_content = "";

	// =========================================================================
	// public static methods
	// =========================================================================

    /**
     * accessor for singleton
     *
     * @return Stats
     */
    function getInstance() {
		global $instanceStats;
		return (isset($instanceStats))
			? $instanceStats
			: false;
    }

	/**
	 * process a request
	 *
	 * @param $params
	 * @return mixed
	 */
    function processRequest($params) {
		global $instanceStats;
    	// create new instance
    	$instanceStats = new Stats($params);
		// call instance-method
		return (!$instanceStats)
			? false
			: $instanceStats->instance_processRequest();
    }

	// =========================================================================
	// ctor
	// =========================================================================

    /**
     * do not use direct, use the public static methods !
     *
	 * @param $params
     * @return Stats
     */
    function Stats($params) {
    	global $cfg;

		// send usage ?
		if (isset($params["usage"]))
			$this->_sendUsage();
		if (($cfg['stats_show_usage'] == 1) && (count($params) == 0))
			$this->_sendUsage();

		// type
		$this->_type = (isset($params["t"]))
			? $params["h"]
			: $cfg['stats_default_type'];

		// format
		$this->_format = (isset($params["f"]))
			? $params["f"]
			: $cfg['stats_default_format'];

		// header
		$this->_header = (isset($params["h"]))
			? $params["h"]
			: $cfg['stats_default_header'];

		// attachment
		$this->_attachment = (isset($params["a"]))
			? $params["a"]
			: $cfg['stats_default_attach'];

		// compressed
		$this->_compressed = (isset($params["c"]))
			? $params["c"]
			: $cfg['stats_default_compress'];

    }

	// =========================================================================
	// public methods
	// =========================================================================

	/**
	 * process a request
	 *
	 * @return mixed
	 */
    function instance_processRequest() {
    	global $cfg;

    }

	// =========================================================================
	// private methods
	// =========================================================================

    /**
     * sends usage
     *
	 * @return mixed
     */
    function _sendUsage() {
    	global $cfg;

    }


}

?>