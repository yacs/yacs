<?php
/**
 * web service client broker
 *
 * This script is a skeleton used to build actual web service, whether it be XML-RPC, Packeteer API, or whatever else.
 * Basically, it has the glue necessary to interface with YACS client and server API.
 *
 * When some script has to invoke some remote service it has to provide following information:
 * - the name of the targeted service
 * - adequate parameters
 *
 * After the call, the calling script has to fetch and to process the result of the remote process.
 *
 * For example, the remote search facility is implemented quite easily in search.php:
 * [php]
 * // search at other sites
 * if(is_callable(array('Hooks', 'call_scripts'))) {
 *	$parameters = array( 'search' => $_REQUEST['search'],
 *		'output_spec' => array('output_format' => 'slashdot') );
 *	if($rows = Hooks::call_scripts('search.php#call', $parameters)) {
 *		$context['text'] .= Skin::build_block(i18n::s('title'), 'title');
 *		$context['text'] .= Skin::build_list($rows, 'decorated');
 *		$no_result = FALSE;
 *	}
 * }
 * [/php]
 *
 * Within YACS the actual binding of the service to a network address and to some protocol is achieved through some hooks definition.
 * [php]
 * // client hook to some web service
 * global $hooks;
 * $hooks[] = array(
 *	'id'		=> 'search.php#call',
 *	'type'		=> 'call',
 *	'link'		=> 'http://127.0.0.1/yacs/broker.php',
 *	'service'	=> 'search',
 *	'label_en'	=> 'Remote search',
 *	'label_fr'	=> 'Recherche distante',
 *	'description_en' => 'Example of remote search configuration.',
 *	'description_fr' => 'Exemple de configuration pour recherche distante.',
 *	'source' => 'http://www.yacs.fr/'
 * );
 * [/php]
 *
 * The hook is automatically converted by [script]control/scan.php[/script] into a call to [code]Call::invoke()[/code] to actually
 * handle the transport layer between the client and the server.
 * For example, the previous declaration appears into parameters/hooks.include.php as:
 * [php]
 *	function call_scripts($id, $parameters) {
 *		global $local;
 *		include_once $context['path_to_root'].'services/call.php';
 *
 *		switch($id) {
 *		case 'search.php#call':
 *			$result = array_merge($result, Call::invoke('http://127.0.0.1/yacs/broker.php', 'search', $parameters));
 *			break;
 *		}
 *
 *		return $result;
 *	}
 * [/php]
 *
 * [title]Implementing different protocol[/title]
 *
 * By default the XML-RPC protocol is used between the client and the server.
 * However other options may have to be considered to adapt to other constraints.
 * For example Packeteer, as others, has its own implementation of XML-over-HTTP for web services.
 *
 * To select among available protocols you have to specify it on the client side like this:
 * [php]
 * // query a PacketShaper from Packeteer
 * $variant = 'packetwiseAPI';
 * if($data = Hooks::call_scripts('search.php#call', $parameters, $variant)) {
 *	...
 * [/php]
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 *
 * @see services/configure.php
 */

