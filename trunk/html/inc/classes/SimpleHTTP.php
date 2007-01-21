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
define('SIMPLEHTTP_STATE_NULL', 0);                                      // null
define('SIMPLEHTTP_STATE_OK', 1);                                          // ok
define('SIMPLEHTTP_STATE_ERROR', -1);                                   // error

/**
 * SimpleHTTP
 */
class SimpleHTTP
{
	// public fields

    // timeout
    var $timeout = 20;

	/**
	 * Temporarily use HTTP/1.0 until chunked encoding is sorted out
	 * Valid values are '1.0' or '1.1'
	 * @param	string	$httpVersion
	 */
	var $httpVersion = "1.0";

	/**
	 * Cookie string used in raw HTTP request
	 * @param	string	$cookie
	 */
	var $cookie = "";

	/**
	 * URI/path used in GET request:
	 * @param	string	$getcmd
	 */
	var $getcmd	= "";

	/**
	 * the raw HTTP request to send to the remote webserver
	 * @param	string	$request
	 */
	var $request = "";

	/**
	 * the raw HTTP response received from the remote webserver
	 * @param	string	$responseBody
	 */
	var $responseBody = "";

	/**
	 * Array of HTTP response headers
	 * @param	array	$responseHeaders
	 */
	var $responseHeaders = array();

	/**
	 * Indicates if we got the response line or not from webserver
	 * 'HTTP/1.1 200 OK
	 * etc
	 * @param	bool	$gotResponseLine
	 */
	var $gotResponseLine = false;

	/**
	 * Status code of webserver resonse
	 * @param	string	$status
	 */
	var $status = "";

	/**
	 * socket
	 */
	var $socket = 0;

	/**
	 * Error string used in fsockopen
	 * @param	string	$errstr
	 */
	var $errstr = "";

	/**
	 * Error number used in fsockopen
	 * @param	int		$errno
	 */
	var $errno = 0;

	// user-agent
	var $userAgent = "";

    // filename
    var $filename = "";

    // url
    var $url = "";

    // referer
    var $referer = "";

    // messages
    var $messages = array();

    // state
    var $state = SIMPLEHTTP_STATE_NULL;

	// =========================================================================
	// public static methods
	// =========================================================================

    /**
     * accessor for singleton
     *
     * @return SimpleHTTP
     */
    function getInstance() {
		global $instanceSimpleHTTP;
		// initialize if needed
		if (!isset($instanceSimpleHTTP))
			SimpleHTTP::initialize();
		return $instanceSimpleHTTP;
    }

    /**
     * initialize SimpleHTTP.
     */
    function initialize() {
    	global $instanceSimpleHTTP;
    	// create instance
    	if (!isset($instanceSimpleHTTP))
    		$instanceSimpleHTTP = new SimpleHTTP();
    }

	/**
	 * getState
	 *
	 * @return state
	 */
    function getState() {
		global $instanceSimpleHTTP;
		return (isset($instanceSimpleHTTP))
			? $instanceSimpleHTTP->state
			: SIMPLEHTTP_STATE_NULL;
    }

    /**
     * getMessages
     *
     * @return array
     */
    function getMessages() {
		global $instanceSimpleHTTP;
		return (isset($instanceSimpleHTTP))
			? $instanceSimpleHTTP->messages
			: array();
    }

    /**
     * getMessages
     *
     * @return string
     */
    function getFilename() {
		global $instanceSimpleHTTP;
		return (isset($instanceSimpleHTTP))
			? $instanceSimpleHTTP->filename
			: "";
    }

	/**
	 * method to get data from URL -- uses timeout and user agent
	 *
	 * @param $get_url
	 * @param $get_referer
	 * @return string
	 */
	function getData($get_url, $get_referer = "") {
		global $instanceSimpleHTTP;
		// initialize if needed
		if (!isset($instanceSimpleHTTP))
			SimpleHTTP::initialize();
		// call instance-method
		return $instanceSimpleHTTP->instance_getData($get_url, $get_referer);
	}

