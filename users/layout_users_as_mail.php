<?php
/**
 * layout users as a list of recipients
 *
 * @see users/users.php
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_users_as_mail extends Layout_interface {

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

			// not a valid e-mail recipient
			if(!$item['email'] || !preg_match('/^[a-zA-Z0-9\.\-_]+?@[a-zA-Z0-9\.\-_]+/', $item['email']))
				continue;

			// the e-mail address
			$key = $item['email'];

			// use the full name to label the link
			$label = ucfirst(Skin::strip($item['full_name'], 10));

			// list all components for this item
			$items[$key] = $label;

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>