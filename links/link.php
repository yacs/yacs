<?php
/**
 * handle one single link
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Link {

	/**
	 * fetch the content of one url
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
	function fetch($url, $headers='', $data='', $debug='', $cookie='') {
		global $context;

		// advanced and optimized download
		if(is_callable('curl_init'))
			$body = Link::fetch_using_curl($url, $headers, $data, $debug, $cookie);

		// plan B
		else
			$body = Link::fetch_directly($url, $headers, $data, $debug, $cookie);

		// compensate for network time
		Safe::set_time_limit(30);

		return $body;

	}

	/**
	 * handles HTTP to fetch a web object
	 *
	 * This function only requires the network library.
	 * It can handle gzip objects. It also accomodates of chunked coding.
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
	 * @return the actual content, of FALSE on error
	 */
	function fetch_directly($url, $headers='', $data='', $debug='', $cookie='') {
		global $context;

		// remember errors, if any
		global $link_internal_error;
		$link_internal_error = '';

		// parse this url
		$items = @parse_url($url);

		// no host, assume it's us
		if(!$host = $items['host'])
			$host = $context['host_name'];

		// sometime parse_url() adds a '_'
		$host = rtrim($host, '_');

		// no port, assume the standard
		if(isset($items['port']) && $items['port'])
			$port = $items['port'];
		else
			$port = 80;

		// outbound web is not authorized
		if(isset($context['without_outbound_http']) && ($context['without_outbound_http'] == 'Y')) {
			if($debug)
				Logger::remember($debug, 'Outbound HTTP is not authorized', '', 'debug');
			return FALSE;
		}

		// encode provided data, if any
		if(is_array($data) && count($data)) {
			$items = array();
			foreach($data as $name => $value)
				$items[] = urlencode($name).'='.urlencode($value);
			$data = implode('&', $items);

			// remember encoding in meta-data
			$headers .= "Content-Type: application/x-www-form-urlencoded\015\012";
		}

		// set request date, RFC822 format
		if(strpos($headers, 'Date: ') === FALSE)
			$headers .= 'Date :'.gmdate('D, d M Y H:i:s T')."\015\012";

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
				Logger::remember($debug, 'Impossible to connect to '.$host.':'.$port, '', 'debug');
			return FALSE;
		}

		// ensure enough execution time
		Safe::set_time_limit(30);

		// build the path
		$path = $items['path'];
		if(!$path)
			$path = '/';

		// sometime parse_url() adds a '_'
		$path = rtrim($path, '_');

		// include any query
		if(isset($items['query']) && $items['query'])
			$path .= '?'.$items['query'];

		// headers as a string
		if(isset($headers) && is_array($headers))
			$headers = explode("\n", $headers);

		// build an HTTP request
		if($data)
			$request = 'POST';
		else
			$request = 'GET';
		$request .= ' '.$path." HTTP/1.1\015\012"
			.'Host: '.$host."\015\012"
			."User-Agent: YACS (www.yetanothercommunitysystem.com)\015\012";

		// use the connection pool
		$request .= "Connection: keep-alive\015\012";

		// enable compression
