<?php
/**
 * user information as meta data
 *
 * This script uses RDF ans FOAF to describe a user profile.
 *
 * @link http://xmlns.com/foaf/0.1/ FOAF Vocabulary Specification
 * @link http://www.intertwingly.net/public/foaf.rdf A very good example of FOAF usage
 *
 * You will find below an example of script result:
 * [snippet]
 * <?xml version="1.0" encoding="UTF-8"?>
 * <rdf:RDF
 *	xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
 *	xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
 *	xmlns:foaf="http://xmlns.com/foaf/0.1/">
 * <foaf:Person>
 *	<foaf:name>Sam Ruby</foaf:name>
 *	<foaf:firstName>Sam</foaf:firstName>
 *	<foaf:surname>Ruby</foaf:surname>
 *	<foaf:nick>rubys</foaf:nick>
 *	<foaf:mbox_sha1sum>703471c6f39094d88665d24ce72c42fdc5f20585</foaf:mbox_sha1sum>
 *	<foaf:homepage rdf:resource="http://www.intertwingly.net/"/>
 *	<foaf:depiction rdf:resource="http://www.intertwingly.net/images/SamR_small.jpg"/>
 *	<foaf:workplaceHomepage rdf:resource="http://www.ibm.com/"/>
 *	<foaf:schoolHomepage rdf:resource="http://www.cnu.edu/"/>
 * </foaf:Person>
 * </rdf:RDF>
 * [/snippet]
 *
 * If following features are enabled, this script will use them:
 * - compression - Using gzip, if accepted by user agent
 * - cache - Cache is supported through ETag and by setting Content-Length; Also, Cache-Control enables caching for some time, even through HTTPS
 *
 * Restrictions apply on this page:
 * - associates are allowed to move forward
 * - this is the page of the authenticated surfer
 * - access is restricted ('active' field == 'R'), but the surfer is an authenticated member
 * - public access is allowed ('active' field == 'Y')
 * - permission denied is the default
 *
 * Accept following invocations:
 * - describe.php/12
 * - describe.php?id=12
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// look for the article id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
elseif(Surfer::is_logged())
	$id = Surfer::get_id();
$id = strip_tags($id);

// get the item from the database
$item =& Users::get($id);

// associates can do what they want
if(Surfer::is_associate())
	$permitted = TRUE;

// the page of the authenticated surfer
elseif(isset($item['id']) && Surfer::is_creator($item['id']))
	$permitted = TRUE;

// access is restricted to authenticated member
elseif(isset($item['active']) && ($item['active'] == 'R') && Surfer::is_member())
	$permitted = TRUE;

// public access is allowed
elseif(isset($item['active']) && ($item['active'] == 'Y'))
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// load localized strings
i18n::bind('users');

// load the skin
load_skin('users');

// the path to this page
$context['path_bar'] = array( 'users/' => i18n::s('People') );

// the title of the page
if(isset($item['nick_name']))
	$context['page_title'] = $item['nick_name'];
elseif(isset($item['full_name']))
	$context['page_title'] = $item['full_name'];
else
	$context['page_title'] = i18n::s('Unknown user');

// not found
if(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No item has the provided id.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Users::get_url($item['id'], 'describe')));

	// permission denied to authenticated user
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// describe the article
} else {

	// prepare the response
	$text = '<?xml version="1.0" encoding="'.$context['charset'].'"?>'."\n"
		.'<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"'."\n"
		.'		   xmlns:foaf="http://xmlns.com/foaf/0.1/">'."\n"
		.'	<foaf:Person>'."\n";

	// full name
	if($item['full_name'])
		$text .= '		<foaf:name>'.encode_field($item['full_name']).'</foaf:name>'."\n";

	// nick name
	if($item['nick_name'])
		$text .= '		<foaf:nick>'.encode_field($item['nick_name']).'</foaf:nick>'."\n";

	// a representation of the mailto: URI -- protect privacy
	if($item['email'] && is_callable('sha1'))
		$text .= '		<foaf:mbox_sha1sum>'.sha1(encode_field('mailto:'.$item['email'])).'</foaf:mbox_sha1sum>'."\n";

	// the web address
	if($item['web_address'])
		$text .= '		<foaf:homepage rdf:resource="'.encode_field($item['web_address']).'" />'."\n";

	// the user avatar
	if($item['avatar_url'])
		$text .= '		<foaf:img rdf:resource="'.encode_field($item['avatar_url']).'" />'."\n";

	$text .= '	</foaf:Person>'."\n"
		.'</rdf:RDF>';

	//
	// transfer to the user agent
	//

	// handle the output correctly
	render_raw('text/xml; charset='.$context['charset']);

	// suggest a name on download
	if(!headers_sent()) {
		$file_name = utf8::to_ascii(Skin::strip($context['page_title'], 20).'.opml.xml');
		Safe::header('Content-Disposition: inline; filename="'.$file_name.'"');
	}

	// enable 30-minute caching (30*60 = 1800), even through https, to help IE6 on download
	if(!headers_sent()) {
		Safe::header('Expires: '.gmdate("D, d M Y H:i:s", time() + 1800).' GMT');
		Safe::header("Cache-Control: max-age=1800, public");
		Safe::header("Pragma: ");
	}

	// strong validation
	if((!isset($context['without_http_cache']) || ($context['without_http_cache'] != 'Y')) && !headers_sent()) {

		// generate some strong validator
		$etag = '"'.md5($text).'"';
		Safe::header('ETag: '.$etag);

		// validate the content if hash is ok
		if(isset($_SERVER['HTTP_IF_NONE_MATCH']) && is_array($if_none_match = explode(',', str_replace('\"', '"', $_SERVER['HTTP_IF_NONE_MATCH'])))) {
			foreach($if_none_match as $target) {
				if(trim($target) == $etag) {
					Safe::header('Status: 304 Not Modified', TRUE, 304);
					return;
				}
			}
		}
	}

	// actual transmission except on a HEAD request
	if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
		echo $text;

	// the post-processing hook, then exit
	finalize_page(TRUE);

}

// render the skin on error
render_skin();

?>