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
	 * @return array of resulting items (id => label), or NULL
	 *
	 * @see skins/layout.php
	**/
	function &layout(&$result) {
		global $context;

		// we return an array of ($url => $attributes)
		$items = array();

		// empty list
		if(!$delta = SQL::count($result))
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
			$hover = i18n::s('View profile');
			if(Surfer::is_member())
				$hover .= ' [user='.$item['id'].']';

			// the url to view this item
			$url = Users::get_permalink($item);

			// use full name, then nick name
			if(isset($item['full_name']) && $item['full_name']) {
				$title = $item['full_name'];
			} elseif(isset($item['nick_name']))
				$title = $item['nick_name'];

			// show contact information
			if(Surfer::may_contact() && ($contacts = Users::build_presence($item)))
				$suffix .= ' '.$contacts;

			// flag users updated recently
			if($item['create_date'] >= $dead_line)
				$suffix .= NEW_FLAG.' ';
			elseif($item['edit_date'] >= $dead_line)
				$suffix .= UPDATED_FLAG.' ';

			// do not use description because of codes such as location, etc
			if(isset($item['introduction']) && $item['introduction'])
				$suffix .= ' - '.Codes::beautify($item['introduction']);

			// display all tags
// 			if($item['tags'])
// 				$suffix .= ' <span class="details tags">'.Skin::build_tags($item['tags'], 'user:'.$item['id']).'</span>';

			// the full label
			$label = $prefix.Skin::build_link($url, $title, 'basic', $hover).$suffix;

			// use the avatar, if any
			$icon ='';
			if(isset($item['avatar_url']) && $item['avatar_url'])
				$icon = '<a href="'.$url.'"><img src="'.$item['avatar_url'].'" alt=" " title="'.encode_field($hover).'" style="float: left; max-width: 16px; max-height: 16px; margin-right: 4px;" /></a>';

			// list all components for this item --use basic link style to avoid prefix or suffix images, if any
			$items[ $item['id'] ] = $icon.$label;

		}

		// end of processing
		SQL::free($result);

		// job done
		return $items;
	}
}

?>