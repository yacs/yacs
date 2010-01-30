<?php
/**
 * contribute to a poll
 *
 * If the additional parameter 'next' is provided, this script will silently redirect to it, except on error.
 * Else links are provided either to go to the permalink page, or to the front page.
 *
 * Multiple votes are prevented though cookies.
 * While this method is not bullet-proof, it is an adequate security scheme for most surfers.
 * Each vote has its own cookie, with a reference to the poll id in its name.
 * The cookie path is limited to the YACS instance.
 * Voting cookies have a limited life cycle of one day.
 *
 * On first vote a cookie is sent to the browser with following parameters:
 * - name: 'poll_&lt;id&gt;', where '&lt;id&gt;' designates the target poll
 * - value: the vote itself
 * - path: the network path to this instance of YACS (to avoid side-effects on shared servers)
 * - lifetime: 24 hours (to avoid storing too many cookies on the browser workstation)
 *
 * Of course, the script rejects voting data if a cookie is presented by the browser, proving a vote has already taken place.
 *
 * The script accepts polling data based of the following permission assessment:
 * - associates and editors are allowed to move forward
 * - creator is allowed to view the page
 * - permission is denied if the anchor is not viewable
 * - access is restricted ('active' field == 'R'), but the surfer is an authenticated member
 * - public access is allowed ('active' field == 'Y')
 * - permission denied is the default
 *
 * @see articles/view.php
 *
 *
 * Accept following invocations:
 * - vote.php/12/1
 * - vote.php?id=12&vote=1
 *
 * If the anchor for this item specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @tester Timster
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../../shared/global.php';

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
	$anchor =& Anchors::get($item['anchor']);

// get poll data
include_once '../overlay.php';
$overlay = Overlay::load($item);

// look for the vote
$vote = '';
if(isset($_REQUEST['vote']))
	$vote = $_REQUEST['vote'];
if(isset($context['arguments'][1]))
	$vote = $context['arguments'][1];
$vote = strip_tags($vote);

// next url
$next = '';
if(isset($_REQUEST['next']))
	$next = $_REQUEST['next'];
if(isset($context['arguments'][2]))
	$next = $context['arguments'][2];
$next = strip_tags($next);

//
// is this surfer allowed to browse the resulting page?
//

// associates and editors can do what they want
if(Surfer::is_associate() || Articles::is_assigned($id) || (is_object($anchor) && $anchor->is_assigned()))
	$permitted = TRUE;

// poster can always view the page
elseif(Surfer::get_id() && isset($item['create_id']) && ($item['create_id'] == Surfer::get_id()))
	$permitted = TRUE;

// the anchor has to be viewable by this surfer
elseif(is_object($anchor) && !$anchor->is_viewable())
	$permitted = FALSE;

// access is restricted to authenticated member
elseif(isset($item['active']) && ($item['active'] == 'R') && Surfer::is_member())
	$permitted = TRUE;

// public access is allowed
elseif(isset($item['active']) && ($item['active'] == 'Y'))
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// load the skin, maybe with a variant
load_skin('polls', $anchor);

// the path to this page
$context['path_bar'] = Surfer::get_path_bar($anchor);

// the title of the page
if(isset($item['title']) && $item['title'])
	$context['page_title'] = $item['title'];
else
	$context['page_title'] = i18n::s('Vote for a poll');

// no subject
if(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No item has the provided id.'));

// no overlay
} elseif(!is_object($overlay)) {
	Logger::error(i18n::s('This page has no overlay'));

// not a valid poll
} elseif(!@count($overlay->attributes['answers'])) {
	Logger::error(i18n::s('Not a valid poll'));

// not a valid vote
} elseif(($vote < 1 ) || ($vote > @count($overlay->attributes['answers']))) {
	Logger::error(i18n::s('Not a valid vote'));

// a vote has already been registered
} elseif(isset($_COOKIE['poll_'.$item['id']])) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You have already voted'));

// record the vote
} else {

	// set a cookie to remember the vote for 100 days
	if($id && $vote)
		Safe::setcookie('poll_'.$item['id'], $vote, time()+60*60*24*100, $context['url_to_root']);

	// increment answers
	$vote--;
	list($label, $count) = $overlay->attributes['answers'][$vote];
	$overlay->attributes['answers'][$vote] = array($label, ($count+1));

	// supports up to two levels arrays
	foreach($overlay->attributes as $name => $value) {
		if(is_array($value)) {
			foreach($value as $sub_name => $sub_value)
				$overlay->attributes[$name][$sub_name] = str_replace('\\', '\\\\', $sub_value);
		} else
			$overlay->attributes[$name] = str_replace('\\', '\\\\', $value);
	}

	// update the record
	$item['overlay'] = serialize($overlay->attributes);

	// touch the related anchor
	if($article = $anchor->load_by_content($item, $anchor))
		$article->touch('vote', $item['id'], isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y'));

	// update the database
	if(!Articles::put($item))
		;

	// jump automatically to the next page, if any
	elseif($next && !headers_sent()) {
		Safe::redirect($next);

	// ask for manual click
	} else {
		$context['text'] .= '<p>'.i18n::s('Thank you for your contribution')."</p>\n";

		// link to the poll, depending on access rights
		$menu = array();
		if($permitted)
			$menu = array_merge($menu, array(Articles::get_permalink($item) => i18n::s('View poll results')));

		// back to the front page
		$menu = array_merge($menu, array($context['url_to_root'] => i18n::s('Front page')));
		$context['text'] .= Skin::build_list($menu, 'menu_bar');

	}
}

// render the skin
render_skin();

?>