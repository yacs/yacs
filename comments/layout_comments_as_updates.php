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
	 * this layout arranges reactions to previous comments
	 * @return boolean TRUE
	 */
	function can_handle_cascaded_items() {
		return TRUE;
	}

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
			$anchor = Anchors::get($item['anchor']);

			// get poster information
			$poster = array();
			if($item['create_name']) {
				if(!$poster = Users::get($item['create_id'])) {
					$poster['id'] = 0;
					$poster['full_name'] = $item['create_name'];
					$poster['email'] = $item['create_address'];
				}
			} else {
				if(!$poster = Users::get($item['edit_id'])) {
					$poster['id'] = 0;
					$poster['full_name'] = $item['edit_name'];
					$poster['email'] = $item['edit_address'];
				}
			}

			// author description
			$author = '';

			// avatar, but not for notifications
			if(($item['type'] != 'notification') && isset($poster['avatar_url']) && $poster['avatar_url'])
				$author .= '<img src="'.$poster['avatar_url'].'" alt="" title="avatar" class="avatar" />'.BR;

			// link to poster, if possible
			if(isset($poster['id']))
				$author .= Users::get_link($poster['full_name'], $poster['email'], $poster['id']);

			// commands to handle this comment
			$menu = array();

			// get an icon for this comment
			$icon = Comments::get_img($item['type']);

			// link to comment permalink
			$label = Skin::build_link(Comments::get_url($item['id']), $icon, 'basic', i18n::s('View this comment')).' ';

			// the creation date
			if($item['create_date'])
				$label .= Skin::build_date($item['create_date'], 'with_hour');
			else
				$label .= Skin::build_date($item['edit_date'], 'with_hour');

			// flag new comments
			if($item['create_date'] >= $context['fresh'])
				$label .= NEW_FLAG;

			$menu[] = $label;

			// an approval -- can be modified, but not deleted
			if($item['type'] == 'approval') {

				// additional commands for associates and poster and editor
				if($anchor->is_owned()) {
					Skin::define_img('COMMENTS_EDIT_IMG', 'comments/edit.gif');
					$menu[] = Skin::build_link(Comments::get_url($item['id'], 'edit'), COMMENTS_EDIT_IMG.i18n::s('Edit'), 'basic');
				}

			// an automatic notification -- can be deleted, but not modified
			} elseif($item['type'] == 'notification') {

				// additional commands for associates and poster and editor
				if($anchor->is_owned()) {
					Skin::define_img('COMMENTS_DELETE_IMG', 'comments/delete.gif');
					$menu[] = Skin::build_link(Comments::get_url($item['id'], 'delete'), COMMENTS_DELETE_IMG.i18n::s('Delete'), 'basic');
				}

			// regular case
			} else {

				// additional commands for associates and poster and editor
				if(Comments::allow_modification($anchor, $item)) {
					Skin::define_img('COMMENTS_EDIT_IMG', 'comments/edit.gif');
					$menu[] = Skin::build_link(Comments::get_url($item['id'], 'edit'), COMMENTS_EDIT_IMG.i18n::s('Edit'), 'basic');

					Skin::define_img('COMMENTS_DELETE_IMG', 'comments/delete.gif');
					$menu[] = Skin::build_link(Comments::get_url($item['id'], 'delete'), COMMENTS_DELETE_IMG.i18n::s('Delete'), 'basic');
				}

			}

			// comment main text
			$text = '';

			// state clearly that this is an approval
			if(($item['type'] == 'approval') && isset($poster['id']))
				$text .= '<p>'.sprintf(i18n::s('%s has provided his approval'),
					Users::get_link($poster['full_name'], $poster['email'], $poster['id'])).'</p>';

			// display comment main text
			$text .= $item['description'];

			// display signature, but not for notifications
			if($item['type'] != 'notification')
				$text .= Users::get_signature($item['create_id']);

			// format and display
			$text = ucfirst(trim($text));

			// float the menu on the right
			if(count($menu))
				$text = '<div style="text-align: right">'.Skin::finalize_list($menu, 'menu').'</div>'.$text;

			// comment has been modified
			if($item['create_name'] && ($item['edit_name'] != $item['create_name']))
				$text .= '<p '.tag::_class('details').'>'.ucfirst(sprintf(i18n::s('edited by %s %s'), $item['edit_name'], Skin::build_date($item['edit_date']))).'</p>';

			// potential replies to this comment
			if($item['type'] != 'notification') {

				// look for replies
				if($replies = Comments::list_next($item['id'], 'replies')) {
					if(is_array($replies))
						$replies = Skin::build_list($replies, 'compact');
					$text .= '<div>'.$replies.'</div>';
				}

				// allow to reply to this comment
				if(Comments::allow_creation($anchor)) {

					// the form to edit a comment
					$text .= '<form method="post" action="'.$context['url_to_root'].Comments::get_url($item['id'], 'reply').'" onsubmit="return validateDocumentPost(this)" enctype="multipart/form-data"><div style="margin-top: 1em;">';

					// reference the anchor page
					$text .= '<input type="hidden" name="anchor" value="'.$item['anchor'].'" />';

					// remember the id of the replied comment
					$text .= '<input type="hidden" name="previous_id" value="'.$item['id'].'" />';

					// notify watchers
					$text .= '<input type="hidden" name="notify_watchers" value="Y" />';

					// ensure id uniqueness
					static $fuse_id;
					if(!isset($fuse_id))
						$fuse_id = 1;
					else
						$fuse_id++;

					// a textarea that grow on focus
					Page::insert_script('var reply'.$fuse_id.'=1;');
					$text .= '<textarea name="description" id="reply'.$fuse_id.'"'
						.	' rows="1" cols="50"'
						.	' onfocus="if(reply'.$fuse_id.'){$(\'div#submit'.$fuse_id.'\').slideDown(600);reply'.$fuse_id.'=0;}">'
						.	'</textarea>'."\n";

					// fix number of rows in firefox
					Page::insert_script(
						'$(function(){'
						.	'$("textarea#reply'.$fuse_id.'")'
						.		'.each(function(){'
						.			'var lineHeight = parseFloat($(this).css("line-height"));'
						.			'var lines = $(this).attr("rows")*1 || $(this).prop("rows")*1;'
						.			'$(this).css("height", lines*lineHeight);'
						.		'})'
						.		'.autogrow();'
						.'});'."\n"
						);

					// the submit button
					$text .= '<div class="menu_bar" style="display: none;" id="submit'.$fuse_id.'">'.Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's').'</div>';

					// end of the form
					$text .= '</div></form>';

				}
			}

			// the main part of the comment, with an id
			$text = '<td class="comment '.$item['type'].'" id="comment_'.$item['id'].'">'.$text.'</td>';

			// this is another row of the output
			$rows[] = '<td class="author '.$item['type'].'">'.$author.'</td>'.$text;

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

		// process yacs codes
		$output = Codes::beautify($output);

		return $output;
	}
}

?>
