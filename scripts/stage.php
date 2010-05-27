<?php
/**
 * stage updated files
 *
 * As explained into [script]scripts/index.php[/script], the local staging store is built after a reference store.
 *
 * @see scripts/index.php
 *
 * The reference store can be a remote server, usually, www.yacs.fr.
 * Server name can be changed from the configuration panel for scripts.
 *
 * Or staging files can be provided in a local archive containing all files of some YACS release.
 * The archive may be uploaded unattended, or it can have been put in the directory inbox/yacs.
 *
 * Lastly, the staging store can be populated manually, through FTP for example.
 * In this case just use the link at the bottom of the page to start the update process.
 *
 * Content of the staging store is built as follows:
 * - if a file has been uploaded, its content is used
 * - else if a reference to a local archive has been provided, its content is used
 * - else if it is a POST, some remote server is contacted
 *
 * To achieve staging, the signature file [code]footprints.php[/code] is extracted from the reference store
 * and put in the [code]scripts/staging[/code] directory.
 *
 * Then every running script on the local server is checked against the signature file, and if a difference occurs
 * the related reference script is extracted from the reference store and saved in the [code]scripts/staging[/code] directory.
 *
 * Note that scripts that have to be executed only once will be transferred also only once.
 * Because the script [script]scripts/run_once.php[/script] adds the extension '.done' to executed scripts,
 * an additional step has been added to the staging process.
 *
 * @see scripts/run_once.php
 *
 * Therefore, the algorithm used to transfer run once files to the staging repository involves following steps:
 * - ran script - if the script appears in the signature file, and if a local file has the same name plus the extension '.done', then skip the update
 * - new script - if the script appears in the signature file, but not in the running environment, it has to be transferred
 * - updated script - if the running script has a different signature, the reference script has to be transferred
 *
 * At the end of the staging process a link is displayed to launch [script]scripts/update.php[/script], that will
 * actually move the staging files to running directories. But this is another story.
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
include_once '../links/link.php';
include_once 'scripts.php';

// parameters for scripts
Safe::load('parameters/scripts.include.php');

// ensure we have a default reference server
if(!isset($context['reference_server']) || !$context['reference_server'])
	$context['reference_server'] = i18n::s('www.yacs.fr');

// no local file at the moment
$id = NULL;
$external_id = NULL;

// load localized strings
i18n::bind('scripts');

// load the skin
load_skin('scripts');

// the path to this page
$context['path_bar'] = array( 'scripts/' => i18n::s('Server software') );

// the title of the page
$context['page_title'] = i18n::s('Stage updated scripts');

/**
 * delete a directory and all of its content
 *
 * @param string the directory to delete
 */
function delete_all($path) {
	global $context;

	$path_translated = str_replace('//', '/', $context['path_to_root'].'/'.$path);
	if($handle = Safe::opendir($path_translated)) {

		while(($node = Safe::readdir($handle)) !== FALSE) {

			if($node[0] == '.')
				continue;

			// make a real name
			$target = str_replace('//', '/', $path.'/'.$node);
			$target_translated = str_replace('//', '/', $path_translated.'/'.$node);

			// delete a sub directory
			if(is_dir($target_translated)) {
				delete_all($path.'/'.$node);
				Safe::rmdir($target_translated);

			// delete the node
			} else
				Safe::unlink($target_translated);

			// statistics
			global $deleted_nodes;
			$deleted_nodes++;

		}
		Safe::closedir($handle);
	}

}

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('scripts/stage.php'));

// only associates can proceed
elseif(!Surfer::is_associate()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
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

	}

