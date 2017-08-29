<?php
/**
 * handling scripts
 *
 * @link http://www.ics.uci.edu/~eppstein/161/960229.html ICS 161: Design and Analysis of Algorithms
 * @link http://www-es.fernuni-hagen.de/cgi-bin/info2html?(diff)Example%20Unified (diff) Example Unified
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

class Scripts {

	/**
	 * adjust the size of one string
	 *
	 * @param string the string to adjust
	 * @param int its target size - default is 45
	 * @return either a sub-string or a padded string
	 */
	public static function adjust($text, $size=45) {
		$text = str_replace(array("\r", "\n"), '', $text);
		$text_length = strlen($text);
		if($text_length > $size)
			$text = substr($text, 0, $size-3).'...';
		elseif($text_length < $size)
			$text = str_pad($text, $size);
		return $text;
	}

	/**
	 * compare two sets of lines by finding the longest common sequence
	 *
	 * @param string or array the left set
	 * @param string or array the right set
	 * @param int maximm number of lines to consider
	 * @return an array of ('=', $left, $right) or ('-', $left, '-') or ('+', '-', $right)
	 */
	public static function compare($old_stream, $new_stream, $maximum=500) {

		$start_time = get_micro_time();

		// the resulting sequence
		$sequence = array();

		// make lists of nodes
		if(is_array($old_stream))
			$old_lines = $old_stream;
		else
			$old_lines = explode("\n", $old_stream);
		if(is_array($new_stream))
			$new_lines = $new_stream;
		else
			$new_lines = explode("\n", $new_stream);

		// don't count things too many times
		$old_lines_count = count($old_lines);
		$new_lines_count = count($new_lines);

		// hash nodes
		for($i = $old_lines_count-1; $i >= 0; $i--) {
			$old_lines[$i] = rtrim($old_lines[$i]);
			$old_hash[$i] = md5(strtolower(trim($old_lines[$i])));
		}

		// ensure enough execution time
		Safe::set_time_limit(30);

		for($j = $new_lines_count-1; $j >= 0; $j--) {
			$new_lines[$j] = rtrim($new_lines[$j]);
			$new_hash[$j] = md5(strtolower(trim($new_lines[$j])));
		}

		// ensure enough execution time
		Safe::set_time_limit(30);

		// skip the head of common nodes
		$head = 0;
		while(($head < $old_lines_count) && ($head < $new_lines_count)) {
			if($old_hash[$head] == $new_hash[$head])
				$head++;
			else
				break;
		}

		// skip the tail of common nodes
		$tail = 0;
		$oindex = $old_lines_count;
		$nindex = $new_lines_count;
		while((--$oindex > $head) && (--$nindex > $head)) {
			if($old_hash[$oindex] == $new_hash[$nindex])
				$tail++;
			else
				break;
		}

		// compute lengths
		$lengths = array();
		$lengths[0][0] = 0;
		$lengths[0][1] = 0;
		$i_count = min($maximum, $old_lines_count - $head - $tail);
		$j_count = min($maximum, $new_lines_count - $head - $tail);
		for($i = $i_count; $i >= 0; $i--) {
			$lengths[$i+1][$j_count+1] = 0;
			$lengths[$i+1][$j_count] = 0;
			$lengths[$i][$j_count+1] = 0;
			for($j = $j_count; $j >= 0; $j--) {

				if(($i == $i_count) || ($j == $j_count))
					$lengths[$i][$j] = 0;

				elseif($old_hash[$i+$head] == $new_hash[$j+$head])
					$lengths[$i][$j] = 1 + $lengths[$i+1][$j+1];

				else
					$lengths[$i][$j] = max($lengths[$i+1][$j], $lengths[$i][$j+1]);

			}

		}

		// ensure enough execution time
		Safe::set_time_limit(30);

		// parse the resulting matrix
		$i = $j = 0;
		while(($i < $old_lines_count) && ($j < $new_lines_count)) {

			// same nodes
			if($old_hash[$i] == $new_hash[$j]) {
				$sequence[] = array( '=', $old_lines[$i], $new_lines[$j]);
				$i++;
				$j++;

			// one node has been deleted
			} elseif(isset($lengths[$i-$head+1][$j-$head]) && ($lengths[$i-$head+1][$j-$head] >= $lengths[$i-$head][$j-$head+1])) {
				$sequence[] = array( '-', $old_lines[$i], '-');
				$i++;

			// one node has been inserted
			} else {
				$sequence[] = array( '+', '-', $new_lines[$j]);
				$j++;
			}
		}

		// other nodes that have been removed
		while($i < $old_lines_count)
			$sequence[] = array( '-', $old_lines[$i++], '-');

		// nodes that have been appended
		while($j < $new_lines_count)
			$sequence[] = array( '+', '-', $new_lines[$j++]);

		// return the whole diff sequence
		return $sequence;
	}

	/**
	 * get the diff as a table of lines
	 *
	 * @param string a file name of the original content
	 * @param string a file for the updated content
	 * @return an ASCII string
	 */
	public static function diff($original, $updated) {
		global $context;

		// read the original file
		$stat = Safe::stat($context['path_to_root'].$original);
		if(!is_array($stat))
			return sprintf(i18n::s('Impossible to read %s.'), $original);
		$original_lines = Safe::file($context['path_to_root'].$original);

		// read the updated file
		$stat = Safe::stat($context['path_to_root'].$updated);
		if(!is_array($stat))
			return sprintf(i18n::s('Impossible to read %s.'), $updated);
		$updated_lines = Safe::file($context['path_to_root'].$updated);

		// compare the two sequences
		$sequence = Scripts::compare($original_lines, $updated_lines);

		// format the output string
		$text = '';
		foreach($sequence as $item) {
			list($tag, $left, $right) = $item;
			$text .= Scripts::adjust('	'.$tag, 5).' '.Scripts::adjust($left).' '.Scripts::adjust($right)."\n";
		}

		// return the result of the whole comparison
		$text = Scripts::adjust(i18n::s('Delta'), 5).' '.Scripts::adjust($original).' '.Scripts::adjust($updated)."\n"
			.str_replace("\t", '  ', $text)."\n";
		return $text;
	}

	/**
	 * get the gdiff as a single text string
	 *
	 * @param string a file name of the original content
	 * @param string a file for the updated content
	 * @return an ASCII string
	 */
	public static function gdiff($original, $updated) {
		global $context;

		// read the original file
		$stat = Safe::stat($context['path_to_root'].$original);
		if(!is_array($stat))
			return sprintf(i18n::s('Impossible to read %s.'), $original);
		$text = '--- '.$original."\t".str_replace('&nbsp;', ' ', Skin::build_date($stat[9]))."\n";
		$original_lines = Safe::file($context['path_to_root'].$original);

		// read the updated file
		$stat = Safe::stat($context['path_to_root'].$updated);
		if(!is_array($stat))
			return sprintf(i18n::s('Impossible to read %s.'), $updated);
		$text .= '+++ '.$updated."\t".str_replace('&nbsp;', ' ', Skin::build_date($stat[9]))."\n";
		$updated_lines = Safe::file($context['path_to_root'].$updated);

		// compare the two sequences
		$sequence = Scripts::compare($original_lines, $updated_lines);

		// format the result according to the gdiff specification
		$in_chunk = TRUE;
		$sync = 0;
		$chunk_start = $old_start = $old_current = $new_start = $new_current = 0;
		for($index = 0; $index < count($sequence); $index++) {
			list($tag, $left, $right) = $sequence[$index];
			switch($tag) {
			case '-':
				$chunk[] = '-'.$left;
				$sync = 0;
				if(!$in_chunk) {
					$old_start = $old_current-3;
					$new_start = $new_current-3;
					$in_chunk = TRUE;
				}
				$old_current++;
				break;
			case '+':
				$chunk[] = '+'.$right;
				$sync = 0;
				if(!$in_chunk) {
					$old_start = $old_current-3;
					$new_start = $new_current-3;
					$in_chunk = TRUE;
				}
				$new_current++;
				break;
			case '=':
				$chunk[] = ' '.$left;
				if($in_chunk) {
					if($sync++ >= 2) {
						$in_chunk = FALSE;
						$text .= '@@ -'.($old_start+1).','.($old_current-$old_start+1)
							.' +'.($new_start+1).','.($new_current-$new_start+1).' @@'."\n";
						foreach($chunk as $line)
							$text .= $line."\n";
						unset($chunk);
					}
				} elseif(count($chunk) > 3)
					array_shift($chunk);
				$old_current++;
				$new_current++;
				break;
			}
		}

		if(is_array($chunk) && (count($chunk) > 0)) {
			$text .= '@@ -'.($old_start+1).','.($old_current-$old_start)
				.' +'.($new_start+1).','.($new_current-$new_start).' @@'."\n";
			foreach($chunk as $line)
				$text .= $line."\n";
		}

		// return the result of the whole comparison
		return $text;
	}

	/**
	 * get the url to view a script
	 *
	 * By default, a relative URL will be provided (e.g. '[code]scripts/view.php?script=search.php[/code]'),
	 * which may be not processed correctly by search engines.
	 * If the parameter '[code]with_friendly_urls[/code]' has been set to '[code]Y[/code]' in the configuration panel,
	 * this function will return an URL parsable by search engines (e.g. '[code]scripts/view.php/search.php[/code]').
	 *
	 * @param string the target script
	 * @param string the expected action ('view', 'print', 'edit', 'delete', ...)
	 * @return an anchor to the viewing script
	 *
	 * @see control/configure.php
	 */
	public static function get_url($id, $action='view') {
		global $context;

		// sanity check
		if(!$id)
			return NULL;

		// check the target action
		if(!preg_match('/^(browse|fetch|view)$/', $action))
			$action = 'view';

		// be cool with search engines
		if($context['with_friendly_urls'] == 'Y')
			return 'scripts/'.$action.'.php/'.$id;
		else
			return 'scripts/'.$action.'.php?script='.$id;
	}

	/**
	 * hash the content of one file
	 *
	 * @param string the path of the target file
	 * @return an array of ($lines, $hash), or NULL if not part of the reference set
	 */
	public static function hash($file) {
		global $context;

		// only process php scripts
		if(!strpos(basename($file), '.php'))
			return NULL;

		// check file content
		if(!$handle = Safe::fopen($file, 'rb'))
			return NULL;

		// count lines
		$reference = FALSE;
		$count = 0;
		while($line = fgets($handle)) {
			$count++;
			if(strpos($line, '@reference'))
				$reference = TRUE;
		}
		fclose($handle);

		// only accept reference scripts
		if(!$reference)
			return NULL;

		// compute md5 signature
		if(!$hash = md5_file(Safe::realpath($file)))
			return NULL;

		// return the result
		return array($count, $hash);
	}

	/**
	 * turn an HTML string to tokens
	 *
	 * @param string original content
	 * @return array the set of tokens
	 */
	public static function hbreak(&$text) {
		global $context;


		// locate pre-formatted areas
		$areas = preg_split('#<(code|pre)>(.*?)</$1>#is', trim($text), -1, PREG_SPLIT_DELIM_CAPTURE);

		// format only adequate areas
		$output = array();
		$index = 0;
		$tag = '';
		foreach($areas as $area) {

			switch($index%3) {
			case 0: // area to be formatted

				// do not rewrite tags
				$items = preg_split('/<(\/{0,1}[a-zA-Z!\-][^>]*)>/is', $area, -1, PREG_SPLIT_DELIM_CAPTURE);
				$where = 0;
				foreach($items as $item) {

					switch($where%2) {

					case 0: // outside a tag -- break on space
						$tokens = explode(' ', $item);
						foreach($tokens as $token)
							$output[] = $token;
						break;

					case 1: // inside a tag -- left untouched
						$output[] = '<'.$item.'>';
						break;

					}
					$where++;
				}
				break;

			case 1: // area boundary
				$tag = $area;
				break;

			case 2: // pre-formatted area - left unmodified
				$output[] = '<'.$tag.'>'.$area.'</'.$tag.'>';
				break;

			}
			$index++;
		}

		// job done
		return $output;
	}

	/**
	 * compare two HTML strings
	 *
	 * @param string original content
	 * @param string updated content
	 * @return an ASCII string
	 */
	public static function hdiff(&$original, &$updated) {
		global $context;

		// preserve HTML tags
		$old_tokens = Scripts::hbreak($original);
		$new_tokens = Scripts::hbreak($updated);

		// do the job
		$output = Scripts::sdiff($old_tokens, $new_tokens);
		return $output;
	}

	/**
	 * list all files below a certain path
	 *
	 * Also print '.' and '!' while scanning the path to animate the resulting screen,
	 * if the verbose parameter is set to TRUE.
	 *
	 * Node names starting with a '.' character are skipped, except if they match the last parameter.
	 *
	 * @param string the absolute path to scan
	 * @param boolean TRUE to animate the screen, FALSE to stay silent
	 * @param string prefix to strip in path returned to caller
	 * @param string to accept special names (e.g., '.htaccess')
	 * @return an array of array(path, file name)
	 *
	 * @see scripts/build.php
	 */
	public static function list_files_at($path, $verbose=TRUE, $stripped='', $special=NULL) {
		global $context, $script_count;

		// the list that is returned
		$files = array();

		// sanity check
		$path = rtrim($path, '/');

		// make a real path
		if($handle = Safe::opendir($path)) {

			// list all nodes
			while(($node = Safe::readdir($handle)) !== FALSE) {

				// special directory names
				if(($node == '.') || ($node == '..'))
					continue;

				// process special nodes
				if($node[0] == '.') {

					// skip this item
					if(!$special || (strpos($node, $special) === FALSE))
						continue;

				}

				// make a real name
				$target = $path.'/'.$node;

				// scan a sub directory
				if(is_dir($target)) {

					// extend the list recursively
					$files = array_merge($files, Scripts::list_files_at($target, $verbose, $stripped, $special));

					// animate the screen
					if($verbose)
						$context['text'] .= '!';
					if($script_count++ > 50) {
						$script_count = 0;
						if($verbose)
							$context['text'] .= BR."\n";
					}

				// scan a file
				} elseif(is_readable($target)) {

					// remove prefix, if any
					if($stripped && (strpos($path, $stripped) === 0))
						$relative = substr($path, strlen($stripped));
					elseif($stripped && (strpos($stripped, $path) === 0))
						$relative = '';
					else
						$relative = $path;

					// append the item to the list
					$files[] = array($relative, $node);

					// animate the screen
					if($verbose)
						$context['text'] .= '.';
					if($script_count++ > 50) {
						$script_count = 0;
						if($verbose)
							$context['text'] .= BR."\n";
					}
				}
			}
			Safe::closedir($handle);
		}

		return $files;
	}

	/**
	 * load PHP scripts at a certain path
	 *
	 * This script is used to load extensions of yacs.
	 *
	 * @param string the path to scan, e.g., 'codes/extensions'
	 *
	 */
	public static function load_scripts_at($path) {
		global $context;

		// relative to yacs installation directory
		$path_translated = $context['path_to_root'];
		if($path)
			$path_translated .= '/'.$path;

		// target directory does exist
		if($handle = Safe::opendir($path_translated)) {

			// look at all entries there
			while(($node = Safe::readdir($handle)) !== FALSE) {

				// skip special files
				if($node[0] == '.')
					continue;

				// make a real name
				if($path)
					$target = $path.'/'.$node;
				else
					$target = $node;
				$target_translated = $path_translated.'/'.$node;

				// process only PHP files
				if(is_file($target_translated) && preg_match('/\.php$/i', $node) && is_readable($target_translated)) {

					// load the script
					include_once $target_translated;

				}
			}
			Safe::closedir($handle);
		}

	}

	/**
	 * list running scripts below a certain path
	 *
	 * This script is used to list scripts below the YACS installation path.
	 * Special directories 'scripts/reference' and 'scripts/staging' are skipped.
	 * Also directory entries named either 'files' or 'images' are not recursively scanned,
	 * because of the potential high number of uninteresting files they can contain.
	 *
	 * Also echo '.' (one per file) and '!' (one per directory) during the scan,
	 * if the verbose parameter is set to TRUE.
	 *
	 * @param string the path to scan
	 * @param boolean TRUE to animate the screen, FALSE to stay silent
	 * @return an array of file names
	 *
	 * @see scripts/build.php
	 */
	public static function list_scripts_at($path, $verbose=TRUE) {
		global $context, $script_count;

		// we want a list of files
		$files = array();

		$path_translated = $context['path_to_root'];
		if($path)
			$path_translated .= '/'.$path;
		if($handle = Safe::opendir($path_translated)) {

			while(($node = Safe::readdir($handle)) !== FALSE) {

				if($node[0] == '.')
					continue;

				// avoid listing of special directories
				if(($node == 'reference') || ($node == 'staging'))
					continue;

				// make a real name
				if($path)
					$target = $path.'/'.$node;
				else
					$target = $node;
				$target_translated = $path_translated.'/'.$node;

				// scan a sub directory
				if(is_dir($target_translated)) {

					// skip files and images, because of so many sub directories
					if((strpos($path, 'files/') !== FALSE) || (strpos($path, 'images/') !== FALSE))
						continue;

					// already included
					if(strpos($path, 'included/') !== FALSE)
						continue;

					// extend the list recursively
					$files = array_merge($files, Scripts::list_scripts_at($target));

					// animate the screen
					if($verbose)
						$context['text'] .= '!';
					if($script_count++ > 50) {
						$script_count = 0;
						if($verbose)
							$context['text'] .= BR."\n";
					}

				// scan a file
				} elseif(preg_match('/\.php$/i', $node) && is_readable($target_translated)) {

					// append the script to the list
					if($path)
						$files[] = $path.'/'.$node;
					else
						$files[] = $node;

					// animate the screen
					if($verbose)
						$context['text'] .= '.';
					if($script_count++ > 50) {
						$script_count = 0;
						if($verbose)
							$context['text'] .= BR."\n";
						Safe::set_time_limit(30);
					}
				}
			}
			Safe::closedir($handle);
		}

		return $files;
	}

	/**
	 * merge two files
	 *
	 * @param string a file name of the original content
	 * @param string a file for the updated content
	 * @return an ASCII string
	 */
	public static function merge($original, $updated) {
		global $context;

		// read the original file
		if(!is_array($original_lines = Safe::file($context['path_to_root'].$original))) {
			echo sprintf(i18n::s('Impossible to read %s.'), $original).BR."\n";
			return NULL;
		}

		// read the updated file
		if(!is_array($updated_lines = Safe::file($context['path_to_root'].$updated))) {
			echo sprintf(i18n::s('Impossible to read %s.'), $updated).BR."\n";
			return NULL;
		}

		// compare the two sequences
		$sequence = Scripts::compare($original_lines, $updated_lines);

//		echo $original.' vs. '.$updated.BR."\n";

		// format the output string
		$text = '';
		foreach($sequence as $item) {
			list($tag, $left, $right) = $item;

			//comment out suppressed lines
			if($tag == '-')
				$text .= '//-'.$left."\n";

			else
				$text .= $right."\n";
		}

		// return the result of the whole comparison
		return $text;
	}

	/**
	 * flag all scripts in scripts/run_once
	 *
	 */
	public static function purge_run_once() {
		global $context;

		// silently purge pending run-once scripts, if any
		if($handle = Safe::opendir($context['path_to_root'].'scripts/run_once')) {

			// process every file in the directory
			while(($node = Safe::readdir($handle)) !== FALSE) {

				// skip special entries
				if($node[0] == '.')
					continue;

				// we are only interested in php scripts
				if(!preg_match('/\.php$/i', $node))
					continue;

				// full name
				$target = $context['path_to_root'].'scripts/run_once/'.$node;

				// skip directories and links
				if(!is_file($target))
					continue;

				// check we have enough permissions
				if(!is_readable($target))
					continue;

				// stamp the file to remember execution time
				Safe::touch($target);

				// flag script as being already processed
				Safe::unlink($target.'.done');
				Safe::rename($target, $target.'.done');

			}
			Safe::closedir($handle);
		}

	}

	/**
	 * get the diff as a single text string
	 *
	 * @param string original content
	 * @param string updated content
	 * @return an ASCII string
	 */
	public static function sdiff(&$original, &$updated) {
		global $context;

		// compare the two sequences
		$sequence = Scripts::compare($original, $updated, 2000);

		// format the output string
		$text = '';
		foreach($sequence as $item) {
			list($tag, $left, $right) = $item;

			//comment out suppressed lines
			if($tag == '-') {
				if(strncmp($left, '<', 1))
					$text .= ' <del>'.$left.'</del> ';

			} elseif($tag == '+') {
				if(strncmp($right, '<', 1))
					$text .= ' <ins>'.$right.'</ins> ';
				else
					$text .= $right.' ';

			} elseif($right) {
				if(($right[0] != '<') && $text && ($text[strlen($text)-1] != '>'))
					$text .= ' ';
				$text .= $right;
			}
		}

		// recombine added words
		$text = trim(str_replace(array('</del>  <del>', '</ins>  <ins>'), ' ', $text));

		// return the result of the whole comparison
		return $text;
	}

	/**
	 * walk all files below a certain path
	 *
	 * @param string the absolute path to scan
	 * @param function the function to call back with the file found
	 *
	 * @see scripts/check.php
	 */
	public static function walk_files_at($path, $call_back=NULL) {
		global $context, $script_count;

		// sanity check
		$path = rtrim($path, '/');

		// list all files at this level
		$directories = array();
		if($handle = Safe::opendir($path)) {

			// list all nodes
			while(($node = Safe::readdir($handle)) !== FALSE) {

				// special directory names
				if(($node == '.') || ($node == '..'))
					continue;

				// process special nodes
				if($node[0] == '.')
					continue;

				// make a real name
				$target = $path.'/'.$node;

				// scan a sub directory
				if(is_dir($target))
					$directories[] = $target;

				// scan a file
				elseif(is_readable($target))
					$call_back($target);

			}
			Safe::closedir($handle);
		}

		// walk sub-directories as well
		foreach($directories as $directory)
			Scripts::walk_files_at($directory, $call_back);

	}


}

?>
