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
	function &adjust($text, $size=45) {
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
	function &compare($old_stream, $new_stream, $maximum=500) {

		$start_time = get_micro_time();

		// make lists of nodes
		if(is_array($old_stream))
			$old_lines = $old_stream;
		else
			$old_lines = explode("\n", $old_stream);
		if(is_array($new_stream))
			$new_lines = $new_stream;
		else
			$new_lines = explode("\n", $new_stream);

		// hash nodes
		for($i = count($old_lines)-1; $i >= 0; $i--) {
			$old_lines[$i] = rtrim($old_lines[$i]);
			$old_hash[$i] = md5(strtolower(trim($old_lines[$i])));
		}

		// ensure enough execution time
		Safe::set_time_limit(30);

		for($j = count($new_lines)-1; $j >= 0; $j--) {
			$new_lines[$j] = rtrim($new_lines[$j]);
			$new_hash[$j] = md5(strtolower(trim($new_lines[$j])));
		}

		// ensure enough execution time
		Safe::set_time_limit(30);

		// compute lengths
		$lengths = array();
		$lengths[0][0] = 0;
		$lengths[0][1] = 0;
		$i_count = min($maximum, count($old_lines));
		$j_count = min($maximum, count($new_lines));
		for($i = $i_count; $i >= 0; $i--) {
			$lengths[$i][0] = 0;
			$lengths[$i][1] = 0;
			for($j = $j_count; $j >= 0; $j--) {

				if(($i == $i_count) || ($j == $j_count))
					$lengths[$i][$j] = 0;

				elseif($old_hash[$i] == $new_hash[$j])
					$lengths[$i][$j] = 1 + @$lengths[$i+1][$j+1];

				else
					$lengths[$i][$j] = max(@$lengths[$i+1][$j], @$lengths[$i][$j+1]);

			}

			// ensure enough execution time
			Safe::set_time_limit(30);

		}

		// parse the resulting matrix
		$i = $j = 0;
		while(($i < count($old_lines)) && ($j < count($new_lines))) {

			// debug and control
//			echo 'i='.$i.', j='.$j.' L[i,j]='.$lengths[$i][$j]."\n";

			if($old_lines[$i] == $new_lines[$j]) {
				$sequence[] = array( '=', $old_lines[$i], $new_lines[$j]);
				$i++;
				$j++;

			} elseif($lengths[$i+1][$j] >= $lengths[$i][$j+1]) {
				$sequence[] = array( '-', $old_lines[$i], '-');
				$i++;
			} else {
				$sequence[] = array( '+', '-', $new_lines[$j]);
				$j++;
			}
		}
		while($i < count($old_lines))
			$sequence[] = array( '-', $old_lines[$i++], '-');
		while($j < count($new_lines))
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
	function diff($original, $updated) {
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
	function &gdiff($original, $updated) {
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
	function get_url($id, $action='view') {
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
	 * @return an array of ($lines, $hash, $content, $compressed), or NULL if not part of the reference set
	 */
	function hash($file) {
		global $context;

		// only process php scripts
		if(!preg_match('/(\.php|\.php\.done)$/i', basename($file)))
			return NULL;

		// read the file
		if(!$content = Safe::file_get_contents(str_replace('//', '/', $context['path_to_root'].$file)))
			return NULL;
		$content = trim($content);

		// hash only reference scripts
		if(!preg_match('/\*\s+@reference/i', $content))
			return NULL;

		// streamline new lines
		$content = str_replace("\r", '', $content);

		// count lines
		$count = substr_count($content, "\n")+1;

		// hash the regular content
		$content_hash = md5(str_replace("\n", '', $content));

		// suppress comments and also, leading spaces
		$compressed = preg_replace(array('|/\*.*?\*/|s', '|^\s+|m', '|^//.*?$|m'), '', $content);

		// hash the compressed content
		$compressed_hash = md5($compressed);

		// return the result
		return array($count, $content_hash, $content, $compressed_hash, $compressed);
	}

	/**
	 * compare two HTML strings
	 *
	 * @param string original content
	 * @param string updated content
	 * @return an ASCII string
	 */
	function &hdiff(&$original, &$updated) {
		global $context;

		// split original lines --preserve HTML tags
		$areas = preg_split('/<(\/{0,1}[a-zA-Z][^>]*)>/is', $original, -1, PREG_SPLIT_DELIM_CAPTURE);
		$text = '';
		$index = 0;
		foreach($areas as $area) {
			switch($index++) {
			case 0;
				$text .= str_replace(array(' ', "\t"), "\n", trim($area))."\n";
				break;
			case 1;
				$text .= '<'.$area.">\n";
				break;
			}
			$index = $index%2;
		}
		$original_lines = str_replace(array("\n\n\n\n", "\n\n\n", "\n\n"), "\n", $text);

		// split updated lines
		$areas = preg_split('/<(\/{0,1}[a-zA-Z][^>]*)>/is', $updated, -1, PREG_SPLIT_DELIM_CAPTURE);
		$text = '';
		$index = 0;
		foreach($areas as $area) {
			switch($index++) {
			case 0;
				$text .= str_replace(array(' ', "\t"), "\n", trim($area))."\n";
				break;
			case 1;
				$text .= '<'.$area.">\n";
				break;
			}
			$index = $index%2;
		}
		$updated_lines = str_replace(array("\n\n\n\n", "\n\n\n", "\n\n"), "\n", $text);

		// do the job
		$output =& Scripts::sdiff($original_lines, $updated_lines);
		return $output;
	}

	/**
	 * list all files below a certain path
	 *
	 * This function is called during the creation of the archive file.
	 * It is aiming to scan the reference store and to list every file in it, whether it is a reference PHP script or not.
	 *
	 * Also print '.' and '!' while scanning the path to animate the resulting screen, if the third parameter is TRUE.
	 *
	 * @param string the path to scan - 'scripts/reference', most often
	 * @param boolean TRUE to animate the screen, FALSE to stay silent
	 * @return an array of array(path, file name)
	 *
	 * @see scripts/build.php
	 */
	function list_files_at($path, $verbose=TRUE) {
		global $context, $script_count;

		// the list that is returned
		$files = array();

		// make a real path
		$path_translated = str_replace('//', '/', $context['path_to_root'].'/'.$path);
		if($handle = Safe::opendir($path_translated)) {

			// list all nodes
			while(($node = Safe::readdir($handle)) !== FALSE) {

				// skip special nodes
				if($node == '.' || $node == '..')
					continue;

				// make a real name
				$target = str_replace('//', '/', $path.'/'.$node);
				$target_translated = str_replace('//', '/', $path_translated.'/'.$node);

				// scan a sub directory
				if(is_dir($target_translated)) {

					// extend the list recursively
					$files = array_merge($files, Scripts::list_files_at($target));

					// animate the screen
					if($verbose)
						$context['text'] .= '!';
					if($script_count++ > 50) {
						$script_count = 0;
						if($verbose)
							$context['text'] .= BR."\n";
					}

				// scan a file
				} elseif(is_readable($target_translated)) {

					// append the file to the list
					$files[] = array($path, $node);

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
	 * list running scripts below a certain path
	 *
	 * This script is used to list scripts below the YACS installation path.
	 * Special directories 'scripts/reference' and 'scripts/staging' are skipped.
	 * Also directory entries named either 'files' or 'images' are not recursively scanned,
	 * because of the potential high number of uninteresting files they can contain.
	 *
	 * Also echo '.' (one per file) and '!' (one per directory) during the scan.
	 *
	 * @param string the path to scan
	 * @return an array of file names
	 *
	 * @see scripts/build.php
	 */
	function list_scripts_at($path) {

		global $context, $script_count;

		// we want a list of files
		$files = array();

		$path_translated = $context['path_to_root'];
		if($path)
			$path_translated .= '/'.$path;
		if($handle = Safe::opendir($path_translated)) {

			while(($node = Safe::readdir($handle)) !== FALSE) {

				if($node == '.' || $node == '..')
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
					if(preg_match('/(files|images)/', $path))
						continue;

					// extend the list recursively
					$files = array_merge($files, Scripts::list_scripts_at($target));

					// animate the screen
					$context['text'] .= '!';
					if($script_count++ > 50) {
						$script_count = 0;
						$context['text'] .= BR."\n";
					}

				// scan a file
				} elseif(preg_match('/\.php$/i', $node) && is_readable($target_translated)) {

					// append the script to the list
					$files[] = array($path, $node);

					// animate the screen
					$context['text'] .= '.';
					if($script_count++ > 50) {
						$script_count = 0;
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
	function merge($original, $updated) {
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
	function purge_run_once() {
		global $context;

		// silently purge pending run-once scripts, if any
		if($handle = Safe::opendir($context['path_to_root'].'scripts/run_once')) {

			// process every file in the directory
			while(($node = Safe::readdir($handle)) !== FALSE) {

				// skip special entries
				if($node == '.' || $node == '..')
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
	function &sdiff(&$original, &$updated) {
		global $context;

		// compare the two sequences
		$sequence = Scripts::compare($original, $updated, 2000);

		// format the output string
		$text = '';
		foreach($sequence as $item) {
			list($tag, $left, $right) = $item;

			//comment out suppressed lines
			if($tag == '-')
				$text .= '<del>'.$left.'</del> ';

			elseif($tag == '+')
				$text .= '<ins>'.$right.'</ins> ';

			else
				$text .= $right.' ';
		}

		// recombine added words
		$text = str_replace(array('</del> <del>', '</ins> <ins>'), ' ', $text);

		// return the result of the whole comparison
		return $text;
	}

}

?>