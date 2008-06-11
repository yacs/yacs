<?php
/**
 * authenticate through a web transaction
 *
 * This plugin performs a REST POST transaction to another server, and expects
 * to receive a status code 200 on successful authentication.
 *
 * To work properly this plugin has to know following parameters:
 *	- the web address to perform the HTTP POST request
 *	- the parameter name for the user name
 *	- the parameter name for the password
 *
 * Example configuration:
 * [snippet]
 * restpost http://www.mydomain.com/authenticate.do username password
 * [/snippet]
 *
 * @author Alexandre FRANCOIS [email]alexandre-francois@voila.fr[/email]
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Http extends Authenticator {

	/**
	 * login
	 *
	 * The script checks provided name and password against remote server.
	 *
	 * This is done by posting the user name and the password
	 * to the web server.
	 *
	 * @param string the nickname of the user
	 * @param string the submitted password
	 * @return TRUE on succesful authentication, FALSE othewise
	 */
	function login($name, $password) {
		global $context;

		// we need some parameters
		if(!isset($this->attributes['authenticator_parameters']) || !$this->attributes['authenticator_parameters']) {
			Skin::error(i18n::s('Please indicate adequate parameters to the REST POST authenticator.'));
			return FALSE;
		}

		// extract parameters
		$parameters = split(" ", $this->attributes['authenticator_parameters']);

		// ensure a minimum number of parameters
		if(count($parameters) != 3) {
			Skin::error(i18n::s('Provide expected parameters to the REST POST authenticator.'));
			return FALSE;
		}

		// parse URL format
		if(!($url = $parameters[0])) {
			Skin::error(i18n::s('Wrong format of the URL target for the HTTP authenticator.'));
			return(FALSE);
		}

		// prepare raw POST payload
		$payload = urlencode($parameters[1]). "=" .urlencode($name). "&" .urlencode($parameters[2]). "=" .urlencode($password);

		// submit credentials to the authenticating server
		include_once $context['path_to_root'].'services/call.php';
		// build an HTTP request
		$request = "POST ".$url." HTTP/1.0\015\012"
			.'Host: '.$host."\015\012"
			."Accept-Encoding: gzip\015\012"
			."User-Agent: YACS (www.yetanothercommunitysystem.com)\015\012"
			."Connection: close\015\012"
			."Content-Type: application/x-www-form-urlencoded\015\012"
			."Content-Length: ".strlen($payload)."\015\012"
			."\015\012"
			.$payload;

		// parse the target URL
		$items = @parse_url($url);

		// no host, assume it's us
		if(!isset($items['host']) || (!$host = $items['host']))
			$host = $context['host_name'];

		// no port, assume the standard
		if(!isset($items['port']) || !($port = $items['port']))
			$port = 80;

		// outbound web is not authorized
		if(isset($context['without_outbound_http']) && ($context['without_outbound_http'] == 'Y')) {
			Skin::error(i18n::s('Outbound HTTP is not authorized.'));
			return FALSE;
		}

		// connect to the server
		if(!$handle = Safe::fsockopen($host, $port, $errno, $errstr, 30)) {
			Skin::error(sprintf(i18n::s('Impossible to connect to %.'), $items['host'].':'.$items['port']));
			return FALSE;
		}

		// ensure enough execution time
		Safe::set_time_limit(30);

		// build the path, including any query
		$path = $items['path'];
		if(!$path)
			$path = '/';
		if(isset($items['query']) && $items['query'])
			$path .= '?'.$items['query'];

		// submit the request
		fputs($handle, $request);

		// get everything by Ethernet-sized chunks
		$response = '';
		while(!feof($handle) && (strlen($response) < 5242880))
			$response .= fread($handle, 1500);
		fclose($handle);

		// ensure we have a valid HTTP status line
		if(preg_match('/^HTTP\/[0-9\.]+ 200 /', $response))
			return TRUE;

		// failed authentication
		return FALSE;

	}
}

?>