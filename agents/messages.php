<?php
/**
 * process inbound mail messages
 *
 * @todo should be able to decode UTF-8 from YACS newsletters (dobliu)
 * @todo strip HTML (dobliu)
 *
 * This script is triggered through the hook 'tick'.
 * It scans one or more POP3 accounts and processes received messages.
 *
 * [*] Mail accounts that are scanned are configured in [script]agents/configure.php[/script].
 * Account information is stored in [code]parameters/agents.include.php[/code].
 *
 * [*] Messages are transferred to the YACS server and deleted from the mail server.
 * The number of fetched messages per tick is capped.
 *
 * [*] Only associates and explicitly given mail boxes can use to send messages.
 * Following keywords can also be used to enable '[code]any_member[/code]',
 * '[code]any_subscriber[/code]', and '[code]anyone[/code].
 * Opening the door can be useful to catch error messages, but use this feature cautiously because of spammers.
 *
 * [*] Security can be enforced through pattern matching on a per-queue basis.
 * Useful to check that only a given mailing list server is using your e-mail address.
 *
 * [*] Messages are parsed to break content into article components if any:
 * introduction, source, section, categories, author.
 *
 * [*] Messages tagged with either a ##article:123## or ##section:456## label are posted as comments of the referenced anchor.
 * This feature is useful to capture thread information by e-mail.
 * For example, support teams can label their e-mail messages to customers,
 * to have these messages automatically recorded at the right place in the database.
 *
 * [*] Articles can be auto-publish if the queue has been configured for that,
 * if the target section is enabled for that, or if the server has been configured to work
 * in Wiki-mode.
 *
 * [*] Any mail message successfully processed is submitted to modules hooked to the id 'inbound_mail',
 * and to any specific ids listed into the configuration field 'processing hooks'.
 *
 * Message components are put in $context['mail_body'] and $context['mail_headers'] for further processing
 * by included modules. Also, queue attributes are provided into $context['mail_queue'].
 *
 * This design allows for generic hooks, bound on id 'inbound_mail', to process every input message.
 * Also, particular mail processors can be defined on specific ids, and used only on selected mail queues.
 *
 * Possible use of e-mail processing:
 *
 * - blogging - send a structured message and that's it - sender appears in the list of community members
 *
 * - importing data - subscribe to mailing lists and that's it - first application is to fetch and process security bulletins
 *
 * - asynchronous RPC - reporting to a central server for example
 *
 * - error catching - to identify invalid e-mail recipient addresses on newsletter sending
 *
 * One application (suggested by Antoine)  is to dispatch security bulletins collected by e-mail.
 * A single agents/messages_dispatch_hook.php script is enough to achieve this.
 * On mail reception, this script will extract keywords from mail body, and look for user profiles having
 * one or more of these keywords in the 'tags' field.
 * Then the script forwards the incoming message to each matching user,
 * using the e-mail address mentioned in user profiles.
 *
 * This script is hooked on id 'messages_dispatch' and, therefore,
 * will be triggered only on queues having this id into their hook list.
 *
 * Another application is to automatically disable invalid e-mail addresses.
 * agents/messages_feedback_hook.php will be hooked on id 'messages_feedback'.
 *
 *
 * YACS parses input queues and uses regular expressions to locate weblog attributes.
 * Following tags are allowed into a textual entity to be posted as a page:
 *
 * [*] '[code]anchor[/code]' - the anchor for this post.
 * Example: [code]&lt;anchor&gt;section:234&lt;/anchor&gt;[/code].
 *
 * [*] '[code]author[/code]' - the nick name or user id of the author.
 * Example: [code]&lt;author&gt;Alfred&lt;/author&gt;[/code].
 *
 * [*] '[code]blogid[/code]' - an alias for  '[code]section[/code]'
 *
 * [*] '[code]categories[/code]' - a comma-separated list of categories for this post.
 * Example: [code]&lt;categories&gt;PHP, MySQL, Apache, interesting subjects&lt;/categories&gt;[/code].
 *
 * [*] '[code]introduction[/code]' - text to be used as blog excerpt.
 * Example: [code]&lt;introduction&gt;Hello World&lt;/introduction&gt;[/code].
 *
 * [*] '[code]section[/code]' - the id or the nick name of the section to be used
 * Example: [code]&lt;section&gt;news&lt;/section&gt;[/code].
 *
 * [*] '[code]source[/code]' - reference link for this blog entry
 * Example: [code]&lt;source&gt;http://www.yetanothercommunitysystem.com/yacs/index.php&lt;/source&gt;[/code].
 *
 * [*] '[code]title[/code]' - the title to use for this entry
 * Example: [code]&lt;title&gt;What'up doc?&lt;/title&gt;[/code].
 *
 *
 * Following parameters are used while processing inbound messages:
 *
 * - [code]debug_messages[/code] - if set to Yes, then processed messages will be saved
 * for debugging purpose.
 *
 * - [code]mail_queues[][/code] - An array of mail accounts to poll.
 * Each entry has a name, and following attributes: server network address, account name, account password,
 * default section, security match, processing options, processing hooks, prefix boundary, suffix boundary.
 *
 * Following options may be used to change the behaviour of this script:
 *
 * - [code]auto_publish[/code] to force the automatic publishing of articles received from this queue.
 *
 * - [code]no_reply[/code] to not send a feed-back message. Useful when capturing data from mailing lists,
 * to avoid bouncing messages.
 *
 * These parameters are set in [script]agents/configure.php[/script], and saved in parameters/agents.include.php.
 *
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @tester Guillaume Perez
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

class Messages {

	/**
	 * transform text back to its original format
	 *
	 * According to RFC2045:
	 *
	 * Three transformations are currently defined: identity, the "quoted-
	 * printable" encoding, and the "base64" encoding.	The domains are
	 * "binary", "8bit" and "7bit".
	 *
	 * The Content-Transfer-Encoding values "7bit", "8bit", and "binary" all
	 * mean that the identity (i.e. NO) encoding transformation has been
	 * performed.  As such, they serve simply as indicators of the domain of
	 * the body data, and provide useful information about the sort of
	 * encoding that might be needed for transmission in a given transport
	 * system.
	 *
	 * The quoted-printable and base64 encodings transform their input from
	 * an arbitrary domain into material in the "7bit" range, thus making it
	 * safe to carry over restricted transports.
	 *
	 * @link http://www.faqs.org/rfcs/rfc2045.html MIME Extensions part one
	 *
	 * @param string the encoded text
	 * @param string the encoded method
	 * @return the transformed string
	 */
	function decode($text, $method) {

		// quoted-printable
		if(preg_match('/quoted-printable/i', $method)) {

			// remove soft line breaks
			$text = preg_replace("/=\r?\n/", '', $text);

			// replace encoded characters
			return preg_replace('/=([a-f0-9]{2})/ie', "chr(hexdec('\\1'))", $text);

		// base64
		} elseif(preg_match('/base64/i', $method)) {
			return base64_decode($text);

		// 7bit, 8bit, binary, or whatever
		} else
			return $text;

	}

	/**
	 * unfold and decode message headers
	 *
	 * Headers names and fields have to match RFC822 specification:
	 *
	 * Once a field has been unfolded, it may be viewed as being com-
	 * posed of a field-name followed by a colon (":"), followed by a
	 * field-body, and	terminated	by	a  carriage-return/line-feed.
	 * The	field-name must be composed of printable ASCII characters
	 * (i.e., characters that  have  values  between  33.  and	126.,
	 * decimal, except colon).	The field-body may be composed of any
	 * ASCII characters, except CR or LF.  (While CR and/or LF may be
	 * present	in the actual text, they are removed by the action of
	 * unfolding the field.)
	 *
	 * Also, headers are supposed to be decoded according to RFC2047, more or less.
	 *
	 * @link http://www.faqs.org/rfcs/rfc822.html Internet text messages
	 * @link http://www.faqs.org/rfcs/rfc2047.html MIME Message Header Extensions for Non-ASCII Text
	 *
	 * @param string some raw text
	 * @return an array of decoded headers ('name' => $name, 'value' => $value)
	 */
	function parse_headers($text) {

		if(!$text)
			return array();

		// unfold lines
		$text = preg_replace("/\r?\n/", "\015\012", $text);
		$text = preg_replace("/\015\012(\t| )+/", ' ', $text);

		// split headers
		$lines = explode("\015\012", trim($text));

		// separate name from value
		foreach($lines as $line) {

			// printable ascii set, less space (\x20) and colon (\x3a) (rfc822)
			if(!preg_match('/^([\x21-\x39\x3b-\x7e]+):\s+(.+)$/', $line, $matches))
				continue;
			$name = $matches[1];
			$value = $matches[2];

			// remove white space between encoded-words
			$value = preg_replace('/(=\?[^?]+\?(q|b)\?[^?]*\?=)(\s)+=\?/i', '\1=?', trim($value));

			// process every encoded-word
			while(preg_match('/(=\?([^?]+)\?(q|b)\?([^?]*)\?=)/i', $value, $matches)) {

				$encoded  = $matches[1];
				$charset  = $matches[2];
				$encoding = $matches[3];
				$text	 = $matches[4];

				switch (strtolower($encoding)) {
					case 'b':
						$text = base64_decode($text);
						break;

					case 'q':
						$text = str_replace('_', ' ', $text);
						preg_match_all('/=([a-f0-9]{2})/i', $text, $matches);
						foreach($matches[1] as $match)
							$text = str_replace('='.$match, chr(hexdec($match)), $text);
						break;
				}

				$value = str_replace($encoded, $text, $value);
			}

			//store the header and its value
			$message_headers[] = array('name' => $name, 'value' => $value);
		}

		return $message_headers;

	}

	/**
	 * process one entity
	 *
	 * This function may process either the global message, or any embedded entity.
	 *
	 * According to RFC 2046, the processing depends on content type.
	 *
	 * The five discrete top-level media types are:
	 *
	 * [*] text -- textual information.  The subtype "plain" in
	 * particular indicates plain text containing no
	 * formatting commands or directives of any sort. Plain
	 * text is intended to be displayed "as-is".
	 * YACS attemps to create a page out of provided text.
	 *
	 * [*] image -- image data.  "Image" requires a display device
	 * (such as a graphical display, a graphics printer, or a
	 * FAX machine) to view the information.
	 * YACS stores the image entity in the file system, and
	 * creates an article to reference it.
	 *
	 * [*] audio -- audio data.  "Audio" requires an audio output
	 * device (such as a speaker or a telephone) to "display"
	 * the contents.
	 * YACS stores the audio entity in the file system, and
	 * creates an article to reference it.
	 *
	 * [*] video -- video data.  "Video" requires the capability
	 * to display moving images, typically including
	 * specialized hardware and software.
	 * YACS stores the video entity in the file system, and
	 * creates an article to reference it.
	 *
	 * [*] application -- some other kind of data, typically
	 * either uninterpreted binary data or information to be
	 * processed by an application.
	 * YACS stores the entity in the file system, and
	 * creates an article to reference it.
	 *
	 * [*] multipart -- data consisting of multiple entities of
	 * independent data types.
	 * YACS separate entities, attempts to locate an article,
	 * and attach all other entities to it.
	 *
	 * [*] message -- an encapsulated message.	A body of media
	 * type "message" is itself all or a portion of some kind
	 * of message object.
	 * YACS processes only sub types 'message/rfc822' and 'message/delivery-status', as plain text.
	 *
	 * @link http://www.faqs.org/rfcs/rfc2046.html MIME Media Types
	 *
	 * @param array message headers
	 * @param string the entity to be processed
	 * @param string the main anchor to attach found items
	 *
	 * @see files/files.php
	 * @see images/images.php
	 */
	function process_entity($message_headers, $entity, $anchor=NULL) {
		global $context;

		// sanity check
		if(!trim($entity))
			return;

		// split headers and body
		if(!preg_match("/^(.*?)\r?\n\r?\n(.*)/s", $entity, $matches)) {
			Logger::remember('agents/messages.php', 'Can not split header and body', $entity);
			return;
		}

		// parse and decode all headers
		$entity_headers = Messages::parse_headers($matches[1]);
		if(!$entity_headers)
			$entity_headers[] = array('name' => 'Content-Type', 'value' => 'text/plain; charset=us-ascii');

		// use these as message headers
		if(!$message_headers)
			$message_headers = $entity_headers;

		// determine content encoding
		$content_transfer_encoding = '7bit';
		foreach($entity_headers as $header)
			if(preg_match('/Content-Transfer-Encoding/i', $header['name'])) {
				$content_transfer_encoding = $header['value'];
				break;
			}

		// decode the entity
		$content = Messages::decode($matches[2], $content_transfer_encoding);

		// determine content type
		$content_type = 'text/plain';
		foreach($entity_headers as $header)
			if(preg_match('/Content-Type/i', $header['name'])) {
				$content_type = $header['value'];
				break;
			}

		// text type -- create a page or a comment out of it
		if(preg_match('/^text\//i', $content_type)) {
			Messages::submit_page($message_headers, $entity_headers, $content, $anchor);

		// image type -- save as an image file and create a page to reference it
		} elseif(preg_match('/^image\//i', $content_type)) {

			// create a hosting page if this is the sole entity
			if(!$anchor)
				$anchor = Messages::submit_container($message_headers, $content_type);

			// save the referenced image
			if($anchor)
				Messages::submit_image($message_headers, $entity_headers, $content, $anchor);

		// audio, video or application types -- create a page and attache the file to it
		} elseif(preg_match('/^(audio|video|application)\//i', $content_type)) {

			// create a hosting page if this is the sole entity
			if(!$anchor)
				$anchor = Messages::submit_container($message_headers, $content_type);

			// save the attached entity
			if($anchor)
				Messages::submit_file($message_headers, $entity_headers, $content, $anchor);

		// multipart -- split entities and process each of them
		} elseif(preg_match('/^multipart\//i', $content_type)) {

			// look for the boundary
			if(!preg_match('/boundary="*([a-zA-Z0-9\'\(\)\+_,-\.\/:=\?]+)"*\s*/i', $content_type, $matches)) {
				Logger::remember('agents/messages.php', 'No multipart boundary in '.$content_type, $content_type);
				return;
			}

			// split entities
			$entities = explode('--'.$matches[1], $content);
			if(@count($entities) < 3) {
				Logger::remember('agents/messages.php', 'Empty multipart message', $content);
				return;
			}

			// if multipart/alternative, select only one entity
			if(preg_match('/^multipart\/alternative/i', $content_type)) {

				// ignore preamble and epilogue parts; start from the end
				for($index = count($entities)-2; $index > 0; $index--) {

					// split headers and body for this part
					if(!preg_match("/^(.*?)\r?\n\r?\n(.*)/s", $entities[$index], $matches)) {
						Logger::remember('agents/messages.php', 'Can not split header and body', $entities[$index]);
						continue;
					}

					// parse and decode part headers
					$entity_headers = Messages::parse_headers($matches[1]);

					// determine content type
					$content_type = 'text/plain';
					foreach($entity_headers as $header)
						if(preg_match('/Content-Type/i', $header['name'])) {
							$content_type = $header['value'];
							break;
						}

					// if we found a textual entity, use it
					if(preg_match('/^text\/i/', $content_type)) {

						// determine content encoding
						$content_transfer_encoding = '7bit';
						foreach($entity_headers as $header)
							if(preg_match('/Content-Transfer-Encoding/i', $header['name'])) {
								$content_transfer_encoding = $header['value'];
								break;
							}

						// decode the part
						$content = Messages::decode($matches[2], $content_transfer_encoding);

						// attempt to create a page
						$entity_anchor = Messages::submit_page($message_headers, $entity_headers, $content, $anchor);

						// stop on first created anchor
						if($entity_anchor)
							break;
					}
				}

				// no textual entity, look for a binary entity
				if(!$entity_anchor) {

					// use the last representation in the list
					$index = count($entities)-2;

					// split headers and body for this part
					if(!preg_match("/^(.*?)\r?\n\r?\n(.*)/s", $entities[$index], $matches)) {
						Logger::remember('agents/messages.php', 'Can not split header and body', $entities[$index]);
						continue;
					}

					// parse and decode part headers
					$entity_headers = Messages::parse_headers($matches[1]);

					// determine content encoding
					$content_transfer_encoding = '7bit';
					foreach($entity_headers as $header)
						if(preg_match('/Content-Transfer-Encoding/i', $header['name'])) {
							$content_transfer_encoding = $header['value'];
							break;
						}

					// decode the part
					$content = Messages::decode($matches[2], $content_transfer_encoding);

					// determine content type
					$content_type = 'text/plain';
					foreach($entity_headers as $header)
						if(preg_match('/Content-Type/i', $header['name'])) {
							$content_type = $header['value'];
							break;
						}

					// a new page or a comment
					if(preg_match('/^text\//i', $content_type))
						$entity_anchor = Messages::submit_page($message_headers, $entity_headers, $content, $anchor);

					// an image
					elseif(preg_match('/^image\//i', $content_type)) {

						// ensure we have a valid anchor for this entity
						if(!$anchor)
							$anchor = Messages::submit_container($message_headers, $content_type);

						// attach the image
						if($anchor)
							$entity_anchor = Messages::submit_image($message_headers, $entity_headers, $content, $anchor);

					// anything else (maybe we should distinguish multipart???)
					} else {

						// ensure we have a valid anchor for this entity
						if(!$anchor)
							$anchor = Messages::submit_container($message_headers, $content_type);

						// attach the entity as a file
						if($anchor)
							$entity_anchor = Messages::submit_file($message_headers, $entity_headers, $content, $anchor);
					}
				}

			// we have a mix of entities to save
			} else {

				// if we don't have an anchor yet, look for a textual entity
				if(!$anchor) {

					// skip preamble and epilogue
					for($index = 1; $index < count($entities)-1; $index++) {

						// split headers and body for this part
						if(!preg_match("/^(.*?)\r?\n\r?\n(.*)/s", $entities[$index], $matches)) {
							Logger::remember('agents/messages.php', 'Can not split header and body', $entities[$index]);
							continue;
						}

						// parse and decode part headers
						$entity_headers = Messages::parse_headers($matches[1]);

						// determine content type
						$content_type = 'text/plain';
						foreach($entity_headers as $header)
							if(preg_match('/Content-Type/i', $header['name'])) {
								$content_type = $header['value'];
								break;
							}

						// if we found a textual entity, use it
						if(preg_match('/^text\//i', $content_type)) {

							// determine content encoding
							$content_transfer_encoding = '7bit';
							foreach($entity_headers as $header)
								if(preg_match('/Content-Transfer-Encoding/i', $header['name'])) {
									$content_transfer_encoding = $header['value'];
									break;
								}

							// decode the part
							$content = Messages::decode($matches[2], $content_transfer_encoding);

							// attempt to create a page
							$anchor = Messages::submit_page($message_headers, $entity_headers, $content);

							// stop on first created anchor
							if($anchor) {

								// delete this part from the list of entities
								array_splice($entities, $index, 1);

								// use this anchor for other entities
								break;
							}
						}
					}
				}

				// if we still don't have an anchor, build one from scratch
				if(!$anchor)
					$anchor = Messages::submit_container($message_headers, 'application/octet-stream');

				// give up if no anchor is available
				if(!$anchor)
					return;

				// attach every part to the anchor recursively; skip preamble and epilogue
				for($index = count($entities)-2; $index > 0; $index--)
					Messages::process_entity($message_headers, $entities[$index], $anchor);

			}

		// message/delivery-status -- create a page or a comment out of it
		} elseif(preg_match('/^message\/delivery-status/i', $content_type)) {
			Messages::submit_page($message_headers, $entity_headers, nl2br($content), $anchor);

		// message/rfc822 -- process it as the main entity
		} elseif(preg_match('/^message\/rfc822/i', $content_type)) {

			// suppress the anchor, it is likely some text/plain to introduce the main message
			if(preg_match('/article:(.+)$/', $anchor, $matches))
				@Articles::delete($matches[1]);

			// make a new page out of this entity
			Messages::process_entity($message_headers, $content, NULL);

		// unknown type
		} else
			Logger::remember('agents/messages.php', 'Do not know how to process type '.$content_type);
	}

	/**
	 * process all messages from one mailbox
	 *
	 * This is origianl code compliant to RFC 1939 for the authentication,
	 * fetching and processing of messages queued in a POP3 mailbox.
	 *
	 * @param array of mailbox attributes ($server, $account, $password)
	 * @return the number of processed messages
	 */
	function process_queue($queue) {
		global $context;


		// useless if we don't have a valid database connection
		if(!$context['connection'])
			return 0;

		// make queue parameters available
		$context['mail_queue'] = $queue;

		// use queue parameters to connect to the server
		list($server, $account, $password, $allowed, $match, $section, $options, $hooks, $prefix, $suffix) = $queue;

		// no host, assume it's us
		if(!$server)
			$server = $context['host_name'];

		// no port, assume the standard pop3 socket
		$port = 110;

		// open a network connection
		if(!$handle = Safe::fsockopen($server, $port, $errno, $errstr, 10)) {
			Logger::remember('agents/messages.php', 'Impossible to connect to '.$server);
			return 0;
		}

		// ensure enough execution time
		Safe::set_time_limit(30);

		// get server banner
		if(($reply = fgets($handle)) === FALSE) {
			Logger::remember('agents/messages.php', 'Impossible to get banner of '.$server);
			fclose($handle);
			return 0;
		}
		if($context['debug_messages'] == 'Y')
			Logger::remember('agents/messages.php', 'POP <-', $reply, 'debug');

		// expecting an OK
		if(!preg_match('/^\+OK/', $reply)) {
			Logger::remember('agents/messages.php', 'Mail service is closed at '.$server, $reply);
			fclose($handle);
			return 0;
		}

		// maybe the server accepts APOP
		$stamp = '';
		if(preg_match('/<.+@.+>/U', $reply, $matches))
			$stamp = $matches[0];

		// we will go with APOP, only if explicitly allowed
		$authenticated = FALSE;
		if($stamp && preg_match('/\bwith_apop\b/i', $options)) {

			// the digest
			if($context['debug_messages'] == 'Y')
				Logger::remember('agents/messages.php', 'POP stamp', $stamp.$password, 'debug');
			$hash = md5($stamp.$password);

			// send user name and hash
			$request = 'APOP '.$account.' '.$hash."\015\012";
			fputs($handle, $request);
			if($context['debug_messages'] == 'Y')
				Logger::remember('agents/messages.php', 'POP ->', $request, 'debug');

			// expecting an OK
			if(($reply = fgets($handle)) === FALSE) {
				Logger::remember('agents/messages.php', 'No reply to APOP command at '.$server);
				fclose($handle);
				return 0;
			}
			if($context['debug_messages'] == 'Y')
				Logger::remember('agents/messages.php', 'POP <-', $reply, 'debug');

			if(!preg_match('/^\+OK/', $reply)) {
				Logger::remember('agents/messages.php', 'Impossible to authenticate account '.$account.' at '.$server, $reply);
			} else
				$authenticated = TRUE;

		}

		// we will transmit the password in clear
		if(!$authenticated) {

			// send user name
			$request = 'USER '.$account."\015\012";
			fputs($handle, $request);
			if($context['debug_messages'] == 'Y')
				Logger::remember('agents/messages.php', 'POP ->', $request, 'debug');

			// expecting an OK
			if(($reply = fgets($handle)) === FALSE) {
				Logger::remember('agents/messages.php', 'No reply to USER command at '.$server);
				fclose($handle);
				return 0;
			}
			if($context['debug_messages'] == 'Y')
				Logger::remember('agents/messages.php', 'POP <-', $reply, 'debug');

			if(!preg_match('/^\+OK/', $reply)) {
				Logger::remember('agents/messages.php', 'Unknown account '.$account.' at '.$server, $reply);
				fclose($handle);
				return 0;
			}

			// send password
			$request = 'PASS '.$password."\015\012";
			fputs($handle, $request);
			if($context['debug_messages'] == 'Y')
				Logger::remember('agents/messages.php', 'POP ->', $request, 'debug');

			// expecting an OK
			if(($reply = fgets($handle)) === FALSE) {
				Logger::remember('agents/messages.php', 'No reply to PASS command at '.$server);
				fclose($handle);
				return 0;
			}
			if($context['debug_messages'] == 'Y')
				Logger::remember('agents/messages.php', 'POP <-', $reply, 'debug');

			if(!preg_match('/^\+OK/', $reply)) {
				Logger::remember('agents/messages.php', 'Invalid password for account '.$account.' at '.$server, $reply);
				fclose($handle);
				return 0;
			}
		}

		// be cool with the server
		Safe::sleep(1);

		// ask for information
		$request = 'STAT'."\015\012";
		fputs($handle, $request);
		if($context['debug_messages'] == 'Y')
			Logger::remember('agents/messages.php', 'POP ->', $request, 'debug');

		// expecting an OK
		if(($reply = fgets($handle)) === FALSE) {
			Logger::remember('agents/messages.php', 'No reply to STAT command at '.$server);
			fclose($handle);
			return 0;
		}
		if(!preg_match('/^\+OK/', $reply)) {
			Logger::remember('agents/messages.php', 'Rejected command STAT at '.$server, 'reply="'.$reply.'"');
			fclose($handle);
			return 0;
		}

		// evaluate queue size
		$tokens = explode(' ', $reply);
		if($context['debug_messages'] == 'Y')
			Logger::remember('agents/messages.php', 'POP <-', $reply, 'debug');
		$queue_size = @$tokens[1];

		// nothing to do
		if(!$queue_size) {
			fclose($handle);
			return 0;
		}

		// limit the number of messages processed on each tick
		if($queue_size > 10)
			$queue_size = 10;

		// process messages one by one
		for($index = 1; $index <= $queue_size; $index++) {

			// ask for the message
			$request = 'RETR '.$index."\015\012";
			fputs($handle, $request);
			if($context['debug_messages'] == 'Y')
				Logger::remember('agents/messages.php', 'POP ->', $request, 'debug');

			// expecting an OK
			if(($reply = fgets($handle)) === FALSE) {
				Logger::remember('agents/messages.php', 'No reply to RETR command at '.$server);
				fclose($handle);
				return ($index-1);
			}
			if(!preg_match('/^\+OK/', $reply)) {
				Logger::remember('agents/messages.php', 'Rejected command RETR at '.$server, $reply);
				fclose($handle);
				return ($index-1);
			}

			// fetch one message at a time
			$message = '';
			while (!feof($handle)) {

				// ensure enough execution time
				Safe::set_time_limit(30);

				// get a chunk (up to ten 1500-byte Ethernet packets)
				$chunk = fread($handle, 16384);

				// look for message end
				if(preg_match("/(.*)\.\015\012$/s", $chunk, $matches)) {
					$message .= $matches[1];
					break;
				}

				// not yet at the end
				$message .= $chunk;
			}

			// suppress the message from the mailbox before entering into the database
			$request = 'DELE '.$index."\015\012";
			fputs($handle, $request);
			if($context['debug_messages'] == 'Y')
				Logger::remember('agents/messages.php', 'POP ->', $request, 'debug');

			// expecting an OK
			if(($reply = fgets($handle)) === FALSE)
				Logger::remember('agents/messages.php', 'No reply to DELE command at '.$server);
			elseif(!preg_match('/^\+OK/', $reply))
				Logger::remember('agents/messages.php', 'Rejected command DELE at '.$server, $reply);

			// file the message if in debug mode
			if(($context['debug_messages'] == 'Y') && Safe::make_path('temporary/agents'))
				Safe::file_put_contents('temporary/agents/'.uniqid('message_'), $message);

			// process the message
			Messages::process_entity(NULL, $message);

		}

		// close the session to actually purge the queue
		$request = 'QUIT'."\015\012";
		fputs($handle, $request);
		if($context['debug_messages'] == 'Y')
			Logger::remember('agents/messages.php', 'POP ->', $request, 'debug');

		// expecting an OK
		if(($reply = fgets($handle)) === FALSE)
			Logger::remember('agents/messages.php', 'No reply to QUIT command at '.$server);
		elseif(!preg_match('/^\+OK/', $reply))
			Logger::remember('agents/messages.php', 'Rejected command QUIT at '.$server, $reply);

		if($queue_size > 0)
			Logger::remember('agents/messages.php', $queue_size.' message(s) have been processed from '.$server);
		fclose($handle);
		return $queue_size;

	}


	/**
	 * create a page to host some other entity
	 *
	 * This function is called when a message contains only binary entities.
	 * It is aiming to create a fake page to either embed posted images or to reference attached files.
	 *
	 * @param array of message attributes
	 * @param string the type of the attached entity
	 * @return the anchor of created page, or NULL on error
	 */
	function submit_container($message_headers, $type) {
		global $context;

		// type for the fake page
		$entity_headers[] = array('name' => 'Content-Type', 'value' => 'text/plain; charset=iso8859-1');

		// text for the fake page
		$text =& i18n::c('Item sent by e-mail')."\n";

		return Messages::submit_page($message_headers, $entity_headers, $text);

	}

	/**
	 * create an attached file
	 *
	 * @param array of message attributes
	 * @param array of entity attributes (e.g., 'Content-Disposition')
	 * @param string file actual content
	 * @param string the target anchor (e.g., 'article:123')
	 */
	function submit_file($message_headers, $entity_headers, $content, $anchor) {
		global $context;

		// retrieve queue parameters
		list($server, $account, $password, $allowed, $match, $section, $options, $hooks, $prefix, $suffix) = $context['mail_queue'];

		// identify message sender
		$post_sender = NULL;
		foreach($message_headers as $header)
			if(preg_match('/From/i', $header['name'])) {
				$post_sender = $header['value'];
				break;
			}

		// use only the mail address
		if(preg_match('/^[^<>]*<([^<>]+)>/', $post_sender, $matches))
			$post_sender = $matches[1];

		// no poster
		if(!$post_sender) {
			Logger::remember('agents/messages.php', 'No poster address for submitted file');
			return;
		}

		// maybe the sender has a record in the database
		$user =& Users::get($post_sender);

		// check access is allowed
		$granted = FALSE;

		// the address is in the list of allowed addresses, including anyone
		if($allowed && preg_match('/\b('.preg_quote($post_sender).'|anyone)\b/i', $allowed)) {
			$granted = TRUE;

			// email addresses not present in the database are allowed
			if(!$user['id']) {
				list($user['nick_name'], $domain) = explode('@', $post_sender);
				$user['id'] = '0';
				$user['email'] = $post_sender;
				$user['capability'] = 'M';
			}

		// the poster has to be recorded in the database
		} elseif(!$user['id']) {
			Logger::remember('agents/messages.php', 'Unknown poster address '.$post_sender);
			return;

		// maybe subscribers are allowed to post here
		} elseif(($user['capability'] == 'S') && $allowed && preg_match('/\bany_subscriber\b/i', $allowed))
			$granted = TRUE;

		// maybe members are allowed to post here
		elseif(($user['capability'] == 'M') && $allowed && preg_match('/\bany_member\b/i', $allowed))
			$granted = TRUE;

		// else the poster has to be an associate
		elseif($user['capability'] != 'A') {
			Logger::remember('agents/messages.php', 'Poster '.$post_sender.' is not allowed to post files by email');
			return;
		}

		// locate content-disposition
		foreach($entity_headers as $header)
			if(preg_match('/Content-Disposition/i', $header['name'])) {
				$content_disposition = $header['value'];
				break;
			}

		// find file name in content-disposition
		$file_name = '';
		if($content_disposition && preg_match('/filename="*([a-zA-Z0-9\'\(\)\+_,-\.\/:=\? ]+)"*\s*/i', $content_disposition, $matches))
			$file_name = $matches[1];

		// as an alternative, look in content-type
		if(!$file_name) {

			// locate content-type
			foreach($entity_headers as $header)
				if(preg_match('/Content-Type/i', $header['name'])) {
					$content_type = $header['value'];
					break;
				}

			// find file name in content-type
			if($content_type && preg_match('/name="*([a-zA-Z0-9\'\(\)\+_,-\.\/:=\? ]+)"*\s*/i', $content_type, $matches))
				$file_name = $matches[1];

		}

		// as an alternative, look in content-description
		if(!$file_name) {

			// locate content-description
			foreach($entity_headers as $header)
				if(preg_match('/Content-Description/i', $header['name'])) {
					$content_description = $header['value'];
					break;
				}

			// find file name in content-description
			$file_name = $content_description;

		}

		// sanity check
		if(!$file_name) {
			Logger::remember('agents/messages.php', 'No name to use for submitted file');
			return;
		}

		// we don't accept all extensions
		include_once $context['path_to_root'].'files/files.php';
		if(!Files::is_authorized($file_name)) {
			Logger::remember('agents/messages.php', 'Rejected file type for '.$file_path.$file_name);
			return;
		}

		// file size
		$file_size = strlen($content);

		// sanity check
		if($file_size < 7) {
			Logger::remember('agents/messages.php', 'Short file skipped');
			return;
		}

		// sanity check
		if(!$anchor) {
			Logger::remember('agents/messages.php', 'No anchor to use for submitted file', $file_name);
			return;
		}

		// get anchor data -- this is a mutable object
		$host = Anchors::get($anchor, TRUE);
		if(!is_object($host)) {
			Logger::remember('agents/messages.php', 'Unknown anchor '.$anchor);
			return;
		}

		// create target folders
		list($anchor_type, $anchor_id) = explode(':', $anchor, 2);
		$file_path = 'files/'.$anchor_type.'/'.$anchor_id;
		if(!Safe::make_path($file_path)) {
			Logger::remember('agents/messages.php', 'Impossible to create '.$file_path);
			return;
		}
		$file_path = $context['path_to_root'].$file_path.'/';

		// save the entity in the file system
		if(!$file = Safe::fopen($file_path.$file_name, 'wb')) {
			Logger::remember('agents/messages.php', 'Impossible to open '.$file_path.$file_name);
			return;
		}
		if(fwrite($file, $content) === FALSE) {
			Logger::remember('agents/messages.php', 'Impossible to write to '.$file_path.$file_name);
			return;
		}
		fclose($file);

		// update file description
		$item = array();
		$item['anchor'] = $anchor;
		$item['file_name'] = $file_name;
		$item['file_size'] = $file_size;
		if(isset($content_description) && ($content_description != $file_name))
			$item['description'] = $content_description;
		$item['edit_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', time());
		$item['edit_name'] = $user['nick_name'];
		$item['edit_id'] = $user['id'];
		$item['edit_address'] = $user['email'];

		// create a file record in the database
		include_once $context['path_to_root'].'files/files.php';
		if(!$file_id = Files::post($item)) {
			Logger::remember('agents/messages.php', Skin::error_pop());
			return;
		}
		if($context['debug_messages'] == 'Y')
			Logger::remember('agents/messages.php', 'Messages::submit_file()', $item, 'debug');

	}

	/**
	 * create a referenced image
	 *
	 * @param array of message attributes
	 * @param array of entity attributes (e.g., 'Content-Disposition')
	 * @param string image actual content
	 * @param string the target anchor (e.g., 'article:123')
	 */
	function submit_image($message_headers, $entity_headers, $content, $anchor) {
		global $context;

		// retrieve queue parameters
		list($server, $account, $password, $allowed, $match, $section, $options, $hooks, $prefix, $suffix) = $context['mail_queue'];

		// identify message sender
		$post_sender = NULL;
		foreach($message_headers as $header)
			if(preg_match('/From/i', $header['name'])) {
				$post_sender = $header['value'];
				break;
			}

		// use only the mail address
		if(preg_match('/^[^<>]*<([^<>]+)>/', $post_sender, $matches))
			$post_sender = $matches[1];

		// no poster
		if(!$post_sender) {
			Logger::remember('agents/messages.php', 'No poster address for submitted image');
			return;
		}

		// maybe the sender has been recorded
		$user =& Users::get($post_sender);

		// ensure access is allowed
		$granted = FALSE;

		// the address is in the list of allowed addresses, including anyone
		if($allowed && preg_match('/\b('.preg_quote($post_sender).'|anyone)\b/i', $allowed)) {
			$granted = TRUE;

			// email addresses not present in the database are allowed
			if(!$user['id']) {
				list($user['nick_name'], $domain) = explode('@', $post_sender);
				$user['id'] = '0';
				$user['email'] = $post_sender;
				$user['capability'] = 'M';
			}

		// the poster has to be recorded in the database
		} elseif(!$user['id']) {
			Logger::remember('agents/messages.php', 'Unknown poster address '.$post_sender);
			return;

		// maybe subscribers are allowed to post here
		} elseif(($user['capability'] == 'S') && $allowed && preg_match('/\bany_subscriber\b/i', $allowed))
			$granted = TRUE;

		// maybe members are allowed to post here
		elseif(($user['capability'] == 'M') && $allowed && preg_match('/\bany_member\b/i', $allowed))
			$granted = TRUE;

		// else the poster has to be an associate
		elseif($user['capability'] != 'A') {
			Logger::remember('agents/messages.php', 'Poster '.$post_sender.' is not allowed to post images by email');
			return;
		}

		// locate content-disposition
		foreach($entity_headers as $header)
			if(preg_match('/Content-Disposition/i', $header['name'])) {
				$content_disposition = $header['value'];
				break;
			}

		// find file name in content-disposition
		$file_name = '';
		if($content_disposition && preg_match('/filename="*([a-zA-Z0-9\'\(\)\+_,-\.\/:=\? ]+)"*\s*/i', $content_disposition, $matches))
			$file_name = $matches[1];

		// as an alternative, look in content-type
		if(!$file_name) {

			// locate content-type
			foreach($entity_headers as $header)
				if(preg_match('/Content-Type/i', $header['name'])) {
					$content_type = $header['value'];
					break;
				}

			// find file name in content-type
			if($content_type && preg_match('/name="*([a-zA-Z0-9\'\(\)\+_,-\.\/:=\? ]+)"*\s*/i', $content_type, $matches))
				$file_name = $matches[1];

		}

		// as an alternative, look in content-description
		if(!$file_name) {

			// locate content-description
			foreach($entity_headers as $header)
				if(preg_match('/Content-Description/i', $header['name'])) {
					$content_description = $header['value'];
					break;
				}

			// find file name in content-description
			$file_name = $content_description;

		}

		// sanity check
		if(!$file_name) {
			Logger::remember('agents/messages.php', 'No file name to use for submitted image');
			return;
		}

		// file size
		$file_size = strlen($content);

		// sanity check
		if($file_size < 7) {
			Logger::remember('agents/messages.php', 'Short image skipped', $file_name);
			return;
		}

		// sanity check
		if(!$anchor) {
			Logger::remember('agents/messages.php', 'No anchor to use for submitted image', $file_name);
			return;
		}

		// get anchor data -- this is a mutable object
		$host = Anchors::get($anchor, TRUE);
		if(!is_object($host)) {
			Logger::remember('agents/messages.php', 'Unknown anchor '.$anchor, $file_name);
			return;
		}

		// create target folders
		list($anchor_type, $anchor_id) = explode(':', $anchor, 2);
		$file_path = 'images/'.$context['virtual_path'].$anchor_type.'/'.$anchor_id;
		if(!Safe::make_path($file_path)) {
			Logger::remember('agents/messages.php', 'Impossible to create '.$file_path);
			return;
		}
		if(!Safe::make_path($file_path.'/thumbs')) {
			Logger::remember('agents/messages.php', 'Impossible to create '.$file_path.'/thumbs');
			return;
		}
		$file_path = $context['path_to_root'].$file_path.'/';

		// save the entity in the file system
		if(!$file = Safe::fopen($file_path.$file_name, 'wb')) {
			Logger::remember('agents/messages.php', 'Impossible to open '.$file_path.$file_name);
			return;
		}
		if(fwrite($file, $content) === FALSE) {
			Logger::remember('agents/messages.php', 'Impossible to write to '.$file_path.$file_name);
			return;
		}
		fclose($file);

		// get image information
		if(!$image_information = Safe::GetImageSize($file_path.$file_name)) {
			Safe::unlink($file_path.$file_name);
			Logger::remember('agents/messages.php', 'No image information in '.$file_path.$file_name);
			return;
		}

		// we accept only gif, jpeg and png
		if(($image_information[2] != 1) && ($image_information[2] != 2) && ($image_information[2] != 3)) {
			Safe::unlink($file_path.$file_name);
			Logger::remember('agents/messages.php', 'Rejected image type for '.$file_path.$file_name);
			return;
		}

		// build a thumbnail
		$thumbnail_name = 'thumbs/'.$file_name;

		// do not stop on error
		include_once $context['path_to_root'].'images/image.php';
		Image::shrink($file_path.$file_name, $file_path.$thumbnail_name, TRUE);

		// resize the image where applicable
		if(Image::adjust($file_path.$file_name, FALSE))
			$file_size = Safe::filesize($file_path.$file_name);

		// all details
		$details = array();

		// image size
		if($image_information = Safe::GetImageSize($file_path.$file_name))
			$details[] = i18n::c('Size').': '.$image_information[0].' x '.$image_information[1];

		// update image description
		$item = array();
		$item['anchor'] = $anchor;
		$item['image_name'] = $file_name;
		$item['thumbnail_name'] = $thumbnail_name;
		$item['image_size'] = $file_size;
		$item['description'] = '';
		if(isset($content_description) && ($content_description != $file_name))
			$item['description'] .= $content_description;
		if(@count($details))
			$item['description'] .= "\n\n".'<p class="details">'.implode(BR."\n", $details)."</p>\n";
		$item['edit_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', time());
		$item['edit_name'] = $user['nick_name'];
		$item['edit_id'] = $user['id'];
		$item['edit_address'] = $user['email'];

		// create an image record in the database
		include_once $context['path_to_root'].'images/images.php';
		if(!$id = Images::post($item)) {
			Logger::remember('agents/messages.php', 'Impossible to save image '.$item['image_name']);
			return;
		}
		if($context['debug_messages'] == 'Y')
			Logger::remember('agents/messages.php', 'Messages::submit_image()', $item, 'debug');

		// insert the image in the anchor page
		$host->touch('image:create', $id, TRUE);

	}

	/**
	 * create a page out of a textual entity
	 *
	 * If no anchor is provided, this function will create an article.
	 * Else it will create a comment attached to the provided anchor.
	 *
	 * @param array of message attributes
	 * @param array of entity attributes
	 * @param string the textual entity to process
	 * @param string an optional anchor (e.g., 'article:123')
	 * @return the anchor of created page, if any
	 */
	function submit_page($message_headers, $entity_headers, $text, $anchor=NULL) {
		global $context;

		// retrieve queue parameters
		list($server, $account, $password, $allowed, $match, $section, $options, $hooks, $prefix, $suffix) = $context['mail_queue'];

		// the array to collect item attributes
		$entry_fields = array();

		// identify message subject
		foreach($message_headers as $header)
			if(preg_match('/Subject/i', $header['name'])) {
				$post_subject = $header['value'];
				break;
			}

		// no subject
		if(!$post_subject) {
			Logger::remember('agents/messages.php', 'No subject');
			return;
		}

		// identify message sender
		$post_sender = NULL;
		foreach($message_headers as $header)
			if(preg_match('/From/i', $header['name'])) {
				$post_sender = $header['value'];
				break;
			}

		// use only the mail address
		if(preg_match('/^[^<>]*<([^<>]+)>/', $post_sender, $matches))
			$post_sender = $matches[1];

		// no poster
		if(!$post_sender) {
			Logger::remember('agents/messages.php', 'No poster address');
			return;
		}

		// ensure poster is allowed to move forward
		$granted = FALSE;

		// maybe the sender has been recorded
		$user =& Users::get($post_sender);

		// the address is in the list of allowed addresses, including anyone
		if($allowed && preg_match('/\b('.preg_quote($post_sender).'|anyone)\b/i', $allowed)) {
			$granted = TRUE;

			// email addresses not present in the database are allowed
			if(!$user['id']) {
				list($user['nick_name'], $domain) = explode('@', $post_sender);
				$user['id'] = '0';
				$user['email'] = $post_sender;
				$user['capability'] = 'M';
			}

		// the poster has to be recorded in the database
		} elseif(!$user['id']) {
			Logger::remember('agents/messages.php', 'Unknown poster address '.$post_sender);
			return;

		// maybe subscribers are allowed to post here
		} elseif(($user['capability'] == 'S') && $allowed && preg_match('/\bany_subscriber\b/i', $allowed))
			$granted = TRUE;

		// maybe members are allowed to post here
		elseif(($user['capability'] == 'M') && $allowed && preg_match('/\bany_member\b/i', $allowed))
			$granted = TRUE;

		// else the poster has to be an associate
		elseif($user['capability'] != 'A') {
			Logger::remember('agents/messages.php', 'Poster '.$post_sender.' is not allowed to post email messages');
			return;
		}

		// security match
		if($match) {

			// rebuild a textual message
			$to_be_matched = '';
			if(@count($message_headers))
				foreach($message_headers as $header)
					$to_be_matched .= $header['name'].': '.$header['value']."\n";
			if(@count($entity_headers))
				foreach($entity_headers as $header)
					$to_be_matched .= $header['name'].': '.$header['value']."\n";
			$to_be_matched .= "\n".$text;

			if(!preg_match('/'.preg_quote($match, '/').'/i', $to_be_matched)) {
				Logger::remember('agents/messages.php', 'Message does not match /'.preg_quote($match, '/').'/');
				return;
			}
		}

		// if not associate
		if(isset($user['capability']) && ($user['capability'] != 'A')) {

			// preserve breaks
			$text = preg_replace('/\s*<(br|div|p)/is', "\n\n<\\1", $text);

			// suppress most html tags
			$text = strip_tags($text, '<b><table><td><tr><u>');

		}

		// parse article content
		include_once $context['path_to_root'].'articles/article.php';
		$article =& new Article();
		$entry_fields = $article->parse($text, $entry_fields);

		// trim the header
		if($prefix) {
			$tokens = explode($prefix, $entry_fields['description']);
			if(isset($tokens[1]))
				$entry_fields['description'] = $tokens[1];
			else
				$entry_fields['description'] = $tokens[0];
		}

		// trim the signature
		if($suffix)
			list($entry_fields['description'], $dropped) = explode($suffix, $entry_fields['description']);

		// strip extra text
		$entry_fields['description'] = trim(preg_replace('/\(See attached file: [^\)]+?\)/', '', $entry_fields['description']));

		// anchor this item to something
		if(!isset($entry_fields['anchor'])) {
			if($anchor)
				$entry_fields['anchor'] = $anchor;
			else {
				if(!$section)
					$section =& Sections::get_default();
				if($section)
					$entry_fields['anchor'] = 'section:'.$section;
			}

		}

		// make a title
		if(!isset($entry_fields['title']))
			$entry_fields['title'] = $post_subject;

		// message creation stamp
		$post_date = gmdate('D, j M Y G:i:s');
		foreach($message_headers as $header)
			if(preg_match('/Date/i', $header['name'])) {
				$post_date = $header['value'];
				break;
			}
		$entry_fields['create_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', strtotime($post_date.' UTC'));
		if(!isset($entry_fields['create_name']))
			$entry_fields['create_name'] = $user['nick_name'];
		if(!isset($entry_fields['create_id']))
			$entry_fields['create_id'] = $user['id'];
		if(!isset($entry_fields['create_address']))
			$entry_fields['create_address'] = $user['email'];

		// message edition stamp
		$entry_fields['edit_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', time());
		if(!isset($entry_fields['edit_name']))
			$entry_fields['edit_name'] = $user['nick_name'];
		if(!isset($entry_fields['edit_id']))
			$entry_fields['edit_id'] = $user['id'];
		if(!isset($entry_fields['edit_address']))
			$entry_fields['edit_address'] = $user['email'];

		// post a textual entity as a comment to the main page
		if($anchor || (isset($entry_fields['anchor']) && preg_match('/^article:/', $entry_fields['anchor']))) {

			// insert introduction, if any
			if(isset($entry_fields['introduction']) && $entry_fields['introduction'])
				$entry_fields['description'] = $entry_fields['introduction']."\n\n".$entry_fields['description'];

			// insert title, if any
			if(isset($entry_fields['title']) && $entry_fields['title'])
				$entry_fields['description'] = $entry_fields['title']."\n\n".$entry_fields['description'];

			// insert comment in the database
			include_once $context['path_to_root'].'comments/comments.php';
			if(!$new_id = Comments::post($entry_fields)) {
				Logger::remember('agents/messages.php', Skin::error_pop());
				return;
			}
			$anchor = 'comment:'.$new_id;

			// debug, if required to do so
			if($context['debug_messages'] == 'Y')
				Logger::remember('agents/messages.php', 'Messages::submit_page() as a comment', $entry_fields, 'debug');

			// increment the post counter of the surfer
			Users::increment_posts($user['id']);

		// post a brand new page
		} else {

			// publish automatically, if required to do so
			$section = Anchors::get($entry_fields['anchor']);
			if((isset($context['users_with_auto_publish']) && ($context['users_with_auto_publish'] == 'Y'))
				|| preg_match('/\bauto_publish\b/i', $options)
				|| (is_object($section) && $section->has_option('auto_publish'))) {
				$entry_fields['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', time());
				if(!isset($entry_fields['publish_name']))
					$entry_fields['publish_name'] = $user['nick_name'];
				if(!isset($entry_fields['publish_id']))
					$entry_fields['publish_id'] = $user['id'];
				if(!isset($entry_fields['publish_address']))
					$entry_fields['publish_address'] = $user['email'];
			}

			// ensure we are using ids instead of nicknames
			if(is_object($section))
				$entry_fields['anchor'] = $section->get_reference();

			// save in the database
			if(!$article_id = Articles::post($entry_fields)) {
				Logger::remember('agents/messages.php', Skin::error_pop());
				return;
			}

			// debugging log
			if(isset($context['debug_messages']) && ($context['debug_messages'] == 'Y')) {
				$entry_fields['description'] = substr($entry_fields['description'], 0, 1024);
				Logger::remember('agents/messages.php', 'Messages::submit_page() as an article', $entry_fields, 'debug');
			}

			// increment the post counter of the surfer
			Users::increment_posts($user['id']);

			// get the new item
			$anchor = 'article:'.$article_id;
			$article = Anchors::get($anchor);

			// touch the related anchor
			if(is_object($section) && isset($article_id))
				$section->touch('article:create', $article_id, TRUE);

			// if the page has been published
			if(isset($entry_fields['publish_date']) && ($entry_fields['publish_date'] > NULL_DATE)) {

				// advertise public pages
				if(is_object($section) && $section->is_public()) {

					// text to be indexed
					$text = '';

					if(isset($entry_fields['introduction']))
						$text .= $entry_fields['introduction'].' ';
					if(isset($entry_fields['source']))
						$text .= $entry_fields['source'].' ';
					if(isset($entry_fields['description']))
						$text .= $entry_fields['description'];

					// pingback, if any
					if($text) {
						include_once $context['path_to_root'].'links/links.php';
						Links::ping($text, $anchor);
					}

				}

				// 'publish' hook
				if(is_callable(array('Hooks', 'include_scripts')))
					Hooks::include_scripts('publish', $article_id);

			}

			// if replies are allowed
			if(!preg_match('/\bno_reply\b/i', $options)) {

				// let the sender know about his post
				if($entry_fields['publish_date'])
					$splash = i18n::s("The page received by e-mail has been successfully published.\nPlease review it now to ensure that it reflects your mind.\n");
				else
					$splash = i18n::s("The page received by e-mail has been posted.\nDon't forget to read it online. Then click on the Publish command to make it publicly available.\n");

				$message = $splash."\n"
					.$article->get_title()."\n"
					.$article->get_teaser('basic')."\n"
					."\n".$context['url_to_home'].$context['url_to_root'].$article->get_url()."\n"
					."\n"
					.i18n::c('Thank you very much for your contribution');

				// send a mail message
				include_once $context['path_to_root'].'shared/mailer.php';
				Mailer::notify($post_sender, 'Re: '.$post_subject, $message);
			}

			// log the creation of a new article if not published
			if(!isset($entry_fields['publish_date']) || $entry_fields['publish_date'] <= NULL_DATE) {

				$label = sprintf(i18n::c('New submission: %s'), strip_tags($entry_fields['title']));
				if(is_object($section))
					$description = sprintf(i18n::c('Sent by %s in %s'), $user['nick_name'], $section->get_title());
				else
					$description = sprintf(i18n::c('Sent by %s'), $user['nick_name']);
				if(is_object($article))
					$description .= "\n\n".$article->get_teaser('basic')
						."\n\n".$context['url_to_home'].$context['url_to_root'].$article->get_url();
				Logger::notify('agents/messages.php', $label, $description);

			}

			// trigger hooks
			if(is_callable(array('Hooks', 'include_scripts'))) {

				// set hook parameters -- $context['mail_queue'] has queue attributes
				$context['mail_headers']	= $entity_headers;
				$context['mail_body']		= $text;

				// insert 'inbound-mail' in hooks to call
				$hooks = trim('inbound-mail '.$hooks);

				// trigger each hook one by one
				$hooks = preg_split('/[\s,]+/', $hooks, -1, PREG_SPLIT_NO_EMPTY);
				foreach($hooks as $hook)
					Hooks::include_scripts($hook);

			}

		}

		// job ends
		return $anchor;
	}

	/**
	 * process new messages, if any
	 *
	 * This function checks inbound mailboxes, and process new messages on their arrival.
	 *
	 * This function is aiming to run silently, therefore errors are logged in a file.
	 *
	 * @return a string to be displayed in resulting page, if any
	 *
	 */
	function tick_hook() {
		global $context;

		// useless if we don't have a valid database connection
		if(!$context['connection'])
			return;

		// we need some queue definitions
		Safe::load('parameters/agents.include.php');
		if(!isset($context['mail_queues']) || !is_array($context['mail_queues']) || !count($context['mail_queues']))
			return 'agents/messages.php: no queue has been defined'.BR;

		// remember start time
		$stamp = get_micro_time();

		// process each inbound queue
		include_once $context['path_to_root'].'shared/values.php';	// messages.tick
		$count = 0;
		foreach($context['mail_queues'] as $name => $queue) {

			// count messages retrieved
			$messages = Messages::process_queue($queue);
			$count += $messages;

			// remember tick date
			Values::set('messages.tick.'.$name, $messages);
		}

		// rebuild index pages
		if($count)
			Cache::clear();

		// compute execution time
		$time = round(get_micro_time() - $stamp, 2);

		// report on work achieved
		if($count > 1)
			return 'agents/messages.php: '.$count.' messages have been processed ('.$time.' seconds)'.BR;
		elseif($count == 1)
			return 'agents/messages.php: 1 message has been processed ('.$time.' seconds)'.BR;
		else
			return 'agents/messages.php: nothing to do ('.$time.' seconds)'.BR;
	}

}

?>