Class Call {

	/**
	 * call a web service
	 *
	 * This function supports following protocols, as specified in last parameter:
	 * - 'XML-RPC' - request and response are XML snippets
	 * - 'JSON-RPC' - request and response are JSON snippets
	 *
	 * Minimum example:
	 * [php]
	 * $result = Call::invoke($url, $method);
	 * if(!$result[0])
	 *	echo $result[1]; // error message
	 * else
	 *	... // use call result from $result[1]
	 * [/php]
	 *
	 * Note that in a previous version data was encoded.
	 * Unfortunately, most servers do not handle encoding, despite it's a useful web standard.
	 *
	 * @param string the url to use
	 * @param string the service to call
	 * @param array the parameters to transmit
	 * @param string the protocol to use
	 * @return an array of which the first value indicates call success or failure
	 *
	 * @see control/scan.php
	 * @see links/links.php
	 * @see servers/servers.php
	 * @see services/blog_test.php
	 */
	function invoke($url, $service, $parameters = NULL, $variant='XML-RPC') {
		global $context;

		// submit a raw request
		$raw_request = FALSE;
		if($variant == 'raw') {
			$raw_request = TRUE;
			$variant = 'XML-RPC';
		}

		// select a codec handler
		include_once $context['path_to_root'].'services/codec.php';
		$codec = Codec::initialize($variant);
		if(!is_object($codec))
			return array(FALSE, 'Do not know how to cope with API '.$variant);

		// adjust content type
		if($variant == 'JSON-RPC')
			$headers = 'Content-Type: application/json'."\015\012";
		else
			$headers = 'Content-Type: text/xml'."\015\012";

		// build the request
		if($raw_request)
			$result = array(TRUE, $parameters);
		else
			$result = $codec->export_request($service, $parameters);
		if(!$result[0])
			return array(FALSE, $result[1]);
		$headers .= 'Content-Length: '.strlen($result[1])."\015\012";

		// parse the target URL
		$items = @parse_url($url);

		// no host, assume it's us
		if(!isset($items['host']) || (!$host = $items['host']))
			$host = $context['host_name'];

		// no port, assume the standard
		if(!isset($items['port']) || !($port = $items['port']))
			$port = 80;

		// outbound web is not authorized
		if(isset($context['without_outbound_http']) && ($context['without_outbound_http'] == 'Y'))
			return array(FALSE, 'Outbound HTTP is not authorized.');

		// connect to the server
		if(!$handle = Safe::fsockopen($host, $port, $errno, $errstr, 30))
			return array(FALSE, sprintf('Impossible to connect to %s.', $items['host'].':'.$items['port']));

		// ensure enough execution time
		Safe::set_time_limit(30);

		// build the path, including any query
		$path = $items['path'];
		if(!$path)
			$path = '/';
		if(isset($items['query']) && $items['query'])
			$path .= '?'.$items['query'];

		// build an HTTP request
		$request = "POST ".$path." HTTP/1.0\015\012"
			.'Host: '.$host."\015\012"
			."Accept-Encoding: gzip\015\012"
			."User-Agent: YACS (www.yacs.fr)\015\012"
			."Connection: close\015\012"
			.$headers."\015\012".$result[1];

		// save the request if debug mode
		if(isset($context['debug_call']) && ($context['debug_call'] == 'Y'))
			Logger::remember('services/call.php', 'Call::invoke() request', str_replace("\r\n", "\n", $request), 'debug');

		// submit the request
		fputs($handle, $request);

		// get everything by Ethernet-sized chunks
		$response = '';
		while(!feof($handle) && (strlen($response) < 5242880))
			$response .= fread($handle, 1500);
		fclose($handle);

		// ensure we have a valid HTTP status line
		if(preg_match('/^HTTP/', $response) && !preg_match('/^HTTP\/[0-9\.]+ 200 /', $response)) {
			$lines = explode("\n", $response, 2);
			return array(FALSE, 'Unexpected HTTP status "'.$lines[0].'"');
		}

		// separate headers from body
		list($headers, $content) = explode("\015\012\015\012", $response, 2);

		// uncompress payload if necessary
		if(preg_match('/Content-Encoding: \s*gzip/i', $headers))
			$content = gzinflate(substr($content, 10));

		// save the response if debug mode
		if(isset($context['debug_call']) && ($context['debug_call'] == 'Y'))
			Logger::remember('services/call.php', 'Call::invoke() response', str_replace("\r\n", "\n", $headers."\n\n".$content), 'debug');

		// decode the result
		return $codec->import_response($content, $headers, $parameters);
	}

	/**
	 * get a list of remote resources
	 *
	 * This function performs a REST call against a web services that provides a RSS-encoded response.
	 *
	 * Minimum example:
	 * [php]
	 * $result = Call::list_resources($url);
	 * if(!$result[0])
	 *	echo $result[1]; // error message
	 * else
	 *	... // use call result from $result[1]
	 * [/php]
	 *
	 * @param string the url to use
	 * @param array the parameters to transmit
	 * @return an array of which the first value indicates call success or failure
	 *
	 * @see search.php
	 */
	function list_resources($url, $parameters = NULL) {
		global $context;

		// encode the request
		$data = '';
		foreach($parameters as $label => $value) {
			if($data)
				$data .= '&';
			$data .= urlencode($label).'='.urlencode($value);
		}
		$headers = '';
		$headers .= 'Content-Type: application/x-www-form-urlencoded'."\015\012";
		$headers .= 'Content-Length: '.strlen($data)."\015\012";

		// parse the target URL
		$items = @parse_url($url);

		// no host, assume it's us
		if(!$host = $items['host'])
			$host = $context['host_name'];

		// no port, assume the standard
		if(!isset($items['port']) || (!$port = $items['port']))
			$port = 80;

		// outbound web is not authorized
		if(isset($context['without_outbound_http']) && ($context['without_outbound_http'] == 'Y'))
			return array(FALSE, 'Outbound HTTP is not authorized.');

		// connect to the server
		if(!$handle = Safe::fsockopen($host, $port, $errno, $errstr, 30))
			return array(FALSE, sprintf('Impossible to connect to %s.', $host.':'.$port));

		// ensure enough execution time
		Safe::set_time_limit(30);

		// build the path, including any query
		$path = $items['path'];
		if($items['query'])
			$path .= '?'.$items['query'];

		// build an HTTP request
		$request = "POST ".$path." HTTP/1.0\015\012"
			.'Host: '.$host."\015\012"
			."Accept-Encoding: gzip\015\012"
			."User-Agent: YACS (www.yacs.fr)\015\012"
			."Connection: close\015\012"
			.$headers."\015\012".$data;

		// save the request if debug mode
		if($context['debug_call'] == 'Y')
			Logger::remember('services/call.php', 'Call::list_resources() request', str_replace("\r\n", "\n", $request), 'debug');

		// submit the request
		fputs($handle, $request);

		// get everything by Ethernet-sized chunks
		$response = '';
		while(!feof($handle) && (strlen($response) < 5242880))
			$response .= fread($handle, 1500);
		fclose($handle);

		// ensure we have a valid HTTP status line
		if(preg_match('/^HTTP/', $response) && !preg_match('/^HTTP\/[0-9\.]+ 200 /', $response)) {
			$lines = explode("\n", $response, 2);
			return array(FALSE, 'Unexpected HTTP status "'.$lines[0].'"');
		}

		// separate headers from body
		list($headers, $content) = explode("\015\012\015\012", $response, 2);

		// uncompress payload if necessary
		if(preg_match('/Content-Encoding: \s*gzip/i', $headers))
			$content = gzinflate(substr($content, 10));

		// save the response if debug mode
		if($context['debug_call'] == 'Y')
			Logger::remember('services/call.php', 'Call::list_resources() response', str_replace("\r\n", "\n", $headers."\n\n".$content), 'debug');

		// we understand only text responses
		if(!preg_match('/^Content-Type: text/m', $headers))
			return array(FALSE, 'Impossible to process not-textual response');

		// passthrough if not xml
		if(!preg_match('/^Content-Type: text\/xml/m', $headers))
			return $content;

		// select a codec handler
		include_once $context['path_to_root'].'services/codec.php';
		include_once $context['path_to_root'].'services/rss_codec.php';
		$codec = new RSS_Codec();
		if(!is_object($codec))
			return array(FALSE, 'Impossible to load codec RSS_Codec');

		// decode the result
		return $codec->import_response($content, $headers, $parameters);
	}

}

?>