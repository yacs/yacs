<?php
/**
 * upload and install a skin package
 *
 * A skin package is made of an archive file, containing templates, the skin library, css files, etc.
 *
 * The archive file may have been pushed separately to the directory [code]inbox/skins[/code],
 * or it may be uploaded unattended.
 *
 * In both cases this script will explode the archive to the target skin directory.
 *
 * Only associates can use this script.
 *
 * This script relies on an external library to handle archive files.
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// the maximum size for uploads
$file_maximum_size = str_replace('M', '000000', Safe::get_cfg_var('upload_max_filesize'));
if(!$file_maximum_size || $file_maximum_size > 10000000)
	$file_maximum_size = 10000000;

// the target file
$id = NULL;
$name = NULL;

// load the skin
load_skin('skins');

// the path to this page
$context['path_bar'] = array( 'skins/' => i18n::s('Skins') );

// the title of the page
$context['page_title'] = i18n::s('Upload a skin');

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('skins/upload.php'));

// only associates can proceed
elseif(!Surfer::is_associate()) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// process uploaded data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// nothing has been uploaded
	if(!$_FILES['upload']['name'] || ($_FILES['upload']['name'] == 'none'))
		Skin::error(i18n::s('Nothing has been received.'));

	// a file has been uploaded
	else {

		// access the temporary uploaded file
		$id = $_FILES['upload']['tmp_name'];
		$name = $_FILES['upload']['name'];

		// zero bytes transmitted
		$_REQUEST['file_size'] = $_FILES['upload']['size'];
		if(!$_FILES['upload']['size'])
			Skin::error(i18n::s('Nothing has been received.'));

		// check provided upload name
		elseif(!Safe::is_uploaded_file($id))
			Skin::error(i18n::s('Possible file attack.'));

	}

// look in archives available locally
} else {
	if(isset($_REQUEST['id']))
		$id = $_REQUEST['id'];
	elseif(isset($context['arguments'][0]))
		$id = $context['arguments'][0];

	// fight against hackers
	$id = preg_replace(FORBIDDEN_STRINGS_IN_PATHS, '', strip_tags($id));
	$name = basename($id);

	// scope is limited to the inbox
	if($id)
		$id = $context['path_to_root'].'inbox/skins/'.$id;
}

// process the provided file
if($id) {

	// not yet a success
	$success = FALSE;

	// ensure file exists
	if(!is_readable($id))
		Skin::error(sprintf(i18n::s('Impossible to read %s.'), basename($id)));

	// explode a .zip file
	elseif(isset($name) && preg_match('/\.zip$/i', $name)) {
		include_once '../shared/zipfile.php';
		$zipfile = new zipfile();

		// extract archive components and save them in mentioned directory
		if($count = $zipfile->explode($id, 'skins')) {
			$context['text'] .= '<p>'.sprintf(i18n::s('%d files have been extracted.'), $count)."</p>\n";
			$success = TRUE;
		} else
			Skin::error(sprintf(i18n::s('Nothing has been extracted from %s.'), $name));

	// ensure we have the external library to explode other kinds of archives
	} elseif(!is_readable('../included/tar.php'))
			Skin::error(i18n::s('Impossible to extract files.'));

	// explode the archive
	else {
		include_once $context['path_to_root'].'included/tar.php';
		$handle =& new Archive_Tar($id);

		if($handle->extract($context['path_to_root'].'skins'))
			$success = TRUE;
		else
			Skin::error(sprintf(i18n::s('Error while processing %s.'), isset($name)?$name:basename($id)));

	}

	// everything went fine
	if($success) {
		$context['text'] .= '<p>'.i18n::s('Congratulations, the skins directory has been updated.').'</p>'
			.'<p>'.i18n::s('You can now visit the skins directory to test the behaviour of available skins, and to select the one you wish.').'</p>';

		// display follow-up commands
		$menu = array( 'skins/' => i18n::s('Go to the skins directory') );
		$context['text'] .= Skin::build_list($menu, 'menu_bar');

	}

	// clear the cache, to avoid side effects of complex updates
	$context['text'] .= Cache::clear();

// ask for something to process, except on error
} elseif(!count($context['error'])) {

	// the splash message
	$context['text'] .= '<p>'.i18n::s('This script allows you to install or update a skin package to your YACS server.')."</p>\n";

	// the form to post an file
	$context['text'] .= '<form method="post" enctype="multipart/form-data" action="'.$context['script_url'].'" id="main_form"><div>';

	// upload an archive
	$context['text'] .= '<p>'.i18n::s('Select the archive file that you want to install remotely.').'</p>';

	// the file
	$label = i18n::s('File');
	$size_hint = preg_replace('/000$/', 'k', preg_replace('/000000$/', 'M', $file_maximum_size));
	$input = '<input type="hidden" name="MAX_FILE_SIZE" value="'.$file_maximum_size.'" />'
		.'<input type="file" name="upload" id="focus" size="30"'.EOT
		.' (&lt;&nbsp;'.$size_hint.'&nbsp;'.i18n::s('bytes').')';
	$hint = i18n::s('Select the file to upload');
	$fields[] = array($label, $input, $hint);

	// build the form
	$context['text'] .= Skin::build_form($fields);

	// the submit button
	$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

	// end of the form
	$context['text'] .= '</div></form>';

	// the script used for form handling at the browser
	$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
		.'// set the focus on first form field'."\n"
		.'document.getElementById("focus").focus();'."\n"
		.'// ]]></script>'."\n";

	// use an available archive
	$context['text'] .= '<p>'.i18n::s('Alternatively, this script is able to handle archives that have been put in the directory <code>inbox/skins</code>.').'</p>';

	// find available skin archives
	$archives = array();
	if($dir = Safe::opendir("../inbox/skins")) {

		// scan the file system
		while(($file = Safe::readdir($dir)) !== FALSE) {

			// skip special entries
			if($file == '.' || $file == '..')
				continue;

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
			sort($archives);

	}

	// list available archives
	if(count($archives)) {

		$context['text'] .= '<ul>';
		foreach($archives as $archive)
			$context['text'] .= '<li>'.Skin::build_link('skins/upload.php?id='.urlencode($archive), sprintf(i18n::s('Install skin %s'), $archive), 'basic').'</li>';
		$context['text'] .= '</ul>';

	}

	// general help on this form
	$help = '<p>'.sprintf(i18n::s('For more information on YACS skins, go %s.'), Skin::build_link('http://www.yetanothercommunitysystem.com/scripts/view.php/skins/', i18n::s('here'), 'external')).'</p>';
	$context['extra'] .= Skin::build_box(i18n::s('Help'), $help, 'navigation', 'help');

}

// render the skin
render_skin();

?>