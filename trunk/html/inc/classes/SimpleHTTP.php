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
 * SimpleHTTP
 */
class SimpleHTTP
{
	// fields

	// version
    var $version = "0.1";

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
	 * Error string used in fsockopen
	 * @param	string	$errstr
	 */
	var $errstr = "";

	/**
	 * Error number used in fsockopen
	 * @param	int		$errno
	 */
	var $errno = 0;

    // filename
    var $filename = "";

    // url
    var $url = "";

    // referer
    var $referer = "";

    // config-array
    var $cfg = array();

    // messages
    var $messages = array();

    // state
    //  0 : not initialized
    //  1 : initialized
    //  2 : last op done suc.
    // -1 : error
    var $state = 0;

    // mode
    // 1 : cli
    // 2 : web
    var $mode = 0;

    // factory + ctor

    /**
     * factory
     *
     * @param $cfg
     * @return SimpleHTTP
     */
    function getInstance($cfg) {
    	return new SimpleHTTP(serialize($cfg));
    }

    /**
     * do not use direct, use the factory-method !
     *
     * @param $cfg
     * @return SimpleHTTP
     */
    function SimpleHTTP($cfg) {
        $this->cfg = unserialize($cfg);
        if (empty($this->cfg)) {
            array_push($this->messages , "Config not passed");
            $this->state = -1;
            return false;
        }
        // cli/web
		global $argv;
		if (isset($argv)) {
			$this->mode = 1;
		} else
			$this->mode = 2;
		// ini-settings
		@ini_set("allow_url_fopen", "1");
		if (isset($_SERVER['HTTP_USER_AGENT']))
			@ini_set("user_agent", $_SERVER['HTTP_USER_AGENT']);
		else
			@ini_set("user_agent", "torrentflux-b4rt/". $this->cfg["version"]);
        // state
        $this->state = 1;
    }

    // public meths

