<?php
/**
 * the index page for letters
 *
 * @author Bernard Paques
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
$context['page_title'] = i18n::s('Newsletters');

// seek the database
$anchor = Sections::lookup('letters');
if($anchor && ($rows =& Articles::list_for_anchor_by('publication', $anchor, 0, 20)))
	$context['text'] .= Skin::build_list($rows, 'decorated');

// empty list
else
	$context['text'] .= '<p>'.i18n::s('No letter has been sent yet.').'</p>';

// page tools
//
if(Surfer::is_associate()) {
	$context['page_tools'][] = Skin::build_link('letters/new.php', i18n::s('Post a letter'), 'basic');
	$context['page_tools'][] = Skin::build_link('letters/configure.php', i18n::s('Configure'), 'basic');
}

// referrals, if any
$context['components']['referrals'] = Skin::build_referrals('letters/index.php');

// render the skin
render_skin();

?>