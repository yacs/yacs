<?php
/**
 * Establish a communication between two phone numbers
 *
 * A service aiming to bridge the web with telephony services, through JSON-RPC.
 *
 * Syntax: obs.call(<user_id>, <phone_number>) returns <call_id>
 *
 * This call starts a phone call between the phone number of a registered member
 * and any other international number, like in the following example:
 *
 * [snippet]
 * Yacs.call( { method: 'obs.call', params: { user: '123', number: '33146411313' } }, function(s) { if(s.message) { alert(s.message); } } );
 * [/snippet]
 *
 * Syntax: obs.release(<call_id>) returns <call_id>
 *
 * This call stops a phone call that has been created with obs.call().
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// server hook to some web service
global $hooks;

// start a one-to-one call
$hooks[] = array(
	'id'		=> 'obs.call',
	'type'		=> 'serve',
	'script'	=> 'services/rpc_obs_hook.php',
	'function'	=> 'RPC_OBS::call',
	'label_en'	=> 'Start a phone call',
	'label_fr'	=> 'Etablir une communication t&eacute;l&eacute;phonique',
	'description_en' => 'Between one user and any other number',
	'description_fr' => 'Entre un utilisateur et un visiteur',
	'source' => 'http://www.yacs.fr/'
);

// stop a one-to-one call
$hooks[] = array(
	'id'		=> 'obs.release',
	'type'		=> 'serve',
	'script'	=> 'services/rpc_obs_hook.php',
	'function'	=> 'RPC_OBS::release',
	'label_en'	=> 'Stop a phone call',
	'label_fr'	=> 'Stopper une communication t&eacute;l&eacute;phonique',
	'source' => 'http://www.yacs.fr/'
);

class RPC_OBS {

	/**
	 * build a link to interact with OBS API
	 *
	 * @param string the API action (e.g., 'call/createCall')
	 * @param mixed parameters of the query string
	 * @return string the URL to be used
	 */
	function build_obs_link($action, $parameters) {
		global $context;

		// link to the server
		$url = 'http://run.orangeapi.com/'.$action.'.xml';

		// add secret key provided by OBS, if not set yet
		if(!isset($parameters['id']) && isset($context['obs_api_key']))
			$parameters['id'] = $context['obs_api_key'];

		// build a string of parameters
		if(is_array($parameters))
			$parameters = join($parameters, '&');

		// job done
		return $url.'?'.$parameters;
	}

	/**
	 * start a phone call
	 *
	 * @param array e.g., array('user' => '123', 'number' => '33146411313')
	 * @return mixed some error array with code and message, or the id of the on-going call
	 */
	function call($parameters) {
		global $context;

		// do we have some API key?
		if(!isset($context['obs_api_key']))
			return array('code' => -32000, 'message' => 'Missing credentials to use OBS API');

		// look for a user id
		if(empty($parameters['user']))
			return array('code' => -32602, 'message' => 'Invalid parameter "user"');

		// get the matching record
		if(!$user = Users::get($parameters['user']))
			return array('code' => -32602, 'message' => 'Unable to find this "user"');

		// get the related phone number
		if(!isset($user['phone_number']))
			return array('code' => -32602, 'message' => 'No phone number for this "user"');

		// look for an external number
		if(empty($parameters['number']))
			return array('code' => -32602, 'message' => 'Invalid parameter "number"');

		// data to be submitted to OBS
		$data = array();
		$data[] = 'id='.urlencode($context['obs_api_key']);
		$data[] = 'from='.urlencode(ltrim($user['phone_number'], '0+'));
		$data[] = 'to='.urlencode(ltrim($parameters['number'], '0+'));

		// build the endpoint to invoke
		$url = self::build_obs_link('call/createCall', $data);

		// do create the call
		if(!$response = http::proceed_natively($url, NULL, NULL, 'services/rpc_obs_hook.php'))
			return array('code' => -32603, 'message' => 'Unable to query the OBS API');

		// ensure we receive correct xml
		if(!$xml = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA))
			return array('code' => -32603, 'message' => 'Invalid response from OBS API');
		if(!$xml->status->status_code)
			return array('code' => -32603, 'message' => 'Invalid response from OBS API');

		// stop on error
		if($xml->status->status_code != 200)
			return array('code' => -32000, 'message' => 'Error: '.$xml->status->status_code.' '.$xml->status->status_msg);

		// look for call id
		if(!$id = $xml->call_info->call_id)
			return array('code' => -32603, 'message' => 'Invalid response from OBS API');

		// if surfer has been authenticated, save his phone number
		if($user = Users::get(Surfer::get_id())) {

			// update session data
			$_SESSION['surfer_phone_number'] = $parameters['number'];

			// update surfer record too
			$query = "UPDATE ".SQL::table_name('users')
				." SET phone_number='".SQL::escape($parameters['number'])."'"
				." WHERE id = ".SQL::escape(Surfer::get_id());
			SQL::query($query, FALSE, $context['users_connection']);
		}

		// provide id to caller, for subsequent actions on the call
		return array('call_id' => (string)$id);
	}

	/**
	 * stop a phone call
	 *
	 * @param array e.g., array('call_id' => '123')
	 * @return mixed some error array with code and message, or the id of the stopped call
	 */
	function release($parameters) {
		global $context;

		// do we have some API key?
		if(!isset($context['obs_api_key']))
			return array('code' => -32000, 'message' => 'Missing credentials to use OBS API');

		// look for a call id
		if(empty($parameters['call_id']))
			return array('code' => -32602, 'message' => 'Invalid parameter "call_id"');

		// data to be submitted to OBS
		$data = array();
		$data[] = 'id='.urlencode($context['obs_api_key']);
		$data[] = 'call_id='.urlencode($parameters['call_id']);

		// build the endpoint to invoke
		$url = self::build_obs_link('call/releaseCall', $data);

		// do release the call
		if(!$response = http::proceed_natively($url, NULL, NULL, 'services/rpc_obs_hook.php'))
			return array('code' => -32603, 'message' => 'Unable to query the OBS API');

		// ensure we receive correct xml
		if(!$xml = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA))
			return array('code' => -32603, 'message' => 'Invalid response from OBS API');

		// stop on error
		if($xml->status->status_code != 200)
			return array('code' => -32000, 'message' => 'Error: '.$xml->status->status_code.' '.$xml->status->status_msg);

		// look for call id
		if(!$id = $xml->call_info->call_id)
			return array('code' => -32603, 'message' => 'Invalid response from OBS API');

		// provide id back to caller
		return array('call_id' => (string)$id);
	}

}

?>