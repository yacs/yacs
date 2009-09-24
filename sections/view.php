<?php
/**
 * view one section
 *
 * @todo index presentation of articles (agnes)
 *
 * The main panel has following elements:
 * - top icons, if any --set in sub-section
 * - the section itself, with details, introduction, and main text.
 * - gadget boxes, if any --set in sub-sections
 * - list of sub-sections.
 * - list of related articles (from this section, or from sub-sections)
 * - list of files, if option 'with_files'
 * - list of comments, if option 'with_comments'
 * - list of related links
 * - list of inactive sub-section, for associates
 * - bottom icons, if any --set in sub-section
 *
 * The extra panel has following elements:
 * - A navigation box for flashy news (#news, #scrolling_news, or #rotating_news)
 * - A contextual menu to switch to other sections in the neighbour
 * - twin pages, if any
 * - Up to 6 articles in extra boxes --set in sub-section
 * - Extra boxes listing articles of some sub-sections
 * - categories attached to this section
 * - Links to rss feeds related to this section
 * - Bookmarklet to post to this section
 * - Related feeding servers, if any
 * - Means to reference this page, into a sidebar box
 * - The top popular referrals, if any
 *
 * Several HTTP headers, or &lt;meta&gt; attributes of the displayed page, are set dynamically here
 * to help advanced web usage. This includes:
 * - a link to a RDF description of this page (e.g., '&lt;link rel="meta" href="http://127.0.0.1/yacs/sections/describe.php/4310" title="rdf" type="application/rdf+xml" /&gt;')
 * - a link to a RSS feed for this section (e.g., '&lt;link rel="alternate" href="http://127.0.0.1/yacs/sections/feed.php/4038" title="RSS" type="application/rss+xml" /&gt;')
 * - a rdf section implementing the [link=trackback]http://www.movabletype.org/docs/mttrackback.html[/link] interface
 * - a [link=pingback]http://www.hixie.ch/specs/pingback/pingback[/link] link (e.g., '&lt;link rel="pingback" href="http://here/yacs/services/pingback.php" /&gt;')
 *
 * @link http://www.movabletype.org/docs/mttrackback.html TrackBack Technical Specification
 * @link http://www.hixie.ch/specs/pingback/pingback Pingback specification
 *
 * Meta information also includes:
 * - page description, which is a copy of the introduction, if any, or the default general description parameter
 * - page author, who is the original creator
 *
 * How to customize this page?
 *
 * Well, there is so much you can do:
 * - use a special bullet for a given section by posting the adequate image to this section
 * - edit the section to use a skin variant, or another skin - see [script]sections/edit.php[/script]
 * - create a new layout for articles, to change the rendering of listed pages - see the table below
 *
 * You can select among following layouts for sub-sections:
 * [table]
 * Sections layout|Script loaded
 * [body]
 * 'compact'|[script]sections/layout_sections_as_compact.php[/script]
 * 'decorated'|[script]sections/layout_sections.php[/script]
 * 'folded'|[script]sections/layout_sections_as_folded.php[/script]
 * 'freemind'|[script]shared/codes.php[/script]
 * 'inline'|[script]sections/layout_sections_as_inline.php[/script]
 * 'jive'|[script]sections/layout_sections_as_jive.php[/script]
 * 'map' (also default value)|[script]sections/layout_sections_as_yahoo.php[/script]
 * 'none'|All sections are shown only to associates an editors, as a compact set of special sections
 * 'yabb'|[script]sections/layout_sections_as_yabb.php[/script]
 * custom|[script]sections/layout_sections_as_custom.php[/script] (to load a customized layout)
 * [/table]
 *
 * To create a custom layout for sections, create a script that implement the Layout interface
 * (look into [script]shared/layout.php[/script]) and save it into the directory ##sections##
 * with the name prefix ##layout_sections_as_##. Then edit the section to manually configure the layout.
 *
 * For example, for the custom layout ##foo## for sub-sections,
 * YACS will attempt to load the script ##sections/layout_sections_as_foo.php##.
 * Edit the section to manually configure the layout ##foo## for sub-sections.
 *
 * You can select among following layouts for articles:
 * [table]
 * Articles layout|Script loaded
 * [body]
 * 'alistapart'|[script]articles/layout_articles_as_alistapart.php[/script]
 * 'boxesandarrows'|[script]articles/layout_articles_as_boxesandarrows.php[/script]
 * 'compact'|[script]articles/layout_articles_as_compact.php[/script]
 * 'daily'|[script]articles/layout_articles_as_daily.php[/script]
 * 'decorated' (also default value)|[script]articles/layout_articles.php[/script]
 * 'jive'|[script]articles/layout_articles_as_jive.php[/script]
 * 'manual'|[script]articles/layout_articles_as_manual.php[/script]
 * 'map'|[script]articles/layout_articles_as_yahoo.php[/script]
 * 'none'|No articles are shown
 * 'table'|[script]articles/layout_articles_as_table.php[/script]
 * 'wiki'|[script]articles/layout_articles.php[/script]
 * 'yabb'|[script]articles/layout_articles_as_yabb.php[/script]
 * custom|[script]articles/layout_articles_as_custom.php[/script] (to load a customized layout)
 * [/table]
 *
 * For example, for the custom layout ##bar## for articles,
 * YACS will attempt to load the script ##articles/layout_articles_as_bar.php##.
 * Edit the section to manually configure the layout ##bar## for content.
 *
 * @link http://www.boxesandarrows.com/ Boxes and Arrows
 * @link http://www.jivesoftware.com/products/forums/  Jive Forums
 * @link http://www.php.net/manual/en/index.php PHP Manual
 * @link http://www.yabbforum.com/ Yet Another Bulletin Board
 *
 * The caching strategy for section rendering is aiming to save on database
 * requests. Since this script udates $context['page_details'], $context['text'],
 * and $context['extra'], each of these is cached separately.
 * The caching topic is the reference of this section (e.g;, 'section:678').
 * Cache entries are purged directly either when the page is modified, or when
 * some object attached to it triggers the Section::touch() function.
 *
 * Restrictions apply on this page:
 * - associates and editors are allowed to move forward
 * - permission is denied if the anchor is not viewable
 * - access is restricted ('active' field == 'R'), but the surfer is an authenticated member
 * - public access is allowed ('active' field == 'Y')
 * - permission denied is the default
 *
 * Accept following invocations:
 * - view.php/12 (view the first page of the section document)
 * - view.php?id=12 (view the first page of the section document)
 * - view.php/12/sections/1 (view the page 1 of the list of related sections)
 * - view.php?id=12&sections=1 (view the page 1 of the list of related sections)
 * - view.php/12/articles/3 (view the page 3 of the list of related articles)
 * - view.php?id=12&articles=3 (view the page 3 of the list of related articles)
 * - view.php/12/comments/1 (view the page 1 of the list of related comments)
 * - view.php?id=12&comments=1 (view the page 1 of the list of related comments)
 * - view.php/12/files/2 (view the page 2 of the list of related files)
 * - view.php?id=12&files=2 (view the page 2 of the list of related files)
 * - view.php/12/links/1 (view the page 1 of the list of related links)
 * - view.php?id=12&links=1 (view the page 1 of the list of related links)
 *
 * If this section, or one of its anchor, specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Fw_crocodile
 * @tester Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @tester Elrik
 * @tester Viviane Zaniroli
 * @tester Fernand Le Chien
 * @tester Lucrecius
 * @tester Agnes
 * @tester Guillaume Perez
 * @tester Olivier
 * @tester Cloubech
 * @tester Le_ffrench
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../behaviors/behaviors.php';
include_once '../categories/categories.php';	// tags and categories
include_once '../comments/comments.php';		// attached comments and notes
include_once '../files/files.php';				// attached files
include_once '../images/images.php';			// attached images
include_once '../links/links.php';				// related pages
include_once '../overlays/overlay.php';
include_once '../servers/servers.php';
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
$page = max(1,intval($page));

// stop hackers
if($page > 10)
	$page = 10;

// no follow-up page yet
$zoom_type = '';
$zoom_index = 1;

// view.php?id=12&sections=2
if(isset($_REQUEST['sections']) && ($zoom_index = $_REQUEST['sections']))
	$zoom_type = 'sections';

// view.php?id=12&articles=2
elseif(isset($_REQUEST['articles']) && ($zoom_index = $_REQUEST['articles']))
	$zoom_type = 'articles';

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

// sanity check
if($zoom_index < 1)
	$zoom_index = 1;

// get the item from the database
$item =& Sections::get($id);

// get the related overlay, if any
$overlay = NULL;
if(isset($item['overlay']))
	$overlay = Overlay::load($item);

// get the overlay for content of this section, if any
$content_overlay = NULL;
if(isset($item['content_overlay']))
	$content_overlay = Overlay::bind($item['content_overlay']);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor =& Anchors::get($item['anchor']);

// get related behaviors, if any
$behaviors = NULL;
if(isset($item['id']))
	$behaviors = new Behaviors($item, $anchor);

// editors can do what they want on items anchored here
if((isset($item['id']) && Sections::is_assigned($item['id']) && Surfer::is_member()) || (is_object($anchor) && $anchor->is_assigned()))
	Surfer::empower();

// readers have additional rights
elseif((isset($item['id']) && Sections::is_assigned($item['id']) && Surfer::is_logged()) || (is_object($anchor) && $anchor->is_assigned()))
	Surfer::empower('S');

//
// is this surfer allowed to browse the page?
//

// page is not defined
if(!isset($item['id']))
	$permitted = FALSE;

// associates, editors and readers can read this page
elseif(Surfer::is_empowered('S'))
	$permitted = TRUE;

// change default behavior
elseif(is_object($behaviors) && !$behaviors->allow('sections/view.php', 'section:'.$item['id']))
	$permitted = FALSE;

// the anchor has to be viewable by this surfer
elseif(is_object($anchor) && !$anchor->is_viewable())
	$permitted = FALSE;

// access is restricted to authenticated surfer
elseif(($item['active'] == 'R') && Surfer::is_logged())
	$permitted = TRUE;

// public access is allowed
elseif($item['active'] == 'Y')
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// is the page on user watch list?
$in_watch_list = FALSE;
if(Surfer::is_logged() && isset($item['id']))
	$in_watch_list = Members::check('section:'.$item['id'], 'user:'.Surfer::get_id());

// has this page some versions?
$has_versions = FALSE;
if(isset($item['id']) && !$zoom_type && Surfer::is_empowered() && Surfer::is_logged() && Versions::count_for_anchor('section:'.$item['id']))
	$has_versions = TRUE;

// has this page some content to manage?
if(!isset($item['id']))
	$has_content = FALSE;
elseif(Articles::count_for_anchor('section:'.$item['id']))
	$has_content = TRUE;
elseif(Sections::count_for_anchor('section:'.$item['id']))
	$has_content = TRUE;
else
	$has_content = FALSE;

// load the skin, maybe with a variant
load_skin('sections', $anchor, isset($item['options']) ? $item['options'] : '');

// clear the tab we are in
$context['current_focus'] = array();
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();
if(isset($item['id']))
	$context['current_focus'][] = 'section:'.$item['id'];

// path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();

// page title
if(isset($item['index_title']) && $item['index_title']) {
	if(is_object($overlay))
		$context['page_title'] = $overlay->get_text('title', $item);
	elseif(isset($item['index_title']) && $item['index_title'])
		$context['page_title'] = $item['index_title'];
} elseif(isset($item['title']) && $item['title']) {
	if(is_object($overlay))
		$context['page_title'] = $overlay->get_text('title', $item);
	elseif(isset($item['title']) && $item['title'])
		$context['page_title'] = $item['title'];
}

// insert page family, if any
if(isset($item['family']) && $item['family'])
	$context['page_title'] = FAMILY_PREFIX.'<span id="family">'.$item['family'].'</span> '.FAMILY_SUFFIX.$context['page_title']."\n";

// page language, if any
if(isset($item['language']) && $item['language'] && ($item['language'] != 'none'))
	$context['page_language'] = $item['language'];

// not found -- help web crawlers
if(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No item has the provided id.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Sections::get_permalink($item)));

	// permission denied to authenticated user
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// display the section
} else {

	// behaviors can change page menu
	if(is_object($behaviors))
		$context['page_menu'] =& $behaviors->add_commands('sections/view.php', 'section:'.$item['id'], $context['page_menu']);

	// remember surfer visit
	Surfer::is_visiting(Sections::get_permalink($item), Codes::beautify_title($item['title']), 'section:'.$item['id'], $item['active']);

	// increment silently the hits counter if not associate, nor creator -- editors are taken into account
	if(Surfer::is_associate())
		;
	elseif(isset($item['owner_id']) && Surfer::is($item['owner_id']))
		;
	elseif(!$zoom_type) {
		$item['hits'] = isset($item['hits'])?($item['hits']+1):1;
		Sections::increment_hits($item['id']);
	}

	// initialize the rendering engine
	Codes::initialize(Sections::get_permalink($item));

	//
	// set page image -- $context['page_image']
	//

	// the section or the anchor icon, if any
	if(isset($item['icon_url']) && $item['icon_url'])
		$context['page_image'] = $item['icon_url'];
	elseif(is_object($anchor))
		$context['page_image'] = $anchor->get_icon_url();

	//
	// set page meta_information -- $context['page_header'], etc.
	//

	// add meta information, if any
	if(isset($item['meta']) && $item['meta'])
		$context['page_header'] .= $item['meta'];

	// a meta link to a feeding page
	$context['page_header'] .= "\n".'<link rel="alternate" href="'.$context['url_to_home'].$context['url_to_root'].Sections::get_url($item['id'], 'feed').'" title="RSS" type="application/rss+xml" />';

	// a meta link to a description page (actually, rdf)
	$context['page_header'] .= "\n".'<link rel="meta" href="'.$context['url_to_home'].$context['url_to_root'].Sections::get_url($item['id'], 'describe').'" title="Meta Information" type="application/rdf+xml" />';

	// implement the trackback interface
	$permanent_link = $context['url_to_home'].$context['url_to_root'].Sections::get_permalink($item);
	if($context['with_friendly_urls'] == 'Y')
		$trackback_link = $context['url_to_home'].$context['url_to_root'].'links/trackback.php/section/'.$item['id'];
	else
		$trackback_link = $context['url_to_home'].$context['url_to_root'].'links/trackback.php?anchor=section:'.$item['id'];
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
	$context['page_header'] .= "\n".'<link rel="pingback" href="'.$context['url_to_home'].$context['url_to_root'].'services/ping.php" />';

	// a meta link to our blogging interface
	$context['page_header'] .= "\n".'<link rel="EditURI" href="'.$context['url_to_home'].$context['url_to_root'].Sections::get_url($item['id'], 'EditURI').'" title="RSD" type="application/rsd+xml" />';

	// set specific headers
	if(isset($item['introduction']) && $item['introduction'])
		$context['page_description'] = strip_tags(Codes::beautify_introduction($item['introduction']));
	if(isset($item['create_name']) && $item['create_name'])
		$context['page_author'] = $item['create_name'];

	//
	// set page details -- $context['page_details']
	//

	// do not mention details at follow-up pages, nor to crawlers
	if(!$zoom_type && !Surfer::is_crawler()) {

		// one detail per line
		$text = '<p class="details">';
		$details = array();

		// add details from the overlay, if any
		if(is_object($overlay) && ($more = $overlay->get_text('details', $item)))
			$details[] = $more;

		// restricted to logged members
		if($item['active'] == 'R')
			$details[] = RESTRICTED_FLAG.' '.i18n::s('Community - Access is restricted to authenticated members');

		// restricted to associates
		if($item['active'] == 'N')
			$details[] = PRIVATE_FLAG.' '.i18n::s('Private - Access is restricted to selected persons');

		// index panel
		if(Surfer::is_empowered() && Surfer::is_logged()) {

			// at the parent index page
			if($item['anchor']) {

				if(isset($item['index_panel']) && ($item['index_panel'] == 'extra'))
					$details[] = i18n::s('Is displayed at the parent section page among other extra boxes.');
				elseif(isset($item['index_panel']) && ($item['index_panel'] == 'extra_boxes'))
					$details[] = i18n::s('Topmost articles are displayed at the parent section page in distinct extra boxes.');
				elseif(isset($item['index_panel']) && ($item['index_panel'] == 'gadget'))
					$details[] = i18n::s('Is displayed in the middle of the parent section page, among other gadget boxes.');
				elseif(isset($item['index_panel']) && ($item['index_panel'] == 'gadget_boxes'))
					$details[] = i18n::s('First articles are displayed at the parent section page in distinct gadget boxes.');
				elseif(isset($item['index_panel']) && ($item['index_panel'] == 'news'))
					$details[] = i18n::s('Articles are listed at the parent section page, in the area reserved to flashy news.');

			// at the site map
			} else {

				if(isset($item['index_map']) && ($item['index_map'] != 'Y'))
					$details[] = i18n::s('Is not publicly listed at the Site Map. Is listed with special sections, but only to associates.');
			}

		}

		// home panel
		if(Surfer::is_empowered() && Surfer::is_logged()) {
			if(isset($item['home_panel']) && ($item['home_panel'] == 'extra'))
				$details[] = i18n::s('Is displayed at the front page, among other extra boxes.');
			elseif(isset($item['home_panel']) && ($item['home_panel'] == 'extra_boxes'))
				$details[] = i18n::s('First articles are displayed at the front page in distinct extra boxes.');
			elseif(isset($item['home_panel']) && ($item['home_panel'] == 'gadget'))
				$details[] = i18n::s('Is displayed in the middle of the front page, among other gadget boxes.');
			elseif(isset($item['home_panel']) && ($item['home_panel'] == 'gadget_boxes'))
				$details[] = i18n::s('First articles are displayed at the front page in distinct gadget boxes.');
			elseif(isset($item['home_panel']) && ($item['home_panel'] == 'news'))
				$details[] = i18n::s('Articles are listed at the front page, in the area reserved to recent news.');
		}

		// signal sections to be activated
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		if(Surfer::is_empowered() && Surfer::is_logged() && ($item['activation_date'] > $now))
			$details[] = DRAFT_FLAG.' '.sprintf(i18n::s('Section will be activated %s'), Skin::build_date($item['activation_date']));

		// expired section
		if(Surfer::is_empowered() && Surfer::is_logged() && ($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $now))
			$details[] = EXPIRED_FLAG.' '.sprintf(i18n::s('Section has expired %s'), Skin::build_date($item['expiry_date']));

		// section editors and readers
		if(Surfer::is_empowered() && Surfer::is_logged()) {
			if($items =& Members::list_editors_for_member('section:'.$item['id'], 0, 50, 'comma'))
				$details[] = sprintf(i18n::s('%s: %s'), i18n::s('Editors'), Skin::build_list($items, 'comma'));

			if($items =& Members::list_readers_by_name_for_member('section:'.$item['id'], 0, 50, 'comma'))
				$details[] = sprintf(i18n::s('Readers: %s'), Skin::build_list($items, 'comma'));
		}

		// page watchers
		if(Surfer::is_logged() && ($items =& Members::list_watchers_by_posts_for_anchor('section:'.$item['id'], 0, 50, 'comma')))
			$details[] = sprintf(i18n::s('%s: %s'), i18n::s('Watchers'), Skin::build_list($items, 'comma'));

		// display details, if any
		if(count($details))
			$text .= ucfirst(implode(BR."\n", $details)).BR."\n";

		// other details
		$details = array();

		// additional details for associates and editors
		if(Surfer::is_empowered()) {

			// the creator of this section
			if($item['create_date'])
				$details[] = sprintf(i18n::s('posted by %s %s'), Users::get_link($item['create_name'], $item['create_address'], $item['create_id']), Skin::build_date($item['create_date']));

			// hide last edition if done by creator, and if less than 24 hours between creation and last edition
			if($item['create_date'] && ($item['create_id'] == $item['edit_id'])
					&& (SQL::strtotime($item['create_date'])+24*60*60 >= SQL::strtotime($item['edit_date'])))
				;

			// the last edition of this section
			else {

				if($item['edit_action'])
					$action = get_action_label($item['edit_action']);
				else
					$action = i18n::s('edited');

				$details[] = sprintf(i18n::s('%s by %s %s'), $action, Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));
			}

			// the number of hits
			if($item['hits'] > 1)
				$details[] = Skin::build_number($item['hits'], i18n::s('hits'));

		}

		// rank for this section
		if(Surfer::is_empowered() && Surfer::is_logged() && (intval($item['rank']) != 10000))
			$details[] = '{'.$item['rank'].'}';

		// locked section
		if(Surfer::is_empowered() && Surfer::is_logged() && ($item['locked'] ==  'Y') )
			$details[] = LOCKED_FLAG.' '.i18n::s('page is locked.');

		// inline details
		if(count($details))
			$text .= ucfirst(implode(', ', $details));

		// reference this item
		if(Surfer::is_member()) {
			$text .= BR.sprintf(i18n::s('Code to reference this page: %s'), '[section='.$item['id'].']');

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

	// show creator profile, if required to do so
	if(preg_match('/\bwith_owner_profile\b/', $item['options']) && ($poster = Users::get($item['create_id'])) && ($section =& Anchors::get('section:'.$item['id'])))
		$context['components']['profile'] = $section->get_user_profile($poster, 'extra', Skin::build_date($item['create_date']));

	// show news -- set in sections/edit.php
	if($item['index_news'] != 'none') {

		// news from sub-sections where index_panel == 'news'
		if($anchors =& Sections::get_anchors_for_anchor('section:'.$item['id'], 'news')) {

			// build a complete box
			$box['bar'] = array();
			$box['text'] = '';

			// set in sections/edit.php
			if($item['index_news_count'] < 1)
				$item['index_news_count'] = 7;

			// list articles by date
			$items =& Articles::list_for_anchor_by('publication', $anchors, 0, $item['index_news_count'], 'news');

			// render html
			if(is_array($items))
				$box['text'] .= Skin::build_list($items, 'news');
			elseif(is_string($items))
				$box['text'] .= $items;

			// we do have something to display
			if($box['text']) {

				// animate the text if required to do so
				if($item['index_news'] == 'scroll') {
					$box['text'] = Skin::scroll($box['text']);
					$box['id'] = 'scrolling_news';
				} elseif($item['index_news'] == 'rotate') {
					$box['text'] = Skin::rotate($box['text']);
					$box['id'] = 'rotating_news';
				} else
					$box['id'] = 'news';

				// make an extra box -- the css id is either #news, #scrolling_news or #rotating_news
				$context['components']['news'] = Skin::build_box(i18n::s('In the news'), $box['text'], 'extra', $box['id']);
			}
		}
	}

	// add extra information from the overlay, if any
	if(is_object($overlay))
		$context['components']['overlay'] = $overlay->get_text('extra', $item);

	// more extra boxes
	$context['components']['boxes'] = '';

	// add extra information from this item, if any
	if(isset($item['extra']) && $item['extra'])
		$context['components']['boxes'] .= Codes::beautify_extra($item['extra']);

	// one extra box per article, from sub-sections
	if($anchors =& Sections::get_anchors_for_anchor('section:'.$item['id'], 'extra_boxes')) {

		// the maximum number of boxes is a global parameter
		if(!isset($context['site_extra_maximum']) || !$context['site_extra_maximum'])
			$context['site_extra_maximum'] = 7;

		// articles to be displayed as extra boxes
		if($items =& Articles::list_for_anchor_by('publication', $anchors, 0, $context['site_extra_maximum'], 'boxes')) {
			foreach($items as $title => $attributes)
				$context['components']['boxes'] .= Skin::build_box($title, $attributes['content'], 'extra', $attributes['id'])."\n";
		}

	}

	// one extra box per section, from sub-sections
	if($anchors =& Sections::get_anchors_for_anchor('section:'.$item['id'], 'extra')) {

		// one box per section
		foreach($anchors as $anchor) {
			$box = array('title' => '', 'text' => '');

			// sanity check
			if(!$section =& Anchors::get($anchor))
				continue;

			// link to the section page from box title
			$box['title'] =& Skin::build_box_title($section->get_title(), $section->get_url(), i18n::s('View the section'));

			// build a compact list
			$box['list'] = array();

			// list matching articles
			if($items =& Articles::list_for_anchor_by('edition', $anchor, 0, COMPACT_LIST_SIZE+1, 'compact'))
				$box['list'] = array_merge($box['list'], $items);

			// add matching links, if any
			if((COMPACT_LIST_SIZE >= count($box['list'])) && ($items = Links::list_by_date_for_anchor($anchor, 0, COMPACT_LIST_SIZE - count($box['list']), 'compact')))
				$box['list'] = array_merge($box['list'], $items);

			// add matching sections, if any
			if((COMPACT_LIST_SIZE >= count($box['list'])) && ($items = Sections::list_by_title_for_anchor($anchor, 0, COMPACT_LIST_SIZE - count($box['list']), 'compact')))
				$box['list'] = array_merge($box['list'], $items);

			// more at the section page
			if(count($box['list']) > COMPACT_LIST_SIZE) {
				@array_splice($box['list'], COMPACT_LIST_SIZE);

				// link to the section page
				$box['list'] = array_merge($box['list'], array($section->get_url() => i18n::s('More pages').MORE_IMG));
			}

			// render the html for the box
			if(count($box['list']))
				$box['text'] =& Skin::build_list($box['list'], 'compact');

			// give a chance to associates to populate empty sections
			elseif(Surfer::is_empowered())
				$box['text'] = Skin::build_link($section->get_url(), i18n::s('View the section'), 'shortcut');

			// append a box
			if($box['text'])
				$context['components']['boxes'] .= Skin::build_box($box['title'], $box['text'], 'navigation');

		}
	}

	// 'Share' box
	//
	$lines = array();

	// add participants
	if(Sections::is_owned($anchor, $item) && isset($context['with_email']) && ($context['with_email'] == 'Y')) {
		Skin::define_img('SECTIONS_INVITE_IMG', 'sections/invite.gif');
		$lines[] = Skin::build_link(Sections::get_url($item['id'], 'invite'), SECTIONS_INVITE_IMG.i18n::s('Invite participants'), 'basic');
	}

	// the command to track back
	if(Surfer::is_logged()) {
		Skin::define_img('TOOLS_TRACKBACK_IMG', 'tools/trackback.gif');
		$lines[] = Skin::build_link('links/trackback.php?anchor='.urlencode('section:'.$item['id']), TOOLS_TRACKBACK_IMG.i18n::s('Reference this page'), 'basic', i18n::s('Various means to link to this page'));
	}

	// print this page
	if(Surfer::is_logged() || (isset($context['with_anonymous_export_tools']) && ($context['with_anonymous_export_tools'] == 'Y'))) {
		Skin::define_img('TOOLS_PRINT_IMG', 'tools/print.gif');
		$lines[] = Skin::build_link(Sections::get_url($id, 'print'), TOOLS_PRINT_IMG.i18n::s('Print this page'), 'basic', i18n::s('Get a paper copy of this page.'));
	}

	// in a side box
	if(count($lines))
		$context['components']['share'] = Skin::build_box(i18n::s('Share'), Skin::finalize_list($lines, 'tools'), 'extra', 'share');

	// 'Information channels' box
	$lines = array();

	// watch command is provided to logged surfers
	if(Surfer::get_id() && !$zoom_type) {

		$link = Users::get_url('section:'.$item['id'], 'track');

		if($in_watch_list)
			$label = i18n::s('Forget this section');
		else
			$label = i18n::s('Watch this section');

		Skin::define_img('TOOLS_WATCH_IMG', 'tools/watch.gif');
		$lines[] = Skin::build_link($link, TOOLS_WATCH_IMG.$label, 'basic', i18n::s('Manage your watch list'));
	}

	// get news from rss
	if(isset($item['id']) && (!isset($context['skins_general_without_feed']) || ($context['skins_general_without_feed'] != 'Y')) ) {

		$lines[] = Skin::build_link($context['url_to_home'].$context['url_to_root'].Sections::get_url($item['id'], 'feed'), i18n::s('Recent pages'), 'xml');

		$lines[] = Skin::build_link($context['url_to_home'].$context['url_to_root'].Files::get_url('section:'.$item['id'], 'feed'), i18n::s('Recent files'), 'xml');

		// comments are allowed
		if(Comments::are_allowed($anchor, $item)) {
			$lines[] = Skin::build_link($context['url_to_home'].$context['url_to_root'].Comments::get_url('section:'.$item['id'], 'feed'), i18n::s('Recent comments'), 'xml');
		}

		// public aggregators
// 		if(!isset($context['without_internet_visibility']) || ($context['without_internet_visibility'] != 'Y'))
// 			$lines[] = join(BR, Skin::build_subscribers($context['url_to_home'].$context['url_to_root'].Sections::get_url($item['id'], 'feed'), $item['title']));

	}

	// in a side box
	if(count($lines))
		$context['components']['channels'] = Skin::build_box(i18n::s('Monitor'), join(BR, $lines), 'extra', 'feeds');

	// twin pages
	if(isset($item['nick_name']) && $item['nick_name']) {

		// build a complete box
		$box['text'] = '';

		// list pages with same name
		$items = Sections::list_for_name($item['nick_name'], $item['id'], 'compact');

		// actually render the html for the section
		if(is_array($items))
			$box['text'] .= Skin::build_list($items, 'compact');
		if($box['text'])
			$context['components']['twins'] = Skin::build_box(i18n::s('Related'), $box['text'], 'navigation', 'twins');

	}

	// the contextual menu, in a navigation box, if this has not been disabled
	if( (!is_object($anchor) || !$anchor->has_option('no_contextual_menu', FALSE))
		&& (!isset($item['options']) || !preg_match('/\bno_contextual_menu\b/i', $item['options']))
		&& isset($context['current_focus']) && ($menu =& Skin::build_contextual_menu($context['current_focus']))) {

		// use title from topmost level
		if(count($context['current_focus']) && ($anchor =& Anchors::get($context['current_focus'][0]))) {
			$box_title = $anchor->get_title();
			$box_url = $anchor->get_url();

		// generic title
		} else {
			$box_title = i18n::s('Navigation');
			$box_url = '';
		}

		// in a navigation box
		$box_popup = '';
		$context['components']['contextual'] = Skin::build_box($box_title, $menu, 'navigation', 'contextual_menu', $box_url, $box_popup)."\n";
	}

	// categories attached to this section
	if(!$zoom_type || ($zoom_type == 'categories')) {

		// build a complete box
		$box['bar'] = array();
		$box['text'] = '';

		// list categories by title
		$offset = ($zoom_index - 1) * CATEGORIES_PER_PAGE;
		$items =& Members::list_categories_by_title_for_member('section:'.$item['id'], $offset, CATEGORIES_PER_PAGE, 'sidebar');

		// the command to change categories assignments
		if(Categories::are_allowed($anchor, $item))
			$items = array_merge($items, array( Categories::get_url('section:'.$item['id'], 'select') => i18n::s('Assign categories') ));

		// actually render the html for the section
		if(@count($box['bar']))
			$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');
		if(is_array($items))
			$box['text'] .= Skin::build_list($items, 'compact');
		if($box['text'])
			$context['components']['categories'] = Skin::build_box(i18n::s('See also'), $box['text'], 'navigation', 'categories');

	}

	// offer bookmarklets if submissions are allowed -- complex command
	if(Surfer::has_all() && (!isset($context['pages_without_bookmarklets']) || ($context['pages_without_bookmarklets'] != 'Y'))) {

		// accessible bookmarklets
		$bookmarklets = array();

		// blogging bookmarklet uses YACS codes
		if(Articles::are_allowed($anchor, $item)) {
			$bookmarklet = "javascript:function findFrame(f){var i;try{isThere=f.document.selection.createRange().text;}catch(e){isThere='';}if(isThere==''){for(i=0;i&lt;f.frames.length;i++){findFrame(f.frames[i]);}}else{s=isThere}return s}"
				."var s='';"
				."d=document;"
				."s=d.selection?findFrame(window):window.getSelection();"
				."window.location='".$context['url_to_home'].$context['url_to_root']."articles/edit.php?"
					."blogid=".$item['id']
					."&amp;title='+escape(d.title)+'"
					."&amp;text='+escape('%22'+s+'%22%5Bnl]-- %5Blink='+d.title+']'+d.location+'%5B/link]')+'"
					."&amp;source='+escape(d.location);";
			$bookmarklets[] = '<a href="'.$bookmarklet.'">'.sprintf(i18n::s('Blog at %s'), $item['title']).'</a>';
		}

		// bookmark bookmarklet, if links are allowed
		if(Links::are_allowed($anchor, $item, 'section')) {
			$bookmarklet = "javascript:function findFrame(f){var i;try{isThere=f.document.selection.createRange().text;}catch(e){isThere='';}if(isThere==''){for(i=0;i&lt;f.frames.length;i++){findFrame(f.frames[i]);}}else{s=isThere}return s}"
				."var s='';"
				."d=document;"
				."s=d.selection?findFrame(window):window.getSelection();"
				."window.location='".$context['url_to_home'].$context['url_to_root']."links/edit.php?"
					."link='+escape(d.location)+'"
					."&amp;anchor='+escape('section:".$item['id']."')+'"
					."&amp;title='+escape(d.title)+'"
					."&amp;text='+escape(s);";

			if($item['nick_name'] == 'bookmarks')
				$name = strip_tags($context['site_name']);
			else
				$name = strip_tags($item['title']);
			$bookmarklets[] = '<a href="'.$bookmarklet.'">'.sprintf(i18n::s('Bookmark at %s'), $name).'</a>';
		}

		// an extra box
		if(count($bookmarklets)) {
			$label = i18n::ns('Bookmark following link to contribute here:', 'Bookmark following links to contribute here:', count($bookmarklets))."\n<ul>".'<li>'.implode('</li><li>', $bookmarklets).'</li></ul>'."\n";

			$context['components']['bookmarklets'] = Skin::build_box(i18n::s('Bookmarklets to contribute'), $label, 'extra', 'bookmarklets');
		}
	}

	// list feeding servers, if any
// 	if(Surfer::is_associate() && ($content = Servers::list_by_date_for_anchor('section:'.$item['id']))) {
// 		if(is_array($content))
// 			$content =& Skin::build_list($content, 'compact');
// 		$context['components']['servers'] = Skin::build_box(i18n::s('Related servers'), $content, 'navigation', 'servers');
// 	}

	// download content
// 	if(Surfer::is_member() && !$zoom_type && (!isset($context['pages_without_freemind']) || ($context['pages_without_freemind'] != 'Y')) ) {
//
// 		// box content
// 		$content = Skin::build_link(Sections::get_url($item['id'], 'freemind', utf8::to_ascii($context['site_name'].' - '.strip_tags(Codes::beautify_title(trim($item['title']))).'.mm')), i18n::s('Freemind map'), 'basic');
//
// 		// in a sidebar box
// 		$context['components']['download'] = Skin::build_box(i18n::s('Download'), $content, 'navigation');
//
// 	}

	// referrals, if any
	$context['components']['referrals'] =& Skin::build_referrals(Sections::get_permalink($item));

	//
	// use a specific script to render the page in replacement of the standard one --also protect from hackers
	//

	// the overlay may generate some tabs
	$context['tabs'] = '';
	if(is_object($overlay))
		$context['tabs'] = $overlay->get_tabs('view', $item);

	// branch to another script
	if(isset($item['options']) && preg_match('/\bview_as_[a-zA-Z0-9_\.]+?\b/i', $item['options'], $matches) && is_readable($matches[0].'.php')) {
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
	// update main panel -- $context['text']
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

		// only at the first page
		if($page == 1) {

			// the introduction text, if any
			if(is_object($overlay))
				$text .= Skin::build_block($overlay->get_text('introduction', $item), 'introduction');
			elseif(isset($item['introduction']) && trim($item['introduction']))
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
			if($page > count($pages))
				$page = count($pages);
			if($page < 1)
				$page = 1;
			$description = $pages[ $page-1 ];

			// if there are several pages, remove toc and toq codes
			if(count($pages) > 1)
				$description = preg_replace('/\s*\[(toc|toq)\]\s*/is', '', $description);

			// beautify the target page
			$text .= Skin::build_block($description, 'description', '', $item['options']);

			// if there are several pages, add navigation commands to browse them
			if(count($pages) > 1) {
				$page_menu = array( '_' => i18n::s('Pages') );
				$home =& Sections::get_permalink($item);
				$prefix = Sections::get_url($item['id'], 'navigate', 'articles');
				$page_menu = array_merge($page_menu, Skin::navigate($home, $prefix, count($pages), 1, $page));

				$text .= Skin::build_list($page_menu, 'menu_bar');
			}

		}

		// add trailer information from the overlay, if any
		if(is_object($overlay))
			$text .= $overlay->get_text('trailer', $item);

	}

	//
	// gadget boxes
	//

	// gadget boxes are featured only at the main index page
	if(!$zoom_type) {

		// all boxes
		$content = '';

		// one gadget box per article, from sub-sections
		if($anchors =& Sections::get_anchors_for_anchor('section:'.$item['id'], 'gadget_boxes')) {

			// up to 6 articles to be displayed as gadget boxes
			if($items =& Articles::list_for_anchor_by('edition', $anchors, 0, 7, 'boxes')) {
				foreach($items as $title => $attributes)
					$content .= Skin::build_box($title, $attributes['content'], 'gadget', $attributes['id'])."\n";
			}
		}

		// one gadget box per section, from sub-sections
		if($anchors =& Sections::get_anchors_for_anchor('section:'.$item['id'], 'gadget')) {

			// one box per section
			foreach($anchors as $anchor) {

				// sanity check
				if(!$section =& Anchors::get($anchor))
					continue;

				$box = array( 'title' => '', 'list' => array(), 'text' => '');

				// link to the section page from box title
				$box['title'] =& Skin::build_box_title($section->get_title(), $section->get_url(), i18n::s('View the section'));

				// add sub-sections, if any
				if($related = Sections::list_by_title_for_anchor($anchor, 0, COMPACT_LIST_SIZE+1, 'compact')) {
					foreach($related as $url => $label) {
						if(is_array($label))
							$label = $label[0].' '.$label[1];
						$box['list'] = array_merge($box['list'], array($url => array('', $label, '', 'basic')));
					}
				}

				// list matching articles
				if((COMPACT_LIST_SIZE >= count($box['list'])) && ($items =& Articles::list_for_anchor_by('edition', $anchor, 0, COMPACT_LIST_SIZE+1 - count($box['list']), 'compact')))
					$box['list'] = array_merge($box['list'], $items);

				// add matching links, if any
				if((COMPACT_LIST_SIZE >= count($box['list'])) && ($items = Links::list_by_date_for_anchor($anchor, 0, COMPACT_LIST_SIZE+1 - count($box['list']), 'compact')))
					$box['list'] = array_merge($box['list'], $items);

				// more at the section page
				if(count($box['list']) > COMPACT_LIST_SIZE) {
					@array_splice($box['list'], COMPACT_LIST_SIZE);

					// link to the section page
					$box['list'] = array_merge($box['list'], array($section->get_url() => i18n::s('More pages').MORE_IMG));
				}

				// render the html for the box
				if(count($box['list']))
					$box['text'] =& Skin::build_list($box['list'], 'compact');

				// display content of the section itself
				elseif($description = $section->get_value('description')) {
					$box['text'] .= Skin::build_block($description, 'description', '', $item['options']);

				// give a chance to associates to populate empty sections
				 } elseif(Surfer::is_empowered())
					$box['text'] = Skin::build_link($section->get_url(), i18n::s('View the section'), 'shortcut');

				// append a box
				if($box['text'])
					$content .= Skin::build_box($box['title'], $box['text'], 'gadget');

			}

		}

		// leverage CSS
		if($content)
			$content = '<p id="gadgets_prefix"> </p>'."\n".$content.'<p id="gadgets_suffix"> </p>'."\n";

		// add after main menu bar
		$text .= $content;
	}

	//
	// sub-sections, if any
	//

	// the list of related sections if not at another follow-up page
	if(!$zoom_type || ($zoom_type == 'sections')) {

		// display sub-sections as a Freemind map, except to search engines
		if(isset($item['sections_layout']) && ($item['sections_layout'] == 'freemind') && !Surfer::is_crawler()) {
			$text .= Codes::render_freemind('section:'.$item['id'].', 100%, 400px');

		// use a regular layout
		} elseif(!isset($item['sections_layout']) || ($item['sections_layout'] != 'none')) {

			// select a layout
			if(!isset($item['sections_layout']) || !$item['sections_layout']) {
				include_once 'layout_sections.php';
				$layout = new Layout_sections();
			} elseif($item['sections_layout'] == 'decorated') {
				include_once 'layout_sections.php';
				$layout = new Layout_sections();
			} elseif($item['sections_layout'] == 'map') {
				include_once 'layout_sections_as_yahoo.php';
				$layout = new Layout_sections_as_yahoo();
			} elseif(is_readable($context['path_to_root'].'sections/layout_sections_as_'.$item['sections_layout'].'.php')) {
				$name = 'layout_sections_as_'.$item['sections_layout'];
				include_once $name.'.php';
				$layout = new $name;
			} else {

				// useful warning for associates
				if(Surfer::is_associate())
					Logger::error(sprintf(i18n::s('Warning: No script exists for the customized layout %s'), $item['sections_layout']));

				include_once '../sections/layout_sections.php';
				$layout = new Layout_sections();
			}

			// the maximum number of sections per page
			if(isset($item['sections_count']) && ($item['sections_count'] > 1))
				$items_per_page = $item['sections_count'];
			elseif(is_object($layout))
				$items_per_page = $layout->items_per_page();
			else
				$items_per_page = SECTIONS_PER_PAGE;

			// build a complete box
			$box = array('top_bar' => array(), 'text' => '', 'bottom_bar' => array());

			// count the number of subsections
			if($count = Sections::count_for_anchor('section:'.$item['id'])) {

				if($count > 5)
					$box['top_bar'] = array('_count' => sprintf(i18n::ns('%d section', '%d sections', $count), $count));

				// navigation commands for sections
				$home =& Sections::get_permalink($item);
				$prefix = Sections::get_url($item['id'], 'navigate', 'sections');
				$box['top_bar'] += Skin::navigate($home, $prefix, $count, $items_per_page, $zoom_index);

				// help to navigate across multiple pages
				if($count > $items_per_page)
					$box['bottom_bar'] = $box['top_bar'];

			}

			// the command to add a new section
			if(Sections::are_allowed($anchor, $item)) {
				Skin::define_img('SECTIONS_ADD_IMG', 'sections/add.gif');
				$box['top_bar'] += array('sections/edit.php?anchor='.urlencode('section:'.$item['id']) => SECTIONS_ADD_IMG.i18n::s('Add a section'));
			}

			// list items by title
			$offset = ($zoom_index - 1) * $items_per_page;
			$items = Sections::list_by_title_for_anchor('section:'.$item['id'], $offset, $items_per_page, $layout);

			// top menu
			if($box['top_bar'])
				$box['text'] .= Skin::build_list($box['top_bar'], 'menu_bar');

			// actually render the html for the section
			if(is_array($items) && is_string($item['sections_layout']) && ($item['sections_layout'] == 'compact'))
				$box['text'] .= Skin::build_list($items, 'compact');
			elseif(is_array($items))
				$box['text'] .= Skin::build_list($items, 'decorated');
			elseif(is_string($items))
				$box['text'] .= $items;

			// bottom menu
			if($box['bottom_bar'])
				$box['text'] .= Skin::build_list($box['bottom_bar'], 'menu_bar');

			// there is some box content
			if($box['text'])
				$text .= $box['text'];

		}
	}

	//
	// articles related to this section, or to sub-sections
	//

	// the list of related articles if not at another follow-up page
	if(!$zoom_type || ($zoom_type == 'articles')) {

		// this is a slideshow
		if(preg_match('/\bwith_slideshow\b/i', $item['options'])) {

			// explain what we are talking about
			$description = '<p>'.sprintf(i18n::s('Content of this section has been designed as an interactive on-line presentation. Navigate using the keyboard or a pointing device as usual. Use letter C to display control, and letter B to switch to/from a black screen. Based on the %s technology.'), Skin::build_link('http://www.meyerweb.com/eric/tools/s5/', i18n::s('S5'), 'external')).'</p>';

			// the label
			Skin::define_img('FILES_PLAY_IMG', 'files/play.gif');
			$label = FILES_PLAY_IMG.' '.sprintf(i18n::s('Play %s'), str_replace('_', ' ', $item['title']));

			// hovering the link
			$title = i18n::s('Start');

			// use a definition list to enable customization of the download box
			$text .= '<dl class="download">'
				.'<dt>'.Skin::build_link(Sections::get_url($item['id'], 'slideshow'), $label, 'basic', $title).'</dt>'
				.'<dd>'.$description.'</dd></dl>'."\n";

		}

		// only associates and editors can list pages of a slideshow
		if(Surfer::is_empowered() || !preg_match('/\bwith_slideshow\b/i', $item['options'])) {

			// delegate rendering to the overlay, where applicable
			if(is_object($content_overlay) && ($overlaid = $content_overlay->render('articles', 'section:'.$item['id'], $zoom_index))) {
				$text .= $overlaid;

			// regular rendering
			} elseif(!isset($item['articles_layout']) || ($item['articles_layout'] != 'none')) {

				// select a layout
				if(!isset($item['articles_layout']) || !$item['articles_layout']) {
					include_once '../articles/layout_articles.php';
					$layout = new Layout_articles();
				} elseif($item['articles_layout'] == 'decorated') {
					include_once '../articles/layout_articles.php';
					$layout = new Layout_articles();
				} elseif($item['articles_layout'] == 'map') {
					include_once '../articles/layout_articles_as_yahoo.php';
					$layout = new Layout_articles_as_yahoo();
				} elseif($item['articles_layout'] == 'wiki') {
					include_once '../articles/layout_articles.php';
					$layout = new Layout_articles();
				} elseif(is_readable($context['path_to_root'].'articles/layout_articles_as_'.$item['articles_layout'].'.php')) {
					$name = 'layout_articles_as_'.$item['articles_layout'];
					include_once $context['path_to_root'].'articles/'.$name.'.php';
					$layout = new $name;
				} else {

					// useful warning for associates
					if(Surfer::is_associate())
						Logger::error(sprintf(i18n::s('Warning: No script exists for the customized layout %s'), $item['articles_layout']));

					include_once '../articles/layout_articles.php';
					$layout = new Layout_articles();
				}

				// avoid links to this page
				if(is_object($layout) && is_callable(array($layout, 'set_variant')))
					$layout->set_variant('section:'.$item['id']);

				// the maximum number of articles per page
				if(is_object($layout))
					$items_per_page = $layout->items_per_page();
				else
					$items_per_page = ARTICLES_PER_PAGE;

				// create a box
				$box = array('top_bar' => array(), 'text' => '', 'bottom_bar' => array());

				// no navigation bar with alistapart
				if(!isset($item['articles_layout']) || ($item['articles_layout'] != 'alistapart')) {

					// count the number of articles in this section
					if($count = Articles::count_for_anchor('section:'.$item['id'])) {
						if($count > 5)
							$box['top_bar'] += array('_count' => sprintf(i18n::ns('%d page', '%d pages', $count), $count));

						// navigation commands for articles
						$home =& Sections::get_permalink($item);
						$prefix = Sections::get_url($item['id'], 'navigate', 'articles');
						$box['top_bar'] += Skin::navigate($home, $prefix, $count, $items_per_page, $zoom_index);

						// help to navigate across multiple pages
						if($count > $items_per_page)
							$box['bottom_bar'] = $box['top_bar'];

					}

					// the command to post a new page
					if(Articles::are_allowed($anchor, $item)) {

						Skin::define_img('ARTICLES_ADD_IMG', 'articles/add.gif');
						$url = 'articles/edit.php?anchor='.urlencode('section:'.$item['id']);
						if(is_object($content_overlay) && ($label = $content_overlay->get_label('new_command')))
							;
						else
							$label = ARTICLES_ADD_IMG.i18n::s('Add a page');
						$box['top_bar'] += array( $url => $label );

					}
				}

				// sort and list articles
				$offset = ($zoom_index - 1) * $items_per_page;
				if(preg_match('/\barticles_by_([a-z_]+)\b/i', $item['options'], $matches))
					$order = $matches[1];
				elseif(is_callable(array($layout, 'items_order')))
					$order = $layout->items_order();
				else
					$order = 'edition';
				$items =& Articles::list_for_anchor_by($order, 'section:'.$item['id'], $offset, $items_per_page, $layout);

				// top menu
				if($box['top_bar'])
					$box['text'] .= Skin::build_list($box['top_bar'], 'menu_bar');

				// items in the middle
				if(is_array($items) && isset($item['articles_layout']) && ($item['articles_layout'] == 'compact'))
					$box['text'] .= Skin::build_list($items, 'compact');
				elseif(is_array($items))
					$box['text'] .= Skin::build_list($items, 'decorated');
				elseif(is_string($items))
					$box['text'] .= $items;

				// bottom menu
				if($box['bottom_bar'])
					$box['text'] .= Skin::build_list($box['bottom_bar'], 'menu_bar');

				// there is some box content
				if($box['text'])
					$text .= $box['text'];

				// newest articles of main sub-sections, if not at another follow-up page
				if(!$zoom_type && preg_match('/\bwith_deep_news\b/i', $item['options'])) {

					// select a layout
					if(!isset($item['articles_layout']) || !$item['articles_layout']) {
						include_once '../articles/layout_articles.php';
						$layout = new Layout_articles();
					} elseif($item['articles_layout'] == 'decorated') {
						include_once '../articles/layout_articles.php';
						$layout = new Layout_articles();
					} elseif($item['articles_layout'] == 'map') {
						include_once '../articles/layout_articles_as_yahoo.php';
						$layout = new Layout_articles_as_yahoo();
					} elseif($item['articles_layout'] == 'wiki') {
						include_once '../articles/layout_articles.php';
						$layout = new Layout_articles();
					} elseif(is_readable($context['path_to_root'].'articles/layout_articles_as_'.$item['articles_layout'].'.php')) {
						$name = 'layout_articles_as_'.$item['articles_layout'];
						include_once $context['path_to_root'].'articles/'.$name.'.php';
						$layout = new $name;
					} else {

						// useful warning for associates
						if(Surfer::is_associate())
							Logger::error(sprintf(i18n::s('Warning: No script exists for the customized layout %s'), $item['articles_layout']));

						include_once '../articles/layout_articles.php';
						$layout = new Layout_articles();
					}

					// avoid links to this page
					if(is_object($layout) && is_callable(array($layout, 'set_variant')))
						$layout->set_variant('section:'.$item['id']);

					// the maximum number of articles per page
					if(is_object($layout))
						$items_per_page = $layout->items_per_page();
					else
						$items_per_page = ARTICLES_PER_PAGE;

					// sub-sections targeting the main area
					if($anchors =& Sections::get_anchors_for_anchor('section:'.$item['id'], 'main')) {

						// use ordering options set for the section
						if(preg_match('/\barticles_by_([a-z_]+)\b/i', $item['options'], $matches))
							$order = $matches[1];
						else
							$order = 'edition';
						$items =& Articles::list_for_anchor_by($order, $anchors, 0, $items_per_page, $layout);

						// actually render the html for the section
						$content = '';
						if(is_array($items) && is_string($item['articles_layout']) && ($item['articles_layout'] == 'compact'))
							$content .= Skin::build_list($items, 'compact');
						elseif(is_array($items))
							$content .= Skin::build_list($items, 'decorated');
						elseif(is_string($items))
							$content .= $items;

						// part of the main content
						if($content)
							$text .= Skin::build_box(i18n::s('What is new?'), $content, 'header1', 'what_is_new');
					}
				}
			}
		}

	// show hidden articles to associates and editors
	} elseif( (!$zoom_type || ($zoom_type == 'articles'))
		&& isset($item['articles_layout']) && ($item['articles_layout'] == 'none')
		&& Surfer::is_empowered() ) {

		// make a compact list
		include_once '../articles/layout_articles_as_compact.php';
		$layout = new Layout_articles_as_compact();

		// avoid links to this page
		if(is_object($layout) && is_callable(array($layout, 'set_variant')))
			$layout->set_variant('section:'.$item['id']);

		// the maximum number of articles per page
		if(is_object($layout))
			$items_per_page = $layout->items_per_page();
		else
			$items_per_page = ARTICLES_PER_PAGE;

		// list articles by date (default) or by title (option 'articles_by_title')
		$offset = ($zoom_index - 1) * $items_per_page;
		if(preg_match('/\barticles_by_([a-z_]+)\b/i', $item['options'], $matches))
			$order = $matches[1];
		else
			$order = 'edition';
		$items =& Articles::list_for_anchor_by($order, 'section:'.$item['id'], $offset, $items_per_page, $layout);

		// actually render the html for the box
		$content = '';
		if(is_array($items))
			$content = Skin::build_list($items, 'compact');
		else
			$content = $items;

		// make a complete box
		if($content)
			$text .= Skin::build_box(i18n::s('Hidden pages'), $content, 'header1', 'articles');
	}

	//
	// files attached to this section
	//

	// the list of related files if not at another follow-up page
	if(!$zoom_type || ($zoom_type == 'files')) {

		// build a complete box
		$box = array('bar' => array(), 'text' => '');

		// count the number of files in this section
		if($count = Files::count_for_anchor('section:'.$item['id'])) {
			if($count > 5)
				$box['bar'] += array('_count' => sprintf(i18n::ns('%d file', '%d files', $count), $count));

			// list files by date (default) or by title (option 'files_by_title')
			$offset = ($zoom_index - 1) * FILES_PER_PAGE;
			if(preg_match('/\bfiles_by_title\b/i', $item['options']))
				$items = Files::list_by_title_for_anchor('section:'.$item['id'], $offset, FILES_PER_PAGE);
			else
				$items = Files::list_by_date_for_anchor('section:'.$item['id'], $offset, FILES_PER_PAGE);

			// actually render the html
			if(is_array($items))
				$box['text'] .= Skin::build_list($items, 'decorated');
			elseif(is_string($items))
				$box['text'] .= $items;

			// navigation commands for files
			$home =& Sections::get_permalink($item);
			$prefix = Sections::get_url($item['id'], 'navigate', 'files');
			$box['bar'] = array_merge($box['bar'],
				Skin::navigate($home, $prefix, $count, FILES_PER_PAGE, $zoom_index));

		}

		// the command to post a new file -- check 'with_files' option
		if(Files::are_allowed($anchor, $item, 'section')) {
			Skin::define_img('FILES_UPLOAD_IMG', 'files/upload.gif');
			$box['bar'] += array('files/edit.php?anchor='.urlencode('section:'.$item['id']) => FILES_UPLOAD_IMG.i18n::s('Upload a file') );
		}

		// integrate the nemu bar
		if(count($box['bar']) && ($context['skin_variant'] != 'mobile'))
			$box['text'] = Skin::build_list($box['bar'], 'menu_bar').$box['text'];

		// build a box
		if(trim($box['text']))
			$text .= Skin::build_box(i18n::s('Files'), $box['text'], 'header1', 'files');

	}

	//
	// attached comments
	//

	// the list of related comments if not at another follow-up page
	if(!$zoom_type || ($zoom_type == 'comments')) {

		// title label
		if($section =& Anchors::get('section:'.$item['id']))
			$title_label = $section->get_label('comments', 'title');
		else
			$title_label = i18n::s('Comments');

		// new comments are allowed -- check option 'with_comments'
		if(Comments::are_allowed($anchor, $item, 'section')) {
			if(preg_match('/\bcomments_as_wall\b/i', $item['options']))
				$comments_prefix = TRUE;
			else
				$comments_suffix = TRUE;
		}

		// layout is defined in options
		if($item['articles_layout'] == 'boxesandarrows') {
			include_once '../comments/layout_comments_as_boxesandarrows.php';
			$layout = new Layout_comments_as_boxesandarrows();

		} elseif($item['articles_layout'] == 'daily') {
			include_once '../comments/layout_comments_as_daily.php';
			$layout = new Layout_comments_as_daily();

		} elseif($item['articles_layout'] == 'jive') {
			include_once '../comments/layout_comments_as_jive.php';
			$layout = new Layout_comments_as_jive();

		} elseif($item['articles_layout'] == 'manual') {
			include_once '../comments/layout_comments_as_manual.php';
			$layout = new Layout_comments_as_manual();

		} elseif($item['articles_layout'] == 'yabb') {
			include_once '../comments/layout_comments_as_yabb.php';
			$layout = new Layout_comments_as_yabb();

		} else {
			include_once '../comments/layout_comments_as_yabb.php';
			$layout = new Layout_comments_as_yabb();
		}

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
		$box = array('bar' => array(), 'text' => '');

		// new comments are allowed
		if(isset($comments_prefix))
			$box['text'] .= Comments::get_form('section:'.$item['id']);

		// a navigation bar for these comments
		if($zoom_type && ($zoom_type == 'comments'))
			$link = '_count';
		if($count = Comments::count_for_anchor('section:'.$item['id'])) {
			if($count > 5)
				$box['bar'] += array($link => sprintf(i18n::s('%d comments'), $count));

			// list comments by date
			$items = Comments::list_by_date_for_anchor('section:'.$item['id'], $offset, $items_per_page, $layout, isset($comments_prefix) || preg_match('/\bcomments_as_wall\b/i', $item['options']));

			// actually render the html
			if(is_array($items))
				$box['text'] .= Skin::build_list($items, 'rows');
			elseif(is_string($items))
				$box['text'] .= $items;

			// navigation commands for comments
			$prefix = Comments::get_url('section:'.$item['id'], 'navigate');
			$box['bar'] = array_merge($box['bar'],
				Skin::navigate(NULL, $prefix, $count, $items_per_page, $zoom_index, FALSE, TRUE));

		}

		// new comments are allowed
		if(isset($comments_suffix)) {
			Skin::define_img('COMMENTS_ADD_IMG', 'comments/add.gif');
			$box['bar'] += array( Comments::get_url('section:'.$item['id'], 'comment') => array('', COMMENTS_ADD_IMG.i18n::s('Post a comment'), '', 'basic', '', i18n::s('Express yourself, and say what you think.')));
		}

		// show commands
		if(count($box['bar']) && ($context['skin_variant'] != 'mobile'))
			$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');

		// insert a full box
		if($box['text'])
			$box['text'] =& Skin::build_box($title_label, $box['text'], 'header1', 'comments');


		// there is some box content
		if(trim($box['text']))
			$text .= $box['text'];

	}

	//
	// links attached to this section
	//

	// the list of related links if not at another follow-up page
	if(!$zoom_type || ($zoom_type == 'links')) {

		// build a complete box
		$box = array('bar' => array(), 'text' => '');

		// a navigation bar for these links
		if($count = Links::count_for_anchor('section:'.$item['id'])) {
			if($count > 5)
				$box['bar'] += array('_count' => sprintf(i18n::ns('%d link', '%d links', $count), $count));

			// list links by date (default) or by title (option 'links_by_title')
			$offset = ($zoom_index - 1) * LINKS_PER_PAGE;
			if(preg_match('/\blinks_by_title\b/i', $item['options']))
				$items = Links::list_by_title_for_anchor('section:'.$item['id'], $offset, LINKS_PER_PAGE, 'no_anchor');
			else
				$items = Links::list_by_date_for_anchor('section:'.$item['id'], $offset, LINKS_PER_PAGE, 'no_anchor');

			// actually render the html
			if(is_array($items))
				$box['text'] .= Skin::build_list($items, 'rows');
			elseif(is_string($items))
				$box['text'] .= $items;

			// navigation commands for links
			$home =& Sections::get_permalink($item);
			$prefix = Sections::get_url($item['id'], 'navigate', 'links');
			$box['bar'] = array_merge($box['bar'],
				Skin::navigate($home, $prefix, $count, LINKS_PER_PAGE, $zoom_index));

		}

		// new links are allowed -- check option 'with_links'
		if(Links::are_allowed($anchor, $item, 'section')) {
			Skin::define_img('LINKS_ADD_IMG', 'links/add.gif');
			$box['bar'] += array('links/edit.php?anchor='.urlencode('section:'.$item['id']) => LINKS_ADD_IMG.i18n::s('Add a link') );
		}

		// integrate the menu bar at the end
		if(count($box['bar']) && ($context['skin_variant'] != 'mobile'))
			$box['text'] = Skin::build_list($box['bar'], 'menu_bar').$box['text'];

		// build a box
		if(trim($box['text']))
			$text .= Skin::build_box(i18n::s('Links'), $box['text'], 'header1', 'links');

	}

	//
	// inactive sub sections
	//

	// associates may list special sections as well
	if(!$zoom_type && Surfer::is_empowered()) {

		// no special item yet
		$items = array();

		// if sub-sections are rendered by Freemind applet, also provide regular links to empowered surfers
		if(isset($item['sections_layout']) && ($item['sections_layout'] == 'freemind'))
			$items = Sections::list_by_title_for_anchor('section:'.$item['id'], 0, 50, 'compact');

		// append inactive sections, if any
		$items = array_merge($items, Sections::list_inactive_by_title_for_anchor('section:'.$item['id'], 0, 50, 'compact'));

		// we have an array to format
		if(count($items)) {
			$content =& Skin::build_list($items, 'compact');

			// displayed as another box
			$text .= Skin::build_box(i18n::s('Other sections'), $content, 'header1', 'other_sections');

		}
	}

	//
	// trailer information
	//

	// add trailer information from this item, if any
	if(isset($item['trailer']) && trim($item['trailer']))
		$text .= Codes::beautify($item['trailer']);

	// update the main content panel
	$context['text'] .= $text;

	//
	// extra panel
	//

	// page tools
	//

	// commands to add pages
	if(Articles::are_allowed($anchor, $item)) {

		Skin::define_img('ARTICLES_ADD_IMG', 'articles/add.gif');
		$url = 'articles/edit.php?anchor='.urlencode('section:'.$item['id']);
		if(is_object($content_overlay) && ($label = $content_overlay->get_label('new_command')))
			;
		else
			$label = i18n::s('Add a page');
		$context['page_tools'][] = Skin::build_link($url, ARTICLES_ADD_IMG.$label, 'basic', i18n::s('Add new content to this section'));

		// the command to create a new poll, if no overlay nor template has been defined for content of this section
		if((!isset($item['content_overlay']) || !trim($item['content_overlay'])) && (!isset($item['articles_templates']) || !trim($item['articles_templates'])) && (!is_object($anchor) || !$anchor->get_templates_for('article'))) {

			Skin::define_img('ARTICLES_POLL_IMG', 'articles/poll.gif');
			$url = 'articles/edit.php?anchor='.urlencode('section:'.$item['id']).'&amp;variant=poll';
			$context['page_tools'][] = Skin::build_link($url, ARTICLES_POLL_IMG.i18n::s('Add a poll'), 'basic', i18n::s('Add new content to this section'));
		}

	}

	// add a section
	if(Sections::are_allowed($anchor, $item)) {
		Skin::define_img('SECTIONS_ADD_IMG', 'sections/add.gif');
		$context['page_tools'][] = Skin::build_link('sections/edit.php?anchor='.urlencode('section:'.$item['id']), SECTIONS_ADD_IMG.i18n::s('Add a section'), 'basic', i18n::s('Add a section'));
	}

	// comment this page if anchor does not prevent it
	if(Comments::are_allowed($anchor, $item, 'section')) {
		Skin::define_img('COMMENTS_ADD_IMG', 'comments/add.gif');
		$context['page_tools'][] = Skin::build_link(Comments::get_url('section:'.$item['id'], 'comment'), COMMENTS_ADD_IMG.i18n::s('Post a comment'), 'basic', i18n::s('Express yourself, and say what you think.'));
	}

	// attach a file, if upload is allowed
	if(Files::are_allowed($anchor, $item, 'section')) {
		Skin::define_img('FILES_UPLOAD_IMG', 'files/upload.gif');
		$context['page_tools'][] = Skin::build_link('files/edit.php?anchor='.urlencode('section:'.$item['id']), FILES_UPLOAD_IMG.i18n::s('Upload a file'), 'basic', i18n::s('Attach related files.'));
	}

	// add a link
	if(Links::are_allowed($anchor, $item, 'section')) {
		Skin::define_img('LINKS_ADD_IMG', 'links/add.gif');
		$context['page_tools'][] = Skin::build_link('links/edit.php?anchor='.urlencode('section:'.$item['id']), LINKS_ADD_IMG.i18n::s('Add a link'), 'basic', i18n::s('Contribute to the web and link to relevant pages.'));
	}

	// post an image, if upload is allowed
	if(Images::are_allowed($anchor, $item, 'section')) {
		Skin::define_img('IMAGES_ADD_IMG', 'images/add.gif');
		$context['page_tools'][] = Skin::build_link('images/edit.php?anchor='.urlencode('section:'.$item['id']), IMAGES_ADD_IMG.i18n::s('Add an image'), 'basic', i18n::s('You can upload a camera shot, a drawing, or another image file.'));
	}

	// commands for section owners
	if(Sections::is_owned($anchor, $item)) {

		// modify this page
		Skin::define_img('SECTIONS_EDIT_IMG', 'sections/edit.gif');
		if(!is_object($overlay) || (!$label = $overlay->get_label('edit_command')))
			$label = i18n::s('Edit this section');
		$context['page_tools'][] = Skin::build_link(Sections::get_url($item['id'], 'edit'), SECTIONS_EDIT_IMG.$label, 'basic', i18n::s('Press [e] to edit'), FALSE, 'e');

		// access previous versions, if any
		if($has_versions) {
			Skin::define_img('SECTIONS_VERSIONS_IMG', 'sections/versions.gif');
			$context['page_tools'][] = Skin::build_link(Versions::get_url('section:'.$item['id'], 'list'), SECTIONS_VERSIONS_IMG.i18n::s('Versions'), 'basic', i18n::s('Restore a previous version if necessary'));
		}

		// lock the page
		if(!isset($item['locked']) || ($item['locked'] == 'N')) {
			Skin::define_img('SECTIONS_LOCK_IMG', 'sections/lock.gif');
			$context['page_tools'][] = Skin::build_link(Sections::get_url($item['id'], 'lock'), SECTIONS_LOCK_IMG.i18n::s('Lock'), 'basic');
		} else {
			Skin::define_img('SECTIONS_UNLOCK_IMG', 'sections/unlock.gif');
			$context['page_tools'][] = Skin::build_link(Sections::get_url($item['id'], 'lock'), SECTIONS_UNLOCK_IMG.i18n::s('Unlock'), 'basic');
		}

		// delete the page
		Skin::define_img('SECTIONS_DELETE_IMG', 'sections/delete.gif');
		$context['page_tools'][] = Skin::build_link(Sections::get_url($item['id'], 'delete'), SECTIONS_DELETE_IMG.i18n::s('Delete this section'), 'basic');

		// manage content
		if($has_content) {
			Skin::define_img('SECTIONS_MANAGE_IMG', 'sections/manage.gif');
			$context['page_tools'][] = Skin::build_link(Sections::get_url($item['id'], 'manage'), SECTIONS_MANAGE_IMG.i18n::s('Manage content'), 'basic', i18n::s('Bulk operations'));
		}

		// assign editors
		if(Sections::is_owned($anchor, $item)) {
			Skin::define_img('SECTIONS_ASSIGN_IMG', 'sections/assign.gif');
			$context['page_tools'][] = Skin::build_link(Users::get_url('section:'.$item['id'], 'select'), SECTIONS_ASSIGN_IMG.i18n::s('Manage editors'));
		}

	}

	//
	// reload this page if it changes
	//
	$context['page_footer'] .= JS_PREFIX
		."\n"
		.'// reload this page on update'."\n"
		.'var PeriodicalCheck = {'."\n"
		."\n"
		.'	url: "'.$context['url_to_home'].$context['url_to_root'].Sections::get_url($item['id'], 'check').'",'."\n"
		.'	timestamp: '.SQL::strtotime($item['edit_date']).','."\n"
		."\n"
		.'	initialize: function() { },'."\n"
		."\n"
		.'	subscribe: function() {'."\n"
		.'		this.ajax = new Ajax.Request(PeriodicalCheck.url, {'."\n"
		.'			method: "get",'."\n"
		.'			requestHeaders: {Accept: "application/json"},'."\n"
		.'			onSuccess: PeriodicalCheck.updateOnSuccess,'."\n"
		.'			onFailure: PeriodicalCheck.updateOnFailure });'."\n"
		.'	},'."\n"
		."\n"
		.'	updateOnSuccess: function(transport) {'."\n"
		.'		var response = transport.responseText.evalJSON(true);'."\n"
		.'		// page has been updated'."\n"
		.'		if(PeriodicalCheck.timestamp && response["timestamp"] && (PeriodicalCheck.timestamp != response["timestamp"])) {'."\n"
		.'			// reflect updater name in window title'."\n"
		.'			if(typeof this.windowOriginalTitle != "string")'."\n"
		.'				this.windowOriginalTitle = document.title;'."\n"
		.'			document.title = "[" + response["name"] + "] " + this.windowOriginalTitle;'."\n"
		.'			// smart reload of the page'."\n"
		.'			new Ajax.Updater( { success: $$("body")[0] }, window.location, { method: "get" } );'."\n"
		.'		}'."\n"
		.'		// wait for more time'."\n"
		.'		setTimeout("PeriodicalCheck.subscribe()", 120000);'."\n"
		.'	},'."\n"
		."\n"
		.'	updateOnFailure: function(transport) {'."\n"
		.'		setTimeout("PeriodicalCheck.subscribe()", 600000);'."\n"
		.'	}'."\n"
		."\n"
		.'}'."\n"
		."\n"
		.'// look for some page update'."\n"
		.'setTimeout("PeriodicalCheck.subscribe()", 120000);'."\n"
		."\n"
		.JS_SUFFIX;

}

// stamp the page
$last_modified = SQL::strtotime($item['edit_date']);

// at the minimum, consider the date of the last configuration change
if($last_configured = Safe::filemtime('../parameters/control.include.php'))
	$last_modified = max($last_modified, $last_configured);

// render the skin
render_skin($last_modified);

?>