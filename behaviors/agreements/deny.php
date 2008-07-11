<?php
/**
 * explicit agreement deny
 *
 * This script is called from within behaviors that ask for formal agreement by end users.
 *
 * It only displays a clear message to deny access to target resource
 *
 * Accept following invocations:
 * - deny.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../../shared/global.php';

// load localized strings
i18n::bind('behaviors');

// load the skin
load_skin('agreements');

// the title of the page
$context['page_title'] = i18n::s('Access has been denied');

// splash message
$context['text'] .= '<p>'.i18n::s('Your agreement is required to access the target page. Sorry for any inconvenience.').'</p>';

// common commands for this page
if(isset($_SERVER['HTTP_REFERER']))
	$context['page_menu'] = array( $_SERVER['HTTP_REFERER'] => i18n::s('Back to main page') );

// render the skin
render_skin();

?>