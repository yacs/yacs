<?php
/**
 * concatenate js files
 *
 * This script reads all javascript from the directory included/browser
 * and builds a single file included/browser/library.js.
 *
 * If the library jsmin is available it is used to reduce the size of the
 * concatenated string.
 *
 * @see included/jsmin.php
 *
 * To give you some performance figures:
 * - 237426 bytes for prototype + scriptaculous combined files
 * - become 176343 bytes after jsmin processing (26% reduction)
 * - which become 43523 bytes after gzip compression (overall 82% reduction)
 * - without the jsmin extra step, gzip compression produces 56316 bytes (76% reduction)
 *
 * To run this script, the surfer has to be an associate, or no switch file exists.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../../shared/global.php';
include_once '../../scripts/scripts.php';

// load localized strings
i18n::bind('included');

// load the skin
load_skin('included');

// the path to this page
$context['path_bar'] = array( 'control/' => i18n::s('Control Panel') );

// the title of the page
$context['page_title'] = i18n::s('Build Javascript library');

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('included/browser/build.php'));

// only associates can proceed when a switch file exists
elseif(!Surfer::is_associate() && !(file_exists($context['path_to_root'].'parameters/switch.on') || file_exists($context['path_to_root'].'parameters/switch.off'))) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

	// forward to the index page
	$menu = array('control/' => i18n::s('Control Panel'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// the jsmin library is required
} elseif(!file_exists($context['path_to_root'].'included/jsmin.php')) {
	header('Status: 500 Internal Error', TRUE, 500);
	die('No way to compress Javascript files');

// do the action
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'confirm')) {

	// load the compression library
	include_once $context['path_to_root'].'included/jsmin.php';

	// list running scripts
	$context['text'] .= '<p>'.i18n::s('Compressing Javascript files...').BR."\n";

	// script to not compress
	$to_avoid = array(

		// this is us!
		'library.js',
		
		// already compressed
		'swfobject.js'
		
		);

	// process all js files
	$count = 0;
	foreach(Safe::glob($context['path_to_root'].'included/browser/*.js') as $name) {

		if(in_array(basename($name), $to_avoid))
			continue;

		$context['text'] .= 'included/browser/'.basename($name).' -> .js.jsmin'.BR."\n";

		// we do have some content
		if($text = Safe::file_get_contents($name)) {

			// actual compression
			$text = JSMin::minify($text);

			// save updated content
			Safe::file_put_contents($name.'.jsmin', $text);

			// one file has been compressed
			$count++;

		}
	}

	// report to surfer
	if($count)
		$context['text'] .= sprintf(i18n::s('%d files have been updated.'), $count)."\n";
	$context['text'] .= "</p>\n";

	// assemble the static library
	$context['text'] .= '<p>'.i18n::s('Assembling javascript files...').BR."\n";

	// the returned string
	$text = '';

	// load prototype.js
	if(file_exists($context['path_to_root'].'included/browser/prototype.js'.'.jsmin'))
		$name = $context['path_to_root'].'included/browser/prototype.js'.'.jsmin';
	else
		$name = $context['path_to_root'].'included/browser/prototype.js';
	$context['text'] .= 'included/browser/'.basename($name).BR."\n";
	$text .= Safe::file_get_contents($name)."\n";

	// effects
	if($context['with_debug'] == 'Y')
		Logger::remember('included/browser/build.php', 'effects.js', '', 'debug');
	
	// load effects.js
	if(file_exists($context['path_to_root'].'included/browser/effects.js'.'.jsmin'))
		$name = $context['path_to_root'].'included/browser/effects.js'.'.jsmin';
	else
		$name = $context['path_to_root'].'included/browser/effects.js';
	$context['text'] .= 'included/browser/'.basename($name).BR."\n";
	$text .= Safe::file_get_contents($name)."\n";

	// script to not load afterwards
	$to_avoid = array(

		// the target file
		'library.js',
		
		// already loaded
		'effects.js', 'prototype.js',

		// not used at the moment
		'builder.js', 'scriptaculous.js', 'slider.js', 'sound.js', 'unittest.js'

		);

	// read all compressed files
	if($names = Safe::glob('*.js.jsmin'))
		foreach($names as $name) {
			$name = str_replace('.js.jsmin', '.js', $name);
			if(in_array($name, $to_avoid))
				continue;

			// use the compressed version
			$context['text'] .= 'included/browser/'.$name.'.jsmin'.BR."\n";
			$text .= Safe::file_get_contents($context['path_to_root'].'included/browser/'.$name.'.jsmin')."\n";
			$to_avoid[] = $name;
		}

	// read all js files
	if($names = Safe::glob('*.js'))
		foreach($names as $name) {
			if(in_array($name, $to_avoid))
				continue;

			// use the regular version
			$context['text'] .= 'included/browser/'.basename($name).BR."\n";
			$text .= Safe::file_get_contents($context['path_to_root'].'included/browser/'.$name)."\n";
		}

	// save the library file
	Safe::file_put_contents($context['path_to_root'].'included/browser/library.js', $text);
	$context['text'] .= sprintf('%s has %d %s', 'included/browser/library.js', Safe::filesize($context['path_to_root'].'included/browser/library.js'), i18n::s('bytes')).'</p>';
	
	// display the execution time
	$time = round(get_micro_time() - $context['start_time'], 2);
	$context['text'] .= '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

// confirmation is required
} else {

	// the confirmation question
	$context['text'] .= '<b>'.sprintf(i18n::s('You are about to compress and assemble Javascript files. Are you sure?'), $context['file_mask'])."</b>\n";

	// the menu for this page
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'"><p>'
		.Skin::build_submit_button(i18n::s('Yes, I do want to compress and assemble Javascript files'))
		.'<input type="hidden" name="action" value="confirm" />'
		.'</p></form>'."\n";

	// this may take several minutes
	$context['text'] .= '<p>'.i18n::s('When you will click on the button the server will be immediately requested to proceed. However, because of the so many things to do on the back-end, you may have to wait for minutes before getting a response displayed. Thank you for your patience.')."</p>\n";

}

// render the skin
render_skin();


?>