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
	echo '<h1>'.i18n::s('the Hello World page').'</h1>';

	// display page content
	echo '<p>'.i18n::s('Hello Word, I am happy to be there!').'</p>';

}

// let YACS end the page
embed_yacs_suffix();

?>