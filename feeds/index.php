<?php
/**
 * use feeds to exchange news with other web servers
 *
 * @todo add other web subscriptions http://feeds.atwonline.com/AtwDailyNews
 *
 * Configuring feeding channels between servers is the very first mean to expand a YACS community.
 *
 * The patterns that are supported at the moment are quite straightforward, as usual:
 * - outbound feeding pattern
 * - inbound feeding pattern
 *
 * The outbound feeding pattern provides the newest articles to other polling servers.
 * You only have to configure some descriptive information (into [code]parameters/feeds.include.php[/code]) that will be embedded into
 * generated files, and that's it.
 *
 * Resources are available in several formats:
 * - atom.php supports the version 0.3 of the ATOM standard
 * - rss.php supports the version 2.0 of the RSS standard
 * - rdf.php supports the version 1.0 of the RDF/RSS standard
 * - describe.php is an OPML list of most important feeds at this site
 *
 * More specific outbound feeds are available at [script]sections/feed.php[/script],
 * [script]categories/feed.php[/script], and [script]users/feed.php[/script].
 * You can also build a feed on particular keywords, at [script]services/search.php[/script].
 *
 * Bloggers will use another feed to download full contents, at [script]articles/feed.php[/script].
 * Comments are available as RSS at [script]comments/feed.php[/script].
 *
 * YACS also builds a feed to list most recent public files at [script]files/feed.php[/script].
 *
 * Associates can benefit from another specific feed to monitor the event log, at [script]agents/feed.php[/script].
 *
 * Moreover, this page also features links to add the main RSS feed either to Yahoo! or to NewsGator, in an extra box.
 *
 * The inbound feeding pattern is used to fetch fresh information from targeted servers.
 * Once the list of feeding servers has been configured (into parameters/feeds.include.php), everything
 * happens automatically.
 * Feeders are polled periodically (see [script]feeds/configure.php[/script])
 * and items are put in the database as news links.
 * Items may be listed into any part of your server using the Feeds class.
 *
 * @link http://www.atomenabled.org/developers/syndication/atom-format-spec.php The Atom Syndication Format 0.3 (PRE-DRAFT)
 * @link http://blogs.law.harvard.edu/tech/rss RSS 2.0 Specification
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'feeds.php';

// load the skin
load_skin('feeds');

// the title of the page
$context['page_title'] = i18n::s('Information channels');

// page main content
$cache_id = 'feeds/index.php#text';
if(!$text =& Cache::get($cache_id)) {

	// tabbed panels
	$panels = array();

	// outbound feeds
	//
	$outbound = '';

	// splash -- largely inspired by ALA
	$outbound .= '<p>'.sprintf(i18n::s('If you are unfamiliar with news feeds, they are easy to use. First, download a newsreader application like %s or %s. Then, copy and paste the URL of any news feed below into the application subscribe dialogue. See Google for more about %s.'), Skin::build_link(i18n::s('http://www.rssbandit.org/'), i18n::s('Rss Bandit'), 'external'), Skin::build_link(i18n::s('http://www.feedreader.com/'), i18n::s('FeedReader'), 'external'), Skin::build_link(i18n::s('http://www.google.com/search?q=RSS+newsreaders'), i18n::s('RSS newsreaders'), 'external'))."</p>\n";

	// a list of feeds
	$outbound .= '<p>'.i18n::s('Regular individuals will feed their preferred news reader with one of the links below:')."</p>\n";

	$links = array(
			Feeds::get_url('rss')	=> array('', i18n::s('RSS format'), '', 'xml'),
			Feeds::get_url('atom')	=> array('', i18n::s('ATOM format'), '', 'xml'),
			'feeds/rdf.php' 	=> array('', i18n::s('RDF/RSS format'), '', 'xml'),
			Feeds::get_url('opml')	=> array('', i18n::s('Index of main channels in OPML format'), '', 'xml')
			);

	$outbound .= Skin::build_list($links, 'bullets');

	// feeds for power users
	$outbound .= '<p>'.i18n::s('Advanced bloggers can also use heavy feeds:').'</p>';

	$links = array(
			Feeds::get_url('articles')	=> array('', i18n::s('recent articles, integral version'), '', 'xml'),
			Feeds::get_url('files')	=> array('', i18n::s('recent files'), '', 'xml'),
			Feeds::get_url('comments')	=> array('', i18n::s('recent comments'), '', 'xml'),
			);

	$outbound .= Skin::build_list($links, 'bullets');

	// feeding files
	$outbound .= '<p>'.sprintf(i18n::s('YACS enables automatic downloads and %s through a feed dedicated to %s.'), Skin::build_link(i18n::s('http://en.wikipedia.org/wiki/Podcasting'), i18n::s('podcasting'), 'external'), Skin::build_link(Feeds::get_url('files'), i18n::s('recent files'), 'xml')).'</p>';

	// other outbound feeds
	$outbound .= '<p>'.i18n::s('More specific outbound feeds are also available. Look for the XML button at other places:').'</p>'."\n<ul>\n"
		.'<li>'.sprintf(i18n::s('Browse the %s; each section has its own feed.'), Skin::build_link('sections/', i18n::s('site map'), 'shortcut')).'</li>'
		.'<li>'.sprintf(i18n::s('Or browse %s to get a more focused feed.'), Skin::build_link('categories/', i18n::s('categories'), 'shortcut')).'</li>'
		.'<li>'.sprintf(i18n::s('Visit %s, each one has a feed to monitor contributions from one person.'), Skin::build_link('users/', i18n::s('user profiles'), 'shortcut')).'</li>'
		.'<li>'.sprintf(i18n::s('You can even use our %s to build one customised feed.'), Skin::build_link('search.php', i18n::s('search engine'), 'shortcut')).'</li>';

	// help Google
	if(Surfer::is_associate())
		$outbound .= '<li>'.sprintf(i18n::s('To help %s we also maintain a %s to be indexed.'), Skin::build_link(i18n::s('https://www.google.com/webmasters/sitemaps/'), i18n::s('Google'), 'external'), Skin::build_link('sitemap.php', i18n::s('Sitemap list of important pages'), 'xml')).'</li>';

	// feeding events
	if(Surfer::is_associate())
		$outbound .= '<li>'.sprintf(i18n::s('As a an associate, you can also access the %s.'), Skin::build_link('agents/feed.php', i18n::s('event log'), 'xml')).'</li>';

	// end of outbound feeds
	$outbound.= "\n</ul>\n";

	// get local news
	include_once 'feeds.php';
	$rows = Feeds::get_local_news();
	if(is_array($rows)) {
		$outbound .= Skin::build_block(i18n::s('Current local news'), 'title');
		$outbound .= "<ul>\n";
		foreach($rows as $url => $attributes) {
			list($time, $title, $author, $section, $image, $description) = $attributes;
			$outbound .= '<li>'.$title.' ('.$url.")</li>\n";
		}
		$outbound .= "</ul>\n";
	}

	// display in a separate panel
	if(trim($outbound))
		$panels[] = array('outbound', i18n::s('Outbound feeds'), 'outbound_panel', $outbound);

	// inbound feeds, but only to associates
	//
	if(Surfer::is_associate()) {

		$inbound = '';

		// list existing feeders
		include_once $context['path_to_root'].'servers/servers.php';
		if($items = Servers::list_for_feed(0, COMPACT_LIST_SIZE, 'full')) {

			// link to the index of server profiles
			$inbound .= '<p>'.sprintf(i18n::s('To extend the list of feeders add adequate %s.'), Skin::build_link('servers/', i18n::s('server profiles'), 'shortcut'))."</p>\n";

			// list of profiles used as news feeders
			$inbound .= Skin::build_list($items, 'decorated');

		// no feeder defined
		} else
			$inbound .= sprintf(i18n::s('No feeder has been defined. If you need to integrate some external RSS %s.'), Skin::build_link('servers/edit.php', i18n::s('add a server profile')));

		// get news from remote feeders
		include_once 'feeds.php';
		$news = Feeds::get_remote_news();
		if(is_array($news)) {
			$inbound .= Skin::build_block(i18n::s('Most recent external news'), 'title');

			// list of profiles used as news feeders
			$inbound .= Skin::build_list($news, 'compact');

		}

		// display in a separate panel
		if(trim($inbound))
			$panels[] = array('inbound', i18n::s('Inbound feeds'), 'inbound_panel', $inbound);

	}

	// assemble all tabs
	//
	$text .= Skin::build_tabs($panels);

	// cache, whatever change, for 5 minutes
	Cache::put($cache_id, $text, 'stable', 300);
}
$context['text'] .= $text;

//
// extra panel
//

// page tools
if(Surfer::is_associate())
	$context['page_tools'][] = Skin::build_link('feeds/configure.php', i18n::s('Configure'), 'basic');

// an extra box with popular standard icons for newsfeeds
$text = '';

Skin::define_img('FEEDS_OPML_IMG', 'feeds/opml.png');
$text .= Skin::build_link(Feeds::get_url('opml'), FEEDS_OPML_IMG, '').BR;

Skin::define_img('FEEDS_ATOM_IMG', 'feeds/atom.png');
$text .= Skin::build_link(Feeds::get_url('atom'), FEEDS_ATOM_IMG, '').BR;

Skin::define_img('FEEDS_RSS_IMG', 'feeds/rss_2.0.png');
$text .= Skin::build_link(Feeds::get_url('rss'), FEEDS_RSS_IMG, '').BR;

Skin::define_img('FEEDS_RDF_IMG', 'feeds/rss_1.0.png');
$text .= Skin::build_link(Feeds::get_url('rdf'), FEEDS_RDF_IMG, '').BR;

$context['components']['channels'] = Skin::build_box(i18n::s('Pick a feed'), '<p>'.$text.'</p>', 'channels');

// public aggregrators
if(!isset($context['without_internet_visibility']) || ($context['without_internet_visibility'] != 'Y')) {
	$link = $context['url_to_home'].$context['url_to_root'].Feeds::get_url('rss');
	$context['components']['channels'] .= Skin::build_box(i18n::s('Aggregate this site'), '<p>'.join(BR, Skin::build_subscribers($link)).'</p>', 'channels');
}

// referrals, if any
$context['components']['referrals'] = Skin::build_referrals('feeds/index.php');

// render the skin
render_skin();

?>
