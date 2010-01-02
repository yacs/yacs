<?php
/**
 * upload a patch
 *
 * This script helps to apply a patch to a server.
 *
 * If an archive file is uploaded, its content is extracted automatically.
 *
 * Only associates can use this script.
 *
 * Accept following invocations:
 * - upload.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'scripts.php';

// load localized strings
i18n::bind('scripts');

// load the skin
load_skin('scripts');

// the path to this page
$context['path_bar'] = array( 'scripts/' => i18n::s('Server software') );

// the title of the page
$context['page_title'] = i18n::s('Apply a patch');

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('scripts/upload.php'));

// only associates can proceed
elseif(!Surfer::is_associate()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// manage the upload
} else {

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
				$success = Safe::move_uploaded_file($temporary, $name);

			// explode a .zip file
			elseif(isset($name) && preg_match('/\.zip$/i', $name)) {
				include_once '../shared/zipfile.php';
				$zipfile = new zipfile();

				// extract archive components and save them in mentioned directory
				if($count = $zipfile->explode($temporary, $context['path_to_root'])) {
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
				$handle = new Archive_Tar($temporary);

				if($handle->extract($context['path_to_root']))
					$success = TRUE;
				else
					Logger::error(sprintf(i18n::s('Error while processing %s.'), isset($name)?$name:basename($temporary)));

			}

			// everything went fine
			if($success) {
				$context['text'] .= '<p>'.i18n::s('Congratulations, the patch has been applied.').'</p>';

				// display follow-up commands
				$menu = array( 'scripts/' => i18n::s('Server software') );
				$menu = array( 'control/scan.php' => i18n::s('Extensions') );
				$menu = array( 'control/setup.php' => i18n::s('Database maintenance') );
				$context['text'] .= Skin::build_list($menu, 'menu_bar');

			}

			// clear the cache, to avoid side effects of complex updates
			Cache::clear();

		}

	// offer to upload a file
	} else {

		// the splash message
		$context['text'] .= '<p>'.i18n::s('This script allows you to upload an archive file and to extract its content to patch running scripts. Please note that any file may be modified during the process, therefore the need to trust the patch provider, and to carefully select a patch adapted to your current situation.')."</p>\n";

		// the form to post an file
		$context['text'] .= '<form method="post" enctype="multipart/form-data" action="'.$context['script_url'].'" id="main_form"><div>';

		// the file
		$context['text'] .= '<input type="file" name="upload" id="focus" size="30" />'
			.' (&lt;&nbsp;'.$context['file_maximum_size'].i18n::s('bytes').')';

		// the submit button
		$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

		// end of the form
		$context['text'] .= '</div></form>';

		// the script used for form handling at the browser
		$context['text'] .= JS_PREFIX
			.'// set the focus on first form field'."\n"
			.'$("focus").focus();'."\n"
			.JS_SUFFIX."\n";

	}

}

// render the skin
render_skin();

?>