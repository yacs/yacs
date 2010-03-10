<?php
/**
 * fetch a business card
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
 * @link http://www.imc.org/pdi/vcard-21.txt
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
 * @author Bernard Paques
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

// load the skin
load_skin('users');

// the path to this page
$context['path_bar'] = array( 'users/' => i18n::s('People') );

// the title of the page
if($item['full_name'])
	$context['page_title'] = $item['full_name'];
elseif($item['nick_name'])
	$context['page_title'] = $item['nick_name'];

// not found
if(!isset($item['id'])) {
	include '../error.php';

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Users::get_url($item['id'], 'fetch_vcard')));

	// permission denied to authenticated user
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// display the user profile
} else {

	// format name components
	$names = $item['full_name'];
	if(preg_match('/^(.+)\s(.+?)$/', $names, $matches))
		$names = $matches[2].';'.$matches[1];

	// build the vCard
	$text = 'BEGIN:VCARD'.CRLF
		.'VERSION:2.1'.CRLF
		.'FN:'.$item['full_name'].CRLF
		.'N:'.$names.CRLF
		.'NICKNAME:'.$item['nick_name'].CRLF;

	// organization, if any
	if(isset($item['vcard_organization']) && $item['vcard_organization'])
		$text .= 'ORG:'.$item['vcard_organization'].CRLF;

	// title, if any
	if(isset($item['vcard_title']) && $item['vcard_title'])
		$text .= 'TITLE:'.$item['vcard_title'].CRLF;

	// physical address, if any
	if(isset($item['vcard_label']) && $item['vcard_label'])
		$text .= 'LABEL:'.str_replace(array("\r", "\n"), array('', ';'), $item['vcard_label']).CRLF;

	// phone number, if any
	if(isset($item['phone_number']) && $item['phone_number'])
		$text .= 'TEL;PREF:'.$item['phone_number'].CRLF;

	// alternate number, if any
	if(isset($item['alternate_number']) && $item['alternate_number'])
		$text .= 'TEL;MSG:'.$item['alternate_number'].CRLF;

	// web mail, if any
	if(isset($item['email']) && $item['email'])
		$text .= 'EMAIL;PREF;INTERNET:'.$item['email'].CRLF;

	// web address, if any
	if(isset($item['web_address']) && $item['web_address'])
		$text .= 'ORG:'.$item['web_address'].CRLF;

	// birth date, if any
	if(isset($item['birth_date']) && $item['birth_date'])
		$text .= 'BDAY:'.substr($item['birth_date'], 0, 10).CRLF;

	// agent, if any -- not accepted by Palm Desktop :-(
// 	if(isset($item['vcard_agent']) && $item['vcard_agent'] && ($agent =& Users::get($item['vcard_agent']))) {
// 		$text .= 'AGENT:'."\x0D\x0A"
// 			.'BEGIN:VCARD'."\x0D\x0A"
// 			.'VERSION:2.1'."\x0D\x0A"
// 			.'FN:'.$agent['full_name']."\x0D\x0A"
// 			.'NICKNAME:'.$agent['nick_name']."\x0D\x0A";

// 		// phone number, if any
// 		if(isset($agent['phone_number']) && $agent['phone_number'])
// 			$text .= 'TEL;PREF:'.$agent['phone_number']."\x0D\x0A";

// 		// alternate number, if any
// 		if(isset($agent['alternate_number']) && $agent['alternate_number'])
// 			$text .= 'TEL;MSG:'.$agent['alternate_number']."\x0D\x0A";

// 		// web mail, if any
// 		if(isset($agent['email']) && $agent['email'])
// 			$text .= 'EMAIL;PREF;INTERNET:'.$agent['email']."\x0D\x0A";

// 		$text .= 'END:VCARD'."\x0D\x0A";
// 	}

	// date of last update
	$text .= 'REV:'.date('Ymd\THis\Z', SQL::strtotime($item['edit_date'])).CRLF
		.'END:VCARD'.CRLF;

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

// render the skin
render_skin();

?>