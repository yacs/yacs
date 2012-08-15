<?php
/**
 * list versions available for a given anchor
 *
 * The permission assessment is based upon following rules applied in the provided orders:
 * - associates and editors are allowed to move forward
 * - permission is granted if the anchor is viewable
 * - permission denied is the default
 *
 * Accepted calls:
 * - list.php?id=article:&lt;id&gt;
 * - list.php/&lt;article&gt;/&lt;id&gt;
 *
 * If the anchor for this item specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @tester SonniesEdge
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'versions.php';

// look for the target anchor
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][1]) && $context['arguments'][0] && $context['arguments'][1])
	$id = $context['arguments'][0].':'.$context['arguments'][1];
$id = strip_tags($id);

// get the anchor
$anchor = NULL;
if($id)
	$anchor = Anchors::get($id);

// which page should be displayed
if(isset($_REQUEST['page']))
	$page = $_REQUEST['page'];
elseif(isset($context['arguments'][2]))
	$page = $context['arguments'][2];
else
	$page = 1;
$page = max(1,intval($page));

// only anchor owners can proceed
if(is_object($anchor) && $anchor->is_owned())
	$permitted = TRUE;

// editors of parent anchor can do it also
elseif(is_object($anchor) && ($anchor->get_type() == 'article') && Articles::is_owned(NULL, $anchor))
	$permitted = TRUE;

elseif(is_object($anchor) && ($anchor->get_type() == 'section') && Sections::is_owned(NULL, $anchor))
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// load the skin, maybe with a variant
load_skin('versions', $anchor);

if(!defined('VERSIONS_PER_PAGE'))
	define('VERSIONS_PER_PAGE', 25);

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
// else
//	$context['path_bar'] = array( 'versions/' => i18n::s('Versions') );

// the title of the page
if(is_object($anchor) && $anchor->is_viewable())
	$context['page_title'] = sprintf(i18n::s('Versions: %s'), $anchor->get_title());
else
	$context['page_title'] = i18n::s('List versions');

// an anchor is mandatory
if(!is_object($anchor)) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No anchor has been found.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Versions::get_url($anchor->get_reference(), 'list')));

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// stop hackers
} elseif($page > 10) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// display the index
} else {

	// insert anchor prefix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_prefix();

	// cache the section
	$cache_id = 'versions/list.php?id='.$anchor->get_reference().'#'.$page;
	if(!$text = Cache::get($cache_id)) {

		// section title
		$box['title'] = '';
		$box['bar'] = array();
		$box['text'] = '';

		// count versions in the database
		$stats = Versions::stat_for_anchor($anchor->get_reference());
		$stats['count'] += 1;
		if($stats['count'] > VERSIONS_PER_PAGE) {

			// total count of versions
			$box['bar'] += array('_count' => sprintf(i18n::ns('%d version', '%d versions', $stats['count']), $stats['count']));

			// navigation commands
			$home = 'versions/list.php';
			if($context['with_friendly_urls'] == 'Y')
				$prefix = $home.'/'.str_replace(':', '/', $anchor->get_reference());
			elseif($context['with_friendly_urls'] == 'R')
				$prefix = $home.'/'.str_replace(':', '/', $anchor->get_reference());
			else
				$prefix = $home.'?id='.$anchor->get_reference().'&page=';
			$box['bar'] += Skin::navigate($home, $prefix, $stats['count'], VERSIONS_PER_PAGE, $page);
		}

		// query the database and layout that stuff
		$offset = ($page - 1) * VERSIONS_PER_PAGE;
		if($items = Versions::list_by_date_for_anchor($anchor->get_reference(), $offset, VERSIONS_PER_PAGE, 'full')) {

			// we have an array to format
			if(@count($items)) {
				$items = array_merge(array('_' => sprintf(i18n::s('edited by %s %s'), ucfirst($anchor->get_value('edit_name')), Skin::build_date($anchor->get_value('edit_date')))), $items);
				$box['text'] .= Skin::build_list($items, 'decorated');
			}

			// layout everything in a box
			if(count($box['bar']))
				$box['text'] = Skin::build_list($box['bar'], 'menu_bar')."\n".$box['text'];
			$text =& Skin::build_box($box['title'], $box['text']);

		}

		// cache this to speed subsequent queries
		Cache::put($cache_id, $text, 'versions');
	}
	$context['text'] .= $text;

	// back to the anchor page
	$links = array();
	$links[] = Skin::build_link($anchor->get_url(), i18n::s('Done'), 'button');
	$context['text'] .= Skin::finalize_list($links, 'assistant_bar');

	// insert anchor suffix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_suffix();

	// page help
	$help = '<p>'.i18n::s('Select a version to check differences with the current page. Only the last modification for any given date is saved in the database.').'</p>';
	$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'boxes', 'help');

}

// render the skin
render_skin();

?>
