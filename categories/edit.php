<?php
/**
 * create a new category or edit an existing one
 *
 * A button-based editor is used for the description field.
 * It's aiming to introduce most common [link=codes]codes/index.php[/link] supported by YACS.
 *
 * This script attempts to validate the new or updated category description against a standard PHP XML parser.
 * The objective is to spot malformed or unordered HTML and XHTML tags. No more, no less.
 *
 * The form displayed allows for easy setting of layouts for various category
 * components. It handles category overlay as well.
 *
 * Optional expiration date is automatically translated from and to the surfer time zone and the
 * server time zone.
 *
 * Restrictions apply on this page:
 * - associates are allowed to create a new category
 * - associates and editors are allowed to modify an existing category
 * - else permission is denied
 *
 * Accepted calls:
 * - edit.php
 * - edit.php?anchor=category:&lt;id&gt;
 * - edit.php/&lt;id&gt;
 * - edit.php?id=&lt;id&gt;
 *
 * @author Bernard Paques
 * @author Vincent No&euml;l
 * @author GnapZ
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @tester Manuel López Gallego
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../shared/xml.php';	// input validation
include_once 'categories.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Categories::get($id);

// get the related anchor, if any --use request first, because anchor can change
$anchor = NULL;
if(isset($_REQUEST['anchor']) && $_REQUEST['anchor'])
	$anchor = Anchors::get($_REQUEST['anchor']);
elseif(isset($item['anchor']) && $item['anchor'])
	$anchor = Anchors::get($item['anchor']);

// get the related overlay, if any
$overlay = NULL;
include_once '../overlays/overlay.php';
if(isset($item['overlay']) && $item['overlay'])
	$overlay = Overlay::load($item);
elseif(isset($item['overlay_id']) && $item['overlay_id'])
	$overlay = Overlay::bind($item['overlay_id']);
elseif(isset($_REQUEST['variant']) && $_REQUEST['variant'])
	$overlay = Overlay::bind($_REQUEST['variant']);
elseif(isset($_REQUEST['overlay_type']) && $_REQUEST['overlay_type'])
	$overlay = Overlay::bind($_REQUEST['overlay_type']);
elseif(isset($_SESSION['variant']) && $_SESSION['variant']) {
	$overlay = Overlay::bind($_SESSION['variant']);
	unset($_SESSION['variant']);
} elseif(!isset($item['id']) && is_object($anchor) && ($overlay_class = $anchor->get_overlay('categories_overlay')))
	$overlay = Overlay::bind($overlay_class);

// associates can do what they want
if(Surfer::is_associate())
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// do not always show the edition form
$with_form = FALSE;

// load the skin
load_skin('categories');

// the path to this page
if(is_object($anchor)&& $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'categories/' => i18n::s('Categories') );

if(isset($item['id']) && isset($item['title']))
	$context['path_bar'] = array_merge($context['path_bar'], array(Categories::get_permalink($item) => $item['title']));

// the title of the page
if($item['title'])
	$context['page_title'] = sprintf(i18n::s('Edit: %s'), $item['title']);
else
	$context['page_title'] = i18n::s('Add a category');

// validate input syntax
if(!Surfer::is_associate() || (isset($_REQUEST['option_validate']) && ($_REQUEST['option_validate'] == 'Y'))) {
	if(isset($_REQUEST['introduction']))
		xml::validate($_REQUEST['introduction']);
	if(isset($_REQUEST['description']))
		xml::validate($_REQUEST['description']);
}

// adjust dates from surfer time zone to UTC time zone
if(isset($_REQUEST['expiry_date']))
	$_REQUEST['expiry_date'] = Surfer::to_GMT($_REQUEST['expiry_date']);

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged()) {

		if(isset($item['id']))
			$link = Categories::get_url($item['id'], 'edit');
		elseif(isset($_REQUEST['anchor']))
			$link = 'categories/edit.php?anchor='.urlencode($_REQUEST['anchor']);
		else
			$link = 'categories/edit.php';

		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode($link));
	}

	// permission denied to authenticated user
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// an error occured
} elseif(count($context['error'])) {
	$item = $_REQUEST;
	$with_form = TRUE;

// update an existing category
} elseif(($item['id']) && isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// limit access rights based on parent heritage, if any
	if(is_object($anchor))
		$_REQUEST['active'] = $anchor->ceil_rights($_REQUEST['active_set']);
	else
		$_REQUEST['active'] = $_REQUEST['active_set'];


	// when the page has been overlaid
	if(is_object($overlay)) {

		// update the overlay from form content
		$overlay->parse_fields($_REQUEST);

		// save content of the overlay in the category itself
		$_REQUEST['overlay'] = $overlay->save();
		$_REQUEST['overlay_id'] = $overlay->get_id();
	}

	// display the form on error
	if(($error = Categories::put($_REQUEST))
			|| (is_object($overlay) && !$overlay->remember('update', $_REQUEST))) {
		Skin::error($error);
		$item = $_REQUEST;
		$with_form = TRUE;

	// else display the updated page
	} else {

		// cascade changes on access rights
		if($_REQUEST['active'] != $item['active'])
			Anchors::cascade('category:'.$item['id'], $_REQUEST['active']);

		// touch the related anchor
		if(is_object($anchor))
			$anchor->touch('category:update', $item['id'], isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y') );

		// clear cache
		Categories::clear($_REQUEST);

		Safe::redirect($context['url_to_home'].$context['url_to_root'].Categories::get_permalink($item));
	}

// post a new category
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// limit access rights based on parent heritage, if any
	if(is_object($anchor))
		$_REQUEST['active'] = $anchor->ceil_rights($_REQUEST['active_set']);
	else
		$_REQUEST['active'] = $_REQUEST['active_set'];

	// when the page has been overlaid
	if(is_object($overlay)) {

		// update the overlay from form content
		$overlay->parse_fields($_REQUEST);

		// save content of the overlay in the category itself
		$_REQUEST['overlay'] = $overlay->save();
		$_REQUEST['overlay_id'] = $overlay->get_id();
	}
	// display the form on error
	if((!$_REQUEST['id'] = Categories::post($_REQUEST))
			|| (is_object($overlay) && !$overlay->remember('insert', $_REQUEST, $_REQUEST['id']))) {
		$item = $_REQUEST;
		$with_form = TRUE;

	// post-processing
	} else {

		// touch the related anchor
		if(is_object($anchor))
			$anchor->touch('category:create', $_REQUEST['id'], isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y'));

		// clear cache
		Categories::clear($_REQUEST);

		// increment the post counter of the surfer
		Users::increment_posts(Surfer::get_id());

		// reward the poster for new posts
		$context['page_title'] = i18n::s('Thank you for your contribution');

		$context['text'] .= '<p>'.i18n::s('Please review the new page carefully and fix possible errors rapidly.').'</p>';

		// get the new item
		$category = Anchors::get('category:'.$_REQUEST['id'], TRUE);

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu = array_merge($menu, array($category->get_url() => i18n::s('View the category')));
		if(Surfer::may_upload())
			$menu = array_merge($menu, array('images/edit.php?anchor='.urlencode('category:'.$_REQUEST['id']) => i18n::s('Add an image')));
		if(preg_match('/\bwith_files\b/i', $category->item['options']) && Surfer::may_upload())
			$menu = array_merge($menu, array('files/edit.php?anchor='.urlencode('category:'.$_REQUEST['id']) => i18n::s('Upload a file')));
		if(!preg_match('/\bno_links\b/i', $category->item['options']))
			$menu = array_merge($menu, array('links/edit.php?anchor='.urlencode('category:'.$_REQUEST['id']) => i18n::s('Add a link')));
		$follow_up .= Skin::build_list($menu, 'page_menu');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	}

// display the form on GET
} else
	$with_form = TRUE;

// display the form
if($with_form) {

	// the form to edit a category
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>';

	// form fields
	$fields = array();

	//
	// index tab - fields that contribute directly to the section index page
	//
	$index = '';

	// the title
	$label = i18n::s('Title').' *';
	$input = '<textarea name="title" id="title" rows="2" cols="50" accesskey="t">'.encode_field(isset($item['title'])?$item['title']:'').'</textarea>';
	$hint = i18n::s('Please provide a meaningful title.');
	$fields[] = array($label, $input, $hint);

	// the introduction
	$label = i18n::s('Introduction');
	$input = '<textarea name="introduction" rows="2" cols="50" accesskey="i">'.encode_field(isset($item['introduction'])?$item['introduction']:'').'</textarea>';
	$hint = i18n::s('Appears in list of categories near the title');
	$fields[] = array($label, $input, $hint);

	// include overlay fields, if any
	if(is_object($overlay)) {

		// append editing fields for this overlay
		$fields = array_merge($fields, $overlay->get_fields($item));

		// remember the overlay type as well
		$context['text'] .= '<input type="hidden" name="overlay_type" value="'.encode_field($overlay->get_type()).'" />';

	}

	// the description
	$label = i18n::s('Description');
	$input = Surfer::get_editor('description', isset($item['description'])?$item['description']:'');
	$fields[] = array($label, $input);

	// append regular fields
	$index .= Skin::build_form($fields);
	$fields = array();

	// splash message for new items
	if(!isset($item['id']))
		$index .= '<p>'.i18n::s('Hit the submit button and post images afterwards.').'</p>';

	// images
	else {
		$box = '';

		if(Surfer::may_upload()) {

			// an horizontal table
			$cells = array();

			// the command to upload a regular image
			$cells[] = Skin::build_link('images/edit.php?anchor='.urlencode('category:'.$item['id']), i18n::s('Add an image'), 'basic')
				.BR.'<span class="details">'.i18n::s('Upload an image file and integrate it into the page. Big images will be rendered as clickable thumbnails.').'</span>';

			// the command to upload page thumbnail
			if(isset($item['thumbnail_url']) && $item['thumbnail_url'])
				$cells[] = Skin::build_link('images/edit.php?anchor='.urlencode('category:'.$item['id']).'&amp;action=thumbnail', i18n::s('Change page thumbnail').BR.'<img src="'.$item['thumbnail_url'].'" alt="" />', 'basic');
			else
				$cells[] = Skin::build_link('images/edit.php?anchor='.urlencode('category:'.$item['id']).'&amp;action=thumbnail', i18n::s('Add page thumbnail'), 'basic')
					.BR.'<span class="details">'.i18n::s('Upload a small image to illustrate this page when it is listed into parent page.').'</span>';

			// the command to upload page icon
			if(isset($item['icon_url']) && $item['icon_url'])
				$cells[] = Skin::build_link('images/edit.php?anchor='.urlencode('category:'.$item['id']).'&amp;action=icon', i18n::s('Change page icon').BR.'<img src="'.preg_replace('/\/images\/article\/[0-9]+\//', "\\0thumbs/", $item['icon_url']).'" alt="" />', 'basic');
			else
				$cells[] = Skin::build_link('images/edit.php?anchor='.urlencode('category:'.$item['id']).'&amp;action=icon', i18n::s('Add page icon'), 'basic')
					.BR.'<span class="details">'.i18n::s('Upload an image to be displayed at page top. This will also be the default icon image for items attached to this page.').'</span>';

			// display all commands
			$box .= Skin::table_prefix('form').'<tr><td style="width: 200px">'.implode('</td><td style="padding-left: 3em; width: 200px">', $cells).'</td></tr></table>';

			// the list of images
			include_once '../images/images.php';
			if($items = Images::list_by_date_for_anchor('category:'.$item['id'], 0, 50)) {

				// help to insert in textarea
				if(!isset($_SESSION['surfer_editor']) || ($_SESSION['surfer_editor'] == 'yacs'))
					$box .= '<p>'.i18n::s('Click on codes to insert images in the page.')."</p>\n";
				else
					$box .= '<p>'.i18n::s('Use codes to insert images in the page.')."</p>\n";

				$box .= Skin::build_list($items, 'decorated');
			}
		}

		// in a folded box
		if($box)
			$index .= Skin::build_box(i18n::s('Images'), $box, 'folder', 'edit_images');

	}

	// layouts for the category page
	//
	$label = i18n::s('Categories');
	if(!isset($item['categories_count']) || ($item['categories_count'] < 1))
		$item['categories_count'] = 5;
	$input = sprintf(i18n::s('List up to %s sub-categories with the following layout:'), '<input type="text" name="categories_count" value="'.encode_field($item['categories_count']).'" size="2" />').BR;
	$custom_layout = '';
	if(!isset($item['categories_layout']) || !$item['categories_layout'])
		$item['categories_layout'] = 'decorated';
	elseif(!preg_match('/(compact|cloud|decorated|inline|yahoo|none)/', $item['categories_layout'])) {
		$custom_layout = $item['categories_layout'];
		$item['categories_layout'] = 'custom';
	}
	$input .= BR.'<input type="radio" name="categories_layout" value="decorated"';
	if($item['categories_layout'] == 'decorated')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('decorated - List these categories on one column, in the middle of the page.');

	$input .= BR.'<input type="radio" name="categories_layout" value="yahoo"';
	if($item['categories_layout'] == 'yahoo')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('yahoo - List these categories on two columns, like Yahoo!');

	$input .= BR.'<input type="radio" name="categories_layout" value="inline"';
	if($item['categories_layout'] == 'inline')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('inline - List sub-categories and related articles.');

	$input .= BR.'<input type="radio" name="categories_layout" value="compact"';
	if($item['categories_layout'] == 'compact')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('compact - In a compact list, like DMOZ.');

	$input .= BR.'<input type="radio" name="categories_layout" value="cloud"';
	if($item['categories_layout'] == 'cloud')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('clouds - List sub-categories as clouds.');

	$input .= BR.'<input type="radio" name="categories_layout" value="custom"';
	if($item['categories_layout'] == 'custom')
		$input .= ' checked="checked"';
	$input .= '/> '.sprintf(i18n::s('Use the customized layout %s'), '<input type="text" name="categories_custom_layout" value="'.encode_field($custom_layout).'" size="32" />');
	$input .= BR.'<input type="radio" name="categories_layout" value="none"';
	if($item['categories_layout'] == 'none')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Do not list sub-categories.').BR;
	$fields[] = array($label, $input);

	// layout for  related sections - 'compact', 'decorated', 'freemind', 'folded', 'inline', 'jive', 'map', 'titles', 'yabb', 'none' or custom - default is decorated
	$label = i18n::s('Sections');
	if(!isset($item['sections_count']) || ($item['sections_count'] < 1))
		$item['sections_count'] = SECTIONS_PER_PAGE;
	$input = sprintf(i18n::s('List up to %s sections with the following layout:'), '<input type="text" name="sections_count" value="'.encode_field($item['sections_count']).'" size="2" />').BR;
	$custom_layout = '';
	if(!isset($item['sections_layout']) || !$item['sections_layout'])
		$item['sections_layout'] = 'map';
	elseif(!preg_match('/(compact|decorated|folded|freemind|inline|jive|map|titles|yabb|none)/', $item['sections_layout'])) {
		$custom_layout = $item['sections_layout'];
		$item['sections_layout'] = 'custom';
	}
	$input .= '<input type="radio" name="sections_layout" value="decorated"';
	if($item['sections_layout'] == 'decorated')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('decorated - As a decorated list.')
		.BR.'<input type="radio" name="sections_layout" value="map"';
	if($item['sections_layout'] == 'map')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('map - Map in two columns, like Yahoo!')
		.BR.'<input type="radio" name="sections_layout" value="freemind"';
	if($item['sections_layout'] == 'freemind')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('freemind - Build an interactive mind map')
		.BR.'<input type="radio" name="sections_layout" value="jive"';
	if($item['sections_layout'] == 'jive')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('jive - List 5 threads per board')
		.BR.'<input type="radio" name="sections_layout" value="yabb"';
	if($item['sections_layout'] == 'yabb')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('yabb - A discussion forum')
		.BR.'<input type="radio" name="sections_layout" value="inline"';
	if($item['sections_layout'] == 'inline')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('inline - List sections and related articles.')
		.BR.'<input type="radio" name="sections_layout" value="folded"';
	if($item['sections_layout'] == 'folded')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('folded - List sections as folded boxes, with content (one box per section).')
		.BR.'<input type="radio" name="sections_layout" value="compact"';
	if($item['sections_layout'] == 'compact')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('compact - In a compact list, like DMOZ.')
		.BR.'<input type="radio" name="sections_layout" value="titles"';
	if($item['sections_layout'] == 'titles')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('titles - Use only titles and thumbnails.')
		.BR.'<input type="radio" name="sections_layout" value="custom"';
	if($item['sections_layout'] == 'custom')
		$input .= ' checked="checked"';
	$input .= '/> '.sprintf(i18n::s('Use the customized layout %s'), '<input type="text" name="sections_custom_layout" value="'.encode_field($custom_layout).'" size="32" />')
		.BR.'<input type="radio" name="sections_layout" value="none"';
	if($item['sections_layout'] == 'none')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Do not list sections.');
	$fields[] = array($label, $input);

	// layout for related articles
	$label = i18n::s('Pages');
	$input = i18n::s('List recent pages using the following layout:').BR;
	$custom_layout = '';
	if(!isset($item['articles_layout']) || !$item['articles_layout'])
		$item['articles_layout'] = 'decorated';
	elseif(!preg_match('/(alistapart|boxesandarrows|compact|daily|decorated|digg|jive|manual|map|none|slashdot|table|wiki|yabb)/', $item['articles_layout'])) {
		$custom_layout = $item['articles_layout'];
		$item['articles_layout'] = 'custom';
	}
	$input .= '<input type="radio" name="articles_layout" value="decorated"';
	if($item['articles_layout'] == 'decorated')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('decorated - A decorated list of most recent pages');
	$input .= BR.'<input type="radio" name="articles_layout" value="digg"';
	if($item['articles_layout'] == 'digg')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('digg - To order pages by rating');
	$input .= BR.'<input type="radio" name="articles_layout" value="slashdot"';
	if($item['articles_layout'] == 'slashdot')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('slashdot - List most recent pages equally');
	$input .= BR.'<input type="radio" name="articles_layout" value="map"';
	if($item['articles_layout'] == 'map')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('map - Map in two columns, like Yahoo!');
	$input .= BR.'<input type="radio" name="articles_layout" value="table"';
	if($item['articles_layout'] == 'table')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('table - A table of recent pages');
	$input .= BR.'<input type="radio" name="articles_layout" value="daily"';
	if($item['articles_layout'] == 'daily')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('daily - A list of stamped pages (blog)');
	$input .= BR.'<input type="radio" name="articles_layout" value="boxesandarrows"';
	if($item['articles_layout'] == 'boxesandarrows')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('boxesandarrows - Click on titles to read articles');
	$input .= BR.'<input type="radio" name="articles_layout" value="jive"';
	if($item['articles_layout'] == 'jive')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('jive - Display most of articles content');
	$input .= BR.'<input type="radio" name="articles_layout" value="yabb"';
	if($item['articles_layout'] == 'yabb')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('yabb - A discussion board');
	$input .= BR.'<input type="radio" name="articles_layout" value="alistapart"';
	if($item['articles_layout'] == 'alistapart')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('alistapart - Display entirely the last published page');
	$input .= BR.'<input type="radio" name="articles_layout" value="wiki"';
	if($item['articles_layout'] == 'wiki')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('wiki - A set of editable and extensible pages');
	$input .= BR.'<input type="radio" name="articles_layout" value="manual"';
	if($item['articles_layout'] == 'manual')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('manual - A hierarchy of article titles');
	$input .= BR.'<input type="radio" name="articles_layout" value="compact"';
	if($item['articles_layout'] == 'compact')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('compact - A compact list of items');
	$input .= BR.'<input type="radio" name="articles_layout" value="custom"';
	if($item['articles_layout'] == 'custom')
		$input .= ' checked="checked"';
	$input .= '/> '.sprintf(i18n::s('Use the customized layout %s'), '<input type="text" name="articles_custom_layout" value="'.encode_field($custom_layout).'" size="32" />');
	$input .= BR.'<input type="radio" name="articles_layout" value="none"';
	if($item['articles_layout'] == 'none')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Do not display articles.').BR;
	$fields[] = array($label, $input);

	// options
	$label = i18n::s('Rendering');
	$input = '<input type="text" name="options" id="options" size="55" value="'.encode_field(isset($item['options']) ? $item['options'] : '').'" maxlength="255" accesskey="o" />'
		.'<script type="text/javascript">// <![CDATA['."\n"
		.'function append_to_options(keyword) {'."\n"
		.'	var target = $("options");'."\n"
		.'	target.value = target.value + " " + keyword;'."\n"
		.'}'."\n"
		.'// ]]></script>'."\n";
	$keywords = array();
	$keywords[] = '<a onclick="append_to_options(\'articles_by_title\')" style="cursor: pointer;">articles_by_title</a> - '.i18n::s('Sort pages by title');
	$keywords[] = '<a onclick="append_to_options(\'with_files\')" style="cursor: pointer;">with_files</a> - '.i18n::s('Files can be added to the index page');
	$keywords[] = '<a onclick="append_to_options(\'files_by_title\')" style="cursor: pointer;">files_by_title</a> - '.i18n::s('Sort files by title (and not by date)');
	$keywords[] = '<a onclick="append_to_options(\'with_links\')" style="cursor: pointer;">with_links</a> - '.i18n::s('Links can be added to the index page');
	$keywords[] = '<a onclick="append_to_options(\'links_by_title\')" style="cursor: pointer;">links_by_title</a> - '.i18n::s('Sort links by title (and not by date)');
	$keywords[] = '<a onclick="append_to_options(\'with_comments\')" style="cursor: pointer;">with_comments</a> - '.i18n::s('The index page itself is a thread');
	$keywords[] = 'skin_foo_bar - '.i18n::s('Apply a specific skin (in skins/foo_bar) here');
	$keywords[] = 'variant_foo_bar - '.i18n::s('To load template_foo_bar.php instead of the regular skin template');
	$hint = i18n::s('You may combine several keywords:').Skin::finalize_list($keywords, 'compact');
	$fields[] = array($label, $input, $hint);

	// trailer information
	$label = i18n::s('Trailer');
	$input = Surfer::get_editor('trailer', isset($item['trailer'])?$item['trailer']:'');
	$hint = i18n::s('Text to be appended at the bottom of the page, after all other elements attached to this page.');
	$fields[] = array($label, $input, $hint);

	// extra information
	$label = i18n::s('Extra');
	$input = '<textarea name="extra" rows="6" cols="50">'.encode_field(isset($item['extra']) ? $item['extra'] : '').'</textarea>';
	$hint = i18n::s('Text to be inserted in the panel aside the page.');
	$fields[] = array($label, $input, $hint);

	// append fields
	$index .= Skin::build_box(i18n::s('More content'), Skin::build_form($fields), 'folder');
	$fields = array();

	//
	// content tab - management of the content tree
	//
	$content = '';

	// this category
	//

	// the parent category
	if(Surfer::is_associate() && (!isset($item['nick_name']) || !preg_match('/^(week|month)/', $item['nick_name']))) {
		$label = i18n::s('Parent category');
		$to_avoid = NULL;
		if(isset($item['id']))
			$to_avoid = 'category:'.$item['id'];
		$to_select = NULL;
		if(isset($item['anchor']))
			$to_select = $item['anchor'];
		elseif(isset($_REQUEST['anchor']))
			$to_select = $_REQUEST['anchor'];
		$input = '<select name="anchor">'.Categories::get_options($to_avoid, $to_select).'</select>';
		$hint = i18n::s('Please carefully select a parent category.');
		$fields[] = array($label, $input, $hint);
	} elseif(is_object($anchor))
		$context['text'] .= '<input type="hidden" name="anchor" value="'.$anchor->get_reference().'" />';

	// the nick name
	$label = i18n::s('Nick name');
	$input = '<input type="text" name="nick_name" size="32" value="'.encode_field(isset($item['nick_name'])?$item['nick_name']:'').'" maxlength="64" accesskey="n" />';
	$hint = i18n::s('To designate a category by name');
	$fields[] = array($label, $input, $hint);

	// the keywords
	$label = i18n::s('Keyword');
	$input = '<input type="text" name="keywords" size="32" value="'.encode_field(isset($item['keywords'])?$item['keywords']:'').'" maxlength="255" />';
	$hint = i18n::s('To relate this category to some search pattern');
	$fields[] = array($label, $input, $hint);

	// fields related to this category
	$content .= Skin::build_form($fields);
	$fields = array();

	// categories overlay
	$label = i18n::s('Category overlay');
	$input = '<input type="text" name="categories_overlay" size="20" value="'.encode_field(isset($item['categories_overlay']) ? $item['categories_overlay'] : '').'" maxlength="128" />';
	$hint = sprintf(i18n::s('Script used to %s this category'), Skin::build_link('overlays/', i18n::s('overlay'), 'help'));
	$fields[] = array($label, $input, $hint);

	// the prefix
	$label = i18n::s('Prefix');
	$input = '<textarea name="prefix" rows="2" cols="50">'.encode_field(isset($item['prefix']) ? $item['prefix'] : '').'</textarea>';
	$hint = i18n::s('To be inserted at the top of related pages.');
	$fields[] = array($label, $input, $hint);

	// the suffix
	$label = i18n::s('Suffix');
	$input = '<textarea name="suffix" rows="2" cols="50">'.encode_field(isset($item['suffix']) ? $item['suffix'] : '').'</textarea>';
	$hint = i18n::s('To be inserted at the bottom of related pages.');
	$fields[] = array($label, $input, $hint);

	// sub-categories
	$content .= Skin::build_box(i18n::s('Sub-categories'), Skin::build_form($fields), 'folder');
	$fields = array();

	// excerpts
	if(!isset($item['nick_name']) || strcmp($item['nick_name'], i18n::c('featured'))) {
		$input = i18n::s('YACS can list most recent items').BR;
		$input .= '<input type="radio" name="display" value="site:all"';
		if(isset($item['display']) && ($item['display'] == 'site:all'))
			$input .= ' checked="checked"';
		$input .= '/> '.i18n::s('at all pages, among other navigation boxes')
			.BR.'<input type="radio" name="display" value="home:gadget"';
		if(isset($item['display']) && ($item['display'] == 'home:gadget'))
			$input .= ' checked="checked"';
		$input .= '/> '.i18n::s('in the middle of the front page, among other gadget boxes')
			.BR.'<input type="radio" name="display" value="home:extra"';
		if(isset($item['display']) && ($item['display'] == 'home:extra'))
			$input .= ' checked="checked"';
		$input .= '/> '.i18n::s('at the front page, among other extra boxes')
			.BR.'<input type="radio" name="display" value="section:index"';
		if(isset($item['display']) && ($item['display'] == 'section:index'))
			$input .= ' checked="checked"';
		$input .= '/> '.i18n::s('at site map')
			.BR.'<input type="radio" name="display" value="article:index"';
		if(isset($item['display']) && ($item['display'] == 'article:index'))
			$input .= ' checked="checked"';
		$input .= '/> '.i18n::s('at articles index')
			.BR.'<input type="radio" name="display" value="file:index"';
		if(isset($item['display']) && ($item['display'] == 'file:index'))
			$input .= ' checked="checked"';
		$input .= '/> '.i18n::s('at files index')
			.BR.'<input type="radio" name="display" value="link:index"';
		if(isset($item['display']) && ($item['display'] == 'link:index'))
			$input .= ' checked="checked"';
		$input .= '/> '.i18n::s('at links index')
			.BR.'<input type="radio" name="display" value="user:index"';
		if(isset($item['display']) && ($item['display'] == 'user:index'))
			$input .= ' checked="checked"';
		$input .= '/> '.i18n::s('at users index')
			.BR.'<input type="radio" name="display" value="site:none"';
		if(!isset($item['display']) || !$item['display'] || ($item['display'] == 'site:none'))
			$input .= ' checked="checked"';
		$input .= '/> '.i18n::s('No, thank you to not handle excerpts for this category').' ';

		// excerpts
		$content .= Skin::build_box(i18n::s('Excerpts'), $input, 'folder');
	}

	//
	// options tab
	//
	$options = '';

	// the active flag: Yes/public, Restricted/logged, No/associates --we don't care about inheritance, to enable security changes afterwards
	$label = i18n::s('Visibility');

	// maybe a public page
	$input = '<input type="radio" name="active_set" value="Y" accesskey="v"';
	if(!isset($item['active_set']) || ($item['active_set'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Anyone may read this category').BR;

	// maybe a restricted page
	$input .= '<input type="radio" name="active_set" value="R"';
	if(isset($item['active_set']) && ($item['active_set'] == 'R'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Access is restricted to authenticated members').BR;

	// or a hidden page
	$input .= '<input type="radio" name="active_set" value="N"';
	if(isset($item['active_set']) && ($item['active_set'] == 'N'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Access is restricted to associates');

	$fields[] = array($label, $input);

	// the rank
	if(!isset($item['rank']) || !$item['rank'])
		$item['rank'] = 10000;
	$label = i18n::s('Rank');
	$input = '<input type="text" name="rank" size="10" value="'.encode_field($item['rank']).'" maxlength="255" />';
	$hint = i18n::s('Regular categories are ranked at 10000.');
	$fields[] = array($label, $input, $hint);

	// the expiry date, if any
	$label = i18n::s('Expiry date');

	// adjust date from UTC time zone to surfer time zone
	$value = '';
	if(isset($item['expiry_date']) && ($item['expiry_date'] > NULL_DATE))
		$value = Surfer::from_GMT($item['expiry_date']);

	$input = Skin::build_input('expiry_date', $value, 'date_time');
	$hint = i18n::s('Let the server hide categories on dead-lines - automatically.');
	$fields[] = array($label, $input, $hint);

	// append fields
	$options .= Skin::build_form($fields);
	$fields = array();

	// the thumbnail url may be set after the page has been created
	if(isset($item['id'])) {
		$label = i18n::s('Thumbnail URL');
		$input = '<input type="text" name="thumbnail_url" size="55" value="'.encode_field($item['thumbnail_url']).'" maxlength="255" />';
		if(Surfer::may_upload())
			$input .= ' <span class="details">'.Skin::build_link('images/edit.php?anchor='.urlencode('category:'.$item['id']).'&amp;action=thumbnail', i18n::s('Add an image'), 'basic').'</span>';
		$hint = i18n::s('The image that illustrates the page at parent level.');
		$fields[] = array($label, $input, $hint);
	}

	// the icon url may be set after the page has been created
	if(isset($item['id'])) {
		$label = i18n::s('Icon URL');
		$input = '<input type="text" name="icon_url" size="55" value="'.encode_field($item['icon_url']).'" maxlength="255" />';
		if(Surfer::may_upload())
			$input .= ' <span class="details">'.Skin::build_link('images/edit.php?anchor='.urlencode('category:'.$item['id']).'&amp;action=icon', i18n::s('Add an image'), 'basic').'</span>';
		$hint = i18n::s('The image displayed at page top, and used as the default icon for related items.');
		$fields[] = array($label, $input, $hint);
	}

	// the bullet url may be set after the page has been created
	if(isset($item['id'])) {
		$label = i18n::s('Bullet URL');
		$input = '<input type="text" name="bullet_url" size="55" value="'.encode_field($item['bullet_url']).'" maxlength="255" />';
		if(Surfer::may_upload())
			$input .= ' <span class="details">'.Skin::build_link('images/edit.php?anchor='.urlencode('category:'.$item['id']).'&amp;action=bullet', i18n::s('Add an image'), 'basic').'</span>';
		$hint = i18n::s('The default image that illustrates every related item.');
		$fields[] = array($label, $input, $hint);
	}

	// more options
	$options .= Skin::build_box(i18n::s('More options'), Skin::build_form($fields), 'folder');
	$fields = array();

	//
	// assemble all tabs
	//
	$all_tabs = array(
		array('index_tab', i18n::s('Index page'), 'index_panel', $index),
		array('content_tab', i18n::s('Content tree'), 'content_panel', $content),
		array('options_tab', i18n::s('Options'), 'options_panel', $options)
		);

	// let YACS do the hard job
	$context['text'] .= Skin::build_tabs($all_tabs);

	//
	// bottom commands
	//
	$menu = array();

	// the submit button
	$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');

	// cancel button
	if(isset($item['id']))
		$menu[] = Skin::build_link(Categories::get_permalink($item), i18n::s('Cancel'), 'span');

	// insert the menu in the page
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// several options to check
	$input = array();

	// do not stamp edition date -- complex command
	if(Surfer::is_empowered() && isset($item['id']) && Surfer::has_all())
		$input[] = '<input type="checkbox" name="silent" value="Y" /> '.i18n::s('Do not change modification date.');

	// validate page content
	if(Surfer::is_empowered())
		$input[] = '<input type="checkbox" name="option_validate" value="Y" checked="checked" /> '.i18n::s('Ensure this post is valid XHTML.');

	// append post-processing options
	if($input)
		$context['text'] .= '<p>'.implode(BR, $input).'</p>';

	// transmit the id as a hidden field
	if(isset($item['id']) && $item['id'])
		$context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'" />';

	// end of the form
	$context['text'] .= '</div></form>';

	// append the script used for data checking on the browser
	$context['page_footer'] .= '<script type="text/javascript">// <![CDATA['."\n"
		.'// check that main fields are not empty'."\n"
		.'func'.'tion validateDocumentPost(container) {'."\n"
		."\n"
		.'	// title is mandatory'."\n"
		.'	if(!container.title.value) {'."\n"
		.'		alert("'.i18n::s('Please provide a meaningful title.').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		return false;'."\n"
		.'	}'."\n"
		."\n"
		.'	// successful check'."\n"
		.'	return true;'."\n"
		.'}'."\n"
		."\n"
		.'// detect changes in form'."\n"
		.'func'.'tion detectChanges() {'."\n"
		."\n"
		.'	var nodes = $$("form#main_form input");'."\n"
		.'	for(var index = 0; index < nodes.length; index++) {'."\n"
		.'		var node = nodes[index];'."\n"
		.'		Event.observe(node, "change", function() { $("preferred_editor").disabled = true; });'."\n"
		.'	}'."\n"
		."\n"
		.'	nodes = $$("form#main_form textarea");'."\n"
		.'	for(var index = 0; index < nodes.length; index++) {'."\n"
		.'		var node = nodes[index];'."\n"
		.'		Event.observe(node, "change", function() { $("preferred_editor").disabled = true; });'."\n"
		.'	}'."\n"
		."\n"
		.'	nodes = $$("form#main_form select");'."\n"
		.'	for(var index = 0; index < nodes.length; index++) {'."\n"
		.'		var node = nodes[index];'."\n"
		.'		Event.observe(node, "change", function() { $("preferred_editor").disabled = true; });'."\n"
		.'	}'."\n"
		.'}'."\n"
		."\n"
		.'// observe changes in form'."\n"
		.'Event.observe(window, "load", detectChanges);'."\n"
		."\n"
		.'// set the focus on first form field'."\n"
		.'$("title").focus();'."\n"
		.'// ]]></script>'."\n";

	// content of the help box
	$help = '';

	// html and codes
	$help .= '<p>'.sprintf(i18n::s('%s and %s are available to enhance text rendering.'), Skin::build_link('codes/', i18n::s('YACS codes'), 'help'), Skin::build_link('smileys/', i18n::s('smileys'), 'help')).'</p>';

 	// locate mandatory fields
 	$help .= '<p>'.i18n::s('Mandatory fields are marked with a *').'</p>';

 	// change to another editor
	$help .= '<form action=""><p><select name="preferred_editor" id="preferred_editor" onchange="Yacs.setCookie(\'surfer_editor\', this.value); window.location = window.location;">';
	$selected = '';
	if(!isset($_SESSION['surfer_editor']) || ($_SESSION['surfer_editor'] == 'tinymce'))
		$selected = ' selected="selected"';
	$help .= '<option value="tinymce"'.$selected.'>'.i18n::s('TinyMCE')."</option>\n";
	$selected = '';
	if(isset($_SESSION['surfer_editor']) && ($_SESSION['surfer_editor'] == 'fckeditor'))
		$selected = ' selected="selected"';
	$help .= '<option value="fckeditor"'.$selected.'>'.i18n::s('FCKEditor')."</option>\n";
	$selected = '';
	if(isset($_SESSION['surfer_editor']) && ($_SESSION['surfer_editor'] == 'yacs'))
		$selected = ' selected="selected"';
	$help .= '<option value="yacs"'.$selected.'>'.i18n::s('Textarea')."</option>\n";
	$help .= '</select></p></form>';

	// in a side box
	$context['extra'] .= Skin::build_box(i18n::s('Help'), $help, 'navigation', 'help');

}

// render the skin
render_skin();

?>