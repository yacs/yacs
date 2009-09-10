<?php
/**
 * layout users as a feed
 *
 * @see users/users.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_users_as_feed extends Layout_interface {

	/**
	 * list users
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
				Codes::initialize(Users::get_permalink($item));

			// url to view the user profile
			$url = $context['url_to_home'].$context['url_to_root'].Users::get_permalink($item);

			// time of last update
			$time = SQL::strtotime($item['edit_date']);

			// item title
			if($item['full_name'])
				$label = ucfirst(Skin::strip($item['full_name'], 10));
			else
				$label = ucfirst(Skin::strip($item['nick_name'], 10));

			// the section
			$section = '';

			// the author(s) is an e-mail address, according to rss 2.0 spec
			$author .= $item['edit_address'].' ('.$item['edit_name'].')';

			// introduction
			$introduction = Codes::beautify($item['introduction']);

			// the description
			$description = Codes::beautify($item['description']);

			// cap the number of words
			$description = Skin::cap($description, 300);

			// fix image references
			$description = preg_replace('/"\/([^">]+?)"/', '"'.$context['url_to_home'].'/\\1"', $description);

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