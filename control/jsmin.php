<?php
/**
 * compress running javascript files
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
 * To run this script, the surfer has to be an associate, or no switch file
 * exists.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../scripts/scripts.php';

// load localized strings
i18n::bind('control');

// load the skin
load_skin('control');

// the path to this page
$context['path_bar'] = array( 'control/' => i18n::s('Control Panel') );

// the title of the page
$context['page_title'] = i18n::s('Compress Javascript files');

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('control/jsmin.php'));

// only associates can proceed when a switch file exists
elseif(!Surfer::is_associate() && !(file_exists($context['path_to_root'].'parameters/switch.on') || file_exists($context['path_to_root'].'parameters/switch.off'))) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	$context['text'] .= '<p>'.i18n::s('You are not allowed to perform this operation.')."</p>\n";

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

	// paths to scan
	$patterns = array(
		$context['path_to_root'].'included/browser/*.js',
		$context['path_to_root'].'included/jscalendar/*.js'
	);

	// process all js files
	$count = 0;
	foreach($patterns as $pattern) {
		foreach(Safe::glob($pattern) as $name) {

			$context['text'] .= $name.BR."\n";

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

	// also delete cached javascript files
	Cache::purge('js');

	// report to surfer
	if($count)
		$context['text'] .= sprintf(i18n::s('%d files have been updated.'), $count)."\n";
	$context['text'] .= "</p>\n";

	// display the execution time
	$time = round(get_micro_time() - $context['start_time'], 2);
	$context['text'] .= '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

	// forward to the index page
	$menu = array('control/' => i18n::s('Control Panel'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

	// remember the operation
	$label = i18n::c('jsmin has been applied to Javascript files');
	Logger::remember('control/jsmin.php', $label);

// confirmation is required
} else {

	// the confirmation question
	$context['text'] .= '<b>'.sprintf(i18n::s('You are about to apply jsmin to Javascript files of this server. Are you sure?'), $context['file_mask'])."</b>\n";

	// the menu for this page
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'"><p>'
		.Skin::build_submit_button(i18n::s('Yes, I do want to compress Javascript files'))
		.'<input type="hidden" name="action" value="confirm" />'
		.'</p></form>'."\n";

	// this may take several minutes
	$context['text'] .= '<p>'.i18n::s('When you will click on the button the server will be immediately requested to proceed. However, because of the so many things to do on the back-end, you may have to wait for minutes before getting a response displayed. Thank you for your patience.')."</p>\n";

}

// render the skin
render_skin();

?>