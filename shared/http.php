<?php
/**
 * handle the web protocol
 *
 * You can use the Resource Expert Droid (RED) web service to validate headers produced by your scripts.
 *
 * @link http://redbot.org/
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class http {

	/**
	 * remember headers of the last response
	 *
	 * This is a call-back function used in proceed_natively() and in proceed_using_curl() as well.
	 *
	 * @link http://fr.php.net/manual/en/function.curl-setopt.php
	 *
	 * @param the channel considered
	 * @param string received HTTP header
	 * @return sizeof headers
	 */
	function callback_headers($dummy, $header) {

		// remember this header
		if(rtrim($header)) {
			global $http_fetch_headers;
			$http_fetch_headers[] = rtrim($header);
		}

		// mandatory information
		return strlen($header);
	}

	/**
	 * use cache by expiration
	 *
	 * Ask the user agent to cache data for some time.
	 * If the provided parameter is set to 0, ask for systematic validation instead.
	 *
	 * [*] Internet Explorer may have strange behaviour with the [code]Expire[/code] attribute.
	 * It does not take into account very short-term expiration date and does not validate after the deadline.
	 * On the other hand, setting an expiration date is useful to fix the 'download a .zip file directly from the browser' bug.
	 * We recommend to set this attribute in all scripts related to file transfers and download, and to not set it
	 * at all in every other script.
	 *
	 * [*] The [code]Cache-Control[/code] attribute allows for cache-control.
	 * It has been primarily designed for HTTP/1.1 agents, and few proxies seem to handle it correctly at the moment.
	 * However to explicitly declare that the output of some script may be cached for three hours by intermediate proxies,
	 * you can use [code]Safe::header("Cache-Control: private, max-age=10800");[/code].
	 * On the other hand, if only the user-agent (i.e., the browser) is allowed to cache something,
	 * you can use [code]Safe::header("Cache-Control: no-cache, max-age=10800");[/code].
	 *
	 * [*] What to do with [code]Pragma:[/code]? Well, almost nothing; this is used only by some legacy browsers.
	 * If you want an old browser to cache some object, use [code]Safe::header("Pragma: no-cache");[/code].
	 *
	 * @param int number of seconds to cache (default: 30 minutes)
	 */
	function expire($time=1800) {

		// ask for revalidation - 'no-cache' is mandatory for IE6 !!!
		if(!$time || ($time < 1)) {
			Safe::header('Expires: Thu, 19 Nov 1981 08:52:00 GMT');
			Safe::header('Cache-Control: no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
			Safe::header('Pragma: no-cache');
		} else {
			Safe::header('Expires: '.gmdate("D, d M Y H:i:s", time() + $time).' GMT');
			Safe::header('Cache-Control: private, max-age='.$time);
			Safe::header('Pragma: no-cache');
		}
	}

	/**
	 * get base part of the URI to the current script
	 *
	 * This should be used for users passing through proxies.
	 *
	 * @return string the base URI
	 */
	function get_base_uri() {
		global $context, $_SERVER;

		// host name
		if(isset($_SERVER['HTTP_VIA']) && ($tags = explode(' ', $_SERVER['HTTP_VIA'], 3)) && isset($tags[1]))
			$host_name = rtrim($tags[1], ' ,'); // from outer proxy
		else
			$host_name = $context['host_name']; // from running configuration

		// URL
		$root_url = '';
		if(isset($_SERVER['X_FORWARDED_BASE']) && $_SERVER['X_FORWARDED_BASE'])
			$root_url .= $_SERVER['X_FORWARDED_BASE']; // from reverse proxy
		$root_url .= $context['url_to_root']; // from running configuration

		// done.
		return 'http://'.$host_name.$root_url;
	}

	/**
	 * returns the last error description, if any
	 */
	function get_error() {
		global $http_internal_error;
		return $http_internal_error;
	}

	/**
	 * returns headers of the last response, if any
	 */
	function get_headers() {
		global $http_fetch_headers;
		return implode("\n", $http_fetch_headers);
	}

	/**
	 * just terminate the web transaction
	 *
	 * Useful to AJAX responses when nothing has to be transmitted back to the browser.
	 */
	function no_content() {
		Safe::header('Status: 204 No Content', TRUE, 204);
		Safe::header('Content-Length: 0',true);
		Safe::header('Content-Type: text/html',true);
	}

	/**
	 * send a request and get a response
	 *
	 * @todo on successful fetch put Last-Modified and ETag in the cache
	 *
	 * @param string the link to fetch
	 * @param array of strings optional headers (eg, 'array("Content-Type: text/xml")')
	 * @param string optional data to send
	 * @param string the name of the calling script to be debugged (eg, 'scripts/stage.php')
	 * @param string cookie, if any
	 * @return the actual content, of FALSE on error
	 */
	function proceed($url, $headers='', $data='', $debug='', $cookie='') {
		global $context;

		// target content
		$body = FALSE;

		// advanced and optimized download
		if(is_callable('curl_init'))
			$body = http::proceed_using_curl($url, $headers, $data, $debug, $cookie);

		// plan B, in case curl has not done the job properly
		if(!$body)
			$body = http::proceed_natively($url, $headers, $data, $debug, $cookie);

		// compensate for network time
		Safe::set_time_limit(30);

		return $body;

	}

	/**
	 * handle HTTP natively
	 *
	 * This function only requires the network library.
	 * It accomodates SSL/TLS requests, and chunked coding.
	 *
	 * Provided data will be POSTed to the target url, else GET is used.
	 * If data is an array of named values, it is encoded as x-www-urlform-encoded before sending.
	 *
	 * @todo add proxy support to fetch_directly()
	 *
	 * @param string the link to fetch
	 * @param array of strings optional headers (eg, 'array("Content-Type: text/xml")')
	 * @param mixed optional data to send
	 * @param string the name of the calling script to be debugged (eg, 'scripts/stage.php')
	 * @param string cookie, if any
	 * @param int to manage a maximum number of redirections
	 * @return the actual content, of FALSE on error
	 */
	function proceed_natively($url, $headers='', $data='', $debug='', $cookie='', $limit=3) {
		global $context;

		// outbound web is not authorized
		if(isset($context['without_outbound_http']) && ($context['without_outbound_http'] == 'Y')) {
			if($debug)
				Logger::remember($debug, 'Outbound HTTP is not authorized.', '', 'debug');
			return FALSE;
		}

		// no headers yet
		global $http_fetch_headers;
		$http_fetch_headers = array();

		// remember errors, if any
		global $http_internal_error;
		$http_internal_error = '';

		// parse this url
		$items = @parse_url($url);

		// no host, assume it's us
		if(!$host = $items['host'])
			$host = $context['host_name'];

		// sometime parse_url() adds a '_'
		$host = rtrim($host, '_');

		if(is_array($headers))
			$headers = implode("\015\012", $headers)."\015\012";

		// set target host
		if(strpos($headers, 'Host: ') === FALSE)
			$headers .= 'Host: '.$host."\015\012";

		// ask fsockopen to open a TLS/SSL connection
		if($items['scheme'] == 'https')
			$host = 'ssl://'.$host;

		// set user agent
		if(strpos($headers, 'User-Agent: ') === FALSE)
			$headers .= 'User-Agent: yacs'."\015\012";

		// no port, assume the standard
		if(isset($items['port']) && $items['port'])
			$port = $items['port'];
		elseif($items['scheme'] == 'https')
			$port = 443;
		else
			$port = 80;

		// build the path
		$path = $items['path'];
		if(!$path)
			$path = '/';

		// sometime parse_url() adds a '_'
		$path = rtrim($path, '_');

		// include any query
		if(isset($items['query']) && $items['query'])
			$path .= '?'.$items['query'];

		// encode provided data, if any
		if(is_array($data) && count($data)) {
			$items = array();
			foreach($data as $name => $value)
				$items[] = urlencode($name).'='.urlencode($value);
			$data = implode('&', $items);

		}

		// remember encoding in meta-data
		if($data && (strpos($headers, 'Content-Type: ') === FALSE))
			$headers .= "Content-Type: application/x-www-form-urlencoded; charset=UTF-8\015\012";

		// remember content length in meta-data
		if($data && (strpos($headers, 'Content-Length: ') === FALSE))
			$headers .= "Content-Length: ".strlen($data)."\015\012";

		// set request date, RFC822 format
		if(strpos($headers, 'Date: ') === FALSE)
			$headers .= 'Date: '.gmdate('D, d M Y H:i:s T')."\015\012";

		// append cookies, if any
		if(is_array($cookie))
			$headers .= 'Cookie: '.join('; ', $cookie)."\015\012";
		elseif($cookie)
			$headers .= 'Cookie: '.$cookie."\015\012";

		// pool of connections
		static $handles;
		if(!isset($handles))
			$handles = array();

		// no handle yet
		$handle = NULL;

		// reuse an existing connection to this server socket
		if(isset($handles[$host][$port]) && $handles[$host][$port]) {
			$handle = $handles[$host][$port];

			// drop the connection if closed
			if(!$status = stream_get_meta_data($handle))
				$handle = $handles[$host][$port] = NULL;
			elseif((isset($status['timedout']) && $status['timedout']) || (isset($status['eof']) && $status['eof']))
				$handle = $handles[$host][$port] = NULL;
		}

		// open a network connection -- wait for up to 10 seconds for the TCP connection
		if(!$handle && (!$handle = Safe::fsockopen($host, $port, $errno, $errstr, 10))) {
			if($debug)
				Logger::remember($debug, sprintf('Impossible to connect to %s.', $host.':'.$port), '', 'debug');
			return FALSE;
		}

		// ensure enough execution time
		Safe::set_time_limit(30);

		// build an HTTP request
		if($data)
			$request = 'POST';
		else
			$request = 'GET';
		$request .= ' '.$path." HTTP/1.1\015\012";

		// use the connection pool
		$request .= "Connection: keep-alive\015\012";

		// enable compression
//		if(is_callable('gzinflate'))
//			$request .= "Accept-Encoding: gzip\015\012";

		// finalize the request
		$request .= $headers."\015\012".$data;
		if($debug)
			Logger::remember($debug, 'http request', $request, 'debug');

		// submit the request
		fwrite($handle, $request);

		// read HTTP status
		if(($status = fgets($handle, 1024)) === FALSE) {
			$http_internal_error = 'Impossible to get HTTP status from '.$url;
			return FALSE;
		}

		// ensure we have a valid HTTP status line
		if(!preg_match('/^HTTP\/[0-9\.]+ (\d\d\d) /', $status, $matches)) {
			$http_internal_error = 'Unexpected HTTP status "'.$status.'" from '.$url;
			return FALSE;
		}

		// read response headers, up to 5120k
		$r_headers = '';
		while(!feof($handle) && (strlen($r_headers) < 5242880)) {

			// one header at a time
			if(!$header = fgets($handle, 10240))
				break;

			// empty line marks end of headers
			if(!trim($header))
				break;

			// conform to curl sequencing
			http::callback_headers('*dummy*', $header);

			// remember this header
			$r_headers .= $header;
		}

		// redirect to another place
		if(preg_match('/^Location: (\w.+?)/', $r_headers, $matches)) {

			if(--$limit <= 0) {
				$http_internal_error = 'Too many redirections';
				return FALSE;
			}

			if($debug)
				Logger::remember($debug, 'redirecting to '.$matches[1], '', 'debug');
			return http::proceed_natively($matches[1], $headers, $data, $debug, $cookie, $limit);

		}

		// get content length, if provided in header --maximum is 5120k
		$length = 5242880;
		if(preg_match('/Content-Length:\s+([0-9]+)\s+/', $r_headers, $matches))
			$length = $matches[1];

		// maybe a chunked element -- get size from body
		if(preg_match('/Transfer-Encoding:\s+chunked\s+/', $r_headers) && ($header = fgets($handle, 10240))) {

			// decodes from hexa to decimal
			if(preg_match('/^([0-9a-f]+)/i', $header, $matches))
				$length = hexdec($matches[1]);
		}

		// get everything
		$body = '';
		while(!feof($handle) && ($length > strlen($body)))
			$body .= fread($handle, min($length-strlen($body), 10240));

		// uncompress payload if necessary --sometimes gzinflate produces errors
		if($body && preg_match('/Content-Encoding: \s*gzip/i', $r_headers)&& is_callable('gzinflate'))
			$body = gzinflate(substr($body, 10));

		// server asks for closing
		if(preg_match('/Connection: \s+close\s+/', $r_headers))
			fclose($handle);

		// put in pool for future use
		else
			$handles[$host][$port] = $handle;

		// log the response
		if($debug)
			Logger::remember($debug, 'http response', http::get_headers()."\n\n".$body, 'debug');

		// return the fetched object
		return $body;

	}

	/**
	 * delegate a web transaction to the CURL library
	 *
	 * As it name implies, this function requires the library to be available.
	 * It inherently supports https.
	 * It also supports proxy settings, if any.
	 *
	 * @param string the link to fetch
	 * @param array of strings optional headers (eg, 'array("Content-Type: text/xml")')
	 * @param string optional data to send
	 * @param the name of the calling script to be debugged (eg, 'scripts/stage.php')
	 * @param string cookie, if any
	 * @return the actual content, of FALSE on error
	 */
	function proceed_using_curl($url, $headers='', $data='', $debug='', $cookie='') {
		global $context;

		// outbound web is not authorized
		if(isset($context['without_outbound_http']) && ($context['without_outbound_http'] == 'Y')) {
			if($debug)
				Logger::remember($debug, 'Outbound HTTP is not authorized.', '', 'debug');
			return FALSE;
		}

		// sanity check
		if(!is_callable('curl_init')) {
			if($debug)
				Logger::remember($debug, 'CURL is not implemented"', '', 'debug');
			return FALSE;
		}

		// no headers yet
		global $http_fetch_headers;
		$http_fetch_headers = array();

		// create a new curl resource
		$handle = curl_init();

		// sanity check
		$items = @parse_url($url);
		if(!isset($items['path']) || !$items['path'])
			$url .= '/';

		// set URL and other appropriate options
		curl_setopt($handle, CURLOPT_URL, $url);

		// no ending NULL char
		curl_setopt($handle, CURLOPT_BINARYTRANSFER, TRUE);

		// redirect if necessary -- does not work if safe mode
		if(!ini_get("safe_mode")) {
			curl_setopt($handle, CURLOPT_FOLLOWLOCATION, TRUE);
			curl_setopt($handle, CURLOPT_MAXREDIRS, 3);
		}

		// set timeouts
		curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);

		// pass through a proxy, maybe
		if(isset($context['proxy_server']) && $context['proxy_server']) {
			curl_setopt($handle, CURLOPT_PROXY, $context['proxy_server']);

			// authenticate to the proxy
			if(isset($context['proxy_user']) && $context['proxy_user']) {
				curl_setopt($handle, CURLOPT_PROXYUSERPWD, $context['proxy_user'].':'.$context['proxy_password']);
			}
		}

		// let CURL adapt to the encoding
		curl_setopt($handle, CURLOPT_ENCODING, '');

		// parse the returned error code as well
		curl_setopt($handle, CURLOPT_FAILONERROR, TRUE);

		// add cookie information, if any
		if($cookie)
			curl_setopt($handle, CURLOPT_COOKIE, $cookie);

		// set headers, if any
		if(isset($headers) && is_array($headers))
			curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);

		// we would like to look at headers by ourselves
		curl_setopt($handle, CURLOPT_HEADER, FALSE);
		curl_setopt($handle, CURLOPT_HEADERFUNCTION, array('http', 'callback_headers'));

		// of course we need the content
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);

		// do the job
		$response = curl_exec($handle);

		// remember errors, if any
		global $http_internal_error;
		$http_internal_error = '';
		if(curl_errno($handle) == 22)
			$http_internal_error = 'HTTP server returns an error code that is >= 400';
		elseif(curl_error($handle))
			$http_internal_error = curl_error($handle).' '.curl_errno($handle);

		// close curl resource, and free up system resources
		curl_close($handle);

		// return the entire reply
		return $response;
	}

	/**
	 * validate data cached by user agent
	 *
	 * @param string the date of last modification, as per HTTP specification
	 * @param string the opaque string characterizing the target object
	 * @return boolean TRUE if the client has provided the right headers, FALSE otherwise
	 */
	function validate($last_modified, $etag=NULL) {

		// not cached yet
		$cached = FALSE;

		// web cache is not managed
		if(isset($context['without_http_cache']) && ($context['without_http_cache'] == 'Y'))
			return FALSE;

		// validate the content if date of last modification is the same
		if($last_modified && isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && ($if_modified_since = preg_replace('/;.*$/', '', $_SERVER['HTTP_IF_MODIFIED_SINCE']))) {
			if(($if_modified_since == $last_modified) && !isset($_SERVER['HTTP_IF_NONE_MATCH']))
				$cached = TRUE;
		}

		// validate the content if hash is ok
		if($etag && isset($_SERVER['HTTP_IF_NONE_MATCH']) && is_array($if_none_match = explode(',', str_replace('\"', '"', $_SERVER['HTTP_IF_NONE_MATCH'])))) {
			foreach($if_none_match as $target) {
				if(trim($target) == $etag) {
					$cached = TRUE;
					$break;
				}
			}
		}

		// client has the right data
		if($cached)
			Safe::header('Status: 304 Not Modified', TRUE, 304);

		// else inform the browser
		else {

			// set the date of last modification
			if($last_modified)
				Safe::header('Last-Modified: '.$last_modified);

			// set the opaque string for this object
			if($etag)
				Safe::header('ETag: '.$etag);

		}

		// what we have found
		return $cached;
	}
}

?>
