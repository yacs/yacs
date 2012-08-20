<?php
/**
 * handle one single link
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Link {

	/**
	 * fetch the content of one url
	 *
	 * @param string the link to fetch
	 * @param array of strings optional headers (eg, 'array("Content-Type: text/xml")')
	 * @param string optional data to send
	 * @param string cookie, if any
	 * @return the actual content, of FALSE on error
	 */
	function fetch($url, $headers='', $data='', $cookie='') {
		global $context;

		// use the http library
		return http::proceed($url, $headers, $data, $cookie);

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
			if($context['with_debug'] == 'Y')
				logger::remember('links/link.php', $host.':'.$port.' is not reachable', $url, 'debug');
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
		fputs($handle, 'HEAD '.$path." HTTP/1.0".CRLF
			.'Host: '.$host.CRLF
			."User-Agent: YACS (www.yacs.fr)".CRLF
			."Connection: close".CRLF
			.CRLF);

		// we are interested into the header only
		$response = '';
		while(!feof($handle) && (strlen($response) < 5242880)) {

			// ask for Ethernet-sized chunks
			$chunk = fread($handle, 1500);

			// split on headers boundary
			$here = strpos($chunk, CRLF.CRLF);
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
		$lines = explode(CRLF, $response);

		// ensure we have a valid HTTP status line
		if(!preg_match('/^HTTP\/[0-9\.]+ 20\d /', $lines[0])) {
			if($context['with_debug'] == 'Y')
				logger::remember('links/link.php', 'bad status: '.$lines[0], $url, 'debug');
			return FALSE;
		}

		// scan lines for "Last-Modified" header
		foreach($lines as $line) {

			if(preg_match('/^Last-Modified: (.*?)/', $line, $matches))

				// return the stamp for this link
				return date("Y-m-d H:i:s", strtotime($matches[1]));

		}

		// no date, but the link has been validated anyway
		return TRUE;
	}

}

?>
