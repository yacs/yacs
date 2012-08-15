<?php
/**
 * layout articles as a feed
 *
 * This is a special layout used to build a newsfeed.
 *
 * @link http://georss.org/Main_Page GeoRSS
 *
 * @see articles/articles.php
 * @see feeds/feeds.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Thierry Pinelli (ThierryP)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_feed extends Layout_interface {

	/**
	 * list articles
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
		include_once $context['path_to_root'].'locations/locations.php';
		while($item = SQL::fetch($result)) {

			// get the related overlay, if any
			$overlay = Overlay::load($item, 'article:'.$item['id']);

			// get the anchor
			$anchor = Anchors::get($item['anchor']);

			// provide an absolute link
			$url = $context['url_to_home'].$context['url_to_root'].Articles::get_permalink($item);

			// build a title
			if(is_object($overlay))
				$title = Codes::beautify_title($overlay->get_text('title', $item));
			else
				$title = Codes::beautify_title($item['title']);

			// time of last update
			$time = SQL::strtotime($item['edit_date']);

			// the section
			$section = '';
			if($item['anchor'] && ($anchor = Anchors::get($item['anchor'])))
				$section = ucfirst(trim(strip_tags(Codes::beautify_title($anchor->get_title()))));

			// the icon to use
			$icon = '';
			if($item['thumbnail_url'])
				$icon = $item['thumbnail_url'];
			elseif($item['anchor'] && ($anchor = Anchors::get($item['anchor'])) && is_callable($anchor, 'get_bullet_url'))
				$icon = $anchor->get_bullet_url();
			if($icon)
				$icon = $context['url_to_home'].$context['url_to_home'].$icon;

			// the author(s) is an e-mail address, according to rss 2.0 spec
			$author = '';
			if(isset($item['create_address']))
				$author .= $item['create_address'];
			if(isset($item['create_name']) && trim($item['create_name']))
				$author .= ' ('.$item['create_name'].')';
			if(isset($item['edit_address']) && trim($item['edit_address']) && ($item['create_address'] != $item['edit_address'])) {
				if($author)
					$author .= ', ';
				$author .= $item['edit_address'];
				if(isset($item['edit_name']) && trim($item['edit_name']))
					$author .= ' ('.$item['edit_name'].')';
			}

			// some introductory text for this article
			$article = new Article();
			$article->load_by_content($item);
			$introduction = $article->get_teaser('teaser');

			// warns on restricted access
			if(isset($item['active']) && ($item['active'] != 'Y'))
				$introduction = '['.i18n::c('Restricted to members').'] '.$introduction;

			// fix references
			$introduction = preg_replace('/"\//', '"'.$context['url_to_home'].'/', $introduction);

			// the article content
			$description = '';

			// other rss fields
			$extensions = array();

			// the geolocation for this page, if any
			if($location = Locations::locate_anchor('article:'.$item['id']))
				$extensions[] = '<georss:point>'.str_replace(',', ' ', $location).'</georss:point>';

			// url for comments
			if(is_object($anchor))
				$extensions[] = '<comments>'.encode_link($context['url_to_home'].$context['url_to_root'].$anchor->get_url('comments')).'</comments>';

			// count comments
			$comment_count = Comments::count_for_anchor('article:'.$item['id']);
			$extensions[] = '<slash:comments>'.$comment_count."</slash:comments>";

			// the comment post url
			$extensions[] = '<wfw:comment>'.encode_link($context['url_to_home'].$context['url_to_root'].Comments::get_url('article:'.$item['id'], 'service.comment'))."</wfw:comment>";

			// the comment Rss url
			$extensions[] = '<wfw:commentRss>'.encode_link($context['url_to_home'].$context['url_to_root'].Comments::get_url('article:'.$item['id'], 'feed'))."</wfw:commentRss>";

			// the trackback url
			$extensions[] = '<trackback:ping>'.encode_link($context['url_to_home'].$context['url_to_root'].'links/trackback.php?anchor='.urlencode('article:'.$item['id']))."</trackback:ping>"; // no trackback:about;

			// list all components for this item
			$items[$url] = array($time, $title, $author, $section, $icon, $introduction, $description, $extensions);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>
