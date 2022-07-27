<?php
/**
 * process uploads
 *
 *
 * The hook 'tick' triggers periodic checks of the [code]inbox/entries[/code] directory.
 * If a file, uploaded from handx weblog, is found, its content is parsed,
 * related pages are created, and it is then archived.
 *
 * handx weblog generates one file per day, containing all entries for the day.
 * YACS uses such files as input queues.
 *
 * YACS parses input queues and uses regular expressions to split weblog entries.
 * The date of the entry is taken from the file name.
 * The hour is taken from the standard separator.
 * The content may use standard yacs codes, plus specific elements &lt;title&gt;, &lt;introduction&gt; and &lt;source&gt;.
 * If no title element is provided, the first sentence is used instead.
 *
 * Note that YACS parser does not support customised separators.
 * Please be careful to not change related parameters in weblog configuration.
 *
 * Following parameters are used while processing uploaded weblog entries:
 * - user nick name
 * - section to anchor posted pages
 *
 * These parameters are set in agents/configure.php, and saved in parameters/agents.include.php.
 *
 * Items processed by YACS are saved in backup queues.
 * Backup queues have the same names than input queues, with an additional .bak extension.
 * Basically, you just have to suppress the .bak extension to make YACS process related entries again.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

class Uploads {

	/**
	 * list files into one directory
	 *
	 * @param string the directory to look at
	 * @return an array of directory entries, or NULL
	 */
	public static function list_files($path) {
		global $context;

		// we are looking for files
		$files = array();

		// look for directory entries
		$path_translated = $context['path_to_root'].$path;
		if($handle = Safe::opendir($path_translated)) {

			// handle files one by one
			while(($node = Safe::readdir($handle)) !== FALSE) {

				// skip trivial entries
				if($node[0] == '.')
					continue;

				// skip processed entries
				if(preg_match('/\.(done|bak)$/i', $node))
					continue;

				// make a real name
				$target = $path.'/'.$node;
				$target_translated = $path_translated.'/'.$node;

				// scan a file
				if(is_file($target_translated) && is_readable($target_translated))
					$files[] = $target;

			}
			Safe::closedir($handle);
		}

		return $files;

	}

	/**
	 * process one handx entry
	 *
	 * This function actually creates an article out an entry
	 *
	 * @param string entry content
	 * @param time stamp
	 */
	public static function process_handx_entry($text, $stamp=NULL) {
		global $context;

		// parse article content
		include_once $context['path_to_root'].'articles/article.php';
		$article = new Article();
		$fields = $article->parse($text);

		// if no title, use the first line
		if(!$fields['title'])
			list($fields['title'], $fields['description']) = preg_split("/\n/", $fields['description'], 2);

		// if we have a time stamp, use it
		if($stamp) {
			$fields['create_date'] = $stamp;
			$fields['publish_date'] = $stamp;
			$fields['edit_date'] = $stamp;
		}

		// load parameters for uploads
		Safe::load('parameters/agents.include.php');

		// user information
		if($context['uploads_nick_name']) {
			if($user = Users::get($context['uploads_nick_name'])) {
				if(!$fields['create_name'])
					$fields['create_name'] = $user['nick_name'];
				if(!$fields['create_id'])
					$fields['create_id'] = $user['id'];
				if(!$fields['create_address'])
					$fields['create_address'] = $user['email'];
				if(!$fields['publish_name'])
					$fields['publish_name'] = $user['nick_name'];
				if(!$fields['publish_id'])
					$fields['publish_id'] = $user['id'];
				if(!$fields['publish_address'])
					$fields['publish_address'] = $user['email'];
				if(!$fields['edit_name'])
					$fields['edit_name'] = $user['nick_name'];
				if(!$fields['edit_id'])
					$fields['edit_id'] = $user['id'];
				if(!$fields['edit_address'])
					$fields['edit_address'] = $user['email'];
			}
		}

		// the anchor
		if(!$fields['anchor'] && $context['uploads_anchor'])
			$fields['anchor'] = $context['uploads_anchor'];
		$anchor = Anchors::get($fields['anchor']);

		// post a page
		$fields['id'] = Articles::post($fields);

		// increment the post counter of the surfer
		Users::increment_posts($user['id']);

		// do whatever is necessary on page publication
		if(isset($fields['publish_date']) && ($fields['publish_date'] > NULL_DATE))
			Articles::finalize_publication($anchor, $fields);

	}

	/**
	 * process one file uploaded by handx weblog
	 *
	 * @param string the file to process
	 */
	public static function process_handx_weblog($file) {
		global $context;

		// load parameters for uploads
		Safe::load('parameters/agents.include.php');
		if(!$context['uploads_nick_name']) {
			Logger::remember('agents/upload.php: no parameters, skipping '.$file);
			return;
		}

		// read the input queue
		if(!$content = trim(Safe::file_get_contents($context['path_to_root'].$file)))
			return;

		// save in the output queue
		if($handle = Safe::fopen($context['path_to_root'].$file.'.bak', 'ab')) {
			fwrite($handle, $content);
			fclose($handle);

			// delete the input queue
			Safe::unlink($context['path_to_root'].$file);
		}

		// date is derived from file name
		$name = basename($file);
		$year	= substr($name, 0, 4);
		$month	= substr($name, 4, 2);
		$day	= substr($name, 6, 2);

		// split entries using the default separator value
		$separator = "/<table width=100%><tr><td class='time'>(.+?)<\/td><\/tr><\/table>/";
		$entries = preg_split($separator, $content, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);

		// no time information
		if(@count($entries) == 1) {

			// make a stamp
			$stamp = gmdate('Y-m-d H:i:s', mktime(0, 0, 0, $month, $day, $year));

			// process this entry
			Uploads::process_handx_entry(trim($entries[0]), $stamp);

		// pairs of time and content strings
		} elseif(@count($entries) > 1) {

			// process all pairs
			for($index=0; $index < count($entries); $index++) {

				// the time as extracted by preg_split()
				$stamp = '';
				if(preg_match('/(\d{1,2}):(\d{1,2}) (am|pm)/', $entries[$index], $matches)) {
					$index++;

					// make a stamp
					$hour = $matches[1];
					$minutes = $matches[2];
					if($matches[3] == 'pm')
						$hour += 12;
					$stamp = gmdate('Y-m-d H:i:s', mktime($hour, $minutes, 0, $month, $day, $year));

				}

				// the entry itself
				$entry = $entries[$index];

				// process this entry
				Uploads::process_handx_entry(trim($entry), $stamp);

			}
		}

	}


	/**
	 * process new uploads, if any
	 *
	 * This function checks the input queue, and process new files on their arrival.
	 *
	 * This function is aiming to run silently, therefore errors are logged in a file.
	 *
	 * @return a string to be displayed in resulting page, if any
	 *
	 */
	public static function tick_hook() {
		global $context;

		// useless if we don't have a valid database connection
		if(!$context['connection'])
			return;

		// remember start time
		$stamp = get_micro_time();

		// process handx weblog entries, if any
		$count = 0;
		if(($files = Uploads::list_files('inbox/entries')) && (@count($files) > 0)) {

			foreach($files as $file) {

				// help the webmaster
				Logger::remember('agents/upload.php: processing '.$file);

				// create articles
				Uploads::process_handx_weblog($file);

				// no more than 10 entries per tick
				$count += 1;
				if($count >= 10)
					break;
			}

			// remember tick date
			include_once $context['path_to_root'].'shared/values.php';
			Values::set('uploads.tick.entries', $count);
		}

		// rebuild index pages
		if($count)
			Cache::clear();

		// compute execution time
		$time = round(get_micro_time() - $stamp, 2);

		// report on work achieved
		if($count > 1)
			return 'agents/uploads.php: '.$count.' files have been processed ('.$time." seconds)".BR;
		elseif($count == 1)
			return 'agents/uploads.php: 1 file has been processed ('.$time." seconds)".BR;
		else
			return 'agents/uploads.php: nothing to do ('.$time." seconds)".BR;
	}

}

// load localized strings
if(is_callable(array('i18n', 'bind')))
	i18n::bind('agents');

?>
