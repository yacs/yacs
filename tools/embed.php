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

// most skin templates call send_meta() to echo customized tags in the <head> tag
function send_meta() {
	global $context;

	// you can generate something directly, or include any other script able to echo meta tags
	echo "\t".'<meta name="embedded" content="by tools/embed.php" />'."\n";

}

// most skin templates call send_body(), which can execute any code and generate any output
function send_body() {
	global $context;

	// load another script that will generate some text
	include 'echo.php';

}

?>
