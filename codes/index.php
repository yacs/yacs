<?php
/**
 * the index page for formatting codes
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// load localized strings
i18n::bind('codes');

// load the skin
load_skin('codes');

// do not index this page
$context->sif('robots','noindex');

// the path to this page
$context['path_bar'] = array( 'help/' => i18n::s('Help index') );

// the title of the page
$context['page_title'] = i18n::s('Introduction to formatting codes');

// the date of last modification
if(Surfer::is_associate())
	$context['page_details'] .= '<p '.tag::_class('details').'>'.sprintf(i18n::s('Edited %s'), Skin::build_date(getlastmod())).'</p>';

// all help pages
$help_links = array(
		'codes/basic.php'	=> i18n::s('In-line formatting codes (bold, underlined, ...)'),
		'codes/links.php'	=> i18n::s('Links (and shortcuts, buttons, ...)'),
		'codes/lists.php'	=> i18n::s('Lists (with bullets, numbered, ...)'),
		'codes/blocks.php'	=> i18n::s('Blocks (indentation, script, quote, ...)'),
		'codes/tables.php'	=> i18n::s('Tables (with headers, with grids, use CSV data, ...)'),
		'codes/titles.php'	=> i18n::s('Titles and questions (and table of content)'),
		'codes/live.php'	=> i18n::s('Dynamic queries (updates, content, ...)'),
		'codes/widgets.php'	=> i18n::s('Widgets (twitter, calendar, ...)'),
		'codes/misc.php'	=> i18n::s('Miscellaneous codes (charts, ...)')
		);

// the text of the page
$context['text'] .= '<p>'.i18n::s('Codes allow you to specify formatting rules for your text, even if you are not allowed to use HTML in your posts. BB codes, or UBB codes, originated from the forum software named PHPBB, and YACS has a special implementation of it.').'</p>'
	.'<p>'.i18n::s('With codes you use "tags" to add formatting to your text. Every  tag is enclosed in [ and ]. If you want to mark some region in your text, you need to use an opening tag and a closing tag. Closing tags start with [/, as you will see in the examples at the next pages. If you mistype a tag or forget to close it, you will not get the desired formatting.').'</p>'
	.'<p>'.i18n::s('Follow the links below to get more information on codes implemented on this server.').'</p>'
	.Skin::build_list($help_links, 'bullets')
	.'<p>'.sprintf(i18n::s('You can also browse links above in complement to the %s.'), Skin::build_link('skins/test.php', i18n::s('Theme test'), 'shortcut')).'</p>';

// referrals, if any
$context['components']['referrals'] = Skin::build_referrals('codes/index.php');

// render the skin
render_skin();

?>