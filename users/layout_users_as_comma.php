<?php
/**
 * layout users as a very compact list
 *
 * @see users/users.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_users_as_comma extends Layout_interface {

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

		// we return some text
		$text = '';
		
		// empty list
		if(!$delta = SQL::count($result))
			return $text;

		// flag users updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));

		// flag idle users
		$idle = gmstrftime('%Y-%m-%d %H:%M:%S', time() - 600);

		// process all items in the list
		$count = 0;
		$items = array();
		while($item =& SQL::fetch($result)) {

			// url to view the user
			$url = Users::get_permalink($item);

			// initialize variables
			$prefix = $suffix = '';

			// signal restricted and private users
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// signal banned profiles
			if($item['capability'] == '?')
				$prefix .= EXPIRED_FLAG;

			// item title
			if($item['full_name']) {
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

			// provide only some results
			if(++$count >= 4)
				break;
		}

		// end of processing
		SQL::free($result);

		// turn this to some text
		$text = Skin::build_list($items, 'comma');
		
		// some indications on the number of connections
		if($delta -= $count)
			$text .= ', ...';
		
		return $text;
	}

}

?>