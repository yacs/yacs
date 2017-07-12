<?php
/**
 * enhance threads of discussion
 *
 * @see overlays/overlay.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Thread extends Overlay {

	/**
	 * list participants
	 *
	 * @see overlays/overlay.php
	 *
	 * @param array the hosting record
	 * @return some HTML to be inserted into the resulting page
	 */
	function get_list_text($host=NULL) {
		global $context;

		// we return some text
		$text = '';

		$to_avoid = NULL;
		if($id = Surfer::get_id())
			$to_avoid = 'user:'.$id;

		// page editors, except target surfer
		if($friends = Members::list_users_by_posts_for_member('article:'.$host['id'], 0, USERS_LIST_SIZE, 'comma', $to_avoid))
			$text = '<p '.tag::_class('details').'>'.sprintf(i18n::s('with %s'), Skin::build_list($friends, 'comma')).'</p>';

		return $text;

	}

	/**
	 * we are almost invisible in the main panel
	 *
	 * @see overlays/overlay.php
	 *
	 * @param array the hosting record
	 * @return some HTML to be inserted into the resulting page
	 */
	function get_view_text($host=NULL) {
		global $context;

		$text = '';
		return $text;

	}
        
        function get_label($name, $action='view') {
            if($name == 'permalink_command') {
                $label = i18n::c('View the message');
                return $label;
            } else	
                return NULL;
        }
    
        public function get_comment_notification($item) {
            global $context;

            // build a tease notification for simple members

            // sanity check
            if(!isset($item['anchor']) || (!$anchor = Anchors::get($item['anchor'])))
                    throw new Exception('no anchor for this comment');

            // headline
            $headline   = sprintf(i18n::c('%s has replied'), Surfer::get_link());
            $content    = BR;

            // shape these
            $tease = Skin::build_mail_content($headline, $content);

            // a set of links
            $menu = array();
            // call for action
            $link = $context['url_to_home'].$context['url_to_root'].Comments::get_url($item['id'], 'view');
            $menu[] = Skin::build_mail_button($link, i18n::c('View the reply'), TRUE);

            // link to the container
            $menu[] = Skin::build_mail_button($anchor->get_url(), $anchor->get_title(), FALSE);

            // finalize links
            $tease .= Skin::build_mail_menu($menu);

            // assemble all parts of the mail
            $mail = array();
            $mail['subject']        = sprintf(i18n::c('%s: %s'), i18n::c('Reply in the discussion'), strip_tags($anchor->get_title()));
            $mail['notification']   = Comments::build_notification($item); // full notification
            $mail['tease']          = Mailer::build_notification($tease, 1);

            return $mail;
        }

}

?>