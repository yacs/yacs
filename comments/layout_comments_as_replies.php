<?php
/**
 * layout comments as replies to a main comment
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_comments_as_replies extends Layout_interface {

	/**
	 * list comments as successive reader notes
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

		// return some formatted text
		$text = '<dl class="wiki_comments">';

		// build a list of comments
		$index = 0;
		include_once $context['path_to_root'].'comments/comments.php';
		while($item = SQL::fetch($result)) {

			// odd or even
			$index++;
			if($index%2)
				$class = 'odd';
			else
				$class = 'even';

			// get the anchor
			$anchor = Anchors::get($item['anchor']);

			// include a link to comment permalink
			$text .= '<dt class="'.$class.' details">';

			// a link to the user profile
			$text .= Users::get_link($item['create_name'], $item['create_address'], $item['create_id']);

			$menu = array();

			// the creation date
			$label = Skin::build_date($item['create_date']);

			// flag new comments
			if($item['create_date'] >= $context['fresh'])
				$label .= NEW_FLAG;

			$menu[] = $label;

			// the menu bar for associates and poster
			if(Comments::allow_modification($anchor, $item)) {
				$menu[] = Skin::build_link(Comments::get_url($item['id'], 'edit'), i18n::s('edit'), 'basic');
				$menu[] = Skin::build_link(Comments::get_url($item['id'], 'delete'), i18n::s('delete'), 'basic');
			}

			$text .= ' - '.Skin::finalize_list($menu, 'menu');

			$text .= '</dt>';

			// each comment has an id
			$text .= '<dd class="'.$class.'" id="comment_'.$item['id'].'">';

			// the comment itself
			$text .= ucfirst(trim($item['description'].Users::get_signature($item['create_id'])));

			// comment has been modified
			if($item['create_name'] && ($item['edit_name'] != $item['create_name']))
				$text .= BR.'<span class="details">('.sprintf(i18n::s('modified by %s'), $item['edit_name']).')</span>';

			// end of this note
			$text .= '</dd>';

		}

		// end of the list
		$text .= '</dl>';

		// process yacs codes
		$text = Codes::beautify($text);

		// end of processing
		SQL::free($result);
		return $text;
	}
}

?>
