<?php
/**
 * process remote blog calls
 *
 * @todo restrict the list of blogs to assigned sections, plus the default section
 * @todo use section.php for editors
 *
 * This script interfaces YACS with popular weblog client software.
 *
 * The support of the XML-RPC API in YACS is really straightforward.
 * The main complexity is that different words are used for same things.
 * Therefore, please keep in mind that metaWeblog' blogs are YACS sections.
 *
 * @link http://www.xmlrpc.com/spec XML-RPC specification
 *
 * At the moment YACS supports following XML-RPC flavors:
 * - the Movable Type API
 * - the MetaWeblog API
 * - the Blogger API
 *
 * Actually, all three of those API's build on each other.
 * Blogger is the most basic. MetaWeblog "embraces and extends" it. The
 * Movable Type API does the same to MetaWeblog.
 *
 *
 * [title]Error codes[/title]
 *
 * YACS uses standard error codes, as specified in [link=Specification for Fault Code Interoperability]http://xmlrpc-epi.sourceforge.net/specs/rfc.fault_codes.php[/link]:
 * &quot;As the number of xml-rpc server implementations has proliferated, so has the number of error codes and descriptions.
 * The charter of this specification is to define a small set of error codes that are common across most server implementations
 * so that clients can programmatically handle common errors such as "method not found" or "parse error".&quot;
 *
 * Here are the error codes and their meaning:
 * - -32700 ---> parse error. not well formed
 * - -32701 ---> parse error. unsupported encoding
 * - -32702 ---> parse error. invalid character for encoding
 * - -32600 ---> server error. invalid xml-rpc. not conforming to spec.
 * - -32601 ---> server error. requested method not found
 * - -32602 ---> server error. invalid method parameters (bad login, etc.)
 * - -32603 ---> server error. internal xml-rpc error (no response)
 * - -32500 ---> application error (database error)
 * - -32400 ---> system error
 * - -32300 ---> transport error
 *
 * @link http://xmlrpc-epi.sourceforge.net/specs/rfc.fault_codes.php Specification for Fault Code Interoperability
 *
 *
 * [title]The Movable Type API[/title]
 *
 * @link http://msdn2.microsoft.com/en-us/library/bb259697.aspx MetaWeblog API Reference, according to Microsoft
 * @link http://blog.ustc.edu.cn/mts/docs/mtmanual_programmatic.html
 *
 *
 * [subtitle]mt.getRecentPostTitles[/subtitle]
 *
 * Returns a bandwidth-friendly list of the most recent posts in the system.
 *
 * Syntax: mt.getRecentPostTitles(blogid, username, password, numberOfPosts) returns struct
 *
 * Following components of the struct are correctly processed:
 * - dateCreated - ISO 8601
 * - userid
 * - postid
 * - title
 *
 *
 * [subtitle]mt.getCategoryList[/subtitle]
 *
 * List all categories defined in the weblog.
 *
 * Syntax: mt.getCategoryList(blogid, username, password) returns struct
 *
 * In YACS categories are shared at the system level, therefore the blogid parameter is not used.
 * Returns up to 30 YACS categories.
 *
 * Returns following components:
 * - categoryId - id of the category
 * - categoryName - the name of the category
 *
 *
 * [subtitle]mt.getPostCategories[/subtitle]
 *
 * List all categories to which the post is assigned.
 *
 * Syntax: mt.getPostCategories(postid, username, password) returns struct
 *
 * Returns following components:
 * - categoryId - id of the category
 * - categoryName - the name of the category
 * - isPrimary - 1 if true, 0 otherwise
 *
 *
 * [subtitle]mt.getTrackbackPings[/subtitle]
 *
 * List pages linked to this post.
 *
 * Syntax: mt.getTrackbackPings(postid) returns struct
 *
 * Returns following components:
 * - pingTitle - title of the entry sent in the ping
 * - pingUrl - the URL of the entry
 * - pingIP - the IP address of the host that sent the ping
 *
 *
 * [subtitle]mt.publishPost[/subtitle]
 *
 * Publish (rebuild) all of the static files related to an entry from your weblog. Equivalent to saving an entry in the system (but without the ping).
 *
 * Syntax: mt.publishPost(postid, username, password) returns boolean
 *
 *
 * [subtitle]mt.setPostCategories[/subtitle]
 *
 * Assign categories to a post.
 *
 * Syntax: mt.setPostCategories(postid, username, password, struct) returns boolean
 *
 * The struct has following components:
 * - categoryId - id of the category
 * - isPrimary - 1 if true, 0 otherwise (option)
 *
 *
 * [subtitle]mt.supportedMethods[/subtitle]
 *
 * Retrieve information about the XML-RPC methods supported by the server.
 *
 * Syntax: mt.supportedMethods() returns array of strings
 *
 *
 * [title]The MetaWeblog API[/title]
 *
 * The MetaWeblog API is a programming interface that allows external programs
 * to get and set the text and attributes of weblog posts. It builds on the
 * popular XML-RPC communication protocol, with implementations available
 * in many popular programming environments.
 *
 * @link http://www.xmlrpc.com/metaWeblogApi The MetaWeblog API
 *
 * The MetaWeblog API uses an XML-RPC struct to represent a weblog post. Rather
 * than invent a new vocabulary for the metadata of a weblog post, this uses the
 * vocabulary for an item in RSS 2.0. So you can refer to a post's title, link
 * and description; or its author, comments, enclosure, guid, etc using the
 * already-familiar names given to those elements in RSS 2.0.
 *
 * @link http://blogs.law.harvard.edu/tech/rss RSS 2.0 Specification
 *
 *
 * [subtitle]metaWeblog.editPost[/subtitle]
 *
 * Modify or publish an existing post.
 *
 * Syntax: metaWeblog.editPost(postid, username, password, struct, publish) returns true
 *
 * Following components of the struct are correctly processed:
 * - title - set the page title
 * - source (a sub-component of the 'description' field) - set the page source, usually, an originating URL
 * - introduction (a sub-component of the 'description' field) - set the page introduction
 * - description - set the actual page content
 * - category - link this page to listed categories
 *
 * If the publish field is set to TRUE, the publication mechanism of YACS applies.
 *
 *
 * [subtitle]metaWeblog.deletePost[/subtitle]
 *
 * An alias for blogger.deletePost
 *
 * @link http://www.xmlrpc.com/stories/storyReader$2460 Rounding out the MetaWeblog API
 *
 *
 * [subtitle]metaWeblog.getCategories[/subtitle]
 *
 * List categories for a given blog.
 *
 * Syntax: metaWeblog.getCategories(blogid, username, password) returns struct
 *
 * In YACS categories are shared at the system level, therefore the blogid parameter is not used.
 * Returns up to 30 YACS categories.
 *
 * Returns following components:
 * - categoryId - id of the category
 * - categoryName - the name of the category
 * - description - category description
 * - rssUrl - reference of the RSS feed for the category
 * - htmlUrl - reference of the category web page
 *
 *
 * [subtitle]metaWeblog.getPost[/subtitle]
 *
 * Read one post. Returns the post if successful, or a fault otherwise.
 *
 * Syntax: metaWeblog.getPost(postid, username, password) returns struct
 *
 * Returns following components:
 * - title - page title
 * - link - the web address to get the original page
 * - permaLink - the web address to get the original page
 * - description - the actual page content - with 'introduction' and 'source' sub components
 * - author - mail address of the page creator
 * - comments - the web address to comment the page
 * - dateCreated - date of last edition (ISO 8601)
 * - userid - id of last editor
 * - postid - page id
 *
 * Six Apart extensions:
 * String mt_excerpt, String mt_text_more, int mt_allow_comments, int mt_allow_pings, String mt_convert_breaks, String mt_keywords;
 *
 * [subtitle]metaWeblog.getRecentPosts[/subtitle]
 *
 * List most recent posts at a given blog.
 *
 * Syntax: metaWeblog.getRecentPosts(blogid, username, password, numberOfPosts) returns array of structs
 *
 * Each post has following components:
 * - dateCreated - date of last edition
 * - userid - id of last editor
 * - postid - page id
 * - title - page title
 * - link - the web address to get the original page
 * - permaLink - the web address to get the original page
 * - description - the actual page content - with 'introduction' and 'source' sub components
 * - author - mail address of the page creator
 * - comments - the web address to comment the page
 * - pubDate - publication date, if any
 * - category - list of related categories, if any
 *
 * Returns up to 30 posts from the target blog.
 *
 * [subtitle]metaWeblog.getUsersBlogs[/subtitle]
 *
 * An alias for blogger.getUsersBlogs
 *
 * @link http://www.xmlrpc.com/stories/storyReader$2460 Rounding out the MetaWeblog API
 *
 * [subtitle]metaWeblog.getTemplate[/subtitle]
 *
 * An alias for blogger.getTemplate
 *
 * @link http://www.xmlrpc.com/stories/storyReader$2460 Rounding out the MetaWeblog API
 *
 * [subtitle]metaWeblog.newMediaObject[/subtitle]
 *
 * Upload some file to a blog. If successful, the method will return a URL to
 * the newly created file. Otherwise, a fault will be returned.
 *
 * Please note that the data model with this API is to attach
 * files to the blog, where YACS offers to attach files to final pages.
 *
 * Syntax: metaWeblog.newMediaObject(blogid, username, password, struct) returns struct
 *
 * The submitted struct has following attributes:
 * - name - file name
 * - type - media type of the file (e.g., text/html) - can be safely ignored
 * - bits - the base64-encoded contents of the file
 *
 * The returned structure has only one attribute:
 * - url (to be used to access uploaded file)
 *
 *
 * [subtitle]metaWeblog.newPost[/subtitle]
 *
 * Create a new post, and optionally publishes it. If the post is successful,
 * this method will return the id assigned to the post by the server.
 * Otherwise, it will return a fault.
 *
 * Syntax: metaWeblog.newPost(blogid, username, password, struct, publish) returns string
 *
 * Following components of the struct are processed:
 * - title - set the page title
 * - source (a sub-component of the 'description' field) - set the page source, usually, an originating URL
 * - introduction (a sub-component of the 'description' field) - set the page introduction
 * - description - set the actual page content
 * - categories - link this page to listed categories
 *
 * If the publish field is set to TRUE, the publication mechanism of YACS applies.
 *
 * [subtitle]metaWeblog.setTemplate[/subtitle]
 *
 * An alias for blogger.setTemplate
 *
 * @link http://www.xmlrpc.com/stories/storyReader$2460 Rounding out the MetaWeblog API
 *
 *
 * [title]The Blogger API[/title]
 *
 * @link http://www.sixapart.com/developers/xmlrpc/blogger_api/ Six Apart definition of the Blogger API

 * [subtitle]blogger.deletePost[/subtitle]
 *
 * Definitely suppress an existing post. Method will return true if successful
 * or a fault otherwise.
 *
 * Syntax: blogger.deletePost(ignored_appkey, postid, username, password) returns boolean
 *
 *
 * [subtitle]blogger.editPost[/subtitle]
 *
 * Modify or publish an existing post. If the request is successful then the
 * method will return true, otherwise it will return a fault.
 *
 * Syntax: blogger.editPost(ignored_appkey, postid, username, password, content, publish) returns boolean
 *
 * The content field can include following HTML tags:
 * - source - set the page source, usually, an originating URL
 * - introduction - set the page introduction
 *
 * If the publish field is set to TRUE, the publication mechanism YACS is correctly used
 *
 * @link http://www.blogger.com/developers/api/1_docs/xmlrpc_editPost.html blogger.editPost, from Blogger API 1.0
 *
 *
 * [subtitle]blogger.getPost[/subtitle]
 *
 * Read one post.
 *
 * Syntax: blogger.getPost(ignored_appkey, postid, username, password) returns struct
 *
 * Returns following components:
 * - dateCreated - date of last edition
 * - userid - id of last editor
 * - postid - page id
 * - content - the actual page content - with 'introduction' and 'source' sub components
 *
 *
 * [subtitle]blogger.getRecentPosts[/subtitle]
 *
 * Retrieves a list of posts that were created recently in a blog. The results
 * are returned in descending chronolocial order with the most recent post
 * first in the list.
 *
 * Syntax: blogger.getRecentPosts(ignored_appkey, blogid, username, password, numberOfPosts) returns array of structs
 *
 * Returns up to 30 posts of the mentioned blog.
 *
 * Each returned post has following components:
 * - dateCreated - date of last edition
 * - userid - id of last editor
 * - postid - page id
 * - content - the actual page content - with 'introduction' and 'source' sub components
 *
 *
 * [subtitle]blogger.getTemplate[/subtitle]
 *
 * Read one template.
 *
 * Syntax: blogger.getTemplate(ignored_appkey, blogid, username, password, template_type) returns string
 *
 * Template type can be either 'main' or 'archiveIndex'.
 *
 * If the target section does have a template attribute, it is returned to caller.
 * Else a bare and simple template is returned instead.
 *
 * @link http://www.blogger.com/developers/api/1_docs/xmlrpc_getTemplate.html blogger.getTemplate from Blogger API 1.0
 *
 *
 * [subtitle]blogger.getUserInfo[/subtitle]
 *
 * If the user specified by the supplied username and password is found, then
 * the method returns information about that user, specifically: the user’s id,
 * first name, last name, nickname, e-mail address and URL. If the user is not
 * found, or their is an error processing the request then the method will
 * return a fault.
 *
 * Syntax: blogger.getUserInfo(ignored_appkey, username, password) returns an array
 *
 * Returns following attributes:
 * - userid
 * - nickname - the nick name
 * - firstname - actually, the nick name
 * - lastname - actually, the full name
 * - email - the email address
 * - url - the web address
 *
 * @link http://www.blogger.com/developers/api/1_docs/xmlrpc_getUserInfo.html blogger.getUserInfo from Blogger API 1.0
 *
 *
 * [subtitle]blogger.getUsersBlogs[/subtitle]
 *
 * List blogs allowed for one user profile.
 * Assigned sections are listed first, as a convenient mean to access areas with
 * explicit editorial responsibilities. Then up to 9 top-level sections are
 * listed, with up to 9 sub-sections for each of them.
 * This provide a hierarchical view of the site map through XML-RPC.
 * Also, items are numbered to ensure proper sorting of items in w.bloggar.
 *
 * Syntax: blogger.getUsersBlogs(ignored_appkey, username, password) returns an array
 *
 * On success this function returns an array of struct containing following
 * attributes:
 * - url (of the blog/section)
 * - blogid
 * - blogName (actually, the section title)
 *
 * @link http://www.blogger.com/developers/api/1_docs/xmlrpc_getUsersBlogs.html blogger.getUsersBlogs from Blogger API 1.0
 *
 *
 * [subtitle]blogger.newPost[/subtitle]
 *
 * Creates a new post on the designated blog. If the publish parameter is set to
 * true then the post will be published on the weblog as well, otherwise the
 * post will be created and be kept in draft mode, except at auto_publish web
 * spaces. If the post is successfully created, the method will return the id
 * of the newly created post. Otherwise, the method will return a fault.
 *
 * Syntax: blogger.newPost(ignored_appkey, blogid, username, password, content, publish) returns string
 *
 * The content field can include following HTML tags:
 * - source - set the page source, usually, an originating URL
 * - introduction - set the page introduction
 *
 * If the publish field is set to TRUE, the publication mechanism of YACS is correctly used.
 *
 * @link http://www.blogger.com/developers/api/1_docs/xmlrpc_newPost.html blogger.newPost, from Blogger API 1.0
 *
 *
 * [subtitle]blogger.setTemplate[/subtitle]
 *
 * Change one template.
 *
 * Syntax: blogger.setTemplate(ignored_appkey, blogid, username, password, template_content, template_type) returns true or false
 *
 * Template type can be either 'main' or 'archiveIndex'.
 *
 * YACS only supports the type 'index'. Other types are silently ignored.
 *
 * The template is translated as a PHP YACS skin (i.e., [code]template.php[/code] and [code]skin.php[/code])
 * and the skin name is [code]section_&lt;blogid&gt;[/code].
 *
 * The provided template text is saved as a section attribute, for later retrieval
 * through calls to [code]blogger.getTemplate[/code].
 *
 * @link http://www.blogger.com/developers/api/1_docs/xmlrpc_setTemplate.html blogger.setTemplate from Blogger API 1.0
 *
 *
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @tester Marcelo L. L. Cabral
 * @tester Pat
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 *
 * @see services/blog_test.php
 * @see services/configure.php
 *
 */
