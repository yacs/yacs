<?php
/**
 * change parameters for flash
 *
 * This script will let you change some rendering options for the flash object dynamically built by YACS, such as:
 *
 * - [code]flash_font_r[/code], [code]flash_font_g[/code] and [code]flash_font_b[/code] - The font color.
 * The default value is '[code]0x11[/code]', '[code]0x33[/code]' and '[code]0x33[/code]' respectively.
 *
 * - [code]flash_background_r[/code], [code]flash_background_g[/code] and [code]flash_background_b[/code] - The background color.
 * The default value is to have a transparent object, meaning you will the web page background through the Flash object.
 *
 * - [code]flash_font[/code] - The font to be used to draw titles.
 * The default value is '[code]Bimini.fdb[/code]', but this can be changed once you will have uploaded the adequate font file.
 *
 * - [code]flash_font_height[/code] - The size of the font.
 * The default value is '[code]40[/code]'.
 *
 * - [code]flash_width[/code] - The internal horizontal scale.
 * The default value is '[code]500[/code]'.
 *
 * - [code]flash_height[/code] - The internal vertical scale.
 * The default value is '[code]50[/code]'.
 *
 * @see feeds/flash/slashdot.php
 *
 * Configuration information is saved into [code]parameters/feeds.flash.include.php[/code].
 * If YACS is prevented to write to the file, it displays parameters to allow for a manual update.
 *
 * The file [code]parameters/feeds.flash.include.php.bak[/code] can be used to restore
 * the active configuration before the last change, if necessary.
 *
 * If the file [code]parameters/demo.flag[/code] exists, the script assumes that this instance
 * of YACS runs in demonstration mode.
 * In this mode the edit form is displayed, but parameters are not saved in the configuration file.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../../shared/global.php';

// load localized strings
i18n::bind('feeds');

// load the skin
load_skin('feeds');

// the path to this page
$context['path_bar'] = array( 'control/' => i18n::s('Control Panel') );

// the title of the page
$context['page_title'] = sprintf(i18n::s('%s: %s'), i18n::s('Configure'), i18n::s('Flash'));

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('feeds/flash/configure.php'));

// only associates can proceed
elseif(!Surfer::is_associate()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// display the input form
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'POST')) {

	// load current parameters, if any
	Safe::load('parameters/feeds.flash.include.php');

	// the form
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><div>';

	// slashdot font r
	$label = i18n::s('Font Red');
	$input = '<input type="text" name="flash_font_r" id="flash_font_r" size="8" value="'.encode_field(isset($context['flash_font_r']) ? $context['flash_font_r'] : 0).'" maxlength="255" />';
	$hint = i18n::s('An integer value, in decimal or hexadecimal, eg, 0x11');
	$fields[] = array($label, $input, $hint);

	// slashdot font g
	$label = i18n::s('Font Green');
	$input = '<input type="text" name="flash_font_g" size="8" value="'.encode_field(isset($context['flash_font_g']) ? $context['flash_font_g'] : 0).'" maxlength="255" />';
	$hint = i18n::s('An integer value, in decimal or hexadecimal, eg, 0x11');
	$fields[] = array($label, $input, $hint);

	// slashdot font b
	$label = i18n::s('Font Blue');
	$input = '<input type="text" name="flash_font_b" size="8" value="'.encode_field(isset($context['flash_font_b']) ? $context['flash_font_b'] : 0).'" maxlength="255" />';
	$hint = i18n::s('An integer value, in decimal or hexadecimal, eg, 0x11');
	$fields[] = array($label, $input, $hint);

	// slashdot background r
	$label = i18n::s('Background Red');
	$input = '<input type="text" name="flash_background_r" size="8" value="'.encode_field(isset($context['flash_background_r']) ? $context['flash_background_r'] : 0).'" maxlength="255" />';
	$hint = i18n::s('An integer value, in decimal or hexadecimal, eg, 0x11');
	$fields[] = array($label, $input, $hint);

	// slashdot background g
	$label = i18n::s('Background Green');
	$input = '<input type="text" name="flash_background_g" size="8" value="'.encode_field(isset($context['flash_background_g']) ? $context['flash_background_g'] : 0).'" maxlength="255" />';
	$hint = i18n::s('An integer value, in decimal or hexadecimal, eg, 0x11');
	$fields[] = array($label, $input, $hint);

	// slashdot background b
	$label = i18n::s('Background Blue');
	$input = '<input type="text" name="flash_background_b" size="8" value="'.encode_field(isset($context['flash_background_b']) ? $context['flash_background_b'] : 0).'" maxlength="255" />';
	$hint = i18n::s('An integer value, in decimal or hexadecimal, eg, 0x11');
	$fields[] = array($label, $input, $hint);

	// slashdot font
	if(!isset($context['flash_font']))
		$context['flash_font'] = 'Bimini.fdb';
	$label = i18n::s('Font file');
	$input = '<input type="text" name="flash_font" size="40" value="'.encode_field($context['flash_font']).'" maxlength="255" />';
	$hint = i18n::s('Ensure the file already exists and is a valid font description');
	$fields[] = array($label, $input, $hint);

	// slashdot font height
	if(!isset($context['flash_font_height']))
		$context['flash_font_height'] = 40;
	$label = i18n::s('Font height');
	$input = '<input type="text" name="flash_font_height" size="8" value="'.encode_field(isset($context['flash_font_height']) ? $context['flash_font_height'] : 0).'" maxlength="255" />';
	$hint = i18n::s('Compare to the internal vertical scale below');
	$fields[] = array($label, $input, $hint);

	// slashdot width
	if(!isset($context['flash_width']))
		$context['flash_width'] = 500;
	$label = i18n::s('Object width');
	$input = '<input type="text" name="flash_width" size="8" value="'.encode_field(isset($context['flash_width']) ? $context['flash_width'] : 0).'" maxlength="255" />';
	$hint = i18n::s('Internal horizontal scale');
	$fields[] = array($label, $input, $hint);

	// slashdot height
	if(!isset($context['flash_height']))
		$context['flash_height'] = 50;
	$label = i18n::s('Object height');
	$input = '<input type="text" name="flash_height" size="8" value="'.encode_field(isset($context['flash_height']) ? $context['flash_height'] : 0).'" maxlength="255" />';
	$hint = i18n::s('Internal vertical scale');
	$fields[] = array($label, $input, $hint);

	// build the form
	$context['text'] .= Skin::build_form($fields);
	$fields = array();

	//
	// bottom commands
	//
	$menu = array();

	// the submit button
	$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');

	// control panel
	if(file_exists('../../parameters/control.include.php'))
		$menu[] = Skin::build_link('control/', i18n::s('Control Panel'), 'span');

	// front page
	if(file_exists('../../parameters/control.include.php'))
		$menu[] = Skin::build_link($context['url_to_root'], i18n::s('Front page'), 'span');

	// index page
	if(file_exists('../../parameters/control.include.php'))
		$menu[] = Skin::build_link('feeds/', i18n::s('Information channels'), 'span');

	// insert the menu in the page
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// end of the form
	$context['text'] .= '</div></form>';

	// set the focus
	$context['text'] .= JS_PREFIX
		.'// set the focus on first form field'."\n"
		.'$("flash_font_r").focus();'."\n"
		.JS_SUFFIX."\n";

	// general help on this form
	$help = '<p>'.i18n::s('Do not set any background color to achieve a transparent object.').'</p>';
	$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'navigation', 'help');

// no modifications in demo mode
} elseif(file_exists($context['path_to_root'].'parameters/demo.flag')) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation in demonstration mode.'));

// save updated parameters
} else {

	// backup the old version
	Safe::unlink($context['path_to_root'].'parameters/feeds.flash.include.php.bak');
	Safe::rename($context['path_to_root'].'parameters/feeds.flash.include.php', $context['path_to_root'].'parameters/feeds.flash.include.php.bak');

	// build the new configuration file
	$content = '<?php'."\n"
		.'// This file has been created by the configuration script feeds/flash/configure.php'."\n"
		.'// on '.gmdate("F j, Y, g:i a").' GMT, for '.Surfer::get_name().'. Please do not modify it manually.'."\n";
	if(isset($_REQUEST['flash_font_r']))
		$content .= '$context[\'flash_font_r\']='.addcslashes($_REQUEST['flash_font_r'], "\\'").";\n";
	if(isset($_REQUEST['flash_font_g']))
		$content .= '$context[\'flash_font_g\']='.addcslashes($_REQUEST['flash_font_g'], "\\'").";\n";
	if(isset($_REQUEST['flash_font_b']))
		$content .= '$context[\'flash_font_b\']='.addcslashes($_REQUEST['flash_font_b'], "\\'").";\n";
	if(isset($_REQUEST['flash_background_r']))
		$content .= '$context[\'flash_background_r\']='.addcslashes($_REQUEST['flash_background_r'], "\\'").";\n";
	if(isset($_REQUEST['flash_background_g']))
		$content .= '$context[\'flash_background_g\']='.addcslashes($_REQUEST['flash_background_g'], "\\'").";\n";
	if(isset($_REQUEST['flash_background_b']))
		$content .= '$context[\'flash_background_b\']='.addcslashes($_REQUEST['flash_background_b'], "\\'").";\n";
	if(isset($_REQUEST['flash_font']))
		$content .= '$context[\'flash_font\']=\''.addcslashes($_REQUEST['flash_font'], "\\'")."';\n";
	if(isset($_REQUEST['flash_font_height']))
		$content .= '$context[\'flash_font_height\']='.addcslashes($_REQUEST['flash_font_height'], "\\'").";\n";
	if(isset($_REQUEST['flash_width']))
		$content .= '$context[\'flash_width\']='.addcslashes($_REQUEST['flash_width'], "\\'").";\n";
	if(isset($_REQUEST['flash_height']))
		$content .= '$context[\'flash_height\']='.addcslashes($_REQUEST['flash_height'], "\\'").";\n";
	$content .= '?>'."\n";

	// update the parameters file
	if(!Safe::file_put_contents('parameters/feeds.flash.include.php', $content)) {

		Logger::error(sprintf(i18n::s('ERROR: Impossible to write to the file %s. The configuration has not been saved.'), 'parameters/feeds.flash.include.php'));

		// allow for a manual update
		$context['text'] .= '<p style="text-decoration: blink;">'.sprintf(i18n::s('To actually change the configuration, please copy and paste following lines by yourself in file %s.'), 'parameters/feeds.flash.include.php')."</p>\n";

	// job done
	} else {

		$context['text'] .= '<p>'.sprintf(i18n::s('The following configuration has been saved into the file %s.'), 'parameters/feeds.flash.include.php')."</p>\n";

		// remember the change
		$label = sprintf(i18n::c('%s has been updated'), 'parameters/feeds.flash.include.php');
		Logger::remember('feeds/configure.php', $label);

	}

	// display updated parameters
	$context['text'] .= Skin::build_box(i18n::s('Configuration parameters'), Safe::highlight_string($content), 'folded');

	// follow-up commands
	$follow_up = i18n::s('Where do you want to go now?');
	$menu = array();
	$menu = array_merge($menu, array( $context['url_to_root'] => i18n::s('Front page') ));
	$menu = array_merge($menu, array( 'feeds/' => i18n::s('Information channels') ));
	$menu = array_merge($menu, array( 'control/' => i18n::s('Control Panel') ));
	$menu = array_merge($menu, array( 'feeds/flash/configure.php' => i18n::s('Configure again') ));
	$follow_up .= Skin::build_list($menu, 'menu_bar');
	$context['text'] .= Skin::build_block($follow_up, 'bottom');

}

// render the skin
render_skin();

?>