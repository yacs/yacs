<?php
/**
 * display one version
 *
 * If several versions have been posted to a single anchor, a navigation bar will be built to jump
 * directly to previous and next neighbours.
 * This is displayed as a sidebar box in the extra panel.
 *
 * The extra panel also features top popular referrals in a sidebar box, if applicable.
 *
 * Restrictions apply on this page:
 * - associates and editors are allowed to move forward
 * - else permission is denied
 *
 * Accept following invocations:
 * - view.php/12
 * - view.php?id=12
 *
 * If the anchor for this item specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'versions.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Versions::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor =& Anchors::get($item['anchor']);

// associates and editors can do what they want
if(Surfer::is_associate() || (is_object($anchor) && $anchor->is_editable()))
	$permitted = TRUE;

// the default is to deny access
else
	$permitted = FALSE;

// load the skin, maybe with a variant
load_skin('versions', $anchor);

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
// else
//	   $context['path_bar'] = array( 'versions/' => i18n::s('Versions') );

// the title of the page
if(is_object($anchor))
	$context['page_title'] = sprintf(i18n::s('Version: %s'), $anchor->get_title());

// back to the anchor page
if(is_object($anchor) && $anchor->is_viewable())
	$context['page_menu'] = array_merge($context['page_menu'], array( $anchor->get_url() => i18n::s('Back to current version') ));

// index of available versions
if((is_object($anchor) && $anchor->is_editable()))
	$context['page_menu'] = array_merge($context['page_menu'], array( Versions::get_url($anchor->get_reference(), 'list') => i18n::s('List versions') ));

// restore command
if($item['id'] && (Surfer::is_associate()
	|| (Surfer::is_member() && is_object($anchor) && $anchor->is_editable())))
	$context['page_menu'] = array_merge($context['page_menu'], array( Versions::get_url($item['id'], 'restore') => i18n::s('Restore this version') ));

// not found -- help web crawlers
if(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No item has the provided id.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Versions::get_url($item['id'])));

	// permission denied to authenticated user
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// display the version
} else {

	// initialize the rendering engine
//	Codes::initialize(Versions::get_url($item['id']));

	// information to members
	if(Surfer::is_member())
		$context['text'] .= '<p>'.sprintf(i18n::s('This version has been posted by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']))."</p>\n";

	// display details for this version
	$context['text'] .= '<dl class="version">'."\n";
	if(($attributes = Safe::unserialize($item['content'])) && @count($attributes)) {
		foreach($attributes as $name => $value) {

			// display changes to the main description field
			if(($name == 'description') && is_object($anchor)) {
				$context['text'] .= '<dt>'.$name.'</dt>'."\n".'<dd>'.$anchor->diff($name, $value).'</dd>'."\n";

			// display previous attribute value
			} elseif(is_string($value) && $value && preg_match('/(active|anchor|description|introduction|rank|title)$/', $name)) {
				$context['text'] .= '<dt>'.$name.'</dt>'."\n".'<dd>'.$value.'</dd>'."\n";
			}
		}
	}
	$context['text'] .= '</dl>'."\n";

	//
	// the navigation sidebar
	//
	$cache_id = 'versions/view.php?id='.$item['id'].'#navigation';
	if(!$text =& Cache::get($cache_id)) {

		// buttons to display previous and next pages, if any
		if(is_object($anchor)) {
			$neighbours = $anchor->get_neighbours('version', $item);
			$text .= Skin::neighbours($neighbours, 'sidebar');
		}

		// build a nice sidebar box
		if($text)
			$text =& Skin::build_box(i18n::s('Navigation'), $text, 'navigation', 'neighbours');

		// save in cache
		Cache::put($cache_id, $text, 'versions');
	}
	$context['extra'] .= $text;

	//
	// referrals, if any, in a sidebar
	//
	if(Surfer::is_associate() || (isset($context['with_referrals']) && ($context['with_referrals'] == 'Y'))) {

		$cache_id = 'versions/view.php?id='.$item['id'].'#referrals#';
		if(!$text =& Cache::get($cache_id)) {

			// box content in a sidebar box
			include_once '../agents/referrals.php';
			if($text = Referrals::list_by_hits_for_url($context['url_to_root_parameter'].Versions::get_url($item['id'])))
				$text =& Skin::build_box(i18n::s('Referrals'), $text, 'navigation', 'referrals');

			// save in cache for one hour 60 * 60 = 3600
			Cache::put($cache_id, $text, 'referrals', 3600);

		}

		// in the extra panel
		$context['extra'] .= $text;

	}
}

// render the skin
render_skin();

?>