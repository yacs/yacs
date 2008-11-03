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
 * Following values may be selected:
 * - 'alistapart' - Display only the most recent published page.
 * Previous articles may be accessed through a menu.
 * This layout is suitable for small sites with a low activity, maybe with a single section of pages.
 * Implemented at [script]skins/layout_home_articles_as_alistapart.php[/script].
 * - 'boxesandarrows' - List the last ten most recent pages.
 * Previous articles may be accessed through sections, or through the index of articles.
 * This layout is suitable for sites providing several different kinds of information.
 * Implemented at [script]skins/layout_home_articles_as_boxesandarrows.php[/script].
 * - 'compact' - A simple list of titles.
 * Implemented at [script]articles/layout_articles_as_compact.php[/script].
 * - 'daily' (this is also the default value) - Make titles out of publication dates.
 * This layout is suitable for weblogs. It is the default value.
 * Implemented at [script]skins/layout_home_articles_as_daily.php[/script].
 * - 'decorated' - A compact table featuring the introduction and thumbnail.
 * This layout is suitable for sites with a lot of items (gadget boxes, etc.) at the front page.
 * Implemented at [script]articles/layout_articles.php[/script].
 * - 'digg' - To show evidence of rating.
 * Implemented at [script]articles/layout_articles_as_digg.php[/script].
 * - 'newspaper' - Focus on the last published article, and list some articles published previously.
 * This layout is suitable for most sites.
 * Implemented at [script]skins/layout_home_articles_as_newspaper.php[/script].
 * - 'no_articles' - Do not mention recent articles.
 * Use this option to fully customize the front page, for example through some hook.
 * - 'slashdot' - List the last ten most recent pages.
 * Previous articles may be accessed through sections, or through the index of articles.
 * This layout is suitable for sites providing several different kinds of information.
 * Implemented at [script]skins/layout_home_articles_as_slashdot.php[/script].
 * - custom value - useful to implement an external layout.
 * YACS expects to have custom layout ##foo## implemented in ##skins/layout_home_articles_as_foo.php##.
 *
 * [*] [code]root_articles_count_at_home[/code] - Specify explicitly the number of articles to list at the front page.
 * By default YACS uses the value related to the selected layout.
 *
 * [*] [code]home_with_recent_files[/code] - List most recent files at the front page
 *
 * [*] [code]home_with_recent_links[/code] - List most recent links at the front page
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
 * [*] [code]home_with_older_articles[/code] - List in a side box articles older that those featured in the
 * main area.
 *
 * [*] [code]home_with_peering_servers[/code] - Display a list of servers that have pinged us recently.
 *
 * [*] [code]home_with_random_articles[/code] - Display a list of random pages.
 *
 * [*] [code]home_with_recent_poll[/code] - List the current poll at the front page
 *
 * [*] [code]home_with_top_articles[/code] - List most read articles at the front page
 *
 * [*] [code]home_with_top_files[/code] - List most fetched files at the front page
 *
 * [*] [code]home_with_top_links[/code] - List most clicked links at the front page
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
 * If the file [code]demo.flag[/code] exists, the script assumes that this instance
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
	Safe::header('Status: 401 Forbidden', TRUE, 401);
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
	if(!isset($context['root_sections_at_home']) || !preg_match('/([0-9]+|none|root)/', $context['root_sections_at_home']))
		$context['root_sections_at_home'] = 'root';
	if(!isset($context['root_sections_count_at_home']) || ($context['root_sections_count_at_home'] < 1))
		$context['root_sections_count_at_home'] = 5;
	$input = sprintf(i18n::s('List up to %s sections:'), '<input type="text" name="root_sections_count_at_home" value="'.encode_field($context['root_sections_count_at_home']).'" size="2" />')
		.BR.'<input type="radio" name="root_sections_at_home" value="root"';
	if($context['root_sections_at_home'] == 'root')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('List top-level sections.')
		.BR.'<input type="radio" name="root_sections_at_home" value="id"';
	if((int)$context['root_sections_at_home'] > 0) {
		$input .= ' checked="checked"';
		$value = $context['root_sections_at_home'];
	} else {
		$value = 0;
	}
	$input .= '/> '.i18n::s('List only section with the following id').' <input type="text" name="section_id_at_home" value="'.encode_field($value).'" size="2" />'
		.BR.'<input type="radio" name="root_sections_at_home" value="none"';
	if($context['root_sections_at_home'] == 'none')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Do not list sections explicitly -- Useful for skins that feature tabbed sections.').BR;

	// root_sections_layout - which layout for sections
	$input .= BR.i18n::s('Following layouts can be used for selected sections:');

	// default layout is to map sections
	$custom_layout = '';
	if(!isset($context['root_sections_layout']))
		$context['root_sections_layout'] = 'map';
	elseif(!preg_match('/(compact|decorated|freemind|folded|inline|jive|map|menu|titles|yabb)/', $context['root_sections_layout'])) {
		$custom_layout = $context['root_sections_layout'];
		$context['root_sections_layout'] = 'custom';
	}

	// available layouts for sections
	$input .= BR.'<input type="radio" name="root_sections_layout" value="menu"';
	if($context['root_sections_layout'] == 'menu')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('menu - List sections in the menu bar, right below page title.')
		.BR.'<input type="radio" name="root_sections_layout" value="decorated"';
	if($context['root_sections_layout'] == 'decorated')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('decorated - As a decorated list.')
		.BR.'<input type="radio" name="root_sections_layout" value="map"';
	if($context['root_sections_layout'] == 'map')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('map - Map in two columns, like Yahoo!')
		.BR.'<input type="radio" name="root_sections_layout" value="freemind"';
	if($context['root_sections_layout'] == 'freemind')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('freemind - Build an interactive mind map')
		.BR.'<input type="radio" name="root_sections_layout" value="jive"';
	if($context['root_sections_layout'] == 'jive')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('jive - List 5 threads per board')
		.BR.'<input type="radio" name="root_sections_layout" value="yabb"';
	if($context['root_sections_layout'] == 'yabb')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('yabb - A discussion forum')
		.BR.'<input type="radio" name="root_sections_layout" value="inline"';
	if($context['root_sections_layout'] == 'inline')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('inline - List sub-sections and related articles.')
		.BR.'<input type="radio" name="root_sections_layout" value="folded"';
	if($context['root_sections_layout'] == 'folded')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('folded - List sub-sections as folded boxes, with content (one box per section).')
		.BR.'<input type="radio" name="root_sections_layout" value="compact"';
	if($context['root_sections_layout'] == 'compact')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('compact - In a compact list, like DMOZ.')
		.BR.'<input type="radio" name="root_sections_layout" value="titles"';
	if($context['root_sections_layout'] == 'titles')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('titles - Use only titles and thumbnails.')
		.BR.'<input type="radio" name="root_sections_layout" value="custom"';
	if($context['root_sections_layout'] == 'custom')
		$input .= ' checked="checked"';
	$input .= '/> '.sprintf(i18n::s('Use the customized layout %s'), '<input type="text" name="sections_custom_layout" value="'.encode_field($custom_layout).'" size="32" />');
	$fields[] = array($label, $input);

	// use flash to animate recent pages
	$label = i18n::s('Flash');
	$input = '<input type="radio" name="root_flash_at_home" value="N"';
	if(!isset($context['root_flash_at_home']) || ($context['root_flash_at_home'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Do not list recent articles in an animated Flash object.');
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
	$input .= '/> '.sprintf(i18n::s('Display up to 6 gadget boxes. Post articles in %s to add more boxes. You can also flag some sections or some categories to list their content in gadget boxes.'), Skin::build_link(Sections::get_url('gadget_boxes'), i18n::s('the section dedicated to gadget boxes'), 'shortcut'));
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
	$custom_layout = '';
	if(!isset($context['root_articles_layout']) || !$context['root_articles_layout'])
		$context['root_articles_layout'] = 'daily';
	elseif(!preg_match('/(alistapart|boxesandarrows|compact|daily|decorated|digg|newspaper|no_articles|slashdot)/', $context['root_articles_layout'])) {
		$custom_layout = $context['root_articles_layout'];
		$context['root_articles_layout'] = 'custom';
	}

	// daily
	$input .= '<input type="radio" name="root_articles_layout" value="daily"';
	if($context['root_articles_layout'] == 'daily')
		$input .= ' checked="checked"';
	$input .= '/>daily - '.i18n::s('For weblogs and blogmarks.')
		.' '.Skin::build_link('skins/layout_home_articles_as_daily.jpg', i18n::s('Preview'), 'help').BR;

	// newspaper
	$input .= '<input type="radio" name="root_articles_layout" value="newspaper"';
	if($context['root_articles_layout'] == 'newspaper')
		$input .= ' checked="checked"';
	$input .= '/>newspaper - '.i18n::s('Focus on the last published article, and on the three articles published previously.')
		.' '.Skin::build_link('skins/layout_home_articles_as_newspaper.jpg', i18n::s('Preview'), 'help').BR;

	// boxesandarrows
	$input .= '<input type="radio" name="root_articles_layout" value="boxesandarrows"';
	if($context['root_articles_layout'] == 'boxesandarrows')
		$input .= ' checked="checked"';
	$input .= '/>boxesandarrows - '.i18n::s('Focus on the last two most recent articles, then list previous pages. Click on article titles to read full text.')
		.' '.Skin::build_link('skins/layout_home_articles_as_boxesandarrows.jpg', i18n::s('Preview'), 'help').BR;

	// slashdot
	$input .= '<input type="radio" name="root_articles_layout" value="slashdot"';
	if($context['root_articles_layout'] == 'slashdot')
		$input .= ' checked="checked"';
	$input .= '/>slashdot - '.i18n::s('List most recent pages equally.')
		.' '.Skin::build_link('skins/layout_home_articles_as_slashdot.jpg', i18n::s('Preview'), 'help').BR;

	// digg
	$input .= '<input type="radio" name="root_articles_layout" value="digg"';
	if($context['root_articles_layout'] == 'digg')
		$input .= ' checked="checked"';
	$input .= '/>digg - '.i18n::s('A decorated list of pages that have been most rated by community members.').BR;

	// decorated
	$input .= '<input type="radio" name="root_articles_layout" value="decorated"';
	if($context['root_articles_layout'] == 'decorated')
		$input .= ' checked="checked"';
	$input .= '/>decorated - '.i18n::s('A decorated list of most recent pages. This layout is suitable for sites with a long cover article at the front page.').BR;

	// compact
	$input .= '<input type="radio" name="root_articles_layout" value="compact"';
	if($context['root_articles_layout'] == 'compact')
		$input .= ' checked="checked"';
	$input .= '/>compact - '.i18n::s('A compact list of most recent pages. This layout is suitable for sites with a lot of items (gadget boxes, etc.) at the front page.').BR;

	// alistapart
	$input .= '<input type="radio" name="root_articles_layout" value="alistapart"';
	if($context['root_articles_layout'] == 'alistapart')
		$input .= ' checked="checked"';
	$input .= '/>alistapart - '.i18n::s('Display only the most recent published page. Previous articles may be accessed through a menu. This layout is suitable for sites with a low number of heavy publications.')
		.' '.Skin::build_link('skins/layout_home_articles_as_alistapart.jpg', i18n::s('Preview'), 'help').BR;

	// custom
	$input .= '<input type="radio" name="root_articles_layout" value="custom"';
	if($context['root_articles_layout'] == 'custom')
		$input .= ' checked="checked"';
	$input .= '/> '.sprintf(i18n::s('Use the customized layout %s'), '<input type="text" name="home_custom_layout" value="'.encode_field($custom_layout).'" size="32" />').BR;

	// no article
	$input .= '<p><input type="radio" name="root_articles_layout" value="no_articles"';
	if($context['root_articles_layout'] == 'no_articles')
		$input .= ' checked="checked"';
	$input .= EOT.sprintf(i18n::s('Do not display recent articles. The layout of the front page is solely based on a %s, on %s, plus %s.'),
		Skin::build_link(Sections::get_url('covers'), i18n::s('cover article'), 'shortcut'),
		Skin::build_link('sections/', i18n::s('the site map'), 'shortcut'),
		Skin::build_link(Sections::get_url('gadget_boxes'), i18n::s('gadget boxes'), 'shortcut')).'</p>';

	// number of entries at the front page
	if(!isset($context['root_articles_count_at_home']))
		$context['root_articles_count_at_home'] = '';
	$input .= '<p>'.sprintf(i18n::s('Display %s articles at the front page. Put a number if you wish to override the default value for the selected layout.'), '<input type="text" name="root_articles_count_at_home" size="2" value="'.encode_field($context['root_articles_count_at_home']).'" maxlength="4" />').'</p>';

	$fields[] = array($label, $input);

	// options
	$label = i18n::s('Options');

	// optional components for the main panel of the front page
	$input = i18n::s('Following elements can be added to the main panel:').BR;

	// with recent files
	$checked = '';
	if(isset($context['home_with_recent_files']) && ($context['home_with_recent_files'] == 'Y'))
		$checked .= ' checked="checked"';
	$input .= '<input type="checkbox" name="home_with_recent_files" value="Y" '.$checked.'/> '.i18n::s('Include the list of recent files').BR;

	// with recent links
	$checked = '';
	if(isset($context['home_with_recent_links']) && ($context['home_with_recent_links'] == 'Y'))
		$checked .= ' checked="checked"';
	$input .= '<input type="checkbox" name="home_with_recent_links" value="Y" '.$checked.'/> '.i18n::s('Include the list of recent links').BR;

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

	// optional components in the extra panel
	$label = i18n::s('Extra');
	$input = i18n::s('Following elements can be added to the extra panel:');

	// with recent poll
	$checked = '';
	if(isset($context['home_with_recent_poll']) && ($context['home_with_recent_poll'] == 'Y'))
		$checked = 'checked="checked" ';
	$input .= BR.'<input type="checkbox" name="home_with_recent_poll" value="Y" '.$checked.'/> '.i18n::s('Include the last recent poll');

	// with peering servers
	$checked = '';
	if(isset($context['home_with_peering_servers']) && ($context['home_with_peering_servers'] == 'Y'))
		$checked = 'checked="checked" ';
	$input .= BR.'<input type="checkbox" name="home_with_peering_servers" value="Y" '.$checked.'/> '.i18n::s('Include the list of servers that ping us');

	// with older articles
	$checked = '';
	if(isset($context['home_with_older_articles']) && ($context['home_with_older_articles'] == 'Y'))
		$checked = 'checked="checked" ';
	$input .= BR.'<input type="checkbox" name="home_with_older_articles" value="Y" '.$checked.'/> '.i18n::s('List older articles as well');

	// with top articles
	$checked = '';
	if(isset($context['home_with_top_articles']) && ($context['home_with_top_articles'] == 'Y'))
		$checked = 'checked="checked" ';
	$input .= BR.'<input type="checkbox" name="home_with_top_articles" value="Y" '.$checked.'/> '.i18n::s('Include the list of most read articles');

	// with top files
	$checked = '';
	if(isset($context['home_with_top_files']) && ($context['home_with_top_files'] == 'Y'))
		$checked = 'checked="checked" ';
	$input .= BR.'<input type="checkbox" name="home_with_top_files" value="Y" '.$checked.'/> '.i18n::s('Include the list of most popular files');

	// with top links
	$checked = '';
	if(isset($context['home_with_top_links']) && ($context['home_with_top_links'] == 'Y'))
		$checked = 'checked="checked" ';
	$input .= BR.'<input type="checkbox" name="home_with_top_links" value="Y" '.$checked.'/> '.i18n::s('Include the list of most popular links');

	// with random articles
	$checked = '';
	if(isset($context['home_with_random_articles']) && ($context['home_with_random_articles'] == 'Y'))
		$checked = 'checked="checked" ';
	$input .= BR.'<input type="checkbox" name="home_with_random_articles" value="Y" '.$checked.'/> '.i18n::s('Include a sample of random articles');

	$fields[] = array($label, $input);

	// build the form
	$extra .= Skin::build_form($fields);
	$fields = array();

	//
	// assemble all tabs
	//
	$all_tabs = array(
		array('main_tab', i18n::s('Main panel'), 'main_content', $main),
		array('extra_tab', i18n::s('Side panel'), 'extra_content', $extra)
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
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation in demonstration mode.'));

// save updated parameters
} else {

	// ensure we have a valid layout for sections
	if(!isset($_REQUEST['root_sections_layout']) || !$_REQUEST['root_sections_layout'] || !preg_match('/(compact|custom|decorated|freemind|folded|inline|jive|map|menu|titles|yabb)/', $_REQUEST['root_sections_layout']))
		$_REQUEST['root_sections_layout'] = 'map';
	elseif($_REQUEST['root_sections_layout'] == 'custom') {
		if(isset($_REQUEST['sections_custom_layout']) && $_REQUEST['sections_custom_layout'])
			$_REQUEST['root_sections_layout'] = basename(strip_tags($_REQUEST['sections_custom_layout']));
		else
			$_REQUEST['root_sections_layout'] = 'map';
	}

	// ensure we have a valid layout for articles
	if(!isset($_REQUEST['root_articles_layout']) || !$_REQUEST['root_articles_layout'] || !preg_match('/(alistapart|boxesandarrows|compact|custom|daily|decorated|digg|newspaper|no_articles|slashdot)/', $_REQUEST['root_articles_layout']))
		$_REQUEST['root_articles_layout'] = 'daily';
	elseif($_REQUEST['root_articles_layout'] == 'custom') {
		if(isset($_REQUEST['home_custom_layout']) && $_REQUEST['home_custom_layout'])
			$_REQUEST['root_articles_layout'] = basename(strip_tags($_REQUEST['home_custom_layout']));
		else
			$_REQUEST['root_articles_layout'] = 'daily';
	}

	// backup the old version
	Safe::unlink($context['path_to_root'].'parameters/root.include.php.bak');
	Safe::rename($context['path_to_root'].'parameters/root.include.php', $context['path_to_root'].'parameters/root.include.php.bak');

	// build the new configuration file
	$content = '<?php'."\n"
		.'// This file has been created by the configuration script configure.php'."\n"
		.'// on '.gmdate("F j, Y, g:i a").' GMT, for '.Surfer::get_name().'. Please do not modify it manually.'."\n"
		.'global $context;'."\n"
		.'$context[\'root_articles_layout\']=\''.addcslashes($_REQUEST['root_articles_layout'], "\\'")."';\n";
	if(isset($_REQUEST['root_articles_count_at_home']) && intval($_REQUEST['root_articles_count_at_home']))
		$content .= '$context[\'root_articles_count_at_home\']=\''.intval($_REQUEST['root_articles_count_at_home'])."';\n";
	if(isset($_REQUEST['root_cover_at_home']))
		$content .= '$context[\'root_cover_at_home\']=\''.addcslashes($_REQUEST['root_cover_at_home'], "\\'")."';\n";
	if(isset($_REQUEST['root_flash_at_home']))
		$content .= '$context[\'root_flash_at_home\']=\''.addcslashes($_REQUEST['root_flash_at_home'], "\\'")."';\n";
	if(isset($_REQUEST['root_gadget_boxes_at_home']))
		$content .= '$context[\'root_gadget_boxes_at_home\']=\''.addcslashes($_REQUEST['root_gadget_boxes_at_home'], "\\'")."';\n";
	if(isset($_REQUEST['home_with_older_articles']))
		$content .= '$context[\'home_with_older_articles\']=\''.addcslashes($_REQUEST['home_with_older_articles'], "\\'")."';\n";
	if(isset($_REQUEST['home_with_peering_servers']))
		$content .= '$context[\'home_with_peering_servers\']=\''.addcslashes($_REQUEST['home_with_peering_servers'], "\\'")."';\n";
	if(isset($_REQUEST['home_with_random_articles']))
		$content .= '$context[\'home_with_random_articles\']=\''.addcslashes($_REQUEST['home_with_random_articles'], "\\'")."';\n";
	if(isset($_REQUEST['home_with_recent_files']))
		$content .= '$context[\'home_with_recent_files\']=\''.addcslashes($_REQUEST['home_with_recent_files'], "\\'")."';\n";
	if(isset($_REQUEST['home_with_recent_links']))
		$content .= '$context[\'home_with_recent_links\']=\''.addcslashes($_REQUEST['home_with_recent_links'], "\\'")."';\n";
	if(isset($_REQUEST['home_with_recent_poll']))
		$content .= '$context[\'home_with_recent_poll\']=\''.addcslashes($_REQUEST['home_with_recent_poll'], "\\'")."';\n";
	if(isset($_REQUEST['home_with_top_articles']))
		$content .= '$context[\'home_with_top_articles\']=\''.addcslashes($_REQUEST['home_with_top_articles'], "\\'")."';\n";
	if(isset($_REQUEST['home_with_top_files']))
		$content .= '$context[\'home_with_top_files\']=\''.addcslashes($_REQUEST['home_with_top_files'], "\\'")."';\n";
	if(isset($_REQUEST['home_with_top_links']))
		$content .= '$context[\'home_with_top_links\']=\''.addcslashes($_REQUEST['home_with_top_links'], "\\'")."';\n";
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
			if(isset($_REQUEST['section_id_at_home']) && ((int)$_REQUEST['section_id_at_home'] > 0))
				$_REQUEST['root_sections_at_home'] = $_REQUEST['section_id_at_home'];
			else
				$_REQUEST['root_sections_at_home'] = 'none';
		}
		$content .= '$context[\'root_sections_at_home\']=\''.addcslashes($_REQUEST['root_sections_at_home'], "\\'")."';\n";
	}
	if(isset($_REQUEST['root_sections_count_at_home']))
		$content .= '$context[\'root_sections_count_at_home\']=\''.addcslashes($_REQUEST['root_sections_count_at_home'], "\\'")."';\n";
	$content .= '$context[\'root_sections_layout\']=\''.addcslashes($_REQUEST['root_sections_layout'], "\\'")."';\n";
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
		Logger::remember('configure.php', $label);
	}

	// display updated parameters
	$context['text'] .= Skin::build_box(i18n::s('Configuration parameters'), Safe::highlight_string($content), 'folder');

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
		$follow_up .= Skin::build_list($menu, 'page_menu');

		// at page bottom
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	}

}

// render the skin
render_skin();

?>