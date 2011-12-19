<?php
/**
 * a bare list of e-mail addresses
 *
 * @see users/users.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_users_as_address extends Layout_interface {

	/**
	 * list users
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	 *
	 * @see skins/layout.php
	**/
	function layout($result) {
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

			// we need some address
			if(!$item['email'])
				continue;

			// do not write to myself
// 			if($item['id'] == Surfer::get_id())
// 				continue;

			$label = ucfirst(trim(Codes::beautify(strip_tags($item['full_name'], '<br><div><img><p><span>'))));
			if(!$label)
				$label = ucfirst($item['nick_name']);

			// one entry per address
			$items[ trim($item['email']) ] = $label;

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>