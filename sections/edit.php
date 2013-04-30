<?php
/**
 * create a new section or edit an existing one
 *
 * Accepted calls:
 * - edit.php
 * - edit.php/&lt;id&gt;
 * - edit.php?id=&lt;id&gt;
 * - edit.php?anchor=&lt;anchor&gt;
 *
 * @author Bernard Paques
 * @author Vincent No&euml;l
 * @author GnapZ
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @author Alexis Raimbault
 * @tester Agnes
 * @tester Marco Pici
 * @tester Ghjmora
 * @tester Aleko
 * @tester Manuel Lopez Gallego
 * @tester Jan Boen
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../images/images.php';
include_once '../locations/locations.php';
include_once '../tables/tables.php';
include_once '../versions/versions.php'; // roll-back

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item = Sections::get($id);

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
	$overlay = Overlay::load($item, 'section:'.$item['id']);
elseif(isset($_REQUEST['variant']) && $_REQUEST['variant'])
	$overlay = Overlay::bind($_REQUEST['variant']);
elseif(isset($_SESSION['pasted_variant']) && $_SESSION['pasted_variant']) {
	$overlay = Overlay::bind($_SESSION['pasted_variant']);
	unset($_SESSION['pasted_variant']);
} elseif(!isset($item['id']) && is_object($anchor))
	$overlay = $anchor->get_overlay('section_overlay');

// we are allowed to add a new section
if(!isset($item['id']) && Sections::allow_creation(NULL, $anchor))
	$permitted = TRUE;

// we are allowed to modify an existing section
elseif(isset($item['id']) && Sections::allow_modification($item, $anchor))
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// do not always show the edition form
$with_form = FALSE;

// load the skin, maybe with a variant
load_skin('sections', $anchor, isset($item['options']) ? $item['options'] : '');

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// path to this page
if(is_object($anchor)&& $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'sections/' => i18n::s('Site map') );

if(isset($item['id']) && isset($item['title']))
	$context['path_bar'] = array_merge($context['path_bar'], array(Sections::get_permalink($item) => $item['title']));

// page title
if(isset($item['title']))
	$context['page_title'] = sprintf(i18n::s('Edit: %s'), $item['title']);
else
	$context['page_title'] = i18n::s('Add a section');

// validate input syntax only if required
if(isset($_REQUEST['option_validate']) && ($_REQUEST['option_validate'] == 'Y')) {
	if(isset($_REQUEST['introduction']))
		xml::validate($_REQUEST['introduction']);
	if(isset($_REQUEST['description']))
		xml::validate($_REQUEST['description']);
}

// adjust dates from surfer time zone to UTC time zone
if(isset($_REQUEST['activation_date']) && $_REQUEST['activation_date'])
	$_REQUEST['activation_date'] = Surfer::to_GMT($_REQUEST['activation_date']);
if(isset($_REQUEST['expiry_date']) && $_REQUEST['expiry_date'])
	$_REQUEST['expiry_date'] = Surfer::to_GMT($_REQUEST['expiry_date']);

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// access denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged()) {

		if(isset($item['id']))
			$link = Sections::get_url($item['id'], 'edit');
		elseif(isset($_REQUEST['anchor']))
			$link = 'sections/edit.php?anchor='.urlencode($_REQUEST['anchor']);
		else
			$link = 'sections/edit.php';

		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode($link));
	}

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// an error occured
} elseif(count($context['error'])) {
	$item = $_REQUEST;
	$with_form = TRUE;

// process uploaded data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// protect from hackers
	if(isset($_REQUEST['edit_name']))
		$_REQUEST['edit_name'] = preg_replace(FORBIDDEN_IN_NAMES, '_', $_REQUEST['edit_name']);
	if(isset($_REQUEST['edit_address']))
		$_REQUEST['edit_address'] = encode_link($_REQUEST['edit_address']);

	// overlay may have changed
	if(isset($_REQUEST['overlay_type']) && $_REQUEST['overlay_type']) {

		// associates are allowed to change overlay types -- see overlays/select.php
		if(!Surfer::is_associate())
			unset($_REQUEST['overlay_type']);

		// overlay type has not changed
		elseif(is_object($overlay) && ($overlay->get_type() == $_REQUEST['overlay_type']))
			unset($_REQUEST['overlay_type']);
	}

	// new overlay type
	if(isset($_REQUEST['overlay_type']) && $_REQUEST['overlay_type']) {

		// delete the previous version, if any
		if(is_object($overlay))
			$overlay->remember('delete', $_REQUEST, 'section:'.$_REQUEST['id']);

		// new version of page overlay
		$overlay = Overlay::bind($_REQUEST['overlay_type']);
	}

	// when the page has been overlaid
	if(is_object($overlay)) {

		// allow for change detection
		$overlay->snapshot();

		// update the overlay from form content
		$overlay->parse_fields($_REQUEST);

		// save content of the overlay in this item
		$_REQUEST['overlay'] = $overlay->save();
		$_REQUEST['overlay_id'] = $overlay->get_id();
	}

	// update an existing page
	if(isset($_REQUEST['id'])) {

		// remember the previous version
		if($item['id'])
			Versions::save($item, 'section:'.$item['id']);

		// overlay has been inserted or updated
		if(isset($_REQUEST['overlay_type']) && $_REQUEST['overlay_type'])
			$action = 'insert';
		else
			$action = 'update';

		// stop on error
		if(!Sections::put($_REQUEST) || (is_object($overlay) && !$overlay->remember($action, $_REQUEST, 'section:'.$_REQUEST['id']))) {
			$item = $_REQUEST;
			$with_form = TRUE;

		// else display the updated page
		} else {

			// cascade changes on access rights
			if($_REQUEST['active'] != $item['active'])
				Anchors::cascade('section:'.$item['id'], $_REQUEST['active']);

			// notification to send by e-mail
			$mail = array();
			$mail['subject'] = sprintf(i18n::c('%s: %s'), i18n::c('Contribution'), strip_tags($_REQUEST['title']));
			$mail['notification'] = Sections::build_notification('update', $_REQUEST);
			$mail['headers'] = Mailer::set_thread('section:'.$_REQUEST['id']);

			// notify watchers of the updated section and of its parent
			if($handle = new Section()) {
				$handle->load_by_content($_REQUEST, $anchor);

				// send to watchers of this anchor and upwards
				if(isset($_REQUEST['notify_watchers']) && ($_REQUEST['notify_watchers'] == 'Y'))
					$handle->alert_watchers($mail, 'section:update', ($_REQUEST['active'] == 'N'));

			}

			// send to followers of this user
			if(isset($_REQUEST['notify_followers']) && ($_REQUEST['notify_followers'] == 'Y')
				&& Surfer::get_id() && ($_REQUEST['active'] != 'N')) {
					$mail['message'] = Mailer::build_notification($mail['notification'], 2);
					Users::alert_watchers('user:'.Surfer::get_id(), $mail);
			}

			// touch the related anchor
			if(is_object($anchor))
				$anchor->touch('section:update', $item['id'], isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y'));

			// the section has been modified
			$context['text'] .= '<p>'.i18n::s('The section has been successfully updated.').'</p>';

			// list persons that have been notified
			if($recipients = Mailer::build_recipients('section:'.$item['id'])) {

				$context['text'] .= $recipients;

				// follow-up commands
				$follow_up = i18n::s('What do you want to do now?');
				$menu = array();
				$menu = array_merge($menu, array(Sections::get_permalink($_REQUEST) => i18n::s('View the section')));
				if(preg_match('/\bwith_files\b/i', $_REQUEST['options']) && Surfer::may_upload())
					$menu = array_merge($menu, array('files/edit.php?anchor='.urlencode('section:'.$_REQUEST['id']) => i18n::s('Add a file')));
				$follow_up .= Skin::build_list($menu, 'menu_bar');
				$context['text'] .= Skin::build_block($follow_up, 'bottom');

				// log page modification
				$label = sprintf(i18n::c('%s: %s'), i18n::c('Contribution'), strip_tags($_REQUEST['title']));
				$description = '<a href="'.$context['url_to_home'].$context['url_to_root'].Sections::get_permalink($_REQUEST).'">'.$_REQUEST['title'].'</a>';
				Logger::notify('sections/edit.php: '.$label, $description);

			// display the updated page
			} else
				Safe::redirect($context['url_to_home'].$context['url_to_root'].Sections::get_permalink($item));

		}

	// create a new section
	} elseif(!$_REQUEST['id'] = Sections::post($_REQUEST)) {
		$item = $_REQUEST;
		$with_form = TRUE;

	// successful post
	} else {

		// post an overlay, with the new section id --don't stop on error
		if(is_object($overlay))
			$overlay->remember('insert', $_REQUEST, 'section:'.$_REQUEST['id']);

		// notification to send by e-mail
		$mail = array();
		$anchor_title = (is_object($anchor))?strip_tags($anchor->get_title()):i18n::s('Root');
		$mail['subject'] = sprintf(i18n::c('%s: %s'), $anchor_title, strip_tags($_REQUEST['title']));
		$mail['notification'] = Sections::build_notification('create', $_REQUEST);
		$mail['headers'] = Mailer::set_thread('section:'.$_REQUEST['id']);

		// touch the related anchor
		if(is_object($anchor)) {

			// send to watchers of this anchor
			if(isset($_REQUEST['notify_watchers']) && ($_REQUEST['notify_watchers'] == 'Y'))
				$anchor->alert_watchers($mail, 'section:create', ($_REQUEST['active'] == 'N'));

			// update anchors
			$anchor->touch('section:create', $_REQUEST['id'], isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y'));
		}

		// send to followers of this user
		if(isset($_REQUEST['notify_followers']) && ($_REQUEST['notify_followers'] == 'Y')
			&& Surfer::get_id() && ($_REQUEST['active'] != 'N')) {
				$mail['message'] = Mailer::build_notification($mail['notification'], 2);
				Users::alert_watchers('user:'.Surfer::get_id(), $mail);
		}

		// increment the post counter of the surfer
		Users::increment_posts(Surfer::get_id());

		// get the new item
		$section = Anchors::get('section:'.$_REQUEST['id'], TRUE);

		// reward the poster for new posts
		$context['page_title'] = i18n::s('Thank you for your contribution');

		$context['text'] .= '<p>'.i18n::s('Please review the new page carefully and fix possible errors rapidly.').'</p>';

		// list persons that have been notified
		$context['text'] .= Mailer::build_recipients('section:'.$_REQUEST['id']);

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu = array_merge($menu, array($section->get_url() => i18n::s('View the section')));
		if(Surfer::may_upload())
			$menu = array_merge($menu, array('images/edit.php?anchor='.urlencode('section:'.$_REQUEST['id']) => i18n::s('Add an image')));
		if(preg_match('/\bwith_files\b/i', $section->item['options']) && Surfer::may_upload())
			$menu = array_merge($menu, array('files/edit.php?anchor='.urlencode('section:'.$_REQUEST['id']) => i18n::s('Add a file')));
		if(preg_match('/\bwith_links\b/i', $section->item['options']))
			$menu = array_merge($menu, array('links/edit.php?anchor='.urlencode('section:'.$_REQUEST['id']) => i18n::s('Add a link')));
		if(is_object($anchor))
			$menu = array_merge($menu, array('sections/edit.php?anchor='.urlencode($anchor->get_reference()) => i18n::s('Add another section')));
		$follow_up .= Skin::build_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

		// log the creation of a new section
		$label = sprintf(i18n::c('New section: %s'), strip_tags($section->get_title()));

		if(is_object($anchor))
			$description = sprintf(i18n::s('Sent by %s in %s'), Surfer::get_name(), $anchor->get_title());
		else
			$description = sprintf(i18n::s('Sent by %s'), Surfer::get_name());

                $link = $context['url_to_home'].$context['url_to_root'].$section->get_url();
                $description .= "\n\n".$section->get_teaser('basic')
			."\n\n".'<a href="'.$link.'">'.$link.'</a>';
		Logger::notify('sections/edit.php: '.$label, $description);

	}

// display the form on GET
} else
	$with_form = TRUE;

// display the form
if($with_form) {

	// the form to edit a section
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
	if(!is_object($overlay) || !($label = $overlay->get_label('title', isset($item['id'])?'edit':'new')))
		$label = i18n::s('Title').' *';

	// copy this as compact title on initial edit
	if((!isset($item['index_title']) || !$item['index_title']) && isset($item['title']))
		$item['index_title'] = $item['title'];

	$input = '<textarea name="index_title" id="index_title" rows="2" cols="50" accesskey="t">'.encode_field(isset($item['index_title']) ? $item['index_title'] : '').'</textarea>';
	if(!is_object($overlay) || !($hint = $overlay->get_label('title_hint', isset($item['id'])?'edit':'new')))
		$hint = i18n::s('Please provide a meaningful title.');
	$fields[] = array($label, $input, $hint);

	// the introduction
	if(!is_object($overlay) || !($label = $overlay->get_label('introduction', isset($item['id'])?'edit':'new')))
		$label = i18n::s('Introduction');
	$input = '<textarea name="introduction" rows="5" cols="50" accesskey="i">'.encode_field(isset($item['introduction']) ? $item['introduction'] : '').'</textarea>';
	if(!is_object($overlay) || !($hint = $overlay->get_label('introduction_hint', isset($item['id'])?'edit':'new')))
		$hint = i18n::s('Appears at the site map, near section title');
	$fields[] = array($label, $input, $hint);

	// include overlay fields, if any
	if(is_object($overlay))
		$fields = array_merge($fields, $overlay->get_fields($item));

	// the description label
	if(!is_object($overlay) || !($label = $overlay->get_label('description', isset($item['id'])?'edit':'new')))
		$label = i18n::s('Description');

	// use the editor if possible
	$input = Surfer::get_editor('description', isset($item['description'])?$item['description']:'');
	if(!is_object($overlay) || !($hint = $overlay->get_label('description_hint', isset($item['id'])?'edit':'new')))
		$hint = '';
	$fields[] = array($label, $input, $hint);

	// append regular fields
	$text .= Skin::build_form($fields);
	$fields = array();

	// settings for attached pages
	//

	// layout for related articles
	$label = i18n::s('Layout');
	$input = '';
	$custom_layout = '';
	if(!isset($item['articles_layout']))
		$item['articles_layout'] = 'decorated';
	elseif(!preg_match('/^(accordion|alistapart|carrousel|compact|daily|decorated|digg|directory|hardboiled|jive|map|newspaper|none|simile|slashdot|table|tabs|tagged|titles|yabb)$/', $item['articles_layout'])) {
		$custom_layout = $item['articles_layout'];
		$item['articles_layout'] = 'custom';
	}

	$input .= '<input type="radio" name="articles_layout" value="decorated"';
	if($item['articles_layout'] == 'decorated')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('decorated - A list decorated with images')
		.BR.'<input type="radio" name="articles_layout" value="digg"';
	if($item['articles_layout'] == 'digg')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('digg - To order pages by rating')
		.BR.'<input type="radio" name="articles_layout" value="slashdot"';
	if($item['articles_layout'] == 'slashdot')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('slashdot - List most recent pages equally')
		.BR.'<input type="radio" name="articles_layout" value="map"';
	if($item['articles_layout'] == 'map')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('map - Map in two columns, like Yahoo!')
		.BR.'<input type="radio" name="articles_layout" value="accordion"';
	if($item['articles_layout'] == 'accordion')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('accordion - Expose one item at a time in a stack')
		.BR.'<input type="radio" name="articles_layout" value="carrousel"';
	if($item['articles_layout'] == 'carrousel')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('carrousel - Animate clickable images')
		.BR.'<input type="radio" name="articles_layout" value="titles"';
	if($item['articles_layout'] == 'titles')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('titles - Use only titles and thumbnails')
		.BR.'<input type="radio" name="articles_layout" value="table"';
	if($item['articles_layout'] == 'table')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('table - A table of recent pages')
		.BR.'<input type="radio" name="articles_layout" value="daily"';
	if($item['articles_layout'] == 'daily')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('daily - A list of stamped pages (blog)')
		.BR.'<input type="radio" name="articles_layout" value="newspaper"';
	if($item['articles_layout'] == 'newspaper')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('newspaper - Highlight four most recent pages')
		.BR.'<input type="radio" name="articles_layout" value="hardboiled"';
	if($item['articles_layout'] == 'hardboiled')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('hardboiled - Highlight two most recent pages')
		.BR.'<input type="radio" name="articles_layout" value="jive"';
	if($item['articles_layout'] == 'jive')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('jive - Display most of articles content')
		.BR.'<input type="radio" name="articles_layout" value="yabb"';
	if($item['articles_layout'] == 'yabb')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('yabb - A discussion board')
		.BR.'<input type="radio" name="articles_layout" value="alistapart"';
	if($item['articles_layout'] == 'alistapart')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('alistapart - Display entirely the last published page')
		.BR.'<input type="radio" name="articles_layout" value="tagged"';
	if($item['articles_layout'] == 'tagged')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('tagged - Titles and tags')
		.BR.'<input type="radio" name="articles_layout" value="tabs"';
	if($item['articles_layout'] == 'tabs')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('tabs - One tab per page, with content')
		.BR.'<input type="radio" name="articles_layout" value="simile"';
	if($item['articles_layout'] == 'simile')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('simile - Dots and titles in a timeline')
		.BR.'<input type="radio" name="articles_layout" value="compact"';
	if($item['articles_layout'] == 'compact')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('compact - A compact list')
		.BR.'<input type="radio" name="articles_layout" value="directory"';
	if($item['articles_layout'] == 'directory')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('directory - An alphabetical index of items')
		.BR.'<input type="radio" name="articles_layout" value="custom" id="custom_articles_layout"';
	if($item['articles_layout'] == 'custom')
		$input .= ' checked="checked"';
	$input .= '/> '.sprintf(i18n::s('Use the customized layout %s'), '<input type="text" name="articles_custom_layout" value="'.encode_field($custom_layout).'" size="32" onfocus="$(\'#custom_articles_layout\').attr(\'checked\', \'checked\')" />')
		.BR.'<input type="radio" name="articles_layout" value="none"';
	if($item['articles_layout'] == 'none')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Do not list pages').BR;
	$fields[] = array($label, $input);

	// content options
	$label = i18n::s('Options');
	$input = '<input type="text" name="content_options" id="content_options" size="55" value="'.encode_field(isset($item['content_options']) ? $item['content_options'] : 'auto_publish').'" maxlength="255" accesskey="o" />';
	$keywords = array();
	$keywords[] = '<a>auto_publish</a> - '.i18n::s('Pages are not reviewed prior publication');
	$keywords[] = '<a>anonymous_edit</a> - '.i18n::s('Allow anonymous surfers to change content');
	$keywords[] = '<a>members_edit</a> - '.i18n::s('Allow members to change content');
	$keywords[] = '<a>no_comments</a> - '.i18n::s('Disallow post of new comments');
	$keywords[] = '<a>files_by_date</a> - '.i18n::s('Sort files by date (default)');
	$keywords[] = '<a>files_by_title</a> - '.i18n::s('Sort files by title (and not by date)');
	$keywords[] = '<a>no_files</a> - '.i18n::s('Prevent the upload of new files');
	$keywords[] = '<a>links_by_title</a> - '.i18n::s('Sort links by title (and not by date)');
	$keywords[] = '<a>no_links</a> - '.i18n::s('Disallow post of new links');
	$keywords[] = '<a>with_neighbours</a> - '.i18n::s('Add links to previous and next pages in the same section');
	$keywords[] = '<a>view_as_chat</a> - '.i18n::s('Real-time collaboration');
	$keywords[] = '<a>view_as_tabs</a> - '.i18n::s('Tabbed panels');
	$keywords[] = '<a>view_as_wiki</a> - '.i18n::s('Discussion is separate from content');
	$keywords[] = 'view_as_foo_bar - '.sprintf(i18n::s('Branch out to %s'), 'articles/view_as_foo_bar.php');
	$keywords[] = 'edit_as_simple - '.sprintf(i18n::s('Branch out to %s'), 'articles/edit_as_simple.php');
	if(isset($context['content_without_details']) && ($context['content_without_details'] == 'Y'))
		$keywords[] = '<a>with_details</a> - '.i18n::s('Show page details to all surfers');
	$keywords[] = '<a>without_rating</a> - '.i18n::s('Surfers are not allowed to rate pages in this section');
	$keywords[] = '<a>rate_as_digg</a> - '.i18n::s('Ask explicitly for more votes');
	$keywords[] = '<a>with_export_tools</a> - '.i18n::s('Add conversion tools to PDF, MS-Word');
	$keywords[] = '<a>with_prefix_profile</a> - '.i18n::s('Introduce the poster before main text');
	$keywords[] = '<a>with_suffix_profile</a> - '.i18n::s('Append some poster details at the bottom of the page');
	$keywords[] = '<a>with_extra_profile</a> - '.i18n::s('Append some poster details aside the page (adequate to most weblogs)');
	$hint = i18n::s('You may combine several keywords:').'<div id="content_options_list">'.Skin::finalize_list($keywords, 'compact').'</div>';
	$fields[] = array($label, $input, $hint);

	$context['page_footer'] .= JS_PREFIX
		.'function append_to_content_options(keyword) {'."\n"
		.'	var target = $("#content_options");'."\n"
		.'	target.val(target.val() + " " + keyword);'."\n"
		.'}'."\n"
		.'$(function() {'."\n"
		.'	$("#content_options_list a").bind("click",function(){'."\n"
		.'		append_to_content_options($(this).text());'."\n"
		.'	}).css("cursor","pointer");'."\n"
		.'});'
		.JS_SUFFIX;

	// content overlay
	if(Surfer::is_associate()) {
		$label = i18n::s('Overlay');
		$input = '<input type="text" name="content_overlay" size="50" value="'.encode_field(isset($item['content_overlay']) ? $item['content_overlay'] : '').'" maxlength="64" />';
		$hint = sprintf(i18n::s('Script used to %s in this section'), Skin::build_link('overlays/', i18n::s('overlay articles'), 'open'));
		$fields[] = array($label, $input, $hint);
	}

	// content template
	if(Surfer::is_associate()) {
		$label = i18n::s('Templates');
		$input = '<input type="text" name="articles_templates" size="50" value="'.encode_field(isset($item['articles_templates']) ? $item['articles_templates'] : '').'" maxlength="250" />';
		$hint = sprintf(i18n::s('One or several %s. This setting overrides the overlay setting.'), Skin::build_link(Sections::get_url('templates'), i18n::s('templates'), 'open'));
		$fields[] = array($label, $input, $hint);
	}

	// reflect content canvas from anchor
	if(!isset($item['articles_canvas']) && is_object($anchor))
		$item['articles_canvas'] = $anchor->get_articles_canvas();

	// content canvas
	if(Surfer::is_associate()) {
		$label = i18n::s('Canvas');
		$input = '<select name="articles_canvas">';
		$hint = sprintf(i18n::s('%s used by articles in this section'), Skin::build_link('canvas/', i18n::s('Canvas'), 'open'));
		$canvas = array();
		if ($dir = Safe::opendir($context['path_to_root'].'canvas')) {

			// every php script is an overlay, except index.php, canvas.php, and hooks
			while(($file = Safe::readdir($dir)) !== FALSE) {
				if(($file[0] == '.') || is_dir($context['path_to_root'].'canvas/'.$file))
					continue;
				if($file == 'index.php')
					continue;
				if($file == 'canvas.php')
					continue;
				if(preg_match('/hook\.php$/i', $file))
					continue;
				if(!preg_match('/(.*)\.php$/i', $file, $matches))
					continue;
				$canvas[] = $matches[1];
			}
			Safe::closedir($dir);
			if(@count($canvas)) {
				natsort($canvas);

				// initialize canvas while creating a new section
				if(!isset($item['articles_canvas'])) $item['articles_canvas'] = 'standard';

				foreach($canvas as $canvas_name) {
					$selected = '';
					if($canvas_name == $item['articles_canvas'])
						$selected = ' selected="selected"';
					$input .= '<option value="'.$canvas_name.'"'.$selected.'>'.$canvas_name."</option>\n";
				}
			}
		}
		$input .= '</select>';
		$fields[] = array($label, $input, $hint);
	}

	// the prefix
	$label = i18n::s('Header');
	$input = '<textarea name="prefix" rows="2" cols="50">'.encode_field(isset($item['prefix']) ? $item['prefix'] : '').'</textarea>';
	$hint = i18n::s('To be inserted at the top of related pages.');
	$fields[] = array($label, $input, $hint);

	// the suffix
	$label = i18n::s('Footer');
	$input = '<textarea name="suffix" rows="2" cols="50">'.encode_field(isset($item['suffix']) ? $item['suffix'] : '').'</textarea>';
	$hint = i18n::s('To be inserted at the bottom of related pages.');
	$fields[] = array($label, $input, $hint);

	// append fields
	$text .= Skin::build_box(i18n::s('Pages'), Skin::build_form($fields), 'folded');
	$fields = array();

	// settings for sub-sections
	//

	// layout for sub-sections - default is 'decorated'
	$label = i18n::s('Layout');
	$input = '';
	$custom_layout = '';
	if(!isset($item['sections_layout']))
		$item['sections_layout'] = 'none';
	elseif(!preg_match('/^(accordion|carrousel|compact|decorated|directory|folded|inline|jive|map|slashdot|tabs|titles|yabb|none)$/', $item['sections_layout'])) {
		$custom_layout = $item['sections_layout'];
		$item['sections_layout'] = 'custom';
	}
	$input .= '<input type="radio" name="sections_layout" value="decorated"';
	if($item['sections_layout'] == 'decorated')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('decorated - A list decorated with images')
		.BR.'<input type="radio" name="sections_layout" value="slashdot"';
	if($item['sections_layout'] == 'slashdot')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('slashdot - List most recent pages equally')
		.BR.'<input type="radio" name="sections_layout" value="map"';
	if($item['sections_layout'] == 'map')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('map - Map in two columns, like Yahoo!')
		.BR.'<input type="radio" name="sections_layout" value="accordion"';
	if($item['sections_layout'] == 'accordion')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('accordion - Expose one item at a time in a stack')
		.BR.'<input type="radio" name="sections_layout" value="carrousel"';
	if($item['sections_layout'] == 'carrousel')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('carrousel - Animate clickable images')
		.BR.'<input type="radio" name="sections_layout" value="titles"';
	if($item['sections_layout'] == 'titles')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('titles - Use only titles and thumbnails')
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
	$input .= '/> '.i18n::s('inline - List sections and related pages')
		.BR.'<input type="radio" name="sections_layout" value="folded"';
	if($item['sections_layout'] == 'folded')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('folded - One folded box per section, with content')
		.BR.'<input type="radio" name="sections_layout" value="tabs"';
	if($item['sections_layout'] == 'tabs')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('tabs - One tab per section, with content')
		.BR.'<input type="radio" name="sections_layout" value="compact"';
	if($item['sections_layout'] == 'compact')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('compact - A compact list')
		.BR.'<input type="radio" name="sections_layout" value="directory"';
	if($item['sections_layout'] == 'directory')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('directory - An alphabetical index of items')
		.BR.'<input type="radio" name="sections_layout" value="custom" id="custom_sections_layout"';
	if($item['sections_layout'] == 'custom')
		$input .= ' checked="checked"';
	$input .= '/> '.sprintf(i18n::s('Use the customized layout %s'), '<input type="text" name="sections_custom_layout" value="'.encode_field($custom_layout).'" size="32" onfocus="$(\'#custom_sections_layout\').attr(\'checked\', \'checked\')" />')
		.BR.'<input type="radio" name="sections_layout" value="none"';
	if($item['sections_layout'] == 'none')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Do not list sections');
	$fields[] = array($label, $input);

	// content overlay
	if(Surfer::is_associate()) {
		$label = i18n::s('Overlay');
		$input = '<input type="text" name="section_overlay" size="20" value="'.encode_field(isset($item['section_overlay']) ? $item['section_overlay'] : '').'" maxlength="64" />';
		$hint = sprintf(i18n::s('Script used to %s in this section'), Skin::build_link('overlays/', i18n::s('overlay sub-sections'), 'open'));
		$fields[] = array($label, $input, $hint);
	}

	// append fields
	$text .= Skin::build_box(i18n::s('Sub-sections'), Skin::build_form($fields), 'folded');
	$fields = array();

	// rendering options

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
			$input .= '<img src="'.preg_replace('/\/images\/section\/[0-9]+\//', "\\0thumbs/", $item['icon_url']).'" alt="" />'.BR;
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
			$input .= ' <span class="details">'.Skin::build_link('images/edit.php?anchor='.urlencode('section:'.$item['id']).'&amp;action=icon', $command, 'button').'</span>';
		$fields[] = array($label, $input, $hint);
	}

	// extra information
	$label = i18n::s('Extra');
	$input = Surfer::get_editor('extra', isset($item['extra'])?$item['extra']:'');
	$hint = i18n::s('Text to be inserted in the panel aside the page. Use [box.extra=title]content[/box] or plain HTML.');
	$fields[] = array($label, $input, $hint);

	// news can be either a static or an animated list
	$label = i18n::s('News');
	if(!isset($item['index_news_count']) || ($item['index_news_count'] < 1) || ($item['index_news_count'] > 7))
		$item['index_news_count'] = 5;
	$input = '<input type="radio" name="index_news" value="static"';
	if(!isset($item['index_news']) || !preg_match('/(rotate|scroll|none)/', $item['index_news']))
		$input .= ' checked="checked"';
	$input .= '/> '.sprintf(i18n::s('List up to %s news aside.'), '<input type="text" name="index_news_count" value="'.encode_field($item['index_news_count']).'" size="2" />');
	$input .= BR.'<input type="radio" name="index_news" value="scroll"';
	if(isset($item['index_news']) && ($item['index_news'] == 'scroll'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Similar to the first option, except that displayed information is scrolling.');
	$input .= BR.'<input type="radio" name="index_news" value="rotate"';
	if(isset($item['index_news']) && ($item['index_news'] == 'rotate'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Similar to the first option, except that news are rotated.');
	$input .= BR.'<input type="radio" name="index_news" value="none"';
	if(isset($item['index_news']) && ($item['index_news'] == 'none'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Do not list news.');
	$fields[] = array($label, $input);

	// append fields
	$text .= Skin::build_box(i18n::s('More content'), Skin::build_form($fields), 'folded');
	$fields = array();

	// append fields
	$text .= Skin::build_form($fields);
	$fields = array();

	// display in a separate panel
	if($text)
		$panels[] = array('index', i18n::s('Index page'), 'index_panel', $text);

	//
	// append tabs from the overlay, if any
	//
	if(is_object($overlay) && ($more_tabs = $overlay->get_tabs('edit', $item)))
 		$panels = array_merge($panels, $more_tabs);

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
		if(Images::allow_creation($anchor, $item, 'section')) {
			$menu = array( 'images/edit.php?anchor='.urlencode('section:'.$item['id']) => i18n::s('Add an image') );
			$box .= Skin::build_list($menu, 'menu_bar');
		}
		if($items = Images::list_by_date_for_anchor('section:'.$item['id']))
			$box .= Skin::build_list($items, 'decorated');
		if($box)
			$text .= Skin::build_box(i18n::s('Images'), $box, 'folded');

		// files
		$box = '';
		if(Files::allow_creation($anchor, $item, 'section')) {
			$menu = array( 'files/edit.php?anchor='.urlencode('section:'.$item['id']) => i18n::s('Add a file') );
			$box .= Skin::build_list($menu, 'menu_bar');
		}
		if($items = Files::list_embeddable_for_anchor('section:'.$item['id'], 0, 50))
			$box .= Skin::build_list($items, 'decorated');
		if($box)
			$text .= Skin::build_box(i18n::s('Files'), $box, 'folded');

		// locations
		$box = '';
		if(Locations::allow_creation($anchor, $item, 'section')) {
			$menu = array( 'locations/edit.php?anchor='.urlencode('section:'.$item['id']) => i18n::s('Add a location') );
			$box .= Skin::build_list($menu, 'menu_bar');
		}
		if($items = Locations::list_by_date_for_anchor('section:'.$item['id'], 0, 50, 'section:'.$item['id']))
			$box .= Skin::build_list($items, 'decorated');
		if($box)
			$text .= Skin::build_box(i18n::s('Locations'), $box, 'folded');

		// tables
		$box = '';
		if(Tables::allow_creation($anchor, $item, 'section')) {
			$menu = array( 'tables/edit.php?anchor='.urlencode('section:'.$item['id']) => i18n::s('Add a table') );
			$box .= Skin::build_list($menu, 'menu_bar');
		}
		if($items = Tables::list_by_date_for_anchor('section:'.$item['id'], 0, 50, 'section:'.$item['id']))
			$box .= Skin::build_list($items, 'decorated');
		if($box)
			$text .= Skin::build_box(i18n::s('Tables'), $box, 'folded');

	}

	// display in a separate panel
	if($text)
		$panels[] = array('resources', i18n::s('Resources'), 'resources_panel', $text);

	//
	// options tab
	//
	$text = '';

	// provide information to section owner, and to editors of parent section
	if(Sections::is_owned($item, $anchor) || Surfer::is_associate()) {

		// owner
		if(isset($item['owner_id'])) {
			$label = i18n::s('Owner');
			if($owner = Users::get($item['owner_id']))
				$input = Users::get_link($owner['full_name'], $owner['email'], $owner['id']);
			else
				$input = i18n::s('No owner has been found.');

			// only real owner can delegate to another person
			if(Sections::is_owned($item, $anchor, TRUE) || Surfer::is_associate())
				$input .= ' <span class="details">'.Skin::build_link(Sections::get_url($item['id'], 'own'), i18n::s('Change'), 'button').'</span>';

			$fields[] = array($label, $input);
		}

	}

	// the active flag: Yes/public, Restricted/logged, No/associates --we don't care about inheritance, to enable security changes afterwards
	$label = i18n::s('Access');
	$input = Skin::build_active_set_input($item);
	$hint = Skin::build_active_set_hint($anchor);
	$fields[] = array($label, $input, $hint);

	// locked: Yes / No
	$label = i18n::s('Locker');
	$input = '<input type="radio" name="locked" value="N"';
	if(!isset($item['locked']) || ($item['locked'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Contributions are accepted').' '
		.BR.'<input type="radio" name="locked" value="Y"';
	if(isset($item['locked']) && ($item['locked'] == 'Y'))
		$input .= ' checked="checked"';
	if(isset($item['active']) && ($item['active'] == 'N'))
		$input .= '/> '.i18n::s('Only owners and associates can add content');
	else
		$input .= '/> '.i18n::s('Only assigned persons, owners and associates can add content');
	$fields[] = array($label, $input);

	// append fields
	$text .= Skin::build_form($fields);
	$fields = array();

	// settings for parent or site map
	//
	$parent = '';

	// layout of the upper index page, or of the site map
	//
	if(is_object($anchor)) {

		// define how this section appears
		$input = i18n::s('This section should be:').BR
			.'<input type="radio" name="index_map" value="Y"';
		if(!isset($item['index_map']) || ($item['index_map'] == 'Y'))
			$input .= ' checked="checked"';
		$input .= '/> '.sprintf(i18n::s('listed in the main panel, with the rank %s (default value is 10000).'), '<input type="text" name="rank" size="5" value="'.encode_field(isset($item['rank']) ? $item['rank'] : '10000').'" maxlength="5" />');
		$input .= BR.'<input type="radio" name="index_map" value="N"';
		if(isset($item['index_map']) && ($item['index_map'] != 'Y'))
			$input .= ' checked="checked"';
		$input .= '/> '.i18n::s('listed only to associates and editors, with other special sections').BR;
		$parent .= '<p>'.$input.'</p>';

		$input = i18n::s('Content of this section should be:').BR;
		$input .= '<input type="radio" name="index_panel" value="main"';
		if(!isset($item['index_panel']) || ($item['index_panel'] == '') || ($item['index_panel'] == 'main'))
			$input .= ' checked="checked"';
		$input .= '/> '.i18n::s('displayed in the main panel')
			.BR.'<input type="radio" name="index_panel" value="news"';
		if(isset($item['index_panel']) && ($item['index_panel'] == 'news'))
			$input .= ' checked="checked"';
		$input .= '/> '.i18n::s('displayed in the area reserved to news')
			.BR.'<input type="radio" name="index_panel" value="extra"';
		if(isset($item['index_panel']) && ($item['index_panel'] == 'extra'))
			$input .= ' checked="checked"';
		$input .= '/> '.i18n::s('listed on page side, in an extra box')
			.BR.'<input type="radio" name="index_panel" value="extra_boxes"';
		if(isset($item['index_panel']) && ($item['index_panel'] == 'extra_boxes'))
			$input .= ' checked="checked"';
		$input .= '/> '.i18n::s('displayed in distinct extra boxes')
			.BR.'<input type="radio" name="index_panel" value="none"';
		if(isset($item['index_panel']) && ($item['index_panel'] == 'none'))
			$input .= ' checked="checked"';
		$input .= '/> '.i18n::s('not displayed at the parent index page');
		$parent .= '<p>'.$input.'</p>';

	// layout options related to the site map
	} else {

		// associates may decide if the top-level section will be hidden on the site map or not
		if(Surfer::is_associate()) {

			// define how this section appear at the site map
			$label= '';
			$input = i18n::s('This section should be:').BR
				.'<input type="radio" name="index_map" value="Y"';
			if(!isset($item['index_map']) || ($item['index_map'] == 'Y'))
				$input .= ' checked="checked"';
			$input .= '/> '.sprintf(i18n::s('listed in the main panel, with the rank %s (default value is 10000).'), '<input type="text" name="rank" size="5" value="'.encode_field(isset($item['rank']) ? $item['rank'] : '10000').'" maxlength="5" />');
			$input .= BR.'<input type="radio" name="index_map" value="N"';
			if(isset($item['index_map']) && ($item['index_map'] != 'Y'))
				$input .= ' checked="checked"';
			$input .= '/> '.i18n::s('listed only to associates, with other special sections, and never appears at the site front page').BR;
			$parent .= '<p>'.$label.BR.$input.'</p>';

		// preserve previous settings
		} else {
			if(isset($item['index_map']))
				$text .= '<input type="hidden" name="index_map" value="'.encode_field($item['index_map']).'" />';
			if(isset($item['rank']))
				$text .= '<input type="hidden" name="rank" value="'.encode_field($item['rank']).'" />';
		}

	}

	// the thumbnail url may be set after the page has been created
	if(isset($item['id'])) {
		$label = i18n::s('Thumbnail');
		$input = '';
		$hint = '';

		// show the current thumbnail
		if(isset($item['thumbnail_url']) && $item['thumbnail_url']) {
			$input .= '<img src="'.$item['thumbnail_url'].'" alt="" />'.BR;
			$command = i18n::s('Change');
		} elseif(Surfer::may_upload()) {
			$hint .= i18n::s('Upload a small image to illustrate this page when it is listed into parent page.');
			$command = i18n::s('Add an image');
		}

		$input .= '<input type="text" name="thumbnail_url" size="55" value="'.encode_field(isset($item['thumbnail_url']) ? $item['thumbnail_url'] : '').'" maxlength="255" />';
		if(Surfer::may_upload())
			$input .= ' <span class="details">'.Skin::build_link('images/edit.php?anchor='.urlencode('section:'.$item['id']).'&amp;action=thumbnail', $command, 'button').'</span>';
		$fields[] = array($label, $input, $hint);
	}

	// the activation date
	$label = i18n::s('Activation date');

	// adjust date from UTC time zone to surfer time zone
	$value = '';
	if(isset($item['activation_date']) && ($item['activation_date'] > NULL_DATE))
		$value = Surfer::from_GMT($item['activation_date']);

	$input = Skin::build_input('activation_date', $value, 'date_time');
	$hint = i18n::s('Publish content in the future - automatically');
	$fields[] = array($label, $input, $hint);

	// the expiry date
	$label = i18n::s('Expiry date');

	// adjust date from UTC time zone to surfer time zone
	$value = '';
	if(isset($item['expiry_date']) && ($item['expiry_date'] > NULL_DATE))
		$value = Surfer::from_GMT($item['expiry_date']);

	$input = Skin::build_input('expiry_date', $value, 'date_time');
	$hint = i18n::s('Remove content on dead-line - automatically');
	$fields[] = array($label, $input, $hint);

	// provide my id
	$me = isset($item['id']) ? $item['id'] : NULL;

	// reference to parent section
	$ref = is_object($anchor) ? $anchor->get_reference(): NULL;

	// associates can anchor the section anywhere
	if(Surfer::is_associate()) {
		$label = i18n::s('Section');
		$input =& Skin::build_box(i18n::s('Select parent container'), Sections::get_radio_buttons($ref, $me), 'folded');
		$fields[] = array($label, $input);
        // parent section is defined and surfer is an editor of it
	}elseif(is_object($anchor) && ($anchor->is_assigned())) {

			$label = i18n::s('Section');
			$input =& Skin::build_box(i18n::s('Select parent container'), Sections::get_radio_buttons($ref, $me), 'folded');
			$fields[] = array($label, $input);

	// preserve the existing anchor
	} else
		$text .= '<input type="hidden" name="anchor" value="'.$anchor->get_reference().'" />';



	// append fields
	$parent .= Skin::build_form($fields);
	$fields = array();

	// append fields
	if(is_object($anchor))
		$label = sprintf(i18n::s('Contribution to "%s"'), $anchor->get_title());
	else
		$label = i18n::s('Appearance at the site map');
	$text .= Skin::build_box($label, $parent, 'folded');

	// contribution to the front page
	//
	if(Surfer::is_associate()) {

		// options for recent pages of this section
		$input = i18n::s('Content of this section should be:').BR;
		$input .= '<input type="radio" name="home_panel" value="main"';
		if(isset($item['home_panel']) && ($item['home_panel'] == 'main'))
			$input .= ' checked="checked"';
		$input .= '/> '.i18n::s('displayed in the main panel')
			.BR.'<input type="radio" name="home_panel" value="news"';
		if(isset($item['home_panel']) && ($item['home_panel'] == 'news'))
			$input .= ' checked="checked"';
		$input .= '/> '.i18n::s('displayed in the area reserved to news')
			.BR.'<input type="radio" name="home_panel" value="extra"';
		if(isset($item['home_panel']) && ($item['home_panel'] == 'extra'))
			$input .= ' checked="checked"';
		$input .= '/> '.i18n::s('listed on page side, in an extra box')
			.BR.'<input type="radio" name="home_panel" value="extra_boxes"';
		if(isset($item['home_panel']) && ($item['home_panel'] == 'extra_boxes'))
			$input .= ' checked="checked"';
		$input .= '/> '.i18n::s('displayed in distinct extra boxes')
			.BR.'<input type="radio" name="home_panel" value="none"';
		if(!isset($item['home_panel']) || !preg_match('/^(extra|extra_boxes|main|news)$/', $item['home_panel']))
			$input .= ' checked="checked"';
		$input .= '/> '.i18n::s('not displayed at the front page');

		// one folded box for layout options
		$text .= Skin::build_box(i18n::s('Contribution to the site front page'), $input, 'folded');

	}

	// the nick name
	$label = i18n::s('Nick name');
	$input = '<input type="text" name="nick_name" size="32" value="'.encode_field(isset($item['nick_name']) ? $item['nick_name'] : '').'" maxlength="64" accesskey="n" />';
	$hint = sprintf(i18n::s('To designate a section by its name in the %s'), Skin::build_link('go.php', i18n::s('page selector'), 'open'));
	$fields[] = array($label, $input, $hint);

	// the family
	$label = i18n::s('Family');
	$input = '<input type="text" name="family" size="50" value="'.encode_field(isset($item['family']) ? $item['family'] : '').'" maxlength="255" />';
	$hint = i18n::s('Comes before the title; Used to categorize sections in forums');
	$fields[] = array($label, $input, $hint);

	// compact title
	$label = i18n::s('Compact title');
	$input = '<textarea name="title" id="title" rows="2" cols="50">'.encode_field(isset($item['title']) ? $item['title'] : '').'</textarea>';
	$hint = i18n::s('Alternate title used in lists and in the contextual menu');
	$fields[] = array($label, $input, $hint);

	// rendering options
	$label = i18n::s('Rendering');
	$input = '<input type="text" name="options" id="options" size="55" value="'.encode_field(isset($item['options']) ? $item['options'] : '').'" maxlength="255" accesskey="o" />';

	$keywords = array();
	$keywords[] = '<a>articles_by_publication</a> - '.i18n::s('Sort pages by publication date');
	$keywords[] = '<a>articles_by_rating</a> - '.i18n::s('Sort pages by rating');
	$keywords[] = '<a>articles_by_title</a> - '.i18n::s('Sort pages by title');
	$keywords[] = '<a>articles_by_reverse_title</a> - '.i18n::s('Sort pages by reverse order of titles');
	$keywords[] = '<a>articles_by_reverse_rank</a> - '.i18n::s('Sort pages by reverse rank');
	$keywords[] = '<a>with_deep_news</a> - '.i18n::s('List recent pages from sub-sections');
	$keywords[] = '<a>with_files</a> - '.i18n::s('Files can be added to the index page');
	$keywords[] = '<a>files_by_date</a> - '.i18n::s('Sort files by date (default)');
	$keywords[] = '<a>files_by_title</a> - '.i18n::s('Sort files by title (and not by date)');
	$keywords[] = '<a>with_links</a> - '.i18n::s('Links can be added to the index page');
	$keywords[] = '<a>links_by_title</a> - '.i18n::s('Sort links by title (and not by date)');
	$keywords[] = '<a>with_extra_profile</a> - '.i18n::s('Display profile of section owner');
	$keywords[] = '<a>with_comments</a> - '.i18n::s('The index page itself is a thread of discussion');
	$keywords[] = '<a>view_as_tabs</a> - '.i18n::s('Tabbed panels');
	$keywords[] = 'view_as_foo_bar - '.sprintf(i18n::s('Branch out to %s'), 'sections/view_as_foo_bar.php');
	$keywords[] = 'skin_foo_bar - '.i18n::s('Apply a specific theme (in skins/foo_bar)');
	$keywords[] = 'variant_foo_bar - '.i18n::s('To load template_foo_bar.php instead of the regular template');
	$keywords[] = '<a>no_contextual_menu</a> - '.i18n::s('No information about surrounding sections');
	$keywords[] = '<a>anonymous_edit</a> - '.i18n::s('Allow anonymous surfers to edit content');
	$keywords[] = '<a>members_edit</a> - '.i18n::s('Allow members to edit content');
	$keywords[] = '<a>forward_notifications</a> - '.i18n::s('Alert watchers of containing section');
	$hint = i18n::s('You may combine several keywords:').'<div id="options_list">'.Skin::finalize_list($keywords, 'compact').'</div>';
	$fields[] = array($label, $input, $hint);

	$context['page_footer'] .= JS_PREFIX
		.'function append_to_options(keyword) {'."\n"
		.'	var target = $("#options");'."\n"
		.'	target.val(target.val() + " " + keyword);'."\n"
		.'}'."\n"
		.'$(function() {'."\n"
		.'	$("#options_list a").bind("click",function(){'."\n"
		.'		append_to_options($(this).text());'."\n"
		.'	}).css("cursor","pointer");'."\n"
		.'});'
		.JS_SUFFIX;

	// language of this page
	$label = i18n::s('Language');
	$input = i18n::get_languages_select(isset($item['language'])?$item['language']:'');
	$hint = i18n::s('Select the language used for this page');
	$fields[] = array($label, $input, $hint);

	// meta information
	$label = i18n::s('Meta information');
	$input = '<textarea name="meta" rows="2" cols="50">'.encode_field(isset($item['meta']) ? $item['meta'] : '').'</textarea>';
	$hint = i18n::s('Type here any XHTML tags to be put in page header.');
	$fields[] = array($label, $input, $hint);

	// behaviors
	if(Surfer::is_associate()) {
		$label = i18n::s('Behaviors');
		$input = '<textarea name="behaviors" rows="2" cols="50">'.encode_field(isset($item['behaviors']) ? $item['behaviors'] : '').'</textarea>';
		$hint = sprintf(i18n::s('One %s per line'), Skin::build_link('behaviors/', i18n::s('behavior'), 'open'));
		$fields[] = array($label, $input, $hint);
	}

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

	// add a folded box
	if(count($fields)) {
		$text .= Skin::build_box(i18n::s('More options'), Skin::build_form($fields), 'folded');
		$fields = array();
	}

	// display in a separate panel
	if($text)
		$panels[] = array('options', i18n::s('Options'), 'options_panel', $text);

	//
	// assemble all tabs
	//
	$context['text'] .= Skin::build_tabs($panels);

	//
	// bottom commands
	//
	$menu = array();

	// the submit button
	$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');

	// cancel button
	if(isset($item['id']))
		$menu[] = Skin::build_link(Sections::get_permalink($item), i18n::s('Cancel'), 'span');
	elseif(is_object($anchor))
		$menu[] = Skin::build_link($anchor->get_url(), i18n::s('Cancel'), 'span');

	// several options to check
	$suffix = array();

	// notify watchers
	$suffix[] = '<input type="checkbox" name="notify_watchers" value="Y" /> '.i18n::s('Notify watchers');

	// do not stamp edition date -- complex command
	if(isset($item['id']) && Surfer::has_all())
		$suffix[] = '<input type="checkbox" name="silent" value="Y" /> '.i18n::s('Do not change modification date.');

	// validate page content
	$suffix[] = '<input type="checkbox" name="option_validate" value="Y" checked="checked" /> '.i18n::s('Ensure this post is valid XHTML.');

	// an assistant-like rendering at page bottom
	$context['text'] .= Skin::build_assistant_bottom('', $menu, $suffix, isset($item['tags'])?$item['tags']:'');

	// transmit the id as a hidden field
	if(isset($item['id']) && $item['id'])
		$context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'" />';

	// end of the form
	$context['text'] .= '</div></form>';

	// append the script used for data checking on the browser
	$context['page_footer'] .= JS_PREFIX
		.'// check that main fields are not empty'."\n"
		.'func'.'tion validateDocumentPost(container) {'."\n"
		."\n"
		.'	// title is mandatory'."\n"
		.'	if(!Yacs.trim(container.index_title.value)) {'."\n"
		.'		alert("'.i18n::s('Please provide a meaningful title.').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		return false;'."\n"
		.'	}'."\n"
		."\n"
		.'	// successful check'."\n"
		.'//	return true;'."\n"
		.'}'."\n"
		."\n"
		.'// if title is empty of equal to index_title'."\n"
		.'if( $("#title").val()=="" || $("#title").val()==$("#index_title").val()) {'."\n"
		.'      // then synchronise index_title and title'."\n"
		.'      $("#index_title").change(function() {'."\n"
		.'              $("#title").val($("#index_title").val());'."\n"
		.'      });'."\n"
		.'}'."\n"
		.'//stop updating title from index_title if edited by surfer'."\n"
		.'$("#title").change( function() {'."\n"
		.'      $("#index_title").unbind("change");'."\n"
		.'});'."\n"
		.'// disable editor selection on change in form'."\n"
		.'$("#main_form textarea, #main_form input, #main_form select").change(function() {'."\n"
		.'      $("#preferred_editor").attr("disabled",true);'."\n"
		.'});'."\n"
		.'// set the focus on first form field'."\n"
		.'$("#index_title").focus();'."\n"
		."\n"
		.'// enable tags autocompletion'."\n"
		.'$(function() {'."\n"
		.'  Yacs.autocomplete_m("tags", "'.$context['url_to_root'].'categories/complete.php");'."\n"
		.'});  '."\n"
		.JS_SUFFIX;

	// content of the help box
	$help = '';

	// splash message for new pages
	if(!isset($item['id']) && !count($context['error']))
		$help .= '<p>'.i18n::s('Please describe the new section and hit the submit button. You will then be able to post images, files and links on subsequent forms.').'</p>';

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

	// drive associates to the Content Assistant
	if(Surfer::is_associate())
		$help .= '<p>'.sprintf(i18n::s('Use the %s to populate this server.'), Skin::build_link('help/populate.php', i18n::s('Content Assistant'), 'shortcut')).'</p>'."\n";

	// in a side box
	$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'boxes', 'help');

}

// render the skin
render_skin();

?>
