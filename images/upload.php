<?php
/**
 * upload several images at once
 *
 * This script takes received archive files and extract images they can contain.
 *
 * Accepted calls:
 * - upload.php/&lt;type&gt;/&lt;id&gt;
 * - upload.php?anchor=&lt;type&gt;:&lt;id&gt;
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'image.php';	// image processing
include_once 'images.php';

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']))
	$anchor =& Anchors::get($item['anchor']);
elseif(isset($_REQUEST['anchor']))
	$anchor =& Anchors::get($_REQUEST['anchor']);
elseif(isset($context['arguments'][1]))
	$anchor =& Anchors::get($context['arguments'][0].':'.$context['arguments'][1]);

// editors can do what they want on items anchored here
if(is_object($anchor) && $anchor->is_assigned())
	Surfer::empower();

// do not accept new files if uploads have been disallowed
if(!Surfer::may_upload())
	$permitted = FALSE;

// associates and editors can do what they want
elseif(Surfer::is_associate() || (is_object($anchor) && $anchor->is_assigned()))
	$permitted = TRUE;

// the anchor has to be viewable by this surfer
elseif(is_object($anchor) && !$anchor->is_viewable())
	$permitted = FALSE;

// authenticated members are allowed to add images to existing pages
elseif(Surfer::is_member() && is_object($anchor))
	$permitted = TRUE;

// authenticated members can post new items if submission is allowed
elseif(Surfer::is_member() && (!isset($context['users_without_submission']) || ($context['users_without_submission'] != 'Y')))
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

// page title
$context['page_title'] = i18n::s('Bulk upload');

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged()) {

		if(isset($_REQUEST['anchor']))
			$link = 'images/upload.php?anchor='.urlencode($_REQUEST['anchor']);
		else
			$link = 'images/upload.php';

		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode($link));
	}

	// permission denied to authenticated user
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// an anchor is mandatory
} elseif(!is_object($anchor)) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No anchor has been found.'));

// maybe posts are not allowed here
} elseif($anchor->has_option('locked') && !Surfer::is_empowered()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('This page has been locked.'));

// an error occured
} elseif(count($context['error'])) {
	$item = $_REQUEST;
	$with_form = TRUE;

// process uploaded data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// true when several files are uploaded at once
	$exploded = FALSE;

	// a file has been uploaded
	if(isset($_FILES['upload']['name']) && $_FILES['upload']['name'] && ($_FILES['upload']['name'] != 'none')) {

		// where to put this file
		$file_path = 'images/'.$context['virtual_path'].str_replace(':', '/', $_REQUEST['anchor']);

		// explode a .zip file
		if(preg_match('/\.zip$/i', $_FILES['upload']['name'])) {
			include_once '../shared/zipfile.php';
			$zipfile = new zipfile();

			function explode_callback($name) {
				global $context;

//				$context['text'] .= 'extracting '.$name.BR;
			}

			// extract archive components and save them in mentioned directory
			if($count = $zipfile->explode($_FILES['upload']['tmp_name'], $file_path, '', 'explode_callback')) {
				$context['text'] .= '<p>'.sprintf('%d files have been extracted.', $count)."</p>\n";
				$exploded = TRUE;
			} else
				Logger::error(sprintf('Nothing has been extracted from %s.', $_FILES['upload']['name']));

		// explode a tar file
		} elseif(preg_match('/\.(tar|tar.gz|tgz)$/i', $_FILES['upload']['name'])) {

			// ensure we have the external library to explode other kinds of archives
			if(!is_readable('../included/tar.php'))
					Logger::error('Impossible to extract files.');

			// explode the archive
			else {
				include_once $context['path_to_root'].'included/tar.php';
				$handle = new Archive_Tar($_FILES['upload']['tmp_name']);

				if($handle->extract($context['path_to_root'].$file_path)) {
					$context['text'] .= '<p>'.'Files have been extracted.'."</p>\n";
					$exploded = TRUE;
				} else
					Logger::error(sprintf('Nothing has been extracted from %s.', $_FILES['upload']['name']));

			}

		// nothing to do
		} else
			Logger::error(i18n::s('Please provide an archive file.'));

	// nothing has been posted
	} else
		Logger::error(i18n::s('No file has been transmitted.'));

	// reward the poster for new posts
	if($exploded) {

		// increment the post counter of the surfer
		Users::increment_posts(Surfer::get_id());

		// thanks
		$context['page_title'] = i18n::s('Thank you for your contribution');
		$count = 0;

		// scan all image files for this anchor
		if($handle = Safe::opendir($file_path)) {

			// list all nodes
			while(($node = Safe::readdir($handle)) !== FALSE) {

				// special directory names
				if(($node == '.') || ($node == '..'))
					continue;

				// process special nodes
				if($node[0] == '.')
					continue;

				// an image has been found --skip non-images
				if(Image::upload($node, $context['path_to_root'].$file_path.'/', TRUE)) {
					$count++;

					// resize the image where applicable
					Image::adjust($context['path_to_root'].$file_path.'/'.$node, TRUE, 'standard');

					// if the file does not exist yet
					if(!$item =& Images::get_by_anchor_and_name($anchor->get_reference(), $node)) {

						// create a new image record for this file
						$item = array();
						$item['anchor'] = $anchor->get_reference();
						$item['image_name'] = $node;
						$item['thumbnail_name'] = 'thumbs/'.$node;
						$item['image_size'] = Safe::filesize($file_path.'/'.$node);
						$item['use_thumbnail'] = 'A'; // ensure it is always displayed as a clickable small image
						$item['id'] = Images::post($item);
					}

					// ensure that the image is in anchor description field
					if(isset($item['id']))
						$anchor->touch('image:create', $item['id']);

				}
			}
			Safe::closedir($handle);
		}

		// clear floating thumbnails
		if($count)
			$anchor->touch('clear');

		// provide feed-back to surfer
		if($count)
			$context['text'] .= '<p>'.sprintf(i18n::ns('%d image has been processed.', '%d images have been processed.', $count), $count).'</p>';
		else
			$context['text'] .= '<p>'.i18n::s('No image has been processed.').'</p>';

		// list persons that have been notified
		$context['text'] .= Mailer::get_recipients(i18n::s('Persons that have been notified of your post'));

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu = array_merge($menu, array($anchor->get_url() => i18n::s('View the page')));
		$menu = array_merge($menu, array($anchor->get_url('edit') => i18n::s('Edit the page')));
		$follow_up .= Skin::build_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

		// clear cache
		Images::clear($_REQUEST);

		// log the submission by a non-associate
		if(!Surfer::is_associate()) {
			$label = sprintf(i18n::c('New images in %s'), strip_tags($anchor->get_title()));
			$description = sprintf(i18n::ns('%d image has been processed.', '%d images have been processed.', $count), $count);
			Logger::notify('images/upload.php', $label, $description);
		}

	}

// display the form on GET
} else
	$with_form = TRUE;

// display the form
if($with_form) {

	// the form to edit an image
	$context['text'] .= '<form method="post" enctype="multipart/form-data" action="'.$context['script_url'].'" id="main_form"><div>';
	$fields = array();

	// generic splash message
	$context['text'] .= '<p>'.i18n::s('To create nice picture albums, upload archive files that combine several images. In one operation the server will extract each image, resize it if necessary, create a thumbnail image, and integrate the image into the page.').'</p>';

	// the section
	if($anchor)
		$context['text'] .= '<input type="hidden" name="anchor" value="'.$anchor->get_reference().'" />';

	// the upload entry requires rights to upload
	$context['text'] .= '<input type="file" name="upload" id="upload" size="30" accesskey="i" title="'.encode_field(i18n::s('Press to select a local file')).'" />'
		.' (&lt;&nbsp;'.$context['file_maximum_size'].i18n::s('bytes').')'
		.BR.'<span class="details">'.i18n::s('Select a .zip, .tar, .tar.gz or .tgz archive.').'</span>';

	// bottom commands
	$menu = array();
	$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');
	if(is_object($anchor) && $anchor->is_viewable())
		$menu[] = Skin::build_link($anchor->get_url(), i18n::s('Cancel'), 'span');
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// end of the form
	$context['text'] .= '</div></form>';

	// the script used for form handling at the browser
	$context['text'] .= JS_PREFIX
		.'// set the focus on first form field'."\n"
		.'$("upload").focus();'."\n"
		.JS_SUFFIX."\n";

	// this may take several minutes
	$context['text'] .= '<p>'.i18n::s('When you will click on the button the server will be immediately requested to proceed. However, because of the so many things to do on the back-end, you may have to wait for minutes before getting a response displayed. Thank you for your patience.')."</p>\n";

}

// render the skin
render_skin();

?>