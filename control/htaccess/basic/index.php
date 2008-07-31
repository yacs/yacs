<?php
/**
 * check apache options
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../../../shared/global.php';

// load localized strings
i18n::bind('control');

// load the skin
load_skin('control');

// page title
$context['page_title'] = sprintf(i18n::s('%s: %s'), i18n::s('Configure'), i18n::s('Apache .htaccess'));

if(is_callable('apache_get_modules')) {
	if(in_array('mod_deflate', apache_get_modules()))
		$context['text'] .= '<p>'.i18n::s('Compression of dynamic pages is available.').'</p>';
	else
		$context['text'] .= '<p>'.sprintf(i18n::s('Activate the following Apache module to allow dynamic compression: %s'), 'mod_deflate').'</p>';
}

// follow-up commands
$follow_up = Skin::build_link('control/htaccess/', i18n::s('Done'), 'button');
$context['text'] .= Skin::build_block($follow_up, 'bottom');

// remember capability in session context
if(!isset($_SESSION['htaccess']))
	$_SESSION['htaccess'] = array();
$_SESSION['htaccess']['basic'] = TRUE;

// render the page according to the loaded skin
render_skin();

?>