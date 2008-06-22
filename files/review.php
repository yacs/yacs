<?php
/**
 * the review page for files
 *
 * This page lists files that should be reviewed, that is:
 *
 * - oldest files - it is likely that their validity should be controlled
 *
 * - biggest files - it is likely that they are cluttering some disk drive
 *
 * Anonymous surfers are asked to login to access this page.
 * This feature will also avoid the indexing of outdated material by web crawlers.
 *
 * Of course, more or less files are displayed, depending on surfer status.
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'files.php';

// load the skin
load_skin('files');

// the path to this page
$context['path_bar'] = array( 'files/' => i18n::s('Files') );

// the title of the page
$context['page_title'] = i18n::s('Files to review');

// the menu bar for this page
$context['page_menu'] = array( 'files/' => i18n::s('Files') );

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('files/review.php'));

// logged users
else {

	// oldest files
	if($rows = Files::list_by_oldest_date(0, 10, 'full')) {
		$context['text'] .= Skin::build_block(i18n::s('Oldest files'), 'title');
		if(is_array($rows))
			$context['text'] .= Skin::build_list($rows, 'decorated');
		else
			$context['text'] .= $rows;
	}

	// biggest files
	if(Surfer::is_associate() && ($rows = Files::list_by_size(0, 25, 'full'))) {
		$context['text'] .= Skin::build_block(i18n::s('Biggest files'), 'title');
		if(is_array($rows))
			$context['text'] .= Skin::build_list($rows, 'decorated');
		else
			$context['text'] .= $rows;
	}

	// list files with very few hits
	if(Surfer::is_associate() && ($rows = Files::list_unused(0, 25))) {
		$context['text'] .= Skin::build_block(i18n::s('Less downloaded files'), 'title');
		if(is_array($rows))
			$context['text'] .= Skin::build_list($rows, 'decorated');
		else
			$context['text'] .= $rows;
	}

	// size of noise words
	if(Surfer::is_associate() && is_readable($context['path_to_root'].'files/noise_words.php')) {
		include_once $context['path_to_root'].'files/noise_words.php';
		if(@count($noise_words)) {
			$context['text'] .= '<p>'.sprintf(i18n::s('%d items in the list of noise words'), count($noise_words)).'</p>';
		}
	}
}

// render the skin
render_skin();

?>