	/**
	 * get torrent from URL. Has support for specific sites
	 *
	 * @param $durl
	 * @return string
	 */
	function getTorrent($durl) {
		global $instanceSimpleHTTP;
		// initialize if needed
		if (!isset($instanceSimpleHTTP))
			SimpleHTTP::initialize();
		// call instance-method
		return $instanceSimpleHTTP->instance_getTorrent($durl);
	}

	/**
	 * get nzb from URL.
	 *
	 * @param $durl
	 * @return string
	 */
	function getNzb($durl) {
		global $instanceSimpleHTTP;
		// initialize if needed
		if (!isset($instanceSimpleHTTP))
			SimpleHTTP::initialize();
		// call instance-method
		return $instanceSimpleHTTP->instance_getNzb($durl);
	}

	/**
	 * get size from URL.
	 *
	 * @param $durl
	 * @return int
	 */
	function getRemoteSize($durl) {
		global $instanceSimpleHTTP;
		// initialize if needed
		if (!isset($instanceSimpleHTTP))
			SimpleHTTP::initialize();
		// call instance-method
		return $instanceSimpleHTTP->instance_getRemoteSize($durl);
	}

	// =========================================================================
	// ctor
	// =========================================================================

    /**
     * do not use direct, use the factory-method !
     *
     * @return SimpleHTTP
     */
    function SimpleHTTP() {
    	global $cfg;
		// user-agent
		$this->userAgent = $cfg['user_agent'];
		// ini-settings
		@ini_set("allow_url_fopen", "1");
		@ini_set("user_agent", $this->userAgent);
    }

	// =========================================================================
	// public methods
	// =========================================================================

