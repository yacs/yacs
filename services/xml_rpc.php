<?php
/**
 * XML-RPC server broker
 *
 * This script dispatch received XML-RPC requests to the adequate module.
 *
 * [title]What does this script?[/title]
 *
 * When this script is invoked remotely as a web service, it processes the incoming call through following steps:
 * - decode received parameters according to the XML-RPC specification
 * - load the script that has been hooked to the method called and capture results
 * - encode these results and transmit them back to the caller
 *
 * The encoding and decoding opperations are delegated to some instance of a xml_rpc_codec object,
 * as implemented in [script]services/xml_rpc_codec.php[/script].
 *
 * [title]How to implement a new XML-RPC back-end service?[/title]
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
 *	'source' => 'http://www.yetanothercommunitysystem.com/'
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
 * @see services/call.php
 * @see services/xml_rpc_codec.php
 *
 * @link http://www.xmlrpc.com/spec XML-RPC Specification
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
include_once '../shared/global.php';

// load a skin engine
load_skin('services');

// ensure we have some raw content
global $HTTP_RAW_POST_DATA;
if(!isset($HTTP_RAW_POST_DATA))
   $HTTP_RAW_POST_DATA = file_get_contents("php://input");

// save the raw request if debug mode
if(isset($context['debug_rpc']) && ($context['debug_rpc'] == 'Y'))
	Logger::remember('services/xml_rpc.php', 'xml_rpc request', rawurldecode($HTTP_RAW_POST_DATA), 'debug');

// transcode to our internal charset
if($context['charset'] == 'utf-8')
	$HTTP_RAW_POST_DATA = utf8::to_unicode($HTTP_RAW_POST_DATA);

// load the adequate codec
include_once 'codec.php';
include_once 'xml_rpc_codec.php';
$codec =& new xml_rpc_Codec();

// parse xml parameters -- use rawurldecode() instead urldecode(), else you will loose + signs
$result = $codec->import_request(rawurldecode($HTTP_RAW_POST_DATA));
$status = @$result[0];
$parameters = @$result[1];

// nothing to parse
if(!$HTTP_RAW_POST_DATA) {
	$response = array('faultCode' => 1, 'faultString' => 'Empty request, please retry');

// parse has failed
} elseif(!$status) {
	$response = array('faultCode' => 1, 'faultString' => 'Impossible to parse parameters');

// dispatch the request
} else {

	// remember parameters if debug mode
	if(isset($context['debug_rpc']) && ($context['debug_rpc'] == 'Y'))
		Logger::remember('services/xml_rpc.php', 'xml_rpc '.$parameters['methodName'], $parameters['params'], 'debug');

	// depending on method name
	if(is_callable(array('Hooks', 'serve_scripts')))
		$response = Hooks::serve_scripts($parameters['methodName'], $parameters['params']);
}

// unknown method
if(!isset($response)) {
	$response = array('faultCode' => 1, 'faultString' => 'Do not know how to process '.$parameters['methodName']);
	if(isset($context['debug_rpc']) && ($context['debug_rpc'] == 'Y'))
		Logger::remember('services/xml_rpc.php', 'xml_rpc unsupported methodName '.$parameters['methodName'], $parameters, 'debug');
}

// encode the response as an XML snippet
if(!is_string($response) || !preg_match('/^<\?xml/', $response)) {
	$result = $codec->export_response($response);
	$status = @$result[0];
	$response = @$result[1];
}

// handle the output correctly
render_raw('text/xml; charset='.$context['charset']);

// actual transmission except on a HEAD request
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
	echo $response;

// save the response if debug mode
if(isset($context['debug_rpc']) && ($context['debug_rpc'] == 'Y'))
	Logger::remember('services/xml_rpc.php', 'xml_rpc response', $response, 'debug');

// the post-processing hook
finalize_page();

?>