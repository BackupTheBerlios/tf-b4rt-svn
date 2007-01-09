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
 * CommandHandler
 */
class CommandHandler
{
    // private fields

    // messages-array
    var $_messages = array();

    // commands-array
    var $_commands = array();

	// =========================================================================
	// public static methods
	// =========================================================================

	/**
	 * add command for transfer
	 *
	 * @param $transfer
	 * @param $command
	 * @return boolean
	 */
   function add($transfer, $command) {
		global $instanceCommandHandler;
		// initialize if needed
		if (!isset($instanceCommandHandler))
			CommandHandler::initialize();
		// call instance-method
		return $instanceCommandHandler->instance_add($transfer, $command);
    }

	/**
	 * send command(s) to transfer
	 *
	 * @param $transfer
	 * @return boolean
	 */
    function send($transfer) {
		global $instanceCommandHandler;
		// return false if not set
		if (!isset($instanceCommandHandler))
			return false;
		// call instance-method
		return $instanceCommandHandler->instance_send($transfer);
    }

    /**
     * getMessages
     *
     * @return array
     */
    function getMessages() {
		global $instanceCommandHandler;
		return (isset($instanceCommandHandler))
			? $instanceCommandHandler->_messages
			: array();
    }

    /**
     * initialize CommandHandler.
     */
    function initialize() {
    	global $instanceCommandHandler;
    	// create instance
    	if (!isset($instanceCommandHandler))
    		$instanceCommandHandler = new CommandHandler();
    }

	// =========================================================================
	// ctor
	// =========================================================================

    /**
     * do not use this, use only the public static methods !
     *
     * @return CommandHandler
     */
    function CommandHandler() {
    	$this->_commands = array();
    }

	// =========================================================================
	// public methods
	// =========================================================================

	/**
	 * add command for transfer
	 *
	 * @param $transfer
	 * @param $command
	 * @return boolean
	 */
    function instance_add($transfer, $command) {
    	if (!isset($this->_commands[$transfer]))
    		$this->_commands[$transfer] = array();
    	if ((!in_array($command, $this->_commands[$transfer])) && (strlen($command) > 0)) {
    		array_push($this->_commands[$transfer], $command);
    		return true;
    	} else {
			return false;
    	}
    }

	/**
	 * send command(s) to transfer
	 *
	 * @param $transfer
	 * @return boolean
	 */
    function instance_send($transfer) {
    	return (empty($this->_commands[$transfer]))
    		? false
    		: $this->_writeCommandFile($transfer);
    }

	// =========================================================================
	// private methods
	// =========================================================================

    /**
     * write the command-file
     *
	 * @param $transfer
     * @return boolean
     */
	function _writeCommandFile($transfer) {
		global $cfg;
		$filename = "";
		if (substr($transfer, -8) == ".torrent") {
			$filename = substr($transfer, 0, -8).'.cmd';
		} else if (substr($transfer, -5) == ".wget") {
			$filename = substr($transfer, 0, -5).'.cmd';
		} else if (substr($transfer, -4) == ".nzb") {
			$filename = substr($transfer, 0, -4).'.cmd';
		} else {
			array_push($this->_messages , "Invalid Transfer : ".$transfer);
			return false;
		}
		$file = $cfg["transfer_file_path"].$filename;
		$handle = false;
		$handle = @fopen($file, "w");
		if (!$handle) {
            $msg = "cannot open command-file ".$file." for writing.";
            array_push($this->_messages , $msg);
            AuditAction($cfg["constants"]["error"], "CommandHandler _writeCommandFile-Error : ".$msg);
			return false;
		}
        $result = @fwrite($handle, implode("\n", $this->_commands[$transfer])."\n");
		@fclose($handle);
		if ($result === false) {
            $msg = "cannot write content to command-file ".$this->_fileHistory.".";
            array_push($this->_messages , $msg);
            AuditAction($cfg["constants"]["error"], "CommandHandler _writeCommandFile-Error : ".$msg);
			return false;
		}
		return true;
    }

}

?>