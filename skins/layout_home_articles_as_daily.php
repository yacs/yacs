<?php
/**
 * layout articles as a daily weblog do
 *
 * This layout is made of dates, followed by articles displayed in boxes.
 * Each date is written before a horizontal ruler (e.g., &lt;hr&gt;).
 * Each article has its own box.
 * Post title is used as box title.
 * Box content is made of several components:
 * - a time stamp, followed by a link to the section, plus up to three links to related categories
 * - page introduction, if any
 * - post content, up to some hundred words
 * - a menu bar linking the entry to its permanent reference, to attached files, to comments and to related links;
 * also provides commands to add a new comment or a new link.
 *
 * Largely inspired by [link]http://joi.ito.com/[/link].
 *
 * @link http://joi.ito.com/
 * @link http://www.movabletype.org/docs/mttrackback.html TrackBack Technical Specification
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Thierry Pinelli (ThierryP)
 * @tester Timster
 * @tester Alain Lesage (Lasares)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_home_articles_as_daily extends Layout_interface {

	/**
	 * the preferred order for items
	 *
	 * @return string to be used in requests to the database
	 *
	 * @see skins/layout.php
	 */
	function items_order() {
		return 'publication';
	}

	/**
	 * the preferred number of items for this layout
	 *
	 * @return 10, to list the ten most recent entries
	 *
	 * @see skins/layout.php
	 */
	function items_per_page() {
		return 10;
	}

	/**
	 * list articles as a daily weblog do
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	 *
	 * @see skins/layout.php
	**/
	function &layout(&$result) {
		global $context;

		// we return some text
		$text = '';

		// empty list
		if(!SQL::count($result)) {
			$label = i18n::s('No page to display.');
			if(Surfer::is_associate())
				$label .= ' '.sprintf(i18n::s('Use the %s to populate this server.'), Skin::build_link('help/populate.php', i18n::s('Content Assistant'), 'shortcut'));
			$output = '<p>'.$label.'</p>';
			return $output;
		}

		// flag articles updated recently
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));

		// build a list of articles
		$box['content'] = '';
		$box['title'] = '';
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'links/links.php';
		include_once $context['path_to_root'].'overlays/overlay.php';
		while($item =& SQL::fetch($result)) {

			// get the related overlay, if any
			$overlay = Overlay::load($item);

			// get the anchor
			$anchor =& Anchors::get($item['anchor']);

			// permalink
			$url =& Articles::get_permalink($item);

			// what's the date today?
			if(isset($item['publish_date']) && ($item['publish_date'] > NULL_DATE))
				$current_date = substr($item['publish_date'], 0, 10);
			else
				$current_date = DRAFT_FLAG.i18n::s('not published');

			// very first box
			if(!isset($previous_date)) {
				$text .= '<div class="newest">'."\n";
				$in_north = TRUE;
				$text .= '<p class="date">'.Skin::build_date($item['publish_date'], 'no_hour')."</p>\n";
				$previous_date = $current_date;
			}

			// not the same publication date
			if($previous_date != $current_date) {
				if($in_north)
					$text .= '</div>'.BR.BR."\n";
				$in_north = FALSE;
				if(isset($item['publish_date']) && ($item['publish_date'] > NULL_DATE))
					$text .= '<p class="date">'.Skin::build_date($item['publish_date'], 'no_hour')."</p>\n";
				$previous_date = $current_date;
			}

			// always flag articles to be published
			if(!isset($item['publish_date']) || ($item['publish_date'] <= NULL_DATE))
				$text .= '<p class="date">'.$current_date."</p>\n";

			// make a live title
			if(is_object($overlay))
				$box['title'] = Codes::beautify_title($overlay->get_text('title', $item));
			else
				$box['title'] = Codes::beautify_title($item['title']);

			// the icon to put aside - never use anchor images
			if($item['icon_url'])
				$box['content'] .= '<a href="'.$context['url_to_root'].$url.'"><img src="'.$item['icon_url'].'" class="left_image" alt="" /></a>';

			// details
			$details = array();

			// flag articles updated recently
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $now))
				$details[] = EXPIRED_FLAG;
			elseif($item['create_date'] >= $dead_line)
				$details[] = NEW_FLAG;
			elseif($item['edit_date'] >= $dead_line)
				$detaisl[] = UPDATED_FLAG;

			// publication hour
