<?php
/**
 * fetch calendar data
 *
 * Example of data formatted by this script:
 * [snippet]
 * BEGIN:VCALENDAR
 * VERSION:2.0
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
 * - fetch_ics.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../dates/dates.php';

// load the skin
load_skin('dates');

// the path to this page
$context['path_bar'] = array( 'users/' => i18n::s('Dates') );

// the title of the page
$context['page_title'] = i18n::s('Dates');

// list upcoming events
$text = Dates::list_future_for_anchor(NULL, 0, 100, 'ics');

// no encoding, no compression and no yacs handler...
if(!headers_sent()) {
	Safe::header('Content-Type: text/calendar');
	Safe::header('Content-Transfer-Encoding: binary');
	Safe::header('Content-Length: '.strlen($text));
}

// suggest a download
if(!headers_sent()) {
	$file_name = utf8::to_ascii(Skin::strip($context['page_title'], 5).'.ics');
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

?>