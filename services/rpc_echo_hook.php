<?php
/**
 * A simple web service
 *
 * This script is aiming to test remote procedure calls.
 *
 * Syntax: echo(parameters) returns parameters
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
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
 	'source' => 'http://www.yetanothercommunitysystem.com/'
);

class RPC_Echo {

	function serve($parameters) {
		return $parameters;
	}

}

?>