	/**
	 * method to get data from URL -- uses timeout and user agent
	 *
	 * @param $get_url
	 * @param $get_referer
	 * @return string
	 */
	function instance_getData($get_url, $get_referer = "") {
		global $cfg, $db;

		// set fields
		$this->url = $get_url;
		$this->referer = $get_referer;

    	// (re)set state
    	$this->state = SIMPLEHTTP_STATE_NULL;

		// (re-)set some vars
		$this->cookie = "";
		$this->request = "";
		$this->responseBody = "";
		$this->responseHeaders = array();
		$this->gotResponseLine = false;
		$this->status = "";
		$this->errstr = "";
		$this->errno = 0;
		$this->socket = 0;

		/**
		 * array of URL component parts for use in raw HTTP request
		 * @param	array	$domain
		 */
		$domain = parse_url($this->url);

		// get-command
		$this->getcmd = $domain["path"];

	    if (!array_key_exists("query", $domain))
	        $domain["query"] = "";

		// append the query string if included:
	    $this->getcmd .= (!empty($domain["query"])) ? "?" . $domain["query"] : "";

		// Check to see if cookie required for this domain:
		$sql = "SELECT c.data FROM tf_cookies AS c LEFT JOIN tf_users AS u ON ( u.uid = c.uid ) WHERE u.user_id = '" . $cfg["user"] . "' AND c.host = '" . $domain['host'] . "'";
		$this->cookie = $db->GetOne($sql);
		if ($db->ErrorNo() != 0) dbError($sql);

		if (!array_key_exists("port", $domain))
			$domain["port"] = 80;

		// Check to see if this site requires the use of cookies
		// Whilst in SVN/testing, always use the cookie/raw HTTP handling code:
		if (true || !empty($this->cookie)) {
			$this->socket = @fsockopen($domain["host"], $domain["port"], $this->errno , $this->errstr, $this->timeout); //connect to server

			if(!empty($this->socket)) {
				// Write the outgoing HTTP request using cookie info

				// Standard HTTP/1.1 request looks like:
				//
				// GET /url/path/example.php HTTP/1.1
				// Host: example.com
				// Accept: */*
				// Accept-Language: en-us
				// User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-GB; rv:1.8.1) Gecko/20061010 Firefox/2.0
				// Connection: Close
				// Cookie: uid=12345;pass=asdfasdf;
				//
				//$this->request  = "GET " . ($this->httpVersion=="1.1" ? $this->getcmd : $this->url ). " HTTP/" . $this->httpVersion ."\r\n";
				$this->request  = "GET ".$this->getcmd." HTTP/".$this->httpVersion."\r\n";
				$this->request .= (!empty($this->referer)) ? "Referer: " . $this->referer . "\r\n" : "";
				$this->request .= "Accept: */*\r\n";
				$this->request .= "Accept-Language: en-us\r\n";
				$this->request .= "User-Agent: ".$this->userAgent."\r\n";
				$this->request .= "Host: " . $domain["host"] . "\r\n";
				if($this->httpVersion=="1.1"){
					$this->request .= "Connection: Close\r\n";
				}
				$this->request .= "Cookie: " . $this->cookie . "\r\n\r\n";

				// Send header packet information to server
				fputs($this->socket, $this->request);

				// Get response headers:
				while ($line=@fgets($this->socket, 500000)){
					// First empty line/\r\n indicates end of response headers:
					if($line == "\r\n"){
						break;
					}

					if(!$this->gotResponseLine){
						preg_match("@HTTP/[^ ]+ (\d\d\d)@", $line, $matches);
						// TODO: Use this to see if we redirected (30x) and follow the redirect:
						$this->status = $matches[1];
						$this->gotResponseLine = true;
						continue;
					}

					// Get response headers:
					preg_match("/^([^:]+):\s*(.*)/", trim($line), $matches);
					$this->responseHeaders[strtolower($matches[1])] = $matches[2];
				}

				if(
					$this->httpVersion=="1.1"
					&& isset($this->responseHeaders["transfer-encoding"])
					&& !empty($this->responseHeaders["transfer-encoding"])
				){
					/*
					// NOT CURRENTLY WORKING, USE HTTP/1.0 ONLY UNTIL THIS IS FIXED!
					*/

					// Get body of HTTP response:
					// Handle chunked encoding:
					/*
							length := 0
							read chunk-size, chunk-extension (if any) and CRLF
							while (chunk-size > 0) {
							   read chunk-data and CRLF
							   append chunk-data to entity-body
							   length := length + chunk-size
							   read chunk-size and CRLF
							}
					*/

					// Used to count total of all chunk lengths, the content-length:
					$chunkLength=0;

					// Get first chunk size:
					$chunkSize = hexdec(trim(fgets($this->socket)));

					// 0 size chunk indicates end of content:
					while($chunkSize > 0){
						// Read in up to $chunkSize chars:
						$line=@fgets($this->socket, $chunkSize);

						// Discard crlf after current chunk:
						fgets($this->socket);

						// Append chunk to response body:
						$this->responseBody.=$line;

						// Keep track of total chunk/content length:
						$chunkLength+=$chunkSize;

						// Read next chunk size:
						$chunkSize = hexdec(trim(fgets($this->socket)));
					}
					$this->responseHeaders["content-length"] = $chunkLength;
				} else {
					while ($line=@fread($this->socket, 500000)){
						$this->responseBody .= $line;
					}
				}
				@fclose($this->socket); // Close our connection
			} else {
				return "Error fetching ".$this->url.".  PHP Error No=".$this->errno." . PHP Error String=".$this->errstr;
			}
		} else {
			// No cookies - no need for raw HTTP:
			if ($fp = @fopen($this->url, 'r')) {
				while (!@feof($fp))
					$this->responseBody .= @fgets($fp, 4096);

				@fclose($fp);
			}
		}

		// If no response from server or we were redirected with 30x response,
		// try cURL:
		if (
				($this->responseBody == "" && function_exists("curl_init"))
				||
				(preg_match("#HTTP/1\.[01] 30#", $this->responseBody) > 0 && function_exists("curl_init"))
			){

			// Give CURL a Try
			$curl = curl_init();
			if ($this->cookie != "")
				curl_setopt($curl, CURLOPT_COOKIE, $this->cookie);
			curl_setopt($curl, CURLOPT_PORT, $domain["port"]);
			curl_setopt($curl, CURLOPT_URL, $this->url);
			curl_setopt($curl, CURLOPT_VERBOSE, FALSE);
			curl_setopt($curl, CURLOPT_HEADER, FALSE);
			curl_setopt($curl, CURLOPT_USERAGENT, $this->userAgent);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
			curl_setopt($curl, CURLOPT_MAXREDIRS, 5);
			$this->responseBody = curl_exec($curl);
			curl_close($curl);
		}

		// Trim any extraneous linefeed chars:
		$this->responseBody = trim($this->responseBody, "\r\n");

		// If a filename is associated with this content, assign it to $filename
		if (isset($this->responseHeaders["content-disposition"]) && !empty($this->responseHeaders["content-disposition"])) {
			// Content-disposition: attachment; filename="nameoffile":
			// Don't think single quotes can be used to escape filename here, but just in case check for ' and ":
			if (preg_match("/filename=(['\"])([^\\1]+)\\1/", $this->responseHeaders["content-disposition"], $matches)) {
				if(isset($matches[2]) && !empty($matches[2])){
					$file_name = $matches[2];
					// Only accept filenames, not paths:
					if (!preg_match("@/@", $file_name))
						$this->filename = $file_name;
				}
			}
		}

        // state
        $this->state = SIMPLEHTTP_STATE_OK;

		// return content
		return $this->responseBody;
	}

