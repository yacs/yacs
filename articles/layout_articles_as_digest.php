<?php
/**
 * layout articles in a digest
 *
 * This is a special layout used to prepare a newsletter.
 *
 * @see articles/articles.php
 * @see letters/new.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Thierry Pinelli (ThierryP)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_digest extends Layout_interface {

	/**
	 * list articles
	 *
	 * @param resource the SQL result
	 * @return array
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
		include_once $context['path_to_root'].'articles/article.php';
		include_once $context['path_to_root'].'overlays/overlay.php';
		while($item = SQL::fetch($result)) {

			// get the related overlay, if any
			$overlay = Overlay::load($item, 'article:'.$item['id']);

			// get the anchor
			$anchor =& Anchors::get($item['anchor']);

			// the url to view this item
			$url = Articles::get_permalink($item);

			// provide an absolute link
			$url = $context['url_to_home'].$context['url_to_root'].$url;

			// build a title
			if(is_object($overlay))
				$title = Codes::beautify_title($overlay->get_text('title', $item));
			else
				$title = Codes::beautify_title($item['title']);

			// time of publication
			if(isset($item['publish_date']) && ($item['publish_date'] > NULL_DATE))
				$time = $item['publish_date'];
			else
				$time = NULL_DATE;

			// the section
			$section = '';
			if($item['anchor'] && ($anchor =& Anchors::get($item['anchor'])))
				$section = ucfirst(trim(strip_tags(Codes::beautify_title($anchor->get_title()))));

			// the icon to use
			$icon = '';
			if($item['thumbnail_url'])
				$icon = $item['thumbnail_url'];
			elseif($item['anchor'] && ($anchor =& Anchors::get($item['anchor'])))
				$icon = $anchor->get_thumbnail_url();
			if($icon)
				$icon = $context['url_to_home'].$context['url_to_home'].$icon;

			// the author(s) is an e-mail address, according to rss 2.0 spec
			$author = '';
			if(isset($context['with_author_information']) && ($context['with_author_information'] == 'Y')) {
				if(isset($item['create_address']))
					$author .= $item['create_address'];
				if(isset($item['create_name']) && trim($item['create_name']))
					$author .= ' ('.$item['create_name'].')';
			}

			// some introductory text for this article
			$article = new Article();
			$article->load_by_content($item);
			$introduction = $article->get_teaser('basic');

			// warns on restricted access
			if(isset($item['active']) && ($item['active'] != 'Y'))
				$introduction = '['.i18n::c('Restricted to members').'] '.$introduction;

			// fix references
			$introduction = preg_replace('/"\//', '"'.$context['url_to_home'].'/', $introduction);

			// list all components for this item
			$items[$url] = array($time, $title, $author, $section, $icon, $introduction);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>
