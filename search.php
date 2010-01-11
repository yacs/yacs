<?php
/**
 * search among pages
 *
 * @todo search locally, and offer extension to Google (assistant-like)
 * @todo bug on limited search to some top-level section
 * @todo allow for a search limited to users
 * @todo allow for a search limited to files
 * @todo introduce boolean searches, depending on MySQL version (> 4.0.1)
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
 * At the bottom of the page the search can be extended to the page locator,
 * and to external search engines including Google and Yahoo!
 *
 * @see go.php
 *
 * A link to get search results as a rss feed is offered in an extra box.
 *
 * @see services/search.php
 *
 * Small words are removed to avoid users being stucked with unsuccessful searches (Thank you Emmanuel).
 *
 * Accept following invocations:
 * - search.php?search=&lt;keywords&gt;
 * - search.php?search=&lt;keywords&gt;&page=1
 * - search.php?search=&lt;keywords&gt;&anchor=section:12
 *
 * @author Bernard Paques
 * @tester Dobliu
 * @tester fw_crocodile
 * @tester Aleko
 * @tester Vincent Weber
 * @author Richard Gilmour
 * @tester Antoine Bour
 * @tester Emmanuel Beucher
 * @tester Manuel López Gallego
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once 'shared/global.php';
include_once 'services/call.php'; // list RSS resources

// prevent attacks
$search = '';
if(isset($_REQUEST['search']))
	$search = preg_replace('/[\'"\{\}\[\]\(\)]/', ' ', strip_tags($_REQUEST['search']));

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

// which page should be displayed
if(isset($_REQUEST['page']))
	$page = $_REQUEST['page'];
else
	$page = 1;
$page = max(1,intval($page));

// minimum size for any search token - depends of mySQL setup
$query = "SHOW VARIABLES LIKE 'ft_min_word_len'";
if(!defined('MINIMUM_TOKEN_SIZE') && ($row =& SQL::query_first($query)) && ($row['Value'] > 0))
	define('MINIMUM_TOKEN_SIZE', $row['Value']);

// by default MySQL indexes words with at least four chars
if(!defined('MINIMUM_TOKEN_SIZE'))
	define('MINIMUM_TOKEN_SIZE', 4);

// kill short and redundant tokens
$tokens = preg_split('/[\s,]+/', $search);
if(@count($tokens)) {
	$search = '';
	foreach($tokens as $token) {

		// too short
		if(strlen(preg_replace('/&.+?;/', 'x', $token)) < MINIMUM_TOKEN_SIZE)
			continue;

		// already here (repeated word)
		if(strpos($search, $token) !== FALSE)
			continue;

		// keep this token
		$search .= $token.' ';
	}
	$search = trim($search);
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
$input = '<input type="text" name="search" id="search" size="45" value="'.encode_field($search).'" maxlength="255" />';
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

// the submit button
$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

// the form to submit a new search
$context['text'] .= '</div></form>';

// the script used for form handling at the browser
$context['text'] .= JS_PREFIX
	.'	// check that main fields are not empty'."\n"
	.'	func'.'tion validateDocumentPost(container) {'."\n"
	."\n"
	.'		// search is mandatory'."\n"
	.'		if(!container.search.value) {'."\n"
	.'			alert("'.i18n::s('Please type something to search for').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
	.'			return false;'."\n"
	.'		}'."\n"
	."\n"
	.'		// successful check'."\n"
	.'		return true;'."\n"
	.'	}'."\n"
	."\n"
	.'// set the focus on first form field'."\n"
	.'$("search").focus();'."\n"
	.JS_SUFFIX."\n";

// nothing found yet
$no_result = TRUE;

// provide results in separate panels
$panels = array();

// search in articles
$box = array();
$box['title'] = '';
$box['text'] = '';
$offset = ($page - 1) * ARTICLES_PER_PAGE;
$cap = 0;
if($items = Articles::search_in_section($section_id, $search, $offset, ARTICLES_PER_PAGE + 1)) {
	$box['title'] = i18n::s('Matching articles');

	// link to next page if greater than ARTICLES_PER_PAGE
	$cap = count($items);

	// limit the number of boxes displayed
	if($cap > ARTICLES_PER_PAGE)
		@array_splice($items, ARTICLES_PER_PAGE);


}
$cap += $offset;

// we have found some articles
if($cap || ($page > 1))
	$no_result = FALSE;

// navigation commands for articles
$box['bar'] = array();
if($cap > ARTICLES_PER_PAGE)
	$box['bar'] = array('_count' => i18n::s('Results'));
elseif($cap)
	$box['bar'] = array('_count' => sprintf(i18n::ns('%d result', '%d results', count($items)), count($items)));
$home = 'search.php?search='.urlencode($search);
$prefix = $home.'&page=';
if(($navigate = Skin::navigate($home, $prefix, $cap, ARTICLES_PER_PAGE, $page)) && @count($navigate))
	$box['bar'] += $navigate;

// actually render the html
if(@count($box['bar']))
	$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');
if(@count($items))
	$box['text'] .= Skin::build_list($items, 'decorated');
elseif(is_string($items))
	$box['text'] .= $items;
if($box['text'])
	$panels[] = array('articles', i18n::s('Pages'), 'articles_panel', $box['text']);

// on first page, and if search is not constrained
if(($page == 1) && !$section_id) {

	// search in categories
	if($rows = Categories::search($search)) {
		$panels[] = array('categories', i18n::s('Categories'), 'categories_panel', Skin::build_list($rows, 'decorated'));
		$no_result = FALSE;
	}

	// search in files
	if($rows = Files::search($search)) {
		$panels[] = array('files', i18n::s('Files'), 'files_panel', Skin::build_list($rows, 'decorated'));
		$no_result = FALSE;
	}

	// search in users
	if($rows = Users::search($search)) {
		$panels[] = array('users', i18n::s('People'), 'users_panel', Skin::build_list($rows, 'decorated'));
		$no_result = FALSE;
	}

}

// search in sections
if($rows = Sections::search_in_section($section_id, $search)) {
	$panels[] = array('sections', i18n::s('Sections'), 'sections_panel', Skin::build_list($rows, 'decorated'));
	$no_result = FALSE;
}

// assemble all tabs
if(count($panels))
	$context['text'] .= Skin::build_tabs($panels);

// nothing found
if(!count($panels) && $search)
	$context['text'] .= sprintf(i18n::s('<p>No page has been found. This will happen with very short words (less than %d letters), that are not fully indexed. This can happen as well if more than half of pages contain the searched words. Try to use most restrictive words and to suppress "noise" words.</p>'), MINIMUM_TOKEN_SIZE)."\n";

// search at peering sites, but only on unconstrained request and on first page
include_once $context['path_to_root'].'servers/servers.php';
if(!$section_id && ($page == 1) && ($servers = Servers::list_for_search(0, 3, 'search'))) {

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
if($search && ($page == 1)) {

	// a tool to update the related category
	if($cap && Surfer::is_member())
		$context['page_tools'][] = Skin::build_link('categories/set_keyword.php?search='.urlencode($search), i18n::s('Remember this search'));

	// same keywords on whole site
	if($section_id)
		$context['page_tools'][] = Skin::build_link('search.php?search='.urlencode($search), i18n::s('Search in all sections'), 'basic');

	// submit one token to our page locator
	if(preg_match('/^([\S-]+)/', $search, $matches))
		$context['page_tools'][] = Skin::build_link(normalize_shortcut($matches[1]), i18n::s('Look for a named page'), 'basic');

	// encode for urls, but preserve unicode chars
	$search = urlencode($search);

	// Google
	$link = 'http://www.google.com/search?q='.$search.'&ie=utf-8';
	$context['page_tools'][] = Skin::build_link($link, i18n::s('Google'), 'external');

	// Yahoo!
	$link = 'http://search.yahoo.com/search?p='.$search.'&ei=utf-8';
	$context['page_tools'][] = Skin::build_link($link, i18n::s('Yahoo!'), 'external');

	// Ask Jeeves
	$link = 'http://web.ask.com/web?q='.$search;
	$context['page_tools'][] = Skin::build_link($link, i18n::s('Ask Jeeves'), 'external');

	// Technorati
	$link = 'http://www.technorati.com/cosmos/search.html?rank=&url='.$search;
	$context['page_tools'][] = Skin::build_link($link, i18n::s('Technorati'), 'external');

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
if(!$text =& Cache::get($cache_id)) {
	if($items = Categories::list_keywords_by_date(0, COMPACT_LIST_SIZE))
		$text =& Skin::build_box(i18n::s('Recent searches'), Skin::build_list($items, 'compact'), 'boxes');
	Cache::put($cache_id, $text, 'categories');
}
$context['components']['boxes'] .= $text;

// render the skin
render_skin();

?>