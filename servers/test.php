<?php
/**
 * test links attached to a server profile
 *
 * This script actually parses links attached to one server record.
 *
 * It will be of great help to troubleshoot and fix difficult situations.
 *
 * Restrictions apply on this page:
 * - associates are allowed to move forward
 * - permission is denied if the anchor is not viewable
 * - access is restricted ('active' field == 'R'), but the surfer is an authenticated member
 * - public access is allowed ('active' field == 'Y')
 * - permission denied is the default
 *
 * Accepted calls:
 * - test.php/12
 * - test.php?id=12
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'servers.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Servers::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor =& Anchors::get($item['anchor']);

// associates can do what they want
if(Surfer::is_associate())
	$permitted = TRUE;

// the anchor has to be viewable by this surfer
elseif(is_object($anchor) && !$anchor->is_viewable())
	$permitted = FALSE;

// access is restricted to authenticated member
elseif(($item['active'] == 'R') && Surfer::is_member())
	$permitted = TRUE;

// public access is allowed
elseif($item['active'] == 'Y')
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// load the skin
load_skin('servers');

// the path to this page
$context['path_bar'] = array( 'servers/' => i18n::s('Servers') );

// the title of the page
$context['page_title'] = i18n::s('Feed test');

// back to the server record
if($item['id'])
	$context['page_menu'] += array( Servers::get_url($item['id']) => i18n::s('Back to server profile') );

// commands for associates
if(Surfer::is_associate()) {
	$context['page_menu'] += array( Servers::get_url($id, 'edit') => i18n::s('Edit') );
	$context['page_menu'] += array( Servers::get_url($id, 'delete') => i18n::s('Delete') );
}

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// not found
} elseif(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No item has the provided id.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Servers::get_url($item['id'], 'test')));

	// permission denied to authenticated user
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// display the server profile
} else {

	// the nick name
	if($item['host_name'] && Surfer::is_associate())
		$details[] = '"'.$item['host_name'].'"';

	// information on last update
	if($item['edit_name'])
		$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));

	// restricted to logged members
	if($item['active'] == 'R')
		$details[] = RESTRICTED_FLAG.' '.i18n::s('Community - Access is restricted to authenticated members').BR."\n";

	// restricted to associates
	elseif($item['active'] == 'N')
		$details[] = PRIVATE_FLAG.' '.i18n::s('Private - Access is restricted to selected persons').BR."\n";

	// all details
	$context['text'] .= '<p class="details">'.ucfirst(implode(', ', $details))."</p>\n";

	// insert anchor prefix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_prefix();

	// main url
	if($item['main_url'])
		$context['text'] .= '<p>'.sprintf(i18n::s('Main URL: %s'), Skin::build_link($item['main_url'], NULL, 'external'))."</p>\n";

	// no feed url
	if(!$item['feed_url'])
		Logger::error(i18n::s('No feed url has been configured for this server profile.'));

	// test the provided url
	else {

		// display the feed URL
		$context['text'] .= '<p>'.sprintf(i18n::s('Feed URL: %s'), Skin::build_link($item['feed_url'], NULL, 'external'))."</p>\n";

		// fetch news from the provided link
		include_once $context['path_to_root'].'feeds/feeds.php';
		if((!$news = Feeds::get_remote_news_from($item['feed_url'])) || !is_array($news))
			$context['text'] .= '<p>'.i18n::s('Nothing to read from this feed.')."</p>\n";

		// list news
		else {

			// statistics
			$context['text'] .= '<p>'.sprintf(i18n::s('%d elements have been read'), count($news))."</p>\n";

			// list banned hosts
			include_once $context['path_to_root'].'servers/servers.php';
			$banned_pattern = Servers::get_banned_pattern();

			// where links should be anchored
			$reference = '';
			if(is_object($anchor))
				$reference = $anchor->get_reference();

			// process retrieved links
			include_once $context['path_to_root'].'links/links.php';
			$context['text'] .= '<ul>'."\n";
			foreach($news as $item) {

				// debug
				if(isset($context['debug_feeds']) && ($context['debug_feeds'] == 'Y'))
					Logger::remember('servers/test.php', 'item', $item, 'debug');

				// link has to be valid
				if(!$item['link'] || !($item['title'].$item['description'])) {
					$context['text'] .= '<li>'.i18n::s('Empty item!')."</li>\n";
					continue;
				}

				// nice display
				$context['text'] .= '<li>'.Skin::build_link($item['link'], $item['title'], 'external');
				if($item['description'])
					$context['text'] .= ' - '.$item['description'];
				if($item['category'])
					$context['text'] .= ' ('.$item['category'].')';
				if($item['pubDate'])
					$context['text'] .= ' '.gmstrftime('%Y-%m-%d %H:%M:%S', strtotime($item['pubDate']));

				// flag links
				if($banned_pattern && preg_match($banned_pattern, $item['link']))
					$context['text'] .= BR.'('.i18n::s('the target server is banned').')';

				elseif(Links::have($item['link'], $reference))
					$context['text'] .= BR.'('.i18n::s('this link exists in the database').')';

				else
					$context['text'] .= BR.'('.i18n::s('this link has not been inserted in the database yet').')';

				$context['text'] .= '</li>'."\n";

			}
			$context['text'] .= '</ul>'."\n";

		}
	}

	// the related anchor
	if(is_object($anchor))
		$context['text'] .= '<p>'.sprintf(i18n::s('Related to %s'), Skin::build_link($anchor->get_url(), $anchor->get_title(), 'category'))."</p>\n";

	// insert anchor suffix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_suffix();

}

// render the skin
render_skin();

?>