<?php
/**
 * the library to send electronic messages
 *
 * Submitted messages are queued in the database, and actual posts to the mail
 * server are processed in the background. Therefore bursts of mail messages are
 * shaped to accomodate for limitations set by many Internet service providers.
 *
 * Following values are updated during mail operations:
 * - 'mailer.bucket.content' - a leaking bucket is used to shape bursts
 * - 'mailer.last.posted' - subject of last message actually posted
 * - 'mailer.last.queued' - subject of last message queued
 *
 * If the parameter 'debug_mail' is set, then a copy of every posted message
 * is saved in the file temporary/debug.txt for further review.
 *
 * @link http://en.wikipedia.org/wiki/MIME Multipurpose Internet Mail Extensions (MIME)
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

class Mailer {

	/**
	 * send an email message
	 *
	 * This function supports automated posts of e-mail message to back-office administrators.
	 *
	 * @param string recipient address
	 * @param string subject
	 * @param string actual message
	 * @return TRUE on success, FALSE otherwise
	 *
	 * @see agents/messages.php
	 * @see control/configure.php
	 * @see query.php
	 * @see shared/logger.php
	 * @see users/users.php
	 */
	function notify($to, $subject, $message) {
		global $context;

		// email services have to be activated
		if(!isset($context['with_email']) || ($context['with_email'] != 'Y'))
			return FALSE;

		// ensure we have a sender
		if(isset($context['mail_from']) && $context['mail_from'])
			$from = $context['mail_from'];
		else
			$from = 'yacs at '.$context['host_name'];

		// do the job -- don't stop on error
		if(Mailer::post($from, $to, $subject, $message, NULL))
			return TRUE;
		return FALSE;
	}

	/**
	 * send an email message
	 *
	 * @todo allow for multipart messages http://www.php.net/mail
	 *
	 * This function supports automated posts of e-mail message.
	 *
	 * For this to work, e-mail services have to be explicitly activated in the
	 * main configuration panel, at [script]control/configure.php[/script].
	 *
	 * This function is able to authenticate to the mail server using POP3
	 * before engaging SMTP. This is the standard method used at many ISPs to
	 * avoid spam.
	 *
	 * Several recipients can be provided as a list of addresses separated by
	 * commas.
	 * For bulk posts, recipients can be transmitted as an array of strings.
	 * In all cases, this function send separately one message per recipient.
	 *
	 * Bracketed recipients, such as ##Foo Bar <foo@bar.com>##, are handled properly,
	 * meaning ##foo@bar.com## is transmitted to the mailing function, while
	 * the string ##To : Foo Bar <foo@bar.com>## is added to headers.
	 *
	 * Long lines of the message are wrapped according to [link=Dan's suggestion]http://mailformat.dan.info/body/linelength.html[/link].
	 *
	 * @link http://mailformat.dan.info/body/linelength.html Dan's Mail Format Site: Body: Line Length
	 *
	 * Messages are sent using utf-8, and are base64-encoded or send "as-is".
	 *
	 * @link http://www.sitepoint.com/article/advanced-email-php/3 Advanced email in PHP
	 *
	 * This function will ensure that only one mail message is send to a recipient,
	 * by maintaining an internal list of addresses that have been processed.
	 *
	 * This function returns the number of successful posts,
	 * and populates the error context, where applicable.
	 *
	 * @param string sender address
	 * @param mixed recipient address(es)
	 * @param string subject
	 * @param string actual message
	 * @param mixed additional headers, if any
	 * @param string the originating script, if any
	 * @return the number of actual posts, or 0
	 *
	 * @see articles/mail.php
	 * @see letters/new.php
	 * @see users/mail.php
	 */
	function post($from, $to, $subject, $message, $headers='', $debug='shared/mailer.php') {
		global $context;

		// email services have to be activated
		if(!isset($context['with_email']) || ($context['with_email'] != 'Y')) {
			Skin::error(i18n::s('E-mail has not been enabled on this system.'));
			return 0;

		// email services are not allowed
		} elseif(!is_callable('mail')) {
			Skin::error(i18n::s('E-mail is not authorized on this system.'));
			return 0;

		// check sender address
		} elseif(!$from) {
			Skin::error(i18n::s('Empty sender address'));
			return 0;

		// check recipient address
		} elseif(!$to) {
			Skin::error(i18n::s('Empty recipient address'));
			return 0;

		// check mail subject
		} elseif(!$subject) {
			Skin::error(i18n::s('No subject'));
			return 0;

		// check mail content
		} elseif(!$message) {
			Skin::error(i18n::s('No message'));
			return 0;
		}

		// authenticate to a pop3 server if necessary
		if(!isset($context['mail_pop3_handle'])
			&& isset($context['mail_pop3_server']) && isset($context['mail_pop3_user']) && isset($context['mail_pop3_password'])
			&& is_callable('imap_open'))
			$context['mail_pop3_handle'] = @imap_open('{'.$context['mail_pop3_server'].':110/pop3}INBOX', $context['mail_pop3_user'], $context['mail_pop3_password']);

		// set the SMTP server
		if(isset($context['mail_smtp_server']) && $context['mail_smtp_server'])
			Safe::ini_set('SMTP', $context['mail_smtp_server']);

		// set the SMTP sender
		if(isset($context['mail_from']) && $context['mail_from'])
			Safe::ini_set('sendmail_from', $context['mail_from']);

		// no new line nor HTML tag in title
		if(!$subject)
			$subject = '***';
		$subject = preg_replace('/\s+/', ' ', strip_tags($subject));

		// make it utf-8
		$subject = utf8::from_unicode($subject);

		// encode it for the transfer
		$encoded_subject = '=?utf-8?B?'.base64_encode($subject).'?=';

		// Unix-style newlines only
		$message = str_replace("\r\n", "\n", $message);

		// wrapping threshold
		if(!defined('WRAPPING_LENGTH'))
			define('WRAPPING_LENGTH', 70);

		// wrap the message if necessary
		$lines = explode("\n", $message);
		$message = '';
		foreach($lines as $line)
			$message .= wordwrap($line, WRAPPING_LENGTH, " \n", 0)."\n";

		// make some text out of an array
		if(is_array($headers))
			$headers = implode("\n", $headers);

		// From: header
		if(!preg_match('/^From: /im', $headers))
			$headers .= "\n".'From: '.$from;

		// Reply-To: header
		if(!preg_match('/^Reply-To: /im', $headers))
			$headers .= "\n".'Reply-To: '.$from;

		// Return-Path: header --to process errors
		if(!preg_match('/^Return-Path: /im', $headers))
			$headers .= "\n".'Return-Path: '.$from;

		// Message-ID: header --helps to avoid spam filters
		if(!preg_match('/^Message-ID: /im', $headers))
			$headers .= "\n".'Message-ID: <'.time().'@'.$context['host_name'].'>';

		// MIME-Version: header
		if(!preg_match('/^MIME-Version: /im', $headers))
			$headers .= "\n".'MIME-Version: 1.0';

		// ensure utf-8
		$encoded_message = utf8::from_unicode($message);

		// Content-Type: header
		if(!preg_match('/^Content-Type: /im', $headers))
			$headers .= "\n".'Content-Type: text/plain; charset=utf-8';

		// encoding rule
		if(!isset($context['mail_encoding']) || ($context['mail_encoding'] != '8bit'))
			$context['mail_encoding'] = 'base64';

		// encode the message for it transfer
		if($context['mail_encoding'] == 'base64') {

			// do the encoding
			$encoded_message = chunk_split(base64_encode($encoded_message));

			// Content-Transfer-Encoding: header
			if(!preg_match('/^Content-Transfer-Encoding: /im', $headers))
				$headers .= "\n".'Content-Transfer-Encoding: base64';

		// transmit native content
		} else {

			// Content-Transfer-Encoding: header
			if(!preg_match('/^Content-Transfer-Encoding: /im', $headers))
				$headers .= "\n".'Content-Transfer-Encoding: 8bit';

		}

		// X-Mailer: header --helps to avoid spam filters
		if(!preg_match('/^X-Mailer: /im', $headers))
			$headers .= "\n".'X-Mailer: PHP v'.phpversion();

		// strip leading spaces and newlines
		$headers = trim($headers);

		// make an array of recipients
		if(!is_array($to))
			$to = explode(',', $to);

		// the list of recipients contacted during overall script execution
		static $already_processed;
		if(!isset($already_processed))
			$already_processed = array();

		// process every recipient
		$posts = 0;
		foreach($to as $recipient) {

			// clean the provided string
			$recipient = trim(str_replace(array("\r\n", "\r", "\n", "\t"), ' ', $recipient));

			// extract the actual e-mail address -- Foo Bar <foo@bar.com> => foo@bar.com
			$tokens = explode(' ', $recipient);
			$actual_recipient = trim(str_replace(array('<', '>'), '', $tokens[count($tokens)-1]));

			// this e-mail address has already been processed
			if(in_array($actual_recipient, $already_processed)) {
				if(isset($context['debug_mail']) && ($context['debug_mail'] == 'Y'))
					Logger::remember($debug, 'Skipping recipient already processed', $actual_recipient, 'debug');
				continue;

			// remember this recipient
			} else
				$already_processed[] = $actual_recipient;

			// queue the message
			Mailer::queue($actual_recipient, $encoded_subject, $encoded_message, $headers);
			$posts++;
		}

		// track last submission
		include_once $context['path_to_root'].'shared/values.php';
		Values::set('mailer.last.queued', $subject.' ('.$posts.' recipients)');

		// return the number of actual posts
		return $posts;
	}

	/**
	 * defer the processing of one message
	 *
	 * This function saves provided data in the database.
	 *
	 * @param string the target address
	 * @param string message subject
	 * @param string message content
	 * @param string optional headers
	 * @return TRUE on success, FALSE otherwise
	 */
	function queue($recipient, $subject, $message, $headers='') {
		global $context;

		// transaction attributes
		$query = array();
		$query[] = "edit_date='".SQL::escape(gmstrftime('%Y-%m-%d %H:%M:%S'))."'";
		$query[] = "headers='".SQL::escape($headers)."'";
		$query[] = "message='".SQL::escape($message)."'";
		$query[] = "recipient='".SQL::escape($recipient)."'";
		$query[] = "subject='".SQL::escape($subject)."'";

		// insert a new record
		$query = "INSERT INTO ".SQL::table_name('messages')." SET ".implode(', ', $query);

		// actual insert
		if(SQL::query($query) === FALSE)
			return FALSE;
		return TRUE;
	}

	/**
	 * process deferred messages
	 *
	 * Most often, the server has to stay below a given rate of messages,
	 * for example 50 messages per hour.
	 *
	 * Of course, any lively community will feature bursts of activity and of
	 * messages, therefore the need for a shaping mechanism.
	 *
	 * YACS implements a leaking bucket algorithm to take care of messages sent
	 * previously:
	 *
	 * 1. Initially, the bucket is empty.
	 *
	 * 2. New messages are queued in the database, to be processed asynchronously.
	 *
	 * 3. On background ticks, the bucket is decremented. If the bucket becomes
	 * empty, and if some messages have been queued, a couple of them are sent, and
	 * the bucket is incremented accordingly.
	 *
	 * Bucket content is managed as value 'bucket.content' saved in the database.
	 *
	 * The bucket size is given by parameter $context['mail_hourly_maximum'], set
	 * in the configuration panel for system parameters.
	 *
	 * This parameter has a default value of 50, meaning YACS will not send more
	 * than 50 messages per hour.
	 *
	 * Background processing is either added to regular page generation or delegated
	 * to an external sub-system (e.g., cron). In case of a large site, we recommend
	 * to use the second solution, even if this adds additional setup steps. Your
	 * choice will be recorded in the configuration panel for system parameters.
	 *
	 * @see control/configure.php
	 *
	 * The number of messages sent on each tick can go up to the bucket size if
	 * background processing is external. Else it is one fourth of bucket size, to
	 * minimize impact on watching surfer.
	 *
	 * @see cron.php
	 */
	function tick_hook() {
		global $context;

		// useless if we don't have a valid database connection
		if(!$context['connection'])
			return;

		// remember start time
		$start = get_micro_time();

		// get bucket size
		if(!isset($context['mail_hourly_maximum']) || ($context['mail_hourly_maximum'] < 5))
			$context['mail_hourly_maximum'] = 50;

		// get record related to last tick
		include_once $context['path_to_root'].'shared/values.php';
		$bucket = Values::get_record('mailer.bucket.content', 0);
		$bucket['value'] = intval($bucket['value']);

		// some content to leak
		if($bucket['value'] > 0) {

			// date of last stamp
			if(isset($bucket['edit_date']))
				$stamp = SQL::strtotime($bucket['edit_date']);
			else
				$stamp = time() - 3600;

			// leak is maximum after one hour
			$leak = intval($context['mail_hourly_maximum'] * ( time() - $stamp ) / 3600);

			// actual leak
			$bucket['value'] = max(0, $bucket['value'] - $leak);

		}

		// process some messages only when bucket is empty
		$count = 0;
		if($bucket['value'] < 1) {

			// reduced speed if on-line processing
			if(isset($_SERVER['REMOTE_ADDR']))
				$slice = intval($context['mail_hourly_maximum'] / 4);
			else
				$slice = intval($context['mail_hourly_maximum']);

			// get some messages, if any
			$query = "SELECT * FROM ".SQL::table_name('messages')
				." ORDER BY edit_date LIMIT 0, ".$slice;
			if($result = SQL::query($query)) {

				// process every message
				while($item =& SQL::fetch($result)) {

					Mailer::process($item['recipient'], $item['subject'], $item['message'], $item['headers']);

					// purge the queue
					$query = 'DELETE FROM '.SQL::table_name('messages').' WHERE id = '.$item['id'];
					SQL::query($query);

					// fill the bucket
					$bucket['value'] += 1;
					$count++;

					// take care of time
					if(!($count%50)) {

						// ensure enough execution time
						Safe::set_time_limit(30);

						// ease the pain of mail server
						Safe::sleep(1);
					}

				}
			}
		}

		// remember new state of the bucket
		Values::set('mailer.bucket.content', $bucket['value']);

		// compute execution time
		$time = round(get_micro_time() - $start, 2);

		// report on work achieved
		if($count > 1)
			return 'shared/mailer.php: '.$count.' messages have been processed ('.$time.' seconds)'.BR;
		elseif($count == 1)
			return 'shared/mailer.php: 1 message has been processed ('.$time.' seconds)'.BR;
		else
			return 'shared/mailer.php: nothing to do ('.$time.' seconds)'.BR;
	}

	/**
	 * actual transmission of a mail message
	 *
	 * @param string the target address
	 * @param string message subject
	 * @param string message content
	 * @param string optional headers
	 * @return TRUE on success, FALSE otherwise
	 */
	function process($recipient, $subject, $message, $headers) {
		global $context;

		// decode recipient for log
		$decoded_recipient = $recipient;
		if(preg_match('/^=\?[^\?]+\?B\?(.*)=$/i', $recipient, $matches))
			$decoded_recipient = base64_decode($matches[1]);

		// decode subject for log
		$decoded_subject = $subject;
		if(preg_match('/^=\?[^\?]+\?B\?(.*)=$/i', $subject, $matches))
			$decoded_subject = base64_decode($matches[1]);

		// track last post
		include_once $context['path_to_root'].'shared/values.php';
		Values::set('mailer.last.posted', $decoded_subject.' ('.$decoded_recipient.')');

		// debug mode
		if(isset($context['debug_mail']) && ($context['debug_mail'] == 'Y')) {
			$text = $headers."\n"
				.'To: '.$recipient."\n"
				.'Subject: '.$subject."\n\n"
				.$message;

			Logger::remember('shared/mailer.php', 'Sending message by e-mail', $text, 'debug');
		}

		// post in debug mode, to get messages, if any
		if(($context['with_debug'] == 'Y') && mail($recipient, $subject, $message, $headers))
			;

		// regular post
		elseif(($context['with_debug'] != 'Y') && @mail($recipient, $subject, $message, $headers))
			;

		// an error has been encountered
		elseif(isset($context['debug_mail']) && ($context['debug_mail'] == 'Y'))
			Logger::remember('shared/mailer.php', sprintf(i18n::s('Error while sending the message to %s'), $decoded_recipient), $decoded_subject, 'debug');
		elseif($context['with_debug'] == 'Y')
			Logger::remember('shared/mailer.php', sprintf(i18n::s('Error while sending the message to %s'), $decoded_recipient), $decoded_subject, 'debug');

	}

	/**
	 * create tables for queued messages
	 */
	function setup() {
		global $context;

		$fields = array();
		$fields['id']			= "MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT"; 			// up to 16m items
		$fields['edit_date']	= "DATETIME";
		$fields['headers']		= "TEXT NOT NULL";											// up to 64k chars
		$fields['message']		= "MEDIUMTEXT NOT NULL";									// up to 16M chars
		$fields['recipient']	= "VARCHAR(255) DEFAULT 'main' NOT NULL";
		$fields['subject']		= "VARCHAR(255) DEFAULT '' NOT NULL";						// up to 255 chars

		$indexes = array();
		$indexes['PRIMARY KEY'] 		= "(id)";
		$indexes['INDEX edit_date'] 	= "(edit_date)";

		return SQL::setup_table('messages', $fields, $indexes);

	}
}

?>