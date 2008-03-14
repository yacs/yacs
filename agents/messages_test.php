<?php
/**
 * command line test program
 *
 * Use this script to validate the processing of (filed) complex messages.
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
include_once '../shared/global.php';

// load some skin library
load_skin();

// load one test message (check the directory to see what's available)
$message = '';

// comment out following lines before validating scripts at the reference server
//$message = Safe::file_get_contents('messages/message_photo_to_resize_from_notes');
//$message = Safe::file_get_contents('messages/message_sample_structure');
//$message = Safe::file_get_contents('messages/text_excel_RFC822.eml'); // with a header and an attached file
//$message = Safe::file_get_contents('messages/message_2_images'); // attach two images

// process one message
if($teaser = substr($message, 0, 2048)) {
	Logger::debug($teaser, 'processing');

	// build a fake queue
	$context['mail_queue'] = array(
		'localhost', // server
		'nobody', // account
		'nopassword', // password
		'anyone', // allowed
		'', // match
		'', // section
		'no_reply', // options
		'', // hooks
		'', // prefix
		''	// suffix
		);
	Logger::debug($context['mail_queue'], 'mail queue');

	// debug processing
	$context['debug_messages'] = 'Y';
	include_once 'messages.php';
	Messages::process_entity(NULL, $message);
}

?>