<?php
/**
 * prepare a message to one person only
 *
 * If you are looking for a layout suitable to the selection of several recipients,
 * check users/layout_users_as_mail.php instead.
 *
 * @see users/users.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_users_as_request extends Layout_interface {

	/**
	 * list users
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	 *
	 * @see layouts/layout.php
	**/
	function layout($result) {
		global $context;

		// we return some text
		$text = '';

		// empty list
		if(!$count = SQL::count($result))
			return $text;

		$text = '<div>';

		// process all items in the list
		$count = 0;
		$checked = ' checked="checked"';
		while($item = SQL::fetch($result)) {

			// we need some address
			if(!$item['email'])
				continue;

			// do not write to myself
			if($item['id'] == Surfer::get_id())
				continue;

			// get the related overlay, if any
			$overlay = Overlay::load($item, 'user:'.$item['id']);

			// one radio button per person
			$text .= '<input type="radio" name="requested" value="'.encode_field($item['id']).'"'.$checked.' />';

			// signal restricted and private users
			if($item['active'] == 'N')
				$text .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$text .= RESTRICTED_FLAG;

			// the url to view this item
			$url = Users::get_permalink($item);

			// use the title to label the link
			if(is_object($overlay))
				$title = Codes::beautify_title($overlay->get_text('title', $item));
			else
				$title = Codes::beautify_title($item['full_name']);

			// sanity check
			if(!$title)
				$title = $item['nick_name'];

			// link to this page
			$text .= Skin::build_link($url, $title, 'user');

			// the introductory text
			if($item['introduction'])
				$text .= '<span class="tiny"> - '.Codes::beautify_introduction($item['introduction']).'</span>';

			// insert overlay data, if any
			if(is_object($overlay))
				$text .= $overlay->get_text('list', $item);

			// append the row
			$text .= BR;
			$count++;
			$checked = '';
		}

		// div suffix
		$text .= '</div>';

		// no valid account has been found
		if(!$count)
			$text = '';

		// end of processing
		SQL::free($result);
		return $text;
	}

}

?>
