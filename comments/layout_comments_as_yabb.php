<?php
/**
 * layout comments as posts in a YABB forum
 *
 * This layout features two columns, one to display contributor attributes, and the second to show
 * the comment itself.
 *
 * For each poster following attributes are displayed if available:
 * - nickname (with a link to the user profile, if any)
 * - avatar image (if available)
 * - from where (if available)
 * - status (associate, member, subscriber)
 * - number of posts
 * - registration date
 * - contact icons, if any (aim, icq, irc, jabber, msn, skype, yahoo)
 *
 * The comment icon is also a link to the comment permalink.
 *
 * Post of new comments may have been explicitly prevented in anchor (option '[code]no_comments[/code]').
 * Otherwise commands to post new comments are added if the surfer has been authenticated,
 * or if anonymous comments are allowed (parameter '[code]users_with_anonymous_comments[/code]' set to 'Y'),
 * of if teasers have been enabled (parameter '[code]users_without_teasers[/code]' not set to 'Y').
 * Both global parameters are set in [script]users/configure.php[/script]).
 *
 * @link http://www.yabbforum.com/ Yet Another Bulletin Board
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester FabriceV
 * @tester Lucrecius
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 *
 * @see articles/view.php
 * @see control/configure.php
 */
Class Layout_comments_as_yabb extends Layout_interface {

	/**
	 * list comments as successive notes in a thread
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	**/
	function &layout(&$result) {
		global $context;

		// empty list
		if(!SQL::count($result)) {
			$output = array();
			return $output;
		}

		// flag comments updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));

		// build a list of comments
		$rows = array();
		include_once $context['path_to_root'].'comments/comments.php';
		while($item =& SQL::fetch($result)) {

			// get the anchor
			$anchor = Anchors::get($item['anchor']);

			// poster name
			if($item['create_name'])
				$author = Users::get_link($item['create_name'], $item['create_address'], $item['create_id']);
			else
				$author = Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']);

			// get record
			if($item['create_name'])
				$poster =& Users::get($item['create_id']);
			else
				$poster =& Users::get($item['edit_id']);

			// avatar
			if(isset($poster['avatar_url']) && $poster['avatar_url'])
				$author .= BR.'<img src="'.$poster['avatar_url'].'" alt="avatar" title="avatar" class="avatar" />';

			$author .= '<span class="details">';

			// from where
			if(isset($poster['from_where']) && $poster['from_where'])
				$author .= BR.sprintf(i18n::s('from %s'), Codes::beautify($poster['from_where']));

			// guest/member/associate
			$capability = '';
			if($poster['capability'] == 'A')
				$capability = i18n::s('Associate').', ';
			elseif($poster['capability'] == 'M')
				;
			elseif($poster['capability'] == 'S')
				$capability = i18n::s('Subscriber').', ';

			// + posts
			$author .= BR.$capability.sprintf(i18n::ns('%d post', '%d posts', $poster['posts']), $poster['posts']);

			// show contact information
			if(Surfer::may_contact()) {

				// jabber
				if(isset($poster['jabber_address']) && $poster['jabber_address'])
					$author .= ' '.Skin::build_presence($poster['jabber_address'], 'jabber');

				// skype
				if(isset($poster['skype_address']) && $poster['skype_address'])
					$author .= ' '.Skin::build_presence($poster['skype_address'], 'skype');

				// yahoo
				if(isset($poster['yahoo_address']) && $poster['yahoo_address'])
					$author .= ' '.Skin::build_presence($poster['yahoo_address'], 'yahoo');

				// msn
				if(isset($poster['msn_address']) && $poster['msn_address'])
					$author .= ' '.Skin::build_presence($poster['msn_address'], 'msn');

				// aim
				if(isset($poster['aim_address']) && $poster['aim_address'])
					$author .= ' '.Skin::build_presence($poster['aim_address'], 'aim');

				// irc
				if(isset($poster['irc_address']) && $poster['irc_address'])
					$author .= ' '.Skin::build_presence($poster['irc_address'], 'irc');

				// icq
				if(isset($poster['icq_address']) && $poster['icq_address'])
					$author .= ' '.Skin::build_presence($poster['icq_address'], 'icq');

			}

			// put everything in the author cell
			$author .= '</span>';

			// commands to handle this comment
			$menu = array();

			// the reply and quote commands are offered when new comments are allowed
			if(Comments::are_allowed($anchor)) {

				Skin::define_img('NEW_COMMENT_IMG', 'icons/comments/new.gif');
				$menu = array_merge($menu, array( Comments::get_url($item['id'], 'reply') => NEW_COMMENT_IMG.i18n::s('Reply') ));

				Skin::define_img('QUOTE_COMMENT_IMG', 'icons/comments/quote.gif');
				$menu = array_merge($menu, array( Comments::get_url($item['id'], 'quote') => QUOTE_COMMENT_IMG.i18n::s('Quote') ));
			}

			// additional commands for associates and poster and editor
			if(Comments::are_editable($anchor, $item)) {
				Skin::define_img('EDIT_COMMENT_IMG', 'icons/comments/edit.gif');
				$menu = array_merge($menu, array( Comments::get_url($item['id'], 'edit') => EDIT_COMMENT_IMG.i18n::s('Edit') ));

				Skin::define_img('DELETE_COMMENT_IMG', 'icons/comments/delete.gif');
				$menu = array_merge($menu, array( Comments::get_url($item['id'], 'delete') => DELETE_COMMENT_IMG.i18n::s('Delete') ));
			}

			// comment main text
			$text = '';

			// float the menu on the right
			if(count($menu))
				$text .= '<div style="float: right">'.Skin::build_list($menu, 'menu').'</div>';

			// float details on the left
			$text .= '<p class="details" style="float: left">';

			// get an icon for this comment
			$icon = Comments::get_img($item['type']);

			// link to comment permalink
			$text .= Skin::build_link(Comments::get_url($item['id']), $icon, 'basic', i18n::s('View this comment'));

			// link to the previous comment in thread, if any
			if($item['previous_id'] && ($previous =& Comments::get($item['previous_id'])))
				$text .= sprintf(i18n::s('inspired from %s'), Skin::build_link(Comments::get_url($previous['id']), $previous['create_name'])).' ';

			// the creation date
			if($item['create_date'])
				$text .= Skin::build_date($item['create_date'], 'with_hour');
			else
				$text .= Skin::build_date($item['edit_date'], 'with_hour');

			// flag new comments
			if($item['create_date'] >= $dead_line)
				$text .= NEW_FLAG;

			// end of details
			$text .= '</p>';

			// clear on both sides
			$text .= '<hr style="clear:both"'.EOT;

			// the comment itself
			$text .= ucfirst(trim(Codes::beautify($item['description'])))."\n";

			// comment has been modified
			if($item['create_name'] && ($item['edit_name'] != $item['create_name']))
				$text .= '<p class="details">'.ucfirst(sprintf(i18n::s('edited by %s %s'), $item['edit_name'], Skin::build_date($item['edit_date']))).'</p>';

			// the main part of the comment, with an id
			$text = '<td class="comment" id="comment_'.$item['id'].'">'.$text.'</td>';

			// this is another row of the output
			$rows[] = '<td class="author">'.$author.'</td>'.$text;

		}

		// end of processing
		SQL::free($result);

		// sanity check
		if(!count($rows))
			return '';

		// return a table
		$text = Skin::table_prefix('yabb');
		$count = 1;
		foreach($rows as $row) {
			if($count%2)
				$text .= '<tr class="odd">'.$row.'</tr>';
			else
				$text .= '<tr class="even">'.$row.'</tr>';
			$count++;
		}
		$text .= '</table>';

		return $text;
	}
}

?>