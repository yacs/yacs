<?php
/**
 * A simple authentication web service
 *
 * This script is aiming to authenticate users remotely.
 *
 * Syntax: drupal.login(username, password) returns integer
 *
 * This script looks into the local database for the given username and password.
 * On match it returns the local id of the found user profile.
 * Else it returns the value 0.
 *
 * @link http://www.drop.org/node.php?id=531 A distributed user system and a Drupal roadmap
 * @link http://drupal.org/doxygen/drupal/drupal_8module.html modules/drupal.module File Reference
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// server hook to some web service
global $hooks;
$hooks[] = array(
	'id'		=> 'drupal.login',
	'type'		=> 'serve',
	'script'	=> 'services/xml_rpc_drupal.login_hook.php',
	'function'	=> 'Drupal::login',
	'label_en'	=> 'Remote authentication (Drupal style)',
	'label_fr'	=> 'Authentification &agrave; distance (comme Drupal)',
	'description_en' => 'The most simple authentication mechanism one can think of.',
	'description_fr' => 'Le m&eacute;canisme d\'authentification le plus simple auquel on puisse penser',
	'source' => 'http://www.yetanothercommunitysystem.com/'
);

class Drupal {

	function login($parameters) {
		global $context;

		if($user = Users::login($parameters[0], $parameters[1]))
			return $user['id'];

		return 0;
	}

}

?>