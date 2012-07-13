<?php
/**
 * layout articles for manual review
 *
 * This is a special layout used to support manual review of articles.
 *
 * @see articles/articles.php
 * @see articles/review.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Thierry Pinelli (ThierryP)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_review extends Layout_interface {

	/**
	 * list articles for manual review
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	 *
	 * @see skins/layout.php
	**/
	function layout($result) {
		global $context;

		// we return an array of ($url => $attributes)
		$items = array();

		// empty list
		if(!SQL::count($result))
			return $items;

		// process all items in the list
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'links/links.php';
		while($item = SQL::fetch($result)) {

			// get the related overlay, if any
			$overlay = Overlay::load($item, 'article:'.$item['id']);

			// get the anchor
			$anchor = Anchors::get($item['anchor']);

			// the url to view this item
			$url = Articles::get_permalink($item);

			// use the title to label the link
			if(is_object($overlay))
				$title = Codes::beautify_title($overlay->get_text('title', $item));
			else
				$title = Codes::beautify_title($item['title']);

			// initialize variables
			$prefix = $suffix = '';

			// flag sticky pages
			if($item['rank'] < 10000)
				$prefix .= STICKY_FLAG;

			// signal locked articles
			if(isset($item['locked']) && ($item['locked'] == 'Y') && Articles::is_owned($item, $anchor))
				$suffix .= ' '.LOCKED_FLAG;

			// flag articles that are dead, or created or updated very recently
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
				$prefix .= EXPIRED_FLAG;
			elseif($item['create_date'] >= $context['fresh'])
				$suffix .= ' '.NEW_FLAG;
			elseif($item['edit_date'] >= $context['fresh'])
				$suffix .= ' '.UPDATED_FLAG;

			// signal articles to be published
			if(($item['publish_date'] <= NULL_DATE) || ($item['publish_date'] > gmstrftime('%Y-%m-%d %H:%M:%S')))
				$prefix .= DRAFT_FLAG;

			// signal restricted and private articles
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// details
			$details = array();

			// the author(s)
			if($item['create_name'] != $item['edit_name'])
				$details[] = sprintf(i18n::s('by %s, %s'), $item['create_name'], $item['edit_name']);
			else
				$details[] = sprintf(i18n::s('by %s'), $item['create_name']);

			// the last action
			$details[] = Anchors::get_action_label($item['edit_action']).' '.Skin::build_date($item['edit_date']);

			// the number of hits
			if(Surfer::is_logged() && ($item['hits'] > 1))
				$details[] = Skin::build_number($item['hits'], i18n::s('hits'));

			// info on related files
			if($count = Files::count_for_anchor('article:'.$item['id'], TRUE))
				$details[] = sprintf(i18n::ns('%d file', '%d files', $count), $count);

			// info on related links
			if($count = Links::count_for_anchor('article:'.$item['id'], TRUE))
				$details[] = sprintf(i18n::ns('%d link', '%d links', $count), $count);

			// info on related comments
			if($count = Comments::count_for_anchor('article:'.$item['id'], TRUE))
				$details[] = sprintf(i18n::ns('%d comment', '%d comments', $count), $count);

			// append details to the suffix
			$suffix .= ' -&nbsp;<span class="details">'.ucfirst(trim(implode(', ', $details))).'</span>';

			// commands to review the article
			$menu = array();

			// read the page
			$menu = array_merge($menu, array($url => i18n::s('Read')));

			// validate the page
			$menu = array_merge($menu, array('articles/stamp.php?id='.$item['id'].'&amp;confirm=review' => i18n::s('Validate')));

			// add a menu
			$suffix .= ' '.Skin::build_list($menu, 'menu');

			// list all components for this item
			$items[$url] = array($prefix, $title, $suffix, 'basic', NULL);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>