	/**
	 * get torrent from URL. Has support for specific sites
	 *
	 * @param $durl
	 * @return string
	 */
	function instance_getTorrent($durl) {
		global $cfg;

    	// (re)set state
    	$this->state = SIMPLEHTTP_STATE_NULL;

		// Initialize file name:
		$this->filename = "";

		$domain	 = parse_url($durl);

		// Check we have a remote URL:
		if (!isset($domain["host"])) {
			// Not a remote URL:
			$msg = "The torrent requested for download (".$durl.") is not a remote torrent. Please enter a valid remote torrent URL such as http://example.com/example.torrent\n";
			AuditAction($cfg["constants"]["error"], $msg);
			array_push($this->messages , $msg);
			// state
        	$this->state = SIMPLEHTTP_STATE_ERROR;
			// return empty data:
			return ($data="");
		}

		if (strtolower(substr($domain["path"], -8)) != ".torrent") {
			/*
				In these cases below, we check for torrent URLs that have to be manipulated in some
				way to obtain the torrent content.  These are sites that perhaps use redirection or
				URL rewriting in some way.
			*/
			// Check known domain types
			// mininova
			if (strpos(strtolower($domain["host"]), "mininova") !== false) {
				// Sample (http://www.mininova.org/rss.xml):
				// http://www.mininova.org/tor/2254847
				// <a href="/get/2281554">FreeLinux.ISO.iso.torrent</a>
				// If received a /tor/ get the required information
				if (strpos($durl, "/tor/") !== false) {
					// Get the contents of the /tor/ to find the real torrent name
					$data = $this->instance_getData($durl);
					// Check for the tag used on mininova.org
					if (preg_match("/<a href=\"\/get\/[0-9].[^\"]+\">(.[^<]+)<\/a>/i", $data, $data_preg_match)) {
						// This is the real torrent filename
						$this->filename = $data_preg_match[1];
					}
					// Change to GET torrent url
					$durl = str_replace("/tor/", "/get/", $durl);
				}
				// Now fetch the torrent file
				$data = $this->instance_getData($durl);
			// isohunt
			} elseif (strpos(strtolower($domain["host"]), "isohunt") !== false) {
				// Sample (http://isohunt.com/js/rss.php):
				// http://isohunt.com/download.php?mode=bt&id=8837938
				// http://isohunt.com/btDetails.php?ihq=&id=8464972
				$treferer = "http://" . $domain["host"] . "/btDetails.php?id=";

				// If the url points to the details page, change it to the download url
				if (strpos(strtolower($durl), "/btdetails.php?") !== false) {
					// Need to make it grab the torrent
					$durl = str_replace("/btDetails.php?", "/download.php?", $durl) . "&mode=bt";
				}
				// Grab contents of details page
				$data = $this->instance_getData($durl, $treferer);
			// details.php
			} elseif (strpos(strtolower($durl), "details.php?") !== false) {
				// Sample (http://www.bitmetv.org/rss.php?passkey=123456):
				// http://www.bitmetv.org/details.php?id=18435&hit=1
				$treferer = "http://" . $domain["host"] . "/details.php?id=";
				$data = $this->instance_getData($durl, $treferer);

				// Sample (http://www.bitmetv.org/details.php?id=18435)
				// download.php/18435/SpiderMan%20Season%204.torrent
				if (preg_match("/(download.php.[^\"]+)/i", $data, $data_preg_match)) {
					$torrent = str_replace(" ", "%20", substr($data_preg_match[0], 0, -1));
					$turl2 = "http://" . $domain["host"] . "/" . $torrent;
					$data = $this->instance_getData($turl2);
				} else {
					$msg = "Error: could not find link to torrent file in $durl";
					AuditAction($cfg["constants"]["error"], $msg);
					array_push($this->messages , $msg);
					// state
			    	$this->state = SIMPLEHTTP_STATE_ERROR;
					// return empty data:
					return($data="");
				}
			// download.asp
			} elseif (strpos(strtolower($durl), "download.asp?") !== false) {
				// Sample (TF's TorrenySpy Search):
				// http://www.torrentspy.com/download.asp?id=519793
				$treferer = "http://" . $domain["host"] . "/download.asp?id=";
				$data = $this->instance_getData($durl, $treferer);
			// default
			} else {
				// Fallback case for any URL not ending in .torrent and not matching the above cases:
				$data = $this->instance_getData($durl);
			}
		} else {
			$data = $this->instance_getData($durl);
		}
		// Make sure we have a torrent file
		if (strpos($data, "d8:") === false)	{
			// We don't have a Torrent File... it is something else.  Let the user know about it:
			$msg = "Content returned from $durl does not appear to be a valid torrent.";
			AuditAction($cfg["constants"]["error"], $msg);
			array_push($this->messages , $msg);
			// Display the first part of $data if debuglevel higher than 1:
			if ($cfg["debuglevel"] > 1){
				if (strlen($data) > 0){
					array_push($this->messages , "Displaying first 1024 chars of output: ");
					array_push($this->messages , htmlentities(substr($data, 0, 1023)), ENT_QUOTES);
				} else {
					array_push($this->messages , "Output from $durl was empty.");
				}
			} else {
				array_push($this->messages , "Set debuglevel > 2 in 'Admin, Webapps' to see the content returned from $durl.");
			}
			$data = "";
			// state
			$this->state = SIMPLEHTTP_STATE_ERROR;
		} else {
			// If the torrent file name isn't set already, do it now:
			if ((!isset($this->filename)) || (strlen($this->filename) == 0)) {
				// Get the name of the torrent, and make it the filename
				if (preg_match("/name([0-9][^:]):(.[^:]+)/i", $data, $data_preg_match)) {
					$filelength = $data_preg_match[1];
					$file_name = $data_preg_match[2];
					$this->filename = substr($file_name, 0, $filelength).".torrent";
				} else {
					require_once('inc/classes/BDecode.php');
				    $btmeta = @BDecode($data);
				    $this->filename = ((is_array($btmeta)) && (!empty($btmeta['info'])) && (!empty($btmeta['info']['name'])))
				    	? trim($btmeta['info']['name']).".torrent"
				    	: "";
					}
			}
	        // state
	        $this->state = SIMPLEHTTP_STATE_OK;
		}
		return $data;
	}

