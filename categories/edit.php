<?php
/**
 * create a new category or edit an existing one
 *
 * A button-based editor is used for the description field.
 * It's aiming to introduce most common [link=codes]codes/[/link] supported by YACS.
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
 * @author Alexis Raimbault
<<<<<<< HEAD
 * @tester Manuel Lopez Gallego
=======
 * @tester Manuel L�pez Gallego
>>>>>>> next
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../images/images.php';
include_once 'categories.php';

// load jscolor library
$context['javascript']['jscolor'] = TRUE;

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item = Categories::get($id);

// get the related anchor, if any --use request first, because anchor can change
$anchor = NULL;
if(isset($_REQUEST['anchor']) && $_REQUEST['anchor'])
	$anchor = Anchors::get($_REQUEST['anchor']);
elseif(isset($item['anchor']) && $item['anchor'])
	$anchor = Anchors::get($item['anchor']);

// reflect access rights from anchor
if(!isset($item['active']) && is_object($anchor))
	$item['active'] = $anchor->get_active();

// get the related overlay, if any
$overlay = NULL;
if(isset($item['overlay']) && $item['overlay'])
	$overlay = Overlay::load($item, 'category:'.$item['id']);
elseif(isset($item['overlay_id']) && $item['overlay_id'])
	$overlay = Overlay::bind($item['overlay_id']);
elseif(isset($_REQUEST['variant']) && $_REQUEST['variant'])
	$overlay = Overlay::bind($_REQUEST['variant']);
elseif(isset($_SESSION['variant']) && $_SESSION['variant']) {
	$overlay = Overlay::bind($_SESSION['variant']);
	unset($_SESSION['variant']);
} elseif(!isset($item['id']) && is_object($anchor))
	$overlay = $anchor->get_overlay('categories_overlay');

// associates can do what they want
if(Surfer::is_associate())
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;


global $render_overlaid;
$whole_rendering = !$render_overlaid;

// do not always show the edition form
$with_form = FALSE;

// load the skin
load_skin('categories');

// the path to this page
if($whole_rendering) {
    if(is_object($anchor)&& $anchor->is_viewable())
	    $context['path_bar'] = $anchor->get_path_bar();
    else
	    $context['path_bar'] = array( 'categories/' => i18n::s('Categories') );

    if(isset($item['id']) && isset($item['title']))
	    $context['path_bar'] = array_merge($context['path_bar'], array(Categories::get_permalink($item) => $item['title']));
}

// the title of the page
if(isset($item['title']))
	$context['page_title'] = sprintf(i18n::s('Edit: %s'), $item['title']);
elseif(!is_object($overlay) || (!$context['page_title'] = $overlay->get_label('page_title', 'new')))
	$context['page_title'] = i18n::s('Add a category');

// validate input syntax
if(isset($_REQUEST['option_validate']) && ($_REQUEST['option_validate'] == 'Y')) {
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
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

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
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// an error occured
} elseif(count($context['error'])) {
	$item = $_REQUEST;
	$with_form = TRUE;

// update an existing category
} elseif(isset($item['id']) && isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// limit access rights based on parent heritage, if any
	if(is_object($anchor))
		$_REQUEST['active'] = $anchor->ceil_rights($_REQUEST['active_set']);
	else
		$_REQUEST['active'] = $_REQUEST['active_set'];
	
	
	// overlay may have changed
	if(isset($_REQUEST['overlay_type']) && $_REQUEST['overlay_type']) {

		// associates are allowed to change overlay types -- see overlays/select.php
		if(!Surfer::is_associate() && isset($_REQUEST['id']))
			unset($_REQUEST['overlay_type']);

		// overlay type has not changed
		elseif(is_object($overlay) && ($overlay->get_type() == $_REQUEST['overlay_type']))
			unset($_REQUEST['overlay_type']);
	}
	
	// new overlay type
	if(isset($_REQUEST['overlay_type']) && $_REQUEST['overlay_type']) {

		// delete the previous version, if any
		if(is_object($overlay) && isset($_REQUEST['id']))
			$overlay->remember('delete', $_REQUEST, 'category:'.$_REQUEST['id']);

		// new version of page overlay
		$overlay = Overlay::bind($_REQUEST['overlay_type']);
	}
	

	// when the page has been overlaid
	if(is_object($overlay)) {

		// allow for change detection
		$overlay->snapshot();

		// update the overlay from form content
		$overlay->parse_fields($_REQUEST);

		// save content of the overlay in the category itself
		$_REQUEST['overlay'] = $overlay->save();
		$_REQUEST['overlay_id'] = $overlay->get_id();
	}

	// display the form on error
	if(($error = Categories::put($_REQUEST))
			|| (is_object($overlay) && !$overlay->remember('update', $_REQUEST, 'category:'.$_REQUEST['id']))) {
		Logger::error($error);
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
		
		if(!$render_overlaid)
		    Safe::redirect(Categories::get_permalink($item));
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

		// allow for change detection
		$overlay->snapshot();

		// update the overlay from form content
		$overlay->parse_fields($_REQUEST);

		// save content of the overlay in the category itself
		$_REQUEST['overlay'] = $overlay->save();
		$_REQUEST['overlay_id'] = $overlay->get_id();
	}
	// display the form on error
	if((!$_REQUEST['id'] = Categories::post($_REQUEST))
			|| (is_object($overlay) && !$overlay->remember('insert', $_REQUEST, 'category:'.$_REQUEST['id']))) {
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
			$menu = array_merge($menu, array('files/edit.php?anchor='.urlencode('category:'.$_REQUEST['id']) => i18n::s('Add a file')));
		if(!preg_match('/\bno_links\b/i', $category->item['options']))
			$menu = array_merge($menu, array('links/edit.php?anchor='.urlencode('category:'.$_REQUEST['id']) => i18n::s('Add a link')));
		$follow_up .= Skin::build_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	}

// display the form on GET
} else
	$with_form = TRUE;

// display the form
if($with_form) {

	// the form to edit a category
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form" enctype="multipart/form-data"><div>';
	$fields = array();

	//
	// panels
	//
	$panels = array();

	//
	// index tab - fields that contribute directly to the section index page
	//
	$text = '';

	// the title
	$label = i18n::s('Title').' *';
	$input = '<textarea name="title" id="title" rows="2" cols="50" accesskey="t">'.encode_field(isset($item['title'])?$item['title']:'').'</textarea>';
	$hint = i18n::s('Please provide a meaningful title.');
	$fields[] = array($label, $input, $hint);

	// the introduction
	$label = i18n::s('Introduction');
<<<<<<< HEAD
	$input = '<textarea name="introduction" rows="5" cols="50" accesskey="i">'.encode_field(isset($item['introduction'])?$item['introduction']:'').'</textarea>';
=======
	//$input = '<textarea name="introduction" rows="2" cols="50" accesskey="i">'.encode_field(isset($item['introduction'])?$item['introduction']:'').'</textarea>';
	$input = Surfer::get_editor('introduction', isset($item['introduction'])?$item['introduction']:'');
>>>>>>> next
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
	$text .= Skin::build_form($fields);
	$fields = array();

	// layout for  related sections
	$label      = i18n::s('Layout');
	
        if(!isset($item['sections_layout']) || !$item['sections_layout'])
		$item['sections_layout'] = 'map';
	
        $input      = Skin::build_layouts_selector('section', $item['sections_layout']);
	$fields[]   = array($label, $input);

	// append fields
	$text .= Skin::build_box(i18n::s('Sections'), Skin::build_form($fields), 'folded');
	$fields = array();

	// layout for related articles
	if(!isset($item['articles_layout']) || !$item['articles_layout'])
		$item['articles_layout'] = 'decorated';
	
        $input      = Skin::build_layouts_selector('article', $item['articles_layout']);
	$fields[]   = array($label, $input);
        
        // append fields
	$text .= Skin::build_box(i18n::s('Articles'), Skin::build_form($fields), 'folded');
	$fields = array();
        
        // layout for related users
        if(!isset($item['users_layout']) || !$item['users_layout'])
		$item['users_layout'] = 'decorated';
        
        $input      = Skin::build_layouts_selector('user', $item['users_layout']);
        $fields[]   = array($label, $input);

	// append fields
	$text .= Skin::build_box(i18n::s('Persons'), Skin::build_form($fields), 'folded');
	$fields = array();

	// layouts for sub-categories
	//
	if(!isset($item['categories_count']) || ($item['categories_count'] < 1))
		$item['categories_count'] = 5;
	$input = sprintf(i18n::s('List up to %s sub-categories with the following layout&nbsp;:'), '<input type="text" name="categories_count" value="'.encode_field($item['categories_count']).'" size="2" />').BR;

	if(!isset($item['categories_layout']) || !$item['categories_layout'])
		$item['categories_layout'] = 'decorated';

	$input .= BR.Skin::build_layouts_selector('category', $item['categories_layout']);
	$fields[] = array($label, $input);

	// categories overlay
	$label = i18n::s('Category overlay');
	$input = '<input type="text" name="categories_overlay" size="20" value="'.encode_field(isset($item['categories_overlay']) ? $item['categories_overlay'] : '').'" maxlength="128" />';
	$hint = sprintf(i18n::s('Script used to %s this category'), Skin::build_link('overlays/', i18n::s('overlay'), 'open'));
	$fields[] = array($label, $input, $hint);

	// the prefix
// 	$label = i18n::s('Prefix');
// 	$input = '<textarea name="prefix" rows="2" cols="50">'.encode_field(isset($item['prefix']) ? $item['prefix'] : '').'</textarea>';
// 	$hint = i18n::s('To be inserted at the top of related pages.');
// 	$fields[] = array($label, $input, $hint);

	// the suffix
// 	$label = i18n::s('Suffix');
// 	$input = '<textarea name="suffix" rows="2" cols="50">'.encode_field(isset($item['suffix']) ? $item['suffix'] : '').'</textarea>';
// 	$hint = i18n::s('To be inserted at the bottom of related pages.');
// 	$fields[] = array($label, $input, $hint);

	// append fields
	$text .= Skin::build_box(i18n::s('Sub-categories'), Skin::build_form($fields), 'folded');
	$fields = array();

	// trailer information
	$label = i18n::s('Trailer');
	$input = Surfer::get_editor('trailer', isset($item['trailer'])?$item['trailer']:'');
	$hint = i18n::s('Text to be appended at the bottom of the page, after all other elements attached to this page.');
	$fields[] = array($label, $input, $hint);

	// the icon url may be set after the page has been created
	if(isset($item['id'])) {
		$label = i18n::s('Image');
		$input = '';
		$hint = '';

		// show the current icon
		if(isset($item['icon_url']) && $item['icon_url']) {
			$input .= '<img src="'.preg_replace('/\/images\/category\/[0-9]+\//', "\\0thumbs/", $item['icon_url']).'" alt="" />'.BR;
			$command = i18n::s('Change');
		} elseif(Surfer::may_upload()) {
			$hint .= i18n::s('Image to be displayed in the panel aside the page.');
			$command = i18n::s('Add an image');
		}

		$value = '';
		if(isset($item['icon_url']) && $item['icon_url'])
			$value = $item['icon_url'];
		$input .= '<input type="text" name="icon_url" size="55" value="'.encode_field($value).'" maxlength="255" />';
		if(Surfer::may_upload())
			$input .= ' <span class="details">'.Skin::build_link('images/edit.php?anchor='.urlencode('category:'.$item['id']).'&amp;action=icon', $command, 'button').'</span>';
		$fields[] = array($label, $input, $hint);
	}

	// extra information
	$label = i18n::s('Extra');
<<<<<<< HEAD
=======
	//$input = '<textarea name="extra" rows="6" cols="50">'.encode_field(isset($item['extra']) ? $item['extra'] : '').'</textarea>';
>>>>>>> next
	$input = Surfer::get_editor('extra', isset($item['extra'])?$item['extra']:'');
	$hint = i18n::s('Text to be inserted in the panel aside the page. Use [box.extra=title]content[/box] or plain HTML.');
	$fields[] = array($label, $input, $hint);

	// append fields
	$text .= Skin::build_box(i18n::s('More content'), Skin::build_form($fields), 'folded');
	$fields = array();

	// display in a separate panel
	if($text)
		$panels[] = array('information', i18n::s('Index page'), 'information_panel', $text);

	//
	// resources tab
	//
	$text = '';

	// splash message for new items
	if(!isset($item['id']))
		$text .= '<p>'.i18n::s('Submit the new item, and you will be able to add resources afterwards.').'</p>';

	// resources attached to this anchor
	else {

		// images
		$box = '';
		if(Images::allow_creation($item, $anchor, 'category')) {
			$menu = array( 'images/edit.php?anchor='.urlencode('category:'.$item['id']) => i18n::s('Add an image') );
			$box .= Skin::build_list($menu, 'menu_bar');
		}
		if($items = Images::list_by_date_for_anchor('category:'.$item['id']))
			$box .= Skin::build_list($items, 'decorated');
		if($box)
			$text .= Skin::build_box(i18n::s('Images'), $box, 'folded');

		// files
		$box = '';
		if(Files::allow_creation($item, $anchor, 'category')) {
			$menu = array( 'files/edit.php?anchor='.urlencode('category:'.$item['id']) => i18n::s('Add a file') );
			$box .= Skin::build_list($menu, 'menu_bar');
		}
		if($items = Files::list_embeddable_for_anchor('category:'.$item['id'], 0, 50))
			$box .= Skin::build_list($items, 'decorated');
		if($box)
			$text .= Skin::build_box(i18n::s('Files'), $box, 'folded');

	}

	// display in a separate panel
	if($text)
		$panels[] = array('resources', i18n::s('Resources'), 'resources_panel', $text);

	//
	// options tab
	//
	$text = '';

	// the active flag: Yes/public, Restricted/logged, No/associates --we don't care about inheritance, to enable security changes afterwards
	$label = i18n::s('Access');
	$input = Skin::build_active_set_input($item);
	$hint = Skin::build_active_set_hint($anchor);
	$fields[] = array($label, $input, $hint);

	// append fields
	$text .= Skin::build_form($fields);
	$fields = array();

	// settings for parent or category tree
	//
	$parent = '';

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

	$input = Skin::build_input_time('expiry_date', $value, 'date_time');
	$hint = i18n::s('Remove content on dead-line - automatically');
	$fields[] = array($label, $input, $hint);

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

	// the thumbnail url may be set after the page has been created
	if(isset($item['id'])) {
		$label = i18n::s('Thumbnail');
		$input = '';
		$hint = '';

		// show the current thumbnail
		$input = '';
		if(isset($item['thumbnail_url']) && $item['thumbnail_url']) {
			$input .= '<img src="'.$item['thumbnail_url'].'" alt="" />'.BR;
			$command = i18n::s('Change');
		} elseif(Surfer::may_upload()) {
			$hint .= i18n::s('Upload a small image to illustrate this page when it is listed into parent page.');
			$command = i18n::s('Add an image');
		}

		$input .= '<input type="text" name="thumbnail_url" size="55" value="'.encode_field(isset($item['thumbnail_url']) ? $item['thumbnail_url'] : '').'" maxlength="255" />';
		if(Surfer::may_upload())
			$input .= ' <span class="details">'.Skin::build_link('images/edit.php?anchor='.urlencode('category:'.$item['id']).'&amp;action=thumbnail', $command, 'button').'</span>';
		$fields[] = array($label, $input, $hint);
	}

	// append fields
	$parent .= Skin::build_form($fields);
	$fields = array();

	// append fields
	if(is_object($anchor))
		$label = sprintf(i18n::s('Contribution to "%s"'), $anchor->get_title());
	else
		$label = i18n::s('Appearance at the category tree');
	$text .= Skin::build_box($label, $parent, 'folded');

	// excerpts
	if(!isset($item['nick_name']) || strcmp($item['nick_name'], i18n::c('featured'))) {
		$input = i18n::s('YACS can list most recent items').BR;
		$input .= '<input type="radio" name="display" value="site:all"';
		if(isset($item['display']) && ($item['display'] == 'site:all'))
			$input .= ' checked="checked"';
		$input .= '/> '.i18n::s('at all pages, among other navigation boxes')
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
		$text .= Skin::build_box(i18n::s('Excerpts'), $input, 'folded');
	}

	// the background color
	$label = i18n::s('Background color');
	$input = '<input class="color {hash:true,required:false}" name="background_color" size="10" value="'.encode_field(isset($item['background_color'])?$item['background_color']:'').'" maxlength="8" />';
	$hint = i18n::s('To highlight this category in lists');
	$fields[] = array($label, $input, $hint);

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

	// rendering options
	$label = i18n::s('Rendering');
	$input = '<input type="text" name="options" id="options" size="55" value="'.encode_field(isset($item['options']) ? $item['options'] : '').'" maxlength="255" accesskey="o" />';

	$keywords = array();
	$keywords[] = '<a>articles_by_title</a> - '.i18n::s('Sort pages by title');
	$keywords[] = '<a>articles_by_rating</a> - '.i18n::s('Sort pages by rating sum');
	$keywords[] = '<a>with_files</a> - '.i18n::s('Files can be added to the index page');
	$keywords[] = '<a>files_by_title</a> - '.i18n::s('Sort files by title (and not by date)');
	$keywords[] = '<a>with_links</a> - '.i18n::s('Links can be added to the index page');
	$keywords[] = '<a>links_by_title</a> - '.i18n::s('Sort links by title (and not by date)');
	$keywords[] = '<a>with_comments</a> - '.i18n::s('The index page itself is a thread');
	$keywords[] = 'skin_foo_bar - '.i18n::s('Apply a specific theme (in skins/foo_bar)');
	$keywords[] = 'variant_foo_bar - '.i18n::s('To load template_foo_bar.php instead of the regular template');
	$hint = i18n::s('You may combine several keywords:').'<div id="options_list">'.Skin::finalize_list($keywords, 'compact').'</div>';
	$fields[] = array($label, $input, $hint);

	Page::insert_script(
		'function append_to_options(keyword) {'."\n"
		.'	var target = $("#options");'."\n"
		.'	target.val(target.val() + " " + keyword);'."\n"
		.'}'."\n"
		.'$(function() {'."\n"
		.'	$("#options_list a").bind("click",function(){'."\n"
		.'		append_to_options($(this).text());'."\n"
		.'	}).css("cursor","pointer");'."\n"
		.'});'
		);

	// associates can change the overlay --complex interface
	if(Surfer::is_associate() && Surfer::has_all()) {

		// current type
		$overlay_type = '';
		if(is_object($overlay))
			$overlay_type = $overlay->get_type();

		// list overlays available on this system
		$label = i18n::s('Change the overlay');
		$input = '<select name="overlay_type">';
		if($overlay_type) {
			$input .= '<option value="none">('.i18n::s('none').")</option>\n";
			$hint = i18n::s('If you change the overlay you may loose some data.');
		} else {
			$hint = i18n::s('No overlay has been selected yet.');
			$input .= '<option value="" selected="selected">('.i18n::s('none').")</option>\n";
		}
		if ($dir = Safe::opendir($context['path_to_root'].'overlays')) {

			// every php script is an overlay, except index.php, overlay.php, and hooks
			while(($file = Safe::readdir($dir)) !== FALSE) {
				if(($file[0] == '.') || is_dir($context['path_to_root'].'overlays/'.$file))
					continue;
				if($file == 'index.php')
					continue;
				if($file == 'overlay.php')
					continue;
				if(preg_match('/hook\.php$/i', $file))
					continue;
				if(!preg_match('/(.*)\.php$/i', $file, $matches))
					continue;
				$overlays[] = $matches[1];
			}
			Safe::closedir($dir);
			if(@count($overlays)) {
				natsort($overlays);
				foreach($overlays as $overlay_name) {
					$selected = '';
					if($overlay_name == $overlay_type)
						$selected = ' selected="selected"';
					$input .= '<option value="'.$overlay_name.'"'.$selected.'>'.$overlay_name."</option>\n";
				}
			}
		}
		$input .= '</select>';
		$fields[] = array($label, $input, $hint);

	// remember the overlay type
	} elseif(is_object($overlay))
		$text .= '<input type="hidden" name="overlay_type" value="'.encode_field($overlay->get_type()).'" />';

	// more options
	$text .= Skin::build_box(i18n::s('More options'), Skin::build_form($fields), 'folded');
	$fields = array();

	// display in a separate panel
	if($text)
		$panels[] = array('options', i18n::s('Options'), 'options_panel', $text);

	// let YACS do the hard job
	$context['text'] .= Skin::build_tabs($panels);

	//
	// bottom commands
	//
	$menu = array();

        if($whole_rendering) {
            // the submit button
            $menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');

            // cancel button
            if(isset($item['id']))
                    $menu[] = Skin::build_link(Categories::get_permalink($item), i18n::s('Cancel'), 'span');

            // insert the menu in the page
            $context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

        }

	// several options to check
	$input = array();

	// do not stamp edition date -- complex command
	if(Surfer::is_empowered() && isset($item['id']) && Surfer::has_all())
		$input[] = '<input type="checkbox" name="silent" value="Y" /> '.i18n::s('Do not change modification date.');

	// validate page content
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
	Page::insert_script(
		// check that main fields are not empty
		'func'.'tion validateDocumentPost(container) {'."\n"
			// title is mandatory
		.'	if(!container.title.value) {'."\n"
		.'		alert("'.i18n::s('Please provide a meaningful title.').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		return false;'."\n"
		.'	}'."\n"
			// successful check
		.'	return true;'."\n"
		.'}'."\n"
		// disable editor selection on change in form
                .'$("#main_form textarea, #main_form input, #main_form select").change(function() {'."\n"
                .'      $("#preferred_editor").attr("disabled",true);'."\n"
                .'});'."\n"
		// set the focus on first form field
		.'$("#title").focus();'."\n"
		);

	// content of the help box
	if($whole_rendering) {
	    $help = '';

	    // html and codes
	    $help .= '<p>'.sprintf(i18n::s('%s and %s are available to enhance text rendering.'), Skin::build_link('codes/', i18n::s('YACS codes'), 'open'), Skin::build_link('smileys/', i18n::s('smileys'), 'open')).'</p>';

	    // locate mandatory fields
	    $help .= '<p>'.i18n::s('Mandatory fields are marked with a *').'</p>';

	    // change to another editor
	    $help .= '<form action=""><p><select name="preferred_editor" id="preferred_editor" onchange="Yacs.setCookie(\'surfer_editor\', this.value); window.location = window.location;">';
	    $selected = '';
	    if(!isset($_SESSION['surfer_editor']) || ($_SESSION['surfer_editor'] == 'tinymce'))
		    $selected = ' selected="selected"';
	    $help .= '<option value="tinymce"'.$selected.'>'.i18n::s('TinyMCE')."</option>\n";
	    $selected = '';
	    if(isset($_SESSION['surfer_editor']) && ($_SESSION['surfer_editor'] == 'yacs'))
		    $selected = ' selected="selected"';
	    $help .= '<option value="yacs"'.$selected.'>'.i18n::s('Textarea')."</option>\n";
	    $help .= '</select></p></form>';

	    // in a side box
	    $context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'boxes', 'help');
	}

}

// render the skin
render_skin();

?>
