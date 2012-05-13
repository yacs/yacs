<?php
/**
 * layout articles as news
 *
 * This special layout is used at the site front page to layout news and featured pages.
 * It is also used at section index pages to layout news.
 *
 * @see index.php
 * @see sections/view.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Thierry Pinelli (ThierryP)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_news extends Layout_interface {

	/**
	 * list articles as news
	 *
	 * @param resource the SQL result
	 * @return array
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
		include_once $context['path_to_root'].'articles/article.php';
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'links/links.php';
		while($item = SQL::fetch($result)) {

			// get the related overlay, if any
			$overlay = Overlay::load($item, 'article:'.$item['id']);

			// get the main anchor
			$anchor =& Anchors::get($item['anchor']);

			// the url to view this item
			$url = Articles::get_permalink($item);

			// reset the rendering engine between items
			Codes::initialize($url);

			// use the title to label the link
			if(is_object($overlay))
				$title = Codes::beautify_title($overlay->get_text('title', $item));
			else
				$title = Codes::beautify_title($item['title']);

			// initialize variables
			$prefix = $suffix = $icon = '';

			// flag articles that are dead, or created or updated very recently
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
				$prefix .= EXPIRED_FLAG;
			elseif($item['create_date'] >= $context['fresh'])
				$suffix .= NEW_FLAG;
			elseif($item['edit_date'] >= $context['fresh'])
				$suffix .= UPDATED_FLAG;

			// signal articles to be published
			if(($item['publish_date'] <= NULL_DATE) || ($item['publish_date'] > gmstrftime('%Y-%m-%d %H:%M:%S')))
				$prefix .= DRAFT_FLAG;

			// signal restricted and private articles
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// go to a new line
			$suffix .= BR;

			// get introduction from overlay
			if(is_object($overlay)) {
				$suffix .= Codes::beautify_introduction($overlay->get_text('introduction', $item));

				// add a link to the main page
				$suffix .= BR.Skin::build_link($url, i18n::s('More').MORE_IMG, 'basic', i18n::s('View the page'));

			// use introduction
			} elseif($item['introduction']) {
				$suffix .= Codes::beautify_introduction($item['introduction']);

				// add a link to the main page
				$suffix .= BR.Skin::build_link($url, i18n::s('More').MORE_IMG, 'basic', i18n::s('View the page'));

			// else use a teaser, if no overlay
			} elseif(!is_object($overlay)) {
				$article = new Article();
				$article->load_by_content($item);
				$suffix .= $article->get_teaser('teaser');
			}

			// insert overlay data, if any
			if(is_object($overlay))
				$suffix .= $overlay->get_text('list', $item);

			// the icon to put in the left column
			if($item['thumbnail_url'])
				$suffix .= BR.Skin::build_link($url, '<img src="'.$item['thumbnail_url'].'" alt="" title="'.encode_field($title).'" />', 'basic');

			// details
			$details = array();

			// info on related files
			if($count = Files::count_for_anchor('article:'.$item['id'], TRUE))
				$details[] = sprintf(i18n::ns('%d file', '%d files', $count), $count);

			// info on related links
			if($count = Links::count_for_anchor('article:'.$item['id'], TRUE))
				$details[] = sprintf(i18n::ns('%d link', '%d links', $count), $count);

			// info on related comments
			if($count = Comments::count_for_anchor('article:'.$item['id'], TRUE))
				$details[] = sprintf(i18n::ns('%d comment', '%d comments', $count), $count);

			// actually insert details
			if($details)
				$suffix .= '<p class="details">'.ucfirst(trim(implode(', ', $details))).'</p>';

			// list all components for this item
			$items[$url] = array($prefix, $title, $suffix, 'article', $icon);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>
