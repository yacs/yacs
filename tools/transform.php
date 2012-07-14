<?php
/**
 * demonstrate YACS capability to build dynamic pages from XSLT
 *
 * A minimum script based on the YACS framework.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../shared/xml.php';

// load localized strings -- see i18n/i18n.php for more information on internationalization and localization in YACS
i18n::bind('tools');

// load the skin
load_skin('tools');

if(!defined('DUMMY_TEXT'))
	define('DUMMY_TEXT', 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'
		.' Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.'
		.' Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.'
		.' Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');

// the path to this page
$context['path_bar'] = array( 'tools/' => i18n::s('Tools') );

// populate page attributes -- attributes used by YACS are described in skins/test.php
$context['page_title'] = i18n::s('Hello world');

// $context['navigation'] - navigation boxes
$context['navigation'] .= Skin::build_box(i18n::s('navigation').' 1', DUMMY_TEXT, 'navigation');
$context['navigation'] .= Skin::build_box(i18n::s('navigation').' 2', DUMMY_TEXT, 'navigation');

// $context['extra'] - extra boxes
$context['extra'] .= Skin::build_box(i18n::s('extra').' 1', DUMMY_TEXT, 'extra');
$context['extra'] .= Skin::build_box(i18n::s('extra').' 2', DUMMY_TEXT, 'extra');

// $context['page_author'] - the author
$context['page_author'] = 'webmaestro, through some PHP script';

// back to skin index
$context['page_menu'] += array( 'skins/' => i18n::s('Themes') );

// $context['page_publisher'] - the publisher
$context['page_publisher'] = 'webmaestro again, still through some PHP script';

// $context['path_bar'] - back to other sections
$context['path_bar'] = array( 'skins/' => i18n::s('Themes'));

// $context['text'] - some text
$context['text'] .= '<p>'.DUMMY_TEXT.'</p>'.'<p>'.DUMMY_TEXT.'</p>'.'<p>'.DUMMY_TEXT.'</p>';

// do the transformation
$data = xml::load_array($context);
$text = xml::transform($data, 'transform.xsl');

// actual transmission except on a HEAD request
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
	echo $text;

?>