<?php
/**
 * hook to extract keywords from a binary file
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

class Hook {

	function index_keywords($file_name) {

		// parse keywords in some files
		if(preg_match('/(\.txt|\.doc|\.xls)$/i', $file_name) && (($handle = Safe::fopen($file_name, 'rb')) !== FALSE)) {

			// load noise words
			Safe::load('files/noise_words.php');

			// use chunks of 50 kbytes
			$filtered_words = array();
			while(count($noise_words) && ($buffer = fread($handle, 51200))) {

				// strip binary stuff
				$buffer = preg_replace("/[вда]/m", 'a', $buffer);
				$buffer = preg_replace("/[йкли]/m", 'e', $buffer);
				$buffer = preg_replace("/[оп]/m", 'i', $buffer);
				$buffer = preg_replace("/[фц]/m", 'o', $buffer);
				$buffer = preg_replace("/[ыь]/m", 'u', $buffer);
				$buffer = str_replace('з', 'c', $buffer);
				$buffer = preg_replace('/[^a-zA-Z_0-9]+/m', ' ', $buffer);

				// ensure enough execution time
				Safe::set_time_limit(30);

				// strip html-like things
				$buffer = strip_tags($buffer);
				$buffer = preg_replace('/&\w;/m', '', $buffer);

				// ensure enough execution time
				Safe::set_time_limit(30);

				// extract all readable words
		//					$context['debug'][] = 'buffer=<pre>'.$buffer.'</pre>';
				$words = preg_split("/[\s]+/", $buffer);
		//					$context['debug'][] = count($words).' words extracted';

				// ensure enough execution time
				Safe::set_time_limit(30);

				// filter words
				foreach($words as $word) {

					// mysql does not index words of less than 3 chars
					$length= strlen($word);
					if(($length <= 3) || ($length > 25))
						continue;

					if(preg_match('/[0-9]/', $word))
						continue;

					if(preg_match('/^[_0-9]/', $word))
						continue;

					// filter words against the list of noise words
					$word = strtolower($word);
					if(!in_array($word, $noise_words))
						$filtered_words[$word] += 1;

				}

				// ensure enough execution time
				Safe::set_time_limit(30);

			}

			// the complete file has been read
			fclose($handle);

			// ensure enough execution time
			Safe::set_time_limit(30);

			// memorize up to 1000 keywords
			if(is_array($filtered_words)) {
				ksort($filtered_words);
				reset($filtered_words);
				$keywords = '';
				if(is_array($filtered_words)) {
					foreach($filtered_words as $word => $count) {
						$keywords .= $word.' ';
						if($keywords_count++ > 1000)
							break;
					}
				}
			}

			// ensure enough execution time
			Safe::set_time_limit(30);

		}
		return $keywords;
	}
}

?>