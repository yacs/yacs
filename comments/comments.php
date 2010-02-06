<?php
/**
 * the database abstraction layer for comments
 *
 * @todo add a field to count words in a post
 *
 * Comments are not intended to create complex threading systems.
 * They are more or less to be used as sticky notes aside published pages.
 *
 * At the moment YACS supports following comment types:
 * - attention - it's worth the reading
 * - done - job has been completed
 * - idea - to submit a new suggestion
 * - information - answering a previous request
 * - question - please help
 * - thumbs down - I dislike it
 * - thumbs up - I enjoy this
 * - warning - you should take care
 *
 * @author Bernard Paques
 * @author Florent
 * @author GnapZ
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Comments {

	/**
	 * check if new comments can be added
	 *
	 * This function returns TRUE if comments can be added to some place,
	 * and FALSE otherwise.
	 *
	 * @param object an instance of the Anchor interface, if any
	 * @param array a set of item attributes, if any
	 * @param string the type of item, e.g., 'section'
	 * @return TRUE or FALSE
	 */
	function allow_creation($anchor=NULL, $item=NULL, $variant=NULL) {
		global $context;

		// guess the variant
		if(!$variant) {

			// most frequent case
			if(isset($item['id']))
				$variant = 'article';

			// we have no item, look at anchor type
			elseif(is_object($anchor))
				$variant = $anchor->get_type();

			// sanity check
			else
				return FALSE;
		}

		// only in articles
		if($variant == 'article') {

			// 'no files' option
			if(Articles::has_option('no_comments', $anchor, $item))
				return FALSE;


		// other containers
		} else {

			// comments have to be activated
			if(isset($item['options']) && is_string($item['options']) && preg_match('/\bwith_comments\b/i', $item['options']))
				;
			else if(is_object($anchor) && $anchor->has_option('with_comments', FALSE))
				;
			else
				return FALSE;

		}

		// surfer is an associate
		if(Surfer::is_associate())
			return TRUE;

		// submissions have been disallowed
		if(isset($context['users_without_submission']) && ($context['users_without_submission'] == 'Y'))
			return FALSE;

		// only in articles
		if($variant == 'article') {

			// surfer owns this item, or the anchor
			if(Articles::is_owned($item, $anchor))
				return TRUE;

			// surfer is an editor, and the page is not private
			if(isset($item['active']) && ($item['active'] != 'N') && Articles::is_assigned($item['id']))
				return TRUE;

		// only in sections
		} elseif($variant == 'section') {

			// surfer owns this item, or the anchor
			if(Sections::is_owned($item, $anchor, TRUE))
				return TRUE;

			// surfer is an editor, and the section is not private
			if(isset($item['active']) && ($item['active'] != 'N') && Sections::is_assigned($item['id']))
				return TRUE;

		}

		// surfer is an editor, and container is not private
		if(isset($item['active']) && ($item['active'] != 'N') && is_object($anchor) && $anchor->is_assigned())
			return TRUE;
		if(!isset($item['id']) && is_object($anchor) && !$anchor->is_hidden() && $anchor->is_assigned())
			return TRUE;

		// editors cannot contribute if container has been locked
		if(isset($item['locked']) && ($item['locked'] == 'Y'))
			return FALSE;

		// anchor has been locked --only used when there is no item provided
		if(!$item['id'] && is_object($anchor) && $anchor->has_option('locked'))
			return FALSE;

		// surfer is an editor (and item has not been locked)
		if(($variant == 'article') && isset($item['id']) && Articles::is_assigned($item['id']))
			return TRUE;
		if(($variant == 'section') && isset($item['id']) && Sections::is_assigned($item['id']))
			return TRUE;
		if(is_object($anchor) && $anchor->is_assigned())
			return TRUE;

		// container is hidden
		if(isset($item['active']) && ($item['active'] == 'N'))
			return FALSE;
		if(is_object($anchor) && $anchor->is_hidden())
			return FALSE;

		// surfer is a member
		if(Surfer::is_member())
			return TRUE;

		// container is restricted
		if(isset($item['active']) && ($item['active'] == 'R'))
			return FALSE;
		if(is_object($anchor) && !$anchor->is_public())
			return FALSE;

		// authenticated members and subscribers are allowed to add files
		if(Surfer::is_logged())
			return TRUE;

		// anonymous surfers are allowed to contribute
		if(isset($context['users_with_anonymous_comments']) && ($context['users_with_anonymous_comments'] == 'Y'))
			return TRUE;

		// anonymous contributions are allowed for articles
		if($variant == 'article') {
			if(isset($item['options']) && preg_match('/\banonymous_edit\b/i', $item['options']))
				return TRUE;
			if(is_object($anchor) && $anchor->has_option('anonymous_edit'))
				return TRUE;
		}

		// the default is to not allow for new comments
		return FALSE;
	}

	/**
	 * check if comments can be modified
	 *
	 * This function returns TRUE if comments can be edited to some place,
	 * and FALSE otherwise.
	 *
	 * @param object an instance of the Anchor interface, if any
	 * @param array a set of item attributes, if any
	 * @return TRUE or FALSE
	 */
	function allow_modification($anchor, $item) {
		global $context;

		// associates can do what they want
		if(Surfer::is_associate())
			return TRUE;

		// the item is anchored to the profile of this member
// 		if(Surfer::is_member() && isset($item['anchor']) && !strcmp($item['anchor'], 'user:'.Surfer::get_id()))
// 			return TRUE;

		// you can handle your own comments
		if(isset($item['create_id']) && Surfer::is($item['create_id']) && is_object($anchor) && !$anchor->has_option('locked'))
			return TRUE;

		// owner
		if(is_object($anchor) && $anchor->is_owned())
			return TRUE;

		// editor of a public page
		if(is_object($anchor) && !$anchor->is_hidden() && $anchor->is_assigned())
			return TRUE;

		// the default is to not allow modifications
		return FALSE;
	}

	/**
	 * clear cache entries for one item
	 *
	 * @param array item attributes
	 */
	function clear(&$item) {

		// where this item can be displayed
		$topics = array('articles', 'categories', 'comments', 'sections', 'users');

		// clear anchor page
		if(isset($item['anchor']))
			$topics[] = $item['anchor'];

		// clear this page
		if(isset($item['id']))
			$topics[] = 'comment:'.$item['id'];

		// clear the cache
		Cache::clear($topics);

	}

	/**
	 * count records for one anchor
	 *
	 * @param string the selected anchor (e.g., 'article:12')
	 * @param boolean TRUE if this can be optionnally avoided
	 * @return int the resulting count, or NULL on error
	 */
	function count_for_anchor($anchor, $optional=FALSE) {
		global $context;

		// sanity check
		if(!$anchor)
			return NULL;

		// request the database only in hi-fi mode
		if($optional && ($context['skins_with_details'] != 'Y'))
			return NULL;

		// profiling mode
		if($context['with_profile'] == 'Y')
			logger::profile('comments::count_for_anchor');

		// select among available items
		$query = "SELECT COUNT(id) as count"
			." FROM ".SQL::table_name('comments')." AS comments "
			." WHERE comments.anchor LIKE '".SQL::escape($anchor)."'";

		return SQL::query_scalar($query);
	}

	/**
	 * delete one comment
	 *
	 * @param int the id of the comment to delete
	 * @return boolean TRUE on success, FALSE otherwise
	 *
	 * @see comments/delete.php
	 */
	function delete($id) {
		global $context;

		// id cannot be empty
		if(!$id || !is_numeric($id))
			return FALSE;

		// suppress links to this comment
		$query = "UPDATE ".SQL::table_name('comments')." SET previous_id=0 WHERE previous_id = ".SQL::escape($id);
		if(SQL::query($query) === FALSE)
			return FALSE;

		// delete the record in the database
		$query = "DELETE FROM ".SQL::table_name('comments')." WHERE id = ".SQL::escape($id);
		if(SQL::query($query) === FALSE)
			return FALSE;

		// job done
		return TRUE;
	}

	/**
	 * delete all comments for a given anchor
	 *
	 * @param the anchor to check
	 *
	 * @see shared/anchors.php
	 */
	function delete_for_anchor($anchor) {
		global $context;

		// delete all matching records in the database
		$query = "DELETE FROM ".SQL::table_name('comments')." WHERE anchor LIKE '".SQL::escape($anchor)."'";
		SQL::query($query);
	}

	/**
	 * duplicate all comments for a given anchor
	 *
	 * This function duplicates records in the database, and changes anchors
	 * to attach new records as per second parameter.
	 *
	 * @param string the source anchor
	 * @param string the target anchor
	 * @return int the number of duplicated records
	 *
	 * @see shared/anchors.php
	 */
	function duplicate_for_anchor($anchor_from, $anchor_to) {
		global $context;

		// look for records attached to this anchor
		$count = 0;
		$query = "SELECT * FROM ".SQL::table_name('comments')." WHERE anchor LIKE '".SQL::escape($anchor_from)."'";
		if(($result =& SQL::query($query)) && SQL::count($result)) {

			// the list of transcoded strings
			$transcoded = array();

			// process all matching records one at a time
			while($item =& SQL::fetch($result)) {

				// a new id will be allocated
				$old_id = $item['id'];
				unset($item['id']);

				// target anchor
				$item['anchor'] = $anchor_to;

				// actual duplication
				if($new_id = Comments::post($item)) {

					// more pairs of strings to transcode
					$transcoded[] = array('/\[comment='.preg_quote($old_id, '/').'/i', '[comment='.$new_id);

					// duplicate elements related to this item
					Anchors::duplicate_related_to('comment:'.$old_id, 'comment:'.$new_id);

					// stats
					$count++;
				}
			}

			// transcode in anchor
			if($anchor =& Anchors::get($anchor_to))
				$anchor->transcode($transcoded);

		}

		// number of duplicated records
		return $count;
	}

	/**
	 * get one comment by id
	 *
	 * @param int the id of the comment
	 * @return the resulting $item array, with at least keys: 'id', 'type', 'description', etc.
	 *
	 * @see comments/delete.php
	 * @see comments/edit.php
	 * @see comments/view.php
	 */
	function &get($id) {
		global $context;

		// sanity check
		if(!$id) {
			$output = NULL;
			return $output;
		}

		// select among available items -- exact match
		$query = "SELECT * FROM ".SQL::table_name('comments')." AS comments "
			." WHERE (comments.id = ".SQL::escape($id).")";

		$output =& SQL::query_first($query);
		return $output;
	}

	/**
	* build a small form to post a comment
	*
	* @param string reference to the anchor to attach the comment
	* @param string the place to come back when complete
	* @return string the HTML tags to put in the page
	*/
	function get_form($anchor, $follow_up='comments') {
		global $context;

		$menu = array(Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's'));

		if(Surfer::may_upload())
			$menu[] = '<span class="details">'.sprintf(i18n::s('You may attach a file of up to %sbytes'), $context['file_maximum_size']).' <input type="hidden" name="file_type" value="upload" /><input type="file" name="upload" size="30" /></span>';

		$text = '<form method="post" enctype="multipart/form-data" action="'.$context['url_to_root'].'comments/edit.php" ><div style="margin: 1em 0;">';

		$text .= Surfer::get_editor('description', '', TRUE);

		$text .= '<input type="hidden" name="anchor" value="'.$anchor.'" />'
			.'<input type="hidden" name="follow_up" value="'.$follow_up.'" />'
			.Skin::finalize_list($menu, 'menu_bar')
			.'</div></form>';

		return $text;
	}

	/**
	 * get a <img> element
	 *
	 * @param the type ('suggestion', etc.')
	 * @return a suitable HTML element
	 *
	 * @see comments/layout_comments_as_daily.php
	 * @see comments/layout_comments_as_jive.php
	 * @see comments/layout_comments_as_manual.php
	 * @see comments/layout_comments_as_yabb.php
	 * @see skins/skin_skeleton.php
	 */
	function get_img($type) {
		global $context;
		switch($type) {

		// it's worth the reading
		case 'attention':
		case 'default':
		default:

			// use skin declaration if any
			if(!defined('ATTENTION_IMG')) {

				// else use default image file
				$file = 'skins/_reference/comments/attention.gif';
				if($size = Safe::GetImageSize($context['path_to_root'].$file))
					define('ATTENTION_IMG', '<img src="'.$context['url_to_root'].$file.'" '.$size[3].' alt="" /> ');
				else
					define('ATTENTION_IMG', '');
			}
			return ATTENTION_IMG;

		// job has been completed
		case 'done':

			// use skin declaration if any
			if(!defined('DONE_IMG')) {

				// else use default image file
				$file = 'skins/_reference/comments/done.gif';
				if($size = Safe::GetImageSize($context['path_to_root'].$file))
					define('DONE_IMG', '<img src="'.$context['url_to_root'].$file.'" '.$size[3].' alt="" /> ');
				else
					define('DONE_IMG', '');
			}
			return DONE_IMG;

		// to submit a new suggestion
		case 'idea':
		case 'suggestion':	//-- legacy keyword

			// use skin declaration if any
			if(!defined('IDEA_IMG')) {

				// else use default image file
				$file = 'skins/_reference/comments/idea.gif';
				if($size = Safe::GetImageSize($context['path_to_root'].$file))
					define('IDEA_IMG', '<img src="'.$context['url_to_root'].$file.'" '.$size[3].' alt="" /> ');
				else
					define('IDEA_IMG', '');
			}
			return IDEA_IMG;

		// answering a previous request
		case 'information':

			// use skin declaration if any
			if(!defined('INFORMATION_IMG')) {

				// else use default image file
				$file = 'skins/_reference/comments/information.gif';
				if($size = Safe::GetImageSize($context['path_to_root'].$file))
					define('INFORMATION_IMG', '<img src="'.$context['url_to_root'].$file.'" '.$size[3].' alt="" /> ');
				else
					define('INFORMATION_IMG', '');
			}
			return INFORMATION_IMG;

		// please help
		case 'question':

			// use skin declaration if any
			if(!defined('QUESTION_IMG')) {

				// else use default image file
				$file = 'skins/_reference/comments/question.gif';
				if($size = Safe::GetImageSize($context['path_to_root'].$file))
					define('QUESTION_IMG', '<img src="'.$context['url_to_root'].$file.'" '.$size[3].' alt="" /> ');
				else
					define('QUESTION_IMG', '');
			}
			return QUESTION_IMG;

		// I dislike it
		case 'thumbs_down':
		case 'dislike': 	//-- legacy keyword

			// use skin declaration if any
			if(!defined('THUMBS_DOWN_IMG')) {

				// else use default image file
				$file = 'skins/_reference/comments/thumbs_down.gif';
				if($size = Safe::GetImageSize($context['path_to_root'].$file))
					define('THUMBS_DOWN_IMG', '<img src="'.$context['url_to_root'].$file.'" '.$size[3].' alt="" /> ');
				else
					define('THUMBS_DOWN_IMG', '');
			}
			return THUMBS_DOWN_IMG;

		// I like it
		case 'thumbs_up':
		case 'like':		//-- legacy keyword

			// use skin declaration if any
			if(!defined('THUMBS_UP_IMG')) {

				// else use default image file
				$file = 'skins/_reference/comments/thumbs_up.gif';
				if($size = Safe::GetImageSize($context['path_to_root'].$file))
					define('THUMBS_UP_IMG', '<img src="'.$context['url_to_root'].$file.'" '.$size[3].' alt="" /> ');
				else
					define('THUMBS_UP_IMG', '');
			}
			return THUMBS_UP_IMG;

		// you should take care
		case 'warning':

			// use skin declaration if any
			if(!defined('WARNING_IMG')) {

				// else use default image file
				$file = 'skins/_reference/comments/warning.gif';
				if($size = Safe::GetImageSize($context['path_to_root'].$file))
					define('WARNING_IMG', '<img src="'.$context['url_to_root'].$file.'" '.$size[3].' alt="" /> ');
				else
					define('WARNING_IMG', '');
			}
			return WARNING_IMG;

		}
	}

	/**
	 * get a suitable layout
	 *
	 * @param object anchor that describes the layout that applies
	 * @param array atributes for the current item
	 * @return object an instance of a Layout interface
	 */
	function &get_layout($anchor, $item=NULL) {
		global $context;

		// a wall
		if((is_object($anchor) && $anchor->has_option('comments_as_wall'))) {
			include_once '../comments/layout_comments_as_yabb.php';
			$layout = new Layout_comments_as_yabb();

		} elseif(isset($item['options']) && preg_match('/\bcomments_as_wall\b/', $item['options'])) {
			include_once '../comments/layout_comments_as_yabb.php';
			$layout = new Layout_comments_as_yabb();

		// a manual
		} elseif((is_object($anchor) && $anchor->has_option('comments_as_manual'))) {
			include_once '../comments/layout_comments_as_manual.php';
			$layout = new Layout_comments_as_manual();

		} elseif(isset($item['options']) && preg_match('/\bcomments_as_manual\b/', $item['options'])) {
			include_once '../comments/layout_comments_as_manual.php';
			$layout = new Layout_comments_as_manual();

		// a wiki
		} elseif((is_object($anchor) && $anchor->has_option('view_as_wiki'))) {
			include_once '../comments/layout_comments_as_wiki.php';
			$layout = new Layout_comments_as_wiki();

		} elseif(isset($item['options']) && preg_match('/\bview_as_wiki\b/', $item['options'])) {
			include_once '../comments/layout_comments_as_wiki.php';
			$layout = new Layout_comments_as_wiki();

		// no anchor
		} elseif(!is_object($anchor)) {
			include_once '../comments/layout_comments.php';
			$layout = new Layout_comments();

		// layout is defined in anchor
		} elseif(is_object($anchor) && $anchor->has_layout('daily')) {
			include_once '../comments/layout_comments_as_daily.php';
			$layout = new Layout_comments_as_daily();

		} elseif(is_object($anchor) && $anchor->has_layout('jive')) {
			include_once '../comments/layout_comments_as_jive.php';
			$layout = new Layout_comments_as_jive();

		} elseif(is_object($anchor) && $anchor->has_layout('manual')) {
			include_once '../comments/layout_comments_as_manual.php';
			$layout = new Layout_comments_as_manual();

		} elseif(is_object($anchor) && $anchor->has_layout('yabb')) {
			include_once '../comments/layout_comments_as_yabb.php';
			$layout = new Layout_comments_as_yabb();

		// regular case
		} else {
			include_once '../comments/layout_comments.php';
			$layout = new Layout_comments();
		}

		// job done
		return $layout;
	}

	/**
	 * get last comment in a thread
	 *
	 * @param string anchor reference
	 * @return the resulting $item array, with at least keys: 'id', 'type', 'description', etc.
	 *
	 * @see comments/thread.php
	 */
	function &get_newest_for_anchor($anchor) {
		global $context;

		// sanity check
		if(!$anchor) {
			$output = NULL;
			return $output;
		}
		// select among available items -- exact match
		$query = "SELECT * FROM ".SQL::table_name('comments')." AS comments "
			." WHERE comments.anchor LIKE '".SQL::escape($anchor)."'"
			." ORDER BY comments.create_date DESC LIMIT 1";

		$output =& SQL::query_first($query);
		return $output;
	}

	/**
	 * get id of next comment
	 *
	 * This function is used to build navigation bars.
	 *
	 * @param array the current item
	 * @param string the anchor of the current item
	 * @param string the order, either 'date' or 'reverse'
	 * @return some text
	 *
	 * @see articles/article.php
	 * @see users/user.php
	 */
	function get_next_url($item, $anchor, $order='date') {
		global $context;

		// sanity check
		if(!is_array($item))
			return $item;

		// depending on selected sequence
		if($order == 'date') {
			$match = "comments.create_date > '".SQL::escape($item['create_date'])."'";
			$order = 'comments.create_date';
		} elseif($order == 'reverse') {
			$match = "comments.create_date < '".SQL::escape($item['create_date'])."'";
			$order = 'comments.create_date DESC';
		} else
			return "unknown order '".$order."'";


		// query the database
		$query = "SELECT id FROM ".SQL::table_name('comments')." AS comments "
			." WHERE (comments.anchor LIKE '".SQL::escape($anchor)."') AND (".$match.")"
			." ORDER BY ".$order." LIMIT 0, 1";
		if(!$item =& SQL::query_first($query))
			return NULL;

		// return url of the first item of the list
		return Comments::get_url($item['id']);
	}

	/**
	 * get types as options of a &lt;SELECT&gt; field
	 *
	 * @param string the current type
	 * @return the HTML to insert in the page
	 *
	 * @see comments/edit.php
	 */
	function get_options($type) {
		global $context;

		// a suggestion
		$content .= '<option value="suggestion"';
		if($type == 'suggestion')
			$content .= ' selected';
		$content .='>'.i18n::s('A suggestion')."</option>\n";

		// a question
		$content .= '<option value="question"';
		if($type == 'question')
			$content .= ' selected';
		$content .='>'.i18n::s('A question')."</option>\n";

		// warning
		$content .= '<option value="warning"';
		if($type == 'warning')
			$content .= ' selected';
		$content .='>'.i18n::s('Warning!')."</option>\n";

		// like
		$content .= '<option value="like"';
		if($type == 'like')
			$content .= ' selected';
		$content .='>'.i18n::s('I like...')."</option>\n";

		// dislike
		$content .= '<option value="dislike"';
		if($type == 'dislike')
			$content .= ' selected';
		$content .='>'.i18n::s('I don\'t like...')."</option>\n";

		// default
		$content .= '<option value="information"';
		if($type == 'information')
			$content .= ' selected';
		$content .='>'.i18n::s('My two cents')."</option>\n";

		return $content;
	}

	/**
	 * get id of previous comment
	 *
	 * This function is used to build navigation bars.
	 *
	 * @param array the current item
	 * @param string the anchor of the current item
	 * @param string the order, either 'date' or 'reverse'
	 * @return some text
	 *
	 * @see articles/article.php
	 * @see users/user.php
	 */
	function get_previous_url($item, $anchor, $order='date') {
		global $context;

		// sanity check
		if(!is_array($item))
			return $item;

		// depending on selected sequence
		if($order == 'date') {
			$match = "comments.create_date < '".SQL::escape($item['create_date'])."'";
			$order = 'comments.create_date DESC';
		} elseif($order == 'reverse') {
			$match = "comments.create_date > '".SQL::escape($item['create_date'])."'";
			$order = 'comments.create_date';
		} else
			return "unknown order '".$order."'";

		// query the database
		$query = "SELECT id FROM ".SQL::table_name('comments')." AS comments "
			." WHERE (comments.anchor LIKE '".SQL::escape($anchor)."') AND (".$match.")"
			." ORDER BY ".$order." LIMIT 0, 1";
		if(!$item =& SQL::query_first($query))
			return NULL;

		// return url of the first item of the list
		return Comments::get_url($item['id']);
	}

	/**
	 * get types as radio buttons
	 *
	 * @param string the current type
	 * @return the HTML to insert in the page
	 *
	 * @see comments/edit.php
	 */
	function get_radio_buttons($name, $type) {
		global $context;

		// a 2-column layout
		$content = '<div style="float: left;">'."\n";

		// col 1 - attention - also the default
		$content .= '<input type="radio" name="'.$name.'" value="attention"';
		if(($type == 'attention') || !trim($type))
			$content .= ' checked="checked"';
		$content .='/>'.Comments::get_img('attention').i18n::s('Attention').BR;

		// col 1 - an idea
		$content .= '<input type="radio" name="'.$name.'" value="idea"';
		if(($type == 'idea') || ($type == 'suggestion'))
			$content .= ' checked="checked"';
		$content .='/>'.Comments::get_img('idea').i18n::s('A suggestion').BR;

		// col 1 - a question
		$content .= '<input type="radio" name="'.$name.'" value="question"';
		if($type == 'question')
			$content .= ' checked="checked"';
		$content .='/>'.Comments::get_img('question').i18n::s('A question').BR;

		// col 1 - like
		$content .= '<input type="radio" name="'.$name.'" value="like"';
		if($type == 'like')
			$content .= ' checked="checked"';
		$content .='/>'.Comments::get_img('like').i18n::s('I like...');

		// from column 1 to column 2
		$content .= '</div>'."\n".'<div style="float: left;">';

		// col 2 - warning
		$content .= '<input type="radio" name="'.$name.'" value="warning"';
		if($type == 'warning')
			$content .= ' checked="checked"';
		$content .='/>'.Comments::get_img('warning').i18n::s('Warning!').BR;

		// col 2 - done
		$content .= '<input type="radio" name="'.$name.'" value="done"';
		if($type == 'done')
			$content .= ' checked="checked"';
		$content .='/>'.Comments::get_img('done').i18n::s('Job has been completed').BR;

		// col 2 - information
		$content .= '<input type="radio" name="'.$name.'" value="information"';
		if($type == 'information')
			$content .= ' checked="checked"';
		$content .='/>'.Comments::get_img('information').i18n::s('My two cents').BR;

		// col2 - dislike
		$content .= '<input type="radio" name="'.$name.'" value="dislike"';
		if($type == 'dislike')
			$content .= ' checked="checked"';
		$content .='/>'.Comments::get_img('dislike').i18n::s('I don\'t like...');

		// end of columns
		$content .= '</div>'."\n";

		return $content;
	}

	/**
	 * get a default title from the type selected
	 *
	 * @param the type ('suggestion', etc.')
	 * @return a suitable title
	 */
	function get_title($type) {
		global $context;
		switch($type) {
		case 'suggestion':
			return i18n::s('A suggestion');
		case 'question':
			return i18n::s('A question');
		case 'warning':
			return i18n::s('Warning!');
		case 'like':
			return i18n::s('I like...');
		case 'dislike':
			return i18n::s('I don\'t like...');
		case 'default':
		default:
			return i18n::s('My two cents');
		}
	}

	/**
	 * build a reference to a comment
	 *
	 * The action parameter defines the kind of link you want:
	 * - 'comment' - a form to add a new comment to something - id has to reference an anchor (e.g., 'article:123')
	 * - 'delete' - a form to delete a comment
	 * - 'edit' - a form to edit a comment
	 * - 'feed' - get comments as a feed - id has to reference an anchor (e.g., 'article:123')
	 * - 'list' - list comments attached to something - id has to reference an anchor (e.g., 'article:123')
	 * - 'navigate' - used to build a paging menu for comments - id has to reference an anchor (e.g., 'article:123')
	 * - 'promote' - a form to turn a comment to an article
	 * - 'quote' - use an existing comment in yours
	 * - 'reply' - chain a comment to an existing one
	 * - 'service.comment' - a service to add a new comment to something - id has to reference an anchor (e.g., 'article:123')
	 * - 'thread' - a service to manage threads - id has to reference an anchor (e.g., 'article:123')
	 * - 'view' - a page to zoom on one comment
	 *
	 * Depending on parameter '[code]with_friendly_urls[/code]' and on action,
	 * following results can be observed:
	 *
	 * - view - comments/view.php?id=123 or comments/view.php/123 or comment-123
	 *
	 * - other - comments/edit.php?id=123 or comments/edit.php/123 or comment-edit/123
	 *
	 * @param mixed the id of the comment to handle, or some anchor reference, e.g., 'section:123'
	 * @param string the expected action ('view', 'print', 'edit', 'delete', ...)
	 * @return string a normalized reference
	 *
	 * @see control/configure.php
	 */
	function get_url($id, $action='view') {
		global $context;

		// add a comment -- the id has to be an anchor (e.g., 'article:15')
		if($action == 'comment') {
			if($context['with_friendly_urls'] == 'Y')
				return 'comments/edit.php/'.str_replace(':', '/', $id);
			elseif($context['with_friendly_urls'] == 'R')
				return 'comments/edit.php/'.str_replace(':', '/', $id);
			else
				return 'comments/edit.php?anchor='.urlencode($id);
		}

		// get comments in rss -- the id has to be an anchor (e.g., 'article:15')
		if($action == 'feed') {
			if($context['with_friendly_urls'] == 'Y')
				return 'comments/feed.php/'.str_replace(':', '/', $id);
			elseif($context['with_friendly_urls'] == 'R')
				return 'comments/feed.php/'.str_replace(':', '/', $id);
			else
				return 'comments/feed.php?anchor='.urlencode($id);
		}

		// list comments -- the id has to be an anchor (e.g., 'article:15')
		if($action == 'list') {
			if($context['with_friendly_urls'] == 'Y')
				return 'comments/list.php/'.str_replace(':', '/', $id);
			elseif($context['with_friendly_urls'] == 'R')
				return 'comment-list/'.$id;
			else
				return 'comments/list.php?id='.urlencode($id);
		}

		// navigate comments -- the id has to be an anchor (e.g., 'article:15')
		if($action == 'navigate') {
			if($context['with_friendly_urls'] == 'Y')
				return 'comments/list.php/'.str_replace(':', '/', $id).'/';
			elseif($context['with_friendly_urls'] == 'R')
				return 'comments/list.php/'.str_replace(':', '/', $id).'/';
			else
				return 'comments/list.php?id='.urlencode($id).'&amp;page=';
		}

		// quote an existing comment
		if($action == 'quote') {
			if($context['with_friendly_urls'] == 'Y')
				return 'comments/edit.php/quote/'.rawurlencode($id);
			elseif($context['with_friendly_urls'] == 'R')
				return 'comments/edit.php/quote/'.rawurlencode($id);
			else
				return 'comments/edit.php?quote='.urlencode($id);
		}

		// reply to an existing comment
		if($action == 'reply') {
			if($context['with_friendly_urls'] == 'Y')
				return 'comments/edit.php/reply/'.rawurlencode($id);
			elseif($context['with_friendly_urls'] == 'R')
				return 'comments/edit.php/reply/'.rawurlencode($id);
			else
				return 'comments/edit.php?reply='.urlencode($id);
		}

		// add a comment, the service -- the id has to be an anchor (e.g., 'article:15')
		if($action == 'service.comment') {
			if($context['with_friendly_urls'] == 'Y')
				return 'comments/post.php/'.str_replace(':', '/', $id);
			elseif($context['with_friendly_urls'] == 'R')
				return 'comments/post.php/'.str_replace(':', '/', $id);
			else
				return 'comments/post.php?anchor='.urlencode($id);
		}

		// check the target action
		if(!preg_match('/^(delete|edit|promote|thread|view)$/', $action))
			return 'comments/'.$action.'.php?id='.urlencode($id).'&action='.urlencode($name);

		// normalize the link
		return normalize_url(array('comments', 'comment'), $action, $id);
	}

	/**
	 * list newest comments
	 *
	 * To build a simple box of the newest comments in your main index page, just use
	 * the following example:
	 * [php]
	 * // side bar with the list of most recent comments
	 * include_once 'comments/comments.php';
	 * $title = i18n::s('Most recent comments');
	 * $items = Comments::list_by_date(0, 10);
	 * $text = Skin::build_list($items, 'compact');
	 * $context['text'] .= Skin::build_box($title, $text, 'navigation');
	 * [/php]
	 *
	 * You can also display the newest comment separately, using Comments::get_newest()
	 * In this case, skip the very first comment in the list by using
	 * Comments::list_by_date(1, 10)
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see comments/feed.php
	 */
	function &list_by_date($offset=0, $count=10, $variant='date') {
		global $context;

		// if not associate, restrict to comments at public published not expired pages
		if(!Surfer::is_associate())
			$query = "SELECT comments.* FROM ".SQL::table_name('comments')." AS comments"
				.", ".SQL::table_name('articles')." AS articles"
				." WHERE ((comments.anchor_type LIKE 'article') AND (comments.anchor_id = articles.id))"
				." AND (articles.active='Y')"
				." AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND ((articles.expiry_date is NULL)"
				."	OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".gmstrftime('%Y-%m-%d %H:%M:%S')."'))"
				." ORDER BY comments.create_date DESC LIMIT ".$offset.','.$count;

		// the list of comments
		else
			$query = "SELECT comments.* FROM ".SQL::table_name('comments')." AS comments "
				." ORDER BY comments.create_date DESC LIMIT ".$offset.','.$count;

		$output =& Comments::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list comments by date for one anchor
	 *
	 * If variant is 'compact', the list start with the most recent comments.
	 * Else comments are ordered depending of their edition date.
	 *
	 * Example:
	 * [php]
	 * include_once 'comments/comments.php';
	 * $items = Comments::list_by_date_for_anchor('section:12', 0, 10);
	 * $context['text'] .= Skin::build_list($items, 'compact');
	 * [/php]
	 *
	 * @param int the id of the anchor
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @param boolean TRUE for the wall, FALSE for a forum
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see articles/fetch_as_msword.php
	 * @see articles/fetch_as_pdf.php
	 * @see articles/print.php
	 * @see articles/view.php
	 * @see categories/view.php
	 * @see comments/feed.php
	 * @see sections/view.php
	 */
	function &list_by_date_for_anchor($anchor, $offset=0, $count=20, $variant='no_anchor', $reverse=FALSE) {
		global $context;

		// the wall or a forum
		if($reverse)
			$reverse = 'DESC';
		else
			$reverse = '';

		// the list of comments
		$query = "SELECT * FROM ".SQL::table_name('comments')." AS comments "
			." WHERE comments.anchor LIKE '".SQL::escape($anchor)."'"
			." ORDER BY comments.create_date ".$reverse." LIMIT ".$offset.','.$count;

		$output =& Comments::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list newest comments for one author
	 *
	 * Example:
	 * include_once 'comments/comments.php';
	 * $items = Comments::list_by_date_for_author(12, 0, 10);
	 * $context['text'] .= Skin::build_list($items, 'compact');
	 *
	 * @param int the id of the author of the comment
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 */
	function &list_by_date_for_author($author_id, $offset=0, $count=20, $variant='date') {
		global $context;

		// the list of comments
		$query = "SELECT * FROM ".SQL::table_name('comments')." AS comments "
			." WHERE (comments.create_id = ".SQL::escape($author_id).")"
			." ORDER BY comments.create_date DESC LIMIT ".$offset.','.$count;

		$output =& Comments::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list newest comments for one anchor
	 *
	 * This is a tricky way to get the tail of a thread.
	 * You will have to use a layout that re-order comments properly.
	 *
	 * @param int the id of the anchor
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see comments/thread.php
	 */
	function &list_by_thread_for_anchor($anchor, $offset=0, $count=20, $variant='thread') {
		global $context;

		// the list of comments
		$query = "SELECT * FROM ".SQL::table_name('comments')." AS comments "
			." WHERE comments.anchor LIKE '".SQL::escape($anchor)."'"
			." ORDER BY comments.create_date DESC LIMIT ".$offset.','.$count;

		$output =& Comments::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list next comments in thread
	 *
	 * @param int the id of the main comment
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see comments/view.php
	 */
	function &list_next($id, $variant='date') {
		global $context;

		// the list of comments
		$query = "SELECT comments.* FROM ".SQL::table_name('comments')." AS comments "
			." WHERE previous_id = ".SQL::escape($id)
			." ORDER BY comments.create_date LIMIT 0,50";

		$output =& Comments::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list selected comments
	 *
	 * If variant is provided as a string, the functions looks for a script featuring this name.
	 * E.g., for variant 'compact', the file 'comments/layout_comments_as_compact.php' is loaded.
	 * If no file matches then the default 'comments/layout_comments.php' script is loaded.
	 *
	 * @param resource result of database query
	 * @param string 'full', etc or object, i.e., an instance of Layout_Interface
	 * @return an array of $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_selected(&$result, $variant='compact') {
		global $context;

		// no result
		if(!$result) {
			$output = NULL;
			return $output;
		}

		// use an external layout
		if(is_object($variant)) {
			$output =& $variant->layout($result);
			return $output;
		}

		// no layout yet
		$layout = NULL;

		// separate options from layout name
		$attributes = explode(' ', $variant, 2);

		// instanciate the provided name
		if($attributes[0]) {
			$name = 'layout_comments_as_'.$attributes[0];
			if(is_readable($context['path_to_root'].'comments/'.$name.'.php')) {
				include_once $context['path_to_root'].'comments/'.$name.'.php';
				$layout = new $name;

				// provide parameters to the layout
				if(isset($attributes[1]))
					$layout->set_variant($attributes[1]);

			}
		}

		// use default layout
		if(!$layout) {
			include_once $context['path_to_root'].'comments/layout_comments.php';
			$layout = new Layout_comments();
			$layout->set_variant($variant);
		}

		// do the job
		$output =& $layout->layout($result);
		return $output;

	}

	/**
	 * thread comments by numbers
	 *
	 * Result of this query should be processed with a layout adapted to articles
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see comments/index.php
	 */
	function &list_threads_by_count($offset=0, $count=10, $variant='date') {
		global $context;

		// a dynamic where clause
		$where = '';

		// if not associate, restrict to comments at public published not expired pages
		if(!Surfer::is_associate()) {
			$where = "(articles.active='Y')"
				." AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND ((articles.expiry_date is NULL)"
				."	OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".gmstrftime('%Y-%m-%d %H:%M:%S')."'))";
		}

		// avoid blank records on join
		if($where)
			$where .= ' AND ';
		$where .= '(articles.id > 0)';

		// the list of comments
		$query = "SELECT articles.*, count(comments.id) as comments_count FROM ".SQL::table_name('comments')." AS comments"
			.", ".SQL::table_name('articles')." AS articles"
			." WHERE ((comments.anchor_type LIKE 'article') AND (comments.anchor_id = articles.id))"
			."	AND ".$where
			." GROUP BY articles.id"
			." ORDER BY comments_count DESC, articles.edit_date DESC LIMIT ".$offset.','.$count;

		// return a list of articles
		$output =& Articles::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * thread comments by umbers for given anchor
	 *
	 * Result of this query should be processed with a layout adapted to articles
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see comments/index.php
	 */
	function &list_threads_by_count_for_anchor($anchor, $offset=0, $count=10, $variant='date') {
		global $context;

		// select among active items
		$where = "articles.active='Y'";

		// add restricted items to members, or if teasers are allowed
		if(Surfer::is_logged() || Surfer::is_teased())
			$where .= " OR articles.active='R'";

		// associates, editors and readers may see everything
		if(Surfer::is_empowered('S'))
			$where .= " OR articles.active='N'";

		// a dynamic where clause
		$where = '('.$where.')';

		// current time
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// provide published pages to anonymous surfers
		if(!Surfer::is_logged()) {
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$now."')";

		// logged surfers that are non-associates are restricted to their own articles, plus published articles
		} elseif(!Surfer::is_empowered()) {
			$where .= " AND ((articles.create_id=".Surfer::get_id().") OR (NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$now."')))";
		}

		// only consider live articles for non-associates
		if(!Surfer::is_empowered()) {
			$where .= " AND ((articles.expiry_date is NULL) "
					."OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".$now."'))";
		}

		// if not associate, restrict to comments at public published not expired pages
		if(!Surfer::is_associate()) {
			$where = " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND ((articles.expiry_date is NULL)"
				."	OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".gmstrftime('%Y-%m-%d %H:%M:%S')."'))";
		}

		// avoid blank records on join
		$where .= ' AND (articles.id > 0)';

		// several anchors
		if(is_array($anchor)) {
			$items = array();
			foreach($anchor as $token)
				$items[] = "articles.anchor LIKE '".SQL::escape($token)."'";
			$where_anchor = join(' OR ', $items);
		} else
			$where_anchor = "articles.anchor LIKE '".SQL::escape($anchor)."'";

		// the list of comments
		$query = "SELECT articles.*, count(comments.id) as comments_count FROM ".SQL::table_name('comments')." AS comments"
			.", ".SQL::table_name('articles')." AS articles"
			." WHERE ((comments.anchor_type LIKE 'article') AND (comments.anchor_id = articles.id))"
			."	AND (".$where_anchor.") AND ".$where
			." GROUP BY articles.id"
			." ORDER BY comments_count DESC, articles.edit_date DESC LIMIT ".$offset.','.$count;

		// return a list of articles
		$output =& Articles::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * thread newest comments
	 *
	 * Result of this query should be processed with a layout adapted to articles
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see comments/index.php
	 */
	function &list_threads_by_date($offset=0, $count=10, $variant='date') {
		global $context;

		// a dynamic where clause
		$where = '';

		// if not associate, restrict to comments at public published not expired pages
		if(!Surfer::is_empowered()) {
			$where = "(articles.active='Y')"
				." AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND ((articles.expiry_date is NULL)"
				."	OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".gmstrftime('%Y-%m-%d %H:%M:%S')."'))";
		}

		// avoid blank records on join
		if($where)
			$where .= ' AND ';
		$where .= '(articles.id > 0)';

		// the list of comments
		$query = "SELECT articles.* FROM ".SQL::table_name('comments')." AS comments"
			.", ".SQL::table_name('articles')." AS articles"
			." WHERE ((comments.anchor_type LIKE 'article') AND (comments.anchor_id = articles.id))"
			."	AND ".$where
			." GROUP BY articles.id"
			." ORDER BY articles.edit_date DESC LIMIT ".$offset.','.$count;

		// return a list of articles
		$output =& Articles::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * thread newest comments
	 *
	 * Result of this query should be processed with a layout adapted to articles
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see comments/index.php
	 */
	function &list_threads_by_date_for_anchor($anchor, $offset=0, $count=10, $variant='date') {
		global $context;

		// select among active items
		$where = "articles.active='Y'";

		// add restricted items to members, or if teasers are allowed
		if(Surfer::is_logged() || Surfer::is_teased())
			$where .= " OR articles.active='R'";

		// associates, editors and readers may see everything
		if(Surfer::is_empowered('S'))
			$where .= " OR articles.active='N'";

		// a dynamic where clause
		$where = '('.$where.')';

		// current time
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// provide published pages to anonymous surfers
		if(!Surfer::is_logged()) {
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$now."')";

		// logged surfers that are non-associates are restricted to their own articles, plus published articles
		} elseif(!Surfer::is_empowered()) {
			$where .= " AND ((articles.create_id=".Surfer::get_id().") OR (NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$now."')))";
		}

		// only consider live articles for non-associates
		if(!Surfer::is_empowered()) {
			$where .= " AND ((articles.expiry_date is NULL) "
					."OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".$now."'))";
		}

		// if not associate, restrict to comments at public published not expired pages
		if(!Surfer::is_associate()) {
			$where = " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND ((articles.expiry_date is NULL)"
				."	OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".gmstrftime('%Y-%m-%d %H:%M:%S')."'))";
		}

		// avoid blank records on join
		$where .= ' AND (articles.id > 0)';

		// several anchors
		if(is_array($anchor)) {
			$items = array();
			foreach($anchor as $token)
				$items[] = "articles.anchor LIKE '".SQL::escape($token)."'";
			$where_anchor = join(' OR ', $items);
		} else
			$where_anchor = "articles.anchor LIKE '".SQL::escape($anchor)."'";

		// the list of comments
		$query = "SELECT articles.* FROM ".SQL::table_name('comments')." AS comments"
			.", ".SQL::table_name('articles')." AS articles"
			." WHERE ((comments.anchor_type LIKE 'article') AND (comments.anchor_id = articles.id))"
			."	AND (".$where_anchor.") AND ".$where
			." GROUP BY articles.id"
			." ORDER BY articles.edit_date DESC LIMIT ".$offset.','.$count;

		// return a list of articles
		$output =& Articles::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * post a new comment or an updated comment
	 *
	 * The surfer signature is also appended to the comment, if any.
	 *
	 * This function populates the error context, where applicable.
	 *
	 * @param array an array of fields
	 * @return the id of the new comment, or FALSE on error
	 *
	 * @see agents/messages.php
	 * @see comments/edit.php
	 * @see comments/post.php
	**/
	function post(&$fields) {
		global $context;

		// no comment
		if(!$fields['description']) {
			Logger::error(i18n::s('No comment has been transmitted.'));
			return FALSE;
		}

		// no anchor reference
		if(!$fields['anchor']) {
			Logger::error(i18n::s('No anchor has been found.'));
			return FALSE;
		}

		// get the anchor
		if(!$anchor =& Anchors::get($fields['anchor'])) {
			Logger::error(i18n::s('No anchor has been found.'));
			return FALSE;
		}

		// set default values for this editor
		Surfer::check_default_editor($fields);
		$fields['edit_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');

		// reinforce date formats
		if(!isset($fields['create_date']) || ($fields['create_date'] <= NULL_DATE))
			$fields['create_date'] = $fields['edit_date'];

		// update the existing record
		if(isset($fields['id'])) {

			// id cannot be empty
			if(!isset($fields['id']) || !is_numeric($fields['id'])) {
				Logger::error(i18n::s('No item has the provided id.'));
				return FALSE;
			}

			// update the existing record
			$query = "UPDATE ".SQL::table_name('comments')." SET "
				."type='".SQL::escape(isset($fields['type']) ? $fields['type'] : 'attention')."', "
				."description='".SQL::escape($fields['description'])."'";

			// maybe another anchor
			if($fields['anchor'])
				$query .= ", anchor='".SQL::escape($fields['anchor'])."', "
					."anchor_type=SUBSTRING_INDEX('".SQL::escape($fields['anchor'])."', ':', 1), "
					."anchor_id=SUBSTRING_INDEX('".SQL::escape($fields['anchor'])."', ':', -1)";

			// maybe a silent update
			if(!isset($fields['silent']) || ($fields['silent'] != 'Y')) {
				$query .= ", "
					."edit_name='".SQL::escape($fields['edit_name'])."', "
					."edit_id=".SQL::escape($fields['edit_id']).", "
					."edit_address='".SQL::escape($fields['edit_address'])."', "
					."edit_action='comment:update', "
					."edit_date='".SQL::escape($fields['edit_date'])."'";
			}

			$query .= " WHERE id = ".SQL::escape($fields['id']);

		// insert a new record
		} else {

			$query = "INSERT INTO ".SQL::table_name('comments')." SET "
				."anchor='".SQL::escape($fields['anchor'])."', "
				."anchor_type=SUBSTRING_INDEX('".SQL::escape($fields['anchor'])."', ':', 1), "
				."anchor_id=SUBSTRING_INDEX('".SQL::escape($fields['anchor'])."', ':', -1), "
				."previous_id='".SQL::escape(isset($fields['previous_id']) ? $fields['previous_id'] : 0)."', "
				."type='".SQL::escape(isset($fields['type']) ? $fields['type'] : 'attention')."', "
				."description='".SQL::escape($fields['description'])."', "
				."create_name='".SQL::escape($fields['edit_name'])."', "
				."create_id=".SQL::escape($fields['edit_id']).", "
				."create_address='".SQL::escape($fields['edit_address'])."', "
				."create_date='".SQL::escape($fields['create_date'])."', "
				."edit_name='".SQL::escape($fields['edit_name'])."', "
				."edit_id=".SQL::escape($fields['edit_id']).", "
				."edit_address='".SQL::escape($fields['edit_address'])."', "
				."edit_action='comment:create', "
				."edit_date='".SQL::escape($fields['edit_date'])."'";

		}

		// actual update query
		if(SQL::query($query) === FALSE)
			return FALSE;

		// remember the id of the new item
		if(!isset($fields['id']))
			$fields['id'] = SQL::get_last_id($context['connection']);

		// clear the cache for comments
		Comments::clear($fields);

		// end of job
		return $fields['id'];
	}

	/**
	 * wait for updates
	 *
	 * This script will wait for new updates before providing them to caller.
	 * Because of potential time-outs, you have to care of retries.
	 *
	 * @param string reference to thread (e.g., 'article:123')
	 * @param string timestamp of previous update
	 * @return array attributes including new comments and a timestamp
	 *
	 * @see articles/view_as_chat.php
	 * @see comments/thread.php
	 */
	function &pull($anchor, $stamp, $count=100) {
		global $context;

		$timer = 1;

		// some implementations will kill network connections earlier anyway
		Safe::set_time_limit(max(30, $timer));

		// we return formatted text
		$text = '';

		// sanity check
		if(!$anchor)
			return $text;

		// the query to get time of last update
		$query = "SELECT edit_date, edit_name FROM ".SQL::table_name('comments')." AS comments "
			." WHERE comments.anchor LIKE '".SQL::escape($anchor)."'"
			." ORDER BY comments.edit_date DESC"
			." LIMIT 1";

		// we may timeout ourself, to be safe with network resources
		while((!$stat =& SQL::query_first($query)) || (isset($stat['edit_date']) && ($stat['edit_date'] <= $stamp))) {

			// kill the request to avoid repeated transmissions when nothing has changed
			if(--$timer < 1) {
				header('Status: 504 Gateway Timeout', TRUE, 504);
				die();
			}

			// preserve server resources
			sleep(1);
		}

		// return an array of variables
		$response = array();
		$response['items'] =& Comments::list_by_thread_for_anchor($anchor, 0, $count, 'thread');
		$response['name'] = strip_tags($stat['edit_name']);
		$response['timestamp'] = SQL::strtotime($stat['edit_date']);

		// return by reference
		return $response;

	}

	/**
	 * limit the size of a thread
	 *
	 * This function deletes oldest comments of a thread.
	 *
	 * The default value of 2000 means 100 pages of comments in a yabb thread.
	 *
	 * @param string anchor of the thread to check (e.g., 'article:123')
	 * @param int the maximum number of comments to keep in the database
	 * @return void
	 *
	 * @see comments/thread.php
	 */
	function purge_for_anchor($anchor, $limit=2000) {
		global $context;

		// lists oldest entries beyond the limit
		$query = "SELECT comments.id FROM ".SQL::table_name('comments')." AS comments "
			." WHERE (comments.anchor LIKE '".SQL::escape($anchor)."')"
			." ORDER BY comments.edit_date DESC LIMIT ".$limit.', 100';

		// no result
		if(!$result =& SQL::query($query))
			return;

		// empty list
		if(!SQL::count($result))
			return;

		// build an array of links
		$ids = array();
		while($item =& SQL::fetch($result))
			$ids[] = "(id = ".SQL::escape($item['id']).")";

		// delete the record in the database
		$query = "DELETE FROM ".SQL::table_name('comments')." WHERE ".implode(' OR ', $ids);
		SQL::query($query);

		// end of processing
		SQL::free($result);

	}

	/**
	 * search for some keywords in all comments
	 *
	 * @param the search string
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see search.php
	 * @see services/search.php
	 */
	function &search($pattern, $offset=0, $count=30, $variant='search') {
		global $context;

		// sanity check
		if(!$pattern = trim($pattern)) {
			$output = NULL;
			return $output;
		}

		// match
		$match = '';
		$words = preg_split('/\s/', $pattern);
		while($word = each($words)) {
			if($match)
				$match .= ' AND ';
			$match .=  "MATCH(description) AGAINST('".SQL::escape($word['value'])."')";
		}

		// the list of comments
		$query = "SELECT * FROM ".SQL::table_name('comments')." AS comments "
			." WHERE ".$match
			." ORDER BY comments.edit_date DESC"
			." LIMIT ".$offset.','.$count;

		$output =& Comments::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * create tables for comments
	 *
	 * @see control/setup.php
	 */
	function setup() {
		global $context;

		$fields = array();
		$fields['id']			= "MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT";
		$fields['anchor']		= "VARCHAR(64) DEFAULT 'section:1' NOT NULL";
		$fields['anchor_type']	= "VARCHAR(64) DEFAULT 'section' NOT NULL";
		$fields['anchor_id']	= "MEDIUMINT UNSIGNED NOT NULL";
		$fields['previous_id']	= "MEDIUMINT UNSIGNED DEFAULT 0 ";
		$fields['type'] 		= "VARCHAR(64) DEFAULT 'default' NOT NULL";
		$fields['description']	= "TEXT NOT NULL";
		$fields['create_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['create_id']	= "MEDIUMINT DEFAULT 0 NOT NULL";
		$fields['create_address']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['create_date']	= "DATETIME";
		$fields['edit_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_id']		= "MEDIUMINT DEFAULT 0 NOT NULL";
		$fields['edit_address'] = "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_action']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_date']	= "DATETIME";

		$indexes = array();
		$indexes['PRIMARY KEY'] 	= "(id)";
		$indexes['INDEX anchor']	= "(anchor)";
		$indexes['INDEX anchor_id'] = "(anchor_id)";
		$indexes['INDEX anchor_type']	= "(anchor_type)";
		$indexes['INDEX create_date'] = "(create_date)";
		$indexes['INDEX create_id'] = "(create_id)";
		$indexes['INDEX edit_date'] = "(edit_date)";
		$indexes['INDEX edit_id']	= "(edit_id)";
		$indexes['INDEX previous_id']	= "(previous_id)";
		$indexes['INDEX type']		= "(type)";
		$indexes['FULLTEXT INDEX']	= "full_text(description)";

		return SQL::setup_table('comments', $fields, $indexes);
	}

	/**
	 * get some statistics
	 *
	 * @return the resulting ($count, $min_date, $max_date) array
	 *
	 * @see comments/index.php
	 */
	function &stat() {
		global $context;

		// if not associate, restrict to comments at public published not expired pages
		if(!Surfer::is_associate())
			$query = "SELECT COUNT(*) as count, MIN(comments.create_date) as oldest_date, MAX(comments.create_date) as newest_date FROM ".SQL::table_name('articles')." AS articles"
				." LEFT JOIN ".SQL::table_name('comments')." AS comments"
				."	ON ((comments.anchor_type LIKE 'article') AND (comments.anchor_id = articles.id))"
				." WHERE (articles.active='Y')"
				." AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND ((articles.expiry_date is NULL)"
				."	OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".gmstrftime('%Y-%m-%d %H:%M:%S')."'))";

		// the list of comments
		else
			$query = "SELECT COUNT(*) as count, MIN(comments.create_date) as oldest_date, MAX(comments.create_date) as newest_date FROM ".SQL::table_name('comments')." AS comments ";

		// select among available items
		$output =& SQL::query_first($query);
		return $output;
	}

	/**
	 * get some statistics for one anchor
	 *
	 * @param the selected anchor (e.g., 'article:12')
	 * @return the resulting ($count, $min_date, $max_date) array
	 *
	 * @see articles/delete.php
	 * @see articles/layout_articles_as_yabb.php
	 * @see articles/layout_articles_as_jive.php
	 * @see articles/view.php
	 * @see categories/delete.php
	 * @see categories/view.php
	 * @see sections/delete.php
	 * @see sections/sections.php
	 * @see sections/view.php
	 * @see skins/layout_home_articles_as_alistapart.php
	 * @see skins/layout_home_articles_as_daily.php
	 * @see skins/layout_home_articles_as_newspaper.php
	 * @see skins/layout_home_articles_as_slashdot.php
	 * @see skins/skin_skeleton.php
	 * @see users/delete.php
	 */
	function &stat_for_anchor($anchor) {
		global $context;

		// profiling mode
		if($context['with_profile'] == 'Y')
			logger::profile('comments::stat_for_anchor');

		// sanity check
		if(!$anchor)
			return NULL;

		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(create_date) as oldest_date, MAX(create_date) as newest_date"
			." FROM ".SQL::table_name('comments')." AS comments "
			." WHERE comments.anchor LIKE '".SQL::escape($anchor)."'";

		$output =& SQL::query_first($query);
		return $output;
	}

	/**
	 * get some statistics on threads
	 *
	 * @return the resulting ($count, $min_date, $max_date) array
	 *
	 * @see comments/index.php
	 */
	function &stat_threads() {
		global $context;

		// a dynamic where clause
		$where = '';

		// if not associate, restrict to comments at public published not expired pages
		if(!Surfer::is_associate()) {
			$where = "(articles.active='Y')"
				." AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND ((articles.expiry_date is NULL)"
				."	OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".gmstrftime('%Y-%m-%d %H:%M:%S')."'))";
		}

		// avoid blank records on join
		if($where)
			$where .= ' AND ';
		$where .= '(articles.id > 0)';

		// the list of comments
		$query = "SELECT DISTINCT articles.id as id FROM ".SQL::table_name('comments')." AS comments"
			.", ".SQL::table_name('articles')." AS articles"
			." WHERE (comments.anchor_type LIKE 'article') AND (comments.anchor_id = articles.id)"
			."	AND ".$where;

		// select among available items
		$output = SQL::count(SQL::query($query));
		return $output;
	}

}

// load localized strings
if(is_callable(array('i18n', 'bind')))
	i18n::bind('comments');

?>