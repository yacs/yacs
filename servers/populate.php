<?php
/**
 * populate servers
 *
 * At the moment following entries are created by this script:
 *
 * [*] [code]blo.gs[/code], a directory of recently updated weblogs and tools for tracking interesting weblogs,
 * in the spirit of services like [code]weblogs[/code], [code]blogtracker[/code] and [code]blogrolling[/code].
 * To get your YACS site listed, [code]blo.gs[/code] must be informed about your site's updates.
 * This is done automatically by a XML-RPC call to [code]weblogUpdates.ping[/code] when your site is updated.
 *
 * [*] [link=Moreover]http://w.moreover.com/[/link] - The Moreover Ping Server enables a wide range of publishers, from major international news sites to personal Weblogs,
 * to automatically notify Moreover when new content has been published.
 * This ensures that your content is entered into the vast Moreover distribution network as quickly and as accurately as possible.
 *
 * The Moreover Ping Server is used to automatically inform Moreover whenever you update content on your site.
 * It is a server program that receives notification from your site that you have added new content.
 * When Moreover receives this message, we send out a harvesting program to your site to collect the new content
 * and post it to our vast distribution network.
 *
 * [*] [link=ping-o-matic]http://pingomatic.com/[/link], the pinging gateway to most famous blogging services, including
 * BlogChatter, BlogRolling, BlogShares, BlogStreet, Feed Burner, GeoURL, PubSub, Root Blog, RubHub and Weblogs.
 *
 * [*] [link=Technorati]http://www.technorati/[/link] monitors several millions (more than three million blogs in July 2004) in real time so you can discover the conversations happening now.
 * This may be used as search engine dedicated to blogs.
 * Based on a XML-RPC call to [code]weblogUpdates.ping[/code] when your site is updated.
 *
 * [*] [link=Yahoo!]http://www.yahoo.com/[/link]. don't you Yahoo yet?
 * To get your YACS site listed, Yahoo! must be informed about your site's updates.
 * Based on a XML-RPC call to [code]weblogUpdates.ping[/code] when your site is updated.
 *
 * [*] [link=yetanothercommunitysystem]http://www.yacs.fr/[/link] is advertised as well,
 * in order to maintain the list of (active) servers using YACS.
 * Based on a XML-RPC call to [code]weblogUpdates.ping[/code] when your site is updated.
 *
 * This script may be included in an other script, such as [script]help/populate.php[/script].
 * A link is also added at [script]servers/index.php[/script], the index page of servers, to launch it directly.
 *
 * Only associates can proceed.
 *
 * @see help/populate.php
 * @see servers/index.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// if it was a HEAD request, stop here
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
	return;

// this script will be included most of the time
$included = TRUE;

// but direct call is also allowed
global $context;
if(!defined('YACS')) {
	$included = FALSE;

	// include global declarations
	include_once '../shared/global.php';

	// load the skin
	load_skin('servers');

	// the path to this page
	$context['path_bar'] = array( 'servers/' => i18n::s('Servers') );

	// the title of the page
	$context['page_title'] = i18n::s('Content Assistant');

	// stop hackers the hard way
	if(!Surfer::is_associate())
		exit('You are not allowed to perform this operation.');

}


// clear the cache for servers
Cache::clear('servers');

// this page is dedicated to servers
include_once $context['path_to_root'].'servers/servers.php';
$text = '';

// splash message
$text .= '<p>'.i18n::s('If your server has been installed on an intranet and is not visible from the Internet, please delete these profiles to avoid unnecessary requests back and forth. Your site will not be referenced anyway.')."</p>\n";

// 'blo.gs' server
$fields = array();
$fields['host_name'] = 'blo.gs';
if(Servers::get($fields['host_name']))
	$text .= sprintf(i18n::s('An entry already exists for server %s'), $fields['host_name']).BR."\n";
else {
	$fields['title'] = 'blo.gs';
	$fields['description'] = i18n::c('Another famous blogs aggregator');
	$fields['main_url'] = 'http://blo.gs/';
	$fields['submit_feed'] = 'N';
	$fields['feed_url'] = '';
	$fields['submit_ping'] = 'Y';
	$fields['ping_url'] = 'http://ping.blo.gs/';
	$fields['submit_search'] = 'N';
	$fields['search_url'] = '';
	$fields['submit_monitor'] = 'N';
	$fields['monitor_url'] = '';
	if($error = Servers::post($fields))
		$text .= $error;
	else
		$text .= sprintf(i18n::s('A record has been created for server %s'), $fields['host_name']).BR."\n";
}

// 'moreover' server
$fields = array();
$fields['host_name'] = 'moreover';
if(Servers::get($fields['host_name']))
	$text .= sprintf(i18n::s('An entry already exists for server %s'), $fields['host_name']).BR."\n";
else {
	$fields['title'] = 'Moreover';
	$fields['description'] = i18n::c('Another famous blogs aggregator');
	$fields['main_url'] = 'http://w.moreover.com/';
	$fields['submit_feed'] = 'N';
	$fields['feed_url'] = '';
	$fields['submit_ping'] = 'Y';
	$fields['ping_url'] = 'http://api.moreover.com/RPC2';
	$fields['submit_search'] = 'N';
	$fields['search_url'] = '';
	$fields['submit_monitor'] = 'N';
	$fields['monitor_url'] = '';
	if($error = Servers::post($fields))
		$text .= $error;
	else
		$text .= sprintf(i18n::s('A record has been created for server %s'), $fields['host_name']).BR."\n";
}

// 'pingomatic.com' server
$fields = array();
$fields['host_name'] = 'pingomatic.com';
if(Servers::get($fields['host_name']))
	$text .= sprintf(i18n::s('An entry already exists for server %s'), $fields['host_name']).BR."\n";
else {
	$fields['title'] = 'ping-o-matic';
	$fields['description'] = i18n::c('The famous pinging gateway');
	$fields['main_url'] = 'http://pingomatic.com/';
	$fields['submit_feed'] = 'N';
	$fields['feed_url'] = '';
	$fields['submit_ping'] = 'Y';
	$fields['ping_url'] = 'http://rpc.pingomatic.com/';
	$fields['submit_search'] = 'N';
	$fields['search_url'] = '';
	$fields['submit_monitor'] = 'N';
	$fields['monitor_url'] = '';
	if($error = Servers::post($fields))
		$text .= $error;
	else
		$text .= sprintf(i18n::s('A record has been created for server %s'), $fields['host_name']).BR."\n";
}

// 'www.technorati.com' server
$fields = array();
$fields['host_name'] = 'www.technorati.com';
if(Servers::get($fields['host_name']))
	$text .= sprintf(i18n::s('An entry already exists for server %s'), $fields['host_name']).BR."\n";
else {
	$fields['title'] = 'Technorati';
	$fields['main_url'] = 'http://www.technorati.com/';
	$fields['submit_feed'] = 'N';
	$fields['feed_url'] = '';
	$fields['submit_ping'] = 'Y';
	$fields['ping_url'] = 'http://rpc.technorati.com/rpc/ping';
	$fields['submit_search'] = 'N';
	$fields['search_url'] = '';
	$fields['submit_monitor'] = 'N';
	$fields['monitor_url'] = '';
	if($error = Servers::post($fields))
		$text .= $error;
	else
		$text .= sprintf(i18n::s('A record has been created for server %s'), $fields['host_name']).BR."\n";
}

// 'www.yahoo.com' server
$fields = array();
$fields['host_name'] = 'www.yahoo.com';
if(Servers::get($fields['host_name']))
	$text .= sprintf(i18n::s('An entry already exists for server %s'), $fields['host_name']).BR."\n";
else {
	$fields['title'] = 'yahoo';
	$fields['description'] = i18n::c('Do you Yahoo?');
	$fields['main_url'] = 'http://www.yahoo.com/';
	$fields['submit_feed'] = 'N';
	$fields['feed_url'] = '';
	$fields['submit_ping'] = 'Y';
	$fields['ping_url'] = 'http://api.my.yahoo.com/RPC2';
	$fields['submit_search'] = 'N';
	$fields['search_url'] = '';
	$fields['submit_monitor'] = 'N';
	$fields['monitor_url'] = '';
	if($error = Servers::post($fields))
		$text .= $error;
	else
		$text .= sprintf(i18n::s('A record has been created for server %s'), $fields['host_name']).BR."\n";
}

// 'www.yacs.fr' server
$fields = array();
$fields['host_name'] = i18n::s('www.yacs.fr');
if(Servers::get($fields['host_name']))
	$text .= sprintf(i18n::s('An entry already exists for server %s'), $fields['host_name']).BR."\n";
else {
	$fields['title'] = 'yacs';
	$fields['description'] = i18n::c('The origin server for the YACS system');
	$fields['main_url'] = 'http://www.yacs.fr/';
	$fields['submit_feed'] = 'Y';
	$fields['feed_url'] = 'http://www.yacs.fr/feeds/rss.php';
	$fields['submit_ping'] = 'Y';
	$fields['ping_url'] = 'http://www.yacs.fr/services/ping.php';
	$fields['submit_search'] = 'N';
	$fields['search_url'] = 'http://www.yacs.fr/services/search.php';
	$fields['submit_monitor'] = 'N';
	$fields['monitor_url'] = 'http://www.yacs.fr/services/ping.php';
	if($error = Servers::post($fields))
		$text .= $error;
	else
		$text .= sprintf(i18n::s('A record has been created for server %s'), $fields['host_name']).BR."\n";
}

// report on actions performed
if($included)
	echo $text;
else {
	$context['text'] .= $text;
	$menu = array('servers/' => i18n::s('Back to servers'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');
	render_skin();
}

?>