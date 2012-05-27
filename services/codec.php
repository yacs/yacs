<?php
/**
 * web service encoder and decoder
 *
 * To implement a client of some web service, you will have:
 * - to implement at least export_request() and import_response()
 * - to extend the list of accepted protocols in services/codec.php
 *
 * @see services/call.php
 * @see services/json_rpc.php
 * @see services/xml_rpc.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Codec {

	/**
	 * build a request on client side
	 *
	 * [php]
	 * $result = $codec->export_request($service, $parameters);
	 * if(!$result[0])
	 *	echo $result[1]; // print error code
	 * else
	 *	... // send data from $result[1] to the remote web server
	 * [/php]
	 *
	 * @param string name of the remote service
	 * @param mixed transmitted parameters, if any
	 * @return an array of which the first value indicates call success or failure
	 */
	function export_request($service, $parameters = NULL) {
		return array(FALSE, 'Please overlay export_request()');
	}

	/**
	 * build a response on server side
	 *
	 * [php]
	 * $result = $codec->export_response($values, $service);
	 * if(!$result[0])
	 *	echo $result[1]; // print error code
	 * else
	 *	... // send data from $result[1] to the web client
	 * [/php]
	 *
	 * @param mixed transmitted values, if any
	 * @param string name of the remote service, if any
	 * @return an array of which the first value indicates call success or failure
	 */
	function export_response($values=NULL, $service=NULL) {
		return array(FALSE, 'Please overlay export_response()');
	}

	/**
	 * parse a request on server side
	 *
	 * @param string raw data received
	 * @return an array of which the first value indicates call success or failure
	 */
	function import_request($data) {
		return array(FALSE, 'Please overlay import_request()');
	}

	/**
	 * parse a XML response on client side
	 *
	 * @param string the received HTTP body
	 * @param string the received HTTP headers
	 * @param mixed the submitted parameters
	 * @return an array of which the first value indicates call success or failure
	 */
	function import_response($data, $headers=NULL, $parameters=NULL) {
		return array(FALSE, 'Please overlay import_response()');
	}

	/**
	 * load one XML codec handler
	 *
	 * @param string the variant to handle
	 * @return a Codec instance, or NULL if the variant is unknown
	 */
	public static function initialize($variant) {
		global $context;

		switch($variant) {
		case 'JSON-RPC':
			include_once $context['path_to_root'].'services/json_rpc_codec.php';
			return new JSON_RPC_Codec();
		case 'XML-RPC':
			include_once $context['path_to_root'].'services/xml_rpc_codec.php';
			return new XML_RPC_Codec();
		}
		return NULL;
	}
}

?>