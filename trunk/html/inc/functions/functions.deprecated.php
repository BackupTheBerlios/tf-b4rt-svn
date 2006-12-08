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
 * get data from URL. Has support for specific sites
 *
 * @param $url
 * @return string
 */
function FetchTorrent($url) {
	global $cfg, $db, $messages;

	// Initialize torrent name:
	$cfg["save_torrent_name"] = "";

	ini_set("allow_url_fopen", "1");
	ini_set("user_agent", $_SERVER['HTTP_USER_AGENT']);
	$domain	 = parse_url($url);

	// Check we have a remote URL:
	if(!isset($domain["host"])){
		// Not a remote URL:
		$messages="The torrent requested for download (".$url.") is not a remote torrent.  Please enter a valid remote torrent URL such as http://example.com/example.torrent\n";
		AuditAction($cfg["constants"]["error"], $messages);

		// return empty HTML:
		return($html="");
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
			if (strpos($url, "/tor/") !== false) {
				// Get the contents of the /tor/ to find the real torrent name
				$html = FetchHTML($url);
				// Check for the tag used on mininova.org
				if (preg_match("/<a href=\"\/get\/[0-9].[^\"]+\">(.[^<]+)<\/a>/i", $html, $html_preg_match)) {
					// This is the real torrent filename
					$cfg["save_torrent_name"] = $html_preg_match[1];
				}
				// Change to GET torrent url
				$url = str_replace("/tor/", "/get/", $url);
			}

			// Now fetch the torrent file
			$html = FetchHTML($url);
		} elseif (strpos(strtolower($domain["host"]), "isohunt") !== false) {
			// Sample (http://isohunt.com/js/rss.php):
			// http://isohunt.com/download.php?mode=bt&id=8837938
			// http://isohunt.com/btDetails.php?ihq=&id=8464972
			$referer = "http://" . $domain["host"] . "/btDetails.php?id=";

			// If the url points to the details page, change it to the download url
			if (strpos(strtolower($url), "/btdetails.php?") !== false) {
				// Need to make it grab the torrent
				$url = str_replace("/btDetails.php?", "/download.php?", $url) . "&mode=bt";
			}

			// Grab contents of details page
			$html = FetchHTML($url, $referer);
		} elseif (strpos(strtolower($url), "details.php?") !== false) {
			// Sample (http://www.bitmetv.org/rss.php?passkey=123456):
			// http://www.bitmetv.org/details.php?id=18435&hit=1
			$referer = "http://" . $domain["host"] . "/details.php?id=";
			$html = FetchHTML($url, $referer);

			// Sample (http://www.bitmetv.org/details.php?id=18435)
			// download.php/18435/SpiderMan%20Season%204.torrent
			if (preg_match("/(download.php.[^\"]+)/i", $html, $html_preg_match)) {
				$torrent = str_replace(" ", "%20", substr($html_preg_match[0], 0, -1));
				$url2 = "http://" . $domain["host"] . "/" . $torrent;
				$html = FetchHTML($url2);
			} else {
				$messages = "Error: could not find link to torrent file in $url";
				return($html="");
			}
		} elseif (strpos(strtolower($url), "download.asp?") !== false) {
			// Sample (TF's TorrenySpy Search):
			// http://www.torrentspy.com/download.asp?id=519793
			$referer = "http://" . $domain["host"] . "/download.asp?id=";
			$html = FetchHTML($url, $referer);
		} else {
			// Fallback case for any URL not ending in .torrent and not matching the above cases:
			$html = FetchHTML($url);
		}
	} else {
		$html = FetchHTML($url);
	}

	// Make sure we have a torrent file
	if (strpos($html, "d8:") === false)	{
		// We don't have a Torrent File... it is something else.  Let the user know about it:
		$messages = "Content returned from $url does not appear to be a valid torrent.";
		AuditAction($cfg["constants"]["error"], $messages);

		// Display the first part of $html if debuglevel higher than 1:
		if($cfg["debuglevel"] > 1){
			if(strlen($html) > 0){
				$messages .="  Displaying first 1024 chars of output: ".htmlentities(substr($html, 0, 1023), ENT_QUOTES);
			} else {
				$messages .="  Output from $url was empty.";
			}
		} else {
			$messages.="  Set debuglevel > 2 in 'Admin, Webapps' to see the content returned from $url.";
		}
		$html = "";
	} else {
		// If the torrent file name isn't set already, do it now:
		if ((!isset($cfg["save_torrent_name"])) || (strlen($cfg["save_torrent_name"]) == 0)) {
			// Get the name of the torrent, and make it the filename
			if (preg_match("/name([0-9][^:]):(.[^:]+)/i", $html, $html_preg_match)) {
				$filelength = $html_preg_match[1];
				$filename = $html_preg_match[2];
				$cfg["save_torrent_name"] = substr($filename, 0, $filelength) . ".torrent";
			} else {
				$cfg["save_torrent_name"] = "unknown.torrent";
			}
		}
	}
	return $html;
}

/**
 * method to get data from URL -- uses timeout and user agent
 *
 * @param $url
 * @param $referer
 * @return string
 */
