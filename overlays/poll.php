<?php
/**
 * describe one poll
 *
 * Normally, a poll appears at the front page only to let people vote.
 *
 * However, you can select to also display raw results by adding the keyword
 * '[code]poll_with_results[/code]' in the field for options of the containing article.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Timster
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Poll extends Overlay {

	/**
	 * build the list of fields for one overlay
	 *
	 * @see overlays/overlay.php
	 *
	 * @param the hosting attributes
	 * @return a list of ($label, $input, $hint)
	 */
	function get_fields($host,$field_pos=NULL) {
		global $context;

		// a placeholder for new answers
		if(!isset($this->attributes['answers']) || !is_array($this->attributes['answers'])) {
			$this->attributes['answers'] = array();
			$this->attributes['answers'][] = array(i18n::s('Answer 1'), 0);
			$this->attributes['answers'][] = array(i18n::s('Answer 2'), 0);
			$this->attributes['answers'][] = array(i18n::s('Answer 3'), 0);
		}

		// list existing answers
		if(is_array($this->attributes['answers'])) {
			foreach($this->attributes['answers'] as $answer) {
				list($text, $count) = $answer;
				$label = i18n::s('Answer');
				$input = '<input type="text" name="answer_texts[]" size="55" value="'.encode_field($text).'" maxlength="64" />';
				$hint = i18n::s('Delete to suppress');
				$fields[] = array($label, $input, $hint);
				$label = i18n::s('Count');
				$input = '<input type="text" name="answer_counts[]" size="10" value="'.encode_field($count).'" maxlength="64" />';
				$fields[] = array($label, $input);
			}
		}

		// append one answer
		$label = i18n::s('Add an answer');
		$input = '<input type="text" name="answer_texts[]" size="55" maxlength="64" />';
		$hint = i18n::s('Check this is a valid answer to your question');
		$fields[] = array($label, $input, $hint);
		$label = i18n::s('Count');
		$input = '<input type="text" name="answer_counts[]" size="10" value="0" maxlength="64" />';
		$hint = i18n::s('Do not trick your polls; Start at zero');
		$fields[] = array($label, $input, $hint);

		return $fields;
	}

	/**
	 * get an overlaid label
	 *
	 * Accepted action codes:
	 * - 'edit' modification of an existing object
	 * - 'delete' deletion form
	 * - 'new' creation of a new object
	 * - 'view' rendering of the object
	 *
	 * @see overlays/overlay.php
	 *
	 * @param string the target label
	 * @param string the on-going action
	 * @return the label to use
	 */
	function get_label($label, $action='view') {
		global $context;

		switch($label) {

		// description label
		case 'description':
			return i18n::s('Additional information');

		case 'edit_command':
			return i18n::s('Edit this poll');

		// help panel
		case 'help':
			if(($action == 'new') || ($action == 'edit'))
				return '<p>'.i18n::s('Keep your polls as simple to understand as possible.').'</p>';
			return NULL;

		// command to add an item
		case 'new_command':
			return i18n::s('Add a poll');

		// page title
		case 'page_title':

			switch($action) {

			case 'edit':
				return i18n::s('Edit a poll');

			case 'delete':
				return i18n::s('Delete a poll');

			case 'new':
				return i18n::s('Add a poll');

			}
		}

		// no match
		return NULL;
	}

	/**
	 * display the content of one poll
	 *
	 * @see overlays/overlay.php
	 *
	 * @param attributes of the hosting page
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_list_text($host=NULL) {
		global $context;

		// are votes still allowed?
		if(isset($_COOKIE['poll_'.$host['id']])) {
			$enable_votes = FALSE;
			cache::poison();
		} elseif(isset($host['locked']) && ($host['locked'] == 'Y'))
			$enable_votes = FALSE;
		else
			$enable_votes = TRUE;

		// show voting form and results if asked for
		if(isset($host['options']) && preg_match('/\bpoll_with_results\b/i', $host['options']))
			$text = $this->get_text_to_list($host, $enable_votes);

		// else only provide the voting form
		else
			$text = $this->get_text_to_vote($host, $enable_votes);

		$text = Codes::beautify($text);
		return $text;
	}

	/**
	 * display the content of one poll
	 *
	 * @see overlays/overlay.php
	 *
	 * @param attributes of the hosting page
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_view_text($host=NULL) {
		global $context;

		// are votes still allowed?
		if(isset($_COOKIE['poll_'.$host['id']])) {
			$enable_votes = FALSE;
			cache::poison();
		} elseif(isset($host['locked']) && ($host['locked'] == 'Y'))
			$enable_votes = FALSE;
		else
			$enable_votes = TRUE;

		// at the main page, show full content
		$text = $this->get_text_to_view($host, $enable_votes);

		$text = Codes::beautify($text);
		return $text;
	}

	/**
	 * show voting form and raw results
	 *
	 * @param array attributes of the containing page
	 * @param boolean TRUE if votes are enabled, FALSE otherwise
	 * @return some HTML to be inserted into the resulting page
	 */
	function get_text_to_list($host=NULL, $enable_votes=TRUE) {
		global $context;

		// go to the voting page
		if($enable_votes)
			$text = '<form method="post" action="'.$context['url_to_root'].'overlays/polls/vote.php">';

		// if this article cannot be modified anymore, go to the permalink
		else
			$text = '<form method="post" action="'.Articles::get_permalink($host).'">';

		// layout poll elements
		$text .= '<dl class="poll">';

		// list raw results
		if(isset($this->attributes['answers']) && is_array($this->attributes['answers'])) {

			// compute the total number of votes
			$total = 0;
			$maximum = 0;
			foreach($this->attributes['answers'] as $answer) {
				list($label, $count) = $answer;
				$total += $count;
				$maximum = max($maximum, strlen($label));
			}

			// use images either from the skin, or from the polls directory
			if($size = Safe::GetImageSize($context['path_to_root'].$context['skin'].'/images/poll_left.gif'))
				$left_img = '<img src="'.$context['url_to_root'].$context['skin'].'/images/poll_left.gif" '.$size[3].' alt="" />';
			elseif($size = Safe::GetImageSize($context['path_to_root'].'overlays/polls/bar_left.gif'))
				$left_img = '<img src="'.$context['url_to_root'].'overlays/polls/bar_left.gif" '.$size[3].' alt="" />';

			if($size = Safe::GetImageSize($context['path_to_root'].$context['skin'].'/images/poll_main.gif'))
				$main_img = '<img src="'.$context['url_to_root'].$context['skin'].'/images/poll_main.gif" '.$size[3].' alt="" />';
			elseif($size = Safe::GetImageSize($context['path_to_root'].'overlays/polls/bar_main.gif'))
				$main_img = '<img src="'.$context['url_to_root'].'overlays/polls/bar_main.gif" '.$size[3].' alt="" />';

			if($size = Safe::GetImageSize($context['path_to_root'].$context['skin'].'/images/poll_right.gif'))
				$right_img = '<img src="'.$context['url_to_root'].$context['skin'].'/images/poll_right.gif" '.$size[3].' alt="" />';
			elseif($size = Safe::GetImageSize($context['path_to_root'].'overlays/polls/bar_right.gif'))
				$right_img = '<img src="'.$context['url_to_root'].'overlays/polls/bar_right.gif" '.$size[3].' alt="" />';

			// one row per answer
			$index = 1;
			foreach($this->attributes['answers'] as $answer) {

				list($label, $count) = $answer;

				$text .= '<dt>';
				if($enable_votes)
					$text .= '<input type="radio" name="vote" value="'.$index++.'" />';
				$text .= $label.'</dt>';

				if($total > 0) {
					$size = (integer)($count*200/$total)+2;
					$middle = preg_replace('/width=".+?"/i', 'width="'.$size.'"', $main_img);
					$ratio = (integer)($count*100/$total).'%';
				} else {
					$middle = '';
					$ratio = '';
				}
				$text .= '<dd>'.$left_img.$middle.$right_img.' '.$ratio.'<br style="clear: left;" /></dd>'."\n";

			}
		}

		// end of polling layout
		$text .= '</dl>'."\n";

		// votes are allowed
		if($enable_votes) {

			// a button to vote
			$text .= '<p>'.Skin::build_submit_button(i18n::s('Cast your vote'))."\n";

			// a link to see results
			$text .= ' <a '.tag::_class('details').' href="'.Articles::get_permalink($host).'">'.i18n::s('View results').'</a>'."</p>\n";

		// view results only
		} else {

			// a button to view results
			$text .= '<p>'.Skin::build_submit_button(i18n::s('View results'))."</p>\n";

		}

		// end of the form
		$text .= '<input type="hidden" name="id" value="'.$host['id'].'" />'."\n"
				.'</form>'."\n";
		return $text;
	}

	/**
	 * fully describe a poll in its main page
	 *
	 * This also features a voting form, to let people vote from permalinks.
	 *
	 * @link http://www.slashdot.org/ Slashdot has been copied for the layout, and for the bar images as well
	 *
	 * @param array attributes of the containing page
	 * @param boolean TRUE if votes are enabled, FALSE otherwise
	 * @return some HTML to be inserted into the resulting page
	 */
	function get_text_to_view($host=NULL, $enable_votes=TRUE) {
		global $context;

		if(!isset($this->attributes['answers']))
			return '';

		if(!is_array($this->attributes['answers']))
			return '';

		// use images either from the skin, or from the polls directory
		if($size = Safe::GetImageSize($context['path_to_root'].$context['skin'].'/images/poll_left.gif'))
			$left_img = '<img src="'.$context['url_to_root'].$context['skin'].'/images/poll_left.gif" '.$size[3].' alt="" />';
		elseif($size = Safe::GetImageSize($context['path_to_root'].'overlays/polls/bar_left.gif'))
			$left_img = '<img src="'.$context['url_to_root'].'overlays/polls/bar_left.gif" '.$size[3].' alt="" />';

		if($size = Safe::GetImageSize($context['path_to_root'].$context['skin'].'/images/poll_main.gif'))
			$main_img = '<img src="'.$context['url_to_root'].$context['skin'].'/images/poll_main.gif" '.$size[3].' alt="" />';
		elseif($size = Safe::GetImageSize($context['path_to_root'].'overlays/polls/bar_main.gif'))
			$main_img = '<img src="'.$context['url_to_root'].'overlays/polls/bar_main.gif" '.$size[3].' alt="" />';

		if($size = Safe::GetImageSize($context['path_to_root'].$context['skin'].'/images/poll_right.gif'))
			$right_img = '<img src="'.$context['url_to_root'].$context['skin'].'/images/poll_right.gif" '.$size[3].' alt="" />';
		elseif($size = Safe::GetImageSize($context['path_to_root'].'overlays/polls/bar_right.gif'))
			$right_img = '<img src="'.$context['url_to_root'].'overlays/polls/bar_right.gif" '.$size[3].' alt="" />';

		// compute totals
		$total = 0;
		$maximum = 0;
		foreach($this->attributes['answers'] as $answer) {
			list($label, $count) = $answer;
			$total += $count;
			$maximum = max($maximum, strlen($label));
		}

		// empty form when votes are disallowed
		$text = '<form method="post" action="'.$context['url_to_root'].'overlays/polls/vote.php">';

		// layout poll elements
		$text .= '<dl class="poll">';

		// one row per answer
		$index = 1;
		foreach($this->attributes['answers'] as $answer) {

			list($label, $count) = $answer;

			$text .= '<dt>';
			if($enable_votes)
				$text .= '<input type="radio" name="vote" value="'.$index++.'" /> ';
			$text .= $label.'</dt>'."\n";

			$text .= '<dd>';
			if($total > 0)
				$size = (integer)($count*350/$total)+2;
			else
				$size = 2;
			$text .= $left_img.preg_replace('/width=".+?"/i', 'width="'.$size.'"', $main_img).$right_img
				.' '.number_format($count);
			if($total > 0)
				$text .= ' / <b>'.(integer)($count*100/$total).'%</b>';
			$text .= '<br style="clear: left;" /></dd>'."\n";

		}

		$text .= '</dl>'."\n";

		// votes are allowed
		if($enable_votes) {

			$text .= '<p>';

			// a button to vote
			$text .= Skin::build_submit_button(i18n::s('Cast your vote'));

			// summarize votes
			if($total > 1) {
				$text .= ' '.sprintf(i18n::s('%d votes up to now'), $total);
			}

			$text .= '</p>'."\n";
		}

		// end of the form
		$text .= '<input type="hidden" name="id" value="'.$host['id'].'" />'.'</form>'."\n";

		return $text;
	}

	/**
	 * get the text to make people vote
	 *
	 * @param array attributes of the containing page
	 * @param boolean TRUE if votes are enabled, FALSE otherwise
	 * @return some HTML to be inserted into the resulting page
	 */
	function get_text_to_vote($host=NULL, $enable_votes=TRUE) {
		global $context;

		$text = '';

		// go to the voting page
		if($enable_votes)
			$text = '<form method="post" action="'.$context['url_to_root'].'overlays/polls/vote.php">';

		// if this article cannot be modified anymore, go to the permalink
		else
			$text = '<form method="post" action="'.Articles::get_permalink($host).'">';

		$text .= '<p class="poll">';

		// list answers
		if(isset($this->attributes['answers']) && is_array($this->attributes['answers'])) {
			$index = 1;
			foreach($this->attributes['answers'] as $answer) {
				list($label, $count) = $answer;
				if($enable_votes)
					$text .= '<input type="radio" name="vote" value="'.$index.'" /> ';
				$text .= $label.BR;
				$index++;
			}
		}

		// votes are allowed
		if($enable_votes) {

			// a button to vote
			$text .= Skin::build_submit_button(i18n::s('Vote'))."\n";

			// a link to see results
			$text .= ' <a '.tag::_class('details').' href="'.Articles::get_permalink($host).'">'.i18n::s('View results').'</a>'."\n";

		// display results only
		} else {

			// a button to view results
			$text .= Skin::build_submit_button(i18n::s('View results'))."\n";

		}

		// end of the form
		$text .= '<input type="hidden" name="id" value="'.$host['id'].'" />'."\n"
				.'</p></form>'."\n";

		return $text;
	}

	/**
	 * retrieve the content of one modified overlay
	 *
	 * @see overlays/overlay.php
	 *
	 * @param the fields as filled by the end user
	 */
	function parse_fields($fields) {

		// reset everything
		$this->attributes['answers'] = array();

		// parse provided answers
		if(isset($fields['answer_texts']) && is_array($fields['answer_texts'])) {
			for($index = 0; $index < count($fields['answer_texts']); $index++) {
				$text = $fields['answer_texts'][$index];
				$count = $fields['answer_counts'][$index];

				// append one answer
				if($text)
					$this->attributes['answers'][] = array($text, $count);
			}
		}

	}

}

?>