	/**
	 * get nzb from URL
	 *
	 * @param $durl
	 * @return string
	 */
	function instance_getNzb($durl) {
		global $cfg;

    	// (re)set state
    	$this->state = SIMPLEHTTP_STATE_NULL;

		// Initialize file name:
		$this->filename = "";

		$domain	 = parse_url($durl);

		// Check we have a remote URL:
		if (!isset($domain["host"])) {
			// Not a remote URL:
			$msg = "The nzb requested for download (".$durl.") is not a remote nzb. Please enter a valid remote nzb URL such as http://example.com/example.nzb\n";
			AuditAction($cfg["constants"]["error"], $msg);
			array_push($this->messages , $msg);
			// state
        	$this->state = SIMPLEHTTP_STATE_ERROR;
			// return empty data:
			return ($data="");
		}

		if (strtolower(substr($domain["path"], -4)) != ".nzb") {
			/*
				In these cases below, we check for URLs that have to be manipulated in some
				way to obtain the content.  These are sites that perhaps use redirection or
				URL rewriting in some way.
			*/
			// details.php
			if (strpos(strtolower($durl), "details.php?") !== false) {
				// Sample (http://www.bitmetv.org/rss.php?passkey=123456):
				// http://www.bitmetv.org/details.php?id=18435&hit=1
				$treferer = "http://" . $domain["host"] . "/details.php?id=";
				$data = $this->instance_getData($durl, $treferer);
				// Sample (http://www.bitmetv.org/details.php?id=18435)
				// download.php/18435/SpiderMan%20Season%204.torrent
				if (preg_match("/(download.php.[^\"]+)/i", $data, $data_preg_match)) {
					$tr = str_replace(" ", "%20", substr($data_preg_match[0], 0, -1));
					$turl2 = "http://" . $domain["host"] . "/" . $tr;
					$data = $this->instance_getData($turl2);
				} else {
					$msg = "Error: could not find link to nzb file in $durl";
					AuditAction($cfg["constants"]["error"], $msg);
					array_push($this->messages , $msg);
					// state
			    	$this->state = SIMPLEHTTP_STATE_ERROR;
					// return empty data:
					return($data="");
				}
			// download.asp
			} elseif (strpos(strtolower($durl), "download.asp?") !== false) {
				// Sample (TF's TorrenySpy Search):
				// http://www.torrentspy.com/download.asp?id=519793
				$treferer = "http://" . $domain["host"] . "/download.asp?id=";
				$data = $this->instance_getData($durl, $treferer);
			// default
			} else {
				// Fallback case for any URL not ending in .nzb and not matching the above cases:
				$data = $this->instance_getData($durl);
			}
		} else {
			$data = $this->instance_getData($durl);
		}
		// Make sure we have a nzb file
		if (strpos($data, "nzb") === false)	{
			// We don't have a nzb File... it is something else.  Let the user know about it:
			$msg = "Content returned from $durl does not appear to be a valid nzb.";
			AuditAction($cfg["constants"]["error"], $msg);
			array_push($this->messages , $msg);
			// Display the first part of $data if debuglevel higher than 1:
			if ($cfg["debuglevel"] > 1){
				if (strlen($data) > 0){
					array_push($this->messages , "Displaying first 1024 chars of output: ");
					array_push($this->messages , htmlentities(substr($data, 0, 1023)), ENT_QUOTES);
				} else {
					array_push($this->messages , "Output from $durl was empty.");
				}
			} else {
				array_push($this->messages , "Set debuglevel > 2 in 'Admin, Webapps' to see the content returned from $durl.");
			}
			$data = "";
			// state
			$this->state = SIMPLEHTTP_STATE_ERROR;
		} else {
	        // state
	        $this->state = SIMPLEHTTP_STATE_OK;
		}
		return $data;
	}

