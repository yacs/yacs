<?php
/**
 * post a comment
 *
 * This script serves as a back-end for the Comment API and for Post-It notifications.
 *
 * Using the Comment API, bloggers can easily submit comments from within their preferred RSS reader.
 *
 * @link http://wellformedweb.org/story/9 The Comment API
 * @link http://wellformedweb.org/news/wfw_namespace_elements/ wfw namespace elements
 *
 * YACS attempts to bind author information to an existing user profile.
 *
 * If anonymous comments are allowed, YACS uses the value of the author field as the id of a user profile.
 * If a password is provided, YACS validates it as well.
 *
 * To achieve this YACS expects to get credentials in the user name, in the usual form [code]&lt;nick_name&gt;:&lt;password&gt;[/code].
 *
 * For example, for the user [code]foo[/code] and the password [code]a_password[/code], configure your newsreader
 * to send '[code]foo:a_password[/code]' as user name.
 *
 * YACS allows for several strategies to protect from spam, depending of the settings of global parameters:
 * - if no user name has been provided, and if [code]$context['users_with_anonymous_comments'] == 'Y'[/code],
 * the comment is accepted
 * - if no password has been provided, and if [code]$context['users_with_anonymous_comments'] == 'Y'[/code],
 * the comment is accepted
 * - if user name and password are those of a valid user profile,
 * the comment is accepted
 * - else the comment is rejected
 *
 * @see control/configure.php
 *
 * Here is an example of Comment API, as submit from [link=RSS Bandit]http://www.rssbandit.org/[/link].
 * Note that line breaks have been inserted for readability.
 *
 * [snippet]
 * POST /yacs/comments/post.php/123 HTTP/1.1
 * Content-Type: text/xml
 *
 * <?xml version="1.0" encoding="iso-8859-15"?>
 * <item>
 *	<title>RE: How to create a button from an image?  </title>
 *	<link>http://www.yetanothercommunitysystem.com/</link>
 *	<pubDate>Mon, 04 Oct 2004 12:21:52 GMT</pubDate>
 *	<description><![CDATA[hello world ]]></description>
 *	<author>foo.bar@acme.com (Foo)</author>
 *	<dc:creator xmlns:dc="http://purl.org/dc/elements/1.1/">foo.bar@acme.com (Foo)</dc:creator>
 * </item>
 * [/snippet]
 *
 * Here is an example of Post-It. Note that line breaks have been inserted for readability.
 *
 * [snippet]
 * POST http://www.foo.com/yacs/comments/post.php/123
 * Content-Type: application/x-www-form-urlencoded
 *
 * comment=My+Comment+Comes+Here
 * &email=joe@bitworking.org
 * &name=Foo+Bar
 * &url=http://www.bar.com/
 * &agent=send-cb.pl+(Version+0.1)
 * [/snippet]
 *
 * Accepted calls:
 * - post.php/&lt;type&gt;/&lt;id&gt;			create a new comment for this anchor
 * - post.php?anchor=&lt;type&gt;:&lt;id&gt;	create a new comment for this anchor
 * - post.php/&lt;id&gt;						create a new comment for the anchor 'article:&lt;id&gt;'
 *
 * If the anchor for this item specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'comments.php';

// ensure we have some raw content
global $HTTP_RAW_POST_DATA;
if(!isset($HTTP_RAW_POST_DATA))
   $HTTP_RAW_POST_DATA = file_get_contents("php://input");

// save the request if debug mode
if($HTTP_RAW_POST_DATA && isset($context['debug_comment']) && ($context['debug_comment'] == 'Y'))
	Logger::remember('comments/post.php', 'comments post request', $HTTP_RAW_POST_DATA, 'debug');

// transcode to our internal charset
if($context['charset'] == 'utf-8')
	$HTTP_RAW_POST_DATA = utf8::encode($HTTP_RAW_POST_DATA);

// look for the anchor reference
$anchor = NULL;
if(isset($_REQUEST['anchor']))
	$anchor = $_REQUEST['anchor'];
elseif(isset($context['arguments'][1]))
	$anchor = $context['arguments'][0].':'.$context['arguments'][1];
elseif(isset($context['arguments'][0]))
	$anchor = 'article:'.$context['arguments'][0];
$anchor = strip_tags($anchor);

// get the related anchor, if any
if($anchor)
	$anchor = Anchors::get($anchor);

// a straightforward implementation of the Comment API
if(isset($_SERVER['CONTENT_TYPE']) && ($_SERVER['CONTENT_TYPE'] == 'text/xml')) {

	// description -- escaped or not
	$comment = '';
	if(preg_match('/<description><!\[CDATA\[(.+)\]\]><\/description>/is', $HTTP_RAW_POST_DATA, $matches))
		$comment = $matches[1];
	elseif(preg_match('/<description>(.+)<\/description>/is', $HTTP_RAW_POST_DATA, $matches))
		$comment = $matches[1];

	// creator
	$name = '';
	if(preg_match('/<creator[^>]*>(.+)<\/creator>/is', $HTTP_RAW_POST_DATA, $matches))
		$name = $matches[1];

	// dc:creator
	elseif(preg_match('/<dc:creator[^>]*>(.+)<\/dc:creator>/is', $HTTP_RAW_POST_DATA, $matches))
		$name = $matches[1];

	// title
	if(preg_match('/<title>(.+)<\/title>/is', $HTTP_RAW_POST_DATA, $matches)) {

		// only if we are not repeating page title again and again
		if($anchor) {
			if(!preg_match('/'.preg_quote($anchor->get_title(), '/').'/i', $matches[1]))
				$comment = '[b]'.$matches[1].'[/b] '.$comment;
		} else
			$comment = '[b]'.$matches[1].'[/b] '.$comment;
	}

	// link
	$source = '';
	if(preg_match('/<link>(.+)<\/link>/is', $HTTP_RAW_POST_DATA, $matches))
		$source = $matches[1];

	// author
	if(!$source)
		if(preg_match('/<author>(.+)<\/author>/is', $HTTP_RAW_POST_DATA, $matches))
			$source = $matches[1];

// the Post-It API
} elseif(isset($_SERVER['CONTENT_TYPE']) && ($_SERVER['CONTENT_TYPE'] == 'application/x-www-form-urlencoded')) {

	// the comment
	if(isset($_REQUEST['comment']))
		$comment = strip_tags($_REQUEST['comment']);

	// poster name
	if(isset($_REQUEST['name']))
		$name = strip_tags($_REQUEST['name']);

	// poster web address
	if(isset($_REQUEST['url']))
		$source = strip_tags($_REQUEST['url']);

	// or poster email address
	elseif(isset($_REQUEST['email']))
		$source = strip_tags($_REQUEST['email']);
}

// load the skin, maybe with a variant
load_skin('comments', $anchor);

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor))
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'comments/' => i18n::s('Comments') );

// the title of the page
$context['page_title'] = i18n::s('Comment service');

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// process uploaded data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// extract nick name if  mailbox@server (name)
	if(preg_match('/\((.+)\)/', $name, $matches))
		$name = $matches[1];

	// split name and password, if any
	$password = '';
	if(preg_match('/^(.+):(.+)$/', $name, $matches)) {
		$name = $matches[1];
		$password = $matches[2];
	}

	// make things explicit
	if(!$name)
		$name = 'anonymous';

	// anonymous comments are not allowed
	if(($name == 'anonymous') && (!isset($context['users_with_anonymous_comments']) || ($context['users_with_anonymous_comments'] != 'Y')))
		$response = array('faultCode' => 49, 'faultString' => 'Anonymous posts are not allowed');

	// users have to be authenticated
	elseif(!$password && (!isset($context['users_with_anonymous_comments']) || ($context['users_with_anonymous_comments'] != 'Y')))
		$response = array('faultCode' => 49, 'faultString' => 'Please authenticate with a valid user name and password');

	// do we have a valid target to track?
	elseif(!$anchor || !is_object($anchor))
		$response = array('faultCode' => 33, 'faultString' => 'Nothing to comment');

	// save the comment
	else {

		// prepare a new comment record
		$fields = array();
		$fields['anchor'] = $anchor->get_reference();
		$fields['description'] = $comment;
		$fields['create_name'] = $name;
		$fields['create_address'] = $source;
		$fields['edit_name'] = $name;
		$fields['edit_address'] = $source;

		// if user name and/or password are provided, authentication has to be correct
		$user = array();
		if($name && !$password && (!$user =& Users::get($name)))
			$response = array('faultCode' => 49, 'faultString' => 'Unknown user name');

		elseif($name && $password && (!$user = Users::login($name, $password)))
			$response = array('faultCode' => 49, 'faultString' => 'Invalid user name and password');

		// ok, post this comment
		else {

			// reference user profile if any
			if($user['id']) {
				$fields['create_id'] = $user['id'];
				$fields['create_name'] = $user['nick_name'];
				$fields['create_address'] = $user['email'];
				$fields['edit_id'] = $user['id'];
				$fields['edit_name'] = $user['nick_name'];
				$fields['edit_address'] = $user['email'];
			}

			// save the request if debug mode
			if($context['debug_comment'] == 'Y')
				Logger::remember('comments/post.php', 'comments post item', $fields, 'debug');

			// save in the database
			if(!$fields['id'] = Comments::post($fields))
				$response = array('faultCode' => 1, 'faultString' => Skin::error_pop());

			// post-processing
			else {

				// touch the related anchor
				$anchor->touch('comment:create', $fields['id']);

				// clear cache
				Comments::clear($fields);

				// increment the post counter of the surfer
				if($user['id'])
					Users::increment_posts($user['id']);

			}
		}
	}

	// an error has been encountered
	if(is_array($response)) {
		$response = '<?xml version="1.0" encoding="'.$context['charset'].'"?>'
			."\n".'<response>'
			."\n".'<error>'.$response['faultCode'].'</error>'
			."\n".'<message>'.$response['faultString'].'</message>'
			."\n".'</response>';

		// also sets an error at the HTTP level
		Safe::header('Status: 400 Bad Request', TRUE, 400);

	// everything's going fine
	} else {
		$response = '<?xml version="1.0" encoding="'.$context['charset'].'"?>'
			."\n".'<response>'
			."\n".'<error>0</error>'
			."\n".'</response>';
	}

	// save the response if debug mode
	if($context['debug_comment'] == 'Y')
		Logger::remember('comments/post.php', 'comments post response', $response, 'debug');

	// send the response
	Safe::header('Content-Type: text/xml');
	Safe::header('Content-Length: '.strlen($response));
	echo $response;
	return;

// this is not a POST -- assume we have a human being
} else {

	// detail usage rule
	Skin::error(i18n::s('This script supports Comment API and Post-It updates through HTTP POST requests.'));

}

// render the skin
render_skin();
?>