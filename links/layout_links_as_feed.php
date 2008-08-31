<?php
/**
 * layout links as a feed
 *
 * @see links/links.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_links_as_feed extends Layout_interface {

	/**
	 * list links
	 *
	 * @param resource the SQL result
	 * @return array of resulting items, or NULL
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
		while($item =& SQL::fetch($result)) {

			// reset the rendering engine between items
			if(is_callable(array('Codes', 'initialize')))
				Codes::initialize(Links::get_url($item['id']));

			// get the anchor for this link
			if($item['anchor'])
				$anchor =& Anchors::get($item['anchor']);

			// url is the link itself
			$url = $item['link_url'];

			// time of last update
			$time = SQL::strtotime($item['edit_date']);

			// the title as the label
			if($item['title'])
				$label = $item['title'];
			else
				$label = $url;

			// the section
			$section = '';
			if(is_object($anchor))
				$section = ucfirst($anchor->get_title());

			// the author(s) is an e-mail address, according to rss 2.0 spec
			$author = $item['edit_address'].' ('.$item['edit_name'].')';

			// the description
			$description = Codes::beautify($item['description']);

			// cap the number of words
			$description = Skin::cap($description, 300);

			// fix image references
			$description = preg_replace('/"\/([^">]+?)"/', '"'.$context['url_to_home'].'/\\1"', $description);

			$introduction = $description;

			// other rss fields
			$extensions = array();

			// list all components for this item
			$items[$url] = array($time, $label, $author, $section, NULL, $introduction, $description, $extensions);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>