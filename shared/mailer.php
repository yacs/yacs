<?php
/**
 * send electronic messages
 *
 * Use the function Mailer::post() to submit new messages, maybe with multiple parts,
 * attached files and customised headers.
 *
 * Use the function Mailer::notify() to send simple notifications to end users.
 *
 * When a list of recipients is provided to these functions, they actually send one separate
 * message per recipient. This feature is important to preserve confidentiality, and to pass
 * through spam filters.
 *
 * This script is conforming to the Simple Mail Transfer Protocol (SMTP), including
 * extensions related to security and authentication. If openssl is available, it can connect
 * to mail servers using the SSL/TLS protocol. For authentication, CRAM-MD5, LOGIN and PLAIN
 * mechanisms are provided. Alternatively, authentication can also be done using POP3 before
 * the start of the SMTP session.
 *
 * @link http://www.rfc-editor.org/rfc/rfc5321.txt SMTP specification
 *
 * Mailer::post() can be used to send messages with some textual part and some HTML part, to
 * allow both for rich content display and for graceful downgrade if necessary.
 *
 * Several files can be attached to messages submitted to Mailer:post(), and this feature
 * can be used jointly with multiple part messages.
 *
 * @link http://en.wikipedia.org/wiki/MIME Multipurpose Internet Mail Extensions (MIME)
 *
 * The number of messages transmitted every hour is limited, and exceeding messages
 * are queued in the database. When this happens, actual posts to the mail
 * server are processed in the background. Therefore bursts of mail messages are
 * shaped to accomodate for limitations set by many Internet service providers.
 *
 * Following values are updated during mail operations:
 * - 'mailer.bucket.content' - a leaking bucket is used to shape bursts
 * - 'mailer.last.posted' - subject of last message actually posted
 * - 'mailer.last.queued' - subject of last message queued
 *
 * If the parameter 'with_email' is not set to 'Y', pending messages are not processed at all.
 *
 * If the parameter 'debug_mail' is set, then a copy of every posted message
 * is saved in the file temporary/debug.txt for further review.
 *
 * @see control/configure.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

class Mailer {

	/**
	 * close connection to mail server
	 *
	 * This function gracefully ends the transmission of messages.
	 */
	function close() {
		global $context;

		// nothing to do
		if(!isset($context['mail_handle']) || !is_resource($context['mail_handle']))
			return;

		// close the session
		$request = 'QUIT';
		fputs($context['mail_handle'], $request.CRLF);
		if($context['debug_mail'] == 'Y')
			Logger::remember('shared/mailer.php', 'SMTP ->', $request, 'debug');

		// purge transmission queue
		Mailer::parse_response($context['mail_handle'], 221);

		// break the network session
		fclose($context['mail_handle']);
		unset($context['mail_handle']);

	}

	/**
	 * connect to the mail server
	 *
	 * This function opens a network connection to the server, authenticate if required to do so,
	 * and set $context['mail_handle'] to be used for actual transmissions.
	 *
	 * If parameter $context['mail_variant'] is set to 'smtp', a SMTP connection is
	 * established with the computer specified in $context['mail_server']. If some credentials
	 * are provided in $context['mail_account'] and $context['mail_password'], they are
	 * transmitted to the server as per protocol extension. CRAM-MD5, LOGIN and PLAIN authentication
	 * schemes have been implemented.
	 *
	 * @link http://tools.ietf.org/rfc/rfc2104.txt HMAC
	 * @link http://www.fehcom.de/qmail/smtpauth.html
	 *
	 * If parameter $context['mail_variant'] is set to 'pop3', and if credentials have been
	 * set in $context['mail_account'] and in $context['mail_password'], a POP3 connection
	 * is made to the mail server just to authenticate, and then a SMTP connection
	 * is established to actually transmit messages. If a secured communication has been
	 * configured for SMTP, then a secured POP3 communication is performed on port 995. Else
	 * a vanilla POP3 transaction is done on regular port 110.
	 *
	 * For any other value of $context['mail_variant'], or if the parameter is not set,
	 * the function relies on the PHP mail() function to do the job. If the parameter
	 * $context['mail_server'] is set, it overloads php.ini settings. Therefore you can change
	 * the SMTP server used for transmission without the needs to edit the php.ini file.
	 *
	 * The parameter $context['mail_server'] can call for SSL/TLS support, or use a specific
	 * port number, as in the following examples:
	 *
	 * [snippet]
	 * ssl://mail.server.com
	 * mail.server.com:234
	 * [/snippet]
	 *
	 * @return mixed the socket handle itself, of FALSE on error
	 *
	 * @see control/configure.php
	 */
	function connect() {
		global $context;

		// we already have an open handle
		if(isset($context['mail_handle']))
			return $context['mail_handle'];

		// email services have to be activated
		if(!isset($context['with_email']) || ($context['with_email'] != 'Y')) {
			Logger::error(i18n::s('E-mail has not been enabled on this system.'));
			return FALSE;
		}

		// define target smtp server
		$port = 25;
		if(isset($context['mail_server'])) {
			$server = $context['mail_server'];

			// use alternate port if required to do so
			if(preg_match('/^(.+):([0-9]+)$/', $server, $matches)) {
				$server = $matches[1];
				$port = intval($matches[2]);
			}

		}

		// ensure that we can support tls communications
		if(isset($server) && !strncmp($server, 'ssl://', 6) && is_callable('extension_loaded') && !extension_loaded('openssl')) {
			logger::remember('shared/mailer.php', 'Load the OpenSSL extension to support secured transmissions to mail server '.$server);
			return FALSE;
		}

		// go for POP authentication
		if(isset($server) && isset($context['mail_variant']) && ($context['mail_variant'] == 'pop3')) {

			// authenticate to a pop3 server
			if(isset($context['mail_account']) && isset($context['mail_password'])) {

				// select which port to use
				if(strncmp($server, 'ssl://', 6))
					$pop3_port = 110;
				else
					$pop3_port = 995;

				// open a network connection
				if(!$handle = Safe::fsockopen($server, $pop3_port, $errno, $errstr, 10)) {
					if($context['debug_mail'] == 'Y')
						Logger::remember('shared/mailer.php', 'fsockopen:', $errstr.' ('.$errno.')', 'debug');
					Logger::remember('shared/mailer.php', sprintf('Impossible to connect to %s', $server.':'.$pop3_port));
					return FALSE;
				}

				// ensure enough execution time
				Safe::set_time_limit(30);

				// get server banner
				if(($reply = fgets($handle)) === FALSE) {
					Logger::remember('shared/mailer.php', 'Impossible to get banner of '.$server);
					fclose($handle);
					return FALSE;
				}
				if($context['debug_mail'] == 'Y')
					Logger::remember('shared/mailer.php', 'POP <-', $reply, 'debug');

				// expecting an OK
				if(strncmp($reply, '+OK', 3)) {
					Logger::remember('shared/mailer.php', 'Mail service is closed at '.$server, $reply);
					fclose($handle);
					return FALSE;
				}

				// send user name
				$request = 'USER '.$context['mail_account'];
				fputs($handle, $request.CRLF);
				if($context['debug_mail'] == 'Y')
					Logger::remember('shared/mailer.php', 'POP ->', $request, 'debug');

				// expecting an OK
				if(($reply = fgets($handle)) === FALSE) {
					Logger::remember('shared/mailer.php', 'No reply to USER command at '.$server);
					fclose($handle);
					return FALSE;
				}
				if($context['debug_mail'] == 'Y')
					Logger::remember('shared/mailer.php', 'POP <-', $reply, 'debug');

				if(strncmp($reply, '+OK', 3)) {
					Logger::remember('shared/mailer.php', 'Unknown account '.$context['mail_account'].' at '.$server, $reply);
					fclose($handle);
					return FALSE;
				}

				// send password
				$request = 'PASS '.$context['mail_password'];
				fputs($handle, $request.CRLF);
				if($context['debug_mail'] == 'Y')
					Logger::remember('shared/mailer.php', 'POP ->', $request, 'debug');

				// expecting an OK
				if(($reply = fgets($handle)) === FALSE) {
					Logger::remember('shared/mailer.php', 'No reply to PASS command at '.$server);
					fclose($handle);
					return FALSE;
				}
				if($context['debug_mail'] == 'Y')
					Logger::remember('shared/mailer.php', 'POP <-', $reply, 'debug');

				if(strncmp($reply, '+OK', 3)) {
					Logger::remember('shared/mailer.php', 'Invalid password for account '.$account.' at '.$server, $reply);
					fclose($handle);
					return FALSE;
				}

				// we just wanted to authenticate
				fclose($handle);
			}
		}

		// we manage directly the SMTP transaction
		if(isset($server) && isset($context['mail_variant']) && (($context['mail_variant'] == 'pop3') || ($context['mail_variant'] == 'smtp'))) {

			// open a network connection
			if(!$handle = Safe::fsockopen($server, $port, $errno, $errstr, 10)) {
				if($context['debug_mail'] == 'Y')
					Logger::remember('shared/mailer.php', 'fsockopen:', $errstr.' ('.$errno.')', 'debug');
				Logger::remember('shared/mailer.php', sprintf('Impossible to connect to %s', $server.':'.$port));
				return FALSE;
			}

			// ensure enough execution time
			Safe::set_time_limit(30);

			// get server banner
			if(($response = Mailer::parse_response($handle, 220)) === FALSE) {
				Logger::remember('shared/mailer.php', 'Impossible to get banner of '.$server);
				fclose($handle);
				return FALSE;
			}

			// provide our logical name
			if(strpos($response, 'ESMTP'))
				$request = 'EHLO '.$context['host_name'];
			else
				$request = 'HELO '.$context['host_name'];
			fputs($handle, $request.CRLF);
			if($context['debug_mail'] == 'Y')
				Logger::remember('shared/mailer.php', 'SMTP ->', $request, 'debug');

			// expecting a welcome message
			if(($response = Mailer::parse_response($handle, 250)) === FALSE) {
				Logger::remember('shared/mailer.php', 'Command EHLO has been rejected at '.$server);
				fclose($handle);
				return FALSE;
			}

			// authenticate as per SMTP protocol extension
			if(isset($context['mail_account']) && isset($context['mail_password']) && preg_match('/^AUTH (.+)$/m', $response, $matches)) {

				// CRAM-MD5 -- the preferred method
				if(strpos($matches[1], 'CRAM-MD5') !== FALSE) {

					// get the challenge
					$request = 'AUTH CRAM-MD5';
					fputs($handle, $request.CRLF);
					if($context['debug_mail'] == 'Y')
						Logger::remember('shared/mailer.php', 'SMTP ->', $request, 'debug');
					if(($response = Mailer::parse_response($handle, 334)) === FALSE) {
						Logger::remember('shared/mailer.php', 'Command AUTH has been rejected at '.$server);
						fclose($handle);
						return FALSE;
					}
					$challenge = base64_decode($response);

					// from password to a 64 bytes block
					if(strlen($context['mail_password']) < 64)
						$key = str_pad($context['mail_password'], 64, chr(0));
					elseif(strlen($context['mail_password']) > 64)
						$key = str_pad(pack('H32', md5($context['mail_password'])), 64, chr(0));
					else
						$key = $context['mail_password'];

					// compute HMAC-MD5
					$inner = $key ^ str_repeat(chr(0x36), 64);
					$outer = $key ^ str_repeat(chr(0x5C), 64);
					$digest = md5( $outer . pack('H32', md5( $inner . $challenge )) );

					// answer the challenge
					$request = base64_encode($context['mail_account'].' '.$digest);
					fputs($handle, $request.CRLF);
					if($context['debug_mail'] == 'Y')
						Logger::remember('shared/mailer.php', 'SMTP ->', $request, 'debug');

				// LOGIN
				} elseif(strpos($matches[1], 'LOGIN') !== FALSE) {

					$request = 'AUTH LOGIN';
					fputs($handle, $request.CRLF);
					if($context['debug_mail'] == 'Y')
						Logger::remember('shared/mailer.php', 'SMTP ->', $request, 'debug');
					if(Mailer::parse_response($handle, 334) === FALSE) {
						Logger::remember('shared/mailer.php', 'Command AUTH has been rejected at '.$server);
						fclose($handle);
						return FALSE;
					}

					$request = base64_encode($context['mail_account']);
					fputs($handle, $request.CRLF);
					if($context['debug_mail'] == 'Y')
						Logger::remember('shared/mailer.php', 'SMTP ->', $request, 'debug');
					if(Mailer::parse_response($handle, 334) === FALSE) {
						Logger::remember('shared/mailer.php', 'Command AUTH has been rejected at '.$server);
						fclose($handle);
						return FALSE;
					}

					$request = base64_encode($context['mail_password']);
					fputs($handle, $request.CRLF);
					if($context['debug_mail'] == 'Y')
						Logger::remember('shared/mailer.php', 'SMTP ->', $request, 'debug');

				// PLAIN
				} elseif(strpos($matches[1], 'PLAIN') !== FALSE) {

					$request = 'AUTH PLAIN '.base64_encode("\0".$context['mail_account']."\0".$context['mail_password']);
					fputs($handle, $request.CRLF);
					if($context['debug_mail'] == 'Y')
						Logger::remember('shared/mailer.php', 'SMTP ->', $request, 'debug');

				}

				// expecting an OK
				if(Mailer::parse_response($handle, 235) === FALSE) {
					Logger::remember('shared/mailer.php', 'Command AUTH has been rejected at '.$server);
					fclose($handle);
					return FALSE;
				}

			}

			// ready to submit messages
			$context['mail_handle'] = $handle;
			return $handle;

		// rely on system settings and PHP
		} elseif(is_callable('mail')) {

			// set the SMTP server
			if($server)
				Safe::ini_set('SMTP', $server);

			// set the SMTP sender
			if(isset($context['mail_from']) && $context['mail_from'])
				Safe::ini_set('sendmail_from', $context['mail_from']);

			// ready to submit messages
			$context['mail_handle'] = TRUE;
			return TRUE;

		}

		// no SMTP configuration
		return FALSE;

	}

	/**
	 * format a message
	 *
	 * This function prepares a localized message
	 *
	 * Depending of the reason, the message will have the following kind of trail:
	 * - 0 - no trail
	 * - 1 - you are watching the container
	 * - 2 - you are watching the poster
	 *
	 * @param string coded action (e.g., 'article:create') or full description
	 * @param string title of the target page
	 * @param string link to the target page
	 * @param int reason for notification
	 * @return string text to be put in message
	 */
	function &build_notification($action, $title, $link, $reason=0) {
		global $context;

		// decode action
		if(strpos($action, ':create')) {
			if($surfer = Surfer::get_name())
				$action = sprintf(i18n::c('%s by %s'), ucfirst(Anchors::get_action_label($action)), $surfer);
			else
				$action = ucfirst(Anchors::get_action_label($action));
		}

		// clean title
		$title = strip_tags($title);

		// decode the reason
		switch($reason) {

		case 0: // no trail
		default:
			$reason = '';
			break;

		case 1: // you are watching the container
			$reason = "\n\n"
				.sprintf(i18n::c('This message has been generated automatically by %s since the new item has been posted in a web space that is part of your watch list. If you wish to stop these automatic alerts please visit the page and click on the Forget link.'), $context['site_name']);
			break;

		case 2: // you are watching the poster
			$reason = "\n\n"
				.sprintf(i18n::c('This message has been generated automatically by %s since you are connected to the person who posted the new item. If you wish to stop these automatic alerts please visit the following user profile and click on the Disconnect link.'), $context['site_name'])
				."\n\n".ucfirst(strip_tags(Surfer::get_name()))
				."\n".$context['url_to_home'].$context['url_to_root'].Surfer::get_permalink()
				."\n\n";
			break;

		}

		// allow for localized templates
		$template = i18n::get_template('mail_notification');

		// assemble everything
		$text = sprintf($template, $action, $title, $link).$reason;

		// job done
		return $text;
	}

	/**
	 * retrieve recipients of last post
	 *
	 * This is useful to list all persons notified after a post for example.
	 *
	 * @param string title of the folded box generated
	 * @return mixed text to be integrated into the page, or array with one item per recipient, or ''
	 */
	function get_recipients($title=NULL) {
		global $context;

		// nothing to show
		if(!Surfer::get_id() || !isset($context['mailer_recipients']))
			return '';

		// return the bare list
		if(!$title)
			return $context['mailer_recipients'];

		// build a nice list
		$list = array();
		if(count($context['mailer_recipients']) > 50)
			$count = 30;	// list only 30 first recipients
		else
			$count = 100;	//never reached
		foreach($context['mailer_recipients'] as $recipient) {
			$list[] = htmlspecialchars($recipient);
			if($count-- ==1) {
				$list[] = sprintf(i18n::s('and %d other persons'), count($context['mailer_recipients'])-30);
				break;
			}
		}
		return Skin::build_box($title, Skin::finalize_list($list, 'compact'), 'folded');

	}

	/**
	 * send a short email message
	 *
	 * This is the function used by yacs to notify community members of various events.
	 *
	 * @param string sender address, use default system parameter if NULL
	 * @param string recipient address
	 * @param string subject
	 * @param string actual message
	 * @param mixed to be given to Mailer::post()
	 * @return TRUE on success, FALSE otherwise
	 *
	 * @see agents/messages.php
	 * @see control/configure.php
	 * @see query.php
	 * @see shared/logger.php
	 * @see users/users.php
	 */
	function notify($from, $to, $subject, $message, $headers='') {
		global $context;

		// email services have to be activated
		if(!isset($context['with_email']) || ($context['with_email'] != 'Y'))
			return FALSE;

		// ensure we have a sender
		if(!$from) {
			if(isset($context['mail_from']) && $context['mail_from'])
				$from = $context['mail_from'];
			else
				$from = 'yacs at '.$context['host_name'];
		}

		// do the job -- don't stop on error
		if(Mailer::post($from, $to, $subject, $message, NULL, $headers))
			return TRUE;
		return FALSE;
	}

	/**
	 * parse responses from the mail server
	 *
	 * @param resource handle to the network connection
	 * @param int the expected response code
	 * @return mixed the string of returned messages, or FALSE on unexpected response or on error
	 */
	function parse_response($handle, $expected) {
		global $context;

		$response = '';
		while(TRUE) {

			// read one line
			if(($line = fgets($handle)) === FALSE)
				return FALSE;
			if($context['debug_mail'] == 'Y')
				Logger::remember('shared/mailer.php', 'SMTP <-', rtrim($line), 'debug');

			// get text
			if($response)
				$response .= "\n";
			$response .= substr($line, 4);

			// continue on next line
			if($line[3] == '-')
				continue;

			// check status code
			if(substr($line, 0, 3) != $expected)
				return FALSE;
			return $response;

		}

	}

	/**
	 * build and transmit a complex e-mail messages
	 *
	 * This function allows for individual posts, textual and HTML messages, and attached files.
	 *
	 * For this to work, e-mail services have to be explicitly activated in the
	 * main configuration panel, at [script]control/configure.php[/script].
	 *
	 * Several recipients can be provided as a list of addresses separated by
	 * commas. For bulk posts, recipients can be transmitted as an array of strings.
	 * In all cases, this function sends one separate message per recipient.
	 *
	 * This function will ensure that only one mail message is send to a recipient,
	 * by maintaining an internal list of addresses that have been processed.
	 * Therefore, if this function is called several times, with some repeated recipients,
	 * those will receive only the first message, and other messages to the same address
	 * will be dropped.
	 *
	 * Bracketed recipients, such as ##Foo Bar <foo@bar.com>##, are handled properly,
	 * meaning ##foo@bar.com## is transmitted to the mailing function, while
	 * the string ##To : Foo Bar <foo@bar.com>## is added to headers.
	 *
	 * If an array of messages is provided to the function, it is turned to a multi-part
	 * message, as in the following example:
	 *
	 * [php]
	 * $message = array();
	 * $message['text/plain; charset=utf-8'] = 'This is a plain message';
	 * $message['text/html'] = '<html><head><title>Hello</title><body>This is an HTML message</body></html>';
	 * Mailer::post($from, $to, $subject, $message);
	 * [/php]
	 *
	 * If you don't provide a charset, then UTF-8 is used. Also, it is recommended to
	 * begin with the bare text, and to have the rich format part comming after, as in the example.
	 *
	 * Long lines of text/plain parts are wrapped according to
	 * [link=Dan's suggestion]http://mailformat.dan.info/body/linelength.html[/link].
	 *
	 * @link http://mailformat.dan.info/body/linelength.html Dan's Mail Format Site: Body: Line Length
	 *
	 * Message parts are base64-encoded or send "as-is", as set in $context['mail_encoding'].
	 *
	 * A list of files to be attached to the message can be provided as in the following example:
	 *
	 * [php]
	 * $attachments = array();
	 * $attachments[] = 'report.pdf';
	 * $attachments[] = 'image.png';
	 * Mailer::post($from, $to, $subject, $message, $attachments);
	 * [/php]
	 *
	 * This function returns the number of successful posts,
	 * and populates the error context, where applicable.
	 *
	 * @param string sender address
	 * @param mixed recipient address(es)
	 * @param string subject
	 * @param string actual message
	 * @param array attachments, if any
	 * @param mixed additional headers, if any
	 * @return the number of actual posts, or 0
	 *
	 * @see articles/mail.php
	 * @see letters/new.php
	 * @see users/mail.php
	 */
	function post($from, $to, $subject, $message, $attachments=NULL, $headers='') {
		global $context;

		// use surfer own address
		if(!$from)
			$from = Surfer::from();

		// email services have to be activated
		if(!isset($context['with_email']) || ($context['with_email'] != 'Y')) {
			Logger::error(i18n::s('E-mail has not been enabled on this system.'));
			return 0;

		// check sender address
		} elseif(!$from) {
			Logger::error(i18n::s('Empty sender address'));
			return 0;

		// check recipient address
		} elseif(!$to) {
			Logger::error(i18n::s('Empty recipient address'));
			return 0;

		// check mail subject
		} elseif(!$subject) {
			Logger::error(i18n::s('No subject'));
			return 0;

		// check mail content
		} elseif(!$message) {
			Logger::error(i18n::s('No message'));
			return 0;
		}

		// no new line nor HTML tag in title
		$subject = preg_replace('/\s+/', ' ', strip_tags($subject));

		// make it utf-8
		$subject = utf8::from_unicode($subject);

		// encode it for the transfer
		$encoded_subject = '=?utf-8?B?'.base64_encode($subject).'?=';

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

		// arrays are easier to manage
		if(is_string($message)) {
			$copy = $message;
			$message = array();
			$message['text/plain; charset=utf-8'] = $copy;
			unset($copy);
		}
		if(!$attachments)
			$attachments = array();

		// we need some boundary string
		if((count($message) + count($attachments)) > 1)
			$boundary = md5(time());

		// wrapping threshold
		if(!defined('WRAPPING_LENGTH'))
			define('WRAPPING_LENGTH', 70);

		// combine message parts
		$content_type = '';
		$content_encoding = '8bit';
		$body = '';
		foreach($message as $type => $part) {

			// encode plain text parts
			$content_encoding = '8bit';
			if(!strncmp($type, 'text/plain', 10)) {

				// wrap the message if necessary
				$lines = explode("\n", $part);
				$part = '';
				foreach($lines as $line)
					$part .= wordwrap($line, WRAPPING_LENGTH, ' '.CRLF, 0).CRLF;

				// ensure utf-8
				$part = utf8::from_unicode($part);

				// encoding rule
				if(!isset($context['mail_encoding']) || ($context['mail_encoding'] != '8bit'))
					$context['mail_encoding'] = 'base64';

				// encode the message for it transfer
				if($context['mail_encoding'] == 'base64') {
					$part = chunk_split(base64_encode($part));
					$content_encoding = 'base64';
				}
			}

			// only one part
			if(count($message) == 1) {
				$content_type = $type;
				$body = $part;

			// one part among several
			} else {

				if(!$content_type)
					$content_type = 'multipart/alternative; boundary="'.$boundary.'-internal"';

				if(!$body)
					$body = 'This is a multi-part message in MIME format.'.CRLF;

				$body .= CRLF.'--'.$boundary.'-internal'
					.CRLF.'Content-Type: '.$type
					.CRLF.'Content-Transfer-Encoding: '.$content_encoding
					.CRLF.CRLF.$part."\n";

			}
		}

		// finalize the body
		if(count($message) > 1)
			$body .= CRLF.'--'.$boundary.'-internal--';

		// a mix of things
		if(count($attachments)) {

				// the current body becomes the first part of a larger message
				if(!strncmp($content_type, 'multipart/', 10))
					$content_encoding = '';
				else
					$content_encoding = CRLF.'Content-Transfer-Encoding: '.$content_encoding;

				$body = 'This is a multi-part message in MIME format.'.CRLF
					.CRLF.'--'.$boundary.'-external'
					.CRLF.'Content-Type: '.$content_type
					.$content_encoding
					.CRLF.CRLF.$body."\n";

				$content_type = 'multipart/mixed; boundary="'.$boundary.'-external"';
				$content_encoding = '';

				// process every file
				foreach($attachments as $name) {

					// read file content
					if(!$content = Safe::file_get_contents($name))
						continue;

					// append it to mail message
					$basename = basename($name);
					$type = Files::get_mime_type($basename);

					$body .= CRLF.'--'.$boundary.'-external'
						.CRLF.'Content-Type: '.$type.'; name="'.$basename.'"'
						.CRLF.'Content-Transfer-Encoding: base64'
						.CRLF.CRLF.chunk_split(base64_encode($content))."\n";

				}
				$body .= CRLF.'--'.$boundary.'-external--';

		}


		// Content-Type: header
		if($content_type && !preg_match('/^Content-Type: /im', $headers))
			$headers .= "\n".'Content-Type: '.$content_type;

		// Content-Transfer-Encoding: header
		if(!isset($boundary) && $content_encoding && !preg_match('/^Content-Transfer-Encoding: /im', $headers))
			$headers .= "\n".'Content-Transfer-Encoding: '.$content_encoding;

		// X-Mailer: header --helps to avoid spam filters
		if(!preg_match('/^X-Mailer: /im', $headers))
			$headers .= "\n".'X-Mailer: yacs';

		// strip leading spaces and newlines
		$headers = trim($headers);

		// make an array of recipients
		if(!is_array($to))
			$to = explode(',', $to);

		// the list of recipients contacted during overall script execution
		if(!isset($context['mailer_recipients']))
			$context['mailer_recipients'] = array();

		// process every recipient
		$posts = 0;
		foreach($to as $recipient) {

			// clean the provided string
			$recipient = trim(str_replace(array("\r\n", "\r", "\n", "\t"), ' ', $recipient));

			// this e-mail address has already been processed
			if(in_array($recipient, $context['mailer_recipients'])) {
				if(isset($context['debug_mail']) && ($context['debug_mail'] == 'Y'))
					Logger::remember('shared/mailer.php', 'Skipping recipient already processed', $recipient, 'debug');
				continue;

			// remember this recipient
			} else
				$context['mailer_recipients'][] = $recipient;

			// queue the message
			Mailer::queue($recipient, $encoded_subject, $body, $headers);
			$posts++;
		}

		// track last submission
		include_once $context['path_to_root'].'shared/values.php';
		Values::set('mailer.last.queued', $subject.' ('.$posts.' recipients)');

		// return the number of actual posts
		return $posts;
	}

	/**
	 * actual transmission of a mail message
	 *
	 * This function transmit messages to a mail server, as per SMTP protocol.
	 *
	 * @param string destination address
	 * @param string message subject line
	 * @param string message content
	 * @param mixed message headers
	 * @return int the number of transmitted messages, O on error
	 */
	function process($recipient, $subject, $message, $headers='') {
		global $context;

		// email services have to be activated
		if(!isset($context['with_email']) || ($context['with_email'] != 'Y')) {
			Logger::error(i18n::s('E-mail has not been enabled on this system.'));
			return 0;

		// check recipient address
		} elseif(!$recipient) {
			Logger::error(i18n::s('Empty recipient address'));
			return 0;

		// check mail subject
		} elseif(!$subject) {
			Logger::error(i18n::s('No subject'));
			return 0;

		// check mail content
		} elseif(!$message) {
			Logger::error(i18n::s('No message'));
			return 0;
		}

		// decode recipient for log
		$decoded_recipient = $recipient;
		if(preg_match('/^=\?[^\?]+\?B\?(.*)=$/i', $recipient, $matches))
			$decoded_recipient = base64_decode($matches[1]);

		// extract the actual e-mail address -- Foo Bar <foo@bar.com> => foo@bar.com
		$tokens = explode(' ', $decoded_recipient);
		$actual_recipient = trim(str_replace(array('<', '>'), '', $tokens[count($tokens)-1]));

		// decode subject for log
		$decoded_subject = $subject;
		if(preg_match('/^=\?[^\?]+\?B\?(.*)=$/i', $subject, $matches))
			$decoded_subject = base64_decode($matches[1]);

		// connect to the mail server
		if(!isset($context['mail_handle']) && !Mailer::connect())
			return 0;

		// we manage directly the SMTP transaction
		if(isset($context['mail_variant']) && (($context['mail_variant'] == 'pop3') || ($context['mail_variant'] == 'smtp'))) {
			$handle = $context['mail_handle'];

			// determine the From: address
			if(isset($context['mail_from']) && $context['mail_from'])
				$from = $context['mail_from'];
			else
				$from = $context['host_name'];

			// the adress to use on error
			if(preg_match('/<([^>]+)>/', $from, $matches))
				$address = $matches[1];
			else
				$address = trim($from);

			// say who we are
			$request = 'MAIL FROM:<'.$address.'>';
			fputs($handle, $request.CRLF);
			if($context['debug_mail'] == 'Y')
				Logger::remember('shared/mailer.php', 'SMTP ->', $request, 'debug');

			// expecting an OK
			if(Mailer::parse_response($handle, 250) === FALSE) {
				Logger::remember('shared/mailer.php', 'Command MAIL FROM has been rejected at '.$server);
				Mailer::close();
				return 0;
			}

			// provide destination address
			$request = 'RCPT TO:<'.$actual_recipient.'>';
			fputs($handle, $request.CRLF);
			if($context['debug_mail'] == 'Y')
				Logger::remember('shared/mailer.php', 'SMTP ->', $request, 'debug');

			// expecting an OK
			if(Mailer::parse_response($handle, 250) === FALSE) {
				Logger::remember('shared/mailer.php', 'Command RCPT TO has been rejected at '.$server);
				Mailer::close();
				return 0;
			}

			// actual transmission
			$request = 'DATA';
			fputs($handle, $request.CRLF);
			if($context['debug_mail'] == 'Y')
				Logger::remember('shared/mailer.php', 'SMTP ->', $request, 'debug');

			// expecting an OK
			if(Mailer::parse_response($handle, 354) === FALSE) {
				Logger::remember('shared/mailer.php', 'Command DATA has been rejected at '.$server);
				Mailer::close();
				return 0;
			}

			// make some text out of an array
			if(is_array($headers))
				$headers = implode("\n", $headers);

			// From: header
			if(!preg_match('/^From: /im', $headers))
				$headers .= "\n".'From: '.$from;

			// To: header
			if(!preg_match('/^To: /im', $headers))
				$headers .= "\n".'To: '.$recipient;

			// prepare message headers
			$headers = trim($headers."\n".'Subject: '.$subject)."\n";

			// reenforce SMTP specification
			$headers = str_replace("\n", CRLF, $headers);

			// append message body
			$request = $headers.CRLF.$message.CRLF.'.'.CRLF;

			// actual post
			fputs($handle, $request);
			if($context['debug_mail'] == 'Y')
				Logger::remember('shared/mailer.php', 'SMTP ->', $request, 'debug');

			// expecting an OK
			if(Mailer::parse_response($handle, 250) === FALSE) {
				Logger::remember('shared/mailer.php', 'Message has been rejected at '.$server);
				Mailer::close();
				return 0;
			}

		// rely on system settings and PHP
		} elseif(is_callable('mail')) {

			// submit the post
			if(!@mail($actual_recipient, $subject, $message, $headers)) {
				if(isset($context['debug_mail']) && ($context['debug_mail'] == 'Y'))
					Logger::remember('shared/mailer.php', sprintf(i18n::s('Error while sending the message to %s'), $decoded_recipient), $decoded_subject, 'debug');
				elseif($context['with_debug'] == 'Y')
					Logger::remember('shared/mailer.php', sprintf(i18n::s('Error while sending the message to %s'), $decoded_recipient), $decoded_subject, 'debug');
				return 0;
			}

		// don't know how to send messages
		} else {
			Logger::remember('shared/mailer.php', i18n::s('E-mail has not been enabled on this system.'));
			return 0;
		}

		// track last post
		include_once $context['path_to_root'].'shared/values.php';
		Values::set('mailer.last.posted', $decoded_subject.' ('.$decoded_recipient.')');

		// job done
		if($context['debug_mail'] == 'Y')
			Logger::remember('shared/mailer.php', 'one message has been transmitted to '.$decoded_recipient, $decoded_subject, 'debug');
		return 1;

	}

	/**
	 * defer the processing of one message
	 *
	 * This function saves provided data in the database, except if the flow of messages is not
	 * shaped.
	 *
	 * @param string the target address
	 * @param string message subject
	 * @param string message content
	 * @param string optional headers
	 * @return int the number of queued messages, or 0 on error
	 */
	function queue($recipient, $subject, $message, $headers='') {
		global $context;

		// we don't have to rate messages
		if(!isset($context['mail_hourly_maximum']) || ($context['mail_hourly_maximum'] < 1))
			return Mailer::process($recipient, $subject, $message, $headers);

		// transaction attributes
		$query = array();
		$query[] = "edit_date='".SQL::escape(gmstrftime('%Y-%m-%d %H:%M:%S'))."'";
		$query[] = "headers='".SQL::escape($headers)."'";
		$query[] = "message='".SQL::escape($message)."'";
		$query[] = "recipient='".SQL::escape($recipient)."'";
		$query[] = "subject='".SQL::escape($subject)."'";

		// insert a new record
		$query = "INSERT INTO ".SQL::table_name('messages')." SET ".implode(', ', $query);
		if(SQL::query($query) === FALSE)
			return 0;
		return 1;
	}

	/**
	 * prepare for message threading
	 *
	 * @link http://www.jwz.org/doc/threading.html message threading
	 *
	 * @param string unique id for this message
	 * @param string thread context for this message
	 * @return array headers to be used by Mailer::post()
	 */
	function set_thread($this_id=NULL, $parent_id=NULL) {
		global $context;

		$headers = array();

		// just help to overcome spam filters
		if(!$this_id)
			$this_id = time();

		// Message-ID: header
		$header[] = 'Message-ID: <'.str_replace(array('@', '>', ':'), array('', '', '.'), $this_id).'@'.$context['host_name'].'>';

		// In-Reply-To: header
		if($parent_id) {
			if(is_object($parent_id))
				$parent_id = $parent_id->get_reference();
			$header[] = 'In-Reply-To: <'.str_replace(array('@', '>', ':'), array('', '', '.'), $parent_id).'@'.$context['host_name'].'>';
		}

		return $headers;
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
		$fields['recipient']	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['subject']		= "VARCHAR(255) DEFAULT '' NOT NULL";						// up to 255 chars

		$indexes = array();
		$indexes['PRIMARY KEY'] 		= "(id)";
		$indexes['INDEX edit_date'] 	= "(edit_date)";

		return SQL::setup_table('messages', $fields, $indexes);

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

		// email services have to be activated
		if(!isset($context['with_email']) || ($context['with_email'] != 'Y'))
			return;

		// useless if we don't have a valid database connection
		if(!$context['connection'])
			return;

		// remember start time
		$start = get_micro_time();

		// get bucket size --force it if set to 0
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

			// preserve previous value until actual leak
			if($leak < 1)
				return;

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

					}

				}

				// close connection
				Mailer::close();
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
		elseif($bucket['value'])
			return 'shared/mailer.php: delaying messages ('.$time.' seconds)'.BR;
		else
			return 'shared/mailer.php: nothing to do ('.$time.' seconds)'.BR;
	}

}

?>