<?php
/**
 * layout users in a watch list
 *
 * This script layouts watched users.
 *
 * @see users/element.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_users_as_watch extends Layout_interface {

	/**
	 * list users
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

		// flag users updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// build a list of users
		while($item =& SQL::fetch($result)) {

			// reset everything
			$prefix = $label = $suffix = $icon = '';

			// signal restricted and private users
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG.' ';
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG.' ';

			// indicate the id in the hovering popup
			$hover = i18n::s('Visit user profile');
			if(Surfer::is_member())
				$hover .= ' [user='.$item['id'].']';

			// the url to view this item
			$url = Users::get_url($item['id'], 'view', $item['nick_name']);

			// use full name, then nick name
			if(isset($item['full_name']) && $item['full_name']) {
				$title = $item['full_name'].' <span style="font-size: smaller;">- '.$item['nick_name'].'</span>';
			} elseif(isset($item['nick_name']))
				$title = $item['nick_name'];

			// flag users updated recently
			if($item['create_date'] >= $dead_line)
				$suffix = NEW_FLAG.' ';
			elseif($item['edit_date'] >= $dead_line)
				$suffix = UPDATED_FLAG.' ';

			// get last posts for this author
			$articles =& Articles::list_by_date_for_author($item['id'], 0, 5, 'simple');
			if(is_array($articles))
				$suffix .= Skin::build_list($articles, 'compact');

			// use the avatar, if any
			if(isset($item['avatar_url']))
				$icon = $item['avatar_url'];

			// list all components for this item --use basic link style to avoid prefix or suffix images, if any
			$items[$url] = array($prefix, $title, $suffix, 'basic', $icon, $hover);

		}

		// end of processing
		SQL::free($result);

		return $items;
	}
}

?>