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
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @tester Pat
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'servers.php';

// which page should be displayed
$page = 1;
if(isset($_REQUEST['page']))
	$page = $_REQUEST['page'];
elseif(isset($context['arguments'][0]))
	$page = $context['arguments'][0];
$page = strip_tags($page);

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
	$context['page_menu'] = array_merge($context['page_menu'], array('_count' => sprintf(i18n::ns('1 server', '%d servers', $stats['count']), $stats['count'])));

// navigation commands for servers, if necessary
if($stats['count'] > SERVERS_PER_PAGE) {
	$home = 'servers/index.php';
	if($context['with_friendly_urls'] == 'Y')
		$prefix = $home.'/';
	elseif($context['with_friendly_urls'] == 'R')
		$prefix = $home.'/';
	else
		$prefix = $home.'?page=';
	$context['page_menu'] = array_merge($context['page_menu'], Skin::navigate($home, $prefix, $stats['count'], SERVERS_PER_PAGE, $page));
}

// associates may create a new server profile
if(Surfer::is_associate())
	$context['page_menu'] = array_merge($context['page_menu'], array( 'servers/edit.php' => i18n::s('Add a server profile') ));

// associates may ping the cloud
if(Surfer::is_associate())
	$context['page_menu'] = array_merge($context['page_menu'], array( 'servers/ping.php' => i18n::s('Ping the cloud') ));

// associates may populate default server profiles
if(Surfer::is_associate())
	$context['page_menu'] = array_merge($context['page_menu'], array( 'servers/populate.php' => i18n::s('Populate') ));

// associates may change parameters
if(Surfer::is_associate())
	$context['page_menu'] = array_merge($context['page_menu'], array( 'servers/configure.php' => i18n::s('Configure') ));

// seek the database
$cache_id = 'servers/index.php#text#'.$page;
if(!$text =& Cache::get($cache_id)) {

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

// referrals, if any
$context['extra'] .= Skin::build_referrals('servers/index.php');

// render the skin
render_skin();

?>