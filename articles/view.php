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
 * Owner's avatar is displayed if the layout is a forum.
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
 * If the item is not public, a meta attribute is added to prevent search engines from presenting
 * cached versions of the page to end users.
 *
 * @link http://www.gsadeveloper.com/category/google-mini/page/2/
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

// wrapper tag you may use in template (html5, microdata...)
$context['content_wrap'] = 'article';

global $render_overlaid;
$whole_rendering = !$render_overlaid;
if($render_overlaid) 
    $context['text'] .= '<div class="hidden require-overlaid"></div>'."\n";

// view.php/12/nick name induces no particular processing

// sanity check
if(!is_int($zoom_index) || $zoom_index < 1)
	$zoom_index = 1;

// get the item from the database
$item = Articles::get($id);

// redirect to another article with a better suitable language, if any
if($item['nick_name']) {
    Anchors::check_better_lang('article:'.$item['id'], $item['nick_name']);
}

// get owner profile, if any
$owner = array();
if(isset($item['owner_id']))
	$owner = Users::get($item['owner_id']);

// get the related overlay, if any
$overlay = NULL;
if(isset($item['overlay']))
	$overlay = Overlay::load($item, 'article:'.$item['id']);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']))
	$anchor = Anchors::get($item['anchor']);

// current viewed article as object
$cur_article = new article();
$cur_article->item      = $item;
$cur_article->anchor    = $anchor;
$cur_article->overlay   = $overlay;

// get related behaviors, if any
$behaviors = NULL;
if(isset($item['id']))
	$behaviors = new Behaviors($item, $anchor);

// change default behavior
if(isset($item['id']) && is_object($behaviors) && !$behaviors->allow('articles/view.php', 'article:'.$item['id']))
	$permitted = FALSE;

// check access rights
elseif($cur_article->allows('access'))
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// owners can do what they want
if($cur_article->allows('modification'))
	Surfer::empower();

// readers have additional rights
elseif(Surfer::is_logged() && is_object($anchor) && $anchor->is_assigned())
	Surfer::empower('S');
elseif(isset($item['id']) && $cur_article->is_assigned() && Surfer::is_logged())
	Surfer::empower('S');

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

// current item
if(isset($item['id'])) {
	$context['current_item']    = 'article:'.$item['id'];
        $context['current_action']  = 'view';
}

// path to this page
if($whole_rendering)
    $context['path_bar'] = Surfer::get_path_bar($anchor);

// page title
if(($item['publish_date'] <= NULL_DATE) || ($item['publish_date'] > $context['now']))
	$context['page_title'] .= DRAFT_FLAG;
if(isset($item['active']) && ($item['active'] == 'R'))
	$context['page_title'] .= RESTRICTED_FLAG;
elseif(isset($item['active']) && ($item['active'] == 'N'))
	$context['page_title'] .= PRIVATE_FLAG;
if(is_object($overlay))
	$context['page_title'] .= $overlay->get_text('title', $item);
elseif(isset($item['title']))
	$context['page_title'] .= $item['title'];
if(isset($item['locked']) && ($item['locked'] == 'Y') && Articles::is_owned($item, $anchor))
	$context['page_title'] .= ' '.LOCKED_FLAG;

// page language, if any
if(isset($item['language']) && $item['language'] && ($item['language'] != 'none'))
	$context['page_language'] = $item['language'];

// page canonical link
$context['page_link'] = Articles::get_permalink($item);

