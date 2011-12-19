<?php
/**
 * layout comments as a daily weblog
 *
 * Post of new comments may have been explicitly prevented in anchor (option '[code]no_comments[/code]').
 * Otherwise commands to post new comments are added if the surfer has been authenticated,
 * or if anonymous comments are allowed (parameter '[code]users_with_anonymous_comments[/code]' set to 'Y'),
 * of if teasers have been enabled (parameter '[code]users_without_teasers[/code]' not set to 'Y').
 * Both global parameters are set in [script]users/configure.php[/script]).
 *
 * @link http://joi.ito.com/archives/2003/12/30/css_in_rss_feed.html
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 *
 * @see articles/view.php
 * @see control/configure.php
 */
Class Layout_comments_as_daily extends Layout_interface {

	/**
	 * the preferred number of items for this layout
	 *
	 * @return int the optimised count of items for this layout
	 *
	 * @see skins/layout.php
	 */
	function items_per_page() {
		return 20;
	}

	/**
	 * list comments as notes in a discussion thread
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	 *
	 * @see skins/layout.php
	**/
	function layout($result) {
		global $context;

		// empty list
		if(!SQL::count($result)) {
			$output = array();
			return $output;
		}

		// header
		$text = '';

		// build a list of comments
		include_once $context['path_to_root'].'comments/comments.php';
		if(isset($this->offset))
			$count = $this->offset;
		else
			$count = 0;
		while($item = SQL::fetch($result)) {

			// get the anchor
			$anchor =& Anchors::get($item['anchor']);

			// odd or even
			if($count%2)
				$class = 'even';
			else
				$class = 'odd';

			// flag comments from anchor author
			if(isset($this->layout_variant) && ($this->layout_variant == 'user:'.$item['create_id']))
				$class .= ' follow_up';

			// name this comment
			$text .= '<div id="comment_'.$item['id'].'" class="'.$class.' comment">'."\n".'<h4>';

			// comment #
			$count++;
			$text .= $count.'- ';

			// a link to the user profile
			$text .= Users::get_link($item['create_name'], $item['create_address'], $item['create_id']);

			// the icon is a link to comment permalink
			$text .= ' '.Skin::build_link(Comments::get_url($item['id']), Comments::get_img($item['type']), 'basic', i18n::s('View this comment')).' ';

			// flag new comments
			if($item['create_date'] >= $context['fresh'])
				$text .= NEW_FLAG;

			// the creation date is a link to the permanent link for this comment
			$text .= Skin::build_link( Comments::get_url($item['id']), Skin::build_date($item['create_date']));

			// comment has been modified
			if($item['create_name'] && ($item['edit_name'] != $item['create_name']))
				$text .= ', '.sprintf(i18n::s('modified by %s'), $item['edit_name']);

			// commands to handle this comment
			$menu = array();

			// the reply and quote commands are offered when new comments are allowed
			if(Comments::allow_creation($anchor)) {
				$menu = array_merge($menu, array( Comments::get_url($item['id'], 'reply') => i18n::s('Reply') ));
				$menu = array_merge($menu, array( Comments::get_url($item['id'], 'quote') => i18n::s('Quote') ));
			}

			// the menu bar for associates and poster
			if(Comments::allow_modification($anchor, $item)) {
				$menu = array_merge($menu, array( Comments::get_url($item['id'], 'edit') => i18n::s('Edit') ));
				$menu = array_merge($menu, array( Comments::get_url($item['id'], 'delete') => i18n::s('Delete') ));
			}

			if($menu)
				$text .= ' -&nbsp;'.Skin::build_list($menu, 'menu');

			// the note itself
			$text .= '</h4>';

			// the comment itself
			$text .= Skin::build_block($item['description'].Users::get_signature($item['create_id']), 'description');

			// end of this note
			$text .= '</div>'."\n";

		}

		// end of processing
		SQL::free($result);
		return $text;
	}

	/**
	 * number of the first comment to display
	 */
	var $offset;

	function set_offset($offset) {
		$this->offset = $offset;
	}
}

?>