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
 * - contact icons, if any (aim, icq, irc, jabber, msn, skype, twitter, yahoo)
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
		
		// we return some text
		$output = '';

		// empty list
		if(!SQL::count($result))
			return $output;

		// flag comments updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));

		// build a list of comments
		$rows = array();
		include_once $context['path_to_root'].'comments/comments.php';
		while($item =& SQL::fetch($result)) {

			// get the anchor
			$anchor =& Anchors::get($item['anchor']);

			// get record
			if($item['create_name']) {
				if(!$poster =& Users::get($item['create_id'])) {
					$poster = array();
					$poster['id'] = 0;
					$poster['full_name'] = $item['create_name'];
					$poster['email'] = $item['create_address'];
				}
			} else {
				if(!$poster =& Users::get($item['edit_id'])) {
					$poster = array();
					$poster['id'] = 0;
					$poster['full_name'] = $item['edit_name'];
					$poster['email'] = $item['edit_address'];
				}
			}

			// author description
			$author = '';
			
			// avatar
			if(isset($poster['avatar_url']) && $poster['avatar_url'])
				$author .= '<img src="'.$poster['avatar_url'].'" alt="avatar" title="avatar" class="avatar" />'.BR;

			// link to poster, if possible
			$author .= Users::get_link($poster['full_name'], $poster['email'], $poster['id']);

			$author .= '<span class="details">';

			// show contact information
			if(Surfer::may_contact() && ($presence = Users::build_presence($poster)))
				$author .= BR.$presence;

			// from where
			if(isset($poster['from_where']) && $poster['from_where'])
				$author .= sprintf(i18n::s('from %s'), Codes::beautify($poster['from_where']));

			// guest/member/associate
			$capability = '';
			if(!isset($poster['capability']))
				;
			elseif($poster['capability'] == 'A')
				$capability = i18n::s('Associate').', ';
			elseif($poster['capability'] == 'M')
				;
			elseif($poster['capability'] == 'S')
				$capability = i18n::s('Subscriber').', ';

			// + posts
			if(isset($poster['posts']))
				$author .= BR.$capability.sprintf(i18n::ns('%d post', '%d posts', $poster['posts']), $poster['posts']);

			// put everything in the author cell
			$author .= '</span>';

			// commands to handle this comment
			$menu = array();

			// the reply and quote commands are offered when new comments are allowed
			if(Comments::are_allowed($anchor)) {

				Skin::define_img('COMMENTS_ADD_IMG', 'comments/add.gif');
				$menu = array_merge($menu, array( Comments::get_url($item['id'], 'reply') => COMMENTS_ADD_IMG.i18n::s('Reply') ));

				Skin::define_img('COMMENTS_QUOTE_IMG', 'comments/quote.gif');
				$menu = array_merge($menu, array( Comments::get_url($item['id'], 'quote') => COMMENTS_QUOTE_IMG.i18n::s('Quote') ));
			}

			// additional commands for associates and poster and editor
			if(Comments::are_editable($anchor, $item)) {
				Skin::define_img('COMMENTS_EDIT_IMG', 'comments/edit.gif');
				$menu = array_merge($menu, array( Comments::get_url($item['id'], 'edit') => COMMENTS_EDIT_IMG.i18n::s('Edit') ));

				Skin::define_img('COMMENTS_DELETE_IMG', 'comments/delete.gif');
				$menu = array_merge($menu, array( Comments::get_url($item['id'], 'delete') => COMMENTS_DELETE_IMG.i18n::s('Delete') ));
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
			$text .= '<hr style="clear:both" />';

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
		$output = Skin::table_prefix('yabb');
		$count = 1;
		foreach($rows as $row) {
			if($count%2)
				$output .= '<tr class="odd">'.$row.'</tr>';
			else
				$output .= '<tr class="even">'.$row.'</tr>';
			$count++;
		}
		$output .= '</table>';

		return $output;
	}
}

?>