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

// states
define('FLUXDMOD_STATE_NULL', 0);                                        // null
define('FLUXDMOD_STATE_RUNNING', 1);                                  // running
define('FLUXDMOD_STATE_ERROR', -1);                                     // error

// base class for a Fluxd-Service-module
class FluxdServiceMod
{
	// public fields

	// mod-related
	var $moduleName = "";
    var $version = "0.2";

    // state
    var $state = FLUXDMOD_STATE_NULL;

    // messages-array
    var $messages = array();

    // modstate
    var $modstate = FLUXDMOD_STATE_NULL;

    // protected fields

    // config-array
    var $_cfg = array();

    // private fields

	// =========================================================================
	// public static methods
	// =========================================================================

    /**
     * accessor for singleton
     *
     * @return FluxdServiceMod
     */
    function getInstance() {}

    /**
     * initialize FluxdServiceMod.
     */
    function initialize() {}

	/**
	 * getState
	 *
	 * @return state
	 */
	function getState() {}

    /**
     * getMessages
     *
     * @return array
     */
    function getMessages() {}

	/**
	 * getModState
	 *
	 * @return state
	 */
	function getModState() {}

    /**
     * isRunning
     *
     * @return boolean
     */
    function isRunning() {}

    /**
     * initialize a Fluxd-Service-mod.
     *
     * @param $type
     */
    function initializeServiceMod($type) {
        switch ($type) {
            case "Qmgr":
            	require_once('inc/classes/FluxdServiceMod.Qmgr.php');
            	FluxdQmgr::initialize();
            	return;
            case "Fluxinet":
            	require_once('inc/classes/FluxdServiceMod.Fluxinet.php');
                FluxdFluxinet::initialize();
                return;
            case "Watch":
            	require_once('inc/classes/FluxdServiceMod.Watch.php');
                FluxdWatch::initialize();
                return;
            case "Rssad":
            	require_once('inc/classes/FluxdServiceMod.Rssad.php');
                FluxdRssad::initialize();
                return;
            case "Trigger":
            	require_once('inc/classes/FluxdServiceMod.Trigger.php');
                FluxdTrigger::initialize();
                return;
            case "Maintenance":
            	require_once('inc/classes/FluxdServiceMod.Maintenance.php');
                FluxdMaintenance::initialize();
                return;
            default:
            	global $cfg, $argv;
            	AuditAction($cfg["constants"]["error"], "Invalid FluxdServiceMod-Class : ".$type);
    			if (isset($argv))
    				die("Invalid FluxdServiceMod-Class : ".$type);
    			else
    				showErrorPage("Invalid FluxdServiceMod-Class : <br>".htmlentities($type, ENT_QUOTES));
        }
    }

	// =========================================================================
	// ctor
	// =========================================================================

    /**
     * ctor
     */
    function FluxdServiceMod() {
        die('base class -- dont do this');
    }

	// =========================================================================
	// public methods
	// =========================================================================

    /**
     * initialize the FluxdServiceMod.
     *
     * @param $cfg torrentflux config-array
     */
    function instance_initialize($cfg) {
        // config
    	$this->_cfg = unserialize($cfg);
        if (empty($this->_cfg)) {
            array_push($this->messages , "Config not passed");
            $this->state = FLUXDMOD_STATE_ERROR;
            return;
        }
        // modstate-init
        $this->modstate = Fluxd::modState($this->moduleName);
    }

}

?>