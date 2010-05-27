<?php
/**
 * layout users
 *
 * This is the default layout for users.
 *
 * @see users/index.php
 * @see users/users.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_users extends Layout_interface {

	/**
	 * list users
	 *
	 * @param resource the SQL result
	 * @param string a variant, if any
	 * @return string the rendered text
	 *
	 * @see skins/layout.php
	**/
	function &layout(&$result, $variant='full') {
		global $context;

		// we return an array of ($url => $attributes)
		$items = array();

		// empty list
		if(!SQL::count($result))
			return $items;

		// flag idle users
		$idle = gmstrftime('%Y-%m-%d %H:%M:%S', time() - 600);

		// process all items in the list
		while($item =& SQL::fetch($result)) {

			// initialize variables
			$prefix = $suffix = $icon = '';

			// the url to view this item
			$url = Users::get_permalink($item);

			// flag profiles updated recently
			if($item['create_date'] >= $context['fresh'])
				$suffix .= NEW_FLAG;
			elseif($item['edit_date'] >= $context['fresh'])
				$suffix .= UPDATED_FLAG;

			// signal restricted and private articles
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// signal locked profiles
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

			// show contact information
			if(Surfer::may_contact())
				$suffix .= Users::build_presence($item);

			// the introduction
			if($item['introduction']) {
				if(is_callable(array('codes', 'beautify')))
					$suffix .= ' -&nbsp;'.Codes::beautify($item['introduction']);
				else
					$suffix .= ' -&nbsp;'.$item['introduction'];
			}

			// display all tags
			if($item['tags'])
				$suffix .= ' <span class="tags">'.Skin::build_tags($item['tags'], 'user:'.$item['id']).'</span>';

			// details
			$details = array();

			// capability
			if($item['capability'] == 'A')
				$details[] = i18n::s('Associate');
			elseif($item['capability'] == 'S')
				$details[] = i18n::s('Subscriber');
			else
				$details[] = i18n::s('Member');

			// creation date
			if($item['create_date'])
				$details[] = sprintf(i18n::s('registered %s'), Skin::build_date($item['create_date']));

			// last login
			if($variant == 'dates') {
				if(isset($item['login_date']) && ($item['login_date'] > '2000-01-01')) {
					$address = '';
					if($item['login_address'])
						$address = ' ('.$item['login_address'].')';
					$details[] = sprintf(i18n::s('last login %s'), Skin::build_date($item['login_date']).$address);
				} else
					$details[] = i18n::s('no login');

			}

			// last post
			if($variant == 'dates') {
				if(isset($item['post_date']) && ($item['post_date'] > '2000-01-01'))
					$details[] = sprintf(i18n::s('last post %s'), Skin::build_date($item['post_date']));
			}

			// posts
			if(intval($item['posts']) > 1)
				$details[] = sprintf(i18n::s('%d posts'), intval($item['posts']));

			if(count($details))
				if($variant == 'full')
					$suffix .= ' <span class="details">('.implode(', ', $details).')</span>';
				else
					$suffix .= ' <span class="details">'.implode(', ', $details).'</span>';

			// flag idle users
			if(isset($item['click_date']) && ($item['click_date'] < $idle))
				$class = 'idle user';
			else
				$class = 'user';

			// use the avatar, if any
			if(isset($item['avatar_url']) && isset($context['users_with_avatars']) && $context['users_with_avatars'] == 'Y')
				$icon = $item['avatar_url'];

			// list all components for this item
			$items[$url] = array($prefix, $label, $suffix, $class, $icon, $hover);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>