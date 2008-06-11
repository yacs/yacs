<?php
/**
 * derive a new skin from an existing one
 *
 * This script allows any associate to copy an existing skin, before customizing it.
 *
 * An initial form is proposed to the surfer, to select the origin skin, and to name the target skin.
 * Optionnally, the new skin can be assigned specifically to one section.
 *
 * Then, files of the origin skin are parsed and copied to the new skin.
 *
 * Following patterns are removed from text files:
 * - @reference - the new skin is not part of the core set of YACS files
 *
 * Accept following invocations:
 * - derive.php/original_skin
 * - derive.php?skin=original_skin
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @tester Dim
 * @tester Agnes
 * @tester Ghjmora
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// look for the origin skin
$skin = $context['skin'];
if(isset($_REQUEST['skin']))
	$skin = $_REQUEST['skin'];
if(isset($context['arguments'][0]))
	$skin = $context['arguments'][0];

// avoid potential attacks
$skin = preg_replace(FORBIDDEN_STRINGS_IN_PATHS, '', strip_tags($skin));

// ensure there is a template.php
if(!file_exists($context['path_to_root'].'skins/'.$skin.'/template.php'))
	$skin = str_replace('skins/', '', $context['skin']);

// load the skin
load_skin('skins');

// the path to this page
$context['path_bar'] = array( 'skins/' => i18n::s('Skins') );

// the title of the page
$context['page_title'] = i18n::s('Derive a new skin from an existing one');

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('skins/derive.php'));

// only associates can use this tool
elseif(!Surfer::is_associate())
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// it is not allowed to rewrite one reference template
elseif(isset($_REQUEST['directory']) && preg_match('/^(boxesandarrows|digital|joi|skeleton)$/', $_REQUEST['directory'])) {
	Skin::error(i18n::s('Reference skins cannot be modified.'));

// do the job
} elseif(isset($_REQUEST['directory']) && $_REQUEST['directory']) {

	// ensure a safe skin name
	$directory = preg_replace('/[^a-zA-Z_0-9]/', '', $_REQUEST['directory']);
	if(!$directory)
		$directory = i18n::s('my_skin');

	// the splash message
	$context['text'] .= '<p>'.i18n::s('Please go to the end of this page to check results of the building process.')."</p>\n";

	// list origin files
	$context['text'] .= '<p>'.sprintf(i18n::s('Listing files of the originating skin %s...'), $skin).BR."\n";

	/**
	 * list all files below a certain path
	 *
	 * @param string the path to scan
	 * @return an array of file names
	 */
	function list_files_at($root, $path='') {
		global $context;

		// the list of files
		$files = array();

		$path_translated = rtrim(str_replace('//', '/', $context['path_to_root'].'/'.$root.'/'.$path), '/');
		if($handle = Safe::opendir($path_translated)) {

			while(($node = Safe::readdir($handle)) !== FALSE) {

				if($node == '.' || $node == '..')
					continue;

				// skip transient files
				if(preg_match('/\.cache$/i', $node))
					continue;

				// make a real name
				$target = str_replace('//', '/', $path.'/'.$node);
				$target_translated = str_replace('//', '/', $path_translated.'/'.$node);

				// extend the list recursively
				if(is_dir($target_translated))
					$files = array_merge($files, list_files_at($root, $target));

				// append the file to the list
				elseif(is_readable($target_translated))
					$files[] = $path.'/'.$node;

			}
			Safe::closedir($handle);
		}

		return $files;
	}

	// locate originating files
	$files = list_files_at('skins/'.$skin);
	if(is_array($files))
		$context['text'] .= sprintf(i18n::s('%d files have been found.'), count($files))."\n";
	$context['text'] .= "</p>\n";

	// no error yet
	$errors = 0;

	// create the new skin
	$context['text'] .= '<p>'.sprintf(i18n::s('Creating the directory for the new skin %s...'), $directory).BR."\n";

	// create a path for the new skin
	if(!Safe::make_path('skins/'.$directory)) {
		$context['text'] .= sprintf(i18n::s('Error: Unable to create path skins/%s'), $directory)."</p>\n";
		$errors++;

	// move forward
	} else {
		$context['text'] .= i18n::s('done.')."</p>\n";

		// copy files
		$context['text'] .= '<p>'.i18n::s('Copying files to the new skin...').BR."\n";

		// analyse each script
		foreach($files as $file) {

			// ensure we have enough time to process this script
			Safe::set_time_limit(30);

			// the origin file
			$origin = 'skins/'.$skin.$file;

			// the target file
			if($file == '/'.$skin.'.css')
				$target = 'skins/'.$directory.'/'.$directory.'.css';
			else
				$target = 'skins/'.$directory.$file;

			// ensure the path has been created
			Safe::make_path(dirname($target));

			// unlink previous files, if any
			Safe::unlink($context['path_to_root'].$target);

			// transcode php files
			if(preg_match('/(\.php|\.css)$/i', $target) && ($content = Safe::file_get_contents($context['path_to_root'].$origin))) {

				// change internal reference
				$content = preg_replace('/skins\/'.preg_quote($skin, '/').'/i', 'skins/'.$directory, $content);
				$content = preg_replace('/\''.preg_quote($skin, '/').'\'/i', "'".$directory."'", $content);
				$content = preg_replace('/'.preg_quote($skin, '/').'\.css/i', $directory.".css", $content);

				// not part of the reference set anymore
				$content = preg_replace('/\s*\*\s+@reference\s*\n/i', "\n", $content);

				// save it as the new cache file
				if(Safe::file_put_contents($target, $content))
					$context['text'] .= sprintf(i18n::s('%s has been transcoded'), $target).BR."\n";

				else {
					$context['text'] .= sprintf(i18n::s('Impossible to write to %s.'), $target).BR."\n";
					$errors++;
				}

			// copy the file
			} elseif(!Safe::copy($context['path_to_root'].$origin, $context['path_to_root'].$target)) {
				$context['text'] .= sprintf(i18n::s('Impossible to copy file %s.'), $target).BR."\n";
				$errors++;

			// attempt to preserve the modification date of the origin file
			} else {
				Safe::touch($context['path_to_root'].$target, Safe::filemtime($context['path_to_root'].$origin));
				$context['text'] .= sprintf(i18n::s('%s has been created'), $target).BR."\n";
			}

			// this will be filtered by umask anyway
			Safe::chmod($context['path_to_root'].$target, $context['file_mask']);

		}
		$context['text'] .= "</p>\n";
	}

	// some errors have occured
	if($errors)
		$context['text'] .= '<p>'.i18n::s('Some errors have occured and the skin has not been properly derived.')."</p>\n";

	// congratulations
	else {
		$context['text'] .= '<p>'.i18n::s('Congratulations, you have completed the creation of a new skin.').'</p>'
			.'<p>'.sprintf(i18n::s('Feel free to change and adjust files at skins/%s to better suit your needs.'), $directory).'</p>';

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu = array_merge($menu, array( 'control/configure.php?parameter=skin&value='.urlencode('skins/'.$directory) => i18n::s('Use this skin') ));
		$menu = array_merge($menu, array( 'skins/test.php?skin='.urlencode($directory) => i18n::s('Test the new skin') ));
		$menu = array_merge($menu, array( 'skins/' => i18n::s('Skins') ));
		$follow_up .= Skin::build_list($menu, 'page_menu');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	}


} else {

	// the splash message
	$context['text'] .= '<p>'.i18n::s('Do not attempt to modify a reference skin directly, your changes would be overwritten on next software update. To get a skin of your own that you can use safely, use this script to derive a new skin from an existing one instead.').'</p>';

	// start of the form
	$context['text'] .= '<form method="get" action="'.$context['script_url'].'"><div>';

	// step 1 - select an available skin
	$box = array();
	$box['title'] = i18n::s('Step 1- Select the skin you want to use');
	$box['text'] = '<p>'.i18n::s('Make your choice among available skins at this server:').' <select name="skin">';
	if ($dir = Safe::opendir("../skins")) {

		// valid skins have a template.php
		while(($file = Safe::readdir($dir)) !== FALSE) {
			if($file == '.' || $file == '..' || !is_dir('../skins/'.$file))
				continue;
			if(!file_exists('../skins/'.$file.'/template.php'))
				continue;

			// set a default skin
			if(!$context['skin'])
				$context['skin'] = 'skins/'.$file;

			$checked = '';
			if($skin == $file)
				$checked = ' selected="selected"';
			$skins[] = '<option value="'.$file.'"'.$checked.'>'.$file."</option>\n";
		}
		Safe::closedir($dir);
		if(@count($skins)) {
			sort($skins);
			foreach($skins as $skin)
				$box['text'] .= $skin;
		}
	}
	$box['text'] .= '</select>';

	$context['text'] .= Skin::build_box($box['title'], $box['text']);

	// step 2 - name the new skin
	$box = array();
	$box['title'] = i18n::s('Step 2- Name the new skin');

	// the name for the new skin
	$label = i18n::s('Skin name');
	$input = '<input type="text" name="directory" value="'.encode_field(i18n::s('my_skin')).'" size="45" maxlength="255" accesskey="d"'.EOT;
	$hint = i18n::s('Also the name of the sub-directory for skin files');
	$fields[] = array($label, $input, $hint);

	// section, if any
	$label = i18n::s('Section');
	$input = '<select name="section"><option value="none" selected="selected">'.i18n::s('-- Do not assign any section to this skin').'</option>'
		.Sections::get_options('none').'</select>';
	$hint = i18n::s('As an option, you can assign this template to all pages anchored to one particular section');
	$fields[] = array($label, $input, $hint);

	// build the form
	$box['text'] = Skin::build_form($fields);

	$context['text'] .= Skin::build_box($box['title'], $box['text']);

	// step 3 - do the job
	$box = array();
	$box['title'] = i18n::s('Step 3- Do the job');

	// the submit button
	$box['text'] = '<p>'.Skin::build_submit_button(i18n::s('Copy and transcode files'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

	$context['text'] .= Skin::build_box($box['title'], $box['text']);

	// end of the form
	$context['text'] .= '</div></form>';

	// general help on this form
	$help = '<p>'.sprintf(i18n::s('For more information on YACS skins, visit %s'), Skin::build_link(i18n::s('http://www.yetanothercommunitysystem.com/'), 'the YACS web site', 'external')).'</p>';
	$context['extra'] .= Skin::build_box(i18n::s('Help'), $help, 'navigation', 'help');

}

// render the skin
render_skin();

?>