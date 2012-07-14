<?php
/**
 * layout comments as contributions to a thread
 *
 * This layout features definition lists to structure comments, and add
 * style information to distinguish between surfer contributions and others.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 *
 * @see articles/view.php
 * @see control/configure.php
 */
Class Layout_comments_as_thread extends Layout_interface {

	/**
	 * list comments as successive notes in a thread
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	**/
	function layout($result) {
		global $context;

		// we return formatted text
		$text = '';

		// empty list
		if(!SQL::count($result))
			return $text;

		// build a list of comments
		while($item = SQL::fetch($result)) {

			// automatic notification
			if($item['type'] == 'notification')
				$text = '<dd class="thread_other" style="font-style: italic;">'.ucfirst(trim(Codes::beautify($item['description']))).'</dd>'.$text;

			// regular comment
			else {

				// link to user profile -- open links in separate window to enable side browsing of participant profiles
				if($item['create_id']) {
					if($user = Users::get($item['create_id']) && $user['full_name'])
						$hover = $user['full_name'];
					else
						$hover = NULL;
					$author = Users::get_link($item['create_name'], $item['create_address'], $item['create_id'], TRUE, $hover);
				} else
					$author = Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id'], TRUE);

				// differentiate my posts from others
				if(Surfer::get_id() && ($item['create_id'] == Surfer::get_id()))
					$style = ' class="thread_me"';
				else
					$style = ' class="thread_other"';

				// a clickable label
				$stamp = '#';

				// flag old items on same day
				if(!strncmp($item['edit_date'], gmstrftime('%Y-%m-%d %H:%M:%S', time()), 10))
					$stamp = Skin::build_time($item['edit_date']);

				// flag items from previous days
				else
					$stamp = Skin::build_date($item['edit_date']);

				// append this at the end of the comment
				$stamp = ' <div style="float: right; font-size: x-small">'.Skin::build_link( Comments::get_url($item['id']), $stamp, 'basic', i18n::s('Edit')).'</div>';

				// package everything --change order to get oldest first
				$text = '<dt'.$style.'>'.$author.'</dt><dd'.$style.'>'.$stamp.ucfirst(trim(Codes::beautify($item['description']))).'</dd>'.$text;
			}
		}

		// end of processing
		SQL::free($result);

		// finalize the returned definition list
		if($text)
			$text = '<dl>'.$text.'</dl>';
		return $text;
	}
}

?>
