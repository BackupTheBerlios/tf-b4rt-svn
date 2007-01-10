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

// class for the Fluxd-Service-module Qmgr
class FluxdQmgr extends FluxdServiceMod
{

	// =========================================================================
	// public static methods
	// =========================================================================

    /**
     * accessor for singleton
     *
     * @return FluxdQmgr
     */
    function getInstance() {
		global $instanceFluxdQmgr;
		// initialize if needed
		if (!isset($instanceFluxdQmgr))
			FluxdQmgr::initialize();
		return $instanceFluxdQmgr;
    }

    /**
     * initialize FluxdQmgr.
     */
    function initialize() {
    	global $instanceFluxdQmgr;
    	// create instance
    	if (!isset($instanceFluxdQmgr))
    		$instanceFluxdQmgr = new FluxdQmgr();
    }

	/**
	 * getState
	 *
	 * @return state
	 */
    function getState() {
		global $instanceFluxdQmgr;
		return $instanceFluxdQmgr->state;
    }

    /**
     * getMessages
     *
     * @return array
     */
    function getMessages() {
    	global $instanceFluxdQmgr;
		return $instanceFluxdQmgr->messages;
    }

	/**
	 * getModState
	 *
	 * @return state
	 */
	function getModState() {
		global $instanceFluxdQmgr;
		return $instanceFluxdQmgr->modstate;
	}

    /**
     * isRunning
     *
     * @return boolean
     */
    function isRunning() {
    	global $instanceFluxdQmgr;
		return ($instanceFluxdQmgr->modstate == FLUXDMOD_STATE_RUNNING);
    }

    /**
     * getQueuedTransfers
     *
     * @param $user
     * @return string
     */
    function getQueuedTransfers($user = "") {
    	global $instanceFluxdQmgr;
    	return $instanceFluxdQmgr->instance_getQueuedTransfers($user);
    }

    /**
     * countQueuedTransfers
     *
     * @param $user
     * @return int
     */
    function countQueuedTransfers($user = "") {
    	global $instanceFluxdQmgr;
    	return $instanceFluxdQmgr->instance_countQueuedTransfers($user);
    }

    /**
     * enqueue
     *
     * @param $transfer
     * @param $user
     */
    function enqueueTransfer($transfer, $user) {
    	global $instanceFluxdQmgr;
    	$instanceFluxdQmgr->instance_enqueueTransfer($transfer, $user);
    }

    /**
     * dequeue
     *
     * @param $transfer
     * @param $user
     */
    function dequeueTransfer($transfer, $user) {
    	global $instanceFluxdQmgr;
    	$instanceFluxdQmgr->instance_dequeueTransfer($transfer, $user);
    }

	// =========================================================================
	// ctor
	// =========================================================================

    /**
     * ctor
     */
    function FluxdQmgr() {
    	// name
        $this->moduleName = "Qmgr";
		// initialize
        $this->instance_initialize();
    }

	// =========================================================================
	// public methods
	// =========================================================================

    /**
     * instance_getQueuedTransfers
     *
     * @param $user
     * @return string
     */
    function instance_getQueuedTransfers($user = "") {
     	return ($this->modstate == FLUXDMOD_STATE_RUNNING)
    		? Fluxd::sendServiceCommand($this->moduleName, 'list-queue', 1)
    		: "";
    }

    /**
     * instance_countQueuedTransfers
     *
     * @param $user
     * @return int
     */
    function instance_countQueuedTransfers($user = "") {
     	return ($this->modstate == FLUXDMOD_STATE_RUNNING)
    		? Fluxd::sendServiceCommand($this->moduleName, 'count-queue', 1)
    		: 0;
    }

    /**
     * instance_enqueue
     *
     * @param $transfer
     * @param $user
     */
    function instance_enqueueTransfer($transfer, $user) {
    	if ($this->modstate == FLUXDMOD_STATE_RUNNING) {
    		// send command (hardcoded for .torrent for now)
    		Fluxd::sendServiceCommand($this->moduleName, 'enqueue;'.substr($transfer, 0, -8).';'.$user, 0);
	        // just 2 sec... dont stress fluxd
	        sleep(2);
    	}
    }

    /**
     * instance_dequeue
     *
     * @param $transfer
     * @param $user
     */
    function instance_dequeueTransfer($transfer, $user) {
    	global $cfg;
    	if ($this->modstate == FLUXDMOD_STATE_RUNNING) {
        	if (isTransferRunning($transfer)) {
	            // transfer has been started...log
	            // TODO : kill it ?
	            AuditAction($cfg["constants"]["unqueued_transfer"], $transfer . "has been already started.");
        	} else {
	            // send command (hardcoded for .torrent for now)
    			Fluxd::sendServiceCommand($this->moduleName, 'dequeue;'.substr($transfer, 0, -8).';'.$user, 0);
	            // flag the transfer as stopped (in db)
	            stopTransferSettings($transfer);
	            // update the stat file.
	            $this->_updateStatFile($transfer);
	            // log
	            AuditAction($cfg["constants"]["unqueued_transfer"], $transfer);
	            // just 2 sec... dont stress fluxd
	            sleep(2);
        	}
        }
    }

    // =========================================================================
	// private methods
	// =========================================================================

    /**
     * _updateStatFile
     *
     * @param $transfer
     */
    function _updateStatFile($transfer) {
    	global $transfers;
        $the_user = getOwner($transfer);
        $btclient = getTransferClient($transfer);
        $modded = 0;
        // create sf object
        $sf = new StatFile($transfer, $the_user);
        if ($sf->percent_done > 0 && $sf->percent_done < 100) {
            // has downloaded something at some point, mark it is incomplete
            $sf->running = "0";
            $sf->time_left = "Transfer Stopped";
            $modded++;
        }
        if ($modded == 0) {
            if ($sf->percent_done == 0 || $sf->percent_done == "") {
                // We are going to write a '2' on the front of the stat file so that it will be set back to New Status
                $sf->running = "2";
                $sf->time_left = "";
                $modded++;
            }
        }
        if ($modded == 0) {
            if ($sf->percent_done == 100) {
                // Torrent was seeding and is now being stopped
                $sf->running = "0";
                $sf->time_left = "Download Succeeded!";
                $modded++;
            }
        }
        if ($modded == 0) {
            // hmmm this stat-file is quite strange... just rewrite it stopped.
            $sf->running = "0";
            $sf->time_left = "Transfer Stopped";
        }
        // Write out the new Stat File
        $sf->write();
		// set transfers-cache
		cacheTransfersSet();
    }

}

?>