	/**
	 * get size from URL.
	 *
	 * @param $durl
	 * @return string
	 */
	function instance_getRemoteSize($durl) {
		// set fields
		$this->url = $durl;
		$this->timeout = 8;
		$this->status = "";
		$this->errstr = "";
		$this->errno = 0;
		// domain
		$domain = parse_url($this->url);
		if (!isset($domain["port"]))
			$domain["port"] = 80;
		// check we have a remote URL:
		if (!isset($domain["host"]))
			return 0;
		// check we have a remote path:
		if (!isset($domain["path"]))
			return 0;
		// open socket
		$this->socket = @fsockopen($domain["host"], $domain["port"], $this->errno, $this->errstr, $this->timeout);
		if (!$this->socket)
			return 0;
		// send HEAD request
		$this->request  = "HEAD ".$domain["path"]." HTTP/1.0\r\nConnection: Close\r\n\r\n";
		@fwrite($this->socket, $this->request);
		// read the response
		$this->responseBody = "";
		for ($i = 0; $i < 25; $i++) {
			$s = @fgets($this->socket, 4096);
			$this->responseBody .= $s;
			if (strcmp($s, "\r\n") == 0 || strcmp($s, "\n") == 0)
				break;
		}
		// close socket
		fclose($this->socket);
		// try to get Content-Length in response-body
		preg_match('/Content-Length:\s([0-9].+?)\s/', $this->responseBody, $matches);
		return (isset($matches[1]))
			? $matches[1]
			: 0;
	}

}

?>