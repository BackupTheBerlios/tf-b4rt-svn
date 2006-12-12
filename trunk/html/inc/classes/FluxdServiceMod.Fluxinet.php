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

// class for the Fluxd-Service-module Fluxinet
class FluxdFluxinet extends FluxdServiceMod
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
     * @return FluxdFluxinet
     */
    function getInstance() {
		global $instanceFluxdFluxinet;
		// initialize if needed
		if (!isset($instanceFluxdFluxinet))
			FluxdFluxinet::initialize();
		return $instanceFluxdFluxinet;
    }

    /**
     * initialize FluxdFluxinet.
     */
    function initialize() {
    	global $cfg, $instanceFluxdFluxinet;
    	// create instance
    	if (!isset($instanceFluxdFluxinet))
    		$instanceFluxdFluxinet = new FluxdFluxinet(serialize($cfg));
    }

	/**
	 * getState
	 *
	 * @return state
	 */
    function getState() {
		global $instanceFluxdFluxinet;
		return (isset($instanceFluxdFluxinet))
			? $instanceFluxdFluxinet->state
			: FLUXDMOD_STATE_NULL;
    }

    /**
     * getMessages
     * @return array
     */
    function getMessages() {
    	global $instanceFluxdFluxinet;
		return $instanceFluxdFluxinet->messages;
    }

	// =========================================================================
	// ctor
	// =========================================================================

    /**
     * ctor
     */
    function FluxdFluxinet($cfg) {
        $this->moduleName = "Fluxinet";
		// initialize
        $this->instance_initialize($cfg);
    }

}

?>