<?php
/**
 * the index page for letters
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// load localized strings
i18n::bind('letters');

// load the skin
load_skin('letters');

// the title of the page
$context['page_title'] = i18n::s('Most recent letters');

// menu bar
if(Surfer::is_associate()) {
	$context['page_menu'] = array( 'letters/new.php' => i18n::s('Post a letter') );
	$context['page_menu'] = array_merge($context['page_menu'], array( 'letters/configure.php' => i18n::s('Configure') ));
}

// seek the database
$anchor = Sections::lookup('letters');
if($anchor && ($rows = Articles::list_by_date_for_anchor($anchor, 0, 20)))
	$context['text'] .= Skin::build_list($rows, 'decorated');

// empty list
else
	$context['text'] .= '<p>'.i18n::s('No letter has been sent yet.').'</p>';

// referrals, if any
$context['extra'] .= Skin::build_referrals('letters/index.php');

// render the skin
render_skin();

?>