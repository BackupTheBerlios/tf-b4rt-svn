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

// base class for a Fluxd-Service-module
class FluxdServiceMod
{
	// fields

	// mod-related
	var $moduleName = "";
    var $version = "";

    // call-result
    var $callResult;

    // config-array
    var $cfg = array();

    // messages-string
    var $messages = "";

    // Fluxd-instance
    var $fluxd;

    // state
    //  0 : not initialized
    //  1 : initialized
    //  2 : started/running
    // -1 : error
    var $state = 0;

    /**
     * ctor
     */
    function FluxdServiceMod() {
        $this->state = -1;
        die('base class -- dont do this');
    }

    /**
     * factory : get FluxdServiceMod-instance
     *
     * @param $fluxCfg torrentflux config-array
     * @param $fluxd Fluxd instance
     * @param $moduleType (Qmgr|Fluxinet|Trigger|Watch|Maintenance)
     * @return FluxdServiceMod-instance
     */
    function getFluxdServiceModInstance($fluxCfg, $fluxd, $moduleType) {
    	// create and return object-instance
        $classFile = 'inc/classes/Fluxd.'.$moduleType.'.php';
        switch ($moduleType) {
            case "Qmgr":
            	require_once($classFile);
                return new FluxdQmgr(serialize($fluxCfg), $fluxd);
            case "Fluxinet":
            	require_once($classFile);
                return new FluxdFluxinet(serialize($fluxCfg), $fluxd);
            case "Watch":
            	require_once($classFile);
                return new FluxdWatch(serialize($fluxCfg), $fluxd);
            case "Rssad":
            	require_once($classFile);
                return new FluxdRssad(serialize($fluxCfg), $fluxd);
            case "Trigger":
            	require_once($classFile);
                return new FluxdTrigger(serialize($fluxCfg), $fluxd);
            case "Maintenance":
            	require_once($classFile);
                return new FluxdMaintenance(serialize($fluxCfg), $fluxd);
            default:
            	AuditAction($fluxCfg["constants"]["error"], "Invalid FluxdServiceMod-Class : ".$moduleType);
				global $argv;
    			if (isset($argv))
    				die("Invalid FluxdServiceMod-Class : ".$moduleType);
    			else
    				showErrorPage("Invalid FluxdServiceMod-Class : <br>".htmlentities($moduleType, ENT_QUOTES));
        }
    }

    /**
     * initialize the FluxdServiceMod.
     *
     * @param $cfg torrentflux config-array
     * @param $fluxd Fluxd instance
     */
    function initialize($cfg, $fluxd) {
        // config
    	$this->cfg = unserialize($cfg);
        if (empty($this->cfg)) {
            $this->messages = "Config not passed";
            $this->state = -1;
            return;
        }
        // Fluxd-instance
        $this->fluxd = $fluxd;
        if (!(isset($this->fluxd))) {
            $this->messages = "Fluxd-instance not set";
            $this->state = -1;
            return;
        }
        // all ok
        $this->state = 1;
    }

    /**
     * setConfig
     *
     * @param $key
     * @param $val
     */
    function setConfig($key, $val) {
    	// send command to Qmgr
    	$this->sendServiceCommand("set;".$key.";".$val, 0);
    }

    /**
     * send service command
     * @param $command
     * @param $read does this command return something ?
     * @return string with retval or null if error
     */
    function sendServiceCommand($command, $read = 0) {
        $this->callResult = $this->fluxd->sendCommand('!'.$this->moduleName.':'.$command, $read);
        return $this->callResult;
    }

}

?>