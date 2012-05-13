<?php
/**
 * prepare bulk mail messages
 *
 * @see users/users.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_users_as_mail extends Layout_interface {

	/**
	 * list users
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	 *
	 * @see skins/layout.php
	**/
	function layout($result) {
		global $context;

		// we return some text
		$text = '';

		// empty list
		if(!$count = SQL::count($result))
			return $text;

		// allow for several lists in the same page
		static $serial;
		if(isset($serial))
			$serial++;
		else
			$serial = 1;

		// don't blast too many people
		if($count > 100)
			$checked = '';
		elseif(isset($this->layout_variant) && ($this->layout_variant == 'unchecked'))
			$checked = '';
		else
			$checked = ' checked="checked"';

		// div prefix
		$text .= '<div id="users_as_mail_panel_'.$serial.'">';

		// process all items in the list
		$count = 0;
		while($item = SQL::fetch($result)) {

			// we need some address
			if(!$item['email'])
				continue;

			// do not write to myself
			if($item['id'] == Surfer::get_id())
				continue;

			// get the related overlay, if any
			$overlay = Overlay::load($item, 'user:'.$item['id']);

			// column to select the row
			$text .= '<input type="checkbox" name="selected_users[]" class="row_selector" value="'.encode_field($item['email']).'"'.$checked.' />';

			// signal restricted and private users
			if($item['active'] == 'N')
				$text .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$text .= RESTRICTED_FLAG;

			// the url to view this item
			$url = Users::get_permalink($item);

			// use the title to label the link
			if(is_object($overlay))
				$title = Codes::beautify_title($overlay->get_text('title', $item));
			else
				$title = Codes::beautify_title($item['full_name']);

			// sanity check
			if(!$title)
				$title = $item['nick_name'];

			// link to this page
			$text .= Skin::build_link($url, $title, 'user');

			// the introductory text
			if($item['introduction'])
				$text .= '<span class="tiny"> - '.Codes::beautify_introduction($item['introduction']).'</span>';

			// insert overlay data, if any
			if(is_object($overlay))
				$text .= $overlay->get_text('list', $item);

			// display all tags
			if($item['tags'])
				$text .= ' <span class="tags">'.Skin::build_tags($item['tags'], 'user:'.$item['id']).'</span>';

			// append the row
			$text .= BR;
			$count++;
		}

		// allow to select/deslect multiple rows at once
		$text .= '<input type="checkbox" class="row_selector" onchange="check_user_as_mail_panel_'.$serial.'(\'div#users_as_mail_panel_'.$serial.'\', this);"'.$checked.' /> '.i18n::s('Select all/none');

		// the script used to check all items at once
		$text .= JS_PREFIX
			.'function check_user_as_mail_panel_'.$serial.'(scope, handle) {'."\n"
			.'	$(scope + " input[type=\'checkbox\'].row_selector").each('."\n"
			.'		function() { $(this).attr("checked", $(handle).is(":checked"));}'."\n"
			.'	);'."\n"
			.'}'."\n"
			.JS_SUFFIX."\n";

		// div suffix
		$text .= '</div>';

		// no valid account has been found
		if(!$count)
			$text = '';

		// end of processing
		SQL::free($result);
		return $text;
	}

}

?>
