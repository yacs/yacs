<?php
/**
 * edit a skin remotely
 *
 * This script allows the modification of some cascaded style sheet and of the template itself through a web interface.
 *
 * Accept following invocations:
 * - edit.php/original_skin
 * - edit.php?skin=original_skin
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
include_once '../shared/global.php';

// the skin under test
$skin = '';
if(isset($_REQUEST['skin']))
	$skin = $_REQUEST['skin'];
if(isset($context['arguments'][0]))
	$skin = $context['arguments'][0];

// avoid potential attacks
$skin = preg_replace(FORBIDDEN_IN_PATHS, '', strip_tags($skin));

// back to current skin if there is no template.php
if(!file_exists($context['path_to_root'].'skins/'.$skin.'/template.php'))
	$skin = str_replace('skins/', '', $context['skin']);

// the file to edit
$file = '';
if(isset($_REQUEST['file']))
	$file = $_REQUEST['file'];
if(isset($context['arguments'][1]))
	$file = $context['arguments'][1];

// avoid potential attacks
$file = preg_replace(FORBIDDEN_IN_PATHS, '', strip_tags($file));

// load the skin
load_skin('skins');

// the path to this page
$context['path_bar'] = array( 'skins/' => i18n::s('Themes') );

// the title of the page
$context['page_title'] = i18n::s('Skin editor');

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// anonymous users are invited to log in or to register
} elseif(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('skins/edit.php'));

// only associates can use this tool
elseif(!Surfer::is_associate()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// only *.css and template.php can be modified
} elseif($file && !preg_match('/(\.css|template\.php)$/i', $file)) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// ensure the file already exists
} elseif($file && !file_exists($context['path_to_root'].'skins/'.$skin.'/'.$file)) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// save the content of an updated file
} elseif(isset($_REQUEST['content']) && $_REQUEST['content']) {

	// warning if modification of some reference skin
	if(isset($_REQUEST['content']) && $_REQUEST['content'] && preg_match('/^(boxesandarrows|digital|joi|skeleton)$/', $skin))
		Logger::error(sprintf(i18n::s('Do not attempt to modify a reference theme directly, your changes would be overwritten on next software update. %s instead to preserve your work over time.'), Skin::build_link('skins/derive.php', i18n::s('Derive a theme'), 'shortcut')));

	// backup the old version, if any
	Safe::unlink($context['path_to_root'].'skins/'.$skin.'/'.$file.'.bak');
	Safe::rename($context['path_to_root'].'skins/'.$skin.'/'.$file, $context['path_to_root'].'skins/'.$skin.'/'.$file.'.bak');

	// actual save
	if(Safe::file_put_contents('skins/'.$skin.'/'.$file, $_REQUEST['content']) != strlen($_REQUEST['content']))
		Logger::error(sprintf(i18n::s('The target file %s may have been corrupted. Please check file content manually, and revert to the backup file, with the extension .bak, if necessary.'), 'skins/'.$skin.'/'.$file));

	// congratulations
	else {
		$context['text'] .= '<p>'.sprintf(i18n::s('The target file %s has been successfully updated.'), 'skins/'.$skin.'/'.$file).'</p>';

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu = array_merge($menu, array('skins/test.php?skin='.urlencode($skin) => i18n::s('Test this theme')));
		$menu = array_merge($menu, array('skins/edit.php?skin='.urlencode($skin) => i18n::s('Edit this theme')));
		$menu = array_merge($menu, array('skins/' => i18n::s('Themes')));
		$menu = array_merge($menu, array('skins/configure.php' => i18n::s('Configure the page factory')));
		$follow_up .= Skin::build_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	}

// select the file to edit
} else {

	// allow to edit another skin
	$context['text'] .= '<form method="get" action="'.$context['script_url'].'"><p>';

	$context['text'] .= i18n::s('Select a theme').' <select name="skin">';
	if ($dir = Safe::opendir("../skins")) {

		// valid skins have a template.php
		$items = array();
		while(($item = Safe::readdir($dir)) !== FALSE) {
			if(($item[0] == '.') || !is_dir('../skins/'.$item))
				continue;
			if(!file_exists('../skins/'.$item.'/template.php'))
				continue;
			$checked = '';
			if($skin == $item)
				$checked = ' selected="selected"';
			$items[] = '<option value="'.$item.'"'.$checked.'>'.$item."</option>\n";
		}
		Safe::closedir($dir);

		// list items by alphabetical order
		if(@count($items)) {
			natsort($items);
			foreach($items as $item)
				$context['text'] .= $item;
		}
	}
	$context['text'] .= '</select> '.Skin::build_submit_button(' &raquo; ').'</p></form>';

	// list all cascaded style sheets and template.php for this skin
	$context['text'] .= '<form method="get" action="'.$context['script_url'].'"><p>'
		.'<input type="hidden" name="skin" value="'.encode_field($skin).'" />';
	$context['text'] .= i18n::s('Files').' <select name="file">';
	if ($dir = Safe::opendir("../skins/".$skin)) {

		// list files in the skin directory
		$items = array();
		while(($item = Safe::readdir($dir)) !== FALSE) {
			if(($item[0] == '.') || is_dir('../skins/'.$item.'/'.$file))
				continue;
			if(!preg_match('/(\.css|template.php)$/i', $item))
				continue;
			$checked = '';
			if($file == $item)
				$checked = ' selected="selected"';
			$items[] = '<option value="'.$item.'"'.$checked.'>skin/'.$skin.'/'.$item."</option>\n";
		}
		Safe::closedir($dir);

		// list items by alphabetical order
		if(@count($items)) {
			natsort($items);
			foreach($items as $item)
				$context['text'] .= $item;
		}
	}
	$context['text'] .= '</select> '.Skin::build_submit_button(' &raquo; ').'</p></form>';

	// allow for content modification
	if($file) {

		// start of the form
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'"><div>'
			.'<input type="hidden" name="skin" value="'.encode_field($skin).'" />'
			.'<input type="hidden" name="file" value="'.encode_field($file).'" />';

		// load file content
		if(!$content = Safe::file_get_contents('../skins/'.$skin.'/'.$file))
			Logger::error(i18n::s('No file has been transmitted.'));

		// textarea to edit the file
		$context['text'] .= '<textarea name="content" rows="25" cols="50" accesskey="c">'.encode_field($content).'</textarea>';

		// button to upload changes
		$context['text'] .= BR.Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's').'</div></form>'."\n";

	}
}

// render the skin
render_skin();

?>