function FetchHTML($url, $referer = "") {
	global $cfg, $db;

	ini_set("allow_url_fopen", "1");
	ini_set("user_agent", $_SERVER['HTTP_USER_AGENT']);

	/**
	 * array of URL component parts for use in raw HTTP request
	 * @param	array	$domain
	 */
	$domain = parse_url($url);

	/**
	 * URI/path used in GET request:
	 * @param	string	$getcmd
	 */
	$getcmd	= $domain["path"];

    if (!array_key_exists("query", $domain))
        $domain["query"] = "";

	// append the query string if included:
    $getcmd .= (!empty($domain["query"])) ? "?" . $domain["query"] : "";

	/**
	 * Cookie string used in raw HTTP request
	 * @param	string	$cookie
	 */
	$cookie = "";

	// Check to see if cookie required for this domain:
	$sql = "SELECT c.data FROM tf_cookies AS c LEFT JOIN tf_users AS u ON ( u.uid = c.uid ) WHERE u.user_id = '" . $cfg["user"] . "' AND c.host = '" . $domain['host'] . "'";
	$cookie = $db->GetOne($sql);
	showError($db, $sql);

	if (!array_key_exists("port", $domain))
		$domain["port"] = 80;

	/**
	 * the raw HTTP request to send to the remote webserver
	 * @param	string	$request
	 */
	$request = "";

	/**
	 * the raw HTTP response received from the remote webserver
	 * @param	string	$responseBody
	 */
	$responseBody = "";

	/**
	 * Array of HTTP response headers
	 * @param	array	$responseHeaders
	 */
	$responseHeaders = array();

	/**
	 * Indicates if we got the response line or not from webserver
	 * 'HTTP/1.1 200 OK
	 * etc
	 * @param	bool	$gotResponseLine
	 */
	$gotResponseLine = false;

	/**
	 * Status code of webserver resonse
	 * @param	string	$status
	 */
	$status = "";

	/**
	 * Temporarily use HTTP/1.0 until chunked encoding is sorted out
	 * Valid values are '1.0' or '1.1'
	 * @param	string	$httpVersion
	 */
	$httpVersion = "1.0";

	/**
	 * Error string used in fsockopen
	 * @param	string	$errstr
	 */
	$errstr="";

	/**
	 * Error number used in fsockopen
	 * @param	int		$errno
	 */
	$errno="";

	// Check to see if this site requires the use of cookies
	// Whilst in SVN/testing, always use the cookie/raw HTTP handling code:
	if (true || !empty($cookie)) {
		$socket = @fsockopen($domain["host"], $domain["port"], $errno, $errstr, 30); //connect to server

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
			$request  = "GET " . ($httpVersion=="1.1" ? $getcmd : $url ). " HTTP/" . $httpVersion ."\r\n";
			$request .= (!empty($referer)) ? "Referer: " . $referer . "\r\n" : "";
			$request .= "Accept: */*\r\n";
			$request .= "Accept-Language: en-us\r\n";
			$request .= "User-Agent: ".$_SERVER['HTTP_USER_AGENT']."\r\n";
			$request .= "Host: " . $domain["host"] . "\r\n";
			if($httpVersion=="1.1"){
				$request .= "Connection: Close\r\n";
			}
			$request .= "Cookie: " . $cookie . "\r\n\r\n";

			// Send header packet information to server
			fputs($socket, $request);

			// Get response headers:
			while ($line=@fgets($socket, 500000)){
				// First empty line/\r\n indicates end of response headers:
				if($line == "\r\n"){
					break;
				}

				if(!$gotResponseLine){
					preg_match("@HTTP/[^ ]+ (\d\d\d)@", $line, $matches);
					// TODO: Use this to see if we redirected (30x) and follow the redirect:
					$status = $matches[1];
					$gotResponseLine = true;
					continue;
				}

				// Get response headers:
				preg_match("/^([^:]+):\s*(.*)/", trim($line), $matches);
				$responseHeaders[strtolower($matches[1])] = $matches[2];
			}

			if(
				$httpVersion=="1.1"
				&& isset($responseHeaders["transfer-encoding"])
				&& !empty($responseHeaders["transfer-encoding"])
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
					$responseBody.=$line;

					// Keep track of total chunk/content length:
					$chunkLength+=$chunkSize;

					// Read next chunk size:
					$chunkSize = hexdec(trim(fgets($socket)));
				}
				$responseHeaders["content-length"] = $chunkLength;
			} else {
				while ($line=@fread($socket, 500000)){
					$responseBody .= $line;
				}
			}
			@fclose($socket); // Close our connection
		} else {
			return "Error fetching $url.  PHP Error No=$errno. PHP Error String=$errstr";
		}
	} else {
		// No cookies - no need for raw HTTP:
		if ($fp = @fopen($url, 'r')) {
			while (!@feof($fp))
				$responseBody .= @fgets($fp, 4096);

			@fclose($fp);
		}
	}

	// If no response from server or we were redirected with 30x response,
	// try cURL:
	if (
			($responseBody == "" && function_exists("curl_init"))
			||
			(preg_match("#HTTP/1\.[01] 30#", $responseBody) > 0 && function_exists("curl_init"))
		){

		// Give CURL a Try
		$ch = curl_init();

		if ($cookie != "")
			curl_setopt($ch, CURLOPT_COOKIE, $cookie);

		curl_setopt($ch, CURLOPT_PORT, $domain["port"]);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_VERBOSE, FALSE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 5);

		$responseBody = curl_exec($ch);

		curl_close($ch);
	}

	// Trim any extraneous linefeed chars:
	$responseBody = trim($responseBody, "\r\n");

	// If a filename is associated with this content, assign it to $cfg:
	if(isset($responseHeaders["content-disposition"]) && !empty($responseHeaders["content-disposition"])){
		// Content-disposition: attachment; filename="nameoffile":
		// Don't think single quotes can be used to escape filename here, but just in case check for ' and ":
		if(preg_match("/filename=(['\"])([^\\1]+)\\1/", $responseHeaders["content-disposition"], $matches)){
			if(isset($matches[2]) && !empty($matches[2])){
				$filename=$matches[2];

				// Only accept filenames, not paths:
				if(!preg_match("@/@", $filename)){
					$cfg["save_torrent_name"] = $filename;
				}
			}
		}
	}

	return $responseBody;
}

?>