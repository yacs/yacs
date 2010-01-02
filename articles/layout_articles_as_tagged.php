<?php
/**
 * layout articles with tags
 *
 * Useful for flat and long lists of articles, to offer some visual structure.
 *
 * @see articles/index.php
 * @see articles/articles.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_tagged extends Layout_interface {

	/**
	 * list articles
	 *
	 * @param resource the SQL result
	 * @return array of resulting items, or NULL
	 *
	 * @see skins/layout.php
	**/
	function &layout(&$result) {
		global $context;

		// we return an array of ($url => $attributes)
		$items = array();

		// empty list
		if(!SQL::count($result))
			return $items;

		// flag articles updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// process all items in the list
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'links/links.php';
		include_once $context['path_to_root'].'overlays/overlay.php';
		while($item =& SQL::fetch($result)) {

			// get the related overlay, if any
			$overlay = Overlay::load($item);

			// get the main anchor
			$anchor =& Anchors::get($item['anchor']);

			// the url to view this item
			$url =& Articles::get_permalink($item);

			// use the title to label the link
			if(is_object($overlay) && is_callable(array($overlay, 'get_live_title')))
				$title = $overlay->get_live_title($item);
			else
				$title = Codes::beautify_title($item['title']);

			// initialize variables
			$prefix = $suffix = $icon = '';

			// flag sticky pages
			if($item['rank'] < 10000)
				$prefix .= STICKY_FLAG;

			// signal articles to be published
			if(($item['publish_date'] <= NULL_DATE) || ($item['publish_date'] > gmstrftime('%Y-%m-%d %H:%M:%S')))
				$prefix .= DRAFT_FLAG;

			// signal restricted and private articles
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// flag articles that are dead, or created or updated very recently
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $now))
				$prefix .= EXPIRED_FLAG;
			elseif($item['create_date'] >= $dead_line)
				$suffix .= NEW_FLAG;
			elseif($item['edit_date'] >= $dead_line)
				$suffix .= UPDATED_FLAG;

			// info on related comments
			if($count = Comments::count_for_anchor('article:'.$item['id'], TRUE))
				$suffix .= ' ('.$count.')';

			// details
			$details = array();

			// info on related files
			if($count = Files::count_for_anchor('article:'.$item['id'], TRUE))
				$details[] = sprintf(i18n::ns('%d file', '%d files', $count), $count);

			// info on related links
			if($count = Links::count_for_anchor('article:'.$item['id'], TRUE))
				$details[] = sprintf(i18n::ns('%d link', '%d links', $count), $count);

			// signal locked articles
			if(isset($item['locked']) && ($item['locked'] == 'Y'))
				$details[] = LOCKED_FLAG;

			// combine in-line details
			if(count($details))
				$suffix .= ' <span class="details">'.trim(implode(', ', $details)).'</span>';

			// display all tags
			if($item['tags'])
				$suffix .= ' <span class="tags">'.Skin::build_tags($item['tags'], 'article:'.$item['id']).'</span>';

			// list all components for this item
			$items[$url] = array($prefix, $title, $suffix, 'article', $icon);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>