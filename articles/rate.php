<?php
/**
 * rate a page
 *
 * If the additional parameter 'next' is provided, this script will silently redirect to it, except on error.
 * Else links are provided either to go to the permalink page, or to the front page.
 *
 * Multiple tentatives to rate a page are prevented though cookies.
 * On first rating a cookie is sent to the browser with following parameters:
 * - name: 'rating_&lt;id&gt;', where '&lt;id&gt;' designates the target article
 * - value: the rating itself
 * - path: the network path to this instance of YACS (to avoid side-effects on shared servers)
 * - lifetime: 24 hours (to avoid storing too many cookies on the browser workstation)
 *
 * Of course, the script rejects rating data if a cookie is presented by the browser, proving a similar operation has already taken place.
 *
 * @see articles/view.php
 *
 * Accept following invocations:
 * - rate.php/12/1
 * - rate.php?id=12&rating=1
 *
 * If this article, or one of its anchor, specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Timster
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item = Articles::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor = Anchors::get($item['anchor']);

// look for the rating
$rating = 0;
if(isset($_REQUEST['rating']))
	$rating = $_REQUEST['rating'];
if(isset($context['arguments'][1]))
	$rating = $context['arguments'][1];
$rating = strip_tags($rating);

//
// is this surfer allowed to browse the resulting page?
//

// the anchor has to be viewable by this surfer
if(is_object($anchor) && !$anchor->is_viewable())
	$permitted = FALSE;

// rating has been explicitly disallowed
elseif(is_object($anchor) && $anchor->has_option('without_rating'))
	$permitted = FALSE;

// the default is to allow rating
else
	$permitted = TRUE;

// load the skin, maybe with a variant
load_skin('articles', $anchor, isset($item['options']) ? $item['options'] : '');

// do not crawl this page
$context->sif('robots','noindex');

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// path to this page
$context['path_bar'] = Surfer::get_path_bar($anchor);

// page title
if(isset($item['title']))
	$context['page_title'] = sprintf(i18n::s('Rate: %s'), $item['title']);

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
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Articles::get_url($item['id'], 'rate')));

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// a rating has already been registered
} elseif(isset($_COOKIE['rating_'.$id])) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You have already rated this page.'));

// not a valid rating
} elseif(($rating < 1 ) || ($rating > 5)) {

	// reference the anchor page
	if(is_object($anchor) && $anchor->is_viewable())
		$context['text'] .= '<p>'.sprintf(i18n::s('You are rating: %s'), Skin::build_link(Articles::get_permalink($item), $item['title']))."</p>\n";

	// splash
	$context['text'] .= '<p>'.i18n::s('What do you think of this page?')."</p>\n";

	// a form to capture user rating
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><div>'
		.'<input type="hidden" name="id" value="'.$item['id'].'" />';

	// the page that will be updated
	if(isset($_SERVER['HTTP_REFERER']))
		$context['text'] .= '<input type="hidden" name="referer" value="'.encode_field($_SERVER['HTTP_REFERER']).'" />';

	// give a five
	$context['text'] .= '<div style="float: left;"><input name="rating" type="radio" value="5" onclick="$(\'#main_form\').submit()" /> '.i18n::s('Excellent').' </div> ';

	// give a four
	$context['text'] .= '<div style="float: left;"><input name="rating" type="radio" value="4" onclick="$(\'#main_form\').submit()" /> '.i18n::s('Good').' </div> ';

	// give a three
	$context['text'] .= '<div style="float: left;"><input name="rating" type="radio" value="3" onclick="$(\'#main_form\').submit()" /> '.i18n::s('Average').' </div> ';

	// give a two
	$context['text'] .= '<div style="float: left;"><input name="rating" type="radio" value="2" onclick="$(\'#main_form\').submit()" /> '.i18n::s('Poor').' </div> ';

	// give a one
	$context['text'] .= '<div style="float: left;"><input name="rating" type="radio" value="1" onclick="$(\'#main_form\').submit()" /> '.i18n::s('Forget it').' </div> ';

	$context['text'] .= '<br style="clear: left;" />';

	// end of the form
	$context['text'] .= '</div></form>';

// record the rating
} else {

	// set a cookie to remember the rating for 100 days
	if($rating)
		Safe::setcookie('rating_'.$item['id'], $rating, time()+60*60*24*100, $context['url_to_root']);

	// update the database
	Articles::rate($item['id'], $rating);

	// touch the related anchor
	if(is_object($anchor))
		$anchor->touch('article:update', $item['id'], isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y') );

	// clear the cache
	Articles::clear($item);

	// return from rating
	if(!headers_sent()) {

		// go back to page referring to here
		if(isset($_REQUEST['referer']))
			Safe::redirect($_REQUEST['referer']);

		// go page to rated page
		else
			Safe::redirect(Articles::get_permalink($item));

	// ask for manual click
	} else {
		$context['text'] .= '<p>'.i18n::s('Thank you for your contribution')."</p>\n";

		// follow-up commands
		$menu = array();

		// link to the article, depending on access rights
		if($permitted)
			$menu = array_merge($menu, array(Articles::get_permalink($item) => i18n::s('Back to main page')));

		// back to the front page
		$menu = array_merge($menu, array('index.php#article_'.$item['id'] => i18n::s('Go to the front page')));
		$context['text'] .= Skin::build_list($menu, 'menu_bar');

	}
}

// render the skin
render_skin();

?>
