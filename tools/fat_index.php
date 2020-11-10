<?php
/**
 * compute a fat index for some web page
 *
 * Based on a given URL, or on pasted HTML text, this script extract characters
 * that have a good chance to be displayed to the surfer.
 *
 * Then it computes the fat index as the difference between submitted and computed sizes:
 * [snippet]
 * Fat index = 10 * log( raw bytes / useful bytes )
 * [/snippet]
 *
 * The number of referenced objects is also estimated as follows:
 * - references coming from src=... directives
 * - references coming from href=... directives
 *
 * This script is a practical example of using YACS as a development platform.
 *
 * Browse it to understand:
 * - how to benefit from YACS rendering engines (skin and template)
 * - how to use YACS libraries (to fetch some URL content)
 * - how to fit into YACS security model
 * - and, finally, how to create instructive phpDoc comments like this one
 *
 * @see skins/index.php
 * @see scripts/phpdoc.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// ask shared/surfer.php to not filter html input
// by default YACS allows only associates to submit some HTML
// but here we would like to accept HTML input from any logged member
$context['allow_html_input'] = 'Y';

// common definitions and initial processing
// shared/global.php is the main file to include at the beginning of each script fitting into YACS
include_once '../shared/global.php';

// load localized strings
i18n::bind('tools');

// load the skin
// if the current skin has been set to 'foo', YACS includes skins/foo/skin.php
// then the static class Skin is available to format output components (lists, links, etc.)
load_skin('tools');

// do not index this page
$context->sif('robots','noindex');

// content of the target web object
$input = '';

// maybe we have a cookie string to process
$cookie = '';
if(isset($_REQUEST['cookie']) && strlen($_REQUEST['cookie']))
	$cookie = $_REQUEST['cookie'];

// we have an URL to scan
$reference = '';
if(isset($_REQUEST['reference']) && strlen($_REQUEST['reference'])) {
	$reference = $_REQUEST['reference'];

	// fetch the object through the web
	if(!$input = http::proceed($reference, '', '', $cookie)) {

		// the standard way to localize string throughout YACS is to invoke i18n::s() -- see i18n/i18n.php
		Logger::error(sprintf(i18n::s('error while fetching %s'), $reference).' ('.http::get_error().')');
	}

// the user has submitted some content to crunch
} elseif(isset($_REQUEST['input']) && strlen($_REQUEST['input']))
	$input = $_REQUEST['input'];

// the path to this page
$context['path_bar'] = array( 'tools/' => i18n::s('Tools') );

// the title of the page
// in YACS templates, it is placed into $context['title']
$context['page_title'] = i18n::s('Fat Index');

// the splash message
$context['text'] .= '<p>'.i18n::s('This script strips tags and white space to evaluate the amount of useful bytes. Then it computes the fat index as follows:').'</p>'."\n"
	.'<dl><dd>'.i18n::s('FAT Index = 10 log( Raw Bytes / Useful Bytes )').'</dd></dl>'."\n";

// we at least ask for registration
// look at shared/surfer.php for more information on the YACS security model
if(!Surfer::is_logged())
	Logger::error(i18n::s('This script can be used only by an authenticated user of this server.'));

// user is allowed to use this tool
else {

	// compute the fat index
	if($input) {

		// section title
		// we ask the skin to render a block title
		$context['text'] .= Skin::build_block(i18n::s('Computation results'), 'title');

		// remind the target reference page
		if($reference)
			$context['text'] .= '<p>'.sprintf(i18n::s('Web Reference: %s'), $reference).'</p>'."\n";

		// submitted bytes
		$raw_bytes = strlen($input);
		$context['text'] .= '<p>'.sprintf(i18n::s('Raw Bytes: %d bytes'), $raw_bytes).'</p>'."\n";

		// remove javascript snippets
		$filtered = preg_replace('/<script.*?<\/script>/is', '', $input);

		// remove in-line styles
		$filtered = preg_replace('/<style.*?<\/style>/is', '', $filtered);

		// remove special spaces
		$filtered = preg_replace('/&(nbsp|#160);/is', ' ', $filtered);

		// remove stand-alone underlines
		$filtered = preg_replace('/\b_+\b/is', ' ', $filtered);

		// remove html tags and compact space chars
		$filtered = trim(preg_replace('/\s+/', ' ', strip_tags(str_replace('<', ' <', $filtered))));

		// transcode unicode entities to single chars to compute useful bytes
		$transcoded = preg_replace('/&\S+;/', '.', $filtered);

		// useful bytes
		$useful_bytes = strlen($transcoded);
		$context['text'] .= '<p>'.sprintf(i18n::s('Useful Bytes: %d bytes'), $useful_bytes).'</p>'."\n";

		// fat index
		if($useful_bytes) {
			$fat_index = intval(10 * log( $raw_bytes / $useful_bytes ) / log(10));
			$context['text'] .= '<p><b>'.sprintf(i18n::s('Fat Index = %d dB'), $fat_index).'</b> = 10 * log( '.$raw_bytes.' / '.$useful_bytes.' )</p>'."\n";
		} else {
			$context['text'] .= '<p>'.i18n::s('The Fat Index cannot be computed').'</p>'."\n";
		}

		// find references in input
		list($head, $body) = preg_split('/\<body/i', $input);
		preg_match_all('/\b(src|href)\s*=\s*(\'|")(.+?)\2/i', $head, $matches_in_head);
		preg_match_all('/\b(src)\s*=\s*(\'|")(.+?)\2/i', $body, $matches_in_body);
		$matches = array_merge($matches_in_head[3], $matches_in_body[3]);
		if(count($matches)) {
			$links = array_unique($matches);
			$context['text'] .= '<p>'.sprintf(i18n::s('This page has %d references to %d links:'), count($matches), count($links)).'</p>'."\n<ul>";
			foreach($links as $link)
				$context['text'] .= '<li>'.$link.'</li>';
			$context['text'] .= "</ul>\n";
		}


	}

	// get script parameters
	$context['text'] .= Skin::build_block(i18n::s('Computation parameters'), 'title');

	// the form to catch the input
	$context['text'] .= '<form method="post" action="fat_index.php" id="main_form"><div>'."\n";

	// an external reference to parse
	$context['text'] .= '<p>'.sprintf(i18n::s('Web Reference: %s'), '<input type="text" name="reference" size="60" value="'.htmlspecialchars($reference).'" />')."\n";

	// with an optional cookie
	$context['text'] .= '<br />'.sprintf(i18n::s('Optional cookie string: %s'), '<input type="text" name="cookie" size="60" value="'.htmlspecialchars($cookie).'" />').'</p>'."\n";

	// or
	$context['text'] .= '<p><i>'.i18n::s('or').'</i></p>'."\n";

	// raw input
	$context['text'] .= sprintf(i18n::s('Raw Input: %s'), '<br /><textarea name="input" rows="10" cols="80">'.htmlspecialchars($input).'</textarea>')."\n";

	// the submit button
	$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Submit'), NULL, NULL, 'action').'</p>'."\n"
		.'</div></form>'."\n";

	// the filtered text, for visual control
	if(isset($filtered) && $filtered) {

		// section title
		$context['text'] .= Skin::build_block(i18n::s('Visual control of filtered data'), 'title');

		// transcode html entities
		$context['text'] .= '<p>'.htmlentities($filtered).'</p>'."\n";

	}

}

// render the skin
// if the current skin has been set to 'foo', YACS includes skins/foo/template.php
// basically, this script uses content of $context[] (e.g., $context['title']) to render the page
render_skin();

?>
