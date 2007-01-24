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
    var $_paramc = 0;

    // arg-errors-array
    var $_argErrors = array();

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

		// set user-var
		$cfg["user"] = GetSuperAdmin();

		// set admin-var
		$cfg['isAdmin'] = true;

		// set user-agent
		$cfg['user_agent'] = $this->name."/" . _REVISION_FLUXCLI;
		$_SERVER['HTTP_USER_AGENT'] = $this->name."/" . _REVISION_FLUXCLI;

		// parse args and set fields
		$argCount = count($args);
		if ($argCount < 1) {
			// invalid args
			$this->_outputError("invalid args.\n");
			return false;
		}
		$this->_script = basename($args[0]);
		$this->_action = (isset($args[1])) ? $args[1] : "";
		if ($argCount > 2) {
			$prm = array_splice($args, 2);
			$this->_params = array_map('trim', $prm);
			$this->_paramc = count($this->_params);
		} else {
			$this->_params = array();
			$this->_paramc = 0;
		}
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

		// action-switch
		switch ($this->_action) {

			/* netstat */
			case "netstat":
				return $this->_netstat();

			/* transfers */
			case "transfers":
				return $this->_transfers();

			/* start */
			case "start":
				if (empty($this->_params[0])) {
					array_push($this->_argErrors, "missing argument: name of transfer. (extra-arg 1)");
					break;
				} else {
					return $this->_transferStart($this->_params[0]);
				}

			/* stop */
			case "stop":
				if (empty($this->_params[0])) {
					array_push($this->_argErrors, "missing argument: name of transfer. (extra-arg 1)");
					break;
				} else {
					return $this->_transferStop($this->_params[0]);
				}

			/* reset */
			case "reset":
				if (empty($this->_params[0])) {
					array_push($this->_argErrors, "missing argument: name of transfer. (extra-arg 1)");
					break;
				} else {
					return $this->_transferReset($this->_params[0]);
				}

			/* delete */
			case "delete":
				if (empty($this->_params[0])) {
					array_push($this->_argErrors, "missing argument: name of transfer. (extra-arg 1)");
					break;
				} else {
					return $this->_transferDelete($this->_params[0]);
				}

			/* wipe */
			case "wipe":
				if (empty($this->_params[0])) {
					array_push($this->_argErrors, "missing argument: name of transfer. (extra-arg 1)");
					break;
				} else {
					return $this->_transferWipe($this->_params[0]);
				}

			/* inject */
			case "inject":
				if ($this->_paramc < 2) {
					array_push($this->_argErrors, "missing argument(s) for inject.");
					break;
				} else {
					return $this->_transferInject($this->_params[0], $this->_params[1]);
				}

			/* start-all */
			case "start-all":
				return $this->_transfersStart();

			/* resume-all */
			case "resume-all":
				return $this->_transfersResume();

			/* stop-all */
			case "stop-all":
				return $this->_transfersStop();

			/* watch */
			case "watch":
				if ($this->_paramc < 2) {
					array_push($this->_argErrors, "missing argument(s) for watch.");
					break;
				} else {
					return $this->_watch($this->_params[0], $this->_params[1]);
				}

			/* rss */
			case "rss":
				if ($this->_paramc < 4) {
					array_push($this->_argErrors, "missing argument(s) for rss.");
					break;
				} else {
					return $this->_rss(
						$this->_params[0], $this->_params[1],
						$this->_params[2], $this->_params[3],
						empty($this->_params[4]) ? "" : $this->_params[4]
					);
				}

			/* xfer */
			case "xfer":
				if (empty($this->_params[0])) {
					array_push($this->_argErrors, "missing argument: time-delta of xfer to use : (all/total/month/week/day) (extra-arg 1)");
					break;
				} else {
					return $this->_xferShutdown($this->_params[0]);
				}

			/* repair */
			case "repair":
				return $this->_repair();

	        /* maintenance */
			case "maintenance":
				return $this->_maintenance(((isset($this->_params[0])) && ($this->_params[0] == "true")) ? true : false);
	        	return true;

	        /* dump */
			case "dump":
				if (empty($this->_params[0])) {
					array_push($this->_argErrors, "missing argument: type. (settings/users) (extra-arg 1)");
					break;
				} else {
					return $this->_databaseDump($this->_params[0]);
				}

			/* filelist */
			case "filelist":
				printFileList((empty($this->_params[0])) ? $cfg['docroot'] : $this->_params[0], 1, 1);
				return true;

			/* checksums */
			case "checksums":
				printFileList((empty($this->_params[0])) ? $cfg['docroot'] : $this->_params[0], 2, 1);
				return true;

			/* version */
			case "version":
			case "-version":
			case "--version":
			case "-v":
				return $this->_printVersion();

			/* help */
			case "help":
			case "-help":
			case "--help":
			case "-h":
			default:
				return $this->_printUsage();

		}

		// help
		return $this->_printUsage();
    }

	// =========================================================================
	// private methods
	// =========================================================================

	/**
	 * Print Net Stat
	 *
	 * @return mixed
	 */
	function _netstat() {
		// TODO
		return true;
	}

	/**
	 * Show Transfers
	 *
	 * @return mixed
	 */
	function _transfers() {
		// TODO
		return true;
	}

	/**
	 * Start Transfer
	 *
	 * @param $transfer
	 * @return mixed
	 */
	function _transferStart($transfer) {
		echo $transfer."\n";
		// TODO
		return true;
	}

	/**
	 * Stop Transfer
	 *
	 * @param $transfer
	 * @return mixed
	 */
	function _transferStop($transfer) {
		echo $transfer."\n";
		// TODO
		return true;
	}

	/**
	 * Reset Transfer
	 *
	 * @param $transfer
	 * @return mixed
	 */
	function _transferReset($transfer) {
		// TODO
		return true;
	}

	/**
	 * Delete Transfer
	 *
	 * @param $transfer
	 * @return mixed
	 */
	function _transferDelete($transfer) {
		// TODO
		return true;
	}

	/**
	 * Wipe Transfer
	 *
	 * @param $transfer
	 * @return mixed
	 */
	function _transferWipe($transfer) {
		// TODO
		return true;
	}

	/**
	 * Inject Transfer
	 *
	 * @param $transferFile
	 * @param $username
	 * @return mixed
	 */
	function _transferInject($transferFile, $username) {
		// TODO
		return true;
	}

	/**
	 * Start Transfers
	 *
	 * @return mixed
	 */
	function _transfersStart() {
		// TODO
		return true;
	}

	/**
	 * Resume Transfers
	 *
	 * @return mixed
	 */
	function _transfersResume() {
		// TODO
		return true;
	}

	/**
	 * Stop Transfers
	 *
	 * @return mixed
	 */
	function _transfersStop() {
		// TODO
		return true;
	}

	/**
	 * Watch Dir
	 *
	 * @param $watchDir
	 * @param $username
	 * @return mixed
	 */
	function _watch($watchDir, $username) {
		// TODO
		return true;
	}

	/**
	 * Watch Dir
	 *
	 * @param $watchDir
	 * @param $username
	 * @return mixed
	 */
	/**
	 * rss download
	 *
	 * @param $saveDir
	 * @param $filterFile
	 * @param $historyFile
	 * @param $url
	 * @param $username
	 * @return mixed
	 */
	function _rss($saveDir, $filterFile, $historyFile, $url, $username = "") {
		global $cfg;
		// set user
		if (!empty($username))
			$cfg["user"] = $username;
		// process Feed
		require_once("inc/classes/Rssd.php");
		return Rssd::processFeed($saveDir, $filterFile, $historyFile, $url);
	}

	/**
	 * Xfer Shutdown
	 *
	 * @param $delta
	 * @return mixed
	 */
	function _xfer($delta) {
		// TODO
		return true;
	}

	/**
	 * Repair
	 *
	 * @return mixed
	 */
	function _repair() {
		require_once("inc/classes/MaintenanceAndRepair.php");
		MaintenanceAndRepair::repair();
		return true;
	}

	/**
	 * Maintenance
	 *
	 * @param $trestart
	 * @return mixed
	 */
	function _maintenance($trestart) {
		require_once("inc/classes/MaintenanceAndRepair.php");
		MaintenanceAndRepair::maintenance($trestart);
		return true;
	}

	/**
	 * Dump Database
	 *
	 * @param $type
	 * @return mixed
	 */
	function _dump($type) {
		// TODO
		return true;
	}

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
     * prints version
     *
	 * @return mixed
     */
    function _printVersion() {
    	echo $this->name." Revision "._REVISION_FLUXCLI."\n";
    	return true;
    }

    /**
     * prints usage
     *
	 * @return mixed
     */
    function _printUsage() {
		$this->_printVersion();
		echo "\n"
		. "Usage: ".$this->_script." action [extra-args]\n"
		. "\n"
		. "action: \n"
		. "  transfers   : show transfers.\n"
		. "  netstat     : show netstat.\n"
		. "  start       : start a transfer.\n"
		. "                extra-arg : name of transfer as known inside webapp\n"
		. "  stop        : stop a transfer.\n"
		. "                extra-arg : name of transfer as known inside webapp\n"
	    . "  start-all   : start all transfers.\n"
	    . "  resume-all  : resume all transfers.\n"
		. "  stop-all    : stop all running transfers.\n"
		. "  reset       : reset totals of a transfer.\n"
		. "                extra-arg : name of transfer as known inside webapp\n"
		. "  delete      : delete a transfer.\n"
		. "                extra-arg : name of transfer as known inside webapp\n"
		. "  wipe        : reset totals, delete metafile, delete data.\n"
		. "                extra-arg : name of transfer as known inside webapp\n"
		. "  inject      : injects a transfer-file into the application.\n"
		. "                extra-arg 1 : path to transfer-meta-file\n"
		. "                extra-arg 2 : username of fluxuser\n"
		. "  watch       : watch a dir and inject+start transfers into app.\n"
		. "                extra-arg 1 : path to users watch-dir\n"
		. "                extra-arg 2 : username of fluxuser\n"
		. "  rss         : download torrents matching filter-rules from a rss-feed.\n"
		. "                extra-arg 1 : save-dir\n"
		. "                extra-arg 2 : filter-file\n"
		. "                extra-arg 3 : history-file\n"
		. "                extra-arg 4 : rss-feed-url\n"
		. "                extra-arg 5 : use cookies from this torrentflux user (optional, default is none)\n"
		. "  xfer        : xfer-Limit-Shutdown. stop all transfers if xfer-limit is met.\n"
		. "                extra-arg 1 : time-delta of xfer to use : (all/total/month/week/day)\n"
		. "  repair      : repair of torrentflux. DONT do this unless you have to.\n"
		. "                Doing this on a running ok flux _will_ screw up things.\n"
		. "  maintenance : call maintenance and repair all died transfers.\n"
		. "                extra-arg 1 : restart died transfers (true/false. optional, default is false)\n"
		. "  dump        : dump database.\n"
		. "                extra-arg 1 : type. (settings/users)\n"
		. "  filelist    : print file-list.\n"
		. "                extra-arg 1 : dir (optional, default is docroot)\n"
		. "  checksums   : print checksum-list.\n"
		. "                extra-arg 1 : dir (optional, default is docroot)\n"
		. "\n"
		. "examples:\n"
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
		if (count($this->_argErrors) > 0) {
			echo "arg-error(s) :\n"
			. implode("\n", $this->_argErrors)
			. "\n\n";
			return false;
		}
		return true;
    }


}

?>