// look in archives available locally
} else {
	if(isset($_REQUEST['id']))
		$id = $_REQUEST['id'];
	elseif(isset($context['arguments'][0]))
		$id = $context['arguments'][0];

	// fight against hackers
	$id = preg_replace(FORBIDDEN_IN_PATHS, '', strip_tags($id));
	$external_id = basename($id);

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
		include_once '../shared/zipfile.php';
		$zipfile = new zipfile();

		// extract archive components and save them in mentioned directory --strip yacs from path, if any
		if($count = $zipfile->explode($id, 'scripts/staging', 'yacs/')) {
			$context['text'] .= '<p>'.sprintf(i18n::s('%d files have been extracted.'), $count)."</p>\n";
			$success = TRUE;
		} else
			Logger::error(sprintf(i18n::s('Nothing has been extracted from %s.'), $external_id));

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

// contact remote server
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// purge the staging directory
	$context['text'] .= '<p>'.i18n::s('Purging old staging files...').BR."\n";
	delete_all('scripts/staging');
	global $deleted_nodes;
	if($deleted_nodes)
		$context['text'] .= sprintf(i18n::s('%d items have been purged'), $deleted_nodes);
	$context['text'] .= "</p>\n";

	// get the reference footprints -- reference server have to be installed at the root
	$url = 'http://'.$context['reference_server'].'/scripts/fetch.php?script=footprints.php';
	if(($content = Link::fetch($url, '', '', 'scripts/stage.php')) === FALSE) {
		$context['text'] .= '<p>'.sprintf(i18n::s('Impossible to get %s. Please %s again.'), $url, '<a href="configure.php">'.i18n::s('configure').'</a>')."</p>\n";

		// forward to the index page
		$menu = array('scripts/' => i18n::s('Server software'));
		$context['text'] .= Skin::build_list($menu, 'menu_bar');

	// save the reference footprints in the cache
	} elseif(!Safe::file_put_contents('scripts/staging/footprints.php', $content))
		$context['text'] .= '<p>'.sprintf(i18n::s('Impossible to write to %s.'), 'scripts/staging/footprints.php')."</p>\n";

	// look for updated scripts and stage them
	else {
		$context['text'] .= '<p>'.i18n::s('Footprints of reference scripts have been saved in file scripts/staging/footprints.php.').BR."\n";

		// load the reference footprints
		include_once 'staging/footprints.php';

		// file is invalid
		if(!$generation['date'] || !$generation['server'] || !is_array($footprints)) {
			$context['text'] .= i18n::s('Invalid reference index. Staging has been cancelled.')."</p>\n";

			// forward to the index page
			$menu = array('scripts/' => i18n::s('Server software'));
			$context['text'] .= Skin::build_list($menu, 'menu_bar');

		// print date of reference build
		} else {
			$context['text'] .= sprintf(i18n::s('Reference set of %d files built on %s on server %s'), count($footprints), $generation['date'], $generation['server'])."</p>\n";

			// splash message
			$context['text'] .= '<p>'.i18n::s('Download of updated scripts. Please wait...').BR."\n";

			// sort the array by alphabetical order
			ksort($footprints);
			reset($footprints);

			// display one link per script to view it
			$staging_files = 0;
			$errors = 0;
			foreach($footprints as $file => $attributes) {

				// only consider php scripts at the moment
				if(!preg_match('/\.php$/', $file))
					continue;

				// never update run-once scripts
				if(file_exists($context['path_to_root'].$file.'.done'))
					continue;

				// is the current file version ok?
				if(is_readable($context['path_to_root'].$file) && ($result = Scripts::hash($file))) {

					// check that hashes are equal
					if($attributes[1] == $result[1])
						continue;

				}

				// do we have a suitable copy in staging repository?
				$staged = 'scripts/staging/'.$file;
				if(is_readable($context['path_to_root'].$staged) && ($result = Scripts::hash($staged))) {

					// used staged file
					$context['text'] .= sprintf(i18n::s('Using staged file %s'), $file).BR."\n";

					$staging_files++;
					continue;
				}

				// download the updated script from the reference server
				$context['text'] .= sprintf(i18n::s('Staging %s'), $file).BR."\n";

				// get the file -- reference server have to be installed at the root
				$url = 'http://'.$context['reference_server'].'/scripts/fetch.php?script='.$file;
				if(!$content = Link::fetch($url, '', '', 'scripts/stage.php')) {
					$context['text'] .= sprintf(i18n::s('Impossible to read %s.'), $url).BR."\n";
					$errors++;
					continue;
				}

				// ensure enough execution time
				Safe::set_time_limit(30);

				// save it in the staging store
				if(!Safe::file_put_contents('scripts/staging/'.$file, $content)) {
					$context['text'] .= '<p>'.sprintf(i18n::s('Impossible to write to %s.'), 'scripts/staging/'.$file)."</p>\n";
					$errors++;
					continue;
				}

				// ensure we have an exact copy by comparing hashes
				$staging_hash = Scripts::hash('scripts/staging/'.$file);
				if($attributes[1] != $staging_hash[1]) {
					$context['text'] .= i18n::s('File has been corrupted!').BR."\n";
					$errors++;
					continue;
				}

				// update statistics
				$staging_files++;

			}

			// errors have been encountered
			if($errors) {
				$context['text'] .= i18n::s('Errors have been encountered, and you cannot proceed with the network update.')."</p>\n";

				// forward to the index page
				$menu = array('scripts/' => i18n::s('Server software'));
				$context['text'] .= Skin::build_list($menu, 'menu_bar');


			// server is up to date
			} elseif(!$staging_files) {
				$context['text'] .= i18n::s('No file has been staged. Scripts on your server are exact copies of the reference set.')."</p>\n";

				// forward to the index page
				$menu = array('scripts/' => i18n::s('Server software'));
				$context['text'] .= Skin::build_list($menu, 'menu_bar');

			// scripts are ready for update
			} else {
				$context['text'] .= sprintf(i18n::ns('%d file has been downloaded from the reference server.', '%d files have been downloaded from the reference server.', $staging_files), $staging_files)."</p>\n";

				// forward to the update script
				$context['text'] .= '<form method="get" action="'.$context['url_to_root'].'scripts/update.php">'."\n"
					.'<p>'.Skin::build_submit_button(i18n::s('Review staged scripts before the update')).'</p>'."\n"
					.'</form>'."\n";

			}
		}
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
	$context['text'] .= '<form method="post" enctype="multipart/form-data" action="'.$context['script_url'].'"><div>';

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

/*
	// option #3 - on-line staging
	$context['text'] .= Skin::build_block(i18n::s('Staging individual files'), 'title');

	// the splash message
	$context['text'] .= '<p>'.i18n::s('This script will compare the running scripts with those available on the reference server. Then it will attempt to download updated files in a staging directory. You will then be able to manually review updated scripts before actually using them on your site.').'</p>';

	// the submit button
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'
		.Skin::build_submit_button(sprintf(i18n::s('Yes, I want to stage files from %s'), $context['reference_server']), NULL, NULL, 'confirmed')
		.'</p></form>'."\n";

	// the script used for form handling at the browser
	$context['text'] .= JS_PREFIX
		.'// set the focus on first form field'."\n"
		.'$("confirmed").focus();'."\n"
		.JS_SUFFIX."\n";

	// this may take several minutes
	$context['text'] .= '<p>'.i18n::s('When you will click on the button the server will immediately start to stage updated scripts. However, because of the time requested to complete data exchanges, you may have to wait for minutes before getting a response displayed.').'</p>';
*/
	// option #4 - out-of-band staging
	$context['text'] .= Skin::build_block(i18n::s('Direct staging'), 'title');

	// upload an archive
	$context['text'] .= '<p>'.sprintf(i18n::s('Ultimately, you can populate the directory %s by yourself. On completion you can start the %s.'), '<code>scripts/staging</code>', Skin::build_link('scripts/update.php', i18n::s('update process'))).'</p>';

}

// render the skin
render_skin();

?>