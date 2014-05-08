<?php
/**
 * a streamlined form to change an article
 *
 * This script allows to change a limited number of items in a page:
 * - page title
 * - the description field, and overlay data
 * - tags
 * - access restrictions, if any
 *
 * This script also allows to attach an uploaded file during page creation.
 *
 * Site associates have access to the full set of options, if required to access complex
 * elements of information.
 *
 * Add the option 'edit_as_simple' to activate this script in some article.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// loaded from articles/view.php
defined('YACS') or exit('Script must be included');

// process uploaded data
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// update an existing page
	if(isset($_REQUEST['id'])) {

		// remember the previous version
		if($item['id'] && Versions::are_different($item, $_REQUEST))
			Versions::save($item, 'article:'.$item['id']);

		// stop on error
		if(!Articles::put_attributes($_REQUEST) || (is_object($overlay) && !$overlay->remember('update', $_REQUEST, 'article:'.$_REQUEST['id']))) {
			$item = $_REQUEST;
			$with_form = TRUE;

		// else display the updated page
		} else {

			// do whatever is necessary on page update
			Articles::finalize_update($anchor, $_REQUEST, $overlay,
				isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y'),
				isset($_REQUEST['notify_watchers']) && ($_REQUEST['notify_watchers'] == 'Y'),
				isset($_REQUEST['notify_followers']) && ($_REQUEST['notify_followers'] == 'Y'));

			// cascade changes on access rights
			if($_REQUEST['active'] != $item['active'])
				Anchors::cascade('article:'.$item['id'], $_REQUEST['active']);

			// the page has been modified
			$context['text'] .= '<p>'.i18n::s('The page has been successfully updated.').'</p>';

			// display the updated page
			if(!$recipients = Mailer::build_recipients('article:'.$item['id']))
				Safe::redirect(Articles::get_permalink($item));

			// list persons that have been notified
			$context['text'] .= $recipients;

			// follow-up commands
			$follow_up = i18n::s('What do you want to do now?');
			$menu = array();
			$menu = array_merge($menu, array(Articles::get_permalink($_REQUEST) => i18n::s('View the page')));
			if(Surfer::may_upload())
				$menu = array_merge($menu, array('files/edit.php?anchor='.urlencode('article:'.$item['id']) => i18n::s('Add a file')));
			if((!isset($item['publish_date']) || ($item['publish_date'] <= NULL_DATE)) && Surfer::is_empowered())
				$menu = array_merge($menu, array(Articles::get_url($item['id'], 'publish') => i18n::s('Publish the page')));
			$follow_up .= Skin::build_list($menu, 'menu_bar');
			$context['text'] .= Skin::build_block($follow_up, 'bottom');

		}


	// create a new page
	} elseif(!$_REQUEST['id'] = Articles::post($_REQUEST)) {
		$item = $_REQUEST;
		$with_form = TRUE;

	// successful post
	} else {

		// page title
		$context['page_title'] = i18n::s('Thank you for your contribution');

		// the page has been published
		if(isset($_REQUEST['publish_date']) && ($_REQUEST['publish_date'] > NULL_DATE))
			$context['text'] .= '<p>'.i18n::s('The page has been successfully posted. Please review it now to ensure that it reflects your mind.').'</p>';

		// remind that the page has to be published
		elseif(Surfer::is_empowered())
			$context['text'] .= i18n::s('<p>Don\'t forget to publish the new page someday. Review the page, enhance it and then click on the Publish command to make it publicly available.</p>');

		// section ask for auto-publish, but the surfer has posted a draft document
		elseif((isset($context['users_with_auto_publish']) && ($context['users_with_auto_publish'] == 'Y')) || (is_object($anchor) && $anchor->has_option('auto_publish')))
			$context['text'] .= i18n::s('<p>Don\'t forget to publish the new page someday. Review the page, enhance it and then click on the Publish command to make it publicly available.</p>');

		// reward regular members
		else
			$context['text'] .= i18n::s('<p>The new page will now be reviewed before its publication. It is likely that this will be done within the next 24 hours at the latest.</p>');

		// attach some file
		$path = Files::get_path('article:'.$_REQUEST['id']);
		if(isset($_FILES['upload']) && ($uploaded = Files::upload($_FILES['upload'], $path, 'article:'.$_REQUEST['id']))) {

			// several files have been added
			if(is_array($uploaded)) {
				$context['text'] .= '<p>'.i18n::s('Following files have been added:').'</p>'
					.Skin::build_list(Files::list_for_anchor_and_name('article:'.$_REQUEST['id'], $uploaded, 'compact'), 'compact');

				$_REQUEST['first_comment'] = '<div>'.Skin::build_list(Files::list_for_anchor_and_name('article:'.$_REQUEST['id'], $uploaded, 'compact'), 'compact').'</div>';

			// one file has been added
			} elseif($file =& Files::get_by_anchor_and_name('article:'.$_REQUEST['id'], $uploaded)) {
				$context['text'] .= '<p>'.i18n::s('Following file has been added:').'</p>'
					.Codes::render_object('file', $file['id']);

				$_REQUEST['first_comment'] = '<div>'.Codes::render_object('file', $file['id']).'</div>';

				// silently delete the previous file if the name has changed
				if(isset($file['file_name']) && ($file['file_name'] != $uploaded))
					Safe::unlink($file_path.'/'.$file['file_name']);

			}

		}

		// capture first comment too
		if(isset($_REQUEST['first_comment']) && $_REQUEST['first_comment']) {
			include_once $context['path_to_root'].'comments/comments.php';
			$fields = array();
			$fields['anchor'] = 'article:'.$_REQUEST['id'];
			$fields['description'] = $_REQUEST['first_comment'];
			Comments::post($fields);
		}

		// post an overlay, with the new article id --don't stop on error
		if(is_object($overlay))
			$overlay->remember('insert', $_REQUEST, 'article:'.$_REQUEST['id']);

		// increment the post counter of the surfer
		Users::increment_posts(Surfer::get_id());

		// do whatever is necessary on page publication
		if(isset($_REQUEST['publish_date']) && ($_REQUEST['publish_date'] > NULL_DATE)) {

			Articles::finalize_publication($anchor, $_REQUEST, $overlay,
				isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y'),
				isset($_REQUEST['notify_followers']) && ($_REQUEST['notify_followers'] == 'Y'));

		// else do whatever is necessary on page submission
		} else
			Articles::finalize_submission($anchor, $_REQUEST, $overlay);

		// get the new item
		$article = Anchors::get('article:'.$_REQUEST['id'], TRUE);

		// list persons that have been notified
		$context['text'] .= Mailer::build_recipients('article:'.$_REQUEST['id']);

		// list endpoints that have been notified
		$context['text'] .= Servers::build_endpoints(i18n::s('Servers that have been notified'));

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu = array_merge($menu, array($article->get_url() => i18n::s('View the page')));
		if(Surfer::may_upload())
			$menu = array_merge($menu, array('files/edit.php?anchor='.urlencode('article:'.$_REQUEST['id']) => i18n::s('Add a file')));
		if((!isset($_REQUEST['publish_date']) || ($_REQUEST['publish_date'] <= NULL_DATE)) && Surfer::is_empowered())
			$menu = array_merge($menu, array(Articles::get_url($_REQUEST['id'], 'publish') => i18n::s('Publish the page')));
		if(is_object($anchor) && Surfer::is_empowered())
			$menu = array_merge($menu, array('articles/edit.php?anchor='.urlencode($anchor->get_reference()) => i18n::s('Add another page')));
		$follow_up .= Skin::build_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	}

// display the form on GET
} else
	$with_form = TRUE;

// display the form
if($with_form) {

	// the form to edit an article
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form" enctype="multipart/form-data"><div>';
	$fields = array();

	//
	// panels
	//
	$panels = array();

	//
	// information tab
	//
	$text = '';

	// additional fields for anonymous surfers
	if(!Surfer::is_logged()) {

		// splash
		if(isset($item['id']))
			$login_url = $context['url_to_root'].'users/login.php?url='.urlencode('articles/edit.php?id='.$item['id']);
		elseif(is_object($anchor))
			$login_url = $context['url_to_root'].'users/login.php?url='.urlencode('articles/edit.php?anchor='.$anchor->get_reference());
		else
			$login_url = $context['url_to_root'].'users/login.php?url='.urlencode('articles/edit.php');
		$text .= '<p>'.sprintf(i18n::s('If you have previously registered to this site, please %s. Then the server will automatically put your name and address in following fields.'), Skin::build_link($login_url, 'authenticate'))."</p>\n";

		// the name, if any
		$label = i18n::s('Your name');
		$input = '<input type="text" name="edit_name" size="45" maxlength="128" accesskey="n" value="'.encode_field(Surfer::get_name(' ')).'" />';
		$hint = i18n::s('Let us a chance to know who you are');
		$fields[] = array($label, $input, $hint);

		// the address, if any
		$label = i18n::s('Your e-mail address');
		$input = '<input type="text" name="edit_address" size="45" maxlength="128" accesskey="a" value="'.encode_field(Surfer::get_email_address()).'" />';
		$hint = i18n::s('Put your e-mail address to receive feed-back');
		$fields[] = array($label, $input, $hint);

		// stop robots
		if($field = Surfer::get_robot_stopper())
			$fields[] = $field;

	}

	// the title
	if(!is_object($overlay) || !($label = $overlay->get_label('title', isset($item['id'])?'edit':'new')))
		$label = i18n::s('Title').' *';
	$value = '';
	if(isset($item['title']) && $item['title'])
		$value = $item['title'];
	elseif(isset($_SESSION['pasted_title']))
		$value = $_SESSION['pasted_title'];
	$input = '<textarea name="title" id="title" rows="2" cols="50" accesskey="t">'.encode_field($value).'</textarea>';
	if(!is_object($overlay) || !($hint = $overlay->get_label('title_hint', isset($item['id'])?'edit':'new')))
		$hint = i18n::s('Please provide a meaningful title.');
	$fields[] = array($label, $input, $hint);

	// include overlay fields, if any
	if(is_object($overlay))
		$fields = array_merge($fields, $overlay->get_fields($item));

	// the description label
	if(!is_object($overlay) || !($label = $overlay->get_label('description', isset($item['id'])?'edit':'new')))
		$label = i18n::s('Description');

	// use the editor if possible
	$value = '';
	if(isset($item['description']) && $item['description'])
		$value = $item['description'];
	elseif(isset($_SESSION['pasted_text']))
		$value = $_SESSION['pasted_text'];
	$input = Surfer::get_editor('description', $value);
	if(!is_object($overlay) || !($hint = $overlay->get_label('description_hint', isset($item['id'])?'edit':'new')))
		$hint = '';
	$fields[] = array($label, $input, $hint);

	// allow for an initial upload, if allowed
	if(!isset($item['id']) && Surfer::may_upload() && Files::allow_creation($item,$anchor, 'article')) {

		// attachment label
		$label = i18n::s('Add a file');

		// an upload entry
		$input = '<input type="hidden" name="file_type" value="upload" />'
			.'<input type="file" name="upload" size="30" onchange="if(/\\.zip$/i.test($(this).val())){$(\'#upload_option\').slideDown();}else{$(\'#upload_option\').slideUp();}" />'
			.' (&lt;&nbsp;'.$context['file_maximum_size'].i18n::s('bytes').')'
			.'<div id="upload_option" style="display: none;" >'
			.'<input type="checkbox" name="explode_files" checked="checked" /> '.i18n::s('Extract files from the archive')
			.'</div>';

		$fields[] = array($label, $input);

	}

	// associates will have it on options tab
	if(Surfer::is_associate())
		;

	// the active flag: Yes/public, Restricted/logged, No/associates
	elseif(Articles::is_owned($item, $anchor)) {
		$label = i18n::s('Access');
		$input = Skin::build_active_set_input($item);
		$hint = Skin::build_active_set_hint($anchor);
		$fields[] = array($label, $input, $hint);

	// else preserve attribute given by template, if any
	} else
		$context['text'] .= '<input type="hidden" name="active_set" value="'.$item['active_set'].'" />';

	// end of regular fields
	$text .= Skin::build_form($fields);
	$fields = array();

	// display in a separate panel
	if($text)
		$panels[] = array('information', i18n::s('Information'), 'information_panel', $text);

	//
	// append tabs from the overlay, if any
	//
	if(is_object($overlay) && ($more_tabs = $overlay->get_tabs('edit', $item)))
 		$panels = array_merge($panels, $more_tabs);

	//
	// resources tab
	//
	$text = '';

	// resources attached to this anchor, but not on page creation
	if(isset($item['id'])) {

		// images
		$box = '';
		if(Images::allow_creation($item, $anchor)) {
			$menu = array( 'images/edit.php?anchor='.urlencode('article:'.$item['id']) => i18n::s('Add an image') );
			$box .= Skin::build_list($menu, 'menu_bar');
		}
		if($items = Images::list_by_date_for_anchor('article:'.$item['id']))
			$box .= Skin::build_list($items, 'decorated');
		if($box)
			$text .= Skin::build_box(i18n::s('Images'), $box, 'folded');

		// files
		$box = '';
		if(Files::allow_creation($item, $anchor, 'article')) {
			$menu = array( 'files/edit.php?anchor='.urlencode('article:'.$item['id']) => i18n::s('Add a file') );
			$box .= Skin::build_list($menu, 'menu_bar');
		}
		if($items = Files::list_embeddable_for_anchor('article:'.$item['id'], 0, 50))
			$box .= Skin::build_list($items, 'decorated');
		if($box)
			$text .= Skin::build_box(i18n::s('Files'), $box, 'folded');

		// tables
		$box = '';
		if(Tables::allow_creation($item, $anchor)) {
			$menu = array( 'tables/edit.php?anchor='.urlencode('article:'.$item['id']) => i18n::s('Add a table') );
			$box .= Skin::build_list($menu, 'menu_bar');
		}
		if($items = Tables::list_by_date_for_anchor('article:'.$item['id'], 0, 50, 'article:'.$item['id']))
			$box .= Skin::build_list($items, 'decorated');
		if($box)
			$text .= Skin::build_box(i18n::s('Tables'), $box, 'folded');

	}

	// display in a separate panel
	if($text)
		$panels[] = array('resources', i18n::s('Resources'), 'resources_panel', $text);

	//
	// options tab is visible only to site associates
	//
	if(Surfer::is_associate()) {

		$text = '';

		// provide information to section owner
		if(isset($item['id'])) {

			// owner
			$label = i18n::s('Owner');
			$input = '';
			if(isset($item['owner_id'])) {
				if($owner = Users::get($item['owner_id']))
					$input = Users::get_link($owner['full_name'], $owner['email'], $owner['id']);
				else
					$input = i18n::s('No owner has been found.');
			}

			// change the owner
			if(Articles::is_owned($item, $anchor) || Surfer::is_associate())
				$input .= ' <span class="details">'.Skin::build_link(Articles::get_url($item['id'], 'own'), i18n::s('Change'), 'button').'</span>';

			$fields[] = array($label, $input);

		}

		// the active flag: Yes/public, Restricted/logged, No/associates --we don't care about inheritance, to enable security changes afterwards
		if(Articles::is_owned($item, $anchor) || Surfer::is_associate()) {
			$label = i18n::s('Access');
			$input = Skin::build_active_set_input($item);
			$hint = Skin::build_active_set_hint($anchor);
			$fields[] = array($label, $input, $hint);
		}

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

		// the thumbnail url may be set after the page has been created
		if(isset($item['id']) && Surfer::is_empowered() && Surfer::is_member()) {
			$label = i18n::s('Thumbnail');
			$input = '';
			$hint = '';

			// show the current thumbnail
			if(isset($item['thumbnail_url']) && $item['thumbnail_url']) {
				$input .= '<img src="'.$item['thumbnail_url'].'" alt="" />'.BR;
				$command = i18n::s('Change');
			} else {
				$hint .= i18n::s('Upload a small image to illustrate this page when it is listed into parent page.');
				$command = i18n::s('Add an image');
			}

			$input .= '<input type="text" name="thumbnail_url" size="55" value="'.encode_field(isset($item['thumbnail_url']) ? $item['thumbnail_url'] : '').'" maxlength="255" />';
			if(Surfer::may_upload())
				$input .= ' <span class="details">'.Skin::build_link('images/edit.php?anchor='.urlencode('article:'.$item['id']).'&amp;action=thumbnail', $command, 'button').'</span>';
			$fields[] = array($label, $input, $hint);
		}

		// the rank
		if(Articles::is_owned($item, $anchor) || Surfer::is_associate()) {

			// the default value
			if(!isset($item['rank']))
				$item['rank'] = 10000;

			$label = i18n::s('Rank');
			$input = '<input type="text" name="rank" id="rank" size="10" value="'.encode_field($item['rank']).'" maxlength="255" />';
			$hint = sprintf(i18n::s('For %s pages; regular pages are ranked at %s.'),
				'<a href="#" onclick="$(\'#rank\').value=10; return false;">'.i18n::s('sticky').'</a>',
				'<a href="#" onclick="$(\'#rank\').value=10000; return false;">'.i18n::s('10000').'</a>');
			$fields[] = array($label, $input, $hint);
		}

		// the publication date
		$label = i18n::s('Publication date');
		if(isset($item['publish_date']) && ($item['publish_date'] > NULL_DATE))
			$input = Surfer::from_GMT($item['publish_date']);
		elseif(isset($item['id']) && (Surfer::is_associate() || (Surfer::is_member() && is_object($anchor) && $anchor->is_assigned()))) {
			Skin::define_img('ARTICLES_PUBLISH_IMG', 'articles/publish.gif');
			$input = Skin::build_link(Articles::get_url($item['id'], 'publish'), ARTICLES_PUBLISH_IMG.i18n::s('Publish'), 'basic');
		} else {
			Skin::define_img('ARTICLES_UNPUBLISH_IMG', 'articles/unpublish.gif');
			$input = ARTICLES_UNPUBLISH_IMG.i18n::s('not published');
		}
		$fields[] = array($label, $input);

		// the expiry date
		$label = i18n::s('Expiry date');
		if(isset($item['expiry_date']) && ($item['expiry_date'] > NULL_DATE))
			$input = Surfer::from_GMT($item['expiry_date']);
		else
			$input = i18n::s('never');
		$fields[] = array($label, $input);

		// the parent section
		if(is_object($anchor)) {

			if(isset($item['id']) && Articles::is_owned($item, $anchor)) {
				$label = i18n::s('Section');
				$input =& Skin::build_box(i18n::s('Select parent container'), Sections::get_radio_buttons($anchor->get_reference()), 'folded');
				$fields[] = array($label, $input);
			} else
				$text .= '<input type="hidden" name="anchor" value="'.$anchor->get_reference().'" />';

		}

		// append fields
		if(is_object($anchor))
			$label = sprintf(i18n::s('Contribution to "%s"'), $anchor->get_title());
		else
			$label = i18n::s('Contribution to parent container');
		$text .= Skin::build_box($label, Skin::build_form($fields), 'folded');
		$fields = array();

		// the source
		$label = i18n::s('Source');
		$value = '';
		if(isset($item['source']) && $item['source'])
			$value = $item['source'];
		elseif(isset($_SESSION['pasted_source']))
			$value = $_SESSION['pasted_source'];
		$input = '<input type="text" name="source" value="'.encode_field($value).'" size="45" maxlength="255" accesskey="e" />';
		$hint = i18n::s('Mention your source, if any. Web link (http://...), internal reference ([user=tom]), or free text.');
		$fields[] = array($label, $input, $hint);

		// the nick name
		if(Articles::is_owned($item, $anchor) || Surfer::is_associate()) {
			$label = i18n::s('Nick name');
			$value = '';
			if(isset($item['nick_name']) && $item['nick_name'])
				$value = $item['nick_name'];
			elseif(isset($_SESSION['pasted_name']))
				$value = $_SESSION['pasted_name'];
			$input = '<input type="text" name="nick_name" size="32" value="'.encode_field($value).'" maxlength="64" accesskey="n" />';
			$hint = sprintf(i18n::s('To designate a page by its name in the %s'), Skin::build_link('go.php', 'page selector', 'open'));
			$fields[] = array($label, $input, $hint);
		}

		// rendering options
		if(Articles::is_owned($item, $anchor) || Surfer::is_associate()) {
			$label = i18n::s('Rendering');
			$input = Articles::build_options_input($item);
			$hint = Articles::build_options_hint($item);
			$fields[] = array($label, $input, $hint);
		}

		// language of this page
		$label = i18n::s('Language');
		$input = i18n::get_languages_select(isset($item['language'])?$item['language']:'');
		$hint = i18n::s('Select the language used for this page');
		$fields[] = array($label, $input, $hint);

		// meta information
		$label = i18n::s('Meta information');
		$input = '<textarea name="meta" rows="10" cols="50">'.encode_field(isset($item['meta']) ? $item['meta'] : '').'</textarea>';
		$hint = i18n::s('Type here any XHTML tags to be put in page header.');
		$fields[] = array($label, $input, $hint);

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
				$input .= '<option value="" selected="selected">'.i18n::s('none')."</option>\n";
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

		// remember overlay type
		} elseif(is_object($overlay))
			$text .= '<input type="hidden" name="overlay_type" value="'.encode_field($overlay->get_type()).'" />';


		// add a folded box
		$text .= Skin::build_box(i18n::s('More options'), Skin::build_form($fields), 'folded');
		$fields = array();

		// display in a separate panel
		if($text)
			$panels[] = array('options', i18n::s('Options'), 'options_panel', $text);

		// preserve attributes coming from template duplication
		if(!isset($item['id'])) {
			$hidden = array('behaviors', 'extra', 'icon_url', 'index_map', 'prefix', 'suffix', 'trailer');
			foreach($hidden as $name) {
				if(isset($item[ $name ]))
					$context['text'] .= '<input type="hidden" name="'.$name.'" value="'.$item[ $name ].'" />';
			}
		}

	// or preserve attributes not managed interactively
	} else {

		// preserve overlay type
		if(is_object($overlay))
			$context['text'] .= '<input type="hidden" name="overlay_type" value="'.encode_field($overlay->get_type()).'" />';

		// preserve attributes coming from template duplication
		if(!isset($item['id'])) {
			$hidden = array('behaviors', 'extra', 'icon_url', 'index_map', 'locked', 'meta', 'options', 'prefix', 'rank', 'source', 'suffix', 'thumbnail_url', 'trailer');
			foreach($hidden as $name) {
				if(isset($item[ $name ]))
					$context['text'] .= '<input type="hidden" name="'.$name.'" value="'.$item[ $name ].'" />';
			}
		}

	}

	//
	// assemble all tabs
	//
	$context['text'] .= Skin::build_tabs($panels);

	//
	// bottom commands
	//

	// assistant bottom has been defined in articles/edit.php
	if(isset($context['page_bottom']))
		$context['text'] .= $context['page_bottom'];

	// transmit the id as a hidden field
	if(isset($item['id']) && $item['id'])
		$context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'" />';

	// anchor cannot be changed
	if(is_object($anchor))
		$context['text'] .= '<input type="hidden" name="anchor" value="'.$anchor->get_reference().'" />';

	// end of the form
	$context['text'] .= '</div></form>';

	// clear session data now that we have populated the form
	unset($_SESSION['anchor_reference']);
	unset($_SESSION['pasted_blogid']);
	unset($_SESSION['pasted_name']);
	unset($_SESSION['pasted_section']);
	unset($_SESSION['pasted_source']);
	unset($_SESSION['pasted_text']);
	unset($_SESSION['pasted_title']);

}

// render the skin
render_skin();



?>
