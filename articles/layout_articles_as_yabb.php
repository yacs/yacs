<?php
/**
 * layout articles as topics in a yabb forum
 *
 * This script layouts articles as topics in a discussion board.
 *
 * @see sections/view.php
 *
 * @link http://www.yabbforum.com/ Yet Another Bulletin Board
 *
 * The title of each article is also a link to the article itself.
 * A title attribute of the link displays the reference to use to link to the page (Thanks to Anatoly).
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Thierry Pinelli (ThierryP)
 * @tester Anatoly
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_yabb extends Layout_interface {

	/**
	 * the preferred number of items for this layout
	 *
	 * @return 50
	 *
	 * @see skins/layout.php
	 */
	function items_per_page() {
		return 50;
	}

	/**
	 * list articles as topics in a forum
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	**/
	function &layout(&$result) {
		global $context;

		// we return some text
		$text = '';

		// empty list
		if(!SQL::count($result))
			return $text;

		// flag articles updated recently
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));

		// build a list of articles
		$rows = array();
		include_once $context['path_to_root'].'categories/categories.php';
		include_once $context['path_to_root'].'comments/layout_comments_as_yabb.php';
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'overlays/overlay.php';
		while($item =& SQL::fetch($result)) {

			// get the related overlay
			$overlay = Overlay::load($item);

			// get the anchor
			$anchor =& Anchors::get($item['anchor']);

			// the url to view this item
			$url =& Articles::get_permalink($item);

			// build a title
			if(is_object($overlay) && is_callable(array($overlay, 'get_live_title')))
				$title = $overlay->get_live_title($item);
			else
				$title = Codes::beautify_title($item['title']);

			// reset everything
			$text = $prefix = $label = $suffix = $icon = '';

			// signal articles to be published
			if(!isset($item['publish_date']) || ($item['publish_date'] <= NULL_DATE) || ($item['publish_date'] > gmstrftime('%Y-%m-%d %H:%M:%S')))
				$prefix .= DRAFT_FLAG;

			// signal restricted and private articles
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG.' ';
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG.' ';

			// flag expired articles, and articles updated recently
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $now))
				$suffix = EXPIRED_FLAG.' ';
			elseif($item['create_date'] >= $dead_line)
				$suffix = NEW_FLAG.' ';
			elseif($item['edit_date'] >= $dead_line)
				$suffix = UPDATED_FLAG.' ';

			// rating
			if($item['rating_count'] && !(is_object($anchor) && $anchor->has_option('without_rating')))
				$suffix .= ' '.Skin::build_link(Articles::get_url($item['id'], 'rate'), Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])), 'basic');

			// select an icon for this thread
			$item['comments_count'] = Comments::count_for_anchor('article:'.$item['id']);
			if(is_object($overlay) && ($overlay->attributes['overlay_type'] == 'poll')) {
				Skin::define_img('POLL_IMG', 'articles/poll.gif');
				$icon = POLL_IMG;
			} elseif($item['rank'] < 10000) {
				Skin::define_img('STICKY_THREAD_IMG', 'articles/sticky_thread.gif');
				$icon = STICKY_THREAD_IMG;
			} elseif(isset($item['comments_count']) && ($item['comments_count'] >= 20)) {
				Skin::define_img('VERY_HOT_THREAD_IMG', 'articles/very_hot_thread.gif');
				$icon = VERY_HOT_THREAD_IMG;
			} elseif(isset($item['comments_count']) && ($item['comments_count'] >= 10))
				$icon = HOT_THREAD_IMG;
			else
				$icon = THREAD_IMG;

			// indicate the id in the hovering popup
			$hover = i18n::s('View the page');
			if(Surfer::is_member())
				$hover .= ' [article='.$item['id'].']';

			// use the title as a link to the page
			$title = $prefix.Skin::build_link($url, ucfirst($title), 'basic', $hover).$suffix;

			$suffix = '';

			// the introductory text
			if(trim($item['introduction']))
				$suffix .= BR.Codes::beautify_introduction($item['introduction']);

			// page size for comments
			$layout = new Layout_comments_as_yabb();

			// shortcuts to comments pages
			if(isset($item['comments_count']) && ($pages = (integer)ceil($item['comments_count'] / $layout->items_per_page())) && ($pages > 1)) {
				$suffix .= '<p class="details">Pages ';
				for($index = 1; $index <= $pages; $index++)
					$suffix .= Skin::build_link('comments/list.php?id=article:'.$item['id'].'&amp;page='.$index, $index, 'basic', i18n::s('One page of comments')).' ';
				$suffix .= Skin::build_link('comments/list.php?id=article:'.$item['id'].'&amp;page='.$pages, MORE_IMG, 'basic', i18n::s('Most recent comments')).'</p>';
			}

			// links to sections and categories
			$anchors = array();

			// the main anchor link
			if(is_object($anchor) && (!isset($this->layout_variant) || ($item['anchor'] != $this->layout_variant)))
				$anchors[] = Skin::build_link($anchor->get_url(), ucfirst($anchor->get_title()), 'basic', i18n::s('In this section'));


			// list categories by title, if any
			if($members =& Members::list_categories_by_title_for_member('article:'.$item['id'], 0, 7, 'raw')) {
				foreach($members as $category_id => $attributes) {

					// add background color to distinguish this category against others
					if(isset($attributes['background_color']) && $attributes['background_color'])
						$attributes['title'] = '<span style="background-color: '.$attributes['background_color'].'; padding: 0 3px 0 3px;">'.$attributes['title'].'</span>';

					if(!isset($this->layout_variant) || ($this->layout_variant != 'category:'.$category_id))
						$anchors[] = Skin::build_link(Categories::get_permalink($attributes), $attributes['title'], 'basic', i18n::s('Related topics'));
				}
			}

			// list section and categories in the suffix
			if(@count($anchors))
				$suffix .= '<p class="details">'.implode(' ', $anchors).'</p>';

			// the creator of this article
			$starter = '';
			if($item['create_name']) {
				$starter = '<span class="details">'.Users::get_link($item['create_name'], $item['create_address'], $item['create_id']).'</span>';
			}

			// the last editor
			$details = '';
			if($item['edit_date']) {

				// find a name, if any
				$user = '';
				if($item['edit_name']) {

					// label the action
					if(isset($item['edit_action']))
						$user .= get_action_label($item['edit_action']).' ';

					// name of last editor
					$user .= sprintf(i18n::s('by %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']));
				}

				$details .= $user.' '.Skin::build_date($item['edit_date']);
			}

			// signal locked articles
			if(isset($item['locked']) && ($item['locked'] == 'Y'))
				$details .= ', '.LOCKED_FLAG;

			// poster details
			if($details)
				$details = '<p class="details">'.$details."</p>\n";

			if(!isset($item['comments_count']))
				$item['comments_count'] = 0;

			// this is another row of the output
			$cells = array($title.$suffix, 'center='.$starter, 'center='.$item['comments_count'], 'center='.Skin::build_number($item['hits']), $details);
			if(THREAD_IMG)
				$cells = array_merge(array($icon), $cells);

			$rows[] = $cells;

		}

		// end of processing
		SQL::free($result);

		// headers
		$headers = array(i18n::s('Topic'), 'center='.i18n::s('Poster'), 'center='.i18n::s('Replies'), 'center='.i18n::s('Views'), i18n::s('Last post'));
		if(THREAD_IMG)
			$headers = array_merge(array(''), $headers);

		// make a sortable table
		$output = Skin::table($headers, $rows, 'yabb');
		return $output;
	}
}

?>