include_once '../shared/global.php';
include_once '../articles/article.php';
include_once '../categories/categories.php';
include_once '../files/files.php';
include_once '../links/links.php';
include_once '../versions/versions.php';

// at the moment, do not send utf-8 to w.bloggar -- keep unicode entities as-is
if(preg_match('/w\.bloggar/', $_SERVER['HTTP_USER_AGENT']))
	$context['charset'] = 'iso-8859-15';

// load a skin engine
load_skin('services');

// ensure we have some raw content
global $HTTP_RAW_POST_DATA;
if(!isset($HTTP_RAW_POST_DATA))
   $HTTP_RAW_POST_DATA = file_get_contents("php://input");

// save the raw request if debug mode
if(isset($context['debug_blog']) && ($context['debug_blog'] == 'Y'))
	Logger::remember('services/blog.php', 'blog request', rawurldecode($HTTP_RAW_POST_DATA), 'debug');

// load the adequate codec
include_once 'codec.php';
include_once 'xml_rpc_codec.php';
$codec =& new xml_rpc_Codec();

// regular decoding
if(isset($HTTP_RAW_POST_DATA) && $HTTP_RAW_POST_DATA) {
	// parse xml parameters -- use rawurldecode() instead urldecode(), else you will loose + signs
	$result = $codec->import_request(rawurldecode($HTTP_RAW_POST_DATA));
	$status = @$result[0];
	$parameters = @$result[1];

// we also accept REST calls, at least for debugging
} else {
	$status = TRUE;
	$parameters = $_REQUEST;
}

