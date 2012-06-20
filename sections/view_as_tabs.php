<?php
/**
 * view a section as tabbed panels
 *
 * This script is included into [script]sections/view.php[/script], when the
 * option is set to 'view_as_tabs'.
 *
 * It handles the same building blocks than [script]sections/view.php[/script],
 * except that they are featured in tabbed panels:
 * - Information - with details, introduction, main text and gadget boxes.
 * - Pages - for contained articles
 * - Attachments - with files and links
 * - Discussion - A thread of contributions, not in real-time
 * - Sections - for contained sections (including active and inactive)
 * - Persons - The list of section editors
 *
 * This script is loaded by sections/view.php.
 *
 * If this section, or one of its anchor, specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// loaded from sections/view.php
defined('YACS') or exit('Script must be included');

//
// rewrite $context['page_details'] because some details have moved to tabs
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
		$details[] = RESTRICTED_FLAG.i18n::s('Community - Access is granted to any identified surfer');

	// restricted to associates
	if($item['active'] == 'N')
		$details[] = PRIVATE_FLAG.i18n::s('Private - Access is restricted to selected persons');

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
	if(Surfer::is_empowered() && Surfer::is_logged() && ($item['activation_date'] > $context['now']))
		$details[] = DRAFT_FLAG.' '.sprintf(i18n::s('Section will be activated %s'), Skin::build_date($item['activation_date']));

	// expired section
	if(Surfer::is_empowered() && Surfer::is_logged() && ($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
		$details[] = EXPIRED_FLAG.' '.sprintf(i18n::s('Section has expired %s'), Skin::build_date($item['expiry_date']));

	// display details, if any
	if(count($details))
		$text .= ucfirst(implode(BR, $details)).BR;

	// other details
	$details =& Sections::build_dates($anchor, $item);

	// additional details for associates and editors
	if(Surfer::is_empowered()) {

		// the number of hits
		if($item['hits'] > 1)
			$details[] = Skin::build_number($item['hits'], i18n::s('hits'));

		// rank for this section
		if((intval($item['rank']) != 10000) && Sections::is_owned($item, $anchor))
			$details[] = '{'.$item['rank'].'}';

		// locked section
		if($item['locked'] ==  'Y')
			$details[] = LOCKED_FLAG.' '.i18n::s('page is locked.');

	}

	// inline details
	if(count($details))
		$text .= ucfirst(implode(', ', $details));

	// reference this item
	if(Surfer::is_logged()) {
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
	$context['page_details'] = $text;
}

//
// main panel -- $context['text']
//

// insert anchor prefix
if(is_object($anchor))
	$context['text'] .= $anchor->get_prefix();

// links to previous and next pages, if any
if(isset($neighbours) && $neighbours)
	$context['text'] .= Skin::neighbours($neighbours, 'manual');

// only at the first page
if($page == 1) {

	// the introduction text, if any
	if(is_object($overlay))
		$context['text'] .= Skin::build_block($overlay->get_text('introduction', $item), 'introduction');
	elseif(isset($item['introduction']) && trim($item['introduction']))
		$context['text'] .= Skin::build_block($item['introduction'], 'introduction');

}

//
// panels
//
$panels = array();

//
// information tab
//
$text = '';

// in this tab we page in sections, articles, and comments
if(!$zoom_type || ($zoom_type == 'articles') || ($zoom_type == 'comments') || ($zoom_type == 'sections')) {

	// only at the first page
	if($page == 1) {

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
			$prefix = Sections::get_url($item['id'], 'navigate', 'pages');
			$page_menu = array_merge($page_menu, Skin::navigate($home, $prefix, count($pages), 1, $page));

			$text .= Skin::build_list($page_menu, 'menu_bar');
		}
	}

	// gadget boxes
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
		$text .= '<p id="gadgets_prefix"> </p>'."\n".$content.'<p id="gadgets_suffix"> </p>'."\n";

	// the list of related articles if not at another follow-up page
	if(!$zoom_type || ($zoom_type == 'articles')) {

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
			if(Articles::allow_creation($item, $anchor)) {

				Skin::define_img('ARTICLES_ADD_IMG', 'articles/add.gif');
				$url = 'articles/edit.php?anchor='.urlencode('section:'.$item['id']);
				if(is_object($content_overlay) && ($label = $content_overlay->get_label('new_command', 'articles')))
					;
				else
					$label = ARTICLES_ADD_IMG.i18n::s('Add a page');
				$box['top_bar'] += array( $url => $label );

			}

			// list pages under preparation
			if(($order == 'publication') && ($items =& Articles::list_for_anchor_by('draft', 'section:'.$item['id'], 0, 20, 'compact'))) {
				if(is_array($items))
					$items = Skin::build_list($items, 'compact');
				$box['top_bar'] += array('_draft' => Skin::build_sliding_box(i18n::s('Draft pages'), $items));
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

	// title label
	$title_label = '';
	if(is_object($overlay))
		$title_label = $overlay->get_label('list_title', 'comments');
	if(!$title_label)
		$title_label = i18n::s('Comments');

	// get a layout for these comments
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

	// new comments are allowed
	if(Comments::allow_creation($anchor, $item, 'section'))
		$box['text'] .= Comments::get_form('section:'.$item['id']);

	// a navigation bar for these comments
	if($count = Comments::count_for_anchor('section:'.$item['id'])) {
		if($count > 20)
			$box['bar'] += array('_count' => sprintf(i18n::s('%d comments'), $count));

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

	// build a box
	if($box['text']) {

		// show commands
		if(count($box['bar'])) {

			// append the menu bar at the end
			$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');

			// don't repeat commands before the box
			$box['bar'] = array();

		}
	}

	// integrate commands in bottom menu
	if(count($box['bar']))
		$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');

	// there is some box content
	if(trim($box['text']))
		$text .= Skin::build_box($title_label, $box['text'], 'header1', '_discussion');

	// if not at another follow-up page
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
			if(is_object($layout))
				$items_per_page = $layout->items_per_page();
			else
				$items_per_page = SECTIONS_PER_PAGE;

			// build a complete box
			$box = array('top_bar' => array(), 'text' => '', 'bottom_bar' => array());

			// the command to add a new section
			if(Sections::allow_creation($item, $anchor)) {
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
		if(count($items))
			$items =& Skin::build_list($items, 'compact');

		// displayed as another box
		if($items)
			$context['page_menu'] += array('_other_sections' => Skin::build_sliding_box(i18n::s('Other sections'), $items, NULL, TRUE, TRUE));

	}

	// trailer information
	//

	// add trailer information from the overlay, if any
	if(is_object($overlay))
		$text .= $overlay->get_text('trailer', $item);

	// add trailer information from this item, if any
	if(isset($item['trailer']) && trim($item['trailer']))
		$text .= Codes::beautify($item['trailer']);

	// insert anchor suffix
	if(is_object($anchor))
		$text .= $anchor->get_suffix();

}

// display in a separate panel
if(trim($text))
	$panels[] = array('information', i18n::s('Information'), 'information_panel', $text);

//
// append tabs from the overlay, if any -- they have been captured in sections/view.php
//
if(isset($context['tabs']) && is_array($context['tabs']))
	$panels = array_merge($panels, $context['tabs']);

//
// attachments
//
$attachments = '';
$attachments_count = 0;

// the list of related files if not at another follow-up page
if(!$zoom_type || ($zoom_type == 'files')) {

	// list files only to people able to change the page
	if(Sections::allow_modification($item, $anchor))
		$embedded = NULL;
	else
		$embedded = Codes::list_embedded($item['description']);

	// build a complete box
	$box = array('bar' => array(), 'text' => '');

	// count the number of files in this section
	if($count = Files::count_for_anchor('section:'.$item['id'], FALSE, $embedded)) {
		$attachments_count += $count;
		if($count > 20)
			$box['bar'] += array('_count' => sprintf(i18n::ns('%d file', '%d files', $count), $count));

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

		// navigation commands for files
		$home = Sections::get_permalink($item);
		$prefix = Sections::get_url($item['id'], 'navigate', 'files');
		$box['bar'] = array_merge($box['bar'],
			Skin::navigate($home, $prefix, $count, FILES_PER_PAGE, $zoom_index));

	}

	// the command to post a new file -- check 'with_files' option
	if(Files::allow_creation($anchor, $item, 'section')) {
		Skin::define_img('FILES_UPLOAD_IMG', 'files/upload.gif');
		$box['bar'] += array('files/edit.php?anchor='.urlencode('section:'.$item['id']) => FILES_UPLOAD_IMG.i18n::s('Add a file') );
	}

	// integrate the menu bar
	if(count($box['bar']))
		$box['text'] = Skin::build_list($box['bar'], 'menu_bar').$box['text'];

	// there is some box content
	if(trim($box['text']))
		$attachments .= Skin::build_box(i18n::s('Files'), $box['text'], 'header1', 'files');

}

// the list of related links if not at another follow-up page
if(!$zoom_type || ($zoom_type == 'links')) {

	// build a complete box
	$box = array('bar' => array(), 'text' => '');

	// count the number of links in this section
	if($count = Links::count_for_anchor('section:'.$item['id'])) {
		$attachments_count += $count;
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
	if(Links::allow_creation($anchor, $item, 'section')) {
		Skin::define_img('LINKS_ADD_IMG', 'links/add.gif');
		$box['bar'] += array('links/edit.php?anchor='.urlencode('section:'.$item['id']) => LINKS_ADD_IMG.i18n::s('Add a link') );
	}

	// integrate commands
	if(count($box['bar']))
		$box['text'] = Skin::build_list($box['bar'], 'menu_bar').$box['text'];

	// there is some box content
	if(trim($box['text']))
		$attachments .= Skin::build_box(i18n::s('Links'), $box['text'], 'header1', 'links');

}

// display in a separate panel
if(trim($attachments)) {
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

	// list participants
	$rows = array();
	Skin::define_img('CHECKED_IMG', 'ajax/accept.png', '*');
	$offset = ($zoom_index - 1) * USERS_LIST_SIZE;

	// list editors of this section, and of parent sections
	if($items = Sections::list_editors_by_name($item, 0, 1000, 'watch')) {
		foreach($items as $user_id => $user_label) {
			$owner_state = '';
			if($user_id == $item['owner_id'])
				$owner_state = CHECKED_IMG;
			$editor_state = CHECKED_IMG;
			$watcher_state = '';
			if(Members::check($anchors, 'user:'.$user_id))
				$watcher_state = CHECKED_IMG;
			$rows[$user_id] = array($user_label, $watcher_state, $editor_state, $owner_state);
		}
	}

	// watchers
	if($items = Sections::list_watchers_by_posts($item, 0, 1000, 'watch')) {
		foreach($items as $user_id => $user_label) {

			// add the checkmark to existing row
			if(isset($rows[$user_id]))
				$rows[$user_id][1] = CHECKED_IMG;

			// append a new row
			else {
				$owner = '';
				if($user_id == $item['owner_id'])
					$owner = CHECKED_IMG;
				$editor = '';
				$watcher = CHECKED_IMG;
				$rows[$user_id] = array($user_label, $watcher, $editor, $owner);
			}
		}
	}

	// count
	if($count = count($rows))
		$box['bar'] += array('_count' => sprintf(i18n::ns('%d participant', '%d participants', $count), $count));

	// add to the watch list -- $in_watch_list is set in sections/view.php
	if(Surfer::get_id() && ($in_watch_list == 'N')) {
		Skin::define_img('TOOLS_WATCH_IMG', 'tools/watch.gif');
		$box['bar'] += array(Users::get_url('section:'.$item['id'], 'track') => TOOLS_WATCH_IMG.i18n::s('Watch this section'));
	}

	// invite participants, for owners
	if(Sections::is_owned($item, $anchor, TRUE) && isset($context['with_email']) && ($context['with_email'] == 'Y')) {
		Skin::define_img('SECTIONS_INVITE_IMG', 'sections/invite.gif');
		$box['bar'] += array(Sections::get_url($item['id'], 'invite') => SECTIONS_INVITE_IMG.i18n::s('Invite participants'));
	}

	// notify participants
	if(($count > 1) && Sections::allow_message($item, $anchor) && isset($context['with_email']) && ($context['with_email'] == 'Y')) {
		Skin::define_img('SECTIONS_EMAIL_IMG', 'sections/email.gif');
		$box['bar'] += array(Sections::get_url($item['id'], 'mail') => SECTIONS_EMAIL_IMG.i18n::s('Notify participants'));
	}

	// manage editors, for owners
	if(Sections::is_owned($item, $anchor, TRUE) || Surfer::is_associate()) {
		Skin::define_img('SECTIONS_ASSIGN_IMG', 'sections/assign.gif');
		$box['bar'] += array(Users::get_url('section:'.$item['id'], 'select') => SECTIONS_ASSIGN_IMG.i18n::s('Manage participants'));

	// leave this section, for editors
	} elseif(Sections::is_assigned($item['id'])) {
		Skin::define_img('SECTIONS_ASSIGN_IMG', 'sections/assign.gif');
		$box['bar'] += array(Users::get_url('section:'.$item['id'], 'leave') => SECTIONS_ASSIGN_IMG.i18n::s('Leave this section'));
	}

	// headers
	$headers = array(i18n::s('Person'), i18n::s('Watcher'), i18n::s('Editor'), i18n::s('Owner'));

	// layout columns
	if($rows)
		$box['text'] .= Skin::table($headers, $rows, 'grid');

	// actually render the html
	$users .= Skin::build_content(NULL, NULL, $box['text'], $box['bar']);

	// slight correction
	if(count($rows) > $users_count)
		$users_count = count($rows);

}

// display in a separate panel
if($users) {
	$label = i18n::s('Persons');
	if($users_count)
		$label .= ' ('.$users_count.')';
	$panels[] = array('users', $label, 'users_panel', $users);
}


//
// assemble all tabs
//
$context['text'] .= Skin::build_tabs($panels);

// buttons to display previous and next pages, if any
if(isset($neighbours) && $neighbours)
	$context['text'] .= Skin::neighbours($neighbours, 'manual');

//
// the extra panel -- most content is cached, except commands specific to current surfer
//

// page tools
//

// commands to add pages
if(Articles::allow_creation($item, $anchor)) {

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
if(Sections::allow_creation($item, $anchor)) {
	Skin::define_img('SECTIONS_ADD_IMG', 'sections/add.gif');
	$context['page_tools'][] = Skin::build_link('sections/edit.php?anchor='.urlencode('section:'.$item['id']), SECTIONS_ADD_IMG.i18n::s('Add a section'), 'basic', i18n::s('Add a section'));
}

// comment this page if anchor does not prevent it
if(Comments::allow_creation($anchor, $item, 'section')) {
	Skin::define_img('COMMENTS_ADD_IMG', 'comments/add.gif');
	$context['page_tools'][] = Skin::build_link(Comments::get_url('section:'.$item['id'], 'comment'), COMMENTS_ADD_IMG.i18n::s('Post a comment'), 'basic', i18n::s('Express yourself, and say what you think.'));
}

// add a file, if upload is allowed
if(Files::allow_creation($anchor, $item, 'section')) {
	Skin::define_img('FILES_UPLOAD_IMG', 'files/upload.gif');
	$context['page_tools'][] = Skin::build_link('files/edit.php?anchor='.urlencode('section:'.$item['id']), FILES_UPLOAD_IMG.i18n::s('Add a file'), 'basic', i18n::s('Attach related files.'));
}

// add a link
if(Links::allow_creation($anchor, $item, 'section')) {
	Skin::define_img('LINKS_ADD_IMG', 'links/add.gif');
	$context['page_tools'][] = Skin::build_link('links/edit.php?anchor='.urlencode('section:'.$item['id']), LINKS_ADD_IMG.i18n::s('Add a link'), 'basic', i18n::s('Contribute to the web and link to relevant pages.'));
}

// post an image, if upload is allowed
if(Images::allow_creation($anchor, $item, 'section')) {
	Skin::define_img('IMAGES_ADD_IMG', 'images/add.gif');
	$context['page_tools'][] = Skin::build_link('images/edit.php?anchor='.urlencode('section:'.$item['id']), IMAGES_ADD_IMG.i18n::s('Add an image'), 'basic', i18n::s('You can upload a camera shot, a drawing, or another image file.'));
}

// ensure that the surfer can change content
if(Sections::allow_modification($item, $anchor)) {

	// modify this page
	Skin::define_img('SECTIONS_EDIT_IMG', 'sections/edit.gif');
	if(!is_object($overlay) || (!$label = $overlay->get_label('edit_command', 'sections')))
		$label = i18n::s('Edit this section');
	$context['page_tools'][] = Skin::build_link(Sections::get_url($item['id'], 'edit'), SECTIONS_EDIT_IMG.$label, 'basic', i18n::s('Press [e] to edit'), FALSE, 'e');

}

// commands for section owners
if(Sections::is_owned($item, $anchor) || Surfer::is_associate()) {

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

	// duplicate command provided to container owners
	Skin::define_img('SECTIONS_DUPLICATE_IMG', 'sections/duplicate.gif');
	$context['page_tools'][] = Skin::build_link(Sections::get_url($item['id'], 'duplicate'), SECTIONS_DUPLICATE_IMG.i18n::s('Duplicate this section'));

}


// use date of last modification into etag computation
if(isset($item['edit_date']))
	$context['page_date'] = $item['edit_date'];

// render the skin
render_skin();

?>
