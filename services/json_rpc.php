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
 * @link http://groups.google.com/group/json-rpc/web/json-rpc-1-2-proposal JSON-RPC Specification
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
//if(isset($context['debug_rpc']) && ($context['debug_rpc'] == 'Y'))
	Logger::remember('services/json_rpc.php', 'json_rpc request', rawurldecode($raw_data), 'debug');

// transcode to our internal charset
if($context['charset'] == 'utf-8')
	$raw_data = utf8::from_unicode($raw_data);

// decode request components -- use rawurldecode() instead urldecode(), else you will loose + signs
$parameters = Safe::json_decode(rawurldecode($raw_data));

// nothing to parse
if(empty($raw_data)) {
	$response = array('result' => NULL, 'error' => array('code' => -32600, 'message' => 'Empty request, please retry'));

// not a valid request
} elseif(empty($parameters['method']) || empty($parameters['params'])) {
	$response = array('result' => NULL, 'error' => array('code' => -32600, 'message' => 'Impossible to parse parameters'));

// dispatch the request
} else {
	$response = array();
	$response['jsonrpc'] = '2.0';

	// remember parameters if debug mode
	if(isset($context['debug_rpc']) && ($context['debug_rpc'] == 'Y'))
		Logger::remember('services/json_rpc.php', 'json_rpc '.$parameters['method'], $parameters['params'], 'debug');

	// depending on method name
	if(is_callable(array('Hooks', 'serve_scripts')))
		$response['result'] = Hooks::serve_scripts($parameters['method'], $parameters['params']);
	else
		unset($response['result']);

	// unknown method
	if(!isset($response['result']) || !$response['result']) {
		unset($response['result']);
		$response['error'] = array('code' => -32601, 'message' => 'Do not know how to process '.$parameters['method']);
		if(isset($context['debug_rpc']) && ($context['debug_rpc'] == 'Y'))
			Logger::remember('services/json_rpc.php', 'json_rpc unsupported method '.$parameters['method'], NULL, 'debug');
	} else
		unset($response['error']);
}

// copy request id in response, if any
if(empty($parameters['id']))
	unset($response['id']);
else
	$response['id'] = $parameters['id'];

// encode the response as a JSON string
$response = Safe::json_encode($response);

// handle the output correctly
render_raw('application/json; charset='.$context['charset']);

// actual transmission except on a HEAD request
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
	echo $response;

// save the response if debug mode
if(isset($context['debug_rpc']) && ($context['debug_rpc'] == 'Y'))
	Logger::remember('services/json_rpc.php', 'json_rpc response', $response, 'debug');

// the post-processing hook
finalize_page();

?>