//		if(is_callable('gzinflate'))
//			$request .= "Accept-Encoding: gzip\015\012";

		// finalize the request
		$request .= $headers."\015\012".$data;

		// submit the request
		fwrite($handle, $request);

		// read HTTP status
		if(($status = fgets($handle, 1024)) === FALSE) {
			$link_internal_error = 'Impossible to get HTTP status from '.$url;
			return FALSE;
		}

		// ensure we have a valid HTTP status line
		if(!preg_match('/^HTTP\/[0-9\.]+ 20\d /', $status)) {
			$link_internal_error = 'Unexpected HTTP status "'.$status.'" from '.$url;
			return FALSE;
		}

		// read response headers, up to 5120k
		$headers = '';
		while(!feof($handle) && (strlen($headers) < 5242880)) {

			// one header at a time
			if(!$header = fgets($handle, 10240))
				break;

			// empty line marks end of headers
			if(!trim($header))
				break;

			// remember this header
			$headers .= $header;
		}

		// remember headers for later reference
		global $http_headers;
		$http_headers = $headers;

		// remember stamping
		Link::callback_headers('*dummy*', $headers);

		// get content length, if provided in header --maximum is 5120k
		$length = 5242880;
		if(preg_match('/Content-Length:\s+([0-9]+)\s+/', $headers, $matches))
			$length = $matches[1];

		// maybe a chunked element -- get size from body
		if(preg_match('/Transfer-Encoding:\s+chunked\s+/', $headers) && ($header = fgets($handle, 10240))) {

			// decodes from hexa to decimal
			if(preg_match('/^([0-9a-f]+)/i', $header, $matches))
				$length = hexdec($matches[1]);
		}

		// get everything
		$body = '';
		while(!feof($handle) && ($length > strlen($body)))
			$body .= fread($handle, min($length-strlen($body), 10240));

		// uncompress payload if necessary --sometimes gzinflate produces errors
		if($body && preg_match('/Content-Encoding: \s*gzip/i', $headers)&& is_callable('gzinflate'))
			$body = gzinflate(substr($body, 10));

		// server asks for closing
		if(preg_match('/Connection: \s+close\s+/', $headers))
			fclose($handle);

		// put in pool for future use
		else
			$handles[$host][$port] = $handle;

		// return the fetched object
		return $body;

	}

	/**
	 * remember stamp for last request
	 *
	 * @link http://fr.php.net/manual/en/function.curl-setopt.php
	 *
	 * @param the channel considered
	 * @param string received HTTP headers
	 * @return sizeof headers
	 */
	function callback_headers($dummy, $headers) {
		global $context;

		// remember Last-Modified
		global $link_last_modified;
		$link_last_modified = '';
		if(preg_match('/^Last-Modified: (.*?)$/im', $headers, $matches))
			$link_last_modified = $matches[1];

		// remember ETag
		global $link_etag;
		$link_etag = '';
		if(preg_match('/^ETag: (.*?)$/im', $headers, $matches))
			$link_etag = $matches[1];

		return strlen($headers);
	}

	/**
	 * returns the last error description, if any
	 */
	function get_error() {
		global $link_internal_error;
		return $link_internal_error;
	}

	/**
	 * use CURL to fetch a web object
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
	function fetch_using_curl($url, $headers='', $data='', $debug='', $cookie='') {
		global $context;

		// outbound web is not authorized
		if(isset($context['without_outbound_http']) && ($context['without_outbound_http'] == 'Y')) {
			if($debug)
				Logger::remember($debug, 'Outbound HTTP is not authorized', '', 'debug');
			return FALSE;
		}

		// sanity check
		if(!is_callable('curl_init')) {
			if($debug)
				Logger::remember($debug, 'CURL is not implemented"', '', 'debug');
			return FALSE;
		}

		// create a new curl resource
		$handle = curl_init();

		// sanity check
		$items = @parse_url($url);
		if(!$items['path'])
			$url .= '/';

		// set URL and other appropriate options
		curl_setopt($handle, CURLOPT_URL, $url);

		// no ending NULL char
		curl_setopt($handle, CURLOPT_BINARYTRANSFER, TRUE);

//		// up to one redirection --stop working in 4.4 in safe mode, etc.
//		curl_setopt($handle, CURLOPT_FOLLOWLOCATION, TRUE);
//		curl_setopt($handle, CURLOPT_MAXREDIRS, 1);

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

		// we are interested only in content
		curl_setopt($handle, CURLOPT_HEADER, FALSE);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);

		// we would like to process stamp as well
		curl_setopt($handle, CURLOPT_HEADERFUNCTION, array('link', 'callback_headers'));

		// do the job
		$response = curl_exec($handle);

		// remember errors, if any
		global $link_internal_error;
		$link_internal_error = '';
		if(curl_errno($handle) == 22)
			$link_internal_error = 'HTTP server returns an error code that is >= 400';
		elseif(curl_error($handle))
			$link_internal_error = curl_error($handle).' '.curl_errno($handle);

		// close curl resource, and free up system resources
		curl_close($handle);

		// return the entire reply
		return $response;
	}

	/**
	 * validate a link
	 *
	 * This function submits a HTTP request to the target server to check that the page actually exists
	 *
	 * @param the link to validate
	 * @return A date if Last-Modified has been provided, or TRUE if the link is reachable, FALSE otherwise
	 */
	function validate($url) {
		global $context;

		// parse this url
		$items = @parse_url($url);

		// assume the link is correct if not http
		if($items['scheme'] && ($items['scheme'] != 'http'))
			return TRUE;

		// no host, assume it's us
		if(!$host = $items['host'])
			$host = $context['host_name'];

		// sometime parse_url() adds a '_'
		$host = rtrim($host, '_');

		// no port, assume the standard
		if(!$port = $items['port'])
			$port = 80;

		// assume the link is correct when outbound web is not authorized
		if(isset($context['without_outbound_http']) && ($context['without_outbound_http'] == 'Y'))
			return TRUE;

		// open a network connection -- wait for up to 10 seconds for the TCP connection
		if(!$handle = Safe::fsockopen($host, $port, $errno, $errstr, 10)) {
//			logger::debug($host.':'.$port.' is not reachable', $url);
			return FALSE;
		}

		// ensure enough execution time
		Safe::set_time_limit(30);

		// build the path
		$path = $items['path'];
		if(!$path)
			$path = '/';

		// sometime parse_url() adds a '_'
		$path = rtrim($path, '_');

		// include any query
		if($items['query'])
			$path .= '?'.$items['query'];

		// send an HTTP request
		fputs($handle, 'HEAD '.$path." HTTP/1.0\015\012"
			.'Host: '.$host."\015\012"
			."User-Agent: YACS (www.yetanothercommunitysystem.com)\015\012"
			."Connection: close\015\012"
			."\015\012");

		// we are interested into the header only
		$response = '';
		while(!feof($handle) && (strlen($response) < 5242880)) {

			// ask for Ethernet-sized chunks
			$chunk = fread($handle, 1500);

			// split on headers boundary
			$here = strpos($chunk, "\015\012\015\012");
			if($here !== FALSE) {
				$chunk = substr($chunk, 0, $here);
				$response .= $chunk;
				break;
			}

			// gather header information
			$response .= $chunk;
		}
		fclose($handle);

		// split headers into lines
		$lines = explode("\015\012", $response);

		// ensure we have a valid HTTP status line
		if(!preg_match('/^HTTP\/[0-9\.]+ 20\d /', $lines[0])) {
//			logger::debug('bad status: '.$lines[0], $url);
			return FALSE;
		}

		// scan lines for "Last-Modified" header
		foreach($lines as $line) {

			if(preg_match('/^Last-Modified: (.*?)/', $line, $matches))

				// return the stamp for this link
				return date("Y-m-d H:i:s", strtotime($matches[1].' UTC'));

		}

		// no date, but the link has been validated anyway
		return TRUE;
	}

}

?>