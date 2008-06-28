<?php
/**
 * the authenticator interface used during logins
 *
 * This interface allows easy integration of YACS into existing back-end systems.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Authenticator {

	/**
	 * attributes specific to this instance
	 */
	var $attributes;

	/**
	 * The script checks provided name and password.
	 *
	 * @param string the nickname or the email address of the user
	 * @param string the submitted password
	 * @return TRUE on succesful authentication, FALSE othewise
	 *
	 * @see users/users.php
	 */
	function login($name, $password) {
		return FALSE;
	}

	/**
	 * create a new authenticator instance
	 *
	 * This function creates an instance of the Authenticator class based on the given type.
	 * For the type '[code]foo[/code]', the script file '[code]users/authenticators/foo.php[/code]' is loaded.
	 *
	 * Example:
	 * [php]
	 * // create a new authenticator
	 * $authenticator = Authenticator::bind('ldap');
	 * [/php]
	 *
	 * The provided string may include parameters after the type.
	 * These parameters, if any, are saved along authenticator attributes.
	 *
	 * @param string authenticator type, followed by parameters if any
	 * @return a brand new instance
	 *
	 */
	function bind($type='') {
		global $context;

		// stop hackers, if any
		$type = preg_replace(FORBIDDEN_STRINGS_IN_PATHS, '', strip_tags($type));

		// remove side spaces
		$type = trim($type);

		// sanity check
		if(!$type) {
			Skin::error(i18n::s('Invalid authenticator type.'));
			return NULL;
		}

		// localize authenticators strings
		i18n::bind('users');

		// extract parameters, if any
		$parameters = '';
		if((strlen($type) > 1) && (($separator = strpos($type, ' ', 1)) !== FALSE)) {
			$parameters = substr($type, $separator+1);
			$type = substr($type, 0, $separator);
		}

		// load the authenticator class file
		$file = $context['path_to_root'].'users/authenticators/'.$type.'.php';
		if(is_readable($file))
			include_once $file;

		// create the instance
		if(class_exists($type)) {
			$authenticator =& new $type;
			$authenticator->attributes = array();
			$authenticator->attributes['authenticator_type'] = $type;
			$authenticator->attributes['authenticator_parameters'] = $parameters;
			return $authenticator;
		}

		// houston, we've got a problem
		Skin::error(sprintf(i18n::s('Unknown authenticator type %s'), $type));
		return NULL;
	}

}

?>