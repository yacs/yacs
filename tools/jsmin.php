<?php
/**
 * finalize javascript libraries
 *
 * This script reads all javascript files from selected directories, compresses content,
 * and generates compacted .js files.
 *
 * It processes files from following directories:
 * - included/browser
 *
 * If the library jsmin is available it is used to reduce the size of the
 * concatenated string.
 *
 * @see included/jsmin.php
 *
 * To run this script, the surfer has to be an associate, or no switch file exists.
 *
 * @author Bernard Paques
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../scripts/scripts.php';

// load localized strings
i18n::bind('tools');

// load the skin
load_skin('tools');

// the path to this page
$context['path_bar'] = array( 'tools/' => i18n::s('Tools') );

// the title of the page
$context['page_title'] = i18n::s('Finalize Javascript libraries');

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('included/browser/build.php'));

// only associates can proceed when a switch file exists
elseif(!Surfer::is_associate() && !(file_exists($context['path_to_root'].'parameters/switch.on') || file_exists($context['path_to_root'].'parameters/switch.off'))) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
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

	// script to not compress (provide name1.ext, name2.ext...)
	$to_avoid = array(

		);

	// process all js files in included/browser/header
	$count = 0;
	$minified = '';
	foreach(Safe::glob($context['path_to_root'].'included/browser/js_header/*.js') as $name) {

		if(in_array(basename($name), $to_avoid))
			continue;

		$context['text'] .= 'included/browser/header/'.basename($name).BR."\n";

		// we do have some content
		if($text = Safe::file_get_contents($name)) {

			// actual compression
			if(!preg_match('/\.min\./', basename($name)))
			    $minified .= JSMin::minify($text);
			else
			    $minified .= $text;				

			// one file has been compressed
			$count++;

		}
	}
	// save the library to call in page header
	Safe::file_put_contents($context['path_to_root'].'included/browser/library_js_header.min.js', $minified);

	// do the same with included/browser/footer, including shared/yacs.js
	$minified ='';
	foreach(Safe::glob($context['path_to_root'].'included/browser/js_endpage/*.js') as $name) {

		if(in_array(basename($name), $to_avoid))
			continue;

		$context['text'] .= 'included/browser/footer/'.basename($name).BR."\n";

		// we do have some content
		if($text = Safe::file_get_contents($name)) {

			// actual compression
			// actual compression
			if(!preg_match('/\.min\./', basename($name)))
			    $minified .= JSMin::minify($text);
			else
			    $minified .= $text;

			// one file has been compressed
			$count++;

		}
	}
	// include shared/yacs.js library
	if(file_exists($context['path_to_root'].'shared/yacs.js')) {
	    $context['text'] .= 'shared/yacs.js'.BR."\n";
	    $text = Safe::file_get_contents($context['path_to_root'].'shared/yacs.js');
	    $minified .= JSMin::minify($text);
	    $count++;
	}
	// save the library to call in page footer
	Safe::file_put_contents($context['path_to_root'].'included/browser/library_js_endpage.min.js', $minified);

	// do the same in included/calendar
	if($names = Safe::glob($context['path_to_root'].'included/jscalendar/*.js')) {
		foreach($names as $name) {

			$context['text'] .= 'included/calendar/'.basename($name).' -> .js.jsmin'.BR."\n";

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
	}

	// report to surfer
	if($count)
		$context['text'] .= sprintf(i18n::s('%d files have been minified.'), $count)."\n";
	$context['text'] .= "</p>\n";

	$context['text'] .= '<p>'.sprintf('%s has %d %s', 'included/browser/library_js_header.min.js', Safe::filesize($context['path_to_root'].'included/browser/library_js_header.min.js'), i18n::s('bytes')).'</p>';
	$context['text'] .= '<p>'.sprintf('%s has %d %s', 'included/browser/library_js_endpage.min.js', Safe::filesize($context['path_to_root'].'included/browser/library_js_endpage.min.js'), i18n::s('bytes')).'</p>';

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
