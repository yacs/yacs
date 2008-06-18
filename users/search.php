<?php
/**
 * search among users
 *
 * @todo search in every contact addresses (moi-meme)
 *
 * This script calls for a search pattern, then actually searches the database.
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
 * Small words are removed to avoid users being stucked with unsuccessful searches.
 *
 * Accept following invocations:
 * - search.php?search=&lt;keywords&gt;
 * - search.php?search=&lt;keywords&gt;&page=1
 * - search.php?search=&lt;keywords&gt;&anchor=section:12
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// prevent attacks
$search = '';
if(isset($_REQUEST['search']))
	$search = preg_replace('/[\'"\{\}\[\]\(\)]/', ' ', strip_tags($_REQUEST['search']));

// ensure we are really looking for something
if(preg_match('/^(chercher|search)/i', $search))
	$search = '';

// which page should be displayed
$page = 1;
if(isset($_REQUEST['page']))
	$page = $_REQUEST['page'];
$page = strip_tags($page);

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

		// already here (repeated token)
		if($search && (strpos($search, $token) !== FALSE))
			continue;

		// keep this token
		$search .= $token.' ';
	}
	$search = trim($search);
}

// load the skin
load_skin('search');

// number of items per page
if(!defined('USERS_PER_PAGE'))
	define('USERS_PER_PAGE', 50);

// the title of the page
if($search)
	$context['page_title'] = sprintf(i18n::s('Search: %s'), $search);
else
	$context['page_title'] = i18n::s('User search');

// a search form for users
if(!$search)
	$search = i18n::s('Look for some user');
$context['text'] .= '<form method="get" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form">'
	.'<p>'
	.'<input type="text" name="search" size="40" value="'.encode_field($search).'" onfocus="this.value=\'\'" maxlength="128"'.EOT
	.' '.Skin::build_submit_button('&raquo;')
	.'</p>'
	."</form>\n";

// the script used for form handling at the browser
$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
	.'	// check that main fields are not empty'."\n"
	.'	func'.'tion validateDocumentPost(container) {'."\n"
	."\n"
	.'		// search is mandatory'."\n"
	.'		if(!container.search.value) {'."\n"
	.'			alert("'.i18n::s('Please type something to search for.').'");'."\n"
	.'			Yacs.stopWorking();'."\n"
	.'			return false;'."\n"
	.'		}'."\n"
	."\n"
	.'		// successful check'."\n"
	.'		return true;'."\n"
	.'	}'."\n"
	."\n"
	.'// set the focus on first form field'."\n"
	.'$("search").focus();'."\n"
	.'// ]]></script>'."\n";

// nothing found yet
$no_result = TRUE;

// search in users
$box = array();
$box['title'] = '';
$box['text'] = '';
$offset = ($page - 1) * USERS_PER_PAGE;
$cap = 0;
if($items = Users::search($search, $offset, USERS_PER_PAGE + 1)) {

	// link to next page if greater than USERS_PER_PAGE
	$cap = count($items);

	// limit the number of boxes displayed
	if($cap > USERS_PER_PAGE)
		@array_splice($items, USERS_PER_PAGE);


}
$cap += $offset;

// we have found some articles
if($cap || ($page > 1))
	$no_result = FALSE;

// navigation commands for articles
$box['bar'] = array();
if($cap > USERS_PER_PAGE)
	$box['bar'] = array('_count' => i18n::s('Results'));
elseif($cap)
	$box['bar'] = array('_count' => sprintf(i18n::ns('1 result', '%d results', $cap), $cap));
$home = 'search.php?search='.urlencode($search);
$prefix = $home.'&page=';
if(($navigate = Skin::navigate($home, $prefix, $cap, USERS_PER_PAGE, $page)) && @count($navigate))
	$box['bar'] = array_merge($box['bar'], $navigate);

// actually render the html
if(@count($box['bar']))
	$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');
if(is_array($items))
	$box['text'] .= Skin::build_list($items, 'decorated');
elseif(is_string($items))
	$box['text'] .= $items;
if($box['text'])
	$context['text'] .= Skin::build_box($box['title'], $box['text']);

// nothing found
if($no_result && $search)
	$context['text'] .= '<p>'.sprintf(i18n::s('No item has been found. This will happen with very short words (less than %s letters), that are not fully indexed. This can happen as well if more than half of pages contain the searched words. Try to use most restrictive words and to suppress "noise" words.'), MINIMUM_TOKEN_SIZE)."</p>\n";

// general help on this page
$context['extra'] .= Skin::build_box(i18n::s('Help'), i18n::s('This search engine only display pages that have all words in it. Also, only exact matches will be listed.'), 'navigation', 'help');

// render the skin
render_skin();

?>