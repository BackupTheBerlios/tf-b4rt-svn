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
     * @param $moduleType (Qmgr|Fluxinet|Trigger|Watch|Clientmaint)
     * @return FluxdServiceMod-instance
     */
    function getFluxdServiceModInstance($fluxCfg, $fluxd, $moduleType) {
        // damn dirty but does php (< 5) have reflection or something like
        // class-by-name ?
        $classFile = 'inc/classes/Fluxd.'.$moduleType.'.php';
        if (is_file($classFile)) {
            require_once($classFile);
            switch ($moduleType) {
                case "Qmgr":
                    return new FluxdQmgr(serialize($fluxCfg), $fluxd);
                	break;
                case "Fluxinet":
                    return new FluxdFluxinet(serialize($fluxCfg), $fluxd);
                	break;
                case "Trigger":
                    return new FluxdTrigger(serialize($fluxCfg), $fluxd);
                	break;
                case "Watch":
                    return new FluxdWatch(serialize($fluxCfg), $fluxd);
                	break;
                case "Clientmaint":
                    return new FluxdClientmaint(serialize($fluxCfg), $fluxd);
                	break;
            }
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