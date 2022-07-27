<?php
/**
 * create a new letter
 *
 * @todo allow to create a newsletter from a section or sub-section (Jan)
 * @todo allow for customizable templates (Jan)
 * @todo allow for pre-defined lists of recipients (Jan)
 * @todo automate the process (Jan)
 * @todo help: explain that one message will be sent per recipient
 *
 * This script fills a form to prepare the letter, then send it by e-mail to targeted recipients.
 *
 * This allows for the preparation:
 * - of digests - YACS list published articles since the previous digest
 * - of release - YACS lists featured pages
 * - of announcement - type any text and hit the submit button
 *
 * This script builds a digest of most recent articles, and it's up to the writer to change its content
 * based on previous sending.
 *
 * Restricted pages are flagged as such in the digest. Regular members will have to login in order to read them.
 * Note that hidden pages (i.e., accessible only to associates) are not listed in the digest.
 *
 * This newsletter can be sent either:
 * - to all subscribers of the community who have explicitly subscribed for this service,
 * - to all members of the community who have explicitly subscribed for this service,
 * - to all associates,
 * - or to any custom address typed manually
 *
 * Note that once a letter has been sent it becomes a standard article
 * in the 'letters' section, and can be edited via usual tools for articles.
 *
 * This page can only be used by associates.
 *
 * If the file [code]parameters/demo.flag[/code] exists, the script assumes that this instance
 * of YACS runs in demonstration mode, and no message is actually posted.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Agnes
 * @tester Guillaume Perez
 * @tester Jan Boen
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../shared/values.php';	// letters.digest.stamp

// what to do
$action = '';
if(isset($_REQUEST['action']))
	$action = $_REQUEST['action'];
if(!$action && isset($context['arguments'][0]))
	$action = $context['arguments'][0];
$action = strip_tags($action);

// load localized strings
i18n::bind('letters');

// do not index this page
$context->sif('robots','noindex');

// load the skin
load_skin('letters');

// maximum number of recipients
if(!defined('MAXIMUM_RECIPIENTS'))
	define('MAXIMUM_RECIPIENTS', 5000);

// wrapping threshold
if(!defined('WRAPPING_LENGTH'))
	define('WRAPPING_LENGTH', 70);

// the path to this page
$context['path_bar'] = array( 'letters/' => i18n::s('Newsletters') );

// the title of the page
$context['page_title'] = i18n::s('Post a letter');

// load parameters for letters
Safe::load('parameters/letters.include.php');

// default values if no configuration file is available
if(!isset($context['letter_body']))
	$context['letter_body'] = '';
if(!isset($context['letter_prefix']))
	$context['letter_prefix'] = '';
if(!isset($context['letter_suffix']))
	$context['letter_suffix'] = '';
if(!isset($context['letter_title']))
	$context['letter_title'] = '';
if(!isset($context['title_prefix']))
	$context['title_prefix'] = '*** ';
if(!isset($context['title_suffix']))
	$context['title_suffix'] = '';

// if no reply-to, use the one of the logged user
if(!isset($context['letter_reply_to']) || !$context['letter_reply_to'])
	$context['letter_reply_to'] = Surfer::from();

// restrictions: for associates only
if(!Surfer::is_associate()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// e-mail has not been enabled
} elseif(!isset($context['with_email']) || ($context['with_email'] != 'Y')) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('E-mail has not been activated on this system.'));

// no post account
} elseif((!isset($context['mail_from']) || !$context['mail_from']) && (!isset($context['letter_reply_to']) || !$context['letter_reply_to'])) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(sprintf(i18n::s('No account to post the letter. Please %s.'), Skin::build_link('letters/configure.php', i18n::s('configure one'))));

// prepare some announcement
} elseif(isset($action) && ($action == 'announcement')) {

	// the letter prefix
	if($context['letter_prefix'])
		$context['letter_body'] .= '<div>'.$context['letter_prefix'].'</div>';

	// body is free
	$context['letter_body'] .= "\n\n\n";

	// append surfer signature, if any
	if(Surfer::get_id() && ($user = Users::get(Surfer::get_id())) && $user['signature'])
		$context['letter_body'] .= '<p>-----'.BR.strip_tags($user['signature'].'</p>');

	// the letter suffix
	if($context['letter_suffix'])
		$context['letter_body'] .= '<div>'.$context['letter_suffix'].'</div>';

	// the form to edit a letter
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>'
		.'<input type="hidden" name="action" value="send" />';

	// the letter title
	$label = i18n::s('Title');
	if(!isset($context['letter_title']) || !$context['letter_title'])
		$context['letter_title'] = $context['site_name'];
	$input = '<input type="text" name="letter_title" id="letter_title" size="50" value="'.encode_field(strip_tags($context['letter_title'])).'" />';
	$hint = i18n::s('Used as message subject line');
	$fields[] = array($label, $input, $hint);

	// the letter content
	$label = i18n::s('Content');
	$input = Surfer::get_editor('letter_body', $context['letter_body']);
	$fields[] = array($label, $input);

	// letter recipients
	$label = i18n::s('Recipients');
	$input = '<input type="radio" name="letter_recipients" size="40" value="all" checked="checked" /> '.i18n::s('All subscribers of the community').BR."\n";
	$input .= '<input type="radio" name="letter_recipients" size="40" value="members" /> '.i18n::s('Members only').BR."\n";
	$input .= '<input type="radio" name="letter_recipients" size="40" value="associates" /> '.i18n::s('Associates only').BR."\n";
	$input .= '<input type="radio" name="letter_recipients" size="40" value="custom" /> '.i18n::s('Specific addresses:')
		.' <input type="text" name="mail_to"  onfocus="document.main_form.letter_recipients[3].checked=\'checked\'" size="40" />'.BR."\n";
	$hint = i18n::s('The recipients that will receive the letter');
	$fields[] = array($label, $input, $hint);

	// build the form
	$context['text'] .= Skin::build_form($fields);

	// the submit button
	$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Send'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

	// end of the form
	$context['text'] .= '</div></form>';

	// the script used for form handling at the browser
	page::insert_script(
			// check that main fields are not empty
		'	func'.'tion validateDocumentPost(container) {'."\n"
				// letter_title is mandatory
		.'		if(!container.letter_title.value) {'."\n"
		.'			alert("'.i18n::s('No title has been provided.').'");'."\n"
		.'			Yacs.stopWorking();'."\n"
		.'			return false;'."\n"
		.'		}'."\n"
				// successful check
		.'		return true;'."\n"
		.'	}'."\n"
		// set the focus on first form field
		.'document.main_form.letter_title.focus();'."\n"
		);

// prepare a digest
} elseif(isset($action) && ($action == 'digest')) {

	// the letter prefix
	if($context['letter_prefix'])
		$context['letter_body'] .= '<div>'.$context['letter_prefix'].'</div>';

	// get the date of previous newsletter
	$digest_stamp = Values::get('letters.digest.stamp', NULL_DATE);

	// build the content of the letter automatically
	if($items = Articles::list_by('publication', 0, 100, 'digest', $digest_stamp)) {

		// one slot per section
		$slots = array();

		// scan each article
		foreach($items as $url => $label) {

			// text for this article
			$text = "\n";

			// split $label as array($time, $label, $author, $section, $icon, $introduction)
			$time = $author = $section = $icon = $introduction = NULL;
			$sublevel = FALSE;
			if(is_array($label)) {
				$time	= $label[0];
				$author = $label[2];
				$section = $label[3];
				$icon	= $label[4];
				$introduction = $label[5];
				$label	= $label[1];
			}

			// format the title
			$text .= $context['title_prefix'].'<a href="'.$url.'">'.$label.'</a>'.$context['title_suffix'].BR;

			// small details
			$text .= '<span style="color: #ccc; font-size: 0.8em;">';

			// author
			if($author)
				$text .= sprintf(i18n::c('By %s'), $author).' ';

			// publication time
			if($time)
				$text .= Skin::build_date($time, 'no_hour', $context['preferred_language']);

			// end of details
			$text .= '</span>'.BR;

			// introduction
			if($introduction)
				$text .= Codes::beautify($introduction).BR;

			// extra space
			$text .= BR;

			// save it in section slot
			if(isset($slots[$section]))
				$slots[$section] .= $text;
			else
				$slots[$section] = $text;

			// remember most recent publication date
			if($time > $digest_stamp)
				$digest_stamp = $time;
		}

		// populate letter
		foreach($slots as $section => $text)
			$context['letter_body'] .= '<h2>'.$section.'</h2>'.BR.$text.BR;

	}

	// the letter suffix
	if($context['letter_suffix'])
		$context['letter_body'] .= '<div>'.$context['letter_suffix'].'</div>';

	// the form to edit a letter
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>'
		.'<input type="hidden" name="action" value="send" />'
		.'<input type="hidden" name="digest_stamp" value="'.encode_field($digest_stamp).'" />';

	// the letter title
	$label = i18n::s('Title');
	if(!isset($context['letter_title']) || !$context['letter_title'])
		$context['letter_title'] = $context['site_name'];
	$input = '<input type="text" name="letter_title" size="50" value="'.encode_field(strip_tags($context['letter_title'])).'" />';
	$hint = i18n::s('Used as message subject line');
	$fields[] = array($label, $input, $hint);

	// the letter content
	$label = i18n::s('Content');
	$input = Surfer::get_editor('letter_body', $context['letter_body']);
	$fields[] = array($label, $input, $hint);

	// letter recipients
	$label = i18n::s('Recipients');
	$input = '<input type="radio" name="letter_recipients" size="40" value="all" checked="checked" /> '.i18n::s('All subscribers of the community').BR."\n";
	$input .= '<input type="radio" name="letter_recipients" size="40" value="members" /> '.i18n::s('Members only').BR."\n";
	$input .= '<input type="radio" name="letter_recipients" size="40" value="associates" /> '.i18n::s('Associates only').BR."\n";
	$input .= '<input type="radio" name="letter_recipients" size="40" value="custom" /> '.i18n::s('Specific addresses:')
		.' <input type="text" name="mail_to"  onfocus="document.main_form.letter_recipients[3].checked=\'checked\'" size="40" />'.BR."\n";
	$hint = i18n::s('The recipients that will receive the letter');
	$fields[] = array($label, $input, $hint);

	// build the form
	$context['text'] .= Skin::build_form($fields);

	// the submit button
	$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Send'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

	// end of the form
	$context['text'] .= '</div></form>';

	// the script used for form handling at the browser
	Page::insert_script(
		// check that main fields are not empty
		'func'.'tion validateDocumentPost(container) {'."\n"
			// letter_title is mandatory
		.'	if(!container.letter_title.value) {'."\n"
		.'		alert("'.i18n::s('No title has been provided.').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		return false;'."\n"
		.'	}'."\n"
			// successful check
		.'	return true;'."\n"
		.'}'."\n"
		.'// set the focus on first form field'."\n"
		.'document.main_form.letter_title.focus();'."\n"
		);

// list featured pages
} elseif(isset($action) && ($action == 'featured')) {

	// the letter prefix
	if($context['letter_prefix'])
		$context['letter_body'] .= '<div>'.$context['letter_prefix'].'</div>';

	// re-use parameter for featured pages at the front page
	if(!isset($context['root_featured_count']) || ($context['root_featured_count'] < 1))
		$context['root_featured_count'] = 7;

	// the category used to assign featured pages
	$anchor = Categories::get(i18n::c('featured'));
	if(isset($anchor['id']) && ($items = Members::list_articles_by_date_for_anchor('category:'.$anchor['id'], 0, $context['root_featured_count'], 'digest'))) {

		// scan each article
		foreach($items as $url => $label) {

			// text for this article
			$context['letter_body'] .= "\n";

			// split $label as array($time, $label, $author, $section, $icon, $introduction)
			$time = $author = $section = $icon = $introduction = NULL;
			$sublevel = FALSE;
			if(is_array($label)) {
				$time	= $label[0];
				$author = $label[2];
				$section = $label[3];
				$icon	= $label[4];
				$introduction = $label[5];
				$label	= $label[1];
			}

			// format the title
			$context['letter_body'] .= $context['title_prefix'].'<a href="'.$url.'">'.$label.'</a>'.$context['title_suffix']."\n";

			// small details
			$context['letter_body'] .= '<span style="color: #ccc; font-size: 0.8em;">';

			// author
			if($author)
				$context['letter_body'] .= sprintf(i18n::c('By %s'), $author).' ';

			// publication time
			if($time)
				$context['letter_body'] .= Skin::build_date($time, 'no_hour', $context['preferred_language']);

			// end of details
			$context['letter_body'] .= '</span>'.BR;

			// introduction
			if($introduction)
				$context['letter_body'] .= Codes::beautify($introduction).BR;

		}

	}

	// the letter suffix
	if($context['letter_suffix'])
		$context['letter_body'] .= '<div>'.$context['letter_suffix'].'</div>';

	// the form to edit a letter
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>'
		.'<input type="hidden" name="action" value="send" />';

	// the letter title
	$label = i18n::s('Title');
	if(!isset($context['letter_title']) || !$context['letter_title'])
		$context['letter_title'] = $context['site_name'];
	$input = '<input type="text" name="letter_title" size="50" value="'.encode_field(strip_tags($context['letter_title'])).'" />';
	$hint = i18n::s('Used as message subject line');
	$fields[] = array($label, $input, $hint);

	// letter content
	$label = i18n::s('Content');
	$input = Surfer::get_editor('letter_body', $context['letter_body']);
	$fields[] = array($label, $input, $hint);

	// letter recipients
	$label = i18n::s('Recipients');
	$input = '<input type="radio" name="letter_recipients" size="40" value="all" checked="checked" /> '.i18n::s('All subscribers of the community').BR."\n";
	$input .= '<input type="radio" name="letter_recipients" size="40" value="members" /> '.i18n::s('Members only').BR."\n";
	$input .= '<input type="radio" name="letter_recipients" size="40" value="associates" /> '.i18n::s('Associates only').BR."\n";
	$input .= '<input type="radio" name="letter_recipients" size="40" value="custom" /> '.i18n::s('Specific addresses:')
		.' <input type="text" name="mail_to"  onfocus="document.main_form.letter_recipients[3].checked=\'checked\'" size="40" />'.BR."\n";
	$hint = i18n::s('The recipients that will receive the letter');
	$fields[] = array($label, $input, $hint);

	// build the form
	$context['text'] .= Skin::build_form($fields);

	// the submit button
	$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Send'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

	// end of the form
	$context['text'] .= '</div></form>';

	// the script used for form handling at the browser
	Page::insert_script(
		// check that main fields are not empty
		'func'.'tion validateDocumentPost(container) {'."\n"
		.'	// letter_title is mandatory'."\n"
		.'	if(!container.letter_title.value) {'."\n"
		.'		alert("'.i18n::s('No title has been provided.').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		return false;'."\n"
		.'	}'."\n"
			// successful check
		.'	return true;'."\n"
		.'}'."\n"
		."\n"
		// set the focus on first form field
		.'document.main_form.letter_title.focus();'."\n"
		);

// no mail in demo mode
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST') && file_exists($context['path_to_root'].'parameters/demo.flag')) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation in demonstration mode.'));

// handle posted data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// ensure all letters will be sent even if the browser connection dies
	Safe::ignore_user_abort(TRUE);

	// always archive the letter
	$anchor = Sections::lookup('letters');

	// no section yet, create one
	if(!$anchor) {

		$context['text'] .= i18n::s('Creating a section for archived letters').BR."\n";

		$fields['nick_name'] = 'letters';
		$fields['title'] = i18n::c('Archived letters');
		$fields['introduction'] = i18n::c('To remember our previous messages');
		$fields['description'] = i18n::c('YACS puts automatically sent letters into this section.');
		$fields['locked'] = 'Y'; // no direct contributions
		$fields['index_map'] = 'N'; // listed only to associates
		$fields['rank'] = 30000; // at the end of the list

		// reference the new section
		if($fields['id'] = Sections::post($fields, FALSE))
			$anchor = 'section:'.$fields['id'];

	}

	// archive the letter
	$context['text'] .= i18n::s('Archiving the new letter').BR."\n";

	// save the letter as a published article, but don't use special categories
	$fields = array();
	$fields['anchor'] = $anchor;
	$fields['title'] = $_REQUEST['letter_title'];
	$label = $_REQUEST['letter_recipients'];
	if(($_REQUEST['letter_recipients'] == 'custom') && isset($_REQUEST['mail_to']))
		$label = $_REQUEST['mail_to'];
	$fields['introduction'] = sprintf(i18n::c('Sent %s to&nbsp;"%s"'), Skin::build_date(time(), 'full', $context['preferred_language']), $label);
	$fields['description'] = $_REQUEST['letter_body'];
	$fields['publish_name'] = Surfer::get_name();
	$fields['publish_id'] = Surfer::get_id();
	$fields['publish_address'] = Surfer::get_email_address();
	$fields['publish_date'] = gmdate('Y-m-d H:i:s');
	$fields['id'] = Articles::post($fields);

	// from: from configuration files
	if(isset($context['letter_reply_to']) && $context['letter_reply_to'])
		$from = $context['letter_reply_to'];
	elseif(isset($context['mail_from']) && $context['mail_from'])
		$from = $context['mail_from'];
	else
		$from = $context['site_name'];

	// to: build the list of recipients
	$recipients = array();
	switch($_REQUEST['letter_recipients']) {

	case 'all':
		$recipients = Users::list_by_posts(0, MAXIMUM_RECIPIENTS, 'address');
		break;

	case 'members':
		$recipients = Users::list_members_by_posts(0, MAXIMUM_RECIPIENTS, 'address');
		break;

	case 'associates':
		$recipients = Users::list_associates_by_posts(0, MAXIMUM_RECIPIENTS, 'address');
		break;

	case 'custom':
		$to = $_REQUEST['mail_to'];
		break;
	}

	// use only valid addresses
	$recipients_skipped = 0;
	if(count($recipients)) {
		$to = array();

		// check every recipient
		foreach($recipients as $address => $label) {

			// check that the address is valid
			if(!preg_match(VALID_RECIPIENT, $address)) {
				$recipients_skipped++;
				$context['text'] .= str_replace (array('<', '>'), array('&lt;', '&gt;'), $address).' '.i18n::s('Error: Invalid address skipped').BR."\n";
				continue;
			}

			// no recipient string
			if(!$label)
				$to[] = $address;

			// if there is a comma, strip it and reverse nouns order
			else {
				if(preg_match('/,/', $label))
					$label = implode(' ', array_reverse(preg_split("/[\s,]+/", $label)));
				$to[] = Mailer::encode_recipient($address, $label);

			}

		}
	}

	// subject
	$subject = $_REQUEST['letter_title'];

	// enable yacs codes in messages
	$text = Codes::beautify($_REQUEST['letter_body']);

	// preserve tagging as much as possible
	$message = Mailer::build_multipart($text);

	// reply-to: from the letters configuration file
	if(isset($context['letter_reply_to']) && $context['letter_reply_to'])
		$headers[] = 'Reply-To: '.$context['letter_reply_to'];

	// list and count recipients
	$recipients_errors = $recipients_processed = $recipients_ok = 0;
	if(is_array($to)) {
		$context['text'] .= i18n::s('A message has been sent to:')."\n".'<ul>'."\n";
		foreach($to as $address)
			$context['text'] .= '<li>'.encode_field($address).'</li>'."\n";
		$context['text'] .= '</ul>'."\n";
		$recipients_processed = count($to);
	} elseif($to) {
		$context['text'] .= i18n::s('A message has been sent to:').' '.encode_field($to).BR."\n";
		$recipients_processed = 1;
	} else
		$context['text'] .= '<b>'.i18n::s('No recipient has been defined.')."</b>".BR."\n";

	// do the job
	if($recipients_processed) {
		$recipients_ok = Mailer::post($from, $to, $subject, $message, NULL, $headers);
		Mailer::close();

		// we may have more recipients than expected
		if($recipients_ok > $recipients_processed)
			$recipients_processed = $recipients_ok;

		// reports on error
		$recipients_errors = $recipients_processed - $recipients_ok;
		if($recipients_errors || count($context['error'])) {
			$context['text'] .= Logger::error_pop().BR."\n";

			$context['text'] .= '<b>'.i18n::s('Error has been encountered while sending the letter.')."</b>".BR."\n";
		}
	}

	// report on counters
	$context['text'] .= BR."\n";

	// list of recipients
	if($recipients_processed == 0)
		$context['text'] .= i18n::s('No recipient has been processed.').BR."\n";
	elseif($recipients_processed == 1)
		$context['text'] .= i18n::s('One recipient has been processed.').BR."\n";
	else
		$context['text'] .= sprintf(i18n::s('%d recipients have been processed'), $recipients_processed).BR."\n";

	// invalid addresses
	if($recipients_skipped == 1)
		$context['text'] .= i18n::s('One invalid address has been skipped.').BR."\n";
	elseif($recipients_skipped > 1)
		$context['text'] .= sprintf(i18n::s('%d invalid addresses have been skipped'), $recipients_skipped).BR."\n";

	// transmitted messages
	if($recipients_ok == 0)
		$context['text'] .= i18n::s('No letter has been transmitted.').BR."\n";
	elseif($recipients_ok == 1)
		$context['text'] .= i18n::s('One letter has been transmitted.').BR."\n";
	else
		$context['text'] .= sprintf(i18n::s('%d letters have been transmitted.'), $recipients_ok).BR."\n";

	// transmission errors, if any
	if($recipients_errors == 1)
		$context['text'] .= i18n::s('One transmission error has been encountered.').BR."\n";
	elseif($recipients_errors > 1)
		$context['text'] .= sprintf(i18n::s('%d transmission errors have been encountered.'), $recipients_errors).BR."\n";


	// save digest stamp, if any
	if(isset($_REQUEST['digest_stamp']) && ($_REQUEST['digest_stamp'] > NULL_DATE))
		Values::set('letters.digest.stamp', $_REQUEST['digest_stamp']);

	// display the execution time
	$time = round(get_micro_time() - $context['start_time'], 2);
	$context['text'] .= '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

	// forward to the index page
	$menu = array( 'letters/' => i18n::s('Newsletters') );
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// make the user select an option
} else {

	// the splash message
	$context['text'] .= '<p>'.i18n::s('This script will help you to prepare and to send a electronic message to community members. Please select below the action you would like to perform. Depending on your choice, the assistant may ask for additional parameters on successive panels.').'</p>'."\n";

	// the form
	$context['text'] .= '<form method="get" action="'.$context['script_url'].'" id="main_form">'."\n";

	// a digest of most recent articles
	$context['text'] .= '<p><input type="radio" name="action" value="digest" selected="selected" /> '.i18n::s('Send a digest of articles published recently').'</p>'."\n";

	// list featured pages
	$context['text'] .= '<p><input type="radio" name="action" value="featured" /> '.i18n::s('List featured pages').'</p>'."\n";

	// some announcement
	$context['text'] .= '<p><input type="radio" name="action" value="announcement" /> '.i18n::s('Send one announcement to community members').'</p>'."\n";

	// the submit button
	$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Next step'), i18n::s('Press [s] to continue'), 's').'</p>'."\n";

	// end of the form
	$context['text'] .= '</form>'."\n";

}

// render the skin
render_skin();

?>
