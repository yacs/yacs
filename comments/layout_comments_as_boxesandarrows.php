<?php
/**
 * layout comments as boxesandarrows do
 *
 * A very straightforward layout based on paragraphs ('&lt;p&gt;') for post content and
 * div ('&lt;div&gt;') for post attributes.
 *
 * Post of new comments may have been explicitly prevented in anchor (option '[code]no_comments[/code]').
 * Otherwise commands to post new comments are added if the surfer has been authenticated,
 * or if anonymous comments are allowed (parameter '[code]users_with_anonymous_comments[/code]' set to 'Y'),
 * of if teasers have been enabled (parameter '[code]users_without_teasers[/code]' not set to 'Y').
 * Both global parameters are set in [script]users/configure.php[/script]).
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 *
 * @see articles/view.php
 * @see control/configure.php
 */
Class Layout_comments_as_boxesandarrows extends Layout_interface {

	/**
	 * list comments as boxesandarrows do
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	 *
	 * @see skins/layout.php
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
		$text = '';
		$index = 0;
		include_once $context['path_to_root'].'comments/comments.php';
		while($item =& SQL::fetch($result)) {

			// get the anchor
			$anchor =& Anchors::get($item['anchor']);

			// get an icon for this comment
			$icon = Comments::get_img($item['type']);

			// link to comment permalink
			$text .= '<p id="comment_'.$item['id'].'">'.Skin::build_link(Comments::get_url($item['id']), $icon, 'basic', i18n::s('View this comment'));

			// flag new comments
			if($item['create_date'] >= $dead_line)
				$text .= NEW_FLAG;

			// the comment itself
			$text .= ucfirst(trim(Codes::beautify($item['description'])))."</p>\n";

			// odd or even
			$index++;
			if($index%2)
				$class = 'odd';
			else
				$class = 'even';

			// details
			$text .= '<div class="'.$class.' comment">';

			// a link to the user profile
			if(isset($item['create_name']))
				$text .= sprintf(i18n::s('posted by %s %s'), $item['create_name'], Skin::build_date($item['create_date']));

			// the creation date
			$text .= ' '.Skin::build_date($item['create_date']);

			// comment has been modified
			if($item['create_name'] && ($item['edit_name'] != $item['create_name'])) {
				$text .= ', '.sprintf(i18n::s('modified by %s'), $item['edit_name']);
			}

			// commands to handle this comment
			$menu = array();

			// the reply and quote commands are offered, providing new comments are allowed
			if(Comments::allow_creation($anchor)) {

				Skin::define_img('COMMENTS_ADD_IMG', 'comments/add.gif');
				$menu = array_merge($menu, array( Comments::get_url($item['id'], 'reply') => COMMENTS_ADD_IMG.i18n::s('reply') ));

				Skin::define_img('COMMENTS_QUOTE_IMG', 'comments/quote.gif');
				$menu = array_merge($menu, array( Comments::get_url($item['id'], 'quote') => COMMENTS_QUOTE_IMG.i18n::s('quote') ));
			}

			// the menu bar for associates and poster
			if(Comments::allow_modification($anchor, $item)) {

				Skin::define_img('COMMENTS_EDIT_IMG', 'comments/edit.gif');
				$menu = array_merge($menu, array( Comments::get_url($item['id'], 'edit') => COMMENTS_EDIT_IMG.i18n::s('edit') ));

				Skin::define_img('COMMENTS_DELETE_IMG', 'comments/delete.gif');
				$menu = array_merge($menu, array( Comments::get_url($item['id'], 'delete') => COMMENTS_DELETE_IMG.i18n::s('delete') ));
			}

			if(@count($menu))
				$text .= ' '.Skin::build_list($menu, 'menu');

			// end of details
			$text .= "</div><p>&nbsp;</p>\n";
		}

		// end of processing
		SQL::free($result);
		return $text;
	}
}

?>