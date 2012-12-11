<?php
/**
 * test the performance of codes
 *
 * This script transforms codes repeatedly to evaluate overall performance
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 *
 * @see shared/codes.php
 */
include_once '../shared/global.php';

// load localized strings
i18n::bind('codes');

// load the skin
load_skin('codes');

// the title of the page
$context['page_title'] = i18n::s('Codes test');

// only associates can stress the server
if(Surfer::is_associate()) {

	// some text
	define('DUMMY', 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'."\n"
		.' Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.'."\n"
		.' Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.'."\n"
		.' Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.'."\n");

	// to maximize processing time, use only half-side codes
	define('CODES', '[php][anonymous] ;)[code][quote]8-)[sidebar=123][caution][center][decorated][tiny][superscript][inserted]'."\n"
		.'[flag][list][link][script][submenu][email=123][subtitle][snippet][csv]##[indent][folder][note][right][style]'."\n"
		.'[hint][small][big][huge][subscript][deleted][color][image][button][menu][scroller][title]'."\n");

	// a large input
	$text = CODES.DUMMY.DUMMY.DUMMY.DUMMY.DUMMY.DUMMY.DUMMY.DUMMY.DUMMY.DUMMY;

	// report to the end user
	$context['text'] .= '<p>'.sprintf(i18n::s('Input message has %d bytes'), strlen($text)).'</p>'."\n";

	// beautify the string several times
	for($index = 0; $index < 50; $index++) {
		$trash = Codes::beautify($text);

		// ensure enough execution time
		Safe::set_time_limit(30);

	}

	// ending message
	$context['text'] .= sprintf(i18n::s('%d records have been processed'), $index).BR."\n";

	// display the execution time
	$time = round(get_micro_time() - $context['start_time'], 2);
	$context['text'] .= '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

}

render_skin();

?>
