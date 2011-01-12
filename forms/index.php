<?php
/**
 * the index page for forms
 *
 * This is the list of all available forms for this site.
 * Surfers may select a form fo start the filling process.
 * Site associates can do the same, and they can also edit or delete an existing
 * form, or create a new one.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'forms.php';

// which page should be displayed
if(isset($_REQUEST['page']))
	$page = $_REQUEST['page'];
elseif(isset($context['arguments'][0]))
	$page = $context['arguments'][0];
else
	$page = 1;
$page = max(1,intval($page));

// load the skin
load_skin('forms');

// page size
$items_per_page = 50;

// the title of the page
$context['page_title'] = i18n::s('Forms');

// count forms in the database
$stats = Forms::stat();
if($stats['count'])
	$context['page_menu'] += array('_count' => sprintf(i18n::ns('%d form', '%d forms', $stats['count']), $stats['count']));

// stop hackers
if(($page > 1) && (($page - 1) * $items_per_page > $stats['count'])) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

} else {

	// navigation commands for forms, if necessary
	if($stats['count'] > $items_per_page) {
		$home = 'forms/';
		if($context['with_friendly_urls'] == 'Y')
			$prefix = $home.'index.php/';
		elseif($context['with_friendly_urls'] == 'R')
			$prefix = $home;
		else
			$prefix = $home.'?page=';
		$context['page_menu'] += Skin::navigate($home, $prefix, $stats['count'], $items_per_page, $page);
	}
	
	// page main content
	$cache_id = 'forms/index.php#text#'.$page;
	if(!$text =& Cache::get($cache_id)) {
	
		// query the database and layout that stuff
		$offset = ($page - 1) * $items_per_page;
		$text = Forms::list_by_title($offset, $items_per_page);
	
		// we have an array to format
		if(is_array($text))
			$text =& Skin::build_list($text, 'decorated');
	
		// cache this to speed subsequent queries
		Cache::put($cache_id, $text, 'forms');
	}
	$context['text'] .= $text;

}

// page tools
if(Surfer::is_associate())
	$context['page_tools'][] = Skin::build_link('forms/edit.php', i18n::s('Add a form'));

// referrals, if any
$context['components']['referrals'] = Skin::build_referrals('forms/index.php');

// render the skin
render_skin();

?>
