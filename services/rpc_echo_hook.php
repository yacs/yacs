<?php
/**
 * A simple web service
 *
 * This script is aiming to test remote procedure calls.
 *
 * Syntax: echo(parameters) returns parameters
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// server hook to some web service
global $hooks;
$hooks[] = array(
	'id'		=> 'echo',
	'type'		=> 'serve',
	'script'	=> 'services/rpc_echo_hook.php',
	'function'	=> 'RPC_Echo::serve',
	'label_en'	=> 'Echo service',
	'label_fr'	=> 'Service de renvoi',
	'description_en' => 'A very simple web service.',
	'description_fr' => 'Un service web simpliste.',
	'source' => 'http://www.yacs.fr/'
);

class RPC_Echo {

	function serve($parameters) {

		// look for a message
		if(empty($parameters['message']))
			return array('code' => -32602, 'message' => 'Invalid param "message"');

		// extend the 'message' parameter
		$parameters['message'] = ltrim($parameters['message'].' Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.');

		// return everything
		return $parameters;
	}

}

?>
