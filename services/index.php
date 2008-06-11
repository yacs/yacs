<?php
/**
 * back-end services
 *
 * YACS services are aiming to support new flavors of web communications, such as:
 * - blogging software used to post, retrieve and modify articles (e.g., [link=w.bloggar]http://wbloggar.com[/link])
 * - peering web servers submitting data (e.g., community ping)
 * - building customized RSS feed based on search expressions
 * - this server fetching data from others (e.g., server monitoring)
 *
 * YACS currently support following protocols:
 * - [link=JSON-RPC]http://json-rpc.org/wiki/specification[/link]
 * - [link=XML-RPC]http://www.xmlrpc.com/spec[/link]
 * @link http://www.xmlrpc.com/spec XML-RPC Specification
 *
 *
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// load localized strings
i18n::bind('services');

// load the skin
load_skin('services');

// the title of the page
$context['page_title'] = i18n::s('Back-end services');

// splash
$context['text'] .= '<p>'.i18n::s('This index page lists the main services that you can use in the background. Connect your blogging software, RSS news feeder, or other web sites to this server through following Application Programming Interfaces (API).')."</p>\n";

// blog.php
$context['text'] .= Skin::build_block(i18n::s('Blogging interface'), 'title', 'blog');

$context['text'] .= '<p>'.i18n::s('Support popular blogging software such as w.bloggar.').'</p>';

$rows = array();
$rows[] = array(i18n::s('URL:'), '<b>'.$context['url_to_home'].$context['url_to_root'].'services/blog.php</b>');
$rows[] = array(i18n::s('Documentation:'), Skin::build_link('services/blog.php', NULL, 'script'));
$rows[] = array(i18n::s('Specifications:'), Skin::build_link('http://www.xmlrpc.com/metaWeblogApi', 'metaWeblogApi Specification', 'external')
	.', '.Skin::build_link('http://www.xmlrpc.com/spec', 'XML-RPC Specification', 'external') );
$context['text'] .= Skin::table(NULL, $rows);

// json_rpc.php
$context['text'] .= Skin::build_block(i18n::s('Generic JSON-RPC interface'), 'title', 'json-rpc');

$context['text'] .= '<p>'.i18n::s('Bound to hooked back-end scripts').'</p>';

$rows = array();
$rows[] = array(i18n::s('URL:'), '<b>'.$context['url_to_home'].$context['url_to_root'].'services/json_rpc.php</b>');
$rows[] = array(i18n::s('Documentation:'), Skin::build_link('services/json_rpc.php', NULL, 'script'));
$rows[] = array(i18n::s('Specification:'), Skin::build_link('http://json-rpc.org/wiki/specification', 'JSON-RPC Specification', 'external'));
$context['text'] .= Skin::table(NULL, $rows);

// ping.php
$context['text'] .= Skin::build_block(i18n::s('Ping interface'), 'title', 'ping');

$context['text'] .= '<p>'.i18n::s('To spread updates').'</p>';

$rows = array();
$rows[] = array(i18n::s('URL:'), '<b>'.$context['url_to_home'].$context['url_to_root'].'services/ping.php</b>');
$rows[] = array(i18n::s('Documentation:'), Skin::build_link('services/ping.php', NULL, 'script'));
$rows[] = array(i18n::s('Specifications:'), Skin::build_link('http://www.hixie.ch/specs/pingback/pingback', 'Pingback specification', 'external')
	.', '.Skin::build_link('http://www.xmlrpc.com/spec', 'XML-RPC Specification', 'external') );
$context['text'] .= Skin::table(NULL, $rows);

// search.php
$context['text'] .= Skin::build_block(i18n::s('Search interface'), 'title', 'search');

$context['text'] .= '<p>'.i18n::s('Build a customised RSS feed based on any keyword').'</p>';

$rows = array();
$rows[] = array(i18n::s('URL:'), '<b>'.$context['url_to_home'].$context['url_to_root'].'services/search.php</b>');
$rows[] = array(i18n::s('Documentation:'), Skin::build_link('services/search.php', NULL, 'script'));
$rows[] = array(i18n::s('Specifications:'), Skin::build_link('http://blogs.law.harvard.edu/tech/rss', 'RSS 2.0 Specification', 'external') );
$context['text'] .= Skin::table(NULL, $rows);

// xml_rpc.php
$context['text'] .= Skin::build_block(i18n::s('Generic XML-RPC interface'), 'title', 'xml-rpc');

$context['text'] .= '<p>'.i18n::s('Bound to hooked back-end scripts').'</p>';

$rows = array();
$rows[] = array(i18n::s('URL:'), '<b>'.$context['url_to_home'].$context['url_to_root'].'services/xml_rpc.php</b>');
$rows[] = array(i18n::s('Documentation:'), Skin::build_link('services/xml_rpc.php', NULL, 'script'));
$rows[] = array(i18n::s('Specification:'), Skin::build_link('http://www.xmlrpc.com/spec', 'XML-RPC Specification', 'external'));
$context['text'] .= Skin::table(NULL, $rows);

// page tools
if(Surfer::is_associate())
	$context['page_tools'][] = Skin::build_link('services/configure.php', i18n::s('Configure'), 'basic');

// referrals, if any
$context['extra'] .= Skin::build_referrals('services/index.php');

// render the skin
render_skin();

?>