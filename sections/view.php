<?php
/**
 * view one section
 *
 * The main panel has following elements:
 * - top icons, if any --set in sub-section
 * - the section itself, with details, introduction, and main text.
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
 * If the item is not public, a meta attribute is added to prevent search engines from presenting
 * cached versions of the page to end users.
 *
 * @link http://www.gsadeveloper.com/category/google-mini/page/2/
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
 * 'hardboiled'|[script]articles/layout_articles_as_hardboiled.php[/script]
 * 'compact'|[script]articles/layout_articles_as_compact.php[/script]
 * 'daily'|[script]articles/layout_articles_as_daily.php[/script]
 * 'decorated' (also default value)|[script]articles/layout_articles.php[/script]
 * 'jive'|[script]articles/layout_articles_as_jive.php[/script]
 * 'manual'|[script]articles/layout_articles_as_manual.php[/script]
 * 'map'|[script]articles/layout_articles_as_yahoo.php[/script]
 * 'none'|No articles are shown
 * 'table'|[script]articles/layout_articles_as_table.php[/script]
 * 'yabb'|[script]articles/layout_articles_as_yabb.php[/script]
 * custom|[script]articles/layout_articles_as_custom.php[/script] (to load a customized layout)
 * [/table]
 *
 * For example, for the custom layout ##bar## for articles,
 * YACS will attempt to load the script ##articles/layout_articles_as_bar.php##.
 * Edit the section to manually configure the layout ##bar## for content.
 *
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
 * @author Alexis Raimbault
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

// this script can be included into another one, i.e., /index.hp
if(is_readable('../shared/global.php')) {
	include_once '../shared/global.php';
	define('NOT_INCLUDED', true);

// override page title in including page
} else {
	$context['page_title'] = '';
}

// common definitions and initial processing
include_once $context['path_to_root'].'behaviors/behaviors.php';
include_once $context['path_to_root'].'comments/comments.php';		// attached comments and notes
include_once $context['path_to_root'].'images/images.php';			// attached images
include_once $context['path_to_root'].'links/links.php';			// related pages
include_once $context['path_to_root'].'files/files.php';			// attached files
include_once $context['path_to_root'].'servers/servers.php';
include_once $context['path_to_root'].'versions/versions.php';		// back in history
include_once $context['path_to_root'].'sections/section.php';

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
$page = min(max(1,intval($page)), 10);

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
$item = Sections::get($id);

if(!$item) {
    Safe::redirect($context['url_to_root'].'error.php');
}

// get the related overlay, if any
$overlay = NULL;
if(isset($item['overlay']))
	$overlay = Overlay::load($item, 'section:'.$item['id']);

// get the overlay for content of this section, if any
$content_overlay = NULL;
if(isset($item['content_overlay']))
	$content_overlay = Overlay::bind($item['content_overlay']);

$section_overlay = NULL;
if(isset($item['section_overlay']))
        $section_overlay = Overlay::bind($item['section_overlay']);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor = Anchors::get($item['anchor']);

// get related behaviors, if any
$behaviors = NULL;
if(isset($item['id']))
	$behaviors = new Behaviors($item, $anchor);

// current viewed section as object
$cur_section = new section();
$cur_section->item      = $item;
$cur_section->anchor    = $anchor;
$cur_section->overlay   = $overlay;

// editors can do what they want on items anchored here
if(($cur_section->is_assigned() && Surfer::is_member()) || (is_object($anchor) && $anchor->is_assigned()))
	Surfer::empower();

// readers have additional rights
elseif(($cur_section->is_assigned() && Surfer::is_logged()) || (is_object($anchor) && $anchor->is_assigned()))
	Surfer::empower('S');

//
// is this surfer allowed to browse the page?
//

// change default behavior
if(is_object($behaviors) && !$behaviors->allow('sections/view.php', 'section:'.$item['id']))
	$permitted = FALSE;

// check access rights
elseif($cur_section->allows('access'))
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// load the skin, maybe with a variant
load_skin('sections', $anchor, isset($item['options']) ? $item['options'] : '');

// clear the tab we are in
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();
if(isset($item['id']))
	$context['current_focus'][] = 'section:'.$item['id'];

// current item
if(isset($item['id']))
	$context['current_item'] = 'section:'.$item['id'];

// path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();

// page title
if(isset($item['active']) && ($item['active'] == 'R'))
	$context['page_title'] .= RESTRICTED_FLAG;
elseif(isset($item['active']) && ($item['active'] == 'N'))
	$context['page_title'] .= PRIVATE_FLAG;

if(is_object($overlay))
	$context['page_title'] .= $overlay->get_text('title', $item);
elseif(isset($item['title']) && $item['title'])
	$context['page_title'] .= $item['title'];

if(isset($item['locked']) && ($item['locked'] == 'Y') && $cur_section->is_owned())
	$context['page_title'] .= ' '.LOCKED_FLAG;

// insert page family, if any
if(isset($item['family']) && $item['family'])
	$context['page_title'] = FAMILY_PREFIX.'<span id="family">'.$item['family'].'</span> '.FAMILY_SUFFIX.$context['page_title']."\n";

// page language, if any
if(isset($item['language']) && $item['language'] && ($item['language'] != 'none'))
	$context['page_language'] = $item['language'];

// page canonical link
$context['page_link'] = Sections::get_permalink($item);

// is the page on user watch list?
$in_watch_list = 'N';
if(Surfer::is_logged() && isset($item['id'])) {
	if(Members::check('section:'.$item['id'], 'user:'.Surfer::get_id()))
		$in_watch_list = 'Y';
	elseif(Members::check($context['current_focus'], 'user:'.Surfer::get_id()))
		$in_watch_list = 'P';
}

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

// not found -- help web crawlers
if(!isset($item['id'])) {
	include $context['path_to_root'].'error.php';

// permission denied
} elseif(!$permitted) {

	// make it clear to crawlers
	if(Surfer::is_crawler())
		Safe::header('Status: 401 Unauthorized', TRUE, 401);

	// anonymous users are invited to log in or to register
	elseif(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Sections::get_permalink($item)));

	// require access from some owner
	elseif(isset($_REQUEST['requested']) && ($requested = Users::get($_REQUEST['requested'])) && $requested['email']) {

		// prepare the mail message
		$to = Mailer::encode_recipient($requested['email'], $requested['full_name']);
		$subject = sprintf(i18n::c('%s: %s'), i18n::c('Request'), strip_tags($item['title']));
		$message = Sections::build_notification('apply', $item, $overlay);
		$headers = Mailer::set_thread('section:'.$item['id']);

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
} elseif(defined('NOT_INCLUDED') && !$zoom_type && ($page == 1) && $context['self_url'] && strncmp($context['self_url'], $context['page_link'], strlen($context['page_link']))) {
	Safe::header('Status: 301 Moved Permanently', TRUE, 301);
	Safe::header('Location: '.$context['page_link']);
	Logger::error(Skin::build_link($context['page_link']));

// display the section
} else {

	// behaviors can change page menu
	if(is_object($behaviors))
		$context['page_menu'] = $behaviors->add_commands('sections/view.php', 'section:'.$item['id'], $context['page_menu']);

	// remember surfer visit
	Surfer::is_visiting(Sections::get_permalink($item), Codes::beautify_title($item['title']), 'section:'.$item['id'], $item['active']);

	// increment silently the hits counter if not robot, nor associate, nor owner, nor at follow-up page
	if(Surfer::is_crawler() || Surfer::is_associate())
		;
	elseif(isset($item['owner_id']) && Surfer::is($item['owner_id']))
		;
	elseif(!$zoom_type) {
		$item['hits'] = isset($item['hits'])?($item['hits']+1):1;
		Sections::increment_hits($item['id']);
	}

	// initialize the rendering engine
	Codes::initialize($cur_section->get_permalink());

	// neighbours information
	$neighbours = NULL;
	if(Sections::has_option('with_neighbours', $anchor, $item) && is_object($anchor))
		$neighbours = $anchor->get_neighbours('section', $item);

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

	// prevent search engines to present cache versions of this page
	if($item['active'] != 'Y')
		$context['page_header'] .= "\n".'<meta name="robots" content="noarchive" />';

	// add canonical link
	if(!$zoom_type)
		$context['page_header'] .= "\n".'<link rel="canonical" href="'.$cur_section->get_permalink().'" />';

	// a meta link to a feeding page
	$context['page_header'] .= "\n".'<link rel="alternate" href="'.$context['url_to_home'].$context['url_to_root'].Sections::get_url($item['id'], 'feed').'" title="RSS" type="application/rss+xml" />';

	// a meta link to a description page (actually, rdf)
	$context['page_header'] .= "\n".'<link rel="meta" href="'.$context['url_to_home'].$context['url_to_root'].Sections::get_url($item['id'], 'describe').'" title="Meta Information" type="application/rdf+xml" />';

	// implement the trackback interface
	$permanent_link = $cur_section->get_permalink();
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
	$context['page_header'] .= "\n".'<link rel="EditURI" href="'.$context['url_to_home'].$context['url_to_root'].Sections::get_url($item['id'], 'EditURI').'" title="RSD" type="application/rsd+xml" />'."\n";

	// set specific headers
	if(isset($item['introduction']) && $item['introduction'])
		$context['page_meta'] = strip_tags(Codes::beautify_introduction($item['introduction']));
	if(isset($item['create_name']) && $item['create_name'])
		$context['page_author'] = $item['create_name'];
	if(isset($item['edit_date']) && $item['edit_date'])
		$context['page_date'] = $item['edit_date'];

	//
	// set page details -- $context['page_details']
	//

	// do not mention details at follow-up pages, nor to crawlers
	if(!$zoom_type && !Surfer::is_crawler()) {

		// tags, if any
		if(isset($item['tags']))
			$context['page_tags'] =& Skin::build_tags($item['tags']);

		// one detail per line
		$text = '<p class="details">';
		$details = array();

		// add details from the overlay, if any
		if(is_object($overlay) && ($more = $overlay->get_text('details', $item)))
			$details[] = $more;

		// restricted to logged members
		if($item['active'] == 'R')
			$details[] = RESTRICTED_FLAG.i18n::s('Community - Access is granted to any identified surfer');

		// restricted to associates
		if($item['active'] == 'N')
			$details[] = PRIVATE_FLAG.i18n::s('Private - Access is restricted to selected persons');

		// index panel
		if(Surfer::is_empowered() && Surfer::is_logged()) {

			// content of this section is not pushed upwards
			if(isset($item['index_map']) && ($item['index_map'] != 'Y')) {

				// at the parent index page
				if($item['anchor'])
					$details[] = i18n::s('Content does not flow to parent section. Is listed with special sections, but only to associates.');

				// at the site map
				else
					$details[] = i18n::s('Is not publicly listed at the Site Map. Is listed with special sections, but only to associates.');

			}

		}

		// signal sections to be activated
		if(Surfer::is_empowered() && Surfer::is_logged() && ($item['activation_date'] > $context['now']))
			$details[] = DRAFT_FLAG.' '.sprintf(i18n::s('Section will be activated %s'), Skin::build_date($item['activation_date']));

		// expired section
		if(Surfer::is_empowered() && Surfer::is_logged() && ($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
			$details[] = EXPIRED_FLAG.' '.sprintf(i18n::s('Section has expired %s'), Skin::build_date($item['expiry_date']));

		// provide more details to authenticated surfers
		if(Surfer::is_logged()) {

			// section owner
			if(isset($item['owner_id']) && ($owner = Users::get($item['owner_id'])))
				$details[] = sprintf(i18n::s('%s: %s'), i18n::s('Owner'), Users::get_link($owner['full_name'], $owner['email'], $owner['id']));

			// section editors
			if($items = Sections::list_editors_by_name($item, 0, 7, 'comma5'))
				$details[] = sprintf(i18n::s('%s: %s'), Skin::build_link(Users::get_url('section:'.$item['id'], 'select'), i18n::s('Editors')), $items);

			// section watchers
			if($items = Sections::list_watchers_by_posts($item, 0, 7, 'comma5'))
				$details[] = sprintf(i18n::s('%s: %s'), Skin::build_link(Users::get_url('section:'.$item['id'], 'watch'), i18n::s('Watchers')), $items);

		}

		// display details, if any
		if(count($details))
			$text .= ucfirst(implode(BR."\n", $details)).BR."\n";

		// additional details for associates and editors
		if(Surfer::is_empowered()) {

			// other details
			$details =& Sections::build_dates($anchor, $item);

			// the number of hits
			if($item['hits'] > 1)
				$details[] = Skin::build_number($item['hits'], i18n::s('hits'));

			// rank for this section
			if((intval($item['rank']) != 10000) && Sections::is_owned($item, $anchor))
				$details[] = '{'.$item['rank'].'}';

			// locked section
			if($item['locked'] ==  'Y')
				$details[] = LOCKED_FLAG.' '.i18n::s('page is locked.');

			// inline details
			if(count($details))
				$text .= ucfirst(implode(', ', $details));

		}

		// reference this item
		if(Surfer::is_member()) {
			$text .= BR.sprintf(i18n::s('Code to reference this page: %s'), '[section='.$item['id'].']');

			// the nick name
			if($item['nick_name'] && ($link = normalize_shortcut($item['nick_name'], TRUE)))
				$text .= BR.sprintf(i18n::s('Name: %s'), $link);

			// short link
			if($context['with_friendly_urls'] == 'R')
				$text .= BR.sprintf(i18n::s('Shortcut: %s'), $context['url_to_home'].$context['url_to_root'].Sections::get_short_url($item));
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
	if(preg_match('/\bwith_extra_profile\b/', $item['options']) && ($owner = Users::get($item['owner_id'])) && is_object($cur_section))
		$context['components']['profile'] = $cur_section->get_user_profile($owner, 'extra', Skin::build_date($item['create_date']));

	// add extra information from the overlay, if any
	if(is_object($overlay))
		$context['components']['overlay'] = $overlay->get_text('extra', $item);

	// add extra information from this item, if any
	if(isset($item['extra']) && $item['extra'])
		$context['components']['boxes'] .= Codes::beautify_extra($item['extra']);

	// 'Share' box
	//
	$lines = array();

	// facebook, twitter, linkedin
	if(($item['active'] == 'Y') && ((!isset($context['without_internet_visibility']) || ($context['without_internet_visibility'] != 'Y')))) {

		// the best suited link to use
		if($context['with_friendly_urls'] == 'R')
			$url = $context['url_to_home'].$context['url_to_root'].$cur_section->get_short_url ();
		else
			$url = $cur_section->get_permalink ();

		// facebook
		Skin::define_img('PAGERS_FACEBOOK_IMG', 'pagers/facebook.gif');
		$lines[] = Skin::build_link('http://www.facebook.com/share.php?u='.urlencode($url).'&t='.urlencode($item['title']), PAGERS_FACEBOOK_IMG.i18n::s('Post to Facebook'), 'basic', i18n::s('Spread the word'));

		// twitter
		Skin::define_img('PAGERS_TWITTER_IMG', 'pagers/twitter.gif');
		$lines[] = Skin::build_link('http://twitter.com/home?status='.urlencode($item['title'].' '.$url), PAGERS_TWITTER_IMG.i18n::s('Tweet this'), 'basic', i18n::s('Spread the word'));

		// linked in
		Skin::define_img('PAGERS_LINKEDIN_IMG', 'pagers/linkedin.gif');
		$lines[] = Skin::build_link('http://www.linkedin.com/shareArticle?mini=true&url='.$url.'&title='.urlencode($item['title']).'&summary='.urlencode($item['introduction']).'&source='.urlencode(is_object($anchor)?$anchor->get_title():$context['site_name']), PAGERS_LINKEDIN_IMG.i18n::s('Share at LinkedIn'), 'basic', i18n::s('Spread the word'));

	}

	// invite participants
	if(($cur_section->is_owned() || ($item['active'] == 'Y')) && isset($context['with_email']) && ($context['with_email'] == 'Y')) {
		Skin::define_img('SECTIONS_INVITE_IMG', 'sections/invite.gif');
		$lines[] = Skin::build_link(Sections::get_url($item['id'], 'invite'), SECTIONS_INVITE_IMG.i18n::s('Invite participants'), 'basic');
	}

	// notify participants
	if(($cur_section->is_owned() || Surfer::is_associate()) && isset($context['with_email']) && ($context['with_email'] == 'Y')) {
		Skin::define_img('SECTIONS_EMAIL_IMG', 'sections/email.gif');
		$lines[] = Skin::build_link(Sections::get_url($item['id'], 'mail'), SECTIONS_EMAIL_IMG.i18n::s('Notify participants'));
	}

	// manage editors
	if($cur_section->is_owned() || Surfer::is_associate()) {
		Skin::define_img('SECTIONS_ASSIGN_IMG', 'sections/assign.gif');
		$lines[] = Skin::build_link(Users::get_url('section:'.$item['id'], 'select'), SECTIONS_ASSIGN_IMG.i18n::s('Manage participants'));
	}

	// the command to track back
	if(Links::allow_trackback()) {
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
		$context['components']['share'] = Skin::build_box(i18n::s('Share'), Skin::finalize_list($lines, 'newlines'), 'share', 'share');

	// 'Monitor' box
	$lines = array();

	// watch command is provided to logged surfers
	if(Surfer::get_id() && !$zoom_type) {

		$link = Users::get_url('section:'.$item['id'], 'track');

		if($in_watch_list == 'Y')
			$label = i18n::s('Stop notifications');
		elseif($in_watch_list == 'N')
			$label = i18n::s('Watch this section');

		if($in_watch_list != 'P') {
			Skin::define_img('TOOLS_WATCH_IMG', 'tools/watch.gif');
			$lines[] = Skin::build_link($link, TOOLS_WATCH_IMG.$label, 'basic', i18n::s('Manage your watch list'));
		}
	}

	// allow to leave the section
	if($cur_section->is_assigned() && !$cur_section->is_owned(null, false)) {
		Skin::define_img('SECTIONS_ASSIGN_IMG', 'sections/assign.gif');
		$lines[] = Skin::build_link(Users::get_url('section:'.$item['id'], 'leave'), SECTIONS_ASSIGN_IMG.i18n::s('Leave this section'));
	}

	// get news from rss
	if(isset($item['id']) && (!isset($context['skins_general_without_feed']) || ($context['skins_general_without_feed'] != 'Y')) ) {

		$lines[] = Skin::build_link($context['url_to_home'].$context['url_to_root'].Sections::get_url($item['id'], 'feed'), i18n::s('Recent pages'), 'xml');

		$lines[] = Skin::build_link($context['url_to_home'].$context['url_to_root'].Files::get_url('section:'.$item['id'], 'feed'), i18n::s('Recent files'), 'xml');

		// comments are allowed
		if($cur_section->allows('creation','comment')) {
			$lines[] = Skin::build_link($context['url_to_home'].$context['url_to_root'].Comments::get_url('section:'.$item['id'], 'feed'), i18n::s('Recent comments'), 'xml');
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
		$items = Sections::list_for_name($item['nick_name'], $item['id'], 'compact');

		// actually render the html for the section
		if(is_array($items))
			$box['text'] .= Skin::build_list($items, 'compact');
		if($box['text'])
			$context['components']['twins'] = Skin::build_box(i18n::s('Related'), $box['text'], 'twins', 'twins');

	}

	// the contextual menu, in a navigation box, if this has not been disabled
	if( (!is_object($anchor) || !$anchor->has_option('no_contextual_menu', FALSE))
		&& (!isset($item['options']) || !preg_match('/\bno_contextual_menu\b/i', $item['options']))
		&& isset($context['current_focus']) && ($menu =& Skin::build_contextual_menu($context['current_focus']))) {

		// use title from topmost level
		if(count($context['current_focus']) && ($topmost = Anchors::get($context['current_focus'][0]))) {
			$box_title = $topmost->get_title();
			$box_url = $topmost->get_url();

		// generic title
		} else {
			$box_title = i18n::s('Navigation');
			$box_url = '';
		}

		// in a navigation box
		$box_popup = '';
		$context['components']['contextual'] = Skin::build_box($box_title, $menu, 'contextual', 'contextual_menu', $box_url, $box_popup)."\n";
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
		if(Categories::allow_assign($item,$anchor))
			$items = array_merge($items, array( Categories::get_url('section:'.$item['id'], 'select') => i18n::s('Assign categories') ));

		// actually render the html for the section
		if(@count($box['bar']))
			$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');
		if(is_array($items))
			$box['text'] .= Skin::build_list($items, 'compact');
		if($box['text'])
			$context['components']['categories'] = Skin::build_box(i18n::s('See also'), $box['text'], 'categories', 'categories');

	}

	// offer bookmarklets if submissions are allowed -- complex command
	if(Surfer::has_all() && (!isset($context['pages_without_bookmarklets']) || ($context['pages_without_bookmarklets'] != 'Y'))) {

		// accessible bookmarklets
		$bookmarklets = array();

		// blogging bookmarklet uses YACS codes
		if($cur_section->allows('creation','article')) {
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
		if($cur_section->allows('creation','link')) {
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

			$context['components']['bookmarklets'] = Skin::build_box(i18n::s('Bookmarklets to contribute'), $label, 'bookmarklets', 'bookmarklets');
		}
	}

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
		include $context['path_to_root'].'sections/'.$matches[0].'.php';
		return;
	} elseif(is_object($anchor) && ($viewer = $anchor->has_option('view_as')) && is_readable('view_as_'.$viewer.'.php')) {
		include $context['path_to_root'].'sections/view_as_'.$viewer.'.php';
		return;
	} elseif(is_array($context['tabs']) && count($context['tabs'])) {
		include $context['path_to_root'].'sections/view_as_tabs.php';
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

			// buttons to display previous and next pages, if any
			if($neighbours)
				$text .= Skin::neighbours($neighbours, 'manual');

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
			$page = max(min($page, count($pages)), 1);
			$description = $pages[ $page-1 ];

			// if there are several pages, remove toc and toq codes
			if(count($pages) > 1)
				$description = preg_replace('/\s*\[(toc|toq)\]\s*/is', '', $description);

			// beautify the target page
			$text .= Skin::build_block($description, 'description', '', $item['options']);

			// if there are several pages, add navigation commands to browse them
			if(count($pages) > 1) {
				$page_menu = array( '_' => i18n::s('Pages') );
				$home = Sections::get_permalink($item);
				$prefix = Sections::get_url($item['id'], 'navigate', 'articles');
				$page_menu = array_merge($page_menu, Skin::navigate($home, $prefix, count($pages), 1, $page));

				$text .= Skin::build_list($page_menu, 'menu_bar');
			}

		}

	}

	//
	// articles related to this section, or to sub-sections
	//

	// the list of related articles if not at another follow-up page
	if(!$zoom_type || ($zoom_type == 'articles')) {

		// delegate rendering to the overlay, where applicable
		if(is_object($content_overlay) && ($overlaid = $content_overlay->render('articles', 'section:'.$item['id'], $zoom_index))) {
			$text .= $overlaid;

		// regular rendering
		} elseif(!isset($item['articles_layout']) || ($item['articles_layout'] != 'none')) {

			// select a layout
			if(!isset($item['articles_layout']) || !$item['articles_layout']) {
				include_once $context['path_to_root'].'articles/layout_articles.php';
				$layout = new Layout_articles();
			} else
				$layout = Layouts::new_($item['articles_layout'], 'article');

			// avoid links to this page
			if(is_object($layout))
				$layout->set_focus('section:'.$item['id']);

			// the maximum number of articles per page
			if(is_object($layout))
				$items_per_page = $layout->items_per_page();
			else
				$items_per_page = ARTICLES_PER_PAGE;

			// sort and list articles
			$offset = ($zoom_index - 1) * $items_per_page;
			if(preg_match('/\barticles_by_([a-z_]+)\b/i', $item['options'], $matches))
				$order = $matches[1];
			elseif(is_callable(array($layout, 'items_order')))
				$order = $layout->items_order();
			else
				$order = 'edition';

			// create a box
			$box = array('top_bar' => array(), 'text' => '', 'bottom_bar' => array());

			// the command to post a new page
			if($cur_section->allows('creation','article')) {

				Skin::define_img('ARTICLES_ADD_IMG', 'articles/add.gif');
				$url = 'articles/edit.php?anchor='.urlencode('section:'.$item['id']);
				if(is_object($content_overlay) && ($label = $content_overlay->get_label('new_command', 'article')))
					;
				else
					$label = ARTICLES_ADD_IMG.i18n::s('Add a page');
				$box['top_bar'] += array( $url => $label );

			}

			// list pages under preparation
			$this_section = new section;
			$this_section->load_by_content($item, $anchor);
			if($this_section->is_assigned()) {
				if(($order == 'publication') && ($items =& Articles::list_for_anchor_by('draft', 'section:'.$item['id'], 0, 20, 'compact'))) {
					if(is_array($items))
						$items = Skin::build_list($items, 'compact');
					$box['top_bar'] += array('_draft' => Skin::build_sliding_box(i18n::s('Draft pages'), $items));
				}
			}

			// top menu
			if($box['top_bar'])
				$box['text'] .= Skin::build_list($box['top_bar'], 'menu_bar');

			// get pages
			$items =& Articles::list_for_anchor_by($order, 'section:'.$item['id'], $offset, $items_per_page, $layout);

			// items in the middle
			if(is_array($items) && isset($item['articles_layout']) && ($item['articles_layout'] == 'compact'))
				$box['text'] .= Skin::build_list($items, 'compact');
			elseif(is_array($items))
				$box['text'] .= Skin::build_list($items, 'decorated');
			elseif(is_string($items))
				$box['text'] .= $items;

			// no navigation bar with alistapart
			if(!isset($item['articles_layout']) || ($item['articles_layout'] != 'alistapart')) {

				// count the number of articles in this section
				if($count = Articles::count_for_anchor('section:'.$item['id'])) {
					if($count > 20)
						$box['bottom_bar'] += array('_count' => sprintf(i18n::ns('%d page', '%d pages', $count), $count));

					// navigation commands for articles
					$home = Sections::get_permalink($item);
					$prefix = Sections::get_url($item['id'], 'navigate', 'articles');
					$box['bottom_bar'] += Skin::navigate($home, $prefix, $count, $items_per_page, $zoom_index);

				}

			}

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
					include_once $context['path_to_root'].'articles/layout_articles.php';
					$layout = new Layout_articles();
				} else
					$layout = Layouts::new_ ($item['articles_layout'], 'article');

				// avoid links to this page
				if(is_object($layout))
					$layout->set_focus('section:'.$item['id']);

				// the maximum number of articles per page
				if(is_object($layout))
					$items_per_page = $layout->items_per_page();
				else
					$items_per_page = ARTICLES_PER_PAGE;

				// sub-sections targeting the main area
				if($anchors = Sections::get_branch_at_anchor('section:'.$item['id'])) {

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

	// show hidden articles to associates and editors
	} elseif( (!$zoom_type || ($zoom_type == 'articles'))
		&& isset($item['articles_layout']) && ($item['articles_layout'] == 'none')
		&& Surfer::is_empowered() ) {

		// make a compact list		
		$layout = Layouts::new_('compact', 'article');

		// avoid links to this page
		if(is_object($layout))
			$layout->set_focus('section:'.$item['id']);

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

		// list files only to people able to change the page
		if($cur_section->allows('modification'))
			$embedded = NULL;
		else
			$embedded = Codes::list_embedded($item['description']);

		// build a complete box
		$box = array('bar' => array(), 'text' => '');

		// count the number of files in this section
		//if($count = Files::count_for_anchor('section:'.$item['id'], FALSE, $embedded)) {
                $count = Files::count_for_anchor('section:'.$item['id'], FALSE, $embedded);
                
                if($count > 20)
                        $box['bar'] += array('_count' => sprintf(i18n::ns('%d file', '%d files', $count), $count));

                // get overlay for files if any
                $file_overlay = NULL;
                if(isset($item['file_overlay']))
                        $file_overlay = Overlay::bind($item['file_overlay']);

                // delegate rendering to the overlay, where applicable
                if(is_object($file_overlay) && ($overlaid = $file_overlay->render('files', 'section:'.$item['id'], $zoom_index))) {
                    $box['text'] .= $overlaid;
                } elseif($count) {

                    // list files by date (default) or by title (option 'files_by_title')
                    $offset = ($zoom_index - 1) * FILES_PER_PAGE;
                    if(preg_match('/\bfiles_by_title\b/i', $item['options']))
                            $items = Files::list_by_title_for_anchor('section:'.$item['id'], $offset, FILES_PER_PAGE, 'section:'.$item['id'], $embedded);
                    else
                            $items = Files::list_by_date_for_anchor('section:'.$item['id'], $offset, FILES_PER_PAGE, 'section:'.$item['id'], $embedded);

                    // actually render the html
                    if(is_array($items))
                            $box['text'] .= Skin::build_list($items, 'decorated');
                    elseif(is_string($items))
                            $box['text'] .= $items;
                }

                // navigation commands for files
                $home = Sections::get_permalink($item);
                $prefix = Sections::get_url($item['id'], 'navigate', 'files');
                $box['bar'] = array_merge($box['bar'],
                        Skin::navigate($home, $prefix, $count, FILES_PER_PAGE, $zoom_index));

		// the command to post a new file -- check 'with_files' option
		if($cur_section->allows('creation','file')) {
                        
                        // get add button label
                        if(!is_object($file_overlay) || !$add_label = $file_overlay->get_label('new_command','file')) {
                            Skin::define_img('FILES_UPLOAD_IMG', 'files/upload.gif');
                            $add_label = FILES_UPLOAD_IMG.i18n::s('Add a file');
                        }
                        
                    
			Skin::define_img('FILES_UPLOAD_IMG', 'files/upload.gif');
			$box['bar'] += array('files/edit.php?anchor='.urlencode('section:'.$item['id']) => $add_label );
		}

		// integrate the nemu bar
		if(count($box['bar']))
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
		$title_label = '';
		if(is_object($overlay))
			$title_label = $overlay->get_label('list_title', 'comments');
		if(!$title_label)
			$title_label = i18n::s('Comments');

		// get a layout for comments of this item
		$layout =& Comments::get_layout($anchor, $item);

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

		// feed the wall
		if($cur_section->allows('creation','comment'))
			$box['text'] .= Comments::get_form('section:'.$item['id']);

		// a navigation bar for these comments
		if($zoom_type && ($zoom_type == 'comments'))
			$link = '_count';
		if($count = Comments::count_for_anchor('section:'.$item['id'])) {
			if($count > 20)
				$box['bar'] += array($link => sprintf(i18n::s('%d comments'), $count));

			// list comments by date
			$items = Comments::list_by_date_for_anchor('section:'.$item['id'], $offset, $items_per_page, $layout, TRUE);

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

		// show commands
		if(count($box['bar']))
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
			if($count > 20)
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
			$home = Sections::get_permalink($item);
			$prefix = Sections::get_url($item['id'], 'navigate', 'links');
			$box['bar'] = array_merge($box['bar'],
				Skin::navigate($home, $prefix, $count, LINKS_PER_PAGE, $zoom_index));

		}

		// new links are allowed -- check option 'with_links'
		if($cur_section->allows('creation','link')) {
			Skin::define_img('LINKS_ADD_IMG', 'links/add.gif');
			$box['bar'] += array('links/edit.php?anchor='.urlencode('section:'.$item['id']) => LINKS_ADD_IMG.i18n::s('Add a link') );
		}

		// integrate the menu bar at the end
		if(count($box['bar']))
			$box['text'] = Skin::build_list($box['bar'], 'menu_bar').$box['text'];

		// build a box
		if(trim($box['text']))
			$text .= Skin::build_box(i18n::s('Links'), $box['text'], 'header1', 'links');

	}

	//
	// sub-sections, if any
	//

	// the list of related sections if not at another follow-up page
	if(!$zoom_type || ($zoom_type == 'sections')) {

		// layout sub-sections
		if(!isset($item['sections_layout']) || ($item['sections_layout'] != 'none')) {

			// select a layout
			if(!isset($item['sections_layout']) || !$item['sections_layout']) {
				include_once $context['path_to_root'].'sections/layout_sections.php';
				$layout = new Layout_sections();
			} else
				$layout = Layouts::new_($item['sections_layout'], 'section');
				

			// the maximum number of sections per page
			if(is_object($layout))
				$items_per_page = $layout->items_per_page();
			else
				$items_per_page = SECTIONS_PER_PAGE;

			// build a complete box
			$box = array('top_bar' => array(), 'text' => '', 'bottom_bar' => array());

			// the command to add a new section
			if($cur_section->allows('creation','section')) {
                            
                                if(is_object($section_overlay) && ($label = $section_overlay->get_label('new_command', 'section'))) {
                                      ;
                                } else {
                                    $label = i18n::s('Add a section');
                                }
                            
				Skin::define_img('SECTIONS_ADD_IMG', 'sections/add.gif');
				$box['top_bar'] += array('sections/edit.php?anchor='.urlencode('section:'.$item['id']) => SECTIONS_ADD_IMG.$label);
			}

			// list items by family then title
			$offset = ($zoom_index - 1) * $items_per_page;
			$items = Sections::list_by_title_for_anchor('section:'.$item['id'], $offset, $items_per_page, $layout, TRUE);

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

			// count the number of subsections
			if($count = Sections::count_for_anchor('section:'.$item['id'])) {

				if($count > 20)
					$box['bottom_bar'] = array('_count' => sprintf(i18n::ns('%d section', '%d sections', $count), $count));

				// navigation commands for sections
				$home = Sections::get_permalink($item);
				$prefix = Sections::get_url($item['id'], 'navigate', 'sections');
				$box['bottom_bar'] += Skin::navigate($home, $prefix, $count, $items_per_page, $zoom_index);

			}

			// bottom menu
			if($box['bottom_bar'])
				$box['text'] .= Skin::build_list($box['bottom_bar'], 'menu_bar');

			// there is some box content
			if($box['text'])
				$text .= $box['text'];

		}
	}

	//
	// inactive sub sections
	//

	// associates may list special sections as well
	if(!$zoom_type && Surfer::is_empowered()) {

		// inactive sections, if any
		$items = Sections::list_inactive_by_title_for_anchor('section:'.$item['id'], 0, 50, 'compact');

		// we have an array to format
		if(count($items))
			$items =& Skin::build_list($items, 'compact');

		// displayed as another box
		if($items)
			$context['page_menu'] += array('_other_sections' => Skin::build_sliding_box(i18n::s('Other sections'), $items, NULL, TRUE, TRUE));

	}

	//
	// trailer information
	//

	// add trailer information from the overlay, if any
	if(is_object($overlay))
		$text .= $overlay->get_text('trailer', $item);

	// add trailer information from this item, if any
	if(isset($item['trailer']) && trim($item['trailer']))
		$text .= Codes::beautify($item['trailer']);

	// buttons to display previous and next pages, if any
	if($neighbours)
		$text .= Skin::neighbours($neighbours, 'manual');

	// insert anchor suffix
	if(is_object($anchor))
		$text .= $anchor->get_suffix();

	// update the main content panel
	$context['text'] .= $text;

	//
	// extra panel
	//

	// page tools
	//

	// commands to add pages
	if($cur_section->allows('creation','article')) {

		Skin::define_img('ARTICLES_ADD_IMG', 'articles/add.gif');
		$url = 'articles/edit.php?anchor='.urlencode('section:'.$item['id']);
		if(is_object($content_overlay) && ($label = $content_overlay->get_label('new_command', 'articles')))
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
	if($cur_section->allows('creation','section')) {
		Skin::define_img('SECTIONS_ADD_IMG', 'sections/add.gif');
		$context['page_tools'][] = Skin::build_link('sections/edit.php?anchor='.urlencode('section:'.$item['id']), SECTIONS_ADD_IMG.i18n::s('Add a section'), 'basic', i18n::s('Add a section'));
	}

	// comment this page if anchor does not prevent it
	if($cur_section->allows('creation','comment')) {
		Skin::define_img('COMMENTS_ADD_IMG', 'comments/add.gif');
		$context['page_tools'][] = Skin::build_link(Comments::get_url('section:'.$item['id'], 'comment'), COMMENTS_ADD_IMG.i18n::s('Post a comment'), 'basic', i18n::s('Express yourself, and say what you think.'));
	}

	// add a file, if upload is allowed
	if($cur_section->allows('creation','file')) {
		Skin::define_img('FILES_UPLOAD_IMG', 'files/upload.gif');
		$context['page_tools'][] = Skin::build_link('files/edit.php?anchor='.urlencode('section:'.$item['id']), FILES_UPLOAD_IMG.i18n::s('Add a file'), 'basic', i18n::s('Attach related files.'));
	}

	// add a link
	if($cur_section->allows('creation','link')) {
		Skin::define_img('LINKS_ADD_IMG', 'links/add.gif');
		$context['page_tools'][] = Skin::build_link('links/edit.php?anchor='.urlencode('section:'.$item['id']), LINKS_ADD_IMG.i18n::s('Add a link'), 'basic', i18n::s('Contribute to the web and link to relevant pages.'));
	}

	// post an image, if upload is allowed
	if($cur_section->allows('creation','image')) {
		Skin::define_img('IMAGES_ADD_IMG', 'images/add.gif');
		$context['page_tools'][] = Skin::build_link('images/edit.php?anchor='.urlencode('section:'.$item['id']), IMAGES_ADD_IMG.i18n::s('Add an image'), 'basic', i18n::s('You can upload a camera shot, a drawing, or another image file.'));
	}

	// ensure that the surfer can change content
	if($cur_section->allows('modification')) {

		// modify this section
		Skin::define_img('SECTIONS_EDIT_IMG', 'sections/edit.gif');
		if(!is_object($overlay) || (!$label = $overlay->get_label('edit_command', 'sections')))
			$label = i18n::s('Edit this section');
		$context['page_tools'][] = Skin::build_link(Sections::get_url($item['id'], 'edit'), SECTIONS_EDIT_IMG.$label, 'basic', i18n::s('Press [e] to edit'), FALSE, 'e');

	}

	// commands for section owners
	if($cur_section->is_owned() || Surfer::is_associate()) {

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
                if($cur_section->allows('deletion')) {
                    Skin::define_img('SECTIONS_DELETE_IMG', 'sections/delete.gif');
                    if(!is_object($overlay) || (!$label = $overlay->get_label('delete_command', 'sections')))
                            $label = i18n::s('Delete this section');
                    $context['page_tools'][] = Skin::build_link(Sections::get_url($item['id'], 'delete'), SECTIONS_DELETE_IMG.$label, 'basic');
                }

		// manage content
		if($has_content) {
			Skin::define_img('SECTIONS_MANAGE_IMG', 'sections/manage.gif');
			$context['page_tools'][] = Skin::build_link(Sections::get_url($item['id'], 'manage'), SECTIONS_MANAGE_IMG.i18n::s('Manage content'), 'basic', i18n::s('Bulk operations'));
		}

		// duplicate command provided to container owners
		if(Sections::is_owned(NULL, $anchor) || Surfer::is_associate()) {
			Skin::define_img('SECTIONS_DUPLICATE_IMG', 'sections/duplicate.gif');
			$context['page_tools'][] = Skin::build_link(Sections::get_url($item['id'], 'duplicate'), SECTIONS_DUPLICATE_IMG.i18n::s('Duplicate this section'));
		}

	}

	// commands for associates
	if(Surfer::is_associate()) {
		Skin::define_img('SECTIONS_DUPLICATE_IMG', 'sections/duplicate.gif');
		$context['page_tools'][] = Skin::build_link(Sections::get_url($item['id'], 'export'), SECTIONS_DUPLICATE_IMG.i18n::s('Export this section'));
	}

}

// render the skin
render_skin();
?>
