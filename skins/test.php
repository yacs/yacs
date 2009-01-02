<?php
/**
 * test your skin
 *
 * This script enables any web site developer to visually control the rendering of a skin.
 * The skin under test may be selected among any available skins on this system.
 *
 * This page creates reference items to be used by any skin.
 * - [code]$context['extra'][/code] - side content specific to this page
 * - [code]$context['navigation'][/code] - side content
 * - [code]$context['page_image'][/code] - the main image of the page, if any
 * - [code]$context['page_menu'][/code] - an array of $url => $label to show available commands (e.g., 'back', 'edit', 'delete', etc.)
 * - [code]$context['page_title'][/code] - the page title
 * - [code]$context['path_bar'][/code] - an array of $url => $label to show the stack of pages from home
 * - [code]$context['page_author'][/code] - appears in meta
 * - [code]$context['page_publisher'][/code] - appears in meta
 * - [code]$context['prefix'][/code] - some page prefix
 * - [code]$context['text'][/code] - the main content of the page, with cover article and gadget boxes
 *
 * @see skins/index.php
 * @see index.php
 *
 * @link http://www.lipsum.com/ Lorem Ipsum is simply dummy text of the printing and typesetting industry...
 * @link http://www.somacon.com/p141.php HTML and CSS Table Border Style Wizard
 *
 * Use this page while developing or checking a skin, then move to help pages on YACS codes to
 * finalize your task.
 *
 * @see codes/index.php
 *
 * Accept following invocations:
 * - test.php/original_skin
 * - test.php?skin=original_skin
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
include_once '../shared/global.php';
include_once '../files/files.php';

// the skin under test
$skin = '';
if(isset($_REQUEST['skin']))
	$skin = $_REQUEST['skin'];
if(isset($context['arguments'][0]))
	$skin = $context['arguments'][0];

// avoid potential attacks
$skin = preg_replace(FORBIDDEN_IN_PATHS, '', strip_tags($skin));

// accept it if there is a template.php
if(file_exists($context['path_to_root'].'skins/'.$skin.'/template.php'))
	$context['skin'] = 'skins/'.$skin;

// load the skin
load_skin('skins');

define('DUMMY_TEXT', 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'
	.' Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.'
	.' Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.'
	.' Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');

$items = array('_1' => 'Lorem ipsum dolor sit amet', '_2' => 'Excepteur sint occaecat cupidatat non proident', '_3' => 'Ut enim ad minim veniam');

define('COMPACT_LIST', Skin::build_list($items, 'compact'));

// alphabetical order

// // $context['error'] - to report run time errors
if($context['with_debug'] == 'Y')
	Logger::error(i18n::s('error messages, if any'));

// $context['navigation'] - navigation boxes
$context['navigation'] .= Skin::build_box(i18n::s('navigation').' 1', DUMMY_TEXT, 'navigation');
$context['navigation'] .= Skin::build_box(i18n::s('navigation').' 2', COMPACT_LIST, 'navigation');

// $context['extra'] - extra boxes
$context['extra'] .= Skin::build_box(i18n::s('extra').' 1', DUMMY_TEXT, 'extra');
$context['extra'] .= Skin::build_box(i18n::s('extra').' 2', COMPACT_LIST, 'extra');

// $context['extra'] - a fake contextual menu
$text = Skin::build_tree(array(array('#', '', i18n::s('menu').' 1', '', 'close'),
	array('#', '', i18n::s('menu').' 2', '', 'open', '', '',
		array(array('#', '', i18n::s('menu').' 2.1', '', 'close'),
			array('#', '', i18n::s('menu').' 2.2', '', 'open', '', '',
				array(array('#', '', i18n::s('menu').' 2.2.1', '', 'close'),
					array('#', '', i18n::s('menu').' 2.2.2', '', 'open', '', '',
						array(array('#', '', i18n::s('menu').' 2.2.2.1', '', 'close'),
							array('#', '', i18n::s('menu').' 2.2.2.2', '', 'open', '', '',
								array(array('#', '', i18n::s('menu').' 2.2.2.2.1', '', 'close'),
								array('#', '', i18n::s('menu').' 2.2.2.2.2', '', 'close'),
								array('#', '', i18n::s('menu').' 2.2.2.2.3', '', 'close'),
								array('#', '', i18n::s('menu').' 2.2.2.2.4', '', 'close'),
								array('#', '', i18n::s('menu').' 2.2.2.2.5', '', 'open'),
								array('#', '', i18n::s('menu').' 2.2.2.2.6', '', 'close')
								)),
							array('#', '', i18n::s('menu').' 2.2.2.3', '', 'close')
						)),
					array('#', '', i18n::s('menu').' 2.2.3', '', 'close')
				)),
			array('#', '', i18n::s('menu').' 2.3', '', 'close')
		)),
	array('#', '', i18n::s('menu').' 3', '', 'close'),
	array('#', '', i18n::s('menu').' 4', '', 'close')
	));
$context['extra']['contextual'] = Skin::build_box(i18n::s('contextual menu'), $text, 'navigation', 'contextual_menu');

// $context['page_author'] - the author
$context['page_author'] = 'webmaestro, through some PHP script';

// back to skin index
$context['page_menu'] = array_merge($context['page_menu'], array( 'skins/' => i18n::s('Themes') ));

// edit this skin
if(isset($skin) && Surfer::is_associate())
	$context['page_menu'] = array_merge($context['page_menu'], array( 'skins/edit.php?skin='.$skin => i18n::s('Edit this theme') ));

// validate at w3c
$context['page_menu'] = array_merge($context['page_menu'], array( 'http://validator.w3.org/check?uri=referer' => array('', i18n::s('Validate at w3c'), '', 'span') ));

// use this skin for the site
if(isset($skin) && Surfer::is_associate())
	$context['page_menu'] = array_merge($context['page_menu'], array('control/configure.php?parameter=skin&amp;value=skins/'.$skin => i18n::s('Use this theme')));

// derive this skin
if(isset($skin) && Surfer::is_associate())
	$context['page_menu'] = array_merge($context['page_menu'], array('skins/derive.php?skin='.$skin => i18n::s('Derive this theme')));

// $context['page_publisher'] - the publisher
$context['page_publisher'] = 'webmaestro again, still through some PHP script';

// $context['page_title'] - the title of the page
$context['page_title'] = i18n::s('Theme test');

// $context['path_bar'] - back to other sections
$context['path_bar'] = array( 'skins/' => i18n::s('Themes'));

// $context['prefix'] - also list skins available on this system
$context['prefix'] .= '<form method="get" action="'.$context['script_url'].'"><p>';
$context['prefix'] .= i18n::s('Skin to test').' <select name="skin">';
if ($dir = Safe::opendir("../skins")) {

	// valid skins have a template.php
	while(($file = Safe::readdir($dir)) !== FALSE) {
		if(($file[0] == '.') || !is_dir('../skins/'.$file))
			continue;
		if(!file_exists('../skins/'.$file.'/template.php'))
			continue;
		$checked = '';
		if($context['skin'] == 'skins/'.$file)
			$checked = ' selected="selected"';
		$skins[] = '<option value="'.$file.'"'.$checked.'>'.$file."</option>\n";
	}
	Safe::closedir($dir);
	if(@count($skins)) {
		sort($skins);
		foreach($skins as $skin)
			$context['prefix'] .= $skin;
	}
}
$context['prefix'] .= '</select> '.Skin::build_submit_button(' &raquo; ').'</p></form>';

// $context['prefix'] - some prefix data
$context['prefix'] .= '<p>'.sprintf(i18n::s('Use this page while developing or checking a theme, then activate the theme and move to %s to finalize your work.'), Skin::build_link('codes/', i18n::s('help pages on YACS codes'), 'shortcut')).'</p>';

// will be derivated to $context['text'] after codes::beautify()
$text = '';

// $context['text']
$text .= DUMMY_TEXT;

// $context['text'] - with gadgets -- see index.php and sections/view.php
$text .= "\n".'<p id="gadgets_prefix"> </p>'."\n"
	.Skin::build_box(i18n::s('gadget').' 1', DUMMY_TEXT, 'gadget')
	.Skin::build_box(i18n::s('gadget').' 2', DUMMY_TEXT, 'gadget')
	.Skin::build_box(i18n::s('gadget').' 3', DUMMY_TEXT, 'gadget')
	.Skin::build_box(i18n::s('gadget').' 4', DUMMY_TEXT, 'gadget')
	.Skin::build_box(i18n::s('gadget').' 5', DUMMY_TEXT, 'gadget')
	.Skin::build_box(i18n::s('gadget').' 6', DUMMY_TEXT, 'gadget')
	.'<p id="gadgets_suffix"> </p>'."\n";

// $context['text'] - introduction
$text .= Skin::build_block(DUMMY_TEXT, 'introduction');

// surfer profile
if(!$user_id = Surfer::get_id())
	$user_id = 1;

// newest article
$article_id = 1;
if($item =& Articles::get_newest_for_anchor(NULL, TRUE))
	$article_id = $item['id'];

// newest file
$file_id = 1;
if($item =& Files::get_newest())
	$file_id = $item['id'];

// $context['text'] - basic content with links, etc.
$text .= '[toc]'.DUMMY_TEXT."\n"
	.'<ul>'."\n"
	.'<li>[article='.$article_id.']</li>'."\n"
	.'<li>[section='.Sections::get_default().']</li>'."\n"
	.'<li>[category=featured]</li>'."\n"
	.'<li>[user='.$user_id.']</li>'."\n"
	.'<li>[download='.$file_id.']</li>'."\n"
	.'<li>[email]foo@bar.com[/email]</li>'."\n"
	.'<li>[link=Cisco]http://www.cisco.com/[/link]</li>'."\n"
	.'<li>[script]skins/test.php[/script]</li>'."\n"
	.'<li>'.Skin::build_link('skins/test.php', 'skins/test.php', 'shortcut').'</li>'."\n"
	.'<li>'.Skin::strip(DUMMY_TEXT, 7, 'skins/test.php').'</li>'."\n"
	.'<li>'.RESTRICTED_FLAG.i18n::s('Access is restricted to authenticated members').'</li>'."\n"
	.'<li>'.PRIVATE_FLAG.i18n::s('Access is restricted to associates and editors').'</li>'."\n"
	.'<li>'.i18n::s('This item is new').NEW_FLAG.'</li>'."\n"
	.'<li>'.i18n::s('This item has been updated').UPDATED_FLAG.'</li>'."\n"
	.'<li>'.DRAFT_FLAG.i18n::s('This item is a draft, and is not publicly visible').'</li>'."\n"
	.'</ul>'."\n"
	.'<p>[button='.i18n::s('Click to reload this page').']skins/test.php[/button]</p>'."\n"
	.DUMMY_TEXT."\n"
	.' [title]'.i18n::s('level 1 title').'[/title] '."\n".DUMMY_TEXT."\n"
			.' [subtitle]'.i18n::s('level 2 title').'[/subtitle] '."\n".DUMMY_TEXT;

// a sidebar
$sidebar =& Skin::build_box(i18n::s('sidebar box'), DUMMY_TEXT, 'sidebar');

// $context['text'] - section with sidebar box
$text .= Skin::build_box(i18n::s('with a sidebar box'), $sidebar.DUMMY_TEXT);

// a folded box
$folder =& Skin::build_box(i18n::s('folded box'), DUMMY_TEXT, 'folder');

// $context['text'] - section with folded box
$text .= Skin::build_box(i18n::s('with a folded box'), DUMMY_TEXT.$folder.DUMMY_TEXT);

// a menu bar
$menu_bar = array('skins/test.php' => i18n::s('Test page'), 'skins/' => i18n::s('Themes'), 'scripts/' => i18n::s('Server software'));;

// $context['text'] - section with a menu bar
$text .= Skin::build_box(i18n::s('with a menu bar'), DUMMY_TEXT.Skin::build_list($menu_bar, 'menu_bar').DUMMY_TEXT);

// page neighbours
$neighbours = array('#previous', i18n::s('Previous'), '#next', i18n::s('Next'), '#', 'index');

// $context['text'] - section with neighbours
$text .= Skin::build_box(i18n::s('with neighbours'), DUMMY_TEXT.Skin::neighbours($neighbours, 'slideshow'));

// user profile at page bottom
$user = array();
$user['id'] = $user_id;
$user['nick_name'] = 'Geek101';
$user['introduction'] = DUMMY_TEXT;
$text .= Skin::build_profile($user, 'suffix');

// beautify everything and display the result
$context['text'] .= Codes::beautify($text);

// render the skin
render_skin();

?>