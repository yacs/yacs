<?php
/**
 * layout articles for search requests
 *
 * @see serch.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_search extends Layout_interface {

	/**
	 * list articles for search requests
	 *
	 * @param resource the SQL result
	 * @return array of resulting items ($score, $summary), or NULL
	 *
	 * @see skins/layout.php
	**/
	function layout($result) {
		global $context;

		// we return an array of array($score, $summary)
		$items = array();

		// empty list
		if(!SQL::count($result))
			return $items;

		// process all items in the list
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'links/links.php';
		while($item = SQL::fetch($result)) {

			// one box at a time
			$box = '';

			// get the related overlay, if any
			$overlay = Overlay::load($item, 'article:'.$item['id']);

			// get the main anchor
			$anchor =& Anchors::get($item['anchor']);

			// the url to view this item
			$url = Articles::get_permalink($item);

			// use the title to label the link
			if(is_object($overlay))
				$title = Codes::beautify_title($overlay->get_text('title', $item));
			else
				$title = Codes::beautify_title($item['title']);

			// initialize variables
			$prefix = $suffix = $icon = '';

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

			// introduction
			$introduction = '';
			if(is_object($overlay))
				$introduction = $overlay->get_text('introduction', $item);
			else
				$introduction = $item['introduction'];

			// the introductory text
			if($introduction) {
				$suffix .= ' -&nbsp;'.Codes::beautify_introduction($introduction);

				// link to description, if any
				if($item['description'])
					$suffix .= ' '.Skin::build_link($url, MORE_IMG, 'more', i18n::s('View the page')).' ';

			}

			// insert overlay data, if any
			if(is_object($overlay))
				$suffix .= $overlay->get_text('list', $item);

			// details
			$details = array();

			// the author
			if($item['create_name'] != $item['edit_name'])
				$details[] = sprintf(i18n::s('by %s, %s'), Users::get_link($item['create_name'], $item['create_address'], $item['create_id']), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']));
			else
				$details[] = sprintf(i18n::s('by %s'), Users::get_link($item['create_name'], $item['create_address'], $item['create_id']));

			// the last action
			$details[] = Anchors::get_action_label($item['edit_action']).' '.Skin::build_date($item['edit_date']);

			// the number of hits
			if(Surfer::is_logged() && ($item['hits'] > 1))
				$details[] = Skin::build_number($item['hits'], i18n::s('hits'));

			// info on related files
			if($count = Files::count_for_anchor('article:'.$item['id']))
				$details[] = sprintf(i18n::ns('%d file', '%d files', $count), $count);

			// info on related links
			if($count = Links::count_for_anchor('article:'.$item['id'], TRUE))
				$details[] = sprintf(i18n::ns('%d link', '%d links', $count), $count);

			// info on related comments
			if($count = Comments::count_for_anchor('article:'.$item['id']))
				$details[] = sprintf(i18n::ns('%d comment', '%d comments', $count), $count);

			// rating
			if($item['rating_count'] && !(is_object($anchor) && $anchor->has_option('without_rating')))
				$details[] = Skin::build_link(Articles::get_url($item['id'], 'like'), Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])), 'basic');

			// the main anchor link
			if(is_object($anchor))
				$details[] = sprintf(i18n::s('in %s'), Skin::build_link($anchor->get_url(), ucfirst($anchor->get_title()), 'section'));

			// display all tags
			if($item['tags'])
				$details[] = '<span class="tags">'.Skin::build_tags($item['tags'], 'article:'.$item['id']).'</span>';

			// combine in-line details
			if(count($details))
				$suffix .= '<p class="details">'.Skin::finalize_list($details, 'menu').'</p>';

			// insert a suffix separator
			if(trim($suffix))
				$suffix = ' '.$suffix;

			// item summary
			$box .= $prefix.Skin::build_link($url, $title, 'article').$suffix;

			// the icon to put in the left column
			if($item['thumbnail_url'])
				$icon = $item['thumbnail_url'];

			// or inherit from the anchor
			elseif(is_callable(array($anchor, 'get_bullet_url')))
				$icon = $anchor->get_bullet_url();

			// build the complete HTML element
			if($icon) {
				$icon = '<img src="'.$icon.'" alt="" title="'.encode_field(strip_tags($title)).'" />';

				// make it a clickable link
				$icon = Skin::build_link($url, $icon, 'basic');

			// default icon
			} else
				$icon = DECORATED_IMG;

			// layout this item
			$list = array(array($box, $icon));
			$items[] = array($item['score'], Skin::finalize_list($list, 'decorated'));

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>
