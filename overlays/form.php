<?php
/**
 * store values captured in a form
 *
 * @see overlays/overlay.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Form extends Overlay {

	/**
	 * export an overlay as XML
	 *
	 * @return some XML to be inserted into the resulting page
	 */
	function export() {
		$text =  ' <overlay class="form">'."\n";
		foreach($this->attributes as $name => $field) {
			if(!is_array($field))
				continue;
			foreach($field as $name => $value) {
				if(is_string($value))
					$text .= "\t".' <'.$name.'>'.$value.'</'.$name.'>'."\n";
				elseif(is_array($value)) {
					$text .= "\t".' <'.$name.'>'."\n";
					foreach($value as $option => $label)
						$text .= "\t\t".'<'.$option.'>'.$label.'</'.$option.'>'."\n";
					$text .= "\t".' </'.$name.'>'."\n";
				}
			}
		}
		$text .= ' </overlay>'."\n";

		return $text;
	}

	/**
	 * allow for data export
	 *
	 * @see overlays/overlay.php
	 *
	 * @param array the hosting record
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_extra_text($host=NULL) {
		global $context;

		// a line of commands, but only to authenticated empowered surfers
		if(Surfer::is_logged() && Surfer::is_empowered()) {
			$menu = array();
			$menu[] = Skin::build_link($this->get_url($host['id'], 'fetch_as_csv'), i18n::s('CSV'), 'button');
			$menu[] = Skin::build_link(Articles::get_url($host['id'], 'export'), i18n::s('Export to XML'), 'span');
			$text = Skin::build_box(i18n::s('Export data'), Skin::finalize_list($menu, 'menu_bar'), 'overlay');
		} else
			$text = '';
		return $text;

	}

	/**
	 * display the content of one form
	 *
	 * @see overlays/overlay.php
	 *
	 * @param array the hosting record
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_view_text($host=NULL) {
		$text = '';
		return $text;
	}

	function get_url($id, $action) {
		global $context;

		// fetch as csv
		if($action == 'fetch_as_csv') {
			if($context['with_friendly_urls'] == 'Y')
				return 'overlays/forms/fetch_as_csv.php/'.rawurlencode($id);
			elseif($context['with_friendly_urls'] == 'R')
				return 'overlays/forms/fetch_as_csv.php?id='.urlencode($id);
			else
				return 'overlays/forms/fetch_as_csv.php?id='.urlencode($id);
		}

		exit('unknown action');
	}

	/**
	 * store form content permanently
	 *
	 * @see forms/view.php
	 * @param the fields as filled by the end user
	 */
	function parse_once($fields) {
		foreach($fields as $name => $value) {
			$this->attributes[$name] = $value;
		}
	}

}

?>