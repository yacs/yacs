<?php
/**
 * view one article
 *
 * @todo add 'add tag' button http://www.socialtext.com/products/tour/categories
 *
 * The main panel has following elements:
 * - The article itself, with details, introduction, and main text. This may be overloaded if required.
 * - The list of related files
 * - The list of comments, if the focus is on comments or if comments are threaded
 * - The list of related links
 *
 * There are several options to display author's information, depending of option set in section.
 * Poster's avatar is displayed if the layout is a forum and if we are not building the page for a mobile device.
 *
 * If the main description field of the article has been split into pages with the keyword [code]&#91;page][/code],
 * a navigation menu is added at the bottom of the page to move around.
 *
 * A bar of links to previous, next and index pages is inserted at several places if the layout is a manual.
 * The idea comes from the layout of the [Link=MySQL Reference Manual]http://dev.mysql.com/doc/mysql/en/index.html[/link].
 *
 * @link http://dev.mysql.com/doc/mysql/en/index.html MySQL Reference Manual
 *
 * The files section is displayed only if some file has already been attached to this page.
 * Else only a command to add a file is displayed in the main menu bar.
 *
 * The list of comments is based on a specific layout, depending on options set for the anchoring section.
 * For layouts 'daily' and 'yabb', a first page of comments is put below the article, and remaining comments
 * are available on secondary pages (see comments/list.php). For other layouts, the first comment is visible at
 * secondary page, and a simple link to it is put here.
 *
 * The links section is displayed only if some link has already been attached to this page.
 * Else a command to add a link is displayed in the main menu bar, and a command
 * to trackback is added to the bottom menu bar.
 *
 * An extended menu is featured at page bottom, which content depends on
 * items attached to this page.
 *
 * The extra panel has following elements:
 * - Navigation links to previous and next pages in the same section, if any
 * - Contextual links to switch to sections in the neighbour
 * - Tools including icons and links to comment the page, send an image, etc.
 * - The list of twin pages (with the same nick name)
 * - The list of related categories, into a sidebar box
 * - The nearest locations, if any, into a sidebar box
 * - Means to reference this page, into a sidebar box
 * - The top popular referrals, if any
 *
 * Several HTTP headers, or &lt;meta&gt; attributes of the displayed page, are set dynamically here
 * to help advanced web usage. This includes:
 * - a link to the section page (e.g., '&lt;link rel="contents" href="http://127.0.0.1/yacs/sections/view.php/4038" title="Ma cat&eacute;gorie" type="text/html" /&gt;')
 * - a link to a RDF description of this page (e.g., '&lt;link rel="meta" href="http://127.0.0.1/yacs/articles/describe.php/4310" title="rdf" type="application/rdf+xml" /&gt;')
 * - a rdf section implementing the [link=trackback]http://www.movabletype.org/docs/mttrackback.html[/link] interface
 * - a [link=pingback]http://www.hixie.ch/specs/pingback/pingback[/link] link (e.g., '&lt;link rel="pingback" href="http://here/yacs/services/pingback.php" /&gt;')
 * - a link to the [link=Comment API]http://wellformedweb.org/CommentAPI/[/link] for this page
 * - a link to the next page, if neighbours have been defined, enabling pre-fetching
 *
 * @link http://www.mozilla.org/projects/netlib/Link_Prefetching_FAQ.html Link Prefetching FAQ
 *
 * Meta information also includes:
 * - page description, which is a copy of the introduction, if any, or the default general description parameter
 * - page author, who is the original creator
 * - page publisher, if any
 *
 * The displayed article is saved into the history of visited pages if the global parameter
 * [code]pages_without_history[/code] has not been set to 'Y'.
 *
 * @see skins/configure.php
 *
 * Accept following invocations:
 * - view.php/12 (view the first page of the article document)
 * - view.php/12/nick_name (add nick name to regular id, for URL rewriting)
 * - view.php?id=12 (view the first page of the article document)
 * - view.php?id=12&variant=mobile (mobile edition)
 * - view.php/12/articles/1 (view the page 1 of the main content)
 * - view.php?id=12&articles=1 (view the page 1 of the main content)
 * - view.php/12/categories/1 (view the page 1 of the list of related categories)
 * - view.php?id=12&categories=1 (view the page 1 of the list of related categories)
 * - view.php/12/comments/1 (view the page 1 of the list of related comments)
 * - view.php?id=12&comments=1 (view the page 1 of the list of related comments)
 * - view.php/12/files/2 (view the page 2 of the list of related files)
 * - view.php?id=12&files=2 (view the page 2 of the list of related files)
 * - view.php/12/links/1 (view the page 1 of the list of related links)
 * - view.php?id=12&links=1 (view the page 1 of the list of related links)
 *
 * @link http://www.movabletype.org/docs/mttrackback.html TrackBack Technical Specification
 * @link http://www.hixie.ch/specs/pingback/pingback Pingback specification
 *
 * If this article, or one of its anchor, specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @tester Ghjmora
 * @tester Eoin
 * @tester Mark
 * @tester Moi-meme
 * @tester Macnana
 * @tester Guillaume Perez
 * @tester Cyril Blondin
 * @tester NickR
 * @tester Thierry Pinelli (ThierryP)
 * @tester Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../behaviors/behaviors.php';
include_once '../comments/comments.php';		// attached comments and notes
include_once '../images/images.php';			// attached images
include_once '../links/links.php';				// related pages
include_once '../locations/locations.php';
include_once '../overlays/overlay.php';
include_once '../versions/versions.php';		// back in history

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// encode ISO-8859-1 argument, if any
if(isset($_SERVER['HTTP_ACCEPT_CHARSET']) && preg_match('/^iso-8859-1/i', $_SERVER['HTTP_ACCEPT_CHARSET']))
	$id = utf8_encode($id);

// page within a page
if(isset($_REQUEST['page']))
	$page = $_REQUEST['page'];
else
	$page = 1;

// sanity check
$page = min(max(1,intval($page)), 20);

// no follow-up page yet
$zoom_type = '';
$zoom_index = 1;

// view.php?id=12&categories=2
if(isset($_REQUEST['categories']) && ($zoom_index = $_REQUEST['categories']))
	$zoom_type = 'categories';

// view.php?id=12&comments=2
elseif(isset($_REQUEST['comments']) && ($zoom_index = $_REQUEST['comments']))
	$zoom_type = 'comments';

// view.php?id=12&files=2
elseif(isset($_REQUEST['files']) && ($zoom_index = $_REQUEST['files']))
	$zoom_type = 'files';

// view.php?id=12&links=2
elseif(isset($_REQUEST['links']) && ($zoom_index = $_REQUEST['links']))
	$zoom_type = 'links';

// view.php?id=12&users=2
elseif(isset($_REQUEST['users']) && ($zoom_index = $_REQUEST['users']))
	$zoom_type = 'users';

// view.php/12/files/2
elseif(isset($context['arguments'][1]) && isset($context['arguments'][2])) {
	$zoom_type = $context['arguments'][1];
	$zoom_index = $context['arguments'][2];
}

// view.php/12/nick name induces no particular processing

// sanity check
if($zoom_index < 1)
	$zoom_index = 1;

// get the item from the database
$item =& Articles::get($id);

// get poster profile, if any
$poster = array();
if(isset($item['create_id']))
	$poster =& Users::get($item['create_id']);

// get the related overlay, if any
$overlay = NULL;
if(isset($item['overlay']))
	$overlay = Overlay::load($item);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']))
	$anchor =& Anchors::get($item['anchor']);

// also load the article as an object
$article = NULL;
if(isset($item['id'])) {
	include_once 'article.php';
	$article = new Article();
	$article->load_by_content($item, $anchor);
}

// get related behaviors, if any
$behaviors = NULL;
if(isset($item['id']))
	$behaviors = new Behaviors($item, $anchor);

// owners can do what they want
if(Articles::allow_modification($item, $anchor))
	Surfer::empower();

// readers have additional rights
elseif(Surfer::is_logged() && is_object($anchor) && $anchor->is_assigned())
	Surfer::empower('S');
elseif(isset($item['id']) && Articles::is_assigned($item['id']) && Surfer::is_logged())
	Surfer::empower('S');

//
// is this surfer allowed to browse the page?
//

// associates, editors and readers can read this page
if(Surfer::is_empowered('S'))
	$permitted = TRUE;

// change default behavior
elseif(isset($item['id']) && is_object($behaviors) && !$behaviors->allow('articles/view.php', 'article:'.$item['id']))
	$permitted = FALSE;

// the anchor has to be viewable by this surfer
elseif(is_object($anchor) && !$anchor->is_viewable())
	$permitted = FALSE;

// access is restricted to authenticated surfer
elseif(isset($item['active']) && ($item['active'] == 'R') && Surfer::is_logged())
	$permitted = TRUE;

// public access is allowed
elseif(isset($item['active']) && ($item['active'] == 'Y'))
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// is the article on user watch list?
$in_watch_list = FALSE;
if(isset($item['id']) && Surfer::get_id())
	$in_watch_list = Members::check('article:'.$item['id'], 'user:'.Surfer::get_id());

// has this page some versions?
$has_versions = FALSE;
if(isset($item['id']) && !$zoom_type && Surfer::is_empowered() && Versions::count_for_anchor('article:'.$item['id']))
	$has_versions = TRUE;

// load the skin, maybe with a variant
load_skin('article', $anchor, isset($item['options']) ? $item['options'] : '');

// clear the tab we are in
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// path to this page
$context['path_bar'] = Surfer::get_path_bar($anchor);

// page title
if(isset($item['active']) && ($item['active'] == 'R'))
	$context['page_title'] .= RESTRICTED_FLAG.' ';
elseif(isset($item['active']) && ($item['active'] == 'N'))
	$context['page_title'] .= PRIVATE_FLAG.' ';
if(is_object($overlay))
	$context['page_title'] .= $overlay->get_text('title', $item);
elseif(isset($item['title']))
	$context['page_title'] .= $item['title'];
if(isset($item['locked']) && ($item['locked'] == 'Y') && Articles::is_owned($item, $anchor))
	$context['page_title'] .= ' '.LOCKED_FLAG;

// page language, if any
if(isset($item['language']) && $item['language'] && ($item['language'] != 'none'))
	$context['page_language'] = $item['language'];

// not found -- help web crawlers
if(!isset($item['id'])) {
	include '../error.php';

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Articles::get_permalink($item)));

	// permission denied to authenticated user
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// stop crawlers on non-published pages
} elseif((!isset($item['publish_date']) || ($item['publish_date'] <= NULL_DATE)) && Surfer::is_crawler()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// re-enforce the canonical link
} elseif(!$zoom_type && ($page == 1) && $context['self_url'] && ($canonical = $context['url_to_home'].$context['url_to_root'].Articles::get_permalink($item)) && strncmp($context['self_url'], $canonical, strlen($canonical))) {
	Safe::header('Status: 301 Moved Permanently', TRUE, 301);
	Safe::header('Location: '.$canonical);
	Logger::error(Skin::build_link($canonical));

// display the article
} else {

	// allow back-referencing from overlay
	$item['self_reference'] = 'article:'.$item['id'];
	$item['self_url'] = $context['url_to_root'].Articles::get_permalink($item);

	// behaviors can change page menu
	if(is_object($behaviors))
		$context['page_menu'] =& $behaviors->add_commands('articles/view.php', 'article:'.$item['id'], $context['page_menu']);

	// remember surfer visit
	Surfer::is_visiting(Articles::get_permalink($item), Codes::beautify_title($item['title']), 'article:'.$item['id'], $item['active']);

	// increment silently the hits counter if not associate, nor creator, nor at follow-up page -- editors are taken into account
	if(Surfer::is_associate())
		;
	elseif(isset($item['owner_id']) && Surfer::is($item['owner_id']))
		;
	elseif(!$zoom_type) {
		$item['hits'] = isset($item['hits'])?($item['hits']+1):1;
		Articles::increment_hits($item['id']);
	}

	// initialize the rendering engine
	Codes::initialize(Articles::get_permalink($item));

	// neighbours information
	$neighbours = NULL;
	if(Articles::has_option('with_neighbours', $anchor, $item))
		$neighbours = $anchor->get_neighbours('article', $item);

	//
	// set page image -- $context['page_image']
	//

	// the article or the anchor icon, if any
	if(isset($item['icon_url']) && $item['icon_url'])
		$context['page_image'] = $item['icon_url'];
	elseif(is_object($anchor))
		$context['page_image'] = $anchor->get_icon_url();

	//
	// set page meta-information -- $context['page_header'], etc.
	//

	// add meta information, if any
	if(isset($item['meta']) && $item['meta'])
		$context['page_header'] .= $item['meta'];

	// add canonical link
	if(!$zoom_type)
		$context['page_header'] .= "\n".'<link rel="canonical" href="'.$context['url_to_home'].$context['url_to_root'].Articles::get_permalink($item).'" />';

	// a meta link to prefetch the next page
	if(isset($neighbours[2]) && $neighbours[2])
		$context['page_header'] .= "\n".'<link rel="next" href="'.$context['url_to_home'].$context['url_to_root'].$neighbours[2].'" title="'.encode_field($neighbours[3]).'" />';

	// a meta link to the section front page
	if(is_object($anchor))
		$context['page_header'] .= "\n".'<link rel="contents" href="'.$context['url_to_home'].$context['url_to_root'].$anchor->get_url().'" title="'.encode_field($anchor->get_title()).'" type="text/html" />';

	// a meta link to a description page (actually, rdf)
	$context['page_header'] .= "\n".'<link rel="meta" href="'.$context['url_to_home'].$context['url_to_root'].Articles::get_url($item['id'], 'describe').'" title="Meta Information" type="application/rdf+xml" />';

	// implement the trackback interface
	$permanent_link = $context['url_to_home'].$context['url_to_root'].Articles::get_permalink($item);
	$trackback_link = $context['url_to_home'].$context['url_to_root'].'links/trackback.php?anchor=article:'.$item['id'];
	$context['page_header'] .= "\n".'<!--'
		."\n".'<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"'
		."\n".' 		xmlns:dc="http://purl.org/dc/elements/1.1/"'
		."\n".' 		xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/">'
		."\n".'<rdf:Description'
		."\n".' trackback:ping="'.$trackback_link.'"'
		."\n".' dc:identifier="'.$permanent_link.'"'
		."\n".' rdf:about="'.$permanent_link.'" />'
		."\n".'</rdf:RDF>'
		."\n".'-->';

	// implement the pingback interface
	$context['page_header'] .= "\n".'<link rel="pingback" href="'.$context['url_to_root'].'services/ping.php" title="Pingback Interface" />';

	// implement the Comment API interface
	$context['page_header'] .= "\n".'<link rel="service.comment" href="'.$context['url_to_root'].Comments::get_url('article:'.$item['id'], 'service.comment').'" title="Comment Interface" type="text/xml" />';

	// set specific headers
	if(isset($item['introduction']) && $item['introduction'])
		$context['page_description'] = strip_tags(Codes::beautify_introduction($item['introduction']));
	if(isset($item['create_name']) && $item['create_name'])
		$context['page_author'] = $item['create_name'];
	if(isset($item['publish_name']) && $item['publish_name'])
		$context['page_publisher'] = $item['publish_name'];

	//
	// set page details -- $context['page_details']
	//
	$text = '';

	// do not mention details at follow-up pages, nor to crawlers
	if(!$zoom_type && !Surfer::is_crawler()) {

		// tags, if any
		if(isset($item['tags']))
			$context['page_tags'] =& Skin::build_tags($item['tags'], 'article:'.$item['id']);

		// one detail per line
		$text .= '<p class="details">';
		$details = array();

		// add details from the overlay, if any
		if(is_object($overlay) && ($more = $overlay->get_text('details', $item)))
			$details[] = $more;

		// article rating, if the anchor allows for it, and if no rating has already been registered
		if(!Articles::has_option('without_rating', $anchor, $item) && !Articles::has_option('rate_as_digg', $anchor, $item)) {

			// report on current rating
			$label = '';
			if($item['rating_count'])
				$label .= Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])).' '.sprintf(i18n::ns('%d rate', '%d rates', $item['rating_count']), $item['rating_count']).' ';
			if(!$label)
				$label .= i18n::s('Rate this page');

			// link to the rating page
			$label = Skin::build_link(Articles::get_url($item['id'], 'rate'), $label, 'span', i18n::s('Rate this page'));

			// feature page rating
			$details[] = $label;
		}

		// the source, if any
		if($item['source']) {
			if(preg_match('/(http|https|ftp):\/\/([^\s]+)/', $item['source'], $matches))
				$item['source'] = Skin::build_link($matches[0], $matches[0], 'external');
			elseif(strpos($item['source'], '[') === 0) {
				if($attributes = Links::transform_reference($item['source'])) {
					list($link, $title, $description) = $attributes;
					$item['source'] = Skin::build_link($link, $title);
				}
			}
			$details[] = sprintf(i18n::s('Source: %s'), $item['source']);
		}

		// restricted to logged members
		if($item['active'] == 'R')
			$details[] = RESTRICTED_FLAG.' '.i18n::s('Community - Access is restricted to authenticated members');

		// restricted to associates
		elseif($item['active'] == 'N')
			$details[] = PRIVATE_FLAG.' '.i18n::s('Private - Access is restricted to selected persons');

		// expired article
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		if((Surfer::is_associate() || Articles::is_assigned($item['id']) || (is_object($anchor) && $anchor->is_assigned()))
				&& ($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $now)) {
			$details[] = EXPIRED_FLAG.' '.sprintf(i18n::s('Page has expired %s'), Skin::build_date($item['expiry_date']));
		}

		// page editors, for associates and section editors
		if(Surfer::is_empowered() && Surfer::is_logged() && ($items =& Members::list_users_by_posts_for_member('article:'.$item['id'], 0, USERS_LIST_SIZE, 'comma')))
			$details[] = sprintf(i18n::s('%s: %s'), Skin::build_link(Users::get_url('article:'.$item['id'], 'select'), i18n::s('Editors')), Skin::build_list($items, 'comma'));

		// page watchers
		if(Surfer::is_logged() && ($items =& Members::list_watchers_by_posts_for_anchor('article:'.$item['id'], 0, 50, 'comma')))
			$details[] = sprintf(i18n::s('%s: %s'), i18n::s('Watchers'), Skin::build_list($items, 'comma'));

		// no more details
		if(count($details))
			$text .= ucfirst(implode(BR."\n", $details)).BR."\n";

		// other details
		$details =& Articles::build_dates($anchor, $item);

		// signal articles to be published
		if(($item['publish_date'] <= NULL_DATE)) {
			if(Articles::allow_publication($anchor, $item))
				$label = Skin::build_link(Articles::get_url($item['id'], 'publish'), i18n::s('not published'));
			else
				$label = i18n::s('not published');
			$details[] = DRAFT_FLAG.' '.$label;
		}

		// the number of hits
		if(($item['hits'] > 1) && (Articles::is_owned($item, $anchor)
			|| ((!isset($context['content_without_details']) || ($context['content_without_details'] != 'Y')) || Articles::has_option('with_details', $anchor, $item)) ) ) {

			// flag popular pages
			$popular = '';
			if($item['hits'] > 100)
				$popular = POPULAR_FLAG;

			// show the number
			if(Articles::is_owned($item, $anchor) || ($item['hits'] < 100))
				$details[] = $popular.Skin::build_number($item['hits'], i18n::s('hits'));

			// other surfers will benefit from a stable ETag
			elseif($popular)
				$details[] = $popular;
		}

		// rank for this article
		if((intval($item['rank']) != 10000) && Articles::is_owned($item, $anchor))
			$details[] = '{'.$item['rank'].'}';

		// locked article
		if(Surfer::is_member() && isset($item['locked']) && ($item['locked'] == 'Y') )
			$details[] = LOCKED_FLAG.' '.i18n::s('page is locked.');

		// in-line details
		if(count($details))
			$text .= ucfirst(implode(', ', $details));

		// reference this item
		if(Surfer::is_member()) {
			$text .= BR.sprintf(i18n::s('Code to reference this page: %s'), '[article='.$item['id'].']');

			// the nick name
			if($item['nick_name'] && ($link = normalize_shortcut($item['nick_name'], TRUE)))
				$text .= BR.sprintf(i18n::s('Shortcut: %s'), $link);
		}

		// no more details
		$text .= "</p>\n";

		// update page details
		$context['page_details'] .= $text;

	}

	//
	// generic page components --can be overwritten in view_as_XXX.php if necessary
	//

	// the poster profile, if any, aside
	if(isset($poster['id']) && is_object($anchor))
		$context['components']['profile'] = $anchor->get_user_profile($poster, 'extra', Skin::build_date($item['create_date']));

	// add extra information from the overlay, if any
	if(is_object($overlay))
		$context['components']['overlay'] = $overlay->get_text('extra', $item);

	// add extra information from this item, if any
	if(isset($item['extra']) && $item['extra'])
		$context['components']['boxes'] = Codes::beautify_extra($item['extra']);

	// 'Share' box
	//
	$lines = array();

	// mail this page
	if((Articles::is_owned($item, $anchor) || ($item['active'] == 'Y')) && isset($context['with_email']) && ($context['with_email'] == 'Y')) {
		Skin::define_img('ARTICLES_INVITE_IMG', 'articles/invite.gif');
		$lines[] = Skin::build_link(Articles::get_url($item['id'], 'invite'), ARTICLES_INVITE_IMG.i18n::s('Invite participants'), 'basic', i18n::s('Spread the word'));
	}

	// assign command provided to page owners
 	if(Articles::is_owned($item, $anchor, TRUE) || Surfer::is_associate()) {
 		Skin::define_img('ARTICLES_ASSIGN_IMG', 'articles/assign.gif');
 		$lines[] = Skin::build_link(Users::get_url('article:'.$item['id'], 'select'), ARTICLES_ASSIGN_IMG.i18n::s('Manage editors'));
 	}

	// facebook, twitter, linkedin
	if(($item['active'] == 'Y') && ((!isset($context['without_internet_visibility']) || ($context['without_internet_visibility'] != 'Y')))) {
		Skin::define_img('PAGERS_FACEBOOK_IMG', 'pagers/facebook.gif');
		$lines[] = Skin::build_link('http://www.facebook.com/share.php?u='.urlencode($context['url_to_home'].$context['url_to_root'].Articles::get_permalink($item)).'&t='.urlencode($item['title']), PAGERS_FACEBOOK_IMG.i18n::s('Share at Facebook'), 'basic', i18n::s('Spread the word'));

		Skin::define_img('PAGERS_TWITTER_IMG', 'pagers/twitter.gif');
		$lines[] = Skin::build_link('http://twitter.com/home?status='.urlencode($item['title'].' '.$context['url_to_home'].$context['url_to_root'].Articles::get_permalink($item)), PAGERS_TWITTER_IMG.i18n::s('Share at Twitter'), 'basic', i18n::s('Spread the word'));

		Skin::define_img('PAGERS_LINKEDIN_IMG', 'pagers/linkedin.gif');
		$lines[] = Skin::build_link('http://www.linkedin.com/shareArticle?mini=true&url='.$context['url_to_home'].$context['url_to_root'].Articles::get_permalink($item).'&title='.urlencode($item['title']).'&summary='.urlencode($item['introduction']).'&source='.urlencode($anchor->get_title()), PAGERS_LINKEDIN_IMG.i18n::s('Share at LinkedIn'), 'basic', i18n::s('Spread the word'));

	}

	// the command to track back
	if(Links::allow_trackback()) {
		Skin::define_img('TOOLS_TRACKBACK_IMG', 'tools/trackback.gif');
		$lines[] = Skin::build_link('links/trackback.php?anchor='.urlencode('article:'.$item['id']), TOOLS_TRACKBACK_IMG.i18n::s('Reference this page'), 'basic', i18n::s('Various means to link to this page'));
	}

	// more tools
	if(((isset($context['with_export_tools']) && ($context['with_export_tools'] == 'Y'))
		|| (is_object($anchor) && $anchor->has_option('with_export_tools')))) {

		// check tools visibility
		if(Surfer::is_member() || (isset($context['with_anonymous_export_tools']) && ($context['with_anonymous_export_tools'] == 'Y'))) {

			// get a PDF version
			Skin::define_img('ARTICLES_PDF_IMG', 'articles/export_pdf.gif');
			$lines[] = Skin::build_link(Articles::get_url($item['id'], 'fetch_as_pdf'), ARTICLES_PDF_IMG.i18n::s('Save as PDF'), 'basic', i18n::s('Save as PDF'));

			// open in Word
			Skin::define_img('ARTICLES_WORD_IMG', 'articles/export_word.gif');
			$lines[] = Skin::build_link(Articles::get_url($item['id'], 'fetch_as_msword'), ARTICLES_WORD_IMG.i18n::s('Copy in MS-Word'), 'basic', i18n::s('Copy in MS-Word'));

		}
	}

	// export to XML command provided to associates -- complex command
// 	if(!$zoom_type && Surfer::is_associate() && Surfer::has_all())
// 		$lines[] = Skin::build_link(Articles::get_url($item['id'], 'export'), i18n::s('Export to XML'), 'basic');

	// print this page
	if(Surfer::is_logged() || (isset($context['with_anonymous_export_tools']) && ($context['with_anonymous_export_tools'] == 'Y'))) {
		Skin::define_img('TOOLS_PRINT_IMG', 'tools/print.gif');
		$lines[] = Skin::build_link(Articles::get_url($item['id'], 'print'), TOOLS_PRINT_IMG.i18n::s('Print this page'), 'basic', i18n::s('Get a paper copy of this page.'));
	}

	// in a side box
	if(count($lines))
		$context['components']['share'] = Skin::build_box(i18n::s('Share'), Skin::finalize_list($lines, 'tools'), 'share', 'share');

	// 'Information channels' box
	//
	$lines = array();

	// watch command is provided to logged surfers
	if(Surfer::get_id()) {

		$link = Users::get_url('article:'.$item['id'], 'track');

		if($in_watch_list)
			$label = i18n::s('Stop notifications');
		else
			$label = i18n::s('Watch this page');

		Skin::define_img('TOOLS_WATCH_IMG', 'tools/watch.gif');
		$lines[] = Skin::build_link($link, TOOLS_WATCH_IMG.$label, 'basic', i18n::s('Manage your watch list'));

	}

	// allow to leave the page
	if(Articles::is_assigned($item['id']) && !Articles::is_owned($item, $anchor, TRUE)) {
		Skin::define_img('ARTICLES_ASSIGN_IMG', 'articles/assign.gif');
		$lines[] = Skin::build_link(Users::get_url('article:'.$item['id'], 'select'), ARTICLES_ASSIGN_IMG.i18n::s('Leave this page'));
	}

	// get news from rss
	if(isset($item['id']) && (!isset($context['skins_general_without_feed']) || ($context['skins_general_without_feed'] != 'Y')) ) {

		// list of attached files
		$lines[] = Skin::build_link($context['url_to_home'].$context['url_to_root'].Files::get_url('article:'.$item['id'], 'feed'), i18n::s('Recent files'), 'xml');

		// comments are allowed
		if(Comments::allow_creation($anchor, $item)) {
			$lines[] = Skin::build_link($context['url_to_home'].$context['url_to_root'].Comments::get_url('article:'.$item['id'], 'feed'), i18n::s('Recent comments'), 'xml');

			// public aggregators
// 			if(!isset($context['without_internet_visibility']) || ($context['without_internet_visibility'] != 'Y'))
// 				$lines[] = join(BR, Skin::build_subscribers($context['url_to_home'].$context['url_to_root'].Comments::get_url('article:'.$item['id'], 'feed'), $item['title']));
		}
	}

	// in a side box
	if(count($lines))
		$context['components']['channels'] = Skin::build_box(i18n::s('Monitor'), join(BR, $lines), 'channels', 'feeds');

	// twin pages
	if(isset($item['nick_name']) && $item['nick_name']) {

		// build a complete box
		$box['text'] = '';

		// list pages with same name
		$items =& Articles::list_for_name($item['nick_name'], $item['id'], 'compact');

		// actually render the html for the section
		if(is_array($items))
			$box['text'] .= Skin::build_list($items, 'compact');
		if($box['text'])
			$context['components']['twins'] = Skin::build_box(i18n::s('Related'), $box['text'], 'twins', 'twins');

	}

	// the contextual menu, in a navigation box, if this has not been disabled
	if( !Articles::has_option('no_contextual_menu', $anchor, $item)
		&& isset($context['current_focus']) && ($menu =& Skin::build_contextual_menu($context['current_focus']))) {

		// use title from topmost level
		if(count($context['current_focus']) && ($top_anchor =& Anchors::get($context['current_focus'][0]))) {
			$box_title = $top_anchor->get_title();
			$box_url = $top_anchor->get_url();

		// generic title
		} else {
			$box_title = i18n::s('Navigation');
			$box_url = '';
		}

		// in a navigation box
		$box_popup = '';
		$context['components']['contextual'] = Skin::build_box($box_title, $menu, 'contextual', 'contextual_menu', $box_url, $box_popup);
	}

	// categories attached to this article, if not at another follow-up page
	if(!$zoom_type || ($zoom_type == 'categories')) {

		// build a complete box
		$box['bar'] = array();
		$box['text'] = '';

		// list categories by title
		$offset = ($zoom_index - 1) * CATEGORIES_PER_PAGE;
		$items =& Members::list_categories_by_title_for_member('article:'.$item['id'], $offset, CATEGORIES_PER_PAGE, 'sidebar');

		// the command to change categories assignments
		if(Categories::allow_creation($anchor, $item))
			$items = array_merge($items, array( Categories::get_url('article:'.$item['id'], 'select') => i18n::s('Assign categories') ));

		// actually render the html for the section
		if(is_array($box['bar']))
			$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');
		if(is_array($items))
			$box['text'] .= Skin::build_list($items, 'compact');
		if($box['text'])
			$context['components']['categories'] = Skin::build_box(i18n::s('See also'), $box['text'], 'categories', 'categories');

	}

	// referrals, if any
	$context['components']['referrals'] =& Skin::build_referrals(Articles::get_permalink($item));

	//
	// use a specific script to render the page in replacement of the standard one
	//

	// the overlay may generate some tabs
	$context['tabs'] = '';
	if(is_object($overlay))
		$context['tabs'] = $overlay->get_tabs('view', $item);

	// branch to another script
	if(!Surfer::is_desktop()) {
		include 'view_on_mobile.php';
		return;
	} elseif(isset($item['options']) && preg_match('/\bview_as_[a-zA-Z0-9_\.]+?\b/i', $item['options'], $matches) && is_readable($matches[0].'.php')) {
		include $matches[0].'.php';
		return;
	} elseif(is_object($anchor) && ($viewer = $anchor->has_option('view_as')) && is_readable('view_as_'.$viewer.'.php')) {
		$name = 'view_as_'.$viewer.'.php';
		include $name;
		return;
	} elseif(is_array($context['tabs']) && count($context['tabs'])) {
		include 'view_as_tabs.php';
		return;
	}

	//
	// compute main panel -- $context['text']
	//
	$text = '';

	// insert anchor prefix
	if(is_object($anchor))
		$text .= $anchor->get_prefix();

	// display very few things if we are on a follow-up page (comments, files, etc.)
	if($zoom_type)
		$text .= Codes::beautify($item['introduction'], $item['options']);

	// else expose full details
	else {

		// buttons to display previous and next pages, if any
		if($neighbours)
			$text .= Skin::neighbours($neighbours, 'manual');

		// the poster profile, if any, at the beginning of the first page
		if(($page == 1) && isset($poster['id']) && is_object($anchor))
			$text .= $anchor->get_user_profile($poster, 'prefix', Skin::build_date($item['create_date']));

		// only at the first page
		if($page == 1) {

			// article rating, if the anchor allows for it, and if no rating has already been registered
			if(!Articles::has_option('without_rating', $anchor, $item) && Articles::has_option('rate_as_digg', $anchor, $item)) {

				// rating
				if($item['rating_count'])
					$rating_label = sprintf(i18n::ns('%s vote', '%s votes', $item['rating_count']), '<span class="big">'.$item['rating_count'].'</span>'.BR);
				else
					$rating_label = i18n::s('No vote');

				// a rating has already been registered
				$digg = '';
				if(isset($_COOKIE['rating_'.$item['id']]))
					Cache::poison();

				// where the surfer can rate this item
				else
					$digg = '<div class="rate">'.Skin::build_link(Articles::get_url($item['id'], 'rate'), i18n::s('Rate it'), 'basic').'</div>';

				// rendering
				$text .= '<div class="digg"><div class="votes">'.$rating_label.'</div>'
					.$digg
					.'</div>';

				// signal DIGG
				define('DIGG', TRUE);
			}

			// the introduction text, if any
			if(is_object($overlay))
				$text .= Skin::build_block($overlay->get_text('introduction', $item), 'introduction');
			else
				$text .= Skin::build_block($item['introduction'], 'introduction');

			// get text related to the overlay, if any
			if(is_object($overlay))
				$text .= $overlay->get_text('view', $item);

		}

		// filter description, if necessary
		if(is_object($overlay))
			$description = $overlay->get_text('description', $item);
		else
			$description = $item['description'];

		// the beautified description, which is the actual page body
		if($description) {

			// use adequate label
			if(is_object($overlay) && ($label = $overlay->get_label('description')))
				$text .= Skin::build_block($label, 'title');

			// provide only the requested page
			$pages = preg_split('/\s*\[page\]\s*/is', $description);
			$page = min(max($page, count($pages)), 1);
			$description = $pages[ $page-1 ];

			// if there are several pages, remove toc and toq codes
			if(count($pages) > 1)
				$description = preg_replace('/\s*\[(toc|toq)\]\s*/is', '', $description);

			// beautify the target page
			$text .= Skin::build_block($description, 'description', '', $item['options']);

			// if there are several pages, add navigation commands to browse them
			if(count($pages) > 1) {
				$page_menu = array( '_' => i18n::s('Pages') );
				$home = Articles::get_permalink($item);
				$prefix = Articles::get_url($item['id'], 'navigate', 'page');
				$page_menu = array_merge($page_menu, Skin::navigate($home, $prefix, count($pages), 1, $page));

				$text .= Skin::build_list($page_menu, 'menu_bar');
			}

		}

		// add trailer information from the overlay, if any
		if(is_object($overlay))
			$text .= $overlay->get_text('trailer', $item);

		// the poster profile, if any, at the end of the page
		if(isset($poster['id']) && is_object($anchor))
			$text .= $anchor->get_user_profile($poster, 'suffix', Skin::build_date($item['create_date']));

	}

	//
	// files attached to this article
	//

	// the list of related files if not at another follow-up page
	if(!$zoom_type || ($zoom_type == 'files')) {

		// list files only to people able to change the page
		if(Articles::allow_modification($item, $anchor))
			$embedded = NULL;
		else
			$embedded = Codes::list_embedded($item['description']);

		// build a complete box
		$box = array('bar' => array(), 'text' => '');

		// count the number of files in this article
		if($count = Files::count_for_anchor('article:'.$item['id'], FALSE, $embedded)) {
			if($count > 20)
				$box['bar'] += array('_count' => sprintf(i18n::ns('%d file', '%d files', $count), $count));

			// list files by date (default) or by title (option files_by_title)
			$offset = ($zoom_index - 1) * FILES_PER_PAGE;
			if(Articles::has_option('files_by_title', $anchor, $item))
				$items = Files::list_by_title_for_anchor('article:'.$item['id'], 0, 100, 'article:'.$item['id'], $embedded);
			else
				$items = Files::list_by_date_for_anchor('article:'.$item['id'], 0, 100, 'article:'.$item['id'], $embedded);

			// actually render the html
			if(is_array($items))
				$box['text'] .= Skin::build_list($items, 'decorated');
			elseif(is_string($items))
				$box['text'] .= $items;

			// the command to post a new file
			if(Files::allow_creation($anchor, $item, 'article')) {
				Skin::define_img('FILES_UPLOAD_IMG', 'files/upload.gif');
				$box['bar'] += array('files/edit.php?anchor='.urlencode('article:'.$item['id']) => FILES_UPLOAD_IMG.i18n::s('Upload a file'));
			}

		}

		// some files have been attached to this page
		if(($page == 1) && ($count > 1)) {

			// the command to download all files
			$link = 'files/fetch_all.php?anchor='.urlencode('article:'.$item['id']);
			if($count > 20)
				$label = i18n::s('Zip 20 first files');
			else
				$label = i18n::s('Zip all files');
			$box['bar'] += array( $link => $label );

		}

		// there is some box content
		if($box['text'])
			$text .= Skin::build_content('files', i18n::s('Files'), $box['text'], $box['bar']);

	}

	//
	// comments attached to this article
	//

	// the list of related comments, if not at another follow-up page
	if(!$zoom_type || ($zoom_type == 'comments')) {

		// title label
		if(is_object($anchor) && $anchor->is_viewable())
			$title_label = ucfirst($anchor->get_label('comments', 'count_many'));
		else
			$title_label = i18n::s('Comments');

		// no layout yet
		$layout = NULL;

		// new comments are allowed
		if(Comments::allow_creation($anchor, $item)) {

			// we have a wall
			if(Articles::has_option('comments_as_wall', $anchor, $item))
				$comments_prefix = TRUE;

			// editors and associates can always contribute to a thread
			else
				$comments_suffix = TRUE;
		}

//			// label for one comment
//			if(is_object($anchor) && $anchor->is_viewable())
//				$comment_label = $anchor->get_label('comments', 'count_one');
//			else
//				$comment_label = i18n::s('comment');

//			// label for several comments
//			if(is_object($anchor) && $anchor->is_viewable())
//				$comments_label = $anchor->get_label('comments', 'count_many');
//			else
//				$comments_label = i18n::s('comments');

		// label to create a comment
		if(is_object($anchor) && $anchor->is_viewable())
			$add_label = $anchor->get_label('comments', 'new_command');
		else
			$add_label = i18n::s('Post a comment');

		// get a layout from anchor
		$layout =& Comments::get_layout($anchor, $item);

		// provide author information to layout
		if(is_object($layout) && $item['create_id'])
			$layout->set_variant('user:'.$item['create_id']);

		// the maximum number of comments per page
		if(is_object($layout))
			$items_per_page = $layout->items_per_page();
		else
			$items_per_page = COMMENTS_PER_PAGE;

		// the first comment to list
		$offset = ($zoom_index - 1) * $items_per_page;
		if(is_object($layout) && method_exists($layout, 'set_offset'))
			$layout->set_offset($offset);

		// build a complete box
		$box = array('bar' => array(), 'prefix_bar' => array(), 'text' => '');

		// feed the wall
		if(isset($comments_prefix))
			$box['text'] .= Comments::get_form('article:'.$item['id']);

		// a navigation bar for these comments
		if($count = Comments::count_for_anchor('article:'.$item['id'])) {
			if($count > 20)
				$box['bar'] += array('_count' => sprintf(i18n::ns('%d comment', '%d comments', $count), $count));

			// list comments by date
			$items = Comments::list_by_date_for_anchor('article:'.$item['id'], $offset, $items_per_page, $layout, isset($comments_prefix));

			// actually render the html
			if(is_array($items))
				$box['text'] .= Skin::build_list($items, 'rows');
			elseif(is_string($items))
				$box['text'] .= $items;

			// navigation commands for comments
			$prefix = Comments::get_url('article:'.$item['id'], 'navigate');
			$box['bar'] = array_merge($box['bar'],
				Skin::navigate(NULL, $prefix, $count, $items_per_page, $zoom_index));
		}

		// new comments are allowed
		if(isset($comments_suffix)) {
			Skin::define_img('COMMENTS_ADD_IMG', 'comments/add.gif');
			$box['bar'] += array( Comments::get_url('article:'.$item['id'], 'comment') => array('', COMMENTS_ADD_IMG.$add_label, '', 'basic', '', i18n::s('Post a comment')));

			// also feature this command at the top
			if($count > 20)
				$box['prefix_bar'] = array_merge($box['prefix_bar'], array( Comments::get_url('article:'.$item['id'], 'comment') => array('', COMMENTS_ADD_IMG.$add_label, '', 'basic', '', i18n::s('Post a comment'))));

		}

		// show commands
		if(count($box['bar'])) {

			// commands before the box
			$box['text'] = Skin::build_list($box['prefix_bar'], 'menu_bar').$box['text'];

			// append the menu bar at the end
			$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');

		}

		// build a box
		if($box['text']) {

			// put a title if there are other titles or if more than 2048 chars
			$title = '';
			if(preg_match('/(<h1|<h2|<h3|<table|\[title|\[subtitle)/i', $context['text'].$text) || (strlen($context['text'].$text) > 2048))
				$title = $title_label;

			// insert a full box
			$box['text'] =& Skin::build_box($title, $box['text'], 'header1', 'comments');
		}

		// there is some box content
		if(trim($box['text']))
			$text .= $box['text'];

	}

	//
	// links attached to this article
	//

	// the list of related links if not at another follow-up page
	if(!$zoom_type || ($zoom_type == 'links')) {

		// build a complete box
		$box = array('bar' => array(), 'text' => '');

		// a navigation bar for these links
		if($count = Links::count_for_anchor('article:'.$item['id'])) {
			if($count > 20)
				$box['bar'] += array('_count' => sprintf(i18n::ns('%d link', '%d links', $count), $count));

			// list links by date (default) or by title (option links_by_title)
			$offset = ($zoom_index - 1) * LINKS_PER_PAGE;
			if(Articles::has_option('links_by_title', $anchor, $item))
				$items = Links::list_by_title_for_anchor('article:'.$item['id'], $offset, LINKS_PER_PAGE);
			else
				$items = Links::list_by_date_for_anchor('article:'.$item['id'], $offset, LINKS_PER_PAGE);

			// actually render the html
			if(is_array($items))
				$box['text'] .= Skin::build_list($items, 'decorated');
			elseif(is_string($items))
				$box['text'] .= $items;

			// navigation commands for links
			$home = Articles::get_permalink($item);
			$prefix = Articles::get_url($item['id'], 'navigate', 'links');
			$box['bar'] += Skin::navigate($home, $prefix, $count, LINKS_PER_PAGE, $zoom_index);

			// new links are allowed
			if(Links::allow_creation($anchor, $item)) {
				Skin::define_img('LINKS_ADD_IMG', 'links/add.gif');
				$box['bar'] += array( 'links/edit.php?anchor='.urlencode('article:'.$item['id']) => LINKS_ADD_IMG.i18n::s('Add a link') );
			}
		}

		// there is some box content
		if($box['text'])
			$text .= Skin::build_content('links', i18n::s('Links'), $box['text'], $box['bar']);

	}

	//
	// trailer information
	//

	// add trailer information from this item, if any
	if(isset($item['trailer']) && trim($item['trailer']))
		$text .= Codes::beautify($item['trailer']);

	// buttons to display previous and next pages, if any
	if($neighbours)
		$text .= Skin::neighbours($neighbours, 'manual');

	// insert anchor suffix
	if(is_object($anchor))
		$text .= $anchor->get_suffix();

	// special layout for digg
	if(defined('DIGG'))
		$text = '<div class="digg_content">'.$text.'</div>';

	// update the main content panel
	$context['text'] .= $text;

	//
	// extra panel
	//

	// page tools
	//

	// comment this page if anchor does not prevent it
	if(Comments::allow_creation($anchor, $item)) {
		Skin::define_img('COMMENTS_ADD_IMG', 'comments/add.gif');
		$context['page_tools'][] = Skin::build_link(Comments::get_url('article:'.$item['id'], 'comment'), COMMENTS_ADD_IMG.i18n::s('Post a comment'), 'basic', i18n::s('Express yourself, and say what you think.'));
	}

	// attach a file, if upload is allowed
	if(Files::allow_creation($anchor, $item, 'article')) {
		Skin::define_img('FILES_UPLOAD_IMG', 'files/upload.gif');
		$context['page_tools'][] = Skin::build_link('files/edit.php?anchor='.urlencode('article:'.$item['id']), FILES_UPLOAD_IMG.i18n::s('Upload a file'), 'basic', i18n::s('Attach related files.'));
	}

	// add a link
	if(Links::allow_creation($anchor, $item)) {
		Skin::define_img('LINKS_ADD_IMG', 'links/add.gif');
		$context['page_tools'][] = Skin::build_link('links/edit.php?anchor='.urlencode('article:'.$item['id']), LINKS_ADD_IMG.i18n::s('Add a link'), 'basic', i18n::s('Contribute to the web and link to relevant pages.'));
	}

	// post an image, if upload is allowed
	if(Images::allow_creation($anchor, $item)) {
		Skin::define_img('IMAGES_ADD_IMG', 'images/add.gif');
		$context['page_tools'][] = Skin::build_link('images/edit.php?anchor='.urlencode('article:'.$item['id']), IMAGES_ADD_IMG.i18n::s('Add an image'), 'basic', i18n::s('You can upload a camera shot, a drawing, or another image file.'));
	}

	// modify this page
	if(Articles::allow_modification($item, $anchor)) {
		Skin::define_img('ARTICLES_EDIT_IMG', 'articles/edit.gif');
		if(!is_object($overlay) || (!$label = $overlay->get_label('edit_command')))
			$label = i18n::s('Edit this page');
		$context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'edit'), ARTICLES_EDIT_IMG.$label, 'basic', i18n::s('Press [e] to edit'), FALSE, 'e');
	}

	// access previous versions, if any
	if($has_versions && Articles::is_owned($item, $anchor)) {
		Skin::define_img('ARTICLES_VERSIONS_IMG', 'articles/versions.gif');
		$context['page_tools'][] = Skin::build_link(Versions::get_url('article:'.$item['id'], 'list'), ARTICLES_VERSIONS_IMG.i18n::s('Versions'), 'basic', i18n::s('Restore a previous version if necessary'));
	}

	// publish this page
	if((!isset($item['publish_date']) || ($item['publish_date'] <= NULL_DATE)) && Articles::allow_publication($anchor, $item)) {
		Skin::define_img('ARTICLES_PUBLISH_IMG', 'articles/publish.gif');
		$context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'publish'), ARTICLES_PUBLISH_IMG.i18n::s('Publish'));
	}

	// review command provided to container owners
	if(is_object($anchor) && $anchor->is_owned()) {
		Skin::define_img('ARTICLES_STAMP_IMG', 'articles/stamp.gif');
		$context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'stamp'), ARTICLES_STAMP_IMG.i18n::s('Stamp'));
	}

	// lock command provided to container and page owners
	if(Articles::is_owned($item, $anchor)) {

		if(!isset($item['locked']) || ($item['locked'] == 'N')) {
			Skin::define_img('ARTICLES_LOCK_IMG', 'articles/lock.gif');
			$context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'lock'), ARTICLES_LOCK_IMG.i18n::s('Lock'));
		} else {
			Skin::define_img('ARTICLES_UNLOCK_IMG', 'articles/unlock.gif');
			$context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'lock'), ARTICLES_UNLOCK_IMG.i18n::s('Unlock'));
		}
	}

	// delete command
	if(Articles::allow_deletion($item, $anchor)) {
		Skin::define_img('ARTICLES_DELETE_IMG', 'articles/delete.gif');
		$context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'delete'), ARTICLES_DELETE_IMG.i18n::s('Delete this page'));
	}

	// duplicate command provided to container owners
	if(Articles::is_owned(NULL, $anchor)) {
		Skin::define_img('ARTICLES_DUPLICATE_IMG', 'articles/duplicate.gif');
		$context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'duplicate'), ARTICLES_DUPLICATE_IMG.i18n::s('Duplicate this page'));
	}

}

// use date of last modification into etag computation
if(isset($item['edit_date']))
	$context['etag'] = $item['edit_date'];

// render the skin
render_skin();

?>