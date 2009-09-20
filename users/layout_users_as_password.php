<?php
/**
 * layout profiles to ask for password recovery
 *
 * @see users/login.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_users_as_password extends Layout_interface {

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

		// empty list
		if(!SQL::count($result)) {
			$output = array();
			return $output;
		}

		// flag users updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));

		// flag idle users
		$idle = gmstrftime('%Y-%m-%d %H:%M:%S', time() - 600);

		// we return an array of ($url => $attributes)
		$items = array();

		// process all items in the list
		while($item =& SQL::fetch($result)) {

			// initialize variables
			$prefix = $suffix = $icon = '';

			// the url to view this item
			$url = Users::get_permalink($item);

			// reset the rendering engine between items
			if(is_callable(array('Codes', 'initialize')))
				Codes::initialize($url);

			// flag profiles updated recently
			if($item['create_date'] >= $dead_line)
				$suffix .= NEW_FLAG;
			elseif($item['edit_date'] >= $dead_line)
				$suffix .= UPDATED_FLAG;

			// signal restricted and private profiles
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// signal banned profiles
			if($item['capability'] == '?')
				$prefix .= EXPIRED_FLAG;

			// item title
			if($item['full_name'])
				$label = ucfirst(Skin::strip($item['full_name'], 10)).' ['.$item['nick_name'].']';
			else
				$label = ucfirst(Skin::strip($item['nick_name'], 10));

			// the introduction
			if($item['introduction']) {
				if(is_callable(array('codes', 'beautify')))
					$suffix .= ' -&nbsp;'.Codes::beautify($item['introduction']);
				else
					$suffix .= ' -&nbsp;'.$item['introduction'];
			}

			// details
			$details = array();

			// creation date
			if($item['create_date'])
				$details[] = sprintf(i18n::s('registered %s'), Skin::build_date($item['create_date']));

			// last login
			if(isset($item['login_date']) && ($item['login_date'] > '2000-01-01'))
				$details[] = sprintf(i18n::s('last login %s'), Skin::build_date($item['login_date']));
			else
				$details[] = i18n::s('no login');

			// last post
			if(isset($item['post_date']) && ($item['post_date'] > '2000-01-01'))
				$details[] = sprintf(i18n::s('last post %s'), Skin::build_date($item['post_date']));

			// posts
			if(intval($item['posts']) > 1)
				$details[] = sprintf(i18n::s('%d posts'), intval($item['posts']));

			if(count($details))
				if($variant == 'full')
					$suffix .= ' <span class="details">('.implode(', ', $details).')</span>';
				else
					$suffix .= ' <span class="details">'.implode(', ', $details).'</span>';

			// the command to ask for a new password
			$suffix .= '<p style="padding: 0.5em 0 0.5em 0">'.Skin::build_link(Users::get_url($item['id'], 'password', $item['nick_name']), i18n::s('Authenticate with this profile'), 'button').'</p>';
			
			// use the avatar, if any
			if(isset($item['avatar_url']))
				$icon = $item['avatar_url'];

			// list all components for this item
			$items[$url] = array($prefix, $label, $suffix, 'user', $icon);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>