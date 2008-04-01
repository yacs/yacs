<?php
/**
 * fetch the vCard of one user
 *
 * Export some attributes of the user profile.
 * This script is useful to catch user info in an external system such as a Palm OS or a Pocket PC.
 *
 * Example of data formatted by this script:
 * [snippet]
 * BEGIN:VCARD
 * VERSION:2.1
 * FN:Foo Bar
 * N:Bar;Foo
 * NICKNAME:little_foo
 * EMAIL;PREF;INTERNET:foo.bar@acme.com
 * REV:20040922T000712Z
 * END:VCARD
 * [/snippet]
 *
 * If following features are enabled, this script will use them:
 * - compression - using gzip
 * - cache - supported through ETag and Last-Modified, not mentioning the setting of Content-Length;
 * Also, Cache-Control enables caching for some time, even through HTTPS
 *
 * Restrictions apply on this page:
 * - associates are allowed to move forward
 * - access is restricted ('active' field == 'R'), but the surfer is an authenticated member
 * - public access is allowed ('active' field == 'Y') and the surfer has been logged
 * - permission denied is the default
 *
 * Accept following invocations:
 * - fetch_vcard.php/12
 * - fetch_vcard.php?id=12
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Users::get($id);

// reorder tokens of full name on comma
if(preg_match('/^(.+),\s+(.+)$/', $item['full_name'], $matches))
	$item['full_name'] = $matches[2].' '.$matches[1];

// associates can do what they want
if(Surfer::is_associate())
	$permitted = TRUE;

// access is restricted to authenticated member
elseif(($item['active'] == 'R') && Surfer::is_member())
	$permitted = TRUE;

// public access is allowed to logged members
elseif(($item['active'] == 'Y') && Surfer::is_member())
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// load localized strings
i18n::bind('users');

// load the skin
load_skin('users');

// the path to this page
$context['path_bar'] = array( 'users/' => i18n::s('Users') );

// the title of the page
if($item['full_name'])
	$context['page_title'] = $item['full_name'];
elseif($item['nick_name'])
	$context['page_title'] = $item['nick_name'];
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
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Users::get_url($item['id'], 'fetch_vcard')));

	// permission denied to authenticated user
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// display the user profile
} else {

	// format name components
	$names = $item['full_name'];
	if(preg_match('/^(.+)\s(.+?)$/', $names, $matches))
		$names = $matches[2].';'.$matches[1];

	// build the vCard content
	$text = 'BEGIN:VCARD'."\x0D\x0A"
		.'VERSION:2.1'."\x0D\x0A"
		.'FN:'.$item['full_name']."\x0D\x0A"
		.'N:'.$names."\x0D\x0A"
		.'NICKNAME:'.$item['nick_name']."\x0D\x0A"
		.'EMAIL;PREF;INTERNET:'.$item['email']."\x0D\x0A"
		.'REV:'.date('Ymd\THis\Z', SQL::strtotime($item['edit_date']))."\x0D\x0A"
		.'END:VCARD'."\x0D\x0A";

	//
	// transfer to the user agent
	//

	// no encoding, no compression and no yacs handler...
	if(!headers_sent()) {
		Safe::header('Content-Type: text/x-vcard');
		Safe::header('Content-Transfer-Encoding: binary');
		Safe::header('Content-Length: '.strlen($text));
	}

	// suggest a download
	if(!headers_sent()) {
		$file_name = utf8::to_ascii(Skin::strip($context['page_title'], 5).'.vcf');
		Safe::header('Content-Disposition: attachment; filename="'.$file_name.'"');
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

// render the skin
render_skin();

?>