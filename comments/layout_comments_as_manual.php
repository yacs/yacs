<?php
/**
 * layout comments as notes attached to pages of an electronic book
 *
 * Comments are listed as successive reader notes. At the beginning and at the end of the list
 * links are available to add new comments.
 *
 * The layout features a definition list of style class [code]manual_comments[/code],
 * and uses [code]odd[/code] and [code]even[/code] style classes for rendering options of main components.
 *
 * Each note is layered as follows:
 *
 * [snippet]
 * <dt>## <- link to the note, followed by poster and date, then by change menu</dt>
 * <dd>note main text goes here
 * other details go here, if any</dd>
 * [/snippet]
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 *
 * @see articles/view.php
 * @see control/configure.php
 */
Class Layout_comments_as_manual extends Layout_interface {

	/**
	 * list comments as successive reader notes
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

		// return some formatted text
		$text = '<dl class="manual_comments">';

		// build a list of comments
		$index = 0;
		include_once $context['path_to_root'].'comments/comments.php';
		while($item =& SQL::fetch($result)) {

			// odd or even
			$index++;
			if($index%2)
				$class = 'odd';
			else
				$class = 'even';

			// get the anchor
			$anchor =& Anchors::get($item['anchor']);

			// include a link to comment permalink
			$text .= '<dt class="'.$class.' details">';

			// a link to the user profile
			$text .= Users::get_link($item['create_name'], $item['create_address'], $item['create_id']);

			// the creation date
			$text .= ' '.Skin::build_date($item['create_date']);

			// flag new comments
			if($item['create_date'] >= $context['fresh'])
				$text .= NEW_FLAG;

			// include a link to comment permalink
			$text .= ' - '.Skin::build_link(Comments::get_url($item['id']), '#', 'basic', i18n::s('Zoom on this note'));

			// the menu bar for associates and poster
			if(Comments::allow_modification($anchor, $item)) {
				$menu = array( Comments::get_url($item['id'], 'edit') => i18n::s('edit'),
					Comments::get_url($item['id'], 'delete') => i18n::s('delete') );
				$text .= ' - '.Skin::build_list($menu, 'menu');
			}

			$text .= '</dt>';

			// each comment has an id
			$text .= '<dd class="'.$class.'" id="comment_'.$item['id'].'">';

			// the comment itself
			$text .= ucfirst(trim(Codes::beautify($item['description'].Users::get_signature($item['create_id']))));

			// comment has been modified
			if($item['create_name'] && ($item['edit_name'] != $item['create_name']))
				$text .= BR.'<span class="details">('.sprintf(i18n::s('modified by %s'), $item['edit_name']).')</span>';

			// end of this note
			$text .= '</dd>';

		}

		// end of the list
		$text .= '</dl>';

		// end of processing
		SQL::free($result);
		return $text;
	}
}

?>