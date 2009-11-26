<?php
/**
 * upload a new image or update an existing one
 *
 * If no anchor has been provided to host the image, this script will create one.
 * The title given for the image, or the file name, will be used as the page title.
 * On direct uploads the sender will have the opportunity to select in which section
 * the article has to be created.
 * By default the article will be posted in the first public section appearing at the site map.
 *
 * Depending on the user selection, the image upload may be followed by one action among following option:
 * - append at the bottom of the page
 * - append at the bottom of the page, and set as thumbnail
 * - use as page icon
 * - use as page thumbnail
 *
 * If the anchor is a section or a category, the previous list is changed to:
 * - append at the bottom of the page
 * - use as page icon
 * - use as page thumbnail
 *
 * If the anchor is a user profile, the list is changed to:
 * - append at the bottom of the page
 * - use as user avatar --animated image files are accepted
 *
 * If an image is set as the page icon, it is also used as the default thumnail for this page.
 *
 * A button-based editor is used for the description field.
 * It's aiming to introduce most common [link=codes]codes/index.php[/link] supported by YACS.
 *
 * The content of the uploaded file is checked to ensure we have a valid image, that is, a .GIF, .JPG or .PNG image.
 *
 * Also the uploaded image is automatically resized if it is too large, to limit its width and weight.
 * There are different limits for standard images and for avatars, all set in the configuration
 * panel for rendering.
 * Associates can explicitly ask for no resizing, to manage specific situations (high-resolution photos).
 *
 * @see skins/configure.php
 *
 * Lastly, a thumbnail image is automatically created.
 *
 * This script attempts to validate the new or updated article description against a standard PHP XML parser.
 * The objective is to spot malformed or unordered HTML and XHTML tags. No more, no less.
 *
 * The permission assessment is based upon following rules applied in the provided orders:
 * - uploads can have been administratively disallowed
 * - associates and editors are allowed to move forward
 * - permission is denied if the anchor is not viewable
 * - surfer owns the image (= he is the last editor)
 * - this is a new post and the surfer is an authenticated member and submissions are allowed
 * - permission denied is the default
 *
 * Anonymous (not-logged) surfer are invited to register to be able to post new images.
 *
 * A button-based editor is used for the description field.
 * It's aiming to introduce most common [link=codes]codes/index.php[/link] supported by YACS.
 *
 * Accepted calls:
 * - edit.php upload an image and create an article to host it
 * - edit.php/&lt;type&gt;/&lt;id&gt;			create a new image for this anchor
 * - edit.php?anchor=&lt;type&gt;:&lt;id&gt;	upload a new image for the anchor
 * - edit.php/&lt;id&gt;					modify an existing image
 * - edit.php?id=&lt;id&gt; 			modify an existing image
 *
 * If the anchor for this item specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @tester Viviane Zaniroli
 * @author Vincent No&euml;l
 * @author GnapZ
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @tester Kedare
 * @tester Guillaume Perez
 * @tester Timster
 * @tester Ekilibre
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../shared/xml.php';	// input validation
include_once 'image.php';	// image processing
include_once 'images.php';
include_once '../files/files.php'; //file upload

// the maximum size for uploads
$image_maximum_size = str_replace('M', '000000', Safe::get_cfg_var('upload_max_filesize'));
if((!$image_maximum_size) || $image_maximum_size > 20000000)
	$image_maximum_size = 2000000;

// what to do
$action = '';
if(isset($_REQUEST['action']))
	$action = $_REQUEST['action'];
if(!$action && isset($context['arguments'][0]))
	$action = $context['arguments'][0];
$action = strip_tags($action);

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]) && !isset($context['arguments'][1]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Images::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']))
	$anchor =& Anchors::get($item['anchor']);
elseif(isset($_REQUEST['anchor']))
	$anchor =& Anchors::get($_REQUEST['anchor']);
elseif(isset($context['arguments'][1]))
	$anchor =& Anchors::get($context['arguments'][0].':'.$context['arguments'][1]);

// owners can do what they want on items anchored here
if(is_object($anchor) && $anchor->is_owned())
	Surfer::empower();

// do not accept new files if uploads have been disallowed
if(!isset($item['id']) && !Surfer::may_upload())
	$permitted = FALSE;

// associates and owners can do what they want
elseif(Surfer::is_empowered())
	$permitted = TRUE;

// editors can upload new files
elseif(!isset($item['id']) && is_object($anchor) && $anchor->is_assigned())
	$permitted = TRUE;

// the anchor has to be viewable by this surfer
elseif(is_object($anchor) && !$anchor->is_viewable())
	$permitted = FALSE;

// surfer owns the item
elseif(isset($item['edit_id']) && Surfer::is($item['edit_id']))
	$permitted = TRUE;

// authenticated members can post new items if submission is allowed
elseif(!isset($item['id']) && Surfer::is_member() && (!isset($context['users_without_submission']) || ($context['users_without_submission'] != 'Y')))
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// do not always show the edition form
$with_form = FALSE;

// load the skin, maybe with a variant
load_skin('images', $anchor);

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'images/' => i18n::s('Images') );

// the title of the page
if($item['id'])
	$context['page_title'] = i18n::s('Update an image');
else
	$context['page_title'] = i18n::s('Add an image');

// always validate input syntax
if(isset($_REQUEST['introduction']))
	xml::validate($_REQUEST['introduction']);
if(isset($_REQUEST['description']))
	xml::validate($_REQUEST['description']);

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// an anchor is mandatory
} elseif(!is_object($anchor)) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No anchor has been found.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged()) {

		if(isset($item['id']))
			$link = Images::get_url($item['id'], 'edit');
		elseif(isset($_REQUEST['anchor']))
			$link = 'images/edit.php?anchor='.urlencode($_REQUEST['anchor']);
		else
			$link = 'images/edit.php';

		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode($link));
	}

	// permission denied to authenticated user
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// maybe posts are not allowed here
} elseif(!isset($item['id']) && is_object($anchor) && $anchor->has_option('locked') && !Surfer::is_empowered()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('This page has been locked.'));

// an error occured
} elseif(count($context['error'])) {
	$item = $_REQUEST;
	$with_form = TRUE;

// process uploaded data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// a file has been uploaded
	if(isset($_FILES['upload']['name']) && $_FILES['upload']['name'] && ($_FILES['upload']['name'] != 'none')) {

		// where to put this file
		$file_path = 'images/'.$context['virtual_path'].str_replace(':', '/', $_REQUEST['anchor']);

		// attach some file
		if($file_name = Files::upload($_FILES['upload'], $file_path, array('Image', 'upload')))
			$_REQUEST['image_name'] = $file_name;

		// maybe this image has already been uploaded for this anchor
		if(isset($_REQUEST['anchor']) && ($match =& Images::get_by_anchor_and_name($_REQUEST['anchor'], $file_name))) {

			// if yes, switch to the matching record (and forget the record fetched previously, if any)
			$_REQUEST['id'] = $match['id'];
			$item = $match;
		}

		// remember file size
		$_REQUEST['image_size'] = $_FILES['upload']['size'];

		// silently delete the previous file if the name has changed
		if(isset($item['image_name']) && $item['image_name'] && $file_name && ($item['image_name'] != $file_name) && isset($file_path)) {
			Safe::unlink($file_path.'/'.$item['image_name']);
			Safe::unlink($file_path.'/'.$item['thumbnail_name']);
		}

	// nothing has been posted
	} elseif(!isset($_REQUEST['id']))
		Logger::error(i18n::s('No file has been transmitted.'));

	// an error has already been encountered
	if(count($context['error'])) {

		$item = $_REQUEST;
		$with_form = TRUE;

	// display the form on error
	} elseif(!$_REQUEST['id'] = Images::post(array_merge($_REQUEST, $_FILES))) {
		$item = $_REQUEST;
		$with_form = TRUE;

	// reward the poster for new posts
	} elseif(!isset($item['id'])) {

		// increment the post counter of the surfer
		Users::increment_posts(Surfer::get_id());

		// thanks
		$context['page_title'] = i18n::s('Thank you for your contribution');

		// show image attributes
		$attributes = array();
		if($_REQUEST['image_name'])
			$attributes[] = $_REQUEST['image_name'];
		if($url = Images::get_thumbnail_href($_REQUEST)) {
			$stuff = '<img src="'.$url.'" alt="" />';
			$attributes[] = Skin::build_link(Images::get_url($_REQUEST['id']), $stuff, 'basic');
		}
		if(is_array($attributes))
			$context['text'] .= '<p>'.implode(BR, $attributes)."</p>\n";

		// the action
		if(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'set_as_icon')) {
			$action = 'image:set_as_icon';
		} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'set_as_avatar')) {
			$action = 'image:set_as_avatar';
			$context['text'] .= '<p>'.i18n::s('The image has become the profile picture.').'</p>';
		} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'set_as_thumbnail')) {
			$action = 'image:set_as_thumbnail';
			$context['text'] .= '<p>'.i18n::s('This has become the thumbnail image of the page.').'</p>';
		} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'set_as_both')) {
			$action = 'image:set_as_both';
			$context['text'] .= '<p>'.i18n::s('The image has been added, and it also has been set as the page thumbnail.').'</p>';
		} else {
			$action = 'image:create';
			$context['text'] .= '<p>'.i18n::s('The image has been inserted.').'</p>';
		}

		// touch the related anchor
		$anchor->touch($action, $_REQUEST['id'], isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y'));

		// list persons that have been notified
		$context['text'] .= Mailer::get_recipients(i18n::s('Persons that have been notified of your post'));

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu = array_merge($menu, array($anchor->get_url() => i18n::s('View the page')));
		$menu = array_merge($menu, array($anchor->get_url('edit') => i18n::s('Edit the page')));
		$menu = array_merge($menu, array('images/edit.php?anchor='.$anchor->get_reference() => i18n::s('Submit another image')));
		$follow_up .= Skin::build_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

		// clear cache
		Images::clear($_REQUEST);

		// log the submission by a non-associate
		if(!Surfer::is_associate() && is_object($anchor)) {
			$label = sprintf(i18n::c('New image in %s'), strip_tags($anchor->get_title()));
			$description = sprintf(i18n::s('%s at %s'), $_REQUEST['image_name']."\n", $context['url_to_home'].$context['url_to_root'].Images::get_url($_REQUEST['id']));
			Logger::notify('images/edit.php', $label, $description);
		}

	// update an existing image
	} else {

		// the action
		if(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'set_as_icon'))
			$action = 'image:set_as_icon';
		elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'set_as_avatar'))
			$action = 'image:set_as_avatar';
		elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'set_as_thumbnail'))
			$action = 'image:set_as_thumbnail';
		elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'set_as_both'))
			$action = 'image:set_as_both';
		else
			$action = 'image:update';

		// touch the related anchor
		$anchor->touch($action, $_REQUEST['id'], isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y'));

		// clear cache
		Images::clear($_REQUEST);

		// forward to the view page
		Safe::redirect($context['url_to_home'].$context['url_to_root'].Images::get_url($_REQUEST['id']));

	}

// display the form on GET
} else
	$with_form = TRUE;

// display the form
if($with_form) {

	// the form to edit an image
	$context['text'] .= '<form method="post" enctype="multipart/form-data" action="'.$context['script_url'].'" id="main_form"><div>';
	$fields = array();

	// we are updating a user profile
	if(is_object($anchor) && preg_match('/^user:/i', $anchor->get_reference()))
		$context['text'] .= '<p>'.i18n::s('Please upload an image to illustrate this user profile.').'</p>';

	// explicit avatar
	elseif($action == 'avatar')
		$context['text'] .= '<p>'.i18n::s('Please upload an image to illustrate this user profile.').'</p>';

	// use as page thumbnail
	elseif($action == 'thumbnail')
		$context['text'] .= '<p>'.i18n::s('Please upload a thumbnail image for this page.').'</p>';

	// generic splash message
	elseif(is_object($anchor))
		$context['text'] .= '<p>'.sprintf(i18n::s('Please upload an image to illustrate this page. To transmit several images in one single operation, go to %s instead.'), Skin::build_link('images/upload.php?anchor='.urlencode($anchor->get_reference()), i18n::s('Bulk upload'), 'shortcut')).'</p>';

	// the section
	if($anchor)
		$context['text'] .= '<input type="hidden" name="anchor" value="'.$anchor->get_reference().'" />';

	// the image
	$label = i18n::s('Image');
	$input = '';
	$hint = '';

	// display info on current version
	if(isset($item['id']) && $item['id']) {
		$details = array();

		// file name
		if(isset($item['image_name']))
			$details[] = $item['image_name'];

		// last edition
		if($item['edit_name'])
			$details[] = sprintf(i18n::s('posted by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));

		// file size
		if(isset($item['image_size']))
			$details[] = Skin::build_number($item['image_size'], i18n::s('bytes'));

		if(count($details))
			$input .= ucfirst(implode(BR, $details)).BR.BR;
	}

	// the upload entry requires rights to upload
	if(Surfer::may_upload()) {

		if(isset($item['id']))
			$input .= i18n::s('Select another image to replace the current one').BR;
		$input .= '<input type="file" name="upload" id="upload" size="30" accesskey="i" title="'.encode_field(i18n::s('Press to select a local file')).'" />'
			.' (&lt;&nbsp;'.Skin::build_number($image_maximum_size, i18n::s('bytes')).')';
		$hint = i18n::s('Select a .png, .gif or .jpeg image.');

	}

	$fields[] = array($label, $input, $hint);

	// not just a bare upload
	if(($action != 'avatar') && ($action != 'icon') && ($action != 'thumbnail')) {

		// the title
		$label = i18n::s('Title');
		$input = '<input type="text" name="title" size="50" value="'.encode_field(isset($item['title'])?$item['title']:'').'" maxlength="255" accesskey="t" />';
		$fields[] = array($label, $input);

		// the description
		$label = i18n::s('Description');
		$input = Surfer::get_editor('description', isset($item['description'])?$item['description']:'');
		$fields[] = array($label, $input);

		// the source
		$label = i18n::s('Source');
		$input = '<input type="text" name="source" size="45" value="'.encode_field(isset($item['source'])?$item['source']:'').'" maxlength="255" accesskey="u" />';
		$hint = i18n::s('If you have got this file from outside sources, please reference these sources here');
		$fields[] = array($label, $input, $hint);

	}

	// we are now entering the advanced options section
	$context['text'] .= Skin::build_form($fields);
	$fields = array();

	// create a thumbnail on direct upload
	if(!isset($item['id']) && !$anchor)
		$context['text'] .= '<input type="hidden" name="action" value="set_as_both" />';

	// use as avatar
	else if($action == 'avatar')
		$context['text'] .= '<input type="hidden" name="action" value="set_as_avatar" />';

	// use as page icon
	elseif($action == 'icon') {

		// we are updating a user profile
		if(is_object($anchor) && preg_match('/^user:/i', $anchor->get_reference()))
			$context['text'] .= '<input type="hidden" name="action" value="set_as_avatar" />';

		// this is not a user profile
		else
			$context['text'] .= '<input type="hidden" name="action" value="set_as_icon" />';

	// use as page thumbnail
	} elseif($action == 'thumbnail')
		$context['text'] .= '<input type="hidden" name="action" value="set_as_thumbnail" />';

	// not just a bare upload
	if(($action != 'avatar') && ($action != 'icon') && ($action != 'thumbnail')) {

		// the link url, but only for associates and authenticated editors
		if(Surfer::is_associate() || (Surfer::is_member() && is_object($anchor) && $anchor->is_assigned())) {
			$label = i18n::s('Link');
			$input = '<input type="text" name="link_url" size="50" value="'.encode_field(isset($item['link_url'])?$item['link_url']:'').'" maxlength="255" accesskey="l" />';
			$hint = i18n::s('You can make this image point to any web page if you wish');
			$fields[] = array($label, $input, $hint);
		}

		// automatic processing
		if(Surfer::is_associate()) {
			$label = i18n::s('Image processing');
			$fields[] = array($label, '<input type="checkbox" name="automatic_process" value="Y" checked="checked" /> '.i18n::s('Automatically resize the image if necessary'));
		} else {
			$context['text'] .= '<input type="hidden" name="automatic_process" value="Y"  />';
		}

		// how to use the thumbnail
		if((Surfer::is_associate() || (Surfer::is_member() && is_object($anchor) && $anchor->is_assigned()))) {
			$label = i18n::s('Insert a thumbnail');
			$input = '<input type="radio" name="use_thumbnail" value="Y"';
			if(!isset($item['use_thumbnail']) || ($item['use_thumbnail'] == 'Y'))
				$input .= ' checked="checked"';
			$input .= '/> '.i18n::s('Instead of the embedded image, but only for large files (>20&nbsp;kbytes)').BR."\n";
			$input .= '<input type="radio" name="use_thumbnail" value="A"';
			if(isset($item['use_thumbnail']) && ($item['use_thumbnail'] == 'A'))
				$input .= ' checked="checked"';
			$input .= '/> '.i18n::s('Always use the thumbnail. Users will click on it to see the full image.').BR."\n";
			$input .= '<input type="radio" name="use_thumbnail" value="N"';
			if(isset($item['use_thumbnail']) && ($item['use_thumbnail'] == 'N'))
				$input .= ' checked="checked"';
			$input .= '/> '.i18n::s('Never. Response times for surfers using modem links may be degraded on big images.')."\n";
			$fields[] = array($label, $input);

		}

		// add a folded box
		if(count($fields)) {
			$context['text'] .= Skin::build_box(i18n::s('Options'), Skin::build_form($fields), 'folded');
			$fields = array();
		}

	} else
		$context['text'] .= '<input type="hidden" name="automatic_process" value="Y" />';

	// bottom commands
	$menu = array();
	$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');
	if(is_object($anchor) && $anchor->is_viewable())
		$menu[] = Skin::build_link($anchor->get_url(), i18n::s('Cancel'), 'span');
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// associates may decide to not stamp changes -- complex command
	if(isset($item['id']) && $item['id'] && (Surfer::is_associate() || (Surfer::is_member() && is_object($anchor) && $anchor->is_assigned())) && Surfer::has_all())
		$context['text'] .= '<p><input type="checkbox" name="silent" value="Y" /> '.i18n::s('Do not change modification date of the main page.').'</p>';

	// transmit the id as a hidden field
	if(isset($item['id']) && $item['id'])
		$context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'" />';

	// end of the form
	$context['text'] .= '</div></form>';

	// the script used for form handling at the browser
	$context['text'] .= JS_PREFIX
		.'// set the focus on first form field'."\n"
		.'$("upload").focus();'."\n"
		.JS_SUFFIX."\n";

	// not just a bare upload
	if(($action != 'avatar') && ($action != 'icon') && ($action != 'thumbnail')) {

		// general help on this form
		$help = '<p>'.i18n::s('Large files (i.e. exceeding 20&nbsp;kbytes) are published as thumbnails. By clicking on thumbnails people can access full-sized pictures. The title is visible while the mouse is over the thumbnail. The description and the source information are displayed along the full-sized picture.').'</p>'
			.'<p>'.sprintf(i18n::s('%s and %s are available to enhance text rendering.'), Skin::build_link('codes/', i18n::s('YACS codes'), 'help'), Skin::build_link('smileys/', i18n::s('smileys'), 'help')).'</p>'
			.'<p>'.i18n::s('Smaller files are embedded as-is. The description and the source fields are more or less useless in this case.').'</p>';
		$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'boxes', 'help');

	}

}

// render the skin
render_skin();

?>