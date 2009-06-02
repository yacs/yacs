<?php
/**
 * process remote search requests
 *
 * There are two patterns of usage for this script.
 *
 * This script implements the back-end part of the Simple Search RSS API.
 *
 * The first pattern usage is the creation of taylored newsfeeds. Surfers will get news on submitted keywords
 * each time a new matching article will pop up of the target server.
 *
 * The second pattern usage is the extension of search requests to peering servers.
 * Suppose that several YACS servers are involved into the same cloud of servers.
 * One of them, called the Master, is manually requested by a web surfer to search on some keywords.
 * Aside from pages found locally, the Master will also submit the request to other servers, called Peers.
 * Peers are listed in the database of the Master.
 *
 * This web service is based purely on the REST architecture for simplicity.
 * The request is an usual HTTP GET or POST message, using words as parameters. No need for XML here.
 * No authentication either, since the caller is identified by its network address.
 *
 * This script accepts following parameters:
 *
 * [*] [b]search[/b] - One or several words that are looked for.
 * Normally all words have to be present for an item to match.
 * This parameter is mandatory.
 *
 * [*] [b]categories[/b] - A list of categories used to limit the search - Not implemented at the moment
 *
 * [*] [b]type[/b] - Either '[code]articles[/code]', '[code]files[/code]', '[code]images[/code]',
 * '[code]links[/code]', '[code]comments[/code]' or '[code]users[/code]'.
 * By default this parameter is set to '[code]articles[/code]'.
 *
 * The response is a list of matching articles encoded with RSS.
 * Only public items are searched.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 *
 * @see search.php
 * @see services/rss_codec.php
 */
include_once '../shared/global.php';

// stop banned hosts
include_once $context['path_to_root'].'servers/servers.php';
if(isset($_SERVER['REMOTE_HOST']) && ($server =& Servers::get($_SERVER['REMOTE_HOST']) && ($server['process_search'] != 'Y')))
	exit('Access denied');

// look for words
$search = '';
if(isset($_REQUEST['search']))
	$search = $_REQUEST['search'];
elseif(isset($context['arguments'][0]))
	$search = $context['arguments'][0];
$search = strip_tags($search);

// search type
$type = '';
if(isset($_REQUEST['type']))
	$type = $_REQUEST['type'];
$type = strip_tags($type);

// load localized strings
i18n::bind('services');

// load a skin engine
load_skin('services');

// loads feeding parameters
Safe::load('parameters/feeds.include.php');

// set default values
if(!$context['channel_title'])
	$context['channel_title'] = $context['site_name'];
if(!$context['channel_description'])
	$context['channel_description'] = $context['site_description'];

// channel attributes
$values = array();
$values['channel'] = array();

// set channel information
if($search)
	$values['channel']['title'] = sprintf(i18n::s('%s at %s'), $search, $context['channel_title']);
else
	$values['channel']['title'] = $context['channel_title'];
$values['channel']['link'] = $context['url_to_home'].'/';
$values['channel']['description'] = $context['channel_description'];
if(isset($context['powered_by_image']) && $context['powered_by_image'])
	$values['channel']['image'] = $context['url_to_home'].$context['url_to_root'].$context['powered_by_image'];

// depending on search type
switch($type) {

	// search in articles
	default:
	case 'articles':
	case 'images':
		$values['items'] = Articles::search($search, 0, 30, 'feed');
		break;

	// search in comments
	case 'comments':
		include_once $context['path_to_root'].'comments/comments.php';
		$values['items'] = Comments::search($search, 0, 30, 'feed');
		break;

	// search in files
	case 'files':
		include_once $context['path_to_root'].'files/files.php';
		$values['items'] = Files::search($search, 0, 30, 'feed');
		break;

	// search in links
	case 'links':
		include_once $context['path_to_root'].'links/links.php';
		$values['items'] = Links::search($search, 0, 30, 'feed');
		break;

	// search in users
	case 'users':
		$values['items'] = Users::search($search, 0, 30, 'feed');
		break;

}

// make a text
include_once 'codec.php';
include_once 'rss_codec.php';
$result = rss_Codec::encode($values);
$status = @$result[0];
$text = @$result[1];

// handle the output correctly
render_raw('text/xml; charset='.$context['charset']);

// actual transmission except on a HEAD request
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
	echo $text;

// the post-processing hook
finalize_page();

?>