// nothing to do on HEAD --see scripts/validate.php
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
	return;

// nothing to parse
if(!isset($parameters) || !is_array($parameters) || !count($parameters) || !isset($parameters['methodName']) || !$parameters['methodName']) {
	if(isset($context['debug_blog']) && ($context['debug_blog'] == 'Y'))
		Logger::remember('services/blog.php', 'blog request', 'nothing to process', 'debug');

	$response = array('faultCode' => -32700, 'faultString' => 'Empty request, please retry');

// parse has failed
} elseif(!$status)
	$response = array('faultCode' => -32700, 'faultString' => 'Impossible to parse parameters');

// dispatch the request
else {

//	// transcode to our internal charset
//	if($context['charset'] == 'utf-8')
//		$parameters = utf8::decode_recursively($parameters);

	// remember parameters if debug mode
	if($context['debug_blog'] == 'Y')
		Logger::remember('services/blog.php', 'blog '.$parameters['methodName'], isset($parameters['params'])?$parameters['params']:'', 'debug');

	// depending on method name
	switch($parameters['methodName']) {

	// delete a post
	case 'blogger.deletePost':
	case 'metaWeblog.deletePost':
		list($ignored_appkey, $postid, $username, $password) = $parameters['params'];

		// get items from the database
		if($item =& Articles::get($postid))
			$section = Sections::get(str_replace('section:', '', $item['anchor']));

		// check user
		$user = Users::login($username, $password);
		if(!$user || !$user['id'])
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('Please register at %s before blogging'), $context['url_to_home']) );

		// check the article exists
		elseif(!$item)
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('Unknown postid %s at %s'), $postid, $context['url_to_home']) );

		// restrict deletions
		elseif(($user['capability'] != 'A') && !($section && preg_match('/\b('.$user['id'].'|'.$user['nick_name'].')\b/i', $section['editors'])))
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('You are not allowed to delete post %s at %s'), $postid, $context['url_to_home']));

		// delete the article
		elseif(!Articles::delete($postid))
			$response = array( 'faultCode' => -32500, 'faultString' => sprintf(i18n::c('Impossible to delete record of postid %s'), $postid) );

		else
			$response = TRUE;
		break;

	// update the information about an existing post
	case 'blogger.editPost':
		list($ignored_appkey, $postid, $username, $password, $content, $publish) = $parameters['params'];

		// get items from the database
		if($item =& Articles::get($postid))
			$section = Sections::get(str_replace('section:', '', $item['anchor']));

		// check user
		$user = Users::login($username, $password);
		if(!$user || !$user['id'])
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('Please register at %s before blogging'), $context['url_to_home']) );

		// ensure the article actually exists
		elseif(!$item)
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('Unknown postid %s at %s'), $postid, $context['url_to_home']) );

		// restrict posts in protected section
		elseif($section && ($section['active'] == 'N')
			&& ($user['capability'] != 'A') && (!preg_match('/\b('.$user['id'].'|'.$user['nick_name'].')\b/i', $section['editors'])))
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('You are not allowed to post in blog %s at %s'), $blogid, $context['url_to_home']) );

		// restrict changes after publication, if not an associate and not in wiki mode
		elseif(($item['publish_date'] > NULL_DATE) && ($user['capability'] != 'A') && !($section && preg_match('/\b('.$user['id'].'|'.$user['nick_name'].')\b/i', $section['editors'])) && !($context['users_with_auto_publish'] == 'Y'))
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('You are not allowed to change a published post in blog %s at %s'), $blogid, $context['url_to_home']));

		else {
			// remember the previous page version
			if($item['id'])
				Versions::save($item, 'article:'.$item['id']);

			// ensure we are limiting html to non-associates
			if($user['capability'] != 'A')
				$content = strip_tags($content, '<a><b><div><font><i><img><u><strike>'
					.'<introduction><source><title>');

			// parse article content
			$article =& new Article();
			$fields = $article->parse($content, $item);

			// publish if in wiki mode, or if section is configured for auto-publishing,
			// or if the surfer asks for it and add sufficient rights
			if( ($context['users_with_auto_publish'] == 'Y')
				|| ($section && preg_match('/\bauto_publish\b/i', $section['options']))
				|| ($publish && (($user['capability'] == 'A') || ($section && preg_match('/\b('.$user['id'].'|'.$user['nick_name'].')\b/i', $section['editors'])))) ) {
				$fields['publish_name'] = $user['nick_name'];
				$fields['publish_id'] = $user['id'];
				$fields['publish_address'] = $user['email'];
				$fields['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
			}
			$fields['edit_name'] = $user['nick_name'];
			$fields['edit_id'] = $user['id'];
			$fields['edit_address'] = $user['email'];
			$fields['edit_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');

			// update the article
			if(!Articles::put($fields, $user['capability']))
				$response = array( 'faultCode' => -32500, 'faultString' => sprintf(i18n::c('Impossible to update record of postid %s'), $postid) );

			else {
				$response = TRUE;

				// purge section cache
				if($parent = Anchors::get($fields['anchor']))
					$parent->touch('article:update', $article_id, TRUE);

				// if the page has been published
				if($fields['publish_date'] > NULL_DATE) {

					// advertise public pages
					if(($section['active'] == 'Y') && ($item['active'] == 'Y')) {

						// pingback, if any
						Links::ping($fields['introduction'].' '.$fields['source'].' '.$fields['description'], 'article:'.$postid);

					}

					// 'publish' hook
					if(is_callable(array('Hooks', 'include_scripts')))
						Hooks::include_scripts('publish', $item['id']);

				}

				// list the article in categories
				$keywords = '';
				if(isset($fields['tags']))
					$keywords = $fields['tags'];
				if(isset($content['mt_keywords']))
					$keywords .= ', '.$content['mt_keywords'];
				$keywords = trim($keywords, ', ');
				Categories::remember('article:'.$item['id'], isset($fields['publish_date']) ? $fields['publish_date'] : NULL_DATE, $keywords);

			}
		}
		break;

	// get a single post
	case 'blogger.getPost':
		list($ignored_appkey, $postid, $username, $password) = $parameters['params'];

		// get items from the database
		if($item =& Articles::get($postid))
			$section=Sections::get(str_replace('section:', '', $item['anchor']));

		// check user
		$user = Users::login($username, $password);
		if(!$user || !$user['id'])
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('Please register at %s before blogging'), $context['url_to_home']) );

		// check the article actually exists
		elseif(!$item)
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('Unknown postid %s at %s'), $postid, $context['url_to_home']) );

		// restrict gets in protected section
		elseif($section && ($section['active'] == 'N')
			&& ($user['capability'] != 'A') && (!preg_match('/\b('.$user['id'].'|'.$user['nick_name'].')\b/i', $section['editors'])))
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('You are not allowed to post in blog %s at %s'), $blogid, $context['url_to_home']));

		else {

			// page title - don't put a carriage return at the end, w.bloggar will keep it
			$content = '<title>'.$item['title'].'</title>';

			// edit the introduction if one exists
			if($item['introduction'])
				$content .= '<introduction>'.$item['introduction']."</introduction>\n";

			// edit the source if one exists
			if($item['source'])
				$content .= '<source>'.$item['source']."</source>\n";

			// page content
			$content .= $item['description'];

			// build the complete response
			$response = array();
			$response[] = array(
				'dateCreated' => $codec->encode($item['edit_date'], 'date'),
				'userid' => $codec->encode($item['edit_id'], 'string'),
				'postid' => $codec->encode((string)$id, 'string'),
				'content' =>  $codec->encode($content, 'string')
			);
		}
		break;

	// return a list of the most recent posts in the system
	case 'blogger.getRecentPosts':
		list($ignored_appkey, $blogid, $username, $password, $numberOfPosts) = $parameters['params'];

		// check user
		$user = Users::login($username, $password);
		if(!$user || !$user['id'])
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('Please register at %s before blogging'), $context['url_to_home']) );

		// check the section id
		elseif(!Sections::get($blogid))
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('Unknown blog %s at %s'), $blogid, $context['url_to_home']) );

		// list articles
		else {
			$response = array();
			$items = Articles::list_by_edition_date_for_anchor('section:'.$blogid, 0, min($numberOfPosts, 30), 'raw', $user['capability']);
			if(is_array($items)) {
				foreach($items as $id => $item) {

					// page title - don't put a carriage return at the end, w.bloggar will keep it
					$content = '<title>'.$item['title'].'</title>';

					// edit the introduction if one exists
					if($item['introduction'])
						$content .= '<introduction>'.$item['introduction']."</introduction>\n";

					// edit the source if one exists
					if($item['source'])
						$content .= '<source>'.$item['source']."</source>\n";

					// page content
					$content .= $item['description'];

					// build the complete response
					$response[] = array(
						'dateCreated' => $codec->encode($item['edit_date'], 'date'),
						'userid' => $codec->encode($item['edit_id'], 'string'),
						'postid' => $codec->encode((string)$id, 'string'),
						'content' =>  $codec->encode($content, 'string')
					);
				}
			}
		}
		break;

	// return the template attached to a blog
	case 'blogger.getTemplate':
	case 'metaWeblog.getTemplate':
		list($ignored_appkey, $blogid, $username, $password, $type) = $parameters['params'];

		// check user
		$user = Users::login($username, $password);
		if(!$user || !$user['id'])
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('Please register at %s before blogging'), $context['url_to_home']) );

		// check the section id
		elseif(!$section =& Sections::get($blogid))
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('Unknown blog %s at %s'), $blogid, $context['url_to_home']) );

		// restrict access to associates and editors
		elseif(($user['capability'] != 'A') && (!preg_match('/\b('.$user['id'].'|'.$user['nick_name'].')\b/i', $section['editors'])))
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('You are not allowed to get template for blog %s at %s'), $blogid, $context['url_to_home']));

		// provide the existing template
		elseif($section['template'])
			$response = $section['template'];

		// create a dummy template
		else
			$response = '<html><head><title><$BlogTitle$></title></head><body><Blogger><BlogDateHeader><h1><$BlogDateHeaderDate$></h1></BlogDateHeader><$BlogItemBody$><br></Blogger></body></html>';

		// strip DOCTYPE, etc.
		if(preg_match('/<html>.+<\/html>/si', $response, $matches))
			$response = $matches[0];

		break;

	// return information about an author in the system
	case 'blogger.getUserInfo':
		list($ignored_appkey, $username, $password) = $parameters['params'];

		// check user
		$user = Users::login($username, $password);
		if(!$user || !$user['id'])
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('Please register at %s before blogging'), $context['url_to_home']) );

		else {
			$item =& Users::get($username);
			if($item['id'])
				$response = array(
					'userid' => (string)$item['id'],
					'nickname' => $item['nick_name'],
					'firstname' => $item['nick_name'],
					'lastname' => $item['full_name'],
					'email' => $item['email'],
					'url' => ''.$context['url_to_home']
				);
			else
				$response = array( 'faultCode' => -32602, 'faultString' => i18n::s('Unknown user name'));
		}
		break;

	// return a list of weblogs to which an author has posting privileges
	case 'blogger.getUsersBlogs':
	case 'metaWeblog.getUsersBlogs':
		if(isset($parameters['params']) && is_array($parameters['params']))
			list($ignored_appkey, $username, $password) = $parameters['params'];
		else {
			$username = isset($parameters['user'])?$parameters['user']:'';
			$password = isset($parameters['password'])?$parameters['password']:'';
		}

		// check user
		$user = Users::login($username, $password);
		if(!$user || !$user['id'])
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('Please register at %s before blogging'), $context['url_to_home']) );

		// list blogs
		else {
			$response = array();

			// prefix to ensure proper ordering
			$index = 1;

			// list assigned sections, if any
			if(($assigned = Surfer::assigned_sections($user['id'], 9)) && count($assigned)) {
				foreach($assigned as $assigned_id) {
					if($section = Anchors::get('section:'.$assigned_id)) {
						$response[] = array(
							'isAdmin' => '<boolean>1</boolean>',
							'url' => '<string>'.$context['url_to_home'].$context['url_to_root'].Sections::get_url($assigned_id).'</string>',
							'blogid' => '<string>'.(string)$assigned_id.'</string>',
							'blogName' => $codec->encode(sprintf('%1d ', $index).strip_tags($section->get_title()), 'string')
						);
						$index++;
					}
				}
			}

			// can the surfer change the template?
			if(Surfer::is_associate() || ($user['capability'] == 'A'))
				$isAdmin = 1;
			else
				$isAdmin = 0;

			// list top level sections of the site map
			if(($index < 10) && ($items = Sections::list_by_title_for_anchor(NULL, 0, 10-$index, 'raw', $user['capability'])) && is_array($items)) {
				foreach($items as $id => $attributes) {
					$response[] = array(
						'isAdmin' => '<boolean>'.$isAdmin.'</boolean>',
						'url' => '<string>'.$context['url_to_home'].$context['url_to_root'].Sections::get_url($id).'</string>',
						'blogid' => '<string>'.(string)$id.'</string>',
						'blogName' => $codec->encode(sprintf('%1d ', $index).strip_tags($attributes['title']), 'string')
					);

					// add sub-sections, if any
					if($subs =& Sections::list_by_title_for_anchor('section:'.$id, 0, 9, 'raw', $user['capability'])) {
						$sub_index = 1;
						foreach($subs as $sub_id => $attributes) {
							$response[] = array(
								'isAdmin' => '<boolean>'.$isAdmin.'</boolean>',
								'url' => '<string>'.$context['url_to_home'].$context['url_to_root'].Sections::get_url($sub_id).'</string>',
								'blogid' => '<string>'.(string)$sub_id.'</string>',
								'blogName' => $codec->encode(sprintf('%1d.%1d ', $index, $sub_index++).strip_tags($attributes['title']), 'string')
							);
						}
					}

					// next section
					$index++;
				}
			}
		}
		break;

	// create a new post, and optionally publish it
	case 'blogger.newPost':
		list($ignored_appkey, $blogid, $username, $password, $content, $publish) = $parameters['params'];

		// check user
		$user = Users::login($username, $password);
		if(!$user || !$user['id'])
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('Please register at %s before blogging'), $context['url_to_home']) );

		// check the section id
		elseif(!$section =& Sections::get($blogid))
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('Unknown blog %s at %s'), $blogid, $context['url_to_home']) );

		// restrict posts in protected section
		elseif((($section['active'] == 'N') || ($section['locked'] == 'Y'))
			&& ($user['capability'] != 'A') && (!preg_match('/\b('.$user['id'].'|'.$user['nick_name'].')\b/i', $section['editors'])))
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('You are not allowed to post in blog %s at %s'), $blogid, $context['url_to_home']));

		else {
			// ensure we are limiting html to non-associates
			if($user['capability'] != 'A')
				$content = strip_tags($content, '<a><b><div><font><i><img><u><strike>'
					.'<introduction><source><title>');

			// parse article content
			$article =& new Article();
			$fields = $article->parse($content, $content);

			// build fields
			$fields['anchor'] = 'section:'.$blogid;
			$fields['source'] = 'blog';
			$fields['create_name'] = $user['nick_name'];
			$fields['create_id'] = $user['id'];
			$fields['create_address'] = $user['email'];
			$fields['create_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');

			// publish if in wiki mode, or if section is configured for auto-publishing,
			// or if the surfer asks for it and add sufficient rights
			if( ($context['users_with_auto_publish'] == 'Y')
				|| ($section && preg_match('/\bauto_publish\b/i', $section['options']))
				|| ($publish && (($user['capability'] == 'A') || ($section && preg_match('/\b('.$user['id'].'|'.$user['nick_name'].')\b/i', $section['editors'])))) ) {
				$fields['publish_name'] = $user['nick_name'];
				$fields['publish_id'] = $user['id'];
				$fields['publish_address'] = $user['email'];
				$fields['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
			}
			$fields['edit_name'] = $user['nick_name'];
			$fields['edit_id'] = $user['id'];
			$fields['edit_address'] = $user['email'];
			$fields['edit_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');

			// post the article
			if(!$article_id = Articles::post($fields))
				$response = array( 'faultCode' => -32500, 'faultString' => Skin::error_pop());

			else {
				$response = '<string>'.$article_id.'</string>';

				// one post more for this user
				Users::increment_posts($user['id']);

				// purge section cache
				if($parent = Anchors::get($fields['anchor']))
					$parent->touch('article:create', $article_id, TRUE);

				// if the page has been published
				if($fields['publish_date'] > NULL_DATE) {

					// advertise public pages
					if($section['active'] == 'Y') {

						// pingback, if any
						Links::ping($fields['introduction'].' '.$fields['source'].' '.$fields['description'], 'article:'.$article_id);

					}

					// 'publish' hook
					if(is_callable(array('Hooks', 'include_scripts')))
						Hooks::include_scripts('publish', $article_id);

				}

				// list the article in categories
				$keywords = '';
				if(isset($fields['tags']))
					$keywords = $fields['tags'];
				if(isset($content['mt_keywords']))
					$keywords .= ', '.$content['mt_keywords'];
				$keywords = trim($keywords, ', ');
				Categories::remember('article:'.$article_id, isset($fields['publish_date']) ? $fields['publish_date'] : NULL_DATE, $keywords);

			}
		}
		break;

	// set a section template
	case 'blogger.setTemplate':
	case 'metaWeblog.setTemplate':
		list($ignored_appkey, $blogid, $username, $password, $template, $type) = $parameters['params'];

		// check user
		$user = Users::login($username, $password);
		if(!$user || !$user['id'])
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('Please register at %s before blogging'), $context['url_to_home']) );

		// check the section id
		elseif(!$section =& Sections::get($blogid))
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('Unknown blog %s at %s'), $blogid, $context['url_to_home']) );

		// restrict access to associates and editors
		elseif(($user['capability'] != 'A') && (!preg_match('/\b('.$user['id'].'|'.$user['nick_name'].')\b/i', $section['editors'])))
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('You are not allowed to modify blog %s at %s'), $blogid, $context['url_to_home']));

		// we actually process only 'main' type
		elseif($type != 'main')
			$response = array( 'faultCode' => -32602, 'faultString' => i18n::c('Only the main template can be changed'));

		// do the update
		elseif($response = Sections::put_template($blogid, $template))
			$response = array( 'faultCode' => -32602, 'faultString' => $response);
		else
			$response = TRUE;
		break;

	// update the information about an existing post
	case 'metaWeblog.editPost':
		list($postid, $username, $password, $content, $publish) = $parameters['params'];

		// get items from the database
		if($item =& Articles::get($postid))
			$section =& Sections::get(str_replace('section:', '', $item['anchor']));

		// check user
		$user = Users::login($username, $password);
		if(!$user || !$user['id'])
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('Please register at %s before blogging'), $context['url_to_home']) );

		// check the article actually exists
		elseif(!$item)
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('Unknown postid %s at %s'), $postid, $context['url_to_home']) );

		// restrict posts in protected section
		elseif($section && ($section['active'] == 'N')
			&& ($user['capability'] != 'A') && (!preg_match('/\b('.$user['id'].'|'.$user['nick_name'].')\b/i', $section['editors'])))
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('You are not allowed to post in blog %s at %s'), $blogid, $context['url_to_home']));

		// restrict changes after publication
		elseif(($item['publish_date'] > NULL_DATE) && ($user['capability'] != 'A') && !($section && preg_match('/\b('.$user['id'].'|'.$user['nick_name'].')\b/i', $section['editors']) && ($context['users_with_auto_publish'] != 'Y')))
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('You are not allowed to change a published post in blog %s at %s'), $blogid, $context['url_to_home']));

		else {
			// remember the previous page version
			if($item['id'])
				Versions::save($item, 'article:'.$item['id']);

			// ensure we are limiting html to non-associates
			if($user['capability'] != 'A') {
				$content['description'] = strip_tags($content['description'], '<a><b><div><font><i><img><u><strike>'
					.'<introduction><source><title>');
				$content['title'] = strip_tags($content['title'], '<a><b><div><font><i><img><u><strike>'
					.'<introduction><source><title>');
			}

			// parse article content
			$article =& new Article();
			$fields = $article->parse($content['description'], $item);

			if($content['title'])
				$fields['title'] = $content['title'];

			// publish if in wiki mode, or if section is configured for auto-publishing,
			// or if the surfer asks for it and add sufficient rights
			if( ($context['users_with_auto_publish'] == 'Y')
				|| ($section && preg_match('/\bauto_publish\b/i', $section['options']))
				|| ($publish && (($user['capability'] == 'A') || ($section && preg_match('/\b('.$user['id'].'|'.$user['nick_name'].')\b/i', $section['editors'])))) ) {
				$fields['publish_name'] = $user['nick_name'];
				$fields['publish_id'] = $user['id'];
				$fields['publish_address'] = $user['email'];
				$fields['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
			}
			$fields['edit_name'] = $user['nick_name'];
			$fields['edit_id'] = $user['id'];
			$fields['edit_address'] = $user['email'];
			$fields['edit_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');

			// update the article
			if(!Articles::put($fields, $user['capability']))
				$response = array( 'faultCode' => -32500, 'faultString' => sprintf(i18n::c('Impossible to update record of postid %s'), $postid) );

			else {
				$response = TRUE;

				// purge section cache
				if($parent = Anchors::get($fields['anchor']))
					$parent->touch('article:update', $fields['id'], TRUE);

				// if the page has been published
				if($fields['publish_date'] > NULL_DATE) {

					// advertise public pages
					if(($section['active'] == 'Y') && ($item['active'] == 'Y')) {

						// pingback, if any
						Links::ping($fields['introduction'].' '.$fields['source'].' '.$fields['description'], 'article:'.$postid);
					}

					// 'publish' hook
					if(is_callable(array('Hooks', 'include_scripts')))
						Hooks::include_scripts('publish', $item['id']);

				}

				// list the article in categories
				$keywords = '';
				if(isset($fields['tags']))
					$keywords = $fields['tags'];
				if(isset($content['mt_keywords']))
					$keywords .= ', '.$content['mt_keywords'];
				$keywords = trim($keywords, ', ');
				Categories::remember('article:'.$item['id'], isset($fields['publish_date']) ? $fields['publish_date'] : NULL_DATE, $keywords);

			}
		}
		break;

	// return a list of categories for this blog
	case 'metaWeblog.getCategories':
		list($blogid, $username, $password) = $parameters['params'];

		// check user
		$user = Users::login($username, $password);
		if(!$user || !$user['id'])
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('Please register at %s before blogging'), $context['url_to_home']) );

		// list categories
		else {
			$response = array();

			$items = Categories::list_by_path(0, 50, 'raw', $user['capability']);
			if(is_array($items)) {

				// one entry per category
				foreach($items as $id => $attributes) {

					// the category for a human being
					$htmlUrl = $context['url_to_home'].$context['url_to_root'].Categories::get_url($attributes['id'], 'view', $attributes['title']);

					// the category for a robot
					$rssUrl = $context['url_to_home'].$context['url_to_root'].Categories::get_url($id, 'feed');

					// format the response
					$response[] = array(
						'categoryId' => $codec->encode((string)$id, 'string'),
						'categoryName' => $codec->encode(strip_tags($attributes['title']), 'string'),
						'description' => $codec->encode(strip_tags($attributes['title']), 'string'),
						'htmlUrl' => $codec->encode($htmlUrl, 'string'),
						'rssUrl' => $codec->encode($rssUrl, 'string')
					);
				}
			}
		}
		break;

	// get a single post
	case 'metaWeblog.getPost':
		list($postid, $username, $password) = $parameters['params'];

		// get items from the database
		if($item =& Articles::get($postid))
			$section =& Sections::get(str_replace('section:', '', $item['anchor']));

		// check user
		$user = Users::login($username, $password);
		if(!$user || !$user['id'])
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('Please register at %s before blogging'), $context['url_to_home']) );

		// check the article actually exists
		elseif(!$item)
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('Unknown postid %s at %s'), $postid, $context['url_to_home']) );

		// restrict gets in protected section
		elseif($section && ($section['active'] == 'N')
			&& ($user['capability'] != 'A') && (!preg_match('/\b('.$user['id'].'|'.$user['nick_name'].')\b/i', $section['editors'])))
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('You are not allowed to post in blog %s at %s'), $blogid, $context['url_to_home']));

		// fetch the page
		else {
			$response = array();
			$response[] = array(
				'title' =>	$codec->encode($item['title'], 'string'),
				'link' =>  $context['url_to_home'].$context['url_to_root'].Articles::get_url($postid),
				'permaLink' =>	$context['url_to_home'].$context['url_to_root'].Articles::get_url($postid),
				'description' => $codec->encode('<introduction>'.$item['introduction']."</introduction>\n"
						.'<source>'.$item['source']."</source>\n"
						.$item['description'], 'string'),
				'author' => $codec->encode($item['create_address']),
				'comments' =>  $context['url_to_home'].$context['url_to_root'].'comments/edit.php?anchor='.urlencode('article:'.$postid),
				'dateCreated' => $codec->encode($item['edit_date'], 'date'),
				'userid' => (string)$item['edit_id'],
				'postid' => (string)$postid
				);
		}
		break;

	// return a list of the most recent posts in the system
	case 'metaWeblog.getRecentPosts':
		list($blogid, $username, $password, $numberOfPosts) = $parameters['params'];

		// check user
		$user = Users::login($username, $password);
		if(!$user || !$user['id'])
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('Please register at %s before blogging'), $context['url_to_home']) );

		// check the section id
		elseif(!$section =& Sections::get($blogid))
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('Unknown blog %s at %s'), $blogid, $context['url_to_home']) );

		// lists posts
		else {
			$response = array();

			// consider editors as associates
			if(preg_match('/\b('.$user['id'].'|'.$user['nick_name'].')\b/i', $section['editors']))
				$user['capability'] = 'A';

			$items = Articles::list_by_edition_date_for_anchor('section:'.$blogid, 0, min($numberOfPosts, 30), 'raw', $user['capability']);
			if(is_array($items)) {
				foreach($items as $id => $item) {

					// post content
					$entry = array();
					$entry['dateCreated'] = $codec->encode($item['edit_date'], 'date');
					$entry['userid'] = (string)$item['edit_id'];
					$entry['postid'] = (string)$id;
					$entry['title'] = $codec->encode($item['title'], 'string');
					$entry['link'] = $context['url_to_home'].$context['url_to_root'].Articles::get_url($id);
					$entry['permaLink'] = $context['url_to_home'].$context['url_to_root'].Articles::get_url($id);
					$entry['description'] = '';
					if(isset($item['introduction']))
						$entry['description'] .= '<introduction>'.$item['introduction']."</introduction>\n";
					if(isset($item['source']))
						$entry['description'] .= '<source>'.$item['source']."</source>\n";
					if(isset($item['description']))
						$entry['description'] .= $item['description'];
					$entry['description'] = $codec->encode($item['description'], 'string'); // various optimizations
					$entry['author'] = $codec->encode($item['create_address']);
					$entry['comments'] = $context['url_to_home'].$context['url_to_root'].'comments/edit.php?anchor='.urlencode('article:'.$id);
					if(isset($item['publish_date']) && ($item['publish_date'] > NULL_DATE))
						$entry['pubDate'] = $codec->encode($item['publish_date'], 'date');

					// attached categories
					$categories = Members::list_categories_by_title_for_member('article:'.$id, 0, 10, 'raw', $user['capability']);
					foreach($categories as $id => $attributes)
						$entry['categories'][] = strip_tags($attributes['title']);

					// append to the list
					$response[] = $entry;

				}
			}
		}
		break;

	// upload a file
	case 'metaWeblog.newMediaObject':
		list($blogid, $username, $password, $content) = $parameters['params'];

		// check user
		$user = Users::login($username, $password);
		if(!$user || !$user['id'])
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('Please register at %s before blogging'), $context['url_to_home']) );

		// ensure uploads are allowed
		elseif(!Surfer::may_upload($user['capability']))
			$response = array( 'faultCode' => -32602, 'faultString' => i18n::c('You are not allowed to upload files.') );

		// we need some actual content
		elseif(!isset($content['name']) || !$content['name'] || !isset($content['bits']) || !$content['bits'])
			$response = array( 'faultCode' => -32602, 'faultString' => i18n::c('No file data has been received.') );

		// check the section id
		elseif(!$section =& Sections::get($blogid))
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('Unknown blog %s at %s'), $blogid, $context['url_to_home']) );

		// restrict posts in protected section
		elseif((($section['active'] == 'N') || ($section['locked'] == 'Y'))
			&& ($user['capability'] != 'A') && (!preg_match('/\b('.$user['id'].'|'.$user['nick_name'].')\b/i', $section['editors'])))
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('You are not allowed to post in blog %s at %s'), $blogid, $context['url_to_home']));

		// ready to save the file
		else {

			// get a safe path
			$file_path = 'files/'.$context['virtual_path'].'section/'.$section['id'];

			// get a safe file name
			$file_name = utf8::to_ascii(basename($content['name']));

			// save file content
			if(!Safe::file_put_contents($file_path.'/'.$file_name, $content['bits']))
				$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('Impossible to save content of file %s'), $file_name) );

			// also add some file entry
			else {
				$fields = array();

				// build fields
				$fields['file_name'] = $file_name;
				$fields['file_size'] = strlen($content['bits']);
				$fields['anchor'] = 'section:'.$blogid;
				$fields['create_name'] = $user['nick_name'];
				$fields['create_id'] = $user['id'];
				$fields['create_address'] = $user['email'];
				$fields['create_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
				$fields['edit_name'] = $user['nick_name'];
				$fields['edit_id'] = $user['id'];
				$fields['edit_address'] = $user['email'];
				$fields['edit_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');

				// create a file entry in the database, if not a thumbnail
				if(!preg_match('/_tn\./i', $file_name) && (!$file_id = Files::post($fields)))
					$response = array( 'faultCode' => -32500, 'faultString' => Skin::error_pop());

				// provide some file information in response
				else {
					$response = array(
						'file' => $context['path_to_root'].$file_path.'/'.$file_name,
						'url' => $context['url_to_home'].$context['url_to_root'].$file_path.'/'.$file_name,
						'type' => Files::get_mime_type($file_name)
					);
				}

				// increment the post counter of the surfer
				Users::increment_posts($user['id']);
			}
		}
		break;

	// create a new post, and optionally publish it
	case 'metaWeblog.newPost':
		list($blogid, $username, $password, $content, $publish) = $parameters['params'];

		// check user
		$user = Users::login($username, $password);
		if(!$user || !$user['id'])
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('Please register at %s before blogging'), $context['url_to_home']) );

		// check the section id
		elseif(!$section =& Sections::get($blogid))
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('Unknown blog %s at %s'), $blogid, $context['url_to_home']) );

		// restrict posts in protected section
		elseif((($section['active'] == 'N') || ($section['locked'] == 'Y'))
			&& ($user['capability'] != 'A') && (!preg_match('/\b('.$user['id'].'|'.$user['nick_name'].')\b/i', $section['editors'])))
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('You are not allowed to post in blog %s at %s'), $blogid, $context['url_to_home']));

		else {
			// ensure we are limiting html to non-associates
			if($user['capability'] != 'A') {
				$content['description'] = strip_tags($content['description'], '<a><b><div><font><i><img><u><strike>'
					.'<introduction><source><title>');
				$content['title'] = strip_tags($content['title'], '<a><b><div><font><i><img><u><strike>'
					.'<introduction><source><title>');
			}

			// parse article content
			$article =& new Article();
			$fields = $article->parse($content['description'], $content);

			// creation date
			if(isset($content['dateCreated']) && $content['dateCreated'])
				$stamp = strftime('%Y-%m-%d %H:%M:%S', $content['dateCreated']);
			else
				$stamp = gmstrftime('%Y-%m-%d %H:%M:%S');

			// build fields
			$fields['title'] = $content['title'];
			$fields['anchor'] = 'section:'.$blogid;
			$fields['create_name'] = $user['nick_name'];
			$fields['create_id'] = $user['id'];
			$fields['create_address'] = $user['email'];
			$fields['create_date'] = $stamp;

			// article has been explicitly tagged (XML-RPC spec)
			if(isset($content['category'])) {
				if(!isset($fields['tags']))
					$fields['tags'] = '';
				if($fields['tags'])
					$fields['tags'] .= ', ';
				$fields['tags'] .= $content['category'];
			}

			// publish if in wiki mode, or if section is configured for auto-publishing,
			// or if the surfer asks for it and add sufficient rights
			if( ($context['users_with_auto_publish'] == 'Y')
				|| ($section && preg_match('/\bauto_publish\b/i', $section['options']))
				|| ($publish && (($user['capability'] == 'A') || ($section && preg_match('/\b('.$user['id'].'|'.$user['nick_name'].')\b/i', $section['editors'])))) ) {
				$fields['publish_name'] = $user['nick_name'];
				$fields['publish_id'] = $user['id'];
				$fields['publish_address'] = $user['email'];
				$fields['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
			}
			$fields['edit_name'] = $user['nick_name'];
			$fields['edit_id'] = $user['id'];
			$fields['edit_address'] = $user['email'];
			$fields['edit_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');

			// post the article
			if(!$article_id = Articles::post($fields))
				$response = array( 'faultCode' => -32500, 'faultString' => Skin::error_pop());

			else {
				$response = '<string>'.$article_id.'</string>';

				// increment the post counter of the surfer
				Users::increment_posts($user['id']);

				// purge section cache
				if($parent = Anchors::get($fields['anchor']))
					$parent->touch('article:create', $article_id, TRUE);

				// if the page has been published
				if($fields['publish_date'] > NULL_DATE) {

					// advertise public pages
					if($section['active'] == 'Y') {

						// places to look for references
						$to_be_parsed = '';
						if(isset($fields['introduction']))
							$to_be_parsed .= $fields['introduction'].' ';
						if(isset($fields['source']))
							$to_be_parsed .= $fields['source'].' ';
						if(isset($fields['description']))
							$to_be_parsed .= $fields['description'].' ';

						// pingback, if any
						Links::ping($to_be_parsed, 'article:'.$article_id);
					}

					// 'publish' hook
					if(is_callable(array('Hooks', 'include_scripts')))
						Hooks::include_scripts('publish', $article_id);

				}

				// list the article in categories
				$keywords = '';
				if(isset($fields['tags']))
					$keywords = $fields['tags'];
				if(isset($content['mt_keywords']))
					$keywords .= ', '.$content['mt_keywords'];
				$keywords = trim($keywords, ', ');
				Categories::remember('article:'.$article_id, isset($fields['publish_date']) ? $fields['publish_date'] : NULL_DATE, $keywords);

			}
		}
		break;

	// return a list of categories for this blog
	case 'mt.getCategoryList':
		list($blogid, $username, $password) = $parameters['params'];

		// check user
		$user = Users::login($username, $password);
		if(!$user || !$user['id'])
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('Please register at %s before blogging'), $context['url_to_home']) );

		// list categories
		else {
			$response = array();

			$items = Categories::list_by_path(0, 50, 'raw', $user['capability']);
			if(is_array($items)) {

				// one entry per category
				foreach($items as $id => $attributes) {

					// the category for a human being
					$htmlUrl = $context['url_to_home'].$context['url_to_root'].Categories::get_url($attributes['id'], 'view', $attributes['title']);

					// the category for a robot
					$rssUrl = $context['url_to_home'].$context['url_to_root'].Categories::get_url($id, 'feed');

					// format the response
					$response[] = array(
						'categoryId' => (string)$id,
						'categoryName' => $codec->encode(strip_tags($attributes['title']), 'string')
					);
				}
			}
		}
		break;

	// return a list of categories to which the post is assigned
	case 'mt.getPostCategories':
		list($postid, $username, $password) = $parameters['params'];

		// check user
		$user = Users::login($username, $password);
		if(!$user || !$user['id'])
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('Please register at %s before blogging'), $context['url_to_home']) );

		// list categories
		else {
			$response = array();

			$items = Members::list_categories_by_title_for_member('article:'.$postid, 0, 7, 'raw', $user['capability']);
			if(is_array($items)) {

				// one entry per category
				$isPrimary = 1;
				foreach($items as $id => $attributes) {

					// format the response
					$response[] = array(
						'categoryId' => '<string>'.(string)$id.'</string>',
						'categoryName' => '<string>'.$codec->encode(strip_tags($attributes['title']), 'string').'</string>',
						'isPrimary' => '<boolean>'.$isPrimary.'</boolean>'
					);

					$isprimary = 0;
				}
			}
		}
		break;

	// return a bandwidth-friendly list of the most recent posts in the system
	case 'mt.getRecentPostTitles':
		list($blogid, $username, $password, $numberOfPosts) = $parameters['params'];

		// check user
		$user = Users::login($username, $password);
		if(!$user || !$user['id'])
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('Please register at %s before blogging'), $context['url_to_home']) );

		// check the section id
		elseif(!$section =& Sections::get($blogid))
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('Unknown blog %s at %s'), $blogid, $context['url_to_home']) );

		// lists posts
		else {
			$response = array();

			// consider editors as associates
			if(preg_match('/\b('.$user['id'].'|'.$user['nick_name'].')\b/i', $section['editors']))
				$user['capability'] = 'A';

			$items = Articles::list_by_edition_date_for_anchor('section:'.$blogid, 0, min($numberOfPosts, 30), 'raw', $user['capability']);
			if(is_array($items)) {
				foreach($items as $id => $item) {

					$entry = array();
					$entry['dateCreated'] = $codec->encode($item['edit_date'], 'date');
					$entry['userid'] = (string)$item['edit_id'];
					$entry['postid'] = (string)$id;
					$entry['title'] = $codec->encode($item['title'], 'string');

					$response[] = $entry;

				}
			}
		}
		break;

	// list pages linked to this post
	case 'mt.getTrackbackPings':
		list($postid) = $parameters['params'];

		$response = array();
		break;

	// publish a post
	case 'mt.publishPost':
		list($postid, $username, $password) = $parameters['params'];

		$response = '<boolean>1</boolean>';
		break;

	// assign categories to a post
	case 'mt.setPostCategories':
		list($postid, $username, $password, $categories) = $parameters['params'];

		// check user
		$user = Users::login($username, $password);
		if(!$user || !$user['id'])
			$response = array( 'faultCode' => -32602, 'faultString' => sprintf(i18n::c('Please register at %s before blogging'), $context['url_to_home']) );

		// set categories
		else {
			$response = '<boolean>1</boolean>';

			// actual processing of provided categories
			if(isset($categories) && is_array($categories)) {
				foreach($categories as $category) {
					foreach($category as $name => $value) {
						if($name == 'categoryId')
							if($error = Members::assign('category:'.$value, 'article:'.$postid))
								$response = array( 'faultCode' => -32602, 'faultString' => $error );
					}
				}
			}

		}
		break;

	// information about the XML-RPC methods supported by the server
	case 'mt.supportedMethods':

		$response = array(
			'blogger.deletePost',
			'blogger.editPost',
			'blogger.getPost',
			'blogger.getRecentPosts',
			'blogger.getTemplate',
			'blogger.getUserInfo',
			'blogger.getUsersBlogs',
			'blogger.newPost',
			'blogger.setTemplate',
			'metaWeblog.deletePost',
			'metaWeblog.editPost',
			'metaWeblog.getCategories',
			'metaWeblog.getPost',
			'metaWeblog.getRecentPosts',
			'metaWeblog.getTemplate',
			'metaWeblog.getUsersBlogs',
			'metaWeblog.newMediaObject',
			'metaWeblog.newPost',
			'metaWeblog.setTemplate',
			'mt.getCategoryList',
			'mt.getPostCategories',
			'mt.getRecentPostTitles',
			'mt.getTrackbackPings',
			'mt.publishPost',
			'mt.setPostCategories',
			'mt.supportedMethods'
		);

		break;

	default:
		$response = array('faultCode' => -32601, 'faultString' => sprintf(i18n::s('Do not know how to process %s'), $parameters['methodName']));
		Logger::remember('services/blog.php', 'unsupported methodName', $parameters['methodName'], 'debug');
	}
}

// no response yet
if(!isset($response) || !(is_array($response) || $response))
	$response = array('faultCode' => -32603, 'faultString' => 'no response');

// build a XML snippet
$result = $codec->export_response($response);
$status = @$result[0];
$response = @$result[1];

// handle the output correctly
render_raw('text/xml; charset='.$context['charset']);

// actual transmission except on a HEAD request
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
	echo $response;

// save the response if debug mode
if(isset($context['debug_blog']) && ($context['debug_blog'] == 'Y'))
	Logger::remember('services/blog.php', 'blog response', $response, 'debug');

	// something has been buffered
	if(is_callable('ob_get_length') && ob_get_length()) {

		// ensure everything has been sent to the browser
		if(is_callable('ob_end_flush'))
			while(@ob_end_flush());
	}

// the post-processing hook
finalize_page();

?>