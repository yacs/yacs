<?php
/**
 * the LDAP authenticator
 *
 * To work properly this plugin has to know where and how to submit the LDAP request.
 * This is configured by adding some parameters:
 * - server name
 * - distinguished name to be used in search
 * - distinguished name to be used in bind
 * - password to be used in bind
 * - optional filter
 *
 * Parameters can contain placeholders:
 * - %u to be replaced by provided name
 * - %p to be replaced by provided password
 *
 * Example configuration:
 * [snippet]
 * ldap ldap.mydomain.com "mydomain" "uname %u" "password %p" "some filter"
 * [/snippet]
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Ldap extends Authenticator {

	/**
	 * login
	 *
	 * The script checks provided name and password against remote server.
	 *
	 * This is done by transmitting the user name and the password
	 * to the directory.
	 *
	 * @param string the nickname of the user
	 * @param string the submitted password
	 * @return TRUE on successful authentication, FALSE othewise
	 */
	function login($name, $password) {
		global $context;

		// we need some parameters
		if(!isset($this->attributes['authenticator_parameters']) || !$this->attributes['authenticator_parameters']) {
			Skin::error(i18n::s('Please indicate adequate parameters to the LDAP authenticator.'));
			return FALSE;
		}

		// tokenize enclosed parameters
		$tokens = preg_split('/(")/', $this->attributes['authenticator_parameters'], -1, PREG_SPLIT_DELIM_CAPTURE);
		$outside = TRUE;
		$parameters = array();
		foreach($tokens as $token) {

			// sanity check --PREG_SPLIT_NO_EMPTY does not work
			if(!trim($token))
				continue;

			// begin or end of a token
			if($token == '"') {
				$outside = !$outside;
				continue;
			}

			// outside, each word is a token
			if($outside)
				$parameters = array_merge($parameters, explode(' ', trim($token)));

			// inside
			else
				$parameters[] = trim($token);

		}

		// ensure a minimum number of parameters
		if(count($parameters) < 2) {
			Skin::error(i18n::s('Provide at least server name and distinguished name to the LDAP authenticator.'));
			return FALSE;
		}

		// prepare network parameters
		$server = $parameters[0];
		if(strstr($server, ':'))
			list($server, $port) = explode(':', $server, 2);
		else
			$port = 389;

		// distinguished name used for search
		$search_dn = '';
		if(isset($parameters[1]))
			$search_dn = $parameters[1];

		// distinguished name used for bind
		$bind_dn = '';
		if(isset($parameters[2]))
			$bind_dn = str_replace('%u', $name, $parameters[2]);

		// password used for bind
		$bind_password = '';
		if(isset($parameters[3]))
			$bind_password = str_replace('%p', $password, $parameters[3]);

		// additional filter
		$search_filter = '';
		if(isset($parameters[4]))
			$search_filter = str_replace(array('%u', '%p'), array($name, $password), $parameters[4]);

		// ensure we can move forward
		if(!is_callable('ldap_connect')) {
			Skin::error(i18n::s('Please activate the LDAP library.'));
			return FALSE;
		}

		// open network socket
		if(!$handle = @ldap_connect($server, $port)) {
			Skin::error(sprintf(i18n::s('Impossible to open a network connection to %s.'), $server));
			return FALSE;
		}

		// bind to directory, namely or anonymously
		if($bind_dn && @ldap_bind($handle, $bind_dn, $bind_password))
			;
		elseif(@ldap_bind($handle))
			;
		else {
			ldap_close($handle);
			Skin::error(sprintf(i18n::s('Impossible to bind to LDAP server %s.'), $server));
			return FALSE;
		}

		// search the directory
		if(!$result = @ldap_search($handle, $search_dn, $search_filter, array('cn'))) {
			ldap_close($handle);
			Skin::error(sprintf(i18n::s('Impossible to search in LDAP server %s.'), $server));
			return FALSE;
		}

		// successful match
		if(@ldap_first_entry($handle, $result) !== FALSE) {
			ldap_close($handle);
			return TRUE;
		}

		// authentication has failed
		ldap_close($handle);
		return FALSE;
	}

}

?>