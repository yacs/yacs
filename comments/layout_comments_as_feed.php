<?php
/**
 * layout comments as a feed
 *
 * @see comments/comments.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_comments_as_feed extends Layout_interface {

	/**
	 * list comments
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	 *
	 * @see skins/layout.php
	**/
	function &layout(&$result) {
		global $context;

		// empty list
		if(!SQL::count($result)) {
			$output = array();
			return $output;
		}

		// we return an array of ($url => $attributes)
		$items = array();

		// process all items in the list
		include_once $context['path_to_root'].'comments/comments.php';
		while($item =& SQL::fetch($result)) {

			// get the anchor for this comment
			$anchor = NULL;
			if(isset($item['anchor']) && $item['anchor'])
				$anchor =& Anchors::get($item['anchor']);

			// url to read the full comment
			$url = $context['url_to_home'].$context['url_to_root'].Comments::get_url($item['id']);

			// time of last update
			$time = SQL::strtotime($item['edit_date']);

			// the title as the label
			$label = '';
			if($item['create_name'])
				$label .= ucfirst($item['create_name']).' ';
			$label .= Skin::build_date($item['edit_date']);

			// the section
			$section = '';
			if(is_object($anchor))
				$section = ucfirst($anchor->get_title());

			// the icon to use
			$icon = '';
			if(isset($item['thumbnail_url']) && $item['thumbnail_url'])
				$icon = $item['thumbnail_url'];
			elseif(is_object($anchor))
				$icon = $anchor->get_thumbnail_url();
			if($icon)
				$icon = $context['url_to_home'].$icon;

			// the author(s) is an e-mail address, according to rss 2.0 spec
			$author = $item['create_address'].' ('.$item['create_name'].')';
			if($item['create_address'] != $item['edit_address']) {
				if($author)
					$author .= ', ';
				$author .= $item['edit_address'].' ('.$item['edit_name'].')';
			}

			// the comment content
			$description = Codes::beautify($item['description']);

			// cap the number of words
//			$description = Skin::cap($description, 300);

			// fix image references
			$description = preg_replace('/"\/([^">]+?)"/', '"'.$context['url_to_home'].'/\\1"', $description);

			$introduction = $description;

			// other rss fields
			$extensions = array();

			// list all components for this item
			$items[$url] = array($time, $label, $author, $section, $icon, $introduction, $description, $extensions);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>