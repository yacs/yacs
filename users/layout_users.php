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
			$url = Users::get_url($item['id'], 'view', $item['nick_name']);

			// reset the rendering engine between items
			if(is_callable(array('Codes', 'initialize')))
				Codes::initialize($url);

			// flag profiles updated recently
			if($item['create_date'] >= $dead_line)
				$suffix .= NEW_FLAG;
			elseif($item['edit_date'] >= $dead_line)
				$suffix .= UPDATED_FLAG;

			// signal restricted and private articles
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// signal banned profiles
			if($item['capability'] == '?')
				$prefix .= EXPIRED_FLAG;

			// item title
			if($item['full_name'])
				$label = ucfirst(Skin::strip($item['full_name'], 10));
			else
				$label = ucfirst(Skin::strip($item['nick_name'], 10));

			// show contact information
			if(Surfer::may_contact()) {

				// jabber
				if(isset($item['jabber_address']) && $item['jabber_address'])
					$suffix .= ' '.Skin::build_presence($item['jabber_address'], 'jabber');

				// skype
				if(isset($item['skype_address']) && $item['skype_address'])
					$suffix .= ' '.Skin::build_presence($item['skype_address'], 'skype');

				// yahoo
				if(isset($item['yahoo_address']) && $item['yahoo_address'])
					$suffix .= ' '.Skin::build_presence($item['yahoo_address'], 'yahoo');

				// msn
				if(isset($item['msn_address']) && $item['msn_address'])
					$suffix .= ' '.Skin::build_presence($item['msn_address'], 'msn');

				// aim
				if(isset($item['aim_address']) && $item['aim_address'])
					$suffix .= ' '.Skin::build_presence($item['aim_address'], 'aim');

				// irc
				if(isset($item['irc_address']) && $item['irc_address'])
					$suffix .= ' '.Skin::build_presence($item['irc_address'], 'irc');

				// icq
				if(isset($item['icq_address']) && $item['icq_address'])
					$suffix .= ' '.Skin::build_presence($item['icq_address'], 'icq');

			}

			// the introduction
			if($item['introduction']) {
				if(is_callable(array('codes', 'beautify')))
					$suffix .= ' -&nbsp;'.Codes::beautify($item['introduction']);
				else
					$suffix .= ' -&nbsp;'.$item['introduction'];
			}

			// details
			$details = array();

			// capability
			if(Surfer::is_associate() && ($item['capability'] == 'A'))
				$details[] = i18n::s('associate');
			elseif($item['capability'] == 'S')
				$details[] = i18n::s('subscriber');
			else
				$details[] = i18n::s('member');

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
			$items[$url] = array($prefix, $label, $suffix, $class, $icon);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>