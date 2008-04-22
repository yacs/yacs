<?php
/**
 * a place holder to select another overlay
 *
 * @see overlays/overlay.php
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Select extends Overlay {

	/**
	 * build the list of fields for one overlay
	 *
	 * @see overlays/overlay.php
	 *
	 * @param the hosting attributes
	 * @return a list of ($label, $input, $hint)
	 */
	function get_fields($host) {
		global $context;

		// list overlays available on this system
		$label = i18n::s('Select an overlay');
		$input = '<select name="overlay_type">';
		if ($dir = Safe::opendir($context['path_to_root'].'overlays')) {

			// every php script is an overlay, except index.php, overlay.php, and hooks
			while(($file = Safe::readdir($dir)) !== FALSE) {
				if($file == '.' || $file == '..' || is_dir($context['path_to_root'].'overlays/'.$file))
					continue;
				if($file == 'index.php')
					continue;
				if($file == 'overlay.php')
					continue;
				if(preg_match('/hook\.php$/i', $file))
					continue;
				if(!preg_match('/(.*)\.php$/i', $file, $matches))
					continue;
				$overlays[] = $matches[1];
			}
			Safe::closedir($dir);
			if(@count($overlays)) {
				sort($overlays);
				foreach($overlays as $overlay)
					$input .= '<option value="'.$overlay.'">'.$overlay."</option>\n";
			}
		}
		$input .= '</select>';
		$fields[] = array($label, $input);

		return $fields;
	}

	/**
	 * display the content of one recipe
	 *
	 * @see overlays/overlay.php
	 *
	 * @param array the hosting record
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_view_text($host=NULL) {
		global $context;

		// just a reminder message
		$text = '<p>'.i18n::s('Edit this page to select some overlay.')."</p>\n";

		return $text;
	}

	/**
	 * retrieve the content of one modified overlay
	 *
	 * @see overlays/overlay.php
	 *
	 * @param the fields as filled by the end user
	 * @return the updated fields
	 */
	function parse_fields($fields) {
		$this->attributes['people'] = $fields['people'];
		$this->attributes['preparation_time'] = $fields['preparation_time'];
		$this->attributes['cooking_time'] = $fields['cooking_time'];
		$this->attributes['ingredients'] = $fields['ingredients'];

		return $this->attributes;
	}

}

?>