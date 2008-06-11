<?php
/**
 * the LDAP authenticator
 *
 * To work properly this plugin has to know where and how to submit the LDAP request.
 * This is configured by adding some parameters:
 * - server name
 * - distinguished name to be used in bind
 * - password to be used in bind
 * - distinguished name to be used in search (optional)
 * - search expression (optional)
 *
 * Add " characters around any parameter that includes some white space.
 *
 * Moreover, parameters can contain placeholders:
 * - %u to be replaced by provided name
 * - %p to be replaced by provided password
 *
 * Two different authentication schemes are allowed:
 * - bind to the LDAP directory with credentials provided by the end user
 * - bind to the LDAP directory with a fixed account, and look for a record
 * matching provided credentials
 *
 * [title]Dynamic bind[/title]
 *
 * In this case, the user name and related password used for binding are
 * referring to placeholders. You may use a static search expression as well.
 *
 * Example configuration:
 * [snippet]
 * ldap ldap.mydomain.com %u %p
 * [/snippet]
 *
 * Note: This is the most secured and easy way to authenticate some user.
 *
 * [title]Static bind[/title]
 *
 * In this case, the user name and password used for binding have to be mentioned
 * in parameters, and placeholders will be put in the search expression.
 *
 * Example configuration:
 * [snippet]
 * ldap ldap.mydomain.com ldap_account passw0rd ou=mydomain,c=ww "(&(cn=%u)(userPassword=%p))"
 * [/snippet]
 *
 * Note: Do not forgot to add a search expression to parameters, else
 * all requests will be validated on bind.
 *
 * Note: This requires to have passwords stored in clear in the directory...
 *
 * @link http://www.ietf.org/rfc/rfc2254.txt String Representation of LDAP
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author Alf83
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
		if(count($parameters) < 1) {
			Skin::error(i18n::s('Provide at least server name to the LDAP authenticator.'));
			return FALSE;
		}

		// prepare network parameters
		$server = $parameters[0];
		if(strstr($server, ':'))
			list($server, $port) = explode(':', $server, 2);
		else
			$port = 389;

		// distinguished name used for bind
		$bind_dn = '';
		if(isset($parameters[1]))
			$bind_dn = str_replace('%u', $name, $parameters[1]);

		// password used for bind
		$bind_password = '';
		if(isset($parameters[2]))
			$bind_password = str_replace('%p', $password, $parameters[2]);

		// distinguished name used for search
		$search_dn = '';
		if(isset($parameters[3]))
			$search_dn = $parameters[3];

		// encode provided parameters to avoid LDAP injections
		$name = preg_replace('/([^a-zA-Z0-9\' ])/e', "chr(92).bin2hex('\\1')", $name);
		$password = preg_replace('/([^a-zA-Z0-9\' ])/e', "chr(92).bin2hex('\\1')", $password);

		// search expression
		$search_filter = '';
		if(isset($parameters[4]))
			$search_filter = str_replace(array('%u', '%p'), array($name, $password), $parameters[4]);

		// ensure we can move forward
		if(!is_callable('ldap_connect')) {
			Skin::error(i18n::s('Please activate the LDAP library.'));
			if($context['with_debug'] == 'Y')
				Logger::remember('users/authenticators/ldap.php', i18n::c('Please activate the LDAP library.'), '', 'debug');
			return FALSE;
		}

		// open network socket
		if(!$handle = @ldap_connect($server, $port)) {
			Skin::error(sprintf(i18n::s('Impossible to open a network connection to %s.'), $server));
			if($context['with_debug'] == 'Y')
				Logger::remember('users/authenticators/ldap.php', sprintf(i18n::c('Impossible to open a network connection to %s.'), $server.':'.$port), '', 'debug');
			return FALSE;
		}

		// switch to protocol V3 (the current standard)
		@ldap_set_option($handle, LDAP_OPT_PROTOCOL_VERSION, 3);

		// bind to directory, namely or anonymously
		if($bind_dn && @ldap_bind($handle, $bind_dn, $bind_password))
			;
		elseif(!$bind_dn && @ldap_bind($handle))
			;
		else {
			Skin::error(sprintf(i18n::s('Impossible to bind to LDAP server %s.'), $server).BR.ldap_errno($handle).': '.ldap_error($handle));
			if($context['with_debug'] == 'Y')
				Logger::remember('users/authenticators/ldap.php', sprintf(i18n::c('Impossible to bind to LDAP server %s.'), $server.' '.$bind_dn.' '.$bind_password), ldap_errno($handle).': '.ldap_error($handle), 'debug');
			ldap_close($handle);
			return FALSE;
		}

		// stop on successful bind
		if(!trim($search_filter)) {
			ldap_close($handle);
			return TRUE;
		}

		// search the directory
		if(!$result = @ldap_search($handle, $search_dn, $search_filter, array('cn'))) {
			Skin::error(sprintf(i18n::s('Impossible to search in LDAP server %s.'), $server).BR.ldap_errno($handle).': '.ldap_error($handle));
			if($context['with_debug'] == 'Y')
				Logger::remember('users/authenticators/ldap.php', sprintf(i18n::c('Impossible to search in LDAP server %s.'), $server), ldap_errno($handle).': '.ldap_error($handle), 'debug');
			ldap_close($handle);
			return FALSE;
		}

		// successful match
		if(@ldap_first_entry($handle, $result) !== FALSE) {
			ldap_free_result($result);
			ldap_close($handle);
			return TRUE;
		}

		// authentication has failed
		if($context['with_debug'] == 'Y')
			Logger::remember('users/authenticators/ldap.php', sprintf(i18n::c('No match for %s.'), $search_filter), '', 'debug');
		ldap_free_result($result);
		ldap_close($handle);
		return FALSE;
	}

}

?>