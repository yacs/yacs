<?php
/**
 * change parameters for letters
 *
 * Use this script to modify following parameters:
 *
 * - [code]letter_title[/code] - the general title used for the message sent
 *
 * - [code]letter_prefix[/code] - text inserted before letter content
 *
 * - [code]letter_suffix[/code] - text appended after letter content
 *
 * - [code]title_prefix[/code] - text inserted before each item title
 *
 * - [code]title_suffix[/code] - text appended after each item title
 *
 * - [code]letter_reply_to[/code] - the e-mail address to be used for messages sent in reply
 *
 * Configuration information is saved into [code]parameters/letters.include.php[/code].
 * If YACS is prevented to write to the file, it displays parameters to allow for a manual update.
 *
 * The file [code]parameters/letters.include.php.bak[/code] can be used to restore
 * the active configuration before the last change.
 *
 * If the file [code]demo.flag[/code] exists, the script assumes that this instance
 * of YACS runs in demonstration mode.
 * In this mode the edit form is displayed, but parameters are not saved in the configuration file.
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// load localized strings
i18n::bind('letters');

// load the skin
load_skin('letters');

// the path to this page
$context['path_bar'] = array( 'control/' => i18n::s('Control Panel') );

// the title of the page
$context['page_title'] = sprintf(i18n::s('Configure: %s'), i18n::s('Newsletters'));

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('letters/configure.php'));

// only associates can proceed
elseif(!Surfer::is_associate()) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// display the input form
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'POST')) {

	// load current parameters, if any
	Safe::load('parameters/letters.include.php');

	// the form
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><div>';

	// letter title
	if(!isset($context['letter_title']) || !$context['letter_title'])
		$context['letter_title'] = $context['site_name'];
	$label = i18n::s('Letter title');
	$input = '<input type="text" name="letter_title" id="letter_title" size="50" value="'.encode_field(isset($context['letter_title']) ? $context['letter_title'] : '').'" maxlength="255" />';
	$hint = i18n::s('Used as e-mail subject');
	$fields[] = array($label, $input, $hint);

	// letter prefix
	$label = i18n::s('Letter prefix');
	$input = '<textarea name="letter_prefix" cols="40" rows="10">'.encode_field(isset($context['letter_prefix']) ? $context['letter_prefix'] : '').'</textarea>';
	$hint = i18n::s('Several lines of text to introduce your letter');
	$fields[] = array($label, $input, $hint);

	// letter suffix
	$label = i18n::s('Letter suffix');
	$input = '<textarea name="letter_suffix" cols="40" rows="10">'.encode_field(isset($context['letter_suffix']) ? $context['letter_suffix'] : '').'</textarea>';
	$hint = i18n::s('Several lines of text to end your letter');
	$fields[] = array($label, $input, $hint);

	// title prefix
	$label = i18n::s('Title prefix');
	$input = '<input type="text" name="title_prefix" size="30" value="'.encode_field(isset($context['title_prefix']) ? $context['title_prefix'] : '').'" maxlength="255" />';
	$hint = i18n::s('Inserted before each title while building the list of recent articles');
	$fields[] = array($label, $input, $hint);

	// title suffix
	$label = i18n::s('Title suffix');
	$input = '<input type="text" name="title_suffix" size="30" value="'.encode_field(isset($context['title_suffix']) ? $context['title_suffix'] : '').'" maxlength="255" />';
	$hint = i18n::s('Inserted after each title while building the list of recent articles');
	$fields[] = array($label, $input, $hint);

	// letter reply-to address
	if(!isset($context['letter_reply_to']) || !$context['letter_reply_to'])
		$context['letter_reply_to'] = $context['site_email'];
	$label = i18n::s('Mail address to be used on replies');
	$input = '<input type="text" name="letter_reply_to" size="50" value="'.encode_field($context['letter_reply_to']).'" maxlength="255" />';
	$hint = i18n::s('To let people react to your posts');
	$fields[] = array($label, $input, $hint);

	// build the form
	$context['text'] .= Skin::build_form($fields);

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
		$menu[] = Skin::build_link('letters/', i18n::s('Newsletters'), 'span');

	// insert the menu in the page
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// end of the form
	$context['text'] .= '</div></form>';

	// set the focus
	$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
		.'// set the focus on first form field'."\n"
		.'document.getElementById("letter_title").focus();'."\n"
		.'// ]]></script>'."\n";

// no modifications in demo mode
} elseif(file_exists($context['path_to_root'].'parameters/demo.flag')) {

	// remind the surfer
	$context['text'] .= '<p>'.i18n::s('This instance of YACS runs in demonstration mode. For security reasons configuration parameters cannot be changed in this mode.').'</p>';

// save updated parameters
} else {

	// backup the old version
	Safe::unlink($context['path_to_root'].'parameters/letters.include.php.bak');
	Safe::rename($context['path_to_root'].'parameters/letters.include.php', $context['path_to_root'].'parameters/letters.include.php.bak');

	// build the new configuration file
	$content = '<?php'."\n"
		.'// This file has been created by the configuration script letters/configure.php'."\n"
		.'// on '.gmdate("F j, Y, g:i a").' GMT, for '.Surfer::get_name().'. Please do not modify it manually.'."\n"
		.'global $context;'."\n";
	if(isset($_REQUEST['letter_title']))
		$content .= '$context[\'letter_title\']=\''.addcslashes($_REQUEST['letter_title'], "\\'")."';\n";
	if(isset($_REQUEST['letter_prefix']))
		$content .= '$context[\'letter_prefix\']=\''.addcslashes($_REQUEST['letter_prefix'], "\\'")."';\n";
	if(isset($_REQUEST['letter_suffix']))
		$content .= '$context[\'letter_suffix\']=\''.addcslashes($_REQUEST['letter_suffix'], "\\'")."';\n";
	if(isset($_REQUEST['title_prefix']))
		$content .= '$context[\'title_prefix\']=\''.addcslashes($_REQUEST['title_prefix'], "\\'")."';\n";
	if(isset($_REQUEST['title_suffix']))
		$content .= '$context[\'title_suffix\']=\''.addcslashes($_REQUEST['title_suffix'], "\\'")."';\n";
	if(isset($_REQUEST['letter_reply_to']))
		$content .= '$context[\'letter_reply_to\']=\''.addcslashes($_REQUEST['letter_reply_to'], "\\'")."';\n";
	$content .= '?>'."\n";

	// update the parameters file
	if(!Safe::file_put_contents('parameters/letters.include.php', $content)) {

		Skin::error(sprintf(i18n::s('ERROR: Impossible to write to the file %s. The configuration has not been saved.'), 'parameters/letters.include.php'));

		// allow for a manual update
		$context['text'] .= '<p style="text-decoration: blink;">'.sprintf(i18n::s('To actually change the configuration, please copy and paste following lines by yourself in file %s.'), 'parameters/letters.include.php')."</p>\n";

	// job done
	} else {

		$context['text'] .= '<p>'.sprintf(i18n::s('The following configuration has been saved into the file %s.'), 'parameters/letters.include.php')."</p>\n";

		// purge the cache
		Cache::clear('articles');

		// remember the change
		$label = sprintf(i18n::c('%s has been updated'), 'parameters/letters.include.php');
		Logger::remember('letters/configure.php', $label);

	}

	// display updated parameters
	$context['text'] .= Skin::build_box(i18n::s('Configuration parameters'), Safe::highlight_string($content), 'folder');

	// what's next?
	$context['text'] .= '<p>'.i18n::s('Where do you want to go now?')."</p>\n";

	// follow-up commands
	$menu = array();

	// index page
	$menu = array_merge($menu, array( 'letters/' => i18n::s('Newsletters') ));

	// control panel
	$menu = array_merge($menu, array( 'control/' => i18n::s('Control Panel') ));

	// do it again
	$menu = array_merge($menu, array( 'letters/configure.php' => i18n::s('Configure again') ));

	// display follow-up commands
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

}

// render the skin
render_skin();

?>