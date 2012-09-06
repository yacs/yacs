<?php
/**
 * provide only user ids
 *
 * @see users/users.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_users_as_ids extends Layout_interface {

	/**
	 * list users
	 *
	 * @param resource the SQL result
	 * @return array a bare list of item ids
	 *
	 * @see skins/layout.php
	**/
	function layout($result) {
		global $context;

		// we return an array of ids
		$items = array();

		// empty list
		if(!SQL::count($result))
			return $items;

		// process all items in the list
		while($item = SQL::fetch($result)) {

			// just remember the id
			$items[] = $item['id'];

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>
