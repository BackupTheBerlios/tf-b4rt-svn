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

// class for the Fluxd-Service-module Watch
class FluxdWatch extends FluxdServiceMod
{
	// public fields

	// version
	var $version = "0.2";

	// =========================================================================
	// public static methods
	// =========================================================================

    /**
     * accessor for singleton
     *
     * @return FluxdWatch
     */
    function getInstance() {
		global $instanceFluxdWatch;
		// initialize if needed
		if (!isset($instanceFluxdWatch))
			FluxdWatch::initialize();
		return $instanceFluxdWatch;
    }

    /**
     * initialize FluxdWatch.
     */
    function initialize() {
    	global $cfg, $instanceFluxdWatch;
    	// create instance
    	if (!isset($instanceFluxdWatch))
    		$instanceFluxdWatch = new FluxdWatch(serialize($cfg));
    }

	/**
	 * getState
	 *
	 * @return state
	 */
    function getState() {
		global $instanceFluxdWatch;
		return (isset($instanceFluxdWatch))
			? $instanceFluxdWatch->state
			: FLUXDMOD_STATE_NULL;
    }

    /**
     * getMessages
     *
     * @return array
     */
    function getMessages() {
		global $instanceFluxdWatch;
		return (isset($instanceFluxdWatch))
			? $instanceFluxdWatch->messages
			: array();
    }

    /**
     * isRunning
     *
     * @return boolean
     */
    function isRunning() {
		global $instanceFluxdWatch;
		return (isset($instanceFluxdWatch))
			? $instanceFluxdWatch->instance_isRunning()
			: false;
    }

	// =========================================================================
	// ctor
	// =========================================================================

    /**
     * ctor
     */
    function FluxdWatch($cfg) {
        $this->moduleName = "Watch";
		// initialize
        $this->instance_initialize($cfg);
    }

}

?>