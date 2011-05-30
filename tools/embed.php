<?php
/**
 * demonstrate YACS capability to be embedded
 *
 * A minimum script based on the YACS framework.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// load localized strings -- see i18n/i18n.php for more information on internationalization and localization in YACS
i18n::bind('tools');

// load the skin
load_skin('tools');

// path to this page
$context['path_bar'] = array( 'tools/' => i18n::s('Tools') );

// page title
$context['page_title'] = i18n::s('Hello world');

// render the page
render_skin();

// render_skin() calls send_body(), which can execute any code and generate any output
function send_body() {

	// load another script that will generate some text
	include 'echo.php';

}

?>
