<?php
/**
 * the index page for images
 *
 * @todo search for images (Alainderieux)
 * @todo allow for import from inbox/images (olivier)
 * @todo allow for flash uploads (viviane)
 * @todo list and suppress images attached to an article images/list.php
 *
 * For a comprehensive description of images, you should check the database abstraction script
 * at [script]images/images.php[/script].
 *
 * This page list images available in the system.
 *
 * Because image records have no active field, as other items of the database, they
 * cannot be protected individually.
 * Because of that only associates can access this page.
 * Other surfers will have to go through related pages to access images.
 * Therefore, images will be protected by any security scheme applying to related pages.
 *
 * Let take for example a image inserted in a page restricted to logged members.
 * Only authenticated users will be able to read the page, and the embedded image as well.
 * Through this index associates will have an additional access link to all images.
 *
 * The main menu has navigation links to browse images by page, for sites that have numerous images.
 *
 * Images are displayed using the default decorated layout.
 *
 * A list of most recent articles is displayed as a sidebar.
 *
 * Accept following invocations:
 * - index.php (view the 20 top images)
 * - index.php/2 (view images 41 to 60)
 * - index.php?page=2 (view images 41 to 60)
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'images.php';

// which page should be displayed
if(isset($_REQUEST['page']))
	$page = $_REQUEST['page'];
elseif(isset($context['arguments'][0]))
	$page = $context['arguments'][0];
else
	$page = 1;
$page = max(1,intval($page));

// load the skin
load_skin('images');

// the maximum number of images per page
if(!defined('IMAGES_PER_PAGE'))
	define('IMAGES_PER_PAGE', 50);

// the title of the page
$context['page_title'] = i18n::s('Images');

// this page is really only for associates
if(!Surfer::is_associate()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// display the index
} else {

	// count images in the database
	$stats = Images::stat();
	if($stats['count'])
		$context['page_menu'] = array_merge($context['page_menu'], array('_count' => sprintf(i18n::ns('%d image', '%d images', $stats['count']), $stats['count'])));

	// navigation commands for images, if necessary
	if($stats['count'] > IMAGES_PER_PAGE) {
		$home = 'images/';
		if($context['with_friendly_urls'] == 'Y')
		$prefix = $home.'index.php/';
		elseif($context['with_friendly_urls'] == 'R')
			$prefix = $home;
		else
			$prefix = $home.'?page=';
		$context['page_menu'] = array_merge($context['page_menu'], Skin::navigate($home, $prefix, $stats['count'], IMAGES_PER_PAGE, $page));
	}

	// page main content
	$cache_id = 'images/index.php#text#'.$page;
	if(!$text =& Cache::get($cache_id)) {

		// query the database and layout that stuff
		$offset = ($page - 1) * IMAGES_PER_PAGE;
		if(!$text = Images::list_by_date($offset, IMAGES_PER_PAGE, 'full'))
			$context['text'] .= '<p>'.i18n::s('No image has been uploaded yet.').'</p>';

		// we have an array to format
		if(is_array($text))
			$text =& Skin::build_list($text, 'rows');

		// cache this to speed subsequent queries
		Cache::put($cache_id, $text, 'images');
	}
	$context['text'] .= $text;

}

// page tools
if(Surfer::is_associate())
	$context['page_tools'][] = Skin::build_link('images/check.php', i18n::s('Maintenance'), 'basic');

// page extra content
$cache_id = 'images/index.php#extra';
if(!$text =& Cache::get($cache_id)) {

	// sidebar with the list of most recent pages
	if($items =& Articles::list_by('publication', 0, COMPACT_LIST_SIZE, 'compact'))
		$text =& Skin::build_box(i18n::s('Recent pages'), Skin::build_list($items, 'compact'), 'extra');

	Cache::put($cache_id, $text, 'articles');
}
$context['aside']['boxes'] = $text;

// referrals, if any
$context['aside']['referrals'] = Skin::build_referrals('images/index.php');

// render the skin
render_skin();

?>