// 			if(isset($item['publish_date']) && ($item['publish_date'] > NULL_DATE))
// 				$details[] = Skin::build_time($item['publish_date']);

			// signal restricted and private articles
			if($item['active'] == 'N')
				$details[] = PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$details[] = RESTRICTED_FLAG;

			// link to the anchor page
			if(is_object($anchor))
				$details[] = Skin::build_link($anchor->get_url(), $anchor->get_title(), 'basic');

			// list up to three categories by title, if any
			if($items =& Members::list_categories_by_title_for_member('article:'.$item['id'], 0, 3, 'raw')) {
				foreach($items as $id => $attributes)
					$details[] = Skin::build_link(Categories::get_permalink($attributes), $attributes['title'], 'basic');
			}

			// rating
			if($item['rating_count'] && !(is_object($anchor) && $anchor->has_option('without_rating')))
				$details[] = Skin::build_link(Articles::get_url($item['id'], 'rate'), Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])), 'basic');

			// show details
			if(count($details))
				$box['content'] .= '<p class="details">'.implode(' ~ ', $details).'</p>'."\n";

			// the introduction text, if any
			$box['content'] .= Skin::build_block($item['introduction'], 'introduction');

			// insert overlay data, if any
			if(is_object($overlay))
				$box['content'] .= $overlay->get_text('list', $item);

			// the description
			$box['content'] .= Skin::build_block($item['description'], 'description', '', $item['options']);

			// a compact list of attached files
			if($count = Files::count_for_anchor('article:'.$item['id'])) {

				// list files by date (default) or by title (option files_by_title)
				if(Articles::has_option('files_by_title', $anchor, $item))
					$items = Files::list_by_title_for_anchor('article:'.$item['id'], 0, FILES_PER_PAGE, 'compact');
				else
					$items = Files::list_by_date_for_anchor('article:'.$item['id'], 0, FILES_PER_PAGE, 'compact');
				if(is_array($items))
					$items = Skin::build_list($items, 'compact');

				if($items)
					$box['content'] .= Skin::build_box(i18n::s('Files'), $items, 'header2');
			}

			// build a menu
			$menu = array();

			// read the article
			$menu[] = Skin::build_link($url, i18n::s('Permalink'), 'span');

			// info on related files
			if($count = Files::count_for_anchor('article:'.$item['id'], TRUE))
				$menu[] = Skin::build_link($url.'#files', sprintf(i18n::ns('%d file', '%d files', $count), $count), 'span');

			// info on related comments
			if($count = Comments::count_for_anchor('article:'.$item['id']))
				$menu[] = Skin::build_link(Comments::get_url('article:'.$item['id'], 'list'), sprintf(i18n::ns('%d comment', '%d comments', $count), $count), 'span');

			// comment
			if(Comments::allow_creation($anchor, $item))
				$menu[] = Skin::build_link(Comments::get_url('article:'.$item['id'], 'comment'), i18n::s('Discuss'), 'span');

			// info on related links
			if($count = Links::count_for_anchor('article:'.$item['id'], TRUE))
				$menu[] = Skin::build_link($url.'#links', sprintf(i18n::ns('%d link', '%d links', $count), $count), 'span');

			// trackback
			if(Links::allow_trackback())
				$menu[] = Skin::build_link('links/trackback.php?anchor='.urlencode('article:'.$item['id']), i18n::s('Reference this page'), 'span');

			// a menu bar, but flushed to the right
			if(count($menu))
				$box['content'] .= '<p class="menu_bar right" style="clear: left;">'.MENU_PREFIX.implode(MENU_SEPARATOR, $menu).MENU_SUFFIX."</p>\n";

			$text .= Skin::build_box($box['title'], $box['content'], 'header1', 'article_'.$item['id']);
			$box['content'] = '';
			$box['title'] = '';
		}

		// close the on-going box
		if($in_north)
			$text .= '</div>'."\n";

		// end of processing
		SQL::free($result);
		return $text;
	}
}

?>
