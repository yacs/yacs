<?php
/**
 * handling members of this community
 *
 * @todo create buddy lists (CmputrAce)
 *
 * For a comprehensive description of user profiles, you should check the database abstraction script
 * at [script]users/users.php[/script].
 *
 * This page list users of this server, ranked by decreasing number of contributions and by
 * decreasing edition date. Therefore, it is likely
 * that the persons you are interested in are at located near the top of the list.
 *
 * Following restrictions apply:
 * - anonymous users can see only active user profiles (the 'active' field == 'Y')
 * - members can see active and restricted user profiles ('active field == 'Y' or 'R')
 * - associates can see all user profiles
 *
 * The main menu displays the total number of users.
 * It also has navigation links to page among profiles.
 * Commands are available to associates to either create a new user profile or to review noticeable user profiles.
 *
 * Contact shortcuts are included as well to popular systems such as Twitter, GMail, Skype, Yahoo, MSN, etc...
 *
 * A list of most new users is displayed as a sidebox. Also, a shortcut to the search form has been added.
 *
 * A list of surfers who are present on the site is also displayed, as a side box.
 *
 *
 * This script secretely features a link to the main RSS feeder for this site, namely:
 * [code]&lt;link rel="alternate" href="http://.../feeds/rss.php" title="RSS" type="application/rss+xml" /&gt;[/code]
 *
 * The prefix hook is used to invoke any software extension bound as follows:
 * - id: 'users/index.php#prefix'
 * - type: 'include'
 * - parameters: none
 * Use this hook to include any text right before the main content.
 *
 * The suffix hook is used to invoke any software extension bound as follows:
 * - id: 'users/index.php#suffix'
 * - type: 'include'
 * - parameters: none
 * Use this hook to include any text right after the main content.
 *
 * Accept following invocations:
 * - index.php (view the 20 most contributing users)
 * - index.php/10 (view users 200 to 220, ranked by count of contributions)
 * - index.php?page=4 (view users 80 to 100, ranked by count of contributions)
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Fernand Le Chien
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../locations/locations.php';

// which page should be displayed
if(isset($_REQUEST['page']))
	$page = $_REQUEST['page'];
elseif(isset($context['arguments'][0]))
	$page = $context['arguments'][0];
else
	$page = 1;
$page = max(1,intval($page));

// load the skin
load_skin('users');

// the maximum number of users per page
if(!defined('USERS_PER_PAGE'))
	define('USERS_PER_PAGE', 50);

// the title of the page
$context['page_title'] = i18n::s('People');

// count users in the database
$stats = Users::stat();
if($stats['count'])
	$context['page_menu'] += array('_count' =>sprintf(i18n::ns('%d user', '%d users', $stats['count']), $stats['count']));

// the prefix hook for the index of members
if(is_callable(array('Hooks', 'include_scripts')))
	$context['text'] .= Hooks::include_scripts('users/index.php#prefix');

// stop hackers
if(($page > 1) && (($page - 1) * USERS_PER_PAGE > $stats['count'])) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

} else {

	// navigation commands for users, if necessary
	if($stats['count'] > USERS_PER_PAGE) {
		$home = 'users/';
		if($context['with_friendly_urls'] == 'Y')
			$prefix = $home.'index.php/';
		elseif($context['with_friendly_urls'] == 'R')
			$prefix = $home;
		else
			$prefix = $home.'?page=';
		$context['page_menu'] += Skin::navigate($home, $prefix, $stats['count'], USERS_PER_PAGE, $page);
	}

	// a search form for users
	$context['text'] .= '<form action="'.$context['url_to_root'].'users/search.php" method="get">'
		.'<p>'
		.'<input type="text" name="search" id="search" size="40" value="'.encode_field(i18n::s('Look for some user')).'" onfocus="this.value=\'\'" maxlength="128" />'
		.Skin::build_submit_button(i18n::s('Go'))
		.'</p>'
		."</form>\n";

	// set the focus on the button
	$context['text'] .= JS_PREFIX
		.'$("search").focus();'."\n"
		.JS_SUFFIX;



// // map users on Google Maps
// if($stats['count'] && isset($context['google_api_key']) && $context['google_api_key'])
// 	$context['text'] .= '<p>'.Skin::build_link(Locations::get_url('users', 'map_on_google'), i18n::s('Map users at Google Maps')).'</p>';

	// look up the database to find the list of users
	$cache_id = 'users/index.php#text#'.$page;
	if(!$text =& Cache::get($cache_id)) {

		// query the database and layout that stuff
		$offset = ($page - 1) * USERS_PER_PAGE;
		if(!$text = Users::list_by_posts($offset, USERS_PER_PAGE, 'full'))
			$text = '<p>'.i18n::s('No item has been found.').'</p>';

		// we have an array to format
		if(is_array($text))
			$text =& Skin::build_list($text, 'decorated');

		// cache this to speed subsequent queries
		Cache::put($cache_id, $text, 'users');
	}
	$context['text'] .= $text;

}

// the suffix hook for the index of members
if(is_callable(array('Hooks', 'include_scripts')))
	$context['text'] .= Hooks::include_scripts('users/index.php#suffix');

// page tools
if(Surfer::is_associate())
	$context['page_tools'][] = Skin::build_link('users/edit.php', i18n::s('Add a user'));
elseif(Surfer::get_id())
	$context['page_tools'][] = Skin::build_link('users/view.php', i18n::s('My profile'));
else
	$context['page_tools'][] = Skin::build_link('users/edit.php', i18n::s('Register'));
if(isset($context['google_api_key']) && $context['google_api_key'])
	$context['page_tools'][] = Skin::build_link(Locations::get_url('users', 'map_on_google'), i18n::s('Google map'));
$context['page_tools'][] = Skin::build_link('users/review.php', i18n::s('Review profiles'));

// side bar with the list of present users --don't cache, this will change on each request anyway
$context['components']['boxes'] = '';
include_once $context['path_to_root'].'users/visits.php';
if($items = Users::list_present(0, COMPACT_LIST_SIZE, 'compact'))
	$context['components']['boxes'] = Skin::build_box(i18n::s('Present users'), $items, 'extra');


// page extra content
$cache_id = 'users/index.php#extra';
if(!$text =& Cache::get($cache_id)) {

	// side bar with the list of newest users
	if($items = Users::list_by_date(0, COMPACT_LIST_SIZE, 'compact'))
		$text .= Skin::build_box(i18n::s('Newest Members'), Skin::build_list($items, 'compact'), 'extra');

	// side boxes for related categories, if any
	include_once '../categories/categories.php';
	if($categories = Categories::list_by_date_for_display('user:index', 0, 7, 'raw')) {
		foreach($categories as $id => $attributes) {

			// link to the category page from the box title
			$label =& Skin::build_box_title(Skin::strip($attributes['title']), Categories::get_permalink($attributes), i18n::s('View the category'));

			// box content
			if($items =& Members::list_articles_by_date_for_anchor('category:'.$id, 0, COMPACT_LIST_SIZE, 'compact'))
				$text .= Skin::build_box($label, Skin::build_list($items, 'compact'), 'extra')."\n";
		}
	}

	// cache, whatever change, for 5 minutes
	Cache::put($cache_id, $text, 'stable', 300);
}
$context['components']['boxes'] .= $text;

// referrals, if any
$context['components']['referrals'] = Skin::build_referrals('users/index.php');

// a meta link to a feeding page
include_once '../feeds/feeds.php';
$context['page_header'] .= "\n".'<link rel="alternate" href="'.$context['url_to_root'].Feeds::get_url('rss').'" title="RSS" type="application/rss+xml" />';

// render the skin
render_skin();

?>