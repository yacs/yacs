<?php
/**
 * download one table using JSON
 *
 * Permission to access the table is denied if the anchor is not viewable by this surfer.
 *
 * Accept following invocations:
 * - get_as_json.php/12
 * - get_as_json.php?id=12
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'tables.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item = Tables::get($id);

// get the related anchor
$anchor = NULL;
if(isset($item['anchor']))
	$anchor = Anchors::get($item['anchor']);

// the anchor has to be viewable by this surfer
if(is_object($anchor) && !$anchor->is_viewable())
	$permitted = FALSE;
else
	$permitted = TRUE;

// load the skin, maybe with a variant
load_skin('tables', $anchor);

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'tables/' => i18n::s('Tables') );

// the title of the page
if(isset($item['title']) && $item['title'])
	$context['page_title'] = $item['title'];
else
	$context['page_title'] = i18n::s('Fetch as JSON');

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// not found
} elseif(!isset($item['id'])) {
	include '../error.php';

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Tables::get_url($item['id'], 'fetch_as_json')));

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// display the table in CSV
} else {

	// force the character set
	$context['charset'] = 'iso-8859-15';

	// render actual table content
	$text = Tables::build($id, 'json');

	//
	// transfer to the user agent
	//

	// handle the output correctly
	render_raw('text/json; charset='.$context['charset']);

	// suggest a download
	if(!headers_sent()) {
		$file_name = utf8::to_ascii(Skin::strip($item['title']).'.json');
		Safe::header('Content-Disposition: attachment; filename="'.str_replace('"', '', $file_name).'"');
	}

	// enable 3-minute caching (3*60 = 180), even through https, to help IE6 on download
	http::expire(180);

	// strong validator
	$etag = '"'.md5($text).'"';

	// manage web cache
	if(http::validate(NULL, $etag))
		return;

	// actual transmission except on a HEAD request
	if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
		echo $text;

	// the post-processing hook, then exit
	finalize_page(TRUE);

}

// render the normal skin in case of error
render_skin();

?>
