<?php
/**
 * upload a new image or update an existing one
 *
 * @todo allow for the upload of an archive of files
 *
 * If no anchor has been provided to host the image, this script will create one.
 * The title given for the image, or the file name, will be used as the page title.
 * On direct uploads the sender will have the opportunity to select in which section
 * the article has to be created.
 * By default the article will be posted in the first public section appearing at the site map.
 *
 * Depending on the user selection, the image upload may be followed by one action among following option:
 * - insert at the top of the page - reserved to associates on first upload
 * - append at the bottom of the page
 * - append at the bottom of the page, and set as thumbnail
 * - use as page icon
 * - use as page thumbnail
 *
 * If the anchor is a section or a category, the previous list is changed to:
 * - insert at the top of the page - reserved to associates on first upload
 * - append at the bottom of the page
 * - use as page icon
 * - use as page thumbnail
 * - use as bullet
 *
 * If the anchor is a user profile, the list is changed to:
 * - insert at the top of the page - reserved to associates on first upload
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
include_once 'images.php';

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

// editors can do what they want on items anchored here
if(is_object($anchor) && $anchor->is_editable())
	Surfer::empower();

// do not accept new files if uploads have been disallowed
if(!isset($item['id']) && !Surfer::may_upload())
	$permitted = FALSE;

// associates and editors can do what they want
elseif(Surfer::is_associate() || (is_object($anchor) && $anchor->is_editable()))
	$permitted = TRUE;

// the anchor has to be viewable by this surfer
elseif(is_object($anchor) && !$anchor->is_viewable())
	$permitted = FALSE;

// surfer owns the item
elseif(isset($item['edit_id']) && ($item['edit_id'] == Surfer::get_id()))
	$permitted = TRUE;

// authenticated members are allowed to add images to existing pages
elseif(Surfer::is_member() && is_object($anchor))
	$permitted = TRUE;

// authenticated members can post new items if submission is allowed
elseif(Surfer::is_member() && !isset($item['id']) && (!isset($context['users_without_submission']) || ($context['users_without_submission'] != 'Y')))
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

		// access the temporary uploaded file
		$file_upload = $_FILES['upload']['tmp_name'];

		// $_FILES transcoding to utf8 is not automatic
		$_FILES['upload']['name'] = utf8::encode($_FILES['upload']['name']);

		// enhance file name
		$file_name = $_FILES['upload']['name'];
		$file_extension = '';
		$position = strrpos($_FILES['upload']['name'], '.');
		if($position !== FALSE) {
			$file_name = substr($_FILES['upload']['name'], 0, $position);
			$file_extension = strtolower(substr($_FILES['upload']['name'], $position+1));
		}
		$_FILES['upload']['name'] = str_replace(array('.', '_', '%20'), ' ', $file_name);
		if($file_extension)
			$_FILES['upload']['name'] .= '.'.$file_extension;

		// ensure we have a file name
		$file_name = utf8::to_ascii($_FILES['upload']['name']);
		$_REQUEST['image_name'] = $file_name;

		// create an anchor if none has been provided
		if(!is_object($anchor)) {

			// set the title
			if(isset($_REQUEST['title']) && $_REQUEST['title'])
				$fields['title'] = ucfirst(strip_tags($_REQUEST['title']));
			else
				$fields['title'] = ucfirst(strip_tags($file_name));

			// most of time, it is more pertinent to move the description to the article itself
			$fields['description'] = $_REQUEST['description'];
			$_REQUEST['description'] = '';

			$fields['source'] = $_REQUEST['source'];
			$_REQUEST['source'] = '';

			// use the provided section
			if($_REQUEST['section'])
				$fields['anchor'] = $_REQUEST['section'];

			// or select the default section
			else
				$fields['anchor'] = 'section:'.Sections::get_default();

			// create a hosting article for this image
			if($fields['id'] = Articles::post($fields)) {
				$anchor =& Anchors::get('article:'.$fields['id']);
				$_REQUEST['anchor'] = $anchor->get_reference();
			}
			$fields = array();
		}

		// maybe this image has already been uploaded for this anchor
		if(isset($_REQUEST['anchor'])) {
			$existing_row =& Images::get_by_anchor_and_name($_REQUEST['anchor'], $file_name);
			if($existing_row['id']) {
				$item = $existing_row;
				$_REQUEST['id'] = $item['id'];
			}
		}

		// uploads are not allowed
		if(!Surfer::may_upload())
			Logger::error(i18n::s('You are not allowed to perform this operation.'));

		// size exceeds php.ini settings -- UPLOAD_ERR_INI_SIZE
		elseif(isset($_FILES['upload']['error']) && ($_FILES['upload']['error'] == 1))
			Logger::error(i18n::s('The size of this file is over limit.'));

		// size exceeds form limit -- UPLOAD_ERR_FORM_SIZE
		elseif(isset($_FILES['upload']['error']) && ($_FILES['upload']['error'] == 2))
			Logger::error(i18n::s('The size of this file is over limit.'));

		// partial transfer -- UPLOAD_ERR_PARTIAL
		elseif(isset($_FILES['upload']['error']) && ($_FILES['upload']['error'] == 3))
			Logger::error(i18n::s('No file has been transmitted.'));

		// no file -- UPLOAD_ERR_NO_FILE
		elseif(isset($_FILES['upload']['error']) && ($_FILES['upload']['error'] == 4))
			Logger::error(i18n::s('No file has been transmitted.'));

		// zero bytes transmitted
		elseif(!$_FILES['upload']['size'])
			Logger::error(i18n::s('No file has been transmitted.'));

		// an anchor is mandatory to put the file in the file system
		elseif(!is_object($anchor))
			Logger::error(i18n::s('No anchor has been found.'));

		// check provided upload name
		elseif(!Safe::is_uploaded_file($file_upload))
			Logger::error(i18n::s('Possible file attack.'));

		// we accept only valid images
		elseif(!$image_information = Safe::GetImageSize($file_upload))
			Logger::error(sprintf(i18n::s('No image information in %s'), $file_name));

		// we accept only gif, jpeg and png
		elseif(($image_information[2] != 1) && ($image_information[2] != 2) && ($image_information[2] != 3))
			Logger::error(sprintf(i18n::s('Rejected file type %s'), $file_name));

		// save the image into the web space
		else {

			// create folders
			$file_path = $context['path_to_root'].'images/'.$context['virtual_path'].str_replace(':', '/', $_REQUEST['anchor']).'/';
			Safe::make_path($file_path.'thumbs');

			// move the uploaded file
			if(!Safe::move_uploaded_file($file_upload, $file_path.$file_name))
				Logger::error(sprintf(i18n::s('Impossible to move the upload file to %s'), $file_path.$file_name));

			// this will be filtered by umask anyway
			else
				Safe::chmod($file_path.$file_name, $context['file_mask']);

			// remember file size
			$_REQUEST['image_size'] = $_FILES['upload']['size'];

			// silently delete the previous image if the name has changed
			if($item['image_name'] && $file_name && ($item['image_name'] != $file_name)) {
				$file_path = $context['path_to_root'].'images/'.$context['virtual_path'].str_replace(':', '/', $item['anchor']).'/';
				Safe::unlink($file_path.$item['image_name']);
				Safe::unlink($file_path.$item['thumbnail_name']);
			}

			// build a thumbnail
			$_REQUEST['thumbnail_name'] = 'thumbs/'.$file_name;

			// do not stop on error
			include_once $context['path_to_root'].'images/image.php';
			if(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'set_as_avatar'))
				Image::shrink($file_path.$file_name, $file_path.$_REQUEST['thumbnail_name'], TRUE, TRUE);
			else
				Image::shrink($file_path.$file_name, $file_path.$_REQUEST['thumbnail_name'], FALSE, TRUE);

			// always limit the size of avatar images
			if(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'set_as_avatar')) {
				if(Image::adjust($file_path.$file_name, TRUE, 'avatar'))
					$_REQUEST['image_size'] = Safe::filesize($file_path.$file_name);

			// resize the image where applicable
			} elseif(isset($_REQUEST['automatic_process'])) {
				if(Image::adjust($file_path.$file_name, TRUE, 'standard'))
					$_REQUEST['image_size'] = Safe::filesize($file_path.$file_name);

			}

			// all details
			$details = array();

			// extract exif information from JPEG files, if any -- BUGGY function !!!
//			if(($image_information[2] == 2) && is_callable('read_exif_data') && ($attributes = read_exif_data($file_path.$file_name))) {
//				foreach($attributes as $name => $value) {
//					if(preg_match('/^(ApertureFNumber|CameraMake|CameraModel|DateTime|ExposureTime|FocalLength|ISOspeed)$/i', $name))
//						$details[] = $name.': '.$value;
//				}
//			}

			// image size
			if($image_information = Safe::GetImageSize($file_path.$file_name)) {
				$details[] = sprintf(i18n::c('Size: %s x %s'), $image_information[0], $image_information[1]);
			}

			// update image description
			if(!isset($_REQUEST['description']))
				$_REQUEST['description'] = '';
			if((@count($details)) || ($_REQUEST['description'] == ''))
				$_REQUEST['description'] .= "\n\n".'<p class="details">'.implode(BR."\n", $details)."</p>\n";

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
		if(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'set_as_bullet')) {
			$action = 'image:set_as_bullet';
			$context['text'] .= '<p>'.i18n::s('The image has been set as the new bullet.').'</p>';
		} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'set_as_icon')) {
			$action = 'image:set_as_icon';
			$context['text'] .= '<p>'.i18n::s('The image has become the page icon.').'</p>';
		} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'set_as_avatar')) {
			$action = 'image:set_as_avatar';
			$context['text'] .= '<p>'.i18n::s('The image has become the user avatar.').'</p>';
		} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'set_as_thumbnail')) {
			$action = 'image:set_as_thumbnail';
			$context['text'] .= '<p>'.i18n::s('This has become the thumbnail image of the page.').'</p>';
		} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'set_as_both')) {
			$action = 'image:set_as_both';
			$context['text'] .= '<p>'.i18n::s('The image has been added to the page, and it also has been set as the page thumbnail.').'</p>';
		} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'insert')) {
			$action = 'image:insert';
			$context['text'] .= '<p>'.i18n::s('The image has been inserted at the beginning of the page.').'</p>';
		} else {
			$action = 'image:create';
			$context['text'] .= '<p>'.i18n::s('The image has been added at the end of the page.').'</p>';
		}

		// touch the related anchor
		$anchor->touch($action, $_REQUEST['id'], isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y'));

		// clear cache
		Images::clear($_REQUEST);

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu = array_merge($menu, array($anchor->get_url() => i18n::s('View the page')));
		$menu = array_merge($menu, array($anchor->get_url('edit') => i18n::s('Edit the page')));
		$menu = array_merge($menu, array('images/edit.php?anchor='.$anchor->get_reference() => i18n::s('Submit another image')));
		$follow_up .= Skin::build_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

		// log the submission by a non-associate
		if(!Surfer::is_associate() && is_object($anchor)) {
			$label = sprintf(i18n::c('New image in %s'), strip_tags($anchor->get_title()));
			$description = sprintf(i18n::s('%s at %s'), $_REQUEST['image_name']."\n", $context['url_to_home'].$context['url_to_root'].Images::get_url($_REQUEST['id']));
			Logger::notify('images/edit.php', $label, $description);
		}

	// update an existing image
	} else {

		// the action
		if(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'set_as_bullet'))
			$action = 'image:set_as_bullet';
		elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'set_as_icon'))
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

	// the section, for direct uploads
	if(!$anchor) {

		// a splash message for new users
		$context['text'] .= Skin::build_block(i18n::s('This script will create a brand new page for the uploaded file. If you would like to add an image to an existing page, browse the target page instead and use the adequate command from the menu bar.'), 'caution')."\n";

		$label = i18n::s('Section');
		$input = '<select name="section">'.Sections::get_options().'</select>';
		$hint = i18n::s('Please carefully select a section for your image');
		$fields[] = array($label, $input, $hint);
	}

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
		$hint = i18n::s('Please select a .png, .gif or .jpeg image.');

	}

	$fields[] = array($label, $input, $hint);

	// not just a bare upload
	if(($action != 'avatar') && ($action != 'bullet') && ($action != 'icon') && ($action != 'thumbnail')) {

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
		$hint = i18n::s('If you have get this file from outside sources, please reference these sources here');
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

	// use as page bullet
	else if($action == 'bullet')
		$context['text'] .= '<input type="hidden" name="action" value="set_as_bullet" />';

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
	if(($action != 'avatar') && ($action != 'bullet') && ($action != 'icon') && ($action != 'thumbnail')) {

		// the link url, but only for associates and authenticated editors
		if(Surfer::is_associate() || (Surfer::is_member() && is_object($anchor) && $anchor->is_editable())) {
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
		if((Surfer::is_associate() || (Surfer::is_member() && is_object($anchor) && $anchor->is_editable()))) {
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
			$context['text'] .= Skin::build_box(i18n::s('Advanced options'), Skin::build_form($fields), 'folder');
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
	if(isset($item['id']) && $item['id'] && (Surfer::is_associate() || (Surfer::is_member() && is_object($anchor) && $anchor->is_editable())) && Surfer::has_all())
		$context['text'] .= '<p><input type="checkbox" name="silent" value="Y" /> '.i18n::s('Do not change modification date of the main page.').'</p>';

	// transmit the id as a hidden field
	if(isset($item['id']) && $item['id'])
		$context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'" />';

	// other hidden fields
	if(is_object($anchor))
		$context['text'] .= '<input type="hidden" name="anchor" value="'.$anchor->get_reference().'" />';

	// end of the form
	$context['text'] .= '</div></form>';

	// the script used for form handling at the browser
	$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
		.'// set the focus on first form field'."\n"
		.'$("upload").focus();'."\n"
		.'// ]]></script>'."\n";

	// not just a bare upload
	if(($action != 'avatar') && ($action != 'bullet') && ($action != 'icon') && ($action != 'thumbnail')) {

		// general help on this form
		$help = '<p>'.i18n::s('Large files (i.e. exceeding 20&nbsp;kbytes) are published as thumbnails. By clicking on thumbnails people can access full-sized pictures. The title is visible while the mouse is over the thumbnail. The description and the source information are displayed along the full-sized picture.').'</p>'
			.'<p>'.sprintf(i18n::s('%s and %s are available to enhance text rendering.'), Skin::build_link('codes/', i18n::s('YACS codes'), 'help'), Skin::build_link('smileys/', i18n::s('smileys'), 'help')).'</p>'
			.'<p>'.i18n::s('Smaller files are embedded as-is. The description and the source fields are more or less useless in this case.').'</p>';
		$context['aside']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'navigation', 'help');

	}

}

// render the skin
render_skin();

?>