// not found -- help web crawlers
if(!isset($item['id'])) {
	include '../error.php';

// permission denied
} elseif(!$permitted) {

	// make it clear to crawlers
	if(Surfer::is_crawler())
		Safe::header('Status: 401 Unauthorized', TRUE, 401);

	// anonymous users are invited to log in or to register
	elseif(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Articles::get_permalink($item)));

	// require access from some owner
	elseif(isset($_REQUEST['requested']) && ($requested = Users::get($_REQUEST['requested'])) && $requested['email']) {

		// prepare the mail message
		$to = Mailer::encode_recipient($requested['email'], $requested['full_name']);
		$subject = sprintf(i18n::c('%s: %s'), i18n::c('Request'), strip_tags($item['title']));
		$message = Articles::build_notification('apply', $item, $overlay);
		$headers = Mailer::set_thread('article:'.$item['id']);

		// allow for skinnable template
		$message = Skin::build_mail_message($message);

		// build multiple parts, for HTML rendering
		$message = Mailer::build_multipart($message);

		// send the message to requested user
		if(Mailer::post(Surfer::from(), $to, $subject, $message, NULL, $headers)) {
			$text = sprintf(i18n::s('Your request has been transmitted to %s. Check your mailbox for feed-back.'),
				Skin::build_link(Users::get_permalink($requested), Codes::beautify_title($requested['full_name']), 'user'));
			$context['text'] .= Skin::build_block($text, 'note');
		}

		// follow-up navigation
		$context['text'] .= '<div>'.i18n::s('Where do you want to go now?').'</div>';
		$menu = array();
		$menu[] = Skin::build_link($context['url_to_root'], i18n::s('Front page'), 'button');
		$menu[] = Skin::build_link(Surfer::get_permalink(), i18n::s('My profile'), 'span');
		$context['text'] .= Skin::finalize_list($menu, 'menu_bar');

	// offer to request some owner
	} else {

		// provide feed-back to surfer
		$context['text'] .= Skin::build_block(i18n::s('You are not allowed to access this page.'), 'caution');

		// list owners
		$owners = array();

		// owner of this section
		if(isset($item['owner_id']) && $item['owner_id'] && ($user = Users::get($item['owner_id'])) && $user['email'])
			$owners[] = $user['id'];

		// owners of parent containers
		$reference = $item['anchor'];
		while($reference) {
			if(!$parent = Anchors::get($reference))
				break;
			if(($owner_id = $parent->get_value('owner_id')) && ($user = Users::get($owner_id)) && $user['email'])
				$owners[] = $user['id'];
			$reference = $parent->get_value('anchor');
		}

		// suggest to query one of available owners
		if($owners) {
			$context['text'] .= '<div>'.i18n::ns('Following person is entitled to invite you to participate:', 'Following persons are entitled to invite you to participate:', count($owners)).'</div>';

			// the form
			$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><div>'
				.Users::list_for_ids($owners, 'request')
				.Skin::finalize_list(array(Skin::build_submit_button(i18n::s('Submit a request to get access'))), 'menu_bar')
				.'<input type="hidden" name="id" value="'.$item['id'].'">'
				.'</div></form>';
		}
	}

