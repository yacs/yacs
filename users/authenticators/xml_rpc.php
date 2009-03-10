<?php
/**
 * the XML-RPC authenticator
 *
 * To work properly this plugin has to know where to submit the XML-RPC request.
 * This is configured by adding the target URL as a parameter.
 *
 * Example configuration:
 * [snippet]
 * xml_rpc http://www.yacs.fr/yacs/services/xml_rpc.php
 * [/snippet]
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Xml_rpc extends Authenticator {

	/**
	 * login
	 *
	 * The script checks provided name and password against remote server.
	 *
	 * This is done by transmitting the user name and the password to the origin server,
	 * through a XML-RPC call ([code]drupal.login[/code]).
	 * On success the origin server will provide the original id for the user profile.
	 * Else a null id will be returned.
	 *
	 * @link http://drupal.org/node/312 Using distributed authentication (drupal.org)
	 *
	 * @param string the nickname or the email address of the user
	 * @param string the submitted password
	 * @return TRUE on succesful authentication, FALSE othewise
	 */
	function login($name, $password) {
		global $context;

		// we need some parameters
		if(!isset($this->attributes['authenticator_parameters']) || !$this->attributes['authenticator_parameters']) {
			Logger::error(i18n::s('Please provide parameters to the authenticator.'));
			return FALSE;
		}

		// submit credentials to the authenticating server
		include_once $context['path_to_root'].'services/call.php';
		$result = Call::invoke($this->attributes['authenticator_parameters'], 'drupal.login', array($name, $password), 'XML-RPC');

		// invalid result
		if(!$result || (@count($result) < 2)) {
			Logger::error(sprintf(i18n::s('Impossible to complete XML-RPC call to %s.'), $this->attributes['authenticator_parameters']));
			return FALSE;
		}

		// successful authentication
		if($result[0] && ($result[1] > 0))
			return TRUE;

		// failed authentication
		return FALSE;
	}

}

?>