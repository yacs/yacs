<?php
/**
 * display information on the running environment
 *
 * Depending on the subject selected, this script will provide detailed information:
 * - yacs - profiling data about yacs run-time
 * - phpinfo - simple call of phpinfo()
 * - sql - display the result of queries 'SHOW VARIABLES' and 'SHOW CHARACTER SET'
 * - images - check the availability of GD and related library functions
 *
 * If no topic is provided the script also lists a shortcut to background processing.
 *
 * @see agents/index.php
 *
 * If the file [code]demo.flag[/code] exists, the script assumes that this instance
 * of YACS runs in demonstration mode, and declines to provide phpInfo().
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// include the global declarations
include_once '../shared/global.php';

// locate the information source
$subject = NULL;
if(isset($_REQUEST['subject']))
	$subject = $_REQUEST['subject'];
if(isset($context['arguments'][0]))
	$subject = $context['arguments'][0];
$subject = strip_tags($subject);

// load localized strings
i18n::bind('control');

// load the skin
load_skin('control');

// the path to this page
$context['path_bar'] = array( 'control/' => i18n::s('Control Panel') );

// the default title of the page
$context['page_title'] = i18n::s('Run-time information');

// commands for associates
if(Surfer::is_associate()) {
	$context['page_tools'][] = Skin::build_link('control/info.php?subject=yacs', i18n::s('yacs'));
	$context['page_tools'][] = Skin::build_link('control/info.php?subject=phpinfo', i18n::s('phpinfo()'));
	$context['page_tools'][] = Skin::build_link('control/info.php?subject=sql', i18n::s('SQL'));
	$context['page_tools'][] = Skin::build_link('control/info.php?subject=images', i18n::s('images'));
}

// the user has to be an associate
if(!Surfer::is_associate()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

	// forward to the control panel
	$menu = array('control/' => i18n::s('Control Panel'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// give information
} elseif(isset($subject) && $subject) {

	switch($subject) {

	case 'yacs':	// yacs run-time

		// included files
		if($included_files = get_included_files()) {
			$text = '<ul>';
			foreach($included_files as $included_file)
				$text .= '<li>'.$included_file.'</li>';
			$text .= '</ul>';
			$context['text'] .= Skin::build_box(i18n::s('Included files'), $text);
		}

		// size of all variables
		if($all_vars = get_defined_vars()) {
			$all_keys = array_keys($all_vars);
			$text = '<ul>';
			foreach($all_keys as $key) {
				if(!is_array($all_vars[$key]))
					continue;
				if(!$count = count($all_vars[$key]))
					continue;
				$size = strlen(serialize($all_vars[$key]));
				$text .= '<li>'.$key.' ('.$count.' vars, '.$size.' bytes)</li>';
			}
			$text .= '</ul>';
			$context['text'] .= Skin::build_box(i18n::s('Variables'), $text);
		}

		// count constants and their size
		if($all_constants = get_defined_constants()) {
			$count = count($all_constants);
			$size = strlen(serialize($all_constants));
			$text = $count.' constants, '.$size.' bytes';
			$context['text'] .= Skin::build_box(i18n::s('Constants'), $text);
		}

		// memory status
		if(is_callable('memory_get_usage')) {
			$text = memory_get_usage().' bytes';
			$context['text'] .= Skin::build_box(i18n::s('Memory'), $text);
		}

		break;

	case 'phpinfo': // phpinfo(), no more, no less

		// nothing more in demo mode
		if(file_exists($context['path_to_root'].'parameters/demo.flag')) {
			Logger::error(i18n::s('You are not allowed to perform this operation in demonstration mode.'));

		// call is disable
		} elseif(!is_callable('phpinfo')) {
			$context['text'] .= '<p>'.i18n::s('Calls to phpinfo() are not allowed on this server.')."</p>\n";


		// phpinfo()
		} elseif(is_callable('ob_start') && is_callable('ob_get_contents') && is_callable('ob_end_clean')) {
			ob_start();
			phpinfo();
			$context['text'] .= preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', ob_get_contents());
			ob_end_clean();
		} else {
			phpinfo();
			return;
		}

		break;

	case 'sql': // display SQL run-time information

		// basic or improved engine?
		if(is_callable('mysqli_connect'))
			$context['text'] .= '<p>'.i18n::s('YACS uses the improved MySQL PHP extension.')."</p>\n";

		// is database used as utf8?
		if(isset($context['database_is_utf8']) && $context['database_is_utf8'])
			$context['text'] .= '<p>'.i18n::s('YACS saves data using UTF8 character set.')."</p>\n";
		else
			$context['text'] .= '<p>'.i18n::s('YACS saves data Unicode entities encoded in ASCII character set.')."</p>\n";

		// 'SHOW STATUS'
		$query = "SHOW STATUS";
		if(!$result =& SQL::query($query)) {
			$content = Logger::error_pop().BR."\n";
		} else {
			$content = "<table>\n";
			while($row =& SQL::fetch($result)) {
				$content .= '<tr><td>'.$row['Variable_name'].'</td><td>'.$row['Value']."</td></tr>\n";
			}
			$content .= "</table>\n";
		}
		$context['text'] .= Skin::build_box(i18n::s('SQL status'), $content, 'folder');

		// 'SHOW VARIABLES'
		$query = "SHOW VARIABLES";
		if(!$result =& SQL::query($query)) {
			$content = Logger::error_pop().BR."\n";
		} else {
			$content = "<table>\n";
			while($row =& SQL::fetch($result)) {
				$content .= '<tr><td>'.$row['Variable_name'].'</td><td>'.$row['Value']."</td></tr>\n";
			}
			$content .= "</table>\n";
		}
		$context['text'] .= Skin::build_box(i18n::s('SQL variables'), $content, 'folder');

		// 'SHOW CHARACTER SET'
		$query = "SHOW CHARACTER SET";
		if(!$result =& SQL::query($query)) {
			$content = Logger::error_pop().BR."\n";
		} else {
			$content = "<table>\n";
			while($row =& SQL::fetch($result)) {
				$content .= '<tr><td>'.$row['Charset'].'</td><td>'.$row['Description']."</td></tr>\n";
			}
			$content .= "</table>\n";
		}
		$context['text'] .= Skin::build_box(i18n::s('Supported charsets'), $content, 'folder');

		break;

	case 'images':	// support of graphics

		// GD has not been activated at all
		if(!is_callable('ImageTypes')) {
			$context['text'] .= '<p>'.i18n::s('Please activate the GD module, else YACS can not resize images.')."</p>\n";

		// ok, GD is present
		} else {

			// GIF
			if(ImageTypes() & IMG_GIF)
				$context['text'] .= '<p>'.i18n::s('GIF support is enabled')."</p>\n";
			else
				$context['text'] .= '<p>'.i18n::s('GIF support is NOT enabled')."</p>\n";

			// JPG
			if(ImageTypes() & IMG_JPG)
				$context['text'] .= '<p>'.i18n::s('JPG support is enabled')."</p>\n";

			// PNG
			if(ImageTypes() & IMG_PNG)
				$context['text'] .= '<p>'.i18n::s('PNG support is enabled')."</p>\n";

		}

		break;
	}

// select the information source
} else {

	// the splash message
	$context['text'] .= '<p>'.i18n::s('Please select the action to perform.').'</p>'
		.'<ul>'
		.'<li>'.sprintf(i18n::s('%s, to learn more about YACS run-time.'), '<a href="'.$context['url_to_root'].'control/info.php?subject=yacs">'.i18n::s('YACS').'</a>').'</li>'
		.'<li>'.sprintf(i18n::s('%s, to know everything about PHP runtime at this server.'), '<a href="'.$context['url_to_root'].'control/info.php?subject=phpinfo">'.i18n::s('PhpInfo').'</a>').'</li>'
		.'<li>'.sprintf(i18n::s('%s, to get run-time parameters of the SQL engine.'), '<a href="'.$context['url_to_root'].'control/info.php?subject=sql">'.i18n::s('SQL').'</a>').'</li>'
		.'<li>'.sprintf(i18n::s('%s, to understand image support at this site.'), '<a href="'.$context['url_to_root'].'control/info.php?subject=images">'.i18n::s('Images').'</a>').'</li>'
		.'</ul>';

}

// render the skin
render_skin();
?>