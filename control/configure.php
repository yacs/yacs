<?php
/**
 * change YACS main parameters (database, language, etc.)
 *
 * Use this script to change database and other essential parameters of this server.
 *
 * Access to this page is reserved to associates.
 * Moreover, the current surfer is considered as an associate in any of following situations:
 * - there is no configuration file and no switch file
 * - there is no connection to the database and no switch file
 *
 * Configuration information is saved into [code]parameters/control.include.php[/code].
 * If YACS is prevented to write to the file, it displays parameters to allow for a manual update.
 *
 * The file [code]parameters/control.include.php.bak[/code] can be used to restore
 * the active configuration before the last change.
 *
 * If the file [code]parameters/demo.flag[/code] exists, the script assumes that this instance
 * of YACS runs in demonstration mode.
 * In this mode the edit form is not displayed at all to protect database passwords.
 *
 *
 * Database parameters:
 *
 * [*] [code]database_server[/code] - database host name or IP address
 *
 * [*] [code]database_user[/code] - account to use to connect to the database
 *
 * [*] [code]database_password[/code] - to authenticate to the database server
 *
 * [*] [code]database[/code] - the name of the database to use
 *
 * [*] [code]table_prefix[/code] - the prefix for all tables used for this YACS instance
 *
 * [*] [code]users_database_server[/code] - only for user records - database host name or IP address
 *
 * [*] [code]users_database_user[/code] - only for user records - account to use to connect to the database
 *
 * [*] [code]users_database_password[/code] - only for user records - to authenticate to the database server
 *
 * [*] [code]users_database[/code] - only for user records - the name of the database to use
 *
 * [*] [code]users_table_prefix[/code] - only for user records - the prefix for the users table
 *
 *
 * System parameters:
 *
 * [*] [code]directory_mask[/code] - Default mask to be used for mkdir.
 *
 * [*] [code]file_mask[/code] - Default mask to be used for files.
 *
 * [*] [code]preferred_language[/code] - The two-letter ISO code representing the language
 * to be used for auto-generated text, such as background e-mail message, etc.
 * Default value is '[code]en[/code]'.
 *
 * [*] [code]without_language_detection[/code] - By default yacs attempts to localize its interface
 * depending on browser data. In some situations you may prefer to turn this parameter to 'Y', and
 * to stick with the preferred language only.
 * Default value is '[code]N[/code]'.
 *
 * [*] [code]with_compression[/code] - By default page content is transferred 'as-is' to user agents.
 * If this parameter is set explicitly to 'Y', YACS will attempt to compress every HTML and XML content.
 * You should depart from the default mode if the HTTP service does not feature compression.
 *
 * [*] [code]without_cache[/code] - Normally YACS uses partial caching techniques to speed up page rendering.
 * However sometimes it can be useful to disable this feature.
 *
 * [*] [code]with_cron[/code] - By default YACS attempts to add background
 * processing to page rendering. If you have explicitly configured your server
 * to trigger [script]cron.php[/script] instead, for example through crontab,
 * you can set the parameter [code]with_cron[/code] to 'Y' to smooth response
 * times of YACS.
 *
 * [*] [code]with_debug[/code] - By default YACS runs in production mode.
 * However, at development site you can turn this parameter to 'Y' to get more information.
 * For example, PHP is reconfigured to display all messages, including notices and warnings.
 *
 *
 * Inbound HTTP parameters:
 *
 * [*] [code]main_host[/code] - Because crontabs are shared at most hosting
 * providers, [script]cron.php[/script] believes it runs at ##localhost##, where
 * the software expects a real host name. The parameter overcomes this situation
 * and can even be set as a virtual host of your choice.
 *
 * [*] [code]proxy_server[/code] - if the infrastructure requires a proxy for external HTTP requests
 *
 * [*] [code]proxy_user[/code] - the account to be used against the proxy
 *
 * [*] [code]proxy_password[/code] - authentication data
 *
 * [*] [code]url_to_root[/code] - The absolute path to YACS scripts.
 * The default value is '[code]/yacs/[/code]'.
 *
 * [*] [code]with_friendly_urls[/code] - By default pages are referenced using query strings
 * (e.g;, [code]articles/view.php?id=123[/code]). In numerous cases you can activate friendly URLS
 * to let surfers and spiders reference your pages more easily (e.g;, [code]articles/view.php/123[/code]).
 *
 * [*] [code]with_alternate_urls[/code] - By default yacs put titles in page addresses
 * Set this parameter to 'Y' to use nick names instead.
 *
 * [*] [code]with_given_host[/code] - By default yacs accepts any host name.
 * Set this parameter to 'Y' to redirect users to the right host name.
 *
 * [*] [code]with_https[/code] - By default yacs accepts any web requests.
 * Set this parameter to 'Y' to ensure transfers are secured over SSL.
 *
 * [*] [code]without_http_cache[/code] - By default YACS handles [code]ETag[/code] and [code]Last-Modified[/code]
 * HTTP headers to help proxies and browsers cache provided information.
 * However sometimes it can be useful to disable this feature.
 *
 * [*] [code]without_internet_visibility[/code] - By default YACS links to public aggregators.
 * Change this parameter to 'Y' to disable this feature.
 *
 * [*] [code]without_outbound_http[/code] - By default YACS connects to other web servers.
 * Change this parameter to 'Y' to disable this feature.
 *
 *
 * Outbound e-mail parameters:
 *
 * [*] [code]with_email[/code] - the sending of e-mail messages has to be explicitly activated.
 * By default YACS does not send messages.
 *
 * [*] [code]mail_variant[/code] - if set to 'smtp', authenticate during the SMTP session.
 * If set to 'pop3', perform POP3 authentication before starting the SMTP session. Else
 * rely to PHP and system default settings to transmit messages.
 *
 * [*] [code]mail_server[/code] - host name or IP address of the server that will process SMTP requests.
 * There is no default value.
 *
 * [*] [code]mail_from[/code] - the account used to send messages
 * There is no default value.
 *
 * [*] [code]mail_from_surfer[/code] - if set to 'Y', use surfer e-mail address for the From: field
 * of notifications. Else use the mail_from parameter.
 *
 * [*] [code]mail_logger_recipient[/code] - one address, or a comma-separated list of addresses,
 * that will receive event messages. There is no default value.
 *
 * [*] [code]mail_account[/code] and [code]mail_password[/code] - data used for authentication
 * before SMTP sending. There is no default values.
 *
 * [*] [code]mail_hourly_maximum[/code] - the maximum number of outbound
 * messages per hour. The default value is 50 messages per hour, which is safe
 * at most Internet service providers.
 *
 * [*] [code]debug_mail[/code] - if set to 'Y',
 * save titles and recipients of sent messages into [code]temporary/debug.txt[/code]
 *
 *
 * Skin selection:
 *
 * [*] [code]skin[/code] - while YACS is able to support several skins, one of them will be privileged.
 * Default value is '[code]joi[/code]'.
 *
 *
 * Password of last resort:
 *
 * [*] [code]password_of_last_resort[/code] - A pass phrase to be authenticated as an associate.
 * This parameter cannot be set through a web form.
 * The only way to set a password of last resort is to edit manually the main
 * configuration file, [code]parameters/control.include.php[/code].
 * To suppress the password of last resort you can simply use this
 * configuration panel, which will rewrite the configuration file.
 *
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Jan Boen
 * @tester Kedare
 * @tester Timster
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
include_once '../shared/global.php';

// if we have changed the url to root, consider it right now
if(isset($_REQUEST['url_to_root']))
	$context['url_to_root'] = encode_link($_REQUEST['url_to_root']);

// stop hackers
if(isset($_REQUEST['value']))
	$_REQUEST['value'] = preg_replace(FORBIDDEN_IN_PATHS, '_', strip_tags($_REQUEST['value']));

// if we are changing the skin
if(isset($_REQUEST['parameter']) && ($_REQUEST['parameter'] == 'skin') && isset($_REQUEST['value']) && Surfer::is_associate())
	$context['skin'] = 'skins/'.basename($_REQUEST['value']);

// load localized strings
i18n::bind('control');

// load the skin
load_skin('control');

// if no skin has been defined yet, we are in HTML
if(!defined('BR'))
	define('BR', '<br>');
if(!defined('EOT'))
	define('EOT', '>');

// if no configuration file or if no database
if(!file_exists('../parameters/control.include.php') || !isset($context['connection']) || !$context['connection'] ) {

	// consider the current surfer as an associate, but only on first installation
	if(!Surfer::is_associate() && !file_exists('../parameters/switch.on') && !file_exists('../parameters/switch.off')) {
		$fields = array();
		$fields['id'] = 1;
		$fields['nick_name'] = 'admin';
		$fields['email'] = '';
		$fields['capability'] = 'A';
		Surfer::set($fields);
		Logger::error(i18n::s('You are considered temporarily as an associate, with specific rights on this server. Please do not close your browser until the end of the configuration.'));
	}
}

// the path to this page
$context['path_bar'] = array( 'control/' => i18n::s('Control Panel') );

// the title of the page
$context['page_title'] = sprintf(i18n::s('%s: %s'), i18n::s('Configure'), i18n::s('System parameters'));

// ensure we have an associate
if(!Surfer::is_associate()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

	// forward to the control panel
	$menu = array('control/' => i18n::s('Control Panel'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// nothing more in demo mode
} elseif(file_exists($context['path_to_root'].'parameters/demo.flag')) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation in demonstration mode.'));

// display the input form, except if there is only one parameter to change
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'POST') && (!isset($_REQUEST['parameter']) || !isset($_REQUEST['value'])) ) {

	// the user form
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><div>'."\n";

	//
	// database parameters
	//
	$database = '';
	$fields = array();

	$database .= '<p>'.sprintf(i18n::s('Following parameters may be provided by your Internet Service Provider (ISP), or by some database manager. If you manage your own server, the database should have been created before moving forward. You may check the %s file for further information.'), '<a href="../'.i18n::s('readme.txt').'">'.i18n::s('readme.txt').'</a>')."</p>\n";

	// host name
	$label = i18n::s('Database server name');
	if(!isset($context['database_server']) || !$context['database_server'])
		$context['database_server'] = 'localhost';
	$input = '<input type="text" name="database_server" size="45" value="'.encode_field($context['database_server']).'" maxlength="255" />';
	$fields[] = array($label, $input);

	// database account
	$label = i18n::s('Login name');
	if(!isset($context['database_user']) || !$context['database_user'])
		$context['database_user'] = 'root';
	$input = '<input type="text" name="database_user" size="45" value="'.encode_field($context['database_user']).'" maxlength="255" />';
	$fields[] = array($label, $input);

	// related password
	$label = i18n::s('Login password');
	if(!isset($context['database_password']))
		$context['database_password'] = '';
	$input = '<input type="password" name="database_password" size="45" value="'.encode_field($context['database_password']).'" maxlength="255" />';
	$fields[] = array($label, $input);

	// database name
	$label = i18n::s('Database name');
	if(!isset($context['database']) || !$context['database'])
		$context['database'] = 'yacs';
	$input = '<input type="text" name="database" size="45" value="'.encode_field($context['database']).'" maxlength="255" />';
	$fields[] = array($label, $input);

	// prefix for table names
	$label = i18n::s('Prefix for table names');
	if(!isset($context['table_prefix']) || !$context['table_prefix'])
		$context['table_prefix'] = 'yacs_';
	$input = '<input type="text" name="table_prefix" size="45" value="'.encode_field($context['table_prefix']).'" maxlength="255" />';
	$fields[] = array($label, $input);

	// build the form
	$database .= Skin::build_form($fields);
	$fields = array();

	//
	// separate access for user records
	//
	$text = '<p>'.i18n::s('To share user information among several YACS servers configure below parameters specific to the table of users. Else keep fields empty.')."</p>\n";

	// secondary host name
	$label = i18n::s('Database server that hosts user information');
	if(!isset($context['users_database_server']) || !$context['users_database_server'])
		$context['users_database_server'] = '';
	$input = '<input type="text" name="users_database_server" size="45" value="'.encode_field($context['users_database_server']).'" maxlength="255" />';
	$fields[] = array($label, $input);

	// secondary account
	$label = i18n::s('Login name');
	if(!isset($context['users_database_user']) || !$context['users_database_user'])
		$context['users_database_user'] = '';
	$input = '<input type="text" name="users_database_user" size="45" value="'.encode_field($context['users_database_user']).'" maxlength="255" />';
	$fields[] = array($label, $input);

	// related password
	$label = i18n::s('Login password');
	if(!isset($context['users_database_password']))
		$context['users_database_password'] = '';
	$input = '<input type="password" name="users_database_password" size="45" value="'.encode_field($context['users_database_password']).'" maxlength="255" />';
	$fields[] = array($label, $input);

	// database name
	$label = i18n::s('Name of database that contains user information');
	if(!isset($context['users_database']) || !$context['users_database'])
		$context['users_database'] = '';
	$input = '<input type="text" name="users_database" size="45" value="'.encode_field($context['users_database']).'" maxlength="255" />';
	$fields[] = array($label, $input);

	// prefix for table names
	$label = i18n::s('Prefix for the users table');
	if(!isset($context['users_table_prefix']) || !$context['users_table_prefix'])
		$context['users_table_prefix'] = '';
	$input = '<input type="text" name="users_table_prefix" size="45" value="'.encode_field($context['users_table_prefix']).'" maxlength="255" />';
	$fields[] = array($label, $input);

	// build the form
	$text .= Skin::build_form($fields);
	$fields = array();

	// in a folded box
	$database .= Skin::build_box(i18n::s('Custom storage of user information'), $text, 'folded');

	//
	// system parameters
	//
	$system = '';

	// splash message
	$system .= '<p>'.i18n::s('If you do not know what following parameters mean, please use default values for safety.')."</p>\n";

	// preferred language for messages generated by YACS
	$label = i18n::s('Community language');
	if(!isset($context['preferred_language']))
		$context['preferred_language'] = 'en';
	$input = '';
	$locales = i18n::list_locales();
	foreach($locales as $locale => $text) {
		if($input)
			$input .= BR;
		$checked = '';
		if($context['preferred_language'] == $locale)
			$checked = ' checked="checked"';
		$input .= '<input type="radio" name="preferred_language" value="'.$locale.'"'.$checked.'/> '.$text;
	}
	$fields[] = array($label, $input);

	// language detection
	$label = i18n::s('Language detection');
	$input = '<input type="radio" name="without_language_detection" value="N"';
	if(!isset($context['without_language_detection']) || ($context['without_language_detection'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Attempt to adapt the interface to the language indicated by the browser.');
	$input .= BR.'<input type="radio" name="without_language_detection" value="Y"';
	if(isset($context['without_language_detection']) && ($context['without_language_detection'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Stick to the preferred language selected above.');
	$fields[] = array($label, $input);

	// content compression
	$label = i18n::s('Page compression');
	$input = '<input type="radio" name="with_compression" value="N"';
	if(!isset($context['with_compression']) || ($context['with_compression'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Do not try to compress transmitted data. The web engine already does it.');
	$input .= BR.'<input type="radio" name="with_compression" value="Y"';
	if(isset($context['with_compression']) && ($context['with_compression'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Compress content (gzip) transmitted to user agents when possible.');
	$fields[] = array($label, $input);

	// rendering cache
	$label = i18n::s('Rendering cache');
	$input = '<input type="radio" name="without_cache" value="N"';
	if(!isset($context['without_cache']) || ($context['without_cache'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Cache computed elements to speed up rendering.');
	$input .= BR.'<input type="radio" name="without_cache" value="Y"';
	if(isset($context['without_cache']) && ($context['without_cache'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Compute all page elements.');
	$fields[] = array($label, $input);

	// with_cron
	$label = i18n::s('Background processing');
	$input = '<input type="radio" name="with_cron" value="N"';
	if(!isset($context['with_cron']) || ($context['with_cron'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Add background processing at the end of page rendering.');
	$input .= BR.'<input type="radio" name="with_cron" value="Y"';
	if(isset($context['with_cron']) && ($context['with_cron'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('The server launches cron.php on its own').' ('.sprintf(i18n::s('see sample %s'), Skin::build_link('tools/yacs_crontab', 'yacs_crontab', 'open')).')';
	$fields[] = array($label, $input);

	// file_mask and directory_mask
	$label = i18n::s('Default masks');
	$input = i18n::s('Mask for files').' <input type="text" name="file_mask" size="5" value="'.encode_field(sprintf('0%o', $context['file_mask'])).'" maxlength="5" /> '
		.i18n::s('Mask for directories').' <input type="text" name="directory_mask" size="5" value="'.encode_field(sprintf('0%o', $context['directory_mask'])).'" maxlength="5" />';
	$fields[] = array($label, $input);

	// debug
	$label = i18n::s('Verbosity');
	$input = '<input type="radio" name="with_debug" value="N"';
	if($context['with_debug'] != 'Y')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Verbosity should be kept to a minimum (normal operation).');
	$input .= BR.'<input type="radio" name="with_debug" value="Y"';
	if($context['with_debug'] == 'Y')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Provide as much information as possible (development server).');
	$fields[] = array($label, $input);

	// build the form
	$system .= Skin::build_form($fields);
	$fields = array();

	//
	// HTTP parameters
	//
	$http = '';

	// splash message
	$http .= '<p>'.i18n::s('If you do not know what following parameters mean, please use default values for safety.')."</p>\n";

	// main_host
	$label = i18n::s('Server name');
	$input = '<input type="text" name="main_host" value="'.encode_field(isset($context['main_host'])?$context['main_host']:$context['host_name']).'" size="20" />'
		.BR.'<input type="radio" name="with_given_host" value="N"';
	if(!isset($context['with_given_host']) || ($context['with_given_host'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Accept all web requests.');
	$input .= BR.'<input type="radio" name="with_given_host" value="Y"';
	if(isset($context['with_given_host']) && ($context['with_given_host'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Redirect to this server name if another name is used in request.');
	$fields[] = array($label, $input);

	// url to root -- see shared/global.php to understand the usage of 'url_to_root_parameter'
	$label = i18n::s('Path (URL) to root directory');
	if(!isset($context['url_to_root_parameter']))
		$context['url_to_root_parameter'] = '';
	$input = '<input type="text" name="url_to_root_parameter" size="45" value="'.encode_field($context['url_to_root_parameter']).'" maxlength="255" />';
	$fields[] = array($label, $input);

	// friendly urls
	$label = i18n::s('URL generation');
	$input = '<input type="radio" name="with_friendly_urls" value="N"';
	if($context['with_friendly_urls'] != 'Y')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('This system does not support the mapping of args in the URL.').' (<code>articles/view.php?id=123</code>)';
	$input .= BR.'<input type="radio" name="with_friendly_urls" value="Y"';
	if($context['with_friendly_urls'] == 'Y')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Help search engines to index more pages.').' (<code>articles/view.php/123</code>)'
		.' ('.Skin::build_link('control/test.php/123/456', i18n::s('test link'), 'open').')';
	$input .= BR.'<input type="radio" name="with_friendly_urls" value="R"';
	if($context['with_friendly_urls'] == 'R')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Rewriting rules have been activated (in <code>.htaccess</code>) to support pretty references.').' (<code>article-123</code>)'
		.' ('.Skin::build_link('rewrite_test/123', i18n::s('test link'), 'open').')';
	$fields[] = array($label, $input);

	// alternate urls
	$label = i18n::s('URL optimization');
	$input = '<input type="radio" name="with_alternate_urls" value="N"';
	if($context['with_alternate_urls'] != 'Y')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Put page titles in web addresses.');
	$input .= BR.'<input type="radio" name="with_alternate_urls" value="Y"';
	if($context['with_alternate_urls'] == 'Y')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Use nick names instead.');
	$fields[] = array($label, $input);

	// web security
	$label = i18n::s('Web security');
	$input = '<input type="radio" name="with_https" value="N"';
	if(!isset($context['with_https']) || ($context['with_https'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Accept all web requests.');
	$input .= BR.'<input type="radio" name="with_https" value="Y"';
	if(isset($context['with_https']) && ($context['with_https'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Redirect all non-secured requests to https.')
		.' ('.Skin::build_link(str_replace('http:', 'https:', $context['url_to_home']).$context['url_to_root'].'control/test.php/123/456', i18n::s('test link'), 'open').')';
	$fields[] = array($label, $input);

	// web cache
	$label = i18n::s('Web cache');
	$input = '<input type="radio" name="without_http_cache" value="N"';
	if(!isset($context['without_http_cache']) || ($context['without_http_cache'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Set HTTP headers to enable private caching and to ask for every page revalidation.');
	$input .= BR.'<input type="radio" name="without_http_cache" value="Y"';
	if(isset($context['without_http_cache']) && ($context['without_http_cache'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('No cache management. Default settings of the PHP engine apply.');
	$fields[] = array($label, $input);

	// without Internet visibility
	$label = i18n::s('Internet connectivity');
	$input = '<input type="radio" name="without_internet_visibility" value="N"';
	if(!isset($context['without_internet_visibility']) || ($context['without_internet_visibility'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Server is reachable from public web crawlers and news aggregators.');
	$input .= BR.'<input type="radio" name="without_internet_visibility" value="Y"';
	if(isset($context['without_internet_visibility']) && ($context['without_internet_visibility'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Server is not connected to the Internet.');
	$fields[] = array($label, $input);
        
        // Time Zone
        $label = i18n::s('Time Zone');
        $input = '<input type="radio" name="time_zone" value="surfer"';
	if(!isset($context['time_zone']) || ($context['time_zone'] == 'surfer'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Display time according to the time zone of each surfer.');
        $input .= BR.'<input type="radio" name="time_zone" value="forced"';
	if(isset($context['time_zone']) && ($context['time_zone'] !== 'surfer'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Use the same time zone for everyone setted as this value : ');
        $current_time_zone = (isset($context['time_zone']) && is_numeric($context['time_zone']))?$context['time_zone']:0;
        $input .= tag::_('input','name=time_zone_setted type=numeric min="-12" max=12 value="'.$current_time_zone.'"');
        $fields[] = array($label, $input);

	// without outbound http
	$label = i18n::s('Outbound requests');
	$input = '<input type="radio" name="without_outbound_http" value="N"';
	if(!isset($context['without_outbound_http']) || ($context['without_outbound_http'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('This server uses the web for syndication, for pings or for other activities.');
	$input .= BR.'<input type="radio" name="without_outbound_http" value="Y"';
	if(isset($context['without_outbound_http']) && ($context['without_outbound_http'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Prevent this server to connect to others.');
	$fields[] = array($label, $input);
        
        // subdomain for static subcontent
        $label = i18n::s('static subdomain');
        if(!isset($context['static_subdom']))
		$context['static_subdom'] = '';
	$input = '<input type="text" name="static_subdom" size="45" value="'.encode_field($context['static_subdom']).'" maxlength="255" />';
        $hint = i18n::s('provide full url with trailing slash, e.g. http://static.yourdomain.com/');
	$fields[] = array($label, $input, $hint);

	// build the form
	$http .= Skin::build_form($fields);
	$fields = array();

	// the proxy host
	$label = i18n::s('Proxy address or name');
	if(!isset($context['proxy_server']))
		$context['proxy_server'] = '';
	$input = '<input type="text" name="proxy_server" size="45" value="'.encode_field($context['proxy_server']).'" maxlength="255" />';
	$fields[] = array($label, $input);

	// the proxy user name
	$label = i18n::s('Proxy account');
	if(!isset($context['proxy_user']))
		$context['proxy_user'] = '';
	$input = '<input type="text" name="proxy_user" size="45" value="'.encode_field($context['proxy_user']).'" maxlength="255" />';
	$fields[] = array($label, $input);

	// the proxy password
	$label = i18n::s('Proxy password');
	if(!isset($context['proxy_password']))
		$context['proxy_password'] = '';
	$input = '<input type="password" name="proxy_password" size="45" value="'.encode_field($context['proxy_password']).'" maxlength="255" />';
	$fields[] = array($label, $input);

	// build the form
	$http .= Skin::build_box(i18n::s('Proxy settings'), Skin::build_form($fields), 'folded');
	$fields = array();

	//
	// outbound mail parameters
	//
	$mail = '';

	// splash message
	$mail .= '<p>'.i18n::s('If you do not know what following parameters mean, please use default values for safety.')."</p>\n";

	// with mail
	$label = i18n::s('Global switch');
	$input = '<input type="radio" name="with_email" value="N"';
	if(!isset($context['with_email']) || ($context['with_email'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('This system is not configured to send e-mail messages.');
	$input .= BR.'<input type="radio" name="with_email" value="Y"';
	if(isset($context['with_email']) && ($context['with_email'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Use following parameters to send messages and notifications.');
	$fields[] = array($label, $input);

	// mail variant
	$label = i18n::s('Architecture');
	$input = '<input type="radio" name="mail_variant" value="local"';
	if(!isset($context['mail_variant']) || ($context['mail_variant'] == 'local'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Rely on PHP and system settings for the transmission of messages.');
	$input .= BR.'<input type="radio" name="mail_variant" value="smtp"';
	if(isset($context['mail_variant']) && ($context['mail_variant'] == 'smtp'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Connect to the mail server described below, and authenticate with SMTP if necessary.');
	$input .= BR.'<input type="radio" name="mail_variant" value="pop3"';
	if(isset($context['mail_variant']) && ($context['mail_variant'] == 'pop3'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Authenticate using POP3 before sending messages with SMTP.');
	$fields[] = array($label, $input);

	// smtp server
 	$label = i18n::s('SMTP server');
 	if(!isset($context['mail_server']))
 		$context['mail_server'] = '';
 	$input = '<input type="text" name="mail_server" size="45" value="'.encode_field($context['mail_server']).'" maxlength="255" />';
 	$examples = 'smtp.free.fr';
	if(is_callable('extension_loaded') && extension_loaded('openssl'))
		$examples .= ', ssl:/'.'/smtp.gmail.com:465';
 	$hint = sprintf(i18n::s('For example: %s'), $examples);
 	$fields[] = array($label, $input, $hint);

	// account name
	$label = i18n::s('Account name');
	if(!isset($context['mail_account']))
		$context['mail_account'] = '';
	$input = '<input type="text" name="mail_account" size="45" value="'.encode_field($context['mail_account']).'" maxlength="255" />';
	$fields[] = array($label, $input);

	// account password
	$label = i18n::s('Account password');
	if(!isset($context['mail_password']))
		$context['mail_password'] = '';
	$input = '<input type="password" name="mail_password" size="45" value="'.encode_field($context['mail_password']).'" maxlength="255" />';
	$fields[] = array($label, $input);

	// source address
	$label = i18n::s('Source address for electronic mail (From:)');
	if(!isset($context['mail_from']))
		$context['mail_from'] = '';
	$input = '<input type="text" name="mail_from" size="45" value="'.encode_field($context['mail_from']).'" maxlength="255" />';
	$hint = i18n::s('Normalized format, for example: "John Ford" &lt;john.ford@acme.com&gt;');
	$fields[] = array($label, $input, $hint);

	// use surfer address
	$label = i18n::s('Source address for notifications (From:)');
	$input = '<input type="radio" name="mail_from_surfer" value="N"';
	if(!isset($context['mail_from_surfer']) || ($context['mail_from_surfer'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Use the fixed source address configured above.');
	$input .= BR.'<input type="radio" name="mail_from_surfer" value="Y"';
	if(isset($context['mail_from_surfer']) && ($context['mail_from_surfer'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Make notifications personal by using surfer address if known.');
	$fields[] = array($label, $input);

	// maximum outbound messages per hour
	$label = i18n::s('Maximum of outbound messages per hour');
	if(!isset($context['mail_hourly_maximum']))
		$context['mail_hourly_maximum'] = 50;
	$input = '<input type="text" name="mail_hourly_maximum" size="5" value="'.encode_field($context['mail_hourly_maximum']).'" maxlength="10" />';
	$hint = i18n::s('Messages will not be queued if this is set to 0');
	$fields[] = array($label, $input, $hint);

	// target recipients for logged events
	$label = i18n::s('Recipients of system events');
	if(!isset($context['mail_logger_recipient']))
		$context['mail_logger_recipient'] = '';
	$input = '<input type="text" name="mail_logger_recipient" size="45" value="'.encode_field($context['mail_logger_recipient']).'" maxlength="255" />';
	$hint = i18n::s('To forward logged events to one or several recipients.');
	$fields[] = array($label, $input, $hint);

	// debug mail
	$label = i18n::s('Debug mail services');
	$checked = '';
	if(isset($context['debug_mail']) && ($context['debug_mail'] == 'Y'))
		$checked = ' checked="checked" ';
	$input = '<input type="checkbox" name="debug_mail" value="Y" '.$checked.'/> '.i18n::s('Copy messages in the file temporary/debug.txt. Use this option only for troubleshooting.');
	$fields[] = array($label, $input);

	// build the form
	$mail .= Skin::build_form($fields);
	$fields = array();

	//
	// change the skin
	//
	$skin = '';

	// drive people to the visual index, but not on first install
	if(file_exists($context['path_to_root'].'parameters/control.include.php'))
		$skin .= '<p>'.sprintf(i18n::s('Check %s to manage and preview all available styles.'), Skin::build_link('skins/', i18n::s('the index page of skins')))."</p>\n";

	// list skins available on this system
	if($dir = Safe::opendir("../skins")) {

		// valid skins have a template.php file
		$skins = array();
		while(($file = Safe::readdir($dir)) !== FALSE) {
			if(($file[0] == '.') || !is_dir('../skins/'.$file))
				continue;
			if(!file_exists('../skins/'.$file.'/template.php'))
				continue;

			// set a default skin
			if(!$context['skin'])
				$context['skin'] = 'skins/'.$file;

			$checked = '';
			if($context['skin'] == 'skins/'.$file)
				$checked = ' checked="checked"';
			$skins[] = '<input type="radio" name="skin" value="skins/'.encode_field($file).'"'.$checked.' /> '.$file;

		}
		Safe::closedir($dir);
		if(count($skins)) {
			natsort($skins);
			foreach($skins as $item)
				$skin .= '<p>'.$item.'</p>';
		}
	}

	//
	// assemble all tabs
	//
	$all_tabs = array(
		array('database', i18n::s('Database'), 'database_panel', $database),
		array('system', i18n::s('System'), 'system_panel', $system),
		array('http', i18n::s('Network'), 'http_panel', $http),
		array('mail', i18n::s('Mail'), 'mail_panel', $mail),
		array('skin', i18n::s('Skin'), 'skin_panel', $skin)
		);

	// let YACS do the hard job
	$context['text'] .= Skin::build_tabs($all_tabs);

	//
	// bottom commands
	//
	$menu = array();

	// the submit button
	$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');

	// control panel
	if(file_exists('../parameters/control.include.php'))
		$menu[] = Skin::build_link('control/', i18n::s('Control Panel'), 'span');

	// all skins
	if(file_exists('../parameters/control.include.php'))
		$menu[] = Skin::build_link('skins/', i18n::s('Themes'), 'span');

	// insert the menu in the page
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// end of the form
	$context['text'] .= '</div></form>';

// save updated parameters
} else {

	// there is only one parameter to change
	if(isset($_REQUEST['parameter']) && isset($_REQUEST['value'])) {

		// set it
		$context[ $_REQUEST['parameter'] ] = $_REQUEST['value'];

		// return to the skins index if we are coming from there
		if($_REQUEST['parameter'] == 'skin') {
			$context['followup_label'] = i18n::s('Themes');
			$context['followup_link'] = 'skins/';
		}

		// move the updated configuration to the request
		$_REQUEST = $context;
	}

	// backup the old version
	Safe::unlink($context['path_to_root'].'parameters/control.include.php.bak');
	Safe::rename($context['path_to_root'].'parameters/control.include.php', $context['path_to_root'].'parameters/control.include.php.bak');

	// ensure we have default values
	if(!isset($_REQUEST['debug_mail']))
		$_REQUEST['debug_mail'] = 'N';

	// masks are octal
	if($_REQUEST['directory_mask'] < '0700')
		$_REQUEST['directory_mask'] = '0755';
	if($_REQUEST['file_mask'] < '0600')
		$_REQUEST['file_mask'] = '0644';

	// build the new configuration file
	$content = '<?php'."\n"
		.'// This file has been created by the configuration script control/configure.php'."\n"
		.'// on '.gmdate("F j, Y, g:i a").' GMT, for '.Surfer::get_name().'. Please do not modify it manually.'."\n"
		.'global $context;'."\n";
	if(isset($_REQUEST['main_host']))
		$content .= '$context[\'main_host\']=\''.addcslashes($_REQUEST['main_host'], "\\'")."';\n";
	if(isset($_REQUEST['database_server']))
		$content .= '$context[\'database_server\']=\''.addcslashes($_REQUEST['database_server'], "\\'")."';\n";
	if(isset($_REQUEST['database_user']))
		$content .= '$context[\'database_user\']=\''.addcslashes($_REQUEST['database_user'], "\\'")."';\n";
	if(isset($_REQUEST['database_password']))
		$content .= '$context[\'database_password\']=\''.addcslashes($_REQUEST['database_password'], "\\'")."';\n";
	if(isset($_REQUEST['database']))
		$content .= '$context[\'database\']=\''.addcslashes($_REQUEST['database'], "\\'")."';\n";
	$content .= '$context[\'directory_mask\']='.$_REQUEST['directory_mask'].";\n";
	$content .= '$context[\'file_mask\']='.$_REQUEST['file_mask'].";\n";
	if(isset($_REQUEST['table_prefix']))
		$content .= '$context[\'table_prefix\']=\''.addcslashes($_REQUEST['table_prefix'], "\\'")."';\n";
	if(isset($_REQUEST['users_database_server']))
		$content .= '$context[\'users_database_server\']=\''.addcslashes($_REQUEST['users_database_server'], "\\'")."';\n";
	if(isset($_REQUEST['users_database_user']))
		$content .= '$context[\'users_database_user\']=\''.addcslashes($_REQUEST['users_database_user'], "\\'")."';\n";
	if(isset($_REQUEST['users_database_password']))
		$content .= '$context[\'users_database_password\']=\''.addcslashes($_REQUEST['users_database_password'], "\\'")."';\n";
	if(isset($_REQUEST['users_database']))
		$content .= '$context[\'users_database\']=\''.addcslashes($_REQUEST['users_database'], "\\'")."';\n";
	if(isset($_REQUEST['users_table_prefix']))
		$content .= '$context[\'users_table_prefix\']=\''.addcslashes($_REQUEST['users_table_prefix'], "\\'")."';\n";
	if(isset($_REQUEST['mail_server']))
		$content .= '$context[\'mail_server\']=\''.addcslashes($_REQUEST['mail_server'], "\\'")."';\n";
	if(isset($_REQUEST['mail_from']))
		$content .= '$context[\'mail_from\']=\''.addcslashes($_REQUEST['mail_from'], "\\'")."';\n";
	if(isset($_REQUEST['mail_from_surfer']))
		$content .= '$context[\'mail_from_surfer\']=\''.addcslashes($_REQUEST['mail_from_surfer'], "\\'")."';\n";
	if(isset($_REQUEST['mail_hourly_maximum']))
		$content .= '$context[\'mail_hourly_maximum\']='.intval($_REQUEST['mail_hourly_maximum']).";\n";
	if(isset($_REQUEST['mail_logger_recipient']))
		$content .= '$context[\'mail_logger_recipient\']=\''.addcslashes($_REQUEST['mail_logger_recipient'], "\\'")."';\n";
	if(isset($_REQUEST['mail_account']))
		$content .= '$context[\'mail_account\']=\''.addcslashes($_REQUEST['mail_account'], "\\'")."';\n";
	if(isset($_REQUEST['mail_password']))
		$content .= '$context[\'mail_password\']=\''.addcslashes($_REQUEST['mail_password'], "\\'")."';\n";
	if(isset($_REQUEST['mail_variant']))
		$content .= '$context[\'mail_variant\']=\''.addcslashes($_REQUEST['mail_variant'], "\\'")."';\n";
	if(isset($_REQUEST['proxy_server']))
		$content .= '$context[\'proxy_server\']=\''.addcslashes($_REQUEST['proxy_server'], "\\'")."';\n";
	if(isset($_REQUEST['proxy_user']))
		$content .= '$context[\'proxy_user\']=\''.addcslashes($_REQUEST['proxy_user'], "\\'")."';\n";
	if(isset($_REQUEST['proxy_password']))
		$content .= '$context[\'proxy_password\']=\''.addcslashes($_REQUEST['proxy_password'], "\\'")."';\n";
	if(isset($_REQUEST['debug_mail']))
		$content .= '$context[\'debug_mail\']=\''.addcslashes($_REQUEST['debug_mail'], "\\'")."';\n";
	if(isset($_REQUEST['preferred_language']))
		$content .= '$context[\'preferred_language\']=\''.addcslashes($_REQUEST['preferred_language'], "\\'")."';\n";
	if(isset($_REQUEST['skin']))
		$content .= '$context[\'skin\']=\''.addcslashes($_REQUEST['skin'], "\\'")."';\n";
	if(isset($_REQUEST['url_to_root_parameter']))
		$content .= '$context[\'url_to_root\']=\''.addcslashes($_REQUEST['url_to_root_parameter'], "\\'")."';\n";
	if(isset($_REQUEST['with_alternate_urls']))
		$content .= '$context[\'with_alternate_urls\']=\''.addcslashes($_REQUEST['with_alternate_urls'], "\\'")."';\n";
	if(isset($_REQUEST['with_compression']))
		$content .= '$context[\'with_compression\']=\''.addcslashes($_REQUEST['with_compression'], "\\'")."';\n";
	if(isset($_REQUEST['with_cron']))
		$content .= '$context[\'with_cron\']=\''.addcslashes($_REQUEST['with_cron'], "\\'")."';\n";
	if(isset($_REQUEST['with_debug']))
		$content .= '$context[\'with_debug\']=\''.addcslashes($_REQUEST['with_debug'], "\\'")."';\n";
	if(isset($_REQUEST['with_email']))
		$content .= '$context[\'with_email\']=\''.addcslashes($_REQUEST['with_email'], "\\'")."';\n";
	if(isset($_REQUEST['with_friendly_urls']))
		$content .= '$context[\'with_friendly_urls\']=\''.addcslashes($_REQUEST['with_friendly_urls'], "\\'")."';\n";
	if(isset($_REQUEST['with_given_host']))
		$content .= '$context[\'with_given_host\']=\''.addcslashes($_REQUEST['with_given_host'], "\\'")."';\n";
	if(isset($_REQUEST['with_https']))
		$content .= '$context[\'with_https\']=\''.addcslashes($_REQUEST['with_https'], "\\'")."';\n";
	if(isset($_REQUEST['without_cache']))
		$content .= '$context[\'without_cache\']=\''.addcslashes($_REQUEST['without_cache'], "\\'")."';\n";
	if(isset($_REQUEST['without_http_cache']))
		$content .= '$context[\'without_http_cache\']=\''.addcslashes($_REQUEST['without_http_cache'], "\\'")."';\n";
        if(isset($_REQUEST['time_zone'])) {
            $time_zone = ($_REQUEST['time_zone'] == 'forced')?$_REQUEST['time_zone_setted']:0;
            $time_zone = ($_REQUEST['time_zone'] == 'surfer')?'surfer':$time_zone;
            
            $content .= '$context[\'time_zone\']=\''.addcslashes($time_zone, "\\'")."';\n";
        }
	if(isset($_REQUEST['without_internet_visibility']))
		$content .= '$context[\'without_internet_visibility\']=\''.addcslashes($_REQUEST['without_internet_visibility'], "\\'")."';\n";
	if(isset($_REQUEST['without_language_detection']))
		$content .= '$context[\'without_language_detection\']=\''.addcslashes($_REQUEST['without_language_detection'], "\\'")."';\n";
	if(isset($_REQUEST['without_outbound_http']))
		$content .= '$context[\'without_outbound_http\']=\''.addcslashes($_REQUEST['without_outbound_http'], "\\'")."';\n";
        if(isset($_REQUEST['static_subdom']))
		$content .= '$context[\'static_subdom\']=\''.addcslashes($_REQUEST['static_subdom'], "\\'")."';\n";
	$content .= '?>'."\n";

	// silently attempt to create the database if it does not exist
	$query = 'CREATE DATABASE IF NOT EXISTS '.SQL::escape($_REQUEST['database']);
	SQL::query($query, TRUE);

	// alert the end user if we are not able to connect to the database
	if(!$handle = SQL::connect($_REQUEST['database_server'], $_REQUEST['database_user'], $_REQUEST['database_password'], $_REQUEST['database'])) {

		Logger::error(i18n::s('ERROR: Unsuccessful connection to the database. Please check lines below and <a href="configure.php">configure again</a>.'));

	// update the parameters file
	} elseif(!Safe::file_put_contents('parameters/control.include.php', $content)) {

		Logger::error(sprintf(i18n::s('ERROR: Impossible to write to the file %s. The configuration has not been saved.'), 'parameters/control.include.php'));

		// allow for a manual update
		$context['text'] .= '<p style="text-decoration: blink;">'.sprintf(i18n::s('To actually change the configuration, please copy and paste following lines by yourself in file %s.'), 'parameters/control.include.php')."</p>\n";

	// job done
	} else {

		$context['text'] .= '<p>'.sprintf(i18n::s('The following configuration has been saved into the file %s.'), 'parameters/control.include.php')."</p>\n";

		// first installation
		if(!file_exists('../parameters/switch.on') && !file_exists('../parameters/switch.off'))
			$context['text'] .= '<p>'.i18n::s('Review provided information and go to the bottom of the page to move forward.')."</a></p>\n";

		// purge the cache
		Cache::clear();

		// also purge session cache for this surfer
		unset($_SESSION['l10n_modules']);

		// remember the change
		$label = sprintf(i18n::c('%s has been updated'), 'parameters/control.include.php');
		Logger::remember('control/configure.php: '.$label);

	}

	// display updated parameters
	if(is_callable(array('skin', 'build_box')))
		$context['text'] .= Skin::build_box(i18n::s('Configuration'), Safe::highlight_string($content), 'folded');
	else
		$context['text'] .= Safe::highlight_string($content);

	// first installation
	if(!file_exists('../parameters/switch.on') && !file_exists('../parameters/switch.off')) {

		// some css files may have to be slightly updated
		if(($context['url_to_root_parameter'] != '/yacs/') && ($skins = Safe::opendir('../skins'))) {

			// valid skins have a template.php file
			while(($skin = Safe::readdir($skins)) !== FALSE) {
				if(($skin[0] == '.') || !is_dir('../skins/'.$skin))
					continue;
				if(!file_exists('../skins/'.$skin.'/template.php'))
					continue;

				// look for css files
				if($files = Safe::opendir('../skins/'.$skin)) {
					while(($file = Safe::readdir($files)) !== FALSE) {
						if(!preg_match('/\.css$/i', $file))
							continue;

						// change this css file
						if($content = Safe::file_get_contents('../skins/'.$skin.'/'.$file)) {
							$content = str_replace('/yacs/', $context['url_to_root_parameter'], $content);
							Safe::file_put_contents('skins/'.$skin.'/'.$file, $content);
						}
					}
					Safe::closedir($files);
				}
			}
			Safe::closedir($skins);
		}

		// look for software extensions
		$context['text'] .= Skin::build_block('<form method="get" action="scan.php" id="main_form">'."\n"
			.'<p class="assistant_bar">'.Skin::build_submit_button(i18n::s('Look for software extensions'), NULL, 's', 'confirmed').'</p>'."\n"
			.'</form>', 'bottom');

		// this may take several minutes
		$context['text'] .= '<p>'.i18n::s('When you will click on the button the server will be immediately requested to proceed. However, because of the so many things to do on the back-end, you may have to wait for minutes before getting a response displayed. Thank you for your patience.')."</p>\n";

	// the followup link, if any
	} elseif(isset($context['followup_link'])) {

		// ensure we have a label
		if(!isset($context['followup_label']))
			$context['followup_label'] = i18n::s('Next step');

		$context['text'] .= Skin::build_block('<form method="get" action="'.$context['url_to_root'].$context['followup_link'].'" id="main_form">'."\n"
			.'<p class="assistant_bar">'.Skin::build_submit_button($context['followup_label']).'</p>'."\n"
			.'</form>', 'bottom');

	// ordinary follow-up commands
	} else {

		// follow-up commands
		$follow_up = i18n::s('Where do you want to go now?');
		$menu = array();
		$menu = array_merge($menu, array( 'control/' => i18n::s('Control Panel') ));
		$menu = array_merge($menu, array( 'control/configure.php' => i18n::s('Configure again') ));
		$follow_up .= Skin::build_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	}
}

// render the skin
render_skin();

?>
