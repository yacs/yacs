<?php
/**
 * search among pages
 *
 * This script calls for a search pattern, then actually searches the database.
 *
 * The request can be limited to only one section. In this case, sub-sections are searched as well.
 *
 * The integrated search engine is based on full-text indexing capabilities of MySQL.
 *
 * @link http://dev.mysql.com/doc/mysql/en/Fulltext_Search.html MySQL Manual | 12.6 Full-Text Search Functions
 * @link http://www.databasejournal.com/features/mysql/article.php/1578331 Using Fulltext Indexes in MySQL - Part 1
 * @link http://www.databasejournal.com/features/mysql/article.php/1587371 Using Fulltext Indexes in MySQL - Part 2, Boolean searches
 *
 * A link to get search results as a rss feed is offered in an extra box.
 *
 * @see services/search.php
 *
 * Small words are removed to avoid users being stucked with unsuccessful searches (Thank you Emmanuel).
 *
 * Accept following invocations:
 * - search.php?search=&lt;keywords&gt;
 * - search.php?search=&lt;keywords&gt;&offset=0.324
 * - search.php?search=&lt;keywords&gt;&anchor=section:12
 *
 * @author Bernard Paques
 * @tester Guillaume Garnier
 * @tester Dobliu
 * @tester fw_crocodile
 * @tester Aleko
 * @tester Vincent Weber
 * @author Richard Gilmour
 * @tester Antoine Bour
 * @tester Emmanuel Beucher
 * @tester Manuel Lopez Gallego
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once 'shared/global.php';
include_once 'services/call.php'; // list RSS resources

// prevent attacks
$search = '';
if(isset($_REQUEST['search']))
	$search = preg_replace('/[\'"]/', ' ', strip_tags($_REQUEST['search']));

// convert from unicode to utf8
$search = utf8::from_unicode($search);

// ensure we are really looking for something
if(preg_match('/^(chercher|search)/i', $search))
	$search = '';

// search is constrained to only one section
$section_id = '';
if(isset($_REQUEST['anchor']) && (strpos($_REQUEST['anchor'], 'section:') === 0))
	$section_id = str_replace('section:', '', $_REQUEST['anchor']);
$section_id = strip_tags($section_id);

// offset, to navigate in result set
$offset = 1.0;
if(isset($_REQUEST['offset']))
	$offset = (float)$_REQUEST['offset'];
if(($offset > 1.0) || ($offset < 0.0))
	$offset = 1.0;

// minimum size for any search token - depends of mySQL setup
$query = "SHOW VARIABLES LIKE 'ft_min_word_len'";
if(!defined('MINIMUM_TOKEN_SIZE') && ($row = SQL::query_first($query)) && ($row['Value'] > 0))
	define('MINIMUM_TOKEN_SIZE', $row['Value']);

// by default MySQL indexes words with at least four chars
if(!defined('MINIMUM_TOKEN_SIZE'))
	define('MINIMUM_TOKEN_SIZE', 4);

// kill short and redundant tokens; adapt to boolean search
$boolean_search = '';
$tokens = preg_split('/[\s,]+/', $search);
if(@count($tokens)) {
	foreach($tokens as $token) {

		// too short
		if(strlen(preg_replace('/&.+?;/', 'x', $token)) < MINIMUM_TOKEN_SIZE)
			continue;

		// already here (repeated word)
		if(strpos($boolean_search, $token) !== FALSE)
			continue;

		// re-enforce boolean mode
		if(($token[0] != '+') && ($token[0] != '+') && ($token[0] != '~'))
			$token = '+'.$token;

		// keep this token
		$boolean_search .= $token.' ';
	}
	$boolean_search = trim($boolean_search).'*';
}

// load localized strings
i18n::bind('root');

// load the skin
load_skin('search');

// the title of the page
if($search)
	$context['page_title'] = sprintf(i18n::s('Search: %s'), $search);
else
	$context['page_title'] = i18n::s('Search');

// the form to submit a new search
$context['text'] .= '<form method="get" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>';
$fields = array();

// a field to type keywords
$label = i18n::s('You are searching for');
$input = '<input type="text" name="search" size="45" value="'.encode_field($search).'" maxlength="255" />'
	.Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');
$hint = i18n::s('Type one or several words.');
$fields[] = array($label, $input, $hint);

// limit the search to one section
$label = i18n::s('Search in');
if($section_id)
	$current = 'section:'.$section_id;
else
	$current = 'none';
$input = '<select name="anchor">'.'<option value="">'.i18n::s('-- All sections')."</option>\n".Sections::get_options($current, 'no_subsections').'</select>';
$hint = i18n::s('Look in all or only one section.');
$fields[] = array($label, $input, $hint);

// build the form
$context['text'] .= Skin::build_form($fields);
$fields = array();

// the form to submit a new search
$context['text'] .= '</div></form>';

// the script used for form handling at the browser
Page::insert_script(
		// check that main fields are not empty
	'	func'.'tion validateDocumentPost(container) {'."\n"
			// search is mandatory'
	.'		if(!container.search.value) {'."\n"
	.'			alert("'.i18n::s('Please type something to search for.').'");'."\n"
	.'			Yacs.stopWorking();'."\n"
	.'			return false;'."\n"
	.'		}'."\n"
			// successful check
	.'		return true;'."\n"
	.'	}'."\n"
	."\n"
	// set the focus on first form field
	.'$("#search").focus();'."\n"
	);

// various panels
$panels = array();

// all results, as array($score, $summary)
$result = array();

// number of results per page
$bucket = 20;

// search in articles
if($items = Articles::search_in_section($section_id, $boolean_search, $offset, $bucket))
	$result = array_merge($result, $items);

// search in sections
if($items = Sections::search_in_section($section_id, $boolean_search, $offset, $bucket))
	$result = array_merge($result, $items);

// global search
if(!$section_id) {

	// search in categories
	if($items = Categories::search($boolean_search, $offset, $bucket))
		$result = array_merge($result, $items);

	// search in files
	if($items = Files::search($boolean_search, $offset, $bucket))
		$result = array_merge($result, $items);

	// search in users
	if($items = Users::search($boolean_search, $offset, $bucket))
		$result = array_merge($result, $items);

}

// compare scores of two items
function compare_scores($a, $b) {
	if($a[0] < $b[0])
		return 1;
	if($a[0] == $b[0])
		return 0;
	return -1;
}

// sort the full array of results
uasort($result, 'compare_scores');

// limit the number of items displayed
$more_results = FALSE;
if(count($result) > $bucket) {
	@array_splice($result, $bucket);
	$more_results = TRUE;
}

// display results
if($result) {

	// drop scores
	$items = array();
	$last_offset = $offset;
	foreach($result as $item) {
		$items[] = $item[1];
		$last_offset = $item[0];
	}

	// avoid that the first item of the next slice is the last item of current slice
	$last_offset -= 0.0000000000001;

	// stack all results
	$text = Skin::finalize_list($items, 'rows');

	// offer to fetch more results
	if($more_results) {

		// make a valid id
		$id = str_replace('.', '_', $last_offset);

		// the button to get more results
		$text .= '<div style="margin-top: 1em; text-align: center;" id="div'.$id.'"><a href="#" class="button wide" id="a'.$id.'">'.i18n::s('Get more results').'</a></div>';
			
		Page::insert_script(
			'$(function(){'
			.	'$("#div'.$id.' a").click( function() {'
			.		'Yacs.update("div'.$id.'", "'.$context['self_url'].'", {'
			.			'data: "offset='.$last_offset.'",'
			.			'complete: function() {'
			.				'setTimeout(function() {$("#div'.$id.'").animate({"marginTop":0})}, 500); '
			.			'}'
			.		'});'
			.		'return false;'
			.	'});'
			.'});'
			);
	}

	// return a slice of results to update the search page through ajax call
	if($offset < 1.0) {
		echo $text;
		return;
	}

} else
	$text = sprintf(i18n::s('<p>No page has been found. This will happen with very short words (less than %d letters), that are not fully indexed. This can happen as well if more than half of pages contain the searched words. Try to use most restrictive words and to suppress "noise" words.</p>'), MINIMUM_TOKEN_SIZE)."\n";

// in a separate panel
$panels[] = array('results', i18n::s('Results'), 'result', $text);

// add an extra panel
if(isset($context['skins_delegate_search']) && ($context['skins_delegate_search'] == 'X')) {

	$text = '';

	// typically, a form and a button to search at another place
	if(isset($context['skins_search_extension']) && $context['skins_search_extension'])
		$text .= str_replace('%s', encode_field($search), $context['skins_search_extension']);

	// look at other search engines
	$text .= '<p style="margin: 2em 0 0 0">'.i18n::s('Search all of the Internet').'</p><ul>';

	// encode for urls, but preserve unicode chars
	$search = urlencode($search);

	// Google
	$link = 'http://www.google.com/search?q='.$search.'&ie=utf-8';
	$text .= '<li>'.Skin::build_link($link, i18n::s('Google'), 'external').'</li>';

	// Bing
	$link = 'http://www.bing.com/search?q='.$search;
	$text .= '<li>'.Skin::build_link($link, i18n::s('Bing'), 'external').'</li>';

	// Yahoo!
	$link = 'http://search.yahoo.com/search?p='.$search.'&ei=utf-8';
	$text .= '<li>'.Skin::build_link($link, i18n::s('Yahoo!'), 'external').'</li>';

	// Ask Jeeves
	$link = 'http://web.ask.com/web?q='.$search;
	$text .= '<li>'.Skin::build_link($link, i18n::s('Ask Jeeves'), 'external').'</li>';

	// in a separate panel
	$panels[] = array('extension', i18n::s('Extended search'), 'extensions_panel', $text);
}

// assemble all tabs
if(count($panels))
	$context['text'] .= Skin::build_tabs($panels);

// search at peering sites, but only on unconstrained request and on first page
include_once $context['path_to_root'].'servers/servers.php';
if(!$section_id && ($servers = Servers::list_for_search(0, 3, 'search'))) {

	// everything in a separate section
	$context['text'] .= Skin::build_block(i18n::s('At partner sites'), 'title');

	// query each server
	foreach($servers as $server_url => $attributes) {
		list($server_search, $server_label) = $attributes;

		// a REST API that returns a RSS list
		$result = Call::list_resources($server_search, array('search' => $search));

		// error message
		if(!$result[0])
			$context['text'] .= $result[1];

		// some results
		else {

			$items = array();
			foreach($result[1] as $item) {

				$suffix = '';
				if($item['description'])
					$suffix .= ' - '.$item['description'];
				$suffix .= BR;

				$details = array();
				if($item['pubDate'])
					$details[] = $item['pubDate'];
				if($server_url)
					$details[] = Skin::build_link($server_url, $server_label, 'server');

				if(count($details))
					$suffix .= '<span class="details">'.join(' - ', $details).'</span>';

				$items[$item['link']] = array('', $item['title'], $suffix, 'external', '');

			}
			$context['text'] .= Skin::build_list($items, 'decorated');
		}
	}
}

//
// extra panel
//

// extend the search, but only at first page
if($search) {

	// a tool to update the related category
	if(Surfer::is_member())
		$context['page_tools'][] = Skin::build_link('categories/set_keyword.php?search='.urlencode($search), i18n::s('Remember this search'));

	// same keywords on whole site
	if($section_id)
		$context['page_tools'][] = Skin::build_link('search.php?search='.urlencode($search), i18n::s('Search in all sections'), 'basic');

	// submit one token to our page locator
	if(preg_match('/^([\S-]+)/', $search, $matches))
		$context['page_tools'][] = Skin::build_link(normalize_shortcut($matches[1]), i18n::s('Look for a named page'), 'basic');

}

// general help on this page
$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), i18n::s('This search engine only display pages that have all words in it. <p>Also, only exact matches will be listed. Therefore "category" and "categories" won\'t give the same results. Note that "red" and "reds" may also give different results.</p>'), 'boxes', 'help');

// how to stay tuned
$lines = array();
if($search)
	$lines[] = Skin::build_link('services/search.php?search='.urlencode($search), i18n::s('Matching pages'), 'xml');
$context['components']['boxes'] .= Skin::build_box(i18n::s('Monitor'), join(BR, $lines), 'boxes', 'feeds');

// side bar with the list of most recent keywords
$cache_id = 'search.php#keywords_by_date';
if(!$text = Cache::get($cache_id)) {
	if($items = Categories::list_keywords_by_date(0, COMPACT_LIST_SIZE))
		$text = Skin::build_box(i18n::s('Recent searches'), Skin::build_list($items, 'compact'), 'boxes');
	Cache::put($cache_id, $text, 'categories');
}
$context['components']['boxes'] .= $text;

// render the skin
render_skin();

?>
