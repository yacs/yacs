<?php
/**
 * convert legacy queued messages to digest format
 *
 * This one-shot script upgrades pending rows of the messages queue from the
 * legacy format (fully assembled MIME message) to the digest format (bare
 * HTML content), so that tick_hook() can group them per recipient.
 *
 * Only watcher notifications are converted, that is, messages that embed the
 * standard notification trail ('...part of your watch list...'). Other queued
 * messages (personal mails, logger reports) are left untouched and will be
 * transmitted as usual.
 *
 * By default the script only previews what would be done. Add the parameter
 * 'confirm=Y' (or the argument 'confirm' on the command line) to actually
 * update the database.
 *
 * Usage from the browser (associates only):
 *   tools/mail_queue_to_digest.php
 *   tools/mail_queue_to_digest.php?confirm=Y
 *
 * Usage from the command line:
 *   php tools/mail_queue_to_digest.php
 *   php tools/mail_queue_to_digest.php confirm
 *
 * @see shared/mailer.php
 *
 * @author Christian Loubechine
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// this script is for associates and command line only
if((php_sapi_name() != 'cli') && !Surfer::is_associate()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	die(i18n::s('You are not allowed to perform this operation.'));
}

// ensure the skin has been loaded, MAIL_FONT_PREFIX is defined there
load_skin('tools');

// do it for real, or just preview
$confirmed = FALSE;
if(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'Y'))
	$confirmed = TRUE;
if((php_sapi_name() == 'cli') && isset($_SERVER['argv']) && in_array('confirm', $_SERVER['argv']))
	$confirmed = TRUE;

// plain text output
if(php_sapi_name() != 'cli')
	Safe::header('Content-Type: text/plain; charset=utf-8');

/**
 * remove the notification trail, wherever the site name changed since queueing
 *
 * The trail template comes from the i18n localized string of build_notification(),
 * where the site name placeholder is turned to a wildcard.
 *
 * @param string notification content (HTML)
 * @return mixed the content without its trail, or FALSE if no trail has been found
 */
function strip_trail($content) {

	// reason 1 - you are watching some container --the only trail used for bulk notifications
	$template = i18n::c('This message has been generated automatically by %s since the new item has been posted in a web space that is part of your watch list. If you wish to stop some notifications please review watched elements listed in your user profile.');

	// the site name may have changed since the message has been queued
	$pattern = '#'.str_replace('%s', '.*?', preg_quote('<p>&nbsp;</p><p>'.$template.'</p>', '#')).'#s';

	$stripped = preg_replace($pattern, '', $content, 1, $count);
	if(!$count)
		return FALSE;
	return $stripped;
}

/**
 * extract the bare notification content out of one assembled MIME message
 *
 * @param array one row of the messages queue
 * @return string the bare HTML content, or FALSE if this is not a convertible notification
 */
function extract_bare_content($item) {
	global $context;

	// locate the text/html part directly, wherever it sits in the MIME structure
	// --messages with attachments embed multipart/alternative in multipart/related
	if(!preg_match('#\r?\nContent-Type: text/html[^\r\n]*\r?\nContent-Transfer-Encoding: quoted-printable\r?\n\r?\n(.*?)\r?\n--#s', $item['message'], $matches))
		return FALSE;
	$content = quoted_printable_decode($matches[1]);

	// attached images cannot be restored, drop their inline references
	$content = preg_replace('/<img [^>]*src="cid:[^>]*>/i', '', $content);

	// remove the overall html envelope set by build_multipart()
	if(!preg_match('#^<html><body>(.*)</body></html>$#s', trim($content), $matches))
		return FALSE;
	$content = $matches[1];

	// remove the font decoration
	if(!strncmp($content, MAIL_FONT_PREFIX, strlen(MAIL_FONT_PREFIX)) && (substr($content, -strlen(MAIL_FONT_SUFFIX)) == MAIL_FONT_SUFFIX))
		$content = substr($content, strlen(MAIL_FONT_PREFIX), -strlen(MAIL_FONT_SUFFIX));

	// remove the mail template set by build_mail_message() --the digest will wrap items again
	$content = trim($content);
	if(preg_match('#^<table [^>]+><tr><td [^>]+><font [^>]+>(.*)</font></td></tr></table>$#s', $content, $matches))
		$content = $matches[1];

	// only proven watcher notifications are converted --the trail is the signature
	if(($stripped = strip_trail($content)) === FALSE)
		return FALSE;

	return trim($stripped);
}

// scan legacy rows only
$query = "SELECT * FROM ".SQL::table_name('messages')." WHERE digest='N' ORDER BY id";
if(!$result = SQL::query($query))
	die('No messages table, or empty queue'."\n");

$converted = 0;
$skipped = 0;
while($item = SQL::fetch($result)) {

	// extract the bare content, or leave the row untouched
	if(($content = extract_bare_content($item)) === FALSE) {
		$skipped++;
		continue;
	}

	// decode the subject line --it has been MIME-encoded by encode_subject()
	$subject = iconv_mime_decode($item['subject'], ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
	if(!$subject)
		$subject = $item['subject'];

	// remove the site name suffix, it is appended again at delivery time
	$suffix = ' ['.$context['site_name'].']';
	if(substr($subject, -strlen($suffix)) == $suffix)
		$subject = substr($subject, 0, -strlen($suffix));

	// update this row
	if($confirmed) {
		$query = "UPDATE ".SQL::table_name('messages')." SET"
			." content='".SQL::escape($content)."',"
			." digest='Y',"
			." subject='".SQL::escape($subject)."',"
			." message='',"
			." headers=''"
			." WHERE id = ".$item['id'];
		SQL::query($query);
	}

	$converted++;
}

// report on work achieved
echo ($confirmed?'Converted: ':'Would convert: ').$converted."\n";
echo 'Left untouched: '.$skipped."\n";
if(!$confirmed)
	echo 'This was a preview, add confirm=Y (or the argument "confirm" on the command line) to update the database'."\n";
