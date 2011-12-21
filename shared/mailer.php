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
	 * prepare a multi-part message
	 *
	 * @param string message HTML tags or ASCII content
	 * @return array containing message parts ($type => $content)
	 */
	public static function build_multipart($text) {
		global $context;

		// make a full html entity --body attributes are ignored most of the time
		$text = '<html><body><font face="Helvetica, Arial, sans-serif">'.$text.'</font></body></html>';

		// change links to include host name
		$text = str_replace(' href="/', ' href="'.$context['url_to_home'].'/', $text);
		$text = str_replace(' src="/', ' src="'.$context['url_to_home'].'/', $text);

		// one element per type
		$message = array();

		// text/plain part has no tag anymore
		$replacements = array('/<a [^>]*?><img [^>]*?><\/a>/i' => '', // suppress clickable images
			"/<a href=\"([^\"]+?)\"([^>]*?)>\\1<\/a>/i" => "\\1",	// un-twin clickable links
			'/<a href=\"([^\"]+?)" ([^>]*?)>(.*?)<\/a>/i' => "\\3 \\1", // label and link
			'/<a href=\"([^\"]+?)">(.*?)<\/a>/i' => "\\2 \\1", // label and link too
			'/<(br *\/{0,1}|h1|\/h1|h2|\/h2|h3|\/h3|h4|\/h4|h5|\/h5|p|\/p|\/td|\/title)>/i' => "<\\1>\n",
			'/&nbsp;/' => ' ');
		$message['text/plain; charset=utf-8'] = utf8::from_unicode(utf8::encode(trim(strip_tags(preg_replace(array_keys($replacements), array_values($replacements), $text)))));

		// transform the text/html part
		$replacements = array('/<dl[^>]*?>(.*?)<\/dl>/i' => '<table>\\1</table>', 					// <dl> ... </dl> -> <table> ... </table>
			'/<dt[^>]*?>(.*?)<\/dt>/i' => '<tr><td style:"margin-right: 10px">\\1</td>',	// <dt> ... </dt> -> <tr><td> ... </td>
			'/<dd[^>]*?>(.*?)<\/dd>/i' => '<td>\\1</td></tr>',						// <dd> ... </dd> -> <tr><td> ... </td>
			'/class="grid"/i' => 'border="1"',								// display grid borders
			'/on(click|keypress)="([^"]+?)"/i' => '', 							// remove onclick="..." and onkeypress="..." attributes
			'/<script[^>]*?>(.*?)<\/script>/i' => '',								// remove <script> ... </script> --Javascript considered as spam
			'/<style[^>]*?>(.*?)<\/style>/i' => '');								// remove <style> ... </style> --use inline style instead

		// text/html part
		$message['text/html; charset=utf-8'] = preg_replace(array_keys($replacements), array_values($replacements), $text);

		// return all parts
		return $message;
	}

	/**
	 * format a notification to be send by e-mail
	 *
	 * This function appends a reason string, and format the full content
	 * as template provided by the skin.
	 *
	 * Depending of the reason, the notification will have the following kind of trail:
	 * - 0 - no trail
	 * - 1 - you are watching the container
	 * - 2 - you are watching the poster
	 *
	 * You can change function build_mail_message() in your skin.php to use a customized template.
	 *
	 * @param string bare message content
	 * @param int reason for notification
	 * @return string text to be put in message
	 */
	public static function build_notification($text, $reason=0) {
		global $context;

		// decode the reason
		switch($reason) {

		case 0: // no trail
		default:
			break;

		case 1: // you are watching some container
			$text .= '<p>&nbsp;</p><p>'.sprintf(i18n::c('This message has been generated automatically by %s since the new item has been posted in a web space that is part of your watch list. If you wish to stop some notifications please review watched elements listed in your user profile.'), $context['site_name']).'</p>';
			break;

		case 2: // you are watching the poster
			$text .= '<p>&nbsp;</p><p>'.sprintf(i18n::c('This message has been generated automatically by %s since you are following the person who posted the new item. If you wish to stop these automatic alerts please visit the user profile below and click on Stop notifications.'), $context['site_name']).'</p>'
				.'<p><a href="'.$context['url_to_home'].$context['url_to_root'].Surfer::get_permalink().'">'.ucfirst(strip_tags(Surfer::get_name()))
				.'</a></p>';
			break;

		}

		// job done
		return $text;
	}

	/**
	 * retrieve recipients of last post
	 *
	 * This is useful to list all persons notified after a post for example.
	 *
	 * @return mixed text to be integrated into the page
	 */
	public static function build_recipients() {
		global $context;

		// nothing to show
		if(!isset($context['mailer_recipients']))
			return '';

		// title mentions number of recipients
		$count = count($context['mailer_recipients']);
		$title = sprintf(i18n::ns('%d person has been notified', '%d persons have been notified', $count), $count);

		// return the bare list
		if(!$title)
			return $context['mailer_recipients'];

		// build a nice list
		$list = array();
		if($count > 50)
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
		return '<hr align="left" size="1" width="200" />'.Skin::build_box($title, Skin::finalize_list($list, 'compact'), 'folded');

	}

	/**
	 * close connection to mail server
	 *
	 * This function gracefully ends the transmission of messages.
	 */
	public static function close() {
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
	private static function connect() {
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
	 * decode a quoted printable string
	 *
	 * @link http://en.wikipedia.org/wiki/Quoted-printable
	 *
	 * @param string the encoded string
	 * @return string the original string
	 */
	public static function decode_from_quoted_printable($text) {
		global $context;

		// remove soft line breaks
		$text = preg_replace('/=\r?\n/', '', $text);

		// decode single entities
		$text = preg_replace('/=([A-F0-9]{2})/ie', chr(hexdec('\\1' )), $text);

		// decoded string
		return $text;

	}

	/**
	 * encode message recipient
	 *
	 * @param string the original recipient mail address
	 * @param string the original recipient name, if any
	 * @return string the encoded recipient
	 */
	public static function encode_recipient($address, $name=NULL) {

		// no space nor HTML tag in address
		$address = preg_replace('/\s/', '', strip_tags($address));

		// if a name is provided
		if($name) {

			// don't break the list of recipients
			$name = str_replace(array(',', '"'), ' ', $name);

			// at the moment we only accept ASCII names
			$name = utf8::to_ascii($name, PRINTABLE_SAFE_ALPHABET);

			// the full recipient
			$recipient = '"'.$name.'" <'.$address.'>';

		// else use the plain mail address
		} else
			$recipient = $address;

		// done
		return $recipient;
	}

	/**
	 * encode message subject
	 *
	 * This function preserves the original subject line if it only has ASCII characters.
	 * Else is encodes it using UTF-8.
	 *
	 * @param string the original subject
	 * @return string the encoded subject
	 */
	public static function encode_subject($text) {

		// no new line nor HTML tag in title
		$text = preg_replace('/\s+/', ' ', strip_tags($text));

		// encode if text is not pure ASCII - ' ' = 0x20 and 'z' = 0x7a
		if(preg_match('/[^ -z]/', $text)) {

			// make it utf-8
			$text = utf8::from_unicode($text);

			// encode it for the transfer --see RFC 2047
			$text = '=?utf-8?B?'.base64_encode($text).'?=';

		}

		// done
		return $text;
	}

	/**
	 * adapt content to legacy transmission pipes
	 *
	 * @link http://en.wikipedia.org/wiki/Quoted-printable
	 *
	 * @param string the original string
	 * @return string the encoded string
	 */
	public static function encode_to_quoted_printable($text) {
		global $context;

		// split lines
		$lines = preg_split("/\r?\n/", $text);

		// encoding all lines
		$text = '';
		foreach($lines as $line) {

			// encoded line
			$encoded = '';

			// one char at a time
			$len = strlen($line);
			for($index = 0; $index <= $len - 1; $index++) {
				$char = substr($line, $index, 1);
				$code = ord($char);

				// encode this char
				if(($code < 32) || ($code == 61) || ($code > 126))
					$char = '='.strtoupper(dechex($code));

				// insert a soft newline to break a long line
				if((strlen($encoded)+strlen($char)) >= 76) {
					$text .= $encoded.'='.CRLF;
					$encoded = '';
				}

				// append the encoded char
				$encoded .= $char;
			}

			// update the output buffer
			$text .= $encoded.CRLF;
		}

		// encoded string
		return $text;

	}

	/**
	 * explode a list of recipients
	 *
	 * @param string a list of recipients
	 * @return array an array of recipients
	 */
	public static function explode_recipients($text) {

		// we want to split recipients
		$recipients = array();

		// parse the provided string
		$head = 0;
		$index_maximum = strlen($text);
		$quoted = FALSE;
		for($index = 0; $index < $index_maximum; $index++) {

			// start quoted string
			if(!$quoted && ($text[$index] == '"'))
				$quoted = TRUE;

			// end of quoted string
			elseif($quoted && ($text[$index] == '"'))
				$quoted = FALSE;

			// separator
			elseif(!$quoted && ($text[$index] == ',')) {
				if($index > $head)
					$recipients[] = trim(substr($text, $head, $index-$head));
				$head = $index+1;
			}
		}

		// don't forget the last recipient
		if($head < $index_maximum)
			$recipients[] = trim(substr($text, $head));

		// return an array of recipients
		return $recipients;
	}

	/**
	 * get the default From: address
	 *
	 * This function should be invoked to originate all notifications and messages sent
	 * by this server.
	 *
	 * @return string either the address of the current surfer, or the address of the web server itself
	 */
	public static function get_from_recipient() {
		global $context;

		// determine the From: address
		if(isset($context['mail_from']) && $context['mail_from'])
			$from = $context['mail_from'];
		else
			$from = $context['host_name'];

		// done
		return $from;
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
	 * @param array to be given to Mailer::post()
	 * @return TRUE on success, FALSE otherwise
	 *
	 * @see agents/messages.php
	 * @see control/configure.php
	 * @see query.php
	 * @see shared/logger.php
	 * @see users/users.php
	 */
	public static function notify($from, $to, $subject, $message, $headers='', $attachments=NULL) {
		global $context;

		// email services have to be activated
		if(!isset($context['with_email']) || ($context['with_email'] != 'Y'))
			return FALSE;

		// use surfer's address only if this has been explicitly allowed
		if(!isset($context['mail_from_surfer']) || ($context['mail_from_surfer'] != 'Y'))
			$from = NULL;

		// ensure we have a sender
		if(!$from)
			$from = Mailer::get_from_recipient();

		// add site name to message title
		else
			$subject .= ' ['.$context['site_name'].']';

		// allow for skinnable template
		$message = Skin::build_mail_message($message);

		// build multiple parts, for HTML rendering
		$message = Mailer::build_multipart($message);

		// do the job -- don't stop on error
		if(Mailer::post($from, $to, $subject, $message, $attachments, $headers))
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
	private static function parse_response($handle, $expected) {
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
	 * You can refer to local images in HTML parts, and the function will automatically attach these
	 * to the message, else mail clients would not display them correctly.
	 *
	 * The message actually sent has a complex structure, with several parts assembled together,
	 * as [explained at altepeter.net|http://altepeter.net/tech/articles/html-emails].
	 *
	 * @link http://altepeter.net/tech/articles/html-emails
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
	 * the string ##To: Foo Bar <foo@bar.com>## is added to headers.
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
	 * It is recommended to begin with the bare text, and to have the rich format part coming
	 * after, as in the example. Also, if you don't provide a charset, then UTF-8 is used.
	 *
	 * Long lines of text/plain parts are wrapped according to
	 * [link=Dan's suggestion]http://mailformat.dan.info/body/linelength.html[/link].
	 *
	 * @link http://mailformat.dan.info/body/linelength.html Dan's Mail Format Site: Body: Line Length
	 *
	 * Message parts are encoded either as quoted-printable (textual entities) or as base-64 (others).
	 *
	 * A list of files to be attached to the message can be provided as in the following example:
	 *
	 * [php]
	 * $attachments = array();
	 * $attachments[] = 'special/report.pdf';
	 * $attachments[] = 'skins/my_skin/newsletters/image.png';
	 * Mailer::post($from, $to, $subject, $message, $attachments);
	 * [/php]
	 *
	 * Files are named from the installation directory of yacs, as visible in the examples.
	 *
	 * This function returns the number of successful posts,
	 * and populates the error context, where applicable.
	 *
	 * @param string sender address
	 * @param mixed recipient address(es)
	 * @param string subject
	 * @param mixed actual message, either a string, or an array of message parts
	 * @param array attachments, if any
	 * @param mixed additional headers, if any
	 * @return the number of actual posts, or 0
	 *
	 * @see articles/mail.php
	 * @see letters/new.php
	 * @see users/mail.php
	 */
	public static function post($from, $to, $subject, $message, $attachments=NULL, $headers='') {
		global $context;

		// ensure that we have a sender
		if(!$from)
			$from = Mailer::get_from_recipient();

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

		// the end of line string for mail messages
		if(!defined('M_EOL'))
			define('M_EOL', "\n");

		// encode the subject line
		$subject = Mailer::encode_subject($subject);

		// make some text out of an array
		if(is_array($headers))
			$headers = implode(M_EOL, $headers);

		// From: header
		if(!preg_match('/^From: /im', $headers))
			$headers .= M_EOL.'From: '.$from;

		// Reply-To: header
		if(!preg_match('/^Reply-To: /im', $headers))
			$headers .= M_EOL.'Reply-To: '.$from;

		// Return-Path: header --to process errors
		if(!preg_match('/^Return-Path: /im', $headers))
			$headers .= M_EOL.'Return-Path: '.$from;

		// Message-ID: header --helps to avoid spam filters
		if(!preg_match('/^Message-ID: /im', $headers))
			$headers .= M_EOL.'Message-ID: <'.time().'@'.$context['host_name'].'>';

		// MIME-Version: header
		if(!preg_match('/^MIME-Version: /im', $headers))
			$headers .= M_EOL.'MIME-Version: 1.0';

		// arrays are easier to manage
		if(is_string($message)) {

			// turn HTML entities to UTF-8
			$message = Safe::html_entity_decode($message, ENT_QUOTES, 'UTF-8');

			$copy = $message;
			$message = array();
			$message['text/plain; charset=utf-8'] = $copy;
			unset($copy);
		}

		// turn attachments to some array too
		if(is_string($attachments))
			$attachments = array( $attachments );
		elseif(!is_array($attachments))
			$attachments = array();

		// we only consider objects from this server
		$my_prefix = $context['url_to_home'].$context['url_to_root'];

		// transcode objects that will be transmitted along the message (i.e., images)
		foreach($message as $type => $part) {

			// search throughout the full text
			$head = 0;
			while($head = strpos($part, ' src="', $head)) {
				$head += strlen(' src="');

				// a link has been found
				if($tail = strpos($part, '"', $head+1)) {
					$reference = substr($part, $head, $tail-$head);

					// remember local links only
					if(!strncmp($reference, $my_prefix, strlen($my_prefix))) {

						// local name
						$name = urldecode(substr($reference, strlen($my_prefix)));

						// content-id to be used instead of the link
						$cid = sprintf('%u@%s', crc32($name), $context['host_name']);

						// transcode the link in this part
						$part = substr($part, 0, $head).'cid:'.$cid.substr($part, $tail);

						// remember to put content in attachments of this message
						$attachments[] = $name;
					}
				}
			}

			// remember the transcoded part
			$message[ $type ] = $part;
		}

		// we need some boundary string
		if((count($message) + count($attachments)) > 1)
			$boundary = md5(time());

		// wrapping threshold
		if(!defined('WRAPPING_LENGTH'))
			define('WRAPPING_LENGTH', 70);

		// combine message parts
		$content_type = '';
		$body = '';
		foreach($message as $type => $part) {

			// quote textual entities
			if(!strncmp($type, 'text/', 5)) {
				$content_encoding = 'quoted-printable';
				$part = Mailer::encode_to_quoted_printable($part);

			// encode everything else
			} else {
				$content_encoding = 'base64';
				$part = chunk_split(base64_encode($content), 76, M_EOL);
			}

			// only one part
			if(count($message) == 1) {
				$content_type = $type;
				$body = $part;

			// one part among several
			} else {

				// let user agent select among various alternatives
				if(!$content_type)
					$content_type = 'multipart/alternative; boundary="'.$boundary.'-internal"';

				// introduction to assembled parts
				if(!$body)
					$body = 'This is a multi-part message in MIME format.';

				// this part only --second EOL is part of the boundary chain
				$body .= M_EOL.M_EOL.'--'.$boundary.'-internal'
					.M_EOL.'Content-Type: '.$type
					.M_EOL.'Content-Transfer-Encoding: '.$content_encoding
					.M_EOL.M_EOL.$part;

			}
		}

		// finalize the body
		if(count($message) > 1)
			$body .= M_EOL.M_EOL.'--'.$boundary.'-internal--';

		// a mix of things
		if(count($attachments)) {

			// encoding is irrelevant if there are multiple parts
			if(!strncmp($content_type, 'multipart/', 10))
				$content_encoding = '';
			else
				$content_encoding = M_EOL.'Content-Transfer-Encoding: '.$content_encoding;

			// identify the main part of the overall message
			$content_start = 'mainpart';

			// the current body becomes the first part of a larger message
			$body = 'This is a multi-part message in MIME format.'.M_EOL
				.M_EOL.'--'.$boundary.'-external'
				.M_EOL.'Content-Type: '.$content_type
				.$content_encoding
				.M_EOL.'Content-ID: <'.$content_start.'>'
				.M_EOL.M_EOL.$body;

			// message parts should be considered as an aggregate whole --see RFC 2387
			$content_type = 'multipart/related; type="multipart/alternative"; boundary="'.$boundary.'-external"';
			$content_encoding = '';

			// process every file
			foreach($attachments as $name => $content) {

				// read external file content
				if(preg_match('/^[0-9]+$/', $name)) {

					// only a file name has been provided
					$name = $content;

					// read file content from the file system
					if(!$content = Safe::file_get_contents($name))
						continue;

				}

				// file name is the file type
				if(preg_match('/name="(.+)?"/', $name, $matches)) {
					$type = $name;
					$name = $matches[1];

				} else
					$type = Files::get_mime_type($name);

				// set a name that avoids problems
				$basename = utf8::to_ascii(basename($name));

				// a unique id for for this file
				$cid = sprintf('%u@%s', crc32($name), $context['host_name']);

				// headers for one file
				$body .= M_EOL.M_EOL.'--'.$boundary.'-external'
					.M_EOL.'Content-Type: '.$type
					.M_EOL.'Content-Disposition: inline; filename="'.str_replace('"', '', $basename).'"'
					.M_EOL.'Content-ID: <'.$cid.'>';

				// transfer textual entities as they are
				if(!strncmp($type, 'text/', 5)) {
					$body .= M_EOL.'Content-Transfer-Encoding: quoted-printable'
						.M_EOL.M_EOL.Mailer::encode_to_quoted_printable($content);

				// encode everything else
				} else {
					$body .= M_EOL.'Content-Transfer-Encoding: base64'
						.M_EOL.M_EOL.chunk_split(base64_encode($content), 76, M_EOL);
				}

			}

			// the closing boundary
			$body .= M_EOL.M_EOL.'--'.$boundary.'-external--';

		}


		// Content-Type: header
		if($content_type && !preg_match('/^Content-Type: /im', $headers))
			$headers .= M_EOL.'Content-Type: '.$content_type;

		// Content-Transfer-Encoding: header
		if(!isset($boundary) && $content_encoding && !preg_match('/^Content-Transfer-Encoding: /im', $headers))
			$headers .= M_EOL.'Content-Transfer-Encoding: '.$content_encoding;

		// Start: header
		if(isset($boundary) && isset($content_start) && $content_start && !preg_match('/^Start: /im', $headers))
			$headers .= M_EOL.'Start: '.$content_start;

		// X-Mailer: header --helps to avoid spam filters
		if(!preg_match('/^X-Mailer: /im', $headers))
			$headers .= M_EOL.'X-Mailer: yacs';

		// strip leading spaces and newlines
		$headers = trim($headers);

		// make an array of recipients
		if(!is_array($to))
			$to = Mailer::explode_recipients($to);

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
			Mailer::queue($recipient, $subject, $body, $headers);
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
	private static function process($recipient, $subject, $message, $headers='') {
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

		// mail() is expecting a string
		if(is_array($headers))
			$headers = implode("\n", $headers);

		// determine the From: address
		if(isset($context['mail_from']) && $context['mail_from'])
			$from = $context['mail_from'];
		else
			$from = $context['host_name'];

		// From: header is mandatory
		if(!preg_match('/^From: /im', $headers))
			$headers .= "\n".'From: '.$from;

		if($context['debug_mail'] == 'Y') {

			$all_headers = 'Subject: '.$subject."\n".'To: '.$decoded_recipient."\n".$headers;

			Logger::remember('shared/mailer.php', 'sending a message to '.$decoded_recipient, $all_headers."\n\n".$message, 'debug');
			Safe::file_put_contents('temporary/mailer.process.txt', $all_headers."\n\n".$message);
		}

		// connect to the mail server
		if(!isset($context['mail_handle']) && !Mailer::connect())
			return 0;

		// we manage directly the SMTP transaction
		if(isset($context['mail_variant']) && (($context['mail_variant'] == 'pop3') || ($context['mail_variant'] == 'smtp'))) {
			$handle = $context['mail_handle'];

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
				Logger::remember('shared/mailer.php', 'Command MAIL FROM has been rejected by server');
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
				Logger::remember('shared/mailer.php', 'Command RCPT TO has been rejected by server');
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
				Logger::remember('shared/mailer.php', 'Command DATA has been rejected by server');
				Mailer::close();
				return 0;
			}

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
				Logger::remember('shared/mailer.php', 'Message has been rejected by server');
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
	 * This function either processes a message immediately, or it saves provided data in the
	 * database for later processing.
	 *
	 * @param string the target address
	 * @param string message subject
	 * @param string message content
	 * @param string optional headers
	 * @return int the number of queued messages, or 0 on error
	 */
	private static function queue($recipient, $subject, $message, $headers='') {
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
	public static function set_thread($this_id=NULL, $parent_id=NULL) {
		global $context;

		$headers = array();

		// just help to overcome spam filters
		if(!$this_id)
			$this_id = 'object';

		// Message-ID: header
		$headers[] = 'Message-ID: <'.str_replace(array('@', '>', ':'), array('', '', '.'), $this_id).'.'.time().'@'.$context['host_name'].'>';

		// In-Reply-To: header
		if($parent_id) {
			if(is_object($parent_id))
				$parent_id = $parent_id->get_reference();
			$headers[] = 'In-Reply-To: <'.str_replace(array('@', '>', ':'), array('', '', '.'), $parent_id).'@'.$context['host_name'].'>';
		}

		return $headers;
	}

	/**
	 * create tables for queued messages
	 */
	public static function setup() {
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
	public static function tick_hook() {
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
				while($item = SQL::fetch($result)) {

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
