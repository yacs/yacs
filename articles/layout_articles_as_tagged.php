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
	 * @see layouts/layout.php
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

			// get the main anchor
			$anchor = Anchors::get($item['anchor']);

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

			// signal articles to be published
			if(($item['publish_date'] <= NULL_DATE) || ($item['publish_date'] > gmdate('Y-m-d H:i:s')))
				$prefix .= DRAFT_FLAG;

			// signal restricted and private articles
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

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

			// info on related comments
			if($count = Comments::count_for_anchor('article:'.$item['id'], TRUE))
				$suffix .= ' ('.$count.')';

			// the introductory text
			$introduction = '';
			if(is_object($overlay))
				$introduction = $overlay->get_text('introduction', $item);
			elseif($item['introduction'])
				$introduction = $item['introduction'];
			if($introduction)
				$suffix .= ' -&nbsp;'.Codes::beautify_introduction($introduction);

			// details
			$details = array();

			// info on related files
			if($count = Files::count_for_anchor('article:'.$item['id'], TRUE))
				$details[] = sprintf(i18n::ns('%d file', '%d files', $count), $count);

			// info on related links
			if($count = Links::count_for_anchor('article:'.$item['id'], TRUE))
				$details[] = sprintf(i18n::ns('%d link', '%d links', $count), $count);

			// combine in-line details
			if(count($details))
				$suffix .= ' <span '.tag::_class('details').'>'.trim(implode(', ', $details)).'</span>';

			// display all tags
			if($item['tags'])
				$suffix .= tag::_('span', tag::_class('tags'), Skin::build_tags($item['tags']));

			// list all components for this item
			$items[$url] = array($prefix, $title, $suffix, 'article');

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>
