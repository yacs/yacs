<?php
/**
 * layout comments as contributions to a thread
 *
 * This layout features definition lists to structure comments, and add
 * style information to distinguish between surfer contributions and others.
 *
 * Post of new comments may have been explicitly prevented in anchor (option '[code]no_comments[/code]').
 * Otherwise commands to post new comments are added if the surfer has been authenticated,
 * or if anonymous comments are allowed (parameter '[code]users_with_anonymous_comments[/code]' set to 'Y'),
 * of if teasers have been enabled (parameter '[code]users_without_teasers[/code]' not set to 'Y').
 * Both global parameters are set in [script]users/configure.php[/script]).
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

		// flag items older than 5 minutes ago
		$threshold = gmstrftime('%Y-%m-%d %H:%M:%S', time() - 300);

		// build a list of comments
		while($item = SQL::fetch($result)) {

			// automatic notification
			if($item['type'] == 'notification')
				$text = '<dd class="thread_other" style="font-style: italic;">'.ucfirst(trim(Codes::beautify($item['description']))).'</dd>'.$text;

			// regular comment
			else {

				// link to user profile -- open links in separate window to enable side browsing of participant profiles
				if($item['create_id']) {
					if($user =& Users::get($item['create_id']) && $user['full_name'])
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
				if(!strncmp($item['edit_date'], $threshold, 10)) {
					if(isset($item['edit_date']) && ($item['edit_date'] < $threshold))
						$stamp = '--&nbsp;'.Skin::build_time($item['edit_date']);

				// flag items from previous days
				} else {
					if(isset($item['edit_date']) && ($item['edit_date'] < $threshold))
						$stamp = '--&nbsp;'.Skin::build_date($item['edit_date']);

				}

				// append this at the end of the comment
				$stamp = ' <span style="font-size: x-small">'.Skin::build_link( Comments::get_url($item['id']), $stamp, 'basic', i18n::s('Edit')).'</span>';

				// package everything --change order to get oldest first
				$text = '<dt'.$style.'>'.$author.'</dt><dd'.$style.'>'.ucfirst(trim(Codes::beautify($item['description']))).$stamp.'</dd>'.$text;
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
