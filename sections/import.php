<?php
/**
 * import some content for this section
 *
 * This script helps to populate a section.
 *
 * If an archive file is uploaded, its content is extracted automatically.
 *
 * Only associates can use this script.
 *
 * This script relies on an external library to handle archive files.
 *
 * Accept following invocations:
 * - import.php?id=123
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../files/files.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Sections::get($id);

// load the skin
load_skin('sections');

// the path to this page
$context['path_bar'] = array( 'sections/' => i18n::s('Site map') );

// the title of the page
$context['page_title'] = i18n::s('Import section content');

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// anonymous users are invited to log in or to register
} elseif(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Sections::get_url($item['id'], 'import')));

// only associates can proceed
elseif(!Surfer::is_associate()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// process uploaded data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

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
		elseif(!preg_match('/\.(bz2*|tar\.gz|tgz|zip)$/i', $name)) {

			if(!$content = Safe::file_get_contents($temporary))
				Logger::error(sprintf(i18n::s('Error while processing %s.'), isset($name)?$name:basename($temporary)));

			else {

				// convert charset
				if(preg_match('/<meta http-equiv="Content-Type" content="text\/html; charset=iso-8859/', $content))
					$content = utf8::from_iso8859($content);

				// a brand new page
				$article = array();
				$article['anchor'] = 'section:'.$item['id'];

				// extract page title
				if(preg_match('/<title>(.+)<\/title>/is', $content, $matches))
					$article['title'] = $matches[1];
				elseif(preg_match('/<h2>(.+)<\/h2>/is', $content, $matches))
					$article['title'] = $matches[1];
				else
					$article['title'] = i18n::s('Imported page');

				// remove the prefix
				if(isset($_REQUEST['prefixSeparator']))
					$content = preg_replace('/^.*'.preg_quote($_REQUEST['prefixSeparator'], '/').'/ms', '', $content);

				// remove the suffix
				if(isset($_REQUEST['suffixSeparator']))
					$content = preg_replace('/'.preg_quote($_REQUEST['suffixSeparator'], '/').'.*$/ms', '', $content);

				// page content
				$article['description'] = $content;

				if($id = Articles::post($article))
					$success = TRUE;
				else
					Logger::error(sprintf(i18n::s('Error while processing %s.'), isset($name)?$name:basename($temporary)));

			}

		// explode a .zip file
		} elseif(isset($name) && preg_match('/\.zip$/i', $name)) {
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
			$handle =& new Archive_Tar($temporary);

			if($handle->extract($context['path_to_root']))
				$success = TRUE;
			else
				Logger::error(sprintf(i18n::s('Error while processing %s.'), isset($name)?$name:basename($temporary)));

		}

		// everything went fine
		if($success) {
			$context['text'] .= '<p>'.i18n::s('Congratulations, you have shared new content.').'</p>';

			// display follow-up commands
			$menu = array( Sections::get_permalink($item) => i18n::s('Back to the section') );
			$context['text'] .= Skin::build_list($menu, 'menu_bar');

		}

		// clear the cache, to avoid side effects of complex updates
		Cache::clear();

	}

// offer to upload a file
} else {

	// the splash message
	$context['text'] .= '<p>'.i18n::s('This script allows you to upload static web pages and to make dynamic articles in the database.')."</p>\n";

	// the form to post a file
	$context['text'] .= '<form method="post" enctype="multipart/form-data" action="'.$context['script_url'].'" id="main_form"><div>';

	// the file
	$label = i18n::s('File');
	$input = '<input type="file" name="upload" id="focus" size="30" />'
		.' (&lt;&nbsp;'.$context['file_maximum_size'].i18n::s('bytes').')';
	$hint = i18n::s('Select the file to upload');
	$fields[] = array($label, $input, $hint);

	// prefix separator
	$label = i18n::s('Prefix separator');
	$input = '<input type="text" name="prefixSeparator" size="30" value="" />';
	$hint = i18n::s('All characters before this string will be removed');
	$fields[] = array($label, $input, $hint);

	// suffix separator
	$label = i18n::s('Suffix separator');
	$input = '<input type="text" name="suffixSeparator" size="30" value="" />';
	$hint = i18n::s('All characters after this string will be removed');
	$fields[] = array($label, $input, $hint);

	// build the form
	$context['text'] .= Skin::build_form($fields);

	// the submit button
	$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

	// id of the target section
	$context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'" />';

	// end of the form
	$context['text'] .= '</div></form>';

	// the script used for form handling at the browser
	$context['text'] .= JS_PREFIX
		.'// set the focus on first form field'."\n"
		.'$("focus").focus();'."\n"
		.JS_SUFFIX."\n";

}

// render the skin
render_skin();

?>