<?php
/**
 * edit mutable overlays
 *
 * This script helps to change selected pages through named overlays.
 *
 * It gets an overlay id, plus some pieces of text, and updates all related pages.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../../shared/global.php';

// load localized strings
i18n::bind('overlays');

// load the skin
load_skin('overlays');

// the title of the page
$context['page_title'] = i18n::s('Change named overlays');

// this is reserved to associates
if(!Surfer::is_associate()) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	$context['text'] .= '<p>'.i18n::s('You are not allowed to perform this operation.')."</p>\n";

// update targeted overlays
} elseif(isset($_REQUEST['id']) && $_REQUEST['id']) {
	include_once '../../overlays/overlay.php';

	// change all named overlays
	$count = 0;
	if($ids = Articles::get_ids_for_overlay($_REQUEST['id'])) {
		$context['text'] .= sprintf(i18n::s('Changing all overlays with name %s'), $_REQUEST['id']).BR;

		// one page at a time
		foreach($ids as $id) {

			// load the page and bind the related overlay
			if(($item =& Articles::get($id)) && ($overlay = Overlay::load($item)) && is_callable(array($overlay, 'update'))) {
				$count++;

				// update provided attributes
				$overlay->update($_REQUEST);

				// save content of the overlay in the page
				$item['overlay'] = $overlay->save();
				$item['overlay_id'] = $overlay->get_id();

				// store in the database
				if(Articles::put($item))
					$context['text'] .= sprintf(i18n::s('%s has been changed'), Skin::build_link(Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name']), $item['title']));

				// clear the cache
				Articles::clear($item);
			}
		}

	// no page has been found
	} else
		$context['text'] .= '<p>'.i18n::s('No item has the provided id.').'</p>';

	// report on results
	if($count)
		$context['text'] .= '<p>'.sprintf(i18n::ns('%d page has been processed.', '%d pages have been processed.', $count), $count).'</p>';

	// bottom commands
	$menu = array();
	$menu[] = Skin::build_link('overlays/mutables/edit.php', i18n::s('Edit again'), 'basic');
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

// the form
} else {

	// splash
	$context['text'] .= '<p>'.i18n::s('You will change some attributes of a named overlay.').'</p>';

	// the form to send a query
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>';

	// overlay id
	$label = i18n::s('Overlay identifier').' *';
	$input = '<input type="text" name="id" size="45" value="" maxlength="255" />';
	$fields[] = array($label, $input);

	// the main content
	$label = i18n::s('Overlay content');
	$input = '<textarea name="view_content" rows="10" cols="50"></textarea>';
	$hint = i18n::s('Text inserted after page introduction.');
	$fields[] = array($label, $input, $hint);

	// the trailer
	$label = i18n::s('Overlay trailer');
	$input = '<textarea name="trailer_content" rows="10" cols="50"></textarea>';
	$hint = i18n::s('Text appended at the bottom of the page.');
	$fields[] = array($label, $input, $hint);

	// extra content
	$label = i18n::s('Overlay extra');
	$input = '<textarea name="extra_content" rows="10" cols="50"></textarea>';
	$hint = i18n::s('Text inserted in the side panel.');
	$fields[] = array($label, $input, $hint);

	// build the form
	$context['text'] .= Skin::build_form($fields);

	// bottom commands
	$menu = array();

	// the submit button
	$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');

	// step back
	if(isset($_SERVER['HTTP_REFERER']))
		$menu[] = Skin::build_link($_SERVER['HTTP_REFERER'], i18n::s('Cancel'), 'span');

	// display the menu
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// end of the form
	$context['text'] .= '</div></form>';

	// append the script used for data checking on the browser
	$context['page_footer'] .= '<script type="text/javascript">// <![CDATA['."\n"
		.'// check that main fields are not empty'."\n"
		.'func'.'tion validateDocumentPost(container) {'."\n"
		."\n"
		.'	// edit_name is mandatory'."\n"
		.'	if(!container.id.value) {'."\n"
		.'		alert("'.i18n::s('Please provide an overlay identifier').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		return false;'."\n"
		.'	}'."\n"
		."\n"
		.'	// successful check'."\n"
		.'	return true;'."\n"
		.'}'."\n"
		."\n"
		.'// set the focus on first form field'."\n"
		.'$("id").focus();'."\n"
		.'// ]]></script>'."\n";

}

// render the skin
render_skin();

?>