// re-enforce the canonical link
} elseif(!$zoom_type && ($page == 1) && $context['self_url'] && $whole_rendering && strncmp($context['self_url'], $context['page_link'], strlen($context['page_link']))) {
	Safe::header('Status: 301 Moved Permanently', TRUE, 301);
	Safe::header('Location: '.$context['page_link']);
	Logger::error(Skin::build_link($context['page_link']));

// display the article
} else {

	// behaviors can change page menu
	if(is_object($behaviors))
		$context['page_menu'] = $behaviors->add_commands('articles/view.php', 'article:'.$item['id'], $context['page_menu']);

	// remember surfer visit
	Surfer::is_visiting(Articles::get_permalink($item), Codes::beautify_title($item['title']), 'article:'.$item['id'], $item['active']);

	// increment silently the hits counter if not robot, nor associate, nor owner, nor at follow-up page
	if(Surfer::is_crawler() || Surfer::is_associate())
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
	if(Articles::has_option('with_neighbours', $anchor, $item) && is_object($anchor))
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
	if($whole_rendering) {
	    if(isset($item['meta']) && $item['meta'])
		    $context['page_header'] .= $item['meta'];

	    // prevent search engines to present cache versions of this page
	    if($item['active'] != 'Y')
		    $context['page_header'] .= "\n".'<meta name="robots" content="noarchive" />';

	    // add canonical link
	    if(!$zoom_type)
		    $context['page_header'] .= "\n".'<link rel="canonical" href="'.Articles::get_permalink($item).'" />';

	    // a meta link to prefetch the next page
	    if(isset($neighbours[2]) && $neighbours[2])
		    $context['page_header'] .= "\n".'<link rel="next" href="'.$context['url_to_home'].$context['url_to_root'].$neighbours[2].'" title="'.encode_field($neighbours[3]).'" />';

	    // a meta link to the section front page
	    if(is_object($anchor))
		    $context['page_header'] .= "\n".'<link rel="contents" href="'.$context['url_to_home'].$context['url_to_root'].$anchor->get_url().'" title="'.encode_field($anchor->get_title()).'" type="text/html" />';

	    // a meta link to a description page (actually, rdf)
	    $context['page_header'] .= "\n".'<link rel="meta" href="'.$context['url_to_home'].$context['url_to_root'].Articles::get_url($item['id'], 'describe').'" title="Meta Information" type="application/rdf+xml" />';

	    // implement the trackback interface
	    $permanent_link = Articles::get_permalink($item);
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
		if(is_object($overlay) && is_callable(array($overlay,'get_meta_introduction_text')) && ($more = $overlay->get_text('meta_introduction', $item))) {
			$order   = array("\r\n", "\n", "\r");
			$replace = ' ';
			$context['page_meta'] = str_replace($order, $replace,strip_tags(Codes::beautify_introduction($more)));
			}
	    elseif(isset($item['introduction']) && $item['introduction'])
		    $context['page_meta'] = strip_tags(Codes::beautify_introduction($item['introduction']));
	    if(isset($item['create_name']) && $item['create_name'])
		    $context['page_author'] = $item['create_name'];
	    if(isset($item['edit_date']) && $item['edit_date'])
		    $context['page_date'] = $item['edit_date'];
	    if(isset($item['publish_name']) && $item['publish_name'])
		    $context['page_publisher'] = $item['publish_name'];
	}
	//
	// set page details -- $context['page_details']
	//
	$text = '';

	// do not mention details at follow-up pages, nor to crawlers
	if(!$zoom_type && !Surfer::is_crawler()) {

		// tags, if any
		if(isset($item['tags']))
			$context['page_tags'] = Skin::build_tags($item['tags']);

		// one detail per line
		$text .= '<p '.tag::_class('details').'>';
		$details = array();

		// add details from the overlay, if any
		if(is_object($overlay) && ($more = $overlay->get_text('details', $item)))
			$details[] = $more;

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
			$details[] = RESTRICTED_FLAG.i18n::s('Community - Access is granted to any identified surfer');

		// restricted to associates
		elseif($item['active'] == 'N')
			$details[] = PRIVATE_FLAG.i18n::s('Private - Access is restricted to selected persons');

		// expired article
		if((Surfer::is_associate() || $cur_article->is_assigned())
				&& ($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now'])) {
			$details[] = EXPIRED_FLAG.' '.sprintf(i18n::s('Page has expired %s'), Skin::build_date($item['expiry_date']));
		}

		// provide more details to authenticated surfers
		if(Surfer::is_logged()) {

			// page owner
			if(isset($item['owner_id']) && ($owner = Users::get($item['owner_id'])))
				$details[] = sprintf(i18n::s('%s: %s'), i18n::s('Owner'), Users::get_link($owner['full_name'], $owner['email'], $owner['id']));

			// page editors
			if($items = Articles::list_editors_by_name($item, 0, 7, 'comma5'))
				$details[] = sprintf(i18n::s('%s: %s'), Skin::build_link(Users::get_url('article:'.$item['id'], 'select'), i18n::s('Editors')), $items);

			// page watchers
			if($items = Articles::list_watchers_by_posts($item, 0, 7, 'comma5'))
				$details[] = sprintf(i18n::s('%s: %s'), Skin::build_link(Users::get_url('article:'.$item['id'], 'watch'), i18n::s('Watchers')), $items);

		}

		// display details, if any
		if(count($details))
			$text .= ucfirst(implode(BR."\n", $details)).BR."\n";

		// other details
		$details = Articles::build_dates($anchor, $item);

		// signal articles to be published
		if(($item['publish_date'] <= NULL_DATE)) {
			if($cur_article->allows('publication'))
				$label = Skin::build_link(Articles::get_url($item['id'], 'publish'), i18n::s('not published'));
			else
				$label = i18n::s('not published');
			$details[] = DRAFT_FLAG.' '.$label;
		}

		// the number of hits
		if(($item['hits'] > 1) && ($cur_article->is_owned()
			|| ((!isset($context['content_without_details']) || ($context['content_without_details'] != 'Y')) || $cur_article->has_option('with_details')) ) ) {

			// flag popular pages
			$popular = '';
			if($item['hits'] > 100)
				$popular = POPULAR_FLAG;

			// show the number
			if($cur_article->is_owned() || ($item['hits'] < 100))
				$details[] = $popular.Skin::build_number($item['hits'], i18n::s('hits'));

			// other surfers will benefit from a stable ETag
			elseif($popular)
				$details[] = $popular;
		}

		// rank for this article
		if((intval($item['rank']) != 10000) && $cur_article->is_owned())
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
				$text .= BR.sprintf(i18n::s('Name: %s'), $link);

			// short link
			if($context['with_friendly_urls'] == 'R')
				$text .= BR.sprintf(i18n::s('Shortcut: %s'), $context['url_to_home'].$context['url_to_root'].Articles::get_short_url($item));
		}

		// no more details
		$text .= "</p>\n";

		// update page details
		$context['page_details'] .= $text;

	}

	//
	// generic page components --can be overwritten in view_as_XXX.php if necessary
	//
	if($whole_rendering) {

	    // the owner profile, if any, aside
	    if(isset($owner['id']) && is_object($anchor))
		    $context['components']['profile'] = $anchor->get_user_profile($owner, 'extra', Skin::build_date($item['create_date']));

	    // add extra information from the overlay, if any
	    if(is_object($overlay))
		    $context['components']['overlay'] = $overlay->get_text('extra', $item);

	    // add extra information from this item, if any
	    if(isset($item['extra']) && $item['extra'])
		    $context['components']['boxes'] = Codes::beautify_extra($item['extra']);

	    // 'Share' box
	    //
	    $lines = array();

	    // facebook, twitter, linkedin
	    if(($item['active'] == 'Y') && ((!isset($context['without_internet_visibility']) || ($context['without_internet_visibility'] != 'Y')))) {

		    // the best suited link to use
		    if($context['with_friendly_urls'] == 'R')
			    $url = $context['url_to_home'].$context['url_to_root'].Articles::get_short_url($item);
		    else
			    $url = Articles::get_permalink($item);

		    // facebook
		    Skin::define_img('PAGERS_FACEBOOK_IMG', 'pagers/facebook.gif');
		    $lines[] = Skin::build_link('http://www.facebook.com/share.php?u='.urlencode($url).'&t='.urlencode($item['title']), PAGERS_FACEBOOK_IMG.i18n::s('Post to Facebook'), 'basic', i18n::s('Spread the word'));

		    // twitter
		    Skin::define_img('PAGERS_TWITTER_IMG', 'pagers/twitter.gif');
		    $lines[] = Skin::build_link('http://twitter.com/home?status='.urlencode($item['title'].' '.$url), PAGERS_TWITTER_IMG.i18n::s('Tweet about this'), 'basic', i18n::s('Spread the word'));

		    // linked in
		    Skin::define_img('PAGERS_LINKEDIN_IMG', 'pagers/linkedin.gif');
		    $lines[] = Skin::build_link('http://www.linkedin.com/shareArticle?mini=true&url='.$url.'&title='.urlencode($item['title']).'&summary='.urlencode($item['introduction']).'&source='.urlencode($anchor->get_title()), PAGERS_LINKEDIN_IMG.i18n::s('Share at LinkedIn'), 'basic', i18n::s('Spread the word'));

	    }

	    // invite participants
	    if(($cur_article->is_owned() || ($item['active'] == 'Y')) && isset($context['with_email']) && ($context['with_email'] == 'Y')) {
		    $lines[] = Skin::build_link(Articles::get_url($item['id'], 'invite'), fa::_("user-plus").' '.i18n::s('Invite participants'), 'basic', i18n::s('Spread the word'));
	    }

	    // notify participants
	    if(($cur_article->is_owned() || Surfer::is_associate()) && isset($context['with_email']) && ($context['with_email'] == 'Y')) {
		    $lines[] = Skin::build_link(Articles::get_url($item['id'], 'mail'), fa::_("envelope").' '.i18n::s('Notify participants'));
	    }

	    // manage editors
	    if($cur_article->is_owned() || Surfer::is_associate()) {
		    $lines[] = Skin::build_link(Users::get_url('article:'.$item['id'], 'select'), fa::_("users").' '.i18n::s('Manage participants'));
	    }

	    // the command to track back
	    if(Links::allow_trackback()) {
		    $lines[] = Skin::build_link('links/trackback.php?anchor='.urlencode('article:'.$item['id']), fa::_("share-alt").' '.i18n::s('Reference this page'), 'basic', i18n::s('Various means to link to this page'));
	    }

	    // more tools
	    if(((isset($context['with_export_tools']) && ($context['with_export_tools'] == 'Y'))
		    || (is_object($anchor) && $anchor->has_option('with_export_tools')))) {

		    // check tools visibility
		    if(Surfer::is_member() || (isset($context['with_anonymous_export_tools']) && ($context['with_anonymous_export_tools'] == 'Y'))) {

			    // get a PDF version
			    $lines[] = Skin::build_link(Articles::get_url($item['id'], 'fetch_as_pdf'), fa::_("file-pdf").' '.i18n::s('Save as PDF'), 'basic', i18n::s('Save as PDF'));

			    // open in Word
			    $lines[] = Skin::build_link(Articles::get_url($item['id'], 'fetch_as_msword'), fa::_("file-word").' '.i18n::s('Copy in MS-Word'), 'basic', i18n::s('Copy in MS-Word'));

		    }
	    }

	    // print this page
	    if(Surfer::is_logged() || (isset($context['with_anonymous_export_tools']) && ($context['with_anonymous_export_tools'] == 'Y'))) {
		    $lines[] = Skin::build_link(Articles::get_url($item['id'], 'print'), fa::_("print").' '.i18n::s('Print this page'), 'basic', i18n::s('Get a paper copy of this page.'));
	    }

	    // in a side box
	    if(count($lines))
		    $context['components']['share'] = Skin::build_box(i18n::s('Share'), Skin::finalize_list($lines, 'newlines'), 'share', 'share');

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

		    $lines[] = Skin::build_link($link, fa::_("eye").' '.$label, 'basic', i18n::s('Manage your watch list'));

	    }

	    // allow to leave the page
	    if($cur_article->is_assigned() && !$cur_article->is_owned(null, false)) {
		    $lines[] = Skin::build_link(Users::get_url('article:'.$item['id'], 'leave'), fa::_("share-alt").' '.i18n::s('Leave this page'));
	    }

	    // get news from rss
	    if(isset($item['id']) && (!isset($context['skins_general_without_feed']) || ($context['skins_general_without_feed'] != 'Y')) ) {

		    // list of attached files
		    $lines[] = Skin::build_link($context['url_to_home'].$context['url_to_root'].Files::get_url('article:'.$item['id'], 'feed'), i18n::s('Recent files'), 'xml');

		    // comments are allowed
		    if($cur_article->allows('creation','comment')) {
			    $lines[] = Skin::build_link($context['url_to_home'].$context['url_to_root'].Comments::get_url('article:'.$item['id'], 'feed'), i18n::s('Recent comments'), 'xml');
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
		    $items = Articles::list_for_name($item['nick_name'], $item['id'], 'compact');

		    // actually render the html for the section
		    if(is_array($items))
			    $box['text'] .= Skin::build_list($items, 'compact');
		    if($box['text'])
			    $context['components']['twins'] = Skin::build_box(i18n::s('Related'), $box['text'], 'twins', 'twins');

	    }

	    // the contextual menu, in a navigation box, if this has not been disabled
	    if( !$cur_article->has_option('no_contextual_menu')
		    && isset($context['current_focus']) && ($menu = Skin::build_contextual_menu($context['current_focus']))) {

		    // use title from topmost level
		    if(count($context['current_focus']) && ($top_anchor = Anchors::get($context['current_focus'][0]))) {
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
		    $items = Members::list_categories_by_title_for_member('article:'.$item['id'], $offset, CATEGORIES_PER_PAGE, 'sidebar');

		    // the command to change categories assignments
		    if(Categories::allow_assign($item,$anchor))
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
	    $context['components']['referrals'] = Skin::build_referrals(Articles::get_permalink($item));
	}

	//
	// the main part of the page
	//
	$context['page_description'] = '';

	// filter description, if necessary
	if(is_object($overlay))
		$description = $overlay->get_text('description', $item);
	else
		$description = $item['description'];

	// the beautified description, which is the actual page body
	if($description) {

		// use adequate label
		if(is_object($overlay) && ($label = $overlay->get_label('description')))
			$context['page_description'] .= Skin::build_block($label, 'title');

		// provide only the requested page
		$pages = preg_split('/\s*\[page\]\s*/is', $description);
		$page = max(min($page, count($pages)), 1);
		$description = $pages[ $page-1 ];

		// several pages to manage
		if(count($pages) > 1) {

			$data = Skin::pager(Articles::get_permalink($item), Articles::get_url($item['id'], 'navigate', 'page'), $page, count($pages));

			$neighbours = Skin::neighbours($data, 'slideshow');

			// displayed at the top if not on first page
			if($page > 1)
				$context['page_description'] .= $neighbours;

			// remove toc and toq codes
			$description = preg_replace('/\s*\[(toc|toq)\]\s*/is', '', $description);

		}

		// beautify the target page
		$context['page_description'] .= Skin::build_block($description, 'description', '', $item['options']);

		// if there are several pages, add navigation commands to browse them
		if(count($pages) > 1)
			$context['page_description'] .= $neighbours;

	}

	//
	// use a specific script to render the page in replacement of the standard one
	//

	// the overlay may generate some tabs
	$context['tabs'] = array();
	if(is_object($overlay) && ($tabs = $overlay->get_tabs('view', $item)))
		$context['tabs'] = $tabs;

	// branch to another script
	if(isset($item['options']) && preg_match('/\bview_as_[a-zA-Z0-9_\.]+?\b/i', $item['options'], $matches) && is_readable($matches[0].'.php')) {
		include $matches[0].'.php';
		return;
	} elseif(is_object($anchor) && ($viewer = $anchor->has_option('view_as')) && is_readable('view_as_'.$viewer.'.php')) {
		$name = 'view_as_'.$viewer.'.php';
		include $name;
		return;
	}

	//
	// load main page components - display will be done by canvas features
	//
	$canvas = array(
		'anchor_prefix'		    => '',
		'neighbours'		    => '',
		'owner_profile_prefix'	    => '',
		'owner_profile_suffix'	    => '',
		'introduction'		    => '',
		'overlay_text'		    => '',
		'description'		    => '',
		'comments'		    => '',
		'comments_count'	    => 0,
		'files'			    => '',
		'files_count'		    => 0,
		'links'			    => '',
		'links_count'		    => 0,
		'trailer'		    => '',
		'rating'		    => ''
	);

	// anchor prefix
	if(is_object($anchor))
		$canvas['anchor_prefix'] = $anchor->get_prefix();

	// neighbours
	if($neighbours)
		$canvas['neighbours'] = $neighbours;

	// the owner profile, if any
	if(isset($owner['id']) && is_object($anchor)) {
		$canvas['owner_profile_prefix'] = $anchor->get_user_profile($owner, 'prefix', Skin::build_date($item['create_date']));
		$canvas['owner_profile_suffix'] = $anchor->get_user_profile($owner, 'suffix', Skin::build_date($item['create_date']));
	}

	// article rating, if the anchor allows for it, and if no rating has already been registered
	if(!$cur_article->has_option('without_rating') && $cur_article->has_option('rate_as_digg')) {

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
			$digg = '<div class="rate">'.Skin::build_link(Articles::get_url($item['id'], 'like'), i18n::s('Rate it'), 'basic').'</div>';

		// rendering
		$context['rating'] = '<div class="digg"><div class="votes">'.$rating_label.'</div>'
			.$digg
			.'</div>';

		// signal DIGG
		define('DIGG', TRUE);
	}

	// the introduction text, if any
	if(is_object($overlay))
		$canvas['introduction'] = Skin::build_block($overlay->get_text('introduction', $item), 'introduction');
	else
		$canvas['introduction'] = Skin::build_block($item['introduction'], 'introduction');

	// get text related to the overlay, if any
	if(is_object($overlay))
		$canvas['overlay_text'] = $overlay->get_text('view', $item);

	// the main part of the page
	$canvas['description'] = $context['page_description'];

	//
	// comments attached to this article
	//
	$discussion = '';

	// the list of related comments, if not at another follow-up page
	if(!$render_overlaid && !$zoom_type || ($zoom_type == 'comments')) {

		// title label
		$title_label = '';
		if(is_object($overlay)) {
			$list_title = $overlay->get_label('list_title', 'comments');
                        if($list_title)
                            $title_label = ucfirst($title_label);
                } 
                if(!$title_label)
			$title_label = i18n::s('Comments');

		// label to create a comment
		$add_label = '';
		if(is_object($overlay))
			$add_label = $overlay->get_label('new_command', 'comments');
		if(!$add_label)
			$add_label = i18n::s('Post a comment');

		// get a layout for comments of this item
		$layout = Comments::get_layout($anchor, $item);

		// provide author information to layout
		if(is_object($layout) && isset($item['create_id']) && $item['create_id'])
			$layout->set_focus('user:'.$item['create_id']);

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

		// a navigation bar for these comments
		if($count = Comments::count_for_anchor('article:'.$item['id'])) {
			if($count > 20)
				$box['bar'] += array('_count' => sprintf(i18n::ns('%d comment', '%d comments', $count), $count));

			// list comments by date
			$items = Comments::list_by_date_for_anchor('article:'.$item['id'], $offset, $items_per_page, $layout, TRUE);

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
                
                // feed the wall
                if($cur_article->allows('creation','comment'))
                    $box['text'] = Comments::get_form('article:'.$item['id'], 'comments', ucfirst(i18n::s('comment')), false) . $box['text'];

		// show commands
		if(count($box['bar'])) {

			// commands before the box
			$box['text'] = Skin::build_list($box['prefix_bar'], 'menu_bar').$box['text'];

			// append the menu bar at the end
			$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');

		}

		// put the discussion in a separate panel
		if(trim($box['text'])) {
			$canvas['comments'] = $box['text'];
			if($count)
				$canvas['comments_count'] = $count;
		}

	}

	//
	// files attached to this article
	//
	$attachments = '';
	$attachments_count = 0;

	// the list of related files if not at another follow-up page
	if(!$zoom_type || ($zoom_type == 'files')) {

		// list files only to people able to change the page
		if($cur_article->allows('modification'))
			$embedded = NULL;
		else
			$embedded = Codes::list_embedded($item['description']);

		// build a complete box
		$box = array('bar' => array(), 'text' => '');

		// count the number of files in this article
		if($count = Files::count_for_anchor('article:'.$item['id'], FALSE, $embedded)) {
			$attachments_count += $count;
			if($count > 20)
				$box['bar'] += array('_count' => sprintf(i18n::ns('%d file', '%d files', $count), $count));

			// compact list of files
			if($compact = Articles::has_option('files_as_compact', $anchor, $item)) {				
				$layout = Layouts::new_('compact', 'file');
				$layout->set_focus('article:'.$item['id']);

			// standard list of files
			} else
				$layout = 'article:'.$item['id'];


			// list files by date (default) or by title (option files_by_title)
			$offset = ($zoom_index - 1) * FILES_PER_PAGE;
			if($cur_article->has_option('files_by') == 'title')
				$items = Files::list_by_title_for_anchor('article:'.$item['id'], 0, 300, $layout, $embedded);
			else
				$items = Files::list_by_date_for_anchor('article:'.$item['id'], 0, 300, $layout, $embedded);

			// actually render the html
			if(is_array($items))
				$box['text'] .= Skin::build_list($items, $compact?'compact':'decorated');
			elseif(is_string($items))
				$box['text'] .= $items;

			// the command to post a new file
			if(!$compact && $cur_article->allows('creation','file')) {
				$box['bar'] += array('files/edit.php?anchor='.urlencode('article:'.$item['id']) => fa::_("upload").' '.i18n::s('Add a file'));
			}

		}

		// some files have been attached to this page
		if(($page == 1) && ($count > 3)) {

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
			$canvas['files'] = Skin::build_content('files', i18n::s('Files'), $box['text'], $box['bar']);
		$canvas['files_count'] = $count;
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
			$attachments_count += $count;
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
			if($cur_article->allows('creation','link')) {
				$box['bar'] += array( 'links/edit.php?anchor='.urlencode('article:'.$item['id']) => fa::_("chain").' '.i18n::s('Add a link') );
			}
		}

		// there is some box content
		if($box['text'])
			$canvas['links'] = Skin::build_content('links', i18n::s('Links'), $box['text'], $box['bar']);
		$canvas['links_count'] = $count;

	}

	//
	// trailer information
	//

	// add trailer information from the overlay, if any
	if(is_object($overlay))
		$canvas['trailer'] .= $overlay->get_text('trailer', $item);

	// add trailer information from this item, if any
	if(isset($item['trailer']) && trim($item['trailer']))
		$canvas['trailer'] .= Codes::beautify($item['trailer']);

	// buttons to display previous and next pages, if any
	if($neighbours)
		$canvas['trailer'] .= Skin::neighbours($neighbours, 'manual');

	// insert anchor suffix
	if(is_object($anchor))
		$canvas['trailer'] .= $anchor->get_suffix();

	// reflect content canvas from anchor
	if(!isset($item['canvas']) && is_object($anchor)) {
		$item['canvas'] = $anchor->get_articles_canvas();
	}

	// reflect content canvas from anchor
	if(empty($item['canvas']))
		$item['canvas'] = 'standard';
	elseif (!Safe::file('../canvas/'.$item['canvas'].'.php'))
		$item['canvas'] = 'standard';

	// get canvas result
	$text = '';
	require_once '../canvas/'.$item['canvas'].'.php';

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
	if($whole_rendering) {
	    // modify this page
	    if($cur_article->allows('modification')) {
		    if(!is_object($overlay) || (!$label = $overlay->get_label('edit_command', 'articles')))
			    $label = i18n::s('Edit this page');
		    $context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'edit'), fa::_("edit").' '.$label, 'edit', i18n::s('Press [e] to edit'), FALSE, 'e');
		    $context['page_minitools'][] = Skin::build_link(Articles::get_url($item['id'], 'edit'), fa::_("edit"), 'edit', i18n::s('Press [e] to edit'), FALSE, 'e');
	    }

	    // post an image, if upload is allowed
	    if($cur_article->allows('creation','image')) {
		    $context['page_tools'][] = Skin::build_link('images/edit.php?anchor='.urlencode('article:'.$item['id']), fa::_("image").' '.i18n::s('Add an image'), 'basic', i18n::s('You can upload a camera shot, a drawing, or another image file.'));
		    $context['page_minitools'][] = Skin::build_link('images/edit.php?anchor='.urlencode('article:'.$item['id']), fa::_("image"), 'basic', i18n::s('You can upload a camera shot, a drawing, or another image file.'));
	    }

	    // attach a file, if upload is allowed
	    if($cur_article->allows('creation','file')) {
		    $context['page_tools'][] = Skin::build_link('files/edit.php?anchor='.urlencode('article:'.$item['id']),  fa::_("file-alt").' '.i18n::s('Add a file'), 'basic', i18n::s('Attach related files.'));
	    }

	    // add a link
	    if($cur_article->allows('creation','link')) {
		    $context['page_tools'][] = Skin::build_link('links/edit.php?anchor='.urlencode('article:'.$item['id']), fa::_("link").' '.i18n::s('Add a link'), 'basic', i18n::s('Contribute to the web and link to relevant pages.'));
	    }

	    // comment this page if anchor does not prevent it --anonymous surfers will have it in main area
	    if($cur_article->allows('creation','comment') && Surfer::get_id()) {
		    $context['page_tools'][] = Skin::build_link(Comments::get_url('article:'.$item['id'], 'comment'), fa::_("comment-dots").' '.i18n::s('Post a comment'), 'basic', i18n::s('Express yourself, and say what you think.'));
	    }


	    // access previous versions, if any
	    if($has_versions && $cur_article->is_owned()) {
		    $context['page_tools'][] = Skin::build_link(Versions::get_url('article:'.$item['id'], 'list'), fa::_("magic").' '.i18n::s('Versions'), 'basic', i18n::s('Restore a previous version if necessary'));
	    }

	    // publish this page
        if($cur_article->allows('publication')) {
             if(!isset($item['publish_date']) || ($item['publish_date'] <= NULL_DATE)) {
                     $context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'publish'), fa::_("calendar-check").' '.i18n::s('Publish'));
                     $context['page_minitools'][] = Skin::build_link(Articles::get_url($item['id'], 'publish'), fa::_("calendar-check"));
             }
         }

	    // review various dates
	    if($cur_article->allows('publication')) {
		    $context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'stamp'), fa::_("clock").' '.i18n::s('Stamp'));
	    }

	    // lock command provided to container and page owners
	    if($cur_article->allows('locking')) {

		    if(!isset($item['locked']) || ($item['locked'] == 'N')) {
			    $context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'lock'), fa::_("lock").' '.i18n::s('Lock'));
		    } else {
			    $context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'lock'), fa::_("unlock").' '.i18n::s('Unlock'));
		    }
	    }

	    // delete command
	    if($cur_article->allows('deletion')) {
		    if(!is_object($overlay) || (!$label = $overlay->get_label('delete_command', 'articles')))
			    $label = i18n::s('Delete this page');
		    $context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'delete'), fa::_("trash").' '.$label);
	    }

	    // duplicate command provided to container owners
	    if($cur_article->allows('duplicate')) {
		    if(!is_object($overlay) || (!$label = $overlay->get_label('duplicate_command', 'articles')))
			    $label = i18n::s('Duplicate this page');
		    $context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'duplicate'), fa::_("copy").' '.$label);
	    }
	}

}

// render the skin
render_skin();