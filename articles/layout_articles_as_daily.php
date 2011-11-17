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
Class Layout_articles_as_daily extends Layout_interface {

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
		if(!SQL::count($result))
			return $text;

		// build a list of articles
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'links/links.php';
		include_once $context['path_to_root'].'overlays/overlay.php';
		while($item =& SQL::fetch($result)) {

			// three components per box
			$box = array();
			$box['date'] = '';
			$box['title'] = '';
			$box['content'] = '';

			// get the related overlay, if any
			$overlay = Overlay::load($item, 'article:'.$item['id']);

			// get the anchor
			$anchor =& Anchors::get($item['anchor']);

			// permalink
			$url = Articles::get_permalink($item);

			// make a live title
			if(is_object($overlay))
				$box['title'] .= Codes::beautify_title($overlay->get_text('title', $item));
			else
				$box['title'] = Codes::beautify_title($item['title']);

			// make a clickable title
			$box['title'] = Skin::build_link($url, $box['title'], 'basic');

			// what's the date today?
			if(isset($item['publish_date']) && ($item['publish_date'] > NULL_DATE))
				$box['date'] .= Skin::build_date($item['publish_date'], 'publishing');

			// the icon to put aside - never use anchor images
			if($item['icon_url'])
				$box['content'] .= '<a href="'.$context['url_to_root'].$url.'"><img src="'.$item['icon_url'].'" class="left_image" alt="" /></a>';

			// details
			$details = array();

			// flag articles updated recently
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
				$details[] = EXPIRED_FLAG;
			elseif($item['create_date'] >= $context['fresh'])
				$details[] = NEW_FLAG;
			elseif($item['edit_date'] >= $context['fresh'])
				$detaisl[] = UPDATED_FLAG;

			// signal restricted and private articles
			if($item['active'] == 'N')
				$details[] = PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$details[] = RESTRICTED_FLAG;

			// rating
			if($item['rating_count'] && !(is_object($anchor) && $anchor->has_option('without_rating')))
				$details[] = Skin::build_link(Articles::get_url($item['id'], 'rate'), Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])), 'basic');

			// show details
			if(count($details))
				$box['content'] .= '<p class="details">'.implode(' ~ ', $details).'</p>'."\n";

			// list categories by title, if any
			if($items =& Members::list_categories_by_title_for_member('article:'.$item['id'], 0, 7, 'raw')) {
				$tags = array();
				foreach($items as $id => $attributes) {

					// add background color to distinguish this category against others
					if(isset($attributes['background_color']) && $attributes['background_color'])
						$attributes['title'] = '<span style="background-color: '.$attributes['background_color'].'; padding: 0 3px 0 3px;">'.$attributes['title'].'</span>';

					$tags[] = Skin::build_link(Categories::get_permalink($attributes), $attributes['title'], 'basic');
				}
				$box['content'] .= '<p class="tags">'.implode(' ', $tags).'</p>';
			}

			// the introduction text, if any
			if(is_object($overlay))
				$box['content'] .= Skin::build_block($overlay->get_text('introduction', $item), 'introduction');
			else
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

			// build a simple box for this post
			$text .= '<div class="post">'
				.'<div class="date">'.$box['date'].'</div>'
				.'<h2><span>'.$box['title'].'</span></h2>'
				.'<div class="content">'.$box['content'].'</div>'
				.'</div>';

		}

		// end of processing
		SQL::free($result);
		return $text;
	}
}

?>
