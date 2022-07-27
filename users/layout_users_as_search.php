<?php
/**
 * layout users for search requests
 *
 * @see search.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_users_as_search extends Layout_interface {

	/**
	 * list users
	 *
	 * @param resource the SQL result
	 * @return array of resulting items ($score, $summary), or NULL
	 *
	 * @see layouts/layout.php
	**/
	function layout($result) {
		global $context;

		// we return an array of array($score, $summary)
		$items = array();

		// empty list
		if(!SQL::count($result))
			return $items;

		// flag idle users
		$idle = gmdate('Y-m-d H:i:s', time() - 600);

		// process all items in the list
		while($item = SQL::fetch($result)) {

			// one box at a time
			$box = '';

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
				$title = ucfirst(Skin::strip($item['full_name'], 10));
				$hover = $item['nick_name'];
			} else {
				$title = ucfirst(Skin::strip($item['nick_name'], 10));
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
				$suffix .= ' '.tag::_('span', tag::_class('tags'), Skin::build_tags($item['tags']));

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
			if($this->layout_variant == 'dates') {
				if(isset($item['login_date']) && ($item['login_date'] > NULL_DATE)) {
					$address = '';
					if($item['login_address'])
						$address = ' ('.$item['login_address'].')';
					$details[] = sprintf(i18n::s('last login %s'), Skin::build_date($item['login_date']).$address);
				} else
					$details[] = i18n::s('no login');

			}

			// last post
			if($this->layout_variant == 'dates') {
				if(isset($item['post_date']) && ($item['post_date'] > NULL_DATE))
					$details[] = sprintf(i18n::s('last post %s'), Skin::build_date($item['post_date']));
			}

			// posts
			if(intval($item['posts']) > 1)
				$details[] = sprintf(i18n::s('%d posts'), intval($item['posts']));

			if(count($details))
				if($this->layout_variant == 'full')
					$suffix .= ' <span '.tag::_class('details').'>('.implode(', ', $details).')</span>';
				else
					$suffix .= ' <span '.tag::_class('details').'>'.implode(', ', $details).'</span>';

			// flag idle users
			if(isset($item['click_date']) && ($item['click_date'] < $idle))
				$class = 'idle user';
			else
				$class = 'user';

			// item summary
			$box .= $prefix.Skin::build_link($url, $title, 'user').$suffix;

			// use the avatar, if any
			if(isset($item['avatar_url']) && isset($context['users_with_avatars']) && $context['users_with_avatars'] == 'Y')
				$icon = $item['avatar_url'];

			// layout this item
			if($icon) {

				// build the complete HTML element
				$icon = '<img src="'.$icon.'" alt="" title="'.encode_field(strip_tags($title)).'" />';

				// make it a clickable link
				$icon = Skin::build_link($url, $icon, 'basic');

				$list = array(array($box, $icon));
				$items[] = array($item['score'], Skin::finalize_list($list, 'decorated'));

			// put the item in a division
			} else
				$items[] = array($item['score'], '<div style="margin: 0 0 1em 0">'.$box.'</div>');

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>