<?php
/**
 * upload a new file or update an existing one
 *
 * @todo if extension is not allowed, and if associates, add a shortcut to configuration panel for files
 *
 * If no anchor has been provided to host the file, this script will create one.
 * The title given for the file, or the file name, will be used as the page title.
 * On direct uploads the sender will have the opportunity to select in which section
 * the article has to be created.
 * By default the article will be posted in the first public section appearing at the site map.
 *
 * Also, fully qualified href can be provided instead of real files, to create shadow entries
 * linked to material posted elsewhere.
 * For example, you can use these href to list within YACS files that are available at anonymous FTP servers.
 *
 * On new file upload, the href field is always stripped.
 *
 * This script accepts following situations:
 * - If a file has been actually uploaded, then search in the database a record for this name and for this anchor.
 * - If no record has been found and if an id has been provided, seek the database for the related record.
 * - If a valid record has been found, then update it.
 * - Else create a new record in the database.
 *
 * An alternate link can be added to any file record, to better support P2P software (eMule, etc.)
 *
 * This script attempts to validate the new or updated article description against a standard PHP XML parser.
 * The objective is to spot malformed or unordered HTML and XHTML tags. No more, no less.
 *
 * Restrictions apply on this page:
 * - uploads can have been administratively disallowed
 * - anonymous (not-logged) surfer are invited to register to be able to post new files
 * - members can post new files, and modify their files afterwards, if submissions are allowed
 * - members can modify files from others, if parameter users_without_file_overloads != 'Y'
 * - associates and editors can do what they want
 *
 * @see users/configure.php
 *
 * The active field is used to control the publication of uploaded files
 * - members uploads are flagged as being restricted; only other members can access them
 * - associates can publish member uploads by changing the active field
 * - associates uploads are flagged according to the input form
 *
 * A button-based editor is used for the description field.
 * It's aiming to introduce most common [link=codes]codes/index.php[/link] supported by YACS.
 *
 * Accepted calls:
 * - edit.php upload a file and create an article to host it
 * - edit.php/&lt;type&gt;/&lt;id&gt;			upload a new file for the anchor
 * - edit.php?anchor=&lt;type&gt;:&lt;id&gt;	upload a new file for the anchor
 * - edit.php/&lt;id&gt;					modify an existing file
 * - edit.php?id=&lt;id&gt; 			modify an existing file
 *
 * If the anchor for this item specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @author Vincent No&euml;l
 * @author GnapZ
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @tester Natice
 * @tester Vincent Weber
 * @tester Manuel López Gallego
 * @tester Christian Loubechine
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../shared/xml.php';	// input validation
include_once 'files.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]) && !isset($context['arguments'][1]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Files::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']))
	$anchor =& Anchors::get($item['anchor']);
elseif(isset($_REQUEST['anchor']))
	$anchor =& Anchors::get($_REQUEST['anchor']);
elseif(isset($context['arguments'][1]))
	$anchor =& Anchors::get($context['arguments'][0].':'.$context['arguments'][1]);

// owners can do what they want
if(is_object($anchor) && $anchor->is_owned()) {
	Surfer::empower();
	$permitted = TRUE;

// do not accept new files if uploads have been disallowed
} elseif(!isset($item['id']) && Files::are_allowed($anchor))
	$permitted = TRUE;

// the anchor has to be viewable by this surfer
elseif(is_object($anchor) && !$anchor->is_viewable())
	$permitted = FALSE;

// the original poster can change the file
elseif(isset($item['create_id']) && Surfer::is($item['create_id']))
	$permitted = TRUE;

// authenticated members are allowed to modify files from others
elseif(isset($item['id']) && Surfer::is_member() && (!isset($context['users_without_file_overloads']) || ($context['users_without_file_overloads'] != 'Y')))
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// do not always show the edition form
$with_form = FALSE;

// load the skin, maybe with a variant
load_skin('files', $anchor);

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'files/' => i18n::s('Files') );

// the title of the page
if(isset($item['title']) && $item['title'])
	$context['page_title'] = sprintf(i18n::s('Update: %s'), $item['title']);
elseif(isset($item['file_name']))
	$context['page_title'] = sprintf(i18n::s('Update: %s'), str_replace('_', ' ', $item['file_name']));
else
	$context['page_title'] = i18n::s('Add a file');

// always validate input syntax
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
			$link = Files::get_url($item['id'], 'edit');
		elseif(isset($_REQUEST['anchor']))
			$link = 'files/edit.php?anchor='.urlencode(strip_tags($_REQUEST['anchor']));
		else
			$link = 'files/edit.php';

		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode($link));
	}

	// permission denied to authenticated user
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// file has been reserved
} elseif(isset($item['assign_id']) && $item['assign_id'] && !Surfer::is($item['assign_id']) && !$anchor->is_owned()) {

	// prevent updates
	$context['text'] .= Skin::build_block(sprintf(i18n::s('This file has been reserved by %s %s, and it is likely that an updated version will be made available soon.'), Users::get_link($item['assign_name'], $item['assign_address'], $item['assign_id']), Skin::build_date($item['assign_date'])), 'caution');

	// follow-up commands
	$context['text'] .= Skin::build_block(Skin::build_link($anchor->get_url('files'), i18n::s('Done'), 'button'), 'bottom');

// extension is not allowed
} elseif(isset($_FILES['upload']['name']) && $_FILES['upload']['name'] && !Files::is_authorized($_FILES['upload']['name'])) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('This type of file is not allowed.'));

// an error occured
} elseif(count($context['error'])) {
	$item = $_REQUEST;
	$with_form = TRUE;

// process uploaded data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// remember the previous version
	if(isset($item['id'])) {
		include_once '../versions/versions.php';
		Versions::save($item, 'file:'.$item['id']);
	}

	// just an update of the record
	$action = 'file:update';

	// a file has been uploaded
	if(isset($_FILES['upload']['name']) && $_FILES['upload']['name'] && ($_FILES['upload']['name'] != 'none')) {

		// where to put this file
		$file_path = 'files/'.$context['virtual_path'].str_replace(':', '/', $_REQUEST['anchor']);

		// attach some file
		if($file_name = Files::upload($_FILES['upload'], $file_path)) {
			$_REQUEST['file_name'] = $file_name;

			// actually, a new file
			$action = 'file:create';
		}

		// maybe this file has already been uploaded for this anchor
		if(isset($_REQUEST['anchor']) && ($match =& Files::get_by_anchor_and_name($_REQUEST['anchor'], $file_name))) {

			// if yes, switch to the matching record (and forget the record fetched previously, if any)
			$_REQUEST['id'] = $match['id'];
			$item = $match;
		}

		// remember file size
		$_REQUEST['file_size'] = $_FILES['upload']['size'];

		// silently delete the previous file if the name has changed
		if($item['file_name'] && $file_name && ($item['file_name'] != $file_name) && isset($file_path))
			Safe::unlink($file_path.'/'.$item['file_name']);

		// if the file is an image, create a thumbnail for it
		if(($image_information = Safe::GetImageSize($file_path.'/'.$file_name)) && ($image_information[2] >= 1) && ($image_information[2] <= 3)) {

			// derive a thumbnail image
			$thumbnail_name = 'thumbs/'.$file_name;
			include_once $context['path_to_root'].'images/image.php';
			Image::shrink($context['path_to_root'].$file_path.'/'.$file_name, $context['path_to_root'].$file_path.'/'.$thumbnail_name, FALSE, TRUE);

			// remember the address of the thumbnail
			$_REQUEST['thumbnail_url'] = $context['url_to_root'].$file_path.'/'.$thumbnail_name;

		}

		// we have a real file, not a reference
		$_REQUEST['file_href'] = '';

	// we are posting a reference
	} elseif(isset($_REQUEST['file_href']) && $_REQUEST['file_href']) {

		// protect from hackers -- encode_link() would introduce &amp;
		$_REQUEST['file_href'] = trim(preg_replace(FORBIDDEN_IN_URLS, '_', $_REQUEST['file_href']), ' _');

		// ensure we have a title
		if(!$_REQUEST['title'])
			$_REQUEST['title'] = str_replace('%20', ' ', basename($_REQUEST['file_href']));

		// ensure we have a file name
		$_REQUEST['file_name'] = utf8::to_ascii(str_replace('%20', ' ', basename($_REQUEST['file_href'])));

	// nothing has been posted
	} elseif(!isset($_REQUEST['id']))
		Logger::error(i18n::s('No file has been transmitted.'));

	// make the file name searchable on initial post
	if(!isset($_REQUEST['id']) && isset($_REQUEST['file_name']))
		$_REQUEST['keywords'] .= ' '.str_replace(array('%20', '_', '.', '-'), ' ', $_REQUEST['file_name']);

	// hook to index binary files

	// an error has already been encoutered
	if(count($context['error'])) {
		$item = $_REQUEST;

	// do not show the form, since browser may have not transmitted anchor information

	// update the record in the database
	} elseif(!$_REQUEST['id'] = Files::post($_REQUEST)) {
		$item = $_REQUEST;
		$with_form = TRUE;

	// reward the poster for new posts, or for actual upload
	} elseif(!isset($item['id']) || ($action == 'file:create')) {

		// always touch the related anchor on new posts
		$anchor->touch('file:create', $_REQUEST['id']);

		// clear cache
		Files::clear($_REQUEST);

		// increment the post counter of the surfer
		Users::increment_posts(Surfer::get_id());

		// thanks
		$context['page_title'] = i18n::s('Thank you for your contribution');

		// show file attributes
		$attributes = array();
		if(isset($_REQUEST['thumbnail_url']) && $_REQUEST['thumbnail_url'])
			$attributes[] = '<img src="'.$_REQUEST['thumbnail_url'].'" />';
		if($_REQUEST['file_name'])
			$attributes[] = $_REQUEST['file_name'];
		if($_REQUEST['file_size'])
			$attributes[] = $_REQUEST['file_size'].' bytes';
		if(is_array($attributes))
			$context['text'] .= '<p>'.implode(BR, $attributes)."</p>\n";

		// the action
		$context['text'] .= '<p>'.i18n::s('The upload has been successfully recorded.').'</p>';

		// list persons that have been notified
		$context['text'] .= Mailer::get_recipients(i18n::s('Persons that have been notified of your post'));

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');

		// follow-up commands -- do not use #files, because of thread layout, etc.
		$menu = array();
		if(is_object($anchor))
			$menu = array_merge($menu, array($anchor->get_url('files') => i18n::s('Back to main page')));
		$menu = array_merge($menu, array(Files::get_url($_REQUEST['id'], 'view', $_REQUEST['file_name']) => i18n::s('Check the download page for this file')));
		if(Surfer::may_upload())
			$menu = array_merge($menu, array('images/edit.php?anchor='.urlencode('file:'.$_REQUEST['id']) => i18n::s('Add an image')));
		if(is_object($anchor) && Surfer::may_upload())
			$menu = array_merge($menu, array('files/edit.php?anchor='.$anchor->get_reference() => i18n::s('Upload another file')));
		$follow_up .= Skin::build_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

		// log the submission of a new file by a non-associate
		if(!Surfer::is_associate() && is_object($anchor)) {
			$label = sprintf(i18n::c('New file in %s'), strip_tags($anchor->get_title()));
			$description = sprintf(i18n::c('%s at %s'), $_REQUEST['file_name'], $context['url_to_home'].$context['url_to_root'].Files::get_url($_REQUEST['id']));
			Logger::notify('files/edit.php', $label, $description);
		}

	// forward to the updated page
	} else {

		// touch the related anchor
		$anchor->touch('file:update', $_REQUEST['id'], isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y'));

		// clear cache
		Files::clear($_REQUEST);

		// increment the post counter of the surfer
		Users::increment_posts(Surfer::get_id());

		// forward to the anchor page
		Safe::redirect($context['url_to_home'].$context['url_to_root'].$anchor->get_url('files'));

	}

// display the form on GET
} else
	$with_form = TRUE;

// display the form
if($with_form) {

	// prevent updates from section owner or associate
	if(isset($item['assign_id']) && $item['assign_id'] && !Surfer::is($item['assign_id'])) {
		$context['text'] .= Skin::build_block(sprintf(i18n::s('This file has been reserved by %s %s, and it is likely that an updated version will be made available soon.'), Users::get_link($item['assign_name'], $item['assign_address'], $item['assign_id']), Skin::build_date($item['assign_date'])), 'caution');
	}

	// the form to edit a file
	$context['text'] .= '<form method="post" enctype="multipart/form-data" action="'.$context['script_url'].'" id="main_form"><div>';
	$fields = array();

	//
	// panels
	//
	$panels = array();

	//
	// information tab
	//
	$text = '';

	// the file itself
	$label = i18n::s('File');
	$input = '';

	// a member is creating a new file entry
	if(!isset($item['id']) && Surfer::is_member()) {

		// several options to consider
		$input = '<dl>';

		// the upload entry requires rights to upload
		if(Surfer::may_upload()) {

			// an upload entry
			$input .= '<dt><input type="radio" name="file_type" value="upload" checked="checked" />'.i18n::s('Upload a file').'</dt>'
				.'<dd><input type="file" name="upload" id="upload" size="30" />'
				.' (&lt;&nbsp;'.$context['file_maximum_size'].i18n::s('bytes').')</dd>'."\n";

			// or
			$input .= '<dt>'.i18n::s('or').'</dt>';

		}

		// a reference
		$input .= '<dt><input type="radio" name="file_type" value="href" />'.i18n::s('Share an existing reference (ftp://, http://, ...)').'</dt>'
			.'<dd><input type="text" name="file_href" size="45" value="'.encode_field(isset($item['file_href'])?$item['file_href']:'').'" maxlength="255" />';
		$input .= BR.i18n::s('File size')
			.' <input type="text" name="file_size" size="12" value="'.encode_field(isset($item['file_size'])?$item['file_size']:'').'" maxlength="12" /> '.i18n::s('bytes')
			.'</dd>'."\n";

		$input .= '</dl>';

	// an anonymous surfer is creating a new file entry
	} elseif(!isset($item['id'])) {

		// the upload entry requires rights to upload
		if(Surfer::may_upload()) {

			// an upload entry
			$input = '<input type="hidden" name="file_type" value="upload" />'
				.'<input type="file" name="upload" id="upload" size="30" />'
				.' (&lt;&nbsp;'.$context['file_maximum_size'].i18n::s('bytes').')'."\n";

		}

	// update an existing entry
	} elseif(!isset($item['file_href']) || !$item['file_href']) {
		$details = array();

		// file name
		if(isset($item['file_name']) && $item['file_name'])
			$details[] = $item['file_name'];

		// file uploader
		if(isset($item['create_name']))
			$details[] = sprintf(i18n::s('posted by %s %s'), Users::get_link($item['create_name'], $item['create_address'], $item['create_id']), Skin::build_date($item['create_date']));

		// downloads and file size
		$other_details = array();
		if(isset($item['hits']) && ($item['hits'] > 1))
			$other_details[] = Skin::build_number($item['hits'], i18n::s('downloads'));
		if(isset($item['file_size']) && ($item['file_size'] > 1))
			$other_details[] = Skin::build_number($item['file_size'], i18n::s('bytes'));
		if(count($other_details))
			$details[] = join(', ', $other_details);

		if(count($details))
			$input .= ucfirst(implode(BR, $details)).BR.BR;

		// the upload entry requires rights to upload
		if(Surfer::may_upload()) {

			// refresh the file
			$input .= i18n::s('Select another file to replace the current one').BR
				.'<input type="file" name="upload" id="upload" size="30" />'
				.' (&lt;&nbsp;'.$context['file_maximum_size'].i18n::s('bytes').')'."\n";


		}

	// we can refresh the reference
	} else {

		$input .= '<p class="tiny">'.i18n::s('Existing reference (ftp://, http://, ...)')
			.BR.'<input type="text" name="file_href" id="upload" size="45" value="'.encode_field($item['file_href']).'" maxlength="255" />';
		$input .= BR.i18n::s('File size')
			.' <input type="text" name="file_size" size="5" value="'.encode_field($item['file_size']).'" maxlength="12" /> '.i18n::s('bytes').'</p>'."\n";

	}

	// a complex field
	$fields[] = array($label, $input);

	// the title
	$label = i18n::s('Title');
	$input = '<input type="text" name="title" size="50" value="'.encode_field(isset($item['title'])?$item['title']:'').'" maxlength="255" accesskey="t" />';
	$fields[] = array($label, $input);

	// the description
	$label = i18n::s('Description');

	// use the editor if possible
	$input = Surfer::get_editor('description', isset($item['description'])?$item['description']:'');
	$fields[] = array($label, $input);

	// build the form
	$text .= Skin::build_form($fields);
	$fields = array();

	// display in a separate panel
	if($text)
		$panels[] = array('information', i18n::s('Information'), 'information_panel', $text);

	//
	// attachments tab
	//
	$text = '';

	// the icon url may be set after the page has been created
	if(isset($item['id']) && Surfer::is_empowered() && Surfer::is_member()) {
		$label = i18n::s('Image');

		// show the current icon
		if(isset($item['icon_url']) && $item['icon_url']) {
			$input = '<img src="'.preg_replace('/\/images\/file\/[0-9]+\//', "\\0thumbs/", $item['icon_url']).'" alt="" />';
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
			$input .= ' <span class="details">'.Skin::build_link('images/edit.php?anchor='.urlencode('file:'.$item['id']).'&amp;action=icon', $command, 'basic').'</span>';
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
			$input .= ' <span class="details">'.Skin::build_link('images/edit.php?anchor='.urlencode('file:'.$item['id']).'&amp;action=thumbnail', $command, 'basic').'</span>';
		$fields[] = array($label, $input);
	}

	// end of regular fields
	$text .= Skin::build_form($fields);
	$fields = array();

	// splash message for new items
	if(!isset($item['id']))
		$text .= Skin::build_box(i18n::s('Images'), '<p>'.i18n::s('Submit the new page, and you will be able to add images afterwards.').'</p>', 'folded');

	// images
	else {
		$box = '';

		// menu at the top
		$menu = array();

		// the command to add an image
		if(Surfer::may_upload()) {
			Skin::define_img('IMAGES_ADD_IMG', 'images/add.gif');
			$menu = array(Skin::build_link('images/edit.php?anchor='.urlencode('file:'.$item['id']), IMAGES_ADD_IMG.i18n::s('Add an image'), 'basic'));
		}

		// the list of images
		include_once '../images/images.php';
		if($items = Images::list_by_date_for_anchor('file:'.$item['id'])) {

			// help to insert in textarea
			if(!isset($_SESSION['surfer_editor']) || ($_SESSION['surfer_editor'] == 'yacs'))
				$menu[] = i18n::s('Click on codes to insert images in the page.')."\n";
			else
				$menu[] = i18n::s('Use codes to insert images in the page.')."\n";

		}

		if($menu)
			$box .= Skin::finalize_list($menu, 'menu_bar');
		if($items)
			$box .= Skin::build_list($items, 'decorated');

		// in a folded box
		if($box)
			$text .= Skin::build_box(i18n::s('Images'), $box, 'unfolded');

	}

	// display in a separate panel
	if($text)
		$panels[] = array('attachments', i18n::s('Attachments'), 'embedded_panel', $text);

	//
	// options tab
	//
	$text = '';

	// associates may change the active flag: Yes/public, Restricted/logged, No/associates
	if(Surfer::is_empowered() && Surfer::is_member()) {
		$label = i18n::s('Access');
		$input = '<input type="radio" name="active_set" value="Y" accesskey="v"';
		if(!isset($item['active_set']) || ($item['active_set'] == 'Y'))
			$input .= ' checked="checked"';
		$input .= '/> '.i18n::s('Public - Access is granted to anonymous surfers')
			.BR.'<input type="radio" name="active_set" value="R"';
		if(isset($item['active_set']) && ($item['active_set'] == 'R'))
			$input .= ' checked="checked"';
		$input .= '/> '.i18n::s('Community - Access is restricted to authenticated members')
			.BR.'<input type="radio" name="active_set" value="N"';
		if(isset($item['active_set']) && ($item['active_set'] == 'N'))
			$input .= ' checked="checked"';
		$input .= '/> '.i18n::s('Private - Access is restricted to selected persons')."\n";
		$fields[] = array($label, $input);
	}

	// append fields
	$text .= Skin::build_form($fields);
	$fields = array();

	// the source
	$label = i18n::s('Source');
	$input = '<input type="text" name="source" size="45" value="'.encode_field(isset($item['source'])?$item['source']:'').'" maxlength="255" accesskey="u" />';
	$hint = i18n::s('If you have got this file from outside source, please describe it here');
	$fields[] = array($label, $input, $hint);

	// keywords
	$label = i18n::s('Keywords');
	$input = '<input type="text" name="keywords" size="45" value="'.encode_field(isset($item['keywords'])?$item['keywords']:'').'" maxlength="255" accesskey="o" />';
	$hint = i18n::s('As this field may be searched by surfers, please choose adequate searchable words');
	$fields[] = array($label, $input, $hint);

	// alternate href
	$label = i18n::s('Alternate link');
	$input = '<input type="text" name="alternate_href" size="45" value="'.encode_field(isset($item['alternate_href'])?$item['alternate_href']:'').'" maxlength="255" />';
	$hint = i18n::s('Paste here complicated peer-to-peer href (ed2k, torrent, etc.)');
	$fields[] = array($label, $input, $hint);

	// add a folded box
	$text .= Skin::build_box(i18n::s('More options'), Skin::build_form($fields), 'folded');
	$fields = array();

	// display in a separate panel
	if($text)
		$panels[] = array('options', i18n::s('Options'), 'options_panel', $text);

	//
	// assemble all tabs
	//
	$context['text'] .= Skin::build_tabs($panels);

	// bottom commands
	$menu = array();
	$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');
	if(is_object($anchor) && $anchor->is_viewable())
		$menu[] = Skin::build_link($anchor->get_url(), i18n::s('Cancel'), 'span');
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// associates may decide to not stamp changes, but only for changes -- complex command
	if(isset($item['id']) && Surfer::is_associate() && isset($anchor) && Surfer::has_all())
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
	$context['text'] .= JS_PREFIX
		.'// set the focus on first form field'."\n"
		.'$("upload").focus();'."\n"
		.JS_SUFFIX."\n";

	// general help on this form
	$help = '<p>'.i18n::s('Please set a meaningful title to be used instead of the file name in lists of files.').'</p>'
		.'<p>'.i18n::s('Also, take the time to describe your post. This field is fully indexed for searches.').'</p>'
		.'<p>'.sprintf(i18n::s('%s and %s are available to enhance text rendering.'), Skin::build_link('codes/', i18n::s('YACS codes'), 'help'), Skin::build_link('smileys/', i18n::s('smileys'), 'help')).'</p>'
		.'<p>'.i18n::s('Lastly, indicate the original source of the file if you know it, either with a name or, better, with a web address.').'</p>';
	$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'extra', 'help');

}

// render the skin
render_skin();

?>