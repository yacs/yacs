<?php
/**
 * layout comments as successive updates in a wall
 *
 * This layout features two columns, one to display contributor icon, and the second to show
 * the comment itself.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 *
 * @see articles/view.php
 * @see control/configure.php
 */
Class Layout_comments_as_updates extends Layout_interface {

	/**
	 * list comments as successive notes in a thread
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	**/
	function layout($result) {
		global $context;

		// we return some text
		$output = '';

		// empty list
		if(!SQL::count($result))
			return $output;

		// build a list of comments
		$rows = array();
		include_once $context['path_to_root'].'comments/comments.php';
		while($item = SQL::fetch($result)) {

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

			// except for automatic notifications
			if($item['type'] != 'notification') {

				// avatar
				if(isset($poster['avatar_url']) && $poster['avatar_url'])
					$author .= '<img src="'.$poster['avatar_url'].'" alt="" title="avatar" class="avatar" />'.BR;

				// link to poster, if possible
				$author .= Users::get_link($poster['full_name'], $poster['email'], $poster['id']);

			}

			// commands to handle this comment
			$menu = array();

			// an automatic notification
			if($item['type'] == 'notification') {

				// additional commands for associates and poster and editor
				if($anchor->is_owned()) {
					Skin::define_img('COMMENTS_DELETE_IMG', 'comments/delete.gif');
					$menu = array_merge($menu, array( Comments::get_url($item['id'], 'delete') => COMMENTS_DELETE_IMG.i18n::s('Delete') ));
				}

			// regular case
			} else {

				// the reply and quote commands are offered when new comments are allowed
				if(Comments::allow_creation($anchor)) {

					Skin::define_img('COMMENTS_ADD_IMG', 'comments/add.gif');
					$menu = array_merge($menu, array( Comments::get_url($item['id'], 'reply') => COMMENTS_ADD_IMG.i18n::s('Reply') ));

					Skin::define_img('COMMENTS_QUOTE_IMG', 'comments/quote.gif');
					$menu = array_merge($menu, array( Comments::get_url($item['id'], 'quote') => COMMENTS_QUOTE_IMG.i18n::s('Quote') ));
				}

				// additional commands for associates and poster and editor
				if(Comments::allow_modification($anchor, $item)) {
					Skin::define_img('COMMENTS_EDIT_IMG', 'comments/edit.gif');
					$menu = array_merge($menu, array( Comments::get_url($item['id'], 'edit') => COMMENTS_EDIT_IMG.i18n::s('Edit') ));

					Skin::define_img('COMMENTS_DELETE_IMG', 'comments/delete.gif');
					$menu = array_merge($menu, array( Comments::get_url($item['id'], 'delete') => COMMENTS_DELETE_IMG.i18n::s('Delete') ));
				}

			}

			// comment main text
			$text = '';

			// display comment main text
			$comment = $item['description'];

			// display signature, but not for notifications
			if($item['type'] != 'notification')
				$comment .= Users::get_signature($item['create_id']);

			// format and display
			$text .= ucfirst(trim(Codes::beautify($comment)))."\n";

			// comment has been modified
			if($item['create_name'] && ($item['edit_name'] != $item['create_name']))
				$text .= '<p class="details">'.ucfirst(sprintf(i18n::s('edited by %s %s'), $item['edit_name'], Skin::build_date($item['edit_date']))).'</p>';

			// clear on both sides
			$text .= '<hr style="clear:both" />';

			// float the menu on the right
			if(count($menu))
				$text .= '<div style="float: right">'.Skin::build_list($menu, 'menu').'</div>';

			// float details on the left
			$text .= '<p class="details" style="float: left">';

			// get an icon for this comment
			$icon = Comments::get_img($item['type']);

			// link to comment permalink
			$text .= Skin::build_link(Comments::get_url($item['id']), $icon, 'basic', i18n::s('View this comment')).' ';

			// link to the previous comment in thread, if any
			if($item['previous_id'] && ($previous =& Comments::get($item['previous_id'])))
				$text .= sprintf(i18n::s('inspired from %s'), Skin::build_link(Comments::get_url($previous['id']), $previous['create_name'])).' ';

			// the creation date
			if($item['create_date'])
				$text .= Skin::build_date($item['create_date'], 'with_hour');
			else
				$text .= Skin::build_date($item['edit_date'], 'with_hour');

			// flag new comments
			if($item['create_date'] >= $context['fresh'])
				$text .= NEW_FLAG;

			// end of details
			$text .= '</p>';

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
