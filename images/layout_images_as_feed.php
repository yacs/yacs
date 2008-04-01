<?php
/**
 * layout images as a feed
 *
 * @see images/images.php
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_images_as_feed extends Layout_interface {

	/**
	 * list images
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
		while($item =& SQL::fetch($result)) {

			// reset the rendering engine between items
			if(is_callable(array('Codes', 'initialize')))
				Codes::initialize(Images::get_url($item['id']));

			// get the anchor for this image
			if($item['anchor'])
				$anchor = Anchors::get($item['anchor']);

			// url to view the image
			$url = $context['url_to_home'].$context['url_to_root'].Images::get_url($item['id']);

			// time of last update
			$time = SQL::strtotime($item['edit_date']);

			// the title as the label
			if($item['title'])
				$label = ucfirst($item['title']).' ('.$item['image_name'].')';
			else
				$label = $item['image_name'];

			// the section
			$section = '';
			if(is_object($anchor))
				$section = ucfirst($anchor->get_title());

			// the author(s) is an e-mail address, according to rss 2.0 spec
			$author = $item['create_address'].' ('.$item['create_name'].')';
			if($item['create_address'] != $item['edit_address']) {
				if($author)
					$author .= ', ';
				$author .= $item['edit_address'].' ('.$item['edit_name'].')';
			}

			// the description
			$description = Codes::beautify($item['description']);

			// cap the number of words
			$description = Skin::cap($description, 300);

			// fix image references
			$description = preg_replace('/"\//', '"'.$context['url_to_home'].'/', $description);

			$introduction = $description;

			// other rss fields
			$extensions = array();

			// url for enclosure
			include_once $context['path_to_root'].'files/files.php';
			$type = Files::get_mime_type($item['image_name']);
			$extensions[] = '<enclosure url="'.$context['url_to_home'].$context['url_to_root'].'images/'.$context['virtual_path'].str_replace(':', '/', $item['anchor']).'/'.$item['image_name'].'"'
				.' length="'.$item['image_size'].'"'
				.' type="'.$type.'"/>';

			// list all components for this item
			$items[$url] = array($time, $label, $author, $section, NULL, $introduction, $description, $extensions);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>