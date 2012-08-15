<?php
/**
 * the index page for servers
 *
 * For a comprehensive description of servers, you should check the database abstraction script
 * at [script]servers/servers.php[/script].
 *
 * This page list servers part of the &quot;cloud&quot; tracked from this server.
 *
 * You will create a server profile each time you will need to link your YACS server to another web site.
 *
 * [*] inbound news feeding - create one profile per news source.
 *
 * You can try following links to validate your installation:
 * - [link]http://www.xmlhack.com/rss.php[/link] (rss)
 * - [link]http://www.circle.ch/RSS/[/link] (rss)
 * - [link]http://slashdot.org/slashdot.xml[/link] (slashdot)
 *
 *
 * The main menu has navigation links to browse servers by page, for sites that are linked to numerous servers.
 * Commands are available to either create a new server entry, to ping the cloud, or to populate the database with default entries.
 *
 * Entries are listed using the default decorated layout.
 *
 * Accept following calls:
 * - index.php (view the 20 top servers)
 * - index.php/2 (view servers 41 to 60)
 * - index.php?page=2 (view servers 41 to 60)
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Pat
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'servers.php';

// which page should be displayed
if(isset($_REQUEST['page']))
	$page = $_REQUEST['page'];
elseif(isset($context['arguments'][0]))
	$page = $context['arguments'][0];
else
	$page = 1;
$page = max(1,intval($page));

// load the skin
load_skin('servers');

// the maximum number of servers per page
if(!defined('SERVERS_PER_PAGE'))
	define('SERVERS_PER_PAGE', 50);

// the title of the page
$context['page_title'] = i18n::s('Servers');

// count servers in the database
$stats = Servers::stat();
if($stats['count'])
	$context['page_menu'] += array('_count' => sprintf(i18n::ns('%d server', '%d servers', $stats['count']), $stats['count']));

// stop hackers
if(($page > 1) && (($page - 1) * SERVERS_PER_PAGE > $stats['count'])) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

} else {

	// navigation commands for servers, if necessary
	if($stats['count'] > SERVERS_PER_PAGE) {
		$home = 'servers/';
		if($context['with_friendly_urls'] == 'Y')
			$prefix = $home.'index.php/';
		elseif($context['with_friendly_urls'] == 'R')
			$prefix = $home;
		else
			$prefix = $home.'?page=';
		$context['page_menu'] += Skin::navigate($home, $prefix, $stats['count'], SERVERS_PER_PAGE, $page);
	}
	
	// seek the database
	$cache_id = 'servers/index.php#text#'.$page;
	if(!$text = Cache::get($cache_id)) {
	
		// query the database and layout that stuff
		$offset = ($page - 1) * SERVERS_PER_PAGE;
		if(!$text = Servers::list_by_date($offset, SERVERS_PER_PAGE, 'full'))
			$text = '<p>'.i18n::s('No server has been created yet.').'</p>';
	
		// we have an array to format
		if(is_array($text))
			$text =& Skin::build_list($text, 'decorated');
	
		// cache this to speed subsequent queries
		Cache::put($cache_id, $text, 'servers');
	}
	$context['text'] .= $text;

}

// page tools
if(Surfer::is_associate()) {
	$context['page_tools'][] = Skin::build_link('servers/edit.php', i18n::s('Add a server'));
	$context['page_tools'][] = Skin::build_link('servers/ping.php', i18n::s('Ping the cloud'));
	$context['page_tools'][] = Skin::build_link('servers/configure.php', i18n::s('Configure'));
	$context['page_tools'][] = Skin::build_link('servers/populate.php', i18n::s('Populate'));
}

// referrals, if any
$context['components']['referrals'] = Skin::build_referrals('servers/index.php');

// render the skin
render_skin();

?>
