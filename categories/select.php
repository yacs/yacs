<?php
/**
 * assign categories to any given object
 *
 * This script displays assigned categories to an anchor, and list categories that could be assigned as well.
 *
 * For any assigned category, the surfer:
 * - can unlink the category to the anchor
 * - can assign a sub-category, if any exists
 * - can reuse the category thumbnail in the article, if any
 *
 * Only authenticated members can use this script.
 * Members can only link a page to a category, or refine category choice.
 * Associates can also unlink a page to a category.
 *
 * Accept following invocations:
 * - select.php?anchor=article:12
 * - select.php?anchor=section:32
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Agnes
 * @tester Ddaniel
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'categories.php';

// the anchor associated with a category
$member = NULL;
if(isset($_REQUEST['member']))
	$member = $_REQUEST['member'];
elseif(isset($_REQUEST['anchor']) && isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'GET'))
	$member = $_REQUEST['anchor'];
$member = strip_tags($member);

// get the member object, which is supposed to be a container
$anchor = NULL;
if($member)
	$anchor =& Anchors::get($member);

// do we have the permission to add new categories?
if(Categories::allow_creation($anchor))
	$permitted = TRUE;
else
	$permitted = FALSE;

// load the skin
load_skin('categories');

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'categories/' => i18n::s('Categories') );

// the title of the page
if(is_object($anchor))
	$context['page_title'] = sprintf(i18n::s('Categories for: %s'), $anchor->get_title());
else
	$context['page_title'] = i18n::s('Select categories for this page');

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// not found
} elseif(!is_object($anchor)) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No item has been found.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Categories::get_url($member, 'select')));

	// permission denied to authenticated user
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// build a form to associates some categories to this item
} else {

	// actual update
	if(isset($_REQUEST['anchor']) && isset($_REQUEST['member'])) {

		// on error display the form again
		if($error = Members::toggle($_REQUEST['anchor'], $_REQUEST['member'], isset($_REQUEST['father']) ? $_REQUEST['father'] : ''))
			Logger::error($error);

	}

	// the current list of linked categories
	$categories =& Members::list_categories_by_title_for_member($member, 0, CATEGORIES_LIST_SIZE, 'raw');

	// the form to link additional categories
	if(!is_array($categories) || (count($categories) < CATEGORIES_LIST_SIZE)) {
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'"><div style="margin-bottom: 2em;">'
			.i18n::s('Select a category').' <select name="anchor">'.Categories::get_options($categories).'</select>'
			.' '.Skin::build_submit_button(i18n::s('Categorize'))
			.'<input type="hidden" name="member" value="'.encode_field($member).'">'
			.'</div></form>'."\n";
	}

	// the current list of linked categories
	if(count($categories)) {

		// display attached categories with unlink buttons
		$context['text'] .= '<p>'.i18n::s('All categories that have been associated to this page:').'</p>';

		// browse the list
		foreach($categories as $category_id => $attributes) {

			// make an url
			$url =& Categories::get_permalink($attributes);

			// gather information on this category
			$prefix = $suffix = $type = $icon = '';

			$label = Skin::strip($attributes['title']);

			// add background color to distinguish this category against others
			if(isset($attributes['background_color']) && $attributes['background_color'])
				$label = '<span style="background-color: '.$attributes['background_color'].'; padding: 0 3px 0 3px;">'.$label.'</span>';

			// build a unlink button for this category
			if(Surfer::is_associate()) {
				$suffix .= BR.'<form method="post" action="'.$context['script_url'].'"><div>'
					.'<input type="hidden" name="anchor" value="category:'.$category_id.'" />'
					.'<input type="hidden" name="member" value="'.encode_field($member).'" />'
					.Skin::build_submit_button(i18n::s('Unlink'))
					.'</div></form>';
			}

			// a button to change the thumbnail of the anchored page
			if($icon) {
				$suffix .= ' <form method="post" action="'.$context['url_to_root'].'categories/set_as_thumbnail.php"><div>'
					.'<input type="hidden" name="anchor" value="'.encode_field($member).'" />'
					.'<input type="hidden" name="id" value="'.$category_id.'" />'
					.Skin::build_submit_button(i18n::s('Use this thumbnail as the thumbnail of the page'))
					.'</div></form>';
			}

			// list sub-categories to be linked, if any
			// display active and restricted items
			$where = "categories.active='Y'";
			if(Surfer::is_member())
				$where .= " OR categories.active='R'";
			if(Surfer::is_associate())
				$where .= " OR categories.active='N'";

			// only consider live categories
			$where = '('.$where.')'
				.' AND ((categories.expiry_date is NULL)'
				."	OR (categories.expiry_date <= '".NULL_DATE."') OR (categories.expiry_date > '".$context['now']."'))";

			// limit the query to top level only
			$query = "SELECT categories.id, categories.title "
				." FROM ".SQL::table_name('categories')." AS categories "
				." WHERE (".$where.") AND (categories.anchor='category:".$category_id."')"
				." ORDER BY categories.title";
			$result =& SQL::query($query);
			$sub_categories = array();
			while($result && ($option =& SQL::fetch($result)))
				$sub_categories['category:'.$option['id']] = $option['title'];

			if(count($sub_categories)) {
				$suffix .= '<form method="post" action="'.$context['script_url'].'"><div>'
					.i18n::s('More specific:').' <select name="anchor">';
				foreach($sub_categories as $option_reference => $option_label)
					$suffix .= '<option value="'.$option_reference.'">'.$option_label."</option>\n";
				$suffix .= '</select>'
					.' '.Skin::build_submit_button(" >> ")
					.'<input type="hidden" name="member" value="'.$member.'">'
					.'<input type="hidden" name="father" value="category:'.$category_id.'">'
					.'</div></form>'."\n";
			}

			// format the item
			$new_categories[$url] = array($prefix, $label, $suffix, $type, $icon);

		}

		// display attached categories with unlink buttons
		$context['text'] .= Skin::build_list($new_categories, 'decorated');

	}

	// insert anchor suffix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_suffix();

}

// render the skin
render_skin();

?>