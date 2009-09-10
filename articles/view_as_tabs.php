<?php
/**
 * structure a web page as tabs
 *
 * This script is included into [script]articles/view.php[/script], when the
 * option is set to 'view_as_tabs'.
 *
 * The basic structure is made of following panels:
 * - Information - with details, introduction, and main text. This may be overloaded if required.
 * - Attachments - with files and links
 * - Discussion - A thread of contributions, not in real-time
 *
 * The extra panel has following elements:
 * - extra information from the article itself, if any
 * - toolbox for page author, editors, and associates
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
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// loaded from articles/view.php
defined('YACS') or exit('Script must be included');

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'articles/' => i18n::s('All pages') );

// page title
if(is_object($overlay))
	$context['page_title'] = $overlay->get_text('title', $item);
elseif(isset($item['title']))
	$context['page_title'] = $item['title'];

// page language, if any
if(isset($item['language']) && $item['language'] && ($item['language'] != 'none'))
	$context['page_language'] = $item['language'];

// change default behavior
if(isset($item['id']) && is_object($behaviors) && !$behaviors->allow('articles/view.php', 'article:'.$item['id']))
	$permitted = FALSE;

// not found -- help web crawlers
if(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No item has the provided id.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Articles::get_permalink($item)));

	// permission denied to authenticated user
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// stop crawlers on non-published pages
} elseif((!isset($item['publish_date']) || ($item['publish_date'] <= NULL_DATE)) && !Surfer::is_logged()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// display page content
} else {

	// behaviors can change page menu
	if(is_object($behaviors))
		$context['page_menu'] =& $behaviors->add_commands('articles/view.php', 'article:'.$item['id'], $context['page_menu']);

	// remember surfer visit
	Surfer::is_visiting(Articles::get_permalink($item), Codes::beautify_title($item['title']), 'article:'.$item['id'], $item['active']);

	// increment silently the hits counter if not associate, nor creator -- editors are taken into account
	if(Surfer::is_associate())
		;
	elseif(Surfer::get_id() && isset($item['create_id']) && (Surfer::get_id() == $item['create_id']))
		;
	else {
		$item['hits'] = isset($item['hits'])?($item['hits']+1):1;
		Articles::increment_hits($item['id']);
	}

	// initialize the rendering engine
	Codes::initialize(Articles::get_permalink($item));

	// neighbours information
	$neighbours = NULL;
	if(is_object($anchor) && !$anchor->has_option('no_neighbours') && ($context['skin_variant'] != 'mobile'))
		$neighbours = $anchor->get_neighbours('article', $item);

	//
	// compute page image -- $context['page_image']
	//

	// the article icon, if any
	if(isset($item['icon_url']) && $item['icon_url'])
		$context['page_image'] = $item['icon_url'];
	elseif(is_object($anchor))
		$context['page_image'] = $anchor->get_icon_url();

	//
	// meta-information -- $context['page_header'], etc.
	//

	// add meta information, if any
	if(isset($item['meta']) && $item['meta'])
		$context['page_header'] .= $item['meta'];

	// a meta link to prefetch the next page
	if(isset($neighbours[2]) && $neighbours[2])
		$context['page_header'] .= "\n".'<link rel="next" href="'.$context['url_to_root'].$neighbours[2].'" title="'.encode_field($neighbours[3]).'" />';

	// a meta link to the section front page
	if(is_object($anchor))
		$context['page_header'] .= "\n".'<link rel="contents" href="'.$context['url_to_root'].$anchor->get_url().'" title="'.encode_field($anchor->get_title()).'" type="text/html" />';

	// a meta link to a description page (actually, rdf)
	$context['page_header'] .= "\n".'<link rel="meta" href="'.$context['url_to_root'].Articles::get_url($item['id'], 'describe').'" title="Meta Information" type="application/rdf+xml" />';

	// implement the trackback interface
	$permanent_link = $context['url_to_home'].$context['url_to_root'].Articles::get_permalink($item);
	if($context['with_friendly_urls'] == 'Y')
		$trackback_link = $context['url_to_home'].$context['url_to_root'].'links/trackback.php/article/'.$item['id'];
	else
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

	// show the secret handle at an invisible place, and only to associates
	if(Surfer::is_associate() && $item['handle'])
		$context['page_header'] .= "\n".'<meta name="handle" content="'.$item['handle'].'" />';

	// set specific headers
	if(isset($item['introduction']) && $item['introduction'])
		$context['page_description'] = strip_tags(Codes::beautify_introduction($item['introduction']));
	if(isset($item['create_name']) && $item['create_name'])
		$context['page_author'] = $item['create_name'];
	if(isset($item['publish_name']) && $item['publish_name'])
		$context['page_publisher'] = $item['publish_name'];

	//
	// page details -- $context['page_details']
	//
	$text = '';

	// do not mention details to crawlers
	if(!Surfer::is_crawler()) {

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
		if(is_object($anchor) && !$anchor->has_option('without_rating') && !$anchor->has_option('rate_as_digg')) {

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
		if((Surfer::is_associate() || Articles::is_assigned($id) || (is_object($anchor) && $anchor->is_editable()))
				&& ($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $now)) {
			$details[] = EXPIRED_FLAG.' '.sprintf(i18n::s('Page has expired %s'), Skin::build_date($item['expiry_date']));
		}

		// no more details
		if(count($details))
			$text .= ucfirst(implode(BR."\n", $details)).BR."\n";

		// details
		$details = array();

		// the creator of this article, if associate or if editor or if not prevented globally or if section option
		if($item['create_date']
			&& (Surfer::is_associate() || Articles::is_assigned($id) || (is_object($anchor) && $anchor->is_editable())
					|| ((!isset($context['content_without_details']) || ($context['content_without_details'] != 'Y')) || (is_object($anchor) && $anchor->has_option('with_details')) ) ) ) {

			if($item['create_name'])
				$details[] = sprintf(i18n::s('posted by %s %s'), Users::get_link($item['create_name'], $item['create_address'], $item['create_id']), Skin::build_date($item['create_date']));
			else
				$details[] = Skin::build_date($item['create_date']);

		}

		// the publisher of this article, if any
		if(($item['publish_date'] > NULL_DATE)
			&& !strpos($item['edit_action'], ':publish')
			&& (Surfer::is_associate() || Articles::is_assigned($id) || (is_object($anchor) && $anchor->is_editable()))) {

			if($item['publish_name'])
				$details[] = sprintf(i18n::s('published by %s %s'), Users::get_link($item['publish_name'], $item['publish_address'], $item['publish_id']), Skin::build_date($item['publish_date']));
			else
				$details[] = Skin::build_date($item['publish_date']);
		}

		// last modification by creator, and less than 24 hours between creation and last edition
		if(($item['create_date'] > NULL_DATE) && ($item['create_id'] == $item['edit_id'])
				&& (SQL::strtotime($item['create_date'])+24*60*60 >= SQL::strtotime($item['edit_date'])))
			;
		// publication is the last action
		elseif(strpos($item['edit_action'], ':publish'))
			;
		elseif(Surfer::is_associate() || Articles::is_assigned($id) || (is_object($anchor) && $anchor->is_editable())
			|| ((!isset($context['content_without_details']) || ($context['content_without_details'] != 'Y')) || (is_object($anchor) && $anchor->has_option('with_details')) ) ) {

			if($item['edit_action'])
				$action = get_action_label($item['edit_action']).' ';
			else
				$action = i18n::s('edited');

			if($item['edit_name'])
				$details[] = sprintf(i18n::s('%s by %s %s'), $action, Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));
			else
				$details[] = $action.' '.Skin::build_date($item['edit_date']);

		}

		// last revision, if any
		if(isset($item['review_date']) && ($item['review_date'] > NULL_DATE) && Surfer::is_associate())
			$details[] = sprintf(i18n::s('reviewed %s'), Skin::build_date($item['review_date'], 'no_hour'));

		// signal articles to be published
		if(($item['publish_date'] <= NULL_DATE)) {
			if(Surfer::is_associate() || Articles::is_assigned($id) || (is_object($anchor) && $anchor->is_editable()))
				$label = Skin::build_link(Articles::get_url($item['id'], 'publish'), i18n::s('not published'));
			else
				$label = i18n::s('not published');
			$details[] = DRAFT_FLAG.' '.$label;
		}

		// the number of hits
		if(($item['hits'] > 1)
			&& (Surfer::is_associate() || Articles::is_assigned($id) || (is_object($anchor) && $anchor->is_editable())
					|| ((!isset($context['content_without_details']) || ($context['content_without_details'] != 'Y')) || (is_object($anchor) && $anchor->has_option('with_details')) ) ) ) {

			// flag popular pages
			$popular = '';
			if($item['hits'] > 100)
				$popular = POPULAR_FLAG;

			// actually show numbers only to associates and editors
			if(Surfer::is_associate() || Articles::is_assigned($id) || (is_object($anchor) && $anchor->is_editable()) )
				$details[] = $popular.Skin::build_number($item['hits'], i18n::s('hits'));

			// show first hits
			elseif($item['hits'] < 100)
				$details[] = $popular.Skin::build_number($item['hits'], i18n::s('hits'));

			// other surfers will benefit from a stable ETag
			elseif($popular)
				$details[] = $popular;
		}

		// rank for this article
		if((Surfer::is_associate() || Articles::is_assigned($id) || (is_object($anchor) && $anchor->is_editable())) && (intval($item['rank']) != 10000))
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
	// compute main panel -- $context['text']
	//

	// insert anchor prefix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_prefix();

	// article rating, if the anchor allows for it, and if no rating has already been registered
	if(is_object($anchor) && !$anchor->has_option('without_rating') && $anchor->has_option('rate_as_digg')) {

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
		$context['text'] .= '<div class="digg"><div class="votes">'.$rating_label.'</div>'
			.$digg
			.'</div>';

		// signal DIGG
		define('DIGG', TRUE);
	}

	// special layout for digg
	if(defined('DIGG'))
		$context['text'] .= '<div class="digg_content">';

	// the poster profile, if any, at the beginning of the first page
	if(isset($poster['id']) && is_object($anchor))
		$context['text'] .= $anchor->get_user_profile($poster, 'prefix', Skin::build_date($item['create_date']));

	// only at the first page
	if($page == 1) {

		// the introduction text, if any
		if(is_object($overlay))
			$context['text'] .= Skin::build_block($overlay->get_text('introduction', $item), 'introduction');
		elseif(isset($item['introduction']) && trim($item['introduction']))
			$context['text'] .= Skin::build_block($item['introduction'], 'introduction');

	}

	// filter description, if necessary
	if(is_object($overlay))
		$description = $overlay->get_text('description', $item);
	else
		$description = $item['description'];
				
	// the beautified description, which is the actual page body
	if($description) {

		// use adequate label
// 		if(is_object($overlay) && ($label = $overlay->get_label('description')))
// 			$context['text'] .= Skin::build_block($label, 'title');

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
		$context['text'] .= Skin::build_block($description, 'description', '', $item['options']);

		// if there are several pages, add navigation commands to browse them
		if(count($pages) > 1) {
			$page_menu = array( '_' => i18n::s('Pages') );
			$home =& Sections::get_permalink($item);
			$prefix = Sections::get_url($item['id'], 'navigate', 'pages');
			$page_menu = array_merge($page_menu, Skin::navigate($home, $prefix, count($pages), 1, $page));

			$context['text'] .= Skin::build_list($page_menu, 'menu_bar');
		}
	}

	// special layout for digg
	if(defined('DIGG'))
		$context['text'] .= '</div>';

	// the poster profile, if any, at the end of the page
	if(isset($poster['id']) && is_object($anchor))
		$context['text'] .= $anchor->get_user_profile($poster, 'suffix', Skin::build_date($item['create_date']));

	//
	// panels
	//
	$panels = array();

	//
	// information tab
	//
	$information = '';

	// get text related to the overlay, if any
	if(is_object($overlay))
		$information .= $overlay->get_text('view', $item);

	// add trailer information from the overlay, if any
	if(is_object($overlay))
		$information .= $overlay->get_text('trailer', $item);

	// add trailer information from this item, if any
	if(isset($item['trailer']) && trim($item['trailer']))
		$information .= Codes::beautify($item['trailer']);

	// insert anchor suffix
	if(is_object($anchor))
		$information .= $anchor->get_suffix();

	// display in a separate panel
	if($information)
		$panels[] = array('information', i18n::s('Information'), 'information_panel', $information);

	//
	// append tabs from the overlay, if any -- they have been captured in articles/view.php
	//
	if(is_array($context['tabs']))
 		$panels = array_merge($panels, $context['tabs']);

	//
	// discussion tab - a near real-time interaction area
	//
	$discussion = '';
	$discussion_count = 0;

	// conversation is over
	if(isset($item['locked']) && ($item['locked'] == 'Y')) {

		// display a transcript of past comments
		include_once $context['path_to_root'].'comments/comments.php';
		$items = Comments::list_by_date_for_anchor('article:'.$item['id'], 0, 500, 'excerpt');
		if(is_array($items))
			$discussion .= Skin::build_list($items, 'rows');
		elseif(is_string($items))
			$discussion .= $items;

	// on-going conversation
	} else {

		// new comments are allowed
		if(Comments::are_allowed($anchor, $item)) {
		
			// we have a wall
			if((is_object($anchor) && $anchor->has_option('comments_as_wall')) || preg_match('/\bcomments_as_wall\b/', $item['options']))
				$comments_prefix = TRUE;
					
			// editors and associates can always contribute to a thread
			else
				$comments_suffix = TRUE;
		}
			
		// get a layout for these comments
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
		$box = array('top' => array(), 'bottom' => array(), 'text' => '');

		// feed the wall
		if(isset($comments_prefix))
			$box['text'] .= Comments::get_form('article:'.$item['id']);

		// a navigation bar for these comments
		if($count = Comments::count_for_anchor('article:'.$item['id'])) {
			$discussion_count = $count;
			$box['bottom'] += array('_count' => sprintf(i18n::ns('%d comment', '%d comments', $count), $count));
			
			// list comments by date
			$items = Comments::list_by_date_for_anchor('article:'.$item['id'], $offset, $items_per_page, $layout, isset($comments_prefix) || preg_match('/\bcomments_as_wall\b/i', $item['options']));

			// actually render the html
			if(is_array($items))
				$box['text'] .= Skin::build_list($items, 'rows');
			elseif(is_string($items))
				$box['text'] .= $items;

			// navigation commands for comments
			$prefix = Comments::get_url('article:'.$item['id'], 'navigate');
			$box['bottom'] += Skin::navigate(NULL, $prefix, $count, $items_per_page, $zoom_index, FALSE, TRUE);


			// new comments are allowed
			if(isset($comments_suffix)) {
				Skin::define_img('COMMENTS_ADD_IMG', 'comments/add.gif');
				$box['bottom'] += array( Comments::get_url('article:'.$item['id'], 'comment') => array('', COMMENTS_ADD_IMG.i18n::s('Post a comment'), '', 'basic', '', i18n::s('Post a comment')));
			}
		}

		// build a box
		if($box['text'])
			$discussion .= Skin::build_content('comments', '', $box['text'], $box['top'], $box['bottom']);

	}

	// display in a separate panel
	if($discussion) {
		$label = i18n::s('Discussion');
		if($discussion_count)
			$label .= ' ('.$discussion_count.')';
		$panels[] = array('discussion', $label, 'discussion_panel', $discussion);
	}

	//
	// attachments tab
	//
	$attachments = '';
	$attachments_count = 0;

	// build a complete box
	$box = array('bar' => array(), 'text' => '');

	// a navigation bar for these files
	if($count = Files::count_for_anchor('article:'.$item['id'])) {
		$attachments_count += $count;
		if($count > 5)
			$box['bar'] += array('_count' => sprintf(i18n::ns('%d file', '%d files', $count), $count));
		
		// list files by date (default) or by title (option files_by_title)
		if(preg_match('/\bfiles_by_title\b/i', $item['options']))
			$items = Files::list_by_title_for_anchor('article:'.$item['id'], 0, 100, 'no_anchor');
		else
			$items = Files::list_by_date_for_anchor('article:'.$item['id'], 0, 100, 'no_anchor');
	
		// actually render the html
		if(is_array($items))
			$box['text'] .= Skin::build_list($items, 'decorated');
		elseif(is_string($items))
			$box['text'] .= $items;

		// the command to post a new file
		if(Files::are_allowed($anchor, $item)) {
			Skin::define_img('FILES_UPLOAD_IMG', 'files/upload.gif');
			$box['bar'] += array('files/edit.php?anchor='.urlencode('article:'.$item['id']) => FILES_UPLOAD_IMG.i18n::s('Upload a file'));
		}
	
	}
	
	// display this in tab
	if($box['text'])
		$attachments .= Skin::build_content('files', i18n::s('Files'), $box['text'], $box['bar']);

	// build a complete box
	$box = array('bar' => array(), 'text' => '');

	// a navigation bar for these links
	if($count = Links::count_for_anchor('article:'.$item['id'])) {
		$attachments_count += $count;
		if($count > 5)
			$box['bar'] += array('_count' => sprintf(i18n::ns('%d link', '%d links', $count), $count));
		
		// list links by date (default) or by title (option links_by_title)
		if(preg_match('/\blinks_by_title\b/i', $item['options']))
			$items = Links::list_by_title_for_anchor('article:'.$item['id'], 0, 50, 'no_anchor');
		else
			$items = Links::list_by_date_for_anchor('article:'.$item['id'], 0, 50, 'no_anchor');
	
		// actually render the html
		if(is_array($items))
			$box['text'] .= Skin::build_list($items, 'rows');
		elseif(is_string($items))
			$box['text'] .= $items;

		// new links are allowed
		if(Links::are_allowed($anchor, $item)) {
			Skin::define_img('LINKS_ADD_IMG', 'links/add.gif');
			$box['bar'] += array('links/edit.php?anchor='.urlencode('article:'.$item['id']) => LINKS_ADD_IMG.i18n::s('Add a link'));
		}
	
	}
	
	// display this aside the thread
	if($box['text'])
		$attachments .= Skin::build_content('links', i18n::s('Links'), $box['text'], $box['bar']);

	// display in a separate panel
	if($attachments) {
		$label = i18n::s('Attachments');
		if($attachments_count)
			$label .= ' ('.$attachments_count.')';
		$panels[] = array('attachments', $label, 'attachments_panel', $attachments);
	}

	//
	// users
	//
	$users = '';
	$users_count = 0;

	// the list of related users if not at another follow-up page
	if(!$zoom_type || ($zoom_type == 'users')) {

		// build a complete box
		$box = array('bar' => array(), 'text' => '');

		// count the number of users
		$estats = Members::stat_users_for_member('article:'.$item['id']);
		$wstats = Members::stat_users_for_anchor('article:'.$item['id']);
		$users_count = max($estats['count'], $wstats['count']);

		// spread the list over several pages
		if($estats['count'] > 1)
			$box['bar'] += array('_count' => sprintf(i18n::ns('%d person', '%d persons', $estats['count']), $estats['count']));

		// send a message to an article
		if(($estats['count'] > 1) && Surfer::is_empowered() && Surfer::is_logged() && isset($context['with_email']) && ($context['with_email'] == 'Y')) {
			Skin::define_img('ARTICLES_EMAIL_IMG', 'articles/email.gif');
			$box['bar'] += array(Articles::get_url($item['id'], 'mail') => ARTICLES_EMAIL_IMG.i18n::s('Send a message'));
		}

		// assign command provided to associates and authenticated editors
		if(Articles::is_owned($anchor, $item) && isset($context['with_email']) && ($context['with_email'] == 'Y')) {
			Skin::define_img('ARTICLES_INVITE_IMG', 'articles/invite.gif');
			$box['bar'] += array(Articles::get_url($item['id'], 'invite') => ARTICLES_INVITE_IMG.i18n::s('Invite participants'));

		// allow editors to leave their position
		} elseif(Articles::is_assigned($item['id'])) {
			Skin::define_img('ARTICLES_ASSIGN_IMG', 'sections/assign.gif');
			$box['bar'] += array(Users::get_url('article:'.$item['id'], 'select') => ARTICLES_ASSIGN_IMG.i18n::s('Leave this page'));
		}

		// navigation commands for users
		$home = Articles::get_permalink($item);
		$prefix = Articles::get_url($item['id'], 'navigate', 'users');
		$box['bar'] = array_merge($box['bar'], Skin::navigate($home, $prefix, $estats['count'], USERS_LIST_SIZE, $zoom_index));

		// list editors
		$offset = ($zoom_index - 1) * USERS_LIST_SIZE;
		if($items =& Members::list_editors_for_member('article:'.$item['id'], $offset, USERS_LIST_SIZE, 'watch')) {
			if(is_array($items))
				$items = Skin::build_list($items, 'decorated');
			$items = i18n::s('Following persons are entitled to manage content:').$items;
		}

		// watchers
		if($watchers =& Members::list_watchers_by_posts_for_anchor('article:'.$item['id'], 0, 500, 'compact')) {
			if(is_array($watchers))
				$watchers = Skin::build_list($watchers, 'compact');
			$watchers = i18n::s('Following persons are watching this page:').$watchers;
		}
		
		// add to the watch list -- $in_wath_list is set in sections/view.php
		if(Surfer::get_id() && !$in_watch_list) {
			Skin::define_img('TOOLS_WATCH_IMG', 'tools/watch.gif');
			$watchers .= '<p style="margin: 1em 0;">'.Skin::build_link(Users::get_url('article:'.$item['id'], 'track'), TOOLS_WATCH_IMG.i18n::s('Watch this page'), 'button', i18n::s('To be notified when new content is added')).'</p>';
		}

		// layout columns
		if($watchers)		
			$box['text'] .= Skin::layout_horizontally($items, Skin::build_block($watchers, 'sidecolumn'));
		else
			$box['text'] .= $items;
					
		// actually render the html
		$users .= Skin::build_content(NULL, NULL, $box['text'], $box['bar']);

	}

	// display in a separate panel
	if($users) {
		$label = i18n::s('Persons');
		if($users_count)
			$label .= ' ('.$users_count.')';
		$panels[] = array('users', $label, 'users_panel', $users);
	}

	// let YACS do the hard job
	$context['text'] .= Skin::build_tabs($panels);

	//
	// extra panel -- most content is cached, except commands specific to current surfer
	//

	// the poster profile, if any, aside
	if(isset($poster['id']) && is_object($anchor))
		$context['components']['profile'] = $anchor->get_user_profile($poster, 'extra', Skin::build_date($item['create_date']));

	// page tools
	//

	// comment this page if anchor does not prevent it
	if(Comments::are_allowed($anchor, $item)) {
		Skin::define_img('COMMENTS_ADD_IMG', 'comments/add.gif');
		$context['page_tools'][] = Skin::build_link(Comments::get_url('article:'.$item['id'], 'comment'), COMMENTS_ADD_IMG.i18n::s('Post a comment'), 'basic', i18n::s('Express yourself, and say what you think.'));
	}

	// attach a file, if upload is allowed
	if(Files::are_allowed($anchor, $item)) {
		Skin::define_img('FILES_UPLOAD_IMG', 'files/upload.gif');
		$context['page_tools'][] = Skin::build_link('files/edit.php?anchor='.urlencode('article:'.$item['id']), FILES_UPLOAD_IMG.i18n::s('Upload a file'), 'basic', i18n::s('Attach related files.'));
	}

	// add a link
	if(Links::are_allowed($anchor, $item)) {
		Skin::define_img('LINKS_ADD_IMG', 'links/add.gif');
		$context['page_tools'][] = Skin::build_link('links/edit.php?anchor='.urlencode('article:'.$item['id']), LINKS_ADD_IMG.i18n::s('Add a link'), 'basic', i18n::s('Contribute to the web and link to relevant pages.'));
	}

	// post an image, if upload is allowed
	if(Images::are_allowed($anchor, $item)) {
		Skin::define_img('IMAGES_ADD_IMG', 'images/add.gif');
		$context['page_tools'][] = Skin::build_link('images/edit.php?anchor='.urlencode('article:'.$item['id']), IMAGES_ADD_IMG.i18n::s('Add an image'), 'basic', i18n::s('You can upload a camera shot, a drawing, or another image file.'));
	}

	// modify this page
	if($editable) {
		Skin::define_img('ARTICLES_EDIT_IMG', 'articles/edit.gif');
		if(!is_object($overlay) || (!$label = $overlay->get_label('edit_command')))
			$label = i18n::s('Edit this page');
		$context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'edit'), ARTICLES_EDIT_IMG.$label, 'basic', i18n::s('Press [e] to edit'), FALSE, 'e');
	}

	// access previous versions, if any
	if($has_versions && Articles::is_owned($anchor, $item)) {
		Skin::define_img('ARTICLES_VERSIONS_IMG', 'articles/versions.gif');
		$context['page_tools'][] = Skin::build_link(Versions::get_url('article:'.$item['id'], 'list'), ARTICLES_VERSIONS_IMG.i18n::s('Versions'), 'basic', i18n::s('Restore a previous version if necessary'));
	}
	
	// publish this page
	if($publishable) {
	
		if(!isset($item['publish_date']) || ($item['publish_date'] <= NULL_DATE)) {
			Skin::define_img('ARTICLES_PUBLISH_IMG', 'articles/publish.gif');
			$context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'publish'), ARTICLES_PUBLISH_IMG.i18n::s('Publish'));
		}

	}

	// review command provided to associates and section editors
	if(Surfer::is_associate() || (is_object($anchor) && $anchor->is_editable())) {
		Skin::define_img('ARTICLES_STAMP_IMG', 'articles/stamp.gif');
		$context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'stamp'), ARTICLES_STAMP_IMG.i18n::s('Stamp'));
	}
	
	// lock command provided to associates and authenticated editors
	if(Surfer::is_associate() || (Surfer::is_member() && is_object($anchor) && $anchor->is_editable())) {
	
		if(!isset($item['locked']) || ($item['locked'] == 'N')) {
			Skin::define_img('ARTICLES_LOCK_IMG', 'articles/lock.gif');
			$context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'lock'), ARTICLES_LOCK_IMG.i18n::s('Lock'));
		} else {
			Skin::define_img('ARTICLES_UNLOCK_IMG', 'articles/unlock.gif');
			$context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'lock'), ARTICLES_UNLOCK_IMG.i18n::s('Unlock'));
		}
	}
	
	// delete command provided to associates and section editors
	if(Surfer::is_associate() || (is_object($anchor) && $anchor->is_editable())) {
		Skin::define_img('ARTICLES_DELETE_IMG', 'articles/delete.gif');
		$context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'delete'), ARTICLES_DELETE_IMG.i18n::s('Delete this page'));
	}
	
	// duplicate command provided to associates and section editors
	if(isset($item['id']) && !$zoom_type && (Surfer::is_associate() || (is_object($anchor) && $anchor->is_editable()))) {
		Skin::define_img('ARTICLES_DUPLICATE_IMG', 'articles/duplicate.gif');
		$context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'duplicate'), ARTICLES_DUPLICATE_IMG.i18n::s('Duplicate'));
	}

	// assign command provided to page owners
 	if(Articles::is_owned($anchor, $item)) {
 		Skin::define_img('ARTICLES_ASSIGN_IMG', 'articles/assign.gif');
 		$context['page_tools'][] = Skin::build_link(Users::get_url('article:'.$item['id'], 'select'), ARTICLES_ASSIGN_IMG.i18n::s('Manage editors'));
 	}
	
	// add extra information from this item, if any
	if(isset($item['extra']) && $item['extra'])
		$context['components']['boxes'] = Codes::beautify_extra($item['extra']);

	// add extra information from the overlay, if any
	if(is_object($overlay))
		$context['components']['overlay'] = $overlay->get_text('extra', $item);

	// 'Share' box
	//
	$lines = array();

	// mail this page
	if((Articles::is_owned($anchor, $item) || ($item['active'] == 'Y')) && isset($context['with_email']) && ($context['with_email'] == 'Y')) {
		Skin::define_img('ARTICLES_INVITE_IMG', 'articles/invite.gif');
		$lines[] = Skin::build_link(Articles::get_url($item['id'], 'invite'), ARTICLES_INVITE_IMG.i18n::s('Invite participants'), 'basic', i18n::s('Spread the word'));
	}

	// the command to track back
	if(Surfer::is_logged()) {
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
			$lines[] = Skin::build_link(Articles::get_url($id, 'fetch_as_pdf'), ARTICLES_PDF_IMG.i18n::s('Save as PDF'), 'basic', i18n::s('Save as PDF'));

			// open in Word
			Skin::define_img('ARTICLES_WORD_IMG', 'articles/export_word.gif');
			$lines[] = Skin::build_link(Articles::get_url($id, 'fetch_as_msword'), ARTICLES_WORD_IMG.i18n::s('Copy in MS-Word'), 'basic', i18n::s('Copy in MS-Word'));

		}
	}

	// export to XML command provided to associates -- complex command
// 	if(!$zoom_type && Surfer::is_associate() && Surfer::has_all())
// 		$lines[] = Skin::build_link(Articles::get_url($item['id'], 'export'), i18n::s('Export to XML'), 'basic');

	// print this page
	if(Surfer::is_logged() || (isset($context['with_anonymous_export_tools']) && ($context['with_anonymous_export_tools'] == 'Y'))) {
		Skin::define_img('TOOLS_PRINT_IMG', 'tools/print.gif');
		$lines[] = Skin::build_link(Articles::get_url($id, 'print'), TOOLS_PRINT_IMG.i18n::s('Print this page'), 'basic', i18n::s('Get a paper copy of this page.'));
	}

	// in a side box
	if(count($lines))
		$context['components']['share'] = Skin::build_box(i18n::s('Share'), Skin::finalize_list($lines, 'tools'), 'extra', 'share');

	// 'Information channels' box
	//
	$lines = array();

	// watch command is provided to logged surfers
	if(Surfer::get_id() && !$zoom_type) {

		$link = Users::get_url('article:'.$item['id'], 'track');

		if($in_watch_list)
			$label = i18n::s('Forget this page');
		else
			$label = i18n::s('Watch this page');

		Skin::define_img('TOOLS_WATCH_IMG', 'tools/watch.gif');
		$lines[] = Skin::build_link($link, TOOLS_WATCH_IMG.$label, 'basic', i18n::s('Manage your watch list'));
	}

	// get news from rss
	if(isset($item['id']) && (!isset($context['skins_general_without_feed']) || ($context['skins_general_without_feed'] != 'Y')) ) {

		// list of attached files
		$lines[] = Skin::build_link($context['url_to_home'].$context['url_to_root'].Files::get_url('article:'.$item['id'], 'feed'), i18n::s('Recent files'), 'xml');

		// comments are allowed
		if(Comments::are_allowed($anchor, $item)) {
			$lines[] = Skin::build_link($context['url_to_home'].$context['url_to_root'].Comments::get_url('article:'.$item['id'], 'feed'), i18n::s('Recent comments'), 'xml');

			// public aggregators
// 			if(!isset($context['without_internet_visibility']) || ($context['without_internet_visibility'] != 'Y'))
// 				$lines[] = join(BR, Skin::build_subscribers($context['url_to_home'].$context['url_to_root'].Comments::get_url('article:'.$item['id'], 'feed'), $item['title']));
		}
	}

	// in a side box
	if(count($lines))
		$context['components']['channels'] = Skin::build_box(i18n::s('Monitor'), join(BR, $lines), 'extra', 'feeds');

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
			$context['components']['twins'] = Skin::build_box(i18n::s('Related'), $box['text'], 'extra', 'twins');

	}

	// links to previous and next pages in this section, if any
	if(is_object($anchor) && !$anchor->has_option('no_neighbours') && ($context['skin_variant'] != 'mobile')) {

		// build a nice sidebar box
		if(isset($neighbours) && ($content = Skin::neighbours($neighbours, 'sidebar')))
			$context['components']['neighbours'] = Skin::build_box(i18n::s('Navigation'), $content, 'navigation', 'neighbours');

	}

	// the contextual menu, in a navigation box, if this has not been disabled
	if( (!is_object($anchor) || !$anchor->has_option('no_contextual_menu'))
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
		$context['components']['contextual'] = Skin::build_box($box_title, $menu, 'navigation', 'contextual_menu', $box_url, $box_popup);
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
		if(Categories::are_allowed($anchor, $item))
			$items = array_merge($items, array( Categories::get_url('article:'.$item['id'], 'select') => i18n::s('Assign categories') ));

		// actually render the html for the section
		if(is_array($box['bar']))
			$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');
		if(is_array($items))
			$box['text'] .= Skin::build_list($items, 'compact');
		if($box['text'])
			$context['components']['categories'] = Skin::build_box(i18n::s('See also'), $box['text'], 'navigation', 'categories');

	}

	// referrals, if any
	$context['components']['referrals'] =& Skin::build_referrals(Articles::get_permalink($item));

	//
	// the AJAX part
	//

	if(!isset($item['locked']) || ($item['locked'] != 'Y')) {
	}

}

// render the skin
render_skin();

?>