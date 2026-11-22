<?php
/**
 * stage updated files
 *
 * The staging store [code]scripts/staging[/code] is populated from an archive
 * containing the updated files, for example a patch built by [code]tools/smartpatch.sh[/code].
 *
 * Content of the staging store is built as follows:
 * - if a file has been uploaded, its content is used
 * - else if a reference to a local archive has been provided, its content is used
 *
 * The archive may be uploaded through the form of this page,
 * or it can have been put in the directory inbox/yacs, for example with FTP.
 *
 * Lastly, the staging store can be populated manually, through FTP for example.
 * In this case just use the link at the bottom of the page to start the update process.
 *
 * At the end of the staging process a link is displayed to launch [script]scripts/update.php[/script], that will
 * actually move the staging files to running directories.
 *
 * @see scripts/update.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Paddy
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'scripts.php';

// no local file at the moment
$id = NULL;
$external_id = NULL;

// load localized strings
i18n::bind('scripts');

// load the skin
load_skin('scripts');

// do not index this page
$context->sif('robots','noindex');

// the path to this page
$context['path_bar'] = array( 'scripts/' => i18n::s('Server software') );

// the title of the page
$context['page_title'] = i18n::s('Stage updated scripts');

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('scripts/stage.php'));

// only associates can proceed
elseif(!Surfer::is_associate()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// process uploaded data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// a file has been uploaded
	if(isset($_FILES['upload']['name']) && $_FILES['upload']['name'] && ($_FILES['upload']['name'] != 'none')) {

		// remember external name
		$external_id = $_FILES['upload']['name'];

		// access the temporary uploaded file
		$id = $_FILES['upload']['tmp_name'];

		// zero bytes transmitted
		$_REQUEST['file_size'] = $_FILES['upload']['size'];
		if(!$_FILES['upload']['size'])
			Logger::error(i18n::s('Nothing has been received.'));

		// check provided upload name
		elseif(!Safe::is_uploaded_file($id))
			Logger::error(i18n::s('Possible file attack.'));

	// no file has been uploaded
	} else
		Logger::error(i18n::s('Nothing has been received.'));

// look in archives available locally
} else {
	if(isset($_REQUEST['id']))
		$id = $_REQUEST['id'];
	elseif(isset($context['arguments'][0]))
		$id = $context['arguments'][0];

	// fight against hackers
        if($id) {
            $id = preg_replace(FORBIDDEN_IN_PATHS, '', strip_tags($id));
            $external_id = basename($id);
        }

	// scope is limited to the inbox
	if($id)
		$id = $context['path_to_root'].'inbox/yacs/'.$id;
}

// process the provided file
if($id) {

	// not yet a success
	$success = FALSE;

	// ensure file exists
	if(!is_readable($id))
		Logger::error(sprintf(i18n::s('Impossible to read %s.'), basename($id)));

	// explode a .zip file
	elseif(isset($external_id) && preg_match('/\.zip$/i', $external_id)) {
		$zipfile = new ZipArchive();
                
                $zipfile->open($id);

		// extract archive components and save them in mentioned directory --strip yacs from path, if any
		if($zipfile->extractTo(Safe::realpath('scripts/staging'))) {
			$context['text'] .= '<p>'.sprintf(i18n::s('%d files have been extracted.'), $zipfile->numFiles)."</p>\n";
			$success = TRUE;
		} else
			Logger::error(sprintf(i18n::s('Nothing has been extracted from %s.'), $external_id));
                
                $zipfile->close();

	// ensure we have the external library to explode other kinds of archives
	} elseif(!is_readable('../included/tar.php'))
		Logger::error(i18n::s('Impossible to extract files.'));

	// explode the archive
	else {
		include_once $context['path_to_root'].'included/tar.php';
		$handle = new Archive_Tar($id);

		if($handle->extractModify($context['path_to_root'].'scripts/staging', 'yacs'))
			$success = TRUE;
		else
			Logger::error(sprintf(i18n::s('Impossible to complete update of the staging store from %s'), basename($external_id)));
	}

	// everything went fine
	if($success) {
		$context['text'] .= '<p>'.i18n::s('The staging directory has been updated.').'</p>';

		// forward to the update script
		$context['text'] .= '<form method="get" action="'.$context['url_to_root'].'scripts/update.php">'."\n"
			.'<p>'.Skin::build_submit_button(i18n::s('Review staged scripts before the update')).'</p>'."\n"
			.'</form>'."\n";

	}

// ask for something to process, except on error
} elseif(!count($context['error'])) {

	// the splash message
	$context['text'] .= '<p>'.i18n::s('This script allows you to stage new scripts that will then be used to update your YACS server.')."</p>\n";

	// warning if safe mode of limited time
	if(Safe::ini_get('safe_mode') || !is_callable('set_time_limit'))
		Logger::error(sprintf(i18n::s('Extended processing time is not allowed on this server. In case of trouble, please upload individual files manually to the <code>scripts/staging</code> directory, using your preferred FTP tool or equivalent. When this is completed, jump to %s to complete the software update.'), Skin::build_link('scripts/update.php', i18n::s('the update script'), 'basic')));

	// option #1 - in-band upload
	$context['text'] .= Skin::build_block(i18n::s('Direct upload'), 'title');

	// upload an archive
	$context['text'] .= '<p>'.i18n::s('Pick-up and upload the archive file to use for the upgrade.').'</p>';

	// the form to post an file
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" enctype="multipart/form-data"><div>';

	// the file
	$context['text'] .= '<input type="file" name="upload" id="focus" size="30" />'
		.' (&lt;&nbsp;'.$context['file_maximum_size'].i18n::s('bytes').')';

	// the submit button
	$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

	// end of the form
	$context['text'] .= '</div></form>';

	// this may take several minutes
	$context['text'] .= '<p>'.i18n::s('The upload will start on your click. Then the archive will be exploded to stage files. You may have to wait for minutes before getting a response displayed.').'</p>';

	// option #2 - out-of-band upload
	$context['text'] .= Skin::build_block(i18n::s('Indirect upload'), 'title');

	// use an available archive
	$context['text'] .= '<p>'.sprintf(i18n::s('If the file is too large for the web, you can upload it by yourself, for example with FTP, in the directory %s.'), '<code>inbox/yacs</code>').'</p>';

	// find available skin archives
	$archives = array();
	if($dir = Safe::opendir("../inbox/yacs")) {

		// scan the file system
		while(($file = Safe::readdir($dir)) !== FALSE) {

			// skip special files
			if(($file[0] == '.') || ($file[0] == '~'))
				continue;

			// skip non-archive files
			if(!preg_match('/(\.bz2|\.tar|\.tar.gz|\.tgz|\.zip)/i', $file))
				continue;

			// this is an archive to consider
			$archives[] = $file;

		}
		Safe::closedir($dir);

		// alphabetical order
		if(@count($archives))
			natsort($archives);

	}

	// list available archives
	if(count($archives)) {

		$context['text'] .= '<ul>';
		foreach($archives as $archive) {
			$context['text'] .= '<li>'.Skin::build_link('scripts/stage.php?id='.urlencode($archive), sprintf(i18n::s('Install release %s'), $archive), 'basic').'</li>';
		}
		$context['text'] .= '</ul>';

		// this may take several minutes
		$context['text'] .= '<p>'.i18n::s('Click to explode the selected archive. You may have to wait for some time before getting a response displayed.').'</p>';

	}

	// option #3 - out-of-band staging
	$context['text'] .= Skin::build_block(i18n::s('Direct staging'), 'title');

	// upload an archive
	$context['text'] .= '<p>'.sprintf(i18n::s('Ultimately, you can populate the directory %s by yourself. On completion you can start the %s.'), '<code>scripts/staging</code>', Skin::build_link('scripts/update.php', i18n::s('update process'))).'</p>';

}

// render the skin
render_skin();

?>