	/**
	 * method to get data from URL -- uses timeout and user agent
	 *
	 * @param $get_url
	 * @param $get_referer
	 * @return string
	 */
	function getData($get_url, $get_referer = "") {
		global $db;

		// set fields
		$this->url = $get_url;
		$this->referer = $get_referer;

		// (re-)set some vars
		$this->cookie = "";
		$this->request = "";
		$this->responseBody = "";
		$this->responseHeaders = array();
		$this->gotResponseLine = false;
		$this->status = "";
		$this->errstr = "";
		$this->errno = 0;

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
		$sql = "SELECT c.data FROM tf_cookies AS c LEFT JOIN tf_users AS u ON ( u.uid = c.uid ) WHERE u.user_id = '" . $this->cfg["user"] . "' AND c.host = '" . $domain['host'] . "'";
		$this->cookie = $db->GetOne($sql);
		showError($db, $sql);

		if (!array_key_exists("port", $domain))
			$domain["port"] = 80;

		// Check to see if this site requires the use of cookies
		// Whilst in SVN/testing, always use the cookie/raw HTTP handling code:
		if (true || !empty($this->cookie)) {
			$socket = @fsockopen($domain["host"], $domain["port"], $this->errno , $this->errstr, $this->timeout); //connect to server

			if(!empty($socket)) {
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
				$this->request  = "GET " . ($this->httpVersion=="1.1" ? $this->getcmd : $this->url ). " HTTP/" . $this->httpVersion ."\r\n";
				$this->request .= (!empty($this->referer)) ? "Referer: " . $this->referer . "\r\n" : "";
				$this->request .= "Accept: */*\r\n";
				$this->request .= "Accept-Language: en-us\r\n";
				$this->request .= "User-Agent: ".$_SERVER['HTTP_USER_AGENT']."\r\n";
				$this->request .= "Host: " . $domain["host"] . "\r\n";
				if($this->httpVersion=="1.1"){
					$this->request .= "Connection: Close\r\n";
				}
				$this->request .= "Cookie: " . $this->cookie . "\r\n\r\n";

				// Send header packet information to server
				fputs($socket, $this->request);

				// Get response headers:
				while ($line=@fgets($socket, 500000)){
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
					$chunkSize = hexdec(trim(fgets($socket)));

					// 0 size chunk indicates end of content:
					while($chunkSize > 0){
						// Read in up to $chunkSize chars:
						$line=@fgets($socket, $chunkSize);

						// Discard crlf after current chunk:
						fgets($socket);

						// Append chunk to response body:
						$this->responseBody.=$line;

						// Keep track of total chunk/content length:
						$chunkLength+=$chunkSize;

						// Read next chunk size:
						$chunkSize = hexdec(trim(fgets($socket)));
					}
					$this->responseHeaders["content-length"] = $chunkLength;
				} else {
					while ($line=@fread($socket, 500000)){
						$this->responseBody .= $line;
					}
				}
				@fclose($socket); // Close our connection
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
			$ch = curl_init();

			if ($this->cookie != "")
				curl_setopt($ch, CURLOPT_COOKIE, $this->cookie);

			curl_setopt($ch, CURLOPT_PORT, $domain["port"]);
			curl_setopt($ch, CURLOPT_URL, $this->url);
			curl_setopt($ch, CURLOPT_VERBOSE, FALSE);
			curl_setopt($ch, CURLOPT_HEADER, FALSE);
			curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
			curl_setopt($ch, CURLOPT_MAXREDIRS, 5);

			$this->responseBody = curl_exec($ch);

			curl_close($ch);
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
        $this->state = 2;

		// return content
		return $this->responseBody;
	}

	/**
	 * get torrent from URL. Has support for specific sites
	 *
	 * @param $turl
	 * @return string
	 */
	function getTorrent($turl) {

		// Initialize torrent name:
		$this->filename = "";

		$domain	 = parse_url($turl);

		// Check we have a remote URL:
		if(!isset($domain["host"])){
			// Not a remote URL:
			$msg = "The torrent requested for download (".$turl.") is not a remote torrent.  Please enter a valid remote torrent URL such as http://example.com/example.torrent\n";
			AuditAction($this->cfg["constants"]["error"], $msg);
			array_push($this->messages , $msg);
			// state
        	$this->state = -1;
			// return empty data:
			return($data="");
		}

		if (strtolower(substr($domain["path"], -8)) != ".torrent") {
			/*
				In these cases below, we check for torrent URLs that have to be manipulated in some
				way to obtain the torrent content.  These are sites that perhaps use redirection or
				URL rewriting in some way.
			*/
			// Check known domain types
			if (strpos(strtolower($domain["host"]), "mininova") !== false) {
				// Sample (http://www.mininova.org/rss.xml):
				// http://www.mininova.org/tor/2254847
				// <a href="/get/2281554">FreeLinux.ISO.iso.torrent</a>
				// If received a /tor/ get the required information
				if (strpos($turl, "/tor/") !== false) {
					// Get the contents of the /tor/ to find the real torrent name
					$data = $this->getData($turl);
					// Check for the tag used on mininova.org
					if (preg_match("/<a href=\"\/get\/[0-9].[^\"]+\">(.[^<]+)<\/a>/i", $data, $data_preg_match)) {
						// This is the real torrent filename
						$this->filename = $data_preg_match[1];
					}
					// Change to GET torrent url
					$turl = str_replace("/tor/", "/get/", $turl);
				}

				// Now fetch the torrent file
				$data = $this->getData($turl);
			} elseif (strpos(strtolower($domain["host"]), "isohunt") !== false) {
				// Sample (http://isohunt.com/js/rss.php):
				// http://isohunt.com/download.php?mode=bt&id=8837938
				// http://isohunt.com/btDetails.php?ihq=&id=8464972
				$treferer = "http://" . $domain["host"] . "/btDetails.php?id=";

				// If the url points to the details page, change it to the download url
				if (strpos(strtolower($turl), "/btdetails.php?") !== false) {
					// Need to make it grab the torrent
					$turl = str_replace("/btDetails.php?", "/download.php?", $turl) . "&mode=bt";
				}

				// Grab contents of details page
				$data = $this->getData($turl, $treferer);
			} elseif (strpos(strtolower($turl), "details.php?") !== false) {
				// Sample (http://www.bitmetv.org/rss.php?passkey=123456):
				// http://www.bitmetv.org/details.php?id=18435&hit=1
				$treferer = "http://" . $domain["host"] . "/details.php?id=";
				$data = $this->getData($turl, $treferer);

				// Sample (http://www.bitmetv.org/details.php?id=18435)
				// download.php/18435/SpiderMan%20Season%204.torrent
				if (preg_match("/(download.php.[^\"]+)/i", $data, $data_preg_match)) {
					$torrent = str_replace(" ", "%20", substr($data_preg_match[0], 0, -1));
					$turl2 = "http://" . $domain["host"] . "/" . $torrent;
					$data = $this->getData($turl2);
				} else {
					$msg = "Error: could not find link to torrent file in $turl";
					AuditAction($this->cfg["constants"]["error"], $msg);
					array_push($this->messages , $msg);
					// state
			    	$this->state = -1;
					// return empty data:
					return($data="");
				}
			} elseif (strpos(strtolower($turl), "download.asp?") !== false) {
				// Sample (TF's TorrenySpy Search):
				// http://www.torrentspy.com/download.asp?id=519793
				$treferer = "http://" . $domain["host"] . "/download.asp?id=";
				$data = $this->getData($turl, $treferer);
			} else {
				// Fallback case for any URL not ending in .torrent and not matching the above cases:
				$data = $this->getData($turl);
			}
		} else {
			$data = $this->getData($turl);
		}
		// Make sure we have a torrent file
		if (strpos($data, "d8:") === false)	{
			// We don't have a Torrent File... it is something else.  Let the user know about it:
			$msg = "Content returned from $turl does not appear to be a valid torrent.";
			AuditAction($this->cfg["constants"]["error"], $msg);
			array_push($this->messages , $msg);
			// Display the first part of $data if debuglevel higher than 1:
			if($this->cfg["debuglevel"] > 1){
				if(strlen($data) > 0){
					array_push($this->messages , "  Displaying first 1024 chars of output: ".htmlentities(substr($data, 0, 1023)), ENT_QUOTES);
				} else {
					array_push($this->messages , "  Output from $turl was empty.");
				}
			} else {
				array_push($this->messages , "  Set debuglevel > 2 in 'Admin, Webapps' to see the content returned from $turl.");
			}
			$data = "";
			// state
			$this->state = -1;
		} else {
			// If the torrent file name isn't set already, do it now:
			if ((!isset($this->filename)) || (strlen($this->filename) == 0)) {
				// Get the name of the torrent, and make it the filename
				if (preg_match("/name([0-9][^:]):(.[^:]+)/i", $data, $data_preg_match)) {
					$filelength = $data_preg_match[1];
					$file_name = $data_preg_match[2];
					$this->filename = substr($file_name, 0, $filelength) . ".torrent";
				} else {
					$this->filename = "unknown.torrent";
				}
			}
	        // state
	        $this->state = 2;
		}
		return $data;
	}

}

?>