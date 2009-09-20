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
		if(!$delta = SQL::count($result))
			return $items;

		// flag users updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// build a list of users
		$count = 0;
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
				$title = $item['full_name'].' ('.$item['nick_name'].')';
			} elseif(isset($item['nick_name']))
				$title = $item['nick_name'];

			// flag users updated recently
			if($item['create_date'] >= $dead_line)
				$suffix = NEW_FLAG.' ';
			elseif($item['edit_date'] >= $dead_line)
				$suffix = UPDATED_FLAG.' ';

			// show contact information
			if(Surfer::may_contact() && ($contacts = Users::build_presence($item)))
				$suffix .= ' '.$contacts;

			// do not use description because of codes such as location, etc
			if(isset($item['introduction']) && $item['introduction'])
				$suffix .= ' - '.Codes::beautify($item['introduction']);

			// get last posts for this author
			$articles =& Members::list_articles_for_member_by('edition', 'user:'.$item['id'], 0, 3, 'compact');
			if(is_array($articles))
				$suffix .= Skin::build_list($articles, 'details');
			else
				$suffix .= $articles;

			// use the avatar, if any
			if(isset($item['avatar_url']))
				$icon = $item['avatar_url'];

			// list all components for this item --use basic link style to avoid prefix or suffix images, if any
			$items[$url] = array($prefix, $title, $suffix, 'basic', $icon, $hover);

			// limit to one page of results
			if(++$count >= USERS_PER_PAGE)
				break;
		}

		// end of processing
		SQL::free($result);

		// turn this to some text
		$items = Skin::build_list($items, 'decorated');
		
		// some indications on the number of connections
		if($delta -= $count) {
			if($delta < 100)
				$label = sprintf(i18n::ns('and %d other contact', 'and %d other contacts', $delta), $delta);
			else
				$label = i18n::s('and many more contacts');

			$items .= '<p>'.$label.'</p>';
		}
		
		return $items;
	}
}

?>