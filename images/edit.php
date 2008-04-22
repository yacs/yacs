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
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
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

// the maximum size for uploads
$image_maximum_size = str_replace('M', '000000', Safe::get_cfg_var('upload_max_filesize'));
if((!$image_maximum_size) || $image_maximum_size > 20000000)
	$image_maximum_size = 2000000;

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
include_once 'images.php';
$item =& Images::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']))
	$anchor = Anchors::get($item['anchor']);
elseif(isset($_REQUEST['anchor']))
	$anchor = Anchors::get($_REQUEST['anchor']);
elseif(isset($context['arguments'][1]))
	$anchor = Anchors::get($context['arguments'][0].':'.$context['arguments'][1]);

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

// load localized strings
i18n::bind('images');

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
	validate($_REQUEST['introduction']);
if(isset($_REQUEST['description']))
	validate($_REQUEST['description']);

// permission denied
if(!$permitted) {

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
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// maybe posts are not allowed here
} elseif(is_object($anchor) && $anchor->has_option('locked') && !Surfer::is_associate()) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	if(isset($item['id']))
		Skin::error(i18n::s('This page has been locked. It cannot be modified anymore.'));
	else
		Skin::error(i18n::s('Posts are not allowed here.'));

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
		$_FILES['upload']['name'] = utf8::to_unicode($_FILES['upload']['name']);

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
			if($article_id = Articles::post($fields)) {
				$anchor = Anchors::get('article:'.$article_id);
				$_REQUEST['anchor'] = $anchor->get_reference();

				// purge section cache
				if($section = Anchors::get($fields['anchor']))
					$section->touch('article:create', $article_id, TRUE);
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
			Skin::error(i18n::s('You are not allowed to perform this operation.'));

		// size exceeds php.ini settings -- UPLOAD_ERR_INI_SIZE
		elseif(isset($_FILES['upload']['error']) && ($_FILES['upload']['error'] == 1))
			Skin::error(i18n::s('Please select a smaller image. The size of this image is over server limit (php.ini).'));

		// size exceeds form limit -- UPLOAD_ERR_FORM_SIZE
		elseif(isset($_FILES['upload']['error']) && ($_FILES['upload']['error'] == 2))
			Skin::error(i18n::s('Please select a smaller image. The size of this image is over limit mentioned in the form.'));

		// partial transfer -- UPLOAD_ERR_PARTIAL
		elseif(isset($_FILES['upload']['error']) && ($_FILES['upload']['error'] == 3))
			Skin::error(i18n::s('Image transfer has been interrupted.'));

		// no file -- UPLOAD_ERR_NO_FILE
		elseif(isset($_FILES['upload']['error']) && ($_FILES['upload']['error'] == 4))
			Skin::error(i18n::s('No image has been transferred.'));

		// zero bytes transmitted
		elseif(!$_FILES['upload']['size'])
			Skin::error(sprintf(i18n::s('It is likely file size goes beyond the limit displayed in upload form. Nothing has been transmitted for %s'), $_FILES['upload']['name']));

		// an anchor is mandatory to put the file in the file system
		elseif(!is_object($anchor))
			Skin::error(i18n::s('No anchor has been found.'));

		// check provided upload name
		elseif(!Safe::is_uploaded_file($file_upload))
			Skin::error(i18n::s('Possible file attack.'));

		// we accept only valid images
		elseif(!$image_information = Safe::GetImageSize($file_upload))
			Skin::error(sprintf(i18n::s('No image information in %s'), $file_name));

		// we accept only gif, jpeg and png
		elseif(($image_information[2] != 1) && ($image_information[2] != 2) && ($image_information[2] != 3))
			Skin::error(sprintf(i18n::s('Rejected file type %s'), $file_name));

		// save the image into the web space
		else {

			// create folders
			$file_path = $context['path_to_root'].'images/'.$context['virtual_path'].str_replace(':', '/', $_REQUEST['anchor']).'/';
			Safe::make_path($file_path.'thumbs');

			// move the uploaded file
			if(!Safe::move_uploaded_file($file_upload, $file_path.$file_name))
				Skin::error(sprintf(i18n::s('Impossible to move the upload file to %s'), $file_path.$file_name));

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
			Image::shrink($file_path.$file_name, $file_path.$_REQUEST['thumbnail_name'], TRUE);

			// resize the image where applicable
			if(isset($_REQUEST['automatic_process'])) {

				// identify image limits
				$variant = 'standard';
				if(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'set_as_avatar'))
					$variant = 'avatar';

				// if image has been adjusted, change its size
				if(Image::adjust($file_path.$file_name, TRUE, $variant))
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
			if((@count($details)) || ($_REQUEST['description'] == ''))
				$_REQUEST['description'] .= "\n\n".'<p class="details">'.implode(BR."\n", $details)."</p>\n";

		}

	// nothing has been posted
	} elseif(!isset($_REQUEST['id']))
		Skin::error(i18n::s('No file has been transferred. Check maximum file size.'));

	// an error has already been encountered
	if(count($context['error'])) {

		$item = $_REQUEST;
		$with_form = TRUE;

	// display the form on error
	} elseif(!$id = Images::post(array_merge($_REQUEST, $_FILES))) {
		$item = $_REQUEST;
		$with_form = TRUE;

	// reward the poster for new posts
	} elseif(!isset($_REQUEST['id'])) {

		// increment the post counter of the surfer
		Users::increment_posts(Surfer::get_id());

		// thanks
		$context['page_title'] = i18n::s('Thank you very much for your contribution');

		// show image attributes
		$attributes = array();
		if($_REQUEST['image_name'])
			$attributes[] = $_REQUEST['image_name'];
		if($url = Images::get_thumbnail_href($_REQUEST)) {
			$stuff = '<img src="'.$url.'" title="'.encode_field(strip_tags($_REQUEST['title'])).'" alt=""'.EOT;
			$attributes[] = Skin::build_link(Images::get_url($id), $stuff, 'basic');
		}
		if(is_array($attributes))
			$context['text'] .= '<p>'.implode(BR, $attributes)."</p>\n";

		// the action
		if(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'set_as_bullet')) {
			$action = 'image:set_as_bullet';
			$context['text'] .= '<p>'.i18n::s('The image has been set as the new bullet').'</p>';
		} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'set_as_icon')) {
			$action = 'image:set_as_icon';
			$context['text'] .= '<p>'.i18n::s('The image has become the page icon').'</p>';
		} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'set_as_avatar')) {
			$action = 'image:set_as_avatar';
			$context['text'] .= '<p>'.i18n::s('The image has become the user avatar').'</p>';
		} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'set_as_thumbnail')) {
			$action = 'image:set_as_thumbnail';
			$context['text'] .= '<p>'.i18n::s('The image has become the thumbnail image associated to the hosting page.').'</p>';
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
		$anchor->touch($action, $id, isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y'));

		// splash message
		$context['text'] .= '<p>'.i18n::s('What do you want to do now?').'</p>';

		// follow-up commands
		$menu = array();
		if(is_object($anchor)) {
			$menu = array_merge($menu, array($anchor->get_url() => i18n::s('View the updated page')));
			$menu = array_merge($menu, array($anchor->get_url('edit') => i18n::s('Edit the page')));
			$menu = array_merge($menu, array('images/edit.php?anchor='.$anchor->get_reference() => i18n::s('Submit another image')));
		}
		$context['text'] .= Skin::build_list($menu, 'menu_bar');

		// log the submission by a non-associate
		if(!Surfer::is_associate() && is_object($anchor)) {
			$label = sprintf(i18n::c('New image in %s'), strip_tags($anchor->get_title()));
			$description = sprintf(i18n::s('%s at %s'), $_REQUEST['image_name']."\n", $context['url_to_home'].$context['url_to_root'].Images::get_url($id));
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

		// forward to the view page
		Safe::redirect($context['url_to_home'].$context['url_to_root'].Images::get_url($_REQUEST['id']));

	}

// display the form on GET
} else
	$with_form = TRUE;

// display the form
if($with_form) {

	// reference the anchor page
	if(is_object($anchor) && $anchor->is_viewable())
		$context['text'] .= '<p>'.Skin::build_link($anchor->get_url(), $anchor->get_title())."</p>\n";

	// the form to edit an image
	$context['text'] .= '<form method="post" enctype="multipart/form-data" action="'.$context['script_url'].'" id="main_form"><div>';

	// the section, for direct uploads
	if(!$anchor) {

		// a splash message for new users
		$context['text'] .= Skin::build_block(i18n::s('This script will create a brand new page for the uploaded file. If you would like to add an image to an existing page, browse the target page instead and use the adequate command from the menu below page title.'), 'caution')."\n";

		$label = i18n::s('Section');
		$input = '<select name="section">'.Sections::get_options().'</select>';
		$hint = i18n::s('Please carefully select a section for your image');
		$fields[] = array($label, $input, $hint);
	}

	// display info on current version
	if(isset($item['id']) && $item['id']) {

		// file name
		if(isset($item['image_name'])) {
			$label = i18n::s('File name');
			$text = $item['image_name'];
			$fields[] = array($label, $text);
		}

		// file size
		if(isset($item['image_size'])) {
			$label = i18n::s('File size');
			$text = number_format($item['image_size']).' '.i18n::s('bytes');
			$fields[] = array($label, $text);
		}

		// the last poster
		if(isset($item['edit_id'])) {
			$label = i18n::s('Posted by');
			$text = Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id'])
				.' '.Skin::build_date($item['edit_date']);
			$fields[] = array($label, $text);
		}

	}

	// the upload entry requires rights to upload
	if(Surfer::may_upload()) {

		// the image
		$label = i18n::s('Image');
		if(isset($item['id']))
			$input = i18n::s('Select another image to replace the current one');
		else
			$input = i18n::s('Pick up one image you would like to share');
		$size_hint = preg_replace('/000$/', 'k', preg_replace('/000000$/', 'M', $image_maximum_size));
		$input .= BR.'<input type="hidden" name="MAX_FILE_SIZE" value="'.$image_maximum_size.'" />'
			.'<input type="file" name="upload" id="upload" size="30" accesskey="i" title="'.encode_field(i18n::s('Press to select a local file')).'"'.EOT
			.' (&lt;&nbsp;'.$size_hint.'&nbsp;'.i18n::s('bytes').')';
		$hint = i18n::s('Please select a .png, .gif or .jpeg image.');
		$fields[] = array($label, $input, $hint);

	}

	// the title
	$label = i18n::s('Title');
	$input = '<textarea name="title" rows="2" cols="40" accesskey="t">'.encode_field(isset($item['title'])?$item['title']:'')."</textarea>";
	$hint = i18n::s('Please provide a meaningful title.');
	$fields[] = array($label, $input, $hint);

	// the description
	$label = i18n::s('Description');

	// use the editor if possible
	$input = Surfer::get_editor('description', isset($item['description'])?$item['description']:'');
	$fields[] = array($label, $input);

	// the source
	$label = i18n::s('Source');
	$input = '<input type="text" name="source" size="45" value="'.encode_field(isset($item['source'])?$item['source']:'').'" maxlength="255" accesskey="u"'.EOT;
	$hint = i18n::s('If you have get this file from outside sources, please reference these sources here');
	$fields[] = array($label, $input, $hint);

	// we are now entering the advanced options section
	$context['text'] .= Skin::build_form($fields);
	$fields = array();

	// the link url, but only for associates and authenticated editors
	if(Surfer::is_associate() || (Surfer::is_member() && is_object($anchor) && $anchor->is_editable())) {
		$label = i18n::s('Link');
		$input = '<input type="text" name="link_url" size="50" value="'.encode_field(isset($item['link_url'])?$item['link_url']:'').'" maxlength="255" accesskey="l"'.EOT;
		$hint = i18n::s('You can make this image point to any web page if you wish');
		$fields[] = array($label, $input, $hint);
	}

	// how to use the image
	if(is_object($anchor) && (Surfer::is_associate() || (Surfer::is_member() && is_object($anchor) && $anchor->is_editable()))) {
		$label = i18n::s('Image usage');
		$input = '';

		// we are updating a user profile
		if(is_object($anchor) && preg_match('/^user:/i', $anchor->get_reference())) {

			$input .= '<input type="radio" name="action" value="set_as_avatar" checked="checked" '.EOT.' '.i18n::s('Set as user avatar').BR."\n";

			if(Surfer::is_associate() && !$item['id'])
				$input .= '<input type="radio" name="action" value="insert" '.EOT.' '.i18n::s('Insert at profile top').BR."\n";

			$input .= '<input type="radio" name="action" value="embed" '.EOT.' '.i18n::s('Append at the bottom of user profile').BR."\n";

		// this is not a user profile
		} else {

			$input .= '<input type="radio" name="action" value="set_as_icon"'.EOT.' '.i18n::s('Set as page icon').BR."\n";

			if(!isset($item['id']) || !$item['id'])
				$input .= '<input type="radio" name="action" value="insert" '.EOT.' '.i18n::s('Insert at page top').BR."\n";

			$input .= '<input type="radio" name="action" value="embed" checked="checked"'.EOT.' '.i18n::s('Append at page bottom').BR."\n";

			$input .= '<input type="radio" name="action" value="set_as_both" '.EOT.' '.i18n::s('Append the image, and also use it as thumbnail').BR."\n";

			$input .= '<input type="radio" name="action" value="set_as_thumbnail" '.EOT.' '.i18n::s('Set as page thumbnail').BR."\n";

			if(is_object($anchor) && preg_match('/^(section|category):/i', $anchor->get_reference()))
				$input .= '<input type="radio" name="action" value="set_as_bullet" '.EOT.' '.i18n::s('Set as list bullet').BR."\n";

		}
		$fields[] = array($label, $input);

	// create a thumbnail on direct upload
	} elseif(!isset($item['id']) && !$anchor) {
		$context['text'] .= '<input type="hidden" name="action" value="set_as_both" '.EOT;
	}

	// automatic processing
	if(Surfer::is_associate()) {
		$label = i18n::s('Image processing');
		$fields[] = array($label, '<input type="checkbox" name="automatic_process" value="Y" checked="checked" '.EOT.' '.i18n::s('Automatically resize the image if necessary'));
	} else {
		$context['text'] .= '<input type="hidden" name="automatic_process" value="Y" '.EOT;
	}

	// how to use the thumbnail
	if((Surfer::is_associate() || (Surfer::is_member() && is_object($anchor) && $anchor->is_editable()))) {
		$label = i18n::s('Insert a thumbnail');
		$input = '<input type="radio" name="use_thumbnail" value="Y"';
		if(!isset($item['use_thumbnail']) || ($item['use_thumbnail'] == 'Y'))
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('Instead of the embedded image, but only for large files (>20&nbsp;kbytes)').BR."\n";
		$input .= '<input type="radio" name="use_thumbnail" value="A"';
		if(isset($item['use_thumbnail']) && ($item['use_thumbnail'] == 'A'))
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('Always use the thumbnail. Users will click on it to see the full image.').BR."\n";
		$input .= '<input type="radio" name="use_thumbnail" value="N"';
		if(isset($item['use_thumbnail']) && ($item['use_thumbnail'] == 'N'))
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('Never. Response times for surfers using modem links may be degraded on big images.')."\n";
		$fields[] = array($label, $input);

	}

	// add a folded box
	if(count($fields)) {
		$context['text'] .= Skin::build_box(i18n::s('Advanced options'), Skin::build_form($fields), 'folder');
		$fields = array();
	}

	// associates may decide to not stamp changes -- complex command
	if(isset($item['id']) && $item['id'] && (Surfer::is_associate() || (Surfer::is_member() && is_object($anchor) && $anchor->is_editable())) && Surfer::has_all()) {
		$context['text'] .= '<p><input type="checkbox" name="silent" value="Y" '.EOT.' '.i18n::s('Do not change modification date of the related page.').'</p>';
	}

	// the submit button
	$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

	// transmit the id as a hidden field
	if(isset($item['id']) && $item['id'])
		$context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'"'.EOT;

	// other hidden fields
	if(is_object($anchor))
		$context['text'] .= '<input type="hidden" name="anchor" value="'.$anchor->get_reference().'"'.EOT;

	// end of the form
	$context['text'] .= '</div></form>';

	// the script used for form handling at the browser
	$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
		.'// set the focus on first form field'."\n"
		.'document.getElementById("upload").focus();'."\n"
		.'// ]]></script>'."\n";

	// general help on this form
	$help = '<p>'.i18n::s('Large files (i.e. exceeding 20&nbsp;kbytes) are published as thumbnails. By clicking on thumbnails people can access full-sized pictures. The title is visible while the mouse is over the thumbnail. The description and the source information are displayed along the full-sized picture.').'</p>'
		.'<p>'.sprintf(i18n::s('%s and %s are available to enhance text rendering.'), Skin::build_link('codes/', i18n::s('YACS codes'), 'help'), Skin::build_link('smileys/', i18n::s('smileys'), 'help')).'</p>'
		.'<p>'.i18n::s('Smaller files are embedded as-is. The description and the source fields are more or less useless in this case.').'</p>';
	$context['extra'] .= Skin::build_box(i18n::s('Help'), $help, 'navigation', 'help');

}

// render the skin
render_skin();

?>