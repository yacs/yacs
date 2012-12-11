<?php
/**
 * describe blogging API in RSD format
 *
 * Really Simple Discovery (RSD) is a way to help client software find the
 * services needed to read, edit, or "work with" weblogging software.
 *
 * @link http://cyber.law.harvard.edu/blogs/gems/tech/rsd.html RSD 1.0 specification
 *
 * This script helps to locate the blogging interface from any section, and from the front page.
 * To trigger the auto-discovery feature, visit the target section, and enter its web address
 * into your blogging tool.
 *
 * The BlogId provided at section pages is the identification number of the respective section.
 *
 * The BlogID used from the front page is the identification number of the default section,
 * which is the section named 'default', if any, or the first public section of the site map.
 *
 * Accepted calls:
 * - describe.php provide the list of sections for this surfer
 * - describe.php?anchor=123 section with id 123 is the main blogging area
 * - describe.php/123 section with id 123 is the main blogging area
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['anchor']))
	$id = $_REQUEST['anchor'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// ensure we have a default section to use
if(!$id)
	$id = Sections::get_default();

// get the item from the database
$item = Sections::get($id);

// load a skin engine
load_skin('services');

// the preamble
$text = '<?xml version="1.0" encoding="'.$context['charset'].'"?>'."\n"
	.'<rsd version="1.0" xmlns="http://archipelago.phrasewise.com/rsd" >'."\n";

// the service
$text .= '<service>'."\n";

// name and link for the engine
$text .= '	<engineName>YACS</engineName>'."\n"
	.'<engineLink>http://www.yacs.fr/</engineLink>'."\n";

// blog home page
if(isset($item['id']))
	$link = Sections::get_permalink($item);
else
	$link = '';
$text .= '	<homePageLink>'.$context['url_to_home'].$context['url_to_root'].$link.'</homePageLink>'."\n";

// restrict the scope of the API
$scope = '';
if($id)
	$scope = '?id='.urlencode($id);

// available blogging api
$text .= '	<apis>'."\n"
	.'		<api name="MovableType" preferred="true" apiLink="'.$context['url_to_home'].$context['url_to_root'].'services/blog.php'.$scope.'" blogID="'.encode_field($id).'" />'."\n"
	.'		<api name="MetaWeblog" preferred="false" apiLink="'.$context['url_to_home'].$context['url_to_root'].'services/blog.php'.$scope.'" blogID="'.encode_field($id).'" />'."\n"
	.'		<api name="Blogger" preferred="false" apiLink="'.$context['url_to_home'].$context['url_to_root'].'services/blog.php'.$scope.'" blogID="'.encode_field($id).'" />'."\n"
	.'	</apis>'."\n";

// the postamble
$text .= '</service>'."\n".'</rsd>'."\n";

//
// transfer to the user agent
//

// handle the output correctly
render_raw('application/rsd+xml; charset='.$context['charset']);

// suggest a name on download
if(!headers_sent()) {
	$file_name = utf8::to_ascii($context['site_name'].'.rsd.xml');
	Safe::header('Content-Disposition: inline; filename="'.str_replace('"', '', $file_name).'"');
}

// enable 30-minute caching (30*60 = 1800), even through https, to help IE6 on download
http::expire(1800);

// strong validator
$etag = '"'.md5($text).'"';

// manage web cache
if(http::validate(NULL, $etag))
	return;

// actual transmission except on a HEAD request
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
	echo $text;

// the post-processing hook
finalize_page();

?>