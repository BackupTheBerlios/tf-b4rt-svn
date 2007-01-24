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
define('_DUMP_DELIM', '*');
preg_match('|.* (\d+) .*|', '$Revision$', $revisionMatches);
define('_REVISION_FLUXCLI', $revisionMatches[1]);

/**
 * FluxCLI
 */
class FluxCLI
{
	// public fields

	// name
	var $name = "FluxCLI";

    // private fields

	// script
	var $_script = "fluxcli.php";

    // action
    var $_action = "";

    // params
    var $_params = array();

    // messages-array
    var $_messages = array();

	// =========================================================================
	// public static methods
	// =========================================================================

    /**
     * accessor for singleton
     *
     * @return FluxCLI
     */
    function getInstance() {
		global $instanceFluxCLI;
		return (isset($instanceFluxCLI))
			? $instanceFluxCLI
			: false;
    }

    /**
     * getAction
     *
     * @return string
     */
    function getAction() {
		global $instanceFluxCLI;
		return (isset($instanceFluxCLI))
			? $instanceFluxCLI->_action
			: "";
    }

    /**
     * getParams
     *
     * @return array
     */
    function getParams() {
		global $instanceFluxCLI;
		return (isset($instanceFluxCLI))
			? $instanceFluxCLI->_params
			: array();
    }

    /**
     * getMessages
     *
     * @return array
     */
    function getMessages() {
		global $instanceFluxCLI;
		return (isset($instanceFluxCLI))
			? $instanceFluxCLI->_messages
			: array();
    }

	/**
	 * process a request
	 *
	 * @param $args
	 * @return mixed
	 */
    function processRequest($args) {
		global $instanceFluxCLI;
    	// create new instance
    	$instanceFluxCLI = new FluxCLI($args);
		// call instance-method
		return (!$instanceFluxCLI)
			? false
			: $instanceFluxCLI->instance_processRequest();
    }

	// =========================================================================
	// ctor
	// =========================================================================

    /**
     * do not use direct, use the public static methods !
     *
	 * @param $args
     * @return FluxCLI
     */
    function FluxCLI($args) {
    	global $cfg;

		// parse args and set fields
		$argCount = count($args);
		if ($argCount < 1) {
			// invalid args
			$this->_outputError("invalid args.\n");
			return false;
		}
		$this->_script = $args[0];
		$this->_action = (isset($args[1])) ? $args[1] : "";
		$this->_params = ($argCount > 2) ? array_splice($argv, 2) : array();

		// set user-agent
		$cfg['user_agent'] = $this->name."/" . _REVISION_FLUXCLI;
		$_SERVER['HTTP_USER_AGENT'] = $this->name."/" . _REVISION_FLUXCLI;
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

		// action-switch
		switch ($this->_action) {

			case "transfers":
				echo "\ntransfers\n\n";
				break;


			case "version":
			case "-version":
			case "--version":
			case "-v":

				echo "\nversion\n\n";

				break;

			case "help":
			case "-help":
			case "--help":
			case "-h":
			default:
				$this->_printUsage();
				break;

		}

    }

	// =========================================================================
	// private methods
	// =========================================================================

    /**
     * output message
     *
     * @param $message
     */
	function _outputMessage($message) {
		printMessage($this->name, $message);
    }

    /**
     * output error
     *
     * @param $message
     */
	function _outputError($message) {
		printError($this->name, $message);
    }

    /**
     * prints usage
     */
    function _printUsage() {
		echo $this->name." Revision "._REVISION_FLUXCLI."\n"
		. "\n"
		. "Usage: ".$this->_script." action [extra-args]\n"
		. "\n"
		. "action: \n"
		. "  transfers   : print transfers.\n"
		. "  netstat     : print netstat.\n"
		. "  start       : start a transfer.\n"
		. "                extra-arg : name of transfer as known inside torrentflux\n"
		. "  stop        : stop a transfer.\n"
		. "                extra-arg : name of transfer as known inside torrentflux\n"
	    . "  start-all   : start all transfers.\n"
	    . "  resume-all  : resume all transfers.\n"
		. "  stop-all    : stop all running transfers.\n"
		. "  reset       : reset totals of a transfer.\n"
		. "                extra-arg : name of transfer as known inside torrentflux\n"
		. "  delete      : delete a transfer.\n"
		. "                extra-arg : name of transfer as known inside torrentflux\n"
		. "  wipe        : reset totals, delete metafile, delete data.\n"
		. "                extra-arg : name of transfer as known inside torrentflux\n"
		. "  inject      : injects a transfer-file into tflux.\n"
		. "                extra-arg 1 : path to transfer-meta-file\n"
		. "                extra-arg 2 : username of fluxuser\n"
		. "  watch       : watch a dir and inject+start transfers into tflux.\n"
		. "                extra-arg 1 : path to users watch-dir\n"
		. "                extra-arg 2 : username of fluxuser\n"
		. "  rss         : download torrents matching filter-rules from a rss-feed.\n"
		. "                extra-arg 1 : save-dir\n"
		. "                extra-arg 2 : filter-file\n"
		. "                extra-arg 3 : history-file\n"
		. "                extra-arg 4 : rss-feed-url\n"
		. "                extra-arg 5 : use cookies from this torrentflux user\n"
		. "  xfer        : xfer-Limit-Shutdown. stop all transfers if xfer-limit is met.\n"
		. "                extra-arg 1 : time-delta of xfer to use : <all|total|month|week|day>\n"
		. "  repair      : repair of torrentflux. DONT do this unless you have to.\n"
		. "                Doing this on a running ok flux _will_ screw up things.\n"
		. "  maintenance : call maintenance and repair all died transfers.\n"
		. "                extra-arg 1 : restart died transfers (true/false)\n"
		. "  dump        : dump database.\n"
		. "                extra-arg 1 : type : settings/users\n"
		. "  filelist    : print file-list.\n"
		. "                extra-arg 1 : dir (if empty docroot is used)\n"
		. "  checksums   : print checksum-list.\n"
		. "                extra-arg 1 : dir (if empty docroot is used)\n"
		. "\n"
		. "examples: \n"
		. $this->_script." transfers\n"
		. $this->_script." netstat\n"
		. $this->_script." start foo.torrent\n"
		. $this->_script." stop foo.torrent\n"
		. $this->_script." start-all\n"
		. $this->_script." resume-all\n"
		. $this->_script." stop-all\n"
		. $this->_script." reset foo.torrent\n"
		. $this->_script." delete foo.torrent\n"
		. $this->_script." wipe foo.torrent\n"
		. $this->_script." inject /path/to/foo.torrent fluxuser\n"
	    . $this->_script." watch /path/to/watch-dir/ fluxuser\n"
	    . $this->_script." rss /path/to/rss-torrents/ /path/to/filter.dat /path/to/filter.hist http://www.example.com/rss.xml fluxuser\n"
	    . $this->_script." xfer month\n"
		. $this->_script." repair\n"
		. $this->_script." maintenance true\n"
		. $this->_script." dump settings\n"
		. $this->_script." dump users\n"
		. $this->_script." filelist /var/www\n"
		. $this->_script." checksums /var/www\n"
		. "\n";
    }

}

?>