<?php
/**
 * layout users as a compact list
 *
 * @see users/users.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_users_as_compact extends Layout_interface {

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
		if(!$delta = SQL::count($result)) {
			$output = array();
			return $output;
		}

		// we return an array of ($url => $attributes)
		$items = array();

		// flag idle users
		$idle = gmstrftime('%Y-%m-%d %H:%M:%S', time() - 600);

		// process all items in the list
		$count = 0;
		while($item =& SQL::fetch($result)) {

			// url to view the user
			$url = Users::get_permalink($item);

			// initialize variables
			$prefix = $suffix = '';

			// signal restricted and private users
			if(isset($item['active']) && ($item['active'] == 'N'))
				$prefix .= PRIVATE_FLAG;
			elseif(isset($item['active']) && ($item['active'] == 'R'))
				$prefix .= RESTRICTED_FLAG;

			// signal banned profiles
			if(isset($item['active']) && ($item['capability'] == '?'))
				$prefix .= EXPIRED_FLAG;

			// item title
			if(isset($item['full_name']) && $item['full_name']) {
				$label = ucfirst(Skin::strip($item['full_name'], 10));
				$hover = $item['nick_name'];
			} else {
				$label = ucfirst(Skin::strip($item['nick_name'], 10));
				$hover = $item['full_name'];
			}

			// flag idle users
			if(!isset($item['click_date']) || ($item['click_date'] < $idle))
				$class = 'idle user';
			else
				$class = 'user';

			// list all components for this item
			$items[$url] = array($prefix, $label, $suffix, $class, NULL, $hover);

			// limit to one page of results
			if(++$count >= USERS_PER_PAGE)
				break;
		}

		// end of processing
		SQL::free($result);

		// turn this to some text
		$items = Skin::build_list($items, 'compact');

		// some indications on the number of connections
		if($delta -= $count) {
			if($delta < 100)
				$label = sprintf(i18n::ns('and %d other person', 'and %d other persons', $delta), $delta);
			else
				$label = i18n::s('and many more persons');

			$items .= '<p>'.$label.'</p>';
		}

		return $items;
	}

}

?>