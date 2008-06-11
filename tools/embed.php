<?php
/**
 * demonstrate YACS capability to be embedded
 *
 * A minimum script based on the YACS framework.
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// load localized strings -- see i18n/i18n.php for more information on internationalization and localization in YACS
i18n::bind('tools');

// let YACS start the page
embed_yacs_prefix();

// no content on HEAD request
if(!isset($_SERVER['REQUEST_METHOD']) || ($_SERVER['REQUEST_METHOD'] != 'HEAD')) {

	// display page title
	echo '<h1>'.i18n::s('Hello world').'</h1>';

	// display page content
	echo '<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'
		.' Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.'
		.' Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.'
		.' Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>';

}

// let YACS end the page
embed_yacs_suffix();

?>