<?php
/**
 * layout decisions as a feed
 *
 * @see decisions/decisions.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_decisions_as_feed extends Layout_interface {

	/**
	 * list decisions
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
		while($item = SQL::fetch($result)) {

			// get the anchor for this decision
			$anchor = NULL;
			if(isset($item['anchor']) && $item['anchor'])
				$anchor =& Anchors::get($item['anchor']);

			// url to read the full decision
			$url = $context['url_to_home'].$context['url_to_root'].decisions::get_url($item['id']);

			// time of last update
			$time = SQL::strtotime($item['edit_date']);

			// the title as the label
			if($item['create_name'])
				$label = sprintf(i18n::s('%s: %s'), ucfirst($item['create_name']), Skin::strip($item['description'], 10));
			else
				$label = Skin::strip($item['description'], 10);

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

			// the decision content
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