<?php
/**
 * build style sheets
 *
 * This script compiles and minifies the style sheets used by the active skin:
 * - the main sheet of the skin (knacss, fontawesome, yacss, tune.scss and <skin>.scss)
 * - scss and css files of layouts and overlays
 * - other scss and css files of the skin
 *
 * Built files are placed in the directory temporary. A style sheet is built
 * only if its sources are newer, unless a full rebuild is asked for.
 *
 * In production mode (with_debug = 'N') style sheets are not rebuilt while
 * pages are served: run this script after a change.
 *
 * @see shared/js_css.php
 *
 * To run this script, the surfer has to be an associate, or no switch file exists.
 *
 * @author Christian Loubechine
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../shared/update_steps.php';	// not left to the autoloader, global.php may be stale during an update

// load localized strings
i18n::bind('tools');

// load the skin
load_skin('tools');

// do not index this page
$context->sif('robots','noindex');

// the path to this page
$context['path_bar'] = array( 'tools/' => i18n::s('Tools') );

// the title of the page
$context['page_title'] = i18n::s('Build style sheets');

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('tools/build_css.php'));

// only associates can proceed, except on first installation
elseif(!Surfer::is_associate() && !is_installing()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

	// forward to the index page
	$menu = array('control/' => i18n::s('Control Panel'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST') && isset($_REQUEST['action']) && ($_REQUEST['action'] == 'confirm')) {

	// rebuild everything, or only what has changed
	$force = isset($_REQUEST['force']) && ($_REQUEST['force'] == 'Y');

	$built = $errors = 0;
	$lines = array();
	foreach(Js_css::list_styles() as $source => $output) {

		// a label for the surfer
		if($source == 'skin')
			$label = $context['skin'].'/ ('.i18n::s('main style sheet').')';
		else
			$label = substr($source, strlen($context['path_to_root']));

		// nothing has changed
		if(!$force && (Safe::filemtime($output) >= Js_css::source_stamp($source))) {
			$lines[] = $label.' &mdash; '.i18n::s('up to date');
			continue;
		}

		// do the job
		$result = Js_css::build_style($source, $output);
		if($result === true) {
			$lines[] = $label.' &mdash; '.i18n::s('built');
			$built++;
		} else {
			$lines[] = $label.' &mdash; <strong>'.i18n::s('error').'</strong> : '.htmlspecialchars($result);
			$errors++;
		}
	}

	// report to surfer
	$context['text'] .= '<p>'.implode(BR."\n", $lines)."</p>\n";
	$context['text'] .= '<p>'.sprintf(i18n::s('%d style sheets have been built.'), $built)."</p>\n";
	if($errors)
		Logger::error(sprintf(i18n::s('%d style sheets could not be built.'), $errors));

	// display the execution time
	$time = round(get_micro_time() - $context['start_time'], 2);
	$context['text'] .= '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

	// in the middle of an update, go on with javascript libraries
	$context['text'] .= Update_steps::next('build_css');

// confirmation is required
} else {

	// the confirmation question
	$context['text'] .= '<p><b>'.i18n::s('You are about to compile and minify the style sheets of the active skin, of layouts and of overlays. Are you sure?')."</b></p>\n";

	// the menu for this page
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'"><p>'
		.'<input type="checkbox" name="force" id="force" value="Y" /> <label for="force">'.i18n::s('Rebuild all style sheets, even those that are up to date').'</label>'.BR
		.Skin::build_submit_button(i18n::s('Yes, I do want to build style sheets'))
		.'<input type="hidden" name="action" value="confirm" />'
		.'</p></form>'."\n";

	// list style sheets that have changed
	if(Js_css::check_production_styles())
		$context['text'] .= '<p>'.i18n::s('Some style sheets have been changed since they were built.').'</p>';

}

// render the skin
render_skin();
