<?php

/**
 * Layout for header menu
 *
 * @author Alexis Raimbault
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Layout_as_header_menu extends Layout_interface {

	function layout($result) {
		global $context;

		$text = '';

		// empty list
		if(empty($result))
			return $text;

		$text .= '<ul class="header-menu-list">'.PHP_EOL;

		foreach($result as $section_url => $item_data) {
			// Extract section ID from URL (e.g., /section-2-title -> 2)
			if (preg_match('/section-(\d+)-/', $section_url, $matches)) {
				$section_id = $matches[1];
			} else {
				continue; // Skip if ID cannot be parsed
			}

			// Get the full Section data using its ID
			$section_data = Sections::get($section_id);

			if (!is_array($section_data)) {
				continue; // Skip if section data cannot be retrieved or is not an array
			}

			// Create a Section object from the data
			$entity = new Section($section_data);

			$url = $entity->get_permalink();
			$title = $entity->get_title();
			$icon_url = $entity->get_thumbnail_url(); // Get thumbnail URL from the Section object

			$text .= '<li class="header-menu-item">'.PHP_EOL;
			$text .= '<a href="'.$url.'">'.PHP_EOL;
				if ($icon_url) {
					$text .= '<img src="'.$icon_url.'" alt="'.encode_field($title).'" class="header-menu-icon" />'.PHP_EOL;
				} else {
					// Fallback if no thumbnail is available
					$text .= '<img src="'.$context['url_to_root'].$context['skin'].'/images/default-icon.png" alt="'.encode_field($title).'" class="header-menu-icon" />'.PHP_EOL; // You might need to create a default-icon.png
				}
			$text .= '<span class="header-menu-text">'.encode_field($title).'</span>'.PHP_EOL;
			$text .= '</a>'.PHP_EOL;
			$text .= '</li>'.PHP_EOL;
		}

		$text .= '</ul>'.PHP_EOL;

		$this->load_scripts_n_styles();
		return $text;
	}
}
