<?php
/**
 * post a new link or update an existing one
 *
 * This is the main script used to post a new link, or to modify an existing one.
 *
 * Each link may have a label, a hovering title, a target window, and a full description.
 *
 * Shortcuts for internal links are accepted. This script will automatically translate:
 * - a link to an article &#91;article=123]
 * - a link to a section &#91;section=123]
 * - a link to a file &#91;file=123]
 * - a link to an image &#91;image=123]
 * - a link to a category &#91;category=123]
 * - a link to a user &#91;user=jean]
 *
 * This script ensures that links stored in the database may be easily classified as being
 * external or internal.
 * This is achieved by applying following transformations to submitted links:
 * - 'www.foo.bar' becomes 'http://www.foo.bar/'
 * - 'http://this_server/path/target' becomes '/path/target'
 * - 'anything@foo.bar' becomes 'mailto:anything@foo.bar'
 *
 * Also, an external link may be displayed in a separate window, or in the current window.
 *
 * On anonymous usage YACS attempts to stop robots by generating a random string and by asking user to type it.
 *
 * When a new link is posted:
 * - the edition date of the link is updated
 * - the user profile is incremented
 * - the edition field of the anchor page is modified with the value 'link:create' (cf. shared/anchor.php)
 *
 * When a link is updated:
 * - the edition date of the link is updated
 * - the edition field of the anchor page is modified with the value 'link:update' (cf. shared/anchor.php)
 *
 * Associates can also decide to not stamp the creation or the modification of the link.
 * If the silent option is checked:
 * - the previous edition date is retained
 * - the anchor page is not modified either
 *
 * A button-based editor is used for the description field.
 * It's aiming to introduce most common [link=codes]codes/index.php[/link] supported by YACS.
 *
 * This script attempts to validate the new or updated article description against a standard PHP XML parser.
 * The objective is to spot malformed or unordered HTML and XHTML tags. No more, no less.
 *
 * The permission assessment is based upon following rules applied in the provided orders:
 * - associates and editors are allowed to move forward
 * - permission is denied if the anchor is not viewable
 * - surfer owns the image (= he is the last editor)
 * - this is a new post and the surfer is an authenticated member and submissions are allowed
 * - permission denied is the default
 *
 * Anonymous (not-logged) surfer are invited to register to be able to post new images.
 *
 * Accepted calls:
 * - edit.php create a new link,	start by selecting an anchor
 * - edit.php/&lt;type&gt;/&lt;id&gt;	create a new link for this anchor
 * - edit.php?anchor=&lt;type&gt;:&lt;id&gt;	upload a new link for the anchor
 * - edit.php/&lt;id&gt;	modify an existing link
 * - edit.php?id=&lt;id&gt; modify an existing link
 *
 * If no anchor data is provided, a list of anchors is proposed to let the surfer select one of them.
 *
 * There is also a special invocation format to be used for direct bookmarking from bookmarklets,
 * such as the one provided by YACS.
 *
 * @see categories/view.php
 *
 * This format is aiming to provide to YACS every necessary parameters, but through a single GET or POST call.
 * Following parameters have to be provided:
 * - [code]account[/code] - the nickname to log in
 * - [code]password[/code] - the related password
 * - [code]link[/code] - the target href
 * - [code]category[/code] - the optional id of the category where the new link will be placed
 * - [code]title[/code] - an optional title for the new link
 * - [code]text[/code] - some optional text to document the link
 *
 * If no authentication data is provided ([code]account[/code] and [code]password[/code]),
 * the surfer is redirected to the login page at [script]users/login.php[/script].
 *
 * Data submitted from a bookmarklet is saved as session data.
 * Therefore information is preserved through any additional steps,
 * including user authentication and category selection.
 *
 * Also, session data is purged on successful article post.
 *
 * This design allows for a generic bookmarklet bound only to the web site.
 *
 * The generic bookmarking bookmarklet is proposed as a direct link to any authenticated member:
 * - at the control panel ([script]control/index.php[/script])
 * - at any user page ([script]users/view.php[/script])
 * - at the main help page ([script]help/index.php[/script])
 *
 * If the anchor for this item specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @see control/index.php
 * @see users/view.php
 * @see help/index.php
 *
 * @author Bernard Paques
 * @author Vincent No&euml;l
 * @author GnapZ
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @tester Ghjmora
 * @tester GnapZ
 * @tester Cyandrea
 * @tester Lucrecius
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../shared/xml.php';	// input validation
include_once 'links.php';

// allow for direct login
if(isset($_REQUEST['account']) && isset($_REQUEST['password'])) {

	// authenticate the surfer and start a session
	if($user = Users::login($_REQUEST['account'], $_REQUEST['password']))
		Surfer::set($user);

}

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]) && !isset($context['arguments'][1]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Links::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($_REQUEST['anchor']))
	$anchor =& Anchors::get($_REQUEST['anchor']);
elseif(isset($_REQUEST['category']) && $_REQUEST['category'])
	$anchor =& Anchors::get('category:'.$_REQUEST['category']);
elseif(isset($_REQUEST['section']) && $_REQUEST['section'])
	$anchor =& Anchors::get('section:'.$_REQUEST['section']);
elseif(isset($context['arguments'][1]))
	$anchor =& Anchors::get($context['arguments'][0].':'.$context['arguments'][1]);
elseif(isset($item['anchor']))
	$anchor =& Anchors::get($item['anchor']);

// anchor owners can do what they want
if(is_object($anchor) && $anchor->is_owned()) {
	Surfer::empower();
	$permitted = TRUE;

// editors can move forward
} elseif(!isset($item['id']) && Links::are_allowed($anchor, $item))
	$permitted = TRUE;

// the anchor has to be viewable by this surfer
elseif(is_object($anchor) && !$anchor->is_viewable())
	$permitted = FALSE;

// surfer owns the item
elseif(isset($item['edit_id']) && Surfer::is($item['edit_id']))
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// do not always show the edition form
$with_form = FALSE;

// load the skin, maybe with a variant
load_skin('links', $anchor);

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'links/' => i18n::s('Links') );

// the title of the page
if($item['id'])
	$context['page_title'] = i18n::s('Edit a link');
else
	$context['page_title'] = i18n::s('Add a link');

// save data in session, if any, to pass through login step or through anchor selection step
if(!Surfer::is_logged() || !is_object($anchor)) {
	if(isset($_REQUEST['anchor']) && $_REQUEST['anchor'])
		$_SESSION['anchor_reference'] = $_REQUEST['anchor'];
	elseif(isset($_REQUEST['category']) && $_REQUEST['category'])
		$_SESSION['anchor_reference'] = 'category:'.$_REQUEST['category'];
	elseif(isset($_REQUEST['section']) && $_REQUEST['section'])
		$_SESSION['anchor_reference'] = 'section:'.$_REQUEST['section'];

	if(isset($_REQUEST['link']) && $_REQUEST['link'])
		$_SESSION['pasted_link'] = utf8::encode($_REQUEST['link']);

	if(isset($_REQUEST['title']) && $_REQUEST['title'])
		$_SESSION['pasted_title'] = utf8::encode($_REQUEST['title']);

	if(isset($_REQUEST['text']) && $_REQUEST['text'])
		$_SESSION['pasted_text'] = utf8::encode($_REQUEST['text']);
}

// always validate input syntax
if(isset($_REQUEST['description']))
	xml::validate($_REQUEST['description']);

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged()) {

		if(isset($item['id']))
			$link = Links::get_url($item['id'], 'edit');
		elseif(isset($_REQUEST['anchor']))
			$link = 'links/edit.php?anchor='.urlencode($_REQUEST['anchor']);
		else
			$link = 'links/edit.php';

		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode($link));
	}

	// permission denied to authenticated user
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// an error occured
} elseif(count($context['error'])) {
	$item = $_REQUEST;
	$with_form = TRUE;

// process uploaded data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// track anonymous surfers
	Surfer::track($_REQUEST);

	// we have to translate this link to an internal link
	if(($attributes = Links::transform_reference($_REQUEST['link_url'])) && $attributes[0]) {

		$_REQUEST['link_url'] = $attributes[0];
		if(!$_REQUEST['title'] && $attributes[1])
			$_REQUEST['title'] = $attributes[1];
		if(!$_REQUEST['description'] && $attributes[2])
			$_REQUEST['description'] = $attributes[2];

	// rewrite links if necessary
	} else {
		$from = array(
			'/^www\.([^\W\.]+?)\.([^\W]+?)/i',
			'/^(http:|https:)\/\/('.preg_quote($context['host_name'], '/').'|'.$_SERVER['SERVER_ADDR'].')(.+)/i',
			'/^(http:|https:)\/\/('.preg_quote($context['host_name'], '/').'|'.$_SERVER['SERVER_ADDR'].')$/i',
			'/^([^:]+?)@([^\W\.]+?)\.([^\W]+?)/i'
			);

		$to = array(
			'http://\\0',
			'\\3',
			'/',
			'mailto:\\0'
			);

		$_REQUEST['link_url'] = preg_replace($from, $to, $_REQUEST['link_url']);
	}

	// an anchor is mandatory
	if(!is_object($anchor)) {
		Logger::error(i18n::s('No anchor has been found.'));
		$item = $_REQUEST;
		$with_form = TRUE;

	// stop robots
	} elseif(Surfer::may_be_a_robot()) {
		Logger::error(i18n::s('Please prove you are not a robot.'));
		$item = $_REQUEST;
		$with_form = TRUE;

	// reward the poster for new posts
	} elseif(!isset($item['id'])) {

		// display the form on error
		if(!$_REQUEST['id'] = Links::post($_REQUEST)) {
			$item = $_REQUEST;
			$with_form = TRUE;

		// follow-up
		} else {

			// touch the related anchor
			$anchor->touch('link:create', $_REQUEST['id'], isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y'));

			// clear cache
			Links::clear($_REQUEST);

			// increment the post counter of the surfer
			Users::increment_posts(Surfer::get_id());

			// thanks
			$context['page_title'] = i18n::s('Thank you for your contribution');

			// the action
			$context['text'] .= '<p>'.i18n::s('The link has been successfully recorded.').'</p>';

			// follow-up commands
			$follow_up = i18n::s('What do you want to do now?');
			$menu = array();
			if(is_object($anchor)) {
				$menu = array_merge($menu, array($anchor->get_url('links') => i18n::s('View the page')));
				$menu = array_merge($menu, array('links/edit.php?anchor='.$anchor->get_reference() => i18n::s('Submit another link')));
			}
			$follow_up .= Skin::build_list($menu, 'menu_bar');
			$context['text'] .= Skin::build_block($follow_up, 'bottom');

			// log the submission of a new link by a non-associate
			if(!Surfer::is_associate() && is_object($anchor)) {
				$label = sprintf(i18n::c('New link at %s'), strip_tags($anchor->get_title()));
				$description = $_REQUEST['link_url']."\n"
					.sprintf(i18n::c('at %s'), $context['url_to_home'].$context['url_to_root'].$anchor->get_url().'#links');
				Logger::notify('links/edit.php', $label, $description);
			}
		}

	// update an existing link
	} else {

		// display the form on error
		if(!Links::put($_REQUEST)) {
			$item = $_REQUEST;
			$with_form = TRUE;

		// follow-up
		} else {

			// touch the related anchor
			$anchor->touch('link:update', $_REQUEST['id'], isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y'));

			// clear cache
			Links::clear($_REQUEST);

			// forward to the updated anchor page
			Safe::redirect($context['url_to_home'].$context['url_to_root'].$anchor->get_url().'#links');
		}
	}

// display the form on GET
} else
	$with_form = TRUE;

// display the form
if($with_form) {

	// the form to edit a link
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>';

	// the category, for direct uploads
	if(!$anchor) {

		// a splash message for new users
		$context['text'] .= Skin::build_block(i18n::s('This script will add this page to one of the sections listed below. If you would like to add a link to an existing page, browse the target page instead and use the adequate command from the menu.'), 'caution')."\n";

		$label = i18n::s('Section');
		$input = '<select name="anchor">'.Sections::get_options(NULL, 'bookmarks').'</select>';
		$hint = i18n::s('Please carefully select a section for your link');
		$fields[] = array($label, $input, $hint);

	// allow for section change
	} elseif($item['id'] && preg_match('/section:/', $current = $anchor->get_reference())) {

		$label = i18n::s('Section');
		$input = '<select name="anchor">'.Sections::get_options($current, NULL).'</select>';
		$hint = i18n::s('Please carefully select a section for your link');
		$fields[] = array($label, $input, $hint);

	// else preserve the previous anchor
	} elseif(is_object($anchor))
		$context['text'] .= '<input type="hidden" name="anchor" value="'.$anchor->get_reference().'" />';


	// additional fields for anonymous surfers
	if(!isset($item['id']) && !Surfer::is_logged()) {

		// splash
		if(isset($item['id'])) {
			if(is_object($anchor))
				$login_url = $context['url_to_root'].'users/login.php?url='.urlencode('links/edit.php?id='.$item['id'].'&anchor='.$anchor->get_reference());
			else
				$login_url = $context['url_to_root'].'users/login.php?url='.urlencode('links/edit.php?id='.$item['id']);
		} else {
			if(is_object($anchor))
				$login_url = $context['url_to_root'].'users/login.php?url='.urlencode('links/edit.php?anchor='.$anchor->get_reference());
			else
				$login_url = $context['url_to_root'].'users/login.php?url='.urlencode('links/edit.php');
		}
		$context['text'] .= '<p>'.sprintf(i18n::s('If you have previously registered to this site, please %s. Then the server will automatically put your name and address in following fields.'), Skin::build_link($login_url, i18n::s('authenticate')))."</p>\n";

		// the name, if any
		$label = i18n::s('Your name');
		$input = '<input type="text" name="edit_name" size="45" maxlength="128" accesskey="n" value="'.encode_field(isset($_REQUEST['edit_name']) ? $_REQUEST['edit_name'] : Surfer::get_name(' ')).'" />';
		$hint = i18n::s('This optional field can be left blank if you wish.');
		$fields[] = array($label, $input, $hint);

		// the address, if any
		$label = i18n::s('Your address');
		$input = '<input type="text" name="edit_address" size="45" maxlength="128" accesskey="a" value="'.encode_field(isset($_REQUEST['edit_address']) ? $_REQUEST['edit_address'] : Surfer::get_email_address()).'" />';
		$hint = i18n::s('e-mail or web address; this field is optional');
		$fields[] = array($label, $input, $hint);

		// stop robots
		if($field = Surfer::get_robot_stopper())
			$fields[] = $field;

	}

	// the link url
	$label = i18n::s('Web address');
	$value = '';
	if(isset($item['link_url']) && $item['link_url'])
		$value = $item['link_url'];
	elseif(isset($_REQUEST['link']))
		$value = $_REQUEST['link'];
	elseif(isset($_SESSION['pasted_link']))
		$value = $_SESSION['pasted_link'];
	$input = '<input type="text" name="link_url" id="link_url" size="55" value="'.encode_field($value).'" maxlength="255" accesskey="a" />';
	$hint = i18n::s('You can either type a plain url (http://) or use [article=&lt;id&gt;] notation');
	$fields[] = array($label, $input, $hint);

	// the title
	$label = i18n::s('Title');
	$value = '';
	if(isset($item['title']) && $item['title'])
		$value = $item['title'];
	elseif(isset($_REQUEST['title']))
		$value = $_REQUEST['title'];
	elseif(isset($_SESSION['pasted_title']))
		$value = $_SESSION['pasted_title'];
	$input = '<input name="title" value="'.encode_field($value).'" size="55" maxlength="255" />';
	$hint = i18n::s('Please provide a meaningful title.');
	$fields[] = array($label, $input, $hint);

	// the hovering title
	$label = i18n::s('Hovering popup');
	$value = '';
	if(isset($item['link_title']) && $item['link_title'])
		$value = $item['link_title'];
	$input = '<input name="link_title" value="'.encode_field($value).'" size="55" maxlength="255" />';
	$hint = i18n::s('This will appear near the link when the mouse is placed on top of it');
	$fields[] = array($label, $input, $hint);

	// the target flag: Inside the existing window or Blank window
	$label = i18n::s('Target window');
	$input = '<input type="radio" name="link_target" value="B"';
	if(!isset($item['link_target']) || ($item['link_target'] != 'I'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Open a separate window for external links')
		.BR.'<input type="radio" name="link_target" value="I"';
	if(isset($item['link_target']) && ($item['link_target'] == 'I'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Stay in same window on click')."\n";
	$fields[] = array($label, $input);

	// the description
	$label = i18n::s('Description');

	// use the editor if possible
	$value = '';
	if(isset($item['description']) && $item['description'])
		$value = $item['description'];
	elseif(isset($_REQUEST['text']))
		$value = $_REQUEST['text'];
	elseif(isset($_SESSION['pasted_text']))
		$value = $_SESSION['pasted_text'];
	$input = Surfer::get_editor('description', $value);
	$fields[] = array($label, $input);

	// build the form
	$context['text'] .= Skin::build_form($fields);

	// bottom commands
	$menu = array();
	$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');
	if(is_object($anchor) && $anchor->is_viewable())
		$menu[] = Skin::build_link($anchor->get_url(), i18n::s('Cancel'), 'span');
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// associates may decide to not stamp changes -- complex command
	if(Surfer::is_associate() && Surfer::has_all())
		$context['text'] .= '<p><input type="checkbox" name="silent" value="Y" /> '.i18n::s('Do not change modification date of the main page.').'</p>';

	// transmit the id as a hidden field
	if(isset($item['id']) && $item['id'])
		$context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'" />';

	// end of the form
	$context['text'] .= '</div></form>';

	// the script used for form handling at the browser
	$context['text'] .= JS_PREFIX
		.'	// check that main fields are not empty'."\n"
		.'	func'.'tion validateDocumentPost(container) {'."\n"
		."\n"
		.'		// link_url is mandatory'."\n"
		.'		if(!container.link_url.value) {'."\n"
		.'			alert("'.i18n::s('Please type a valid link.').'");'."\n"
		.'			Yacs.stopWorking();'."\n"
		.'			return false;'."\n"
		.'		}'."\n"
		."\n"
		.'		// successful check'."\n"
		.'		return true;'."\n"
		.'	}'."\n"
		."\n"
		.'// set the focus on first form field'."\n"
		.'$("link_url").focus();'."\n"
		.JS_SUFFIX."\n";

	// clear session data now we have populated the form
	unset($_SESSION['pasted_link']);
	unset($_SESSION['anchor_reference']);
	unset($_SESSION['pasted_text']);
	unset($_SESSION['pasted_title']);

	// details
	$details = array();

	// last edition
	if(isset($item['edit_name']) && $item['edit_name'])
		$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));

	// hits
	if(isset($item['hits']) && ($item['hits'] > 1))
		$details[] = Skin::build_number($item['hits'], i18n::s('clicks'));

	// all details
	if(@count($details))
		$context['page_details'] .= '<p class="details">'.ucfirst(implode(', ', $details))."</p>\n";

	// general help on this form
	$help = '<p>'.sprintf(i18n::s('You can use following shortcuts to link to other pages of this server: %s'), '&#91;article=&lt;id>] &#91;section=&lt;id>] &#91;category=&lt;id>]').'</p>'
		.'<p>'.i18n::s('Please set a meaningful title to be used instead of the link itself.').'</p>'
		.'<p>'.i18n::s('Also, take the time to describe the link. This field is fully indexed for searches.').'</p>'
		.'<p>'.sprintf(i18n::s('%s and %s are available to enhance text rendering.'), Skin::build_link('codes/', i18n::s('YACS codes'), 'help'), Skin::build_link('smileys/', i18n::s('smileys'), 'help')).'</p>';
	$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'navigation', 'help');

}

// render the skin
render_skin();
?>