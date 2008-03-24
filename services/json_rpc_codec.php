<?php
/**
 * web service encoder and decoder
 *
 * @see services/call.php
 * @see services/codec.php
 *
 * @link http://json-rpc.org/wiki/specification JSON-RPC Specification
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class JSON_RPC_Codec extends Codec {

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

		// a structured request
		$request = array();
		$request['method'] = $service;
		$request['params'] = $parameters;
		if(empty($parameters['id']))
			$request['id'] = NULL;
		else {
			$request['id'] = $parameters['id'];
			unset($request['parameters']['id']);
		}

		// encode it and deliver the outcome
		if($encoded = Safe::json_encode($request))
			return array(TRUE, $encoded);
		return array(FALSE, 'No way to encode the request');
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

		// a structured response
		$response = array();
		$response['result'] = $values;
		$response['error'] = NULL;
		if(empty($values['id']))
			$response['id'] = NULL;
		else {
			$response['id'] = $values['id'];
			unset($response['result']['id']);
		}

		// encode it and deliver the outcome
		if($encoded = Safe::json_encode($response))
			return array(TRUE, $encoded);
		return array(FALSE, 'No way to encode the request');
	}

	/**
	 * parse a request on server side
	 *
	 * @param array the received $HTTP_RAW_POST_DATA
	 * @return an array of which the first value indicates call success or failure
	 */
	function import_request($data) {
		if($decoded = Safe::json_decode($data))
			return array(TRUE, $decoded);
		return array(FALSE, 'No way to decode the request');
	}

	/**
	 * parse a response on client side
	 *
	 * @param string the received HTTP body
	 * @param string the received HTTP headers
	 * @param mixed the submitted parameters
	 * @return an array of which the first value indicates call success or failure
	 */
	function import_response($data, $headers=NULL, $parameters=NULL) {
		if($decoded = Safe::json_decode($data))
			return array(TRUE, $decoded);
		return array(FALSE, 'No way to decode the response');
	}

}

?>