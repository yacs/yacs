<?php
/**
 * stamp an article
 *
 * This script is used to change the review date, the expiry date, or the publishing date.
 *
 * It is used to review old articles from the review queue.
 * Associates and editors have a button 'This article has been reviewed'.
 *
 * Another section of this script offers to expiry the article, or to change the expiry date.
 *
 * The third section allows to change the publishing date.
 * if the article has not been published yet, it offers a link to the publishing script instead.
 *
 * This page is to be used by associates and editors only, while they are reviewing queued articles.
 *
 * Accept following invocations:
 * - stamp.php/12
 * - stamp.php?id=12
 *
 * If this article, or one of its anchor, specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Jan Boen
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
if(isset($item['anchor']))
	$anchor = Anchors::get($item['anchor']);

// do not always show the edition form
$with_form = FALSE;

// load the skin, maybe with a variant
load_skin('articles', $anchor, isset($item['options']) ? $item['options'] : '');

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// path to this page
if(is_object($anchor))
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'articles/' => 'All pages' );

// page title
if(isset($item['title']))
	$context['page_title'] = sprintf(i18n::s('Stamp: %s'), $item['title']);

// not found
if(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No item has the provided id.'));

// publication is restricted to associates and editors
} elseif(!Surfer::is_associate() && (!Surfer::is_member() || !is_object($anchor) || !$anchor->is_editable())) {

	// anonymous users are invited to log in
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Articles::get_url($item['id'], 'stamp')));

	// permission denied to authenticated user
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// review is confirmed
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'review')) {

	// update the database
	if($error = Articles::stamp($item['id']))
		Skin::error($error);

	// post-processing tasks
	else {

		// touch the related anchor
		if(is_object($anchor))
			$anchor->touch('article:update', $item['id'], isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y') );

		// clear the cache
		Articles::clear($item);

		$context['text'] .= '<p>'.i18n::s('The article has been stamped as being reviewed today.')."</p>\n";

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu = array_merge($menu, array(Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name']) => i18n::s('View the page')));
		$menu = array_merge($menu, array('articles/review.php#oldest' => i18n::s('List oldest pages at the review queue')));
		$follow_up .= Skin::build_list($menu, 'page_menu');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	}

// new expiry date
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'expiry')) {

	// convert from surfer time zone to UTC time zone
	if(isset($_REQUEST['expiry_date']) && ($_REQUEST['expiry_date'] > NULL_DATE))
	 	$_REQUEST['expiry_date'] = Surfer::to_GMT($_REQUEST['expiry_date']);

	// reset the expiry date
	if(!$_REQUEST['expiry_date'] || ($_REQUEST['expiry_date'] <= '0000-00-00')) {
		$query = "UPDATE ".SQL::table_name('articles')." SET expiry_date='".NULL_DATE."' WHERE id = ".SQL::escape($item['id']);
		SQL::query($query);

		$context['text'] .= '<p>'.i18n::s('The expiry date has been removed.')."</p>\n";

	// update the database
	} elseif($error = Articles::stamp($item['id'], NULL, $_REQUEST['expiry_date']))
		Skin::error($error);

	else
		$context['text'] .= '<p>'.i18n::s('The expiry date has been successfully changed.')."</p>\n";

	// touch the related anchor
	if(is_object($anchor))
		$anchor->touch('article:update', $item['id'], isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y') );

	// clear the cache
	Articles::clear($item);

	// follow-up commands
	$follow_up = i18n::s('What do you want to do now?');
	$menu = array();
	$menu = array_merge($menu, array(Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name']) => i18n::s('View the page')));
	$menu = array_merge($menu, array('articles/review.php#expired' => i18n::s('Go to the review queue')));
	$follow_up .= Skin::build_list($menu, 'page_menu');
	$context['text'] .= Skin::build_block($follow_up, 'bottom');

// new publication date
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'publish')) {

	// convert from surfer time zone to UTC time zone
	if(isset($_REQUEST['publish_date']) && ($_REQUEST['publish_date'] > NULL_DATE))
	 	$_REQUEST['publish_date'] = Surfer::to_GMT($_REQUEST['publish_date']);

	// invalid date
	if(!isset($_REQUEST['publish_date']) || ($_REQUEST['publish_date'] <= '0000-00-00')) {
		Skin::error(i18n::s('Publication date is invalid.'));

	// update the database
	} elseif($error = Articles::stamp($item['id'], $_REQUEST['publish_date']))
		Skin::error($error);

	// post-processing tasks
	else {

		// touch the related anchor
		if(is_object($anchor))
			$anchor->touch('article:update', $item['id'], isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y') );

		// clear the cache
		Articles::clear($item);

		$context['text'] .= '<p>'.i18n::s('The publication date has been successfully changed.')."</p>\n";

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu = array_merge($menu, array(Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name']) => i18n::s('View the page')));
		$menu = array_merge($menu, array('articles/review.php' => i18n::s('Go to the review queue')));
		$follow_up .= Skin::build_list($menu, 'page_menu');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	}

// display the form on GET
} else
	$with_form = TRUE;

// display the form
if($with_form) {

	// review the article
	$context['text'] .= Skin::build_block(i18n::s('Validate'), 'title');

	// splash
	$context['text'] .= '<p>'.i18n::s('Click on the button below to purge the article from the review queue.').'</p>'."\n";

	// the submit button
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" name="form_1"><div>'."\n"
		.Skin::build_submit_button(i18n::s('I confirm that the article looks ok'))."\n"
		.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n"
		.'<input type="hidden" name="action" value="review" />'."\n"
		.'</div></form>'."\n";

	// expiry the article
	$context['text'] .= Skin::build_block(i18n::s('Expire'), 'title');

	// change the expiry date
	if(isset($item['expiry_date']) && ($item['expiry_date'] > NULL_DATE)) {

		// adjust date from UTC time zone to surfer time zone
		$value = Surfer::from_GMT($item['expiry_date']);

		// a form to change the date
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" name="form_2"><div>'."\n";

		// catch user input
		$context['text'] .= '<p>'.sprintf(i18n::s('Mask the article to ordinary surfers after the %s'), Skin::build_input('expiry_date', $value, 'date_time')).'</p>';

		// the submit button
		$context['text'] .= Skin::build_submit_button(i18n::s('Change the expiry date (YYYY-MM-DD HH:MM)'))."\n"
			.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n"
			.'<input type="hidden" name="action" value="expiry" />'."\n"
			.'</div></form>'."\n";

		// a form to remove the date
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'"><div>'."\n"
			.Skin::build_submit_button(i18n::s('Remove the expiry date'))."\n"
			.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n"
			.'<input type="hidden" name="action" value="expiry" />'."\n"
			.'<input type="hidden" name="expiry_date" value="" />'."\n"
			.'</div></form>'."\n";

	// set a new expiry date
	} else {

		// adjust date from server time zone to surfer time zone
		$value = strftime('%Y-%m-%d %H:%M:%S', time() + ((Surfer::get_gmt_offset() - intval($context['gmt_offset'])) * 3600));

		// a form to change the date
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" name="form_2"><div>'."\n";

		// catch user input
		$context['text'] .= '<p>'.sprintf(i18n::s('Mask the article to ordinary surfers after the %s'), Skin::build_input('expiry_date', $value, 'date_time')).'</p>';

		// the submit button
		$context['text'] .= Skin::build_submit_button(i18n::s('Set an expiry date (YYYY-MM-DD HH:MM)'))."\n"
			.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n"
			.'<input type="hidden" name="action" value="expiry" />'."\n"
			.'</div></form>'."\n";

	}

	// change or delete the publishing date
	$context['text'] .= Skin::build_block(i18n::s('Publication'), 'title');

	// change the publication date
	if(isset($item['publish_date']) && ($item['publish_date'] > NULL_DATE)) {

		// adjust date from server time zone to surfer time zone
		$value = Surfer::from_GMT($item['publish_date']);

		// a form to change the date
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" name="form_3"><div>'."\n";

		// catch user input
		$context['text'] .= '<p>'.sprintf(i18n::s('Change the publication date to %s'), Skin::build_input('publish_date', $value, 'date_time')).'</p>';

		// the submit button
		$context['text'] .= Skin::build_submit_button(i18n::s('Save the new date (YYYY-MM-DD HH:MM)'))."\n"
			.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n"
			.'<input type="hidden" name="action" value="publish" />'."\n"
			.'</div></form>'."\n";

	// set a new publication date
	} else {

		// go to the dedicated page
		$context['text'] .= '<p>'.Skin::build_link(Articles::get_url($item['id'], 'publish'), i18n::s('Publish the page'), 'shortcut').'</p>';

	}

	// cancel
	if(isset($_SERVER['HTTP_REFERER']))
		$referer = $_SERVER['HTTP_REFERER'];
	else
		$referer = 'articles/review.php';
	$menu = array( Skin::build_link($referer, i18n::s('Cancel'), 'span') );
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

}

// render the skin
render_skin();

?>