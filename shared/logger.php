<?php
/**
 * log strings
 *
 * Use functions of this library in your code depending on the expected effect:
 * - Logger::debug() should be reserved during development, integration and debugging
 * - Logger::remember() stores an event locally, for later review
 * - Logger::notify() stores an event and also sends an e-mail message to site admins
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

class Logger {

	/**
	 * remember a string during software debug
	 *
	 * This script appends information to temporary/debug.txt
	 *
	 * @param mixed something to be printed
	 * @param string an optional label string
	 * @return void
	 */
	function debug($value='', $label=NULL) {
		global $context;

		// ensure we have a string --preserve HTML
		$value = Logger::to_string($value, FALSE);

		// stamp the line
		$line = gmdate('Y-m-d H:i:s')."\t";
		if(isset($label))
			$line .= $label.' ';
		$line .= $value;
		$line .= "\n";

		// ensure enough execution time
		Safe::set_time_limit(30);

		// apend to the debug file
		if($handle = Safe::fopen($context['path_to_root'].'temporary/debug.txt', 'a')) {
			fwrite($handle, $line);
			fclose($handle);
		} else
			echo $line;
	}

	/**
	 * add some error message
	 *
	 * @param string the additional error message
	 * @param boolean if FALSE, do not add message if there is already one
	 *
	 */
	function error($line, $multiple=TRUE) {
		global $context;

		// there is already one message
		if($context['error'] && !$multiple)
			return;

		// sanity check
		if(!$line)
			return;

		// don't repeat error messages
		if(in_array($line, $context['error']))
			return;

		// stack the error
		$context['error'][] = $line;
	}

	/**
	 * pop last error message
	 *
	 * @return string most recent error message, or NULL
	 */
	function error_pop() {
		global $context;

		// no stack
		if(!is_array($context['error']))
			return NULL;

		// empty stack
		if(!count($context['error']))
			return NULL;

		// remove last item, and return it
		return array_pop($context['error']);

	}

	/**
	 * get the latest events
	 *
	 * @param int the number of events - default is 20
	 * @param string variant - default is 'all'
	 * @return an array of ($stamp, $surfer, $script, $label, $description)
	 */
	function get_tail($count=20, $variant='all') {
		global $context;

		// open the log file for reading
		if(!is_readable($context['path_to_root'].'temporary/log.txt'))
			return NULL;
		if(!$handle = Safe::fopen($context['path_to_root'].'temporary/log.txt', 'r'))
			return NULL;

		// about 256 bytes per record
		$chunk = max(256 * $count, 10240);

		// ensure we are at the tail
		fseek($handle, -$chunk, SEEK_END);

		// read the tail
		if($tail = fread($handle, $chunk)) {

			// split lines
			$events = explode("\n", $tail);

			// remove the first item, that is likely to be truncated
			if(strlen($tail) == $chunk)
				array_shift($events);

			// reverse events order
			$events = array_reverse($events);
		}

		// release file resource we have
		fclose($handle);

		// limit the list
		if(@count($events))
			$events = array_slice($events, 0, $count);

		// parse each event
		foreach($events as $event) {
			$attributes = explode("\t", $event);
			$stamp = $attributes[0];

			if(isset($attributes[1]))
				$surfer = $attributes[1];
			else
				$surfer = '';

			if(isset($attributes[2]))
				$script = $attributes[2];
			else
				$script = '';

			if(isset($attributes[3]))
				$label = $attributes[3];
			else
				$label = '';

			if(isset($attributes[4]))
				$description = $attributes[4];
			else
				$description = '';

			// decode new lines
			$label = str_replace('\n', "\n", $label);
			$description = str_replace('\n', "\n", $description);

			if($label)
				$result[] = array($stamp, $surfer, $script, $label, $description);
		}

		// return the list of last events
		return $result;
	}

	/**
	 * notify an event
	 *
	 * This script calls [code]Logger::remember()[/code] to save the event locally, then attempts to send an e-mail
	 * message if possible.
	 *
	 * @param string the source script (e.g., 'articles/edit.php')
	 * @param string a one-line label that can be used as a mail title (e.g. 'creation of a new article')
	 * @param string a more comprehensive description, if any
	 * @return void
	 */
	function notify($script, $label, $description='') {
		global $context;

		// local storage
		Logger::remember($script, $label, $description);

		// send also a message
		if(isset($context['mail_logger_recipient']) && $context['mail_logger_recipient']) {

			// except to current surfer
			if($self_address = Surfer::get_email_address())
				$context['mail_logger_recipient'] = preg_replace('/'.preg_quote($self_address, '/').'[ \s,]*/i', '', $context['mail_logger_recipient']);

			// do we have a recipient after all?
			if(!trim($context['mail_logger_recipient']))
				return;

			// message footer
			$description .= '<div>'.sprintf(i18n::c('This message has been generated automatically by %s. If you wish to stop these automatic alerts please visit the following link and remove your address from recipients of system events.'), $context['site_name']).'</div>'
				.'<p><a href="'.$context['url_to_home'].$context['url_to_root'].'control/configure.php'.'">'.i18n::s('System parameters').'</a></p>';

			// actual mail message
			Mailer::notify(NULL, $context['mail_logger_recipient'], $label, $description);
		}
	}

	/**
	 * remember an event
	 *
	 * This script adds a line to temporary/log.txt, or to temporary/debug.txt.
	 *
	 * It the selected store is 'log', the function also submits a message to [code]syslog()[/code], to enable
	 * distributed logging.
	 *
	 * Each line of the log store is made of fields separated by tabulations:
	 * - time stamp (year-month-day hour:minutes:seconds)
	 * - surfer name, if any, or '-'
	 * - script calling this function (e.g., control/configure.php)
	 * - the label
	 * - the description, if any
	 *
	 * Each line of the debug store is made of fields separated by tabulations:
	 * - time stamp (year-month-day hour:minutes:seconds)
	 * - script calling this function (e.g., control/configure.php)
	 * - the label
	 * - the description, if any
	 *
	 * The description is truncated after 4 kbytes.
	 *
	 * @param string the source script (e.g., 'articles/edit.php')
	 * @param string a one-line label that can be used as a mail title (e.g. 'creation of a new article')
	 * @param mixed a more comprehensive description, if any
	 * @param string either 'log' or 'debug'
	 * @return void
	 */
	function remember($script, $label, $description='', $store='log') {
		global $context;

		// ensure we have a string
		$description = Logger::to_string($description, ($store != 'debug'));

		// cap the description, just in case...
		$description = substr($description, 0, 8192);

		// event saved for debugging purpose
		if($store == 'debug') {
			$store = 'temporary/debug.txt';

			// stamp the line
			$line = gmdate('Y-m-d H:i:s')."\t"
				.$script."\t"
				.$label.' '.$description;

		// event saved for later review
		} else {
			$store = 'temporary/log.txt';

			// surfer name -- surfer.php may not be loaded yet
			if(isset($_SESSION['surfer_name']))
				$name = preg_replace('/(@.+)$/', '', $_SESSION['surfer_name']);
			else
				$name = '-';

			// cap the description, just in case...
			$description = substr($description, 0, 2048);

			// strip control chars, encode new lines, single space
			$from = array ('/\r/', '/\n/', '/\s+/');
			$to = array('', '\n', ' ');

			// make a line
			$line = gmdate('Y-m-d H:i:s')."\t"
				.$name."\t"
				.$script."\t"
				.preg_replace($from, $to, strip_tags($label))."\t"
				.preg_replace($from, $to, $description);

			// save it through syslog
			Safe::syslog(LOG_INFO, $line);

		}

		// save it in a local file
		if($handle = Safe::fopen($context['path_to_root'].$store, 'a')) {
			fwrite($handle, $line."\n");
			fclose($handle);
		}

	}

	/**
	 * make a string out of something
	 *
	 * @param mixed something to be printed
	 * @param boolean TRUE if HTML tags should be suppressed, FALSE otherwise
	 * @return string
	 */
	function &to_string($value='', $strip_tags=TRUE) {
		global $context;

		// a boolean
		if($value === TRUE)
			$value = 'TRUE';
		elseif($value === FALSE)
			$value = 'FALSE';

		// ensure we have a string
		elseif(isset($value) && !is_string($value))
			$value = print_r($value, TRUE);

		// stick to simple line returns
		$value = str_replace("\r", '', $value);

		// log simple messages
		if($strip_tags) {
			$replacements = array('/<a href="([^"]*?)">(.*?)<\/a>/i' => "\\2 \\1",
				'/<(br *\/{0,1}|h1|\/h1|h2|\/h2|h3|\/h3|h4|\/h4|h5|\/h5|p|\/p|\/td|\/title)>/i' => "<\\1>\n",
				'/&nbsp;/' => ' ');
			$value = trim(strip_tags(preg_replace(array_keys($replacements), array_values($replacements), $value)));
		} else
			$value = trim($value);

		// ensure proper encoding
		if(is_callable(array('utf8', 'encode')))
			$value = utf8::from_unicode(utf8::encode($value));

		// return a clean string
		return $value;
	}

}
?>
