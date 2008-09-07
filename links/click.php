<?php
/**
 * redirect to one external link
 *
 * This script actually counts the hit then redirect the browser to an external page.
 *
 * Permission to access the link is denied if the anchor is not viewable by this surfer.
 *
 * Example:
 * [code]links/click.php?url=www.cisco.com[/code]
 *
 * If the anchor for this item specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Cyril Blondin
 * @tester Mordread Wallas
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'links.php';

// get target url
$url = NULL;
if(isset($_REQUEST['url']))
	$url = $_REQUEST['url'];
elseif(isset($context['arguments'][0]))
	$url = $context['arguments'][0];

// decode the target url
$url = trim(str_replace('&amp;', '&', rawurldecode($url)));

// avoid dangerous strings
$url = preg_replace(FORBIDDEN_IN_URLS, '', strip_tags($url));

// fix relative path
if($url && !preg_match('/^(\/|\w+:)/i', $url)) {

	// external ftp server
	if(preg_match('/^ftp\./', $url))
		$url = 'ftp://'.$url;

	// external irc server
	elseif(preg_match('/^irc\./', $url))
		$url = 'irc://'.$url;

	// external news server
	elseif(preg_match('/^(news|nntp)\./', $url))
		$url = 'news://'.$url;

	// external web server
	elseif(preg_match('/^www\./', $url))
		$url = 'http://'.$url;

	// internal address
	else
		$url = $context['url_to_root'].$url;
}

// remove last slash, if any, to normalize links in database
$url = rtrim($url, '/');

// get the item from the database
$item =& Links::get($url);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor =& Anchors::get($item['anchor']);

// the anchor has to be viewable by this surfer
if(is_object($anchor) && !$anchor->is_viewable())
	$permitted = FALSE;
else
	$permitted = TRUE;

// load the skin, maybe with a variant
load_skin('links', $anchor);

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'links/' => i18n::s('Links') );

// the title of the page
$context['page_title'] = $url;

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// do we have something?
} elseif(!$url)
	Skin::error(i18n::s('No link URL has been provided.'));

// do not record click not coming from this site
elseif(!isset($_SERVER['HTTP_REFERER']) || !preg_match('/^'.preg_quote($context['url_to_home'], '/').'\b/', $_SERVER['HTTP_REFERER']))
	Safe::redirect($url);

// increment hits for this link and redirect if no error
else if($error = Links::click($url))
	Skin::error($error);
else
	Safe::redirect($url);

// failed operation
$context['text'] .= '<p>'.i18n::s('Click has not been recorded.').'</p>';

// render the skin
render_skin();

?>