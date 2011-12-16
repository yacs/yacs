<?php
/**
 * layout comments as immutable text
 *
 * @see comments/comments.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_comments_as_excerpt extends Layout_interface {

	/**
	 * list comments
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	 *
	 * @see skins/layout.php
	**/
	function &layout(&$result) {
		global $context;

		// we return some text
		$text = '';

		// empty list
		if(!SQL::count($result))
			return $text;

		// process all items in the list
		include_once $context['path_to_root'].'comments/comments.php';
		while($item = SQL::fetch($result)) {

			// automatic notification
			if($item['type'] == 'notification')
				$text .= '<dd style="font-style: italic; font-size: smaller;">'.ucfirst(trim(Codes::beautify($item['description'])))
					.' <span class="details">'.Skin::build_date($item['create_date']).'</span></dd>';

			// regular comment
			else {

				// the title as the label
				if($item['create_name'])
					$label = ucfirst($item['create_name']);
				else
					$label = i18n::s('anonymous');

				// expand a definition list
				$text .= '<dt>'.$label.'</dt>'
					.'<dd>'.Codes::beautify($item['description'])
						.' <span class="details">'.Skin::build_date($item['create_date']).'</span></dd>'."\n";

			}
		}

		// finalize the definition list
		if($text)
			$text = '<dl class="comments">'.$text.'</dl>';

		// end of processing
		SQL::free($result);
		return $text;
	}

}

?>
