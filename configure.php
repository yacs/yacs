<?php
/**
 * the configuration panel for the front page
 *
 * This configuration panel manages following parameters:
 *
 * [*] [code]root_cover_at_home[/code] - Either 'full', 'body' or 'none'.
 * By default YACS displays the title and the content of the cover article at the front page.
 * Select 'body' to mask the title of the cover page.
 * And use 'none' to not display any cover article.
 *
 * [*] [code]root_sections_count_at_home[/code] - Specify explicitly the number of sections to list at the front page.
 * Default value is 5.
 *
 * [*] [code]root_sections_at_home[/code] - Either 'root', or 'none', or some section id.
 * By default YACS lists content of root sections.
 * Else put the id of the section you would like to focus on at the front page.
 * Or disable explicit listing of sections if you use a skin that features dynamic tabs, based on sections.
 *
 * [*] [code]root_sections_layout[/code] - Either 'menu', or another layout.
 * By default YACS lists top sections as a menu bar.
 * Use 'map' to reflect the site map at the front page.
 *
 * [*] [code]root_flash_at_home[/code] - Insert a Flash object to animate recent titles
 *
 * [*] [code]root_gadget_boxes_at_home[/code] - Display gadget boxes at the front page.
 * Due to the special layout used for gadget boxes, there is a fixed maximum of 6 gadget boxes.
 *
 * [*] [code]root_articles_layout[/code] - Select the layout to use for articles at the home page.
 *
 * [*] [code]root_articles_count_at_home[/code] - Specify explicitly the number of articles to list at the front page.
 * By default YACS uses the value related to the selected layout.
 *
 *
 * Parameters for the extra panel of the front page:
 *
 * [*] [code]root_featured_layout[/code] - Either 'static', or 'scroll', or 'rotate', or 'none'.
 * By default YACS lists featured articles as a static list.
 * Use 'scroll' to animate things.
 *
 * [*] [code]root_featured_count[/code] - Specify explicitly the number of featured articles to list at the front page.
 *
 * [*] [code]root_news_layout[/code] - Either 'static', or 'scroll', or 'rotate', or 'none'.
 * By default YACS lists news articles as a static list.
 * Use 'scroll' to animate the news.
 *
 * [*] [code]root_news_count[/code] - Specify explicitly the number of news to list at the front page.
 *
 *
 * Access to this page is reserved to associates.
 *
 * Configuration information is saved into [code]parameters/root.include.php[/code].
 * If YACS is prevented to write to the file, it displays parameters to allow for a manual update.
 *
 * The file [code]parameters/root.include.php.bak[/code] can be used to restore
 * the active configuration before the last change.
 *
 * If the file [code]parameters/demo.flag[/code] exists, the script assumes that this instance
 * of YACS runs in demonstration mode.
 * In this mode the edit form is displayed, but parameters are not saved in the configuration file.
 *
 * @author Bernard Paques
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
// common definitions and initial processing
include_once 'shared/global.php';

// load localized strings
i18n::bind('root');

// load the skin
load_skin('root');

// the path to this page
$context['path_bar'] = array( 'control/' => i18n::s('Control Panel') );

// the title of the page
$context['page_title'] = sprintf(i18n::s('%s: %s'), i18n::s('Configure'), i18n::s('Front page'));

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('configure.php'));

// only associates can proceed
elseif(!Surfer::is_associate()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// display the input form
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'POST')) {

	// first installation
	if(!file_exists('parameters/switch.on') && !file_exists('parameters/switch.off'))
		$context['text'] .= '<p>'.i18n::s('You can use default values and change these later on. Hit the button at the bottom of the page to move forward.')."</p>\n";

	// the form
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><div>';

	// the main panel
	//
	$main = '';

	// options to display the cover page
	$label = i18n::s('Cover article');
	$input = '<input type="radio" name="root_cover_at_home" value="full"';
	if(!isset($context['root_cover_at_home']) || !preg_match('/(body|none)/', $context['root_cover_at_home']))
		$input .= ' checked="checked"';
	$input .= ' /> '.i18n::s('Display the cover article at the front page.');
	$input .= BR.'<input type="radio" name="root_cover_at_home" value="body"';
	if(isset($context['root_cover_at_home']) && ($context['root_cover_at_home'] == 'body'))
		$input .= ' checked="checked"';
	$input .= ' /> '.i18n::s('Display the main part of the cover article, but not the title.');
	$input .= BR.'<input type="radio" name="root_cover_at_home" value="none"';
	if(isset($context['root_cover_at_home']) && ($context['root_cover_at_home'] == 'none'))
		$input .= ' checked="checked"';
	$input .= ' /> '.i18n::s('Do not use the cover article at the front page.');
	$fields[] = array($label, $input);

	// parameters for rendering of sections at the front page
	$label = i18n::s('Sections');

	// root_sections_at_home - which sections should be displayed
	if(!isset($context['root_sections_at_home']))
		$context['root_sections_at_home'] = 'root';
	if(!isset($context['root_sections_count_at_home']) || ($context['root_sections_count_at_home'] < 1))
		$context['root_sections_count_at_home'] = 5;
	$input = sprintf(i18n::s('List up to %s sections:'), '<input type="text" name="root_sections_count_at_home" value="'.encode_field($context['root_sections_count_at_home']).'" size="2" />')
		.BR.'<input type="radio" name="root_sections_at_home" value="root"';
	if($context['root_sections_at_home'] == 'root')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('List top-level sections.')
		.BR.'<input type="radio" name="root_sections_at_home" value="id"';
	if(!preg_match('/(none|root)/', $context['root_sections_at_home'])) {
		$input .= ' checked="checked"';
		$value = $context['root_sections_at_home'];
	} else {
		$value = 0;
	}
	$input .= '/> '.i18n::s('List only section with the following id or nick name').' <input type="text" name="section_id_at_home" value="'.encode_field($value).'" size="20" />'
		.BR.'<input type="radio" name="root_sections_at_home" value="none"';
	if($context['root_sections_at_home'] == 'none')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Do not list sections explicitly -- Useful for skins that feature tabbed sections.').BR;

	// root_sections_layout - which layout for sections
	$input .= BR.i18n::s('Following layouts can be used for selected sections:');

	// default layout is to map sections
	if(!isset($context['root_sections_layout']))
		$context['root_sections_layout'] = 'map';
	
        $input   .= Skin::build_layouts_selector('section', $context['root_sections_layout']);
	$fields[] = array($label, $input);

	// use flash to animate recent pages
	$label = i18n::s('Flash');
	$input = '<input type="radio" name="root_flash_at_home" value="N"';
	if(!isset($context['root_flash_at_home']) || ($context['root_flash_at_home'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Do not list recent pages in an animated Flash object.');
	$input .= BR.'<input type="radio" name="root_flash_at_home" value="Y"';
	if(isset($context['root_flash_at_home']) && ($context['root_flash_at_home'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.sprintf(i18n::s('List most recent pages in a dynamic and clickable Flash panel. Check this option only if the %s module has been installed. This is the case if you have some text displayed %s.'),
		Skin::build_link('http://ming.sourceforge.net/', 'Ming', 'external'),
		'<a href="'.$context['url_to_root'].'feeds/flash/slashdot.php">'.i18n::s('here').'</a>');
	$fields[] = array($label, $input);

	// gadget boxes
	$label = i18n::s('Gadget boxes');
	$input = '<input type="radio" name="root_gadget_boxes_at_home" value="Y"';
	if(!isset($context['root_gadget_boxes_at_home']) || ($context['root_gadget_boxes_at_home'] != 'N'))
		$input .= ' checked="checked"';
	$input .= '/> '.sprintf(i18n::s('Display up to 6 gadget boxes. Post articles in %s to add more boxes.'), Skin::build_link(Sections::get_url('gadget_boxes'), i18n::s('the section dedicated to gadget boxes'), 'shortcut'));
	$input .= BR.'<input type="radio" name="root_gadget_boxes_at_home" value="N"';
	if(isset($context['root_gadget_boxes_at_home']) && ($context['root_gadget_boxes_at_home'] == 'N'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Do not show gadget boxes at the front page.');
	$fields[] = array($label, $input);

	// recent articles
	$label = i18n::s('Pages');

	// splash message for articles layout
	$input = i18n::s('Following layouts can be used for recent pages:').BR;

	// default layout is to weblog
	if(!isset($context['root_articles_layout']) || !$context['root_articles_layout'])
		$context['root_articles_layout'] = 'daily';
        
	$input   .= Skin::build_layouts_selector('article', $context['root_articles_layout']);

	// number of entries at the front page
	if(!isset($context['root_articles_count_at_home']))
		$context['root_articles_count_at_home'] = '';
	$input .= '<p>'.sprintf(i18n::s('Display %s articles at the front page. Put a number if you wish to override the default value for the selected layout.'), '<input type="text" name="root_articles_count_at_home" size="2" value="'.encode_field($context['root_articles_count_at_home']).'" maxlength="4" />').'</p>';

	$fields[] = array($label, $input);

	// build the form
	$main .= Skin::build_form($fields);
	$fields = array();

	// the extra panel
	//
	$extra = '';

	// featured articles can be either a static or an animated list
	$label = i18n::s('Featured');
	if(!isset($context['root_featured_count']) || ($context['root_featured_count'] < 1) || ($context['root_featured_count'] > 7))
		$context['root_featured_count'] = 5;
	$input = '<input type="radio" name="root_featured_layout" value="static"';
	if(!isset($context['root_featured_layout']) || !preg_match('/(rotate|scroll|none)/', $context['root_featured_layout']))
		$input .= ' checked="checked"';
	$input .= '/> '.sprintf(i18n::s('List up to %s featured pages aside.'), '<input type="text" name="root_featured_count" value="'.encode_field($context['root_featured_count']).'" size="2" />');
	$input .= BR.'<input type="radio" name="root_featured_layout" value="scroll"';
	if(isset($context['root_featured_layout']) && ($context['root_featured_layout'] == 'scroll'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Similar to the first option, except that displayed information is scrolling.');
	$input .= BR.'<input type="radio" name="root_featured_layout" value="rotate"';
	if(isset($context['root_featured_layout']) && ($context['root_featured_layout'] == 'rotate'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Similar to the first option, except that featured are rotated.');
	$input .= BR.'<input type="radio" name="root_featured_layout" value="none"';
	if(isset($context['root_featured_layout']) && ($context['root_featured_layout'] == 'none'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Do not list featured pages.');
	$fields[] = array($label, $input);

	// news can be either a static or an animated list
	$label = i18n::s('News');
	if(!isset($context['root_news_count']) || ($context['root_news_count'] < 1) || ($context['root_news_count'] > 7))
		$context['root_news_count'] = 5;
	$input = '<input type="radio" name="root_news_layout" value="static"';
	if(!isset($context['root_news_layout']) || !preg_match('/(rotate|scroll|none)/', $context['root_news_layout']))
		$input .= ' checked="checked"';
	$input .= '/> '.sprintf(i18n::s('List up to %s news aside.'), '<input type="text" name="root_news_count" value="'.encode_field($context['root_news_count']).'" size="2" />');
	$input .= BR.'<input type="radio" name="root_news_layout" value="scroll"';
	if(isset($context['root_news_layout']) && ($context['root_news_layout'] == 'scroll'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Similar to the first option, except that displayed information is scrolling.');
	$input .= BR.'<input type="radio" name="root_news_layout" value="rotate"';
	if(isset($context['root_news_layout']) && ($context['root_news_layout'] == 'rotate'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Similar to the first option, except that news are rotated.');
	$input .= BR.'<input type="radio" name="root_news_layout" value="none"';
	if(isset($context['root_news_layout']) && ($context['root_news_layout'] == 'none'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Do not list news.');
	$fields[] = array($label, $input);

	// build the form
	$extra .= Skin::build_form($fields);
	$fields = array();

	//
	// assemble all tabs
	//
	$all_tabs = array(
		array('main', i18n::s('Main panel'), 'main_content', $main),
		array('extra', i18n::s('Side panel'), 'extra_content', $extra)
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
	if(file_exists('parameters/control.include.php'))
		$menu[] = Skin::build_link('control/', i18n::s('Control Panel'), 'span');

	// control panel
	if(file_exists('parameters/control.include.php'))
		$menu[] = Skin::build_link($context['url_to_root'], i18n::s('Front page'), 'span');

	// insert the menu in the page
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// end of the form
	$context['text'] .= '</div></form>';

// no modifications in demo mode
} elseif(file_exists($context['path_to_root'].'parameters/demo.flag')) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation in demonstration mode.'));

// save updated parameters
} else {

	// ensure we have a valid layout for sections
	if(!isset($_REQUEST['sections_layout']) || !$_REQUEST['sections_layout'] )
		$_REQUEST['sections_layout'] = 'map';
	elseif($_REQUEST['sections_layout'] == 'custom') {
		if(isset($_REQUEST['sections_custom_layout']) && $_REQUEST['sections_custom_layout'])
			$_REQUEST['sections_layout'] = basename(strip_tags($_REQUEST['sections_custom_layout']));
		else
			$_REQUEST['sections_layout'] = 'map';
	}

	// ensure we have a valid layout for articles
	if(!isset($_REQUEST['articles_layout']) || !$_REQUEST['articles_layout'] )
		$_REQUEST['articles_layout'] = 'daily';
	elseif($_REQUEST['articles_layout'] == 'custom') {
		if(isset($_REQUEST['articles_custom_layout']) && $_REQUEST['articles_custom_layout'])
			$_REQUEST['articles_layout'] = basename(strip_tags($_REQUEST['articles_custom_layout']));
		else
			$_REQUEST['articles_layout'] = 'daily';
	}

	// backup the old version
	Safe::unlink($context['path_to_root'].'parameters/root.include.php.bak');
	Safe::rename($context['path_to_root'].'parameters/root.include.php', $context['path_to_root'].'parameters/root.include.php.bak');

	// build the new configuration file
	$content = '<?php'."\n"
		.'// This file has been created by the configuration script configure.php'."\n"
		.'// on '.gmdate("F j, Y, g:i a").' GMT, for '.Surfer::get_name().'. Please do not modify it manually.'."\n"
		.'global $context;'."\n"
		.'$context[\'root_articles_layout\']=\''.addcslashes($_REQUEST['articles_layout'], "\\'")."';\n";
	if(isset($_REQUEST['root_articles_count_at_home']) && intval($_REQUEST['root_articles_count_at_home']))
		$content .= '$context[\'root_articles_count_at_home\']=\''.intval($_REQUEST['root_articles_count_at_home'])."';\n";
	if(isset($_REQUEST['root_cover_at_home']))
		$content .= '$context[\'root_cover_at_home\']=\''.addcslashes($_REQUEST['root_cover_at_home'], "\\'")."';\n";
	if(isset($_REQUEST['root_flash_at_home']))
		$content .= '$context[\'root_flash_at_home\']=\''.addcslashes($_REQUEST['root_flash_at_home'], "\\'")."';\n";
	if(isset($_REQUEST['root_gadget_boxes_at_home']))
		$content .= '$context[\'root_gadget_boxes_at_home\']=\''.addcslashes($_REQUEST['root_gadget_boxes_at_home'], "\\'")."';\n";
	if(isset($_REQUEST['root_featured_layout']))
		$content .= '$context[\'root_featured_layout\']=\''.addcslashes($_REQUEST['root_featured_layout'], "\\'")."';\n";
	if(isset($_REQUEST['root_featured_count']))
		$content .= '$context[\'root_featured_count\']=\''.addcslashes($_REQUEST['root_featured_count'], "\\'")."';\n";
	if(isset($_REQUEST['root_news_layout']))
		$content .= '$context[\'root_news_layout\']=\''.addcslashes($_REQUEST['root_news_layout'], "\\'")."';\n";
	if(isset($_REQUEST['root_news_count']))
		$content .= '$context[\'root_news_count\']=\''.addcslashes($_REQUEST['root_news_count'], "\\'")."';\n";
	if(isset($_REQUEST['root_sections_at_home'])) {
		if($_REQUEST['root_sections_at_home'] == 'id') {
			if(isset($_REQUEST['section_id_at_home']))
				$_REQUEST['root_sections_at_home'] = $_REQUEST['section_id_at_home'];
			else
				$_REQUEST['root_sections_at_home'] = 'none';
		}
		$content .= '$context[\'root_sections_at_home\']=\''.addcslashes($_REQUEST['root_sections_at_home'], "\\'")."';\n";
	}
	if(isset($_REQUEST['root_sections_count_at_home']))
		$content .= '$context[\'root_sections_count_at_home\']=\''.addcslashes($_REQUEST['root_sections_count_at_home'], "\\'")."';\n";
	$content .= '$context[\'root_sections_layout\']=\''.addcslashes($_REQUEST['sections_layout'], "\\'")."';\n";
	$content .= '?>'."\n";

	// update the parameters file
	if(!Safe::file_put_contents('parameters/root.include.php', $content)) {

		Logger::error(sprintf(i18n::s('ERROR: Impossible to write to the file %s. The configuration has not been saved.'), 'parameters/root.include.php'));

		// allow for a manual update
		$context['text'] .= '<p style="text-decoration: blink;">'.sprintf(i18n::s('To actually change the configuration, please copy and paste following lines by yourself in file %s.'), 'parameters/root.include.php')."</p>\n";

	// job done
	} else {

		$context['text'] .= '<p>'.sprintf(i18n::s('The following configuration has been saved into the file %s.'), 'parameters/root.include.php')."</p>\n";

		// first installation
		if(!file_exists('parameters/switch.on') && !file_exists('parameters/switch.off'))
			$context['text'] .= '<p>'.i18n::s('Review provided information and go to the bottom of the page to move forward.')."</a></p>\n";

		// purge the cache
		Cache::clear();

		// remember the change
		$label = sprintf(i18n::c('%s has been updated'), 'parameters/root.include.php');
		Logger::remember('configure.php: '.$label);
	}

	// display updated parameters
	$context['text'] .= Skin::build_box(i18n::s('Configuration parameters'), Safe::highlight_string($content), 'folded');

	// first installation
	if(!file_exists('parameters/switch.on') && !file_exists('parameters/switch.off')) {
		$context['text'] .= '<form method="get" action="control/" id="main_form">'."\n"
			.'<p>'.Skin::build_submit_button(i18n::s('Switch the server on')).'</p>'."\n"
			.'</form>'."\n";

	// ordinary follow-up commands
	} else {

		// what's next?
		$follow_up = i18n::s('Where do you want to go now?');

		// follow-up menu
		$menu = array();

		// front page
		$menu = array_merge($menu, array( $context['url_to_root'] => i18n::s('Front page') ));

		// control panel
		$menu = array_merge($menu, array( 'control/' => i18n::s('Control Panel') ));

		// do it again
		$menu = array_merge($menu, array( 'configure.php' => i18n::s('Configure again') ));

		// display follow-up commands
		$follow_up .= Skin::build_list($menu, 'menu_bar');

		// at page bottom
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	}

}

// render the skin
render_skin();

?>
