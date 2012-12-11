<?php
/**
 * layout users for autocompletion
 *
 * @see users/complete.php
 * @see users/users.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_users_as_complete extends Layout_interface {

	/**
	 * list users
	 *
	 * @param resource the SQL result
	 * @return array of ($nick_name => $more)
	 *
	 * @see skins/layout.php
	**/
	function layout($result) {
		global $context;

		// we return an array of ($nick_name => $more)
		$items = array();

		// empty list
		if(!SQL::count($result))
			return $items;

		// process all items in the list
		while($item = SQL::fetch($result)) {

			// unique identifier
			$key = $item['nick_name'];

			// use the full name, if nick name is not part of it
			$more = '';
			if($item['full_name'] && !preg_match('/\b'.preg_quote($item['nick_name'], '/').'\b/', $item['full_name']))
				$more = ucfirst($item['full_name']).' ';

			// else use e-mail address, if any --but only to authenticated surfer
			if($item['email'] && Surfer::is_logged()) {
				if($more)
					$more .= '&lt;'.$item['email'].'&gt;';
				else
					$more .= $item['email'];
			}

			// else use introduction, if any
			elseif($item['introduction'])
				$more .= $item['introduction'];

			// record this item
			$items[$key] = $more;

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>