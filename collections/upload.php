<?php
/**
 * upload files in a collection
 *
 * This script helps to populate a collection remotely.
 *
 * If an archive file is uploaded, its content is extracted automatically.
 * This allows for a simple mean to upload photos to the server.
 *
 * Only associates can use this script.
 *
 * This script relies on an external library to handle archive files.
 *
 * Accept following invocations:
 * - upload.php?path=&lt;collection/path/to/browse&gt;
 * - upload.php/collection/path/to/browse;
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../files/files.php';
include_once 'collections.php';

// check network credentials, if any
if($user = Users::authenticate())
	Surfer::empower($user['capability']);

// the maximum size for uploads
$file_maximum_size = str_replace('M', ' M', Safe::get_cfg_var('upload_max_filesize'));
if(!$file_maximum_size)
	$file_maximum_size = '2 M';

// load the skin -- before loading the collection
load_skin('collections');

// the path to browse
$id = NULL;
if(isset($_REQUEST['path']))
	$id = urldecode($_REQUEST['path']);
elseif(isset($context['arguments'][1]))
	$id = join('/', $context['arguments']);
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// bind the virtual item to something real
$item = Collections::get($id);

// icons used to depict files and folders
$icons = array();
$icons['folder_icon'] = '<img src="'.$context['url_to_root'].'skins/images/files_inline/folder.png" width="13" height="16" alt="" />';
$icons['folder_up_icon'] = '<img src="'.$context['url_to_root'].'skins/images/files_inline/folder_up.gif" width="15" height="16" alt="" />';

// the path to this page
$context['path_bar'] = array( 'collections/' => i18n::s('File collections') );

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('collections/upload.php'));

// only associates can proceed
elseif(!Surfer::is_associate()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// the collection has to exist
} elseif(!isset($item['collection']) || !$item['collection']) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	$context['page_title'] = i18n::s('Unknown collection');
	Logger::error(i18n::s('The collection asked for is unknown.'));

// manage the upload
} else {

	// bread crumbs to upper levels, if any
	$context['path_bar'] = array_merge($context['path_bar'], $item['containers']);

	// list where we are
	if(isset($item['node_label'])) {
		$context['page_title'] = $item['node_label'];

		// list parent containers, if any
		foreach($item['containers'] as $link => $label) {
			$context['prefix'] .= BR.'<a href="'.$link.'">'.$icons['folder_up_icon'].'</a> <a href="'.$link.'">'.$label.'</a>';
		}

	// houston, we've got a problem
	} else
		$context['page_title'] = i18n::s('Untitled collection');

	// the description is set at the collection index page
	if($item['collection_description'])
		$context['text'] .= '<p>'.Codes::beautify($item['collection_description'])."</p>\n";

	// the prefix on non-index pages
	if($item['collection_prefix'])
		$context['text'] .= '<p>'.Codes::beautify($item['collection_prefix'])."</p>\n";

	// include the header file, if any
	$context['text'] .= Safe::file_get_contents($item['actual_path'].'/.header');

	// process uploaded data
	if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

		// nothing has been uploaded
		if(!$_FILES['upload']['name'] || ($_FILES['upload']['name'] == 'none'))
			Logger::error(i18n::s('Nothing has been received.'));

		// a file has been uploaded
		else {

			// access the temporary uploaded file
			$temporary = $_FILES['upload']['tmp_name'];
			$name = $_FILES['upload']['name'];

			// zero bytes transmitted
			$_REQUEST['file_size'] = $_FILES['upload']['size'];
			if(!$_FILES['upload']['size'])
				Logger::error(i18n::s('Nothing has been received.'));

			// check provided upload name
			elseif(!Safe::is_uploaded_file($temporary))
				Logger::error(i18n::s('Possible file attack.'));

			// not yet a success
			$success = FALSE;

			// ensure file exists
			if(!is_readable($temporary))
				Logger::error(sprintf(i18n::s('Impossible to read %s.'), basename($temporary)));

			// move regular files
			elseif(!preg_match('/\.(bz2*|tar\.gz|tgz|zip)$/i', $name))
				$success = Safe::move_uploaded_file($temporary, $item['actual_path'].'/'.$name);

			// explode a .zip file
			elseif(isset($name) && preg_match('/\.zip$/i', $name)) {
				include_once '../shared/zipfile.php';
				$zipfile = new zipfile();

				// extract archive components and save them in mentioned directory
				if($count = $zipfile->explode($temporary, $item['actual_path'])) {
					$context['text'] .= '<p>'.sprintf(i18n::s('%d files have been extracted.'), $count)."</p>\n";
					$success = TRUE;
				} else
					Logger::error(sprintf(i18n::s('Nothing has been extracted from %s.'), $name));

			// ensure we have the external library to explode other kinds of archives
			} elseif(!is_readable('../included/tar.php'))
					Logger::error(i18n::s('Impossible to extract files.'));

			// explode the archive
			else {
				include_once $context['path_to_root'].'included/tar.php';
				$handle =& new Archive_Tar($temporary);

				if($handle->extract($item['actual_path']))
					$success = TRUE;
				else
					Logger::error(sprintf(i18n::s('Error while processing %s.'), isset($name)?$name:basename($temporary)));

			}

			// everything went fine
			if($success) {
				$context['text'] .= '<p>'.i18n::s('Congratulations, you have shared new content.').'</p>';

				// display follow-up commands
				if($context['with_friendly_urls'] == 'Y')
					$link = 'collections/browse.php/'.str_replace('//', '/', rawurlencode($item['collection']).'/'.$item['relative_url']);
				else
					$link = 'collections/browse.php?path='.urlencode(str_replace('//', '/', $item['collection'].'/'.$item['relative_path']));
				$menu = array( $link => i18n::s('Browse this directory') );
				$context['text'] .= Skin::build_list($menu, 'menu_bar');

			}

			// clear the cache, to avoid side effects of complex updates
			Cache::clear();

		}

	// offer to upload a file
	} else {

		// the splash message
		$context['text'] .= '<p>'.i18n::s('This script allows you to upload a file, or an archive file to this directory.')."</p>\n";

		// the form to post an file
		$context['text'] .= '<form method="post" enctype="multipart/form-data" action="'.$context['script_url'].'" id="main_form"><div>';

		// upload an archive
		$context['text'] .= '<p>'.i18n::s('Please note that archives will be exploded to expose their content.').'</p>';

		// the file
		$label = i18n::s('File');
		$input = '<input type="file" name="upload" id="focus" size="30" />'
			.' (&lt;&nbsp;'.$file_maximum_size.i18n::s('bytes').')';
		$hint = i18n::s('Select the file to upload');
		$fields[] = array($label, $input, $hint);

		// build the form
		$context['text'] .= Skin::build_form($fields);

		// the submit button
		$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

		// the target path
		$context['text'] .= '<input type="hidden" name="path" value="'.encode_field($id).'" />'."\n";

		// end of the form
		$context['text'] .= '</div></form>';

		// the script used for form handling at the browser
		$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
			.'// set the focus on first form field'."\n"
			.'$("focus").focus();'."\n"
			.'// ]]></script>'."\n";

	}

	// include the footer file, if any
	$context['text'] .= Safe::file_get_contents($item['actual_path'].'/.footer');

	// the suffix on non-index pages
	if($item['collection_suffix'])
		$context['text'] .= '<p>'.Codes::beautify($item['collection_suffix'])."</p>\n";

}

// render the skin
render_skin();

?>