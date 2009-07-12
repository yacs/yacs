<?php
/**
 * display one server profile
 *
 * The extra panel has following elements:
 * - The top popular referrals, if any
 *
 * Restrictions apply on this page:
 * - associates are allowed to move forward
 * - permission is denied if the anchor is not viewable
 * - access is restricted ('active' field == 'R'), but the surfer is an authenticated member
 * - public access is allowed ('active' field == 'Y')
 * - permission denied is the default
 *
 * Accepted calls:
 * - view.php/12
 * - view.php?id=12
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
if($item['title'])
	$context['page_title'] = $item['title'];

// test links
if($item['submit_feed'] == 'Y')
	$context['page_menu'] += array( Servers::get_url($id, 'test') => i18n::s('Test feed') );

// commands for associates
if(Surfer::is_associate()) {
	$context['page_menu'] += array( Servers::get_url($id, 'edit') => i18n::s('Edit') );
	$context['page_menu'] += array( Servers::get_url($id, 'delete') => i18n::s('Delete') );
}

// not found
if(!$item['id']) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No item has the provided id.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Servers::get_url($item['id'])));

	// permission denied to authenticated user
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// display the server profile
} else {

	// initialize the rendering engine
	Codes::initialize(Servers::get_url($item['id']));

	// use the cache if possible
	$cache_id = 'servers/view.php?id='.$item['id'].'#content';
	if(!$text =& Cache::get($cache_id)) {

		// the nick name
		if($item['host_name'] && Surfer::is_associate())
			$details[] = '"'.$item['host_name'].'"';

		// information on last update
		if($item['edit_name'])
			$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));

		// restricted to logged members
		if($item['active'] == 'R')
			$details[] = RESTRICTED_FLAG.' '.i18n::s('Access is restricted to authenticated members').BR."\n";

		// restricted to associates
		elseif($item['active'] == 'N')
			$details[] = PRIVATE_FLAG.' '.i18n::s('Access is restricted to associates').BR."\n";

		// all details
		if(@count($details))
			$text .= '<p class="details">'.ucfirst(implode(', ', $details))."</p>\n";

		// insert anchor prefix
		if(is_object($anchor))
			$text .= $anchor->get_prefix();

		// main url
		if($item['main_url'])
			$text .= '<p>'.sprintf(i18n::s('Main URL: %s'), Skin::build_link($item['main_url'], NULL, 'external'))."</p>\n";

		// a section for remote services
		$text .= Skin::build_block(i18n::s('Services accessed remotely'), 'subtitle');

		// feed submission
		if($item['submit_feed'] == 'Y') {
			$menu = array(Servers::get_url($item['id'], 'test') => i18n::s('Test feed') );
			$label = sprintf(i18n::s('News published at this server at %s - %s are fetched periodically'), Skin::build_link($item['feed_url'], NULL, 'external'), Skin::build_list($menu, 'menu'));
			if(is_object($anchor))
				$label .= BR.sprintf(i18n::s('and aggregated locally at %s'), Skin::build_link($anchor->get_url(), $anchor->get_title(), 'section'));
		} else
			$label = i18n::s('Do not check news from this server.');
		$text .= '<p>'.$label."</p>\n";

		// ping submission
		if($item['submit_ping'] == 'Y')
			$label = sprintf(i18n::s('This server has to be pinged on updates, by using XML-RPC calls <code>weblogUpdates.ping</code> at %s'), Skin::build_link($item['ping_url']));
		else
			$label = i18n::s('Updates are not transmitted to this server.');
		$text .= '<p>'.$label."</p>\n";

		// monitoring
		if($item['submit_monitor'] == 'Y')
			$label = sprintf(i18n::s('This server has to be polled, by using XML-RPC calls <code>monitor.ping</code> at %s'), Skin::build_link($item['monitor_url']));
		else
			$label = i18n::s('Do not poll this server to check its state.');
		$text .= '<p>'.$label."</p>\n";

		// search submission
		if($item['submit_search'] == 'Y')
			$label = sprintf(i18n::s('This server has to be included into searches, by using REST calls at %s'), Skin::build_link($item['search_url']));
		else
			$label = i18n::s('Do not submit search requests to this server.');
		$text .= '<p>'.$label."</p>\n";

		// a section for remote requests
		$text .= Skin::build_block(i18n::s('Allowed queries from this server'), 'subtitle');

		// ping processing
		if($item['process_ping'] == 'Y')
			$label = sprintf(i18n::s('This server is allowed to advertise changes (<code>weblogUpdates.ping</code>) at %'), Skin::build_link('services/index.php#ping', i18n::s('the ping interface'), 'shortcut'));
		else
			$label = i18n::s('This server is not allowed to advertise changes.');
		$text .= '<p>'.$label."</p>\n";

		// search processing
		if($item['process_search'] == 'Y')
			$label = sprintf(i18n::s('This server is allowed to submit search requests at %s'), Skin::build_link('services/index.php#search', i18n::s('the search interface'), 'shortcut'));
		else
			$label = i18n::s('This server is not allowed to submit search requests.');
		$text .= '<p>'.$label."</p>\n";

		// monitoring
		if($item['process_monitor'] == 'Y')
			$label = sprintf(i18n::s('This server is allowed to submit monitoring requests (<code>monitor.ping</code>) at %s'), Skin::build_link('services/index.php#xml-rpc', i18n::s('the XML-RPC interface'), 'shortcut'));
		else
			$label = i18n::s('This server is not allowed to submit monitoring requests.');
		$text .= '<p>'.$label."</p>\n";

		// a section for the description
		if($item['description']) {

			// display the full text
			$text .= Skin::build_block(i18n::s('Server description'), 'subtitle');

			// show the description
			$text .= Skin::build_block($item['description'], 'description');
		}

		// save in cache
		Cache::put($cache_id, $text, 'server:'.$item['id']);
	}
	$context['text'] .= $text;

	// insert anchor suffix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_suffix();

	// referrals, if any
	$context['aside']['referrals'] =& Skin::build_referrals(Servers::get_url($item['id']));
}

// render the skin
render_skin();

?>