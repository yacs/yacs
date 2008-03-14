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
 * Of course, the script rejects rating data if a cookie is presented by the browser, proving a similar opeation has already taken place.
 *
 * The script accepts rating data based of the following permission assessment:
 * - permission is denied if the anchor is not visible by the surfer
 * - permission is denied if the anchor hasn't the option 'with_rating'
 * - permission is granted if the surfer is logged
 * - permission is granted if the surfer may handle the item
 * - else rating data is denied
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
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
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
$item =& Articles::get($id);

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

// rating has to be explicitly allowed
elseif(is_object($anchor) && !$anchor->has_option('with_rating'))
	$permitted = FALSE;

// surfer is logged
elseif(Surfer::is_logged())
	$permitted = TRUE;

// surfer may handle this item
elseif(isset($item['handle']) && Surfer::may_handle($item['handle']))
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// load localized strings
i18n::bind('articles');

// load the skin, maybe with a variant
load_skin('articles', $anchor, isset($item['options']) ? $item['options'] : '');

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor))
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'articles/' => 'Articles' );

// the title of the page
if(isset($item['title']) && $item['title'])
	$context['page_title'] = $item['title'];
else
	$context['page_title'] = i18n::s('No title has been provided.');

// common commands for this page
if(isset($_SERVER['HTTP_REFERER']))
	$referer = $_SERVER['HTTP_REFERER'];
else
	$referer = 'articles/review.php';
$context['page_menu'] = array( $referer => i18n::s('Back to the page') );

// not found
if(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No item has the provided id.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Articles::get_url($item['id'], 'rate')));

	// permission denied to authenticated user
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// a rating has already been registered
} elseif(isset($_COOKIE['rating_'.$id])) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You have already rated his page.'));

// not a valid rating
} elseif(($rating < 1 ) || ($rating > 5)) {

	// reference the anchor page
	if(is_object($anchor) && $anchor->is_viewable())
		$context['text'] .= '<p>'.sprintf(i18n::s('You are rating: %s'), Skin::build_link(Articles::get_url($item['id'], 'view', $item['title']), $item['title']))."</p>\n";

	// splash
	$context['text'] .= '<p>'.i18n::s('What do you think of this page?')."</p>\n";

	// a form to capture user rating
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><div>'
		.'<input type="hidden" name="id" value="'.$item['id'].'" '.EOT;

	// give a five
	$context['text'] .= '<div style="float: left;"><input name="rating" type="radio" value="5" onclick="javascript:document.getElementById(\'main_form\').submit()" /> '.i18n::s('Excellent').' </div> ';

	// give a four
	$context['text'] .= '<div style="float: left;"><input name="rating" type="radio" value="4" onclick="javascript:document.getElementById(\'main_form\').submit()" /> '.i18n::s('Good').' </div> ';

	// give a three
	$context['text'] .= '<div style="float: left;"><input name="rating" type="radio" value="3" onclick="javascript:document.getElementById(\'main_form\').submit()" /> '.i18n::s('Average').' </div> ';

	// give a two
	$context['text'] .= '<div style="float: left;"><input name="rating" type="radio" value="2" onclick="javascript:document.getElementById(\'main_form\').submit()" /> '.i18n::s('Poor').' </div> ';

	// give a one
	$context['text'] .= '<div style="float: left;"><input name="rating" type="radio" value="1" onclick="javascript:document.getElementById(\'main_form\').submit()" /> '.i18n::s('Forget it').' </div> ';

	$context['text'] .= '<br style="clear: left;"'.EOT;

	// end of the form
	$context['text'] .= '</div></form>';

// record the rating
} else {

	// set a cookie to remember the rating for 100 days
	if($id && $rating)
		Safe::setcookie('rating_'.$id, $rating, time()+60*60*24*100, $context['url_to_root']);

	// update the database
	Articles::rate($item['id'], $rating);

	// return to the rated page
	if(!headers_sent())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].Articles::get_url($item['id'], 'view', $item['title']));

	// ask for manual click
	else {
		$context['text'] .= '<p>'.i18n::s('Thank you for your rating')."</p>\n";

		// follow-up commands
		$menu = array();

		// link to the article, depending on access rights
		if($permitted)
			$menu = array_merge($menu, array(Articles::get_url($id) => i18n::s('Back to the page')));

		// back to the front page
		$menu = array_merge($menu, array('index.php#article_'.$item['id'] => i18n::s('Go to the front page')));
		$context['text'] .= Skin::build_list($menu, 'menu_bar');

	}
}

// render the skin
render_skin();

?>