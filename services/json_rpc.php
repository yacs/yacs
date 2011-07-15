<?php
/**
 * JSON-RPC server broker
 *
 * This script dispatch received JSON-RPC requests to the adequate module.
 *
 * [title]What does this script?[/title]
 *
 * When this script is invoked remotely as a web service, it processes the incoming call through following steps:
 * - decode received parameters according to the JSON-RPC specification
 * - load the script that has been hooked to the method called and capture results
 * - encode these results and transmit them back to the caller
 *
 * [title]How to implement a new JSON-RPC back-end service?[/title]
 *
 * Any PHP function may be used to implement a web service, through some hook definition.
 * Example:
 * [php]
 * // server hook to some web service
 * global $hooks;
 * $hooks[] = array(
 *	'id'		=> 'search',
 *	'type'		=> 'serve',
 *	'script'	=> 'services/search_service.php',
 *	'function'	=> 'Search::serve',
 *	'label_en'	=> 'Remote search',
 *	'label_fr'	=> 'Recherche distante',
 *	'description_en' => 'Example of remote search configuration.',
 *	'description_fr' => 'Exemple de configuration pour recherche distante.',
 *	'source' => 'http://www.yacs.fr/'
 * );
 * [/php]
 *
 *
 * The hook is automatically converted by [script]control/scan.php[/script] into a call to the target service function.
 * For example, the previous declaration appears into [code]parameters/hooks.include.php[/code] as:
 * [php]
 *	function serve_scripts($id, $parameters) {
 *		global $local;
 *
 *		switch($id) {
 *
 *		case 'search':
 *			include_once $context['path_to_root'].'services/search_service.php';
 *			$result = array_merge($result, Search::serve($parameters));
 *			break;
 *		}
 *
 *		return $result;
 *	}
 * [/php]
 *
 * @see control/scan.php
 *
 * @link http://en.wikipedia.org/wiki/JSON-RPC
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
include_once '../shared/global.php';

// load a skin engine
load_skin('services');

// process raw content
$raw_data = file_get_contents("php://input");

// save the raw request if debug mode
if(isset($context['debug_rpc']) && ($context['debug_rpc'] == 'Y'))
	Logger::remember('services/json_rpc.php', 'json_rpc request', rawurldecode($raw_data), 'debug');

// transcode to our internal charset
if($context['charset'] == 'utf-8')
	$raw_data = utf8::from_unicode($raw_data);

// decode request components -- use rawurldecode() instead urldecode(), else you will loose + signs
$parameters = Safe::json_decode(rawurldecode($raw_data));

// prepare the response
$response = array();

// replicate version, if any (JSON-RPC 1.1)
if(!empty($parameters['version']))
	$response['version'] = $parameters['version'];

// replicate keyword, if any (JSON-RPC 2.0)
if(!empty($parameters['jsonrpc']))
	$response['jsonrpc'] = $parameters['jsonrpc'];

// no result yet
$response['result'] = NULL;

// no error either
$response['error'] = NULL;

// nothing to parse
if(empty($raw_data)) {
	$response['error'] = array('code' => -32600, 'message' => 'Invalid Request');

// not a valid request
} elseif(!$parameters || empty($parameters['method'])) {
	$response['error'] = array('code' => -32700, 'message' => 'Parse error');

// dispatch the request
} else {

	// remember parameters if debug mode
	if(isset($context['debug_rpc']) && ($context['debug_rpc'] == 'Y'))
		Logger::remember('services/json_rpc.php', 'json_rpc '.$parameters['method'], $parameters['params'], 'debug');

	// sanitize parameters
	if(!isset($parameters['params']))
		$parameters['params'] = array();

	// depending on method name
	if(is_callable(array('Hooks', 'serve_scripts')))
		$response['result'] = Hooks::serve_scripts($parameters['method'], $parameters['params']);

	// unknown method
	if($response['result'] === NULL) {
		$response['error'] = array('code' => -32601, 'message' => 'Method not found "'.$parameters['method'].'"');
		if(isset($context['debug_rpc']) && ($context['debug_rpc'] == 'Y'))
			Logger::remember('services/json_rpc.php', 'json_rpc unsupported method '.$parameters['method'], NULL, 'debug');

	// error passed in result
	} elseif(is_array($response['result']) && !empty($response['result']['code']) && !empty($response['result']['message'])) {
		$response['error'] = $response['result'];
		$response['result'] = NULL;
	}
}

// copy request id in response, if any
if(empty($parameters['id']))
	$response['id'] = NULL;
else
	$response['id'] = $parameters['id'];

// do not reply if the sender has sent a notification, and if there is no error
if(($response['id'] == NULL) && ($response['error'] == NULL))
	$response = '';

// else package a response
else {

	// JSON-RPC 2.0 requires either some result, or an error, but not both
	if(isset($response['jsonrpc'])) {
		if($response['error'] == NULL)
			unset($response['error']);
		else
			unset($response['result']);
	}

	// encode the response
	$response = Safe::json_encode($response);

	// save the response if debug mode
	if(isset($context['debug_rpc']) && ($context['debug_rpc'] == 'Y'))
		Logger::remember('services/json_rpc.php', 'json_rpc response', $response, 'debug');

}

// handle the output correctly
render_raw('application/json; charset='.$context['charset']);

// actual transmission except on a HEAD request
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
	echo $response;

// the post-processing hook
finalize_page();

?>
