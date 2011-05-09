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
 * Accept following invocations:
 * - fetch_ics.php/article/&lt;id&gt;
 * - fetch_ics.php?id=&lt;article:id&gt;
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../../shared/global.php';
include_once '../../overlays/overlay.php';

// look for the id --actually, a reference
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]) && isset($context['arguments'][1]))
	$id = $context['arguments'][0].':'.$context['arguments'][1];
$id = strip_tags($id);

// get the anchor
$anchor =& Anchors::get($id);

// get the related overlay, if any
$overlay = NULL;
if(is_object($anchor)) {
	$fields = array();
	$fields['id'] = $anchor->get_value('id');
	$fields['overlay'] = $anchor->get_value('overlay');
	$overlay = Overlay::load($fields, $anchor->get_reference());
}

// load the skin, maybe with a variant
load_skin('articles', $anchor);

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// not found
} elseif(!is_object($anchor)) {
	include '../../error.php';

// permission denied
} elseif(!$anchor->is_viewable() || !is_object($overlay)) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// an error occured
} elseif(count($context['error']))
	;

// provide ics data
else {

	// begin calendar
	$text = 'BEGIN:VCALENDAR'.CRLF
		.'VERSION:2.0'.CRLF
		.'PRODID:YACS'.CRLF
		.'METHOD:PUBLISH'.CRLF; // required by Outlook

	// organization, if any
	if(isset($context['site_name']) && $context['site_name'])
		$text .= 'X-WR-CALNAME:'.$context['site_name'].CRLF;

	// begin event
	$text .= 'BEGIN:VEVENT'.CRLF;

	// the event spans limited time --duration is expressed in minutes
	if(isset($overlay->attributes['duration']) && $overlay->attributes['duration']) {
		$text .= 'DTSTART:'.gmdate('Ymd\THis\Z', SQL::strtotime($overlay->attributes['date_stamp'])).CRLF;
		$text .= 'DTEND:'.gmdate('Ymd\THis\Z', SQL::strtotime($overlay->attributes['date_stamp'])+($overlay->attributes['duration']*60)).CRLF;

	// a full-day event
	} else {
		$text .= 'DTSTART;VALUE=DATE:'.date('Ymd', SQL::strtotime($overlay->attributes['date_stamp'])).CRLF;
		$text .= 'DTEND;VALUE=DATE:'.date('Ymd', SQL::strtotime($overlay->attributes['date_stamp'])+86400).CRLF;
	}

	// url to view the date
	$text .= 'URL:'.$context['url_to_home'].$context['url_to_root'].$anchor->get_url().CRLF;;

	// organization, if any
	if($value = $anchor->get_value('introduction'))
		$text .= 'DESCRIPTION:'.str_replace(array("\n", "\r"), ' ', strip_tags($value)).CRLF;

	// build a valid title
	if($value = $anchor->get_title())
		$text .= 'SUMMARY:'.Codes::beautify_title($value).CRLF;

	// required by Outlook 2003
	$text .= 'UID:'.$anchor->get_reference().'-'.$context['host_name'].CRLF;

	// date of creation
	if($value = $anchor->get_value('create_date'))
		$text .= 'CREATED:'.gmdate('Ymd\THis\Z', SQL::strtotime($value)).CRLF;

	// date of last modification --also required by Outlook
	if($value = $anchor->get_value('edit_date'))
		$text .= 'DTSTAMP:'.gmdate('Ymd\THis\Z', SQL::strtotime($value)).CRLF;

	// close event
	$text .= 'SEQUENCE:0'.CRLF
		.'END:VEVENT'.CRLF;

	// close calendar
	$text .= 'END:VCALENDAR'.CRLF;

	// no encoding, no compression and no yacs handler...
	if(!headers_sent()) {
		Safe::header('Content-Type: text/calendar');
		Safe::header('Content-Transfer-Encoding: binary');
		Safe::header('Content-Length: '.strlen($text));
	}

	// suggest a download
	if(!headers_sent()) {
		$file_name = utf8::to_ascii(Skin::strip($anchor->get_title(), 5).'.ics');
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

// page title
if(is_object($anchor))
	$context['page_title'] = $anchor->get_title();

// render the skin
render_skin();

?>
