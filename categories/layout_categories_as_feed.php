<?php
/**
 * layout categories as a feed
 *
 * This is a special layout used to build a newsfeed.
 *
 * @link http://georss.org/Main_Page GeoRSS
 *
 * made for zicworld.com
 */
Class Layout_categories_as_feed extends Layout_interface {

	/**
	 * list articles
	 *
	 * @param resource the SQL result
	 * @return array
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
		
		while($item = SQL::fetch($result)) {

			// get the related overlay, if any
			$overlay = Overlay::load($item, 'category:'.$item['id']);

			// get the anchor
			$anchor = Anchors::get($item['anchor']);

			// provide an absolute link
			$url = Categories::get_permalink($item);

			// build a title
			if(is_object($overlay))
				$title = Codes::beautify_title($overlay->get_text('title', $item));
			else
				$title = Codes::beautify_title($item['title']);

			// time of last update
			$time = SQL::strtotime($item['edit_date']);

			// the section
			$root = '';
			if($item['anchor'] && ($anchor = Anchors::get($item['anchor'])))
				$root = ucfirst(trim(strip_tags(Codes::beautify_title($anchor->get_title()))));

			// the icon to use
			$icon = '';
			if($item['thumbnail_url'])
				$icon = $item['thumbnail_url'];
			elseif($item['anchor'] && ($anchor = Anchors::get($item['anchor'])) && is_callable($anchor, 'get_bullet_url'))
				$icon = $anchor->get_bullet_url();
			if($icon)
				$icon = $context['url_to_home'].$context['url_to_root'].$icon;

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

			// list all components for this item
			$items[$url] = array($time, $title, $author, $root, $icon, '', '', '');

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>
