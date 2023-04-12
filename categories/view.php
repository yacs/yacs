<?php
/**
 * view one category
 *
 * The main panel has following elements:
 * - The category itself, with details, introduction, and main text.
 * - The list of sub-categories
 * - The list of related sections
 * - The list of related articles
 * - The list of related files
 * - The list of comments
 * - The list of related links
 * - The list of related users
 *
 * The extra panel has following elements:
 * - A bookmarklet to post bookmarks at this category
 * - A link to the related rss feed, as an extra box
 * - A list of anchored (and feeding) servers, into a sidebar
 * - A search form into a sidebar, if the category has some keyword
 * - Means to reference this page, into a sidebar box
 * - The top popular referrals, if any
 *
 * Several HTTP headers, or &lt;meta&gt; attributes of the displayed page, are set dynamically
 * to help advanced web usage. This includes:
 * - a link to a RDF description of this page (e.g., '&lt;link rel="alternate" href="http://127.0.0.1/yacs/sections/describe.php/4310" title="rdf" type="application/rdf+xml" /&gt;')
 * - a link to a RSS feed for this category (e.g., '&lt;link rel="alternate" href="http://127.0.0.1/yacs/categories/feed.php/4038" title="RSS" type="application/rss+xml" /&gt;')
 * - a rdf section implementing the [link=trackback]http://www.movabletype.org/docs/mttrackback.html[/link] interface
 * - a [link=pingback]http://www.hixie.ch/specs/pingback/pingback[/link] link (e.g., '&lt;link rel="pingback" href="http://here/yacs/services/pingback.php" /&gt;')
 *
 * If the item is not public, a meta attribute is added to prevent search engines from presenting
 * cached versions of the page to end users.
 *
 * @link http://www.gsadeveloper.com/category/google-mini/page/2/
 *
 * Restrictions apply on this page:
 * - associates and editors are allowed to move forward
 * - permission is denied if the anchor is not viewable
 * - access is restricted ('active' field == 'R'), but the surfer is an authenticated member
 * - public access is allowed ('active' field == 'Y')
 * - permission denied is the default
 *
 * Accept following invocations:
 * - view.php/12 (view the first page of the category document)
 * - view.php?id=12 (view the first page of the category document)
 * - view.php/12/categories/1 (view the page 1 of the list of related categories)
 * - view.php?id=12&categories=1 (view the page 1 of the list of related categories)
 * - view.php/12/articles/1 (view the page 1 of the list of related articles)
 * - view.php?id=12&articles=1 (view the page 1 of the list of related articles)
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
 * @author Bernard Paques
 * @author GnapZ
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @author Alexis Raimbault
 * @tester Mark
 * @tester Moi-meme
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../comments/comments.php';
include_once '../images/images.php';
include_once '../links/links.php';
include_once '../servers/servers.php';

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

// zoom, if any
$zoom_type = NULL;
$zoom_index = 1;

// view.php?id=12&categories=2
if(isset($_REQUEST['categories'])  && ($zoom_index = $_REQUEST['categories']))
	$zoom_type = 'categories';

// view.php?id=12&sections=2
elseif(isset($_REQUEST['sections'])  && ($zoom_index = $_REQUEST['sections']))
	$zoom_type = 'sections';

// view.php?id=12&articles=2
elseif(isset($_REQUEST['articles'])  && ($zoom_index = $_REQUEST['articles']))
	$zoom_type = 'articles';

// view.php?id=12&comments=2
elseif(isset($_REQUEST['comments'])  && ($zoom_index = $_REQUEST['comments']))
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
$item = Categories::get($id);
// object interface
$this_cat = new Category($item);

// get the related anchor, if any
$anchor = $this_cat->anchor;

// get the related overlay, if any
$overlay = $this_cat->overlay;

// get related behaviors, if any
$behaviors = NULL;
if(isset($item['id']))
	$behaviors = new Behaviors($item, $anchor);

// change default behavior
if(is_object($behaviors) && !$behaviors->allow('categories/view.php', $this_cat))
	$permitted = FALSE;

// associates and editors can do what they want
elseif(Surfer::is_associate() || (is_object($anchor) && $anchor->is_assigned()))
	$permitted = TRUE;

// the anchor has to be viewable by this surfer
elseif(is_object($anchor) && !$anchor->is_viewable())
	$permitted = FALSE;

// access is restricted to authenticated member
elseif(($item['active'] == 'R') && Surfer::is_member())
	$permitted = TRUE;

// public access is allowed
elseif($item['active'] == 'Y')
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// load the skin, maybe with a variant
load_skin('categories', $anchor, isset($item['options']) ? $item['options'] : '');

// display the tab we are in
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// current item
if(isset($item['id']))
	$context['current_item'] = 'category:'.$item['id'];
$context['current_action'] = 'view';

// path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'categories/' => i18n::s('Categories') );

// page title
if(isset($item['active']) && ($item['active'] == 'R'))
	$context['page_title'] .= RESTRICTED_FLAG;
elseif(isset($item['active']) && ($item['active'] == 'N'))
	$context['page_title'] .= PRIVATE_FLAG;
if(is_object($overlay))
	$context['page_title'] .= $overlay->get_text('title', $item);
elseif(isset($item['title']))
	$context['page_title'] .= $item['title'];
if(isset($item['locked']) && ($item['locked'] == 'Y') && Surfer::is_logged())
	$context['page_title'] .= ' '.LOCKED_FLAG;

// set title background
if(isset($item['background_color']) && $item['background_color'])
	$context['page_title'] = '<span style="background-color: '.$item['background_color'].'; padding: 0 2px 0 2px;">'.$context['page_title'].'</span>';

// not found -- help web crawlers
if(!isset($item['id'])) {
	include '../error.php';

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Categories::get_permalink($item)));

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// re-enforce the canonical link
} elseif(!$zoom_type && $context['self_url'] && ($canonical = Categories::get_permalink($item)) && strncmp($context['self_url'], $canonical, strlen($canonical))) {
	Safe::header('Status: 301 Moved Permanently', TRUE, 301);
	Safe::header('Location: '.$canonical);
	Logger::error(Skin::build_link($canonical));

// display the category
} else {

	// remember surfer visit
	Surfer::is_visiting(Categories::get_permalink($item), Codes::beautify_title($item['title']), 'category:'.$item['id'], $item['active']);

	// increment silently the hits counter if not robot, nor associate, nor creator, nor at follow-up page
	if(Surfer::is_crawler() || Surfer::is_associate())
		;
	elseif(Surfer::get_id() && isset($item['create_id']) && (Surfer::get_id() == $item['create_id']))
		;
	elseif(!$zoom_type) {
		$item['hits'] = isset($item['hits'])?($item['hits']+1):1;
		Categories::increment_hits($item['id']);
	}

	// initialize the rendering engine
	Codes::initialize(Categories::get_permalink($item));

	//
	// page image -- $context['page_image']
	//

	// the category or the anchor icon, if any
	if(isset($item['icon_url']) && $item['icon_url'])
		$context['page_image'] = $item['icon_url'];
	elseif(is_object($anchor))
		$context['page_image'] = $anchor->get_icon_url();

	//
	// page meta information -- $context['page_header'], etc.
	//

	// prevent search engines to present cache versions of this page
	if($item['active'] != 'Y')
		$context['page_header'] .= "\n".'<meta name="robots" content="noarchive" />';

	// add canonical link
	if(!$zoom_type)
		$context['page_header'] .= "\n".'<link rel="canonical" href="'.Categories::get_permalink($item).'" />';

	// a meta link to a feeding page
	$context['page_header'] .= "\n".'<link rel="alternate" href="'.$context['url_to_root'].Categories::get_url($item['id'], 'feed').'" title="RSS" type="application/rss+xml" />';

	// a meta link to a description page (actually, rdf)
	$context['page_header'] .= "\n".'<link rel="meta" href="'.$context['url_to_root'].Categories::get_url($item['id'], 'describe').'" title="Meta Information" type="application/rdf+xml" />';

	// implement the trackback interface
	$permanent_link = Categories::get_permalink($item);
	$trackback_link = $context['url_to_home'].$context['url_to_root'].'links/trackback.php?anchor=category:'.$item['id'];
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
	$context['page_header'] .= "\n".'<link rel="pingback" href="'.$context['url_to_root'].'services/ping.php" />';

	// set specific headers
	if(isset($item['introduction']) && $item['introduction'])
		$context['page_meta'] = strip_tags(Codes::beautify_introduction($item['introduction']));
	if(isset($item['create_name']) && $item['create_name'])
		$context['page_author'] = $item['create_name'];
	if(isset($item['edit_date']) && $item['edit_date'])
		$context['page_date'] = $item['edit_date'];

	//
	// page details -- $context['page_details']
	//

	// do not mention details at follow-up pages, nor to crawlers
	if(!$zoom_type && !Surfer::is_crawler()) {

		// one detail per line
		$context['page_details'] = '<p '.tag::_class('details').'>';
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

		// appears in navigation boxes
		if($item['display'] == 'site:all')
			$details[] = i18n::s('Is displayed on all pages, among other navigation boxes');

		// expired category
		if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
			$details[] = EXPIRED_FLAG.' '.sprintf(i18n::s('Category has expired %s'), Skin::build_date($item['expiry_date']));

		// display details, if any
		if(count($details))
			$context['page_details'] .= ucfirst(implode(BR, $details)).BR;

		// other details
		$details = array();

		// additional details for associates and editors
		if(Surfer::is_associate() || (is_object($anchor) && $anchor->is_assigned())) {

			// the creator of this category
			if($item['create_date'])
				$details[] = sprintf(i18n::s('posted by %s %s'), Users::get_link($item['create_name'], $item['create_address'], $item['create_id']), Skin::build_date($item['create_date']));

			// hide last edition if done by creator, and if less than 24 hours between creation and last edition
			if($item['create_date'] && ($item['create_id'] == $item['edit_id'])
					&& (SQL::strtotime($item['create_date'])+24*60*60 >= SQL::strtotime($item['edit_date'])))
				;

			// the last edition of this category
			else {

				if($item['edit_action'])
					$action = Anchors::get_action_label($item['edit_action']);
				else
					$action = i18n::s('edited');

				$details[] = sprintf(i18n::s('%s by %s %s'), $action, Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));

			}

			// the number of hits
			if($item['hits'] > 1)
				$details[] = Skin::build_number($item['hits'], i18n::s('hits'));

			// rank for this section
			if((intval($item['rank']) != 10000) && Surfer::is_associate())
				$details[] = '{'.$item['rank'].'}';

		}

		// inline details
		if(count($details))
			$context['page_details'] .= ucfirst(implode(', ', $details));

		// reference this item
		if(Surfer::is_member()) {
			$context['page_details'] .= BR.sprintf(i18n::s('Code to reference this page: %s'), '[category='.$item['id'].']');

			// the nick name
			if($item['nick_name'] && ($link = normalize_shortcut($item['nick_name'], TRUE)))
				$context['page_details'] .= BR.sprintf(i18n::s('Name: %s'), $link);
		}

		$context['page_details'] .= '</p>';

	}

	//
	// main panel -- $context['text']
	//
	$text = '';

        
        // display keywords with "#" as prefix for each 
        if($item['keywords'] !== '') {
            $context['text'] .= tag::_p(preg_replace('/([A-Za-zÀ-ÿ]+)/','#$1', $item['keywords']),'details keywords');
        }
        
        // insert anchor prefix
        if(is_object($anchor))
                $context['text'] .= $anchor->get_prefix();
        
	// display very few things if we are on a follow-up page
	if($zoom_type) {


		if($item['introduction'])
			$context['text'] .= Codes::beautify($item['introduction'])."<p> </p>\n";
		else
			$context['text'] .= Skin::cap(Codes::beautify($item['description']), 50)."<p> </p>\n";

	// else expose full details
	} else {

		// the introduction text, if any
		$text .= Skin::build_block($item['introduction'], 'introduction');

		// get text related to the overlay, if any
		if(is_object($overlay))
			$text .= $overlay->get_text('view', $item);

		// the description, which is the actual page body
		$text .= Skin::build_block($item['description'], 'description');

		$context['text'] .= $text;
	}

	//
	// panels
	//
	$panels = array();

	//
	// sections associated to this category
	//

	// the list of related sections if not at another follow-up page
	if(((!$zoom_type) || ($zoom_type == 'sections'))
		&& (!isset($item['sections_layout']) || ($item['sections_layout'] != 'none'))) {

		// build a complete box
		$box = array('bar' => array(), 'text' => '');

		// select a layout
		if(!isset($item['sections_layout']) || !$item['sections_layout']) {
			include_once '../sections/layout_sections.php';
			$layout_sections = new Layout_sections();
		} else
			$layout_sections = Layouts::new_ ($item['sections_layout'], 'section');

		// the maximum number of sections per page
		if(is_object($layout_sections))
			$items_per_page = $layout_sections->items_per_page();
		else
			$items_per_page = SECTIONS_PER_PAGE;

		// count the number of sections in this category
		$count = Members::count_sections_for_anchor('category:'.$item['id']);
		if($count > $items_per_page)
			$box['bar'] = array('_count' => sprintf(i18n::ns('%d section', '%d sections', $count), $count));

		// navigation commands for sections
		$home = Categories::get_permalink($item);
		$prefix = Categories::get_url($item['id'], 'navigate', 'sections');
		$box['bar'] = array_merge($box['bar'],
			Skin::navigate($home, $prefix, $count, $items_per_page, $zoom_index));

		// list items by date (default) or by title (option 'sections_by_title')
		$offset = ($zoom_index - 1) * $items_per_page;
		$items = Members::list_sections_by_title_for_anchor('category:'.$item['id'], $offset, $items_per_page, $layout_sections);

		// actually render the html for the section
		if(is_array($items))
			$box['text'] .= Skin::build_list($items, 'decorated');
		elseif(is_string($items))
			$box['text'] .= $items;
		if($box['bar'])
			$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');

		// in a separate panel
		if($box['text'])
			$panels[] = array('sections', i18n::s('Sections'), 'sections_panel', $box['text']);
	}

	//
	// articles associated to this category
	//

	// the list of related articles if not at another follow-up page
	if(((!$zoom_type) || ($zoom_type == 'articles'))
		&& (!isset($item['articles_layout']) || ($item['articles_layout'] != 'none'))) {

		// build a complete box
		$box = array('bar' => array(), 'text' => '');

		// select a layout
		if(!isset($item['articles_layout']) || !$item['articles_layout']) {
			include_once '../articles/layout_articles.php';
			$layout_articles = new Layout_articles();
		} else
			$layout_articles = Layouts::new_ ($item['articles_layout'], 'article');

		// do not refer to this category
		$layout_articles->set_focus('category:'.$item['id']);

		// count the number of articles in this category
		$count = Members::count_articles_for_anchor('category:'.$item['id'], $this_cat->get_listed_lang());
                if($count)
			$box['bar'] = array('_count' => sprintf(i18n::ns('%d page', '%d pages', $count), $count));

		// navigation commands for articles
		$home = Categories::get_permalink($item);
		$prefix = Categories::get_url($item['id'], 'navigate', 'articles');
		$box['bar'] = array_merge($box['bar'],
			Skin::navigate($home, $prefix, $count, ARTICLES_PER_PAGE, $zoom_index));

		// list items by date (default) or by title (option 'articles_by_title') or by rating_sum (option article_by_rating)
		$offset = ($zoom_index - 1) * ARTICLES_PER_PAGE;
		if(isset($order) && preg_match('/\barticles_by_rating\b/i', $order))
			$items = Members::list_articles_by_rating_for_anchor('category:'.$item['id'], $offset, ARTICLES_PER_PAGE, $layout_articles, $this_cat->get_listed_lang());
		elseif(isset($item['options']) && preg_match('/\barticles_by_title\b/i', $item['options']))
			$items = Members::list_articles_by_title_for_anchor('category:'.$item['id'], $offset, ARTICLES_PER_PAGE, $layout_articles, $this_cat->get_listed_lang());
		else
			$items = Members::list_articles_by_date_for_anchor('category:'.$item['id'], $offset, ARTICLES_PER_PAGE, $layout_articles, $this_cat->get_listed_lang());

		// actually render the html for the section
		if(is_array($items))
			$box['text'] .= Skin::build_list($items, 'decorated');
		elseif(is_string($items))
			$box['text'] .= $items;
		if($box['bar'])
			$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');

		// in a separate panel
		if($box['text'])
			$panels[] = array('articles', i18n::s('Pages'), 'articles_panel', $box['text']);
	}
        
        // images linked with this category
        if((!$zoom_type) || ($zoom_type == 'images')) {
         
                // build a complete box
		$box = array('bar' => array(), 'text' => '');
                if($count = Members::list_images_for_anchor('category:'.$item['id'], null, null, null, 'count')) {
                    
                    if($count > 20)
                        $box['bar'] = array('_count' => sprintf(i18n::ns('%d image', '%d images', $count), $count));
                    
                    $offset = ($zoom_index - 1) * FILES_PER_PAGE;
                    $order  = $this_cat->has_option('images_by');
                    
                    $items  = Members::list_images_for_anchor('category:'.$item['id'], $offset, FILES_PER_PAGE, $order, 'thumb');
                    
                    if(is_array($items))
                            $box['text'] .= Skin::build_list($items, 'decorated');
                    else
                            $box['text'] .= $items;
                }
                
                if($box['bar'])
			$box['text'] = Skin::build_list($box['bar'], 'menu_bar').$box['text'];
                
                // in a separate panel
		if($box['text'])
			$panels[] = array('images', i18n::s('Images'), 'Images_panel', $box['text']);
            
        }
	//
	// files attached to this category
	//

	// the list of related files if not at another follow-up page
	if((!$zoom_type) || ($zoom_type == 'files')) {

		// build a complete box
		$box = array('bar' => array(), 'text' => '');

		// count the number of files in this category
		if($count = Members::list_files_for_anchor('category:'.$item['id'], null, null, null, 'count')) {
			if($count > 20)
				$box['bar'] = array('_count' => sprintf(i18n::ns('%d file', '%d files', $count), $count));

			// list files by date (default) or by title (option 'files_by_title')
                        $order  = $this_cat->has_option('files_by');
                        if(!$order) $order = "edit_date";
			$offset = ($zoom_index - 1) * FILES_PER_PAGE;
                        
                        $items  = Members::list_files_for_anchor('category:'.$item['id'], $offset, FILES_PER_PAGE, $order);
                        
			if(is_array($items))
				$box['text'] .= Skin::build_list($items, 'decorated');
                        else
                                $box['text'] .= $items;

			// navigation commands for files
			$home = Categories::get_permalink($item);
			$prefix = Categories::get_url($item['id'], 'navigate', 'files');
			$box['bar'] = array_merge($box['bar'],
				Skin::navigate($home, $prefix, $count, FILES_PER_PAGE, $zoom_index));
		}

		// actually render the html for the section
		if($box['bar'])
			$box['text'] = Skin::build_list($box['bar'], 'menu_bar').$box['text'];

		// in a separate panel
		if($box['text'])
			$panels[] = array('files', i18n::s('Files'), 'files_panel', $box['text']);
	}

	//
	// comments attached to this category
	//

	// the list of related comments if not at another follow-up page
	if((!$zoom_type) || ($zoom_type == 'comments')) {

		// build a complete box
		$box = array('bar' => array(), 'text' => '');

		// count the number of comments in this category
		if($zoom_type == 'comments')
			$url = '_count';
		 else
			$url = Categories::get_permalink($item).'#comments';
		if($count = Comments::count_for_anchor('category:'.$item['id'])) {
			if($count > 20)
				$box['bar'] = array('_count' => sprintf(i18n::ns('%d comment', '%d comments', $count), $count));

			// list comments by date
			$offset = ($zoom_index - 1) * COMMENTS_PER_PAGE;
			$items = Comments::list_by_date_for_anchor('category:'.$item['id'], $offset, COMMENTS_PER_PAGE);
			if(is_array($items))
				$box['text'] .= Skin::build_list($items, 'rows');

			// navigation commands for comments
			$home = Categories::get_permalink($item);
			$prefix = Categories::get_url($item['id'], 'navigate', 'comments');
			if($zoom_type == 'comments') {
				$box['bar'] = array_merge($box['bar'],
					Skin::navigate($home, $prefix, $count, COMMENTS_PER_PAGE, $zoom_index, TRUE));
			}
		}

		// the command to post a new comment
		if(Comments::allow_creation($item, $anchor, 'category')) {
			$url = 'comments/edit.php?anchor='.urlencode('category:'.$item['id']);
			$box['bar'] += array( $url => fa::_("commenting-o").' '.i18n::s('Post a comment') );
		}

		// actually render the html
		if($box['bar'])
			$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');

		// in a separate panel
		if($box['text'])
			$panels[] = array('comments', i18n::s('Comments'), 'comments_panel', $box['text']);
	}

	//
	// links attached to this category
	//

	// the list of related links if not at another follow-up page
	if(((!$zoom_type) || ($zoom_type == 'links'))) {

		// build a complete box
		$box = array('bar' => array(), 'text' => '');

		// count the number of links in this category
		if($count = Links::count_for_anchor('category:'.$item['id'])) {
			if($count > 20)
				$box['bar'] = array('_count' => sprintf(i18n::ns('%d link', '%d links', $count), $count));

			// list items by date (default) or by title (option 'links_by_title')
			$offset = ($zoom_index - 1) * LINKS_PER_PAGE;
			if(isset($item['options']) && preg_match('/\blinks_by_title\b/i', $item['options']))
				$items = Links::list_by_title_for_anchor('category:'.$item['id'], $offset, LINKS_PER_PAGE);
			else
				$items = Links::list_by_date_for_anchor('category:'.$item['id'], $offset, LINKS_PER_PAGE);
			if(is_array($items))
				$box['text'] .= Skin::build_list($items, 'decorated');

			// navigation commands for links
			$home = Categories::get_permalink($item);
			$prefix = Categories::get_url($item['id'], 'navigate', 'links');
			$box['bar'] = array_merge($box['bar'],
				Skin::navigate($home, $prefix, $count, LINKS_PER_PAGE, $zoom_index));
		}

		// the command to post a new link
		if(Links::allow_creation($item, $anchor, 'category')) {
			$url = 'links/edit.php?anchor='.urlencode('category:'.$item['id']);
			$box['bar'] += array( $url =>  fa::_("chain").' '.i18n::s('Add a link') );
		}

		// actually render the html
		if($box['bar'])
			$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');

		// in a separate panel
		if($box['text'])
			$panels[] = array('links', i18n::s('Links'), 'links_panel', $box['text']);
	}

	//
	// sub-categories of this category
	//

	// the list of related categories if not at another follow-up page
	if( (!isset($zoom_type) || !$zoom_type || ($zoom_type == 'categories'))
		&& (!isset($item['categories_layout']) || ($item['categories_layout'] != 'none')) ) {

		// build a complete box
		$box = array('bar' => array(), 'text' => '');

		// select a layout
		if(!isset($item['categories_layout']) || !$item['categories_layout']) {
			include_once 'layout_categories.php';
			$layout = new Layout_categories();
		} else
			$layout = Layouts::new_ ($item['categories_layout'], 'category');
		    

		// the maximum number of categories per page
		if(is_object($layout))
			$items_per_page = $layout->items_per_page();
		else
			$items_per_page = CATEGORIES_PER_PAGE;

		// count the number of subcategories
		$stats = Categories::stat_for_anchor('category:'.$item['id']);
// 		if($stats['count'])
// 			$box['bar'] += array('_count' => sprintf(i18n::ns('%d category', '%d categories', $stats['count']), $stats['count']));

		// list items by date (default) or by title (option 'categories_by_title')
		$offset = ($zoom_index - 1) * $items_per_page;
		if(preg_match('/\bcategories_by_title\b/i', $item['options']))
			$items = Categories::list_by_title_for_anchor('category:'.$item['id'], $offset, $items_per_page, $layout);
		else
			$items = Categories::list_by_date_for_anchor('category:'.$item['id'], $offset, $items_per_page, $layout);

		// navigation commands for categories
		$home = Categories::get_permalink($item);
		$prefix = Categories::get_url($item['id'], 'navigate', 'categories');
		$box['bar'] = array_merge($box['bar'],
			Skin::navigate($home, $prefix, $stats['count'], $items_per_page, $zoom_index));

		// the command to post a new category
		if($stats['count'] && $this_cat->allow_creation()) {
			$url = 'categories/edit.php?anchor='.urlencode('category:'.$item['id']);
			$box['bar'] += array( $url => i18n::s('Add a category') );
		}

		// actually render the html for the section
		if(is_array($items))
			$box['text'] .= Skin::build_list($items, 'decorated');
		elseif(is_string($items))
			$box['text'] .= $items;
		if($box['bar'])
			$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');

		// in a separate panel
		if($box['text'])
			$panels[] = array('categories', i18n::s('Categories'), 'categories_panel', $box['text']);
	}

	//
	// users associated to this category
	//

	// the list of related users if not at another follow-up page
	if( ((!$zoom_type) || ($zoom_type == 'users'))
		&& (!isset($item['users_layout']) || ($item['users_layout'] != 'none')) ) {

		// build a complete box
		$box = array('bar' => array(), 'text' => '');

		// select a layout
		if(!isset($item['users_layout']) || !$item['users_layout']) {
			include_once '../users/layout_users.php';
			$layout = new Layout_users();
		} else
			$layout = Layouts::new_ ($item['users_layout'], 'user');

		// count the number of users in this category
		$count = Members::count_users_for_anchor('category:'.$item['id']);

		// notify members
		if(($count > 1) && Surfer::is_associate()) {
			$box['bar'] += array(Categories::get_url($item['id'], 'mail') => fa::_("envelope-o").' '.i18n::s('Notify members'));
		}

		// spread the list over several pages
		if($count > USERS_LIST_SIZE)
			$box['bar'] += array('_count' => sprintf(i18n::ns('%d user', '%d users', $count), $count));

		// navigation commands for users
		$home = Categories::get_permalink($item);
		$prefix = Categories::get_url($item['id'], 'navigate', 'users');
		$box['bar'] = array_merge($box['bar'],
			Skin::navigate($home, $prefix, $count, USERS_LIST_SIZE, $zoom_index));

		// list items by date (default) or by title (option 'users_by_title')
		$offset = ($zoom_index - 1) * USERS_LIST_SIZE;
		$items = Members::list_users_by_name_for_anchor('category:'.$item['id'], $offset, USERS_LIST_SIZE, $layout);

		// actually render the html
		if($box['bar'])
			$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');
		if(is_array($items))
			$box['text'] .= Skin::build_list($items, 'decorated');
		elseif(is_string($items))
			$box['text'] .= $items;

		// in a separate panel
		if($box['text'])
			$panels[] = array('users', i18n::s('Persons'), 'users_panel', $box['text']);
	}

	//
	// assemble all tabs
	//

	// let YACS do the hard job
	$context['text'] .= Skin::build_tabs($panels);

	//
	// trailer
	//

	// add trailer information from the overlay, if any
	if(is_object($overlay))
		$context['text'] .= $overlay->get_text('trailer', $item);

	// add trailer information from this item, if any
	if(isset($item['trailer']) && trim($item['trailer']))
		$context['text'] .= Codes::beautify($item['trailer']);

	// insert anchor suffix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_suffix();

	//
	// extra panel
	//

	// page tools
	//

	// only on first page, and for associates or editors
	if(!$zoom_type && (Surfer::is_associate() || $this_cat->is_assigned())) {

		// add a category
		if($this_cat->allow_creation()) {
			$context['page_tools'][] = Skin::build_link('categories/edit.php?anchor='.urlencode('category:'.$item['id']),  fa::_("plus-square-o").' '.i18n::s('Add a category'), 'basic');
		}

		// post an image, if upload is allowed
		if(Images::allow_creation($item, $anchor, 'category')) {
			$context['page_tools'][] = Skin::build_link('images/edit.php?anchor='.urlencode('category:'.$item['id']), fa::_("image").' '.i18n::s('Add an image'), 'basic', i18n::s('You can upload a camera shot, a drawing, or another image file.'));
			$context['page_minitools'][] = Skin::build_link('images/edit.php?anchor='.urlencode('category:'.$item['id']), fa::_("image"), 'basic', i18n::s('You can upload a camera shot, a drawing, or another image file.'));
		}

		// add a file, if upload is allowed
		if(Files::allow_creation($item, $anchor, 'category')) {
			$context['page_tools'][] = Skin::build_link('files/edit.php?anchor='.urlencode('category:'.$item['id']), fa::_("file-o").' '.i18n::s('Add a file'), 'basic', i18n::s('Attach related files.'));
			$context['page_minitools'][] = Skin::build_link('files/edit.php?anchor='.urlencode('category:'.$item['id']), fa::_("file-o"), 'basic', i18n::s('Attach related files.'));
		}

		// add a link
		if(Links::allow_creation($item, $anchor, 'category')) {
			$context['page_tools'][] = Skin::build_link('links/edit.php?anchor='.urlencode('category:'.$item['id']), fa::_("link").' '.i18n::s('Add a link'), 'basic', i18n::s('Contribute to the web and link to relevant pages.'));
		}

		// comment this page if anchor does not prevent it
		if(Comments::allow_creation($item, $anchor, 'category')) {
			$context['page_tools'][] = Skin::build_link(Comments::get_url('category:'.$item['id'], 'comment'), fa::_("commenting-o").' '.i18n::s('Post a comment'), 'basic', i18n::s('Express yourself, and say what you think.'));
		}


		// modify this item
		$context['page_tools'][] = Skin::build_link(Categories::get_url($item['id'], 'edit'), fa::_("edit").' '.i18n::s('Edit this category'), 'basic', i18n::s('Press [e] to edit'), FALSE, 'e');
		$context['page_minitools'][] = Skin::build_link(Categories::get_url($item['id'], 'edit'), fa::_("edit"), 'basic', i18n::s('Press [e] to edit'), FALSE, 'e');

		// delete this item
		$context['page_tools'][] = Skin::build_link(Categories::get_url($item['id'], 'delete'), fa::_("trash").' '.i18n::s('Delete this category'));
		$context['page_minitools'][] = Skin::build_link(Categories::get_url($item['id'], 'delete'), fa::_("trash"));

		// manage persons assigned to this category
		$context['page_tools'][] = Skin::build_link(Users::get_url('category:'.$item['id'], 'select'), fa::_("users").' '.i18n::s('Manage members'));
	}

	// add extra information from the overlay, if any
	if(is_object($overlay))
		$context['components']['overlay'] = $overlay->get_text('extra', $item);

	// add extra information from this item, if any
	if(isset($item['extra']) && $item['extra'])
		$context['components']['boxes'] = Codes::beautify_extra($item['extra']);

	// 'Share' box
	//
	$lines = array();

	// the command to track back
	if(Links::allow_trackback()) {
		$lines[] = Skin::build_link('links/trackback.php?anchor='.urlencode('category:'.$item['id']), fa::_("share-alt").' '.i18n::s('Reference this page'), 'basic', i18n::s('Various means to link to this page'));
	}

	// print this page
	if(Surfer::is_logged()) {
		$lines[] = Skin::build_link(Categories::get_url($item['id'], 'print'), fa::_("print").' '.i18n::s('Print this page'), 'basic', i18n::s('Get a paper copy of this page.'));
	}		

	// in a side box
	if(count($lines))
		$context['components']['share'] = Skin::build_box(i18n::s('Share'), Skin::finalize_list($lines, 'newlines'), 'share', 'share');

	// get news from rss
	if(isset($item['id']) && (!isset($context['skins_general_without_feed']) || ($context['skins_general_without_feed'] != 'Y')) ) {

		$content = Skin::build_link($context['url_to_home'].$context['url_to_root'].Categories::get_url($item['id'], 'feed'), i18n::s('Recent pages'), 'xml');

		// public aggregators
// 		if(!isset($context['without_internet_visibility']) || ($context['without_internet_visibility'] != 'Y'))
// 			$content .= BR.join(BR, Skin::build_subscribers($context['url_to_home'].$context['url_to_root'].Categories::get_url($item['id'], 'feed'), $item['title']));

		$context['components']['channels'] = Skin::build_box(i18n::s('Monitor'), $content, 'channels', 'feeds');
	}

	// search on keyword, if any
	if($item['keywords']) {

		// internal search
		$label = sprintf(i18n::s('Maybe some new pages or additional material can be found by submitting the following keyword to our search engine. Give it a try. %s'), Codes::beautify('[search='.$item['keywords'].']'));
		$context['components']['boxes'] .= Skin::build_box(i18n::s('Internal search'), $label, 'extra');

		// external search
		$content = '<p>'.sprintf(i18n::s('Search for %s at:'), $item['keywords']).' ';

		// encode for urls, but preserve unicode chars
		$search = urlencode(utf8::from_unicode($item['keywords']));

		// Google
		$link = 'http://www.google.com/search?q='.$search.'&amp;ie=utf-8';
		$content .= Skin::build_link($link, i18n::s('Google'), 'external').', ';

		// Yahoo!
		$link = 'http://search.yahoo.com/search?p='.$search.'&amp;ei=utf-8';
		$content .= Skin::build_link($link, i18n::s('Yahoo!'), 'external').', ';

		// Ask Jeeves
		$link = 'http://web.ask.com/web?q='.$search;
		$content .= Skin::build_link($link, i18n::s('Ask Jeeves'), 'external').', ';

		// Technorati
		$link = 'http://www.technorati.com/cosmos/search.html?rank=&url='.$search;
		$content .= Skin::build_link($link, i18n::s('Technorati'), 'external').'.';

		$content .= "</p>\n";
		$context['components']['boxes'] .= Skin::build_box(i18n::s('External search'), $content, 'boxes');

	}

	// referrals, if any
	$context['components']['referrals'] = Skin::build_referrals(Categories::get_permalink($item));

}

// render the skin
render_skin();

?>
