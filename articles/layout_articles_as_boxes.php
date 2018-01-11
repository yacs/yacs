<?php
/**
 * layout articles as a set of boxes
 *
 * Page nick names are used as box CSS identifiers.
 *
 * This is a special layout used at the front page, to process extra and navigation boxes.
 *
 * @see articles/articles.php
 * @see index.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Thierry Pinelli (ThierryP)
 * @tester Timster
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_boxes extends Layout_interface {

	/**
	 * list articles
	 *
	 * @param resource the SQL result
	 * @return array( $title => $content )
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
		include_once $context['path_to_root'].'articles/article.php';
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

			// shortcut for associates
			if(Surfer::is_associate())
				$title = Skin::build_box_title($title, $url, i18n::s('View the page'));

			// title prefix
			$prefix = '';
                        
                                                    
                        // insert thumbnail
                        if($item['thumbnail_url']) {
                            $prefix .= tag::_('span',tag::_class('gadget-icon'), skin::build_icon($item['thumbnail_url']));
                        }


			// flag articles that are dead, or created or updated very recently
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
				$prefix .= EXPIRED_FLAG;

			// signal articles to be published
			if(($item['publish_date'] <= NULL_DATE) || ($item['publish_date'] > $context['now']))
				$prefix .= DRAFT_FLAG;

			// prefix the title
			$title = $prefix.$title;

			// what has to be displayed in this box
			$parts = array();

			// if the page is publicly available, show introduction and link to full content
			$article = new Article($item);
			if($article->is_public()) {
                            
				// get introduction from overlay, if any
				if(is_object($overlay)) {
					$parts[] = Codes::beautify_introduction($overlay->get_text('introduction', $item));

					// add a link to the main page
					$parts[] = Skin::build_link($url, i18n::s('More').MORE_IMG, 'basic', i18n::s('View the page'));

				// use the introduction, if any
				} elseif($item['introduction']) {

					// the content of this box
					$parts[] = Codes::beautify_introduction($item['introduction']);

					// add a link to the main page
					$parts[] = Skin::build_link($url, i18n::s('More').MORE_IMG, 'basic', i18n::s('View the page'));


				// no introduction, display article full content
				} else {

					// insert overlay data, if any
					if(is_object($overlay))
						$parts[] = $overlay->get_text('box', $item);

					// the content of this box
					$parts[] = Codes::beautify($item['description'], $item['options']);

				}

			// else display full box content
			} else {

				// use the introduction, if any
				if($item['introduction'])
					$parts[] = Codes::beautify_introduction($item['introduction']);

				// insert overlay data, if any
				if(is_object($overlay))
					$parts[] = $overlay->get_text('box', $item);

				// the content of this box
				if($item['description'])
					$parts[] = Codes::beautify($item['description'], $item['options']);
			}

			// use nick name as box id
			$id = '';
			if(isset($item['nick_name']))
				$id = trim($item['nick_name']);

			// append to the list
			$items[$title] = array( 'content' => implode(BR, $parts), 'id' => $id );

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>
