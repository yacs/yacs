<?php
/**
 * layout files as a feed
 *
 * @see files/files.php
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_files_as_feed extends Layout_interface {

	/**
	 * list files
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
			Codes::initialize(Files::get_url($item['id'], 'view', $item['file_name']));

			// get the anchor for this file
			if($item['anchor'])
				$anchor = Anchors::get($item['anchor']);

			// view the file page if there is some description
			if(isset($item['description']) && trim($item['description']))
				$url = $context['url_to_home'].$context['url_to_root'].Files::get_url($item['id'], 'view', $item['file_name']);

			// else download the file directly
			else
				$url = $context['url_to_home'].$context['url_to_root'].Files::get_url($item['id'], 'fetch', $item['file_name']);

			// time of last update
			$time = SQL::strtotime($item['edit_date']);

			// the title as the label
			if($item['title'])
				$label = Codes::beautify_title($item['title']).' ('.str_replace(array('%20', '-', '_'), ' ', $item['file_name']).')';
			else
				$label = str_replace(array('%20', '-', '_'), ' ', $item['file_name']);

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
			$type = Files::get_mime_type($item['file_name']);
			$extensions[] = '<enclosure url="'.$context['url_to_home'].$context['url_to_root'].'files/'.$context['virtual_path'].str_replace(':', '/', $item['anchor']).'/'.$item['file_name'].'"'
				.' length="'.$item['file_size'].'"'
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