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
 * - comma separated list of ldap options: (optional)
 *     DEREF=never|always|search|find  (default=never)
 *     PROTOCOL_VERSION=2|3            (default=3)
 *     SCOPE=one|sub                   (default=sub)
 *     SIZELIMIT=n                     (default=0)
 *     TIMELIMIT=n                     (default=0)
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
 * Example configurations:
 * [snippet]
 * ldap ldap.mydomain.com %u %p
 * ldap ldap.mydomain.com %u %p "" "" PROTOCOL_VERSION=2
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
 * ldap ldap.mydomain.com ldap_account passw0rd ou=mydomain,c=ww "(&(cn=%u)(userPassword=%p))" DEREF=always,SCOPE=one
 * [/snippet]
 *
 * Note: Do not forgot to add a search expression to parameters, else
 * all requests will be validated on bind.
 *
 * Note: This requires to have passwords stored in clear in the directory...
 *
 * @link http://www.ietf.org/rfc/rfc2254.txt String Representation of LDAP
 *
 * @author Bernard Paques
 * @author Alexandre Francois [email]alexandre-francois@voila.fr[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Ldap_Authenticator extends Authenticator {

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
			Logger::error(i18n::s('Please provide parameters to the authenticator.'));
			return FALSE;
		}

		// tokenize enclosed parameters
		$tokens = preg_split('/(")/', $this->attributes['authenticator_parameters'], -1, PREG_SPLIT_DELIM_CAPTURE);
		$outside = TRUE;
		$parameters = array();
		foreach($tokens as $token) {

			// sanity check --PREG_SPLIT_NO_EMPTY does not work
			if(!trim($token)) {
				// catch "" arguments (used for example as an empty password)
				if (!$outside)
					$parameters[] = "";
				continue;
			}

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
			Logger::error(i18n::s('Provide at least server name to the LDAP authenticator.'));
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

		// parse options
		$opt_deref=LDAP_DEREF_NEVER;
		$opt_protocol_version=3;
		$opt_sizelimit=0;
		$opt_timelimit=0;
		$opt_ldap_search_func="ldap_search";
		if(isset($parameters[5])) {
			$tokens = preg_split('/,/', $parameters[5], -1, PREG_SPLIT_NO_EMPTY);
			foreach($tokens as $token) {
				$argerror   = $valerror   = 0;
				$argerror_s = $argerror_c = '';
				list($key, $val) = explode('=', $token, 2);

				if(    !strcasecmp($key, "DEREF")) {
					if(    !strcasecmp($val,"never"))
						$opt_deref=LDAP_DEREF_NEVER;
					elseif(!strcasecmp($val,"always"))
						$opt_deref=LDAP_DEREF_ALWAYS;
					else
						$valerror = 1;
				}
				elseif(!strcasecmp($key, "PROTOCOL_VERSION")) {
					if($val == 2 || $val == 3)
						$opt_protocol_version = $val;
					else
						$valerror = 1;
				}
				elseif(!strcasecmp($key, "SCOPE")) {
					if(    !strcasecmp($val, "one"))
						$opt_ldap_search_func = "ldap_list";
					elseif(!strcasecmp($val, "sub"))
						$opt_ldap_search_func = "ldap_search";
					else
						$valerror = 1;
				}
				elseif(!strcasecmp($key, "SIZELIMIT")) {
					if(ctype_digit($val))
						$opt_sizelimit = $val;
					else
						$valerror = 1;
				}
				elseif(!strcasecmp($key, "TIMELIMIT")) {
					if(ctype_digit($val))
						$opt_timelimit = $val;
					else
						$valerror = 1;
				}
				else {
					$argerror_s = sprintf(i18n::s("Unknown LDAP option %s."), $key);
					$argerror_c = sprintf(i18n::c("Unknown LDAP option %s."), $key);
					$argerror = 1;
				}

				// a wrong value must trigger an error message
				if($valerror) {
					$argerror_s = sprintf(i18n::s("LDAP %s: bad value '%s'."), $key, $val);
					$argerror_c = sprintf(i18n::c("LDAP %s: bad value '%s'."), $key, $val);
					$argerror = 1;
				}

				// print any error message raised while parsing the option
				if($argerror) {
					Logger::error($argerror_s);
					if($context['with_debug'] == 'Y')
						Logger::remember('users/authenticators/ldap.php', $argerror_c, '', 'debug');
					return(FALSE);
				}
			}
		}

		// ensure we can move forward
		if(!is_callable('ldap_connect')) {
			Logger::error(i18n::s('Please activate the LDAP library.'));
			if($context['with_debug'] == 'Y')
				Logger::remember('users/authenticators/ldap.php', i18n::c('Please activate the LDAP library.'), '', 'debug');
			return FALSE;
		}

		// open network socket
		if(!$handle = @ldap_connect($server, $port)) {
			Logger::error(sprintf(i18n::s('Impossible to connect to %.'), $server));
			if($context['with_debug'] == 'Y')
				Logger::remember('users/authenticators/ldap.php', sprintf(i18n::c('Impossible to connect to %.'), $server.':'.$port), '', 'debug');
			return FALSE;
		}

		// set desired options
		@ldap_set_option($handle, LDAP_OPT_PROTOCOL_VERSION, $opt_protocol_version);
		@ldap_set_option($handle, LDAP_OPT_DEREF, $opt_deref);
		@ldap_set_option($handle, LDAP_OPT_SIZELIMIT, $opt_sizelimit);
		@ldap_set_option($handle, LDAP_OPT_TIMELIMIT, $opt_timelimit);

		// bind to directory, namely or anonymously
		if($bind_dn && @ldap_bind($handle, $bind_dn, $bind_password))
			;
		elseif(!$bind_dn && @ldap_bind($handle))
			;
		else {
			Logger::error(sprintf(i18n::s('Impossible to bind to LDAP server %s.'), $server).BR.ldap_errno($handle).': '.ldap_error($handle));
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
		if(!$result = @call_user_func($opt_ldap_search_func, $handle, $search_dn, $search_filter, array('cn'))) {
			Logger::error(sprintf(i18n::s('Impossible to search in LDAP server %s.'), $server).BR.ldap_errno($handle).': '.ldap_error($handle));
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