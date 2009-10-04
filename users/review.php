<?php
/**
 * the review page for user profiles
 *
 * This page lists all user profiles that need some review of some sort.
 * This includes:
 *
 * - new subscribers and members that have entered the community recently
 *
 * - inactive users (those who have posted a long time ago)
 *
 * - inactive users (those who have logged in for a long time)
 *
 * Everybody can view this page.
 * Therefore this index page is a straightforward mean for a applicant surfer to ensure that his/her registration has been
 * taken into account by the system.
 *
 * You can check this single page quite regularly to track new user profiles and inactive members.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// load the skin
load_skin('users');

// the path to this page
$context['path_bar'] = array( 'users/' => i18n::s('People') );

// the title of the page
$context['page_title'] = i18n::s('User profiles to be reviewed');

// list newest profiles
if($rows = Users::list_by_date(0, 10, 'full')) {
	$context['text'] .= Skin::build_block(i18n::s('Most recent members'), 'title');
	if(is_array($rows))
		$context['text'] .= Skin::build_list($rows, 'decorated');
	else
		$context['text'] .= $rows;
}

// oldest posts, but only to associates
if(Surfer::is_associate() && ($rows = Users::list_by_post_date())) {
	$context['text'] .= Skin::build_block(i18n::s('Oldest posts'), 'title');
	$context['text'] .= '<p>'.i18n::s('Users who have never contributed are not listed at all.').'</p>'."\n";
	if(is_array($rows))
		$context['text'] .= Skin::build_list($rows, 'decorated');
	else
		$context['text'] .= $rows;
}

// oldest logins, but only to associates
if(Surfer::is_associate() && ($rows = Users::list_by_login_date())) {
	$context['text'] .= Skin::build_block(i18n::s('Oldest logins'), 'title');
	$context['text'] .= '<p>'.i18n::s('Users who have never been authenticated are not listed at all.').'</p>'."\n";
	if(is_array($rows))
		$context['text'] .= Skin::build_list($rows, 'decorated');
	else
		$context['text'] .= $rows;
}

// the menu bar for this page
$context['page_tools'][] = Skin::build_link('users/', i18n::s('People'));

// render the skin
render_skin();

?>