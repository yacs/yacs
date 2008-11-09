<?php
/**
 * change parameters for feeds
 *
 * This script will let you modify information spread along outbound channels, such as:
 *
 * - [code]channel_title[/code] - The name of your site, as it will appears in newsfeeds.
 * The default value is the site name, as set in skins/configure.php.
 *
 * - [code]channel_description[/code] - Up to two lines of text used to describe your site as a news provider.
 * The default value is the site description, as set in skins/configure.php.
 *
 * - [code]webmaster_address[/code] - Appears in news feeds, and therefore can be subject to spam attacks.
 * The default value is the web master e-mail address set in skins/configure.php.
 *
 * - [code]powered_by_image[/code] - The URL of the channel image, relative to the YACS installation path, if any
 *
 * - [code]time_to_live[/code] - Suggested period of refresh
 *
 * @see skins/configure.php
 *
 * General outbound feeds are described at [script]feeds/index.php[/script].
 * More specific feeds are available at [script]sections/feed.php[/script],
 * [script]categories/feed.php[/script], and [script]users/feed.php[/script].
 * You can also build a feed on particular keywords, at [script]services/search.php[/script].
 *
 *
 * Use this script also to modify parameters related to news aggregation, such as:
 *
 * - [code]minutes between feeds[/code] - the feeding period.
 *
 * - [code]maximum_news[/code] - the maximum number of news links to be kept in the database.
 * If the limit is reached, oldest entries will be deleted.
 *
 * - [code]debug_feeds[/code] - if set to '[code]Y[/code]',
 * save into [code]temporary/debug.txt[/code] data sent and received during feeding session.
 * Quite useful to understand why the last URL added to your system screw up everything...
 *
 * To declare inbound feeds you will have to create server profiles.
 * See [script]servers/index.php[/script].
 *
 * @see servers/index.php
 *
 * Configuration information is saved into [code]parameters/feeds.include.php[/code].
 * If YACS is prevented to write to the file, it displays parameters to allow for a manual update.
 *
 * The file [code]parameters/feeds.include.php.bak[/code] can be used to restore
 * the active configuration before the last change, if necessary.
 *
 * If the file [code]demo.flag[/code] exists, the script assumes that this instance
 * of YACS runs in demonstration mode.
 * In this mode the edit form is displayed, but parameters are not saved in the configuration file.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'feeds.php';

// load the skin
load_skin('feeds');

// the path to this page
$context['path_bar'] = array( 'control/' => i18n::s('Control Panel') );

// the title of the page
$context['page_title'] = sprintf(i18n::s('%s: %s'), i18n::s('Configure'), i18n::s('Information channels'));

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('feeds/configure.php'));

// only associates can proceed
elseif(!Surfer::is_associate()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// display the input form
} elseif($_SERVER['REQUEST_METHOD'] != 'POST') {

	// load current parameters, if any
	Safe::load('parameters/feeds.include.php');

	// the form
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><div>';

	// outbound feeding
	//
	$outbound = '';

	// the splash message
	$outbound .= '<p>'.i18n::s('Type below information that will be spread everywhere through news feeding.')."</p>\n";

	// channel title
	if(!isset($context['channel_title']) || !$context['channel_title'])
		$context['channel_title'] = $context['site_name'];
	$label = i18n::s('Channel title');
	$input = '<input type="text" name="channel_title" id="channel_title" size="40" value="'.encode_field($context['channel_title']).'" maxlength="255" />';
	$hint = i18n::s('Short and meaningful label');
	$fields[] = array($label, $input, $hint);

	// channel description
	if(!isset($context['channel_description']) || !$context['channel_description'])
		$context['channel_description'] = $context['site_description'];
	$label = i18n::s('Channel description');
	$input = '<textarea name="channel_description" cols="40" rows="2">'.encode_field($context['channel_description']).'</textarea>';
	$hint = i18n::s('Up to two lines of text');
	$fields[] = array($label, $input, $hint);

	// webmaster address
	if(!isset($context['webmaster_address']) || !$context['webmaster_address'])
		$context['webmaster_address'] = $context['site_email'];
	$label = i18n::s('Webmaster mail address');
	$input = '<input type="text" name="webmaster_address" size="40" value="'.encode_field($context['webmaster_address']).'" maxlength="255" />';
	$hint = i18n::s('May be subject to spam attacks');
	$fields[] = array($label, $input, $hint);

	// 'powered by' image
	if(!isset($context['powered_by_image']) || !$context['powered_by_image']) {
		if(is_readable($context['path_to_root'].$context['skin'].'/icons/feed.gif'))
			$context['powered_by_image'] = $context['skin'].'/icons/feed.gif';
		else
			$context['powered_by_image'] = '';
	}
	$label = i18n::s('Channel image');
	$input = '<input type="text" name="powered_by_image" size="40" value="'.encode_field($context['powered_by_image']).'" maxlength="255" />';
	$hint = i18n::s('Relative to YACS installation path');
	$fields[] = array($label, $input, $hint);

	// time to live
	if(!isset($context['time_to_live']) || !$context['time_to_live'])
		$context['time_to_live'] = 59;
	$label = i18n::s('Time to live');
	$input = '<input type="text" name="time_to_live" size="4" value="'.encode_field($context['time_to_live']).'" maxlength="5" /> min.';
	$hint = i18n::s('The suggested amount of time before fetching a feed again');
	$fields[] = array($label, $input, $hint);

	// build the form
	$outbound .= Skin::build_form($fields);
	$fields = array();

	// inbound feeding
	//
	$inbound = '';

	// the splash message
	$inbound .= '<p>'.sprintf(i18n::s('To extend the list of feeders add adequate %s.'), Skin::build_link('servers/', i18n::s('server profiles'), 'shortcut'))."</p>\n";

	// feeding period
	if(!isset($context['minutes_between_feeds']) || !$context['minutes_between_feeds'])
		$context['minutes_between_feeds'] = 60;
	$label = i18n::s('Feeding period');
	$input = '<input type="text" name="minutes_between_feeds" size="10" value="'.encode_field($context['minutes_between_feeds']).'" maxlength="5" />';
	$hint = i18n::s('In minutes. 60 means one hour, etc. Minimum value is 5 minutes');
	$fields[] = array($label, $input, $hint);

	// maximum_news
	if(!isset($context['maximum_news']) || !$context['maximum_news'])
		$context['maximum_news'] = 1000;
	$label = i18n::s('Maximum news');
	$input = '<input type="text" name="maximum_news" size="10" value="'.encode_field($context['maximum_news']).'" maxlength="5" />';
	$hint = i18n::s('Oldest news entries will be deleted');
	$fields[] = array($label, $input, $hint);

	// debug_feeds
	$label = i18n::s('Debug feeds');
	$checked = '';
	if(isset($context['debug_feeds']) && ($context['debug_feeds'] == 'Y'))
		$checked = 'checked="checked" ';
	$input = '<input type="checkbox" name="debug_feeds" value="Y" '.$checked.'/> '.i18n::s('Save data sent and received in temporary/debug.txt');
	$hint = i18n::s('Use this option only for troubleshooting');
	$fields[] = array($label, $input, $hint);

	// build the form
	$inbound .= Skin::build_form($fields);
	$fields = array();

	//
	// assemble all tabs
	//
	$all_tabs = array(
		array('outbound_tab', i18n::s('Outbound'), 'outbound_panel', $outbound),
		array('inbound_tab', i18n::s('Inbound'), 'inbound_panel', $inbound)
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
		$menu[] = Skin::build_link('feeds/', i18n::s('Information channels'), 'span');

	// insert the menu in the page
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// end of the form
	$context['text'] .= '</div></form>';

	// set the focus
	$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
		.'// set the focus on first form field'."\n"
		.'$("channel_title").focus();'."\n"
		.'// ]]></script>'."\n";

	// general help on this form
	$help = '<p>'.sprintf(i18n::s('To ban some hosts or domains, go to the %s.'), Skin::build_link('servers/configure.php', i18n::s('configuration panel for servers'), 'shortcut')).'</p>';
	$context['aside']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'navigation', 'help');

// no modifications in demo mode
} elseif(file_exists($context['path_to_root'].'parameters/demo.flag')) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation in demonstration mode.'));

// save updated parameters
} else {

	// backup the old version
	Safe::unlink($context['path_to_root'].'parameters/feeds.include.php.bak');
	Safe::rename($context['path_to_root'].'parameters/feeds.include.php', $context['path_to_root'].'parameters/feeds.include.php.bak');

	// minimum values
	if($_REQUEST['minutes_between_feeds'] < 5)
		$_REQUEST['minutes_between_feeds'] = 5;

	// build the new configuration file
	$content = '<?php'."\n"
		.'// This file has been created by the configuration script feeds/configure.php'."\n"
		.'// on '.gmdate("F j, Y, g:i a").' GMT, for '.Surfer::get_name().'. Please do not modify it manually.'."\n";
	if(isset($_REQUEST['debug_feeds']))
		$content .= '$context[\'debug_feeds\']=\''.addcslashes($_REQUEST['debug_feeds'], "\\'")."';\n";
	if(isset($_REQUEST['maximum_news']))
		$content .= '$context[\'maximum_news\']=\''.addcslashes($_REQUEST['maximum_news'], "\\'")."';\n";
	if(isset($_REQUEST['minutes_between_feeds']))
		$content .= '$context[\'minutes_between_feeds\']=\''.addcslashes($_REQUEST['minutes_between_feeds'], "\\'")."';\n";
	if(isset($_REQUEST['powered_by_image']))
		$content .= '$context[\'powered_by_image\']=\''.addcslashes($_REQUEST['powered_by_image'], "\\'")."';\n";
	if(isset($_REQUEST['channel_title']))
		$content .= '$context[\'channel_title\']=\''.addcslashes($_REQUEST['channel_title'], "\\'")."';\n";
	if(isset($_REQUEST['channel_description']))
		$content .= '$context[\'channel_description\']=\''.addcslashes($_REQUEST['channel_description'], "\\'")."';\n";
	if(isset($_REQUEST['time_to_live']))
		$content .= '$context[\'time_to_live\']=\''.addcslashes($_REQUEST['time_to_live'], "\\'")."';\n";
	if(isset($_REQUEST['webmaster_address']))
		$content .= '$context[\'webmaster_address\']=\''.addcslashes($_REQUEST['webmaster_address'], "\\'")."';\n";
	$content .= '?>'."\n";

	// update the parameters file
	if(!Safe::file_put_contents('parameters/feeds.include.php', $content)) {

		Logger::error(sprintf(i18n::s('ERROR: Impossible to write to the file %s. The configuration has not been saved.'), 'parameters/feeds.include.php'));

		// allow for a manual update
		$context['text'] .= '<p style="text-decoration: blink;">'.sprintf(i18n::s('To actually change the configuration, please copy and paste following lines by yourself in file %s.'), 'parameters/feeds.include.php')."</p>\n";

	// job done
	} else {

		$context['text'] .= '<p>'.sprintf(i18n::s('The following configuration has been saved into the file %s.'), 'parameters/feeds.include.php')."</p>\n";

		// purge the cache
		Cache::clear('feeds');

		// remember the change
		$label = sprintf(i18n::c('%s has been updated'), 'parameters/feeds.include.php');
		Logger::remember('feeds/configure.php', $label);

	}

	// display updated parameters
	$context['text'] .= Skin::build_box(i18n::s('Configuration parameters'), Safe::highlight_string($content), 'folder');

	// follow-up commands
	$follow_up = i18n::s('Where do you want to go now?');
	$menu = array();
	$menu = array_merge($menu, array( 'feeds/' => i18n::s('Information channels') ));
	$menu = array_merge($menu, array( 'control/' => i18n::s('Control Panel') ));
	$menu = array_merge($menu, array( 'feeds/configure.php' => i18n::s('Configure again') ));
	$follow_up .= Skin::build_list($menu, 'menu_bar');
	$context['text'] .= Skin::build_block($follow_up, 'bottom');

}

// render the skin
render_skin();

?>