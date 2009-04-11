<?php
/**
 * redirect permanently to library.js
 *
 * @author Bernard Paques
 * @obsolete
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// no decision, no extension
if(!defined('NO_CONTROLLER_PRELOAD'))
	define('NO_CONTROLLER_PRELOAD', TRUE);

// no need for transforming data
if(!defined('NO_VIEW_PRELOAD'))
	define('NO_VIEW_PRELOAD', TRUE);

// no need for access to the database
if(!defined('NO_MODEL_PRELOAD'))
	define('NO_MODEL_PRELOAD', TRUE);

// common definitions and initial processing
include_once '../../shared/global.php';

// actual transmission except on a HEAD request
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD')) {

	// use the static file
	header('Status: 301 Moved Permanently', false, 301);
	header('Location: '.$context['url_to_home'].$context['url_to_root'].'included/browser/library.js');

}
?>