<?php
/**
 * sample integration of iGalerie into yacs.
 *
 * This script assumes that iGalerie has been installed in a separate directory
 * of the yacs top-level directory, with the name igalerie.
 *
 * @http http://www.igalerie.org/documentation/integration
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// declaration files of iGalerie
require_once $context['path_to_root'].'igalerie/index.inc';

// load localized strings -- see i18n/i18n.php for more information on internationalization and localization in YACS
i18n::bind('tools');

// load the skin
load_skin('tools');

// do not index this page
$context->sif('robots','noindex');

// page title
$context['page_title'] = $tpl->getGallery('page_title');

// render the page
render_skin();

// most skin templates call send_meta() to echo customized tags in the <head> tag
function send_meta() {
	global $context;

	// load the script of iGalerie that generate meta tags
	require_once $context['path_to_root'].'igalerie/template/'.$tpl->getGallery('template_name').'/head.tpl.php';

}

// most skin templates call send_body(), which can execute any code and generate any output
function send_body() {
	global $context;

	// load the actual iGalerie script
	require_once $context['path_to_root'].'igalerie/template/'.$tpl->getGallery('template_name').'/index.tpl.php';

}

?>