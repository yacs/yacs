<?php
/**
 * to test the option 'edit_as_simple'
 *
 * This script allows only to change the description field, and overlay data.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// loaded from articles/view.php
defined('YACS') or exit('Script must be included');

// process uploaded data
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// set in articles/edit.php, but we don't use it here
	unset($_REQUEST['options']);

	// update an existing page
	if(isset($_REQUEST['id'])) {

		// remember the previous version
		if($item['id'] && Versions::are_different($item, $_REQUEST))
			Versions::save($item, 'article:'.$item['id']);

		// stop on error
		if(!Articles::put_attributes($_REQUEST) || (is_object($overlay) && !$overlay->remember('update', $_REQUEST))) {
			$item = $_REQUEST;
			$with_form = TRUE;

		// else display the updated page
		} else {

			// touch the related anchor, but only if the page has been published
			if(isset($item['publish_date']) && ($item['publish_date'] > NULL_DATE))
				$anchor->touch('article:update', $_REQUEST['id'], isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y'));

			// add this page to poster watch list
			if(Surfer::get_id())
				Members::assign('article:'.$item['id'], 'user:'.Surfer::get_id());

			// display the updated page
			Safe::redirect($context['url_to_home'].$context['url_to_root'].Articles::get_permalink($item));
		}


	// create a new page
	} elseif(!$_REQUEST['id'] = Articles::post($_REQUEST)) {
		$item = $_REQUEST;
		$with_form = TRUE;

	// successful post
	} else {

		// allow back-referencing from overlay
		$_REQUEST['self_reference'] = 'article:'.$_REQUEST['id'];
		$_REQUEST['self_url'] = $context['url_to_root'].Articles::get_permalink($_REQUEST);

		// post an overlay, with the new article id --don't stop on error
		if(is_object($overlay))
			$overlay->remember('insert', $_REQUEST);

		// increment the post counter of the surfer
		if(Surfer::get_id())
			Users::increment_posts(Surfer::get_id());

		// touch the related anchor, but only if the page has been published
		if(isset($_REQUEST['publish_date']) && ($_REQUEST['publish_date'] > NULL_DATE)) {
			$anchor->touch('article:create', $_REQUEST['id'], isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y'));

			// advertise public pages
			if(isset($_REQUEST['active']) && ($_REQUEST['active'] == 'Y')) {

				// pingback, if any
				Links::ping($_REQUEST['description'], 'article:'.$_REQUEST['id']);

				// ping servers
				Servers::notify($anchor->get_url());

			}

			// 'publish' hook
			if(is_callable(array('Hooks', 'include_scripts')))
				Hooks::include_scripts('publish', $_REQUEST['id']);

		}

		// get the new item
		$article =& Anchors::get('article:'.$_REQUEST['id'], TRUE);

		// page title
		$context['page_title'] = i18n::s('Thank you for your contribution');

		// the page has been published
		if(isset($_REQUEST['publish_date']) && ($_REQUEST['publish_date'] > NULL_DATE))
			$context['text'] .= i18n::s('<p>The new page has been successfully published. Please review it now to ensure that it reflects your mind.</p>');

		// remind that the page has to be published
		elseif(Surfer::is_empowered())
			$context['text'] .= i18n::s('<p>Don\'t forget to publish the new page someday. Review the page, enhance it and then click on the Publish command to make it publicly available.</p>');

		// section ask for auto-publish, but the surfer has posted a draft document
		elseif((isset($context['users_with_auto_publish']) && ($context['users_with_auto_publish'] == 'Y')) || (is_object($anchor) && $anchor->has_option('auto_publish')))
			$context['text'] .= i18n::s('<p>Don\'t forget to publish the new page someday. Review the page, enhance it and then click on the Publish command to make it publicly available.</p>');

		// reward regular members
		else
			$context['text'] .= i18n::s('<p>The new page will now be reviewed before its publication. It is likely that this will be done within the next 24 hours at the latest.</p>');

		// list persons that have been notified
		$context['text'] .= Mailer::build_recipients(i18n::s('Persons that have been notified of your post'));

		// list persons that have been notified
		$context['text'] .= Servers::build_endpoints(i18n::s('Servers that have been notified of your post'));

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu = array_merge($menu, array($article->get_url() => i18n::s('View the page')));
		if(Surfer::may_upload()) {
			$menu = array_merge($menu, array('images/edit.php?anchor='.urlencode('article:'.$_REQUEST['id']) => i18n::s('Add an image')));
			$menu = array_merge($menu, array('files/edit.php?anchor='.urlencode('article:'.$_REQUEST['id']) => i18n::s('Upload a file')));
		}
		$menu = array_merge($menu, array('links/edit.php?anchor='.urlencode('article:'.$_REQUEST['id']) => i18n::s('Add a link')));
		if((!isset($_REQUEST['publish_date']) || ($_REQUEST['publish_date'] <= NULL_DATE)) && Surfer::is_empowered())
			$menu = array_merge($menu, array(Articles::get_url($_REQUEST['id'], 'publish') => i18n::s('Publish the page')));
		if(Surfer::get_email_address() && isset($context['with_email']) && ($context['with_email'] == 'Y'))
			$menu = array_merge($menu, array(Articles::get_url($_REQUEST['id'], 'invite') => i18n::s('Invite participants')));
		if(is_object($anchor) && Surfer::is_empowered())
			$menu = array_merge($menu, array('articles/edit.php?anchor='.urlencode($anchor->get_reference()) => i18n::s('Add another page')));
		$follow_up .= Skin::build_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

		// log the creation of a new page
		if(!Surfer::is_empowered())
			$label = sprintf(i18n::c('New submission: %s'), strip_tags($article->get_title()));
		else
			$label = sprintf(i18n::c('New page: %s'), strip_tags($article->get_title()));

		// poster and target section
		if(is_object($anchor))
			$description = sprintf(i18n::s('Sent by %s in %s'), Surfer::get_name(), $anchor->get_title())."\n\n";
		else
			$description = sprintf(i18n::s('Sent by %s'), Surfer::get_name())."\n\n";

		// title and link
		if($title = $article->get_title())
			$description .= $title."\n";
		$description = $context['url_to_home'].$context['url_to_root'].$article->get_url()."\n\n";

		// teaser
		if($teaser = $article->get_teaser('basic'))
			$description .= "\n\n".$teaser."\n\n";

		// notify sysops
		Logger::notify('articles/edit.php', $label, $description);

	}

// display the form on GET
} else
	$with_form = TRUE;

// display the form
if($with_form) {

	// the form to edit an article
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>';
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

	// tags
	$label = i18n::s('Tags');
	$input = '<input type="text" name="tags" id="tags" value="'.encode_field(isset($item['tags'])?$item['tags']:'').'" size="45" maxlength="255" accesskey="t" /><div id="tags_choices" class="autocomplete"></div>';
	$hint = i18n::s('A comma-separated list of keywords');
	$fields[] = array($label, $input, $hint);

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
	// attachments tab
	//
	$text = '';

	// the icon url may be set after the page has been created
	if(isset($item['id']) && Surfer::is_empowered() && Surfer::is_member()) {
		$label = i18n::s('Image');

		// show the current icon
		if(isset($item['icon_url']) && $item['icon_url']) {
			$input = '<img src="'.preg_replace('/\/images\/article\/[0-9]+\//', "\\0thumbs/", $item['icon_url']).'" alt="" />';
			$command = i18n::s('Change');
		} else {
			$input = '<span class="details">'.i18n::s('Upload an image to be displayed at page top').'</span>';
			$command = i18n::s('Add an image');
		}

		$value = '';
		if(isset($item['icon_url']) && $item['icon_url'])
			$value = $item['icon_url'];
		$input .= BR.'<input type="text" name="icon_url" size="55" value="'.encode_field($value).'" maxlength="255" />';
		if(Surfer::may_upload())
			$input .= ' <span class="details">'.Skin::build_link('images/edit.php?anchor='.urlencode('article:'.$item['id']).'&amp;action=icon', $command, 'button').'</span>';
		$fields[] = array($label, $input);
	}

	// the thumbnail url may be set after the page has been created
	if(isset($item['id']) && Surfer::is_empowered() && Surfer::is_member()) {
		$label = i18n::s('Thumbnail');

		// show the current thumbnail
		if(isset($item['thumbnail_url']) && $item['thumbnail_url']) {
			$input = '<img src="'.$item['thumbnail_url'].'" alt="" />';
			$command = i18n::s('Change');
		} else {
			$input = '<span class="details">'.i18n::s('Upload a small image to illustrate this page when it is listed into parent page.').'</span>';
			$command = i18n::s('Add an image');
		}

		$input .= BR.'<input type="text" name="thumbnail_url" size="55" value="'.encode_field(isset($item['thumbnail_url']) ? $item['thumbnail_url'] : '').'" maxlength="255" />';
		if(Surfer::may_upload())
			$input .= ' <span class="details">'.Skin::build_link('images/edit.php?anchor='.urlencode('article:'.$item['id']).'&amp;action=thumbnail', $command, 'button').'</span>';
		$fields[] = array($label, $input);
	}

	// end of regular fields
	$text .= Skin::build_form($fields);
	$fields = array();

	// splash message for new items
	if(!isset($item['id']))
		$text .= Skin::build_box(i18n::s('Images'), '<p>'.i18n::s('Submit the new page, and you will be able to add images afterwards.').'</p>', 'folded');

	// the list of images
	elseif($items = Images::list_by_date_for_anchor('article:'.$item['id']))
		$text .= Skin::build_box(i18n::s('Images'), Skin::build_list($items, 'decorated'), 'unfolded', 'edit_images');

	// display in a separate panel
	if($text)
		$panels[] = array('media', i18n::s('Media'), 'media_panel', $text);

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
		$menu[] = Skin::build_link(Articles::get_permalink($item), i18n::s('Cancel'), 'span');

	// insert the menu in the page
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// several options to check
	$input = array();

	// keep as draft
	if(!isset($item['id']))
		$input[] = '<input type="checkbox" name="option_draft" value="Y" /> '.i18n::s('This is a draft document. Do not publish the page, even if auto-publish has been enabled.');

	// validate page content
	if(Surfer::is_associate())
		$input[] = '<input type="checkbox" name="option_validate" value="Y" checked="checked" /> '.i18n::s('Ensure this post is valid XHTML.');

	// append post-processing options
	if($input)
		$context['text'] .= '<p>'.implode(BR, $input).'</p>';

	// transmit the id as a hidden field
	if(isset($item['id']) && $item['id'])
		$context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'" />';

	// anchor cannot be changed
	if(is_object($anchor))
		$context['text'] .= '<input type="hidden" name="anchor" value="'.$anchor->get_reference().'" />';

	// end of the form
	$context['text'] .= '</div></form>';

	// the script used for form handling at the browser
	$context['page_footer'] .= JS_PREFIX
		.'// check that main fields are not empty'."\n"
		.'func'.'tion validateDocumentPost(container) {'."\n"
		."\n"
		.'	// title is mandatory'."\n"
		.'	if(!Yacs.trim(container.title.value)) {'."\n"
		.'		alert("'.i18n::s('Please provide a meaningful title.').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		$("title").focus();'."\n"
		.'		return false;'."\n"
		.'	}'."\n"
		.'	// extend validation --used in overlays'."\n"
		.'	if(typeof validateOnSubmit == "function") {'."\n"
		.'		return validateOnSubmit(container);'."\n"
		.'	}'."\n"
		."\n";

	// warning on jumbo size, but only on first post
	if(!isset($item['id']))
		$context['page_footer'] .= '	if(container.description.value.length > 64000){'."\n"
			.'		return confirm("'.i18n::s('Page content exceeds 64,000 characters. Do you confirm you are intended to post a jumbo page?').'");'."\n"
			.'	}'."\n"
			."\n";

	$context['page_footer'] .= '	// successful check'."\n"
		.'	return true;'."\n"
		.'}'."\n"
		."\n"
		.'// set the focus on first form field'."\n"
		.'Event.observe(window, "load", function() { $("title").focus() });'."\n"
		."\n"
		.'// enable tags autocompletion'."\n"
		.'Event.observe(window, "load", function() { new Ajax.Autocompleter("tags", "tags_choices", "'.$context['url_to_root'].'categories/complete.php", { paramName: "q", minChars: 1, frequency: 0.4, tokens: "," }); });'."\n"
		.JS_SUFFIX;

	// clear session data now that we have populated the form
	unset($_SESSION['anchor_reference']);
	unset($_SESSION['pasted_blogid']);
	unset($_SESSION['pasted_name']);
	unset($_SESSION['pasted_section']);
	unset($_SESSION['pasted_source']);
	unset($_SESSION['pasted_text']);
	unset($_SESSION['pasted_title']);

	// content of the help box
	$help = '';

	// capture help messages from the overlay, if any
	if(is_object($overlay))
		$help .= $overlay->get_label('help', isset($item['id'])?'edit':'new');

	// splash message for new pages
	if(!isset($item['id']))
		$help .= '<p>'.i18n::s('Please type the text of your new page and hit the submit button. You will then be able to post images, files and links on subsequent forms.').'</p>';

	// html and codes
	$help .= '<p>'.sprintf(i18n::s('%s and %s are available to enhance text rendering.'), Skin::build_link('codes/', i18n::s('YACS codes'), 'help'), Skin::build_link('smileys/', i18n::s('smileys'), 'help')).'</p>';

 	// locate mandatory fields
 	$help .= '<p>'.i18n::s('Mandatory fields are marked with a *').'</p>';

	// in a side box
	$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'boxes', 'help');

}

// render the skin
render_skin();



?>