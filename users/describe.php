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
 *	<foaf:homepage rdf:resource="http://www.intertwingly.net/" />
 *	<foaf:depiction rdf:resource="http://www.intertwingly.net/images/SamR_small.jpg" />
 *	<foaf:workplaceHomepage rdf:resource="http://www.ibm.com/" />
 *	<foaf:schoolHomepage rdf:resource="http://www.cnu.edu/" />
 * </foaf:Person>
 * </rdf:RDF>
 * [/snippet]
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
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// check network credentials, if any
if($user = Users::authenticate())
	Surfer::empower($user['capability']);

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
$item = Users::get($id);

// associates can do what they want
if(Surfer::is_associate())
	$permitted = TRUE;

// the page of the authenticated surfer
elseif(isset($item['id']) && Surfer::is($item['id']))
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

// load the skin
load_skin('users');

// the path to this page
$context['path_bar'] = array( 'users/' => i18n::s('People') );

// the title of the page
if(isset($item['nick_name']))
	$context['page_title'] = $item['nick_name'];
elseif(isset($item['full_name']))
	$context['page_title'] = $item['full_name'];

// not found
if(!isset($item['id'])) {
	include '../error.php';

// permission denied
} elseif(!$permitted) {

	// give anonymous surfers a chance for HTTP authentication
	if(!Surfer::is_logged()) {
		Safe::header('WWW-Authenticate: Basic realm="'.utf8::to_iso8859($context['site_name']).'"');
		Safe::header('Status: 401 Unauthorized', TRUE, 401);
	}

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// describe the article
} else {

	// prepare the response
	$text = '<?xml version="1.0" encoding="'.$context['charset'].'"?>'."\n"
		.'<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"'."\n"
		.'		   xmlns:foaf="http://xmlns.com/foaf/0.1/"'."\n"
		.'         xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#">'."\n"
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
	else
		$text .= '		<foaf:homepage rdf:resource="'.encode_field(Users::get_permalink($item)).'" />'."\n";

	// the user avatar
	if($item['avatar_url']) {
		if($item['avatar_url'][0] == '/')
			$item['avatar_url'] = str_replace('//', '/', $context['url_to_home'].$context['url_to_root'].$item['avatar_url']);
		$text .= '		<foaf:img rdf:resource="'.encode_field($item['avatar_url']).'" />'."\n";
	}

	// list watched users by posts
	if($items =& Members::list_users_by_posts_for_member('user:'.$item['id'], 0, USERS_PER_PAGE, 'raw')) {
		foreach($items as $id => $attributes)
			$text .= '	<foaf:knows>'."\n"
				.'		<foaf:Person>'."\n"
				.'			<foaf:name>'.encode_field($attributes['full_name']).'</foaf:name>'."\n"
				.'			<rdfs:seeAlso rdf:resource="'.encode_field($context['url_to_home'].$context['url_to_root'].Users::get_url($id, 'describe')).'" />'."\n"
				.'		</foaf:Person>'."\n"
				.'	</foaf:knows>'."\n";
	}



	$text .= '	</foaf:Person>'."\n"
		.'</rdf:RDF>';

	//
	// transfer to the user agent
	//

	// handle the output correctly
	render_raw('text/xml; charset='.$context['charset']);

	// suggest a name on download
	if(!headers_sent()) {
		$file_name = utf8::to_ascii(Skin::strip($context['page_title']).'.opml.xml');
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

	// the post-processing hook, then exit
	finalize_page(TRUE);

}

// render the skin on error
render_skin();

?>
