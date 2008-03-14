<?php
/**
 * hello world
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

// load the skin -- parameter enables to load an alternate template, if any -- see function definition in shared/global.php
load_skin('hello');

// populate page attributes -- attributes used by YACS are described in skins/test.php
$context['page_title'] = i18n::s('the Hello World page');
$context['text'] .= '<p>'.i18n::s('Hello Word, I am happy to be there!').'</p>';

// render the page according to the loaded skin
render_skin();

?>