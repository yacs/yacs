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
	function &layout(&$result) {
		global $context;

		// we return some text
		$text = '';

		// empty list
		if(!SQL::count($result))
			return $text;

		// flag users updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// the script used to check all items at once
		$text .= JS_PREFIX
			.'function cascade_selection_to_all_user_rows(handle) {'."\n"
			.'	var checkers = $$("div#users_as_mail_panel input[type=\'checkbox\'].row_selector");'."\n"
			.'	for(var index=0; index < checkers.length; index++) {'."\n"
			.'		checkers[index].checked = handle.checked;'."\n"
			.'	}'."\n"
			.'}'."\n"
			.JS_SUFFIX."\n";

		// div prefix
		$text .= '<div id="users_as_mail_panel">';

		// process all items in the list
		include_once $context['path_to_root'].'overlays/overlay.php';

		$count = 0;
		while($item =& SQL::fetch($result)) {
			if(!$item['email'])
				continue;

			// do not write to myself
			if($item['id'] == Surfer::get_id())
				continue;

			// get the related overlay, if any
			$overlay = Overlay::load($item);

			// column to select the row
			$text .= '<input type="checkbox" name="selected_users[]" class="row_selector" value="'.encode_field($item['email']).'" checked="checked" />';

			// signal restricted and private users
			if($item['active'] == 'N')
				$text .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$text .= RESTRICTED_FLAG;

			// the url to view this item
			$url =& Users::get_permalink($item);

			// use the title to label the link
			if(is_object($overlay) && is_callable(array($overlay, 'get_live_title')))
				$title = $overlay->get_live_title($item);
			else
				$title = ucfirst(Codes::beautify(strip_tags($item['full_name'], '<br><div><img><p><span>')));

			// link to this page
			$text .= Skin::build_link($url, $title, 'user');

			// the introductory text
			if($item['introduction'])
				$text .= ' - '.Codes::beautify_introduction($item['introduction']);

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

		// select all rows
		$text .= '<input type="checkbox" class="row_selector" onchange="cascade_selection_to_all_user_rows(this);" checked="checked" /> '.i18n::s('Select all/none');

		// div suffix
		$text .= '</div>';

		// end of processing
		SQL::free($result);
		return $text;
	}

}

?>