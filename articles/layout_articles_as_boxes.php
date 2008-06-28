<?php
/**
 * layout articles as a text of boxes
 *
 * Page nick names as used as box CSS identifiers.
 *
 * This layout is mainly used at the front page, to process extra and navigation boxes.
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
	 * @see skins/layout.php
	**/
	function &layout(&$result) {
		global $context;

		// we return an array of ($url => $attributes)
		$items = array();

		// empty list
		if(!SQL::count($result))
			return $items;

		// process all items in the list
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		include_once $context['path_to_root'].'articles/article.php';
		include_once $context['path_to_root'].'overlays/overlay.php';
		while($item =& SQL::fetch($result)) {

			// get the related overlay, if any
			$overlay = Overlay::load($item);

			// get the main anchor
			$anchor = Anchors::get($item['anchor']);

			// the url to view this item
			$url = Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name']);

			// reset the rendering engine between items
			Codes::initialize($url);

			// use the title to label the link
			if(is_object($overlay) && is_callable(array($overlay, 'get_live_title')))
				$title = $overlay->get_live_title($item);
			else
				$title = Codes::beautify_title($item['title']);

			// shortcut for associates
			if(Surfer::is_associate())
				$title =& Skin::build_box_title($title, $url, i18n::s('View the page'));

			// title prefix
			$prefix = '';

			// flag articles that are dead, or created or updated very recently
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $now))
				$prefix .= EXPIRED_FLAG;

			// signal articles to be published
			if(($item['publish_date'] <= NULL_DATE) || ($item['publish_date'] > $now))
				$prefix .= DRAFT_FLAG;

			// prefix the title
			$title = $prefix.$title;

			// what has to be displayed in this box
			$parts = array();

			// if the page is publicly available, show introduction and link to full content
			$article =& new Article();
			$article->load_by_content($item, Anchors::get($item['anchor']));
			if($article->is_public()) {

				// use the introduction, if any
				if($item['introduction']) {

					// the content of this box
					$parts[] = Codes::beautify($item['introduction'], $item['options']);

					// add a link to the main page
					$parts[] = Skin::build_link($url, i18n::s('More').MORE_IMG, 'basic', i18n::s('Read the page'));


				// no introduction, display article full content
				} else {

					// get the related overlay, if any
					$overlay = Overlay::load($item);

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
					$parts[] = Codes::beautify($item['introduction'], $item['options']);

				// get the related overlay, if any
				$overlay = Overlay::load($item);

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